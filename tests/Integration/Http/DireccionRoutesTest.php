<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Http\Handler;
use App\Http\Request;
use App\Http\Response;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\AplicacionDeTest;

final class DireccionRoutesTest extends TestCase
{
    private PDO $pdo;
    private Handler $app;
    private string $token;

    protected function setUp(): void
    {
        $this->pdo = AplicacionDeTest::conexion();
        $this->limpiar();
        $this->app = AplicacionDeTest::crear($this->pdo);
        $this->token = $this->registrarYLoguear('ana@changuito.test');
    }

    protected function tearDown(): void
    {
        $this->limpiar();
    }

    public function test_crea_la_primera_direccion_como_predeterminada(): void
    {
        // Act
        $response = $this->crearDireccion($this->token);

        // Assert
        self::assertSame(201, $response->status);
        self::assertTrue($this->json($response)['esDefault']);
    }

    public function test_lista_las_direcciones_del_cliente_autenticado(): void
    {
        // Arrange
        $this->crearDireccion($this->token);

        // Act
        $response = $this->pedir('GET', '/api/v1/clientes/me/direcciones', $this->token);

        // Assert
        self::assertSame(200, $response->status);
        self::assertCount(1, $this->json($response)['direcciones']);
    }

    public function test_rechaza_listar_sin_token_con_401(): void
    {
        // Act
        $response = $this->app->handle(new Request('GET', '/api/v1/clientes/me/direcciones'));

        // Assert
        self::assertSame(401, $response->status);
    }

    public function test_marcar_predeterminada_desmarca_la_anterior(): void
    {
        // Arrange
        $primera = $this->json($this->crearDireccion($this->token))['id'];
        $segunda = $this->json($this->crearDireccion($this->token, 'Otra calle'))['id'];

        // Act
        $response = $this->pedir('POST', "/api/v1/clientes/me/direcciones/{$segunda}/predeterminada", $this->token);

        // Assert
        self::assertSame(200, $response->status);
        $direcciones = $this->json($this->pedir('GET', '/api/v1/clientes/me/direcciones', $this->token))['direcciones'];
        $porId = array_column($direcciones, 'esDefault', 'id');
        self::assertTrue($porId[$segunda]);
        self::assertFalse($porId[$primera]);
    }

    public function test_responde_404_al_tocar_la_direccion_de_otro_cliente(): void
    {
        // Arrange
        $ajena = $this->json($this->crearDireccion($this->token))['id'];
        $otroToken = $this->registrarYLoguear('otro@changuito.test');

        // Act
        $response = $this->pedir('DELETE', "/api/v1/clientes/me/direcciones/{$ajena}", $otroToken);

        // Assert
        self::assertSame(404, $response->status);
    }

    public function test_elimina_una_direccion_propia(): void
    {
        // Arrange
        $id = $this->json($this->crearDireccion($this->token))['id'];

        // Act
        $response = $this->pedir('DELETE', "/api/v1/clientes/me/direcciones/{$id}", $this->token);

        // Assert
        self::assertSame(204, $response->status);
        self::assertSame([], $this->json($this->pedir('GET', '/api/v1/clientes/me/direcciones', $this->token))['direcciones']);
    }

    private function crearDireccion(string $token, string $calle = 'Av. Siempreviva'): Response
    {
        return $this->app->handle(new Request(
            'POST',
            '/api/v1/clientes/me/direcciones',
            headers: ['Authorization' => "Bearer {$token}"],
            body: [
                'calle' => $calle,
                'numero' => '742',
                'ciudad' => 'Luján',
                'cp' => '6700',
                'latitud' => -34.57,
                'longitud' => -59.10,
            ],
        ));
    }

    private function pedir(string $metodo, string $path, string $token): Response
    {
        return $this->app->handle(new Request(
            $metodo,
            $path,
            headers: ['Authorization' => "Bearer {$token}"],
        ));
    }

    private function registrarYLoguear(string $email): string
    {
        $this->app->handle(new Request(
            'POST',
            '/api/v1/auth/registro',
            body: ['email' => $email, 'password' => 'secreta123', 'nombre' => 'Ana'],
        ));

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
