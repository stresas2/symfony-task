<?php

namespace App\Validator;

use App\DTO\CreateShiftRequest;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class ValidShiftDurationValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidShiftDuration) {
            throw new UnexpectedTypeException($constraint, ValidShiftDuration::class);
        }

        if (!$value instanceof CreateShiftRequest) {
            throw new UnexpectedValueException($value, CreateShiftRequest::class);
        }

        // A missing time is already reported by the field constraints.
        $startTime = $value->getStartTime();
        $endTime = $value->getEndTime();

        if (null === $startTime || null === $endTime) {
            return;
        }

        if (!$endTime->isAfter($startTime)) {
            $this->context->buildViolation($constraint->endNotAfterStartMessage)
                ->atPath('endTime')
                ->addViolation();

            return;
        }

        $durationMinutes = ($endTime->toSecondOfDay() - $startTime->toSecondOfDay()) / 60;

        if ($durationMinutes > $constraint->maxDurationMinutes) {
            $this->context->buildViolation($constraint->tooLongMessage)
                ->setParameter('{{ hours }}', (string) ($constraint->maxDurationMinutes / 60))
                ->atPath('endTime')
                ->addViolation();
        }
    }
}
