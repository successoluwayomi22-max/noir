<?php
declare(strict_types=1);
namespace Noir\App\Middleware;
use Noir\Core\Container; use Noir\Core\Request;
final class GuestMiddleware { public function handle(Request $request): void { if(Container::get('auth')->check()){ $user=Container::get('auth')->user(); if(!empty($user['is_guest']))return; $base=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/'); $path=Container::get('role_redirect')->pathFor($user['roles']??[]); header('Location: '.($base?:'').$path); exit; } } }
