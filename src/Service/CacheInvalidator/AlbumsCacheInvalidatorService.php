<?php

declare(strict_types=1);

namespace App\Service\CacheInvalidator;

use App\Cache\KeyMaker;

class AlbumsCacheInvalidatorService extends AbstractCacheInvalidatorService implements CacheInvalidatorInterface
{
    #[\Override]
    public function getSupportedTypes(): array
    {
        return [
            'regional_dex_numbers',
            'games_availabilities',
            'games_shinies_availabilities',
            'game_bundles_availabilities',
            'game_bundles_shinies_availabilities',
            'collections_availabilities',
            'pokemon_availabilities',
            'albums',
            'dex_availabilities',
        ];
    }

    #[\Override]
    public function invalidate(): void
    {
        $this->cache->invalidateTags([KeyMaker::getAlbumKey()]);
    }
}
