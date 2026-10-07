<?php
declare(strict_types=1);

namespace Noir\App\Controllers\Api;

use Noir\Core\Container;
use Noir\Core\Request;
use Noir\Core\Jwt;

final class AuthApiController
{
    public function login(Request $request): never
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        try {
            $email = strtolower(trim((string)$request->input('email')));
            $password = (string)$request->input('password');
            
            if ($email === '' || $password === '') {
                json(['error' => 'Email and password are required.'], 400);
            }

            $db = Container::get('db');
            $rateLimiter = new \Noir\App\Services\SecurityRateLimiter($db);
            $check = $rateLimiter->checkLogin($ip, $email);
            if (!$check['allowed']) {
                json(['error' => $check['message'] ?? 'Too many failed login attempts.', 'locked' => true, 'wait_seconds' => $check['wait_seconds']], 429);
            }

            $query = $db->prepare('SELECT id, public_id, username, name, email, account_type, password_hash, status, email_verified_at, is_guest, profile_image, loyalty_points, vip_tier FROM users WHERE LOWER(TRIM(email))=? OR LOWER(TRIM(username))=? LIMIT 1');
            $query->execute([$email, $email]);
            $user = $query->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $rateLimiter->recordLoginFailure($ip, $email);
                json(['error' => 'Invalid email/username or password.'], 401);
            }

            if (!empty($user['is_guest'])) {
                json(['error' => 'Please create an account to sign in.'], 403);
            }

            if ($user['status'] === 'suspended') {
                json(['error' => 'This account is suspended. Please contact support.'], 403);
            }

            if ($user['status'] === 'deleted') {
                json(['error' => 'This account has been deactivated.'], 403);
            }

            $rateLimiter->recordLoginSuccess($ip, $email);

            // Auto-verify / activate legitimate registered users on first mobile login
            if ($user['status'] !== 'active' || empty($user['email_verified_at'])) {
                $db->prepare("UPDATE users SET status='active', email_verified_at=COALESCE(email_verified_at, NOW()) WHERE id=?")->execute([(int)$user['id']]);
                $user['status'] = 'active';
            }

            $db->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([(int)$user['id']]);

            // Fetch user roles
            $rolesQuery = $db->prepare('SELECT r.id,r.name,r.slug FROM roles r JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=?');
            $rolesQuery->execute([(int)$user['id']]);
            $roles = $rolesQuery->fetchAll();
            if (($user['account_type'] ?? null) === 'customer' && !in_array('customer', array_column($roles, 'slug'), true)) {
                $roles = array_merge([['id'=>0,'name'=>'Customer','slug'=>'customer']], $roles);
            }

            $payload = [
                'user_id' => (int)$user['id'],
                'exp' => time() + 86400 * 30,
                'iat' => time()
            ];

            $token = Jwt::encode($payload, (string)config('key'));

