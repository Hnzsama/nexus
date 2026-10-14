<?php

declare(strict_types=1);

namespace Core\Validation;

use Core\Http\Request;
use Core\Http\Response;
use Exception;

/**
 * Class ValidationException
 * 
 * Exception yang dilempar saat validasi data form atau request gagal.
 * Otomatis menangani format response HTTP 422 JSON untuk permintaan API/AJAX,
 * atau redirect kembali ke halaman formulir sebelumnya dengan pesan error dan input lama (old input) tersimpan di session.
 */
class ValidationException extends Exception
{
    /**
     * @param Validator $validator Instance validator yang memicu exception
     * @param string|null $redirectTo URL tujuan redirect khusus jika tidak ingin ke referer
     */
    public function __construct(
        protected Validator $validator,
        protected ?string $redirectTo = null
    ) {
        $firstMessage = $validator->first() ?? 'Validasi input data gagal.';
        parent::__construct($firstMessage, 422);
    }

    /**
     * Dapatkan instance Validator.
     */
    public function validator(): Validator
    {
        return $this->validator;
    }

    /**
     * Dapatkan array pesan error.
     *
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->validator->errors();
    }

    /**
     * Bentuk Response HTTP yang sesuai dengan tipe request yang masuk.
     *
     * @param Request|null $request
     * @return Response
     */
    public function getResponse(?Request $request = null): Response
    {
        $request ??= Request::capture();

        // 1. Jika request berupa API murni yang meminta JSON atau mengirim body JSON (dan bukan navigasi Turbo SPA)
        $isSpa = (bool) $request->header('x-nexus-spa');
        if (!$isSpa && ($request->wantsJson() || $request->isJson())) {
            return Response::json([
                'status' => 'error',
                'message' => 'Data formulir yang diberikan tidak valid.',
                'errors' => $this->errors(),
            ], 422);
        }

        // 2. Jika request web standar atau Turbo SPA -> Simpan errors & old input ke session lalu redirect back
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        if (isset($_SESSION)) {
            $_SESSION['_errors'] = $this->errors();

            // Salin semua input lama kecuali data sensitif (password) dan token CSRF
            $oldInput = $request->input();
            unset($oldInput['password'], $oldInput['password_confirmation'], $oldInput['_token']);
            $_SESSION['_old'] = $oldInput;

            // Pastikan session tersimpan ke disk sebelum header redirect dikirim
            session_write_close();
        }

        $targetUrl = $this->redirectTo ?? $request->header('referer') ?: $request->uri() ?: '/';

        return Response::redirect($targetUrl);
    }
}
