<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\AccessTokenHandler;
use App\Security\SessionTokenService;
use App\Security\User;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * @internal
 */
#[CoversClass(AccessTokenHandler::class)]
final class AccessTokenHandlerTest extends TestCase
{
    #[Test]
    public function getUserBadgeFrom(): void
    {
        $authUser = $this->createMock(ResourceOwnerInterface::class);
        $authUser
            ->expects($this->once())
            ->method('getId')
            ->willReturn('some-id')
        ;

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with('some-access-token')
            ->willReturn($authUser)
        ;

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->willReturn($client)
        ;

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'HTTP_X-Provider' => 'some-provider',
            ],
        );

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Authentication succeeded for provider some-provider')
        ;

        $accessTokenHandler = new AccessTokenHandler(
            $clientRegistry,
            $requestStack,
            'some-id,another-id,more-id,extra-id',
            'some-id,another-id',
            'some-id',
            false,
            $logger,
            'prod',
            $this->createSessionTokenService(),
        );

        $userBadge = $accessTokenHandler->getUserBadgeFrom('some-access-token');

        $this->assertSame('some-access-token', $userBadge->getUserIdentifier());
        $user = $userBadge->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('some-id', $user->getId());
    }

    #[Test]
    public function getUserBadgeFromWithInternalSessionToken(): void
    {
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry->expects($this->never())->method('getClient');

        $request = new Request([], [], [], [], [], ['HTTP_X-Provider' => 'google']);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Authentication succeeded from internal session token for provider google')
        ;

        $sessionTokenService = $this->createSessionTokenService();

        $issuingUser = new User('some-id', 'google');
        $issuingUser->addAdminRole();
        $issuingUser->addCollectorRole();
        $issuingUser->addTrainerRole();

        $sessionToken = $sessionTokenService->issue($issuingUser);

        $accessTokenHandler = new AccessTokenHandler(
            $clientRegistry,
            $requestStack,
            'some-id,another-id,more-id,extra-id',
            'some-id,another-id',
            'some-id',
            false,
            $logger,
            'prod',
            $sessionTokenService,
        );

        $userBadge = $accessTokenHandler->getUserBadgeFrom($sessionToken);

        $this->assertSame('some-id', $userBadge->getUserIdentifier());
        $user = $userBadge->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->isAnAdmin());
        $this->assertTrue($user->isACollector());
        $this->assertTrue($user->isATrainer());
    }

    #[Test]
    public function getUserBadgeFromWithoutCurrentRequest(): void
    {
        $clientRegistry = $this->createStub(ClientRegistry::class);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Authentication failed: no current request')
        ;

        $accessTokenHandler = new AccessTokenHandler(
            $clientRegistry,
            $requestStack,
            'some-id,another-id,more-id,extra-id',
            'some-id,another-id',
            'some-id',
            false,
            $logger,
            'prod',
            $this->createSessionTokenService(),
        );

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessageIsOrContains('No current request available.');

        $accessTokenHandler->getUserBadgeFrom('some-access-token');
    }

    #[Test]
    public function getUserBadgeFromWithoutProviderHeader(): void
    {
        $clientRegistry = $this->createStub(ClientRegistry::class);

        $request = new Request();

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Authentication failed: missing X-Provider header')
        ;

        $accessTokenHandler = new AccessTokenHandler(
            $clientRegistry,
            $requestStack,
            'some-id,another-id,more-id,extra-id',
            'some-id,another-id',
            'some-id',
            false,
            $logger,
            'prod',
            $this->createSessionTokenService(),
        );

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessageIsOrContains('The "X-Provider" header is missing.');

        $accessTokenHandler->getUserBadgeFrom('some-access-token');
    }

    #[Test]
    public function getUserBadgeFromWithNullProviderHeader(): void
    {
        $clientRegistry = $this->createStub(ClientRegistry::class);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'HTTP_X-Provider' => null,
            ],
        );

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Authentication failed: empty X-Provider header')
        ;

        $accessTokenHandler = new AccessTokenHandler(
            $clientRegistry,
            $requestStack,
            'some-id,another-id,more-id,extra-id',
            'some-id,another-id',
            'some-id',
            false,
            $logger,
            'prod',
            $this->createSessionTokenService(),
        );

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessageIsOrContains('The "X-Provider" header is empty.');

        $accessTokenHandler->getUserBadgeFrom('some-access-token');
    }

    #[Test]
    public function getUserBadgeFromWithEmptyProviderHeader(): void
    {
        $clientRegistry = $this->createStub(ClientRegistry::class);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'HTTP_X-Provider' => '',
            ],
        );

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Authentication failed: empty X-Provider header')
        ;

        $accessTokenHandler = new AccessTokenHandler(
            $clientRegistry,
            $requestStack,
            'some-id,another-id,more-id,extra-id',
            'some-id,another-id',
            'some-id',
            false,
            $logger,
            'prod',
            $this->createSessionTokenService(),
        );

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessageIsOrContains('The "X-Provider" header is empty.');

        $accessTokenHandler->getUserBadgeFrom('some-access-token');
    }

    #[Test]
    public function getUserBadgeFromAsAdmin(): void
    {
        $user = $this->getUserFromUserBadge('some-admin-id', false);

        $this->assertTrue($user->isAnAdmin());
        $this->assertFalse($user->isACollector());
        $this->assertTrue($user->isATrainer());
    }

    #[Test]
    public function getUserBadgeFromAsCollector(): void
    {
        $user = $this->getUserFromUserBadge('some-collector-id', false);

        $this->assertFalse($user->isAnAdmin());
        $this->assertTrue($user->isACollector());
        $this->assertTrue($user->isATrainer());
    }

    #[Test]
    public function getUserBadgeFromAsTrainer(): void
    {
        $user = $this->getUserFromUserBadge('some-trainer-id', false);

        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isACollector());
        $this->assertTrue($user->isATrainer());
    }

    #[Test]
    public function getUserBadgeFromAsUnknown(): void
    {
        $user = $this->getUserFromUserBadge('some-unknown-id', false);

        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isACollector());
        $this->assertTrue($user->isATrainer());
    }

    #[Test]
    public function getUserBadgeFromAsAdminWithInvitationRequested(): void
    {
        $user = $this->getUserFromUserBadge('some-admin-id', true);

        $this->assertTrue($user->isAnAdmin());
        $this->assertFalse($user->isACollector());
        $this->assertFalse($user->isATrainer());
    }

    #[Test]
    public function getUserBadgeFromAsCollectorWithInvitationRequested(): void
    {
        $user = $this->getUserFromUserBadge('some-collector-id', true);

        $this->assertFalse($user->isAnAdmin());
        $this->assertTrue($user->isACollector());
        $this->assertFalse($user->isATrainer());
    }

    #[Test]
    public function getUserBadgeFromAsTrainerWithInvitationRequested(): void
    {
        $user = $this->getUserFromUserBadge('some-trainer-id', true);

        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isACollector());
        $this->assertTrue($user->isATrainer());
    }

    #[Test]
    public function getUserBadgeFromAsUnknownWithInvitationRequested(): void
    {
        $user = $this->getUserFromUserBadge('some-unknown-id', true);

        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isACollector());
        $this->assertFalse($user->isATrainer());
    }

    #[Test]
    public function getUserFromUserBadgeWithInvalidToken(): void
    {
        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with('some-access-token')
            ->willThrowException(new IdentityProviderException('Invalid totoken', 0o01, '{"body": "error"}'))
        ;

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->willReturn($client)
        ;

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'HTTP_X-Provider' => 'some-provider',
            ],
        );

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Authentication failed: invalid token for provider some-provider')
        ;

        $accessTokenHandler = new AccessTokenHandler(
            $clientRegistry,
            $requestStack,
            'some-id,another-id,more-id,extra-id,    some-admin-id ',
            'some-id, another-id ,    ,    some-collector-id       ',
            'some-id,    some-trainer-id    ',
            false,
            $logger,
            'prod',
            $this->createSessionTokenService(),
        );

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessageIsOrContains('Token is invalid, maybe expired');

        $userBadge = $accessTokenHandler->getUserBadgeFrom('some-access-token');

        $userBadge->getUser();
    }

    #[Test]
    public function getUserBadgeFromWithFakeProviderInDevAsAdmin(): void
    {
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry->expects($this->never())->method('getClient');

        $request = new Request([], [], [], [], [], ['HTTP_X-Provider' => 'FaKe']);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('Authentication succeeded for provider FaKe');

        $accessTokenHandler = new AccessTokenHandler(
            $clientRegistry,
            $requestStack,
            'admin,some-admin-id',
            'collector,admin,some-admin-id',
            'trainer,collector,admin,some-admin-id',
            false,
            $logger,
            'dev',
            $this->createSessionTokenService(),
        );

        $userBadge = $accessTokenHandler->getUserBadgeFrom('admin');

        $this->assertSame('admin', $userBadge->getUserIdentifier());
        $user = $userBadge->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->isAnAdmin());
        $this->assertTrue($user->isACollector());
        $this->assertTrue($user->isATrainer());
    }

    #[Test]
    public function getUserBadgeFromWithFakeProviderInDevAsTrainer(): void
    {
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry->expects($this->never())->method('getClient');

        $request = new Request([], [], [], [], [], ['HTTP_X-Provider' => 'FaKe']);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('Authentication succeeded for provider FaKe');

        $accessTokenHandler = new AccessTokenHandler(
            $clientRegistry,
            $requestStack,
            'admin',
            'collector,admin',
            'trainer,collector,admin',
            false,
            $logger,
            'dev',
            $this->createSessionTokenService(),
        );

        $userBadge = $accessTokenHandler->getUserBadgeFrom('trainer');

        $user = $userBadge->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isACollector());
        $this->assertTrue($user->isATrainer());
    }

    #[Test]
    public function getUserBadgeFromWithNonFakeProviderInDevUsesOAuth2Client(): void
    {
        $authUser = $this->createMock(ResourceOwnerInterface::class);
        $authUser
            ->expects($this->once())
            ->method('getId')
            ->willReturn('some-id')
        ;

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with('some-access-token')
            ->willReturn($authUser)
        ;

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->willReturn($client)
        ;

        $request = new Request([], [], [], [], [], ['HTTP_X-Provider' => 'some-provider']);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('Authentication succeeded for provider some-provider');

        $accessTokenHandler = new AccessTokenHandler(
            $clientRegistry,
            $requestStack,
            'some-id',
            'some-id',
            'some-id',
            false,
            $logger,
            'dev',
            $this->createSessionTokenService(),
        );

        $userBadge = $accessTokenHandler->getUserBadgeFrom('some-access-token');

        $this->assertSame('some-access-token', $userBadge->getUserIdentifier());
        $user = $userBadge->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('some-id', $user->getId());
    }

    #[Test]
    public function getUserBadgeFromWithFakeProviderInDevAndEmptyToken(): void
    {
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry->expects($this->never())->method('getClient');

        $request = new Request([], [], [], [], [], ['HTTP_X-Provider' => 'fake']);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('info');

        $accessTokenHandler = new AccessTokenHandler(
            $clientRegistry,
            $requestStack,
            'admin',
            'collector,admin',
            'trainer,collector,admin',
            false,
            $logger,
            'dev',
            $this->createSessionTokenService(),
        );

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessageIsOrContains('Empty fake token.');

        $accessTokenHandler->getUserBadgeFrom('');
    }

    #[Test]
    public function getUserBadgeFromWithLowercaseFakeProviderInDev(): void
    {
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry->expects($this->never())->method('getClient');

        $request = new Request([], [], [], [], [], ['HTTP_X-Provider' => 'fake']);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('Authentication succeeded for provider fake');

        $accessTokenHandler = new AccessTokenHandler(
            $clientRegistry,
            $requestStack,
            'admin',
            'collector,admin',
            'trainer,collector,admin',
            false,
            $logger,
            'dev',
            $this->createSessionTokenService(),
        );

        $userBadge = $accessTokenHandler->getUserBadgeFrom('admin');

        $user = $userBadge->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->isAnAdmin());
        $this->assertTrue($user->isACollector());
        $this->assertTrue($user->isATrainer());
    }

    /**
     * @param list<string> $roles
     */
    #[Test]
    #[DataProvider('provideE2eLogins')]
    public function getUserBadgeFromWithE2eLogin(string $accessToken, string $userId, array $roles): void
    {
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry->expects($this->never())->method('getClient');

        $request = new Request([], [], [], [], [], ['HTTP_X-Provider' => 'E2E']);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $profile = 'e2e-test-admin' === $userId ? 'admin' : 'trainer';

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with("Authentication succeeded for the E2E test {$profile}")
        ;

        $accessTokenHandler = $this->createE2eAccessTokenHandler(
            $clientRegistry,
            $requestStack,
            $logger,
            'the-e2e-secret',
            'e2e-test-trainer',
            'the-e2e-admin-secret',
            'e2e-test-admin',
        );

        $userBadge = $accessTokenHandler->getUserBadgeFrom($accessToken);

        $this->assertSame($userId, $userBadge->getUserIdentifier());
        $user = $userBadge->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($userId, $user->getId());
        $this->assertSame('e2e', $user->getProvider());
        $this->assertSame($roles, array_values($user->getRoles()));
    }

    /**
     * @return iterable<string, array{string, string, list<string>}>
     */
    public static function provideE2eLogins(): iterable
    {
        yield 'trainer' => ['the-e2e-secret', 'e2e-test-trainer', ['ROLE_USER', 'ROLE_TRAINER']];

        yield 'admin' => ['the-e2e-admin-secret', 'e2e-test-admin', ['ROLE_USER', 'ROLE_TRAINER', 'ROLE_COLLECTOR', 'ROLE_ADMIN']];
    }

    #[Test]
    #[DataProvider('provideInvalidE2eLogins')]
    public function getUserBadgeFromWithInvalidE2eLogin(
        string $secret,
        string $userId,
        string $adminSecret,
        string $adminUserId,
        string $accessToken,
    ): void {
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry->expects($this->never())->method('getClient');

        $request = new Request([], [], [], [], [], ['HTTP_X-Provider' => 'e2e']);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Authentication failed: invalid E2E login')
        ;

        $accessTokenHandler = $this->createE2eAccessTokenHandler(
            $clientRegistry,
            $requestStack,
            $logger,
            $secret,
            $userId,
            $adminSecret,
            $adminUserId,
        );

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessageIsOrContains('Invalid E2E login.');

        $accessTokenHandler->getUserBadgeFrom($accessToken);
    }

    /**
     * @return iterable<string, array{string, string, string, string, string}>
     */
    public static function provideInvalidE2eLogins(): iterable
    {
        yield 'wrong secret' => ['the-e2e-secret', 'e2e-test-trainer', 'the-e2e-admin-secret', 'e2e-test-admin', 'not-the-secret'];

        yield 'trainer secret not configured' => ['', 'e2e-test-trainer', '', 'e2e-test-admin', ''];

        yield 'trainer not configured' => ['the-e2e-secret', '', '', '', 'the-e2e-secret'];

        yield 'admin secret not configured' => ['the-e2e-secret', 'e2e-test-trainer', '', 'e2e-test-admin', ''];

        yield 'admin not configured' => ['the-e2e-secret', 'e2e-test-trainer', 'the-e2e-admin-secret', '', 'the-e2e-admin-secret'];
    }

    private function getUserFromUserBadge(string $userId, bool $isInvitationRequired): User
    {
        $authUser = $this->createMock(ResourceOwnerInterface::class);
        $authUser
            ->expects($this->once())
            ->method('getId')
            ->willReturn($userId)
        ;

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with('some-access-token')
            ->willReturn($authUser)
        ;

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->willReturn($client)
        ;

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'HTTP_X-Provider' => 'some-provider',
            ],
        );

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Authentication succeeded for provider some-provider')
        ;

        $accessTokenHandler = new AccessTokenHandler(
            $clientRegistry,
            $requestStack,
            'some-id,another-id,more-id,extra-id,    some-admin-id ',
            'some-id, another-id ,    ,    some-collector-id       ',
            'some-id,    some-trainer-id    ',
            $isInvitationRequired,
            $logger,
            'prod',
            $this->createSessionTokenService(),
        );

        $userBadge = $accessTokenHandler->getUserBadgeFrom('some-access-token');

        /** @var User */
        return $userBadge->getUser();
    }

    private function createE2eAccessTokenHandler(
        ClientRegistry $clientRegistry,
        RequestStack $requestStack,
        LoggerInterface $logger,
        string $secret,
        string $userId,
        string $adminSecret,
        string $adminUserId,
    ): AccessTokenHandler {
        return new AccessTokenHandler(
            $clientRegistry,
            $requestStack,
            // The test users are in no list: their roles come from the secret only
            'someone-else',
            'someone-else',
            'someone-else',
            true,
            $logger,
            'prod',
            $this->createSessionTokenService(),
            $secret,
            $userId,
            $adminSecret,
            $adminUserId,
        );
    }

    private function createSessionTokenService(): SessionTokenService
    {
        return new SessionTokenService('test-session-token-secret-that-is-at-least-32-bytes-long', 604800);
    }
}
