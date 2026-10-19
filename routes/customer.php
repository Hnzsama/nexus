<?php

declare(strict_types=1);

use App\Handlers\Customer\CheckoutController;
use App\Handlers\Customer\CinemaController;
use App\Handlers\Customer\TicketController;
use App\Middlewares\AuthMiddleware;
use Core\Http\Router;

/*
|--------------------------------------------------------------------------
| Customer & Public Cinema Routes
|--------------------------------------------------------------------------
|
| Rute customer untuk melihat katalog bioskop, film yang sedang tayang,
| jadwal, peta kursi real-time, checkout tiket, dan melihat e-tiket.
|
*/

// =========================================================================
// 1. PUBLIC CATALOG & SHOWTIME SCHEDULE
// =========================================================================
Router::get('/api/cinemas', [CinemaController::class, 'cinemas'])->name('api.cinemas');
Router::get('/api/movies', [CinemaController::class, 'movies'])->name('api.movies');
Router::get('/api/movies/{slug}', [CinemaController::class, 'movieDetail'])->name('api.movies.detail');
Router::get('/api/showtimes/{id}/seats', [CinemaController::class, 'seats'])->name('api.showtimes.seats');

// =========================================================================
// 2. CHECKOUT & TRANSAKSI PEMESANAN TIKET
// =========================================================================
Router::post('/api/checkout/create-transaction', [CheckoutController::class, 'createTransaction'])
    ->middleware(AuthMiddleware::class)
    ->name('api.checkout.create');

Router::get('/api/orders/{orderNumber}', [CheckoutController::class, 'orderDetail'])
    ->name('api.orders.detail');

// =========================================================================
// 3. E-TIKET DIGITAL CUSTOMER
// =========================================================================
Router::get('/api/tickets', [TicketController::class, 'myTickets'])
    ->middleware(AuthMiddleware::class)
    ->name('api.tickets.index');
