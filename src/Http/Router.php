<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Exception\MethodNotAllowedException;
use App\Http\Exception\RouteNotFoundException;

/**
 * Resuelve método + path -> handler. No conoce nada de reglas de negocio:
 * solo matching de rutas y despacho.
 */
final class Router implements Handler
{
    /**
     * @var array<string, array<string, Handler>> método => [path pattern => handler]
     */
    private array $routes = [];

    public function get(string $path, Handler|callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, Handler|callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, Handler|callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, Handler|callable $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, Handler|callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    public function add(string $method, string $path, Handler|callable $handler): void
    {
        $method = strtoupper($method);
        $this->routes[$method][$path] = $handler instanceof Handler
            ? $handler
            : new CallableHandler($handler);
    }

    public function handle(Request $request): Response
    {
        try {
            [$handler, $params] = $this->match($request->method, $request->path);
        } catch (RouteNotFoundException) {
            return Response::error('Not Found', 404);
        } catch (MethodNotAllowedException $exception) {
            return Response::error('Method Not Allowed', 405)
                ->withHeader('Allow', implode(', ', $exception->allowedMethods()));
        }

        return $handler->handle($request->withRouteParams($params));
    }

    /**
     * @return array{0: Handler, 1: array<string, string>}
     */
    private function match(string $method, string $path): array
    {
        $allowedMethods = [];

        foreach ($this->routes as $routeMethod => $patterns) {
            foreach ($patterns as $pattern => $handler) {
                $params = $this->matchPattern($pattern, $path);
                if ($params === null) {
                    continue;
                }

                if ($routeMethod === $method) {
                    return [$handler, $params];
                }

                $allowedMethods[] = $routeMethod;
            }
        }

        if ($allowedMethods !== []) {
            throw new MethodNotAllowedException(array_values(array_unique($allowedMethods)));
        }

        throw new RouteNotFoundException("No route matches path [{$path}]");
    }

    /**
     * @return array<string, string>|null
     */
    private function matchPattern(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);

        if (preg_match('#^' . $regex . '$#', $path, $matches) !== 1) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
