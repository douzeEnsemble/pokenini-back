<?php

declare(strict_types=1);

namespace App\Tests\Integration\Election;

use App\Controller\Election\ElectionIndexController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use App\Tests\Integration\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(ElectionIndexController::class)]
class ElectionIndexTest extends WebTestCase
{
    use ClientRequestTrait;
    use JsonResponseTrait;

    #[DataProvider('providerDex')]
    public function testDex(string $dexName): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            "/election/{$dexName}",
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, "ElectionIndex/{$dexName}.json");
    }

    /**
     * @return string[][]
     */
    public static function providerDex(): array
    {
        return [
            ['demolite'],
            ['demolitelastpage'],
            ['demolitelastone'],
            ['demolitenotlastpage'],
            ['demolitenotlastone'],
        ];
    }
}
