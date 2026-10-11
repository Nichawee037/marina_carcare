<?php
session_start();
require_once __DIR__ . '/../app/Support.php';
require_once __DIR__ . '/../app/Models/Booking.php';
$m = new Booking();
$code = trim($_GET['code'] ?? $_POST['code'] ?? '');
$phone = trim($_GET['phone'] ?? $_POST['phone'] ?? '');
$errors = [];
$rows = [];
$verified = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
}
if ($code !== '' && $phone !== '') {
  $verifiedBooking = $m->findByCodePhone($code, $phone);
  if ($verifiedBooking) {
    $verified = true;
    $rows = $m->historyByPhone($phone);
  } else $errors[] = 'ไม่พบข้อมูล กรุณาตรวจสอบรหัสการจองและเบอร์โทรศัพท์ที่ใช้จอง';
}
?>
<!doctype html>
<html lang="th">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>ประวัติการจอง | Marina Car Care</title>
  <link rel="stylesheet" href="assets/style.css">
</head>

<body>
  <header class="topbar"><a class="brand" href="index.php"><span class="brand-icon">✦</span><span>MARINA <small>CAR CARE</small></span></a>
    <nav><a href="index.php">หน้าลูกค้า</a></nav>
  </header>
  <main class="container narrow">
    <section class="panel"><span class="eyebrow">CUSTOMER BOOKING HISTORY</span>
      <h1>ประวัติการจองของฉัน</h1>
      <p>กรอกรหัสการจองและเบอร์โทรศัพท์ที่ใช้จอง เพื่อยืนยันตัวตนและดูประวัติการจองทั้งหมดที่ใช้เบอร์นี้</p><?php if ($errors): ?><div class="alert"><?= e(implode(' • ', $errors)) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-grid"><label>รหัสการจอง<input name="code" required value="<?= e($code) ?>" placeholder="เช่น MN261011E7456E"></label><label>เบอร์โทรศัพท์ที่ใช้จอง<input name="phone" required value="<?= e($phone) ?>" placeholder="เบอร์โทรศัพท์ลูกค้า"></label></div><button class="btn primary" type="submit">ตรวจสอบและดูประวัติ</button>
      </form>
      <?php if ($verified): ?><h3>รายการจองของเบอร์ <?= e($phone) ?></h3><?php if (!$rows): ?><p>ยังไม่มีประวัติการจอง</p><?php else: foreach ($rows as $r): ?><div class="panel" style="margin-top:14px">
              <div class="service-row"><strong><?= e($r['booking_code']) ?></strong><span class="status"><?= e($r['status']) ?></span></div>
              <p>วันที่จอง: <?= e($r['booking_date']) ?> เวลา <?= e(substr($r['booking_time'], 0, 5)) ?> น.</p>
              <p>รถ: <?= e($r['vehicle_name']) ?> · <?= e($r['vehicle_model']) ?> · ทะเบียน <?= e($r['plate_number']) ?></p>
              <p>บริการ: <?= e(implode(', ', array_column($r['services'], 'name'))) ?></p>
              <div class="service-row total"><span>ราคารวม</span><strong><?= money((float)$r['total_price']) ?> บาท</strong></div><a class="btn secondary" href="my_booking.php?code=<?= urlencode($r['booking_code']) ?>&phone=<?= urlencode($phone) ?>">ดูรายละเอียดการจอง</a>
            </div><?php endforeach;
                                                                                                                  endif; ?><?php endif; ?>
    </section>
  </main>
</body>

</html>