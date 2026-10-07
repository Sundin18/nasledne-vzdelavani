<?php

namespace Tests\Feature\Courses;

use App\Mail\CourseRegistrationCreated;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class CourseListTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('courses.index'))->assertRedirect(route('login'));
    }

    public function test_users_see_only_upcoming_courses_nearest_first(): void
    {
        $later = Course::factory()->create(['name' => 'Later course', 'start' => now()->addDays(20), 'end' => now()->addDays(20)->addHours(4)]);
        $sooner = Course::factory()->create(['name' => 'Sooner course', 'start' => now()->addDays(2), 'end' => now()->addDays(2)->addHours(4)]);
        Course::factory()->past()->create(['name' => 'Past course']);

        $this->actingAs(User::factory()->create());

        $this->get(route('courses.index'))
            ->assertOk()
            ->assertSeeInOrder([$sooner->name, $later->name])
            ->assertDontSee('Past course');
    }

    public function test_users_cannot_switch_to_past_courses(): void
    {
        Course::factory()->past()->create(['name' => 'Past course']);

        $this->actingAs(User::factory()->create());

        Livewire::test('pages::courses.index')
            ->set('show', 'past')
            ->assertDontSee('Past course');
    }

    public function test_admins_can_see_past_courses(): void
    {
        Course::factory()->past()->create(['name' => 'Past course']);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::courses.index')
            ->set('show', 'past')
            ->assertSee('Past course');
    }

    public function test_past_courses_are_paginated_by_thirty(): void
    {
        Course::factory()->past()->count(31)->sequence(
            fn ($sequence) => [
                'name' => 'Past course '.($sequence->index + 1),
                'start' => now()->subDays($sequence->index + 1),
                'end' => now()->subDays($sequence->index + 1)->addHours(4),
            ],
        )->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::courses.index')
            ->set('show', 'past')
            ->assertSee('Past course 30')
            ->assertDontSee('Past course 31')
            ->call('gotoPage', 2)
            ->assertSee('Past course 31')
            ->assertDontSee('Past course 30');
    }

    public function test_user_can_register_for_a_course_and_the_organizer_is_notified(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $course = Course::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::courses.index')->call('register', $course->id);

        $this->assertDatabaseHas('course_registrations', [
            'course_id' => $course->id,
            'user_id' => $user->id,
            'attended' => false,
        ]);

        Mail::assertQueued(CourseRegistrationCreated::class, function (CourseRegistrationCreated $mail) use ($user, $course) {
            return $mail->hasTo(config('courses.notification_email'))
                && $mail->registration->user->is($user)
                && $mail->registration->course->is($course);
        });
    }

    public function test_notification_email_contains_participant_and_course_details(): void
    {
        $registration = CourseRegistration::factory()->create();

        $mail = new CourseRegistrationCreated($registration);

        $mail->assertSeeInHtml($registration->user->name);
        $mail->assertSeeInHtml($registration->user->email);
        $mail->assertSeeInHtml($registration->course->name);
        $mail->assertSeeInHtml($registration->course->start->format('j. n. Y'));
    }

    public function test_user_cannot_register_for_a_full_course(): void
    {
        Mail::fake();

        $course = Course::factory()->create(['capacity' => 1]);
        CourseRegistration::factory()->for($course)->create();

        $this->actingAs($user = User::factory()->create());

        Livewire::test('pages::courses.index')->call('register', $course->id);

        $this->assertDatabaseMissing('course_registrations', ['course_id' => $course->id, 'user_id' => $user->id]);
        Mail::assertNothingQueued();
    }

    public function test_user_cannot_register_twice(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $course = Course::factory()->create();
        CourseRegistration::factory()->for($course)->for($user)->create();

        $this->actingAs($user);

        Livewire::test('pages::courses.index')->call('register', $course->id);

        $this->assertSame(1, $course->registrations()->count());
        Mail::assertNothingQueued();
    }

    public function test_user_cannot_register_for_a_course_that_has_started(): void
    {
        Mail::fake();

        $course = Course::factory()->past()->create();

        $this->actingAs($user = User::factory()->create());

        Livewire::test('pages::courses.index')->call('register', $course->id);

        $this->assertDatabaseMissing('course_registrations', ['course_id' => $course->id, 'user_id' => $user->id]);
    }

    public function test_user_can_unregister_from_an_upcoming_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        CourseRegistration::factory()->for($course)->for($user)->create();

        $this->actingAs($user);

        Livewire::test('pages::courses.index')->call('unregister', $course->id);

        $this->assertDatabaseMissing('course_registrations', ['course_id' => $course->id, 'user_id' => $user->id]);
    }

    public function test_only_admins_can_delete_courses(): void
    {
        $course = Course::factory()->create();

        $this->actingAs(User::factory()->create());

        Livewire::test('pages::courses.index')
            ->call('confirmDelete', $course->id)
            ->assertForbidden();

        $this->assertModelExists($course);
    }

    public function test_admin_can_delete_a_course(): void
    {
        $course = Course::factory()->create();
        CourseRegistration::factory()->for($course)->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::courses.index')
            ->call('confirmDelete', $course->id)
            ->call('deleteCourse');

        $this->assertModelMissing($course);
        $this->assertDatabaseCount('course_registrations', 0);
    }
}
