<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception;

use App\Exception\ActionAlreadyRunningException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ActionAlreadyRunningException::class)]
final class ActionAlreadyRunningExceptionTest extends TestCase
{
    #[Test]
    public function messageIsATranslationKey(): void
    {
        $previous = new \RuntimeException('api 409');

        $exception = new ActionAlreadyRunningException($previous);

        $this->assertSame('admin.action.already_running', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