            json([
                'success' => true,
                'token' => $token,
                'user_id' => (int)$user['id'],
                'user' => [
                    'id' => (int)$user['id'],
                    'public_id' => $user['public_id'] ?? '',
                    'name' => $user['name'] ?? '',
                    'email' => $user['email'] ?? '',
                    'username' => $user['username'] ?? '',
                    'account_type' => $user['account_type'] ?? 'customer',
                    'profile_image' => $user['profile_image'] ?? null,
                    'loyalty_points' => (int)($user['loyalty_points'] ?? 0),
                    'vip_tier' => $user['vip_tier'] ?? 'Member',
                    'roles' => array_column($roles, 'slug'),
                ]
            ]);
        } catch (\Throwable $e) {
            json(['error' => 'An error occurred during sign in: ' . $e->getMessage()], 500);
        }
    }

    public function googleLogin(Request $request): never
    {
        try {
            $idToken = (string)$request->input('id_token');
            if (empty($idToken)) {
                json(['error' => 'ID token is required.'], 400);
            }

            // Verify Google token
            $ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$response) {
                json(['error' => 'Invalid Google token.'], 401);
            }

            $payload = json_decode($response, true);
            if (!isset($payload['email'])) {
                json(['error' => 'Google account does not have an email.'], 400);
            }

            $email = $payload['email'];
            $name = $payload['name'] ?? explode('@', $email)[0];
            $picture = $payload['picture'] ?? null;

            $db = Container::get('db');
            
            // Check if user exists
            $q = $db->prepare('SELECT id, status, is_guest FROM users WHERE email=? LIMIT 1');
            $q->execute([$email]);
            $user = $q->fetch();

            if ($user) {
                if ($user['status'] === 'suspended') {
                    json(['error' => 'This account is suspended.'], 403);
                }
                $userId = (int)$user['id'];
                
                // If it was a guest, upgrade them
                if (!empty($user['is_guest'])) {
                    $upQ = $db->prepare("UPDATE users SET is_guest=0, account_type='customer', status='active', email_verified_at=NOW() WHERE id=?");
                    $upQ->execute([$userId]);
                }
            } else {
                // Register new user
                $insQ = $db->prepare("INSERT INTO users (name, email, password_hash, account_type, status, is_guest, email_verified_at, profile_image) VALUES (?, ?, ?, 'customer', 'active', 0, NOW(), ?)");
                // Generate a random password hash since they use Google
                $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $insQ->execute([$name, $email, $randomPass, $picture]);
                $userId = (int)$db->lastInsertId();
            }

            $jwtPayload = [
                'user_id' => $userId,
                'exp' => time() + 86400 * 30,
                'iat' => time()
            ];
            $token = Jwt::encode($jwtPayload, (string)config('key'));

            json([
                'success' => true,
                'token' => $token,
                'user_id' => $userId
            ]);

        } catch (\Throwable $e) {
            json(['error' => 'An error occurred during Google sign in.'], 500);
        }
    }


    public function register(Request $request): never
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $db = Container::get('db');
        $rateLimiter = new \Noir\App\Services\SecurityRateLimiter($db);
        $checkReg = $rateLimiter->checkRegister($ip);
        if (!$checkReg['allowed']) {
            json(['error' => $checkReg['message'] ?? 'Too many registration attempts.', 'locked' => true, 'wait_seconds' => $checkReg['wait_seconds']], 429);
        }

        $name = trim((string)$request->input('name'));
        $email = strtolower(trim((string)$request->input('email')));
        $password = (string)$request->input('password');

        if ($name === '') {
            json(['error' => 'Name is required.'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json(['error' => 'Invalid email address.'], 400);
        }

        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            json(['error' => 'Password must be at least 8 characters long and contain both letters and numbers.'], 400);
        }

        $db = Container::get('db');
        
        $check = $db->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) {
            json(['error' => 'This email address is already registered.'], 409);
        }

        $emailSeed = strstr($email, '@', true) ?: 'customer';
        $emailSeed = trim((string)preg_replace('/[^a-z0-9]+/i', '.', $emailSeed), '.');
        $emailSeed = preg_match('/^[a-z]/', $emailSeed) ? $emailSeed : 'customer.' . $emailSeed;
        $username = substr($emailSeed, 0, 68) . '.' . strtolower(bin2hex(random_bytes(2)));

        try {
            $db->beginTransaction();
            $verifiedAt = date('Y-m-d H:i:s');
            
            $q = $db->prepare('INSERT INTO users (public_id,username,name,email,password_hash,status,email_verified_at) VALUES (?,?,?,?,?,?,?)');
            $q->execute([
                strtoupper(bin2hex(random_bytes(13))),
                $username,
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                'active',
                $verifiedAt
            ]);
            
            $userId = (int)$db->lastInsertId();
            
            $role = $db->query("SELECT id FROM roles WHERE slug='customer' LIMIT 1")->fetchColumn();
            if ($role) {
                $db->prepare('INSERT INTO user_roles (user_id,role_id) VALUES (?,?)')->execute([$userId, (int)$role]);
            }
            
            $db->prepare('INSERT INTO customer_wallets (user_id) VALUES (?) ON DUPLICATE KEY UPDATE user_id=user_id')->execute([$userId]);
            
            $db->prepare('INSERT INTO activity_logs (user_id,event,ip_address) VALUES (?,?,?)')->execute([$userId, 'auth.register', $_SERVER['REMOTE_ADDR'] ?? null]);
            
            $rateLimiter->recordRegisterAttempt($ip);
            $db->commit();

            try {
                $loginUrl = rtrim((string)config('url', 'http://localhost'), '/') . '/login';
                (new \Noir\Core\Mailer())->sendCustomerWelcome($email, $name, $loginUrl);
            } catch (\Throwable $e) {
                // Ignore mailer errors so registration still succeeds
                error_log('Failed to send welcome email: ' . $e->getMessage());
            }

            $payload = [
                'user_id' => $userId,
                'exp' => time() + 86400 * 30,
                'iat' => time()
            ];

            $token = Jwt::encode($payload, (string)config('key'));

            json([
                'success' => true,
                'token' => $token,
                'user_id' => $userId
            ], 201);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            json(['error' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    public function me(Request $request): never
    {
        $user = \Noir\Core\Auth::user();
        if (!$user) {
            json(['error' => 'Unauthenticated.'], 401);
        }
        json(['user' => $user]);
    }

    public function forgotPassword(Request $request): never
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $email = strtolower(trim((string)$request->input('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json(['error' => 'Invalid email address.'], 400);
        }

        $db = Container::get('db');
        $q = $db->prepare("SELECT id,name,email FROM users WHERE email=? AND status='active' LIMIT 1");
        $q->execute([$email]);
        $user = $q->fetch();

        if ($user) {
            $db->prepare('DELETE FROM password_reset_tokens WHERE user_id=? OR expires_at<NOW()')->execute([(int)$user['id']]);
            $otpCode = (string)random_int(100000, 999999);
            $q = $db->prepare('INSERT INTO password_reset_tokens (user_id,token_hash,expires_at,requested_ip) VALUES (?,?,DATE_ADD(NOW(),INTERVAL 15 MINUTE),?)');
            $q->execute([(int)$user['id'], hash('sha256', $otpCode), $ip]);
            (new \Noir\Core\Mailer())->sendPasswordReset($user['email'], $user['name'], '', $otpCode);
        }
        
        json(['success' => true, 'message' => 'If this email is registered, you will receive an OTP shortly.']);
    }

    public function verifyOtp(Request $request): never
    {
        $email = strtolower(trim((string)$request->input('email')));
        $otp = (string)$request->input('otp');
        
        if (empty($email) || empty($otp)) {
            json(['error' => 'Email and OTP are required.'], 400);
        }

        $db = Container::get('db');
        $q = $db->prepare("SELECT id FROM users WHERE email=? AND status='active' LIMIT 1");
        $q->execute([$email]);
        $user = $q->fetch();

        if (!$user) {
            json(['error' => 'Invalid or expired OTP.'], 400);
        }

        $q = $db->prepare('SELECT id FROM password_reset_tokens WHERE user_id=? AND token_hash=? AND used_at IS NULL AND expires_at>NOW() LIMIT 1');
        $q->execute([(int)$user['id'], hash('sha256', $otp)]);
        $reset = $q->fetch();

        if (!$reset) {
            json(['error' => 'Invalid or expired OTP.'], 400);
        }

        json(['success' => true, 'message' => 'OTP verified.']);
    }

    public function resetPassword(Request $request): never
    {
        $email = strtolower(trim((string)$request->input('email')));
        $otp = (string)$request->input('otp');
        $password = (string)$request->input('password');

        if (empty($email) || empty($otp) || empty($password)) {
            json(['error' => 'All fields are required.'], 400);
        }

        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            json(['error' => 'Password must be at least 8 characters long and contain both letters and numbers.'], 400);
        }

        $db = Container::get('db');
        $q = $db->prepare("SELECT id FROM users WHERE email=? AND status='active' LIMIT 1");
        $q->execute([$email]);
        $user = $q->fetch();

        if (!$user) {
            json(['error' => 'Invalid or expired OTP.'], 400);
        }

        $q = $db->prepare('SELECT id FROM password_reset_tokens WHERE user_id=? AND token_hash=? AND used_at IS NULL AND expires_at>NOW() LIMIT 1');
        $q->execute([(int)$user['id'], hash('sha256', $otp)]);
        $reset = $q->fetch();

        if (!$reset) {
            json(['error' => 'Invalid or expired OTP.'], 400);
        }

        $db->beginTransaction();
        try {
            // Update password
            $q = $db->prepare('UPDATE users SET password_hash=?,updated_at=NOW() WHERE id=?');
            $q->execute([password_hash($password, PASSWORD_DEFAULT), (int)$user['id']]);

            // Mark OTP as used
            $q = $db->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE id=?');
            $q->execute([(int)$reset['id']]);
            
            $db->commit();
            json(['success' => true, 'message' => 'Password successfully reset.']);
        } catch (\Throwable $e) {
            $db->rollBack();
            json(['error' => 'An error occurred. Please try again.'], 500);
        }
    }

    public function updateFcmToken(Request $request): never
    {
        try {
            $token = (string)$request->input('fcm_token');
            if (empty($token)) {
                json(['error' => 'FCM token is required.'], 400);
            }

            $userId = (int)\Noir\Core\Auth::id();
            if (!$userId) {
                json(['error' => 'Unauthorized.'], 401);
            }

            $db = Container::get('db');
            $q = $db->prepare('UPDATE users SET fcm_token=? WHERE id=?');
            $q->execute([$token, $userId]);

            json(['success' => true]);
        } catch (\Throwable $e) {
            json(['error' => 'An error occurred while updating the token.'], 500);
        }
    }

    public function updateProfile(Request $request): never
    {
        try {
            $userId = (int)\Noir\Core\Auth::id();
            if (!$userId) {
                json(['error' => 'Unauthorized.'], 401);
            }

            $name = trim((string)$request->input('name'));
            $phone = trim((string)$request->input('phone', ''));

            if ($name === '') {
                json(['error' => 'Full name cannot be empty.'], 400);
            }

            $db = Container::get('db');
            $stmt = $db->prepare('UPDATE users SET name = ?, phone = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$name, $phone, $userId]);

            $stmtUser = $db->prepare('SELECT id, public_id, username, name, email, phone, profile_image, loyalty_points, vip_tier FROM users WHERE id = ? LIMIT 1');
            $stmtUser->execute([$userId]);
            $updatedUser = $stmtUser->fetch();

            json([
                'success' => true,
                'message' => 'Profile details updated successfully.',
                'user' => $updatedUser,
            ]);
        } catch (\Throwable $e) {
            json(['error' => 'Failed to update profile: ' . $e->getMessage()], 500);
        }
    }

    public function uploadProfileImage(Request $request): never
    {
        try {
            $userId = (int)\Noir\Core\Auth::id();
            if (!$userId) {
                json(['error' => 'Unauthorized.'], 401);
            }

            $db = Container::get('db');
            $dir = BASE_PATH . '/public/uploads/avatars';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $publicUrl = null;

            // Handle Base64 image payload (e.g. from mobile app)
            $base64Image = (string)$request->input('image', '');
            if (!empty($base64Image)) {
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                    $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                    $ext = strtolower($type[1]);
                    if ($ext === 'jpeg') $ext = 'jpg';
                } else {
                    $ext = 'jpg';
                }

                $data = base64_decode($base64Image, true);
                if ($data === false) {
                    json(['error' => 'Invalid image format.'], 400);
                }

                $filename = 'avatar-' . $userId . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = $dir . '/' . $filename;
                if (file_put_contents($dest, $data) === false) {
                    json(['error' => 'Failed to save avatar image.'], 500);
                }

                $publicUrl = '/uploads/avatars/' . $filename;
            } elseif (!empty($_FILES['image']) || !empty($_FILES['file']) || !empty($_FILES['profile_image'])) {
                $file = $_FILES['image'] ?? $_FILES['file'] ?? $_FILES['profile_image'];
                $ext = pathinfo((string)$file['name'], PATHINFO_EXTENSION) ?: 'jpg';
                $filename = 'avatar-' . $userId . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = $dir . '/' . $filename;
                if (!move_uploaded_file((string)$file['tmp_name'], $dest)) {
                    json(['error' => 'Failed to upload avatar file.'], 500);
                }
                $publicUrl = '/uploads/avatars/' . $filename;
            } else {
                json(['error' => 'No image file or image data provided.'], 400);
            }

            // Remove old image
            $stmtOld = $db->prepare('SELECT profile_image FROM users WHERE id = ? LIMIT 1');
            $stmtOld->execute([$userId]);
            $old = (string)$stmtOld->fetchColumn();
            if ($old && str_starts_with($old, '/uploads/avatars/')) {
                $oldFile = BASE_PATH . '/public' . $old;
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }

            $stmt = $db->prepare('UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$publicUrl, $userId]);

            json([
                'success' => true,
                'message' => 'Profile picture updated successfully.',
                'profile_image' => $publicUrl,
            ]);
        } catch (\Throwable $e) {
            json(['error' => 'Failed to upload profile picture: ' . $e->getMessage()], 500);
        }
    }

    public function deleteProfileImage(Request $request): never
    {
        try {
            $userId = (int)\Noir\Core\Auth::id();
            if (!$userId) {
                json(['error' => 'Unauthorized.'], 401);
            }

            $db = Container::get('db');
            $stmtOld = $db->prepare('SELECT profile_image FROM users WHERE id = ? LIMIT 1');
            $stmtOld->execute([$userId]);
            $old = (string)$stmtOld->fetchColumn();
            if ($old && str_starts_with($old, '/uploads/avatars/')) {
                $oldFile = BASE_PATH . '/public' . $old;
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }

            $db->prepare('UPDATE users SET profile_image = NULL, updated_at = NOW() WHERE id = ?')->execute([$userId]);

            json(['success' => true, 'message' => 'Profile picture removed.']);
        } catch (\Throwable $e) {
            json(['error' => 'Failed to delete profile picture: ' . $e->getMessage()], 500);
        }
    }

    public function changePassword(Request $request): never
    {
        try {
            $userId = (int)\Noir\Core\Auth::id();
            if (!$userId) {
                json(['error' => 'Unauthorized.'], 401);
            }

            $currentPassword = (string)$request->input('current_password');
            $newPassword = (string)$request->input('new_password');

            if ($currentPassword === '' || $newPassword === '') {
                json(['error' => 'Current password and new password are required.'], 400);
            }

            if (strlen($newPassword) < 8) {
                json(['error' => 'New password must be at least 8 characters long.'], 400);
            }

            $db = Container::get('db');
            $stmt = $db->prepare('SELECT id, password_hash FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($currentPassword, (string)$user['password_hash'])) {
                json(['error' => 'Your current password is incorrect.'], 422);
            }

            if (password_verify($newPassword, (string)$user['password_hash'])) {
                json(['error' => 'New password must be different from your current password.'], 422);
            }

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $db->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
            $update->execute([$newHash, $userId]);

            $db->prepare('INSERT INTO activity_logs (user_id, event, ip_address) VALUES (?, ?, ?)')
               ->execute([$userId, 'auth.password_changed', $_SERVER['REMOTE_ADDR'] ?? null]);

            json([
                'success' => true,
                'message' => 'Password updated successfully.',
            ]);
        } catch (\Throwable $e) {
            json(['error' => 'Failed to change password: ' . $e->getMessage()], 500);
        }
    }

    public function requestPasswordOtp(Request $request): never
    {
        try {
            $userId = (int)\Noir\Core\Auth::id();
            if (!$userId) {
                json(['error' => 'Unauthorized.'], 401);
            }

            $db = Container::get('db');
            $stmt = $db->prepare('SELECT id, name, email FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || empty($user['email'])) {
                json(['error' => 'User account or email not found.'], 404);
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $db->prepare('DELETE FROM password_reset_tokens WHERE user_id = ? OR expires_at < NOW()')->execute([(int)$user['id']]);
            $otpCode = (string)random_int(100000, 999999);
            
            $q = $db->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, requested_ip) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), ?)');
            $q->execute([(int)$user['id'], hash('sha256', $otpCode), $ip]);

            try {
                (new \Noir\Core\Mailer())->sendPasswordReset((string)$user['email'], (string)$user['name'], '', $otpCode);
            } catch (\Throwable $e) {
                error_log('Failed to send password reset OTP email: ' . $e->getMessage());
            }

            json([
                'success' => true,
                'message' => 'A 6-digit OTP has been sent to ' . substr((string)$user['email'], 0, 3) . '***@' . explode('@', (string)$user['email'])[1],
                'email' => $user['email'],
            ]);
        } catch (\Throwable $e) {
            json(['error' => 'Failed to request OTP: ' . $e->getMessage()], 500);
        }
    }

    public function changePasswordWithOtp(Request $request): never
    {
        try {
            $userId = (int)\Noir\Core\Auth::id();
            if (!$userId) {
                json(['error' => 'Unauthorized.'], 401);
            }

            $otp = trim((string)$request->input('otp'));
            $newPassword = (string)$request->input('new_password');

            if ($otp === '' || $newPassword === '') {
                json(['error' => 'OTP and new password are required.'], 400);
            }

            if (strlen($newPassword) < 8) {
                json(['error' => 'New password must be at least 8 characters long.'], 400);
            }

            $db = Container::get('db');
            $q = $db->prepare('SELECT id FROM password_reset_tokens WHERE user_id = ? AND token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
            $q->execute([$userId, hash('sha256', $otp)]);
            $reset = $q->fetch();

            if (!$reset) {
                json(['error' => 'Invalid or expired OTP.'], 400);
            }

            $db->beginTransaction();
            try {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $db->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?')->execute([$newHash, $userId]);
                $db->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?')->execute([(int)$reset['id']]);
                $db->prepare('INSERT INTO activity_logs (user_id, event, ip_address) VALUES (?, ?, ?)')
                   ->execute([$userId, 'auth.password_changed_otp', $_SERVER['REMOTE_ADDR'] ?? null]);
                $db->commit();

                json([
                    'success' => true,
                    'message' => 'Password updated successfully.',
                ]);
            } catch (\Throwable $e) {
                $db->rollBack();
                json(['error' => 'Failed to update password.'], 500);
            }
        } catch (\Throwable $e) {
            json(['error' => 'Failed to process request: ' . $e->getMessage()], 500);
        }
    }
}
