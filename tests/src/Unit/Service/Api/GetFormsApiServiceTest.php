<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetFormsApiService;
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
#[CoversClass(GetFormsApiService::class)]
final class GetFormsApiServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    public function testGetFormsCategory(): void
    {
        $expectedResult = [
            ['slug' => 'starter', 'name' => 'Starter', 'french_name' => 'de Départ'],
            ['slug' => 'legendary', 'name' => 'Legendary', 'french_name' => 'Légendaire'],
        ];

        $this->assertEquals($expectedResult, $this->getService()->getFormsCategory());

        /** @var string $value */
        $value = $this->cache->getItem('forms')->get();
        $this->assertJson($value);
    }

    public function testGetFormsRegional(): void
    {
        $expectedResult = [
            ['slug' => 'alolan', 'name' => 'Alolan', 'french_name' => "d'Alola"],
            ['slug' => 'galarian', 'name' => 'Galarian', 'french_name' => 'de Galar'],
        ];

        $this->assertEquals($expectedResult, $this->getService()->getFormsRegional());
    }

    public function testGetFormsSpecial(): void
    {
        $expectedResult = [
            ['slug' => 'mega', 'name' => 'Mega', 'french_name' => 'Mega'],
            ['slug' => 'primal', 'name' => 'Primal', 'french_name' => 'Originelle'],
        ];

        $this->assertEquals($expectedResult, $this->getService()->getFormsSpecial());
    }

    public function testGetFormsVariant(): void
    {
        $expectedResult = [
            ['slug' => 'gender', 'name' => 'Gender', 'french_name' => 'Genre'],
            ['slug' => 'alternate', 'name' => 'Alternate', 'french_name' => 'Alternatif'],
            ['slug' => 'therian', 'name' => 'Therian', 'french_name' => 'Totémique'],
        ];

        $this->assertEquals($expectedResult, $this->getService()->getFormsVariant());
    }

    private function getService(): GetFormsApiService
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))->method('info');

        $client = $this->createMock(HttpClientInterface::class);
        $json = (string) file_get_contents('/app/tests/resources/unit/service/api/forms.json');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getContent')->willReturn($json);

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.domain/forms',
                [
                    'headers' => ['accept' => 'application/json'],
                    'auth_basic' => ['web', 'douze'],
                    'cafile' => './resources/certificates/cacert.pem',
                ],
            )
            ->willReturn($response)
        ;

        $this->cachePool = new ArrayAdapter();
        $this->cache = new TagAwareAdapter($this->cachePool, new ArrayAdapter());

        return new GetFormsApiService(
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
