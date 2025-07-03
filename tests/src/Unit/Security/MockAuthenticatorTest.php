<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\MockAuthenticator;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(MockAuthenticator::class)]
class MockAuthenticatorTest extends AbstractAuthenticatorTesting
{
    #[\Override]
    protected function getAuthenticatorClassName(): string
    {
        return MockAuthenticator::class;
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
