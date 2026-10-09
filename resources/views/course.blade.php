@extends('layout')
@section('title', $course->title)
@section('content')<section class="section">
        <a class="breadcrumb" href="/">← All courses</a>
        <div class="course-intro">
            <div>
                <p class="eyebrow">{{ $course->category }} / {{ $course->level }}</p>
                <h1>{{ $course->title }}</h1>
                <p class="lead">{{ $course->description }}</p>
                <p>With {{ $course->instructor->name }} · {{ $course->lessons->sum('minutes') }} minutes ·
                    {{ $course->lessons->count() }} lessons</p>
            </div>
            <aside class="enroll-card"><span class="large-star">✳</span>
                <h3>A little time. A new skill.</h3>
                <p>Read each lesson and check your understanding. Finish the course to earn your certificate.</p>
                @if ($enrollment)
                    <a class="button" href="{{ route('lesson', $course->lessons->first()) }}">Continue learning ↗</a>
                @elseif($course->published)
                    <form method="post" action="{{ route('enroll', $course) }}">@csrf<button class="button">Enroll for free
                        ↗</button></form>@else<p>Draft course</p>
                @endif
            </aside>
        </div>
        <h2>Your learning path</h2>
        <div class="syllabus">
            @foreach ($course->lessons as $lesson)
                <div><span class="lesson-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <div>
                        <h3>{{ $lesson->title }}</h3>
                        <p>Reading + knowledge check</p>
                    </div><span>{{ $lesson->minutes }} min</span>
                </div>
            @endforeach
        </div>
</section>@endsection
