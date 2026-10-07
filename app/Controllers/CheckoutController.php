<?php

declare(strict_types=1);

namespace Noir\App\Controllers;

use Noir\App\Repositories\CustomerRepository;
use Noir\App\Services\CartService;
use Noir\App\Services\PaymentService;
use Noir\Core\Container;
use Noir\Core\Request;

/**
 * Handles everything related to the checkout flow and Paystack payment integration.
 * Extracted from StoreController to enforce single-responsibility.
 */
final class CheckoutController
{
    public function checkout(Request $request): never
    {
        if (!Container::get('auth')->check()) {
            $publicPath = $this->publicPath();
            header('Location: ' . $publicPath . '/login?redirect=' . rawurlencode('/checkout'), true, 303);
            exit;
        }

        $publicPath  = $this->publicPath();
        $db          = Container::get('db');
        $currentUser   = Container::get('auth')->user() ?? [];
        $currentUserId = (int) ($currentUser['user_id'] ?? $currentUser['id'] ?? 0);
        $cart        = CartService::syncSessionCart($db, $currentUserId);
        $storeName   = store_setting('store_name', 'Noir & Co.');
        $currency    = active_currency();
        $currencies  = config('payments.currencies', ['NGN', 'USD', 'EUR', 'GBP']);

        if (!in_array($currency, $currencies, true)) {
            $currency = 'NGN';
        }

        $cartSubtotalNgn = 0.0;
        foreach ($cart as $cItem) {
            $cartSubtotalNgn += (float)($cItem['price'] ?? 0) * (int)($cItem['quantity'] ?? 1);
        }
        $shippingOptions = \Noir\App\Services\ShippingService::getOptions($cartSubtotalNgn, $currency);

        $paymentError   = (string) $request->input('error');
        $paymentSuccess = (string) $request->input('success');
        $retryPayment   = null;
        $savedAddresses = [];

        if ($currentUserId > 0) {
            $savedAddresses = (new CustomerRepository(Container::get('db')))->addresses($currentUserId);
        }

        if (!empty($_SESSION['checkout_retry_reference'])) {
            try {
                $retryPayment = (new PaymentService(Container::get('db')))
                    ->recoverablePayment((string) $_SESSION['checkout_retry_reference']);

                if (!$retryPayment) {
                    unset($_SESSION['checkout_retry_reference']);
                }
            } catch (\Throwable) {
                $retryPayment = null;
            }
        }

        require BASE_PATH . '/app/Views/store/checkout.php';
        exit;
    }

    public function startPayment(Request $request): never
    {
        $provider  = (string) $request->input('provider', config('payments.default_provider', 'paystack'));
        $provider  = $provider === 'paystack' ? 'paystack' : '';
        $email     = filter_var(trim((string) $request->input('email')), FILTER_VALIDATE_EMAIL);
        $name      = trim((string) $request->input('name'));
        $address   = trim((string) $request->input('address'));
        $city      = trim((string) $request->input('city'));
        $state     = trim((string) $request->input('state'));
        $currency  = strtoupper((string) $request->input('currency', active_currency()));
        $publicPath = $this->publicPath();

        $botTrap = trim((string) $request->input('_hp_company_fax', ''));
        if ($botTrap !== '') {
            $this->checkoutRedirect($publicPath, 'rejected');
        }

        $recaptchaToken = (string) $request->input('g-recaptcha-response', '');
        if (!recaptcha_verify($recaptchaToken)) {
            $this->checkoutRedirect($publicPath, 'recaptcha_failed');
        }

        if (!$email || $name === '' || mb_strlen($name) < 2 || $address === '' || $city === '' || $provider === '') {
            $this->checkoutRedirect($publicPath, 'details');
        }

        $postalCode = trim((string) $request->input('postal_code', ''));
        $country = trim((string) $request->input('country', 'Nigeria'));

        $validation = \Noir\App\Services\AddressValidationService::validateAndStandardize($address, $city, $state, $postalCode, $country);
        if (!$validation['valid']) {
            $this->checkoutRedirect($publicPath, 'invalid_address');
        }

        $std = $validation['standardized'] ?? [];
        $address = $std['street'] ?? $address;
        $city = $std['city'] ?? $city;
        $state = $std['state'] ?? $state;

        $currencies = config('payments.currencies', ['NGN', 'USD', 'EUR', 'GBP']);
        if (!in_array($currency, $currencies, true)) {
            $currency = 'NGN';
        }

        $secret = (string) config('payments.paystack.secret_key', '');
        if ($secret === '') {
            $this->checkoutRedirect($publicPath, 'payment_unavailable');
        }

        unset($_SESSION['checkout_retry_reference']);

        try {
            $user    = Container::get('auth')->user() ?? [];
            $service = new PaymentService(Container::get('db'));

            $order = $service->createPendingOrder(
                $_SESSION['cart'] ?? [],
                [
                    'name' => $name,
                    'email' => $email,
                    'address' => $address,
                    'city' => $city,
                    'state' => $state,
                    'postal_code' => $std['postal_code'] ?? $postalCode,
                    'country' => $std['country'] ?? $country,
                ],
                (int) ($user['user_id'] ?? $user['id'] ?? 0),
                $currency,
                'paystack',
            );

            $url = $service->initializePaystack(
                $order,
                $email,
                $this->absoluteUrl($publicPath . '/payments/paystack/callback'),
                $secret,
            );

            header('Location: ' . $url, true, 303);
            exit;
        } catch (\Throwable $exception) {
            $this->logFailure('Payment initialization failed: ' . $exception->getMessage());
            $this->checkoutRedirect($publicPath, 'payment_failed');
        }
    }

