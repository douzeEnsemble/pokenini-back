<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\ApiValidationException;
use App\Security\UserTokenService;
use App\Service\Api\WhichShinyGameApiService;
use App\Service\WhichShinyGameService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(WhichShinyGameService::class)]
final class WhichShinyGameServiceTest extends TestCase
{
    /**
     * @param string[] $expectedParams
     */
    #[Test]
    #[DataProvider('providerGetDexList')]
    public function getDexList(bool $includePremium, bool $includeUnreleased, array $expectedParams): void
    {
        $apiService = $this->createMock(WhichShinyGameApiService::class);
        $apiService->expects($this->once())
            ->method('getDexList')
            ->with('8800088', $expectedParams)
            ->willReturn([['slug' => 'demo']])
        ;

        $this->assertSame(
            [['slug' => 'demo']],
            $this->service($apiService)->getDexList($includePremium, $includeUnreleased),
        );
    }

    /**
     * @return array<string, array{bool, bool, string[]}>
     */
    public static function providerGetDexList(): array
    {
        return [
            'none' => [false, false, []],
            'premium' => [true, false, ['include_premium_dex' => '1']],
            'unreleased' => [false, true, ['include_unreleased_dex' => '1']],
            'both' => [true, true, ['include_premium_dex' => '1', 'include_unreleased_dex' => '1']],
        ];
    }

    #[Test]
    public function getGames(): void
    {
        $apiService = $this->createMock(WhichShinyGameApiService::class);
        $apiService->expects($this->once())
            ->method('getGames')
            ->with('8800088')
            ->willReturn([['id' => 'g1']])
        ;

        $this->assertSame([['id' => 'g1']], $this->service($apiService)->getGames());
    }

    #[Test]
    public function create(): void
    {
        $apiService = $this->createMock(WhichShinyGameApiService::class);
        $apiService->expects($this->once())
            ->method('createGame')
            ->with('8800088', 'demo', null)
            ->willReturn(['id' => 'g1'])
        ;

        $this->assertSame(['id' => 'g1'], $this->service($apiService)->create('demo', null));
    }

    #[Test]
    public function createWithRoundCount(): void
    {
        $apiService = $this->createMock(WhichShinyGameApiService::class);
        $apiService->expects($this->once())
            ->method('createGame')
            ->with('8800088', 'demo', 10)
            ->willReturn(['id' => 'g1'])
        ;

        $this->assertSame(['id' => 'g1'], $this->service($apiService)->create('demo', 10));
    }

    #[Test]
    public function get(): void
    {
        $apiService = $this->createMock(WhichShinyGameApiService::class);
        $apiService->expects($this->once())
            ->method('getGame')
            ->with('8800088', 'g1')
            ->willReturn(['round' => null])
        ;

        $this->assertSame(['round' => null], $this->service($apiService)->get('g1'));
    }

    #[Test]
    public function answer(): void
    {
        $apiService = $this->createMock(WhichShinyGameApiService::class);
        $apiService->expects($this->once())
            ->method('answer')
            ->with('8800088', 'g1', 2, 2)
            ->willReturn(['is_correct' => true])
        ;

        $this->assertSame(['is_correct' => true], $this->service($apiService)->answer('g1', 2, 2));
    }

    /**
     * @param non-empty-string $apiMethod
     */
    #[Test]
    #[DataProvider('providerApiErrors')]
    public function apiErrorsAreTranslated(string $apiMethod, callable $call, int $statusCode): void
    {
        $apiResponse = $this->createStub(ResponseInterface::class);
        $apiResponse->method('getStatusCode')->willReturn($statusCode);

        $exception = $this->createStub(HttpExceptionInterface::class);
        $exception->method('getResponse')->willReturn($apiResponse);

        $apiService = $this->createMock(WhichShinyGameApiService::class);
        $apiService->expects($this->once())
            ->method($apiMethod)
            ->willThrowException($exception)
        ;

        try {
            $call($this->service($apiService));
            $this->fail('Expected ApiValidationException');
        } catch (ApiValidationException $e) {
            $this->assertSame($statusCode, $e->getStatusCode());
        }
    }

    /**
     * @return array<string, array{non-empty-string, callable, int}>
     */
    public static function providerApiErrors(): array
    {
        return [
            'create' => ['createGame', static fn (WhichShinyGameService $service): array => $service->create('demo', null), 400],
            'get' => ['getGame', static fn (WhichShinyGameService $service): array => $service->get('g1'), 404],
            'answer' => ['answer', static fn (WhichShinyGameService $service): array => $service->answer('g1', 1, 1), 409],
        ];
    }

    private function service(WhichShinyGameApiService $apiService): WhichShinyGameService
    {
        $userTokenService = $this->createStub(UserTokenService::class);
        $userTokenService->method('getLoggedUserToken')->willReturn('8800088');

        return new WhichShinyGameService($userTokenService, $apiService);
    }
}
