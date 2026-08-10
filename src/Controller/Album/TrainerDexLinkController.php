<?php

declare(strict_types=1);

namespace App\Controller\Album;

use App\Exception\ApiValidationException;
use App\Service\FindDexBySlugService;
use App\Service\TrainerDexLinkService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/album_link')]
final class TrainerDexLinkController extends AbstractController
{
    public function __construct(
        private readonly FindDexBySlugService $findDexBySlugService,
        private readonly TrainerDexLinkService $trainerDexLinkService,
    ) {}

    #[Route('/{dexSlug}', methods: ['GET'])]
    #[IsGranted('ROLE_TRAINER')]
    public function list(string $dexSlug): Response
    {
        $notAccessible = $this->assertDexIsAccessible($dexSlug);
        if (null !== $notAccessible) {
            return $notAccessible;
        }

        return new JsonResponse($this->trainerDexLinkService->list($dexSlug), Response::HTTP_OK);
    }

    #[Route('/{dexSlug}', methods: ['POST'])]
    #[IsGranted('ROLE_TRAINER')]
    public function create(string $dexSlug, Request $request): Response
    {
        $notAccessible = $this->assertDexIsAccessible($dexSlug);
        if (null !== $notAccessible) {
            return $notAccessible;
        }

        $json = $request->getContent();

        if (!$json) {
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);
        }

        /** @var array{targetDexSlug?: mixed, bidirectional?: mixed} $content */
        $content = json_decode($json, true) ?? [];

        if (!isset($content['targetDexSlug']) || !\is_string($content['targetDexSlug'])) {
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);
        }

        $bidirectional = $content['bidirectional'] ?? false;

        if (!\is_bool($bidirectional)) {
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->trainerDexLinkService->create($dexSlug, $content['targetDexSlug'], $bidirectional);
        } catch (ApiValidationException $e) {
            return new JsonResponse([], $e->getStatusCode());
        }

        return new Response('', Response::HTTP_CREATED);
    }

    #[Route('/{linkId}', methods: ['DELETE'])]
    #[IsGranted('ROLE_TRAINER')]
    public function delete(string $linkId): Response
    {
        $this->trainerDexLinkService->delete($linkId);

        return new Response();
    }

    private function assertDexIsAccessible(string $dexSlug): ?JsonResponse
    {
        $dex = $this->findDexBySlugService->find($dexSlug);

        if (null === $dex) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        /** @var array{is_premium: bool} $flags */
        $flags = $dex['flags'];

        if ($flags['is_premium'] && !$this->isGranted('ROLE_COLLECTOR')) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        return null;
    }
}
