<?php

declare(strict_types=1);

namespace App\Service\CacheInvalidator;

use App\Cache\KeyMaker;

class TypesCacheInvalidatorService extends AbstractCacheInvalidatorService implements CacheInvalidatorInterface
{
    #[\Override]
    public function getSupportedTypes(): array
    {
        return ['labels'];
    }

    #[\Override]
    public function invalidate(): void
    {
        $this->cache->delete(KeyMaker::getTypesKey());
    }
}
