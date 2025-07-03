<?php

namespace App\Tests\Functional\Trait;

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
