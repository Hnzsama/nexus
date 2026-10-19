<?php

declare(strict_types=1);

namespace App\Middlewares;

use Closure;
use Core\Http\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Class CsrfMiddleware
 * 
 * Melindungi aplikasi dari serangan Cross-Site Request Forgery (CSRF).
 * Memvalidasi token pada method yang mengubah state (POST, PUT, PATCH, DELETE).
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * Pola URI yang dikecualikan dari proteksi CSRF (misal: webhook atau API publik).
     *
     * @var array<int, string>
     */
    protected array $except = [
        'api/*',
        'payment/*',
        'payment/webhook',
        'checkout/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Pastikan session aktif
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Generate token jika belum tersedia
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        // Metode baca (GET, HEAD, OPTIONS) tidak memerlukan validasi token
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // Cek apakah URL dikecualikan
        if ($this->isExcluded($request)) {
            return $next($request);
        }

        // Ambil token dari body input atau HTTP header
        $token = $request->input('_token')
            ?? $request->header('x-csrf-token')
            ?? $request->header('x-xsrf-token');

        if (!is_string($token) || !hash_equals((string) $_SESSION['_csrf_token'], $token)) {
            if ($request->wantsJson() || $request->isJson()) {
                return Response::json([
                    'error' => 'CSRF token mismatch or expired',
                    'code' => 419
                ], 419);
            }

            return Response::html(
                '<!DOCTYPE html><html><head><title>419 Page Expired</title>' .
                '<style>body{font-family:sans-serif;background:#090d16;color:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}' .
                '.box{text-align:center;padding:2rem;background:#111827;border-radius:12px;border:1px solid #1f293d;}' .
                'h1{color:#ef4444;margin-bottom:0.5rem;}p{color:#94a3b8;}</style></head>' .
                '<body><div class="box"><h1>419 | Page Expired</h1><p>Sesi CSRF token Anda tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.</p></div></body></html>',
                419
            );
        }

        return $next($request);
    }

    /**
     * Dapatkan CSRF token saat ini.
     */
    public static function token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    /**
     * Generate HTML input hidden untuk form HTML.
     */
    public static function field(): string
    {
        return sprintf('<input type="hidden" name="_token" value="%s">', htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Cek apakah URI masuk dalam daftar pengecualian.
     */
    protected function isExcluded(Request $request): bool
    {
        $uri = trim($request->uri(), '/');

        foreach ($this->except as $pattern) {
            $pattern = trim($pattern, '/');

            if ($pattern === $uri) {
                return true;
            }

            if (str_ends_with($pattern, '*')) {
                $prefix = rtrim(substr($pattern, 0, -1), '/');
                if (str_starts_with($uri, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }
}
