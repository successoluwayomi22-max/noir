<?php

namespace Noir\Controllers\Api;

class LogisticsController {
    
    /**
     * Dispatch an order to a 3PL partner (e.g. DHL, ShipBob)
     */
    public function dispatchOrder($request) {
        // In a real scenario, this gets triggered automatically by the payment webhook
        // (e.g. Stripe checkout.session.completed)

        $orderId = $_POST['order_id'] ?? null;
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Missing order ID']);
            return;
        }

        // 1. Fetch order details from database...
        // 2. Format payload for external courier API
        $courierPayload = [
            'order_reference' => 'NOIR-' . $orderId,
            'destination' => [
                'name' => 'John Doe',
                'address' => '123 Fashion Street, Lagos',
                'phone' => '+2348000000000'
            ],
            'packages' => [
                ['weight' => 1.5, 'dimensions' => '30x20x10']
            ]
        ];

        // 3. Make HTTP request to Courier API (Simulated)
        $ch = curl_init('https://api.fake-courier.com/v1/shipments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($courierPayload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . getenv('COURIER_API_KEY')
        ]);
        
        // $response = curl_exec($ch);
        // curl_close($ch);

        // Simulated Success Response
        $trackingNumber = 'TRK' . rand(1000000, 9999999);
        
        // 4. Update local database order status to 'shipped' and save tracking number

        echo json_encode([
            'success' => true, 
            'message' => 'Order dispatched successfully',
            'tracking_number' => $trackingNumber,
            'status' => 'shipped'
        ]);
    }
}
