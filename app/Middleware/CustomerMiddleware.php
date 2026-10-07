<?php
declare(strict_types=1);
namespace Noir\App\Middleware;
use Noir\Core\Container; use Noir\Core\Request;
final class CustomerMiddleware
{
    public function handle(Request $request): void {
        $base = rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/');
        if(!Container::get('auth')->check()){header('Location: ' . ($base ?: '') . '/login');exit;}
        $user=Container::get('auth')->user();
        $accountType=$user['account_type']??null;
        if($accountType===null&&isset($user['user_id'])){
            $q=Container::get('db')->prepare('SELECT account_type FROM users WHERE id=?');
            $q->execute([(int)$user['user_id']]);
            $accountType=$q->fetchColumn();
        }
        $roles=$user['roles']??[];
        if($accountType==='customer'||in_array('customer',$roles,true)||Container::get('auth')->can('*'))return;
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}
