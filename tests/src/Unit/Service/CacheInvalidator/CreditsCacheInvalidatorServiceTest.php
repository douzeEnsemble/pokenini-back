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
        $service = new CreditsCacheInvalidatorService($this->createStub(TagAwareAdapter::class), ['en', 'fr']);

        $this->assertSame(['labels'], $service->getSupportedTypes());
    }

    #[Test]
    public function invalidate(): void
    {
        $cachePool = new ArrayAdapter();
        $cache = new TagAwareAdapter($cachePool, new ArrayAdapter());

        $cache->get('douze', fn () => 'DouZe');
        $cache->get('credits_v2', fn () => 'whatever');

        $service = new CreditsCacheInvalidatorService($cache, ['en', 'fr']);
        $service->invalidate();

        $this->assertTrue($cache->hasItem('douze'));
        $this->assertFalse($cache->hasItem('credits_v2'));
    }

    #[Test]
    public function invalidateEveryLanguage(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter());

        foreach (['douze', 'credits_v2', 'credits_v2_en', 'credits_v2_fr', 'credits_v2_de'] as $key) {
            $cache->get($key, fn () => 'whatever');
        }

        new CreditsCacheInvalidatorService($cache, ['en', 'fr'])->invalidate();

        $this->assertFalse($cache->hasItem('credits_v2'));
        $this->assertFalse($cache->hasItem('credits_v2_en'));
        $this->assertFalse($cache->hasItem('credits_v2_fr'));
        // Not an enabled language, nor a credit
        $this->assertTrue($cache->hasItem('credits_v2_de'));
        $this->assertTrue($cache->hasItem('douze'));
    }
}
