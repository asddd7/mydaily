<?php

ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

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
| RATE LIMIT CONFIG
|--------------------------------------------------------------------------
*/
$max_attempts = 5; // maksimal percobaan login
$lock_time    = 300; // 300 detik = 5 menit

/*
|--------------------------------------------------------------------------
| INIT SESSION LOGIN ATTEMPT
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['login_attempt'])) {
    $_SESSION['login_attempt'] = 0;
}

if (!isset($_SESSION['last_attempt_time'])) {
    $_SESSION['last_attempt_time'] = 0;
}

$error = "";

/*
|--------------------------------------------------------------------------
| CHECK LOCK
|--------------------------------------------------------------------------
*/
if (
    $_SESSION['login_attempt'] >= $max_attempts &&
    (time() - $_SESSION['last_attempt_time']) < $lock_time
) {

    $remaining = $lock_time - (time() - $_SESSION['last_attempt_time']);

    $minutes = ceil($remaining / 60);

    $error = "Terlalu banyak percobaan login. Coba lagi dalam {$minutes} menit.";
}

/*
|--------------------------------------------------------------------------
| LOGIN PROCESS
|--------------------------------------------------------------------------
*/
if (isset($_POST['login']) && empty($error)) {

    /*
    |--------------------------------------------------------------------------
    | VALIDASI CSRF TOKEN
    |--------------------------------------------------------------------------
    */
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
        die("Token tidak valid!");
    }

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    /*
    |--------------------------------------------------------------------------
    | VALIDASI EMAIL
    |--------------------------------------------------------------------------
    */
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } else {

        /*
        |--------------------------------------------------------------------------
        | CHECK USER
        |--------------------------------------------------------------------------
        */
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

            /*
            |--------------------------------------------------------------------------
            | VERIFY PASSWORD
            |--------------------------------------------------------------------------
            */
            if (password_verify($password, $user['password'])) {

                /*
                |--------------------------------------------------------------------------
                | RESET RATE LIMIT
                |--------------------------------------------------------------------------
                */
                $_SESSION['login_attempt'] = 0;
                $_SESSION['last_attempt_time'] = 0;

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

                $_SESSION['token'] = bin2hex(random_bytes(32));

                header("Location: ../dashboard.php");
                exit;

            } else {

                /*
                |--------------------------------------------------------------------------
                | PASSWORD SALAH
                |--------------------------------------------------------------------------
                */
                $_SESSION['login_attempt']++;
                $_SESSION['last_attempt_time'] = time();

                $sisa = $max_attempts - $_SESSION['login_attempt'];

                if ($sisa > 0) {
                    $error = "Password salah. Sisa percobaan: {$sisa}";
                } else {
                    $error = "Terlalu banyak percobaan login. Tunggu 5 menit.";
                }
            }

        } else {

            /*
            |--------------------------------------------------------------------------
            | EMAIL TIDAK DITEMUKAN
            |--------------------------------------------------------------------------
            */
            $_SESSION['login_attempt']++;
            $_SESSION['last_attempt_time'] = time();

            $sisa = $max_attempts - $_SESSION['login_attempt'];

            if ($sisa > 0) {
                $error = "Email tidak terdaftar. Sisa percobaan: {$sisa}";
            } else {
                $error = "Terlalu banyak percobaan login. Tunggu 5 menit.";
            }
        }

        $stmt->close();
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
