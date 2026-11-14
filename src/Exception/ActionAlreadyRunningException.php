<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * The api refused to start an action that is already running (HTTP 409). The message is
 * a translation key, displayed as is by the web admin.
 */
final class ActionAlreadyRunningException extends \RuntimeException
{
    public const string MESSAGE = 'admin.action.already_running';

    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(self::MESSAGE, 0, $previous);
    }
}
