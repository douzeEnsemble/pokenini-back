<?php

declare(strict_types=1);

namespace App\Controller\Election;

use App\DTO\ElectionVote;
use App\Service\ModifyElectionVoteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\RateLimit;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/election')]
final class ElectionVoteController extends AbstractController
{
    #[Route(
        '/{dexSlug}/{electionSlug}',
        requirements: [
            'dexSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
            'electionSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
        ],
        methods: ['POST']
    )]
    #[RateLimit(limiter: 'write_api', key: new Expression("request.headers.get('Authorization') ?? request.getClientIp() ?? 'unknown'"))]
    public function vote(
        ModifyElectionVoteService $electionVoteService,
        #[MapRequestPayload]
        ElectionVote $electionVote,
        string $dexSlug,
        string $electionSlug = '',
    ): Response {
        $electionVote->dexSlug = $dexSlug;
        $electionVote->electionSlug = $electionSlug;

        $electionVoteService->vote($electionVote);

        return new JsonResponse([], Response::HTTP_OK);
    }
}
