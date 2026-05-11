<?php

declare(strict_types=1);

namespace App\Service\Api;

class ModifyDexApiService extends AbstractApiService
{
    public function modify(
        string $dexSlug,
        string $data,
        string $trainerId
    ): void {
        $this->requestContent(
            'PUT',
            "/dex/{$trainerId}/{$dexSlug}",
            [
                'body' => $data,
            ]
        );
    }
}
