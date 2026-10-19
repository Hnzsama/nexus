<?php

declare(strict_types=1);

return [
    'title' => env('SWAGGER_TITLE', 'Nexus Framework API'),
    'version' => env('SWAGGER_VERSION', '1.0.0'),
    'description' => env('SWAGGER_DESCRIPTION', 'Dokumentasi interaktif OpenAPI 3.0 untuk Nexus MVC Framework'),
    'scan_paths' => [
        base_path('app/OpenApi'),
        base_path('app/Handlers'),
    ],
    'output_file' => public_path('openapi.json'),
];
