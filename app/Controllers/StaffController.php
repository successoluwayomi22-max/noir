<?php

declare(strict_types=1);

namespace Noir\App\Controllers;

use Noir\Core\Container;
use Noir\Core\Mailer;

final class StaffController
{
    public function apply(): never
    {
        require BASE_PATH . '/app/Views/staff/apply.php';
        exit;
    }

    public function submitted(): never
    {
        require BASE_PATH . '/app/Views/staff/submitted.php';
        exit;
    }

    public function submit(\Noir\Core\Request $request): never
    {
        $name = trim((string)$request->input('name'));
        $email = strtolower(trim((string)$request->input('email')));
        $phone = trim((string)$request->input('phone'));
        $username = strtolower(trim((string)$request->input('username')));
        $password = (string)$request->input('password');
        $position = trim((string)$request->input('position'));
        $message = trim((string)$request->input('message'));

        // Applicants sign in with their email and password. If they enter that
        // email in the optional username field, normalize it to the internal
        // username format used by the staff table.
        if (str_contains($username, '@') && filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $username = strtolower((string)strstr($username, '@', true));
            $username = trim((string)preg_replace('/[^a-z0-9._-]+/i', '.', $username), '.');
        }

        $resume = $_FILES['resume'] ?? null;
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        $resumeExtension = $resume && !empty($resume['name'])
            ? strtolower((string)pathinfo((string)$resume['name'], PATHINFO_EXTENSION))
            : '';

        $resumeError = is_array($resume) ? (int)($resume['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
        $resumeMime = '';

        if (is_array($resume) && !empty($resume['tmp_name']) && is_file((string)$resume['tmp_name']) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $resumeMime = $finfo ? (string)finfo_file($finfo, (string)$resume['tmp_name']) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
        }

        $allowedResumeMimes = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword', 'application/octet-stream'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream']
        ];

        $resumeValid = is_array($resume)
            && $resumeError === UPLOAD_ERR_OK
            && !empty($resume['tmp_name'])
            && in_array($resumeExtension, $allowedExtensions, true)
            && in_array($resumeMime, $allowedResumeMimes[$resumeExtension] ?? [], true)
            && (int)($resume['size'] ?? 0) <= 5 * 1024 * 1024;

        $invalidFields = [];
        if ($name === '') {
            $invalidFields[] = 'name';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $invalidFields[] = 'email';
        }
        if (!preg_match('/^[a-z][a-z0-9._-]{2,79}$/', $username)) {
            $invalidFields[] = 'username';
        }
        if (strlen($password) < 8 || !preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
            $invalidFields[] = 'password';
        }
        if ($position === '') {
            $invalidFields[] = 'position';
        }

        if ($invalidFields) {
            header('Location: ' . $this->path('/careers/apply?' . http_build_query(['error' => 'details', 'fields' => implode(',', $invalidFields)])));
            exit;
        }

        if (!$resumeValid) {
            $resumeMessage = $resumeError === UPLOAD_ERR_INI_SIZE || $resumeError === UPLOAD_ERR_FORM_SIZE ? 'large' : ($resumeError !== UPLOAD_ERR_OK ? 'missing' : 'type');
            header('Location: ' . $this->path('/careers/apply?error=resume&reason=' . $resumeMessage));
            exit;
        }

        $db = Container::get('db');
        $existing = $db->prepare('SELECT id FROM users WHERE email=? OR username=? LIMIT 1');
        $existing->execute([$email, $username]);
        if ($existing->fetchColumn()) {
            header('Location: ' . $this->path('/careers/apply?error=exists'));
            exit;
        }

        $pending = $db->prepare("SELECT id FROM staff_applications WHERE (email=? OR username=?) AND status='pending' LIMIT 1");
        $pending->execute([$email, $username]);
        if ($pending->fetchColumn()) {
            header('Location: ' . $this->path('/careers/apply?error=pending'));
            exit;
        }

        $number = 'STAFF-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $uploadDirectory = BASE_PATH . '/storage/uploads/staff-applications';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) {
            header('Location: ' . $this->path('/careers/apply?error=upload'));
            exit;
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $resumeExtension;
        $storedPath = $uploadDirectory . '/' . $storedName;
        if (!move_uploaded_file((string)$resume['tmp_name'], $storedPath)) {
            header('Location: ' . $this->path('/careers/apply?error=upload'));
            exit;
        }

        $relativePath = 'storage/uploads/staff-applications/' . $storedName;
        try {
            $stmt = $db->prepare('INSERT INTO staff_applications (application_number,name,email,phone,username,password_hash,position,message,resume_path,resume_original_name) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$number, $name, $email, $phone, $username, password_hash($password, PASSWORD_DEFAULT), $position, $message, $relativePath, basename((string)$resume['name'])]);
        } catch (\Throwable) {
            if (is_file($storedPath)) {
                @unlink($storedPath);
            }
            header('Location: ' . $this->path('/careers/apply?error=database'));
            exit;
        }

        header('Location: ' . $this->path('/careers/application-submitted?ref=' . urlencode($number)));
        exit;
    }

    public function resume(\Noir\Core\Request $request): never
    {
        $id = (int)($request->params['id'] ?? 0);
        $stmt = Container::get('db')->prepare('SELECT resume_path,resume_original_name FROM staff_applications WHERE id=? LIMIT 1');
        $stmt->execute([$id]);
        $application = $stmt->fetch();
        $path = $application ? BASE_PATH . '/' . ltrim((string)$application['resume_path'], '/') : '';

        if (!$application || !is_file($path) || !str_starts_with(realpath($path) ?: '', realpath(BASE_PATH . '/storage/uploads/staff-applications') . DIRECTORY_SEPARATOR)) {
            http_response_code(404);
            echo 'Resume not found.';
            exit;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string)filesize($path));
        header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', (string)$application['resume_original_name']) . '"');
        readfile($path);
        exit;
    }

