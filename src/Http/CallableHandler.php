<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Adapta un callable a Handler, para poder registrar rutas con closures
 * o `[$controller, 'metodo']` sin obligar a implementar la interfaz.
 */
final class CallableHandler implements Handler
{
    /**
     * @param callable(Request): Response $callable
     */
    public function __construct(private readonly mixed $callable)
    {
    }

    public function handle(Request $request): Response
    {
        return ($this->callable)($request);
    }
}
