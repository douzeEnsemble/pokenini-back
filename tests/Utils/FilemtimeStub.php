<?php

declare(strict_types=1);

namespace App\Tests\Utils;

/**
 * Lets tests force filemtime() (as called from the App\Service namespace) to
 * return false, deterministically simulating the TOCTOU race where a file
 * passes is_file() but its mtime can no longer be read.
 *
 * @see functions.php
 */
final class FilemtimeStub
{
    public static bool $forceFailure = false;
}
