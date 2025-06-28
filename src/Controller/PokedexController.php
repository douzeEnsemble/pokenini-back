<?php

declare(strict_types=1);

namespace App\Controller;

use App\AlbumFilters\FromRequest;
use App\AlbumFilters\Mapping;
use App\Service\GetTrainerPokedexService;
use App\Service\TrainerIdsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/pokedex')]
class PokedexController extends AbstractController
{
    public function __construct(
        private readonly TrainerIdsService $trainerIdsService,
        private readonly GetTrainerPokedexService $getTrainerPokedexService,
    ) {}

    #[Route('/', methods: ['GET'])]
    public function get(
        Request $request,
        string $dexSlug,
    ): JsonResponse {
        $this->trainerIdsService->init();

        $trainerId = $this->trainerIdsService->getTrainerId();

        if (!$trainerId) {
            throw $this->createNotFoundException();
        }

        $filters = FromRequest::get($request);
        $apiFilters = Mapping::get($filters);
        
        $pokedex = $this->getTrainerPokedexService->getPokedexDataByTrainerId($dexSlug, $apiFilters, $trainerId);
        if (null === $pokedex || !isset($pokedex['dex']) || empty($pokedex['dex'])) {
            throw $this->createNotFoundException();
        }

        if (!$this->accessDexIsGranted($pokedex['dex'])) {
            throw $this->createNotFoundException();
        }

        return new JsonResponse($pokedex, Response::HTTP_OK);
    }

    /**
     * @param string[]|string[][] $dex
     */
    private function accessDexIsGranted(array $dex): bool
    {
        if ($dex['is_private']
            && $this->trainerIdsService->getTrainerId() != $this->trainerIdsService->getLoggedTrainerId()
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
