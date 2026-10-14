<?php

declare(strict_types=1);

namespace Core\Http;

use Closure;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;

/**
 * Class Router
 * 
 * Dispatcher rute lightweight dengan identitas sendiri.
 * Mendukung route parameter dinamis, middleware pipeline berantai,
 * route group bersarang, serta akses instance maupun static dispatcher.
 */
class Router
{
    /**
     * Instance router aktif (singleton lightweight).
     */
    protected static ?self $instance = null;

    /**
     * Daftar seluruh rute terdaftar.
     *
     * @var array<int, Route>
     */
    protected array $routes = [];

    /**
     * Daftar rute terdaftar berdasar nama.
     *
     * @var array<string, Route>
     */
    protected array $namedRoutes = [];

    /**
     * Middleware global yang dieksekusi di setiap HTTP request.
     *
     * @var array<int, string|Closure>
     */
    protected array $globalMiddlewares = [];

    /**
     * Stack grup rute aktif (prefix & middlewares).
     *
     * @var array<int, array{prefix: string, middlewares: array<int, string|Closure>}>
     */
    protected array $groupStack = [];

    public function __construct()
    {
        self::$instance = $this;
    }

    /**
     * Dapatkan instance router aktif atau buat baru.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Daftarkan middleware global.
     *
     * @param string|array<int, string|Closure>|Closure $middleware
     */
    public function use(string|array|Closure $middleware): self
    {
        if (is_array($middleware)) {
            $this->globalMiddlewares = array_merge($this->globalMiddlewares, $middleware);
        } else {
            $this->globalMiddlewares[] = $middleware;
        }
        return $this;
    }

