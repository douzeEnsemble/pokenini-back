<?php

declare(strict_types=1);

namespace App\Tests\Integration\Trait;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait ClientRequestTrait
{
    /**
     * @param string[]|string[][]|string[][][] $parameters
     * @param string[]                         $files
     * @param string[]                         $server
     */
    protected function authenticatedRequest(
        KernelBrowser $client,
        string $roleCode,
        string $method,
        string $uri,
        array $parameters = [],
        array $files = [],
        array $server = [],
        ?string $content = null,
    ): void {
        $client->request(
            $method,
            $uri,
            $parameters,
            $files,
            array_merge(
                [
                    'HTTP_AUTHORIZATION' => 'Bearer this-is-the-'.$roleCode.'-token',
                    'HTTP_X-Provider' => 'mock',
                ],
                $server
            ),
            $content,
        );
    }
}
