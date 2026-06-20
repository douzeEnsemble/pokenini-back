<?php

declare(strict_types=1);

namespace App\Controller\Election;

use App\AlbumFilters\FromRequest;
use App\Exception\DexNotFoundException;
use App\Service\GetElectionMetricsService;
use App\Service\GetElectionTopService;
use App\Service\GetPokemonsListService;
use App\Service\GetTrainerPokedexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/election')]
final class ElectionIndexController extends AbstractController
{
    #[Route(
        '/{dexSlug}/{electionSlug}',
        requirements: [
            'dexSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
            'electionSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
        ],
        methods: ['GET']
    )]
    public function index(
        GetPokemonsListService $getPokemonsListService,
        GetElectionTopService $electionTopService,
        GetElectionMetricsService $metricsService,
        GetTrainerPokedexService $getTrainerPokedexService,
        Request $request,
        SerializerInterface $serializer,
        string $dexSlug,
        string $electionSlug = '',
    ): JsonResponse {
        $filters = FromRequest::get($request);

        $electionTop = $electionTopService->getTop($dexSlug, $electionSlug);

        $list = $getPokemonsListService->get($dexSlug, $electionSlug, $filters);
        $metrics = $metricsService->getMetrics($dexSlug, $electionSlug);

        try {
            $pokedex = $getTrainerPokedexService->getPokedexData($dexSlug, $filters);
        } catch (DexNotFoundException $e) {
            $pokedex = null;
        }

        $detachedCount = 0;
        foreach ($electionTop as $pokemon) {
            if ($pokemon['score']['significance']) {
                ++$detachedCount;
            }
        }

        $isTheLastPage = 0 === $metrics->underMaxViewCount && $metrics->maxViewCount === count($list->items);
        $isTheLastOne = $isTheLastPage && 1 === $metrics->maxViewCount;

        return new JsonResponse(
            $serializer->serialize(
                [
                    'type' => $list->type,
                    'pokemons' => $list->items,
                    'pokedex' => $pokedex,
                    'election_top' => $electionTop,
                    'metrics' => $metrics,
                    'detached_count' => $detachedCount,
                    'is_the_last_one' => $isTheLastOne,
                    'is_the_last_page' => $isTheLastPage,
                ],
                'json',
            ),
            Response::HTTP_OK,
            [],
            true,
        );
    }
}
