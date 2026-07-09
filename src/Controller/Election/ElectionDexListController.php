<?php

declare(strict_types=1);

namespace App\Controller\Election;

use App\Security\UserTokenService;
use App\Service\Api\GetElectionDexListApiService;
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
        GetElectionDexListApiService $getDexListService,
        UserTokenService $userTokenService,
    ): JsonResponse {
        $trainerId = $userTokenService->getLoggedUserToken();

        switch (true) {
            case $this->isGranted('ROLE_ADMIN'):
                $dex = $getDexListService->getWithUnreleasedAndPremium($trainerId);

                break;

            case $this->isGranted('ROLE_COLLECTOR'):
                $dex = $getDexListService->getWithPremium($trainerId);

                break;

            default:
                $dex = $getDexListService->get($trainerId);

                break;
        }

        return new JsonResponse($dex, Response::HTTP_OK);
    }
}
