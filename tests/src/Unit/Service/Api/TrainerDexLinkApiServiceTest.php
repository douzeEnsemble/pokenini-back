<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\TrainerDexLinkApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(TrainerDexLinkApiService::class)]
final class TrainerDexLinkApiServiceTest extends TestCase
{
    public function testList(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('[{"id":"link-1"}]');

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.local/trainer_dex_link/8800088/douze', $this->anything())
            ->willReturn($response)
        ;

        $service = $this->service($client);

        $this->assertSame([['id' => 'link-1']], $service->list('douze', '8800088'));
    }

    public function testCreate(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('');

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.local/trainer_dex_link/8800088',
                $this->callback(function (array $options): bool {
                    /** @var array<string, mixed> $body */
                    $body = json_decode((string) $options['body'], true);

                    return ['sourceDexSlug' => 'douze', 'targetDexSlug' => 'treize', 'bidirectional' => true] === $body;
                }),
            )
            ->willReturn($response)
        ;

        $service = $this->service($client);

        $service->create('douze', 'treize', true, '8800088');
    }

    public function testDelete(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('');

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('DELETE', 'https://api.local/trainer_dex_link/8800088/link-1', $this->anything())
            ->willReturn($response)
        ;

        $service = $this->service($client);

        $service->delete('link-1', '8800088');
    }

    private function service(HttpClientInterface $client): TrainerDexLinkApiService
    {
        return new TrainerDexLinkApiService(
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
