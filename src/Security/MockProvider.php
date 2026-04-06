<?php

namespace App\Security;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class MockProvider extends AbstractProvider
{
    #[\Override]
    public function getBaseAuthorizationUrl()
    {
        return 'http://moco.oauth2/authorize';
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     *
     * @param array<string, mixed> $params
     */
    #[\Override]
    public function getBaseAccessTokenUrl(array $params)
    {
        return 'http://moco.oauth2/token';
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     *
     * @param mixed $token
     */
    #[\Override]
    public function getResourceOwnerDetailsUrl($token)
    {
        return 'http://moco.oauth2/userinfo';
    }

    /**
     * @return string[]
     */
    #[\Override]
    protected function getDefaultScopes()
    {
        return ['openid'];
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     *
     * @param array<string, mixed>|string $data
     */
    #[\Override]
    protected function checkResponse(ResponseInterface $response, $data)
    {
        // nothing
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     *
     * @param array<string, mixed> $response
     */
    #[\Override]
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new GenericResourceOwner($response, 'id');
    }

    /**
     * @param null|AccessToken $token
     *
     * @return array<string, string>
     */
    #[\Override]
    protected function getAuthorizationHeaders($token = null)
    {
        return [
            'Authorization' => 'Bearer '.$token->getToken(), // @phpstan-ignore method.nonObject
        ];
    }
}
