<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Api\GetActionLogsApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminActionLogsController extends AbstractController
{
    public function __construct(
        private readonly GetActionLogsApiService $service,
    ) {}

    #[Route('/action-logs', methods: ['GET'])]
    public function actionLogs(): JsonResponse
    {
        return new JsonResponse($this->service->get());
    }
}
