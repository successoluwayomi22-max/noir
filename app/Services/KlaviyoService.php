<?php

declare(strict_types=1);

namespace Noir\App\Services;

class KlaviyoService
{
    private string $privateKey;
    private string $baseUrl = 'https://a.klaviyo.com/api/';

    public function __construct()
    {
        $this->privateKey = (string) store_setting('klaviyo_private_key');
    }

    public function isConfigured(): bool
    {
        return $this->privateKey !== '';
    }

    private function request(string $method, string $endpoint, array $data = []): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $ch = curl_init($this->baseUrl . ltrim($endpoint, '/'));
        if ($ch === false) {
            return null;
        }

        $headers = [
            'Authorization: Klaviyo-API-Key ' . $this->privateKey,
            'accept: application/json',
            'revision: 2024-02-15'
        ];

        if ($method === 'POST' || $method === 'PATCH') {
            $headers[] = 'content-type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && is_string($response)) {
            $decoded = json_decode($response, true);
            return is_array($decoded) ? $decoded : [];
        }

        if (function_exists('system_log')) {
            system_log('error', "Klaviyo API error [$method $endpoint] HTTP $httpCode: $response", 'integrations');
        }
        
        return null;
    }

    // ── PROFILES ────────────────────────────────────────────────────────────

    public function createProfile(string $email, string $firstName = '', string $lastName = '', string $phone = ''): ?array
    {
        $payload = [
            'data' => [
                'type' => 'profile',
                'attributes' => [
                    'email'      => $email,
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                ]
            ]
        ];

        if ($phone !== '') {
            $payload['data']['attributes']['phone_number'] = $phone;
        }

        return $this->request('POST', 'profiles/', $payload);
    }

    // ── EVENTS ───────────────────────────────────────────────────────────────

    public function trackEvent(string $eventName, array $profileData, array $eventProperties = []): ?array
    {
        $payload = [
            'data' => [
                'type' => 'event',
                'attributes' => [
                    'profile'    => [
                        'data' => [
                            'type' => 'profile',
                            'attributes' => [
                                'email' => $profileData['email'] ?? '',
                            ]
                        ]
                    ],
                    'metric'     => [
                        'data' => [
                            'type' => 'metric',
                            'attributes' => ['name' => $eventName],
                        ]
                    ],
                    'properties' => $eventProperties,
                    'time'       => date('c'),
                ]
            ]
        ];
        
        return $this->request('POST', 'events/', $payload);
    }

    /**
     * Track a completed order / purchase.
     */
    public function trackPlacedOrder(string $email, string $orderNumber, float $total, string $currency, array $items = []): ?array
    {
        return $this->trackEvent('Placed Order', ['email' => $email], [
            'order_number' => $orderNumber,
            'value'        => $total,
            'currency'     => $currency,
            'item_count'   => count($items),
            'items'        => $items,
            'brand'        => 'Noir',
        ]);
    }

    /**
     * Track when an order is shipped.
     */
    public function trackOrderShipped(string $email, string $orderNumber, string $carrier = '', string $trackingNumber = ''): ?array
    {
        return $this->trackEvent('Order Shipped', ['email' => $email], [
            'order_number'    => $orderNumber,
            'carrier'         => $carrier ?: 'DHL Express',
            'tracking_number' => $trackingNumber,
            'brand'           => 'Noir',
        ]);
    }

    /**
     * Track when an order is delivered.
     */
    public function trackOrderDelivered(string $email, string $orderNumber): ?array
    {
        return $this->trackEvent('Order Delivered', ['email' => $email], [
            'order_number' => $orderNumber,
            'brand'        => 'Noir',
        ]);
    }

    /**
     * Track an abandoned checkout / cart.
     */
    public function trackAbandonedCart(string $email, float $cartValue, array $items = [], string $checkoutUrl = ''): ?array
    {
        return $this->trackEvent('Started Checkout', ['email' => $email], [
            'value'        => $cartValue,
            'currency'     => 'NGN',
            'item_count'   => count($items),
            'items'        => $items,
            'checkout_url' => $checkoutUrl,
            'brand'        => 'Noir',
        ]);
    }

    /**
     * Track a product view.
     */
    public function trackProductViewed(string $email, int $productId, string $productName, float $price, string $imageUrl = ''): ?array
    {
        return $this->trackEvent('Viewed Product', ['email' => $email], [
            'product_id'   => $productId,
            'product_name' => $productName,
            'price'        => $price,
            'currency'     => 'NGN',
            'image_url'    => $imageUrl,
            'brand'        => 'Noir',
        ]);
    }

    /**
     * Subscribe an email to a Klaviyo list.
     */
    public function subscribeToList(string $listId, string $email, string $firstName = ''): ?array
    {
        return $this->request('POST', "lists/$listId/relationships/profiles/", [
            'data' => [[
                'type'       => 'profile',
                'attributes' => [
                    'email'      => $email,
                    'first_name' => $firstName,
                ]
            ]]
        ]);
    }

    /**
     * Send a welcome event after user registers.
     */
    public function trackWelcome(string $email, string $firstName): ?array
    {
        return $this->trackEvent('Account Created', ['email' => $email], [
            'first_name' => $firstName,
            'brand'      => 'Noir',
        ]);
    }
}
