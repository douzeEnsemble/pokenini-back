<?php

declare(strict_types=1);

namespace App\Attribute;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpKernel\Attribute\RateLimit;

/**
 * The rate limit of the write endpoints: one bucket per caller, identified by its token, else by its IP.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class WriteApiRateLimit extends RateLimit
{
    public function __construct()
    {
        parent::__construct(
            limiter: 'write_api',
            key: new Expression("request.headers.get('Authorization') ?? request.getClientIp() ?? 'unknown'"),
            exposeHeaders: true,
        );
    }
}
