<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Security\UserTokenService;
use App\Service\Api\GetElectionTopApiService;
use App\Service\GetElectionTopService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetElectionTopService::class)]
final class GetElectionTopServiceTest extends TestCase
{
    public function testGetTop(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $apiItem = [
            'pokemon' => [
                'slug' => 'bulbasaur',
                'labels' => [
                    'name' => 'Bulbasaur',
                    'simplified_name' => 'Bulbasaur',
                    'french_name' => 'Bulbizarre',
                    'simplified_french_name' => 'Bulbizarre',
                    'forms_label' => null,
                    'forms_french_label' => null,
                ],
                'national_dex_number' => 1,
                'regional_dex_number' => null,
                'icon' => 'bulbasaur',
                'family_order' => 0,
                'family_lead' => ['slug' => 'bulbasaur'],
                'original_game_bundle' => ['slug' => 'redgreenblueyellow'],
                'order_number' => '9999-0001-000',
                'game_bundles' => [
                    'normal' => [['slug' => 'redgreenblueyellow']],
                    'shiny' => [['slug' => 'redgreenblueyellow']],
                ],
            ],
            'forms' => null,
            'types' => [
                'primary' => ['slug' => 'grass', 'name' => 'Grass', 'french_name' => 'Plante', 'color' => '#78C850'],
                'secondary' => null,
            ],
            'score' => ['elo' => 1040, 'significance' => true],
        ];

        $apiService = $this->createMock(GetElectionTopApiService::class);
        $apiService
            ->expects($this->once())
            ->method('getTop')
            ->with(
                '8800088',
                'demo',
                'whatever',
                12,
            )
            ->willReturn([$apiItem])
        ;

        $service = new GetElectionTopService($userTokenService, $apiService, 12);

        $result = $service->getTop('demo', 'whatever');

        $this->assertCount(1, $result);
        $this->assertSame('bulbasaur', $result[0]['pokemon']['icon']);
        $this->assertSame('bulbasaur', $result[0]['pokemon']['slug']);
        $this->assertSame('Bulbasaur', $result[0]['pokemon']['labels']['simplified_name']);
        $this->assertSame('Bulbizarre', $result[0]['pokemon']['labels']['simplified_french_name']);
        $this->assertNull($result[0]['forms']);
        $this->assertSame($apiItem['score'], $result[0]['score']);
    }
}
