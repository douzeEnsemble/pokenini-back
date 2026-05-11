<?php

declare(strict_types=1);

namespace App\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly RequestStack $requestStack,
        private readonly string $listAdmin,
        private readonly string $listCollector,
        private readonly string $listTrainer,
        private readonly bool $isInvitationRequired,
        private readonly LoggerInterface $logger,
    ) {}

    #[\Override]
    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        if (null === $currentRequest) {
            $this->logger->info('Authentication failed: no current request');

            throw new BadCredentialsException('No current request available.');
        }

        if (!$currentRequest->headers->has('X-Provider')) {
            $this->logger->info('Authentication failed: missing X-Provider header');

            throw new BadCredentialsException('The "X-Provider" header is missing.');
        }

        $provider = $currentRequest->headers->get('X-Provider');

        if (null === $provider || '' === $provider) {
            $this->logger->info('Authentication failed: empty X-Provider header');

            throw new BadCredentialsException('The "X-Provider" header is empty.');
        }

        $client = $this->clientRegistry->getClient($provider);

        $accessTokenObj = new AccessToken([
            'access_token' => $accessToken,
        ]);

        return new UserBadge($accessTokenObj->getToken(), function () use ($accessTokenObj, $client, $provider) {
            try {
                $authUser = $client->fetchUserFromToken($accessTokenObj);
            } catch (IdentityProviderException) {
                $this->logger->info("Authentication failed: invalid token for provider {$provider}");

                throw new BadCredentialsException('Token is invalid, maybe expired');
            }

            /** @var non-empty-string $userId */
            $userId = $authUser->getId();

            $user = $this->loadUserFromLists($userId, $provider);
            $this->logger->info("Authentication succeeded for provider {$provider}");

            return $user;
        });
    }

    /**
     * @param non-empty-string $identifier
     */
    private function loadUserFromLists(string $identifier, string $provider): User
    {
        $user = new User($identifier, $provider);

        $listAdmins = explode(',', $this->listAdmin);
        $listAdmins = array_map(fn ($value) => trim($value), $listAdmins);
        $listTrainers = explode(',', $this->listTrainer);
        $listTrainers = array_map(fn ($value) => trim($value), $listTrainers);
        $listCollectors = explode(',', $this->listCollector);
        $listCollectors = array_map(fn ($value) => trim($value), $listCollectors);

        if (in_array($user->getUserIdentifier(), $listAdmins)) {
            $user->addAdminRole();
        }
        if (in_array($user->getUserIdentifier(), $listCollectors)) {
            $user->addCollectorRole();
        }
        if (!$this->isInvitationRequired || in_array($user->getUserIdentifier(), $listTrainers)) {
            $user->addTrainerRole();
        }

        return $user;
    }
}
