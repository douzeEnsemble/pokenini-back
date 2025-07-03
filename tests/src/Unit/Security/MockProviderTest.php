<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\MockProvider;
use GuzzleHttp\Psr7\Request;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MockProvider::class)]
class MockProviderTest extends TestCase
{
    public function testGetBaseAuthorizationUrl(): void
    {
        $provider = new MockProvider();

        $this->assertSame(
            'http://moco.oauth2/authorize',
            $provider->getBaseAuthorizationUrl(),
        );
    }

    public function testGetBaseAccessTokenUrl(): void
    {
        $provider = new MockProvider();

        $this->assertSame(
            'http://moco.oauth2/token',
            $provider->getBaseAccessTokenUrl([]),
        );
    }

    public function testGetResourceOwnerDetailsUrl(): void
    {
        $provider = new MockProvider();

        $this->assertSame(
            'http://moco.oauth2/userinfo',
            $provider->getResourceOwnerDetailsUrl(
                new AccessToken([
                    'access_token' => 'resource-owner-details-url',
                ])
            ),
        );
    }

    public function testGetAuthorizationUrl(): void
    {
        $provider = new MockProvider();

        $this->assertSame(
            'http://moco.oauth2/authorize?state=123&scope=openid&response_type=code&approval_prompt=auto',
            $provider->getAuthorizationUrl(['state' => '123']),
        );
    }

    public function testCheckResponseWithoutError(): void
    {
        $request = new Request('GET', 'http://moco.oauth2/getparsedresponse/without-error');

        $provider = new MockProvider();

        $this->assertSame(
            [
                'some-json' => 'value',
            ],
            $provider->getParsedResponse($request),
        );
    }

    public function testCheckResponseWithError(): void
    {
        $request = new Request('GET', 'http://moco.oauth2/getparsedresponse/with-error');

        $provider = new MockProvider();

        $this->assertSame(
            [
                'error' => 'oops',
            ],
            $provider->getParsedResponse($request),
        );
    }

    public function testCreateResourceOwner(): void
    {
        $provider = new MockProvider();

        $resourceOwner = $provider->getResourceOwner(
            new AccessToken([
                'access_token' => 'create-resource-owner',
            ]),
        );

        $this->assertSame(
            [
                'sub' => '0987654321',
                'id' => 'this-is-an-id',
                'name' => 'John Admin',
                'email' => 'john.snow@example.com',
            ],
            $resourceOwner->toArray(),
        );
    }

    public function testGetAuthorizationHeaders(): void
    {
        $provider = new MockProvider();

        $this->assertSame(
            [
                'Authorization' => 'Bearer totokenken',
            ],
            $provider->getHeaders(
                new AccessToken([
                    'access_token' => 'totokenken',
                ])
            ),
        );
    }
}
