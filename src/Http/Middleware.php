<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Concern transversal (auth, CORS, logging) que se compone en un Pipeline.
 * Decide si delega en el siguiente handler de la cadena o corta devolviendo
 * su propia Response.
 */
interface Middleware
{
    public function process(Request $request, Handler $next): Response;
}
