<?php

declare(strict_types=1);

namespace App\Controller\Album;

use App\Exception\DexNotFoundException;
use App\Exception\EmptyContentException;
use App\Exception\InvalidJsonException;
use App\Exception\ModifyFailedException;
use App\Service\GetTrainerPokedexService;
use App\Service\ModifyTrainerAlbumService;
use App\Service\RequestedContentService;
use App\Validator\CatchStates;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\RateLimit;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/album')]
final class AlbumUpsertController extends AbstractController
{
    public function __construct(
        private readonly RequestedContentService $requestedContentService,
        private readonly GetTrainerPokedexService $getTrainerPokedexService,
        private readonly ModifyTrainerAlbumService $modifyTrainerAlbumService,
    ) {}

    #[Route('/{dexSlug}/{pokemonSlug}', methods: ['PATCH', 'PUT'])]
    #[IsGranted('ROLE_TRAINER')]
    #[RateLimit(limiter: 'write_api', key: new Expression("request.headers.get('Authorization') ?? request.getClientIp() ?? 'unknown'"))]
    public function upsert(
        string $dexSlug,
        string $pokemonSlug,
    ): Response {
        try {
            $content = $this->requestedContentService->getContent(new CatchStates());
        } catch (EmptyContentException|InvalidJsonException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        try {
            $pokedex = $this->getTrainerPokedexService->getPokedexData($dexSlug, []);
        } catch (DexNotFoundException $e) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        $dex = $pokedex['dex'];

        /** @var array{is_premium: bool} $flags */
        $flags = $dex['flags'];

        if ($flags['is_premium'] && !$this->isGranted('ROLE_COLLECTOR')) {
            return new JsonResponse([], 404);
        }

        try {
            $this->modifyTrainerAlbumService->modifyAlbum(
                $dexSlug,
                $pokemonSlug,
                $content,
            );
        } catch (ModifyFailedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

        return new Response();
    }
}
