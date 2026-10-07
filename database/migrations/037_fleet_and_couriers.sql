-- Migration 037: Fleet and Couriers Management
CREATE TABLE IF NOT EXISTS couriers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(120) DEFAULT NULL,
    vehicle_type ENUM('van', 'car', 'motorcycle', 'armored') DEFAULT 'van',
    vehicle_model VARCHAR(100) NOT NULL,
    vehicle_plate VARCHAR(30) NOT NULL,
    status ENUM('available', 'in_transit', 'off_duty', 'maintenance') DEFAULT 'available',
    current_lat DECIMAL(10, 7) DEFAULT 6.4281000,
    current_lng DECIMAL(10, 7) DEFAULT 3.4219000,
    rating DECIMAL(3, 2) DEFAULT 4.95,
    total_deliveries INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_dispatches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    order_number VARCHAR(60) NOT NULL,
    courier_id INT NOT NULL,
    dispatch_status ENUM('assigned', 'picked_up', 'in_transit', 'arriving', 'delivered', 'failed') DEFAULT 'assigned',
    pickup_time DATETIME DEFAULT NULL,
    estimated_delivery DATETIME DEFAULT NULL,
    actual_delivery DATETIME DEFAULT NULL,
    security_pin VARCHAR(8) NOT NULL,
    recipient_notes TEXT DEFAULT NULL,
    current_lat DECIMAL(10, 7) DEFAULT NULL,
    current_lng DECIMAL(10, 7) DEFAULT NULL,
    distance_remaining_km DECIMAL(6, 2) DEFAULT 3.50,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (order_number),
    INDEX (courier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default VIP couriers
INSERT IGNORE INTO couriers (id, name, phone, email, vehicle_type, vehicle_model, vehicle_plate, status, current_lat, current_lng, rating, total_deliveries, is_active) VALUES
(1, 'Emeka Adeleke', '+2348006647589', 'emeka.adeleke@noiratelier.com', 'van', 'Mercedes-Benz Sprinter Executive', 'LND-849-NR', 'in_transit', 6.4420000, 3.4480000, 4.98, 412, 1),
(2, 'Oluwaseun Bakare', '+2348035512390', 'seun.bakare@noiratelier.com', 'car', 'Range Rover Velar Black Edition', 'IKJ-772-NR', 'available', 6.4281000, 3.4219000, 4.95, 280, 1),
(3, 'Chinedu Eze', '+2348123349911', 'chinedu.eze@noiratelier.com', 'armored', 'Mercedes G63 Armored Escort', 'ABJ-901-NR', 'available', 9.0765000, 7.3986000, 4.99, 165, 1);
