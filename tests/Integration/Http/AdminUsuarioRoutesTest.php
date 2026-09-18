<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Domain\Rol;
use App\Domain\Usuario;
use App\Http\Handler;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\PdoUsuarioRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\AplicacionDeTest;

final class AdminUsuarioRoutesTest extends TestCase
{
    private PDO $pdo;
    private Handler $app;
    private string $tokenAdmin;

    protected function setUp(): void
    {
        $this->pdo = AplicacionDeTest::conexion();
        $this->limpiar();
        $this->app = AplicacionDeTest::crear($this->pdo);

        $this->crearCuenta('admin@changuito.test', Rol::ADMINISTRADOR);
        $this->tokenAdmin = $this->loguear('admin@changuito.test');
    }

    protected function tearDown(): void
    {
        $this->limpiar();
    }

    public function test_el_admin_crea_una_cuenta_de_repartidor(): void
    {
        // Act
        $response = $this->app->handle(new Request(
            'POST',
            '/api/v1/admin/usuarios',
            headers: ['Authorization' => "Bearer {$this->tokenAdmin}"],
            body: ['email' => 'rider@changuito.test', 'password' => 'secreta123', 'nombre' => 'Rider', 'rol' => 'repartidor'],
        ));

        // Assert
        self::assertSame(201, $response->status);
        self::assertSame('repartidor', $this->json($response)['rol']);
    }

    public function test_rechaza_un_rol_inexistente_con_422(): void
    {
        // Act
        $response = $this->app->handle(new Request(
            'POST',
            '/api/v1/admin/usuarios',
            headers: ['Authorization' => "Bearer {$this->tokenAdmin}"],
            body: ['email' => 'x@changuito.test', 'password' => 'secreta123', 'nombre' => 'X', 'rol' => 'jefe'],
        ));

        // Assert
        self::assertSame(422, $response->status);
    }

    public function test_filtra_el_listado_por_rol(): void
    {
        // Arrange
        $this->crearCuenta('rider@changuito.test', Rol::REPARTIDOR);

        // Act
        $response = $this->app->handle(new Request(
            'GET',
            '/api/v1/admin/usuarios',
            query: ['rol' => 'repartidor'],
            headers: ['Authorization' => "Bearer {$this->tokenAdmin}"],
        ));

        // Assert
        self::assertSame(200, $response->status);
        $usuarios = $this->json($response)['usuarios'];
        self::assertCount(1, $usuarios);
        self::assertSame('rider@changuito.test', $usuarios[0]['email']);
    }

    public function test_desactiva_y_reactiva_una_cuenta(): void
    {
        // Arrange
        $rider = $this->crearCuenta('rider@changuito.test', Rol::REPARTIDOR);

        // Act
        $baja = $this->postAdmin("/api/v1/admin/usuarios/{$rider->id}/desactivacion");
        $alta = $this->postAdmin("/api/v1/admin/usuarios/{$rider->id}/activacion");

        // Assert
        self::assertFalse($this->json($baja)['activo']);
        self::assertTrue($this->json($alta)['activo']);
    }

    public function test_un_cliente_no_puede_entrar_al_panel_de_admin(): void
    {
        // Arrange
        $this->crearCuenta('ana@changuito.test', Rol::CLIENTE);
        $tokenCliente = $this->loguear('ana@changuito.test');

        // Act
        $response = $this->app->handle(new Request(
            'GET',
            '/api/v1/admin/usuarios',
            headers: ['Authorization' => "Bearer {$tokenCliente}"],
        ));

        // Assert
        self::assertSame(403, $response->status);
    }

    public function test_rechaza_el_panel_de_admin_sin_token_con_401(): void
    {
        // Act
        $response = $this->app->handle(new Request('GET', '/api/v1/admin/usuarios'));

        // Assert
        self::assertSame(401, $response->status);
    }

    private function postAdmin(string $path): Response
    {
        return $this->app->handle(new Request(
            'POST',
            $path,
            headers: ['Authorization' => "Bearer {$this->tokenAdmin}"],
        ));
    }

    private function crearCuenta(string $email, Rol $rol): Usuario
    {
        return (new PdoUsuarioRepository($this->pdo))->save(Usuario::registrar(
            0,
            $email,
            'secreta123',
            'Cuenta',
            $rol,
            new \DateTimeImmutable('2026-09-18 10:00:00'),
        ));
    }

    private function loguear(string $email): string
    {
        $login = $this->app->handle(new Request(
            'POST',
            '/api/v1/auth/login',
            body: ['email' => $email, 'password' => 'secreta123'],
        ));

        return $this->json($login)['token'];
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
        $this->pdo->exec('DELETE FROM direcciones');
        $this->pdo->exec('DELETE FROM tokens_acceso');
        $this->pdo->exec('DELETE FROM usuarios');
    }
}
