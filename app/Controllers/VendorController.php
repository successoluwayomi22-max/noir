<?php

declare(strict_types=1);

namespace Noir\App\Controllers;

use Noir\App\Traits\ManagesProfileImage;
use Noir\Core\Container;
use Noir\Core\Request;

final class VendorController
{
    use ManagesProfileImage;

    public function dashboard(): never
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $user = Container::get('auth')->user();
        $saved = isset($_GET['saved']);
        require BASE_PATH . '/app/Views/vendor/dashboard.php';
        exit;
    }

    public function updateProfile(Request $request): never
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $this->handleProfileUpload($request, ($base ?: '') . '/vendor/dashboard');
    }
}
