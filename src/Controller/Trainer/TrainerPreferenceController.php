<?php

declare(strict_types=1);

namespace App\Controller\Trainer;

use App\Exception\ApiValidationException;
use App\Service\TrainerPreferenceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\RateLimit;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trainer/preference')]
final class TrainerPreferenceController extends AbstractController
{
    public function __construct(
        private readonly TrainerPreferenceService $service,
    ) {}

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_TRAINER')]
    public function get(): Response
    {
        return new JsonResponse($this->service->get(), Response::HTTP_OK);
    }

    #[Route('', methods: ['PUT'])]
    #[IsGranted('ROLE_TRAINER')]
    #[RateLimit(limiter: 'write_api', key: new Expression("request.headers.get('Authorization') ?? request.getClientIp() ?? 'unknown'"))]
    public function modify(Request $request): Response
    {
        /** @var array{home_page?: mixed} $content */
        $content = json_decode($request->getContent(), true) ?? [];

        if (!isset($content['home_page']) || !\is_string($content['home_page'])) {
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->service->modifyHomePage($content['home_page']);
        } catch (ApiValidationException $e) {
            return new JsonResponse([], $e->getStatusCode());
        }

        return new Response();
    }
}
