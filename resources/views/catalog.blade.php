@extends('layout')
@section('content')
    <section class="hero">
        <div class="hero-copy">
            <div class="eyebrow"><span class="dot"></span> MAKE ROOM FOR CURIOSITY</div>
            <h1>Small lessons.<br><em>Lasting skills.</em></h1>
            <p>A quieter place to learn something new.<br>Thoughtful courses, practical exercises, and progress<br
                    class="desktop"> you can feel good about.</p><a class="button" href="#courses">Find your next course
                <span>↗</span></a>
            <div class="hero-note"><span class="tiny-stars">✳ ✳ ✳</span> Learn at your pace. Every course is free.</div>
        </div>
        <div class="hero-art" aria-label="Illustrated field notebook with leaves and learning notes" role="img">
            <div class="orbit orbit-one"></div>
            <div class="orbit orbit-two"></div><span class="art-star">✳</span>
            <div class="note-card"><span class="note-label">THE CURIOSITY COLLECTION</span>
                <div class="plant"><span></span><i></i><b></b><em></em></div>
                <div class="note-lines"></div>
                <h2>Keep growing.</h2>
                <p>ONE GOOD IDEA AT A TIME</p><span class="note-number">FIELD NOTES / 001</span>
            </div>
            <div class="floating-note"><span>↗</span> A new perspective<br>starts here.</div>
        </div>
    </section>
    <section class="promise"><span>01 <b>Learn by doing</b><small>Short lessons. Real understanding.</small></span><span>02
            <b>Make it your own</b><small>Pick a course. Set your own pace.</small></span><span>03 <b>See how far you’ve
                come</b><small>Saved progress and completion certificates.</small></span></section>
    <section class="section" id="courses">
        <div class="section-top">
            <div>
                <p class="eyebrow">FOLLOW YOUR INTEREST</p>
                <h2>Your next good idea starts here.</h2>
            </div><span class="muted">{{ $courses->total() }} courses to explore</span>
        </div>
        <form class="filters" method="get" action="{{ route('catalog') }}#courses">
            <div class="search"><span aria-hidden="true">⌕</span><input aria-label="Search courses" name="q"
                    value="{{ request('q') }}" placeholder="What are you curious about?"></div><select name="category"
                aria-label="Course category">
                <option value="">All subjects</option>
                @foreach ($categories as $category)
                    <option @selected(request('category') === $category)>{{ $category }}</option>
                @endforeach
            </select>
            <button class="button small">Find courses</button>
            @if (request('q') || request('category'))
                <a href="/#courses">Clear</a>
            @endif
        </form>
        <div class="course-grid">
            @forelse($courses as $course)
                <article class="course-card"><a class="course-art art-{{ $course->id % 3 }}"
                        href="{{ route('course', $course) }}" tabindex="-1" aria-hidden="true"><span
                            class="art-tag">{{ $course->category }}</span>
                        <div class="abstract"><span></span><i></i><b></b></div><span class="art-bottom">FIELDNOTES
                            COLLECTION <span>0{{ $course->id }}</span></span>
                    </a>
                    <div class="course-info">
                        <p class="eyebrow">{{ $course->level }} <span>·</span> {{ $course->lessons_count }} LESSONS</p>
                        <h3><a href="{{ route('course', $course) }}">{{ $course->title }}</a></h3>
                        <p>{{ $course->description }}</p>
                        <div class="course-meta"><span>◷ {{ $course->lessons_sum_minutes }} min</span><span>With
                                {{ $course->instructor->name }}</span><a aria-label="Explore {{ $course->title }}"
                                href="{{ route('course', $course) }}">↗</a></div>
                    </div>
            </article>@empty<div class="empty">
                    <h3>No courses found.</h3>
                    <p>Try another topic or clear your search.</p><a href="/">Explore all courses →</a>
                </div>
            @endforelse
        </div>{{ $courses->links() }}
    </section>
    <section class="closing"><span class="closing-flower">✳</span>
        <div>
            <p class="eyebrow">A LITTLE, OFTEN, GOES A LONG WAY</p>
            <h2>You don’t need a whole afternoon.<br>Just a place to begin.</h2>
        </div><a href="{{ route('register') }}" class="button">Make time to learn ↗</a>
    </section>
@endsection
