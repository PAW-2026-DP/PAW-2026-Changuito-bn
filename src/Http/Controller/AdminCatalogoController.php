<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\AdminCatalogoService;
use App\Application\Command\GuardarProductoCommand;
use App\Domain\Categoria;
use App\Domain\Exception\ValidacionException;
use App\Domain\Producto;
use App\Domain\TamanioEnvio;
use App\Http\Request;
use App\Http\Response;

/**
 * Maestro de categorías y productos normalizados.
 */
final class AdminCatalogoController
{
    use CuerpoDeRequestTrait;

    public function __construct(private readonly AdminCatalogoService $catalogo)
    {
    }

    public function listarCategorias(Request $request): Response
    {
        return Response::json([
            'categorias' => array_map(self::categoriaComoJson(...), $this->catalogo->listarCategorias()),
        ]);
    }

    public function crearCategoria(Request $request): Response
    {
        $categoria = $this->catalogo->crearCategoria($this->textoRequerido($request, 'nombre'));

        return Response::json(self::categoriaComoJson($categoria), 201);
    }

    public function buscarProductos(Request $request): Response
    {
        $categoriaId = $request->query('categoriaId');

        return Response::json([
            'productos' => array_map(
                self::productoComoJson(...),
                $this->catalogo->buscarProductos($request->query('q'), $categoriaId === null ? null : (int) $categoriaId),
            ),
        ]);
    }

    public function crearProducto(Request $request): Response
    {
        $producto = $this->catalogo->crearProducto($this->comando($request));

        return Response::json(self::productoComoJson($producto), 201);
    }

    public function actualizarProducto(Request $request): Response
    {
        $producto = $this->catalogo->actualizarProducto($this->comando($request, $this->idDeRuta($request, 'id')));

        return Response::json(self::productoComoJson($producto));
    }

    private function comando(Request $request, int $id = 0): GuardarProductoCommand
    {
        $tamanio = $this->textoRequerido($request, 'tamanioEnvio');

        return new GuardarProductoCommand(
            $this->textoRequerido($request, 'nombre'),
            $this->textoOpcional($request, 'descripcion'),
            $this->enteroRequerido($request, 'categoriaId'),
            TamanioEnvio::tryFrom($tamanio) ?? throw new ValidacionException("El tamaño de envío {$tamanio} no existe."),
            $id,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function categoriaComoJson(Categoria $categoria): array
    {
        return ['id' => $categoria->id, 'nombre' => $categoria->nombre];
    }

    /**
     * @return array<string, mixed>
     */
    private static function productoComoJson(Producto $producto): array
    {
        return [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion,
            'categoriaId' => $producto->categoriaId,
            'tamanioEnvio' => $producto->tamanioEnvio->value,
        ];
    }
}
