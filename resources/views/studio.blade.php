@extends('layout')
@section('title', 'Instructor studio')
@section('content')<section class="section">
        <p class="eyebrow">INSTRUCTOR STUDIO</p>
        <h1>Share something worth knowing.</h1>
        <div class="studio-grid">
            <div>
                <h2>Your courses</h2>
                @forelse($courses as $course)
                    <a class="studio-course" href="{{ route('studio.edit', $course) }}">
                        <h3>{{ $course->title }} ↗</h3>
                        <p>{{ $course->published ? 'Published' : 'Draft' }} · {{ $course->lessons_count }} lessons ·
                            {{ $course->enrollments_count }} learners</p>
                </a>@empty<p>Your first course starts here.</p>
                @endforelse
            </div>
            <div class="panel">
                <h2>Create a course</h2>
                <form method="post" action="{{ route('studio.store') }}">@csrf<label>Course title<input name="title"
                            required maxlength="120" value="{{ old('title') }}"></label><label>Description
                        <textarea name="description" required maxlength="2000">{{ old('description') }}</textarea>
                    </label><label>Subject<input name="category" required maxlength="40"
                            placeholder="e.g. Design"></label><label>Level<select name="level">
                            <option>Beginner</option>
                            <option>Intermediate</option>
                            <option>Advanced</option>
                        </select></label><button class="button">Create draft ↗</button></form>
            </div>
        </div>
</section>@endsection
