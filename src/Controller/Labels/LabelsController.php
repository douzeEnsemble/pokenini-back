<?php

declare(strict_types=1);

namespace App\Controller\Labels;

use App\Controller\LangQueryTrait;
use App\Exception\InvalidLangException;
use App\Service\Api\GetLabelsApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/labels')]
final class LabelsController extends AbstractController
{
    use LangQueryTrait;

    /**
     * @param string[] $locales the enabled locales
     */
    public function __construct(
        private readonly GetLabelsApiService $getLabelsService,
        private readonly array $locales,
    ) {}

    /**
     * Without "lang", each label has its name in English and in French (legacy shape).
     * With "lang", each label has a single name, in that language.
     */
    #[Route('', methods: ['GET'])]
    public function get(Request $request): JsonResponse
    {
        try {
            $lang = $this->getLang($request, $this->locales);
        } catch (InvalidLangException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(
            [
                'catch_states' => $this->getLabelsService->getCatchStates($lang),
                'types' => $this->getLabelsService->getTypes($lang),
                'category_forms' => $this->getLabelsService->getFormsCategory($lang),
                'regional_forms' => $this->getLabelsService->getFormsRegional($lang),
                'special_forms' => $this->getLabelsService->getFormsSpecial($lang),
                'variant_forms' => $this->getLabelsService->getFormsVariant($lang),
                'game_bundles' => $this->getLabelsService->getGameBundles($lang),
                'collections' => $this->getLabelsService->getCollections($lang),
            ],
            Response::HTTP_OK
        );
    }
}
