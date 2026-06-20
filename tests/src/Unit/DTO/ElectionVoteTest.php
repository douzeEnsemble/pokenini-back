<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\ElectionVote;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ElectionVote::class)]
final class ElectionVoteTest extends TestCase
{
    public function testFilteredWinnersRemovesEmptyStrings(): void
    {
        $vote = new ElectionVote();
        $vote->winnersSlugs = ['pikachu', '', 'ivysaur'];

        $this->assertSame(['pikachu', 'ivysaur'], $vote->filteredWinners());
    }

    public function testFilteredLosersRemovesEmptyStringsAndWinners(): void
    {
        $vote = new ElectionVote();
        $vote->winnersSlugs = ['bulbasaur', '', 'ivysaur'];
        $vote->losersSlugs = ['charmander', 'bulbasaur', ''];

        $this->assertSame(['bulbasaur', 'ivysaur'], $vote->filteredWinners());
        $this->assertSame(['charmander'], $vote->filteredLosers());
    }

    public function testFilteredWinnersWithNoDuplication(): void
    {
        $vote = new ElectionVote();
        $vote->winnersSlugs = ['pikachu', 'pichu'];
        $vote->losersSlugs = ['pichu', 'pikachu', 'raichu'];

        $this->assertSame(['pikachu', 'pichu'], $vote->filteredWinners());
        $this->assertSame(['raichu'], $vote->filteredLosers());
    }

    public function testFilteredLosersWithAllLosers(): void
    {
        $vote = new ElectionVote();
        $vote->winnersSlugs = [];
        $vote->losersSlugs = ['pikachu', 'pichu', 'raichu'];

        $this->assertSame([], $vote->filteredWinners());
        $this->assertSame(['pikachu', 'pichu', 'raichu'], $vote->filteredLosers());
    }

    public function testFilteredWinnersWithAllWinners(): void
    {
        $vote = new ElectionVote();
        $vote->winnersSlugs = ['pikachu', 'pichu', 'raichu'];
        $vote->losersSlugs = [];

        $this->assertSame(['pikachu', 'pichu', 'raichu'], $vote->filteredWinners());
        $this->assertSame([], $vote->filteredLosers());
    }

    public function testDefaultValues(): void
    {
        $vote = new ElectionVote();

        $this->assertSame('', $vote->dexSlug);
        $this->assertSame('', $vote->electionSlug);
        $this->assertSame([], $vote->winnersSlugs);
        $this->assertSame([], $vote->losersSlugs);
        $this->assertSame([], $vote->filteredWinners());
        $this->assertSame([], $vote->filteredLosers());
    }
}
