<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Exception\ModifyFailedException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class AdminActionApiService extends AbstractApiService
{
    public function update(string $type): void
    {
        try {
            $this->requestContent('POST', "/istration/update/{$type}");
        } catch (ExceptionInterface $e) {
            throw new ModifyFailedException($e->getMessage(), previous: $e);
        }
    }

    public function calculate(string $type): void
    {
        try {
            $this->requestContent('POST', "/istration/calculate/{$type}");
        } catch (ExceptionInterface $e) {
            throw new ModifyFailedException($e->getMessage(), previous: $e);
        }
    }
}
