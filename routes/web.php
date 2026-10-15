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



