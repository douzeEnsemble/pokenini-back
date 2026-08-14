<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\AdminAction;
use App\Service\TriggerBannersPipelineService;
use App\Service\TriggerImagesPipelineService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\RateLimit;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration/action/trigger')]
final class AdminActionTriggerController extends AbstractController
{
    public function __construct(
        private readonly TriggerImagesPipelineService $triggerImagesPipelineService,
        private readonly TriggerBannersPipelineService $triggerBannersPipelineService,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route(
        '/{name}',
        methods: ['POST'],
        condition: "params['name'] in ['update_images', 'update_banners']"
    )]
    #[RateLimit(limiter: 'write_api', key: new Expression("request.headers.get('Authorization') ?? request.getClientIp() ?? 'unknown'"))]
    public function process(string $name): JsonResponse
    {
        $state = 'ok';
        $error = '';

        try {
            if ('update_images' === $name) {
                $this->triggerImagesPipelineService->triggerUpdateImages();
            } else {
                $this->triggerBannersPipelineService->triggerUpdateBanners();
            }

            $this->logger->info("Admin action succeeded: trigger {$name}");
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            $state = 'ko';
            $error = $e->getMessage();

            $this->logger->critical(
                $e->getMessage(),
                [
                    'name' => $name,
                    'action' => 'trigger',
                ]
            );
        }

        $adminAction = new AdminAction('trigger', $name, $state, '', $error);

        $statusCode = 'ko' === $state
            ? Response::HTTP_INTERNAL_SERVER_ERROR
            : Response::HTTP_ACCEPTED;

        return new JsonResponse($adminAction, $statusCode);
    }
}
