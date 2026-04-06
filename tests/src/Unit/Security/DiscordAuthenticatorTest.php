<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\AbstractAuthenticator;
use App\Security\DiscordAuthenticator;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[CoversClass(DiscordAuthenticator::class)]
final class DiscordAuthenticatorTest extends AbstractAuthenticatorTesting
{
    #[\Override]
    protected function instanciate(
        ClientRegistry $clientRegistry,
        RouterInterface $router,
        string $listAdmin,
        string $listTrainer,
        string $listCollector,
        bool $isInvitationRequired,
    ): AbstractAuthenticator {
        return new DiscordAuthenticator(
            $clientRegistry,
            $router,
            $listAdmin,
            $listTrainer,
            $listCollector,
            $isInvitationRequired,
        );
    }

    #[\Override]
    protected function getAuthenticatorProviderCode(): string
    {
        return 'discord';
    }

    #[\Override]
    protected function getAuthenticatorProviderName(): string
    {
        return 'Discord';
    }
}
