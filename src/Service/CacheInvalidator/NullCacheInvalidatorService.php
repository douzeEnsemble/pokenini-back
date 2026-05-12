<?php

declare(strict_types=1);

namespace App\Service\CacheInvalidator;

class NullCacheInvalidatorService implements CacheInvalidatorInterface
{
    #[\Override]
    public function getSupportedTypes(): array
    {
        return ['pokemons'];
    }

    #[\Override]
    public function invalidate(): void {}
}
