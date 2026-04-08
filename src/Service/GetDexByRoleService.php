<?php

declare(strict_types=1);

namespace App\Service;

use App\Security\User;
use App\Security\UserTokenService;
use App\Service\Api\GetDexListApiService;
use Symfony\Bundle\SecurityBundle\Security;

class GetDexByRoleService
{
    public function __construct(
        private readonly GetDexListApiService $getDexListService,
        private readonly UserTokenService $userTokenService,
        private Security $security,
    ) {}

    /**
     * @return string[][]
     */
    public function getUserDex(): array
    {
        /** @var ?User $user */
        $user = $this->security->getUser();

        if (!$user) {
            return [];
        }

        $userToken = $this->userTokenService->getLoggedUserToken();

        return $user->isAnAdmin()
            ? $this->getDexListService->getWithUnreleasedAndPremium($userToken)
            : $this->getDexListService->getWithPremium($userToken);
    }
}
