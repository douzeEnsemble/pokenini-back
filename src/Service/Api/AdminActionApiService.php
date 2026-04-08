<?php

declare(strict_types=1);

namespace App\Service\Api;

class AdminActionApiService extends AbstractApiService
{
    public function update(string $type): void
    {
        $this->requestContent(
            'POST',
            "/istration/update/{$type}"
        );
    }

    public function calculate(string $type): void
    {
        $this->requestContent(
            'POST',
            "/istration/calculate/{$type}"
        );
    }
}
