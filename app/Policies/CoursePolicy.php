<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function create(User $user): bool
    {
        return (bool) $user->is_instructor;
    }

    public function update(User $user, Course $course): bool
    {
        return $user->is_instructor && $course->user_id === $user->id;
    }
}
