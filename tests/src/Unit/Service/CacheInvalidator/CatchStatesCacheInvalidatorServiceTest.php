<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\CatchStatesCacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @internal
 */
#[CoversClass(CatchStatesCacheInvalidatorService::class)]
final class CatchStatesCacheInvalidatorServiceTest extends TestCase
{
    #[Test]
    public function getSupportedTypes(): void
    {
        $service = new CatchStatesCacheInvalidatorService($this->createStub(TagAwareAdapter::class), ['en', 'fr']);

        $this->assertSame(['labels', 'catch_states'], $service->getSupportedTypes());
    }

    #[Test]
    public function invalidate(): void
    {
        $cachePool = new ArrayAdapter();
        $cache = new TagAwareAdapter($cachePool, new ArrayAdapter());

        $cache->get('douze', fn () => 'DouZe');
        $cache->get('catch_states', fn () => 'whatever');

        $service = new CatchStatesCacheInvalidatorService($cache, ['en', 'fr']);
        $service->invalidate();

        $this->assertTrue($cache->hasItem('douze'));
        $this->assertFalse($cache->hasItem('catch_states'));
    }

    #[Test]
    public function invalidateEveryLanguage(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter());

        foreach (['douze', 'catch_states', 'catch_states_en', 'catch_states_fr', 'catch_states_de'] as $key) {
            $cache->get($key, fn () => 'whatever');
        }

        new CatchStatesCacheInvalidatorService($cache, ['en', 'fr'])->invalidate();

        $this->assertFalse($cache->hasItem('catch_states'));
        $this->assertFalse($cache->hasItem('catch_states_en'));
        $this->assertFalse($cache->hasItem('catch_states_fr'));
        // Not an enabled language, nor a label
        $this->assertTrue($cache->hasItem('catch_states_de'));
        $this->assertTrue($cache->hasItem('douze'));
    }
}
