<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Api\GetActionLogsApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/istration')]
final class AdminActionLogsController extends AbstractController
{
    public function __construct(
        private readonly GetActionLogsApiService $service,
        private readonly SerializerInterface $serializer
    ) {}

    #[Route('/action-logs', methods: ['GET'])]
    public function actionLogs(): JsonResponse
    {
        $data = $this->service->get();

        return JsonResponse::fromJsonString($this->serializer->serialize($data, 'json'));
    }
}
