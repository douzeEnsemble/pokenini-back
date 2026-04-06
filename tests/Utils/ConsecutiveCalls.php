<?php

declare(strict_types=1);

namespace Tests\Utils;

use PHPUnit\Framework\Assert;

final class ConsecutiveCalls
{
    private int $invocationCount = 0;

    /**
     * @var array<int, array<int|string, mixed>>
     */
    private array $consecutiveCallArguments;

    /**
     * @param array<int|string, mixed> ...$consecutiveCallArguments
     */
    public function __construct(array ...$consecutiveCallArguments)
    {
        $this->consecutiveCallArguments = array_values($consecutiveCallArguments);
    }

    public function __destruct()
    {
        Assert::assertSame(
            $expectedCalls = count($this->consecutiveCallArguments),
            $this->invocationCount,
            sprintf('Expected %d calls, called %d times', $expectedCalls, $this->invocationCount)
        );
    }

    /**
     * @param array<int, array<int, mixed>> $actualArguments
     */
    public function __invoke(array ...$actualArguments): mixed
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
}
