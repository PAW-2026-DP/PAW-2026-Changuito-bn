<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Exception\AccesoDenegadoException;
use App\Domain\Exception\EntidadNoEncontradaException;
use App\Domain\Exception\TransicionInvalidaException;
use App\Domain\Exception\ValidacionException;
use App\Http\Handler;
use App\Http\Middleware;
use App\Http\Request;
use App\Http\Response;

/**
 * Traduce las excepciones de dominio a la Response HTTP correspondiente, en
 * este único punto del Pipeline — así ningún controlador repite el mismo
 * catch. Cualquier excepción no prevista se registra y se devuelve como 500
 * genérico, sin exponer su detalle interno.
 */
final class ExceptionMiddleware implements Middleware
{
    public function process(Request $request, Handler $next): Response
    {
        try {
            return $next->handle($request);
        } catch (EntidadNoEncontradaException $exception) {
            return Response::error($exception->getMessage(), 404);
        } catch (AccesoDenegadoException $exception) {
            return Response::error($exception->getMessage(), 403);
        } catch (TransicionInvalidaException $exception) {
            return Response::error($exception->getMessage(), 409);
        } catch (ValidacionException $exception) {
            return Response::error($exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            error_log($exception->getMessage());

            return Response::error('Internal Server Error', 500);
        }
    }
}
