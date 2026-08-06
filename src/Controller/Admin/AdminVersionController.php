<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Api\GetVersionApiService;
use App\Service\LocalVersionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminVersionController extends AbstractController
{
    public function __construct(
        private readonly LocalVersionService $localVersionService,
        private readonly GetVersionApiService $getVersionApiService,
    ) {}

    #[Route('/version', methods: ['GET'])]
    public function version(): JsonResponse
    {
        return $this->json([
            'back' => [
                'version' => $this->localVersionService->getVersion(),
                'updated_at' => $this->localVersionService->getUpdatedAt(),
            ],
            'api' => $this->getVersionApiService->get(),
        ]);
    }
}
