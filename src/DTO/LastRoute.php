<?php

declare(strict_types=1);

namespace App\DTO;

final class LastRoute
{
    /**
     * @param string[] $routeParams
     */
    public function __construct(
        public readonly string $route,
        public readonly array $routeParams
    ) {}
}
