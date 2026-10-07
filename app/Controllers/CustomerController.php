<?php

declare(strict_types=1);

namespace Noir\App\Controllers;

use Noir\App\Repositories\CustomerRepository;
use Noir\App\Traits\ManagesProfileImage;
use Noir\Core\Container;
use Noir\Core\Request;
use Noir\Core\Mailer;

final class CustomerController
{
    use ManagesProfileImage;

    private function repository(): CustomerRepository
    {
        return new CustomerRepository(Container::get('db'));
    }

    private function userId(): int
    {
        $user = \Noir\Core\Auth::user();
        return (int)($user['user_id'] ?? $user['id'] ?? 0);
    }

    private function render(string $page, array $data = []): never
    {
        $repository = $this->repository();
        $userId = $this->userId();
        $customer = $repository->customer($userId);
        $customerStats = $repository->stats($userId);
        $unreadNotifications = $repository->unreadNotifications($userId);
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

        $viewFile = BASE_PATH . '/app/Views/customer/' . $page . '.php';
        require BASE_PATH . '/app/Views/customer/components.php';
        extract($data, EXTR_SKIP);
        require BASE_PATH . '/app/Views/customer/layout.php';
        exit;
    }

    public function dashboard(): never
    {
        $r = $this->repository();
        $this->render('dashboard', [
            'stats' => $r->stats($this->userId()),
            'orders' => $r->orders($this->userId()),
            'products' => $r->products(),
            'viewed' => $r->products(),
            'coupons' => [],
            'notifications' => $r->notifications($this->userId()),
            'unreadNotifications' => $r->unreadNotifications($this->userId())
        ]);
    }

    public function orders(): never
    {
        $this->render('orders', [
            'orders' => $this->repository()->orders($this->userId())
        ]);
    }

    public function orderDetails(Request $request): never
    {
        $order = $this->repository()->order($this->userId(), (string)($request->params['number'] ?? ''));

        if (!$order) {
            http_response_code(404);
            echo 'Order not found';
            exit;
        }

        $this->render('order-details', ['order' => $order]);
    }

    public function wishlist(): never
    {
        $products = $this->repository()->wishlist($this->userId());

        foreach ($products as &$product) {
            $path = (string)($product['image'] ?? '');
            $local = $path && $path[0] === '/' ? BASE_PATH . '/public' . $path : '';

            if (!$path || preg_match('#^https?://#i', $path) || ($local && !is_file($local))) {
                $product['image'] = catalogue_image_for($product);
            }
        }
        unset($product);

        $this->render('wishlist', ['products' => $products]);
    }

    public function coupons(): never
    {
        $this->render('coupons', [
            'coupons' => $this->repository()->coupons(),
            'appliedCoupon' => $_SESSION['coupon'] ?? null
        ]);
    }

    public function addresses(): never
    {
        $addresses = $this->repository()->addresses($this->userId());
        foreach ($addresses as &$address) {
            $address['default'] = (bool)($address['default_address'] ?? false);
        }
        $this->render('addresses', ['addresses' => $addresses]);
    }

    public function payments(): never
    {
        $this->render('payments', [
            'payments' => $this->repository()->payments($this->userId())
        ]);
    }

    public function reviews(): never
    {
        $this->render('reviews', [
            'reviews' => $this->repository()->reviews($this->userId())
        ]);
    }

    public function support(): never
    {
        $this->render('support', [
            'tickets' => $this->repository()->tickets($this->userId())
        ]);
    }

    public function notifications(): never
    {
        $this->render('notifications', [
            'notifications' => $this->repository()->notifications($this->userId(), 100)
        ]);
    }

    public function settings(): never
    {
        $this->render('settings', [
            'notificationPreferences' => $this->repository()->notificationPreferences($this->userId())
        ]);
    }

