<?php

declare(strict_types=1);

namespace App\Service;

use App\Security\UserTokenService;
use App\Service\Api\GetElectionTopApiService;

class GetElectionTopService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly GetElectionTopApiService $apiService,
        private readonly int $topCount,
    ) {}

    /**
     * @return string[][]
     */
    public function getTop(string $dexSlug, string $electionSlug): array
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        return $this->apiService->getTop($trainerId, $dexSlug, $electionSlug, $this->topCount);
    }
}
