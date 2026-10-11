<?php
session_start();
require_once __DIR__ . '/../app/Support.php';
$data = $_SESSION['last_booking'] ?? null;
if (!$data) redirect('index.php');
unset($_SESSION['last_booking']);
?>
<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>ส่งคำจองสำเร็จ</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <header class="topbar"><a class="brand" href="index.php"><span class="brand-icon">✦</span><span>MARINA <small>CAR CARE</small></span></a>
        <nav><a href="index.php">หน้าลูกค้า</a></nav>
    </header>
    <main class="container narrow">
        <section class="panel success-panel">
            <div class="success-icon">✓</div><span class="eyebrow">BOOKING RECEIVED</span>
            <h1>ส่งคำจองเรียบร้อยแล้ว</h1>
            <p>ระบบรับข้อมูลของคุณแล้ว กรุณารอแอดมินตรวจสอบและยืนยันคิว</p>
            <div class="booking-code"><small>รหัสการจอง</small><strong><?= e($data['code']) ?></strong></div>
            <p class="muted">เก็บรหัสและลิงก์แก้ไขนี้ไว้ เพื่อกลับมาตรวจสอบหรือแก้ไขข้อมูลของคุณ</p><a class="btn primary" href="my_booking.php?code=<?= urlencode($data['code']) ?>&token=<?= urlencode($data['token']) ?>">ดูรายละเอียด / แก้ไขการจอง</a><a class="text-link" href="index.php">กลับหน้าหลัก</a>
        </section>
    </main>
</body>

</html>