<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration/action')]
class AdminActionInvalidateController extends AbstractAdminActionController
{
    #[Route(
        '/invalidate/{name}',
        methods: ['DELETE'],
        condition: "params['name']
            in [
                'labels',
                'dex',
                'albums',
                'reports',
                'actions',
            ]"
    )]
    public function process(string $name): JsonResponse
    {
        return $this->execute($name, 'invalidate');
    }
}
