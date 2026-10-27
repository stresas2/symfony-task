<?php

namespace App\DTO;

use App\Validator\NoShiftOverlap;
use App\Validator\NotPublicHoliday;
use App\Validator\ValidShiftDuration;
use Brick\DateTime\LocalDate;
use Brick\DateTime\LocalTime;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;


#[Assert\GroupSequence(['CreateShiftRequest', 'Business'])]
#[ValidShiftDuration]
#[NotPublicHoliday(groups: ['Business'])]
#[NoShiftOverlap(groups: ['Business'])]
final class CreateShiftRequest
{
    public const SUPPORTED_COUNTRY_CODES = ['LT'];

    private const EMPLOYEE_ID_MESSAGE = 'employee_id is required and must be a non-empty string.';

    #[SerializedName('employee_id')]
    #[Assert\NotBlank(message: self::EMPLOYEE_ID_MESSAGE, normalizer: 'trim')]
    public ?string $employeeId = null;

    #[Assert\NotNull(message: 'date is required and must be a string in YYYY-MM-DD format.')]
    public ?LocalDate $date = null;

    #[SerializedName('start_time')]
    #[Assert\NotNull(message: 'start_time is required and must be a string in HH:mm (24h) format.')]
    public ?LocalTime $startTime = null;

    #[SerializedName('end_time')]
    #[Assert\NotNull(message: 'end_time is required and must be a string in HH:mm (24h) format.')]
    public ?LocalTime $endTime = null;

    #[SerializedName('country_code')]
    #[Assert\Sequentially([
        new Assert\NotBlank(message: 'country_code is required and must be a string.'),
        new Assert\Choice(
            choices: self::SUPPORTED_COUNTRY_CODES,
            message: 'country_code {{ value }} is not supported. Supported codes: {{ choices }}.',
        ),
    ])]
    public ?string $countryCode = null;

    public function getEmployeeId(): ?string
    {
        return $this->employeeId;
    }

    public function getDate(): ?LocalDate
    {
        return $this->date;
    }

    public function getStartTime(): ?LocalTime
    {
        return $this->startTime;
    }

    public function getEndTime(): ?LocalTime
    {
        return $this->endTime;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }
}
