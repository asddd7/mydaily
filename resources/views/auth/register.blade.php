<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - MyDaily</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
</head>
<body class="login-page">
<main class="login-container">
    <h2>Register</h2>

    @if ($errors->any())
        <p class="error-msg">{{ $errors->first() }}</p>
    @endif

    <form method="post" action="{{ route('register.store') }}" class="login-form">
        @csrf
        <div class="input-group">
            <input type="text" name="username" placeholder="Username" value="{{ old('username') }}" required>
        </div>
        <div class="input-group">
            <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
        </div>
        <div class="input-group">
            <input type="password" name="password" placeholder="Password (minimal 8 karakter)" required>
        </div>
        <div class="input-group">
            <input type="password" name="password_confirmation" placeholder="Ulangi password" required>
        </div>
        <button type="submit">Register</button>
    </form>

    <p>Sudah punya akun? <a href="{{ route('login') }}">Login</a></p>
</main>
</body>
</html>
