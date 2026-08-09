<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\FormsCacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @internal
 */
#[CoversClass(FormsCacheInvalidatorService::class)]
final class FormsCacheInvalidatorServiceTest extends TestCase
{
    #[Test]
    public function getSupportedTypes(): void
    {
        $service = new FormsCacheInvalidatorService($this->createStub(TagAwareAdapter::class));

        $this->assertSame(['labels'], $service->getSupportedTypes());
    }

    #[Test]
    public function invalidate(): void
    {
        $cachePool = new ArrayAdapter();
        $cache = new TagAwareAdapter($cachePool, new ArrayAdapter());

        $cache->get('douze', fn () => 'DouZe');
        $cache->get('forms', fn () => 'whatever');

        $service = new FormsCacheInvalidatorService($cache);
        $service->invalidate();

        $this->assertTrue($cache->hasItem('douze'));
        $this->assertFalse($cache->hasItem('forms'));
    }

    #[Test]
    public function invalidateWithAMissingOne(): void
    {
        $cachePool = new ArrayAdapter();
        $cache = new TagAwareAdapter($cachePool, new ArrayAdapter());

        $cache->get('douze', fn () => 'DouZe');

        $service = new FormsCacheInvalidatorService($cache);
        $service->invalidate();

        $this->assertTrue($cache->hasItem('douze'));
        $this->assertFalse($cache->hasItem('forms'));
    }
}
