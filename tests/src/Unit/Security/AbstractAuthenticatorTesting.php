<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\AbstractAuthenticator;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
abstract class AbstractAuthenticatorTesting extends TestCase
{
    use AuthenticatorSupportTestTrait;
    use AuthenticatorAuthenticateClosedTestTrait;
    use AuthenticatorAuthenticateOpenedTestTrait;
    use AuthenticatorOnAuthentificationTestTrait;

    abstract protected function instanciate(
        ClientRegistry $clientRegistry,
        RouterInterface $router,
        string $listAdmin,
        string $listTrainer,
        string $listCollector,
        bool $isInvitationRequired,
    ): AbstractAuthenticator;

    abstract protected function getAuthenticatorProviderCode(): string;

    abstract protected function getAuthenticatorProviderName(): string;
}
