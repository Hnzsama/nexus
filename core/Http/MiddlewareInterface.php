<?php

declare(strict_types=1);

namespace Core\Http;

use Closure;

/**
 * Interface MiddlewareInterface
 * 
 * Kontrak standar untuk pipeline middleware HTTP.
 * Setiap middleware dapat memeriksa/memodifikasi Request sebelum diteruskan ke handler berikutnya,
 * atau memodifikasi Response setelah handler selesai dieksekusi.
 */
interface MiddlewareInterface
{
    /**
     * Tangani request yang masuk.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response;
}
