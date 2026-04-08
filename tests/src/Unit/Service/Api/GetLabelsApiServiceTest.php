<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetCatchStatesApiService;
use App\Service\Api\GetCollectionsApiService;
use App\Service\Api\GetFormsApiService;
use App\Service\Api\GetGameBundlesApiService;
use App\Service\Api\GetLabelsApiService;
use App\Service\Api\GetTypesApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetLabelsApiService::class)]
final class GetLabelsApiServiceTest extends TestCase
{
    public function testGetCatchStates(): void
    {
        $this->getService('catch_states')->getCatchStates();
    }

    public function testGetTypes(): void
    {
        $this->getService('types')->getTypes();
    }

    public function testGetFormsCategory(): void
    {
        $this->getService('forms_category')->getFormsCategory();
    }

    public function testGetFormsRegional(): void
    {
        $this->getService('forms_regional')->getFormsRegional();
    }

    public function testGetFormsSpecial(): void
    {
        $this->getService('forms_special')->getFormsSpecial();
    }

    public function testGetFormsVariant(): void
    {
        $this->getService('forms_variant')->getFormsVariant();
    }

    public function testGetGameBundles(): void
    {
        $this->getService('game_bundles')->getGameBundles();
    }

    public function testGetCollections(): void
    {
        $this->getService('collections')->getCollections();
    }

    /**
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    private function getService(string $type): GetLabelsApiService
    {
        $getCatchStatesService = $this->createMock(GetCatchStatesApiService::class);
        $getCatchStatesService
            ->expects($this->exactly('catch_states' === $type ? 1 : 0))
            ->method('get')
            ->willReturn([])
        ;

        $getTypesService = $this->createMock(GetTypesApiService::class);
        $getTypesService
            ->expects($this->exactly('types' === $type ? 1 : 0))
            ->method('get')
            ->willReturn([])
        ;

        $getFormsService = $this->createMock(GetFormsApiService::class);
        $getFormsService
            ->expects($this->exactly('forms_category' === $type ? 1 : 0))
            ->method('getFormsCategory')
            ->willReturn([])
        ;
        $getFormsService
            ->expects($this->exactly('forms_regional' === $type ? 1 : 0))
            ->method('getFormsRegional')
            ->willReturn([])
        ;
        $getFormsService
            ->expects($this->exactly('forms_special' === $type ? 1 : 0))
            ->method('getFormsSpecial')
            ->willReturn([])
        ;
        $getFormsService
            ->expects($this->exactly('forms_variant' === $type ? 1 : 0))
            ->method('getFormsVariant')
            ->willReturn([])
        ;

        $getGameBundlesService = $this->createMock(GetGameBundlesApiService::class);
        $getGameBundlesService
            ->expects($this->exactly('game_bundles' === $type ? 1 : 0))
            ->method('get')
            ->willReturn([])
        ;

        $getCollectionsService = $this->createMock(GetCollectionsApiService::class);
        $getCollectionsService
            ->expects($this->exactly('collections' === $type ? 1 : 0))
            ->method('get')
            ->willReturn([])
        ;

        return new GetLabelsApiService(
            $getCatchStatesService,
            $getTypesService,
            $getFormsService,
            $getGameBundlesService,
            $getCollectionsService,
        );
    }
}
