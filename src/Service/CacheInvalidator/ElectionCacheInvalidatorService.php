<?php

declare(strict_types=1);

namespace App\Service\CacheInvalidator;

use App\Cache\KeyMaker;

class ElectionCacheInvalidatorService extends AbstractCacheInvalidatorService
{
    public function invalidate(string $trainerId): void
    {
        $this->cache->invalidateTags([
            KeyMaker::getTrainerIdKey($trainerId),
        ]);
    }
}
