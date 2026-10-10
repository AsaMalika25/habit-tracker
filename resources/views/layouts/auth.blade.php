<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1326">
    <title>@yield('title') — Aura</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="auth-intro">
            <a class="auth-brand" href="{{ route('login') }}">
                <span class="brand-mark"><img src="{{ asset('images/aura-logo.png') }}" alt="Aura logo"></span>
                <span><strong>Aura</strong><small>Habits &amp; Journal</small></span>
            </a>
            <div>
                <span class="status-pill"><em></em> Your mindful space</span>
                <h1>Small rituals.<br><span>Meaningful change.</span></h1>
                <p>Bangun kebiasaan baik, catat refleksi, dan lihat perkembangan Anda dalam satu ruang yang tenang.</p>
            </div>
            <blockquote>“Consistency is the quiet architecture of a life well lived.”</blockquote>
        </section>
        <section class="auth-form-panel">
            <div class="auth-card">
                @yield('content')
            </div>
        </section>
    </main>
</body>
</html>
