<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atur Password - MyDaily</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
</head>
<body class="login-page">
<main class="login-container">
    <h2>Atur Password Baru</h2>

    @if ($errors->any())
        <p class="error-msg">{{ $errors->first() }}</p>
    @endif

    <form method="post" action="{{ route('password.update', ['token' => $token]) }}" class="login-form">
        @csrf
        <div class="input-group">
            <input type="password" name="password" placeholder="Password baru (minimal 8 karakter)" required>
        </div>
        <div class="input-group">
            <input type="password" name="password_confirmation" placeholder="Ulangi password" required>
        </div>
        <button type="submit">Simpan password</button>
    </form>
</main>
</body>
</html>
