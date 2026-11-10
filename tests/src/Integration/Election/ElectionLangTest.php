<?php

declare(strict_types=1);

namespace App\Tests\Integration\Election;

use App\Controller\Election\ElectionDexListController;
use App\Controller\Election\ElectionIndexController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(ElectionIndexController::class)]
#[CoversClass(ElectionDexListController::class)]
final class ElectionLangTest extends WebTestCase
{
    use ClientRequestTrait;

    #[Test]
    public function index(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest($client, 'trainer', 'GET', '/election/demolite?lang=fr');

        $this->assertResponseIsSuccessful();

        $content = $this->content($client);

        $this->assertSame(['en' => 'Bulbasaur', 'fr' => 'Bulbizarre'], $this->names($content, 'pokemons'));
        $this->assertSame(['en' => 'Bulbasaur', 'fr' => 'Bulbizarre'], $this->names($content, 'election_top'));
        $this->assertSame(1, $content['detached_count']);
    }

    #[Test]
    public function dexList(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest($client, 'trainer', 'GET', '/election/dex?lang=fr');

        $this->assertResponseIsSuccessful();

        $content = $this->content($client);
        $this->assertIsArray($content[0]);
        $this->assertSame('Maison', $content[0]['name']);
        $this->assertNull($content[0]['french_name']);
    }

    #[DataProvider('providerNotEnabledLanguage')]
    #[Test]
    public function notEnabledLanguage(string $url): void
    {
        $client = self::createClient();

        $this->authenticatedRequest($client, 'trainer', 'GET', $url);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(['error' => 'Invalid language'], $this->content($client));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function providerNotEnabledLanguage(): array
    {
        return [
            'index' => ['/election/demolite?lang=de'],
            'dex list' => ['/election/dex?lang=de'],
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function content(KernelBrowser $client): array
    {
        /** @var array<array-key, mixed> */
        return json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * The names of the first Pokémon of a list.
     *
     * @param array<array-key, mixed> $content
     *
     * @return array<array-key, mixed>
     */
    private function names(array $content, string $list): array
    {
        /** @var array{0: array{pokemon: array{labels: array{names: array<array-key, mixed>}}}} $items */
        $items = $content[$list];

        return $items[0]['pokemon']['labels']['names'];
    }
}
