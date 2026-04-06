<?php

declare(strict_types=1);

namespace Tests\Utils;

use PHPUnit\Framework\Assert;

final class ConsecutiveCalls
{
    private int $invocationCount = 0;

    private array $consecutiveCallArguments;

    function __construct(array ...$consecutiveCallArguments)
    {
        $this->consecutiveCallArguments = $consecutiveCallArguments;
    }

    function __invoke(...$actualArguments)
    {
        $callIndex = $this->invocationCount++;
        $expectedArguments = $this->consecutiveCallArguments[$callIndex];

        $returnValue = null;
        if (count($actualArguments) < count($expectedArguments)) {
            $returnValue = array_pop($expectedArguments);
        }

        Assert::assertSame(
            $expectedArguments,
            $actualArguments,
            sprintf('Expected arguments for call %d do not match actual arguments', $callIndex),
        );

        return $returnValue;
    }

    function __destruct()
    {
        Assert::assertSame(
            $expectedCalls = count($this->consecutiveCallArguments),
            $this->invocationCount,
            sprintf('Expected %d calls, called %d times', $expectedCalls, $this->invocationCount)
        );
    }
}
