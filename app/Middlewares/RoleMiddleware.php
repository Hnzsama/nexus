<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Models\User;
use Closure;
use Core\Http\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Class RoleMiddleware
 * 
 * Middleware dinamis untuk memvalidasi hak akses berdasarkan peran (Role-Based Access Control).
 * 
 * Penggunaan di rute:
 * ->middleware(RoleMiddleware::allow('admin'))
 * ->middleware(RoleMiddleware::allow(['admin', 'customer']))
 */
class RoleMiddleware implements MiddlewareInterface
{
    /**
     * @param string[] $allowedRoles
     */
    public function __construct(
        protected array $allowedRoles = ['admin']
    ) {}

    /**
     * Factory helper fluent untuk membatasi rute ke satu atau banyak role tertentu.
     *
     * @param string|string[] $roles
     */
    public static function allow(string|array $roles): Closure
    {
        $roleList = array_map('strtolower', (array) $roles);

        return function (Request $request, Closure $next) use ($roleList): Response {
            $middleware = new self($roleList);
            return $middleware->handle($request, $next);
        };
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $user = $request->user();

        // 1. Cek Autentikasi
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

        // 2. Ambil role pengguna
        $userRole = 'customer';
        if ($user instanceof User) {
            $userRole = strtolower((string) ($user->role ?? 'customer'));
        } elseif (is_array($user)) {
            $userRole = strtolower((string) ($user['role'] ?? 'customer'));
        } elseif (is_object($user)) {
            $userRole = strtolower((string) ($user->role ?? 'customer'));
        }

        // 3. Cocokkan dengan daftar role yang diizinkan
        if (!in_array($userRole, $this->allowedRoles, true)) {
            $allowedStr = implode(', ', $this->allowedRoles);

            if ($request->wantsJson() || $request->isJson() || str_starts_with($request->uri(), '/api/')) {
                return Response::json([
                    'status'        => 'error',
                    'error'         => 'Forbidden',
                    'message'       => "Akses ditolak. Rute ini memerlukan peran [{$allowedStr}], namun Anda masuk sebagai [{$userRole}].",
                    'current_role'  => $userRole,
                    'required_role' => $this->allowedRoles,
                ], 403);
            }

            return Response::json([
                'status'  => 'error',
                'message' => '403 Forbidden: Hak akses tidak mencukupi.'
            ], 403);
        }

        return $next($request);
    }
}
