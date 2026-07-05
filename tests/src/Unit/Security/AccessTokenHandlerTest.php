<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\AccessTokenHandler;
use App\Security\User;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
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
    public function testGetUserBadgeFrom(): void
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
        );

        $userBadge = $accessTokenHandler->getUserBadgeFrom('some-access-token');

        $this->assertSame('some-access-token', $userBadge->getUserIdentifier());
        $user = $userBadge->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('some-id', $user->getId());
    }

    public function testGetUserBadgeFromWithoutCurrentRequest(): void
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
        );

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessageIsOrContains('No current request available.');

        $accessTokenHandler->getUserBadgeFrom('some-access-token');
    }

    public function testGetUserBadgeFromWithoutProviderHeader(): void
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
        );

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessageIsOrContains('The "X-Provider" header is missing.');

        $accessTokenHandler->getUserBadgeFrom('some-access-token');
    }

    public function testGetUserBadgeFromWithNullProviderHeader(): void
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
        );

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessageIsOrContains('The "X-Provider" header is empty.');

        $accessTokenHandler->getUserBadgeFrom('some-access-token');
    }

    public function testGetUserBadgeFromWithEmptyProviderHeader(): void
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
        );

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessageIsOrContains('The "X-Provider" header is empty.');

        $accessTokenHandler->getUserBadgeFrom('some-access-token');
    }

    public function testGetUserBadgeFromAsAdmin(): void
    {
        $user = $this->getUserFromUserBadge('some-admin-id', false);

        $this->assertTrue($user->isAnAdmin());
        $this->assertFalse($user->isACollector());
        $this->assertTrue($user->isATrainer());
    }

    public function testGetUserBadgeFromAsCollector(): void
    {
        $user = $this->getUserFromUserBadge('some-collector-id', false);

        $this->assertFalse($user->isAnAdmin());
        $this->assertTrue($user->isACollector());
        $this->assertTrue($user->isATrainer());
    }

    public function testGetUserBadgeFromAsTrainer(): void
    {
        $user = $this->getUserFromUserBadge('some-trainer-id', false);

        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isACollector());
        $this->assertTrue($user->isATrainer());
    }

    public function testGetUserBadgeFromAsUnknown(): void
    {
        $user = $this->getUserFromUserBadge('some-unknown-id', false);

        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isACollector());
        $this->assertTrue($user->isATrainer());
    }

    public function testGetUserBadgeFromAsAdminWithInvitationRequested(): void
    {
        $user = $this->getUserFromUserBadge('some-admin-id', true);

        $this->assertTrue($user->isAnAdmin());
        $this->assertFalse($user->isACollector());
        $this->assertFalse($user->isATrainer());
    }

    public function testGetUserBadgeFromAsCollectorWithInvitationRequested(): void
    {
        $user = $this->getUserFromUserBadge('some-collector-id', true);

        $this->assertFalse($user->isAnAdmin());
        $this->assertTrue($user->isACollector());
        $this->assertFalse($user->isATrainer());
    }

    public function testGetUserBadgeFromAsTrainerWithInvitationRequested(): void
    {
        $user = $this->getUserFromUserBadge('some-trainer-id', true);

        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isACollector());
        $this->assertTrue($user->isATrainer());
    }

    public function testGetUserBadgeFromAsUnknownWithInvitationRequested(): void
    {
        $user = $this->getUserFromUserBadge('some-unknown-id', true);

        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isACollector());
        $this->assertFalse($user->isATrainer());
    }

    public function testGetUserFromUserBadgeWithInvalidToken(): void
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
        );

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessageIsOrContains('Token is invalid, maybe expired');

        $userBadge = $accessTokenHandler->getUserBadgeFrom('some-access-token');

        $userBadge->getUser();
    }

    public function testGetUserBadgeFromWithFakeProviderInDevAsAdmin(): void
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
        );

        $userBadge = $accessTokenHandler->getUserBadgeFrom('admin');

        $this->assertSame('admin', $userBadge->getUserIdentifier());
        $user = $userBadge->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->isAnAdmin());
        $this->assertTrue($user->isACollector());
        $this->assertTrue($user->isATrainer());
    }

    public function testGetUserBadgeFromWithFakeProviderInDevAsTrainer(): void
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
        );

        $userBadge = $accessTokenHandler->getUserBadgeFrom('trainer');

        $user = $userBadge->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isACollector());
        $this->assertTrue($user->isATrainer());
    }

    public function testGetUserBadgeFromWithNonFakeProviderInDevUsesOAuth2Client(): void
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
        );

        $userBadge = $accessTokenHandler->getUserBadgeFrom('some-access-token');

        $this->assertSame('some-access-token', $userBadge->getUserIdentifier());
        $user = $userBadge->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('some-id', $user->getId());
    }

    public function testGetUserBadgeFromWithFakeProviderInDevAndEmptyToken(): void
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
        );

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessageIsOrContains('Empty fake token.');

        $accessTokenHandler->getUserBadgeFrom('');
    }

    public function testGetUserBadgeFromWithLowercaseFakeProviderInDev(): void
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
        );

        $userBadge = $accessTokenHandler->getUserBadgeFrom('admin');

        $user = $userBadge->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->isAnAdmin());
        $this->assertTrue($user->isACollector());
        $this->assertTrue($user->isATrainer());
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
        );

        $userBadge = $accessTokenHandler->getUserBadgeFrom('some-access-token');

        /** @var User */
        return $userBadge->getUser();
    }
}
