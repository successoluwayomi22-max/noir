<?php
declare(strict_types=1);

namespace Noir\App\Controllers;

use Noir\Core\Container;
use Noir\Core\Request;
use PDO;

final class AdminFleetController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Container::get('db');
    }

    public function dashboard(Request $request): never
    {
        // 1. Get fleet statistics
        $couriers = $this->db->query("
            SELECT id, name, phone, vehicle_model, vehicle_plate, status, current_lat, current_lng, rating, total_deliveries, is_active
            FROM couriers
            ORDER BY status = 'in_transit' DESC, total_deliveries DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // 2. Get active dispatches with order info
        $dispatches = $this->db->query("
            SELECT od.*, o.total, o.currency, 
                   COALESCE(ca.address_line, 'Atelier Delivery Destination') AS shipping_address, 
                   COALESCE(ca.recipient_name, u.name, 'Valued Client') AS shipping_name,
                   c.name AS courier_name, c.vehicle_plate
            FROM order_dispatches od
            LEFT JOIN orders o ON o.id = od.order_id OR (o.order_number = od.order_number COLLATE utf8mb4_unicode_ci)
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN customer_addresses ca ON ca.user_id = o.user_id
            LEFT JOIN couriers c ON c.id = od.courier_id
            ORDER BY od.created_at DESC
            LIMIT 30
        ")->fetchAll(PDO::FETCH_ASSOC);

        // 3. Get pending unassigned orders
        $unassignedOrders = $this->db->query("
            SELECT o.id, o.order_number, o.total, o.currency, 
                   COALESCE(ca.address_line, 'Victoria Island, Lagos') AS shipping_address, 
                   COALESCE(ca.recipient_name, u.name, 'Valued Client') AS shipping_name, 
                   o.created_at
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN customer_addresses ca ON ca.user_id = o.user_id
            LEFT JOIN order_dispatches od ON (od.order_number COLLATE utf8mb4_unicode_ci = o.order_number)
            WHERE od.id IS NULL AND o.status IN ('processing', 'paid', 'pending')
            ORDER BY o.created_at DESC
            LIMIT 20
        ")->fetchAll(PDO::FETCH_ASSOC);

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        
        // Render fleet view
        $viewPath = BASE_PATH . '/app/Views/admin/fleet.php';
        if (file_exists($viewPath)) {
            require $viewPath;
            exit;
        }

        // Fallback JSON if view is not rendered directly
        json([
            'couriers' => $couriers,
            'dispatches' => $dispatches,
            'unassigned_orders' => $unassignedOrders,
        ]);
    }

    public function assignCourier(Request $request): never
    {
        $orderNumber = trim((string) $request->input('order_number'));
        $courierId = (int) $request->input('courier_id');

        if (empty($orderNumber) || $courierId <= 0) {
            header('Location: /admin/fleet?error=missing_data', true, 303);
            exit;
        }

        $oStmt = $this->db->prepare("SELECT id, user_id FROM orders WHERE order_number = ? LIMIT 1");
        $oStmt->execute([$orderNumber]);
        $order = $oStmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            header('Location: /admin/fleet?error=order_not_found', true, 303);
            exit;
        }

        $pin = (string) rand(1000, 9999);
        $orderId = (int) $order['id'];

        $ins = $this->db->prepare("
            INSERT INTO order_dispatches (order_id, order_number, courier_id, dispatch_status, estimated_delivery, security_pin, distance_remaining_km, created_at)
            VALUES (?, ?, ?, 'in_transit', DATE_ADD(NOW(), INTERVAL 45 MINUTE), ?, 3.8, NOW())
            ON DUPLICATE KEY UPDATE courier_id = VALUES(courier_id), dispatch_status = 'in_transit', updated_at = NOW()
        ");
        $ins->execute([$orderId, $orderNumber, $courierId, $pin]);

        // Update courier status
        $this->db->prepare("UPDATE couriers SET status = 'in_transit' WHERE id = ?")->execute([$courierId]);

        // Update order status to shipped
        $this->db->prepare("UPDATE orders SET status = 'shipped' WHERE id = ?")->execute([$orderId]);

        if (!empty($order['user_id'])) {
            $this->db->prepare("
                INSERT INTO customer_notifications (user_id, title, body, created_at)
                VALUES (?, '🚀 Order Dispatched with VIP Courier', ?, NOW())
            ")->execute([(int) $order['user_id'], "Order #$orderNumber is en route with your dedicated courier. Handshake PIN: $pin."]);
        }

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        header('Location: ' . ($base ?: '') . '/admin/fleet?assigned=1', true, 303);
        exit;
    }

    public function addCourier(Request $request): never
    {
        $name = trim((string) $request->input('name'));
        $phone = trim((string) $request->input('phone'));
        $email = trim((string) $request->input('email'));
        $vehicleModel = trim((string) $request->input('vehicle_model'));
        $vehiclePlate = trim((string) $request->input('vehicle_plate'));
        $vehicleType = trim((string) $request->input('vehicle_type', 'car'));

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

        if (empty($name) || empty($phone) || empty($vehiclePlate)) {
            header('Location: ' . ($base ?: '') . '/admin/fleet?error=missing_driver_fields', true, 303);
            exit;
        }

        $ins = $this->db->prepare("
            INSERT INTO couriers (name, phone, email, vehicle_type, vehicle_model, vehicle_plate, status, rating, total_deliveries, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 'available', 5.00, 0, 1, NOW(), NOW())
        ");
        $ins->execute([$name, $phone, $email, $vehicleType, $vehicleModel, $vehiclePlate]);

        header('Location: ' . ($base ?: '') . '/admin/fleet?driver_registered=1', true, 303);
        exit;
    }

    public function deleteCourier(Request $request): never
    {
        $id = (int) $request->input('id');
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

        if ($id > 0) {
            $this->db->prepare("DELETE FROM couriers WHERE id = ?")->execute([$id]);
        }

        header('Location: ' . ($base ?: '') . '/admin/fleet?driver_deleted=1', true, 303);
        exit;
    }
}
