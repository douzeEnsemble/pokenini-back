<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Service\Api\GetCatchStatesService;
use App\Validator\CatchStates;
use App\Validator\CatchStatesValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @internal
 *
 * @extends ConstraintValidatorTestCase<CatchStatesValidator>
 */
#[CoversClass(CatchStatesValidator::class)]
#[UsesClass(CatchStates::class)]
final class CatchStatesValidatorTest extends ConstraintValidatorTestCase
{
    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new CatchStates());

        $this->assertNoViolation();
    }

    public function testUnexpectedType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('maybenot', new NotNull());
    }

    public function testUnexpectedValue(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate(new \DateTime(), new CatchStates());
    }

    #[\Override]
    protected function createValidator(): CatchStatesValidator
    {
        $getService = $this->createMock(GetCatchStatesService::class);
        $getService
            ->expects($this->never())
            ->method('get')
        ;

        return new CatchStatesValidator($getService);
    }
}
