<?php
declare(strict_types=1);
require_once __DIR__ . '/../Database.php';
class Booking {
    private PDO $db;
    public function __construct() { $this->db = Database::connection(); }
    public function vehicleTypes(): array { return $this->db->query("SELECT v.* FROM vehicle_types v JOIN (SELECT name, MIN(id) AS id FROM vehicle_types WHERE active=1 GROUP BY name) d ON d.id=v.id ORDER BY v.id")->fetchAll(); }
    public function services(): array { return $this->db->query("SELECT s.* FROM services s JOIN (SELECT name, MIN(id) AS id FROM services WHERE active=1 GROUP BY name) d ON d.id=s.id ORDER BY s.id")->fetchAll(); }
    public function calculatePrice(int $vehicleTypeId, array $serviceIds): array {
        $stmt = $this->db->prepare("SELECT price_multiplier FROM vehicle_types WHERE id=? AND active=1");
        $stmt->execute([$vehicleTypeId]); $vehicle = $stmt->fetch();
        if (!$vehicle) throw new RuntimeException('กรุณาเลือกประเภทรถ');
        $ids = array_values(array_unique(array_filter(array_map('intval', $serviceIds), fn($v)=>$v>0)));
        if (!$ids) throw new RuntimeException('กรุณาเลือกบริการอย่างน้อย 1 รายการ');
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT id,name,base_price FROM services WHERE active=1 AND id IN ($marks)");
        $stmt->execute($ids); $services = $stmt->fetchAll();
        if (!$services) throw new RuntimeException('ไม่พบบริการที่เลือก');
        $total = 0; foreach ($services as &$s) { $s['price'] = round((float)$s['base_price'] * (float)$vehicle['price_multiplier'], 2); $total += $s['price']; }
        return ['services'=>$services, 'total'=>$total, 'multiplier'=>(float)$vehicle['price_multiplier']];
    }
    public function create(array $data, array $serviceIds): array {
        $pricing = $this->calculatePrice((int)$data['vehicle_type_id'], $serviceIds);
        $token = bin2hex(random_bytes(24));
        $code = 'MN' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO bookings (booking_code,customer_name,phone,vehicle_type_id,vehicle_model,plate_number,booking_date,booking_time,note,image_path,total_price,edit_token) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$code,$data['customer_name'],$data['phone'],$data['vehicle_type_id'],$data['vehicle_model'],$data['plate_number'],$data['booking_date'],$data['booking_time'],$data['note'],$data['image_path'],$pricing['total'],$token]);
            $id = (int)$this->db->lastInsertId();
            $this->saveServices($id, $pricing['services']);
            $this->db->commit();
            return ['id'=>$id,'code'=>$code,'token'=>$token,'total'=>$pricing['total']];
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }
    private function saveServices(int $bookingId, array $services): void {
        $stmt = $this->db->prepare("INSERT INTO booking_services (booking_id,service_id,service_price) VALUES (?,?,?)");
        foreach ($services as $s) $stmt->execute([$bookingId,$s['id'],$s['price']]);
    }
    public function findByCodeToken(string $code, string $token): ?array {
        $stmt=$this->db->prepare("SELECT b.*,v.name AS vehicle_name FROM bookings b JOIN vehicle_types v ON v.id=b.vehicle_type_id WHERE b.booking_code=? AND b.edit_token=?");
        $stmt->execute([$code,$token]); $b=$stmt->fetch();
        if ($b) { $b['services']=$this->servicesFor((int)$b['id']); }
        return $b ?: null;
    }
    public function findByCodePhone(string $code, string $phone): ?array {
        $stmt=$this->db->prepare("SELECT b.*,v.name AS vehicle_name FROM bookings b JOIN vehicle_types v ON v.id=b.vehicle_type_id WHERE b.booking_code=? AND REPLACE(REPLACE(REPLACE(b.phone,' ',''),'-',''),'+','')=REPLACE(REPLACE(REPLACE(?,' ',''),'-',''),'+','')");
        $stmt->execute([$code,$phone]); $b=$stmt->fetch();
        if ($b) { $b['services']=$this->servicesFor((int)$b['id']); }
        return $b ?: null;
    }
    public function historyByPhone(string $phone): array {
        $stmt=$this->db->prepare("SELECT b.*,v.name AS vehicle_name FROM bookings b JOIN vehicle_types v ON v.id=b.vehicle_type_id WHERE REPLACE(REPLACE(REPLACE(b.phone,' ',''),'-',''),'+','')=REPLACE(REPLACE(REPLACE(?,' ',''),'-',''),'+','') ORDER BY b.created_at DESC");
        $stmt->execute([$phone]); $rows=$stmt->fetchAll();
        foreach ($rows as &$row) $row['services']=$this->servicesFor((int)$row['id']);
        return $rows;
    }
    public function servicesFor(int $id): array {
        $s=$this->db->prepare("SELECT s.name,bs.service_price AS price FROM booking_services bs JOIN services s ON s.id=bs.service_id WHERE bs.booking_id=?");
        $s->execute([$id]); return $s->fetchAll();
    }
    public function update(int $id, array $data, array $serviceIds): float {
        $pricing=$this->calculatePrice((int)$data['vehicle_type_id'],$serviceIds);
        $this->db->beginTransaction();
        try {
            $s=$this->db->prepare("UPDATE bookings SET customer_name=?,phone=?,vehicle_type_id=?,vehicle_model=?,plate_number=?,booking_date=?,booking_time=?,note=?,image_path=COALESCE(?,image_path),total_price=?,status='รอยืนยัน' WHERE id=? AND status='รอยืนยัน'");
            $s->execute([$data['customer_name'],$data['phone'],$data['vehicle_type_id'],$data['vehicle_model'],$data['plate_number'],$data['booking_date'],$data['booking_time'],$data['note'],$data['image_path'],$pricing['total'],$id]);
            $this->db->prepare("DELETE FROM booking_services WHERE booking_id=?")->execute([$id]);
            $this->saveServices($id,$pricing['services']);
            $this->db->commit(); return $pricing['total'];
        } catch(Throwable $e) { $this->db->rollBack(); throw $e; }
    }
    public function all(): array {
        $rows=$this->db->query("SELECT b.*,v.name AS vehicle_name FROM bookings b JOIN vehicle_types v ON v.id=b.vehicle_type_id ORDER BY b.created_at DESC")->fetchAll();
        foreach($rows as &$r) $r['services']=$this->servicesFor((int)$r['id']);
        return $rows;
    }
    public function setStatus(int $id, string $status, string $note=''): void {
        if (!in_array($status,['ยืนยันแล้ว','ปฏิเสธ'],true)) throw new InvalidArgumentException('สถานะไม่ถูกต้อง');
        $s=$this->db->prepare("UPDATE bookings SET status=?,admin_note=? WHERE id=?"); $s->execute([$status,$note,$id]);
    }
}
