<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\AdminAction;
use App\Service\Api\AdminActionService;
use App\Service\CacheInvalidatorService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAdminActionController extends AbstractController
{
    public function __construct(
        private readonly CacheInvalidatorService $cacheInvalidatorService,
        private readonly AdminActionService $adminActionService,
        private readonly LoggerInterface $logger,
    ) {}

    abstract public function process(string $name): JsonResponse;

    protected function execute(
        string $name,
        string $action,
    ): JsonResponse {
        $state = 'ok';
        $content = '';
        $error = '';

        try {
            $this->doAction($name, $action);
        } catch (\Exception $e) {
            $state = 'ko';

            $error = $e->getMessage();

            $this->logger->critical(
                $e->getMessage(),
                [
                    'name' => $name,
                    'action' => $action,
                ]
            );
        }

        $adminAction = new AdminAction(
            $action,
            $name,
            $state,
            $content,
            $error
        );

        $statusCode = 'ko' === $state
            ? Response::HTTP_INTERNAL_SERVER_ERROR
            : Response::HTTP_ACCEPTED;

        return new JsonResponse($adminAction, $statusCode);
    }

    private function doAction(
        string $name,
        string $action,
    ): void {
        switch ($action) {
            case 'update':
                $this->adminActionService->update($name);

                break;

            case 'calculate':
                $this->adminActionService->calculate($name);

                break;
        }

        $this->cacheInvalidatorService->invalidate($name);
    }
}
