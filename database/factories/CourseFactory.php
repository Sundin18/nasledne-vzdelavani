<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(3, 60))->setTime(9, 0);

        return [
            'title' => fake()->sentence(4),
            'categories' => fake()->randomElements(config('courses.categories'), 2),
            'starts_at' => $start,
            'ends_at' => $start->addHours(6),
            'place' => fake()->city(),
            'capacity' => 20,
            'content' => '<p>'.fake()->paragraph().'</p>',
        ];
    }

    /**
     * Indicate that the course has already taken place.
     */
    public function past(): static
    {
        return $this->state(function () {
            $start = now()->subDays(fake()->numberBetween(3, 60))->setTime(9, 0);

            return [
                'starts_at' => $start,
                'ends_at' => $start->addHours(6),
            ];
        });
    }
}
