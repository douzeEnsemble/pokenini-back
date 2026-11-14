<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;
use Symfony\Contracts\Cache\ItemInterface;

class GetActionCatalogApiService extends AbstractApiService
{
    private const int TTL_SECONDS = 3600;

    /**
     * @return list<array{type: string, name: string, depends_on: list<string>}>
     */
    public function get(): array
    {
        $json = $this->cache->get(
            KeyMaker::getActionCatalogKey(),
            function (ItemInterface $item): string {
                $item->expiresAfter(self::TTL_SECONDS);

                return $this->requestContent('GET', '/istration/actions');
            }
        );

        /** @var list<array{type: string, name: string, depends_on: list<string>}> */
        return JsonDecoder::decode($json);
    }

    public function has(string $type, string $name): bool
    {
        foreach ($this->get() as $action) {
            if ($action['type'] === $type && $action['name'] === $name) {
                return true;
            }
        }

        return false;
    }
}
