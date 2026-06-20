<?php

declare(strict_types=1);

namespace App\Tests\Unit\Trait;

use App\DTO\ElectionVote;

trait ElectionVoteFactory
{
    /**
     * @param string[] $winnersSlugs
     * @param string[] $losersSlugs
     */
    private function makeVote(string $dexSlug, string $electionSlug, array $winnersSlugs, array $losersSlugs): ElectionVote
    {
        $vote = new ElectionVote();
        $vote->dexSlug = $dexSlug;
        $vote->electionSlug = $electionSlug;
        $vote->winnersSlugs = $winnersSlugs;
        $vote->losersSlugs = $losersSlugs;

        return $vote;
    }
}
