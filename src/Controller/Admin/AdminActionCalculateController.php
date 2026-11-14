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
        requirements: ['name' => '[a-z_]+'],
    )]
    #[RateLimit(limiter: 'write_api', key: new Expression("request.headers.get('Authorization') ?? request.getClientIp() ?? 'unknown'"))]
    public function process(string $name): JsonResponse
    {
        return $this->execute($name, 'calculate');
    }
}
