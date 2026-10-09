<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'Learn something worth knowing') · Fieldnotes Academy</title>
    <meta name="description"
        content="Short, thoughtful courses for curious minds. Read, practice, and build lasting skills with Fieldnotes Academy.">
    <link rel="stylesheet" href="/app.css">
</head>

<body><a class="skip" href="#main">Skip to content</a>
    <header class="header"><a class="brand" href="{{ route('catalog') }}"><span class="brand-icon">f.</span>
            fieldnotes<span class="brand-small">ACADEMY</span></a>
        <nav aria-label="Main navigation"><a class="{{ request()->routeIs('catalog', 'course') ? 'active' : '' }}"
                href="{{ route('catalog') }}">Explore courses</a>@auth<a
                    class="{{ request()->routeIs('learning', 'lesson') ? 'active' : '' }}" href="{{ route('learning') }}">My
                    learning</a>
                @if (auth()->user()->is_instructor)
                    <a href="{{ route('studio') }}">Instructor studio</a>
                @endif
                <span class="avatar" title="{{ auth()->user()->name }}">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                <form action="{{ route('logout') }}" method="post">@csrf<button class="plain">Sign out</button></form>
            @else<a href="{{ route('login') }}">Sign in</a><a class="button small" href="{{ route('register') }}">Start
                learning <span>↗</span></a>@endauth
        </nav>
    </header>
    <main id="main">
        @if (session('success'))
            <div class="notice" role="status">{{ session('success') }}</div>
            @endif @if (session('feedback'))
                <div class="notice amber" role="status">{{ session('feedback') }}</div>
                @endif @if ($errors->any())
                    <div class="notice error" role="alert">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif @yield('content')
    </main>
    <footer><a class="brand" href="/">fieldnotes<span class="brand-small">ACADEMY</span></a>
        <p>A little curiosity. A lasting skill.</p><span>Built by Cynthia Owolabi · Portfolio edition</span>
    </footer>
</body>

</html>
