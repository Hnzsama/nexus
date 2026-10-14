<?php

declare(strict_types=1);

namespace App\Middlewares;

use Closure;
use Core\Http\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Class AuthMiddleware
 * 
 * Middleware autentikasi pengguna.
 * Memastikan sesi login atau token valid sebelum mengizinkan akses ke rute terproteksi.
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Cek keberadaan user session atau bearer token
        $isLoggedIn = !empty($_SESSION['user_id']) || $request->bearerToken() !== null;

        if (!$isLoggedIn) {
            if ($request->wantsJson()) {
                return Response::json([
                    'error' => 'Unauthenticated',
                    'message' => 'Silakan login terlebih dahulu untuk mengakses sumber daya ini.'
                ], 401);
            }

            return Response::redirect('/login');
        }

        return $next($request);
    }
}
