<?php

declare(strict_types=1);

namespace Noir\App\Controllers;

use Noir\App\Repositories\CustomerRepository;
use Noir\App\Services\GoogleOAuthService;
use Noir\Core\Container;
use Noir\Core\Mailer;

final class AuthController
{
    public function showLogin(): never
    {
        require BASE_PATH . '/app/Views/auth/login.php';
        exit;
    }
    public function showRegister(): never
    {
        require BASE_PATH . '/app/Views/auth/register.php';
        exit;
    }
    public function guest(\Noir\Core\Request $request): never
    {
        $redirect = (string)$request->input('redirect', '/store');
        try {
            Container::get('authentication')->authenticateGuest();
        } catch (\Throwable $exception) {
            error_log('Guest sign-in error: ' . $exception->getMessage());
            header('Location: ' . $this->publicPath('/login?error=' . urlencode('Guest sign-in is unavailable. Please try again.')));
            exit;
        }
        $path = $this->publicPath(str_starts_with($redirect, '/') ? $redirect : '/store');
        header('Location: ' . $path, true, 303);
        exit;
    }
    public function googleRedirect(): never
    {
        if (!$this->googleConfigured()) {
            $this->googleError('Google sign-in is not configured yet.');
        }
        $state = bin2hex(random_bytes(32));
        $_SESSION['google_oauth_state'] = $state;
        $_SESSION['auth_redirect'] = (string)($_GET['redirect'] ?? '');
        $redirectUri = $this->absolutePath('/auth/google/callback');
        $_SESSION['google_oauth_redirect_uri'] = $redirectUri;
        $url = (new GoogleOAuthService())->authorizationUrl($redirectUri, $state);
        header('Location: ' . $url);
        exit;
    }

    public function googleCallback(\Noir\Core\Request $request): never
    {
        $expectedState = (string)($_SESSION['google_oauth_state'] ?? '');
        unset($_SESSION['google_oauth_state']);
        if ($expectedState === '' || !hash_equals($expectedState, (string)$request->input('state'))) {
            $this->googleError('Google sign-in expired. Please try again.');
        }
        if ((string)$request->input('error') !== '') {
            $errDesc = (string)$request->input('error_description', $request->input('error'));
            error_log('Google sign-in callback error from Google: ' . $errDesc);
            $this->googleError('Google sign-in was cancelled: ' . $errDesc);
        }
        $code = (string)$request->input('code');
        if ($code === '') {
            $this->googleError('Google did not return an authorization code.');
        }

        $redirectUri = (string)($_SESSION['google_oauth_redirect_uri'] ?? $this->absolutePath('/auth/google/callback'));
        unset($_SESSION['google_oauth_redirect_uri']);

        $db = Container::get('db');
        try {
            $profile = (new GoogleOAuthService())->profile($code, $redirectUri);
            $email = strtolower(trim((string)($profile['email'] ?? '')));
            $emailVerified = filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOL);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$emailVerified) {
                throw new \RuntimeException('Google did not return a verified email address.');
            }
            $name = trim((string)($profile['name'] ?? '')) ?: ucfirst((string)strstr($email, '@', true));
            $picture = trim((string)($profile['picture'] ?? ''));
            $guestUser = Container::get('auth')->user() ?? [];
            $guestUserId = !empty($guestUser['is_guest']) ? (int)($guestUser['user_id'] ?? 0) : 0;
            $query = $db->prepare('SELECT id,status,email_verified_at FROM users WHERE email=? AND is_guest=0 LIMIT 1');
            $query->execute([$email]);
            $user = $query->fetch();
            $newCustomer = false;

