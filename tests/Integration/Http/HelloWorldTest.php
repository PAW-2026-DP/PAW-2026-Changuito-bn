<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use PHPUnit\Framework\TestCase;

final class HelloWorldTest extends TestCase
{
    public function test_responde_hello_world_en_formato_json(): void
    {
        // Arrange / Act
        ob_start();
        require dirname(__DIR__, 3) . '/public/index.php';
        $output = ob_get_clean();

        $decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        // Assert
        self::assertSame(['message' => 'Hello, World!'], $decoded);
    }
}
