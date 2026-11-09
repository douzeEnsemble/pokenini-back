<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\DexNotFoundException;
use App\Security\UserTokenService;
use App\Service\Api\GetPokedexApiService;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GetTrainerPokedexService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly GetPokedexApiService $getPokedexService,
    ) {}

    /**
     * @param string[]|string[][] $filters
     * @param null|string         $lang    the language of the names, null for the legacy ones (English and French)
     *
     * @return array{
     *   dex: array<string, mixed>,
     *   pokemons: array<int, array<string, mixed>>,
     *   report?: array<string, mixed>,
     *   filtered_report?: array<string, mixed>,
     * }
     */
    public function getPokedexData(string $dexSlug, array $filters, ?string $lang = null): array
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        return $this->getPokedexDataByTrainerId($dexSlug, $filters, $trainerId, $lang);
    }

    /**
     * @param string[]|string[][] $filters
     * @param null|string         $lang    the language of the names, null for the legacy ones (English and French)
     *
     * @return array{
     *   dex: array<string, mixed>,
     *   pokemons: array<int, array<string, mixed>>,
     *   report?: array<string, mixed>,
     *   filtered_report?: array<string, mixed>,
     * }
     */
    public function getPokedexDataByTrainerId(string $dexSlug, array $filters, string $trainerId, ?string $lang = null): array
    {
        try {
            $data = $this->getPokedexService->get($dexSlug, $trainerId, $filters, $lang);

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
