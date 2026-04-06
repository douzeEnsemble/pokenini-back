<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenService;
use Symfony\Component\HttpFoundation\RequestStack;

class TrainerIdsService
{
    private ?string $loggedTrainerId = null;
    private ?string $trainerId = null;
    private ?string $requestedTrainerId = null;

    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly RequestStack $requestStack,
    ) {}

    public function init(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $this->requestedTrainerId = $request->query->getAlnum('t');
        }

        try {
            $this->loggedTrainerId = $this->userTokenService->getLoggedUserToken();

            /** @psalm-suppress RiskyTruthyFalsyComparison */
            $this->trainerId = $this->requestedTrainerId ?: $this->loggedTrainerId;
        } catch (NoLoggedUserException $e) {
            $this->trainerId = $this->requestedTrainerId;
        }
    }

    public function getLoggedTrainerId(): ?string
    {
        return $this->loggedTrainerId;
    }

    public function getTrainerId(): ?string
    {
        return $this->trainerId;
    }
}
