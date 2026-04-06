<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\DexNotFoundException;
use App\Security\UserTokenService;
use App\Service\Api\GetPokedexService;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GetTrainerPokedexService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly GetPokedexService $getPokedexService,
    ) {}

    /**
     * @param string[]|string[][] $filters
     *
     * @return string[][]
     */
    public function getPokedexData(string $dexSlug, array $filters): array
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        return $this->getPokedexDataByTrainerId($dexSlug, $filters, $trainerId);
    }

    /**
     * @param string[]|string[][] $filters
     *
     * @return string[][]
     */
    public function getPokedexDataByTrainerId(string $dexSlug, array $filters, string $trainerId): array
    {
        try {
            $data = $this->getPokedexService->get($dexSlug, $trainerId, $filters);

            /** @psalm-suppress RiskyTruthyFalsyComparison */
            if (empty($data['dex'])) {
                throw new DexNotFoundException();
            }

            return $data;
        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
            throw new DexNotFoundException();
        }
    }
}
