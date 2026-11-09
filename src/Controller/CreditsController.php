<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\InvalidLangException;
use App\Service\Api\GetCreditsApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/credits')]
final class CreditsController extends AbstractController
{
    use LangQueryTrait;

    /**
     * @param string[] $locales the enabled locales
     */
    public function __construct(
        private readonly GetCreditsApiService $service,
        private readonly array $locales,
    ) {}

    /**
     * Without "lang", each Pokémon has its name in English and in French (legacy shape).
     * With "lang", each Pokémon has a single name, in that language.
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
            $this->service->get($lang),
            Response::HTTP_OK
        );
    }
}
