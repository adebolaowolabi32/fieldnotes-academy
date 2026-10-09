<?php

namespace App\Console\Commands;

use App\Models\Course;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Signature('demo:export {path=demo/courses.json}')]
#[Description('Export fictional courses from an isolated in-memory demo database')]
class ExportPortfolioDemo extends Command
{
    public function handle(): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('Demo export is restricted to local and testing environments.');

            return self::FAILURE;
        }

        $original = config('database.default');
        config(['database.connections.portfolio_export' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ], 'database.default' => 'portfolio_export']);

        try {
            $this->callSilent('migrate', ['--database' => 'portfolio_export', '--force' => true]);
            $this->callSilent('db:seed', ['--database' => 'portfolio_export', '--force' => true]);
            $courses = Course::with(['lessons', 'instructor'])->orderBy('id')->get()->map(fn (Course $course): array => [
                'id' => $course->id, 'title' => $course->title, 'description' => $course->description,
                'category' => $course->category, 'instructor' => $course->instructor->name,
                'lessons' => $course->lessons->map(fn ($lesson): array => [
                    'id' => $lesson->id, 'title' => $lesson->title, 'minutes' => $lesson->minutes,
                    'html' => Str::markdown($lesson->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
                    // Public answers are intentional in this browser-only simulation.
                    'quiz' => $lesson->quiz,
                ])->all(),
            ])->all();
            $path = base_path($this->argument('path'));
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode($courses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
            $this->info('Exported fictional course fixtures. No application data was read.');

            return self::SUCCESS;
        } finally {
            DB::purge('portfolio_export');
            config(['database.default' => $original]);
        }
    }
}
