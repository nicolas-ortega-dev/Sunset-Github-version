<?php
declare(strict_types=1);

use Sunset\Core\Config;

define('ROOT_PATH', dirname(__DIR__));

// Autoload PSR-4: Sunset\Core\Database -> src/Core/Database.php
spl_autoload_register(static function (string $class): void {
    $prefix = 'Sunset\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = ROOT_PATH . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require ROOT_PATH . '/src/helpers.php';

if (Config::get('app.debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

date_default_timezone_set('America/Santiago');

if (PHP_SAPI !== 'cli') {   // los scripts de consola no usan sesion
    session_name('sunset_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}
