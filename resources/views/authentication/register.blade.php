@extends('layouts.auth')

@section('title', 'Register')

@section('content')
    <div class="auth-heading">
        <span>Begin your journey</span>
        <h2>Create your sanctuary</h2>
        <p>Daftar untuk mulai membangun kebiasaan yang lebih mindful.</p>
    </div>

    @if ($errors->any())
        <div class="auth-alert" role="alert"><i>error</i><span>{{ $errors->first() }}</span></div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('register') }}" data-submit-once>
        @csrf
        <label>
            <span>Full name</span>
            <span class="auth-input"><i>person</i><input name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="100" placeholder="Your name" required autofocus></span>
        </label>
        <label>
            <span>Email address</span>
            <span class="auth-input"><i>mail</i><input name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" placeholder="you@example.com" required></span>
        </label>
        <label>
            <span>Timezone</span>
            <span class="auth-input"><i>schedule</i><select name="timezone" required><option value="Asia/Jakarta" @selected(old('timezone', 'Asia/Jakarta') === 'Asia/Jakarta')>WIB — Asia/Jakarta</option><option value="Asia/Makassar" @selected(old('timezone') === 'Asia/Makassar')>WITA — Asia/Makassar</option><option value="Asia/Jayapura" @selected(old('timezone') === 'Asia/Jayapura')>WIT — Asia/Jayapura</option></select></span>
        </label>
        <label>
            <span>Password</span>
            <span class="auth-input has-password-toggle"><i>lock</i><input name="password" type="password" autocomplete="new-password" minlength="8" placeholder="Minimum 8 characters" required><button class="password-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">@include('partials.icons.eye')</button></span>
            <small>Gunakan huruf besar, huruf kecil, dan angka.</small>
        </label>
        <label>
            <span>Confirm password</span>
            <span class="auth-input has-password-toggle"><i>verified_user</i><input name="password_confirmation" type="password" autocomplete="new-password" minlength="8" placeholder="Repeat your password" required><button class="password-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">@include('partials.icons.eye')</button></span>
        </label>
        <button class="primary-button auth-submit" type="submit"><i>person_add</i><span data-submit-label>Create account</span></button>
    </form>

    <p class="auth-switch">Sudah memiliki akun? <a href="{{ route('login') }}">Login instead</a></p>
@endsection
