<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
trait AuthenticatorSupportTestTrait
{
    #[Test]
    public function supports(): void
    {
        $clientRegistry = $this->createStub(ClientRegistry::class);

        $router = $this->createStub(RouterInterface::class);

        $authenticator = $this->instanciate(
            $clientRegistry,
            $router,
            'listAdmin',
            'listTrainer',
            'listCollector',
            true,
        );

        $this->assertTrue(
            $authenticator->supports(
                new Request([], [], ['_route' => 'app_connect_'.$this->getAuthenticatorProviderCode().'_check'])
            )
        );
        $this->assertFalse(
            $authenticator->supports(
                new Request([], [], ['_route' => 'app_connect_check'])
            )
        );
    }
}
