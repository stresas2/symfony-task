<?php

namespace App\Tests\Unit\DTO;

use App\DTO\CreateShiftRequest;
use App\Repository\ShiftRepository;
use App\Service\HolidayService;
use App\Validator\NoShiftOverlapValidator;
use App\Validator\NotPublicHolidayValidator;
use Brick\DateTime\LocalDate;
use Brick\DateTime\LocalTime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateShiftRequestValidationTest extends TestCase
{
    private ShiftRepository $shiftRepository;
    private HolidayService $holidayService;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->shiftRepository = $this->createMock(ShiftRepository::class);
        $this->holidayService = $this->createMock(HolidayService::class);

        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                NoShiftOverlapValidator::class => new NoShiftOverlapValidator($this->shiftRepository),
                NotPublicHolidayValidator::class => new NotPublicHolidayValidator($this->holidayService),
            ]))
            ->getValidator();
    }

    private function request(array $overrides = []): CreateShiftRequest
    {
        $data = array_merge([
            'employeeId' => 'employee-123',
            'date' => '2026-10-06',
            'startTime' => '09:00',
            'endTime' => '17:00',
            'countryCode' => 'LT',
        ], $overrides);

        $request = new CreateShiftRequest();
        $request->employeeId = $data['employeeId'];
        $request->date = null !== $data['date'] ? LocalDate::parse($data['date']) : null;
        $request->startTime = null !== $data['startTime'] ? LocalTime::parse($data['startTime']) : null;
        $request->endTime = null !== $data['endTime'] ? LocalTime::parse($data['endTime']) : null;
        $request->countryCode = $data['countryCode'];

        return $request;
    }

    /**
     * @return string[]
     */
    private function errors(CreateShiftRequest $request): array
    {
        $errors = [];
        foreach ($this->validator->validate($request) as $violation) {
            $errors[] = (string) $violation->getMessage();
        }

        return $errors;
    }

    private function assertErrorContaining(CreateShiftRequest $request, string $needle): void
    {
        self::assertStringContainsString($needle, implode(' ', $this->errors($request)));
    }

    public function testValidRequestHasNoErrors(): void
    {
        self::assertSame([], $this->errors($this->request()));
    }

    public function testExactlyTwelveHoursIsAllowed(): void
    {
        self::assertSame([], $this->errors($this->request(['startTime' => '07:00', 'endTime' => '19:00'])));
    }

    public function testMissingEmployeeIdIsRejected(): void
    {
        $this->assertErrorContaining($this->request(['employeeId' => null]), 'employee_id');
    }

    public function testBlankEmployeeIdIsRejected(): void
    {
        $this->assertErrorContaining($this->request(['employeeId' => '   ']), 'employee_id');
    }

    public function testMissingDateIsRejected(): void
    {
        $this->assertErrorContaining($this->request(['date' => null]), 'date');
    }

    public function testMissingStartTimeIsRejected(): void
    {
        $this->assertErrorContaining($this->request(['startTime' => null]), 'start_time');
    }

    public function testEndTimeEqualToStartTimeIsRejected(): void
    {
        $this->assertErrorContaining($this->request(['startTime' => '09:00', 'endTime' => '09:00']), 'after');
    }

    public function testEndTimeBeforeStartTimeIsRejected(): void
    {
        $this->assertErrorContaining($this->request(['startTime' => '17:00', 'endTime' => '09:00']), 'after');
    }

    public function testDurationOverTwelveHoursIsRejected(): void
    {
        $this->assertErrorContaining($this->request(['startTime' => '06:00', 'endTime' => '18:01']), '12 hours');
    }

    public function testUnsupportedCountryCodeIsRejected(): void
    {
        $this->assertErrorContaining($this->request(['countryCode' => 'US']), 'country_code');
    }

    public function testMultipleErrorsAreAllReportedTogether(): void
    {
        self::assertCount(2, $this->errors($this->request(['employeeId' => '', 'date' => null])));
    }

    public function testPublicHolidayIsRejected(): void
    {
        $this->holidayService->method('isPublicHoliday')->willReturn(true);

        $this->assertErrorContaining($this->request(), 'public holiday');
    }

    public function testHolidayLookupFailureIsReported(): void
    {
        $this->holidayService->method('isPublicHoliday')->willThrowException(new \RuntimeException('Nager is down'));

        $this->assertErrorContaining($this->request(), 'Nager is down');
    }

    public function testOverlappingShiftIsRejected(): void
    {
        $this->shiftRepository->method('hasOverlap')->willReturn(true);

        $this->assertErrorContaining($this->request(), 'overlaps');
    }

    public function testOverlapCheckGetsTheFullShiftPeriod(): void
    {
        $this->shiftRepository->expects(self::once())
            ->method('hasOverlap')
            ->with(
                'employee-123',
                new \DateTimeImmutable('2026-10-06 09:00'),
                new \DateTimeImmutable('2026-10-06 17:00'),
            )
            ->willReturn(false);

        $this->errors($this->request());
    }

    public function testBusinessRulesAreSkippedWhenFieldRulesFail(): void
    {
        $this->holidayService->expects(self::never())->method('isPublicHoliday');
        $this->shiftRepository->expects(self::never())->method('hasOverlap');

        $this->errors($this->request(['countryCode' => 'US']));
    }
}
