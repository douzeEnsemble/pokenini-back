<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ApiValidationException;
use App\Security\UserTokenService;
use App\Service\Api\TrainerDexLinkApiService;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

class TrainerDexLinkService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly TrainerDexLinkApiService $apiService,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(string $dexSlug): array
    {
        return $this->apiService->list($dexSlug, $this->userTokenService->getLoggedUserToken());
    }

    public function create(string $dexSlug, string $targetDexSlug, bool $bidirectional): void
    {
        try {
            $this->apiService->create($dexSlug, $targetDexSlug, $bidirectional, $this->userTokenService->getLoggedUserToken());
        } catch (HttpExceptionInterface $e) {
            throw new ApiValidationException($e->getResponse()->getStatusCode());
        }
    }

    public function delete(string $linkId): void
    {
        $this->apiService->delete($linkId, $this->userTokenService->getLoggedUserToken());
    }
}
