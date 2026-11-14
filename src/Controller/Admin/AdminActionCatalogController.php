<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Api\GetActionCatalogApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminActionCatalogController extends AbstractController
{
    public function __construct(
        private readonly GetActionCatalogApiService $service,
    ) {}

    #[Route('/actions', methods: ['GET'])]
    public function actions(): JsonResponse
    {
        return new JsonResponse($this->service->get());
    }
}
