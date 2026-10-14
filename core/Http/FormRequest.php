<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Validation\Validator;
use Core\Validation\ValidationException;
use RuntimeException;

/**
 * Class FormRequest
 * 
 * Base class untuk Request Validation khusus (mirip FormRequest di Laravel).
 * Mewarisi semua fungsionalitas HTTP Request dan menambahkan layer validasi
 * deklaratif serta otorisasi sebelum masuk ke Controller action.
 */
abstract class FormRequest extends Request
{
    /**
     * Instance validator internal.
     */
    protected ?Validator $validator = null;

    /**
     * Data yang telah divalidasi dan lolos.
     *
     * @var array<string, mixed>
     */
    protected array $validatedData = [];

    /**
     * Tentukan apakah pengguna memiliki hak akses (otorisasi) untuk mengeksekusi request ini.
     * Default mengembalikan true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Kumpulan aturan validasi untuk request ini.
     *
     * @return array<string, string|array<int, string>>
     */
    abstract public function rules(): array;

    /**
     * Kumpulan pesan error kustom untuk aturan validasi.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Kumpulan nama label kustom untuk atribut yang lebih ramah dibaca pengguna.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Kustomisasi URL redirect jika validasi gagal.
     * Mengembalikan null akan mengarahkan kembali ke HTTP Referer.
     */
    public function redirectTo(): ?string
    {
        return null;
    }

    /**
     * Buat instance FormRequest baru dari instance Request yang sedang aktif.
     *
     * @param Request $request
     * @return static
     */
    public static function createFrom(Request $request): static
    {
        $instance = new static(
            query: $request->query(),
            post: $request->post(),
            server: $_SERVER,
            cookies: $_COOKIE,
            files: $_FILES,
            rawBody: $request->rawBody()
        );

        $instance->setRouteParams($request->params());

        return $instance;
    }

    /**
     * Eksekusi otorisasi dan validasi request.
     * Otomatis dipanggil oleh Router saat class ini di-type-hint pada method Controller.
     *
     * @throws RuntimeException Jika otorisasi gagal (HTTP 403)
     * @throws ValidationException Jika validasi data gagal (HTTP 422)
     */
    public function validateResolved(): void
    {
        if (!$this->authorize()) {
            throw new RuntimeException("Aksi ini tidak diizinkan untuk akun Anda (403 Unauthorized).", 403);
        }

        $this->validator = new Validator(
            $this->all(),
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );

        if ($this->validator->fails()) {
            throw new ValidationException($this->validator, $this->redirectTo());
        }

        $this->validatedData = $this->validator->validated();
    }

    /**
     * Ambil data yang telah lolos validasi.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function validated(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->validatedData;
        }

        return $this->validatedData[$key] ?? $default;
    }

    /**
     * Dapatkan instance Validator yang digunakan.
     */
    public function validator(): ?Validator
    {
        return $this->validator;
    }
}
