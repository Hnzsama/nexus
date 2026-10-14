<?php

declare(strict_types=1);

namespace Core\Storage;

use Core\Http\UploadedFile;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Class Disk
 * 
 * Merepresentasikan sebuah storage disk (misal: 'local' privat atau 'public')
 * dengan direktori root terisolasi dan helper manipulasi file/folder.
 */
class Disk
{
    protected string $root;
    protected string $baseUrl;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        protected string $name,
        protected array $config
    ) {
        $this->root = rtrim((string) ($config['root'] ?? \storage_path('app')), '/\\');
        $this->baseUrl = rtrim((string) ($config['url'] ?? ''), '/\\');

        if (!is_dir($this->root)) {
            @mkdir($this->root, 0755, true);
        }
    }

    /**
     * Dapatkan path absolut dari berkas atau direktori di dalam disk.
     */
    public function path(string $path = ''): string
    {
        $normalized = ltrim($path, '/\\');
        return $normalized !== '' ? $this->root . DIRECTORY_SEPARATOR . $normalized : $this->root;
    }

    /**
     * Dapatkan URL publik untuk mengakses berkas (khusus disk bertipe publik).
     */
    public function url(string $path): string
    {
        if (empty($this->baseUrl)) {
            return '/storage/' . ltrim($path, '/');
        }

        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * Periksa apakah berkas ada di dalam disk.
     */
    public function exists(string $path): bool
    {
        return file_exists($this->path($path));
    }

    /**
     * Periksa apakah berkas tidak ada di dalam disk.
     */
    public function missing(string $path): bool
    {
        return !$this->exists($path);
    }

    /**
     * Baca seluruh isi konten dari berkas.
     */
    public function get(string $path): ?string
    {
        $fullPath = $this->path($path);
        if (!is_file($fullPath) || !is_readable($fullPath)) {
            return null;
        }

        $content = file_get_contents($fullPath);
        return $content !== false ? $content : null;
    }

    /**
     * Tulis konten ke dalam berkas. Otomatis membuat subdirektori jika belum ada.
     */
    public function put(string $path, mixed $contents): bool
    {
        $fullPath = $this->path($path);
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        if (is_resource($contents)) {
            return (bool) file_put_contents($fullPath, stream_get_contents($contents));
        }

        return file_put_contents($fullPath, (string) $contents) !== false;
    }

    /**
     * Tambahkan konten ke bagian awal berkas.
     */
    public function prepend(string $path, string $data): bool
    {
        if ($this->exists($path)) {
            return $this->put($path, $data . $this->get($path));
        }

        return $this->put($path, $data);
    }

    /**
     * Tambahkan konten ke bagian akhir berkas (append).
     */
    public function append(string $path, string $data): bool
    {
        $fullPath = $this->path($path);
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        return file_put_contents($fullPath, $data, FILE_APPEND) !== false;
    }

    /**
     * Hapus satu atau beberapa berkas.
     *
     * @param string|array<int, string> $paths
     */
    public function delete(string|array $paths): bool
    {
        $paths = is_array($paths) ? $paths : [$paths];
        $success = true;

        foreach ($paths as $path) {
            $fullPath = $this->path($path);
            if (is_file($fullPath)) {
                if (!@unlink($fullPath)) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * Salin berkas ke lokasi baru di dalam disk yang sama.
     */
    public function copy(string $from, string $to): bool
    {
        $fromPath = $this->path($from);
        $toPath = $this->path($to);

        if (!is_file($fromPath)) {
            return false;
        }

        $toDir = dirname($toPath);
        if (!is_dir($toDir)) {
            @mkdir($toDir, 0755, true);
        }

        return copy($fromPath, $toPath);
    }

    /**
     * Pindahkan atau ubah nama berkas ke lokasi baru di dalam disk yang sama.
     */
    public function move(string $from, string $to): bool
    {
        $fromPath = $this->path($from);
        $toPath = $this->path($to);

        if (!is_file($fromPath)) {
            return false;
        }

        $toDir = dirname($toPath);
        if (!is_dir($toDir)) {
            @mkdir($toDir, 0755, true);
        }

        return rename($fromPath, $toPath);
    }

    /**
     * Dapatkan ukuran berkas dalam byte.
     */
    public function size(string $path): int
    {
        $fullPath = $this->path($path);
        return is_file($fullPath) ? (filesize($fullPath) ?: 0) : 0;
    }

    /**
     * Dapatkan timestamp waktu modifikasi terakhir berkas.
     */
    public function lastModified(string $path): int
    {
        $fullPath = $this->path($path);
        return is_file($fullPath) ? (filemtime($fullPath) ?: 0) : 0;
    }

    /**
     * Dapatkan tipe MIME dari berkas.
     */
    public function mimeType(string $path): ?string
    {
        $fullPath = $this->path($path);
        if (!is_file($fullPath)) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) {
            return null;
        }

        $mime = finfo_file($finfo, $fullPath);
        finfo_close($finfo);

        return $mime !== false ? $mime : null;
    }

    /**
     * Ambil daftar file dalam direktori (non-rekursif atau rekursif).
     *
     * @return array<int, string>
     */
    public function files(string $directory = '', bool $recursive = false): array
    {
        $dirPath = $this->path($directory);
        if (!is_dir($dirPath)) {
            return [];
        }

        $files = [];

        if (!$recursive) {
            $iterator = new FilesystemIterator($dirPath, FilesystemIterator::SKIP_DOTS);
            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $files[] = ltrim(substr($item->getPathname(), strlen($this->root)), '/\\');
                }
            }
        } else {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $files[] = ltrim(substr($item->getPathname(), strlen($this->root)), '/\\');
                }
            }
        }

        return str_replace('\\', '/', $files);
    }

    /**
     * Ambil semua berkas dalam direktori dan seluruh subdirektorinya secara rekursif.
     *
     * @return array<int, string>
     */
    public function allFiles(string $directory = ''): array
    {
        return $this->files($directory, true);
    }

    /**
     * Ambil daftar subdirektori di dalam direktori.
     *
     * @return array<int, string>
     */
    public function directories(string $directory = '', bool $recursive = false): array
    {
        $dirPath = $this->path($directory);
        if (!is_dir($dirPath)) {
            return [];
        }

        $dirs = [];

        if (!$recursive) {
            $iterator = new FilesystemIterator($dirPath, FilesystemIterator::SKIP_DOTS);
            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    $dirs[] = ltrim(substr($item->getPathname(), strlen($this->root)), '/\\');
                }
            }
        } else {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    $dirs[] = ltrim(substr($item->getPathname(), strlen($this->root)), '/\\');
                }
            }
        }

        return str_replace('\\', '/', $dirs);
    }

    /**
     * Buat direktori baru beserta parent direktorinya.
     */
    public function makeDirectory(string $path): bool
    {
        $fullPath = $this->path($path);
        if (is_dir($fullPath)) {
            return true;
        }

        return @mkdir($fullPath, 0755, true);
    }

    /**
     * Hapus direktori beserta seluruh isinya secara rekursif.
     */
    public function deleteDirectory(string $directory): bool
    {
        $dirPath = $this->path($directory);
        if (!is_dir($dirPath)) {
            return false;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        return @rmdir($dirPath);
    }

    /**
     * Simpan uploaded file ke dalam direktori disk dengan nama acak unik atau nama kustom.
     *
     * @param string $directory Subdirektori tujuan (misal: 'avatars', 'documents')
     * @param UploadedFile|array<string, mixed> $file
     * @param string|null $name Nama kustom berkas (opsional)
     * @return string|false Path relatif berkas yang tersimpan, atau false jika gagal
     */
    public function putFile(string $directory, UploadedFile|array $file, ?string $name = null): string|false
    {
        if (is_array($file)) {
            $file = new UploadedFile($file);
        }

        if (!$file->isValid()) {
            return false;
        }

        $targetName = $name ?: $file->hashName();
        $targetPath = trim($directory, '/') !== '' ? trim($directory, '/') . '/' . $targetName : $targetName;
        $destination = $this->path($targetPath);

        $destDir = dirname($destination);
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        if (is_uploaded_file($file->getRealPath())) {
            if (move_uploaded_file($file->getRealPath(), $destination)) {
                return $targetPath;
            }
        } elseif (copy($file->getRealPath(), $destination)) {
            return $targetPath;
        }

        return false;
    }

    /**
     * Simpan uploaded file dengan nama file spesifik.
     */
    public function putFileAs(string $directory, UploadedFile|array $file, string $name): string|false
    {
        return $this->putFile($directory, $file, $name);
    }
}
