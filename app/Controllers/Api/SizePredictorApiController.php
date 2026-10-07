<?php
declare(strict_types=1);

namespace Noir\App\Controllers\Api;

use Noir\Core\Container;
use PDO;

final class SizePredictorApiController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Container::get('db');
    }

    /**
     * Calculates smart AI size prediction based on body metrics and garment specs
     */
    public function predictSize(): never
    {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?? $_POST;

        $height = (float) ($body['height_cm'] ?? 178.0); // cm
        $weight = (float) ($body['weight_kg'] ?? 72.0); // kg
        $chest = (float) ($body['chest_cm'] ?? 0);
        $waist = (float) ($body['waist_cm'] ?? 0);
        $fitPreference = strtolower(trim((string) ($body['fit_preference'] ?? 'tailored'))); // 'relaxed', 'tailored', 'oversized'
        $productId = (int) ($body['product_id'] ?? 0);

        // Fetch product category if product_id supplied
        $categoryName = 'Outerwear';
        $productName = 'Bespoke Atelier Garment';
        if ($productId > 0) {
            $stmt = $this->db->prepare("SELECT p.name, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ? LIMIT 1");
            $stmt->execute([$productId]);
            $prod = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($prod) {
                $productName = $prod['name'];
                $categoryName = $prod['category_name'] ?? 'Outerwear';
            }
        }

        // 1. Calculate BMI and estimated torso volume
        $heightM = $height / 100.0;
        $bmi = ($heightM > 0) ? ($weight / ($heightM * $heightM)) : 22.0;

        // 2. Estimate chest if not given
        if ($chest <= 0) {
            $chest = ($weight * 0.95) + ($height * 0.2);
        }

        // 3. Determine base alpha and numeric sizes
        $recommendedAlpha = 'M';
        $recommendedNumeric = 40;
        $confidence = 96;

        if ($chest < 88 || $bmi < 19.5) {
            $recommendedAlpha = 'XS';
            $recommendedNumeric = 36;
        } elseif ($chest < 96 || $bmi < 22.5) {
            $recommendedAlpha = 'S';
            $recommendedNumeric = 38;
        } elseif ($chest < 104 || $bmi < 25.5) {
            $recommendedAlpha = 'M';
            $recommendedNumeric = 40;
        } elseif ($chest < 112 || $bmi < 28.5) {
            $recommendedAlpha = 'L';
            $recommendedNumeric = 42;
        } elseif ($chest < 120 || $bmi < 31.5) {
            $recommendedAlpha = 'XL';
            $recommendedNumeric = 44;
        } else {
            $recommendedAlpha = 'XXL';
            $recommendedNumeric = 46;
        }

        // 4. Adjust for fit preference
        if ($fitPreference === 'oversized') {
            if ($recommendedAlpha === 'XS') { $recommendedAlpha = 'S'; $recommendedNumeric = 38; }
            elseif ($recommendedAlpha === 'S') { $recommendedAlpha = 'M'; $recommendedNumeric = 40; }
            elseif ($recommendedAlpha === 'M') { $recommendedAlpha = 'L'; $recommendedNumeric = 42; }
            elseif ($recommendedAlpha === 'L') { $recommendedAlpha = 'XL'; $recommendedNumeric = 44; }
            elseif ($recommendedAlpha === 'XL') { $recommendedAlpha = 'XXL'; $recommendedNumeric = 46; }
        } elseif ($fitPreference === 'tailored' && $bmi > 25 && $bmi < 27) {
            $confidence = 98;
        }

        // 5. Tailoring guidance explanation
        $tailoringNote = "Based on your height of {$height}cm and weight of {$weight}kg with a {$fitPreference} fit preference, Size {$recommendedAlpha} ({$recommendedNumeric}) guarantees an immaculate silhouette with structured shoulder drape.";
        if (stripos($categoryName, 'shoe') !== false || stripos($productName, 'boot') !== false) {
            $recommendedAlpha = 'EU 42 / UK 8';
            $tailoringNote = "Handcrafted Italian leather lasts run true to size. Recommended EU 42.";
        }

        json([
            'success' => true,
            'prediction' => [
                'recommended_size' => "$recommendedAlpha ($recommendedNumeric)",
                'alpha_size' => $recommendedAlpha,
                'numeric_size' => $recommendedNumeric,
                'fit_confidence_percentage' => $confidence,
                'fit_preference' => ucfirst($fitPreference),
                'estimated_measurements' => [
                    'chest_cm' => round($chest, 1),
                    'waist_cm' => round($waist > 0 ? $waist : ($chest * 0.82), 1),
                    'bmi' => round($bmi, 1),
                ],
                'tailoring_guidance' => $tailoringNote,
                'product_context' => [
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'category' => $categoryName,
                ],
            ],
        ]);
    }
}
