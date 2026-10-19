<?php

declare(strict_types=1);

namespace App\Handlers;

use Core\Http\Request;
use Core\Http\Response;
use OpenApi\Generator;
use Throwable;

/**
 * Class SwaggerHandler
 * 
 * Handler untuk menyajikan antarmuka Swagger UI dan endpoint spesifikasi OpenAPI 3.0.
 */
class SwaggerHandler extends Controller
{
    /**
     * Tampilkan halaman Swagger UI interaktif.
     */
    public function index(Request $request): Response
    {
        return $this->view('pages/swagger', [
            'title' => 'API Documentation (Swagger UI)',
            'specUrl' => route('swagger.json'),
        ]);
    }

    /**
     * Tampilkan antarmuka Scalar Modern API Reference.
     */
    public function scalar(Request $request): Response
    {
        return $this->view('pages/scalar', [
            'title' => 'Nexus API Documentation (Scalar Modern)',
            'specUrl' => route('swagger.json'),
        ]);
    }

    /**
     * Hasilkan dokumen OpenAPI 3.0 dalam format JSON secara dinamis.
     */
    public function spec(Request $request): Response
    {
        try {
            $configPath = base_path('config/swagger.php');
            $config = file_exists($configPath) ? require $configPath : [];

            $scanPaths = $config['scan_paths'] ?? [
                base_path('app/OpenApi'),
                base_path('app/Handlers'),
            ];

            // Filter path yang benar-benar ada
            $validPaths = array_values(array_filter($scanPaths, fn($p) => is_dir((string)$p) || file_exists((string)$p)));

            $generator = new Generator();
            $openapi = $generator->generate($validPaths);

            if ($openapi === null) {
                return Response::json([
                    'status' => 'error',
                    'message' => 'Gagal memproses spesifikasi OpenAPI.',
                ], 500);
            }

            return new Response($openapi->toJson(), 200, [
                'Content-Type' => 'application/json; charset=utf-8',
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'no-cache, private',
            ]);
        } catch (Throwable $e) {
            return Response::json([
                'status' => 'error',
                'message' => 'Swagger Generator Error: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }
}
