<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Trainer;

use App\Controller\Trainer\TrainerPreferenceController;
use App\Exception\ApiValidationException;
use App\Service\TrainerPreferenceService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(TrainerPreferenceController::class)]
final class TrainerPreferenceControllerTest extends TestCase
{
    #[Test]
    public function get(): void
    {
        $service = $this->createMock(TrainerPreferenceService::class);
        $service->expects($this->once())
            ->method('get')
            ->willReturn(['home_page' => 'election'])
        ;

        $controller = new TrainerPreferenceController($service);

        $response = $controller->get();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"home_page":"election"}', $response->getContent());
    }

    #[Test]
    public function modify(): void
    {
        $service = $this->createMock(TrainerPreferenceService::class);
        $service->expects($this->once())
            ->method('modifyHomePage')
            ->with('game')
        ;

        $controller = new TrainerPreferenceController($service);

        $response = $controller->modify(Request::create('test.local', 'PUT', content: '{"home_page":"game"}'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    #[Test]
    #[DataProvider('providerBadBodies')]
    public function modifyRejectsABadBody(string $body): void
    {
        $service = $this->createMock(TrainerPreferenceService::class);
        $service->expects($this->never())->method('modifyHomePage');

        $controller = new TrainerPreferenceController($service);

        $response = $controller->modify(Request::create('test.local', 'PUT', content: $body));

        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * @return string[][]
     */
    public static function providerBadBodies(): array
    {
        return [
            'empty' => [''],
            'missing' => ['{}'],
            'not a string' => ['{"home_page":1}'],
        ];
    }

    #[Test]
    public function modifyForwardsTheApiStatusCode(): void
    {
        $service = $this->createMock(TrainerPreferenceService::class);
        $service->expects($this->once())
            ->method('modifyHomePage')
            ->with('admin')
            ->willThrowException(new ApiValidationException(400))
        ;

        $controller = new TrainerPreferenceController($service);

        $response = $controller->modify(Request::create('test.local', 'PUT', content: '{"home_page":"admin"}'));

        $this->assertSame(400, $response->getStatusCode());
    }
}
