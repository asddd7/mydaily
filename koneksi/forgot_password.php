<?php
session_start();
include 'koneksi.php';

$message = "";

if (isset($_POST['reset'])) {

    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $token = bin2hex(random_bytes(32));
        $expire = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $stmt2 = $conn->prepare("UPDATE users SET reset_token=?, reset_expire=? WHERE email=?");
        $stmt2->bind_param("sss", $token, $expire, $email);
        $stmt2->execute();

        // Ganti domain sesuai project kamu
        $reset_link = "http://mydaily.infinityfreeapp.com/reset_password.php?token=" . $token;

        $message = "Link reset password:<br><a href='$reset_link'>$reset_link</a>";

    } else {
        $message = "Email tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="login-page">

<div class="login-container">
    <h2>Reset Password</h2>

    <?php if($message): ?>
        <div class="alert"><?= $message; ?></div>
    <?php endif; ?>

    <form method="POST" class="login-form">
        <div class="input-group">
            <input type="email" name="email" placeholder="Masukkan Email" required>
        </div>

        <button type="submit" name="reset">Kirim Link Reset</button>
    </form>

    <div class="login-footer">
        <a href="login.php">← Kembali ke Login</a>
    </div>
</div>

</body>
</html>