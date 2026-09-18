<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Http\Handler;
use App\Http\Request;
use App\Http\Response;

/**
 * Handler que siempre lanza la excepción indicada, para testear middlewares
 * que traducen excepciones a Response (ver ExceptionMiddlewareTest).
 */
final class ThrowingHandler implements Handler
{
    public function __construct(private readonly \Throwable $exception)
    {
    }

    public function handle(Request $request): Response
    {
        throw $this->exception;
    }
}
