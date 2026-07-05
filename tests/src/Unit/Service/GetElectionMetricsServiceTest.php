<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Security\UserTokenService;
use App\Service\Api\GetElectionMetricsApiService;
use App\Service\GetElectionMetricsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetElectionMetricsService::class)]
final class GetElectionMetricsServiceTest extends TestCase
{
    public function testGetMetrics(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $rawData = [
            'view_count' => ['sum' => 12, 'max' => 4],
            'win_count' => ['sum' => 5, 'max' => 14],
            'completion' => ['under_max_count' => 24, 'at_max_count' => 5],
            'dex_total_count' => 48,
        ];

        $apiService = $this->createMock(GetElectionMetricsApiService::class);
        $apiService
            ->expects($this->once())
            ->method('getMetrics')
            ->with(
                '8800088',
                'demo',
                'whatever',
            )
            ->willReturn($rawData)
        ;

        $service = new GetElectionMetricsService($userTokenService, $apiService, 12);

        $metrics = $service->getMetrics('demo', 'whatever');

        $this->assertSame(24, $metrics->underMaxViewCount);
        $this->assertSame(5, $metrics->maxViewCount);
        $this->assertSame($rawData, $metrics->raw);
        $this->assertSame(1, $metrics->roundCount);
        $this->assertSame(5.0, $metrics->winnerAverage);
        $this->assertSame(8, $metrics->totalRoundCount);
    }
}
