<?php

declare(strict_types=1);

use Core\App;
use Core\Http\Request;

/**
 * Nexus Framework - Front Controller
 * Seluruh HTTP Request diarahkan ke file ini untuk diproses oleh Router dan App Lifecycle.
 */

define('NEXUS_START', microtime(true));

// 1. Bootstrapping framework
/** @var App $app */
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';

// 2. Tangkap request HTTP saat ini
$request = Request::capture();

// 3. Handle request melalui Router dan Middleware Pipeline
$response = $app->handle($request);

// 4. Kirim header dan konten response ke browser client
$response->send();
