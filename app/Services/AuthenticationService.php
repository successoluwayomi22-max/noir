<?php
declare(strict_types=1);

namespace Noir\App\Services;

use PDO;

final class AuthenticationService
{
    public function __construct(private readonly PDO $db) {}

    public function authenticate(string $identity, string $password, bool $remember = false): array
    {
        $identity = strtolower(trim($identity));
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if ($this->blocked($identity, $ip)) throw new \RuntimeException('Too many login attempts. Please try again in 15 minutes.', 429);

        $query = $this->db->prepare('SELECT id,public_id,username,name,email,account_type,password_hash,status,email_verified_at,is_guest,profile_image FROM users WHERE email=? OR username=? LIMIT 1');
        $query->execute([$identity, $identity]);
        $user = $query->fetch();
        $valid = $user && password_verify($password, $user['password_hash']);
        if (!$valid) {
            // If user not found or password incorrect, check if identity belongs to a staff applicant
            $appQuery = $this->db->prepare("SELECT id, name, status, rejection_reason FROM staff_applications WHERE email=? OR username=? ORDER BY id DESC LIMIT 1");
            $appQuery->execute([$identity, $identity]);
            $app = $appQuery->fetch();
            if ($app && !$user) {
                if ($app['status'] === 'pending') {
                    throw new \RuntimeException('Your staff application is currently pending review by an administrator.', 403);
                } elseif ($app['status'] === 'rejected') {
                    $msg = 'Your staff application was not approved.';
                    if (!empty($app['rejection_reason']) && $app['rejection_reason'] !== 'Application was not approved.') {
                        $msg .= ' Reason: ' . $app['rejection_reason'];
                    }
                    throw new \RuntimeException($msg, 403);
                }
            }

            $this->recordAttempt($identity, $ip, false);
            throw new \RuntimeException('Invalid email/username or password.', 401);
        }
        if (!empty($user['is_guest'])) throw new \RuntimeException('Please create an account to sign in with email.', 403);
        if ($user['status'] === 'suspended') throw new \RuntimeException('This account is suspended. Please contact support.', 403);
        if ($user['status'] !== 'active') throw new \RuntimeException('This account is not active.', 403);
        if (empty($user['email_verified_at'])) throw new \RuntimeException('Please verify your email address before signing in.', 403);

        $session = $this->buildSession((int)$user['id'], $user);
        $this->db->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([(int)$user['id']]);
        $this->recordAttempt($identity, $ip, true);
        $this->audit((int)$user['id'], 'auth.login');
        if ($remember) $this->remember((int)$user['id']);
        return $session;
    }

    public function authenticateGuest(): array
    {
        $this->assertGuestSchemaReady();
        $this->cleanupExpiredGuests();
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 86400 * 7);
        $username = 'guest.' . strtolower(bin2hex(random_bytes(5)));
        $email = $username . '@guest.noir.local';