            if ($user) {
                if ($user['status'] !== 'active') {
                    $this->googleError('This account is not available for Google sign-in.');
                }
                $userId = (int)$user['id'];
                if (empty($user['email_verified_at'])) {
                    $db->prepare('UPDATE users SET email_verified_at=NOW() WHERE id=?')->execute([$userId]);
                }
                if ($picture !== '') {
                    $db->prepare('UPDATE users SET profile_image=COALESCE(NULLIF(profile_image,""),?) WHERE id=?')->execute([$picture, $userId]);
                }
                if ($guestUserId > 0 && $guestUserId !== $userId) {
                    $db->beginTransaction();
                    $this->transferGuestData($db, $guestUserId, $userId);
                    $db->prepare('DELETE FROM users WHERE id=? AND is_guest=1')->execute([$guestUserId]);
                    $db->prepare('INSERT INTO activity_logs (user_id,event,ip_address,metadata) VALUES (?,?,?,?)')->execute([$userId, 'auth.guest_merged', $_SERVER['REMOTE_ADDR'] ?? null, json_encode(['guest_user_id' => $guestUserId], JSON_UNESCAPED_SLASHES)]);
                    $db->commit();
                    unset($_SESSION['guest_token']);
                }
            } else {
                $db->beginTransaction();
                $username = $this->usernameFromEmail($email, $db);
                if ($guestUserId > 0) {
                    $insert = $db->prepare('UPDATE users SET username=?,name=?,email=?,password_hash=?,status=?,account_type=?,is_guest=0,guest_token_hash=NULL,guest_expires_at=NULL,guest_promoted_at=NOW(),email_verified_at=?,profile_image=COALESCE(NULLIF(profile_image,""),?),updated_at=NOW() WHERE id=? AND is_guest=1');
                    $insert->execute([$username,$name,$email,password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),'active','customer',date('Y-m-d H:i:s'),$picture ?: null,$guestUserId]);
                    if ($insert->rowCount() < 1) {
                        throw new \RuntimeException('Guest account could not be promoted.');
                    }
                    $userId = $guestUserId;
                    unset($_SESSION['guest_token']);
                } else {
                    $insert = $db->prepare('INSERT INTO users (public_id,username,name,email,password_hash,status,account_type,email_verified_at,profile_image) VALUES (?,?,?,?,?,?,?,?,?)');
                    $insert->execute([
                        strtoupper(bin2hex(random_bytes(13))),
                        $username,
                        $name,
                        $email,
                        password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
                        'active',
                        'customer',
                        date('Y-m-d H:i:s'),
                        $picture ?: null,
                    ]);
                    $userId = (int)$db->lastInsertId();
                }
                $role = $db->query("SELECT id FROM roles WHERE slug='customer' LIMIT 1")->fetchColumn();
                if (!$role) {
                    throw new \RuntimeException('Customer role is not configured.');
                }
                $db->prepare('INSERT IGNORE INTO user_roles (user_id,role_id) VALUES (?,?)')->execute([$userId, (int)$role]);
                $db->prepare('INSERT INTO customer_wallets (user_id) VALUES (?) ON DUPLICATE KEY UPDATE user_id=user_id')->execute([$userId]);
                (new CustomerRepository($db))->createNotification(
                    $userId,
                    'Welcome to Noir & Co.',
                    'Your account is ready. Discover considered pieces, save your favourites, and keep every order in one calm place.',
                    'success'
                );
                $db->commit();
                $newCustomer = true;
            }

            if ($newCustomer) {
                try {
                    (new Mailer())->sendCustomerWelcome($email, $name, $this->absolutePath('/login'));
                } catch (\Throwable $e) {
                    error_log('Google OAuth: Failed to send welcome email: ' . $e->getMessage());
                }
                
                try {
                    $klaviyo = new \Noir\App\Services\KlaviyoService();
                    if ($klaviyo->isConfigured()) {
                        $firstName = explode(' ', $name, 2)[0] ?? '';
                        $lastName  = explode(' ', $name, 2)[1] ?? '';
                        $klaviyo->createProfile($email, $firstName, $lastName);
                        $klaviyo->trackWelcome($email, $firstName);
                    }
                } catch (\Throwable $e) {
                    error_log('Google OAuth: Failed to track Klaviyo welcome: ' . $e->getMessage());
                }
            }
            $userSession = Container::get('authentication')->authenticateGoogleUser($userId);
            $redirect = (string)($_SESSION['auth_redirect'] ?? '');
            unset($_SESSION['auth_redirect']);
            $path = $this->publicPath(Container::get('role_redirect')->pathFor($userSession['roles'] ?? []));
            if ($redirect !== '' && str_starts_with($redirect, '/')) {
                $path = $this->publicPath($redirect);
            }
            header('Location: ' . $path);
            exit;
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Google OAuth error: ' . $exception->getMessage());
            $this->googleError('Google sign-in could not be completed: ' . $exception->getMessage());
        }
    }
    public function logoutForm(): never
    {
        Container::get('authentication')->logout();
        header('Location: ' . $this->publicPath('/login'));
        exit;
    }
    public function showForgotPassword(): never
    {
        require BASE_PATH . '/app/Views/auth/forgot-password.php';
        exit;
    }
    public function showResetPassword(\Noir\Core\Request $request): never
    {
        $token = (string)$request->input('token');
        if (!$this->validResetToken($token)) {
            require BASE_PATH.'/app/Views/auth/reset-invalid.php';
            exit;
        } require BASE_PATH.'/app/Views/auth/reset-password.php';
        exit;
    }

    public function loginForm(\Noir\Core\Request $request): never
    {
        $identity = (string)$request->input('email');
        $password = (string)$request->input('password');
        $remember = (bool)$request->input('remember');
        $redirect = (string)$request->input('redirect');
        if (trim($identity) === '' || $password === '') {
            $this->loginError('Email/username and password are required.', 422);
        }

        $recaptchaToken = (string)$request->input('g-recaptcha-response', $request->input('recaptcha_token', ''));
        if (!$this->verifyRecaptcha($recaptchaToken)) {
            $this->loginError('reCAPTCHA security verification failed. Please try again.', 422);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $db = Container::get('db');
        $rateLimiter = new \Noir\App\Services\SecurityRateLimiter($db);
        $check = $rateLimiter->checkLogin($ip, $identity);
        if (!$check['allowed']) {
            $this->loginError($check['message'] ?? 'Too many failed login attempts.', 429);
        }

        $guestUser = Container::get('auth')->user() ?? [];
        $guestUserId = !empty($guestUser['is_guest']) ? (int)($guestUser['user_id'] ?? 0) : 0;
        try {
            $user = Container::get('authentication')->authenticate($identity, $password, $remember);
            $rateLimiter->recordLoginSuccess($ip, $identity);
        } catch (\RuntimeException $e) {
            $rateLimiter->recordLoginFailure($ip, $identity);
            $this->loginError($e->getMessage(), $e->getCode() ?: 401);
        }
        if ($guestUserId > 0 && $guestUserId !== (int)($user['user_id'] ?? 0)) {
            $db->beginTransaction();
            try {
                $this->transferGuestData($db, $guestUserId, (int)$user['user_id']);
                $db->prepare('DELETE FROM users WHERE id=? AND is_guest=1')->execute([$guestUserId]);
                $db->commit();
                unset($_SESSION['guest_token']);
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }error_log('Guest merge after login failed: '.$e->getMessage());
            }
        }
        $path = $this->publicPath(Container::get('role_redirect')->pathFor($user['roles'] ?? []));
        if ($redirect !== '' && str_starts_with($redirect, '/')) {
            $path = $this->publicPath($redirect);
        }
        if ($this->isAjax()) {
            json(['success' => true,'message' => 'Login successful.','redirect' => $path]);
        } header('Location: '.$path);
        exit;
    }

    public function login(\Noir\Core\Request $request): never
    {
        $identity = (string)$request->input('email');
        $password = (string)$request->input('password');
        $remember = (bool)$request->input('remember');
        $redirect = (string)$request->input('redirect');

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $db = Container::get('db');
        $rateLimiter = new \Noir\App\Services\SecurityRateLimiter($db);
        $check = $rateLimiter->checkLogin($ip, $identity);
        if (!$check['allowed']) {
            json(['success' => false, 'errors' => ['login' => $check['message'] ?? 'Too many failed login attempts.']], 429);
        }

        $guestUser = Container::get('auth')->user() ?? [];
        $guestUserId = !empty($guestUser['is_guest']) ? (int)($guestUser['user_id'] ?? 0) : 0;
        try {
            $user = Container::get('authentication')->authenticate($identity, $password, $remember);
            $rateLimiter->recordLoginSuccess($ip, $identity);
        } catch (\RuntimeException $e) {
            $rateLimiter->recordLoginFailure($ip, $identity);
            json(['success' => false,'errors' => ['login' => $e->getMessage()]], $e->getCode() ?: 401);
        }
        if ($guestUserId > 0 && $guestUserId !== (int)($user['user_id'] ?? 0)) {
            $db->beginTransaction();
            try {
                $this->transferGuestData($db, $guestUserId, (int)$user['user_id']);
                $db->prepare('DELETE FROM users WHERE id=? AND is_guest=1')->execute([$guestUserId]);
                $db->commit();
                unset($_SESSION['guest_token']);
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }error_log('Guest merge after API login failed: '.$e->getMessage());
            }
        }
        $path = $this->publicPath(Container::get('role_redirect')->pathFor($user['roles'] ?? []));
        if ($redirect !== '' && str_starts_with($redirect, '/')) {
            $path = $this->publicPath($redirect);
        }
        json(['success' => true,'message' => 'Logged in.','redirect' => $path,'user' => $user]);
    }

    public function logout(): never
    {
        Container::get('authentication')->logout();
        json(['message' => 'Logged out.']);
    }
    public function csrf(): never
    {
        json(['csrf_token' => csrf_token()]);
    }
    public function me(): never
    {
        $user = Container::get('auth')->user();
        $user ? json(['user' => $user]) : json(['error' => 'Unauthenticated.'], 401);
    }

    public function register(\Noir\Core\Request $request): never
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $db = Container::get('db');
        $rateLimiter = new \Noir\App\Services\SecurityRateLimiter($db);
        $checkReg = $rateLimiter->checkRegister($ip);
        if (!$checkReg['allowed']) {
            header('Location: register?error=rate_limit');
            exit;
        }

        $recaptchaToken = (string)$request->input('g-recaptcha-response', $request->input('recaptcha_token', ''));
        if (!$this->verifyRecaptcha($recaptchaToken)) {
            header('Location: register?error=' . urlencode('reCAPTCHA security verification failed. Please try again.'));
            exit;
        }

        // Username remains stored for compatibility, but customers may register with email only.
        if (trim((string)$request->input('username')) === '') {
            $emailSeed = strtolower(trim((string)$request->input('email')));
            $emailSeed = strstr($emailSeed, '@', true) ?: 'customer';
            $emailSeed = trim((string)preg_replace('/[^a-z0-9]+/i', '.', $emailSeed), '.');
            $emailSeed = preg_match('/^[a-z]/', $emailSeed) ? $emailSeed : 'customer.' . $emailSeed;
            $_POST['username'] = substr($emailSeed, 0, 68) . '.' . strtolower(bin2hex(random_bytes(2)));
        }
        $name = trim((string)$request->input('name'));
        $username = strtolower(trim((string)$request->input('username')));
        $email = strtolower(trim((string)$request->input('email')));
        $password = (string)$request->input('password');
        $confirmation = (string)$request->input('password_confirmation');

        if ($name === '') {
            header('Location: register?error=name');
            exit;
        }
        if (!preg_match('/^[a-z][a-z0-9._-]{2,79}$/', $username)) {
            header('Location: register?error=username');
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: register?error=email');
            exit;
        }
        if (strlen($password) < 8 || strlen($password) > 20 || !preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
            header('Location: register?error=password');
            exit;
        }
        if ($password !== $confirmation) {
            header('Location: register?error=confirmation');
            exit;
        }
        $db = Container::get('db');
        $currentUser = Container::get('auth')->user() ?? [];
        $guestUserId = !empty($currentUser['is_guest']) ? (int)($currentUser['user_id'] ?? 0) : 0;
        $newUserId = 0;
        try {
            $db->beginTransaction();
            // This form is the non-Google customer path. The email is accepted as the
            // account identifier and can use the normal password login immediately.
            $verifiedAt = date('Y-m-d H:i:s');
            if ($guestUserId > 0) {
                $q = $db->prepare('UPDATE users SET username=?,name=?,email=?,password_hash=?,status=?,account_type=?,is_guest=0,guest_token_hash=NULL,guest_expires_at=NULL,guest_promoted_at=NOW(),email_verified_at=?,updated_at=NOW() WHERE id=? AND is_guest=1');
                $q->execute([$username,$name,$email,password_hash($password, PASSWORD_DEFAULT),'active','customer',$verifiedAt,$guestUserId]);
                if ($q->rowCount() < 1) {
                    throw new \RuntimeException('Guest account could not be promoted.');
                }
                $newUserId = $guestUserId;
                unset($_SESSION['guest_token']);
            } else {
                $q = $db->prepare('INSERT INTO users (public_id,username,name,email,password_hash,status,account_type,email_verified_at) VALUES (?,?,?,?,?,?,?,?)');
                $q->execute([strtoupper(bin2hex(random_bytes(13))),$username,$name,$email,password_hash($password, PASSWORD_DEFAULT),'active','customer',$verifiedAt]);
                $newUserId = (int)$db->lastInsertId();
            }
            $role = $db->query("SELECT id FROM roles WHERE slug='customer' LIMIT 1")->fetchColumn();
            if ($role) {
                $q = $db->prepare('INSERT IGNORE INTO user_roles (user_id,role_id) VALUES (?,?)');
                $q->execute([$newUserId,(int)$role]);
            }
            (new CustomerRepository($db))->createNotification(
                $newUserId,
                'Welcome to Noir & Co.',
                'Your account is ready. Discover considered pieces, save your favourites, and keep every order in one calm place.',
                'success'
            );
            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            header('Location: register?error=exists');
            exit;
        }

        try {
            (new Mailer())->sendCustomerWelcome($email, $name, $this->absolutePath('/login'));
        } catch (\Throwable $e) {
            error_log('Welcome email dispatch notice: ' . $e->getMessage());
        }

        try {
            $klaviyo = new \Noir\App\Services\KlaviyoService();
            if ($klaviyo->isConfigured()) {
                $klaviyo->createProfile($email, explode(' ', $name, 2)[0] ?? '', explode(' ', $name, 2)[1] ?? '');
            }
        } catch (\Throwable $e) {
            error_log('Klaviyo profile dispatch notice: ' . $e->getMessage());
        }

        $userSession = Container::get('authentication')->loginUserById(
            $newUserId,
            $guestUserId > 0 ? 'auth.guest_promoted' : 'auth.register'
        );
        $redirect = (string)$request->input('redirect');
        $path = $this->publicPath(Container::get('role_redirect')->pathFor($userSession['roles'] ?? []));
        if ($redirect !== '' && str_starts_with($redirect, '/')) {
            $path = $this->publicPath($redirect);
        }
        header('Location: ' . $path);
        exit;
    }

    public function requestPasswordReset(\Noir\Core\Request $request): never
    {
        $resetWindow = $_SESSION['password_reset_requests'] ?? ['started' => time(), 'count' => 0];
        if (time() - (int)$resetWindow['started'] > 900) {
            $resetWindow = ['started' => time(), 'count' => 0];
        }
        if ((int)$resetWindow['count'] >= 5) {
            header('Location: forgot-password?error=throttled');
            exit;
        }
        $resetWindow['count']++;
        $_SESSION['password_reset_requests'] = $resetWindow;
        $email = strtolower(trim((string)$request->input('email')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $db = Container::get('db');
            $q = $db->prepare("SELECT id,name,email FROM users WHERE email=? AND status='active' LIMIT 1");
            $q->execute([$email]);
            $user = $q->fetch();
            if ($user) {
                $db->prepare('DELETE FROM password_reset_tokens WHERE user_id=? OR expires_at<NOW()')->execute([(int)$user['id']]);
                $otpCode = (string)random_int(100000, 999999);
                $q = $db->prepare('INSERT INTO password_reset_tokens (user_id,token_hash,expires_at,requested_ip) VALUES (?,?,DATE_ADD(NOW(),INTERVAL 15 MINUTE),?)');
                $q->execute([(int)$user['id'], hash('sha256', $otpCode), $_SERVER['REMOTE_ADDR'] ?? null]);
                (new Mailer())->sendPasswordReset($user['email'], $user['name'], $this->absolutePath('/forgot-password?sent=1'), $otpCode);
            }
        }
        header('Location: forgot-password?sent=1');
        exit;
    }

    public function resetPassword(\Noir\Core\Request $request): never
    {
        $token = (string)$request->input('token');
        $password = (string)$request->input('password');
        $confirmation = (string)$request->input('password_confirmation');
        $strong = strlen($password) >= 8 && strlen($password) <= 20 && preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password) && preg_match('/\d/', $password) && preg_match('/[^A-Za-z0-9]/', $password);
        if (!$this->validResetToken($token) || !$strong || $password !== $confirmation) {
            header('Location: reset-password?token='.urlencode($token).'&error=invalid');
            exit;
        }$db = Container::get('db');
        $db->beginTransaction();
        $q = $db->prepare('SELECT id,user_id FROM password_reset_tokens WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() LIMIT 1');
        $q->execute([hash('sha256', $token)]);
        $reset = $q->fetch();
        if (!$reset) {
            $db->rollBack();
            header('Location: reset-password?token='.urlencode($token).'&error=invalid');
            exit;
        }$q = $db->prepare('UPDATE users SET password_hash=?,updated_at=NOW() WHERE id=?');
        $q->execute([password_hash($password, PASSWORD_DEFAULT),(int)$reset['user_id']]);
        $db->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE id=?')->execute([(int)$reset['id']]);
        $db->commit();
        if (isset($_SESSION['user'])) {
            Container::get('authentication')->logout();
        }header('Location: login?reset=1');
        exit;
    }

    private function validResetToken(string $token): bool
    {
        if (!preg_match('/^(?:[a-f0-9]{64}|[0-9]{6})$/', $token)) {
            return false;
        }$q = Container::get('db')->prepare('SELECT id FROM password_reset_tokens WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() LIMIT 1');
        $q->execute([hash('sha256', $token)]);
        return(bool)$q->fetchColumn();
    }
    private function googleConfigured(): bool
    {
        return trim((string)config('google.client_id', '')) !== '' && trim((string)config('google.client_secret', '')) !== '';
    }
    private function googleError(string $message): never
    {
        header('Location: ' . $this->publicPath('/login?error=' . urlencode($message)));
        exit;
    }
    private function usernameFromEmail(string $email, \PDO $db): string
    {
        $seed = strtolower((string)strstr($email, '@', true));
        $seed = trim((string)preg_replace('/[^a-z0-9]+/i', '.', $seed), '.');
        $seed = $seed !== '' && preg_match('/^[a-z]/', $seed) ? $seed : 'customer.' . ($seed ?: 'account');
        $seed = substr($seed, 0, 68);
        do {
            $username = $seed . '.' . strtolower(bin2hex(random_bytes(2)));
            $query = $db->prepare('SELECT id FROM users WHERE username=? LIMIT 1');
            $query->execute([$username]);
        } while ($query->fetchColumn());
        return $username;
    }
    private function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest' || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
    }
    private function loginError(string $message, int $status): never
    {
        if ($this->isAjax()) {
            json(['success' => false,'errors' => ['login' => $message]], $status >= 400 ? $status : 422);
        }header('Location: login?error='.urlencode($message));
        exit;
    }
    private function transferGuestData(\PDO $db, int $guestUserId, int $targetUserId): void
    {
        if ($guestUserId <= 0 || $targetUserId <= 0 || $guestUserId === $targetUserId) {
            return;
        }
        $db->prepare('INSERT IGNORE INTO customer_wishlist (user_id,product_id,created_at) SELECT ?,product_id,created_at FROM customer_wishlist WHERE user_id=?')->execute([$targetUserId,$guestUserId]);
        $db->prepare('DELETE FROM customer_wishlist WHERE user_id=?')->execute([$guestUserId]);
        foreach (['customer_addresses','payment_methods','wallet_transactions','customer_reviews','support_tickets','customer_notifications','orders'] as $table) {
            $db->prepare("UPDATE $table SET user_id=? WHERE user_id=?")->execute([$targetUserId,$guestUserId]);
        }
        $wallet = $db->prepare('SELECT balance,reward_points FROM customer_wallets WHERE user_id=? LIMIT 1');
        $wallet->execute([$guestUserId]);
        $guestWallet = $wallet->fetch();
        if ($guestWallet) {
            $db->prepare('INSERT INTO customer_wallets (user_id,balance,reward_points) VALUES (?,?,?) ON DUPLICATE KEY UPDATE balance=balance+VALUES(balance),reward_points=reward_points+VALUES(reward_points)')->execute([$targetUserId,(float)$guestWallet['balance'],(int)$guestWallet['reward_points']]);
            $db->prepare('DELETE FROM customer_wallets WHERE user_id=?')->execute([$guestUserId]);
        }
        $pref = $db->prepare('SELECT order_updates,account_alerts,promotions FROM customer_notification_preferences WHERE user_id=? LIMIT 1');
        $pref->execute([$guestUserId]);
        $guestPref = $pref->fetch();
        if ($guestPref) {
            $db->prepare('INSERT INTO customer_notification_preferences (user_id,order_updates,account_alerts,promotions) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE order_updates=VALUES(order_updates),account_alerts=VALUES(account_alerts),promotions=VALUES(promotions)')->execute([$targetUserId,(int)$guestPref['order_updates'],(int)$guestPref['account_alerts'],(int)$guestPref['promotions']]);
            $db->prepare('DELETE FROM customer_notification_preferences WHERE user_id=?')->execute([$guestUserId]);
        }
        $profile = $db->prepare('SELECT profile_image FROM users WHERE id=? AND profile_image IS NOT NULL AND profile_image<>"" LIMIT 1');
        $profile->execute([$guestUserId]);
        $guestProfileImage = (string)($profile->fetchColumn() ?: '');
        if ($guestProfileImage !== '') {
            $db->prepare('UPDATE users SET profile_image=COALESCE(NULLIF(profile_image,""),?) WHERE id=?')->execute([$guestProfileImage,$targetUserId]);
        }
    }
    private function publicPath(string $path): string
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        return($base ?: '').$path;
    }
    private function absolutePath(string $path): string
    {
        $explicitRedirect = (string)config('google.redirect_uri', '');
        if ($explicitRedirect !== '' && $path === '/auth/google/callback') {
            return $explicitRedirect;
        }

        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' && $_SERVER['HTTPS'] !== '')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'))
            || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

        $scheme = $isHttps ? 'https://' : 'http://';
        $host = (string)($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost');
        if (str_contains($host, ',')) {
            $host = trim(explode(',', $host)[0]);
        }
        return $scheme . $host . $this->publicPath($path);
    }

    protected function verifyRecaptcha(string $token): bool
    {
        return recaptcha_verify($token);
    }
}
