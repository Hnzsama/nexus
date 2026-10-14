<?php

declare(strict_types=1);

namespace App\Handlers;

use Core\Http\Request;
use Core\Http\Response;

/**
 * Class HomeHandler
 * 
 * Handler utama untuk halaman beranda dan navigasi awal aplikasi.
 */
class HomeHandler extends Controller
{
    /**
     * Tampilkan halaman welcome dengan data dinamis.
     */
    public function index(Request $request): Response
    {
        return $this->view('pages/welcome', [
            'appName' => env('APP_NAME', 'Nexus Framework'),
            'phpVersion' => PHP_VERSION,
            'environment' => env('APP_ENV', 'local'),
            'debug' => env('APP_DEBUG', true),
        ]);
    }
}
