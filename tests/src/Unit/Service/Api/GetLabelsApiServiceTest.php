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
    public function getCatchStatesInALanguage(): void
    {
        $this->getService('catch_states', 'fr')->getCatchStates('fr');
    }

    #[Test]
    public function getTypesInALanguage(): void
    {
        $this->getService('types', 'fr')->getTypes('fr');
    }

    #[Test]
    public function getFormsCategoryInALanguage(): void
    {
        $this->getService('forms_category', 'fr')->getFormsCategory('fr');
    }

    #[Test]
    public function getFormsRegionalInALanguage(): void
    {
        $this->getService('forms_regional', 'fr')->getFormsRegional('fr');
    }

    #[Test]
    public function getFormsSpecialInALanguage(): void
    {
        $this->getService('forms_special', 'fr')->getFormsSpecial('fr');
    }

    #[Test]
    public function getFormsVariantInALanguage(): void
    {
        $this->getService('forms_variant', 'fr')->getFormsVariant('fr');
    }

    #[Test]
    public function getGameBundlesInALanguage(): void
    {
        $this->getService('game_bundles', 'fr')->getGameBundles('fr');
    }

    #[Test]
    public function getCollectionsInALanguage(): void
    {
        $this->getService('collections', 'fr')->getCollections('fr');
    }

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
    private function getService(string $type, ?string $lang = null): GetLabelsApiService
    {
        $getCatchStatesService = $this->createMock(GetCatchStatesApiService::class);
        $getCatchStatesService
            ->expects($this->exactly('catch_states' === $type ? 1 : 0))
            ->method('get')
            ->with($lang)
            ->willReturn([])
        ;

        $getTypesService = $this->createMock(GetTypesApiService::class);
        $getTypesService
            ->expects($this->exactly('types' === $type ? 1 : 0))
            ->method('get')
            ->with($lang)
            ->willReturn([])
        ;

        $getFormsService = $this->createMock(GetFormsApiService::class);
        $getFormsService
            ->expects($this->exactly('forms_category' === $type ? 1 : 0))
            ->method('getFormsCategory')
            ->with($lang)
            ->willReturn([])
        ;
        $getFormsService
            ->expects($this->exactly('forms_regional' === $type ? 1 : 0))
            ->method('getFormsRegional')
            ->with($lang)
            ->willReturn([])
        ;
        $getFormsService
            ->expects($this->exactly('forms_special' === $type ? 1 : 0))
            ->method('getFormsSpecial')
            ->with($lang)
            ->willReturn([])
        ;
        $getFormsService
            ->expects($this->exactly('forms_variant' === $type ? 1 : 0))
            ->method('getFormsVariant')
            ->with($lang)
            ->willReturn([])
        ;

        $getGameBundlesService = $this->createMock(GetGameBundlesApiService::class);
        $getGameBundlesService
            ->expects($this->exactly('game_bundles' === $type ? 1 : 0))
            ->method('get')
            ->with($lang)
            ->willReturn([])
        ;

        $getCollectionsService = $this->createMock(GetCollectionsApiService::class);
        $getCollectionsService
            ->expects($this->exactly('collections' === $type ? 1 : 0))
            ->method('get')
            ->with($lang)
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
