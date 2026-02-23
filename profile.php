<?php
include 'koneksi/koneksi.php';
session_start();

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$user_id  = $_SESSION['id'];
$username = $_SESSION['username'] ?? 'Guest';

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$success = "";
$error   = "";

// ======================
// AMBIL DATA USER
// ======================
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// ======================
// UPDATE PROFILE
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
        die("Token tidak valid!");
    }

    $new_username = trim($_POST['username']);
    $new_email    = trim($_POST['email']);

    $instagram = trim($_POST['instagram']);
    $facebook  = trim($_POST['facebook']);
    $twitter   = trim($_POST['twitter']);
    $tiktok    = trim($_POST['tiktok']);
    $linkedin  = trim($_POST['linkedin']);
    $public    = isset($_POST['sosmed_public']) ? 1 : 0;

    if ($new_username && $new_email) {

        $stmt = $conn->prepare("UPDATE users 
            SET username=?, email=?, instagram=?, facebook=?, twitter=?, tiktok=?, linkedin=?, sosmed_public=? 
            WHERE id=?");

        $stmt->bind_param("sssssssii", 
            $new_username, 
            $new_email, 
            $instagram, 
            $facebook, 
            $twitter, 
            $tiktok, 
            $linkedin, 
            $public,
            $user_id
        );

        if ($stmt->execute()) {
            $_SESSION['username'] = $new_username;
            $success = "Profile berhasil diperbarui!";
        } else {
            $error = "Gagal update profile.";
        }

        $stmt->close();
    } else {
        $error = "Semua field harus diisi.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="layout">
<?php include 'sidebar.php'; ?>

<main class="content">

<div class="card">
<h3>👤 Profile Saya</h3>

<?php if ($success): ?>
<p class="success-msg"><?= $success ?></p>
<?php endif; ?>

<?php if ($error): ?>
<p class="error-msg"><?= $error ?></p>
<?php endif; ?>

<form method="post">
<input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">

<label>Username</label>
<input type="text" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>

<label>Email</label>
<input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>

<button type="submit" name="submit">Update Profile</button>

<label class="checkbox">
<input type="checkbox" name="sosmed_public" <?= $user['sosmed_public'] ? 'checked' : '' ?>>
Tampilkan sosial media ke publik
</label>
<!-- SOCIAL ICONS -->
<h4>🔗Social Media</h4>
<div class="social-icons">
    <i class="fab fa-instagram" onclick="toggleForm('instagramForm')"></i>
    <i class="fab fa-facebook" onclick="toggleForm('facebookForm')"></i>
    <i class="fab fa-x-twitter" onclick="toggleForm('twitterForm')"></i>
    <i class="fab fa-tiktok" onclick="toggleForm('tiktokForm')"></i>
    <i class="fab fa-linkedin" onclick="toggleForm('linkedinForm')"></i>
</div>

<!-- SOCIAL MEDIA FORMS -->
<div id="instagramForm" class="sosmed-form">
    <input type="text" name="instagram" placeholder="Instagram URL" value="<?= htmlspecialchars($user['instagram']); ?>">
</div>

<div id="facebookForm" class="sosmed-form">
    <input type="text" name="facebook" placeholder="Facebook URL" value="<?= htmlspecialchars($user['facebook']); ?>">
</div>

<div id="twitterForm" class="sosmed-form">
    <input type="text" name="twitter" placeholder="Twitter/X URL" value="<?= htmlspecialchars($user['twitter']); ?>">
</div>

<div id="tiktokForm" class="sosmed-form">
    <input type="text" name="tiktok" placeholder="TikTok URL" value="<?= htmlspecialchars($user['tiktok']); ?>">
</div>

<div id="linkedinForm" class="sosmed-form">
    <input type="text" name="linkedin" placeholder="LinkedIn URL" value="<?= htmlspecialchars($user['linkedin']); ?>">
</div>
</form>

<?php if ($user['sosmed_public']): ?>
<hr>
<div class="social-icons">

<?php if ($user['instagram']): ?>
<a href="<?= htmlspecialchars($user['instagram']); ?>" target="_blank"><i class="fab fa-instagram"></i></a>
<?php endif; ?>

<?php if ($user['facebook']): ?>
<a href="<?= htmlspecialchars($user['facebook']); ?>" target="_blank"><i class="fab fa-facebook"></i></a>
<?php endif; ?>

<?php if ($user['twitter']): ?>
<a href="<?= htmlspecialchars($user['twitter']); ?>" target="_blank"><i class="fab fa-x-twitter"></i></a>
<?php endif; ?>

<?php if ($user['tiktok']): ?>
<a href="<?= htmlspecialchars($user['tiktok']); ?>" target="_blank"><i class="fab fa-tiktok"></i></a>
<?php endif; ?>

<?php if ($user['linkedin']): ?>
<a href="<?= htmlspecialchars($user['linkedin']); ?>" target="_blank"><i class="fab fa-linkedin"></i></a>
<?php endif; ?>

</div>
<?php endif; ?>

</div>
</main>
</div>

<script>
function toggleForm(id) {
    const form = document.getElementById(id);
    if (form.style.display === "none" || form.style.display === "") {
        form.style.display = "block";
    } else {
        form.style.display = "none";
    }
}
</script>
</body>
</html>
