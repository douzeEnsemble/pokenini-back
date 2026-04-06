<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\CacheInvalidator\AlbumsCacheInvalidatorService;
use App\Service\CacheInvalidator\CatchStatesCacheInvalidatorService;
use App\Service\CacheInvalidator\DexCacheInvalidatorService;
use App\Service\CacheInvalidator\FormsCacheInvalidatorService;
use App\Service\CacheInvalidator\ReportsCacheInvalidatorService;
use App\Service\CacheInvalidator\TypesCacheInvalidatorService;

class CacheInvalidatorService
{
    public function __construct(
        private readonly CatchStatesCacheInvalidatorService $catchStatesCacheInvalidatorService,
        private readonly TypesCacheInvalidatorService $typesCacheInvalidatorService,
        private readonly FormsCacheInvalidatorService $formsCacheInvalidatorService,
        private readonly DexCacheInvalidatorService $dexCacheInvalidatorService,
        private readonly AlbumsCacheInvalidatorService $albumsCacheInvalidatorService,
        private readonly ReportsCacheInvalidatorService $reportsCacheInvalidatorService,
    ) {}

    public function invalidate(string $type): void
    {
        $labelsInvalidator = function () {
            $this->catchStatesCacheInvalidatorService->invalidate();
            $this->typesCacheInvalidatorService->invalidate();
            $this->formsCacheInvalidatorService->invalidate();
        };
        $albumsInvalidator = fn () => $this->albumsCacheInvalidatorService->invalidate();
        $dexInvalidator = fn () => $this->dexCacheInvalidatorService->invalidate();
        $dexAvailabilitiesInvalidator = function () {
            $this->dexCacheInvalidatorService->invalidate();
            $this->albumsCacheInvalidatorService->invalidate();
        };
        $reportsInvalidator = fn () => $this->reportsCacheInvalidatorService->invalidate();

        $map = [
            'pokemons' => fn () => null,
            'labels' => $labelsInvalidator,
            'games_collections_and_dex' => $dexInvalidator,
            'dex' => $dexInvalidator,
            'regional_dex_numbers' => $albumsInvalidator,
            'games_availabilities' => $albumsInvalidator,
            'games_shinies_availabilities' => $albumsInvalidator,
            'game_bundles_availabilities' => $albumsInvalidator,
            'game_bundles_shinies_availabilities' => $albumsInvalidator,
            'collections_availabilities' => $albumsInvalidator,
            'pokemon_availabilities' => $albumsInvalidator,
            'albums' => $albumsInvalidator,
            'dex_availabilities' => $dexAvailabilitiesInvalidator,
            'reports' => $reportsInvalidator,
        ];

        if (!isset($map[$type])) {
            throw new \InvalidArgumentException("Invalid type '{$type}'");
        }

        $map[$type]();
    }
}
