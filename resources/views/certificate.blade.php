@extends('layout')
@section('title', 'Certificate of completion')
@section('content')<section class="section certificate-wrap">
        <div class="certificate"><span class="large-star">✳</span>
            <p class="eyebrow">FIELDNOTES ACADEMY</p>
            <h1>Certificate of completion</h1>
            <p>This celebrates the curiosity and commitment of</p>
            <h2>{{ $enrollment->user->name }}</h2>
            <p>who completed every lesson and knowledge check in</p>
            <h3>{{ $enrollment->course->title }}</h3>
            <p>{{ $enrollment->completed_at->format('F j, Y') }}</p>
            <hr><small>Certificate {{ $enrollment->certificate }}<br>A learning milestone from an independent portfolio
                project; not an accredited qualification.</small>
        </div>
        <p class="muted">Use your browser’s Print → Save as PDF to keep a copy.</p><a href="{{ route('learning') }}">← My
            learning</a>
</section>@endsection
