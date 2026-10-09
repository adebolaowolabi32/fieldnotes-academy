<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Notifications\CourseCompleted;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LearningController extends Controller
{
    public function index(Request $request): View
    {
        $query = Course::where('published', true)->with('instructor')->withCount('lessons')->withSum('lessons', 'minutes');
        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.mb_substr($request->string('q'), 0, 100).'%');
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        return view('catalog', ['courses' => $query->orderBy('id')->paginate(9)->withQueryString(), 'categories' => Course::where('published', true)->distinct()->pluck('category')]);
    }

    public function course(Course $course): View
    {
        abort_unless($course->published || auth()->id() === $course->user_id, 404);

        return view('course', ['course' => $course->load('lessons', 'instructor'), 'enrollment' => auth()->check() ? Enrollment::where('user_id', auth()->id())->where('course_id', $course->id)->first() : null]);
    }

    public function enroll(Course $course): RedirectResponse
    {
        abort_unless($course->published && $course->lessons()->exists(), 404);
        Enrollment::firstOrCreate(['user_id' => auth()->id(), 'course_id' => $course->id]);

        return redirect()->route('lesson', $course->lessons()->first());
    }

    public function dashboard(): View
    {
        return view('learning', ['enrollments' => Enrollment::where('user_id', auth()->id())->with(['course.lessons', 'completions'])->latest()->get()]);
    }

    private function enrollment(Lesson $lesson): Enrollment
    {
        abort_unless($lesson->course->published, 404);

        return Enrollment::where('user_id', auth()->id())->where('course_id', $lesson->course_id)->firstOrFail();
    }

    public function lesson(Lesson $lesson): View
    {
        $enrollment = $this->enrollment($lesson);
        $lessons = $lesson->course->lessons;

        return view('lesson', compact('lesson', 'enrollment', 'lessons'));
    }

    public function answer(Request $request, Lesson $lesson): RedirectResponse
    {
        $enrollment = $this->enrollment($lesson);
        $quiz = $lesson->quiz;
        $data = $request->validate(['answer' => 'required|integer|min:0|max:'.(count($quiz['options']) - 1)]);
        $passed = (int) $data['answer'] === (int) $quiz['correct'];
        DB::transaction(function () use ($enrollment, $lesson, $data, $passed) {
            $locked = Enrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            QuizAttempt::create(['enrollment_id' => $locked->id, 'lesson_id' => $lesson->id, 'answer' => $data['answer'], 'passed' => $passed]);
            if (! $passed) {
                return;
            }
            $locked->completions()->firstOrCreate(['lesson_id' => $lesson->id]);
            if (! $locked->completed_at && $locked->completions()->count() === $lesson->course->lessons()->count()) {
                $locked->update(['completed_at' => now(), 'certificate' => (string) Str::uuid()]);
                $locked->user->notify((new CourseCompleted($locked))->afterCommit());
            }
        }, 3);

        return back()->with($passed ? 'success' : 'feedback', $passed ? 'Lesson complete. Your progress has been saved.' : 'Not quite. Revisit the lesson and try again.');
    }

    public function certificate(Enrollment $enrollment): View
    {
        abort_unless($enrollment->user_id === auth()->id(), 403);
        abort_unless($enrollment->completed_at, 404);

        return view('certificate', compact('enrollment'));
    }
}
