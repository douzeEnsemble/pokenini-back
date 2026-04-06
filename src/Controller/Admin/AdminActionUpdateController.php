<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration/action/update')]
final class AdminActionUpdateController extends AbstractAdminActionController
{
    #[\Override()]
    #[Route(
        '/{name}',
        methods: ['POST'],
        condition: "params['name']
            in [
                'labels',
                'games_collections_and_dex',
                'pokemons',
                'games_availabilities',
                'games_shinies_availabilities',
                'regional_dex_numbers',
                'collections_availabilities',
            ]"
    )]
    public function process(string $name): JsonResponse
    {
        return $this->execute($name, 'update');
    }
}
