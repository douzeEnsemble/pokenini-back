<?php

declare(strict_types=1);

namespace App\Utils;

final class JsonDecoder
{
    public static function decode(string $json): mixed
    {
        return json_decode(
            $json,
            true,
            depth: 7,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
