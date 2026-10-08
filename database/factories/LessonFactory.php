<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lesson> */
class LessonFactory extends Factory
{
    public function definition(): array
    {
        return ['course_id' => Course::factory(), 'title' => fake()->sentence(4), 'position' => 1, 'minutes' => 8, 'body' => '## A thoughtful lesson\nRead, reflect, and practice.', 'quiz' => ['question' => 'Which choice is right?', 'options' => ['First', 'Second', 'Third'], 'correct' => 1]];
    }
}
