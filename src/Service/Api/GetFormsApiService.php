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
    public function getFormsCategory(): array
    {
        /** @var string[][] */
        return $this->getForms()['category'];
    }

    /**
     * @return string[][]
     */
    public function getFormsRegional(): array
    {
        /** @var string[][] */
        return $this->getForms()['regional'];
    }

    /**
     * @return string[][]
     */
    public function getFormsSpecial(): array
    {
        /** @var string[][] */
        return $this->getForms()['special'];
    }

    /**
     * @return string[][]
     */
    public function getFormsVariant(): array
    {
        /** @var string[][] */
        return $this->getForms()['variant'];
    }

    /**
     * @return array{category: string[][], regional: string[][], special: string[][], variant: string[][]}
     */
    private function getForms(): array
    {
        $json = $this->cache->get(KeyMaker::getFormsKey(), function () {
            return $this->requestContent('GET', '/forms');
        });

        /** @var array{category: string[][], regional: string[][], special: string[][], variant: string[][]} */
        return JsonDecoder::decode($json);
    }
}
