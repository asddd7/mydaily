<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - MyDaily</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
    <script src="{{ asset('page-loader.js') }}" defer></script>
</head>
<body class="login-page">
@include('layouts.partials.page-loader')
<main class="login-container">
    <h2>Reset Password</h2>

    @if ($errors->any())
        <p class="error-msg">{{ $errors->first() }}</p>
    @endif
    @if (session('reset_link'))
        <p class="success-msg">Tautan reset password:</p>
        <p><a href="{{ session('reset_link') }}">{{ session('reset_link') }}</a></p>
    @endif

    <form method="post" action="{{ route('password.email') }}" class="login-form">
        @csrf
        <div class="input-group">
            <input type="email" name="email" placeholder="Masukkan email"
                   value="{{ old('email') }}" required>
        </div>
        <button type="submit">Buat tautan reset</button>
    </form>

    <p><a href="{{ route('login') }}">Kembali ke Login</a></p>
</main>
</body>
</html>
