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
            $_SESSION['LAST_ACTIVITY'] = time();
            $_SESSION['EXPIRE_TIME']   = 1800;

            $_SESSION['token'] = bin2hex(random_bytes(32));

            header("Location: ../index.php");
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
    <title>Login</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="login-page">

<div class="login-container">
    <h2>Login</h2>
<?php if(isset($_GET['timeout'])): ?>
    <div style="color:red;margin-bottom:10px;">
        Sesi anda telah berakhir karena tidak aktif.
    </div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">

    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>

    <button type="submit" name="login">Login</button>
</form>
    <p>Belum punya akun? <a href="register.php">Register</a></p>
</div>

</body>

</html>
