@extends('layout')
@section('title', 'Edit ' . $course->title)
@section('content')<section class="section">
        <a href="{{ route('studio') }}">← Studio</a>
        <p class="eyebrow">{{ $course->published ? 'PUBLISHED COURSE' : 'COURSE DRAFT' }}</p>
        <h1>{{ $course->title }}</h1>
        <div class="actions"><a href="{{ route('course', $course) }}">Preview course ↗</a>
            @if ($course->published)
                <a href="{{ route('imports', $course) }}">Import learners →</a>
            @elseif($course->lessons->isNotEmpty())
                <form method="post" action="{{ route('studio.publish', $course) }}">@csrf<button class="button">Publish course
                        ↗</button></form>
            @endif
        </div>
        <div class="studio-grid">
            <div>
                <h2>Course lessons</h2>
                @forelse($course->lessons as $lesson)
                    <div class="studio-course">
                        <h3>{{ $lesson->position }}. {{ $lesson->title }}</h3>
                        <p>{{ $lesson->minutes }} minutes · Includes a knowledge check</p>
                </div>@empty<p>Add your first lesson, then publish your course.</p>
                    @endforelse @if ($course->published)
                        <p class="notice">Published curricula are fixed to preserve learners’ progress and certificates.
                            Create a new course for a revised edition.</p>
                    @endif
            </div>
            @unless ($course->published)
                <div class="panel">
                    <h2>Add a lesson</h2>
                    <form method="post" action="{{ route('studio.lesson', $course) }}">@csrf<label>Lesson title<input
                                name="title" required maxlength="120"></label><label>Reading time (minutes)<input
                                type="number" min="1" max="180" name="minutes" value="8"
                                required></label><label>Lesson content (Markdown)
                            <textarea name="body" rows="10" required></textarea>
                        </label><label>Knowledge-check question<input name="question" required></label>
                        @for ($i = 0; $i < 3; $i++)
                            <label>Answer {{ $i + 1 }}<input name="options[]" required></label>
                        @endfor
                        <label>
                            Correct answer<select name="correct">
                                <option value="0">Answer 1</option>
                                <option value="1">Answer 2</option>
                                <option value="2">Answer 3</option>
                            </select></label><button class="button">Add lesson ↗</button>
                    </form>
            </div>@endunless
        </div>
</section>@endsection
