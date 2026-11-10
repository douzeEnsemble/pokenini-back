<?php

declare(strict_types=1);

namespace App\Controller\Election;

use App\AlbumFilters\FromRequest;
use App\Controller\LangQueryTrait;
use App\Exception\DexNotFoundException;
use App\Service\GetElectionReportService;
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
    use LangQueryTrait;

    /**
     * @param string[] $locales the enabled locales
     */
    public function __construct(
        private readonly array $locales,
    ) {}

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
        GetElectionReportService $electionReportService,
        GetTrainerPokedexService $getTrainerPokedexService,
        Request $request,
        SerializerInterface $serializer,
        string $dexSlug,
        string $electionSlug = '',
    ): JsonResponse {
        $lang = $this->getLang($request, $this->locales);
        $filters = FromRequest::get($request);

        $report = $electionReportService->getReport($dexSlug, $electionSlug, $lang);
        $electionTop = $report['top'];

        $list = $getPokemonsListService->get($dexSlug, $electionSlug, $filters, $lang);
        $metrics = $report['metrics'];

        try {
            $pokedex = $getTrainerPokedexService->getPokedexData($dexSlug, $filters, $lang);
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
                    'metrics' => $metrics->raw + [
                        'round_count' => $metrics->roundCount,
                        'total_round_count' => $metrics->totalRoundCount,
                        'winner_average' => $metrics->winnerAverage,
                    ],
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
