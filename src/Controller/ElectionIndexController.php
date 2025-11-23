<?php

declare(strict_types=1);

namespace App\Controller;

use App\AlbumFilters\FromRequest;
use App\AlbumFilters\Mapping;
use App\Service\ElectionMetricsService;
use App\Service\ElectionTopService;
use App\Service\GetPokemonsListService;
use App\Service\GetTrainerPokedexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/election')]
class ElectionIndexController extends AbstractController
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
        ElectionTopService $electionTopService,
        ElectionMetricsService $metricsService,
        GetTrainerPokedexService $getTrainerPokedexService,
        Request $request,
        string $dexSlug,
        string $electionSlug = '',
    ): JsonResponse {
        $filters = FromRequest::get($request);
        $apiFilters = Mapping::get($filters);

        $electionTop = $electionTopService->getTop($dexSlug, $electionSlug);

        $list = $getPokemonsListService->get($dexSlug, $electionSlug, $apiFilters);
        $metrics = $metricsService->getMetrics($dexSlug, $electionSlug);
        $pokedex = $getTrainerPokedexService->getPokedexData($dexSlug, $apiFilters);

        $detachedCount = 0;
        foreach ($electionTop as $pokemon) {
            if ($pokemon['significance']) {
                ++$detachedCount;
            }
        }

        $isTheLastPage = 0 === $metrics->underMaxViewCount && $metrics->maxViewCount === count($list->items);
        $isTheLastOne = $isTheLastPage && 1 === $metrics->maxViewCount;

        return new JsonResponse(
            [
                'type' => $list->type,
                'pokemons' => $list->items,
                'pokedex' => $pokedex,
                'electionTop' => $electionTop,
                'metrics' => $metrics,
                'detachedCount' => $detachedCount,
                'isTheLastOne' => $isTheLastOne,
                'isTheLastPage' => $isTheLastPage,
            ],
            Response::HTTP_OK,
        );
    }
}
