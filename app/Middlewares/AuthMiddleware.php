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
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        // Cek keberadaan user aktif (dari session atau Bearer token)
        $user = $request->user();

        if (!$user) {
            if ($request->wantsJson() || $request->isJson() || str_starts_with($request->uri(), '/api/')) {
                return Response::json([
                    'status' => 'error',
                    'error' => 'Unauthenticated',
                    'message' => 'Silakan login terlebih dahulu untuk mengakses sumber daya ini.'
                ], 401);
            }

            return Response::redirect('/login');
        }

        return $next($request);
    }
}
