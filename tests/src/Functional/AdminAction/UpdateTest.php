<?php

declare(strict_types=1);

namespace App\Tests\Functional\AdminAction;

use App\Controller\AdminActionController;
use App\Security\User;
use App\Tests\Functional\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 */
#[CoversClass(AdminActionController::class)]
class UpdateTest extends WebTestCase
{
    use ClientRequestTrait;

    public function testAdminUpdateLabels(): void
    {
        $this->testAdminUpdate('labels');
    }

    public function testAdminUpdateGamesCollectionsAndDex(): void
    {
        $this->testAdminUpdate('games_collections_and_dex');
    }

    public function testAdminUpdatePokemons(): void
    {
        $this->testAdminUpdate('pokemons');
    }

    public function testAdminUpdateRegionalDexNumbers(): void
    {
        $this->testAdminUpdate('regional_dex_numbers');
    }

    public function testAdminUpdateGamesAvailabilities(): void
    {
        $this->testAdminUpdate('games_availabilities');
    }

    public function testAdminUpdateCollections(): void
    {
        $this->testAdminUpdate('collections_availabilities');
    }

    public function testAdminUpdateUnknown(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->catchExceptions(false);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', '/istration/action/update/truc');
    }

    public function testUnknown(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'POST',
            '/istration/action/update/truc'
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testNonAuthenticate(): void
    {
        $client = static::createClient();

        $client->request('POST', '/istration/action/update/labels');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testNoProvider(): void
    {
        $client = static::createClient();

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

    public function testNonAdmin(): void
    {
        $client = static::createClient();

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
        $client = static::createClient();

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
