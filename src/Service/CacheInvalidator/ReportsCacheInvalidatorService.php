<?php

declare(strict_types=1);

namespace App\Service\CacheInvalidator;

use App\Cache\KeyMaker;

class ReportsCacheInvalidatorService extends AbstractCacheInvalidatorService implements CacheInvalidatorInterface
{
    #[\Override]
    public function getSupportedTypes(): array
    {
        return ['reports'];
    }

    #[\Override]
    public function invalidate(): void
    {
        $this->cache->delete(KeyMaker::getReportsKey());
    }
}
