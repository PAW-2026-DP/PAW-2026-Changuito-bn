<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Representa una request HTTP entrante. Inmutable: cualquier dato derivado
 * (como los parámetros de ruta que resuelve el Router) se agrega devolviendo
 * una copia nueva.
 */
final class Request
{
    public readonly string $method;
    public readonly string $path;

    /** @var array<string, string> */
    private readonly array $normalizedHeaders;

    /**
     * @param array<string, string> $query
     * @param array<string, string> $headers
     * @param array<string, mixed> $body
     * @param array<string, string> $routeParams
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        string $method,
        string $path,
        public readonly array $query = [],
        array $headers = [],
        public readonly array $body = [],
        public readonly array $routeParams = [],
        public readonly array $attributes = [],
    ) {
        $this->method = strtoupper($method);
        $this->path = self::normalizePath($path);

        $normalized = [];
        foreach ($headers as $name => $value) {
            $normalized[strtolower($name)] = $value;
        }
        $this->normalizedHeaders = $normalized;
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $_SERVER['REQUEST_URI'] ?? '/';

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = (string) $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        $body = [];
        $rawBody = file_get_contents('php://input');
        if ($rawBody !== false && $rawBody !== '') {
            /** @var mixed $decoded */
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        return new self($method, $path, $_GET, $headers, $body);
    }

    /**
     * @param array<string, string> $params
     */
    public function withRouteParams(array $params): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->query,
            $this->headersWithOriginalCase(),
            $this->body,
            $params,
            $this->attributes,
        );
    }

    /**
     * Agrega un dato calculado por un middleware (por ejemplo, el usuario
     * autenticado) sin acoplar la Request a cómo se calculó.
     */
    public function withAttribute(string $name, mixed $value): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->query,
            $this->headersWithOriginalCase(),
            $this->body,
            $this->routeParams,
            [...$this->attributes, $name => $value],
        );
    }

    public function header(string $name): ?string
    {
        return $this->normalizedHeaders[strtolower($name)] ?? null;
    }

    public function query(string $key): ?string
    {
        return $this->query[$key] ?? null;
    }

    public function routeParam(string $name): ?string
    {
        return $this->routeParams[$name] ?? null;
    }

    public function attribute(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function headersWithOriginalCase(): array
    {
        return $this->normalizedHeaders;
    }

    private static function normalizePath(string $path): string
    {
        $withoutQuery = explode('?', $path, 2)[0];
        $trimmed = rtrim($withoutQuery, '/');

        return $trimmed === '' ? '/' : $trimmed;
    }
}
