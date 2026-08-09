<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Controller\Album\AlbumUpsertController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\RateLimiter\LimiterInterface;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(AlbumUpsertController::class)]
final class RateLimitTest extends WebTestCase
{
    use ClientRequestTrait;

    /**
     * The rate-limiter key used by write-endpoint controllers.
     *
     * The #[RateLimit] attribute is declared with key: new Expression("...") so
     * Symfony evaluates the expression against the request. In tests,
     * authenticatedRequest() sends HTTP_AUTHORIZATION: 'Bearer this-is-the-trainer-token',
     * so that value becomes the rate-limiter bucket key.
     */
    private const string RATE_LIMITER_KEY = 'Bearer this-is-the-trainer-token';

    private ?LimiterInterface $limiter = null;

    #[\Override]
    protected function tearDown(): void
    {
        // Reset after each test so exhausted state does not leak into subsequent tests
        // that call write endpoints (they all share the same bucket).
        $this->limiter?->reset();
        $this->limiter = null;

        parent::tearDown();
    }

    /**
     * A first write request below the limit is accepted: the listener lets it
     * through and the controller returns a non-429 response.
     */
    #[Test]
    public function writeRequestBelowLimitIsAccepted(): void
    {
        $client = self::createClient();

        // Reset before the test so leftover state from failed runs does not bleed in.
        $this->limiter = $this->getWriteApiLimiter();
        $this->limiter->reset();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'PATCH',
            '/album/demo/bulbasaur',
            [],
            [],
            [],
            'yes',
        );

        // Rate limiter fired and accepted the request — must not be 429.
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * After all tokens are exhausted the next write request must return 429.
     *
     * Strategy:
     *   1. Consume every available token directly via the RateLimiterFactory
     *      (same container / same FilesystemAdapter as the one used by the listener).
     *   2. Send one HTTP request — the RateLimitAttributeListener sees 0 tokens
     *      left and throws TooManyRequestsHttpException before the controller runs,
     *      so no Moco API fixture is needed for this final request.
     */
    #[Test]
    public function writeRequestExceedingLimitReturns429(): void
    {
        $client = self::createClient();

        // Reset before the test so leftover state from failed runs does not bleed in.
        $this->limiter = $this->getWriteApiLimiter();
        $this->limiter->reset();

        // Exhaust all 10 000 test-env tokens (limit set by when@test block).
        // SlidingWindowLimiter::reserve() throws if $tokens > $this->limit, so
        // we stay exactly at the configured limit.
        $this->limiter->consume(10000);

        // The very next request must be rejected with 429 by the listener.
        $this->authenticatedRequest(
            $client,
            'trainer',
            'PATCH',
            '/album/demo/bulbasaur',
            [],
            [],
            [],
            'yes',
        );

        $this->assertResponseStatusCodeSame(429);
    }

    /**
     * Returns a LimiterInterface instance scoped to the shared write-endpoint key.
     * Must be called after self::createClient() so the kernel is already booted.
     */
    private function getWriteApiLimiter(): LimiterInterface
    {
        $factory = self::getContainer()->get('limiter.write_api');

        return $factory->create(self::RATE_LIMITER_KEY);
    }
}
