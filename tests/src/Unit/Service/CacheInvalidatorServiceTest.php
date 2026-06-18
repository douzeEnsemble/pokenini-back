<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\CacheInvalidator\CacheInvalidatorInterface;
use App\Service\CacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CacheInvalidatorService::class)]
final class CacheInvalidatorServiceTest extends TestCase
{
    public function testInvalidateCallsMatchingInvalidator(): void
    {
        $matchingInvalidator = $this->createMock(CacheInvalidatorInterface::class);
        $matchingInvalidator->method('getSupportedTypes')->willReturn(['foo']);
        $matchingInvalidator->expects($this->once())->method('invalidate');

        $nonMatchingInvalidator = $this->createMock(CacheInvalidatorInterface::class);
        $nonMatchingInvalidator->method('getSupportedTypes')->willReturn(['bar']);
        $nonMatchingInvalidator->expects($this->never())->method('invalidate');

        $service = new CacheInvalidatorService([$matchingInvalidator, $nonMatchingInvalidator]);
        $service->invalidate('foo');
    }

    public function testInvalidateCallsAllMatchingInvalidators(): void
    {
        $invalidator1 = $this->createMock(CacheInvalidatorInterface::class);
        $invalidator1->method('getSupportedTypes')->willReturn(['foo', 'bar']);
        $invalidator1->expects($this->once())->method('invalidate');

        $invalidator2 = $this->createMock(CacheInvalidatorInterface::class);
        $invalidator2->method('getSupportedTypes')->willReturn(['foo']);
        $invalidator2->expects($this->once())->method('invalidate');

        $service = new CacheInvalidatorService([$invalidator1, $invalidator2]);
        $service->invalidate('foo');
    }

    public function testInvalidateUnknownTypeThrows(): void
    {
        $invalidator = $this->createStub(CacheInvalidatorInterface::class);
        $invalidator->method('getSupportedTypes')->willReturn(['foo']);

        $service = new CacheInvalidatorService([$invalidator]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains("Invalid type 'unknown'");
        $service->invalidate('unknown');
    }
}
