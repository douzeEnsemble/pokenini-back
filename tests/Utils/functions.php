<?php

declare(strict_types=1);

namespace App\Service;

use App\Tests\Utils\FilemtimeStub;

/**
 * Overrides the global filemtime() for code in the App\Service namespace,
 * so tests can deterministically simulate filemtime() failing right after
 * is_file() succeeded (a TOCTOU race that can't be reproduced reliably by
 * manipulating real files).
 */
function filemtime(string $path): false|int
{
    if (FilemtimeStub::$forceFailure) {
        return false;
    }

    return \filemtime($path);
}
