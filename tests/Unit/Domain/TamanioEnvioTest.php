<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\TamanioEnvio;
use PHPUnit\Framework\TestCase;

final class TamanioEnvioTest extends TestCase
{
    public function test_expone_los_tres_tamanios_posibles(): void
    {
        // Arrange / Act
        $casos = TamanioEnvio::cases();

        // Assert
        self::assertCount(3, $casos);
        self::assertSame('pequenio', TamanioEnvio::PEQUENIO->value);
        self::assertSame('mediano', TamanioEnvio::MEDIANO->value);
        self::assertSame('grande', TamanioEnvio::GRANDE->value);
    }
}
