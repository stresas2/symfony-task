<?php

namespace App\Validator;

use App\Client\NagerHolidays\HolidayProviderInterface;
use App\DTO\CreateShiftRequest;
use App\Service\HolidayService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class NotPublicHolidayValidator extends ConstraintValidator
{
    public function __construct(private readonly HolidayService $holidayService)
    {
    }


    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotPublicHoliday) {
            throw new UnexpectedTypeException($constraint, NotPublicHoliday::class);
        }

        if (!$value instanceof CreateShiftRequest) {
            throw new UnexpectedValueException($value, CreateShiftRequest::class);
        }

        $date = $value->getDate();
        $countryCode = $value->getCountryCode();

        if (null === $date || null === $countryCode) {
            return;
        }

        try {
            $isPublicHoliday = $this->holidayService->isPublicHoliday($countryCode, new \DateTimeImmutable((string) $date));
        } catch (\Throwable $e) {
            $this->context->buildViolation($constraint->errorToCheck . ' - ' . $e->getMessage())
                ->atPath('date')
                ->addViolation();

            return;
        }

        if (!$isPublicHoliday) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ date }}', (string) $date)
            ->setParameter('{{ country_code }}', $countryCode)
            ->atPath('date')
            ->addViolation();
    }
}
