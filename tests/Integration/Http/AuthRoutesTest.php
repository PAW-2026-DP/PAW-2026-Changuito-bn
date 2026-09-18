<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Http\Handler;
use App\Http\Request;
use App\Http\Response;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\AplicacionDeTest;

final class AuthRoutesTest extends TestCase
{
    private PDO $pdo;
    private Handler $app;

    protected function setUp(): void
    {
        $this->pdo = AplicacionDeTest::conexion();
        $this->limpiar();
        $this->app = AplicacionDeTest::crear($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->limpiar();
    }

    public function test_registra_un_cliente_y_devuelve_201(): void
    {
        // Act
        $response = $this->registrar();

        // Assert
        self::assertSame(201, $response->status);
        $cuerpo = $this->json($response);
        self::assertSame('ana@changuito.test', $cuerpo['email']);
        self::assertSame('cliente', $cuerpo['rol']);
        self::assertArrayNotHasKey('password', $cuerpo);
    }

    public function test_rechaza_el_registro_sin_email_con_422(): void
    {
        // Act
        $response = $this->app->handle(new Request(
            'POST',
            '/api/v1/auth/registro',
            body: ['password' => 'secreta123', 'nombre' => 'Ana'],
        ));

        // Assert
        self::assertSame(422, $response->status);
    }

    public function test_el_login_devuelve_un_token_usable_en_me(): void
    {
        // Arrange
        $this->registrar();

        // Act
        $login = $this->login();
        $token = $this->json($login)['token'];
        $me = $this->app->handle(new Request(
            'GET',
            '/api/v1/auth/me',
            headers: ['Authorization' => "Bearer {$token}"],
        ));

        // Assert
        self::assertSame(200, $login->status);
        self::assertSame(200, $me->status);
        self::assertSame('ana@changuito.test', $this->json($me)['email']);
    }

    public function test_rechaza_me_sin_token_con_401(): void
    {
        // Act
        $response = $this->app->handle(new Request('GET', '/api/v1/auth/me'));

        // Assert
        self::assertSame(401, $response->status);
    }

    public function test_rechaza_me_con_token_invalido_con_401(): void
    {
        // Act
        $response = $this->app->handle(new Request(
            'GET',
            '/api/v1/auth/me',
            headers: ['Authorization' => 'Bearer token-inventado'],
        ));

        // Assert
        self::assertSame(401, $response->status);
    }

    public function test_rechaza_el_login_con_contrasenia_incorrecta_con_401(): void
    {
        // Arrange
        $this->registrar();

        // Act
        $response = $this->app->handle(new Request(
            'POST',
            '/api/v1/auth/login',
            body: ['email' => 'ana@changuito.test', 'password' => 'incorrecta'],
        ));

        // Assert
        self::assertSame(401, $response->status);
    }

    public function test_el_logout_invalida_el_token(): void
    {
        // Arrange
        $this->registrar();
        $token = $this->json($this->login())['token'];

        // Act
        $logout = $this->app->handle(new Request(
            'POST',
            '/api/v1/auth/logout',
            headers: ['Authorization' => "Bearer {$token}"],
        ));
        $me = $this->app->handle(new Request(
            'GET',
            '/api/v1/auth/me',
            headers: ['Authorization' => "Bearer {$token}"],
        ));

        // Assert
        self::assertSame(204, $logout->status);
        self::assertSame(401, $me->status);
    }

    private function registrar(): Response
    {
        return $this->app->handle(new Request(
            'POST',
            '/api/v1/auth/registro',
            body: ['email' => 'ana@changuito.test', 'password' => 'secreta123', 'nombre' => 'Ana'],
        ));
    }

    private function login(): Response
    {
        return $this->app->handle(new Request(
            'POST',
            '/api/v1/auth/login',
            body: ['email' => 'ana@changuito.test', 'password' => 'secreta123'],
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function json(Response $response): array
    {
        return json_decode($response->body, true);
    }

    private function limpiar(): void
    {
        $this->pdo->exec('DELETE FROM tokens_acceso');
        $this->pdo->exec('DELETE FROM usuarios');
    }
}
