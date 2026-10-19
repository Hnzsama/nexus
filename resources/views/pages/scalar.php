<?php
/**
 * Scalar Modern API Reference Template
 * 
 * @var \Core\View\ViewEngine $this
 * @var string $title
 * @var string $specUrl
 */
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->e($title ?? 'Nexus API &bull; Scalar Modern Studio') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: dark; }
        body {
            margin: 0;
            padding: 0;
            background-color: #0b0f19;
            color: #f1f5f9;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        }
        #nexus-scalar-topbar {
            background: #0f172a;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            font-size: 0.85rem;
            position: sticky;
            top: 0;
            z-index: 9999;
        }
        #nexus-scalar-topbar a {
            color: #94a3b8;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.15s ease;
        }
        #nexus-scalar-topbar a:hover {
            color: #38bdf8;
        }
        .badge-pill {
            background: rgba(99, 102, 241, 0.2);
            color: #818cf8;
            border: 1px solid rgba(99, 102, 241, 0.4);
            border-radius: 9999px;
            padding: 2px 8px;
            font-size: 0.75rem;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div id="nexus-scalar-topbar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="<?= route('swagger.index') ?>" style="font-weight: 600; color: #f8fafc;">
                &larr; Kembali ke Swagger UI
            </a>
            <span style="color: rgba(255,255,255,0.2);">|</span>
            <span style="color: #cbd5e1; font-weight: 600;">Nexus RESTful API</span>
            <span class="badge-pill">Scalar Modern Studio</span>
        </div>
        <div style="display: flex; align-items: center; gap: 14px;">
            <a href="<?= route('swagger.json') ?>" target="_blank">Download OpenAPI JSON &nearr;</a>
            <a href="<?= route('home') ?>">Beranda Framework &rarr;</a>
        </div>
    </div>

    <!-- Scalar API Reference Standalone Script -->
    <script
      id="api-reference"
      data-url="<?= $this->e($specUrl) ?>"
      data-configuration='{
        "theme": "purple",
        "darkMode": true,
        "searchHotKey": "k",
        "hideDownloadButton": false,
        "metaData": {
          "title": "Nexus Framework API",
          "description": "Dokumentasi interaktif OpenAPI 3.0 Nexus MVC"
        }
      }'
    ></script>
    <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference"></script>
</body>
</html>
