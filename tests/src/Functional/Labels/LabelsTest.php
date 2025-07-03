<?php

declare(strict_types=1);

namespace App\Tests\Functional\Labels;

use App\Controller\LabelsController;
use App\Tests\Functional\Trait\ClientRequestTrait;
use App\Tests\Functional\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(LabelsController::class)]
class LabelsTest extends WebTestCase
{
    use ClientRequestTrait;
    use JsonResponseTrait;

    public function testGetAll(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'GET',
            '/labels/all',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Labels/all.json');
    }
}
