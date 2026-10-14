<?php

declare(strict_types=1);

use Core\Env;
use Core\View\ViteAsset;

if (!function_exists('env')) {
    /**
     * Dapatkan nilai variabel environment dengan fallback default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('vite')) {
    /**
     * Helper untuk meng-inject tag script atau stylesheet hasil bundling Vite.
     * Otomatis beralih ke mode HMR dev jika server Vite sedang berjalan.
     *
     * @param string|array<int, string> $entries Contoh: 'resources/js/app.js'
     * @return string Tag HTML lengkap (<script> / <link>)
     */
    function vite(string|array $entries): string
    {
        return ViteAsset::render($entries);
    }
}

if (!function_exists('base_path')) {
    /**
     * Dapatkan path absolut ke direktori root aplikasi.
     *
     * @param string $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__);
        return $path !== '' ? $base . '/' . ltrim($path, '/\\') : $base;
    }
}

if (!function_exists('public_path')) {
    /**
     * Dapatkan path absolut ke direktori public.
     *
     * @param string $path
     * @return string
     */
    function public_path(string $path = ''): string
    {
        return base_path('public' . ($path !== '' ? '/' . ltrim($path, '/\\') : ''));
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Dapatkan token CSRF untuk session saat ini.
     */
    function csrf_token(): string
    {
        return \App\Middlewares\CsrfMiddleware::token();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate tag HTML hidden input berisi token CSRF.
     */
    function csrf_field(): string
    {
        return \App\Middlewares\CsrfMiddleware::field();
    }
}

if (!function_exists('response')) {
    /**
     * Helper pembuat instance Response HTTP.
     */
    function response(mixed $content = '', int $status = 200, array $headers = []): \Core\Http\Response
    {
        return \Core\Http\Response::make($content, $status, $headers);
    }
}

if (!function_exists('view')) {
    /**
     * Render view template menggunakan ViewEngine dan kembalikan Response HTML.
     *
     * @param string $template Path template (misal: 'pages/welcome' atau 'pages.welcome')
     * @param array<string, mixed> $data
     * @param int $status
     * @param array<string, string> $headers
     * @return \Core\Http\Response
     */
    function view(string $template, array $data = [], int $status = 200, array $headers = []): \Core\Http\Response
    {
        $engine = new \Core\View\ViewEngine();
        $html = $engine->render($template, $data);
        return \Core\Http\Response::html($html, $status, $headers);
    }
}

if (!function_exists('old')) {
    /**
     * Dapatkan nilai input formulir sebelumnya yang tersimpan di flash session.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function old(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('error')) {
    /**
     * Dapatkan pesan error validasi pertama untuk field tertentu.
     *
     * @param string $field
     * @return string|null
     */
    function error(string $field): ?string
    {
        $errs = errors($field);
        return !empty($errs) ? $errs[0] : null;
    }
}

if (!function_exists('errors')) {
    /**
     * Dapatkan daftar error validasi (seluruhnya atau spesifik per field).
     *
     * @param string|null $field
     * @return array<string, array<int, string>>|array<int, string>
     */
    function errors(?string $field = null): array
    {
        $all = $_SESSION['_errors'] ?? [];
        if ($field === null) {
            return $all;
        }
        return $all[$field] ?? [];
    }
}

if (!function_exists('has_error')) {
    /**
     * Cek apakah field memiliki error validasi.
     *
     * @param string $field
     * @return bool
     */
    function has_error(string $field): bool
    {
        return !empty($_SESSION['_errors'][$field]);
    }
}

if (!function_exists('flash')) {
    /**
     * Ambil data flash session satu kali pakai.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function flash(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_flash_' . $key] ?? $default;
    }
}

if (!function_exists('set_flash')) {
    /**
     * Set data flash session satu kali pakai.
     *
     * @param string $key
     * @param mixed $value
     */
    function set_flash(string $key, mixed $value): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION['_flash_' . $key] = $value;
    }
}

if (!function_exists('back')) {
    /**
     * Redirect kembali ke halaman URL sebelumnya (HTTP Referer).
      *
      * @param string $fallback
      * @return \Core\Http\Response
      */
     function back(string $fallback = '/'): \Core\Http\Response
     {
         $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;
         return \Core\Http\Response::redirect($referer);
     }
}

if (!function_exists('route')) {
    /**
     * Generate URL dari nama rute (mirip helper route() di Laravel).
     *
     * @param string $name Nama rute terdaftar (misal: 'users.index', 'users.store', 'users.delete')
     * @param mixed $parameters Parameter rute dinamis (misal: ['id' => 10] atau scalar 10)
     * @return string
     */
    function route(string $name, mixed $parameters = []): string
    {
        return \Core\Http\Router::getInstance()->url($name, $parameters);
    }
}

if (!function_exists('storage_path')) {
    /**
     * Dapatkan path absolut ke direktori storage aplikasi.
     *
     * @param string $path
     * @return string
     */
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path !== '' ? '/' . ltrim($path, '/\\') : ''));
    }
}

if (!function_exists('storage')) {
    /**
     * Helper untuk mengakses instance Storage Disk (mirip Storage::disk() di Laravel).
     *
     * @param string|null $disk Nama disk di config/filesystems.php (default: 'local')
     * @return \Core\Storage\Disk
     */
    function storage(?string $disk = null): \Core\Storage\Disk
    {
        return \Core\Storage\Storage::disk($disk);
    }
}

if (!function_exists('asset')) {
    /**
     * Generate URL publik untuk berkas asset statis atau public storage.
     *
     * @param string $path Path relatif berkas
     * @return string
     */
    function asset(string $path): string
    {
        $rootUrl = rtrim((string) env('APP_URL', 'http://127.0.0.1:8000'), '/');
        return $rootUrl . '/' . ltrim($path, '/');
    }
}




