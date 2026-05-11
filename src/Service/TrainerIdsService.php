<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenService;
use Symfony\Component\HttpFoundation\RequestStack;

class TrainerIdsService
{
    private bool $initialized = false;
    private ?string $loggedTrainerId = null;
    private ?string $trainerId = null;
    private ?string $requestedTrainerId = null;

    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly RequestStack $requestStack,
    ) {}

    public function init(): void
    {
        $this->initialized = true;
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $this->requestedTrainerId = $request->query->get('trainer_id', null);
        }

        try {
            $this->loggedTrainerId = $this->userTokenService->getLoggedUserToken();

            $this->trainerId = null !== $this->requestedTrainerId
                ? $this->requestedTrainerId
                : $this->loggedTrainerId;
        } catch (NoLoggedUserException) {
            $this->trainerId = null !== $this->requestedTrainerId
                ? $this->requestedTrainerId
                : null;
        }
    }

    public function getLoggedTrainerId(): ?string
    {
        if (!$this->initialized) {
            throw new \LogicException('init() must be called before getLoggedTrainerId()');
        }

        return $this->loggedTrainerId;
    }

    public function getTrainerId(): ?string
    {
        if (!$this->initialized) {
            throw new \LogicException('init() must be called before getTrainerId()');
        }

        return $this->trainerId;
    }
}
