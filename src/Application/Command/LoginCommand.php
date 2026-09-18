<?php

declare(strict_types=1);

namespace App\Application\Command;

final class LoginCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {
    }
}
