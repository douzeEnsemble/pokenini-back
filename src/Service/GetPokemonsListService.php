<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\ElectionPokemonsList;
use App\Security\UserTokenService;
use App\Service\Api\GetPokemonsApiService;

class GetPokemonsListService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly GetPokemonsApiService $getPokemonsService,
        private readonly int $electionCandidateCount,
    ) {}

    /**
     * @param string[]|string[][] $filters
     */
    public function get(string $dexSlug, string $electionSlug, array $filters): ElectionPokemonsList
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        return $this->getPokemonsService->get(
            $trainerId,
            $dexSlug,
            $electionSlug,
            $this->electionCandidateCount,
            $filters,
        );
    }
}
