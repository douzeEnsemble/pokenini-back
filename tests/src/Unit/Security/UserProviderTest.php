<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\User;
use App\Security\UserProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 */
#[CoversClass(UserProvider::class)]
final class UserProviderTest extends TestCase
{
    #[Test]
    public function loadUserByIdentifier(): void
    {
        $provider = new UserProvider();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Not use in this project');

        $provider->loadUserByIdentifier('douze');
    }

    #[Test]
    public function refreshUser(): void
    {
        $provider = new UserProvider();

        $user = new User('douze', 'TestProvider');

        $freshUser = $provider->refreshUser($user);

        $this->assertSame($user, $freshUser);
    }

    #[Test]
    public function refreshUserWrongUser(): void
    {
        $provider = new UserProvider();

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessageMatches('/Invalid user class "TestStub_UserInterface_.{8}"\./');

        $notUser = $this->createStub(UserInterface::class);

        $provider->refreshUser($notUser);
    }

    #[Test]
    public function upgradePassword(): void
    {
        $provider = new UserProvider();

        $user = $initialUser = $this->createStub(PasswordAuthenticatedUserInterface::class);

        $provider->upgradePassword($user, 'e3ca7fbe759a0d0afb2cbd2a62390472');

        $this->assertSame($initialUser, $user);
    }

    #[Test]
    public function supportsClass(): void
    {
        $provider = new UserProvider();

        $this->assertTrue($provider->supportsClass('App\Security\User'));
        $this->assertFalse($provider->supportsClass('App\Entity\User'));
    }
}