        $this->db->beginTransaction();
        try {
            $insert = $this->db->prepare('INSERT INTO users (public_id,username,name,email,password_hash,status,account_type,is_guest,guest_token_hash,guest_expires_at,email_verified_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())');
            $insert->execute([strtoupper(bin2hex(random_bytes(13))), $username, 'Guest Customer', $email, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), 'active', 'customer', 1, hash('sha256', $token), $expiresAt]);
            $userId = (int)$this->db->lastInsertId();
            $role = $this->db->query("SELECT id FROM roles WHERE slug='customer' LIMIT 1")->fetchColumn();
            if ($role) $this->db->prepare('INSERT INTO user_roles (user_id,role_id) VALUES (?,?)')->execute([$userId, (int)$role]);
            $this->db->prepare('INSERT INTO customer_wallets (user_id) VALUES (?) ON DUPLICATE KEY UPDATE user_id=user_id')->execute([$userId]);
            $this->auditRaw($userId, 'auth.guest_start', ['expires_at' => $expiresAt]);
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $exception;
        }

        $_SESSION['guest_token'] = $token;
        return $this->loginUserById($userId, 'auth.guest_login');
    }

    public function restoreRememberedSession(): void
    {
        if (isset($_SESSION['user']) || empty($_COOKIE['noir_remember'])) return;
        $hash = hash('sha256', $_COOKIE['noir_remember']);
        $query = $this->db->prepare("SELECT u.id,u.public_id,u.username,u.name,u.email,u.account_type,u.status,u.email_verified_at,u.is_guest,u.profile_image FROM remember_tokens t JOIN users u ON u.id=t.user_id WHERE t.token_hash=? AND t.expires_at>NOW() LIMIT 1");
        $query->execute([$hash]);
        $user = $query->fetch();
        if (!$user || $user['status'] !== 'active' || empty($user['email_verified_at']) || !empty($user['is_guest'])) {
            setcookie('noir_remember', '', ['expires'=>time()-3600,'path'=>'/','secure'=>config('session.secure',false),'httponly'=>true,'samesite'=>'Lax']);
            return;
        }
        $this->buildSession((int)$user['id'], $user);
    }

    public function authenticateGoogleUser(int $userId, bool $remember = false): array
    {
        $query = $this->db->prepare('SELECT id,public_id,username,name,email,account_type,status,email_verified_at,is_guest,profile_image FROM users WHERE id=? LIMIT 1');
        $query->execute([$userId]);
        $user = $query->fetch();
        if (!$user || $user['status'] !== 'active' || empty($user['email_verified_at'])) throw new \RuntimeException('This Google account cannot sign in to this account.', 403);
        $session = $this->buildSession((int)$user['id'], $user);
        $this->db->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([(int)$user['id']]);
        $this->audit((int)$user['id'], 'auth.google_login');
        if ($remember && empty($user['is_guest'])) $this->remember((int)$user['id']);
        return $session;
    }

    public function loginUserById(int $userId, string $event = 'auth.session_refresh'): array
    {
        $query = $this->db->prepare('SELECT id,public_id,username,name,email,account_type,status,email_verified_at,is_guest,guest_expires_at,profile_image FROM users WHERE id=? LIMIT 1');
        $query->execute([$userId]);
        $user = $query->fetch();
        if (!$user || $user['status'] !== 'active' || empty($user['email_verified_at'])) throw new \RuntimeException('This account cannot be signed in.', 403);
        if (!empty($user['is_guest']) && !empty($user['guest_expires_at']) && strtotime((string)$user['guest_expires_at']) < time()) throw new \RuntimeException('This guest session has expired.', 403);
        $session = $this->buildSession((int)$user['id'], $user);
        $this->db->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([(int)$user['id']]);
        $this->audit((int)$user['id'], $event);
        return $session;
    }

    public function logout(): void
    {
        $userId = $_SESSION['user']['user_id'] ?? null;
        if ($userId) $this->audit((int)$userId, 'auth.logout');
        if (!empty($_COOKIE['noir_remember'])) {
            $hash = hash('sha256', $_COOKIE['noir_remember']);
            $this->db->prepare('DELETE FROM remember_tokens WHERE token_hash=?')->execute([$hash]);
            setcookie('noir_remember', '', ['expires'=>time()-3600,'path'=>'/','secure'=>config('session.secure',false),'httponly'=>true,'samesite'=>'Lax']);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', ['expires'=>time()-42000,'path'=>$params['path'],'domain'=>$params['domain'],'secure'=>$params['secure'],'httponly'=>$params['httponly'],'samesite'=>$params['samesite'] ?? 'Lax']);
        }
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }

    private function buildSession(int $userId, array $user): array
    {
        $rolesQuery = $this->db->prepare('SELECT r.id,r.name,r.slug FROM roles r JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=? ORDER BY r.is_system DESC,r.id');
        $rolesQuery->execute([$userId]);
        $roles = $rolesQuery->fetchAll();
        if (($user['account_type'] ?? null) === 'customer' && !in_array('customer', array_column($roles, 'slug'), true)) $roles = array_merge([['id'=>0,'name'=>'Customer','slug'=>'customer']], $roles);
        if (!$roles) throw new \RuntimeException('This account has no assigned role.', 403);
        $permissionQuery = $this->db->prepare('SELECT DISTINCT p.slug FROM permissions p JOIN role_permissions rp ON rp.permission_id=p.id JOIN user_roles ur ON ur.role_id=rp.role_id WHERE ur.user_id=? AND rp.effect="allow"');
        $permissionQuery->execute([$userId]);
        $primaryRole = $roles[0];
        $profileImage = !empty($user['profile_image']) ? (string)$user['profile_image'] : (string)(store_setting('admin_profile_image_' . $userId) ?: store_setting('admin_profile_image_0'));
        session_regenerate_id(true);
        $_SESSION['user'] = ['user_id'=>$userId,'id'=>$userId,'public_id'=>$user['public_id'],'username'=>$user['username'],'role_id'=>(int)$primaryRole['id'],'role_name'=>$primaryRole['name'],'roles'=>array_column($roles,'slug'),'account_type'=>$user['account_type']??null,'display_name'=>$user['name'],'name'=>$user['name'],'email'=>$user['email'],'avatar'=>$profileImage ?: null,'profile_image'=>$profileImage ?: null,'permissions'=>$permissionQuery->fetchAll(PDO::FETCH_COLUMN),'is_guest'=>!empty($user['is_guest'])];
        return $_SESSION['user'];
    }

    private function blocked(string $identity, string $ip): bool
    {
        $q = $this->db->prepare('SELECT COUNT(*) FROM login_attempts WHERE was_successful=0 AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND (identity=? OR ip_address=?)');
        $q->execute([$identity, $ip]);
        return (int)$q->fetchColumn() >= 5;
    }

    private function recordAttempt(string $identity, string $ip, bool $success): void
    {
        $q = $this->db->prepare('INSERT INTO login_attempts (identity,ip_address,was_successful) VALUES (?,?,?)');
        $q->execute([$identity, $ip, $success ? 1 : 0]);
        if ($success) $this->db->prepare('DELETE FROM login_attempts WHERE identity=? OR ip_address=?')->execute([$identity, $ip]);
    }

    private function remember(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $this->db->prepare('DELETE FROM remember_tokens WHERE user_id=? OR expires_at<NOW()')->execute([$userId]);
        $q = $this->db->prepare('INSERT INTO remember_tokens (user_id,token_hash,expires_at) VALUES (?,?,DATE_ADD(NOW(), INTERVAL 30 DAY))');
        $q->execute([$userId, hash('sha256', $token)]);
        setcookie('noir_remember', $token, ['expires'=>time()+2592000,'path'=>'/','secure'=>config('session.secure',false),'httponly'=>true,'samesite'=>'Lax']);
    }

    private function audit(int $userId, string $event, ?string $actorName = null): void
    {
        // Resolve actor name from session if not supplied
        if ($actorName === null) {
            $actorName = (string)(($_SESSION['user']['name'] ?? $_SESSION['user']['display_name'] ?? '') ?: '');
        }
        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
        $q = $this->db->prepare(
            'INSERT INTO activity_logs (user_id,actor_name,event,ip_address,platform,user_agent,created_at) VALUES (?,?,?,?,?,?,NOW())'
        );
        $q->execute([$userId, $actorName ?: null, $event, $_SERVER['REMOTE_ADDR'] ?? null, 'web', $ua ?: null]);
    }

    private function auditRaw(int $userId, string $event, array $metadata, ?string $actorName = null): void
    {
        if ($actorName === null) {
            $actorName = (string)(($_SESSION['user']['name'] ?? $_SESSION['user']['display_name'] ?? '') ?: '');
        }
        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
        $q = $this->db->prepare(
            'INSERT INTO activity_logs (user_id,actor_name,event,ip_address,metadata,platform,user_agent,created_at) VALUES (?,?,?,?,?,?,?,NOW())'
        );
        $q->execute([$userId, $actorName ?: null, $event, $_SERVER['REMOTE_ADDR'] ?? null, json_encode($metadata, JSON_UNESCAPED_SLASHES), 'web', $ua ?: null]);
    }

    private function cleanupExpiredGuests(): void
    {
        try {
            $this->db->prepare("DELETE u FROM users u LEFT JOIN orders o ON o.user_id=u.id WHERE u.is_guest=1 AND u.guest_promoted_at IS NULL AND u.guest_expires_at<NOW() AND o.id IS NULL LIMIT 50")->execute();
        } catch (\Throwable) {}
    }

    private function assertGuestSchemaReady(): void
    {
        try {
            $columns = $this->db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Guest checkout is unavailable because the guest-session schema could not be verified.', 0, $exception);
        }

        $required = ['is_guest', 'guest_token_hash', 'guest_expires_at', 'guest_promoted_at'];
        foreach ($required as $column) {
            if (!in_array($column, $columns, true)) {
                throw new \RuntimeException('Guest checkout requires database/migrations/027_guest_sessions.sql to be applied.');
            }
        }
    }
}
