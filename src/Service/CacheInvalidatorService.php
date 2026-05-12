<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\CacheInvalidator\CacheInvalidatorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class CacheInvalidatorService
{
    /**
     * @param iterable<CacheInvalidatorInterface> $invalidators
     */
    public function __construct(
        #[AutowireIterator('app.cache_invalidator')]
        private readonly iterable $invalidators,
    ) {}

    public function invalidate(string $type): void
    {
        $matched = false;

        foreach ($this->invalidators as $invalidator) {
            if (in_array($type, $invalidator->getSupportedTypes(), true)) {
                $invalidator->invalidate();
                $matched = true;
            }
        }

        if (!$matched) {
            throw new \InvalidArgumentException("Invalid type '{$type}'");
        }
    }
}
