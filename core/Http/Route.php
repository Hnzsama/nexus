<?php

declare(strict_types=1);

namespace Core\Http;

use Closure;

/**
 * Class Route
 * 
 * Merepresentasikan satu entitas rute HTTP beserta URI pattern,
 * action handler (Closure atau Controller), serta middleware yang terikat.
 */
class Route
{
    /**
     * @var array<int, string|Closure>
     */
    protected array $middlewares = [];

    protected ?string $name = null;

    /**
     * @param string|array<int, string> $methods
     * @param string $uri
     * @param Closure|array<int, string>|string $action
     */
    public function __construct(
        protected string|array $methods,
        protected string $uri,
        protected mixed $action
    ) {
        $this->methods = array_map('strtoupper', (array) $methods);
        $this->uri = '/' . trim($uri, '/');
    }

    /**
     * Pasang middleware ke rute ini secara fluent.
     *
     * @param string|array<int, string>|Closure $middleware
     * @return $this
     */
    public function middleware(string|array|Closure $middleware): self
    {
        if (is_array($middleware)) {
            $this->middlewares = array_merge($this->middlewares, $middleware);
        } else {
            $this->middlewares[] = $middleware;
        }

        return $this;
    }

    /**
     * Berikan nama unik untuk rute ini (misal: 'home', 'users.show').
     */
    public function name(string $name): self
    {
        $this->name = $name;
        Router::getInstance()->registerNamedRoute($name, $this);
        return $this;
    }

    /**
     * Cek apakah rute cocok dengan method dan path yang diminta.
     * Jika cocok, mengekstrak parameter rute dinamis (contoh: /users/{id}).
     *
     * @param string $method
     * @param string $path
     * @param array<string, string> &$params
     * @return bool
     */
    public function matches(string $method, string $path, array &$params = []): bool
    {
        $params = [];

        // Cek kecocokan HTTP Method
        if (!in_array('ANY', $this->methods, true) && !in_array($method, $this->methods, true)) {
            return false;
        }

        $path = '/' . trim($path, '/');

        // Jika rute eksak (tanpa parameter dinamis)
        if ($this->uri === $path) {
            return true;
        }

        // Ubah format {param} menjadi regex named-capturing group
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $this->uri);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $path, $matches)) {
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = urldecode($value);
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Cek apakah rute cocok dengan path URI saja (tanpa memeriksa method).
     */
    public function matchesUri(string $path): bool
    {
        $path = '/' . trim($path, '/');

        if ($this->uri === $path) {
            return true;
        }

        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '[^/]+', $this->uri);
        return (bool) preg_match('#^' . $pattern . '$#', $path);
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getAction(): mixed
    {
        return $this->action;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
