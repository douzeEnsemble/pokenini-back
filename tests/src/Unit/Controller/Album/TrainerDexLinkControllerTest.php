<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Album;

use App\Controller\Album\TrainerDexLinkController;
use App\Exception\ApiValidationException;
use App\Service\FindDexBySlugService;
use App\Service\TrainerDexLinkService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @internal
 */
#[CoversClass(TrainerDexLinkController::class)]
final class TrainerDexLinkControllerTest extends TestCase
{
    #[Test]
    public function listRejectsPremiumDexForNonCollector(): void
    {
        $findDexBySlugService = $this->createStub(FindDexBySlugService::class);
        $findDexBySlugService->method('find')
            ->willReturn(['slug' => 'douze', 'flags' => ['is_premium' => true]])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->never())->method('list');

        $controller = $this->controller($findDexBySlugService, $trainerDexLinkService, false);

        $response = $controller->list('douze');

        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function listReturnsLinksWhenAccessible(): void
    {
        $findDexBySlugService = $this->createStub(FindDexBySlugService::class);
        $findDexBySlugService->method('find')
            ->willReturn(['slug' => 'douze', 'flags' => ['is_premium' => false]])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->once())
            ->method('list')
            ->with('douze')
            ->willReturn([['id' => 'link-1']])
        ;

        $controller = $this->controller($findDexBySlugService, $trainerDexLinkService, true);

        $response = $controller->list('douze');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('[{"id":"link-1"}]', $response->getContent());
    }

    #[Test]
    public function listReturnsLinksForNonCollectorWhenDexIsNotPremium(): void
    {
        $findDexBySlugService = $this->createStub(FindDexBySlugService::class);
        $findDexBySlugService->method('find')
            ->willReturn(['slug' => 'douze', 'flags' => ['is_premium' => false]])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->once())
            ->method('list')
            ->with('douze')
            ->willReturn([['id' => 'link-1']])
        ;

        $controller = $this->controller($findDexBySlugService, $trainerDexLinkService, false);

        $response = $controller->list('douze');

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function listNotFoundWhenDexUnknown(): void
    {
        $findDexBySlugService = $this->createStub(FindDexBySlugService::class);
        $findDexBySlugService->method('find')
            ->willReturn(null)
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->never())->method('list');

        $controller = $this->controller($findDexBySlugService, $trainerDexLinkService, true);

        $response = $controller->list('douze');

        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function createRejectsEmptyBody(): void
    {
        $findDexBySlugService = $this->createStub(FindDexBySlugService::class);
        $findDexBySlugService->method('find')
            ->willReturn(['slug' => 'douze', 'flags' => ['is_premium' => false]])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->never())->method('create');

        $controller = $this->controller($findDexBySlugService, $trainerDexLinkService, true);

        $response = $controller->create('douze', Request::create('test.local', 'POST', content: ''));

        $this->assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function createRejectsMissingTargetDexSlug(): void
    {
        $findDexBySlugService = $this->createStub(FindDexBySlugService::class);
        $findDexBySlugService->method('find')
            ->willReturn(['slug' => 'douze', 'flags' => ['is_premium' => false]])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->never())->method('create');

        $controller = $this->controller($findDexBySlugService, $trainerDexLinkService, true);

        $response = $controller->create('douze', Request::create('test.local', 'POST', content: '{}'));

        $this->assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function createRejectsPremiumDexForNonCollector(): void
    {
        $findDexBySlugService = $this->createStub(FindDexBySlugService::class);
        $findDexBySlugService->method('find')
            ->willReturn(['slug' => 'douze', 'flags' => ['is_premium' => true]])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->never())->method('create');

        $controller = $this->controller($findDexBySlugService, $trainerDexLinkService, false);

        $response = $controller->create('douze', Request::create('test.local', 'POST', content: '{"targetDexSlug":"treize"}'));

        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function createRejectsNonBooleanBidirectional(): void
    {
        $findDexBySlugService = $this->createStub(FindDexBySlugService::class);
        $findDexBySlugService->method('find')
            ->willReturn(['slug' => 'douze', 'flags' => ['is_premium' => false]])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->never())->method('create');

        $controller = $this->controller($findDexBySlugService, $trainerDexLinkService, true);

        $response = $controller->create('douze', Request::create('test.local', 'POST', content: '{"targetDexSlug":"treize","bidirectional":"yes"}'));

        $this->assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function createRejectsSelfLink(): void
    {
        $findDexBySlugService = $this->createStub(FindDexBySlugService::class);
        $findDexBySlugService->method('find')
            ->willReturn(['slug' => 'douze', 'flags' => ['is_premium' => false]])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->never())->method('create');

        $controller = $this->controller($findDexBySlugService, $trainerDexLinkService, true);

        $response = $controller->create('douze', Request::create('test.local', 'POST', content: '{"targetDexSlug":"douze"}'));

        $this->assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function createForwardsTheApiStatusCodeOnFailure(): void
    {
        $findDexBySlugService = $this->createStub(FindDexBySlugService::class);
        $findDexBySlugService->method('find')
            ->willReturn(['slug' => 'douze', 'flags' => ['is_premium' => false]])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->once())
            ->method('create')
            ->with('douze', 'treize', false)
            ->willThrowException(new ApiValidationException(409))
        ;

        $controller = $this->controller($findDexBySlugService, $trainerDexLinkService, true);

        $response = $controller->create('douze', Request::create('test.local', 'POST', content: '{"targetDexSlug":"treize"}'));

        $this->assertSame(409, $response->getStatusCode());
    }

    #[Test]
    public function createSucceeds(): void
    {
        $findDexBySlugService = $this->createStub(FindDexBySlugService::class);
        $findDexBySlugService->method('find')
            ->willReturn(['slug' => 'douze', 'flags' => ['is_premium' => false]])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->once())
            ->method('create')
            ->with('douze', 'treize', true)
        ;

        $controller = $this->controller($findDexBySlugService, $trainerDexLinkService, true);

        $response = $controller->create('douze', Request::create('test.local', 'POST', content: '{"targetDexSlug":"treize","bidirectional":true}'));

        $this->assertSame(201, $response->getStatusCode());
    }

    #[Test]
    public function delete(): void
    {
        $findDexBySlugService = $this->createStub(FindDexBySlugService::class);

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->once())
            ->method('delete')
            ->with('link-1')
        ;

        $controller = new TrainerDexLinkController($findDexBySlugService, $trainerDexLinkService);

        $response = $controller->delete('link-1');

        $this->assertSame(200, $response->getStatusCode());
    }

    private function controller(
        FindDexBySlugService $findDexBySlugService,
        TrainerDexLinkService $trainerDexLinkService,
        bool $isCollector,
    ): TrainerDexLinkController {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->atMost(1))->method('isGranted')->with('ROLE_COLLECTOR')->willReturn($isCollector);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($authorizationChecker);

        $controller = new TrainerDexLinkController($findDexBySlugService, $trainerDexLinkService);
        $controller->setContainer($container);

        return $controller;
    }
}
