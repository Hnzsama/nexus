<?php

declare(strict_types=1);

use Core\App;
use Core\Env;
use Core\Http\Router;
use Core\View\ViteAsset;
use App\Middlewares\SessionMiddleware;
use App\Middlewares\CsrfMiddleware;

// 1. Pastikan Autoloader aktif
require_once dirname(__DIR__) . '/vendor/autoload.php';

// 2. Load konfigurasi .env
$baseDir = dirname(__DIR__);
Env::load($baseDir . '/.env');

// 3. Konfigurasi path root untuk ViteAsset
ViteAsset::setBasePath($baseDir);

// 4. Inisialisasi App Container
$app = new App($baseDir);

// 5. Daftarkan Middleware Global pada Router
$router = Router::getInstance();
$router->use([
    SessionMiddleware::class,
    CsrfMiddleware::class,
]);

// 6. Muat definisi rute aplikasi
require_once $baseDir . '/routes/web.php';

return $app;
