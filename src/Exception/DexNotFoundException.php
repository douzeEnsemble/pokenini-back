<?php

declare(strict_types=1);

namespace App\Exception;

final class DexNotFoundException extends \RuntimeException
{
    #[\Override]
    protected $message = 'Dex not found';
}
