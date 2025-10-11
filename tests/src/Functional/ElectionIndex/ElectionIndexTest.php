<?php

declare(strict_types=1);

namespace App\Tests\Functional\ElectionIndex;

use App\Controller\ElectionIndexController;
use App\Tests\Functional\Trait\ClientRequestTrait;
use App\Tests\Functional\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
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
