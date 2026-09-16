<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Compone una lista de Middleware alrededor de un Handler final (típicamente
 * el Router). Agregar un middleware nuevo no requiere tocar ningún
 * controlador existente.
 */
final class Pipeline implements Handler
{
    /**
     * @param list<Middleware> $middlewares
     */
    public function __construct(
        private readonly Handler $target,
        private readonly array $middlewares = [],
    ) {
    }

    public function handle(Request $request): Response
    {
        $next = $this->target;

        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = new class ($middleware, $next) implements Handler {
                public function __construct(
                    private readonly Middleware $middleware,
                    private readonly Handler $next,
                ) {
                }

                public function handle(Request $request): Response
                {
                    return $this->middleware->process($request, $this->next);
                }
            };
        }

        return $next->handle($request);
    }
}
