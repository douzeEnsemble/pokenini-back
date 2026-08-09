<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\ElectionVote;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ElectionVote::class)]
final class ElectionVoteTest extends TestCase
{
    #[Test]
    public function filteredWinnersRemovesEmptyStrings(): void
    {
        $vote = new ElectionVote();
        $vote->winnersSlugs = ['pikachu', '', 'ivysaur'];

        $this->assertSame(['pikachu', 'ivysaur'], $vote->filteredWinners());
    }

    #[Test]
    public function filteredLosersRemovesEmptyStringsAndWinners(): void
    {
        $vote = new ElectionVote();
        $vote->winnersSlugs = ['bulbasaur', '', 'ivysaur'];
        $vote->losersSlugs = ['charmander', 'bulbasaur', ''];

        $this->assertSame(['bulbasaur', 'ivysaur'], $vote->filteredWinners());
        $this->assertSame(['charmander'], $vote->filteredLosers());
    }

    #[Test]
    public function filteredWinnersWithNoDuplication(): void
    {
        $vote = new ElectionVote();
        $vote->winnersSlugs = ['pikachu', 'pichu'];
        $vote->losersSlugs = ['pichu', 'pikachu', 'raichu'];

        $this->assertSame(['pikachu', 'pichu'], $vote->filteredWinners());
        $this->assertSame(['raichu'], $vote->filteredLosers());
    }

    #[Test]
    public function filteredLosersWithAllLosers(): void
    {
        $vote = new ElectionVote();
        $vote->winnersSlugs = [];
        $vote->losersSlugs = ['pikachu', 'pichu', 'raichu'];

        $this->assertSame([], $vote->filteredWinners());
        $this->assertSame(['pikachu', 'pichu', 'raichu'], $vote->filteredLosers());
    }

    #[Test]
    public function filteredWinnersWithAllWinners(): void
    {
        $vote = new ElectionVote();
        $vote->winnersSlugs = ['pikachu', 'pichu', 'raichu'];
        $vote->losersSlugs = [];

        $this->assertSame(['pikachu', 'pichu', 'raichu'], $vote->filteredWinners());
        $this->assertSame([], $vote->filteredLosers());
    }

    #[Test]
    public function defaultValues(): void
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
