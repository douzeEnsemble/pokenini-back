<?php

declare(strict_types=1);

namespace App\Security;

class MockAuthenticator extends AbstractAuthenticator
{
    #[\Override]
    protected function getProviderCode(): string
    {
        return 'mock';
    }

    #[\Override]
    protected function getProviderName(): string
    {
        return 'Mock';
    }
}
