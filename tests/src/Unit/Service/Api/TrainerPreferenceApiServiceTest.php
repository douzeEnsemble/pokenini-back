<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\TrainerPreferenceApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(TrainerPreferenceApiService::class)]
final class TrainerPreferenceApiServiceTest extends TestCase
{
    #[Test]
    public function get(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"home_page":"game"}');

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.local/trainer_preference/8800088', $this->anything())
            ->willReturn($response)
        ;

        $service = $this->service($client);

        $this->assertSame(['home_page' => 'game'], $service->get('8800088'));
    }

    #[Test]
    public function modifyHomePage(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('');

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                'https://api.local/trainer_preference/8800088',
                $this->callback(function (array $options): bool {
                    return '{"home_page":"election"}' === $options['body'];
                }),
            )
            ->willReturn($response)
        ;

        $service = $this->service($client);

        $service->modifyHomePage('election', '8800088');
    }

    private function service(HttpClientInterface $client): TrainerPreferenceApiService
    {
        return new TrainerPreferenceApiService(
            $this->createMock(LoggerInterface::class),
            $client,
            'https://api.local',
            '/path/to/cafile',
            $this->createMock(TagAwareCacheInterface::class),
            'login',
            'password',
        );
    }
}
