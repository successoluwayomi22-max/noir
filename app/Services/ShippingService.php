<?php

declare(strict_types=1);

namespace Noir\App\Services;

/**
 * Luxury Zoned Shipping & White-Glove Dispatch Matrix.
 * Handles zoned logistics fees, free shipping thresholds, and currency conversions.
 */
final class ShippingService
{
    public const FREE_SHIPPING_THRESHOLD_NGN = 500000.00; // Complimentary shipping on orders over ₦500k

    /**
     * Standard base delivery rates in NGN.
     */
    private const ZONES = [
        'local_atelier' => [
            'name' => 'Atelier White-Glove Concierge',
            'description' => 'Dedicated luxury courier dispatch in Lagos & Federal Capital Territory. Same-day / Next-day delivery.',
            'base_ngn' => 5000.00,
            'eta' => 'Same-Day / 24 Hours',
        ],
        'nationwide' => [
            'name' => 'Nationwide Express Courier',
            'description' => 'Armored priority logistics across all Nigerian states with door-to-door signed tracking.',
            'base_ngn' => 8500.00,
            'eta' => '2 – 3 Business Days',
        ],
        'dhl_international' => [
            'name' => 'International DHL Priority Express',
            'description' => 'Air-express global delivery to North America, Europe, UAE, and 120+ destinations.',
            'base_ngn' => 25000.00,
            'eta' => '3 – 5 Business Days',
        ],
    ];

    /**
     * Get all available shipping options formatted for checkout display.
     *
     * @return array<string, array{name: string, description: string, fee: float, fee_formatted: string, eta: string, is_free: bool}>
     */
    public static function getOptions(float $subtotalNgn, string $currency = 'NGN'): array
    {
        $rate = get_exchange_rate($currency);
        $isFreeEligible = ($subtotalNgn >= self::FREE_SHIPPING_THRESHOLD_NGN);
        $options = [];

        foreach (self::ZONES as $key => $zone) {
            $baseFee = $zone['base_ngn'];
            $isFree = $isFreeEligible && ($key !== 'dhl_international'); // International retains discounted rate
            $effectiveNgn = $isFree ? 0.0 : ($isFreeEligible ? $baseFee * 0.5 : $baseFee);
            $convertedFee = round($effectiveNgn * $rate, 2);

            $options[$key] = [
                'name' => $zone['name'],
                'description' => $zone['description'],
                'fee' => $convertedFee,
                'fee_formatted' => $isFree ? 'Complimentary' : (currency_symbol($currency) . ' ' . number_format($convertedFee, 2)),
                'eta' => $zone['eta'],
                'is_free' => $isFree,
            ];
        }

        return $options;
    }

    /**
     * Calculate final shipping fee for an order.
     */
    public static function calculate(string $zoneKey, float $subtotalNgn, string $currency = 'NGN'): array
    {
        $zone = self::ZONES[$zoneKey] ?? self::ZONES['nationwide'];
        $rate = get_exchange_rate($currency);
        $isFreeEligible = ($subtotalNgn >= self::FREE_SHIPPING_THRESHOLD_NGN);
        $isFree = $isFreeEligible && ($zoneKey !== 'dhl_international');
        $effectiveNgn = $isFree ? 0.0 : ($isFreeEligible ? $zone['base_ngn'] * 0.5 : $zone['base_ngn']);
        $convertedFee = round($effectiveNgn * $rate, 2);

        return [
            'zone_key' => $zoneKey,
            'zone_name' => $zone['name'],
            'fee_ngn' => $effectiveNgn,
            'fee_converted' => $convertedFee,
            'currency' => strtoupper($currency),
            'is_free' => $isFree,
            'eta' => $zone['eta'],
        ];
    }
}
