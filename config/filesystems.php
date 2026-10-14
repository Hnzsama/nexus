<?php

declare(strict_types=1);

/**
 * Nexus Framework - Filesystem Configuration
 * 
 * Konfigurasi disk penyimpanan berkas aplikasi (local, public, upload, dll.).
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    | Disk default yang digunakan saat memanggil helper Storage tanpa argumen disk.
    */
    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    | Konfigurasi driver dan direktori root untuk setiap disk.
    */
    'disks' => [
        // Disk privat untuk data internal aplikasi (tidak dapat diakses publik via URL)
        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app/private'),
            'throw'  => false,
        ],

        // Disk publik untuk file upload (avatar, lampiran) yang dapat diakses publik via symlink
        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL', 'http://127.0.0.1:8000') . '/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],
    ],
];
