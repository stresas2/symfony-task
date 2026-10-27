<?php

namespace App\Tests\Unit\Service;

use App\Client\NagerHolidays\Client;
use App\Service\HolidayService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class HolidayServiceTest extends TestCase
{
    private const SAMPLE_RESPONSE = [
        ['date' => '2026-01-01', 'nationalHoliday' => true, 'holidayTypes' => ['Public']],
        ['date' => '2026-02-16', 'nationalHoliday' => true, 'holidayTypes' => ['Public']],
        ['date' => '2026-05-01', 'nationalHoliday' => false, 'holidayTypes' => ['Public']],
        ['date' => '2026-06-24', 'nationalHoliday' => true, 'holidayTypes' => ['Bank']],
    ];

    private function makeService(): HolidayService
    {
        $client = new Client(
            new MockHttpClient([new MockResponse(json_encode(self::SAMPLE_RESPONSE))]),
            new ArrayAdapter(),
            new NullLogger(),
            'https://nagerholidays.example/api/v4',
            5,
        );

        return new HolidayService($client);
    }

    public function testRecognizesAPublicNationalHoliday(): void
    {
        self::assertTrue($this->makeService()->isPublicHoliday('LT', new \DateTimeImmutable('2026-01-01')));
    }

    public function testOrdinaryDayIsNotAHoliday(): void
    {
        self::assertFalse($this->makeService()->isPublicHoliday('LT', new \DateTimeImmutable('2026-01-02')));
    }

    public function testNonNationalHolidayIsIgnored(): void
    {
        self::assertFalse($this->makeService()->isPublicHoliday('LT', new \DateTimeImmutable('2026-05-01')));
    }

    public function testNonPublicHolidayTypeIsIgnored(): void
    {
        self::assertFalse($this->makeService()->isPublicHoliday('LT', new \DateTimeImmutable('2026-06-24')));
    }

    public function testTimeOfDayDoesNotMatter(): void
    {
        self::assertTrue($this->makeService()->isPublicHoliday('LT', new \DateTimeImmutable('2026-02-16 13:45')));
    }
}
