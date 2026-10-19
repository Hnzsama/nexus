<?php

declare(strict_types=1);

namespace App\OpenApi\Endpoints;

use OpenApi\Attributes as OA;

/**
 * Class ApiEndpoints
 * 
 * Spesifikasi endpoint RESTful API bawaan Nexus MVC Framework.
 */
class ApiEndpoints
{
    #[OA\Get(
        path: '/api/ping',
        summary: 'Heartbeat & Framework Status Check',
        description: 'Mengembalikan status kesehatan sistem, runtime PHP, environment aktif, dan IP client.',
        tags: ['General'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Koneksi berhasil dan service aktif.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'framework', type: 'string', example: 'Nexus MVC'),
                        new OA\Property(property: 'environment', type: 'string', example: 'local'),
                        new OA\Property(property: 'timestamp', type: 'string', format: 'date-time', example: '2026-10-19T23:59:00+07:00'),
                        new OA\Property(property: 'client_ip', type: 'string', example: '127.0.0.1'),
                    ]
                )
            )
        ]
    )]
    public function ping(): void
    {
    }

    #[OA\Get(
        path: '/api/v1/version',
        summary: 'Nexus API Version',
        description: 'Mengembalikan versi API v1 saat ini.',
        tags: ['General'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Informasi versi API.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                    ]
                )
            )
        ]
    )]
    public function version(): void
    {
    }

    #[OA\Post(
        path: '/submit',
        summary: 'Contoh POST Request Payload',
        description: 'Endpoint demonstrasi pengiriman data form / JSON payload dengan proteksi CSRF di web browser.',
        tags: ['General'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Pesan Baru'),
                    new OA\Property(property: 'message', type: 'string', example: 'Halo dari Swagger UI!'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data payload berhasil diterima oleh server.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Data formulir berhasil diterima!'),
                        new OA\Property(property: 'payload', type: 'object'),
                    ]
                )
            )
        ]
    )]
    public function submit(): void
    {
    }
}
