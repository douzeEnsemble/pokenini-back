<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\E2eLoginService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(E2eLoginService::class)]
final class E2eLoginServiceTest extends TestCase
{
    #[Test]
    public function findTrainer(): void
    {
        $user = $this->createService()->findUser('the-e2e-secret');

        $this->assertNotNull($user);
        $this->assertSame('e2e-test-trainer', $user->getId());
        $this->assertSame('e2e', $user->getProvider());
        $this->assertSame(['ROLE_USER', 'ROLE_TRAINER'], array_values($user->getRoles()));
    }

    #[Test]
    public function findAdmin(): void
    {
        $user = $this->createService()->findUser('the-e2e-admin-secret');

        $this->assertNotNull($user);
        $this->assertSame('e2e-test-admin', $user->getId());
        $this->assertSame('e2e', $user->getProvider());
        $this->assertSame(['ROLE_USER', 'ROLE_TRAINER', 'ROLE_COLLECTOR', 'ROLE_ADMIN'], array_values($user->getRoles()));
    }

    #[Test]
    #[DataProvider('provideInvalidLogins')]
    public function findNoOne(string $secret, string $userId, string $adminSecret, string $adminUserId, string $login): void
    {
        $service = new E2eLoginService($secret, $userId, $adminSecret, $adminUserId);

        $this->assertNull($service->findUser($login));
    }

    /**
     * @return iterable<string, array{string, string, string, string, string}>
     */
    public static function provideInvalidLogins(): iterable
    {
        yield 'wrong secret' => ['the-e2e-secret', 'e2e-test-trainer', 'the-e2e-admin-secret', 'e2e-test-admin', 'not-the-secret'];

        yield 'trainer secret not configured' => ['', 'e2e-test-trainer', 'the-e2e-admin-secret', 'e2e-test-admin', ''];

        yield 'trainer not configured' => ['the-e2e-secret', '', 'the-e2e-admin-secret', 'e2e-test-admin', 'the-e2e-secret'];

        yield 'admin secret not configured' => ['the-e2e-secret', 'e2e-test-trainer', '', 'e2e-test-admin', ''];

        yield 'admin not configured' => ['the-e2e-secret', 'e2e-test-trainer', 'the-e2e-admin-secret', '', 'the-e2e-admin-secret'];
    }

    private function createService(): E2eLoginService
    {
        return new E2eLoginService('the-e2e-secret', 'e2e-test-trainer', 'the-e2e-admin-secret', 'e2e-test-admin');
    }
}
