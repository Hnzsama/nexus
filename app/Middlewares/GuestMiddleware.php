<?php

declare(strict_types=1);

namespace App\Middlewares;

use Closure;
use Core\Http\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Class GuestMiddleware
 * 
 * Memastikan bahwa pengguna yang mengakses belum melakukan login (Guest / Tamu).
 * Jika pengguna sudah terautentikasi, akses ke halaman register/login dialihkan atau ditolak.
 */
class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $user = $request->user();

        // Jika sudah login
        if ($user) {
            $userName = is_array($user) ? ($user['name'] ?? 'User') : ($user->name ?? 'User');

            if ($request->wantsJson() || $request->isJson() || str_starts_with($request->uri(), '/api/')) {
                return Response::json([
                    'status'  => 'error',
                    'message' => "Anda sudah login sebagai {$userName}. Silakan logout terlebih dahulu.",
                ], 400);
            }

            return Response::redirect('/');
        }

        return $next($request);
    }
}
