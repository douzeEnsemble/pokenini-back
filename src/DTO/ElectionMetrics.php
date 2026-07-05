<?php

declare(strict_types=1);

namespace App\DTO;

use App\Helper\TotalRoundCountHelper;

final class ElectionMetrics
{
    public int $underMaxViewCount;
    public int $maxViewCount;

    public int $roundCount;
    public float $winnerAverage;
    public int $totalRoundCount;

    /** @var array{view_count: array{sum: int, max: int}, win_count: array{sum: int, max: int}, completion: array{under_max_count: int, at_max_count: int}, dex_total_count: int} */
    public array $raw;

    /**
     * @param array{view_count: array{sum: int, max: int}, win_count: array{sum: int, max: int}, completion: array{under_max_count: int, at_max_count: int}, dex_total_count: int} $values
     */
    public function __construct(array $values, int $perViewCount)
    {
        $this->underMaxViewCount = $values['completion']['under_max_count'];
        $this->maxViewCount = $values['completion']['at_max_count'];
        $this->raw = $values;

        $this->roundCount = (int) round($values['view_count']['sum'] / $perViewCount);
        $this->winnerAverage = 4.0;
        if (0 !== $this->roundCount) {
            $this->winnerAverage = round($values['win_count']['sum'] / $this->roundCount, 2);
        }

        $this->totalRoundCount = TotalRoundCountHelper::calculate(
            $values['dex_total_count'],
            $perViewCount,
            $this->winnerAverage,
        );
    }
}
