<?php

declare(strict_types=1);

namespace App\Tests\Integration\Trait;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait JsonResponseTrait
{
    protected function assertResponseContent(
        KernelBrowser $client,
        string $filePath,
    ): void {
        $this->assertJsonStringEqualsJsonFile(
            __DIR__.'/../../../resources/functional/controller/'.$filePath,
            (string) $client->getResponse()->getContent(),
        );
    }
}
