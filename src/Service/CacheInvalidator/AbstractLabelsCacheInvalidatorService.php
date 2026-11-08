<?php

declare(strict_types=1);

namespace App\Service\CacheInvalidator;

use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * The labels are cached once per language, and once for the legacy shape.
 */
abstract class AbstractLabelsCacheInvalidatorService extends AbstractCacheInvalidatorService
{
    /**
     * @param list<string> $locales the enabled locales
     */
    public function __construct(
        TagAwareCacheInterface $cache,
        private readonly array $locales,
    ) {
        parent::__construct($cache);
    }

    /**
     * @return list<null|string> null is the legacy shape
     */
    protected function languages(): array
    {
        return [null, ...$this->locales];
    }
}
