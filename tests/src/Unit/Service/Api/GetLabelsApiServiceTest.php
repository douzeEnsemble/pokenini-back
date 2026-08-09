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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetLabelsApiService::class)]
final class GetLabelsApiServiceTest extends TestCase
{
    #[Test]
    public function getCatchStates(): void
    {
        $this->getService('catch_states')->getCatchStates();
    }

    #[Test]
    public function getTypes(): void
    {
        $this->getService('types')->getTypes();
    }

    #[Test]
    public function getFormsCategory(): void
    {
        $this->getService('forms_category')->getFormsCategory();
    }

    #[Test]
    public function getFormsRegional(): void
    {
        $this->getService('forms_regional')->getFormsRegional();
    }

    #[Test]
    public function getFormsSpecial(): void
    {
        $this->getService('forms_special')->getFormsSpecial();
    }

    #[Test]
    public function getFormsVariant(): void
    {
        $this->getService('forms_variant')->getFormsVariant();
    }

    #[Test]
    public function getGameBundles(): void
    {
        $this->getService('game_bundles')->getGameBundles();
    }

    #[Test]
    public function getCollections(): void
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
