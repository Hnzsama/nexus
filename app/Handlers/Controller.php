<?php

declare(strict_types=1);

namespace App\Handlers;

use Core\Http\Controller as BaseController;

/**
 * Class Controller
 * 
 * Base Controller untuk seluruh handler aplikasi pengguna.
 * Anda dapat menambahkan helper, middleware guard, atau shared data di sini.
 */
abstract class Controller extends BaseController
{
    // Tambahkan method bersama untuk controller aplikasi di sini
}
