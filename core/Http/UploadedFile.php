<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Storage\Storage;

/**
 * Class UploadedFile
 * 
 * Objek pembungkus untuk file upload dari superglobal $_FILES.
 * Menyediakan metode inspeksi berkas, validasi keamanan, serta penyimpanan langsung ke Storage disk.
 */
class UploadedFile
{
    protected string $originalName;
    protected string $mimeType;
    protected string $tmpName;
    protected int $error;
    protected int $size;

    /**
     * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file
     */
    public function __construct(array $file)
    {
        $this->originalName = (string) ($file['name'] ?? '');
        $this->mimeType = (string) ($file['type'] ?? 'application/octet-stream');
        $this->tmpName = (string) ($file['tmp_name'] ?? '');
        $this->error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $this->size = (int) ($file['size'] ?? 0);
    }

    /**
     * Dapatkan nama asli berkas saat di-upload oleh klien.
     */
    public function getClientOriginalName(): string
    {
        return $this->originalName;
    }

    /**
     * Dapatkan ekstensi asli dari berkas.
     */
    public function getClientOriginalExtension(): string
    {
        return strtolower(pathinfo($this->originalName, PATHINFO_EXTENSION));
    }

    /**
     * Dapatkan ekstensi berkas yang direkomendasikan.
     */
    public function extension(): string
    {
        return $this->getClientOriginalExtension();
    }

    /**
     * Dapatkan MIME type yang dikirimkan oleh klien.
     */
    public function getClientMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Dapatkan ukuran berkas dalam satuan byte.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Dapatkan kode error upload PHP.
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Periksa apakah berkas berhasil di-upload tanpa error.
     */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && is_readable($this->tmpName);
    }

    /**
     * Dapatkan path lokal sementara berkas (tmp_name).
     */
    public function getRealPath(): string
    {
        return $this->tmpName;
    }

    /**
     * Buat nama hash acak yang unik untuk mencegah collision saat disimpan.
     */
    public function hashName(?string $extension = null): string
    {
        $ext = $extension !== null ? $extension : $this->extension();
        $random = bin2hex(random_bytes(16));
        return $ext !== '' ? "{$random}.{$ext}" : $random;
    }

    /**
     * Simpan berkas ke direktori Storage disk yang ditentukan (default: 'public').
     *
     * @param string $directory Direktori tujuan (misal: 'avatars')
     * @param string $disk Nama disk di config/filesystems.php (default: 'public')
     * @return string|false Path relatif berkas yang tersimpan, atau false jika gagal
     */
    public function store(string $directory = '', string $disk = 'public'): string|false
    {
        return Storage::disk($disk)->putFile($directory, $this);
    }

    /**
     * Simpan berkas dengan nama khusus ke Storage disk yang ditentukan.
     */
    public function storeAs(string $directory, string $name, string $disk = 'public'): string|false
    {
        return Storage::disk($disk)->putFileAs($directory, $this, $name);
    }

    /**
     * Konversi data berkas ke array standar.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'      => $this->originalName,
            'type'      => $this->mimeType,
            'tmp_name'  => $this->tmpName,
            'error'     => $this->error,
            'size'      => $this->size,
            'extension' => $this->extension(),
            'valid'     => $this->isValid(),
        ];
    }
}
