<?php
include 'koneksi.php';

if (!isset($_GET['token'])) {
    die("Token tidak valid!");
}

$token = $_GET['token'];

$stmt = $conn->prepare("SELECT * FROM users WHERE reset_token=? AND reset_expire > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Token expired / tidak valid!");
}

if (isset($_POST['update'])) {

    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt2 = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expire=NULL WHERE reset_token=?");
    $stmt2->bind_param("ss", $new_password, $token);
    $stmt2->execute();

    echo "<script>alert('Password berhasil diubah'); window.location='login.php';</script>";
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
    <form method="POST">
        <div class="input-group">
            <input type="password" name="password" placeholder="Password Baru" required>
        </div>
        <button type="submit" name="update">Update Password</button>
    </form>
</div>


</body></html>