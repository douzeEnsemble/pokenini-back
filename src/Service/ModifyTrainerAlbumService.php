<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ModifyFailedException;
use App\Security\UserTokenService;
use App\Service\Api\ModifyAlbumApiService;
use App\Service\CacheInvalidator\AlbumCacheInvalidatorService;
use App\Service\CacheInvalidator\AlbumsCacheInvalidatorService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ModifyTrainerAlbumService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly ModifyAlbumApiService $modifyAlbumService,
        private readonly AlbumsCacheInvalidatorService $albumsCacheInvalidatorService,
        private readonly AlbumCacheInvalidatorService $albumCacheInvalidatorService,
        private readonly RequestStack $requestStack,
    ) {}

    public function modifyAlbum(
        string $dexSlug,
        string $pokemonSlug,
        string $content,
    ): void {
        $trainerId = $this->userTokenService->getLoggedUserToken();
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            throw new ModifyFailedException();
        }

        try {
            $updatedDexSlugs = $this->modifyAlbumService->modify(
                $request->getMethod(),
                $dexSlug,
                $pokemonSlug,
                $content,
                $trainerId
            );

            $this->albumsCacheInvalidatorService->invalidate();

            foreach ($updatedDexSlugs as $updatedDexSlug) {
                $this->albumCacheInvalidatorService->invalidate($updatedDexSlug, $trainerId);
            }
        } catch (HttpExceptionInterface|TransportExceptionInterface|\JsonException $e) {
            throw new ModifyFailedException();
        }
    }
}
