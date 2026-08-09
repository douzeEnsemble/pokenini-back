<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\ReportsCacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @internal
 */
#[CoversClass(ReportsCacheInvalidatorService::class)]
final class ReportsCacheInvalidatorServiceTest extends TestCase
{
    #[Test]
    public function getSupportedTypes(): void
    {
        $service = new ReportsCacheInvalidatorService($this->createStub(TagAwareAdapter::class));

        $this->assertSame(['reports'], $service->getSupportedTypes());
    }

    #[Test]
    public function invalidate(): void
    {
        $cachePool = new ArrayAdapter();
        $cache = new TagAwareAdapter($cachePool, new ArrayAdapter());

        $cache->get('douze', fn () => 'DouZe');
        $cache->get('reports', fn () => 'whatever');

        $service = new ReportsCacheInvalidatorService($cache);
        $service->invalidate();

        $this->assertTrue($cache->hasItem('douze'));
        $this->assertFalse($cache->hasItem('reports'));
    }
}
