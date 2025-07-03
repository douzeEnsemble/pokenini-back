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

    #[Route('/all', methods: ['GET'])]
    public function getAll(): JsonResponse
    {
        return new JsonResponse(
            [
                'catchStates' => $this->getLabelsService->getCatchStates(),
                'types' => $this->getLabelsService->getTypes(),
                'categoryForms' => $this->getLabelsService->getFormsCategory(),
                'regionalForms' => $this->getLabelsService->getFormsRegional(),
                'specialForms' => $this->getLabelsService->getFormsSpecial(),
                'variantForms' => $this->getLabelsService->getFormsVariant(),
                'gameBundles' => $this->getLabelsService->getGameBundles(),
                'collections' => $this->getLabelsService->getCollections(),
            ],
            Response::HTTP_OK
        );
    }
}
