<?php
session_start();
include 'koneksi.php';

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

if (isset($_POST['login'])) {

    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
        die("Token tidak valid!");
    }

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['login']    = true;
            $_SESSION['id']       = $user['id'];
            $_SESSION['email']    = $user['email'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['LAST_ACTIVITY'] = time();
            $_SESSION['EXPIRE_TIME']   = 1800;

            $_SESSION['token'] = bin2hex(random_bytes(32));

            header("Location: ../dashboard.php");
            exit;

        } else {
            echo "<script>alert('Password salah');</script>";
        }

    } else {
        echo "<script>alert('Email tidak terdaftar');</script>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>Login</title>
    <link rel="stylesheet" href="../style.css?v=2">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="../icon-192.png">
</head>
<body class="login-page">

<div class="login-container">
    <h2>Login</h2>

<?php if(isset($_GET['timeout'])): ?>
    <div class="alert-timeout">
        <div class="icon">⏰</div>
        <div class="text">
            <strong>Sesi Berakhir</strong>
            <p>Kamu tidak aktif terlalu lama. Silakan login kembali.</p>
        </div>
    </div>
<?php endif; ?>

<form method="POST" class="login-form">
    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">

    <div class="input-group">
        <input type="email" name="email" placeholder="Email" required>
    </div>

    <div class="input-group password-wrapper">
        <input type="password" name="password" id="password" placeholder="Password" required>

    <span class="toggle-password" onclick="togglePassword()">
        <i class="fa-solid fa-eye" id="toggleIcon"></i>
    </span>
    </div>

    <button type="submit" name="login">Login</button>
</form>

<div class="login-footer">
    <p>Belum punya akun? <a href="register.php">Register</a></p>
    <hr>
    <p>Lupa password? <a href="forgot_password.php">Reset Password</a></p>
    <hr>
</div>
</div>
</body>
<script>
function togglePassword() {
    const password = document.getElementById("password");
    const icon = document.getElementById("toggleIcon");

    if (password.type === "password") {
        password.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        password.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}
</script>
</html>
