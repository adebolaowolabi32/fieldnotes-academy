<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QuizAttempt> */
class QuizAttemptFactory extends Factory
{
    public function definition(): array
    {
        return ['enrollment_id' => Enrollment::factory(), 'lesson_id' => Lesson::factory(), 'answer' => 0, 'passed' => false];
    }
}
