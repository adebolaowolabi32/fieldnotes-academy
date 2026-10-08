<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class StudioController extends Controller
{
    public function index(): View
    {
        Gate::authorize('create', Course::class);

        return view('studio', ['courses' => Course::where('user_id', auth()->id())->withCount('lessons', 'enrollments')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Course::class);
        $data = $request->validate(['title' => 'required|string|max:120', 'description' => 'required|string|max:2000', 'category' => 'required|string|max:40', 'level' => 'required|in:Beginner,Intermediate,Advanced']);
        $course = Course::create($data + ['user_id' => auth()->id(), 'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6))]);

        return redirect()->route('studio.edit', $course);
    }

    public function edit(Course $course): View
    {
        Gate::authorize('update', $course);

        return view('editor', ['course' => $course->load('lessons')]);
    }

    public function lesson(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);
        $data = $request->validate(['title' => 'required|string|max:120', 'body' => 'required|string|max:20000', 'minutes' => 'required|integer|min:1|max:180', 'question' => 'required|string|max:500', 'options' => 'required|array|size:3', 'options.*' => 'required|string|max:300', 'correct' => 'required|integer|between:0,2']);
        DB::transaction(function () use ($course, $data): void {
            $locked = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->published || $locked->enrollments()->exists(), 409, 'Enrolled course curricula are immutable. Create a new course edition.');
            $locked->lessons()->create([
                'title' => $data['title'],
                'body' => $data['body'],
                'minutes' => $data['minutes'],
                'position' => ($locked->lessons()->max('position') ?? 0) + 1,
                'quiz' => ['question' => $data['question'], 'options' => array_values($data['options']), 'correct' => (int) $data['correct']],
            ]);
        }, 3);

        return back()->with('success', 'Lesson added.');
    }

    public function publish(Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);
        DB::transaction(function () use ($course): void {
            $locked = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->lessons()->exists(), 422, 'Add a lesson before publishing.');
            $locked->update(['published' => true]);
        }, 3);

        return back()->with('success', 'Your course is now in the catalogue.');
    }
}
