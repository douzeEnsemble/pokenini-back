<?php

declare(strict_types=1);

namespace App\Service\CacheInvalidator;

use App\Cache\KeyMaker;

class CatchStatesCacheInvalidatorService extends AbstractLabelsCacheInvalidatorService implements CacheInvalidatorInterface
{
    #[\Override]
    public function getSupportedTypes(): array
    {
        return ['labels', 'catch_states'];
    }

    #[\Override]
    public function invalidate(): void
    {
        foreach ($this->languages() as $lang) {
            $this->cache->delete(KeyMaker::getCatchStatesKey($lang));
        }
    }
}
