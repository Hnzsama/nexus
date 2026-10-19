<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Models\User;
use Closure;
use Core\Http\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Class AdminMiddleware
 * 
 * Middleware otorisasi hak akses Administrator.
 * Memastikan bahwa pengguna yang sedang login memiliki peran 'admin'.
 */
class AdminMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $user = $request->user();

        // 1. Cek autentikasi (harus login terlebih dahulu)
        if (!$user) {
            if ($request->wantsJson() || $request->isJson() || str_starts_with($request->uri(), '/api/')) {
                return Response::json([
                    'status'  => 'error',
                    'error'   => 'Unauthenticated',
                    'message' => 'Silakan login terlebih dahulu untuk mengakses sumber daya ini.'
                ], 401);
            }

            return Response::redirect('/login');
        }

        // 2. Cek otorisasi peran Admin
        $isAdmin = false;
        if ($user instanceof User) {
            $isAdmin = $user->isAdmin();
        } else {
            $role = is_array($user) ? ($user['role'] ?? null) : ($user->role ?? null);
            $roleId = is_array($user) ? ($user['role_id'] ?? null) : ($user->role_id ?? null);
            $isAdmin = strtolower((string) $role) === 'admin' || (int) $roleId === 1;
        }

        if (!$isAdmin) {
            if ($request->wantsJson() || $request->isJson() || str_starts_with($request->uri(), '/api/')) {
                return Response::json([
                    'status'  => 'error',
                    'error'   => 'Forbidden',
                    'message' => 'Akses ditolak. Endpoint ini hanya dapat diakses oleh Administrator.'
                ], 403);
            }

            return Response::json([
                'status'  => 'error',
                'message' => '403 Forbidden: Anda tidak memiliki izin untuk mengakses halaman ini.'
            ], 403);
        }

        return $next($request);
    }
}
