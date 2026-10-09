<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ImportBatch> */
class ImportBatchFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'course_id' => Course::factory(), 'path' => 'imports/test.csv', 'errors' => [], 'total' => 1, 'cursor' => 0, 'status' => 'preview'];
    }
}
