<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Security\UserTokenService;
use App\Service\Api\GetElectionReportApiService;
use App\Service\GetElectionReportService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetElectionReportService::class)]
final class GetElectionReportServiceTest extends TestCase
{
    public function testGetReport(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $apiTop = [
            [
                'pokemon' => ['slug' => 'bulbasaur'],
                'forms' => null,
                'types' => ['primary' => ['slug' => 'grass', 'name' => 'Grass', 'french_name' => 'Plante', 'color' => '#78C850'], 'secondary' => null],
                'score' => ['elo' => 1040, 'significance' => true],
            ],
        ];
        $rawMetrics = [
            'view_count' => ['sum' => 12, 'max' => 4],
            'win_count' => ['sum' => 5, 'max' => 14],
            'completion' => ['under_max_count' => 24, 'at_max_count' => 5],
            'dex_total_count' => 48,
        ];

        $apiService = $this->createMock(GetElectionReportApiService::class);
        $apiService
            ->expects($this->once())
            ->method('getReport')
            ->with('8800088', 'demo', 'whatever', 12)
            ->willReturn(['top' => $apiTop, 'metrics' => $rawMetrics])
        ;

        $service = new GetElectionReportService($userTokenService, $apiService, 12, 12);

        $result = $service->getReport('demo', 'whatever');

        $this->assertSame($apiTop, $result['top']);
        $this->assertSame(24, $result['metrics']->underMaxViewCount);
        $this->assertSame(5, $result['metrics']->maxViewCount);
        $this->assertSame($rawMetrics, $result['metrics']->raw);
    }
}
