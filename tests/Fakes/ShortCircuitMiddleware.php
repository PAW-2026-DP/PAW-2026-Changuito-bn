<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Http\Handler;
use App\Http\Middleware;
use App\Http\Request;
use App\Http\Response;

final class ShortCircuitMiddleware implements Middleware
{
    public function __construct(private readonly int $status)
    {
    }

    public function process(Request $request, Handler $next): Response
    {
        return Response::error('Cortado por middleware', $this->status);
    }
}
