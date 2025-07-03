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

    #[\Override]
    public function getBaseAccessTokenUrl(array $params)
    {
        return 'http://moco.oauth2/token';
    }

    #[\Override]
    public function getResourceOwnerDetailsUrl($token)
    {
        return 'http://moco.oauth2/userinfo';
    }

    #[\Override]
    protected function getDefaultScopes()
    {
        return ['openid'];
    }

    #[\Override]
    protected function checkResponse(ResponseInterface $response, $data)
    {
        // nothing
    }

    #[\Override]
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new GenericResourceOwner($response, 'id');
    }

    #[\Override]
    protected function getAuthorizationHeaders($token = null)
    {
        return [
            'Authorization' => 'Bearer '.$token->getToken(),
        ];
    }
}
