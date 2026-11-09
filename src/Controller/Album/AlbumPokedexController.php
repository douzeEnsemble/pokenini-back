<?php

declare(strict_types=1);

namespace App\Controller\Album;

use App\AlbumFilters\FromRequest;
use App\Controller\LangQueryTrait;
use App\Exception\DexNotFoundException;
use App\Exception\InvalidLangException;
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
    use LangQueryTrait;

    /**
     * @param string[] $locales the enabled locales
     */
    public function __construct(
        private readonly TrainerIdsService $trainerIdsService,
        private readonly GetTrainerPokedexService $getTrainerPokedexService,
        private readonly array $locales,
    ) {}

    /**
     * Without "lang", the names are in English and in French (legacy shape). With "lang", the names of the
     * Pokémon are in every language and the others in that language.
     */
    #[Route('/{dexSlug}', methods: ['GET'])]
    public function get(
        Request $request,
        string $dexSlug,
    ): JsonResponse {
        $trainerId = $this->trainerIdsService->getTrainerId();

        if (null === $trainerId) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        try {
            $lang = $this->getLang($request, $this->locales);
        } catch (InvalidLangException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $filters = FromRequest::get($request);

        try {
            $pokedex = $this->getTrainerPokedexService->getPokedexDataByTrainerId($dexSlug, $filters, $trainerId, $lang);
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
     * @param array<string, mixed> $dex
     */
    private function accessDexIsGranted(array $dex): bool
    {
        /** @var array{is_private: bool, is_released: bool} $flags */
        $flags = $dex['flags'];

        if ($flags['is_private']
            && $this->trainerIdsService->getTrainerId() !== $this->trainerIdsService->getLoggedTrainerId()
        ) {
            return false;
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$flags['is_released'] && !$user->isAnAdmin()) {
            return false;
        }

        return true;
    }
}
