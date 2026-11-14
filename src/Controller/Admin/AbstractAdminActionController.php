<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\AdminAction;
use App\Exception\ActionAlreadyRunningException;
use App\Service\Api\AdminActionApiService;
use App\Service\Api\GetActionCatalogApiService;
use App\Service\CacheInvalidatorService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAdminActionController extends AbstractController
{
    public function __construct(
        private readonly CacheInvalidatorService $cacheInvalidatorService,
        private readonly AdminActionApiService $adminActionService,
        private readonly LoggerInterface $logger,
        private readonly GetActionCatalogApiService $actionCatalog,
    ) {}

    abstract public function process(string $name): JsonResponse;

    protected function execute(
        string $name,
        string $action,
    ): JsonResponse {
        if (\in_array($action, ['update', 'calculate'], true) && !$this->actionCatalog->has($action, $name)) {
            throw $this->createNotFoundException("Unknown action: {$action} {$name}");
        }

        $state = 'ok';
        $content = '';
        $error = '';
        $statusCode = null;

        try {
            $this->doAction($name, $action);
            $this->logger->info("Admin action succeeded: {$action} {$name}");
        } catch (ActionAlreadyRunningException $e) {
            $state = 'ko';
            $statusCode = Response::HTTP_CONFLICT;

            $error = $e->getMessage();

            $this->logger->warning(
                "Admin action refused, already running: {$action} {$name}"
            );
        } catch (\InvalidArgumentException|\RuntimeException $e) {
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

        $statusCode ??= 'ko' === $state
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
