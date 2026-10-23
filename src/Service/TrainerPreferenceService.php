<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ApiValidationException;
use App\Security\UserTokenService;
use App\Service\Api\TrainerPreferenceApiService;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

class TrainerPreferenceService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly TrainerPreferenceApiService $apiService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        return $this->apiService->get($this->userTokenService->getLoggedUserToken());
    }

    public function modifyHomePage(string $homePage): void
    {
        try {
            $this->apiService->modifyHomePage($homePage, $this->userTokenService->getLoggedUserToken());
        } catch (HttpExceptionInterface $e) {
            throw new ApiValidationException($e->getResponse()->getStatusCode());
        }
    }
}
