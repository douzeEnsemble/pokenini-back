<?php

declare(strict_types=1);

namespace App\Exception;

final class TooManyDexOnHubException extends \RuntimeException
{
    protected $message = 'Too many dex on the hub';
}
