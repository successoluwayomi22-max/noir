<?php
declare(strict_types=1);

namespace Noir\Core;

final class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register(static function (string $class): void {
            $prefixes = [
                'Noir\\App\\' => BASE_PATH . '/app/',
                'Noir\\Core\\' => BASE_PATH . '/core/',
            ];
            foreach ($prefixes as $prefix => $directory) {
                if (str_starts_with($class, $prefix)) {
                    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
                    $file = $directory . $relative . '.php';
                    if (is_file($file)) { require $file; }
                }
            }
        });
    }
}