    public function ajax(Request $request): never
    {
        $action = (string)($request->params['action'] ?? '');
        $id = (int)($request->input('id') ?: $request->input('product_id') ?: $request->input('address_id', 0));
        $r = $this->repository();

        if ($action === 'wishlist-add') {
            $r->addWishlist($this->userId(), $id);
            json(['success' => true, 'message' => 'Piece saved to your private collection.']);
        } elseif ($action === 'wishlist-remove') {
            $r->removeWishlist($this->userId(), $id);
            json(['success' => true, 'message' => 'Piece removed from saved items.']);
        } elseif ($action === 'cart-add') {
            $product = $r->productForCart($id);
            if (!$product) {
                json(['success' => false, 'message' => 'This product is no longer available.'], 404);
            }

            $quantity = max(1, min(99, (int)$request->input('quantity', 1)));
            $cart = $_SESSION['cart'] ?? [];
            $key = (string)$id;
            $existing = (int)($cart[$key]['quantity'] ?? 0);
            $available = (int)$product['quantity'];

            if ($available > 0 && $existing + $quantity > $available) {
                json(['success' => false, 'message' => 'You cannot add more than the available stock.'], 422);
            }

            $cart[$key] = [
                'id' => (int)$product['id'],
                'name' => $product['name'],
                'price' => (float)$product['price'],
                'image' => $product['featured_image'],
                'quantity' => $existing + $quantity
            ];

            $_SESSION['cart'] = $cart;
            $count = array_sum(array_map(static fn (array $item): int => (int)$item['quantity'], $cart));
            json(['success' => true, 'message' => 'Added to cart.', 'count' => $count]);
        } elseif ($action === 'coupon-apply') {
            $code = strtoupper(trim((string)$request->input('code')));
            if ($code === '') {
                json(['success' => false, 'message' => 'Enter a coupon code.'], 422);
            }

            $coupon = $r->coupon($code);
            if (!$coupon) {
                json(['success' => false, 'message' => 'This coupon is invalid or has expired.'], 422);
            }

            $_SESSION['coupon'] = $coupon;
            json(['success' => true, 'message' => 'Coupon applied.', 'coupon' => $coupon]);
        } elseif ($action === 'address-delete') {
            $r->deleteAddress($this->userId(), $id);
        } elseif ($action === 'address-add') {
            $label = trim((string)$request->input('label'));
            $recipientName = trim((string)$request->input('recipient_name'));
            $addressLine = trim((string)$request->input('address_line'));
            $city = trim((string)$request->input('city'));
            $state = trim((string)$request->input('state'));
            $country = trim((string)$request->input('country', 'Nigeria'));
            $phone = trim((string)$request->input('phone'));
            $isDefault = $request->input('is_default');

            if ($label === '' || $recipientName === '' || $addressLine === '' || $city === '' || $country === '' || $phone === '') {
                json(['success' => false, 'message' => 'Please complete all required address fields.'], 422);
            }

            if (!preg_match('/^\+?[0-9\s\-]{8,20}$/', $phone)) {
                json(['success' => false, 'message' => 'Please enter a valid phone number.'], 422);
            }

            $validation = \Noir\App\Services\AddressValidationService::validateAndStandardize($addressLine, $city, $state, '', $country);
            if (!$validation['valid']) {
                json(['success' => false, 'message' => $validation['error'] ?? 'Please provide a valid, deliverable delivery address.'], 422);
            }

            $std = $validation['standardized'] ?? [];
            $data = [
                'label' => $label,
                'recipient_name' => $recipientName,
                'address_line' => $std['street'] ?? $addressLine,
                'city' => $std['city'] ?? $city,
                'state' => $std['state'] ?? $state,
                'country' => $std['country'] ?? $country,
                'phone' => $phone,
                'is_default' => $isDefault,
            ];

            $r->addAddress($this->userId(), $data);
            json(['success' => true, 'message' => 'Address verified and saved successfully.']);
        } elseif ($action === 'address-default') {
            $r->setDefaultAddress($this->userId(), $id);
        } elseif ($action === 'payment-add') {
            $type = trim((string)$request->input('type'));
            $lastFour = preg_replace('/\D/', '', (string)$request->input('last_four'));
            $expires = trim((string)$request->input('expires'));

            if (!in_array($type, ['Visa', 'Mastercard', 'Verve', 'American Express'], true) || strlen($lastFour) !== 4 || $expires === '') {
                json(['success' => false, 'message' => 'Enter a valid card type, last four digits, and expiry date.'], 422);
            }

            $r->addPaymentMethod($this->userId(), $type, $lastFour, $expires);
            json(['success' => true, 'message' => 'Payment method added.']);
        } elseif ($action === 'support-ticket') {
            $subject = trim((string)$request->input('subject'));
            $message = trim((string)$request->input('message'));

            if ($subject === '' || $message === '') {
                json(['success' => false, 'message' => 'Subject and message are required.'], 422);
            }

            $number = $r->createTicket($this->userId(), $subject, $message);
            json(['success' => true, 'message' => 'Support ticket created.', 'ticket' => $number]);
        } elseif ($action === 'review-add') {
            $product = trim((string)$request->input('product'));
            $rating = max(1, min(5, (int)$request->input('rating')));
            $text = trim((string)$request->input('text'));

            if ($product === '' || $text === '') {
                json(['success' => false, 'message' => 'Please provide the product and your review.'], 422);
            }

            $r->addReview($this->userId(), $product, $rating, $text);
            $customer = $r->customer($this->userId());
            (new Mailer())->sendReviewNotification($customer['name'], $customer['email'], $product, $rating, $text);
            json(['success' => true, 'message' => 'Review submitted for approval.']);
        } elseif ($action === 'notification-read') {
            $r->markNotificationRead($this->userId(), $id);
            json(['success' => true, 'message' => 'Notification marked as read.', 'unread' => $r->unreadNotifications($this->userId())]);
        } elseif ($action === 'notification-read-all') {
            $r->markAllNotificationsRead($this->userId());
            json(['success' => true, 'message' => 'All notifications marked as read.', 'unread' => 0]);
        } elseif ($action === 'password-change') {
            if (!empty(\Noir\Core\Auth::user()['is_guest'])) {
                json(['success' => false, 'message' => 'Register your guest session before changing a password.'], 403);
            }

            $current = trim((string)$request->input('current_password'));
            $new = trim((string)$request->input('new_password'));
            $confirmation = trim((string)$request->input('new_password_confirmation'));
            $strong = strlen($new) >= 8 && preg_match('/[a-z]/', $new) && preg_match('/[A-Z]/', $new) && preg_match('/\d/', $new) && preg_match('/[^A-Za-z0-9]/', $new);

            if ($current === '') {
                json(['success' => false, 'message' => 'Please enter your current password.'], 422);
            }
            if (!$strong) {
                json(['success' => false, 'message' => 'Use 8+ characters with upper/lowercase, a number, and a symbol.'], 422);
            }
            if ($new !== $confirmation) {
                json(['success' => false, 'message' => 'Your new passwords do not match.'], 422);
            }
            if ($new === $current) {
                json(['success' => false, 'message' => 'Your new password must be different from your current password.'], 422);
            }
            if (!$r->changePassword($this->userId(), $current, $new)) {
                json(['success' => false, 'message' => 'Your current password is incorrect.'], 422);
            }

            json(['success' => true, 'message' => 'Your password has been updated.']);
        } elseif ($action === 'password-otp-request') {
            $user = \Noir\Core\Auth::user();
            if (!$user || empty($user['email'])) {
                json(['success' => false, 'message' => 'Account email not found.'], 404);
            }
            $userId = (int)$this->userId();
            $db = Container::get('db');
            $db->prepare('DELETE FROM password_reset_tokens WHERE user_id = ? OR expires_at < NOW()')->execute([$userId]);
            $otpCode = (string)random_int(100000, 999999);
            $q = $db->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, requested_ip) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), ?)');
            $q->execute([$userId, hash('sha256', $otpCode), $_SERVER['REMOTE_ADDR'] ?? null]);
            try {
                (new Mailer())->sendPasswordReset((string)$user['email'], (string)($user['name'] ?? 'Customer'), '', $otpCode);
            } catch (\Throwable $e) {}
            json(['success' => true, 'message' => 'A 6-digit OTP has been sent to your email.']);
        } elseif ($action === 'password-otp-change') {
            $otp = trim((string)$request->input('otp'));
            $new = trim((string)$request->input('new_password'));
            $confirmation = trim((string)$request->input('new_password_confirmation'));
            $strong = strlen($new) >= 8;

            if ($otp === '') {
                json(['success' => false, 'message' => 'Please enter the 6-digit OTP code.'], 422);
            }
            if (!$strong) {
                json(['success' => false, 'message' => 'Password must be at least 8 characters long.'], 422);
            }
            if ($new !== $confirmation) {
                json(['success' => false, 'message' => 'Your new passwords do not match.'], 422);
            }

            $userId = (int)$this->userId();
            $db = Container::get('db');
            $q = $db->prepare('SELECT id FROM password_reset_tokens WHERE user_id = ? AND token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
            $q->execute([$userId, hash('sha256', $otp)]);
            $reset = $q->fetch();

            if (!$reset) {
                json(['success' => false, 'message' => 'Invalid or expired OTP code.'], 422);
            }

            $db->beginTransaction();
            try {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $db->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?')->execute([$newHash, $userId]);
                $db->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?')->execute([(int)$reset['id']]);
                $db->prepare('INSERT INTO activity_logs (user_id, event, ip_address) VALUES (?, ?, ?)')
                   ->execute([$userId, 'auth.password_changed_otp', $_SERVER['REMOTE_ADDR'] ?? null]);
                $db->commit();
                json(['success' => true, 'message' => 'Your password has been updated via OTP verification.']);
            } catch (\Throwable $e) {
                $db->rollBack();
                json(['success' => false, 'message' => 'Failed to update password.'], 500);
            }
        } elseif ($action === 'notification-preferences') {
            $r->saveNotificationPreferences($this->userId(), [
                'order_updates' => $request->input('order_updates'),
                'account_alerts' => $request->input('account_alerts'),
                'promotions' => $request->input('promotions')
            ]);
        } elseif ($action === 'profile-update') {
            $name = (string)$request->input('name');
            $username = (string)$request->input('username');
            $email = (string)$request->input('email');
            $phone = (string)$request->input('phone');

            $res = $r->updateProfile($this->userId(), $name, $username, $email, $phone);
            if (!$res['success']) {
                json(['success' => false, 'message' => $res['message']], 422);
            }
            json(['success' => true, 'message' => $res['message'], 'customer' => $r->customer($this->userId())]);
        } elseif ($action === 'profile-upload') {
            $result = $this->processProfileImageUpload($_FILES['profile'] ?? [], $this->userId());
            if (!$result['success']) {
                json(['success' => false, 'message' => $result['message']], 422);
            }
            json(['success' => true, 'message' => $result['message'], 'profile_image' => $result['url']]);
        } elseif ($action === 'profile-delete') {
            $result = $this->processProfileImageRemoval($this->userId());
            $currentCustomer = $r->customer($this->userId());
            json(['success' => true, 'message' => $result['message'], 'initials' => $currentCustomer['initials'] ?? 'C']);
        } else {
            json(['success' => false, 'message' => 'Unknown customer action.'], 400);
        }

        json(['success' => true, 'message' => 'Your changes were saved.']);
    }

    private function queueUploadScan(string $diskPath, string $publicPath, string $source): void
    {
        try {
            $db = Container::get('db');
            $exists = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
            $exists->execute(['upload_scan_queue']);

            if (!$exists->fetchColumn()) {
                return;
            }

            $db->prepare('INSERT INTO upload_scan_queue(disk_path,public_path,source) VALUES(?,?,?)')->execute([$diskPath, $publicPath, $source]);
        } catch (\Throwable) {
        }
    }

    public function updateFcmToken(Request $request): never
    {
        try {
            $token = (string)$request->input('fcm_token');
            if (empty($token)) {
                json(['success' => false, 'error' => 'FCM token is required.'], 400);
            }

            $userId = $this->userId();
            if (!$userId) {
                json(['success' => false, 'error' => 'Unauthorized.'], 401);
            }

            $db = Container::get('db');
            $q = $db->prepare('UPDATE users SET fcm_token=? WHERE id=?');
            $q->execute([$token, $userId]);

            json(['success' => true]);
        } catch (\Throwable $e) {
            json(['success' => false, 'error' => 'An error occurred while updating the token.'], 500);
        }
    }
}
