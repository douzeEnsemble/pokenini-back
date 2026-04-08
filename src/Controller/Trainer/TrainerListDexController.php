<?php

declare(strict_types=1);

namespace App\Controller\Trainer;

use App\Security\UserTokenService;
use App\Service\Api\GetDexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trainer')]
final class TrainerListDexController extends AbstractController
{
    public function __construct(
        private readonly GetDexService $service,
        private readonly UserTokenService $userTokenService,
    ) {}

    #[Route('/dex', methods: ['GET'])]
    #[IsGranted('ROLE_TRAINER')]
    public function index(): Response
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        $dex = $this->service->getWithUnreleasedAndPremium($trainerId);

        return new JsonResponse($dex, Response::HTTP_OK);
    }
}
