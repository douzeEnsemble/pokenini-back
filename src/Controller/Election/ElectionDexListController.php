<?php

declare(strict_types=1);

namespace App\Controller\Election;

use App\Controller\LangQueryTrait;
use App\Security\UserTokenService;
use App\Service\GetElectionDexListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/election')]
final class ElectionDexListController extends AbstractController
{
    use LangQueryTrait;

    /**
     * @param string[] $locales the enabled locales
     */
    public function __construct(
        private readonly array $locales,
    ) {}

    #[Route(
        '/dex',
        methods: ['GET'],
        priority: 2,
    )]
    public function index(
        GetElectionDexListService $getDexListService,
        UserTokenService $userTokenService,
        Request $request,
    ): JsonResponse {
        $lang = $this->getLang($request, $this->locales);
        $trainerId = $userTokenService->getLoggedUserToken();

        switch (true) {
            case $this->isGranted('ROLE_ADMIN'):
                $dex = $getDexListService->getWithUnreleasedAndPremium($trainerId, $lang);

                break;

            case $this->isGranted('ROLE_COLLECTOR'):
                $dex = $getDexListService->getWithPremium($trainerId, $lang);

                break;

            default:
                $dex = $getDexListService->get($trainerId, $lang);

                break;
        }

        return new JsonResponse($dex, Response::HTTP_OK);
    }
}
