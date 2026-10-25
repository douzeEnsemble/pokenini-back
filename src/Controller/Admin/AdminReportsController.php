<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Api\GetGameReportsApiService;
use App\Service\Api\GetReportsApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/istration')]
final class AdminReportsController extends AbstractController
{
    public function __construct(
        private readonly GetReportsApiService $service,
        private readonly SerializerInterface $serializer,
        private readonly GetGameReportsApiService $gameReportsService,
    ) {}

    #[Route('/reports', methods: ['GET'])]
    public function reports(): JsonResponse
    {
        $data = $this->service->get();

        return JsonResponse::fromJsonString($this->serializer->serialize($data, 'json'));
    }

    #[Route('/reports/games', methods: ['GET'])]
    public function gameReports(): JsonResponse
    {
        $data = $this->gameReportsService->get();

        return JsonResponse::fromJsonString($this->serializer->serialize($data, 'json'));
    }
}
