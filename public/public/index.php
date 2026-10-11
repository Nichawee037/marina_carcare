<?php
session_start();
require_once __DIR__ . '/../app/Support.php';
require_once __DIR__ . '/../app/Models/Booking.php';
$bookingModel = new Booking();
$types = $bookingModel->vehicleTypes();
$services = $bookingModel->services();
$errors = [];
$preview = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $step = $_POST['step'] ?? '';
  $data = [
    'customer_name' => trim($_POST['customer_name'] ?? ''),
    'phone' => trim($_POST['phone'] ?? ''),
    'vehicle_type_id' => (int)($_POST['vehicle_type_id'] ?? 0),
    'vehicle_model' => trim($_POST['vehicle_model'] ?? ''),
    'plate_number' => trim($_POST['plate_number'] ?? ''),
    'booking_date' => $_POST['booking_date'] ?? '',
    'booking_time' => $_POST['booking_time'] ?? '',
    'note' => trim($_POST['note'] ?? ''),
    'image_path' => null
  ];
  $ids = $_POST['services'] ?? [];
  if ($step === 'confirm') {
    $saved = $_SESSION['booking_draft'] ?? null;
    if (!$saved || !hash_equals($saved['nonce'], (string)($_POST['nonce'] ?? ''))) {
      $errors[] = 'ข้อมูลยืนยันหมดอายุ กรุณากรอกใหม่';
    } else {
      try {
        $result = $bookingModel->create($saved['data'], $saved['services']);
        $_SESSION['last_booking'] = ['code' => $result['code'], 'token' => $result['token']];
        unset($_SESSION['booking_draft']);
        redirect('success.php');
      } catch (Throwable $e) {
        $errors[] = 'บันทึกการจองไม่สำเร็จ กรุณาตรวจสอบฐานข้อมูลและลองใหม่';
      }
    }
  } elseif ($step === 'preview') {
    if (mb_strlen($data['customer_name']) < 2) $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
    if (!preg_match('/^[0-9+\-\s]{9,20}$/', $data['phone'])) $errors[] = 'กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง';
    if (!$data['vehicle_model'] || !$data['plate_number']) $errors[] = 'กรุณากรอกรุ่นรถและทะเบียนรถ';
    if (!$data['booking_date'] || $data['booking_date'] < date('Y-m-d')) $errors[] = 'กรุณาเลือกวันที่ตั้งแต่วันนี้เป็นต้นไป';
    if (!$data['booking_time']) $errors[] = 'กรุณาเลือกเวลา';
    $imagePath = null;
    if (!empty($_FILES['car_image']['name'])) {
      if ($_FILES['car_image']['error'] !== UPLOAD_ERR_OK) $errors[] = 'อัปโหลดรูปภาพไม่สำเร็จ';
      elseif ($_FILES['car_image']['size'] > 5 * 1024 * 1024) $errors[] = 'รูปภาพต้องมีขนาดไม่เกิน 5 MB';
      else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['car_image']['tmp_name']);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
        if (!$ext) $errors[] = 'รองรับเฉพาะไฟล์ JPG, PNG หรือ WEBP';
        else {
          $dir = __DIR__ . '/uploads';
          if (!is_dir($dir)) mkdir($dir, 0755, true);
          $name = bin2hex(random_bytes(12)) . '.' . $ext;
          if (move_uploaded_file($_FILES['car_image']['tmp_name'], $dir . '/' . $name)) $imagePath = 'uploads/' . $name;
        }
      }
    }
    $data['image_path'] = $imagePath;
    if (!$errors) {
      try {
        $pricing = $bookingModel->calculatePrice($data['vehicle_type_id'], $ids);
        $_SESSION['booking_draft'] = ['data' => $data, 'services' => array_map('intval', $ids), 'nonce' => bin2hex(random_bytes(16))];
        $preview = ['data' => $data, 'services' => $pricing['services'], 'total' => $pricing['total'], 'nonce' => $_SESSION['booking_draft']['nonce']];
      } catch (Throwable $e) {
        $errors[] = $e->getMessage();
      }
    }
  }
}
if (!$preview && !empty($_SESSION['booking_draft']) && ($_GET['review'] ?? '') === '1') {
  $d = $_SESSION['booking_draft'];
  $p = $bookingModel->calculatePrice((int)$d['data']['vehicle_type_id'], $d['services']);
  $preview = ['data' => $d['data'], 'services' => $p['services'], 'total' => $p['total'], 'nonce' => $d['nonce']];
}
function oldval($key)
{
  return e((string)($_POST[$key] ?? ''));
}
?>
<!doctype html>
<html lang="th">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>จองคิว | มารีน่าคาร์แคร์</title>
  <link rel="stylesheet" href="assets/style.css">
</head>

