<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\FakeAuthenticator;
use App\Security\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @internal
 */
#[CoversClass(FakeAuthenticator::class)]
final class FakeAuthenticatorAuthenticateTest extends TestCase
{
    #[Test]
    public function authenticateUser(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator(
            '1313131313',
            '2121212121,1313131313',
            '2121212121',
        );

        $request = Request::create('local.dev', 'GET', ['t' => '1212121212000000000000012']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertFalse($user->isAnAdmin());
        $this->assertFalse($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
    }

    #[Test]
    public function authenticateTrainer(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator(
            '1313131313',
            '2121212121,1313131313,1212121212000000000000012',
            '2121212121,1313131313',
        );

        $request = Request::create('local.dev', 'GET', ['t' => '1212121212000000000000012']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertFalse($user->isAnAdmin());
        $this->assertTrue($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
    }

    #[Test]
    public function authenticateCollector(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator(
            '1313131313',
            '2121212121,1313131313,1212121212000000000000012',
            '2121212121',
        );

        $request = Request::create('local.dev', 'GET', ['t' => '1212121212000000000000012']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertFalse($user->isAnAdmin());
        $this->assertTrue($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
    }

    #[Test]
    public function authenticateAdmin(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator(
            '1313131313,1212121212000000000000012',
            '2121212121,1313131313',
            '2121212121',
        );

        $request = Request::create('local.dev', 'GET', ['t' => '1212121212000000000000012']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertTrue($user->isAnAdmin());
        $this->assertFalse($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
    }

    #[Test]
    public function authenticateAdminTrainer(): void
    {
        $fakeAuthenticator = $this->getFakeAuthenticator(
            '1313131313,1212121212000000000000012',
            '2121212121,1313131313,1212121212000000000000012',
            '2121212121,',
        );

        $request = Request::create('local.dev', 'GET', ['t' => '1212121212000000000000012']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertTrue($user->isAnAdmin());
        $this->assertTrue($user->isATrainer());
        $this->assertFalse($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
    }

    #[Test]
    public function authenticateAdminTrainerWithEndlines(): void
    {
        $listAdmin = <<<'LIST'
            toto,

            1212121212000000000000012,

            01234567890123456789011
            LIST;
        $listTrainer = <<<'LIST'
            titi,

            1212121212000000000000012,

            0123456789012345678901,
            11655986856658439236105875191
            LIST;
        $listCollector = <<<'LIST'
            tata,
            1212121212000000000000012,
            LIST;

        $fakeAuthenticator = $this->getFakeAuthenticator($listAdmin, $listTrainer, $listCollector);

        $request = Request::create('local.dev', 'GET', ['t' => '1212121212000000000000012']);

        $validationPassport = $fakeAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $validationPassport);

        /** @var User $user */
        $user = $validationPassport->getUser();
        $this->assertTrue($user->isAnAdmin());
        $this->assertTrue($user->isATrainer());
        $this->assertTrue($user->isACollector());
        $this->assertEquals('1212121212000000000000012', $user->getId());
        $this->assertEquals('1212121212000000000000012', $user->getUserIdentifier());
    }

    private function getFakeAuthenticator(string $listAdmin, string $listTrainer, string $listCollector): FakeAuthenticator
    {
        $router = $this->createStub(RouterInterface::class);

        return new FakeAuthenticator(
            $router,
            $listAdmin,
            $listTrainer,
            $listCollector,
            true,
        );
    }
}
