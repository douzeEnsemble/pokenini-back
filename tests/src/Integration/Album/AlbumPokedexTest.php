<?php

declare(strict_types=1);

namespace App\Tests\Integration\Album;

use App\Controller\Album\AlbumPokedexController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use App\Tests\Integration\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(AlbumPokedexController::class)]
final class AlbumPokedexTest extends WebTestCase
{
    use ClientRequestTrait;
    use JsonResponseTrait;

    public function testGet(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/album/demo',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Pokedex/demo.json');
    }

    public function testGetForAPublicDex(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/album/demolite',
            [
                't' => 'f86cbe805674d85f7806b175b70647a6a9334631',
            ],
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Pokedex/demolite_f86cbe805674d85f7806b175b70647a6a9334631.json');
    }

    public function testGetForAPrivateDex(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/album/demolist3',
            [
                't' => '159bb9b6d090a313087d2f26135970c2db49ee72',
            ],
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetForAnOwnPrivateDex(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/album/demolist3'
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Pokedex/demolist3.json');
    }

    public function testGetForAnOwnPublicDex(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/album/demolite',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Pokedex/demolite.json');
    }

    public function testGetForANonReleasedDexAsTrainer(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/album/allshinies'
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetForANonReleasedDexAsAdmin(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'GET',
            '/album/allshinies'
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Pokedex/allshinies.json');
    }

    public function testGetNonExistingDex(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/album/demomo',
            [
                't' => 'f86cbe805674d85f7806b175b70647a6a9334631',
            ],
        );

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @param array<string, array<string>|string> $parameters
     */
    #[DataProvider('getFilteredProvider')]
    public function testGetFiltered(array $parameters, string $filename): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/album/demo',
            array_merge(
                [
                    't' => '7b52009b64fd0a2a49e6d8a939753077792b0554',
                ],
                $parameters,
            ),
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Pokedex/filters_'.$filename.'.json');
    }

    /**
     * @return array<string, array{
     *     parameters: array<string, array<string>|string>,
     *     filename: string
     * }>
     */
    public static function getFilteredProvider(): array
    {
        return [
            'cs-no' => [
                'parameters' => [
                    'catch_states' => 'no',
                ],
                'filename' => 'cs-no',
            ],
            'cs-!no' => [
                'parameters' => [
                    'catch_states' => '!no',
                ],
                'filename' => 'cs-!no',
            ],
            'ca-pogoshadow' => [
                'parameters' => [
                    'collection_availabilities' => ['pogoshadow'],
                ],
                'filename' => 'ca-pogoshadow',
            ],
            'f-bulbasaur' => [
                'parameters' => [
                    'families' => 'bulbasaur',
                ],
                'filename' => 'f-bulbasaur',
            ],
            'fc-starter' => [
                'parameters' => [
                    'category_forms' => ['starter'],
                ],
                'filename' => 'fc-starter',
            ],
            'fs-mega' => [
                'parameters' => [
                    'special_forms' => ['mega'],
                ],
                'filename' => 'fs-mega',
            ],
            'fs-mega-gigamax' => [
                'parameters' => [
                    'special_forms' => ['mega', ' gigamax'],
                ],
                'filename' => 'fs-mega-gigamax',
            ],
            'fr-paldean' => [
                'parameters' => [
                    'regional_forms' => ['paldean'],
                ],
                'filename' => 'fr-paldean',
            ],
            'fv-alternate' => [
                'parameters' => [
                    'variant_forms' => ['alternate'],
                ],
                'filename' => 'fv-alternate',
            ],
            'ogb-swordshield' => [
                'parameters' => [
                    'original_game_bundles' => ['swordshield'],
                ],
                'filename' => 'ogb-swordshield',
            ],
            'ogb-unknown' => [
                'parameters' => [
                    'original_game_bundles' => ['unknown'],
                ],
                'filename' => 'ogb-unknown',
            ],
            't1-fire' => [
                'parameters' => [
                    'primary_types' => ['fire'],
                ],
                'filename' => 't1-fire',
            ],
            't2-poison-flying' => [
                'parameters' => [
                    'secondary_types' => ['poison', 'flying'],
                ],
                'filename' => 't2-poison-flying',
            ],
            'at-fire' => [
                'parameters' => [
                    'any_types' => ['fire'],
                ],
                'filename' => 'at-fire',
            ],
        ];
    }

    public function testGetNonAuthentificated(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            '/album/demolite',
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTrainerPublicDexNonAuthentificated(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            '/album/demolite',
            [
                't' => 'f86cbe805674d85f7806b175b70647a6a9334631',
            ],
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Pokedex/demolite_f86cbe805674d85f7806b175b70647a6a9334631.json');
    }
}
