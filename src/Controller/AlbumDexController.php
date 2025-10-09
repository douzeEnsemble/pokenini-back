<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenService;
use App\Service\Api\GetDexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/album')]
class AlbumDexController extends AbstractController
{
    public function __construct() {}

    #[Route(
        '/dex',
        methods: ['GET']
    )]
    public function index(
        GetDexService $getDexService,
        UserTokenService $userTokenService,
    ): JsonResponse {
        try {
            $connectedUserId = $userTokenService->getLoggedUserToken();
        } catch (NoLoggedUserException $e) {
            return new JsonResponse([], Response::HTTP_UNAUTHORIZED);
        }

        $dex = $this->isGranted('ROLE_ADMIN')
            ? $getDexService->getWithUnreleasedAndPremium($connectedUserId)
            : $getDexService->getWithPremium($connectedUserId);

        return new JsonResponse($dex, Response::HTTP_OK);
    }
}
