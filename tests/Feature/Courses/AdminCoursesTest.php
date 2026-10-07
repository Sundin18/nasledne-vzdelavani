<?php

namespace Tests\Feature\Courses;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCoursesTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_users_cannot_access_admin_pages(): void
    {
        $course = Course::factory()->create();

        $this->actingAs(User::factory()->create());

        $this->get(route('admin.courses.create'))->assertForbidden();
        $this->get(route('admin.courses.show', $course))->assertForbidden();
        $this->get(route('admin.courses.edit', $course))->assertForbidden();
        $this->get(route('admin.courses.certificates', $course))->assertForbidden();
        $this->get(route('admin.courses.attendance-sheet', $course))->assertForbidden();
    }

    public function test_admin_can_open_admin_pages(): void
    {
        $course = Course::factory()->create();
        CourseRegistration::factory()->for($course)->create();

        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.courses.create'))->assertOk();
        $this->get(route('admin.courses.show', $course))->assertOk()->assertSee($course->name);
        $this->get(route('admin.courses.edit', $course))->assertOk();
    }

    public function test_admin_can_create_a_course(): void
    {
        $admin = User::factory()->admin()->create();
        $general = Category::where('name', 'Obecné')->firstOrFail();
        $credit = Category::where('name', 'Spotřebitelské úvěry')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test('pages::admin.courses.form')
            ->set('name', 'AML a ochrana spotřebitele')
            ->set('categories', [(string) $general->id, (string) $credit->id])
            ->set('start', '2030-11-19T09:00')
            ->set('end', '2030-11-19T16:00')
            ->set('place', 'Praha')
            ->set('capacity', 25)
            ->set('content', '<p>Program kurzu</p>')
            ->call('save')
            ->assertHasNoErrors();

        $course = Course::sole();

        $this->assertSame('AML a ochrana spotřebitele', $course->name);
        $this->assertSame('2030-11-19 09:00', $course->start->format('Y-m-d H:i'));
        $this->assertSame('2030-11-19 16:00', $course->end->format('Y-m-d H:i'));
        $this->assertSame('Praha', $course->place);
        $this->assertSame(25, $course->capacity);
        $this->assertSame($admin->id, $course->user_id);
        $this->assertEqualsCanonicalizing([$general->id, $credit->id], $course->categories->pluck('id')->all());
    }

    public function test_course_validation(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.courses.form')
            ->set('name', '')
            ->set('categories', ['999999'])
            ->set('start', '2030-11-19T09:00')
            ->set('end', '2030-11-19T08:00')
            ->set('place', 'Praha')
            ->set('capacity', 0)
            ->call('save')
            ->assertHasErrors(['name', 'categories.0', 'end', 'capacity']);

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_admin_can_update_a_course(): void
    {
        $course = Course::factory()->create(['name' => 'Old name']);
        $course->categories()->sync([Category::where('name', 'Obecné')->firstOrFail()->id]);
        $investments = Category::where('name', 'Investice')->firstOrFail();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.courses.form', ['course' => $course])
            ->assertSet('name', 'Old name')
            ->set('name', 'New name')
            ->set('categories', [(string) $investments->id])
            ->call('save')
            ->assertHasNoErrors();

        $course->refresh();

        $this->assertSame('New name', $course->name);
        $this->assertSame([$investments->id], $course->categories->pluck('id')->all());
    }

    public function test_capacity_cannot_drop_below_registered_participants(): void
    {
        $course = Course::factory()->create(['capacity' => 5]);
        CourseRegistration::factory()->count(3)->for($course)->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.courses.form', ['course' => $course])
            ->set('capacity', 2)
            ->call('save')
            ->assertHasErrors(['capacity']);
    }

    public function test_admin_can_toggle_attendance(): void
    {
        $course = Course::factory()->past()->create();
        $registration = CourseRegistration::factory()->for($course)->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.courses.show', ['course' => $course])
            ->call('toggleAttendance', $registration->id);

        $this->assertTrue($registration->refresh()->attended);
    }

    public function test_admin_can_mark_everyone_as_attended(): void
    {
        $course = Course::factory()->past()->create();
        CourseRegistration::factory()->count(3)->for($course)->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.courses.show', ['course' => $course])
            ->call('markAllAttended');

        $this->assertSame(3, $course->registrations()->where('attended', true)->count());
    }

    public function test_attendance_cannot_be_changed_on_another_course(): void
    {
        $course = Course::factory()->past()->create();
        $foreign = CourseRegistration::factory()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.courses.show', ['course' => $course])
            ->call('toggleAttendance', $foreign->id)
            ->assertNotFound();

        $this->assertFalse($foreign->refresh()->attended);
    }

    public function test_admin_can_delete_a_course_from_its_detail(): void
    {
        $course = Course::factory()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.courses.show', ['course' => $course])
            ->call('deleteCourse')
            ->assertRedirect(route('courses.index'));

        $this->assertModelMissing($course);
    }
}
