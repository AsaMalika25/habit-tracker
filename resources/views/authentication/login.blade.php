@extends('layouts.auth')

@section('title', 'Login')

@section('content')
    <div class="auth-heading">
        <span>Welcome back</span>
        <h2>Continue your rhythm</h2>
        <p>Masuk untuk melihat habit dan progres harian Anda.</p>
    </div>

    @if ($errors->any())
        <div class="auth-alert" role="alert"><i>error</i><span>{{ $errors->first() }}</span></div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('login') }}" data-submit-once>
        @csrf
        <label>
            <span>Email address</span>
            <span class="auth-input"><i>mail</i><input name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" placeholder="you@example.com" required autofocus></span>
        </label>
        <label>
            <span>Password</span>
            <span class="auth-input has-password-toggle"><i>lock</i><input name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required><button class="password-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">@include('partials.icons.eye')</button></span>
        </label>
        <label class="remember-row"><input name="remember" type="checkbox" value="1"><span>Keep me signed in</span></label>
        <button class="primary-button auth-submit" type="submit"><i>login</i><span data-submit-label>Login securely</span></button>
    </form>

    <p class="auth-switch">Belum memiliki akun? <a href="{{ route('register') }}">Create an account</a></p>
@endsection
