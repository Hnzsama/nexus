<?php

declare(strict_types=1);

namespace Core\Http;

/**
 * Class Response
 * 
 * Merepresentasikan HTTP Response yang akan dikirim kembali ke client browser.
 * Menyediakan helper praktis untuk JSON, HTML, redirect, serta manipulasi header dan HTTP status.
 */
class Response
{
    /**
     * @param mixed $content Isi body response (string, array, dsb.)
     * @param int $status HTTP status code (default: 200 OK)
     * @param array<string, string> $headers Daftar header HTTP
     */
    public function __construct(
        protected mixed $content = '',
        protected int $status = 200,
        protected array $headers = []
    ) {}

    /**
     * Factory method untuk membuat instance Response baru.
     */
    public static function make(mixed $content = '', int $status = 200, array $headers = []): self
    {
        return new self($content, $status, $headers);
    }

    /**
     * Buat response berformat JSON.
     *
     * @param mixed $data
     * @param int $status
     * @param array<string, string> $headers
     * @return self
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return new self($encoded ?: '{}', $status, $headers);
    }

    /**
     * Buat response berformat HTML.
     */
    public static function html(string $html, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/html; charset=utf-8';
        return new self($html, $status, $headers);
    }

    /**
     * Buat response HTTP Redirect ke URL tertentu.
     */
    public static function redirect(string $url, int $status = 302): self
    {
        return (new self('', $status))->header('Location', $url);
    }

    /**
     * Set HTTP status code.
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Dapatkan HTTP status code.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Alias getStatusCode() untuk kompabilitas PSR standar.
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }

    /**
     * Tambahkan sebuah header HTTP.
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Ambil nilai header HTTP tertentu (case-insensitive).
     */
    public function getHeader(string $name, ?string $default = null): ?string
    {
        $target = strtolower($name);
        foreach ($this->headers as $key => $val) {
            if (strtolower($key) === $target) {
                return $val;
            }
        }
        return $default;
    }

    /**
     * Tambahkan multiple header HTTP sekaligus.
     *
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }
        return $this;
    }

    /**
     * Set isi body konten.
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Dapatkan isi body konten.
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Dapatkan daftar seluruh header HTTP.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Kirim header HTTP dan body konten ke browser client.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        if (is_array($this->content) || is_object($this->content)) {
            echo json_encode($this->content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo (string) $this->content;
        }
    }
}
