<?php
session_start();
require_once __DIR__ . '/config/database.php';
if (!empty($_SESSION['user_id'])) {
    try {
        $check = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $check->execute([(int)$_SESSION['user_id']]);
        if ($check->fetch()) { header('Location: admin/dashboard.php'); exit; }
    } catch (PDOException $e) {}
    $_SESSION = [];
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = 'نام کاربری و رمز عبور را وارد کنید.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: admin/dashboard.php'); exit;
            }
            $error = 'نام کاربری یا رمز عبور نادرست است.';
        } catch (PDOException $e) {
            $error = 'دیتابیس هنوز راه‌اندازی نشده است. ابتدا setup.php را اجرا کنید.';
        }
    }
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>ورود | کلاس من</title><link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
<div class="login-orb orb1"></div><div class="login-orb orb2"></div>
<div class="login-card">
  <div class="brand-mark">CM</div>
  <div class="brand-title">کلاس من</div>
  <div class="brand-sub">سامانه هوشمند مدیریت کلاس</div>
  <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="login-form">
    <label>نام کاربری</label><div class="input-icon"><span>◉</span><input name="username" value="admin" required></div>
    <label>رمز عبور</label><div class="input-icon"><span>◆</span><input type="password" name="password" value="admin" required></div>
    <button class="btn primary full" type="submit">ورود به پنل <span>←</span></button>
  </form>
  <div class="demo-box">ورود آزمایشی: <b>admin</b> / <b>admin</b></div>
</div>
</body></html>