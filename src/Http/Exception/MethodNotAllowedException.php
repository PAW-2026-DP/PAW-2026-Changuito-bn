<?php

declare(strict_types=1);

namespace App\Http\Exception;

/**
 * El path matchea alguna ruta registrada, pero no con el método HTTP usado.
 */
final class MethodNotAllowedException extends \RuntimeException
{
    /**
     * @param list<string> $allowedMethods
     */
    public function __construct(private readonly array $allowedMethods)
    {
        parent::__construct('Method not allowed');
    }

    /**
     * @return list<string>
     */
    public function allowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
