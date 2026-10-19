<?php

declare(strict_types=1);

namespace Core\Http;

/**
 * Class Request
 * 
 * Merepresentasikan HTTP Request yang masuk ke aplikasi.
 * Menyediakan abstraksi bersih terhadap superglobals ($_SERVER, $_GET, $_POST, dll.)
 * serta mendukung payload JSON dan spoofing method HTTP.
 */
class Request
{
    /**
     * Parameter rute dinamis (misal: /users/{id}).
     *
     * @var array<string, string>
     */
    protected array $routeParams = [];

    /**
     * Payload JSON yang telah didecode jika request berformat application/json.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $jsonPayload = null;

    public function __construct(
        protected array $query = [],
        protected array $post = [],
        protected array $server = [],
        protected array $cookies = [],
        protected array $files = [],
        protected ?string $rawBody = null
    ) {
        $this->parseJsonBody();
    }

    /**
     * Tangkap request saat ini dari superglobals PHP.
     */
    public static function capture(): self
    {
        return new self(
            query: $_GET,
            post: $_POST,
            server: $_SERVER,
            cookies: $_COOKIE,
            files: $_FILES,
            rawBody: file_get_contents('php://input') ?: null
        );
    }

    /**
     * Dapatkan HTTP Method (GET, POST, PUT, DELETE, dll.).
     * Mendukung method spoofing via input hidden '_method' atau header 'X-HTTP-Method-Override'.
     */
    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST') {
            $override = $this->post['_method']
                ?? $this->jsonPayload['_method']
                ?? $this->header('X-HTTP-Method-Override');

