<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Domain\Exception\ValidacionException;
use App\Http\Request;

/**
 * Lectura tipada del body JSON. Traducir "lo que llegó por HTTP" a los
 * tipos que espera un Command es responsabilidad del controlador, así el
 * Service nunca recibe un array suelto ni valores sin tipo.
 */
trait CuerpoDeRequestTrait
{
    private function textoRequerido(Request $request, string $campo): string
    {
        $valor = $request->body[$campo] ?? null;

        if (!is_string($valor) || trim($valor) === '') {
            throw new ValidacionException("El campo {$campo} es obligatorio.");
        }

        return trim($valor);
    }

    private function textoOpcional(Request $request, string $campo, string $porDefecto = ''): string
    {
        $valor = $request->body[$campo] ?? null;

        return is_string($valor) ? trim($valor) : $porDefecto;
    }

    private function enteroRequerido(Request $request, string $campo): int
    {
        $valor = $request->body[$campo] ?? null;

        if (!is_int($valor) && !(is_string($valor) && ctype_digit($valor))) {
            throw new ValidacionException("El campo {$campo} debe ser un número entero.");
        }

        return (int) $valor;
    }

    private function decimalRequerido(Request $request, string $campo): float
    {
        $valor = $request->body[$campo] ?? null;

        if (!is_int($valor) && !is_float($valor) && !(is_string($valor) && is_numeric($valor))) {
            throw new ValidacionException("El campo {$campo} debe ser un número.");
        }

        return (float) $valor;
    }

    private function booleanoOpcional(Request $request, string $campo, bool $porDefecto): bool
    {
        $valor = $request->body[$campo] ?? null;

        return is_bool($valor) ? $valor : $porDefecto;
    }

    private function idDeRuta(Request $request, string $parametro): int
    {
        $valor = $request->routeParam($parametro);

        if ($valor === null || !ctype_digit($valor)) {
            throw new ValidacionException("El parámetro {$parametro} debe ser un id numérico.");
        }

        return (int) $valor;
    }
}
