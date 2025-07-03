<?php

declare(strict_types=1);

namespace App\Tests\Functional\Pokedex;

use App\Controller\PokedexController;
use App\Tests\Functional\Trait\ClientRequestTrait;
use App\Tests\Functional\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(PokedexController::class)]
class PokedexTest extends WebTestCase
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
            '/pokedex/demo',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Pokedex/demo.json');
    }

    public function testGetForAPublicDex(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/pokedex/demolite',
            [
                't' => 'f86cbe805674d85f7806b175b70647a6a9334631',
            ],
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Pokedex/demolite_f86cbe805674d85f7806b175b70647a6a9334631.json');
    }

    public function testGetForAPrivateDex(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/pokedex/demolist3',
            [
                't' => '159bb9b6d090a313087d2f26135970c2db49ee72',
            ],
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetForAnOwnPrivateDex(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/pokedex/demolist3'
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Pokedex/demolist3.json');
    }

    public function testGetForAnOwnPublicDex(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/pokedex/demolite',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Pokedex/demolite.json');
    }

    public function testGetForANonReleasedDexAsTrainer(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/pokedex/allshinies'
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetForANonReleasedDexAsAdmin(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'GET',
            '/pokedex/allshinies'
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Pokedex/allshinies.json');
    }

    public function testGetNonExistingDex(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/pokedex/demomo',
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
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/pokedex/demo',
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
     *     parameters: array<string, string|array<string>>,
     *     filename: string
     * }>
     */
    public static function getFilteredProvider(): array
    {
        return [
            'cs-no' => [
                'parameters' => [
                    'cs' => 'no',
                ],
                'filename' => 'cs-no',
            ],
            'cs-!no' => [
                'parameters' => [
                    'cs' => '!no',
                ],
                'filename' => 'cs-!no',
            ],
            'ca-pogoshadow' => [
                'parameters' => [
                    'ca' => ['pogoshadow'],
                ],
                'filename' => 'ca-pogoshadow',
            ],
            'f-bulbasaur' => [
                'parameters' => [
                    'f' => 'bulbasaur',
                ],
                'filename' => 'f-bulbasaur',
            ],
            'fc-starter' => [
                'parameters' => [
                    'fc' => ['starter'],
                ],
                'filename' => 'fc-starter',
            ],
            'fs-mega' => [
                'parameters' => [
                    'fs' => ['mega'],
                ],
                'filename' => 'fs-mega',
            ],
            'fs-mega-gigamax' => [
                'parameters' => [
                    'fs' => ['mega', ' gigamax'],
                ],
                'filename' => 'fs-mega-gigamax',
            ],
            'fr-paldean' => [
                'parameters' => [
                    'fr' => ['paldean'],
                ],
                'filename' => 'fr-paldean',
            ],
            'fv-alternate' => [
                'parameters' => [
                    'fv' => ['alternate'],
                ],
                'filename' => 'fv-alternate',
            ],
            'ogb-swordshield' => [
                'parameters' => [
                    'ogb' => ['swordshield'],
                ],
                'filename' => 'ogb-swordshield',
            ],
            'ogb-unknown' => [
                'parameters' => [
                    'ogb' => ['unknown'],
                ],
                'filename' => 'ogb-unknown',
            ],
            't1-fire' => [
                'parameters' => [
                    't1' => ['fire'],
                ],
                'filename' => 't1-fire',
            ],
            't2-poison-flying' => [
                'parameters' => [
                    't2' => ['poison', 'flying'],
                ],
                'filename' => 't2-poison-flying',
            ],
            'at-fire' => [
                'parameters' => [
                    'at' => ['fire'],
                ],
                'filename' => 'at-fire',
            ],
        ];
    }
}
