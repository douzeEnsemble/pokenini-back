<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Api\GetLabelsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/labels')]
class LabelsController extends AbstractController
{
    public function __construct(
        private readonly GetLabelsService $getLabelsService,
    ) {}

    #[Route('', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return new JsonResponse(
            [
                'catch_states' => $this->getLabelsService->getCatchStates(),
                'types' => $this->getLabelsService->getTypes(),
                'category_forms' => $this->getLabelsService->getFormsCategory(),
                'regional_forms' => $this->getLabelsService->getFormsRegional(),
                'special_forms' => $this->getLabelsService->getFormsSpecial(),
                'variant_forms' => $this->getLabelsService->getFormsVariant(),
                'game_bundles' => $this->getLabelsService->getGameBundles(),
                'collections' => $this->getLabelsService->getCollections(),
            ],
            Response::HTTP_OK
        );
    }
}
