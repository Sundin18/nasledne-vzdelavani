<?php

namespace Tests\Feature\Courses;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_download_their_certificate_after_attending(): void
    {
        $registration = CourseRegistration::factory()->attended()
            ->for(Course::factory()->past())->create();

        $this->actingAs($registration->user)
            ->get(route('certificates.show', $registration))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_certificate_is_not_available_without_attendance(): void
    {
        $registration = CourseRegistration::factory()
            ->for(Course::factory()->past())->create();

        $this->actingAs($registration->user)
            ->get(route('certificates.show', $registration))
            ->assertForbidden();
    }

    public function test_users_cannot_download_someone_elses_certificate(): void
    {
        $registration = CourseRegistration::factory()->attended()
            ->for(Course::factory()->past())->create();

        $this->actingAs(User::factory()->create())
            ->get(route('certificates.show', $registration))
            ->assertForbidden();
    }

    public function test_admin_can_download_any_certificate(): void
    {
        $registration = CourseRegistration::factory()->attended()
            ->for(Course::factory()->past())->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('certificates.show', $registration))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_can_download_all_certificates_of_a_course(): void
    {
        $course = Course::factory()->past()->create();
        CourseRegistration::factory()->count(2)->attended()->for($course)->create();
        CourseRegistration::factory()->for($course)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.courses.certificates', $course))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_bulk_certificates_require_at_least_one_attendee(): void
    {
        $course = Course::factory()->past()->create();
        CourseRegistration::factory()->for($course)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.courses.certificates', $course))
            ->assertNotFound();
    }

    public function test_admin_can_download_the_attendance_sheet(): void
    {
        $course = Course::factory()->create();
        CourseRegistration::factory()->count(2)->for($course)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.courses.attendance-sheet', $course))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