    public function retryPayment(Request $request): never
    {
        $publicPath = $this->publicPath();
        $reference  = trim((string) $request->input('reference'));
        $secret     = (string) config('payments.paystack.secret_key', '');

        if (
            $reference === ''
            || empty($_SESSION['checkout_retry_reference'])
            || !hash_equals((string) $_SESSION['checkout_retry_reference'], $reference)
            || $secret === ''
        ) {
            $this->checkoutRedirect($publicPath, 'payment_failed');
        }

        try {
            $url = (new PaymentService(Container::get('db')))
                ->retryPaystack($reference, $this->absoluteUrl($publicPath . '/payments/paystack/callback'), $secret);

            unset($_SESSION['checkout_retry_reference']);
            header('Location: ' . $url, true, 303);
            exit;
        } catch (\Throwable $exception) {
            $this->logFailure('Payment retry failed: ' . $exception->getMessage());
            $this->checkoutRedirect($publicPath, 'payment_failed');
        }
    }

    public function paystackCallback(Request $request): never
    {
        $publicPath = $this->publicPath();
        $reference  = trim((string) $request->input('reference'));
        $secret     = (string) config('payments.paystack.secret_key', '');

        if ($reference === '' || $secret === '') {
            $this->checkoutRedirect($publicPath, 'payment_failed');
        }

        try {
            $result = (new PaymentService(Container::get('db')))->verifyPaystack($reference, $secret);

            if ($result['success']) {
                unset($_SESSION['checkout_retry_reference']);
                $_SESSION['recent_order_number'] = $result['order_number'];
                $paidUserId = (int)(Container::get('auth')->id() ?? 0);
                CartService::clearCart(Container::get('db'), $paidUserId);
                header('Location: ' . $publicPath . '/checkout?success=' . rawurlencode($result['order_number']), true, 303);
                exit;
            }
        } catch (\Throwable $exception) {
            $this->logFailure('Payment verification failed: ' . $exception->getMessage());
        }

        $_SESSION['checkout_retry_reference'] = $reference;
        $this->checkoutRedirect($publicPath, 'payment_failed');
    }

    public function paystackWebhook(): never
    {
        $secret    = (string) config('payments.paystack.secret_key', '');
        $body      = file_get_contents('php://input') ?: '';
        $signature = (string) ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '');

        if ($secret === '' || !hash_equals(hash_hmac('sha512', $body, $secret), $signature)) {
            http_response_code(401);
            exit;
        }

        $event     = json_decode($body, true) ?: [];
        $reference = (string) ($event['data']['reference'] ?? '');

        if (($event['event'] ?? '') === 'charge.success' && $reference !== '') {
            try {
                (new PaymentService(Container::get('db')))->verifyPaystack($reference, $secret);
            } catch (\Throwable) {
                http_response_code(500);
                exit;
            }
        }

        http_response_code(200);
        exit;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function checkoutRedirect(string $publicPath, string $error): never
    {
        header('Location: ' . $publicPath . '/checkout?error=' . rawurlencode($error), true, 303);
        exit;
    }

    private function logFailure(string $message): void
    {
        $dir = BASE_PATH . '/storage/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents(
            $dir . '/payment-failures.log',
            sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message),
            FILE_APPEND | LOCK_EX,
        );
    }

    private function publicPath(): string
    {
        return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    }

    private function absoluteUrl(string $path): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? parse_url((string) config('url'), PHP_URL_HOST) ?? 'localhost';
        return $scheme . '://' . $host . $path;
    }
}
