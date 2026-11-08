<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;

class GetFormsApiService extends AbstractApiService
{
    /**
     * @return string[][]
     */
    public function getFormsCategory(?string $lang = null): array
    {
        /** @var string[][] */
        return $this->getForms($lang)['category'];
    }

    /**
     * @return string[][]
     */
    public function getFormsRegional(?string $lang = null): array
    {
        /** @var string[][] */
        return $this->getForms($lang)['regional'];
    }

    /**
     * @return string[][]
     */
    public function getFormsSpecial(?string $lang = null): array
    {
        /** @var string[][] */
        return $this->getForms($lang)['special'];
    }

    /**
     * @return string[][]
     */
    public function getFormsVariant(?string $lang = null): array
    {
        /** @var string[][] */
        return $this->getForms($lang)['variant'];
    }

    /**
     * @return array{category: string[][], regional: string[][], special: string[][], variant: string[][]}
     */
    private function getForms(?string $lang): array
    {
        $json = $this->cache->get(KeyMaker::getFormsKey($lang), function () use ($lang) {
            return $this->requestContent('GET', '/forms', $this->langOptions($lang));
        });

        /** @var array{category: string[][], regional: string[][], special: string[][], variant: string[][]} */
        return JsonDecoder::decode($json);
    }
}
