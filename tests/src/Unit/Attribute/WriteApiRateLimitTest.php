<?php

declare(strict_types=1);

namespace App\Tests\Unit\Attribute;

use App\Attribute\WriteApiRateLimit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(WriteApiRateLimit::class)]
final class WriteApiRateLimitTest extends TestCase
{
    #[Test]
    public function presetsTheWriteApiLimiter(): void
    {
        $attribute = new WriteApiRateLimit();

        $this->assertSame('write_api', $attribute->limiter);
        $this->assertSame(1, $attribute->tokens);
        $this->assertSame([], $attribute->methods);
        $this->assertTrue($attribute->exposeHeaders);
        $this->assertTrue($attribute->if);
    }

    #[Test]
    #[DataProvider('provideRequests')]
    public function keyIdentifiesTheCaller(Request $request, string $expectedKey): void
    {
        $key = new WriteApiRateLimit()->key;
        $this->assertInstanceOf(Expression::class, $key);

        $this->assertSame($expectedKey, new ExpressionLanguage()->evaluate($key, ['request' => $request]));
    }

    /**
     * @return iterable<string, array{Request, string}>
     */
    public static function provideRequests(): iterable
    {
        yield 'authorization header first' => [
            new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer abc', 'REMOTE_ADDR' => '10.0.0.1']),
            'Bearer abc',
        ];

        yield 'then the client IP' => [new Request(server: ['REMOTE_ADDR' => '10.0.0.1']), '10.0.0.1'];

        yield 'else a shared key' => [new Request(), 'unknown'];
    }
}
