<?php

namespace Tests\Feature\Courses;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MyCoursesTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_lists_only_the_users_upcoming_registrations(): void
    {
        $user = User::factory()->create();
        $mine = CourseRegistration::factory()->for($user)->create();
        $other = CourseRegistration::factory()->create();

        $this->actingAs($user);

        $this->get(route('courses.mine'))
            ->assertOk()
            ->assertSee($mine->course->name)
            ->assertDontSee($other->course->name);
    }

    public function test_past_courses_offer_a_certificate_only_when_attended(): void
    {
        $user = User::factory()->create();
        $attended = CourseRegistration::factory()->attended()->for($user)
            ->for(Course::factory()->past()->state(['name' => 'Attended course']))->create();
        CourseRegistration::factory()->for($user)
            ->for(Course::factory()->past()->state(['name' => 'Missed course']))->create();

        $this->actingAs($user);

        Livewire::test('pages::courses.mine')
            ->set('show', 'past')
            ->assertSee('Attended course')
            ->assertSee('Missed course')
            ->assertSee(route('certificates.show', $attended))
            ->assertSee('Účast nepotvrzena');
    }

    public function test_past_courses_are_paginated_by_thirty(): void
    {
        $user = User::factory()->create();

        Course::factory()->past()->count(31)->sequence(
            fn ($sequence) => [
                'name' => 'Past course '.($sequence->index + 1),
                'start' => now()->subDays($sequence->index + 1),
                'end' => now()->subDays($sequence->index + 1)->addHours(4),
            ],
        )->create()->each(fn (Course $course) => CourseRegistration::factory()->for($course)->for($user)->create());

        $this->actingAs($user);

        Livewire::test('pages::courses.mine')
            ->set('show', 'past')
            ->assertSee('Proběhlé (31)')
            ->assertSee('Past course 30')
            ->assertDontSee('Past course 31')
            ->call('gotoPage', 2)
            ->assertSee('Past course 31');
    }

    public function test_user_can_unregister_from_my_courses(): void
    {
        $user = User::factory()->create();
        $registration = CourseRegistration::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test('pages::courses.mine')->call('unregister', $registration->id);

        $this->assertModelMissing($registration);
    }

    public function test_user_cannot_unregister_someone_else(): void
    {
        $registration = CourseRegistration::factory()->create();

        $this->actingAs(User::factory()->create());

        Livewire::test('pages::courses.mine')
            ->call('unregister', $registration->id)
            ->assertNotFound();

        $this->assertModelExists($registration);
    }
}
