<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\TypesCacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @internal
 */
#[CoversClass(TypesCacheInvalidatorService::class)]
final class TypesCacheInvalidatorServiceTest extends TestCase
{
    #[Test]
    public function getSupportedTypes(): void
    {
        $service = new TypesCacheInvalidatorService($this->createStub(TagAwareAdapter::class), ['en', 'fr']);

        $this->assertSame(['labels', 'types'], $service->getSupportedTypes());
    }

    #[Test]
    public function invalidate(): void
    {
        $cachePool = new ArrayAdapter();
        $cache = new TagAwareAdapter($cachePool, new ArrayAdapter());

        $cache->get('douze', fn () => 'DouZe');
        $cache->get('types', fn () => 'whatever');

        $service = new TypesCacheInvalidatorService($cache, ['en', 'fr']);
        $service->invalidate();

        $this->assertTrue($cache->hasItem('douze'));
        $this->assertFalse($cache->hasItem('types'));
    }

    #[Test]
    public function invalidateEveryLanguage(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter());

        foreach (['douze', 'types', 'types_en', 'types_fr', 'types_de'] as $key) {
            $cache->get($key, fn () => 'whatever');
        }

        new TypesCacheInvalidatorService($cache, ['en', 'fr'])->invalidate();

        $this->assertFalse($cache->hasItem('types'));
        $this->assertFalse($cache->hasItem('types_en'));
        $this->assertFalse($cache->hasItem('types_fr'));
        // Not an enabled language, nor a label
        $this->assertTrue($cache->hasItem('types_de'));
        $this->assertTrue($cache->hasItem('douze'));
    }
}