    /**
     * Daftarkan rute GET.
     */
    public function addGet(string $uri, mixed $action): Route
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * Daftarkan rute POST.
     */
    public function addPost(string $uri, mixed $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Daftarkan rute PUT.
     */
    public function addPut(string $uri, mixed $action): Route
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Daftarkan rute PATCH.
     */
    public function addPatch(string $uri, mixed $action): Route
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Daftarkan rute DELETE.
     */
    public function addDelete(string $uri, mixed $action): Route
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Daftarkan rute untuk segala method HTTP (ANY).
     */
    public function addAny(string $uri, mixed $action): Route
    {
        return $this->addRoute('ANY', $uri, $action);
    }

    /**
     * Daftarkan grup rute dengan prefix atau middleware bersama.
     *
     * @param array{prefix?: string, middleware?: string|array<int, string|Closure>|Closure} $attributes
     * @param Closure(Router): void $callback
     */
    public function addGroup(array $attributes, Closure $callback): void
    {
        $prefix = isset($attributes['prefix']) ? '/' . trim($attributes['prefix'], '/') : '';
        $middlewares = isset($attributes['middleware']) ? (array) $attributes['middleware'] : [];

        $this->groupStack[] = [
            'prefix' => $prefix,
            'middlewares' => $middlewares,
        ];

        $callback($this);

        array_pop($this->groupStack);
    }

    /**
     * Daftarkan rute ke dalam koleksi internal.
     *
     * @param string|array<int, string> $methods
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function addRoute(string|array $methods, string $uri, mixed $action): Route
    {
        $fullPrefix = '';
        $groupMiddlewares = [];

        foreach ($this->groupStack as $group) {
            $fullPrefix .= $group['prefix'];
            $groupMiddlewares = array_merge($groupMiddlewares, $group['middlewares']);
        }

        $fullUri = $fullPrefix . '/' . trim($uri, '/');
        $route = new Route($methods, $fullUri, $action);

        if (!empty($groupMiddlewares)) {
            $route->middleware($groupMiddlewares);
        }

        $this->routes[] = $route;
        return $route;
    }

    /**
     * Eksekusi pencocokan rute dan alirkan request melalui pipeline middleware.
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $uri = $request->uri();
        $params = [];

        $matchedRoute = null;
        $uriMatchedWithoutMethod = false;

        foreach ($this->routes as $route) {
            if ($route->matches($method, $uri, $params)) {
                $matchedRoute = $route;
                break;
            }

            if ($route->matchesUri($uri)) {
                $uriMatchedWithoutMethod = true;
            }
        }

        // Jika URI cocok namun HTTP method tidak diizinkan -> 405
        if ($matchedRoute === null && $uriMatchedWithoutMethod) {
            return $this->methodNotAllowedResponse($request);
        }

        // Jika tidak ada rute yang cocok sama sekali -> 404
        if ($matchedRoute === null) {
            return $this->notFoundResponse($request);
        }

        $request->setRouteParams($params);

        // Kumpulkan middleware global dan route-specific
        $pipelineMiddlewares = array_merge($this->globalMiddlewares, $matchedRoute->getMiddlewares());

        // Buat pipeline onion execution
        $destination = function (Request $req) use ($matchedRoute) {
            return $this->invokeAction($matchedRoute->getAction(), $req);
        };

        $pipeline = array_reduce(
            array_reverse($pipelineMiddlewares),
            function (Closure $next, mixed $middleware) {
                return function (Request $req) use ($next, $middleware): Response {
                    $instance = $this->resolveMiddleware($middleware);
                    return $instance->handle($req, $next);
                };
            },
            $destination
        );

        return $pipeline($request);
    }

    /**
     * Eksekusi action rute (Closure, [Controller, Method], atau 'Controller@Method').
     */
    protected function invokeAction(mixed $action, Request $request): Response
    {
        $result = null;

        if ($action instanceof Closure) {
            $reflector = new ReflectionFunction($action);
            $args = $this->resolveActionArguments($reflector, $request);
            $result = $action(...$args);
        } elseif (is_array($action) && count($action) === 2) {
            [$class, $method] = $action;
            $controller = is_object($class) ? $class : new $class();
            $reflector = new ReflectionMethod($controller, $method);
            $args = $this->resolveActionArguments($reflector, $request);
            $result = $controller->{$method}(...$args);
        } elseif (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action, 2);
            $fullClass = str_starts_with($class, 'App\\') ? $class : "App\\Handlers\\{$class}";
            if (!class_exists($fullClass)) {
                $fullClass = "App\\Controllers\\{$class}";
            }
            if (!class_exists($fullClass)) {
                throw new RuntimeException("Handler class [{$class}] tidak ditemukan.");
            }
            $controller = new $fullClass();
            $reflector = new ReflectionMethod($controller, $method);
            $args = $this->resolveActionArguments($reflector, $request);
            $result = $controller->{$method}(...$args);
        } else {
            throw new RuntimeException("Format rute action tidak valid.");
        }

        // Normalisasi return value menjadi instance Response
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result) || is_object($result)) {
            return Response::json($result);
        }

        return Response::html((string) $result);
    }

    /**
     * Resolve argumen method atau closure secara dinamis menggunakan Reflection.
     * Otomatis meng-inject FormRequest (dengan validasi langsung), Request standar,
     * serta mencocokkan parameter rute dinamis (misal {id}).
     *
     * @param ReflectionFunctionAbstract $reflector
     * @param Request $request
     * @return array<int, mixed>
     */
    protected function resolveActionArguments(ReflectionFunctionAbstract $reflector, Request $request): array
    {
        $parameters = $reflector->getParameters();
        if (empty($parameters)) {
            return [];
        }

        $args = [];
        $routeParams = $request->params();

        foreach ($parameters as $param) {
            $type = $param->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $className = $type->getName();

                // 1. Cek apakah parameter berupa turunan FormRequest
                if (is_subclass_of($className, FormRequest::class)) {
                    /** @var FormRequest $formRequest */
                    $formRequest = $className::createFrom($request);
                    $formRequest->validateResolved();
                    $args[] = $formRequest;
                    continue;
                }

                // 2. Cek apakah parameter berupa Request standar
                if (is_a($className, Request::class, true)) {
                    $args[] = $request;
                    continue;
                }
            }

            // 3. Cocokkan dengan parameter rute dinamis berdasar nama atau urutan
            $name = $param->getName();
            if (array_key_exists($name, $routeParams)) {
                $args[] = $routeParams[$name];
                unset($routeParams[$name]);
            } elseif (!empty($routeParams)) {
                $args[] = array_shift($routeParams);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $args[] = null;
            }
        }

        return $args;
    }

    /**
     * Resolve instance middleware dari class string atau callable.
     */
    protected function resolveMiddleware(mixed $middleware): MiddlewareInterface
    {
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        if (is_string($middleware) && class_exists($middleware)) {
            $instance = new $middleware();
            if ($instance instanceof MiddlewareInterface) {
                return $instance;
            }
        }

        if ($middleware instanceof Closure) {
            return new class($middleware) implements MiddlewareInterface {
                public function __construct(protected Closure $closure) {}
                public function handle(Request $request, Closure $next): Response
                {
                    return ($this->closure)($request, $next);
                }
            };
        }

        throw new RuntimeException("Middleware [{$middleware}] tidak valid atau tidak mengimplementasikan MiddlewareInterface.");
    }

    /**
     * Tampilan error 404 default berdesain modern dark-mode.
     */
    protected function notFoundResponse(Request $request): Response
    {
        if ($request->wantsJson()) {
            return Response::json([
                'error' => 'Not Found',
                'message' => "Rute [{$request->method()} {$request->uri()}] tidak ditemukan.",
                'code' => 404,
            ], 404);
        }

        return Response::html(
            '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>404 Not Found</title>' .
            '<style>' .
            'body{margin:0;font-family:Inter,system-ui,sans-serif;background:#090d16;color:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;}' .
            '.card{background:#111827;border:1px solid #1f293d;padding:2.5rem 3rem;border-radius:16px;text-align:center;max-width:480px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.5);}' .
            'h1{font-size:3.5rem;margin:0 0 0.5rem;background:linear-gradient(135deg,#6366f1,#06b6d4);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;}' .
            'h2{font-size:1.25rem;margin:0 0 1rem;font-weight:600;color:#e2e8f0;}' .
            'p{color:#94a3b8;margin:0 0 1.5rem;font-size:0.95rem;line-height:1.5;}' .
            'a{display:inline-block;padding:0.75rem 1.5rem;background:#6366f1;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;transition:opacity 0.2s;}' .
            'a:hover{opacity:0.9;}' .
            '</style></head><body>' .
            '<div class="card"><h1>404</h1><h2>Halaman Tidak Ditemukan</h2>' .
            '<p>Alamat rute <code>' . htmlspecialchars($request->uri(), ENT_QUOTES, 'UTF-8') . '</code> tidak terdaftar pada aplikasi ini.</p>' .
            '<a href="/">Kembali ke Beranda</a></div></body></html>',
            404
        );
    }

    /**
     * Tampilan error 405 Method Not Allowed.
     */
    protected function methodNotAllowedResponse(Request $request): Response
    {
        if ($request->wantsJson()) {
            return Response::json([
                'error' => 'Method Not Allowed',
                'message' => "Metode HTTP [{$request->method()}] tidak didukung untuk rute ini.",
                'code' => 405,
            ], 405);
        }

        return Response::html(
            '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>405 Method Not Allowed</title>' .
            '<style>' .
            'body{margin:0;font-family:Inter,system-ui,sans-serif;background:#090d16;color:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;}' .
            '.card{background:#111827;border:1px solid #1f293d;padding:2.5rem;border-radius:16px;text-align:center;max-width:480px;}' .
            'h1{font-size:3rem;margin:0 0 0.5rem;color:#f59e0b;}' .
            'p{color:#94a3b8;margin-bottom:1.5rem;}' .
            '</style></head><body>' .
            '<div class="card"><h1>405</h1><h2>Method Not Allowed</h2><p>Metode <b>' . htmlspecialchars($request->method(), ENT_QUOTES, 'UTF-8') . '</b> tidak diizinkan pada URL ini.</p></div></body></html>',
            405
        );
    }

    /* -------------------------------------------------------------
     * Static Dispatcher Shortcuts (DX yang elegan & ringkas)
     * ------------------------------------------------------------- */

    public static function get(string $uri, mixed $action): Route
    {
        return self::getInstance()->addGet($uri, $action);
    }

    public static function post(string $uri, mixed $action): Route
    {
        return self::getInstance()->addPost($uri, $action);
    }

    public static function put(string $uri, mixed $action): Route
    {
        return self::getInstance()->addPut($uri, $action);
    }

    public static function patch(string $uri, mixed $action): Route
    {
        return self::getInstance()->addPatch($uri, $action);
    }

    public static function delete(string $uri, mixed $action): Route
    {
        return self::getInstance()->addDelete($uri, $action);
    }

    public static function any(string $uri, mixed $action): Route
    {
        return self::getInstance()->addAny($uri, $action);
    }

    public static function group(array $attributes, Closure $callback): void
    {
        self::getInstance()->addGroup($attributes, $callback);
    }

    /**
     * Daftarkan route berdasarkan nama unik.
     */
    public function registerNamedRoute(string $name, Route $route): self
    {
        $this->namedRoutes[$name] = $route;
        return $this;
    }

    /**
     * Cari objek Route terdaftar berdasar namanya.
     */
    public function getNamedRoute(string $name): ?Route
    {
        if (isset($this->namedRoutes[$name])) {
            return $this->namedRoutes[$name];
        }

        // Fallback scan jika rute diset namanya sebelum instance terhubung
        foreach ($this->routes as $route) {
            if ($route->getName() === $name) {
                $this->namedRoutes[$name] = $route;
                return $route;
            }
        }

        return null;
    }

    /**
     * Generate URL dari nama rute beserta parameternya (seperti route() di Laravel).
     *
     * @param string $name Nama rute (misal: 'users.show', 'users.store')
     * @param array<string, mixed>|mixed $parameters Parameter untuk rute dinamis (misal: ['id' => 1] atau 1)
     * @return string URL relatif/absolut yang valid
     * @throws RuntimeException Jika nama rute tidak ditemukan atau parameter kurang
     */
    public function url(string $name, mixed $parameters = []): string
    {
        $route = $this->getNamedRoute($name);

        if ($route === null) {
            throw new RuntimeException("Rute dengan nama [{$name}] tidak ditemukan.");
        }

        $uri = $route->getUri();

        // Normalisasi $parameters jika diberikan nilai skalar langsung (misal: route('users.delete', $user->id))
        if (!is_array($parameters)) {
            $parameters = [$parameters];
        }

        // Cocokkan placeholder {param}
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $uri, $matches);
        $paramNames = $matches[1] ?? [];

        foreach ($paramNames as $index => $paramName) {
            $value = null;
            if (array_key_exists($paramName, $parameters)) {
                $value = $parameters[$paramName];
                unset($parameters[$paramName]);
            } elseif (array_key_exists($index, $parameters)) {
                $value = $parameters[$index];
                unset($parameters[$index]);
            }

            if ($value === null) {
                throw new RuntimeException("Parameter [{$paramName}] wajib diisi untuk rute [{$name}].");
            }

            $uri = str_replace('{' . $paramName . '}', urlencode((string) $value), $uri);
        }

        // Parameter tersisa yang bertipe string key dijadikan query string (contoh: ?tab=active)
        $query = [];
        foreach ($parameters as $k => $v) {
            if (is_string($k)) {
                $query[$k] = $v;
            }
        }
        if (!empty($query)) {
            $uri .= '?' . http_build_query($query);
        }

        return $uri;
    }

    /**
     * Shortcut static untuk mendapatkan URL rute bernama.
     *
     * @param string $name
     * @param mixed $parameters
     * @return string
     */
    public static function routeUrl(string $name, mixed $parameters = []): string
    {
        return self::getInstance()->url($name, $parameters);
    }

    /**
     * Dapatkan seluruh rute terdaftar.
     *
     * @return array<int, Route>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
