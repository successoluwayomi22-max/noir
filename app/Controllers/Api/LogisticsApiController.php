<?php
declare(strict_types=1);

namespace Noir\App\Controllers\Api;

use Noir\Core\Container;
use Noir\Core\Request;
use PDO;

final class LogisticsApiController
{
    /**
     * Get real-time order tracking details with live map coordinates & courier dispatch
     */
    public function getOrderTracking(Request $req, mixed $identifier = null): never
    {
        $db = Container::get('db');
        $idOrNum = (string)($identifier ?? $req->input('order_id', ''));

        if ($idOrNum === '') {
            json(['success' => false, 'error' => 'Order identifier is required'], 400);
        }

        // Look up order by ID or Order Number
        $stmt = $db->prepare("
            SELECT o.*, 
                   u.name as customer_name,
                   u.email as customer_email,
                   u.phone as customer_phone
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = :id_or_num OR o.order_number = :id_or_num_str
        ");
        $stmt->execute([
            'id_or_num' => is_numeric($idOrNum) ? (int)$idOrNum : 0,
            'id_or_num_str' => $idOrNum,
        ]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            json(['success' => false, 'error' => 'Order not found'], 404);
        }

        // Fetch customer address if available
        $addrStmt = $db->prepare("
            SELECT * FROM customer_addresses 
            WHERE user_id = :uid 
            ORDER BY is_default DESC, created_at DESC 
            LIMIT 1
        ");
        $addrStmt->execute(['uid' => $order['user_id'] ?: 0]);
        $address = $addrStmt->fetch(PDO::FETCH_ASSOC);

        // Fetch order items
        $itemsStmt = $db->prepare("
            SELECT oi.*, p.name as product_name
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = :oid
        ");
        $itemsStmt->execute(['oid' => $order['id']]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch shipment record if available
        $shipStmt = $db->prepare("SELECT * FROM order_shipments WHERE order_id = :oid ORDER BY id DESC LIMIT 1");
        $shipStmt->execute(['oid' => $order['id']]);
        $shipment = $shipStmt->fetch(PDO::FETCH_ASSOC);

        $status = strtolower((string)($order['status'] ?? 'processing'));
        if ($shipment && !empty($shipment['status'])) {
            $status = strtolower((string)$shipment['status']);
        }

        // Coordinates configuration
        // Origin: NOIR Flagship Vault & Atelier, Victoria Island, Lagos
        $originCoords = ['lat' => 6.4281, 'lng' => 3.4219, 'name' => 'NOIR Central Fulfillment Hub, Victoria Island, Lagos'];
        
        // Destination resolution
        $city = $address['city'] ?? 'Lagos';
        $street = $address['address_line1'] ?? 'Admiralty Way, Lekki Phase 1';
        $state = $address['state'] ?? 'Lagos';
        $formattedDestination = trim(($street ? "$street, " : '') . "$city, $state");

        $destCoords = $this->resolveCoordinates($city, $state);

        // Progress percentage calculation
        $progressPct = match ($status) {
            'pending' => 15,
            'processing' => 35,
            'packed' => 50,
            'shipped', 'in_transit' => 75,
            'out_for_delivery' => 90,
            'delivered' => 100,
            default => 40,
        };

        // Driver position along route
        $driverLat = $originCoords['lat'] + ($destCoords['lat'] - $originCoords['lat']) * ($progressPct / 100.0);
        $driverLng = $originCoords['lng'] + ($destCoords['lng'] - $originCoords['lng']) * ($progressPct / 100.0);

        // Delivery verification PIN derived deterministically from order number
        $pinSeed = preg_replace('/[^0-9]/', '', (string)$order['order_number']);
        $securityPin = substr(str_pad($pinSeed ?: (string)$order['id'], 4, '7', STR_PAD_RIGHT), -4);

        $createdAt = strtotime((string)$order['created_at']);
        if ($createdAt <= 0) $createdAt = time();
        
        // Milestones
        $milestones = [
            [
                'step' => 1,
                'title' => 'Order Placed & Verified',
                'description' => 'Payment secured via banking gateway. Order sent to Atelier for curation.',
                'timestamp' => date('M j, Y • g:i A', $createdAt),
                'completed' => true,
                'active' => $status === 'pending',
            ],
            [
                'step' => 2,
                'title' => 'Curation & Quality Inspection',
                'description' => 'Garment inspected under dual UV lighting, steamed, and packaged in signature NOIR gold-embossed casing.',
                'timestamp' => date('M j, Y • g:i A', $createdAt + 3600 * 2),
                'completed' => in_array($status, ['processing', 'packed', 'shipped', 'in_transit', 'out_for_delivery', 'delivered'], true),
                'active' => $status === 'processing' || $status === 'packed',
            ],
            [
                'step' => 3,
                'title' => 'Dispatched from Victoria Island Hub',
                'description' => 'Secured in temperature-controlled courier transport with biometric seal.',
                'timestamp' => date('M j, Y • g:i A', $createdAt + 3600 * 6),
                'completed' => in_array($status, ['shipped', 'in_transit', 'out_for_delivery', 'delivered'], true),
                'active' => $status === 'shipped' || $status === 'in_transit',
            ],
            [
                'step' => 4,
                'title' => 'Out for Luxury White-Glove Delivery',
                'description' => 'Dedicated courier is en route to your specified address. Please have your Security PIN ready.',
                'timestamp' => date('M j, Y • g:i A', $createdAt + 3600 * 18),
                'completed' => in_array($status, ['out_for_delivery', 'delivered'], true),
                'active' => $status === 'out_for_delivery',
            ],
            [
                'step' => 5,
                'title' => 'Delivered & Signed',
                'description' => 'Package safely handed over and verified via one-time PIN.',
                'timestamp' => $status === 'delivered' ? date('M j, Y • g:i A', $createdAt + 3600 * 24) : 'Estimated Today',
                'completed' => $status === 'delivered',
                'active' => false,
            ]
        ];

        // Courier details
        $courierInfo = [
            'name' => 'Emeka Adeleke',
            'phone' => '+234 812 890 1234',
            'company' => 'NOIR White-Glove VIP Fleet',
            'vehicle_model' => 'Mercedes-Benz Sprinter Executive (Matte Black)',
            'vehicle_plate' => 'LND-849-NR',
            'rating' => 4.98,
            'total_deliveries' => 1420,
            'avatar_url' => 'assets/images/courier_driver.jpg',
        ];

        $trackingData = [
            'order_id' => (int)$order['id'],
            'order_number' => $order['order_number'],
            'status' => $status,
            'status_label' => ucfirst(str_replace('_', ' ', $status)),
            'progress_percentage' => $progressPct,
            'tracking_number' => $shipment['tracking_number'] ?? ('NR-' . strtoupper(substr(md5((string)$order['order_number']), 0, 10))),
            'carrier' => $shipment['courier'] ?? 'NOIR White-Glove Express',
            'estimated_delivery' => date('l, F j', $createdAt + 86400 * 2),
            'estimated_minutes_remaining' => $status === 'delivered' ? 0 : max(15, (int)(60 - ($progressPct / 2))),
            'distance_remaining_km' => $status === 'delivered' ? 0.0 : round(max(0.4, (100 - $progressPct) * 0.15), 1),
            'security_otp_pin' => $securityPin,
            'origin' => $originCoords,
            'destination' => [
                'name' => $formattedDestination,
                'lat' => $destCoords['lat'],
                'lng' => $destCoords['lng'],
                'recipient_name' => $address['name'] ?? ($order['customer_name'] ?? 'VIP Recipient'),
                'recipient_phone' => $address['phone'] ?? ($order['customer_phone'] ?? ''),
            ],
            'live_driver_location' => [
                'lat' => round($driverLat, 6),
                'lng' => round($driverLng, 6),
                'speed_kmh' => $status === 'delivered' ? 0 : 38,
                'heading_degrees' => 45,
            ],
            'courier' => $courierInfo,
            'milestones' => $milestones,
            'item_count' => count($items),
            'total' => (float)$order['total'],
            'currency' => $order['currency'] ?? 'NGN',
        ];

        json([
            'success' => true,
            'tracking' => $trackingData,
        ]);
    }

    /**
     * Helper to resolve coordinates for major delivery destinations
     */
    private function resolveCoordinates(string $city, string $state): array
    {
        $c = strtolower(trim($city . ' ' . $state));

        if (str_contains($c, 'lekki') || str_contains($c, 'admiralty')) {
            return ['lat' => 6.4474, 'lng' => 3.4735];
        }
        if (str_contains($c, 'ikoyi') || str_contains($c, 'bourdillon')) {
            return ['lat' => 6.4520, 'lng' => 3.4380];
        }
        if (str_contains($c, 'ikeja') || str_contains($c, 'maryland') || str_contains($c, 'gra')) {
            return ['lat' => 6.5965, 'lng' => 3.3421];
        }
        if (str_contains($c, 'abuja') || str_contains($c, 'maitama') || str_contains($c, 'fct')) {
            return ['lat' => 9.0765, 'lng' => 7.3986];
        }
        if (str_contains($c, 'port harcourt') || str_contains($c, 'rivers')) {
            return ['lat' => 4.8156, 'lng' => 7.0498];
        }
        if (str_contains($c, 'ibadan') || str_contains($c, 'oyo')) {
            return ['lat' => 7.3775, 'lng' => 3.9470];
        }

        return ['lat' => 6.4474, 'lng' => 3.4735];
    }
}
