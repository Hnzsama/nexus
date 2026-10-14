<?php

declare(strict_types=1);

namespace Core;

use Closure;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Router;
use RuntimeException;
use Throwable;

/**
 * Class App
 * 
 * Jantung aplikasi Nexus Framework.
 * Berperan sebagai IoC Container lightweight, orchestrator lifecycle aplikasi,
 * serta penengah antara Request, Router, dan Response.
 */
class App
{
    protected static ?self $instance = null;

    /**
     * @var array<string, mixed>
     */
    protected array $bindings = [];

    /**
     * @var array<string, mixed>
     */
    protected array $instances = [];

    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        self::$instance = $this;

        $this->registerCoreServices();
    }

    /**
     * Dapatkan instance global App.
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException("App belum diinisialisasi.");
        }
        return self::$instance;
    }

    /**
     * Dapatkan path root direktori aplikasi.
     */
    public function basePath(string $path = ''): string
    {
        return $path !== '' ? $this->basePath . '/' . ltrim($path, '/\\') : $this->basePath;
    }

    /**
     * Daftarkan binding transient ke container.
     */
    public function bind(string $key, mixed $concrete): self
    {
        $this->bindings[$key] = $concrete;
        unset($this->instances[$key]);
        return $this;
    }

    /**
     * Daftarkan singleton service ke container.
     */
    public function singleton(string $key, mixed $concrete): self
    {
        $this->bindings[$key] = $concrete;
        return $this;
    }

    /**
     * Resolve service dari container.
     */
    public function make(string $key): mixed
    {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        if (!isset($this->bindings[$key])) {
            if (class_exists($key)) {
                return new $key();
            }
            throw new RuntimeException("Service [{$key}] tidak terdaftar di App Container.");
        }

        $concrete = $this->bindings[$key];

        if ($concrete instanceof Closure) {
            $object = $concrete($this);
            $this->instances[$key] = $object;
            return $object;
        }

        if (is_object($concrete)) {
            $this->instances[$key] = $concrete;
            return $concrete;
        }

        if (is_string($concrete) && class_exists($concrete)) {
            $object = new $concrete();
            $this->instances[$key] = $object;
            return $object;
        }

        return $concrete;
    }

    /**
     * Registrasi service bawaan framework.
     */
    protected function registerCoreServices(): void
    {
        $this->singleton('router', fn() => Router::getInstance());
        $this->singleton('request', fn() => Request::capture());
    }

    /**
     * Tangani request yang masuk dan kembalikan Response.
     */
    public function handle(Request $request): Response
    {
        try {
            /** @var Router $router */
            $router = $this->make('router');
            return $router->dispatch($request);
        } catch (\Core\Validation\ValidationException $e) {
            return $e->getResponse($request);
        } catch (Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    /**
     * Tangani uncaught exception dengan tampilan error elegan.
     */
    protected function handleException(Throwable $e, Request $request): Response
    {
        $isDebug = (bool) env('APP_DEBUG', true);

        if ($request->wantsJson() || $request->isJson()) {
            return Response::json([
                'error' => $isDebug ? $e->getMessage() : 'Internal Server Error',
                'exception' => $isDebug ? get_class($e) : null,
                'file' => $isDebug ? $e->getFile() : null,
                'line' => $isDebug ? $e->getLine() : null,
                'trace' => $isDebug ? explode("\n", $e->getTraceAsString()) : null,
            ], 500);
        }

        $errorHtml = $isDebug
            ? $this->renderDebugErrorPage($e)
            : '<!DOCTYPE html><html><body style="background:#090d16;color:#f8fafc;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;">' .
              '<div style="text-align:center;"><h1>500 | Server Error</h1><p>Terjadi kesalahan internal pada server.</p></div></body></html>';

        return Response::html($errorHtml, 500);
    }

    /**
     * Render halaman detail error untuk mode debug lokal.
     */
    protected function renderDebugErrorPage(Throwable $e): string
    {
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $file = htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8');
        $line = $e->getLine();
        $class = htmlspecialchars(get_class($e), ENT_QUOTES, 'UTF-8');
        $trace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exception: {$message}</title>
    <style>
        body { margin: 0; background: #090d16; color: #f8fafc; font-family: 'JetBrains Mono', Consolas, monospace; padding: 2rem; }
        .container { max-width: 1000px; margin: 0 auto; background: #111827; border: 1px solid #1f293d; border-radius: 12px; padding: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        .badge { display: inline-block; background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.8rem; font-weight: bold; margin-bottom: 1rem; }
        h1 { color: #f87171; font-size: 1.5rem; margin: 0 0 1rem; word-break: break-word; }
        .location { background: #060911; padding: 1rem; border-radius: 8px; border: 1px solid #1f293d; color: #38bdf8; font-size: 0.9rem; margin-bottom: 1.5rem; }
        .trace { background: #060911; padding: 1.25rem; border-radius: 8px; border: 1px solid #1f293d; overflow-x: auto; color: #94a3b8; font-size: 0.85rem; line-height: 1.6; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container">
        <span class="badge">Unhandled Exception &bull; {$class}</span>
        <h1>{$message}</h1>
        <div class="location">File: <b>{$file}</b> on line <b>{$line}</b></div>
        <h3>Stack Trace</h3>
        <div class="trace">{$trace}</div>
    </div>
</body>
</html>
HTML;
    }
}
