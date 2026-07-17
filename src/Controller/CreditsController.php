<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Api\GetCreditsApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/credits')]
final class CreditsController extends AbstractController
{
    public function __construct(
        private readonly GetCreditsApiService $service,
    ) {}

    #[Route('', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return new JsonResponse(
            $this->service->get(),
            Response::HTTP_OK
        );
    }
}
