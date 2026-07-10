<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\ElectionCacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @internal
 */
#[CoversClass(ElectionCacheInvalidatorService::class)]
final class ElectionCacheInvalidatorServiceTest extends TestCase
{
    public function testInvalidate(): void
    {
        $cachePool = new ArrayAdapter();
        $cache = new TagAwareAdapter($cachePool, new ArrayAdapter());

        $cache->get('election_dex_list_123', function (ItemInterface $item) {
            $item->tag(['trainer#123']);

            return 'whatever';
        });
        $cache->get('election_dex_list_456', function (ItemInterface $item) {
            $item->tag(['trainer#456']);

            return 'whatever';
        });

        $service = new ElectionCacheInvalidatorService($cache);
        $service->invalidate('123');

        $this->assertFalse($cache->hasItem('election_dex_list_123'));
        $this->assertTrue($cache->hasItem('election_dex_list_456'));
    }

    public function testInvalidateMock(): void
    {
        $cache = $this->createMock(TagAwareAdapter::class);
        $cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['trainer#123'])
        ;

        $service = new ElectionCacheInvalidatorService($cache);
        $service->invalidate('123');
    }
}
