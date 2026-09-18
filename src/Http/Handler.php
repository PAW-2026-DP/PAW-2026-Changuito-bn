<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Contrato común para cualquier cosa capaz de producir una Response a partir
 * de una Request: controladores, el Router y el Pipeline.
 */
interface Handler
{
    public function handle(Request $request): Response;
}
