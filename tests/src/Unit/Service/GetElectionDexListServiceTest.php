<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Api\GetElectionDexListApiService;
use App\Service\GetElectionDexListService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetElectionDexListService::class)]
final class GetElectionDexListServiceTest extends TestCase
{
    public function testGetAddsRoundMetricsToEachItemWithAReport(): void
    {
        $apiService = $this->createMock(GetElectionDexListApiService::class);
        $apiService
            ->expects($this->once())
            ->method('get')
            ->with('8800088')
            ->willReturn([
                [
                    'slug' => 'demo',
                    'report' => [
                        'top' => [],
                        'metrics' => [
                            'view_count' => ['sum' => 24, 'max' => 4],
                            'win_count' => ['sum' => 12, 'max' => 14],
                            'completion' => ['under_max_count' => 43, 'at_max_count' => 18],
                            'dex_total_count' => 61,
                        ],
                    ],
                ],
                [
                    'slug' => 'not-started-yet',
                    'report' => null,
                ],
            ])
        ;

        $service = new GetElectionDexListService($apiService, 12);

        $result = $service->get('8800088');

        /** @var array{report: array{metrics: array<string, mixed>}} $firstItem */
        $firstItem = $result[0];

        $this->assertSame(
            [
                'view_count' => ['sum' => 24, 'max' => 4],
                'win_count' => ['sum' => 12, 'max' => 14],
                'completion' => ['under_max_count' => 43, 'at_max_count' => 18],
                'dex_total_count' => 61,
                'round_count' => 2,
                'total_round_count' => 13,
                'winner_average' => 6.0,
            ],
            $firstItem['report']['metrics'],
        );
        $this->assertSame(['slug' => 'not-started-yet', 'report' => null], $result[1]);
    }

    public function testGetLeavesReportUntouchedWhenMetricsKeyIsMissing(): void
    {
        $apiService = $this->createMock(GetElectionDexListApiService::class);
        $apiService
            ->expects($this->once())
            ->method('get')
            ->with('8800088')
            ->willReturn([
                [
                    'slug' => 'no-metrics',
                    'report' => [
                        'top' => [],
                    ],
                ],
            ])
        ;

        $service = new GetElectionDexListService($apiService, 12);

        $result = $service->get('8800088');

        $this->assertSame(
            [
                'slug' => 'no-metrics',
                'report' => [
                    'top' => [],
                ],
            ],
            $result[0],
        );
    }

    public function testGetStillProcessesLaterItemsAfterSkippingAnItemWithoutAReport(): void
    {
        $apiService = $this->createMock(GetElectionDexListApiService::class);
        $apiService
            ->expects($this->once())
            ->method('get')
            ->with('8800088')
            ->willReturn([
                [
                    'slug' => 'not-started-yet',
                    'report' => null,
                ],
                [
                    'slug' => 'demo',
                    'report' => [
                        'top' => [],
                        'metrics' => [
                            'view_count' => ['sum' => 24, 'max' => 4],
                            'win_count' => ['sum' => 12, 'max' => 14],
                            'completion' => ['under_max_count' => 43, 'at_max_count' => 18],
                            'dex_total_count' => 61,
                        ],
                    ],
                ],
            ])
        ;

        $service = new GetElectionDexListService($apiService, 12);

        $result = $service->get('8800088');

        $this->assertSame(['slug' => 'not-started-yet', 'report' => null], $result[0]);

        /** @var array{report: array{metrics: array<string, mixed>}} $secondItem */
        $secondItem = $result[1];

        $this->assertSame(
            [
                'view_count' => ['sum' => 24, 'max' => 4],
                'win_count' => ['sum' => 12, 'max' => 14],
                'completion' => ['under_max_count' => 43, 'at_max_count' => 18],
                'dex_total_count' => 61,
                'round_count' => 2,
                'total_round_count' => 13,
                'winner_average' => 6.0,
            ],
            $secondItem['report']['metrics'],
        );
    }

    public function testGetWithPremiumDelegatesToApiService(): void
    {
        $apiService = $this->createMock(GetElectionDexListApiService::class);
        $apiService
            ->expects($this->once())
            ->method('getWithPremium')
            ->with('8800088')
            ->willReturn([])
        ;

        $service = new GetElectionDexListService($apiService, 12);

        $this->assertSame([], $service->getWithPremium('8800088'));
    }

    public function testGetWithUnreleasedAndPremiumDelegatesToApiService(): void
    {
        $apiService = $this->createMock(GetElectionDexListApiService::class);
        $apiService
            ->expects($this->once())
            ->method('getWithUnreleasedAndPremium')
            ->with('8800088')
            ->willReturn([])
        ;

        $service = new GetElectionDexListService($apiService, 12);

        $this->assertSame([], $service->getWithUnreleasedAndPremium('8800088'));
    }
}
