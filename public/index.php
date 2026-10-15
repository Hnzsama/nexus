<?php

declare(strict_types=1);

use Core\App;
use Core\Http\Request;

/**
 * Nexus Framework - Front Controller
 * Seluruh HTTP Request diarahkan ke file ini untuk diproses oleh Router dan App Lifecycle.
 */

define('NEXUS_START', microtime(true));

// 1. Deteksi otomatis lokasi bootstrap/app.php
$bootstrapFile = null;

// Skenario A: Mode Standar (index.php berada di dalam folder <project>/public/)
if (file_exists(dirname(__DIR__) . '/bootstrap/app.php')) {
    $bootstrapFile = dirname(__DIR__) . '/bootstrap/app.php';
}
// Skenario B: index.php berada langsung di root project
elseif (file_exists(__DIR__ . '/bootstrap/app.php')) {
    $bootstrapFile = __DIR__ . '/bootstrap/app.php';
}
// Skenario C: Mode aaPanel (index.php berada di web root luar, project berada di subfolder)
else {
    // Cek opsi konfigurasi env spesifik jika ada
    $projectDir = getenv('AAPANEL_PROJECT_DIR') ?: getenv('PROJECT_DIR');
    if ($projectDir && file_exists(__DIR__ . '/' . trim($projectDir, '/') . '/bootstrap/app.php')) {
        $bootstrapFile = __DIR__ . '/' . trim($projectDir, '/') . '/bootstrap/app.php';
    } else {
        // Scan otomatis seluruh subdirektori langsung untuk mencari folder proyek Nexus
        $subdirs = glob(__DIR__ . '/*', GLOB_ONLYDIR) ?: [];
        foreach ($subdirs as $dir) {
            if (file_exists($dir . '/bootstrap/app.php')) {
                $bootstrapFile = $dir . '/bootstrap/app.php';
                break;
            }
        }
    }
}

if (!$bootstrapFile) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Nexus Framework Error: File bootstrap/app.php tidak ditemukan.\n";
    echo "Pastikan folder proyek diletakkan berdampingan dengan file index.php atau konfigurasikan AAPANEL_PROJECT_DIR.\n";
    exit(1);
}

// 2. Bootstrapping framework
/** @var App $app */
$app = require_once $bootstrapFile;

// 3. Tangkap request HTTP saat ini
$request = Request::capture();

// 4. Handle request melalui Router dan Middleware Pipeline
$response = $app->handle($request);

// 5. Kirim header dan konten response ke browser client
$response->send();
