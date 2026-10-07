<?php

namespace Tests\Feature\Courses;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_manage_categories(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.categories.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Obecné');
    }

    public function test_admin_can_create_a_category(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.categories.index')
            ->set('name', 'Penzijní spoření')
            ->call('createCategory')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', ['name' => 'Penzijní spoření', 'deleted_at' => null]);
    }

    public function test_category_names_must_be_unique_among_active_categories(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.categories.index')
            ->set('name', 'Obecné')
            ->call('createCategory')
            ->assertHasErrors(['name' => 'unique']);
    }

    public function test_admin_can_rename_a_category(): void
    {
        $category = Category::factory()->create(['name' => 'Old']);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.categories.index')
            ->call('editCategory', $category->id)
            ->assertSet('editName', 'Old')
            ->set('editName', 'New')
            ->call('updateCategory')
            ->assertHasNoErrors();

        $this->assertSame('New', $category->refresh()->name);
    }

    public function test_deleting_a_category_is_soft_and_keeps_it_on_existing_courses(): void
    {
        $category = Category::factory()->create(['name' => 'Retired']);
        $course = Course::factory()->create();
        $course->categories()->sync([$category->id]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.categories.index')
            ->call('confirmDelete', $category->id)
            ->call('deleteCategory');

        $this->assertSoftDeleted($category);
        $this->assertDatabaseHas('category_course', ['category_id' => $category->id, 'course_id' => $course->id]);
        $this->assertSame(['Retired'], $course->refresh()->categories->pluck('name')->all());
    }

    public function test_deleted_categories_are_not_offered_for_new_courses(): void
    {
        $category = Category::factory()->create(['name' => 'Retired']);
        $category->delete();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.courses.form')
            ->assertDontSee('Retired')
            ->set('name', 'Kurz')
            ->set('categories', [(string) $category->id])
            ->set('start', '2030-11-19T09:00')
            ->set('end', '2030-11-19T16:00')
            ->set('place', 'Praha')
            ->set('capacity', 10)
            ->call('save')
            ->assertHasErrors(['categories.0']);
    }

    public function test_editing_a_course_keeps_its_deleted_category_available(): void
    {
        $category = Category::factory()->create(['name' => 'Retired']);
        $course = Course::factory()->create();
        $course->categories()->sync([$category->id]);
        $category->delete();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.courses.form', ['course' => $course])
            ->assertSee('Retired (smazaná)')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([$category->id], $course->refresh()->categories->pluck('id')->all());
    }

    public function test_admin_can_restore_a_deleted_category(): void
    {
        $category = Category::factory()->create();
        $category->delete();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.categories.index')
            ->set('show', 'trashed')
            ->call('restoreCategory', $category->id);

        $this->assertNotSoftDeleted($category);
    }
}