            if ($override && in_array(strtoupper((string) $override), ['PUT', 'PATCH', 'DELETE', 'OPTIONS'], true)) {
                return strtoupper((string) $override);
            }
        }

        return $method;
    }

    /**
     * Dapatkan path URI yang dinormalisasi tanpa query string.
     */
    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        return '/' . trim($path, '/');
    }

    /**
     * Alias untuk uri().
     */
    public function path(): string
    {
        return $this->uri();
    }

    /**
     * Dapatkan nilai dari query string ($_GET).
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    /**
     * Dapatkan nilai dari POST body ($_POST).
     */
    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    /**
     * Dapatkan input serbaguna (menggabungkan Route params, JSON body, POST, dan GET).
     */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        $all = $this->all();

        if ($key === null) {
            return $all;
        }

        return $all[$key] ?? $default;
    }

    /**
     * Gabungkan semua sumber data request menjadi satu array asosiatif.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge(
            $this->query,
            $this->post,
            $this->jsonPayload ?? [],
            $this->routeParams
        );
    }

    /**
     * Periksa apakah input tertentu ada dalam request.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * Set parameter rute dinamis yang diekstrak oleh Router.
     *
     * @param array<string, string> $params
     */
    public function setRouteParams(array $params): self
    {
        $this->routeParams = $params;
        return $this;
    }

    /**
     * Dapatkan nilai parameter rute.
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * Dapatkan seluruh parameter rute.
     *
     * @return array<string, string>
     */
    public function params(): array
    {
        return $this->routeParams;
    }

    /**
     * Dapatkan file yang di-upload dari $_FILES sebagai instance UploadedFile.
     */
    public function file(string $key): ?UploadedFile
    {
        $file = $this->files[$key] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return new UploadedFile($file);
    }

    /**
     * Dapatkan seluruh file yang di-upload sebagai array instance UploadedFile.
     *
     * @return array<string, UploadedFile>
     */
    public function allFiles(): array
    {
        $uploaded = [];
        foreach ($this->files as $key => $file) {
            if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $uploaded[$key] = new UploadedFile($file);
            }
        }
        return $uploaded;
    }

    /**
     * Cek apakah ada file yang di-upload untuk key tertentu.
     */
    public function hasFile(string $key): bool
    {
        return $this->file($key) !== null;
    }

    /**
     * Dapatkan header HTTP tertentu (case-insensitive).
     */
    public function header(string $key, ?string $default = null): ?string
    {
        $headers = $this->headers();
        $normalizedKey = strtolower($key);

        return $headers[$normalizedKey] ?? $default;
    }

    /**
     * Dapatkan seluruh header HTTP ternormalisasi (lowercase keys).
     *
     * @return array<string, string>
     */
    public function headers(): array
    {
        $headers = [];

        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string) $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $name = strtolower(str_replace('_', '-', $key));
                $headers[$name] = (string) $value;
            }
        }

        return $headers;
    }

    /**
     * Dapatkan Authorization Bearer Token jika ada di header.
     */
    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization');
        if ($auth && preg_match('/Bearer\s+(\S+)/i', $auth, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Cek apakah request mengirim body dengan Content-Type JSON.
     */
    public function isJson(): bool
    {
        $contentType = $this->header('content-type') ?? '';
        return str_contains(strtolower($contentType), 'application/json');
    }

    /**
     * Cek apakah client mengharapkan response JSON (Accept: application/json).
     */
    public function wantsJson(): bool
    {
        $accept = $this->header('accept') ?? '';
        return str_contains(strtolower($accept), 'application/json');
    }

    /**
     * Cek apakah request dikirim via AJAX (X-Requested-With).
     */
    public function isAjax(): bool
    {
        return strtolower($this->header('x-requested-with') ?? '') === 'xmlhttprequest';
    }

    /**
     * Dapatkan IP address client.
     */
    public function ip(): string
    {
        return $this->server['HTTP_X_FORWARDED_FOR']
            ?? $this->server['HTTP_CLIENT_IP']
            ?? $this->server['REMOTE_ADDR']
            ?? '127.0.0.1';
    }

    /**
     * Dapatkan raw body payload.
     */
    public function rawBody(): ?string
    {
        return $this->rawBody;
    }

    /**
     * Data yang telah divalidasi via validate().
     *
     * @var array<string, mixed>
     */
    protected array $validated = [];

    /**
     * Validasi input request saat ini berdasar kumpulan aturan.
     *
     * @param array<string, string|array<int, string>> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @return array<string, mixed> Data yang tervalidasi
     * @throws \Core\Validation\ValidationException
     */
    public function validate(array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = new \Core\Validation\Validator($this->all(), $rules, $messages, $attributes);

        if ($validator->fails()) {
            throw new \Core\Validation\ValidationException($validator);
        }

        $this->validated = $validator->validated();
        return $this->validated;
    }

    /**
     * Dapatkan data hasil validasi terakhir.
     */
    public function validated(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->validated;
        }

        return $this->validated[$key] ?? $default;
    }

    protected ?object $resolvedUser = null;

    /**
     * Dapatkan user yang sedang login dari session / token jika ada.
     */
    public function user(): ?object
    {
        if ($this->resolvedUser !== null) {
            return $this->resolvedUser;
        }

        // 1. Cek $_SESSION['user']
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $userData = $_SESSION['user'] ?? null;
        if ($userData) {
            $this->resolvedUser = is_object($userData) ? $userData : (object) $userData;
            return $this->resolvedUser;
        }

        // 2. Cek $_SESSION['user_id']
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId && class_exists(\App\Models\User::class)) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $this->resolvedUser = $user;
                return $this->resolvedUser;
            }
        }

        // 3. Cek Authorization Bearer Token
        $token = $this->bearerToken();
        if ($token && class_exists(\App\Models\User::class)) {
            $user = \App\Models\User::where('api_token', $token)->first();
            if ($user) {
                $this->resolvedUser = $user;
                return $this->resolvedUser;
            }
        }

        return null;
    }

    /**
     * Parsing isi JSON body jika ada.
     */
    protected function parseJsonBody(): void
    {
        if ($this->rawBody && $this->isJson()) {
            $decoded = json_decode($this->rawBody, true);
            if (is_array($decoded)) {
                $this->jsonPayload = $decoded;
            }
        }
    }
}

