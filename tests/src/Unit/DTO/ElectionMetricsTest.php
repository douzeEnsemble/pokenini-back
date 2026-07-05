<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\ElectionMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ElectionMetrics::class)]
final class ElectionMetricsTest extends TestCase
{
    public function testParsesUnderMaxViewCountFromCompletion(): void
    {
        $metrics = $this->make([
            'view_count' => ['sum' => 82, 'max' => 1],
            'win_count' => ['sum' => 41, 'max' => 1],
            'completion' => ['under_max_count' => 3, 'at_max_count' => 5],
            'dex_total_count' => 48,
        ], 12);

        $this->assertSame(3, $metrics->underMaxViewCount);
    }

    public function testParsesMaxViewCountFromCompletion(): void
    {
        $metrics = $this->make([
            'view_count' => ['sum' => 82, 'max' => 1],
            'win_count' => ['sum' => 41, 'max' => 1],
            'completion' => ['under_max_count' => 1, 'at_max_count' => 7],
            'dex_total_count' => 48,
        ], 12);

        $this->assertSame(7, $metrics->maxViewCount);
    }

    public function testStoresRaw(): void
    {
        $input = [
            'view_count' => ['sum' => 82, 'max' => 1],
            'win_count' => ['sum' => 41, 'max' => 1],
            'completion' => ['under_max_count' => 1, 'at_max_count' => 5],
            'dex_total_count' => 48,
        ];

        $metrics = new ElectionMetrics($input, 12);

        $this->assertSame($input, $metrics->raw);
    }

    public function testComputesRoundCountWinnerAverageAndTotalRoundCount(): void
    {
        $metrics = $this->make([
            'view_count' => ['sum' => 82, 'max' => 1],
            'win_count' => ['sum' => 41, 'max' => 1],
            'completion' => ['under_max_count' => 1, 'at_max_count' => 5],
            'dex_total_count' => 48,
        ], 12);

        $this->assertSame(7, $metrics->roundCount);
        $this->assertSame(5.86, $metrics->winnerAverage);
        $this->assertSame(8, $metrics->totalRoundCount);
    }

    public function testRoundCountRoundsToNearestInsteadOfCeiling(): void
    {
        $metrics = $this->make([
            'view_count' => ['sum' => 25, 'max' => 1],
            'win_count' => ['sum' => 10, 'max' => 1],
            'completion' => ['under_max_count' => 0, 'at_max_count' => 0],
            'dex_total_count' => 24,
        ], 12);

        $this->assertSame(2, $metrics->roundCount);
    }

    public function testDefaultsWinnerAverageWhenRoundCountIsZero(): void
    {
        $metrics = $this->make([
            'view_count' => ['sum' => 0, 'max' => 0],
            'win_count' => ['sum' => 0, 'max' => 0],
            'completion' => ['under_max_count' => 0, 'at_max_count' => 0],
            'dex_total_count' => 0,
        ], 12);

        $this->assertSame(0, $metrics->roundCount);
        $this->assertSame(4.0, $metrics->winnerAverage);
        $this->assertSame(0, $metrics->totalRoundCount);
    }

    /**
     * @param array{view_count: array{sum: int, max: int}, win_count: array{sum: int, max: int}, completion: array{under_max_count: int, at_max_count: int}, dex_total_count: int} $input
     */
    private function make(array $input, int $perViewCount): ElectionMetrics
    {
        return new ElectionMetrics($input, $perViewCount);
    }
}
