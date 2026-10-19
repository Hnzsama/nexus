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

// Contoh Endpoint POST dengan proteksi CSRF
Router::post('/submit', function (Request $request) {
    return Response::json([
        'message' => 'Data formulir berhasil diterima!',
        'payload' => $request->all(),
    ]);
})->name('submit');

// Manajemen User & Pengujian Active Record ORM
Router::get('/users', [UserController::class, 'index'])->name('users.index');
Router::post('/users', [UserController::class, 'store'])->name('users.store');
Router::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
Router::post('/users/{id}/delete', [UserController::class, 'destroy'])->name('users.delete');
Router::get('/users/seed', [UserController::class, 'seed'])->name('users.seed');

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

use App\Handlers\CinemaController;
use App\Handlers\CheckoutController;
use App\Handlers\PaymentWebhookController;

// =========================================================================
// CINEMA TICKETING, CONCURRENCY & PAYMENT API (BAB 1 - 5)
// =========================================================================

// 1. Katalog Film & Slot Jadwal Tayang
Router::get('/api/movies', [CinemaController::class, 'movies'])->name('api.movies.index');
Router::get('/api/movies/{slug}', [CinemaController::class, 'movieDetail'])->name('api.movies.detail');
Router::get('/api/cinema/seed', [CinemaController::class, 'seed'])->name('api.cinema.seed');

// 2. Denah Kursi Real-Time (Visual 4 Status & Lazy Expiry)
Router::get('/api/showtimes/{id}/seats', [CinemaController::class, 'seats'])->name('api.showtimes.seats');

// 3. Checkout Transaksi Atomik & Perolehan Midtrans Snap Token
Router::post('/api/checkout/create-transaction', [CheckoutController::class, 'createTransaction'])->name('api.checkout.create');
Router::post('/checkout/create-transaction', [CheckoutController::class, 'createTransaction'])->name('checkout.create');
Router::get('/api/orders/{orderNumber}', [CheckoutController::class, 'orderDetail'])->name('api.orders.detail');

// 4. Webhook Notifikasi Midtrans Idempoten & Verifikasi SHA-512
Router::post('/api/payment/webhook', [PaymentWebhookController::class, 'handle'])->name('api.payment.webhook');
Router::post('/payment/webhook', [PaymentWebhookController::class, 'handle'])->name('payment.webhook');

// =========================================================================
// 5. AUTHENTICATION & USER PROFILE ENGINE
// =========================================================================
require __DIR__ . '/auth.php';
