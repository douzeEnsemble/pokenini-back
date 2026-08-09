<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\SessionTokenService;
use App\Security\User;
use Firebase\JWT\JWT;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SessionTokenService::class)]
final class SessionTokenServiceTest extends TestCase
{
    public function testIssueThenParseRoundTrip(): void
    {
        $service = new SessionTokenService('some-secret-that-is-at-least-32-bytes-long', 604800);

        $user = new User('some-id', 'google');
        $user->addTrainerRole();
        $user->addCollectorRole();

        $token = $service->issue($user);

        $claims = $service->parse($token);

        $this->assertNotNull($claims);
        $this->assertSame('some-id', $claims['sub']);
        $this->assertSame('google', $claims['provider']);
        $this->assertSame(['ROLE_USER', 'ROLE_TRAINER', 'ROLE_COLLECTOR'], $claims['roles']);
    }

    public function testParseReturnsNullForGarbageToken(): void
    {
        $service = new SessionTokenService('some-secret-that-is-at-least-32-bytes-long', 604800);

        $this->assertNull($service->parse('not-a-jwt'));
    }

    public function testParseReturnsNullForEmptyToken(): void
    {
        $service = new SessionTokenService('some-secret-that-is-at-least-32-bytes-long', 604800);

        $this->assertNull($service->parse(''));
    }

    public function testParseReturnsNullWhenSignedWithADifferentSecret(): void
    {
        $issuer = new SessionTokenService('some-secret-that-is-at-least-32-bytes-long', 604800);
        $verifier = new SessionTokenService('another-secret-that-is-at-least-32-bytes-long', 604800);

        $token = $issuer->issue(new User('some-id', 'google'));

        $this->assertNull($verifier->parse($token));
    }

    public function testParseReturnsNullForExpiredToken(): void
    {
        $service = new SessionTokenService('some-secret-that-is-at-least-32-bytes-long', -1);

        $token = $service->issue(new User('some-id', 'google'));

        $this->assertNull($service->parse($token));
    }

    public function testParseReturnsNullWhenClaimsShapeIsInvalid(): void
    {
        $secret = 'some-secret-that-is-at-least-32-bytes-long';
        $service = new SessionTokenService($secret, 604800);

        $token = JWT::encode(
            ['sub' => 'some-id', 'provider' => 'google', 'roles' => 'not-an-array'],
            $secret,
            'HS256',
        );

        $this->assertNull($service->parse($token));
    }
}
