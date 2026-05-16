<?php

ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true, // kalau sudah HTTPS -> ubah TRUE
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
include "koneksi.php";

/*
|--------------------------------------------------------------------------
| AUTO LOGIN REMEMBER ME
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['login']) && isset($_COOKIE['remember_token'])) {

    $token = $_COOKIE['remember_token'];
    $hash  = hash('sha256', $token);

    $stmt = $conn->prepare("
        SELECT u.id, u.email, u.username, u.role
        FROM remember_tokens rt
        JOIN users u ON u.id = rt.user_id
        WHERE rt.token_hash = ?
        AND rt.expires_at > NOW()
        LIMIT 1
    ");

    $stmt->bind_param("s", $hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {

        session_regenerate_id(true);

        $_SESSION['login']    = true;
        $_SESSION['id']       = $user['id'];
        $_SESSION['email']    = $user['email'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];

        $_SESSION['LAST_ACTIVITY'] = time();
        $_SESSION['EXPIRE_TIME']   = 1800;

        header("Location: ../dashboard.php");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| LOGIN PROCESS
|--------------------------------------------------------------------------
*/
$error = "";

if (isset($_POST['login'])) {

    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
        die("CSRF token invalid!");
    }

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT id, email, username, password, role
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            /*
            |--------------------------------------------------------------------------
            | LOGIN SUCCESS
            |--------------------------------------------------------------------------
            */
            session_regenerate_id(true);

            $_SESSION['login']    = true;
            $_SESSION['id']       = $user['id'];
            $_SESSION['email']    = $user['email'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            $_SESSION['LAST_ACTIVITY'] = time();
            $_SESSION['EXPIRE_TIME']   = 1800;
            $conn->query("DELETE FROM remember_tokens WHERE user_id = {$user['id']}");

            if (isset($_POST['remember'])) {

                setcookie("remember_email", $email, [
                    'expires' => time() + (86400 * 30),
                    'path' => '/',
                    'secure' => true,
                    'httponly' => false,
                    'samesite' => 'Lax'
                ]);

            } else {

                setcookie("remember_email", "", time() - 3600, "/");
            }

            /*
            |--------------------------------------------------------------------------
            | REMEMBER ME
            |--------------------------------------------------------------------------
            */
            if (isset($_POST['remember'])) {

                $token = bin2hex(random_bytes(64));
                $hash  = hash('sha256', $token);
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

                // simpan DB
                $stmt2 = $conn->prepare("
                    INSERT INTO remember_tokens (user_id, token_hash, expires_at)
                    VALUES (?, ?, ?)
                ");

                $stmt2->bind_param("iss", $user['id'], $hash, $expires);
                $stmt2->execute();
                $stmt2->close();

                // simpan cookie
                setcookie("remember_token", $token, [
                    'expires'  => time() + (86400 * 30),
                    'path'     => '/',
                    'secure'   => true, // TRUE kalau HTTPS
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }

            header("Location: ../dashboard.php");
            exit;

        } else {
            $error = "Password salah";
        }

    } else {
        $error = "Email tidak ditemukan";
    }
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
<?php if (!empty($error)): ?>

<div class="modern-alert error show">

    <div class="alert-icon">
        <i class="fa-solid fa-circle-exclamation"></i>
    </div>

    <div class="alert-content">
        <div class="alert-title">
            Login Gagal
        </div>

        <div class="alert-message">
            <?= htmlspecialchars($error) ?>
        </div>
    </div>

    <button class="alert-close" onclick="closeAlert(this)">
        <i class="fa-solid fa-xmark"></i>
    </button>

</div>

<?php endif; ?>

<form method="POST" class="login-form">
    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">

    <div class="input-group">
        <input 
            type="email" 
            name="email" 
            placeholder="Email"
            value="<?= htmlspecialchars($_COOKIE['remember_email'] ?? '') ?>"
            required
        >
    </div>

    <div class="input-group password-wrapper">
        <input type="password" name="password" id="password" placeholder="Password" required>

    <span class="toggle-password" onclick="togglePassword()">
        <i class="fa-solid fa-eye" id="toggleIcon"></i>
    </span>
    </div>
    <div class="remember-wrapper">
        <label>
            <input type="checkbox" name="remember">
            Remember Me
        </label>
    </div>

    <button type="submit" name="login">Login</button>
</form>

<div class="login-footer">
    <p>Belum punya akun? <a href="register.php">Register</a></p>
    <p>Lupa password? <a href="forgot_password.php">Reset Password</a></p>
</div>
</div>
<script>
function closeAlert(button) {
    const alert = button.closest(".modern-alert");

    alert.style.opacity = "0";
    alert.style.transform = "translateY(-10px)";

    setTimeout(() => {
        alert.remove();
    }, 250);
}

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
</body>
</html>
