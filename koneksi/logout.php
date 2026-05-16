<?php
session_start();
include "koneksi.php";

/*
|--------------------------------------------------------------------------
| DELETE REMEMBER TOKEN
|--------------------------------------------------------------------------
*/
if (isset($_COOKIE['remember_token'])) {

    $token_hash = hash('sha256', $_COOKIE['remember_token']);

    $stmt = $conn->prepare("
        DELETE FROM remember_tokens
        WHERE token_hash = ?
    ");

    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->close();

    setcookie("remember_token", "", [
    'expires' => time() - 3600,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
}

/*
|--------------------------------------------------------------------------
| DELETE PHP SESSION COOKIE
|--------------------------------------------------------------------------
*/
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/*
|--------------------------------------------------------------------------
| DESTROY SESSION
|--------------------------------------------------------------------------
*/
session_unset();
session_destroy();

header("Location: login.php");
exit;
?>