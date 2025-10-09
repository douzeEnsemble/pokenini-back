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

    #[Route('/catch_states', methods: ['GET'])]
    public function getCatchStates(): JsonResponse
    {
        return new JsonResponse(
            $this->getLabelsService->getCatchStates(),
            Response::HTTP_OK
        );
    }

    #[Route('/types', methods: ['GET'])]
    public function getTypes(): JsonResponse
    {
        return new JsonResponse(
            $this->getLabelsService->getTypes(),
            Response::HTTP_OK
        );
    }

    #[Route('/forms/category', methods: ['GET'])]
    public function getFormsCategory(): JsonResponse
    {
        return new JsonResponse(
            $this->getLabelsService->getFormsCategory(),
            Response::HTTP_OK
        );
    }

    #[Route('/forms/regional', methods: ['GET'])]
    public function getFormsRegional(): JsonResponse
    {
        return new JsonResponse(
            $this->getLabelsService->getFormsRegional(),
            Response::HTTP_OK
        );
    }

    #[Route('/forms/special', methods: ['GET'])]
    public function getFormsSpecial(): JsonResponse
    {
        return new JsonResponse(
            $this->getLabelsService->getFormsSpecial(),
            Response::HTTP_OK
        );
    }

    #[Route('/forms/variant', methods: ['GET'])]
    public function getFormsVariant(): JsonResponse
    {
        return new JsonResponse(
            $this->getLabelsService->getFormsVariant(),
            Response::HTTP_OK
        );
    }

    #[Route('/game_bundles', methods: ['GET'])]
    public function getGameBundles(): JsonResponse
    {
        return new JsonResponse(
            $this->getLabelsService->getGameBundles(),
            Response::HTTP_OK
        );
    }

    #[Route('/collections', methods: ['GET'])]
    public function getCollections(): JsonResponse
    {
        return new JsonResponse(
            $this->getLabelsService->getCollections(),
            Response::HTTP_OK
        );
    }
}
