<?php

declare(strict_types=1);

namespace App\Controller\Album;

use App\AlbumFilters\FromRequest;
use App\Exception\DexNotFoundException;
use App\Security\User;
use App\Service\GetTrainerPokedexService;
use App\Service\TrainerIdsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/album')]
final class AlbumPokedexController extends AbstractController
{
    public function __construct(
        private readonly TrainerIdsService $trainerIdsService,
        private readonly GetTrainerPokedexService $getTrainerPokedexService,
    ) {}

    #[Route('/{dexSlug}', methods: ['GET'])]
    public function get(
        Request $request,
        string $dexSlug,
    ): JsonResponse {
        $trainerId = $this->trainerIdsService->getTrainerId();

        if (null === $trainerId) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        $filters = FromRequest::get($request);

        try {
            $pokedex = $this->getTrainerPokedexService->getPokedexDataByTrainerId($dexSlug, $filters, $trainerId);
        } catch (DexNotFoundException) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        if (!$this->accessDexIsGranted($pokedex['dex'])) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(
            [
                'pokedex' => $pokedex,
                'filters' => $filters,
            ],
            Response::HTTP_OK
        );
    }

    /**
     * @param string[]|string[][] $dex
     */
    private function accessDexIsGranted(array $dex): bool
    {
        if ($dex['is_private']
            && $this->trainerIdsService->getTrainerId() !== $this->trainerIdsService->getLoggedTrainerId()
        ) {
            return false;
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$dex['is_released'] && !$user->isAnAdmin()) {
            return false;
        }

        return true;
    }
}
