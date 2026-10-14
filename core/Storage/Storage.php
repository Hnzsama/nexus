<?php

declare(strict_types=1);

namespace Core\Storage;

use Core\Http\UploadedFile;
use RuntimeException;

/**
 * Class Storage
 * 
 * Facade utama sistem penyimpanan berkas (Filesystem Manager) di Nexus Framework.
 * Menyediakan antarmuka statis elegan untuk mengelola berbagai disk penyimpanan berkas.
 */
class Storage
{
    /**
     * Cache instance Disk yang telah diinisialisasi.
     *
     * @var array<string, Disk>
     */
    protected static array $disks = [];

    /**
     * Cache konfigurasi filesystem.
     *
     * @var array<string, mixed>|null
     */
    protected static ?array $config = null;

    /**
     * Dapatkan instance Disk berdasarkan nama disk, atau gunakan default disk jika null.
     */
    public static function disk(?string $name = null): Disk
    {
        $config = static::getConfig();
        $diskName = $name ?: (string) ($config['default'] ?? 'local');

        if (isset(static::$disks[$diskName])) {
            return static::$disks[$diskName];
        }

        $diskConfig = $config['disks'][$diskName] ?? null;
        if (!is_array($diskConfig)) {
            throw new RuntimeException("Storage disk [{$diskName}] tidak terdefinisi di config/filesystems.php.");
        }

        static::$disks[$diskName] = new Disk($diskName, $diskConfig);
        return static::$disks[$diskName];
    }

    /**
     * Buat symbolic link dari 'storage/app/public' ke 'public/storage'.
     *
     * @return bool
     */
    public static function link(): bool
    {
        $target = \storage_path('app/public');
        $link = \public_path('storage');

        if (!is_dir($target)) {
            @mkdir($target, 0755, true);
        }

        if (file_exists($link) || is_link($link)) {
            return true;
        }

        // Pada sistem Linux / WSL / Unix
        return @symlink($target, $link);
    }

    /**
     * Muat file konfigurasi config/filesystems.php.
     *
     * @return array<string, mixed>
     */
    protected static function getConfig(): array
    {
        if (static::$config !== null) {
            return static::$config;
        }

        $configFile = \base_path('config/filesystems.php');
        if (is_file($configFile)) {
            static::$config = require $configFile;
        } else {
            static::$config = [
                'default' => 'local',
                'disks' => [
                    'local'  => ['root' => \storage_path('app/private')],
                    'public' => [
                        'root' => \storage_path('app/public'),
                        'url'  => '/storage',
                    ],
                ],
            ];
        }

        return static::$config;
    }

    /**
     * Forward static method call ke disk default.
     *
     * @param string $method
     * @param array<int, mixed> $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return static::disk()->{$method}(...$arguments);
    }

    // Explicit type-hinted helper methods for optimal IDE auto-completion

    public static function path(string $path = ''): string
    {
        return static::disk()->path($path);
    }

    public static function url(string $path): string
    {
        return static::disk('public')->url($path);
    }

    public static function exists(string $path): bool
    {
        return static::disk()->exists($path);
    }

    public static function missing(string $path): bool
    {
        return static::disk()->missing($path);
    }

    public static function get(string $path): ?string
    {
        return static::disk()->get($path);
    }

    public static function put(string $path, mixed $contents): bool
    {
        return static::disk()->put($path, $contents);
    }

    public static function prepend(string $path, string $data): bool
    {
        return static::disk()->prepend($path, $data);
    }

    public static function append(string $path, string $data): bool
    {
        return static::disk()->append($path, $data);
    }

    public static function delete(string|array $paths): bool
    {
        return static::disk()->delete($paths);
    }

    public static function copy(string $from, string $to): bool
    {
        return static::disk()->copy($from, $to);
    }

    public static function move(string $from, string $to): bool
    {
        return static::disk()->move($from, $to);
    }

    public static function size(string $path): int
    {
        return static::disk()->size($path);
    }

    public static function lastModified(string $path): int
    {
        return static::disk()->lastModified($path);
    }

    public static function mimeType(string $path): ?string
    {
        return static::disk()->mimeType($path);
    }

    /**
     * @return array<int, string>
     */
    public static function files(string $directory = '', bool $recursive = false): array
    {
        return static::disk()->files($directory, $recursive);
    }

    /**
     * @return array<int, string>
     */
    public static function allFiles(string $directory = ''): array
    {
        return static::disk()->allFiles($directory);
    }

    /**
     * @return array<int, string>
     */
    public static function directories(string $directory = '', bool $recursive = false): array
    {
        return static::disk()->directories($directory, $recursive);
    }

    public static function makeDirectory(string $path): bool
    {
        return static::disk()->makeDirectory($path);
    }

    public static function deleteDirectory(string $directory): bool
    {
        return static::disk()->deleteDirectory($directory);
    }

    public static function putFile(string $directory, UploadedFile|array $file, ?string $name = null): string|false
    {
        return static::disk('public')->putFile($directory, $file, $name);
    }

    public static function putFileAs(string $directory, UploadedFile|array $file, string $name): string|false
    {
        return static::disk('public')->putFileAs($directory, $file, $name);
    }
}
