<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration/action')]
class AdminActionCalculateController extends AbstractAdminActionController
{
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
    public function process(string $name): JsonResponse
    {
        return $this->execute($name, 'calculate');
    }
}
