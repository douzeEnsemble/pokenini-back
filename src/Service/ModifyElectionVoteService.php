<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\ElectionVote;
use App\Security\UserTokenService;
use App\Service\Api\ModifyElectionVoteApiService;
use App\Service\CacheInvalidator\ElectionCacheInvalidatorService;

class ModifyElectionVoteService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly ModifyElectionVoteApiService $apiService,
        private readonly ElectionCacheInvalidatorService $electionCacheInvalidatorService,
    ) {}

    public function vote(ElectionVote $electionVote): void
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        $this->apiService->vote($trainerId, $electionVote);

        $this->electionCacheInvalidatorService->invalidate($trainerId);
    }
}
