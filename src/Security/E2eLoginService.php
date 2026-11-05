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
        if ($this->matches($this->e2eLoginSecret, $this->e2eUserId, $secret)) {
            $user = new User($this->e2eUserId, self::PROVIDER);
            $user->addTrainerRole();

            return $user;
        }

        if ($this->matches($this->e2eAdminLoginSecret, $this->e2eAdminUserId, $secret)) {
            $user = new User($this->e2eAdminUserId, self::PROVIDER);
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
    private function matches(string $expectedSecret, string $userId, string $secret): bool
    {
        return '' !== $expectedSecret && '' !== $userId && hash_equals($expectedSecret, $secret);
    }
}
