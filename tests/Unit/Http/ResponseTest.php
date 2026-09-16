<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function test_json_codifica_el_body_y_setea_content_type(): void
    {
        // Arrange / Act
        $response = Response::json(['status' => 'ok'], 201);

        // Assert
        self::assertSame(201, $response->status);
        self::assertSame('application/json; charset=utf-8', $response->headers['Content-Type']);
        self::assertSame('{"status":"ok"}', $response->body);
    }

    public function test_error_arma_payload_con_mensaje(): void
    {
        // Arrange / Act
        $response = Response::error('Not Found', 404);

        // Assert
        self::assertSame(404, $response->status);
        self::assertSame('{"error":"Not Found"}', $response->body);
    }

    public function test_no_content_responde_204_sin_body(): void
    {
        // Arrange / Act
        $response = Response::noContent();

        // Assert
        self::assertSame(204, $response->status);
        self::assertSame('', $response->body);
    }

    public function test_with_header_no_muta_la_response_original(): void
    {
        // Arrange
        $original = Response::json([]);

        // Act
        $modified = $original->withHeader('Allow', 'GET');

        // Assert
        self::assertSame('GET', $modified->headers['Allow']);
        self::assertArrayNotHasKey('Allow', $original->headers);
    }
}
