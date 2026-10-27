<?php

namespace App\Validator;

use App\DTO\CreateShiftRequest;
use App\Repository\ShiftRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class NoShiftOverlapValidator extends ConstraintValidator
{
    public function __construct(private readonly ShiftRepository $shifts)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoShiftOverlap) {
            throw new UnexpectedTypeException($constraint, NoShiftOverlap::class);
        }

        if (!$value instanceof CreateShiftRequest) {
            throw new UnexpectedValueException($value, CreateShiftRequest::class);
        }

        // Missing fields are already reported by the field constraints.
        $employeeId = $value->getEmployeeId();
        $date = $value->getDate();
        $startTime = $value->getStartTime();
        $endTime = $value->getEndTime();

        if (null === $employeeId || null === $date || null === $startTime || null === $endTime) {
            return;
        }

        $hasOverlap = $this->shifts->hasOverlap(
            $employeeId,
            new \DateTimeImmutable((string) $date->atTime($startTime)),
            new \DateTimeImmutable((string) $date->atTime($endTime)),
        );

        if ($hasOverlap) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
