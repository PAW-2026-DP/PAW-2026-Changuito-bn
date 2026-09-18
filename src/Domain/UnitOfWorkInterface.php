<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Agrupa escrituras de uno o más repositorios en una única transacción
 * (ver sección 5.5 de los lineamientos). Los Services dependen de esta
 * interfaz, nunca de PDO directamente, para poder testearse con un Fake en
 * memoria (ver tests/Fakes/InMemoryUnitOfWork) sin levantar base de datos.
 */
interface UnitOfWorkInterface
{
    /**
     * @template T
     * @param callable(): T $work
     * @return T
     */
    public function transactional(callable $work): mixed;
}
