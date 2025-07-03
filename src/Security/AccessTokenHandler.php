<?php

namespace App\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Token\AccessToken;
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
    ) {}

    #[\Override]
    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        if (null === $currentRequest) {
            throw new BadCredentialsException('No current request available.');
        }

        if (!$currentRequest->headers->has('X-Provider')) {
            throw new BadCredentialsException('The "X-Provider" header is missing.');
        }

        $provider = $currentRequest->headers->get('X-Provider');

        if (null === $provider || '' === $provider) {
            throw new BadCredentialsException('The "X-Provider" header is empty.');
        }

        $client = $this->clientRegistry->getClient($provider);

        $accessTokenObj = new AccessToken([
            'access_token' => $accessToken,
        ]);

        // and return a UserBadge object containing the user identifier from the found token
        // (this is the same identifier used in Security configuration; it can be an email,
        // a UUID, a username, a database ID, etc.)
        return new UserBadge($accessTokenObj->getToken(), function () use ($accessTokenObj, $client, $provider) {
            $authUser = $client->fetchUserFromToken($accessTokenObj);

            /** @var string $userId */
            $userId = $authUser->getId();

            return $this->loadUserFromLists($userId, $provider);
        });
    }

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
