<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\RateLimit;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration/action')]
final class AdminActionCalculateController extends AbstractAdminActionController
{
    #[\Override()]
    #[Route(
        '/calculate/{name}',
        methods: ['POST'],
        condition: "params['name']
            in [
                'game_bundles_availabilities',
                'game_bundles_shinies_availabilities',
                'collections_availabilities',
                'dex_availabilities',
                'pokemon_availabilities',
            ]"
    )]
    #[RateLimit(limiter: 'write_api', key: new Expression("request.headers.get('Authorization') ?? request.getClientIp() ?? 'unknown'"))]
    public function process(string $name): JsonResponse
    {
        return $this->execute($name, 'calculate');
    }
}
