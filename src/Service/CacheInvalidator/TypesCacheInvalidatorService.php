<?php

declare(strict_types=1);

namespace App\Service\CacheInvalidator;

use App\Cache\KeyMaker;

class TypesCacheInvalidatorService extends AbstractLabelsCacheInvalidatorService implements CacheInvalidatorInterface
{
    #[\Override]
    public function getSupportedTypes(): array
    {
        return ['labels', 'types'];
    }

    #[\Override]
    public function invalidate(): void
    {
        foreach ($this->languages() as $lang) {
            $this->cache->delete(KeyMaker::getTypesKey($lang));
        }
    }
}
