CREATE DATABASE IF NOT EXISTS marina_carcare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE marina_carcare;

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(120) NOT NULL DEFAULT 'ผู้ดูแลระบบ',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vehicle_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  price_multiplier DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT NULL,
  base_price DECIMAL(10,2) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_code VARCHAR(30) NOT NULL UNIQUE,
  customer_name VARCHAR(120) NOT NULL UNIQUE,
  phone VARCHAR(30) NOT NULL,
  vehicle_type_id INT NOT NULL,
  vehicle_model VARCHAR(120) NOT NULL,
  plate_number VARCHAR(30) NOT NULL,
  booking_date DATE NOT NULL,
  booking_time TIME NOT NULL,
  note TEXT DEFAULT NULL,
  image_path VARCHAR(255) DEFAULT NULL,
  total_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  status ENUM('รอยืนยัน','ยืนยันแล้ว','ปฏิเสธ','ยกเลิก') NOT NULL DEFAULT 'รอยืนยัน',
  edit_token VARCHAR(64) NOT NULL,
  admin_note VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_booking_vehicle FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types(id)
);

CREATE TABLE IF NOT EXISTS booking_services (
  booking_id INT NOT NULL,
  service_id INT NOT NULL,
  service_price DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (booking_id, service_id),
  CONSTRAINT fk_bs_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  CONSTRAINT fk_bs_service FOREIGN KEY (service_id) REFERENCES services(id)
);

INSERT INTO vehicle_types (name, price_multiplier) VALUES
('รถจักรยานยนต์',0.50),('รถเก๋ง / Eco Car',1.00),('รถกระบะ',1.20),('รถ SUV / PPV',1.30),('รถตู้',1.50)
ON DUPLICATE KEY UPDATE price_multiplier=VALUES(price_multiplier), active=1;

INSERT INTO services (name, description, base_price) VALUES
('ล้างสีรถ','ล้างภายนอกและทำความสะอาดสีรถ',150.00),
('ดูดฝุ่นภายใน','ดูดฝุ่นห้องโดยสารและพรม',100.00),
('ฉีดล้างใต้ท้องรถ','ทำความสะอาดใต้ท้องรถ',120.00),
('ล้างห้องเครื่อง','ทำความสะอาดห้องเครื่องอย่างระมัดระวัง',250.00)
ON DUPLICATE KEY UPDATE description=VALUES(description), base_price=VALUES(base_price), active=1;

-- username: admin / password: password
INSERT INTO admins (username, password_hash, display_name)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'ผู้ดูแลร้าน')
ON DUPLICATE KEY UPDATE username=username;
