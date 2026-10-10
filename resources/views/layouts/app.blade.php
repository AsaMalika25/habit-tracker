<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1326">
    <title>@yield('title', 'Aura — Habits & Journal')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body>
    @include('partials.sidebar')
    <button class="sidebar-overlay" id="sidebarOverlay" type="button" aria-label="Close navigation" tabindex="-1"></button>

    <div class="app-shell">
        @include('partials.topbar')
        <main>@yield('content')</main>
    </div>

    @if ($streakPopup = session('streak_popup'))
        <div class="streak-celebration" data-streak-popup role="dialog" aria-modal="true" aria-labelledby="streakPopupTitle">
            <button class="streak-celebration__backdrop" type="button" data-close-streak aria-label="Tutup perayaan streak"></button>
            <section class="streak-celebration__card">
                <button class="streak-celebration__close" type="button" data-close-streak aria-label="Tutup"><i>close</i></button>
                <div class="streak-flame" aria-hidden="true">
                    <span class="streak-flame__glow"></span>
                    <span class="streak-flame__outer"></span>
                    <span class="streak-flame__inner"></span>
                    <span class="streak-spark streak-spark--one"></span>
                    <span class="streak-spark streak-spark--two"></span>
                    <span class="streak-spark streak-spark--three"></span>
                </div>
                <span class="streak-celebration__eyebrow">Daily flame</span>
                <h2 id="streakPopupTitle">{{ $streakPopup['title'] }}</h2>
                <p>{{ $streakPopup['message'] }}</p>
                <strong><span>{{ $streakPopup['days'] }}</span> hari</strong>
                <button class="streak-celebration__action" type="button" data-close-streak>Lanjutkan ritme</button>
            </section>
        </div>
    @endif

    @yield('modals')
</body>
</html>
