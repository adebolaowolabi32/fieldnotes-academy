<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->boolean('is_instructor')->default(false));
        Schema::create('courses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('description');
            $t->string('category');
            $t->string('level')->default('Beginner');
            $t->boolean('published')->default(false);
            $t->timestamps();
        });
        Schema::create('lessons', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->unsignedInteger('position');
            $t->unsignedInteger('minutes')->default(8);
            $t->text('body');
            $t->json('quiz');
            $t->timestamps();
            $t->unique(['course_id', 'position']);
        });
        Schema::create('import_batches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->string('status')->default('preview');
            $t->unsignedInteger('cursor')->default(0);
            $t->unsignedInteger('total')->default(0);
            $t->json('errors');
            $t->timestamps();
        });
        Schema::create('enrollments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->foreignId('import_batch_id')->nullable()->constrained()->nullOnDelete();
            $t->uuid('certificate')->nullable()->unique();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'course_id']);
        });
        Schema::create('lesson_completions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $t->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['enrollment_id', 'lesson_id']);
        });
        Schema::create('quiz_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $t->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('answer');
            $t->boolean('passed');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['quiz_attempts', 'lesson_completions', 'enrollments', 'import_batches', 'lessons', 'courses'] as $table) {
            Schema::dropIfExists($table);
        } Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_instructor'));
    }
};
