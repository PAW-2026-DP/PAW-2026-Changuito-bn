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

final class AdminCatalogoRoutesTest extends TestCase
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

    public function test_crea_una_categoria_y_la_lista(): void
    {
        // Act
        $creada = $this->pedir('POST', '/api/v1/admin/categorias', ['nombre' => 'Almacén']);
        $listado = $this->pedir('GET', '/api/v1/admin/categorias');

        // Assert
        self::assertSame(201, $creada->status);
        self::assertCount(1, $this->json($listado)['categorias']);
    }

    public function test_rechaza_una_categoria_repetida_con_422(): void
    {
        // Arrange
        $this->pedir('POST', '/api/v1/admin/categorias', ['nombre' => 'Almacén']);

        // Act
        $response = $this->pedir('POST', '/api/v1/admin/categorias', ['nombre' => 'Almacén']);

        // Assert
        self::assertSame(422, $response->status);
    }

    public function test_crea_un_producto_del_maestro(): void
    {
        // Arrange
        $categoriaId = $this->json($this->pedir('POST', '/api/v1/admin/categorias', ['nombre' => 'Almacén']))['id'];

        // Act
        $response = $this->pedir('POST', '/api/v1/admin/productos', [
            'nombre' => 'Fideos',
            'descripcion' => 'Fideos secos',
            'categoriaId' => $categoriaId,
            'tamanioEnvio' => 'pequenio',
        ]);

        // Assert
        self::assertSame(201, $response->status);
        self::assertSame('pequenio', $this->json($response)['tamanioEnvio']);
    }

    public function test_rechaza_un_tamanio_de_envio_inexistente_con_422(): void
    {
        // Arrange
        $categoriaId = $this->json($this->pedir('POST', '/api/v1/admin/categorias', ['nombre' => 'Almacén']))['id'];

        // Act
        $response = $this->pedir('POST', '/api/v1/admin/productos', [
            'nombre' => 'Fideos',
            'descripcion' => 'Fideos secos',
            'categoriaId' => $categoriaId,
            'tamanioEnvio' => 'gigante',
        ]);

        // Assert
        self::assertSame(422, $response->status);
    }

    public function test_responde_404_si_la_categoria_del_producto_no_existe(): void
    {
        // Act
        $response = $this->pedir('POST', '/api/v1/admin/productos', [
            'nombre' => 'Fideos',
            'descripcion' => 'Fideos secos',
            'categoriaId' => 999,
            'tamanioEnvio' => 'pequenio',
        ]);

        // Assert
        self::assertSame(404, $response->status);
    }

    public function test_busca_productos_por_texto(): void
    {
        // Arrange
        $categoriaId = $this->json($this->pedir('POST', '/api/v1/admin/categorias', ['nombre' => 'Almacén']))['id'];
        $this->crearProducto('Fideos', $categoriaId);
        $this->crearProducto('Arroz', $categoriaId);

        // Act
        $response = $this->app->handle(new Request(
            'GET',
            '/api/v1/admin/productos',
            query: ['q' => 'fide'],
            headers: ['Authorization' => "Bearer {$this->token}"],
        ));

        // Assert
        $productos = $this->json($response)['productos'];
        self::assertCount(1, $productos);
        self::assertSame('Fideos', $productos[0]['nombre']);
    }

    public function test_actualiza_un_producto_existente(): void
    {
        // Arrange
        $categoriaId = $this->json($this->pedir('POST', '/api/v1/admin/categorias', ['nombre' => 'Almacén']))['id'];
        $productoId = $this->json($this->crearProducto('Fideos', $categoriaId))['id'];

        // Act
        $response = $this->pedir('PUT', "/api/v1/admin/productos/{$productoId}", [
            'nombre' => 'Fideos largos',
            'descripcion' => 'Actualizado',
            'categoriaId' => $categoriaId,
            'tamanioEnvio' => 'mediano',
        ]);

        // Assert
        self::assertSame(200, $response->status);
        self::assertSame('Fideos largos', $this->json($response)['nombre']);
    }

    private function crearProducto(string $nombre, int $categoriaId): Response
    {
        return $this->pedir('POST', '/api/v1/admin/productos', [
            'nombre' => $nombre,
            'descripcion' => 'Descripción',
            'categoriaId' => $categoriaId,
            'tamanioEnvio' => 'pequenio',
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
        $this->pdo->exec('DELETE FROM productos');
        $this->pdo->exec('DELETE FROM categorias');
        $this->pdo->exec('DELETE FROM tokens_acceso');
        $this->pdo->exec('DELETE FROM usuarios');
    }
}
