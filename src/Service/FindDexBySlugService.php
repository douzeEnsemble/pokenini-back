<?php

declare(strict_types=1);

namespace App\Service;

use App\Security\UserTokenService;
use App\Service\Api\GetDexListApiService;

class FindDexBySlugService
{
    public function __construct(
        private readonly GetDexListApiService $getDexListService,
        private readonly UserTokenService $userTokenService,
    ) {}

    /**
     * @return null|array<string, mixed>
     */
    public function find(string $dexSlug): ?array
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        /** @var array<int, array<string, mixed>> $dexList */
        $dexList = $this->getDexListService->getWithUnreleasedAndPremium($trainerId);

        foreach ($dexList as $dex) {
            if (($dex['slug'] ?? null) === $dexSlug) {
                return $dex;
            }
        }

        return null;
    }
}
