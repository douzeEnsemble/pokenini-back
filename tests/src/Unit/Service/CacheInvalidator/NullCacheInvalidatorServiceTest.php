<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\NullCacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(NullCacheInvalidatorService::class)]
final class NullCacheInvalidatorServiceTest extends TestCase
{
    #[Test]
    public function getSupportedTypes(): void
    {
        $service = new NullCacheInvalidatorService();

        $this->assertSame(['pokemons'], $service->getSupportedTypes());
    }

    #[Test]
    public function invalidateDoesNothing(): void
    {
        $this->expectNotToPerformAssertions();

        $service = new NullCacheInvalidatorService();
        $service->invalidate();
    }
}
