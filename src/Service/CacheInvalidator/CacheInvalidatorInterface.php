<?php

declare(strict_types=1);

namespace App\Service\CacheInvalidator;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.cache_invalidator')]
interface CacheInvalidatorInterface
{
    /** @return string[] */
    public function getSupportedTypes(): array;

    public function invalidate(): void;
}
