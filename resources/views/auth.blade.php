@extends('layout')
@section('title', $register ? 'Create your account' : 'Welcome back')
@section('content')<section class="auth-wrap">
        <div class="auth-intro">
            <p class="eyebrow">YOUR NEXT CHAPTER</p>
            <h1>{{ $register ? 'Stay curious.' : 'Welcome back.' }}</h1>
            <p class="lead">Good things happen when you<br>make a little room to learn.</p>
            <div class="auth-art">✳</div>
        </div>
        <div class="auth-card">
            <h2>{{ $register ? 'Create your account' : 'Pick up where you left off' }}</h2>
            <form method="post" action="{{ $register ? route('register') : route('login') }}">@csrf @if ($register)
                    <label>Your name<input name="name" autocomplete="name" required value="{{ old('name') }}"
                            maxlength="100"></label>
                @endif
                <label>
                    Email address<input type="email" name="email" autocomplete="email" required
                        value="{{ old('email') }}"></label><label>Password<input type="password" name="password"
                        autocomplete="{{ $register ? 'new-password' : 'current-password' }}" required
                        @if ($register) minlength="12" @endif></label>
                @if ($register)
                    <small>Use at least 12 characters.</small><label>Confirm password<input type="password"
                            name="password_confirmation" autocomplete="new-password" required></label>
                @endif
                <button class="button">
                    {{ $register ? 'Start my learning journey' : 'Sign in' }} ↗</button>
            </form>
            <p>{{ $register ? 'Already have an account?' : 'New to Fieldnotes?' }} <a
                    href="{{ $register ? route('login') : route('register') }}">{{ $register ? 'Sign in' : 'Create an account' }}</a>
            </p>
            @if (app()->environment('local'))
                <div class="demo-hint"><b>Explore the local demo</b>
                    <p>Learner: learner@example.test<br>Instructor: instructor@example.test<br>Password for both:
                        fieldnotes-demo-2026</p>
                </div>
            @endif
        </div>
</section>@endsection
