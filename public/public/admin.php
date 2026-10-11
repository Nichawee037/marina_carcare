<?php
session_start();
require_once __DIR__ . '/../app/Support.php';
require_once __DIR__ . '/../app/Models/Admin.php';
require_once __DIR__ . '/../app/Models/Booking.php';
if (!Admin::check()) redirect('admin_login.php');
$m = new Booking();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $m->setStatus((int)($_POST['id'] ?? 0), $_POST['status'] ?? '', trim($_POST['admin_note'] ?? ''));
        $message = 'อัปเดตสถานะการจองแล้ว';
    } catch (Throwable $e) {
        $message = 'ไม่สามารถอัปเดตสถานะได้';
    }
}
$rows = $m->all();
if (($_GET['logout'] ?? '') === '1') {
    session_destroy();
    redirect('index.php');
}
?>
<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>จัดการการจอง</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <header class="topbar"><a class="brand" href="admin.php"><span class="brand-icon">✦</span><span>MARINA <small>ADMIN</small></span></a>
        <nav><span class="admin-welcome">สวัสดี <?= e($_SESSION['admin_name'] ?? 'แอดมิน') ?></span><a href="index.php">หน้าลูกค้า</a><a href="admin.php?logout=1">ออกจากระบบ</a></nav>
    </header>
    <main class="container">
        <section class="hero admin-hero">
            <div><span class="eyebrow">ADMIN DASHBOARD</span>
                <h1>จัดการคิวล้างรถ</h1>
                <p>ตรวจสอบรายละเอียดและยืนยันการจองจากลูกค้า</p>
            </div>
            <div class="admin-stat"><small>รายการจองทั้งหมด</small><strong><?= count($rows) ?></strong><span>รายการ</span></div>
        </section><?php if ($message): ?><div class="success-msg"><?= e($message) ?></div><?php endif; ?><div class="booking-grid"><?php if (!$rows): ?><section class="panel">ยังไม่มีรายการจอง</section><?php endif; ?><?php foreach ($rows as $r): ?><section class="panel booking-card">
                    <div class="booking-top">
                        <div><span class="eyebrow"><?= e($r['booking_code']) ?></span>
                            <h2><?= e($r['customer_name']) ?></h2>
                            <p><?= e($r['phone']) ?></p>
                        </div><span class="status <?= $r['status'] === 'ยืนยันแล้ว' ? 'approved' : ($r['status'] === 'ปฏิเสธ' ? 'rejected' : 'pending') ?>"><?= e($r['status']) ?></span>
                    </div>
                    <div class="summary-grid compact">
                        <div class="summary-item"><small>รถ</small><strong><?= e($r['vehicle_name']) ?> · <?= e($r['vehicle_model']) ?></strong></div>
                        <div class="summary-item"><small>ทะเบียน</small><strong><?= e($r['plate_number']) ?></strong></div>
                        <div class="summary-item"><small>วันและเวลา</small><strong><?= e($r['booking_date']) ?> · <?= e(substr($r['booking_time'], 0, 5)) ?></strong></div>
                        <div class="summary-item"><small>บริการ</small><strong><?= e(implode(', ', array_column($r['services'], 'name'))) ?></strong></div>
                    </div><?php if ($r['note']): ?><p class="admin-note"><strong>หมายเหตุ:</strong> <?= e($r['note']) ?></p><?php endif; ?><?php if ($r['image_path']): ?><a href="<?= e($r['image_path']) ?>" target="_blank"><img class="admin-image" src="<?= e($r['image_path']) ?>" alt="รูปรถที่ลูกค้าแนบ"></a><?php endif; ?><div class="total-admin">ยอดรวม <strong><?= money((float)$r['total_price']) ?> บาท</strong></div><?php if ($r['status'] === 'รอยืนยัน'): ?><form method="post" class="admin-actions"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input name="admin_note" placeholder="หมายเหตุถึงลูกค้า (ถ้ามี)"><button class="btn primary" name="status" value="ยืนยันแล้ว">ยืนยันคิว</button><button class="btn danger" name="status" value="ปฏิเสธ" onclick="return confirm('ยืนยันการปฏิเสธรายการนี้หรือไม่?')">ปฏิเสธ</button></form><?php elseif ($r['admin_note']): ?><p class="muted">หมายเหตุแอดมิน: <?= e($r['admin_note']) ?></p><?php endif; ?>
                </section><?php endforeach; ?></div>
    </main>
    <footer>Marina Car Care · Admin Dashboard</footer>
</body>

</html>