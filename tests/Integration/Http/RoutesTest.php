<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Http\Handler;
use App\Http\Request;
use PHPUnit\Framework\TestCase;

final class RoutesTest extends TestCase
{
    private Handler $app;

    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 3) . '/config/container.php';
    }

    public function test_health_responde_ok(): void
    {
        // Act
        $response = $this->app->handle(new Request('GET', '/health'));

        // Assert
        self::assertSame(200, $response->status);
        self::assertSame('ok', json_decode($response->body, true)['status']);
    }

    public function test_ruta_inexistente_responde_404(): void
    {
        // Act
        $response = $this->app->handle(new Request('GET', '/no-existe'));

        // Assert
        self::assertSame(404, $response->status);
    }
}
