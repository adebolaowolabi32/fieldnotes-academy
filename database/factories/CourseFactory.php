<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Course> */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'title' => fake()->unique()->sentence(4), 'slug' => fake()->unique()->slug(), 'description' => fake()->paragraph(), 'category' => 'Creative thinking', 'level' => 'Beginner', 'published' => true];
    }
}
