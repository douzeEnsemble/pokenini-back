<?php

declare(strict_types=1);

namespace App\DTO;

final class ElectionMetrics
{
    public int $underMaxViewCount;
    public int $maxViewCount;

    /** @var array{view_count: array{sum: int, max: int}, win_count: array{sum: int, max: int}, completion: array{under_max_count: int, at_max_count: int}, dex_total_count: int} */
    public array $raw;

    /**
     * @param array{view_count: array{sum: int, max: int}, win_count: array{sum: int, max: int}, completion: array{under_max_count: int, at_max_count: int}, dex_total_count: int} $values
     */
    public function __construct(array $values)
    {
        $this->underMaxViewCount = $values['completion']['under_max_count'];
        $this->maxViewCount = $values['completion']['at_max_count'];
        $this->raw = $values;
    }
}
