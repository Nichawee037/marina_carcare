<?php
session_start();
require_once __DIR__ . '/../app/Support.php';
require_once __DIR__ . '/../app/Models/Booking.php';
$m = new Booking();
$code = trim($_GET['code'] ?? $_POST['code'] ?? '');
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$phone = trim($_GET['phone'] ?? $_POST['phone'] ?? '');
$b = $token !== '' ? $m->findByCodeToken($code, $token) : ($phone !== '' ? $m->findByCodePhone($code, $phone) : null);
if (!$b && $code === '') { /* หน้าเข้าดูประวัติ */
} elseif (!$b) {
    http_response_code(404);
    exit('ไม่พบข้อมูลการจอง กรุณาตรวจสอบรหัสการจองและเบอร์โทรศัพท์ที่ใช้จอง');
}
if ($b && $token === '') $token = $b['edit_token'];
if (!$b) {
    header('Location: customer_history.php');
    exit;
}
$errors = [];
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if ($b['status'] !== 'รอยืนยัน') $errors[] = 'รายการนี้ยืนยันหรือปฏิเสธแล้ว จึงไม่สามารถแก้ไขได้';
    else {
        $d = ['customer_name' => trim($_POST['customer_name'] ?? ''), 'phone' => trim($_POST['phone'] ?? ''), 'vehicle_type_id' => (int)($_POST['vehicle_type_id'] ?? 0), 'vehicle_model' => trim($_POST['vehicle_model'] ?? ''), 'plate_number' => trim($_POST['plate_number'] ?? ''), 'booking_date' => $_POST['booking_date'] ?? '', 'booking_time' => $_POST['booking_time'] ?? '', 'note' => trim($_POST['note'] ?? ''), 'image_path' => null];
        try {
            if (!$d['customer_name'] || !preg_match('/^[0-9+\-\s]{9,20}$/', $d['phone']) || !$d['vehicle_model'] || !$d['plate_number'] || $d['booking_date'] < date('Y-m-d')) throw new RuntimeException('กรุณากรอกข้อมูลที่จำเป็นให้ถูกต้อง');
            $path = null;
            if (!empty($_FILES['car_image']['name'])) {
                if ($_FILES['car_image']['size'] > 5 * 1024 * 1024) throw new RuntimeException('รูปภาพต้องไม่เกิน 5 MB');
                $f = new finfo(FILEINFO_MIME_TYPE);
                $mime = $f->file($_FILES['car_image']['tmp_name']);
                $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
                if (!$ext) throw new RuntimeException('รองรับ JPG, PNG, WEBP เท่านั้น');
                $dir = __DIR__ . '/uploads';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $name = bin2hex(random_bytes(12)) . '.' . $ext;
                if (move_uploaded_file($_FILES['car_image']['tmp_name'], $dir . '/' . $name)) $path = 'uploads/' . $name;
            }
            $m->update((int)$b['id'], $d, $_POST['services'] ?? []);
            $message = 'บันทึกการแก้ไขเรียบร้อยแล้ว';
            $b = $m->findByCodeToken($code, $token);
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}
$types = $m->vehicleTypes();
$services = $m->services();
?>
<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>การจองของฉัน</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <header class="topbar"><a class="brand" href="index.php"><span class="brand-icon">✦</span><span>MARINA <small>CAR CARE</small></span></a>
        <nav><a href="index.php">หน้าลูกค้า</a><a href="customer_history.php">ประวัติการจอง</a></nav>
    </header>
    <main class="container narrow">
        <section class="panel"><span class="eyebrow">MY BOOKING</span>
            <h1>การจองของฉัน</h1>
            <p>รหัสการจอง <strong><?= e($b['booking_code']) ?></strong> · สถานะ <span class="status"><?= e($b['status']) ?></span></p>
            <p><a class="text-link" href="customer_history.php?code=<?= urlencode($b['booking_code']) ?>&phone=<?= urlencode($b['phone']) ?>">ดูประวัติการจองทั้งหมดของฉัน</a></p><?php if ($message): ?><div class="success-msg"><?= e($message) ?></div><?php endif; ?><?php if ($errors): ?><div class="alert"><?= e(implode(' • ', $errors)) ?></div><?php endif; ?>
            <?php if ($b['status'] === 'รอยืนยัน'): ?><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="code" value="<?= e($code) ?>"><input type="hidden" name="token" value="<?= e($token) ?>">
                    <div class="form-grid"><label>ชื่อ-นามสกุล<input required name="customer_name" value="<?= e($b['customer_name']) ?>"></label><label>เบอร์โทร<input required name="phone" value="<?= e($b['phone']) ?>"></label><label>ประเภทรถ<select name="vehicle_type_id"><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>" <?= $t['id'] == $b['vehicle_type_id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select></label><label>รุ่นรถ<input required name="vehicle_model" value="<?= e($b['vehicle_model']) ?>"></label><label>ทะเบียนรถ<input required name="plate_number" value="<?= e($b['plate_number']) ?>"></label><label>วันที่จอง<input type="date" min="<?= date('Y-m-d') ?>" required name="booking_date" value="<?= e($b['booking_date']) ?>"></label><label>เวลา<select name="booking_time"><?php foreach (['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'] as $tm): ?><option value="<?= $tm ?>" <?= substr($b['booking_time'], 0, 5) === $tm ? 'selected' : '' ?>><?= $tm ?></option><?php endforeach; ?></select></label></div>
                    <h3>บริการ</h3>
                    <div class="service-list"><?php foreach ($services as $s): ?><label class="service-row"><span><input type="checkbox" name="services[]" value="<?= $s['id'] ?>" <?= array_filter($b['services'], fn($x) => $x['name'] === $s['name']) ? 'checked' : '' ?>> <?= e($s['name']) ?></span><strong>เริ่ม <?= money((float)$s['base_price']) ?> บาท</strong></label><?php endforeach; ?></div><label class="full-label">หมายเหตุ<textarea name="note" rows="3"><?= e($b['note']) ?></textarea></label><label class="upload-box"><span class="upload-icon">＋</span><span><strong>เปลี่ยน/เพิ่มรูปภาพรถ</strong><small>JPG, PNG, WEBP ไม่เกิน 5 MB</small></span><input type="file" name="car_image" accept="image/jpeg,image/png,image/webp"></label><button class="btn primary" type="submit">บันทึกการแก้ไข</button>
                </form><?php else: ?><p>รายการนี้ไม่สามารถแก้ไขได้ เนื่องจากแอดมินดำเนินการแล้ว</p><?php endif; ?>
            <h3>บริการที่บันทึกไว้</h3><?php foreach ($b['services'] as $s): ?><div class="service-row"><span><?= e($s['name']) ?></span><strong><?= money((float)$s['price']) ?> บาท</strong></div><?php endforeach; ?><div class="service-row total"><span>ราคารวม</span><strong><?= money((float)$b['total_price']) ?> บาท</strong></div><?php if ($b['image_path']): ?><p><img class="preview-image" src="<?= e($b['image_path']) ?>" alt="รูปรถ"></p><?php endif; ?>
        </section>
    </main>
</body>

</html>