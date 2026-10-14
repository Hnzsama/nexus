<?php

declare(strict_types=1);

namespace Core\Http;

use RuntimeException;

/**
 * Class Controller
 * 
 * Base Controller Core framework.
 * Menyediakan metode bantu untuk render view, response JSON, redirect,
 * serta validasi input HTTP secara terpusat.
 */
abstract class Controller
{
    /**
     * Render template view menjadi instance Response.
     *
     * @param string $template
     * @param array<string, mixed> $data
     * @param int $status
     * @return Response
     */
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        return view($template, $data, $status);
    }

    /**
     * Buat response berformat JSON.
     *
     * @param mixed $data
     * @param int $status
     * @param array<string, string> $headers
     * @return Response
     */
    protected function json(mixed $data, int $status = 200, array $headers = []): Response
    {
        return Response::json($data, $status, $headers);
    }

    /**
     * Buat response HTTP Redirect.
     *
     * @param string $url
     * @param int $status
     * @return Response
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }

    /**
     * Validasi input request berdasar rules menggunakan Core Validator.
     *
     * @param Request $request
     * @param array<string, string|array<int, string>> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @return array<string, mixed> Data yang tervalidasi
     * @throws \Core\Validation\ValidationException Jika validasi gagal
     */
    protected function validate(
        Request $request,
        array $rules,
        array $messages = [],
        array $attributes = []
    ): array {
        return $request->validate($rules, $messages, $attributes);
    }
}
