<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseRegistration>
 */
class CourseRegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'user_id' => User::factory(),
            'attended' => false,
        ];
    }

    /**
     * Indicate that the participant attended the course.
     */
    public function attended(): static
    {
        return $this->state(fn () => ['attended' => true]);
    }
}
