<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\ApiValidationException;
use App\Security\UserTokenService;
use App\Service\Api\TrainerPreferenceApiService;
use App\Service\TrainerPreferenceService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(TrainerPreferenceService::class)]
#[CoversClass(ApiValidationException::class)]
final class TrainerPreferenceServiceTest extends TestCase
{
    #[Test]
    public function get(): void
    {
        $userTokenService = $this->createStub(UserTokenService::class);
        $userTokenService->method('getLoggedUserToken')->willReturn('8800088');

        $apiService = $this->createMock(TrainerPreferenceApiService::class);
        $apiService->expects($this->once())
            ->method('get')
            ->with('8800088')
            ->willReturn(['home_page' => 'hub'])
        ;

        $service = new TrainerPreferenceService($userTokenService, $apiService);

        $this->assertSame(['home_page' => 'hub'], $service->get());
    }

    #[Test]
    public function modifyHomePage(): void
    {
        $userTokenService = $this->createStub(UserTokenService::class);
        $userTokenService->method('getLoggedUserToken')->willReturn('8800088');

        $apiService = $this->createMock(TrainerPreferenceApiService::class);
        $apiService->expects($this->once())
            ->method('modifyHomePage')
            ->with('album', '8800088')
        ;

        $service = new TrainerPreferenceService($userTokenService, $apiService);

        $service->modifyHomePage('album');
    }

    #[Test]
    public function modifyHomePageTranslatesApiHttpExceptionIntoApiValidationException(): void
    {
        $userTokenService = $this->createStub(UserTokenService::class);
        $userTokenService->method('getLoggedUserToken')->willReturn('8800088');

        $apiResponse = $this->createStub(ResponseInterface::class);
        $apiResponse->method('getStatusCode')->willReturn(400);

        $exception = $this->createStub(HttpExceptionInterface::class);
        $exception->method('getResponse')->willReturn($apiResponse);

        $apiService = $this->createMock(TrainerPreferenceApiService::class);
        $apiService->expects($this->once())
            ->method('modifyHomePage')
            ->willThrowException($exception)
        ;

        $service = new TrainerPreferenceService($userTokenService, $apiService);

        try {
            $service->modifyHomePage('admin');
            $this->fail('Expected ApiValidationException');
        } catch (ApiValidationException $e) {
            $this->assertSame(400, $e->getStatusCode());
        }
    }
}
