@extends('layout')
@section('title', $lesson->title)
@section('content')<div class="reader">
        <aside class="reader-nav"><a class="breadcrumb" href="{{ route('learning') }}">← My learning</a>
            <p class="eyebrow">YOUR LEARNING PATH</p>
            <h2>{{ $lesson->course->title }}</h2>@php($completed = $enrollment->completions->pluck('lesson_id'))<progress value="{{ $completed->count() }}"
                max="{{ $lessons->count() }}"></progress>
            <p class="muted">{{ $completed->count() }} / {{ $lessons->count() }} lessons complete</p>
            <nav aria-label="Course lessons">
                @foreach ($lessons as $item)
                    <a class="{{ $item->id === $lesson->id ? 'selected' : '' }}"
                        href="{{ route('lesson', $item) }}"><span>{{ $completed->contains($item->id) ? '✓' : str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $item->title }}</a>
                @endforeach
            </nav>
            @if ($enrollment->completed_at)
                <a class="button small" href="{{ route('certificate', $enrollment) }}">Your certificate ↗</a>
            @endif
        </aside>
        <article class="lesson-content">
            <p class="eyebrow">LESSON {{ str_pad($lesson->position, 2, '0', STR_PAD_LEFT) }} <span> / </span>
                {{ $lesson->minutes }} MIN READ</p>
            <h1>{{ $lesson->title }}</h1>
            <div class="prose">{!! Illuminate\Support\Str::markdown($lesson->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
            <section class="quiz">
                <p class="eyebrow">PAUSE & REFLECT</p>
                <h2>Make the idea stick.</h2>
                @if ($completed->contains($lesson->id))
                    <p class="completion">✓ You’ve completed this lesson. You can revisit the check anytime.</p>
                @endif
                <form action="{{ route('answer', $lesson) }}" method="post">
                    @csrf<fieldset>
                        <legend>{{ $lesson->quiz['question'] }}</legend>
                        @foreach ($lesson->quiz['options'] as $option)
                            <label class="option"><input type="radio" name="answer" value="{{ $loop->index }}"
                                    required><span>{{ $option }}</span></label>
                        @endforeach
                    </fieldset>
                    <button class="button">Check my understanding ↗</button>
                </form>
            </section>@php($next = $lessons->first(fn($l) => $l->position > $lesson->position)) @if ($next)
            <a class="next-lesson" href="{{ route('lesson', $next) }}">Next: {{ $next->title }} →</a>@else<a
                    class="next-lesson" href="{{ route('learning') }}">Back to my learning →</a>
            @endif
        </article>
</div>@endsection
