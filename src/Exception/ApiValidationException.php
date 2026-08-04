<?php

declare(strict_types=1);

namespace App\Exception;

final class ApiValidationException extends \RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
    ) {}

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
