<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenService;
use App\Service\TrainerIdsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[CoversClass(TrainerIdsService::class)]
final class TrainerIdsServiceTest extends TestCase
{
    #[Test]
    public function withoutRequested(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $requestStack = new RequestStack();
        $request = new Request();
        $requestStack->push($request);

        $service = new TrainerIdsService($userTokenService, $requestStack);

        $this->assertSame('8800088', $service->getLoggedTrainerId());
        $this->assertSame('8800088', $service->getTrainerId());
    }

    #[Test]
    public function withoutLogged(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willThrowException(new NoLoggedUserException())
        ;

        $requestStack = new RequestStack();
        $request = new Request(['trainer_id' => '2100012']);
        $requestStack->push($request);

        $service = new TrainerIdsService($userTokenService, $requestStack);

        $this->assertNull($service->getLoggedTrainerId());
        $this->assertSame('2100012', $service->getTrainerId());
    }

    #[Test]
    public function withoutLoggedAndRequested(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willThrowException(new NoLoggedUserException())
        ;

        $requestStack = new RequestStack();
        $request = new Request();
        $requestStack->push($request);

        $service = new TrainerIdsService($userTokenService, $requestStack);

        $this->assertNull($service->getLoggedTrainerId());
        $this->assertNull($service->getTrainerId());
    }

    #[Test]
    public function getTrainerIdUsesLazyInit(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['trainer_id' => '2100012']));

        $service = new TrainerIdsService($userTokenService, $requestStack);

        $this->assertSame('2100012', $service->getTrainerId());
    }

    #[Test]
    public function getLoggedTrainerIdUsesLazyInit(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $service = new TrainerIdsService($userTokenService, $requestStack);

        $this->assertSame('8800088', $service->getLoggedTrainerId());
    }
}
