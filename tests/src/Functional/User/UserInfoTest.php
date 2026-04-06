<?php

declare(strict_types=1);

namespace App\Tests\Functional\User;

use App\Controller\User\UserInfoController;
use App\Tests\Functional\Trait\ClientRequestTrait;
use App\Tests\Functional\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(UserInfoController::class)]
class UserInfoTest extends WebTestCase
{
    use ClientRequestTrait;
    use JsonResponseTrait;

    public function testGet(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/user',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'UserInfo/trainer.json');
    }

    public function testGetNonAuthenticated(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/user',
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
