<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetFormsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GetFormsService::class)]
class GetFormsServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    public function testGetFormsCategory(): void
    {
        $expectedResult = [
            [
                'name' => 'Starter',
                'french_name' => 'de Départ',
                'slug' => 'starter',
            ],
            [
                'name' => 'Legendary',
                'french_name' => 'Légendaire',
                'slug' => 'legendary',
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            $this->getService('category')->getFormsCategory(),
        );

        /** @var string $value */
        $value = $this->cache->getItem('forms_category')->get();

        $this->assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResult),
            $value,
        );
    }

    public function testGetFormsRegional(): void
    {
        $expectedResult = [
            [
                'name' => 'Alolan',
                'french_name' => "d'Alola",
                'slug' => 'alolan',
            ],
            [
                'name' => 'Galarian',
                'french_name' => 'de Galar',
                'slug' => 'galarian',
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            $this->getService('regional')->getFormsRegional(),
        );

        /** @var string $value */
        $value = $this->cache->getItem('forms_regional')->get();

        $this->assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResult),
            $value,
        );
    }

    public function testGetFormsSpecial(): void
    {
        $expectedResult = [
            [
                'name' => 'Mega',
                'french_name' => 'Mega',
                'slug' => 'mega',
            ],
            [
                'name' => 'Primal',
                'french_name' => 'Originelle',
                'slug' => 'primal',
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            $this->getService('special')->getFormsSpecial(),
        );

        /** @var string $value */
        $value = $this->cache->getItem('forms_special')->get();

        $this->assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResult),
            $value,
        );
    }

    public function testGetFormsVariant(): void
    {
        $expectedResult = [
            [
                'name' => 'Gender',
                'french_name' => 'Genre',
                'slug' => 'gender',
            ],
            [
                'name' => 'Alternate',
                'french_name' => 'Alternatif',
                'slug' => 'alternate',
            ],
            [
                'name' => 'Therian',
                'french_name' => 'Totémique',
                'slug' => 'therian',
            ],
        ];

        $this->assertEquals(
            $expectedResult,
            $this->getService('variant')->getFormsVariant(),
        );

        /** @var string $value */
        $value = $this->cache->getItem('forms_variant')->get();

        $this->assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResult),
            $value,
        );
    }

    private function getService(string $type): GetFormsService
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $json = (string) file_get_contents("/var/www/html/tests/resources/unit/service/api/{$type}_forms.json");

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn($json)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                "https://api.domain/forms/{$type}",
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                    'cafile' => './resources/certificates/cacert.pem',
                ],
            )
            ->willReturn($response)
        ;

        $this->cachePool = new ArrayAdapter();
        $this->cache = new TagAwareAdapter($this->cachePool, new ArrayAdapter());

        return new GetFormsService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $this->cache,
            'web',
            'douze',
        );
    }
}
