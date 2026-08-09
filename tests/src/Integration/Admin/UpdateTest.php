<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Controller\Admin\AdminActionUpdateController;
use App\Security\User;
use App\Tests\Integration\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(AdminActionUpdateController::class)]
final class UpdateTest extends WebTestCase
{
    use ClientRequestTrait;

    #[Test]
    public function adminUpdateLabels(): void
    {
        $this->testAdminUpdate('labels');
    }

    #[Test]
    public function adminUpdateGamesCollectionsAndDex(): void
    {
        $this->testAdminUpdate('games_collections_and_dex');
    }

    #[Test]
    public function adminUpdatePokemons(): void
    {
        $this->testAdminUpdate('pokemons');
    }

    #[Test]
    public function adminUpdateRegionalDexNumbers(): void
    {
        $this->testAdminUpdate('regional_dex_numbers');
    }

    #[Test]
    public function adminUpdateGamesAvailabilities(): void
    {
        $this->testAdminUpdate('games_availabilities');
    }

    #[Test]
    public function adminUpdateCollections(): void
    {
        $this->testAdminUpdate('collections_availabilities');
    }

    #[Test]
    public function adminUpdateUnknown(): void
    {
        $client = self::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->catchExceptions(false);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', '/istration/action/update/truc');
    }

    #[Test]
    public function unknown(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'POST',
            '/istration/action/update/truc'
        );

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function nonAuthenticate(): void
    {
        $client = self::createClient();

        $client->request('POST', '/istration/action/update/labels');

        $this->assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function noProvider(): void
    {
        $client = self::createClient();

        $client->request(
            'POST',
            '/istration/action/update/labels',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer this-is-the-token',
            ],
        );

        $this->assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function nonAdmin(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'user',
            'POST',
            '/istration/action/update/labels'
        );

        $this->assertResponseStatusCodeSame(403);
    }

    private function testAdminUpdate(string $name): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'POST',
            "/istration/action/update/{$name}",
        );

        $this->assertResponseStatusCodeSame(202);
        $content = (string) $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertSame(
            [
                'action' => 'update',
                'item' => $name,
                'state' => 'ok',
                'content' => '',
                'error' => '',
            ],
            $data
        );
    }
}
