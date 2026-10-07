<?php
declare(strict_types=1);

namespace Noir\App\Traits;

use Noir\Core\Container;
use Noir\Core\Request;

trait ManagesProfileImage
{
    /**
     * Reusable method for uploading a profile image.
     * Returns an array: ['success' => bool, 'message' => string, 'url' => ?string]
     */
    protected function processProfileImageUpload(array $fileData, int $userId): array
    {
        if (empty($fileData) || (int)($fileData['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Please select a valid image file.'];
        }

        if ((int)$fileData['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'Image must be 5MB or smaller.'];
        }

        $tmp = (string)$fileData['tmp_name'];
        if (!is_file($tmp)) {
            return ['success' => false, 'message' => 'Upload failed.'];
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $exts = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($exts[$mime])) {
            return ['success' => false, 'message' => 'Allowed formats are JPG, PNG, and WebP.'];
        }

        $dir = BASE_PATH . '/public/uploads/avatars';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'avatar-' . $userId . '-' . bin2hex(random_bytes(6)) . '.' . $exts[$mime];
        $dest = $dir . '/' . $filename;

        if (move_uploaded_file($tmp, $dest)) {
            $db = Container::get('db');
            try {
                $stmt = $db->prepare('SELECT profile_image FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$userId]);
                $old = (string)$stmt->fetchColumn();

                if ($old && str_starts_with($old, '/uploads/avatars/')) {
                    $oldFile = BASE_PATH . '/public' . $old;
                    if (is_file($oldFile)) {
                        @unlink($oldFile);
                    }
                }

                $publicUrl = '/uploads/avatars/' . $filename;
                $db->prepare('UPDATE users SET profile_image = ? WHERE id = ?')->execute([$publicUrl, $userId]);
                
                if (isset($_SESSION['user'])) {
                    $_SESSION['user']['profile_image'] = $publicUrl;
                    $_SESSION['user']['avatar'] = $publicUrl;
                }
                
                return ['success' => true, 'message' => 'Profile photo updated successfully.', 'url' => $publicUrl];
            } catch (\Throwable) {
                 return ['success' => false, 'message' => 'Database error while saving profile image.'];
            }
        }

        return ['success' => false, 'message' => 'Failed to upload image.'];
    }

    /**
     * Reusable method for removing a profile image.
     */
    protected function processProfileImageRemoval(int $userId): array
    {
        $db = Container::get('db');
        try {
            $stmt = $db->prepare('SELECT profile_image FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $old = (string)$stmt->fetchColumn();
            
            if ($old && str_starts_with($old, '/uploads/avatars/')) {
                $file = BASE_PATH . '/public' . $old;
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            $db->prepare('UPDATE users SET profile_image = NULL WHERE id = ?')->execute([$userId]);
        } catch (\Throwable) {}

        if (isset($_SESSION['user'])) {
            $_SESSION['user']['profile_image'] = null;
            $_SESSION['user']['avatar'] = null;
        }

        return ['success' => true, 'message' => 'Profile photo removed successfully.'];
    }

    protected function handleProfileUpload(Request $request, string $redirectUrl): never
    {
        $user = Container::get('auth')->user();
        $userId = (int)($user['user_id'] ?? $user['id'] ?? 0);
        
        if (!$userId) {
            header('Location: ' . $redirectUrl . '?error=auth');
            exit;
        }

        if ($request->input('remove_profile')) {
            $this->processProfileImageRemoval($userId);
            header('Location: ' . $redirectUrl . '?saved=1');
            exit;
        }

        $result = $this->processProfileImageUpload($_FILES['profile'] ?? [], $userId);
        
        if ($result['success']) {
            header('Location: ' . $redirectUrl . '?saved=1');
            exit;
        }

        header('Location: ' . $redirectUrl . '?error=upload');
        exit;
    }
}
