<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\AbstractAuthenticator;
use App\Security\MockAuthenticator;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[CoversClass(MockAuthenticator::class)]
final class MockAuthenticatorTest extends AbstractAuthenticatorTesting
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
        return new MockAuthenticator(
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
        return 'mock';
    }

    #[\Override]
    protected function getAuthenticatorProviderName(): string
    {
        return 'Mock';
    }
}
