<?php

declare(strict_types=1);

use App\Handlers\Admin\CinemaController;
use App\Handlers\Admin\MovieController;
use App\Handlers\Admin\OrderController;
use App\Handlers\Admin\ShowtimeController;
use App\Middlewares\AdminMiddleware;
use App\Middlewares\AuthMiddleware;
use Core\Http\Router;

/*
|--------------------------------------------------------------------------
| Admin & Backoffice Routes
|--------------------------------------------------------------------------
|
| Rute-rute khusus Administrator untuk pengelolaan data master bioskop,
| studio, katalog film, slot jadwal tayang, dan laporan transaksi.
| Seluruh rute dilindungi oleh AuthMiddleware dan AdminMiddleware.
|
*/

// =========================================================================
// 1. MANAJEMEN BIOSKOP (CINEMAS)
// =========================================================================
Router::get('/api/admin/cinemas', [CinemaController::class, 'index'])
    ->middleware(AuthMiddleware::class)
    ->middleware(AdminMiddleware::class)
    ->name('api.admin.cinemas.index');

Router::post('/api/admin/cinemas', [CinemaController::class, 'store'])
    ->middleware(AuthMiddleware::class)
    ->middleware(AdminMiddleware::class)
    ->name('api.admin.cinemas.store');

Router::put('/api/admin/cinemas/{id}', [CinemaController::class, 'update'])
    ->middleware(AuthMiddleware::class)
    ->middleware(AdminMiddleware::class)
    ->name('api.admin.cinemas.update');

Router::delete('/api/admin/cinemas/{id}', [CinemaController::class, 'destroy'])
    ->middleware(AuthMiddleware::class)
    ->middleware(AdminMiddleware::class)
    ->name('api.admin.cinemas.destroy');

// =========================================================================
// 2. MANAJEMEN KATALOG FILM (MOVIES)
// =========================================================================
Router::get('/api/admin/movies', [MovieController::class, 'index'])
    ->middleware(AuthMiddleware::class)
    ->middleware(AdminMiddleware::class)
    ->name('api.admin.movies.index');

Router::post('/api/admin/movies', [MovieController::class, 'store'])
    ->middleware(AuthMiddleware::class)
    ->middleware(AdminMiddleware::class)
    ->name('api.admin.movies.store');

Router::put('/api/admin/movies/{id}', [MovieController::class, 'update'])
    ->middleware(AuthMiddleware::class)
    ->middleware(AdminMiddleware::class)
    ->name('api.admin.movies.update');

Router::delete('/api/admin/movies/{id}', [MovieController::class, 'destroy'])
    ->middleware(AuthMiddleware::class)
    ->middleware(AdminMiddleware::class)
    ->name('api.admin.movies.destroy');

// =========================================================================
// 3. MANAJEMEN JADWAL PENAYANGAN (SHOWTIMES)
// =========================================================================
Router::get('/api/admin/showtimes', [ShowtimeController::class, 'index'])
    ->middleware(AuthMiddleware::class)
    ->middleware(AdminMiddleware::class)
    ->name('api.admin.showtimes.index');

Router::post('/api/admin/showtimes', [ShowtimeController::class, 'store'])
    ->middleware(AuthMiddleware::class)
    ->middleware(AdminMiddleware::class)
    ->name('api.admin.showtimes.store');

Router::delete('/api/admin/showtimes/{id}', [ShowtimeController::class, 'destroy'])
    ->middleware(AuthMiddleware::class)
    ->middleware(AdminMiddleware::class)
    ->name('api.admin.showtimes.destroy');

// =========================================================================
// 4. MONITORING TRANSAKSI & LAPORAN ORDER (ORDERS & AUDIT)
// =========================================================================
Router::get('/api/admin/orders', [OrderController::class, 'index'])
    ->middleware(AuthMiddleware::class)
    ->middleware(AdminMiddleware::class)
    ->name('api.admin.orders.index');
