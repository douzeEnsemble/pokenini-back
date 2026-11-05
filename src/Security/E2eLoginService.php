<?php

declare(strict_types=1);

namespace App\Security;

/**
 * The E2E tests sign in with a shared secret instead of a provider: one secret for the test trainer,
 * another one for the test admin. The roles come from the secret, never from the lists.
 */
class E2eLoginService
{
    public const string PROVIDER = 'e2e';

    public function __construct(
        private readonly string $e2eLoginSecret,
        private readonly string $e2eUserId,
        private readonly string $e2eAdminLoginSecret,
        private readonly string $e2eAdminUserId,
    ) {}

    /**
     * Returns the test user the secret signs in, null when it signs in no one.
     */
    public function findUser(string $secret): ?User
    {
        $userId = $this->e2eUserId;
        if ('' !== $userId && $this->matches($this->e2eLoginSecret, $secret)) {
            $user = new User($userId, self::PROVIDER);
            $user->addTrainerRole();

            return $user;
        }

        $adminUserId = $this->e2eAdminUserId;
        if ('' !== $adminUserId && $this->matches($this->e2eAdminLoginSecret, $secret)) {
            $user = new User($adminUserId, self::PROVIDER);
            $user->addTrainerRole();
            $user->addCollectorRole();
            $user->addAdminRole();

            return $user;
        }

        return null;
    }

    /**
     * An empty secret or user disables the login.
     */
    private function matches(string $expectedSecret, string $secret): bool
    {
        return '' !== $expectedSecret && hash_equals($expectedSecret, $secret);
    }
}