<body>
  <header class="topbar"><a class="brand" href="index.php"><span class="brand-icon">✦</span><span>MARINA <small>CAR CARE</small></span></a>
    <nav><a href="index.php">หน้าลูกค้า</a><a href="customer_history.php">ประวัติการจอง</a><a href="admin_login.php">เข้าสู่ระบบแอดมิน</a></nav>
  </header>
  <main class="container">
    <section class="hero">
      <div><span class="eyebrow">SHINE EVERY DAY</span>
        <h1>จองคิวล้างรถ<br><span>มารีน่าคาร์แคร์</span></h1>
        <p>เลือกบริการที่ต้องการ เช็กราคา และยืนยันการจองอย่างมั่นใจ</p>
        <div class="steps"><span class="active">1. กรอกข้อมูล</span><span class="<?= $preview ? 'active' : '' ?>">2. ตรวจสอบ</span><span>3. ยืนยันการจอง</span></div>
      </div>
      <div class="hero-art">
        <div class="sparkle">✦</div>
        <div class="car">🚘</div>
        <div class="bubble">CAR<br>WASH</div>
      </div>
    </section>
    <?php if ($errors): ?><div class="alert"><?= e(implode(' • ', $errors)) ?></div><?php endif; ?>
    <?php if ($preview): ?>
      <section class="panel">
        <div class="section-head">
          <div><span class="eyebrow">STEP 2 OF 3</span>
            <h2>ตรวจสอบข้อมูลการจอง</h2>
            <p>กรุณาตรวจสอบให้ถูกต้อง ก่อนยืนยันการจองอีกครั้ง</p>
          </div><span class="pill">รอตรวจสอบ</span>
        </div>
        <div class="summary-grid"><?php foreach (['customer_name' => 'ชื่อลูกค้า', 'phone' => 'เบอร์โทร', 'vehicle_model' => 'รุ่นรถ', 'plate_number' => 'ทะเบียนรถ', 'booking_date' => 'วันที่จอง', 'booking_time' => 'เวลา'] as $k => $label): ?><div class="summary-item"><small><?= $label ?></small><strong><?= e((string)$preview['data'][$k]) ?></strong></div><?php endforeach; ?><div class="summary-item"><small>ประเภทรถ</small><strong><?php foreach ($types as $t) if ($t['id'] == $preview['data']['vehicle_type_id']) echo e($t['name']); ?></strong></div>
          <div class="summary-item"><small>หมายเหตุ</small><strong><?= e($preview['data']['note'] ?: '-') ?></strong></div>
        </div>
        <h3>บริการที่เลือก</h3>
        <div class="service-list"><?php foreach ($preview['services'] as $s): ?><div class="service-row"><span><?= e($s['name']) ?></span><strong><?= money($s['price']) ?> บาท</strong></div><?php endforeach; ?><div class="service-row total"><span>รวมทั้งหมด</span><strong><?= money($preview['total']) ?> บาท</strong></div>
        </div>
        <?php if ($preview['data']['image_path']): ?><p><img class="preview-image" src="<?= e($preview['data']['image_path']) ?>" alt="รูปรถ"></p><?php endif; ?>
        <div class="actions"><a class="btn secondary" href="index.php?editdraft=1" onclick="return confirm('กลับไปแก้ไขข้อมูลใช่หรือไม่?')">กลับไปแก้ไขข้อมูล</a>
          <form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="step" value="confirm"><input type="hidden" name="nonce" value="<?= e($preview['nonce']) ?>"><button class="btn primary" type="submit">ยืนยันการจองครั้งที่ 2 ✓</button></form>
        </div>
      </section>
    <?php else: ?>
      <?php if (($_GET['editdraft'] ?? '') === '1') unset($_SESSION['booking_draft']); ?>
      <form class="panel" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="step" value="preview">
        <div class="section-head">
          <div><span class="eyebrow">STEP 1 OF 3</span>
            <h2>ข้อมูลสำหรับจองคิว</h2>
            <p>กรอกข้อมูลรถและเลือกบริการที่ต้องการ</p>
          </div><span class="pill">ฟรี ไม่มีค่าจอง</span>
        </div>
        <div class="form-grid"><label>ชื่อ-นามสกุล *<input name="customer_name" required value="<?= oldval('customer_name') ?>" placeholder="ชื่อผู้จอง"></label><label>เบอร์โทรศัพท์ *<input name="phone" required value="<?= oldval('phone') ?>" placeholder="08X-XXX-XXXX"></label>
          <label>ประเภทรถ *<select name="vehicle_type_id" id="vehicleType" required>
              <option value="">-- เลือกประเภทรถ --</option><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>" data-multiplier="<?= $t['price_multiplier'] ?>" <?= (($_POST['vehicle_type_id'] ?? '') == $t['id']) ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
            </select></label><label>รุ่นรถ *<input name="vehicle_model" required value="<?= oldval('vehicle_model') ?>" placeholder="เช่น Toyota Yaris"></label><label>ทะเบียนรถ *<input name="plate_number" required value="<?= oldval('plate_number') ?>" placeholder="เช่น กข 1234"></label><label>วันที่ต้องการจอง *<input type="date" name="booking_date" min="<?= date('Y-m-d') ?>" required value="<?= oldval('booking_date') ?>"></label><label>เวลาที่ต้องการจอง *<select name="booking_time" required>
              <option value="">-- เลือกเวลา --</option><?php foreach (['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'] as $tm): ?><option value="<?= $tm ?>" <?= (($_POST['booking_time'] ?? '') === $tm) ? 'selected' : '' ?>><?= $tm ?> น.</option><?php endforeach; ?>
            </select></label>
        </div>
        <div class="divider"></div>
        <div class="section-head">
          <div>
            <h3>เลือกบริการล้างรถ</h3>
            <p>ราคาคิดตามประเภทรถที่เลือก</p>
          </div>
        </div>
        <div class="service-cards"><?php foreach ($services as $s): ?><label class="service-card"><input type="checkbox" name="services[]" value="<?= $s['id'] ?>" data-price="<?= $s['base_price'] ?>" <?= in_array((string)$s['id'], $_POST['services'] ?? [], true) ? 'checked' : '' ?>><span class="checkmark">✓</span><span class="service-icon"><?= ['ล้างสีรถ' => '✧', 'ดูดฝุ่นภายใน' => '◌', 'ฉีดล้างใต้ท้องรถ' => '⌁', 'ล้างห้องเครื่อง' => '⚙'][$s['name']] ?? '✦' ?></span><strong><?= e($s['name']) ?></strong><small><?= e($s['description'] ?? '') ?></small><b class="service-price" data-base-price="<?= e((string)$s['base_price']) ?>"><?= money((float)$s['base_price']) ?> บาท</b></label><?php endforeach; ?></div>
        <div class="price-preview">
          <div><small>ราคารวมโดยประมาณ</small><strong id="totalPrice">0.00 บาท</strong></div><span>ราคาสุดท้ายขึ้นกับประเภทรถและบริการที่เลือก</span>
        </div>
        <label class="full-label">หมายเหตุเพิ่มเติม<textarea name="note" rows="3" placeholder="รายละเอียดเพิ่มเติม เช่น จุดที่ต้องการเน้นล้าง"><?= oldval('note') ?></textarea></label><label class="upload-box"><span class="upload-icon">＋</span><span><strong>แนบรูปภาพรถ (ไม่บังคับ)</strong><small>ไฟล์ JPG, PNG หรือ WEBP ขนาดไม่เกิน 5 MB</small></span><input type="file" name="car_image" accept="image/jpeg,image/png,image/webp"></label>
        <div class="actions"><span class="muted">* ข้อมูลที่จำเป็นต้องกรอก</span><button class="btn primary" type="submit">ตรวจสอบข้อมูลก่อนจอง →</button></div>
      </form>
    <?php endif; ?>
    <section class="feature-row">
      <div><span>✦</span><strong>ยืนยัน 2 ขั้นตอน</strong><small>ตรวจสอบข้อมูลก่อนส่งจองจริง</small></div>
      <div><span>◈</span><strong>แก้ไขได้</strong><small>กลับไปแก้ข้อมูลก่อนยืนยัน</small></div>
      <div><span>♡</span><strong>บริการครบครัน</strong><small>เลือกเฉพาะบริการที่ต้องการ</small></div>
    </section>
  </main>
  <footer>© <?= date('Y') ?> Marina Car Care · ดูแลรถของคุณด้วยความใส่ใจ</footer>
  <script>
    const type = document.getElementById('vehicleType'),
      checks = [...document.querySelectorAll('input[name="services[]"]')],
      total = document.getElementById('totalPrice');

    function updateTotal() {
      if (!total) return;
      const opt = type?.selectedOptions[0],
        mult = Number(opt?.dataset.multiplier || 1);
      document.querySelectorAll('.service-price').forEach(el => {
        const price = Math.round(Number(el.dataset.basePrice) * mult * 100) / 100;
        el.textContent = price.toLocaleString('th-TH', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        }) + ' บาท';
      });
      const sum = checks.filter(c => c.checked).reduce((n, c) => n + (Math.round(Number(c.dataset.price) * mult * 100) / 100), 0);
      total.textContent = sum.toLocaleString('th-TH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }) + ' บาท';
    }
    type?.addEventListener('change', updateTotal);
    checks.forEach(c => c.addEventListener('change', updateTotal));
    updateTotal();
  </script>
</body>

</html>