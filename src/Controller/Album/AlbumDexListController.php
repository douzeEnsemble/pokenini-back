<?php

declare(strict_types=1);

namespace App\Controller\Album;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenService;
use App\Service\Api\GetDexListApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/album')]
final class AlbumDexListController extends AbstractController
{
    #[Route(
        '/dex',
        methods: ['GET'],
        priority: 2,
    )]
    public function index(
        GetDexListApiService $getDexListService,
        UserTokenService $userTokenService,
        Request $request,
    ): JsonResponse {
        $trainerId = $request->query->getAlnum('trainer_id', '');

        if ('' === $trainerId) {
            try {
                $trainerId = $userTokenService->getLoggedUserToken();
            } catch (NoLoggedUserException) {
                return new JsonResponse([], Response::HTTP_UNAUTHORIZED);
            }
        }

        $dex = $this->isGranted('ROLE_ADMIN')
            ? $getDexListService->getWithUnreleasedAndPremium($trainerId)
            : $getDexListService->getWithPremium($trainerId);

        return new JsonResponse($dex, Response::HTTP_OK);
    }
}
