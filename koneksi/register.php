<?php
include 'koneksi.php';

if (isset($_POST['register'])) {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $cek = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Email sudah terdaftar";
    } else {
        $query = mysqli_query($conn, 
            "INSERT INTO users (username, email, password)
             VALUES ('$username', '$email', '$password_hash')"
        );

        if ($query) {
            $success = "Registrasi berhasil! Silahkan login.";
        } else {
            $error = "Registrasi gagal. Silahkan coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="../style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="login-page">

<div class="login-container">
    <h2>Register</h2>

    <?php if (!empty($error)): ?>
        <p style="color:red; font-weight:600;"><?= $error; ?></p>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <p style="color:green; font-weight:600;"><?= $success; ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>

        <button type="submit" name="register">Register</button>
    </form>

    <p>Sudah punya akun? <a href="login.php">Login</a></p>
</div>

</body>
</html>
