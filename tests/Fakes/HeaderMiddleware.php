<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Http\Handler;
use App\Http\Middleware;
use App\Http\Request;
use App\Http\Response;

final class HeaderMiddleware implements Middleware
{
    public function __construct(
        private readonly string $name,
        private readonly string $value,
    ) {
    }

    public function process(Request $request, Handler $next): Response
    {
        return $next->handle($request)->withHeader($this->name, $this->value);
    }
}
