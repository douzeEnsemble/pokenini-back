<?php

declare(strict_types=1);

namespace App\DTO;

final class ElectionVote
{
    public string $dexSlug = '';
    public string $electionSlug = '';

    /**
     * @var string[]
     */
    public array $winnersSlugs = [];

    /**
     * @var string[]
     */
    public array $losersSlugs = [];

    /**
     * @return string[]
     */
    public function filteredWinners(): array
    {
        return array_values(array_filter($this->winnersSlugs));
    }

    /**
     * @return string[]
     */
    public function filteredLosers(): array
    {
        $winners = $this->filteredWinners();

        return array_values(array_diff(array_filter($this->losersSlugs), $winners));
    }
}
