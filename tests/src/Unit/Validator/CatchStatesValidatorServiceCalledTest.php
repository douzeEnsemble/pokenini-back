<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Service\Api\GetCatchStatesService;
use App\Validator\CatchStates;
use App\Validator\CatchStatesValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @internal
 *
 * @extends ConstraintValidatorTestCase<CatchStatesValidator>
 */
#[CoversClass(CatchStatesValidator::class)]
#[UsesClass(CatchStates::class)]
final class CatchStatesValidatorServiceCalledTest extends ConstraintValidatorTestCase
{
    public function testTrueIsInvalid(): void
    {
        $this->validator->validate('douze', new CatchStates());

        $this->buildViolation('"{{ string }}" is not a valid catch state')
            ->setParameter('{{ string }}', 'douze')
            ->assertRaised()
        ;
    }

    public function testTrueIsValid(): void
    {
        $this->validator->validate('maybenot', new CatchStates());

        $this->assertNoViolation();
    }

    #[\Override]
    protected function createValidator(): CatchStatesValidator
    {
        $getService = $this->createMock(GetCatchStatesService::class);
        $getService
            ->expects($this->once())
            ->method('get')
            ->willReturn([
                [
                    'name' => 'No',
                    'frenchName' => 'Non',
                    'slug' => 'no',
                    'color' => '#e57373',
                ],
                [
                    'name' => 'Maybe',
                    'frenchName' => 'Peut être',
                    'slug' => 'maybe',
                    'color' => '#9575cd',
                ],
                [
                    'name' => 'Maybe not',
                    'frenchName' => 'Peut être pas',
                    'slug' => 'maybenot',
                    'color' => '#9575cd',
                ],
                [
                    'name' => 'Yes',
                    'frenchName' => 'Oui',
                    'slug' => 'yes',
                    'color' => '#66bb6a',
                ],
            ])
        ;

        return new CatchStatesValidator($getService);
    }
}
