<?php

declare(strict_types=1);

use App\Handlers\HomeHandler;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Router;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Di sini Anda mendaftarkan rute aplikasi. Router mendukung closure cepat,
| Controller/Handler, parameter dinamis {param}, serta route grouping.
*/

// Halaman Utama melalui Controller & Native View Engine
Router::get('/', [HomeHandler::class, 'index'])->name('home');

// Endpoint JSON API
Router::get('/api/ping', function (Request $request) {
    return Response::json([
        'status' => 'success',
        'framework' => 'Nexus MVC',
        'environment' => env('APP_ENV', 'local'),
        'timestamp' => date('c'),
        'client_ip' => $request->ip(),
    ]);
})->name('api.ping');

// Parameter Rute Dinamis: /greet/{name}
Router::get('/greet/{name}', function (Request $request, string $name) {
    return Response::json([
        'greeting' => "Halo, {$name}!",
        'route_params' => $request->params(),
        'method' => $request->method(),
    ]);
})->name('greet');

// Route Grouping dengan Prefix
Router::group(['prefix' => '/api/v1'], function (Router $router) {
    $router->addGet('/version', fn() => Response::json(['version' => '1.0.0']))->name('api.v1.version');
});

use App\Handlers\UserController;
use App\Middlewares\AdminMiddleware;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\GuestMiddleware;
use App\Middlewares\RoleMiddleware;

// Contoh Endpoint POST dengan proteksi CSRF
Router::post('/submit', function (Request $request) {
    return Response::json([
        'message' => 'Data formulir berhasil diterima!',
        'payload' => $request->all(),
    ]);
})->name('submit');

// Manajemen User Backoffice (Dilindungi AuthMiddleware & AdminMiddleware)
Router::group([
    'prefix' => '/users',
    'middleware' => [AuthMiddleware::class, AdminMiddleware::class]
], function (Router $router) {
    $router->addGet('', [UserController::class, 'index'])->name('users.index');
    $router->addPost('', [UserController::class, 'store'])->name('users.store');
    $router->addDelete('/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    $router->addPost('/{id}/delete', [UserController::class, 'destroy'])->name('users.delete');
    $router->addGet('/seed', [UserController::class, 'seed'])->name('users.seed');
});

use App\Handlers\DocsHandler;

// Rute Dokumentasi Teknis Sistem (SPA Viewer dengan Dukungan Mermaid & Markdown)
Router::get('/docs', [DocsHandler::class, 'index'])->name('docs.index');
Router::get('/docs/{slug}', [DocsHandler::class, 'index'])->name('docs.show');
Router::get('/api/docs/{slug}', [DocsHandler::class, 'apiGetDoc'])->name('api.docs.get');

// API Pelacakan & Checklist Interaktif Jobdesk Tim (Penyimpanan JSON di storage/app/jobdesk_progress.json)
Router::get('/api/docs-jobdesk/progress', [DocsHandler::class, 'apiGetJobdeskProgress'])->name('api.docs.jobdesk.progress');
Router::post('/api/docs-jobdesk/toggle', [DocsHandler::class, 'apiToggleJobdeskTask'])->name('api.docs.jobdesk.toggle');
Router::post('/api/docs-jobdesk/reset', [DocsHandler::class, 'apiResetJobdeskProgress'])->name('api.docs.jobdesk.reset');

use App\Handlers\Swagger\SwaggerController;

// Rute Dokumentasi RESTful API Swagger UI & OpenAPI 3.0
Router::get('/swagger', [SwaggerController::class, 'index'])->name('swagger.index');
Router::get('/swagger/scalar', [SwaggerController::class, 'scalar'])->name('swagger.scalar');
Router::get('/swagger/openapi.json', [SwaggerController::class, 'spec'])->name('swagger.json');

use App\Handlers\PaymentWebhookController;

// Webhook Notifikasi Midtrans Idempoten & Verifikasi SHA-512
Router::post('/api/payment/webhook', [PaymentWebhookController::class, 'handle'])->name('api.payment.webhook');
Router::post('/payment/webhook', [PaymentWebhookController::class, 'handle'])->name('payment.webhook');

// =========================================================================
// MODULAR ROUTE SUB-MODULES (Auth, Customer, & Admin Backoffice)
// =========================================================================
require __DIR__ . '/auth.php';
require __DIR__ . '/customer.php';
require __DIR__ . '/admin.php';
