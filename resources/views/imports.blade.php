@extends('layout')
@section('title', 'Import learners')
@section('content')<section class="section">
        <a href="{{ route('studio.edit', $course) }}">← {{ $course->title }}</a>
        <p class="eyebrow">BRING YOUR COHORT TOGETHER</p>
        <h1>Import learners.</h1>
        <div class="studio-grid">
            <div class="panel">
                <h2>Preview before enrolling</h2>
                <p>Upload a CSV with a single <code>email</code> column. Learners must already have accounts. Existing
                    enrollments are kept; duplicate rows won’t create duplicates.</p>
                <p>Up to 5,000 rows / 1 MB. Files stay in private storage.</p>
                <pre>email
learner@example.test
maya@example.test</pre>
                <form method="post" enctype="multipart/form-data" action="{{ route('imports.preview', $course) }}">
                    @csrf<label>Learner CSV<input type="file" name="csv" accept=".csv,text/csv"
                            required></label><button class="button">Preview import ↗</button></form>
            </div>
            <div>
                <h2>Import history</h2>
                @forelse($batches as $batch)
                    <article class="studio-course">
                        <h3>Import #{{ $batch->id }} <span class="badge">{{ $batch->status }}</span></h3>
                        <p>{{ $batch->cursor }} / {{ $batch->total }} rows processed ·
                            {{ $batch->created_at->format('M j, H:i') }}</p>
                        @foreach ($batch->errors as $error)
                            <p class="error-text">{{ $error }}</p>
                        @endforeach
                        <div class="actions">
                            @if (in_array($batch->status, ['preview', 'failed']) && !$batch->errors)
                                <form method="post" action="{{ route('imports.start', $batch) }}">@csrf<button
                                        class="button small">{{ $batch->status === 'failed' ? 'Resume import' : 'Start import' }}
                                        ↗</button></form>
                                @endif @if (in_array($batch->status, ['completed', 'failed']))
                                    <form method="post" action="{{ route('imports.undo', $batch) }}">@csrf<button
                                            class="secondary">Undo unused enrollments</button></form>
                                @endif
                        </div>
                </article>@empty<p class="muted">Your import previews will appear here.</p>
                @endforelse
                <a href="{{ route('imports', $course) }}">Refresh status ↻</a>
                <p class="muted">Undo removes only enrollments created by that import with no quiz attempts or completed
                    lessons.</p>
            </div>
        </div>
</section>@endsection
