<?php

declare(strict_types=1);

namespace Core\View;

use RuntimeException;

/**
 * Class ViteAsset
 * 
 * Pengelola integrasi aset Vite untuk PHP native.
 * Mampu beralih secara dinamis antara Mode Dev (Hot Module Replacement)
 * dan Mode Production (Asset Hash Manifest).
 */
class ViteAsset
{
    /**
     * Cache status apakah Vite dev server sedang aktif.
     */
    protected static ?bool $isDevRunning = null;

    /**
     * Penanda apakah tag script @vite/client sudah pernah di-inject.
     */
    protected static bool $clientInjected = false;

    /**
     * Cache manifest JSON untuk mode production.
     *
     * @var array<string, array<string, mixed>>|null
     */
    protected static ?array $manifest = null;

    /**
     * Lokasi base direktori root project.
     */
    protected static ?string $basePath = null;

    /**
     * Set base path framework root.
     */
    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/\\');
    }

    /**
     * Dapatkan base path project.
     */
    public static function getBasePath(): string
    {
        if (self::$basePath === null) {
            self::$basePath = dirname(__DIR__, 2);
        }
        return self::$basePath;
    }

    /**
     * URL Vite Dev Server.
     */
    public static function getDevServerUrl(): string
    {
        return rtrim((string) (function_exists('env') ? env('VITE_DEV_SERVER_URL', 'http://localhost:5173') : 'http://localhost:5173'), '/');
    }

    /**
     * Cek apakah Vite dev server sedang aktif (HMR mode).
     * Memeriksa koneksi socket TCP dengan timeout sangat singkat (30ms).
     */
    public static function isDevServerRunning(): bool
    {
        if (self::$isDevRunning !== null) {
            return self::$isDevRunning;
        }

        // Cek file marker hot jika ada
        $hotFile = self::getBasePath() . '/public/hot';
        if (file_exists($hotFile)) {
            $hotUrl = trim((string) file_get_contents($hotFile));
            if (!empty($hotUrl)) {
                self::$isDevRunning = true;
                return true;
            }
        }

        $url = parse_url(self::getDevServerUrl());
        $host = $url['host'] ?? '127.0.0.1';
        $port = (int) ($url['port'] ?? 5173);

        // Jika host localhost, gunakan 127.0.0.1 untuk menghindari delay DNS resolve
        if ($host === 'localhost') {
            $host = '127.0.0.1';
        }

        $connection = @fsockopen($host, $port, $errno, $errstr, 0.03);

        if (is_resource($connection)) {
            fclose($connection);
            self::$isDevRunning = true;
            return true;
        }

        self::$isDevRunning = false;
        return false;
    }

    /**
     * Render tag HTML (<link> dan <script>) untuk satu atau beberapa entry aset.
     *
     * @param string|array<int, string> $entries
     * @return string
     */
    public static function render(string|array $entries): string
    {
        $entries = (array) $entries;
        $html = [];

        if (self::isDevServerRunning()) {
            $devUrl = self::getDevServerUrl();

            // Inject Vite client script sekali saja di awal
            if (!self::$clientInjected) {
                $html[] = sprintf('<script type="module" src="%s/@vite/client"></script>', $devUrl);
                self::$clientInjected = true;
            }

            foreach ($entries as $entry) {
                $entry = ltrim($entry, '/');
                // Di Vite Dev Server, seluruh file (termasuk .css) dilayani sebagai ES module
                // agar Vite client script dapat meng-inject style tag dan mengaktifkan WebSocket HMR
                $html[] = sprintf('<script type="module" src="%s/%s"></script>', $devUrl, $entry);
            }

            return implode("\n    ", $html);
        }

        // Mode Production: Baca manifest.json
        $manifest = self::getManifest();

        foreach ($entries as $entry) {
            $entry = ltrim($entry, '/');

            if (!isset($manifest[$entry])) {
                throw new RuntimeException("Vite entry asset [{$entry}] tidak ditemukan pada manifest. Pastikan file terdaftar di vite.config.js dan telah di-build.");
            }

            $item = $manifest[$entry];

            // Render file CSS dependen (jika entry JS meng-import file CSS)
            if (!empty($item['css']) && is_array($item['css'])) {
                foreach ($item['css'] as $cssFile) {
                    $html[] = sprintf('<link rel="stylesheet" href="/build/%s">', ltrim($cssFile, '/'));
                }
            }

            // Render file aset utama
            $buildFile = $item['file'] ?? '';
            if (self::isCssFile($entry) || self::isCssFile($buildFile)) {
                $html[] = sprintf('<link rel="stylesheet" href="/build/%s">', ltrim($buildFile, '/'));
            } else {
                $html[] = sprintf('<script type="module" src="/build/%s"></script>', ltrim($buildFile, '/'));
            }
        }

        return implode("\n    ", array_unique($html));
    }

    /**
     * Dapatkan konten manifest JSON yang sudah diparsing.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getManifest(): array
    {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        $base = self::getBasePath();
        $possiblePaths = [
            $base . '/public/build/.vite/manifest.json', // Vite 5+ default
            $base . '/public/build/manifest.json',       // Vite 4 / custom legacy
        ];

        $manifestPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $manifestPath = $path;
                break;
            }
        }

        if ($manifestPath === null) {
            throw new RuntimeException(
                "File manifest Vite tidak ditemukan di direktori [public/build/]. " .
                "Jalankan 'npm run build' terlebih dahulu untuk menghasilkan aset produksi."
            );
        }

        $content = file_get_contents($manifestPath);
        if ($content === false) {
            throw new RuntimeException("Gagal membaca file manifest Vite pada [{$manifestPath}].");
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new RuntimeException("Format manifest Vite tidak valid pada [{$manifestPath}].");
        }

        self::$manifest = $decoded;
        return self::$manifest;
    }

    /**
     * Cek apakah nama file berakhiran format CSS/SCSS/LESS.
     */
    protected static function isCssFile(string $file): bool
    {
        return (bool) preg_match('/\.(css|scss|sass|less|styl)$/i', $file);
    }

    /**
     * Reset status internal (terutama untuk keperluan unit test).
     */
    public static function reset(): void
    {
        self::$isDevRunning = null;
        self::$clientInjected = false;
        self::$manifest = null;
    }
}
