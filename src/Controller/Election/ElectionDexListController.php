<?php

declare(strict_types=1);

namespace App\Controller\Election;

use App\Service\Api\ElectionDexListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/election')]
final class ElectionDexListController extends AbstractController
{
    #[Route(
        '/dex',
        methods: ['GET'],
        priority: 2,
    )]
    public function index(
        ElectionDexListService $getDexService,
    ): JsonResponse {
        switch (true) {
            case $this->isGranted('ROLE_ADMIN'):
                $dex = $getDexService->getWithUnreleasedAndPremium();

                break;

            case $this->isGranted('ROLE_COLLECTOR'):
                $dex = $getDexService->getWithPremium();

                break;

            default:
                $dex = $getDexService->get();

                break;
        }

        return new JsonResponse($dex, Response::HTTP_OK);
    }
}
