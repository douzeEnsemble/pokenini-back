<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\CreditsCacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @internal
 */
#[CoversClass(CreditsCacheInvalidatorService::class)]
final class CreditsCacheInvalidatorServiceTest extends TestCase
{
    #[Test]
    public function getSupportedTypes(): void
    {
        $service = new CreditsCacheInvalidatorService($this->createStub(TagAwareAdapter::class));

        $this->assertSame(['labels'], $service->getSupportedTypes());
    }

    #[Test]
    public function invalidate(): void
    {
        $cachePool = new ArrayAdapter();
        $cache = new TagAwareAdapter($cachePool, new ArrayAdapter());

        $cache->get('douze', fn () => 'DouZe');
        $cache->get('credits_v2', fn () => 'whatever');

        $service = new CreditsCacheInvalidatorService($cache);
        $service->invalidate();

        $this->assertTrue($cache->hasItem('douze'));
        $this->assertFalse($cache->hasItem('credits_v2'));
    }
}
