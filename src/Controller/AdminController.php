<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Api\GetActionLogsService;
use App\Service\Api\GetReportsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/istration')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly GetReportsService $getReportsService,
        private readonly GetActionLogsService $getActionLogsService,
    ) {}

    #[Route('reports', methods: ['GET'])]
    public function reports(): JsonResponse
    {
        return new JsonResponse(
            $this->getReportsService->get(), 
            Response::HTTP_OK
        );
    }

    #[Route('action-logs', methods: ['GET'])]
    public function actionLogs(): JsonResponse
    {
        return new JsonResponse(
            $this->getActionLogsService->get(), 
            Response::HTTP_OK
        );
    }
}
