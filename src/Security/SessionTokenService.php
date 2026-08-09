<?php

declare(strict_types=1);

namespace App\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Issues and verifies the internal, signed session token exchanged after the initial OAuth login.
 *
 * Unlike the raw provider access token (Google/Discord), this token is signed by pokenini-back itself
 * and can be verified locally, without contacting the provider again. It carries the roles already
 * resolved at login time and is valid for SESSION_TTL, matching the lifetime of the pokenini-web session
 * cookie.
 */
class SessionTokenService
{
    private const string ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $sessionTokenSecret,
        private readonly int $sessionTtl,
    ) {}

    public function issue(User $user): string
    {
        $now = time();

        $payload = [
            'sub' => $user->getUserIdentifier(),
            'provider' => $user->getProvider(),
            'roles' => $user->getRoles(),
            'iat' => $now,
            'exp' => $now + $this->sessionTtl,
        ];

        return JWT::encode($payload, $this->sessionTokenSecret, self::ALGORITHM);
    }

    /**
     * @return null|array{sub: non-empty-string, provider: string, roles: list<string>}
     */
    public function parse(string $token): ?array
    {
        try {
            $claims = JWT::decode($token, new Key($this->sessionTokenSecret, self::ALGORITHM));
        } catch (\Throwable) {
            return null;
        }

        $sub = $claims->sub ?? null;
        $provider = $claims->provider ?? null;
        $roles = $claims->roles ?? null;

        if (!is_string($sub) || '' === $sub || !is_string($provider) || !is_array($roles)) {
            return null;
        }

        $stringRoles = array_values(array_filter($roles, 'is_string'));

        return [
            'sub' => $sub,
            'provider' => $provider,
            'roles' => $stringRoles,
        ];
    }
}
