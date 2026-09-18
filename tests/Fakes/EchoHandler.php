<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Http\Handler;
use App\Http\Request;
use App\Http\Response;

final class EchoHandler implements Handler
{
    public function handle(Request $request): Response
    {
        return Response::json(['path' => $request->path, 'params' => $request->routeParams]);
    }
}
