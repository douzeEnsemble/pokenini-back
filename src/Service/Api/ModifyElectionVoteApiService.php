<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\DTO\ElectionVote;

class ModifyElectionVoteApiService extends AbstractApiService
{
    public function vote(
        string $trainerId,
        ElectionVote $electionVote,
    ): void {
        $this->requestContent(
            'POST',
            '/election/vote',
            [
                'body' => json_encode([
                    'trainer' => ['external_id' => $trainerId],
                    'dex_slug' => $electionVote->dexSlug,
                    'election_slug' => $electionVote->electionSlug,
                    'winners_slugs' => $electionVote->filteredWinners(),
                    'losers_slugs' => $electionVote->filteredLosers(),
                ]),
            ]
        );
    }
}
