<?php

declare(strict_types=1);

namespace App\Tests\Integration\User;

use App\Controller\User\UserInfoController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(UserInfoController::class)]
final class UserInfoTest extends WebTestCase
{
    use ClientRequestTrait;

    #[Test]
    public function get(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/user',
        );

        $this->assertResponseIsSuccessful();

        $content = (string) $client->getResponse()->getContent();

        /** @var array<string, mixed> $data */
        $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        // The session token is a freshly signed JWT (varies with time), so it cannot be snapshot-tested.
        // Its presence and shape are asserted separately, then it is stripped before comparing the rest.
        $this->assertArrayHasKey('session_token', $data);

        /** @var string $sessionToken */
        $sessionToken = $data['session_token'];
        $this->assertMatchesRegularExpression('/^[\w-]+\.[\w-]+\.[\w-]+$/', $sessionToken);
        unset($data['session_token']);

        $this->assertJsonStringEqualsJsonFile(
            __DIR__.'/../../../resources/functional/controller/UserInfo/trainer.json',
            json_encode($data, JSON_THROW_ON_ERROR),
        );
    }

    #[Test]
    public function getNonAuthenticated(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            '/user',
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
