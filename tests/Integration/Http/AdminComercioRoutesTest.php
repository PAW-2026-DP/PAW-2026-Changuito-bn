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

final class AdminComercioRoutesTest extends TestCase
{
    private PDO $pdo;
    private Handler $app;
    private string $token;

    protected function setUp(): void
    {
        $this->pdo = AplicacionDeTest::conexion();
        $this->limpiar();
        $this->app = AplicacionDeTest::crear($this->pdo);

        (new PdoUsuarioRepository($this->pdo))->save(Usuario::registrar(
            0,
            'admin@changuito.test',
            'secreta123',
            'Admin',
            Rol::ADMINISTRADOR,
            new \DateTimeImmutable('2026-09-18 10:00:00'),
        ));

        $login = $this->app->handle(new Request(
            'POST',
            '/api/v1/auth/login',
            body: ['email' => 'admin@changuito.test', 'password' => 'secreta123'],
        ));
        $this->token = $this->json($login)['token'];
    }

    protected function tearDown(): void
    {
        $this->limpiar();
    }

    public function test_crea_un_supermercado_con_su_cuenta_de_acceso(): void
    {
        // Act
        $response = $this->crearSupermercado();

        // Assert
        self::assertSame(201, $response->status);
        self::assertSame('DIA Argentina SA', $this->json($response)['razonSocial']);

        $cuenta = (new PdoUsuarioRepository($this->pdo))->findByEmail('dia@changuito.test');
        self::assertNotNull($cuenta);
        self::assertSame(Rol::SUPERMERCADO, $cuenta->rol);
    }

    public function test_crea_una_sucursal_y_define_su_zona_de_cobertura(): void
    {
        // Arrange
        $supermercadoId = $this->json($this->crearSupermercado())['id'];

        // Act
        $sucursal = $this->pedir('POST', "/api/v1/admin/supermercados/{$supermercadoId}/sucursales", [
            'nombre' => 'Centro',
            'direccion' => 'Calle 1',
            'latitud' => -34.6,
            'longitud' => -58.4,
        ]);
        $sucursalId = $this->json($sucursal)['id'];

        $zona = $this->pedir('PUT', "/api/v1/admin/sucursales/{$sucursalId}/zona-cobertura", [
            'radioMinKm' => 0.5,
            'radioMaxKm' => 8.0,
        ]);

        // Assert
        self::assertSame(201, $sucursal->status);
        self::assertSame(200, $zona->status);
        self::assertSame(8.0, (float) $this->json($zona)['radioMaxKm']);
    }

    public function test_rechaza_una_zona_con_radio_minimo_mayor_al_maximo_con_422(): void
    {
        // Arrange
        $supermercadoId = $this->json($this->crearSupermercado())['id'];
        $sucursal = $this->pedir('POST', "/api/v1/admin/supermercados/{$supermercadoId}/sucursales", [
            'nombre' => 'Centro',
            'direccion' => 'Calle 1',
            'latitud' => -34.6,
            'longitud' => -58.4,
        ]);
        $sucursalId = $this->json($sucursal)['id'];

        // Act
        $response = $this->pedir('PUT', "/api/v1/admin/sucursales/{$sucursalId}/zona-cobertura", [
            'radioMinKm' => 10.0,
            'radioMaxKm' => 2.0,
        ]);

        // Assert
        self::assertSame(422, $response->status);
    }

    public function test_responde_404_al_crear_una_sucursal_de_un_supermercado_inexistente(): void
    {
        // Act
        $response = $this->pedir('POST', '/api/v1/admin/supermercados/999/sucursales', [
            'nombre' => 'Centro',
            'direccion' => 'Calle 1',
            'latitud' => -34.6,
            'longitud' => -58.4,
        ]);

        // Assert
        self::assertSame(404, $response->status);
    }

    private function crearSupermercado(): Response
    {
        return $this->pedir('POST', '/api/v1/admin/supermercados', [
            'email' => 'dia@changuito.test',
            'password' => 'secreta123',
            'nombre' => 'DIA',
            'razonSocial' => 'DIA Argentina SA',
            'cuit' => '30-12345678-9',
        ]);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function pedir(string $metodo, string $path, array $body = []): Response
    {
        return $this->app->handle(new Request(
            $metodo,
            $path,
            headers: ['Authorization' => "Bearer {$this->token}"],
            body: $body,
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
        $this->pdo->exec('DELETE FROM zonas_cobertura');
        $this->pdo->exec('DELETE FROM sucursales');
        $this->pdo->exec('DELETE FROM supermercados');
        $this->pdo->exec('DELETE FROM direcciones');
        $this->pdo->exec('DELETE FROM tokens_acceso');
        $this->pdo->exec('DELETE FROM usuarios');
    }
}
