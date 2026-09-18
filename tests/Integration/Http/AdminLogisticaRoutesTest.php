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

final class AdminLogisticaRoutesTest extends TestCase
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

    public function test_define_y_lista_los_parametros_de_un_tamanio(): void
    {
        // Act
        $definido = $this->pedir('PUT', '/api/v1/admin/parametros-logisticos/mediano', [
            'p0Centavos' => 80000,
            'kCentavos' => 15000,
        ]);
        $listado = $this->pedir('GET', '/api/v1/admin/parametros-logisticos');

        // Assert
        self::assertSame(200, $definido->status);
        self::assertSame(80000, $this->json($definido)['p0Centavos']);
        self::assertCount(1, $this->json($listado)['parametros']);
    }

    public function test_rechaza_un_tamanio_inexistente_en_la_ruta_con_422(): void
    {
        // Act
        $response = $this->pedir('PUT', '/api/v1/admin/parametros-logisticos/gigante', [
            'p0Centavos' => 80000,
            'kCentavos' => 15000,
        ]);

        // Assert
        self::assertSame(422, $response->status);
    }

    public function test_crea_actualiza_y_elimina_un_tramo_de_distancia(): void
    {
        // Arrange
        $creado = $this->pedir('POST', '/api/v1/admin/coeficientes-distancia', [
            'tamanioEnvio' => 'grande',
            'distanciaMinKm' => 0.0,
            'distanciaMaxKm' => 5.0,
            'coeficiente' => 1.2,
        ]);
        $id = $this->json($creado)['id'];

        // Act
        $actualizado = $this->pedir("PUT", "/api/v1/admin/coeficientes-distancia/{$id}", [
            'tamanioEnvio' => 'grande',
            'distanciaMinKm' => 0.0,
            'distanciaMaxKm' => 8.0,
            'coeficiente' => 1.5,
        ]);
        $eliminado = $this->pedir('DELETE', "/api/v1/admin/coeficientes-distancia/{$id}");

        // Assert
        self::assertSame(201, $creado->status);
        self::assertSame(8.0, (float) $this->json($actualizado)['distanciaMaxKm']);
        self::assertSame(204, $eliminado->status);
        self::assertSame([], $this->json($this->pedir('GET', '/api/v1/admin/coeficientes-distancia'))['coeficientes']);
    }

    public function test_responde_404_al_eliminar_un_tramo_inexistente(): void
    {
        // Act
        $response = $this->pedir('DELETE', '/api/v1/admin/coeficientes-distancia/999');

        // Assert
        self::assertSame(404, $response->status);
    }

    public function test_ajusta_el_radio_de_oferta_de_reparto(): void
    {
        // Act
        $definido = $this->pedir('PUT', '/api/v1/admin/parametros-reparto', ['radioOfertaKm' => 8.5]);
        $consultado = $this->pedir('GET', '/api/v1/admin/parametros-reparto');

        // Assert
        self::assertSame(8.5, (float) $this->json($definido)['radioOfertaKm']);
        self::assertSame(8.5, (float) $this->json($consultado)['radioOfertaKm']);
    }

    public function test_rechaza_un_radio_de_oferta_no_positivo_con_422(): void
    {
        // Act
        $response = $this->pedir('PUT', '/api/v1/admin/parametros-reparto', ['radioOfertaKm' => 0]);

        // Assert
        self::assertSame(422, $response->status);
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
        $this->pdo->exec('DELETE FROM coeficientes_distancia');
        $this->pdo->exec('DELETE FROM parametros_logisticos');
        $this->pdo->exec('UPDATE parametros_reparto SET radio_oferta_km = 5.00 WHERE id = 1');
        $this->pdo->exec('DELETE FROM tokens_acceso');
        $this->pdo->exec('DELETE FROM usuarios');
    }
}
