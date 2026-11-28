<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Attribute\WriteApiRateLimit;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration/action')]
final class AdminActionInvalidateController extends AbstractAdminActionController
{
    #[\Override()]
    #[Route(
        '/invalidate/{name}',
        methods: ['DELETE'],
        condition: "params['name']
            in [
                'labels',
                'catch_states',
                'types',
                'dex',
                'albums',
                'reports',
                'actions',
            ]"
    )]
    #[WriteApiRateLimit]
    public function process(string $name): JsonResponse
    {
        return $this->execute($name, 'invalidate');
    }
}
