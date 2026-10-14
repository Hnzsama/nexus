<?php

declare(strict_types=1);

namespace App\Middlewares;

use Closure;
use Core\Http\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Class SessionMiddleware
 * 
 * Memastikan PHP Session aktif dan terkonfigurasi dengan baik
 * untuk seluruh request web.
 */
class SessionMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Konfigurasi session cookie yang aman jika belum dimulai
            if (!headers_sent()) {
                session_set_cookie_params([
                    'lifetime' => 7200,
                    'path' => '/',
                    'domain' => '',
                    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
                @session_start();
            } elseif (php_sapi_name() === 'cli') {
                @session_start();
            }
        }

        $response = $next($request);

        // Bersihkan flash data (error validasi dan old input) setelah selesai dirender pada request GET
        if ($request->method() === 'GET' && isset($_SESSION)) {
            unset($_SESSION['_errors'], $_SESSION['_old']);
            
            // Hapus juga flash session khusus
            foreach (array_keys($_SESSION) as $sessionKey) {
                if (str_starts_with((string) $sessionKey, '_flash_')) {
                    unset($_SESSION[$sessionKey]);
                }
            }
        }

        return $response;
    }
}
