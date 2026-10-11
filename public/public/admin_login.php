<?php
session_start();
require_once __DIR__ . '/../app/Support.php';
require_once __DIR__ . '/../app/Models/Admin.php';
if (Admin::check()) redirect('admin.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $a = new Admin();
    if ($a->login(trim($_POST['username'] ?? ''), $_POST['password'] ?? '')) redirect('admin.php');
    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
}
?>
<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>เข้าสู่ระบบแอดมิน</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <header class="topbar"><a class="brand" href="index.php"><span class="brand-icon">✦</span><span>MARINA <small>CAR CARE</small></span></a>
        <nav><a href="index.php">กลับหน้าลูกค้า</a></nav>
    </header>
    <main class="container narrow">
        <section class="panel login-panel">
            <div class="login-symbol">✦</div><span class="eyebrow">ADMIN PORTAL</span>
            <h1>เข้าสู่ระบบแอดมิน</h1>
            <p>จัดการรายการจองคิวของมารีน่าคาร์แคร์</p><?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><label>ชื่อผู้ใช้<input name="username" required autocomplete="username"></label><label>รหัสผ่าน<input type="password" name="password" required autocomplete="current-password"></label><button class="btn primary block" type="submit">เข้าสู่ระบบ →</button></form>
            <p class="muted">ค่าเริ่มต้น: admin / password</p>
        </section>
    </main>
</body>

</html>