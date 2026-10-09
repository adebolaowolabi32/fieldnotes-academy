@extends('layout')
@section('title', 'My learning')
@section('content')<section class="section">
        <p class="eyebrow">YOUR PERSONAL BOOKSHELF</p>
        <h1>Keep your curiosity going.</h1>
        <p class="lead">Welcome back, {{ auth()->user()->name }}. A little progress is still progress.</p>
        <div class="learning-grid">
            @forelse($enrollments as $enrollment)
                @php($total = $enrollment->course->lessons->count()) @php($done = $enrollment->completions->count()) @php($next = $enrollment->course->lessons->first(fn($l) => !$enrollment->completions->contains('lesson_id', $l->id)) ?? $enrollment->course->lessons->first())
                <article class="learning-card">
                    <p class="eyebrow">{{ $enrollment->course->category }}</p>
                    <h2>{{ $enrollment->course->title }}</h2>
                    <p>{{ $done }} of {{ $total }} lessons complete</p><progress value="{{ $done }}"
                        max="{{ max(1, $total) }}">{{ $done }}/{{ $total }}</progress>
                    <div class="actions">
                        @if ($next)
                            <a class="button"
                                href="{{ route('lesson', $next) }}">{{ $enrollment->completed_at ? 'Revisit course' : 'Continue learning' }}
                                ↗</a>
                            @endif @if ($enrollment->completed_at)
                                <a href="{{ route('certificate', $enrollment) }}">View certificate →</a>
                            @endif
                    </div>
            </article>@empty<div class="empty">
                    <h2>Your bookshelf is ready.</h2>
                    <p>Find a course that catches your curiosity.</p><a class="button" href="/">Explore courses ↗</a>
                </div>
            @endforelse
        </div>
</section>@endsection
