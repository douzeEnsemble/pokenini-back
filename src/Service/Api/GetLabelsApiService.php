<?php

declare(strict_types=1);

namespace App\Service\Api;

class GetLabelsApiService
{
    public function __construct(
        private readonly GetCatchStatesApiService $getCatchStatesService,
        private readonly GetTypesApiService $getTypesService,
        private readonly GetFormsApiService $getFormsService,
        private readonly GetGameBundlesApiService $getGameBundlesService,
        private readonly GetCollectionsApiService $getCollectionsService,
    ) {}

    /**
     * @return string[][]
     */
    public function getCatchStates(?string $lang = null): array
    {
        return $this->getCatchStatesService->get($lang);
    }

    /**
     * @return string[][]
     */
    public function getTypes(?string $lang = null): array
    {
        return $this->getTypesService->get($lang);
    }

    /**
     * @return string[][]
     */
    public function getFormsCategory(?string $lang = null): array
    {
        return $this->getFormsService->getFormsCategory($lang);
    }

    /**
     * @return string[][]
     */
    public function getFormsRegional(?string $lang = null): array
    {
        return $this->getFormsService->getFormsRegional($lang);
    }

    /**
     * @return string[][]
     */
    public function getFormsSpecial(?string $lang = null): array
    {
        return $this->getFormsService->getFormsSpecial($lang);
    }

    /**
     * @return string[][]
     */
    public function getFormsVariant(?string $lang = null): array
    {
        return $this->getFormsService->getFormsVariant($lang);
    }

    /**
     * @return string[][]
     */
    public function getGameBundles(?string $lang = null): array
    {
        return $this->getGameBundlesService->get($lang);
    }

    /**
     * @return string[][]
     */
    public function getCollections(?string $lang = null): array
    {
        return $this->getCollectionsService->get($lang);
    }
}