    public function applications(): never
    {
        $db = Container::get('db');
        $applications = $db->query('SELECT sa.*, r.name role_name FROM staff_applications sa LEFT JOIN roles r ON r.id=sa.role_id ORDER BY sa.id DESC')->fetchAll();
        $roles = $db->query("SELECT id,name,slug FROM roles WHERE slug<>'customer' ORDER BY name")->fetchAll();
        require BASE_PATH . '/app/Views/admin/staff-applications.php';
        exit;
    }

    public function approve(\Noir\Core\Request $request): never
    {
        $applicationId = (int)$request->input('application_id');
        $roleId = (int)$request->input('role_id');
        $db = Container::get('db');

        $stmt = $db->prepare("SELECT * FROM staff_applications WHERE id=? AND status='pending' LIMIT 1");
        $stmt->execute([$applicationId]);
        $application = $stmt->fetch();

        $role = $db->prepare("SELECT id FROM roles WHERE id=? AND slug<>'customer'");
        $role->execute([$roleId]);

        if (!$application || !$role->fetchColumn()) {
            if ($this->isAjax()) {
                json(['success' => false, 'message' => 'Invalid application or role selected.'], 422);
            }
            header('Location: staff-applications?error=invalid');
            exit;
        }

        $exists = $db->prepare('SELECT id FROM users WHERE email=? OR username=? LIMIT 1');
        $exists->execute([$application['email'], $application['username']]);
        if ($exists->fetchColumn()) {
            if ($this->isAjax()) {
                json(['success' => false, 'message' => 'An active user account with this email or username already exists.'], 422);
            }
            header('Location: staff-applications?error=account-exists');
            exit;
        }

        $admin = Container::get('auth')->user();
        try {
            $db->beginTransaction();
            $insert = $db->prepare('INSERT INTO users (public_id,username,name,email,phone,password_hash,status,account_type,email_verified_at) VALUES (?,?,?,?,?,?,?,?,?)');
            $insert->execute([
                strtoupper(bin2hex(random_bytes(13))),
                $application['username'],
                $application['name'],
                $application['email'],
                $application['phone'],
                $application['password_hash'],
                'active',
                'staff',
                date('Y-m-d H:i:s')
            ]);

            $userId = (int)$db->lastInsertId();
            $db->prepare('INSERT INTO user_roles (user_id,role_id,assigned_by) VALUES (?,?,?)')
                ->execute([$userId, $roleId, $admin['id'] ?? null]);

            $db->prepare("UPDATE staff_applications SET status='approved',role_id=?,reviewed_by=?,reviewed_at=NOW(),updated_at=NOW() WHERE id=?")
                ->execute([$roleId, $admin['id'] ?? null, $applicationId]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if ($this->isAjax()) {
                json(['success' => false, 'message' => 'Failed to approve application: ' . $e->getMessage()], 500);
            }
            header('Location: staff-applications?error=failed');
            exit;
        }

        $roleName = (string)($db->query("SELECT name FROM roles WHERE id=" . (int)$roleId)->fetchColumn() ?: 'Staff member');

        // Deliver In-App Welcome Notification
        (new \Noir\App\Repositories\CustomerRepository($db))->createNotification(
            $userId,
            'Staff Application Approved',
            "Congratulations {$application['name']}! Your application to join Noir & Co. has been approved. Your staff account is active with the role of {$roleName}. Welcome to the team.",
            'success'
        );

        // Dispatch Email Notification
        $mailOk = (new Mailer())->sendStaffApproval($application['email'], $application['name'], $roleName, $this->absolutePath('/login'));

        // Activity log
        \Noir\Core\ActivityLogger::log('staff.application.approved', 'staff_application', $applicationId, $application['name'], [
            'role' => $roleName,
            'email' => $application['email'],
            'mail_sent' => $mailOk
        ]);

        if ($this->isAjax()) {
            json([
                'success' => true,
                'message' => 'Application approved successfully. Acceptance email and in-app welcome notification sent.',
                'role' => $roleName,
                'application_id' => $applicationId,
                'mail_sent' => $mailOk
            ]);
        }

        header('Location: staff-applications?saved=approved');
        exit;
    }

    public function reject(\Noir\Core\Request $request): never
    {
        $id = (int)$request->input('application_id');
        $reason = trim((string)$request->input('reason'));
        $admin = Container::get('auth')->user();
        $db = Container::get('db');

        $stmt = $db->prepare("SELECT name,email,position FROM staff_applications WHERE id=? AND status='pending' LIMIT 1");
        $stmt->execute([$id]);
        $application = $stmt->fetch();

        if (!$application) {
            if ($this->isAjax()) {
                json(['success' => false, 'message' => 'Application not found or already reviewed.'], 404);
            }
            header('Location: staff-applications?error=invalid');
            exit;
        }

        $reason = $reason ?: 'Application was not approved.';
        $stmt = $db->prepare("UPDATE staff_applications SET status='rejected',rejection_reason=?,reviewed_by=?,reviewed_at=NOW(),updated_at=NOW() WHERE id=? AND status='pending'");
        $stmt->execute([$reason, $admin['id'] ?? null, $id]);

        // If candidate already has an account in the system, deliver an in-app notice
        $existingUser = $db->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $existingUser->execute([$application['email']]);
        $existingUserId = $existingUser->fetchColumn();
        if ($existingUserId) {
            (new \Noir\App\Repositories\CustomerRepository($db))->createNotification(
                (int)$existingUserId,
                'Staff Application Update',
                "Thank you for your interest in joining Noir & Co. Regarding your application for " . ($application['position'] ?? 'staff position') . ": {$reason}",
                'info'
            );
        }

        // Dispatch Email Notification
        $mailOk = (new Mailer())->sendStaffRejection($application['email'], $application['name'], $reason);

        // Activity log
        \Noir\Core\ActivityLogger::log('staff.application.rejected', 'staff_application', $id, $application['name'], [
            'reason' => $reason,
            'email' => $application['email'],
            'mail_sent' => $mailOk
        ]);

        if ($this->isAjax()) {
            json([
                'success' => true,
                'message' => 'Application rejected. Notice email sent to applicant.',
                'reason' => $reason,
                'application_id' => $id,
                'mail_sent' => $mailOk
            ]);
        }

        header('Location: staff-applications?saved=rejected');
        exit;
    }

    public function statusCheck(\Noir\Core\Request $request): never
    {
        $identifier = trim((string)$request->input('ref', $request->input('email', '')));
        if ($identifier === '') {
            if ($this->isAjax()) {
                json(['success' => false, 'message' => 'Please provide an application reference or email address.'], 422);
            }
            header('Location: ' . $this->path('/careers/application-submitted?error=empty'));
            exit;
        }

        $db = Container::get('db');
        $stmt = $db->prepare('SELECT sa.application_number, sa.name, sa.position, sa.status, sa.rejection_reason, sa.reviewed_at, sa.created_at, r.name role_name FROM staff_applications sa LEFT JOIN roles r ON r.id=sa.role_id WHERE sa.application_number=? OR sa.email=? ORDER BY sa.id DESC LIMIT 1');
        $stmt->execute([$identifier, $identifier]);
        $app = $stmt->fetch();

        if (!$app) {
            if ($this->isAjax()) {
                json(['success' => false, 'message' => 'No application found for the provided reference or email.'], 404);
            }
            header('Location: ' . $this->path('/careers/application-submitted?error=notfound'));
            exit;
        }

        json([
            'success' => true,
            'application' => [
                'reference' => $app['application_number'],
                'name' => $app['name'],
                'position' => $app['position'],
                'status' => $app['status'],
                'role' => $app['role_name'] ?? null,
                'reason' => $app['rejection_reason'] ?? null,
                'submitted_at' => $app['created_at'],
                'reviewed_at' => $app['reviewed_at'],
            ]
        ]);
    }

    private function path(string $path): string
    {
        return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . $path;
    }

    private function absolutePath(string $path): string
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . $host . $this->path($path);
    }

    private function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest' || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
    }
}
