<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Api\GetReportsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/istration')]
final class AdminReportsController extends AbstractController
{
    public function __construct(
        private readonly GetReportsService $service,
        private readonly SerializerInterface $serializer
    ) {}

    #[Route('/reports', methods: ['GET'])]
    public function reports(): JsonResponse
    {
        $data = $this->service->get();

        return JsonResponse::fromJsonString($this->serializer->serialize($data, 'json'));
    }
}
