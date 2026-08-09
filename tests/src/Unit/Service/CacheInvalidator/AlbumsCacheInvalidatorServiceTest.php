<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\AlbumsCacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @internal
 */
#[CoversClass(AlbumsCacheInvalidatorService::class)]
final class AlbumsCacheInvalidatorServiceTest extends TestCase
{
    #[Test]
    public function invalidate(): void
    {
        $cachePool = new ArrayAdapter();
        $cache = new TagAwareAdapter($cachePool, new ArrayAdapter());

        $cache->get('douze', fn () => 'DouZe');
        $cache->get('album_home_123', function (ItemInterface $item) {
            $item->tag(['album']);

            return 'whatever';
        });
        $cache->get('album_home_456', function (ItemInterface $item) {
            $item->tag(['album']);

            return 'whatever';
        });

        $service = new AlbumsCacheInvalidatorService($cache);
        $service->invalidate();

        $this->assertTrue($cache->hasItem('douze'));
        $this->assertFalse($cache->hasItem('album_home_123'));
        $this->assertFalse($cache->hasItem('album_home_456'));
    }

    #[Test]
    public function getSupportedTypes(): void
    {
        $service = new AlbumsCacheInvalidatorService($this->createStub(TagAwareAdapter::class));

        $this->assertContains('albums', $service->getSupportedTypes());
        $this->assertContains('dex_availabilities', $service->getSupportedTypes());
        $this->assertContains('regional_dex_numbers', $service->getSupportedTypes());
    }

    #[Test]
    public function invalidateMock(): void
    {
        $cache = $this->createMock(TagAwareAdapter::class);
        $cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['album'])
        ;

        $service = new AlbumsCacheInvalidatorService($cache);
        $service->invalidate();
    }
}
