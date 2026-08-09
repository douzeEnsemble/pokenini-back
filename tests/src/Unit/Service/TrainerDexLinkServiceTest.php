<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\ApiValidationException;
use App\Security\UserTokenService;
use App\Service\Api\TrainerDexLinkApiService;
use App\Service\TrainerDexLinkService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(TrainerDexLinkService::class)]
#[CoversClass(ApiValidationException::class)]
final class TrainerDexLinkServiceTest extends TestCase
{
    #[Test]
    public function list(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService->method('getLoggedUserToken')->willReturn('8800088');

        $apiService = $this->createMock(TrainerDexLinkApiService::class);
        $apiService->expects($this->once())
            ->method('list')
            ->with('douze', '8800088')
            ->willReturn([['id' => 'link-1']])
        ;

        $service = new TrainerDexLinkService($userTokenService, $apiService);

        $this->assertSame([['id' => 'link-1']], $service->list('douze'));
    }

    #[Test]
    public function create(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService->method('getLoggedUserToken')->willReturn('8800088');

        $apiService = $this->createMock(TrainerDexLinkApiService::class);
        $apiService->expects($this->once())
            ->method('create')
            ->with('douze', 'treize', true, '8800088')
        ;

        $service = new TrainerDexLinkService($userTokenService, $apiService);

        $service->create('douze', 'treize', true);
    }

    #[Test]
    public function createTranslatesApiHttpExceptionIntoApiValidationException(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService->method('getLoggedUserToken')->willReturn('8800088');

        $apiResponse = $this->createMock(ResponseInterface::class);
        $apiResponse->method('getStatusCode')->willReturn(409);

        $exception = $this->createMock(HttpExceptionInterface::class);
        $exception->method('getResponse')->willReturn($apiResponse);

        $apiService = $this->createMock(TrainerDexLinkApiService::class);
        $apiService->expects($this->once())
            ->method('create')
            ->willThrowException($exception)
        ;

        $service = new TrainerDexLinkService($userTokenService, $apiService);

        try {
            $service->create('douze', 'treize', true);
            $this->fail('Expected ApiValidationException');
        } catch (ApiValidationException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
    }

    #[Test]
    public function delete(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService->method('getLoggedUserToken')->willReturn('8800088');

        $apiService = $this->createMock(TrainerDexLinkApiService::class);
        $apiService->expects($this->once())
            ->method('delete')
            ->with('link-1', '8800088')
        ;

        $service = new TrainerDexLinkService($userTokenService, $apiService);

        $service->delete('link-1');
    }
}
