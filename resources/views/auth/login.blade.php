<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - MyDaily</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
    <script src="{{ asset('page-loader.js') }}" defer></script>
</head>
<body class="login-page">
@include('layouts.partials.page-loader')
<main class="login-container">
    <h2>Login MyDaily</h2>

    @if (session('status'))
        <p class="success-msg">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <p class="error-msg">{{ $errors->first() }}</p>
    @endif

    <form method="post" action="{{ route('login.store') }}" class="login-form">
        @csrf
        <div class="input-group">
            <input type="email" name="email" placeholder="Email"
                   value="{{ old('email', request()->cookie('remember_email')) }}" required autofocus>
        </div>
        <div class="input-group">
            <input type="password" name="password" placeholder="Password" required>
        </div>
        <label>
            <input type="checkbox" name="remember" value="1">
            Ingat saya
        </label>
        <button type="submit">Login</button>
    </form>

    <div class="login-footer">
        <p>Belum punya akun? <a href="{{ route('register') }}">Register</a></p>
        <p><a href="{{ route('password.request') }}">Lupa password?</a></p>
    </div>
</main>
</body>
</html>
