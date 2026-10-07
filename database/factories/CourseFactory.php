<?php

namespace Database\Factories;

use App\Models\Category;
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
            'start' => $start,
            'end' => $start->addHours(6),
            'name' => fake()->sentence(4),
            'place' => fake()->city(),
            'capacity' => 20,
            'content' => '<p>'.fake()->paragraph().'</p>',
            'user_id' => null,
        ];
    }

    /**
     * Attach up to two existing categories to every created course.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Course $course) {
            $course->categories()->attach(
                Category::query()->inRandomOrder()->limit(2)->pluck('id'),
            );
        });
    }

    /**
     * Indicate that the course has already taken place.
     */
    public function past(): static
    {
        return $this->state(function () {
            $start = now()->subDays(fake()->numberBetween(3, 60))->setTime(9, 0);

            return [
                'start' => $start,
                'end' => $start->addHours(6),
            ];
        });
    }
}
