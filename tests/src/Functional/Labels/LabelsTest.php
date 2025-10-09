<?php

declare(strict_types=1);

namespace App\Tests\Functional\Labels;

use App\Controller\LabelsController;
use App\Tests\Functional\Trait\ClientRequestTrait;
use App\Tests\Functional\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(LabelsController::class)]
class LabelsTest extends WebTestCase
{
    use ClientRequestTrait;
    use JsonResponseTrait;

    #[DataProvider('providerGetLabels')]
    public function testGets(string $route, string $filename): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'GET',
            "/labels/{$route}",
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, "Labels/{$filename}.json");
    }

    #[DataProvider('providerGetLabels')]
    public function testGetsNonAuthenticated(string $route, string $filename): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            "/labels/{$route}",
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, "Labels/{$filename}.json");
    }

    /**
     * @return array<string, array{
     *     route: string,
     *     filename: string
     * }>
     */
    public static function providerGetLabels(): array
    {
        return [
            'all' => [
                'route' => 'all',
                'filename' => 'all',
            ],
            'catch_states' => [
                'route' => 'catch_states',
                'filename' => 'catch_states',
            ],
            'types' => [
                'route' => 'types',
                'filename' => 'types',
            ],
            'forms/category' => [
                'route' => 'forms/category',
                'filename' => 'forms_category',
            ],
            'forms/regional' => [
                'route' => 'forms/regional',
                'filename' => 'forms_regional',
            ],
            'forms/special' => [
                'route' => 'forms/special',
                'filename' => 'forms_special',
            ],
            'forms/variant' => [
                'route' => 'forms/variant',
                'filename' => 'forms_variant',
            ],
            'game_bundles' => [
                'route' => 'game_bundles',
                'filename' => 'game_bundles',
            ],
            'collections' => [
                'route' => 'collections',
                'filename' => 'collections',
            ],
        ];
    }
}
