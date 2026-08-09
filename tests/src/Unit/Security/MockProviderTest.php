<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\MockProvider;
use GuzzleHttp\Psr7\Request;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MockProvider::class)]
final class MockProviderTest extends TestCase
{
    #[Test]
    public function getBaseAuthorizationUrl(): void
    {
        $provider = new MockProvider();

        $this->assertSame(
            'http://moco.oauth2/authorize',
            $provider->getBaseAuthorizationUrl(),
        );
    }

    #[Test]
    public function getBaseAccessTokenUrl(): void
    {
        $provider = new MockProvider();

        $this->assertSame(
            'http://moco.oauth2/token',
            $provider->getBaseAccessTokenUrl([]),
        );
    }

    #[Test]
    public function getResourceOwnerDetailsUrl(): void
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

    #[Test]
    public function getAuthorizationUrl(): void
    {
        $provider = new MockProvider();

        $this->assertSame(
            'http://moco.oauth2/authorize?state=123&scope=openid&response_type=code&approval_prompt=auto',
            $provider->getAuthorizationUrl(['state' => '123']),
        );
    }

    #[Test]
    public function checkResponseWithoutError(): void
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

    #[Test]
    public function checkResponseWithError(): void
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

    #[Test]
    public function createResourceOwner(): void
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

    #[Test]
    public function getAuthorizationHeaders(): void
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
