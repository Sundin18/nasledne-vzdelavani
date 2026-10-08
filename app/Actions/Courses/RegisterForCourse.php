<?php

namespace App\Actions\Courses;

use App\Mail\CourseRegistrationCreated;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class RegisterForCourse
{
    /**
     * Sign the user up for the course and notify the organizer in the background.
     *
     * @throws ValidationException
     */
    public function handle(User $user, Course $course): CourseRegistration
    {
        $registration = DB::transaction(function () use ($user, $course) {
            $course = Course::query()->lockForUpdate()->findOrFail($course->id);

            if ($course->hasStarted()) {
                throw ValidationException::withMessages(['course' => 'Na kurz, který už začal, se nelze přihlásit.']);
            }

            if ($course->registrations()->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages(['course' => 'Na tento kurz jste už přihlášeni.']);
            }

            if ($course->registrations()->count() >= $course->capacity) {
                throw ValidationException::withMessages(['course' => 'Kurz je již obsazený.']);
            }

            return $course->registrations()->create(['user_id' => $user->id]);
        });

        Mail::to(config('courses.notification_email'))
            ->queue(new CourseRegistrationCreated($registration));

        return $registration;
    }
}
