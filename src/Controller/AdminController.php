<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Api\GetActionLogsService;
use App\Service\Api\GetReportsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/istration')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly GetReportsService $getReportsService,
        private readonly GetActionLogsService $getActionLogsService,
        private readonly SerializerInterface $serializer
    ) {}

    #[Route('/reports', methods: ['GET'])]
    public function reports(): JsonResponse
    {
        $data = $this->getReportsService->get();

        return JsonResponse::fromJsonString($this->serializer->serialize($data, 'json'));
    }

    #[Route('/action-logs', methods: ['GET'])]
    public function actionLogs(): JsonResponse
    {
        $data = $this->getActionLogsService->get();

        return JsonResponse::fromJsonString($this->serializer->serialize($data, 'json'));
    }
}
