<?php

namespace App\Tests\Functional;

use App\Entity\Shift;
use App\Entity\ShiftNotification;
use App\Service\HolidayService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ShiftControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private HolidayService&MockObject $holidayService;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->holidayService = $this->createMock(HolidayService::class);
        $container->set(HolidayService::class, $this->holidayService);

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'employee_id' => 'employee-123',
            'date' => '2026-10-06',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'country_code' => 'LT',
        ], $overrides);
    }

    private function postShift(array $payload): Response
    {
        $this->client->request(
            'POST',
            '/shifts',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload),
        );

        return $this->client->getResponse();
    }

    private function decode(Response $response): array
    {
        return json_decode($response->getContent(), associative: true);
    }

    private function assertBadRequestWithErrorContaining(Response $response, string $needle): void
    {
        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString($needle, implode(' ', $this->decode($response)['errors']));
    }

    public function testCreatingAShiftReturns201WithThePersistedShift(): void
    {
        $response = $this->postShift($this->validPayload());

        self::assertSame(201, $response->getStatusCode());

        $body = $this->decode($response);
        self::assertNotEmpty($body['id']);
        self::assertSame('employee-123', $body['employee_id']);
        self::assertSame('2026-10-06 09:00', $body['start']);
        self::assertSame('2026-10-06 17:00', $body['end']);
        self::assertSame('LT', $body['country_code']);
        self::assertNotEmpty($body['created_at']);

        self::assertCount(1, $this->entityManager->getRepository(Shift::class)->findAll());
    }

    public function testCreatingAShiftRecordsANotification(): void
    {
        $shiftId = $this->decode($this->postShift($this->validPayload()))['id'];

        $notifications = $this->entityManager->getRepository(ShiftNotification::class)->findAll();
        self::assertCount(1, $notifications);
        self::assertSame('employee-123', $notifications[0]->getEmployeeId());
        self::assertSame($shiftId, $notifications[0]->getData()['shift_id']);
    }

    public function testMalformedJsonBodyReturns400(): void
    {
        $this->client->request('POST', '/shifts', server: ['CONTENT_TYPE' => 'application/json'], content: '{not valid json');

        self::assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testMissingFieldReturns400WithDetails(): void
    {
        $payload = $this->validPayload();
        unset($payload['employee_id']);

        $response = $this->postShift($payload);

        $this->assertBadRequestWithErrorContaining($response, 'employee_id');
        self::assertSame('Validation error', $this->decode($response)['message']);
    }

    public function testInvalidDateFormatReturns400(): void
    {
        $response = $this->postShift($this->validPayload(['date' => '06-10-2026']));

        $this->assertBadRequestWithErrorContaining($response, 'date');
    }

    public function testInvalidTimeFormatReturns400(): void
    {
        $response = $this->postShift($this->validPayload(['start_time' => '25:00']));

        $this->assertBadRequestWithErrorContaining($response, 'startTime');
    }

    public function testEndTimeBeforeStartTimeReturns400(): void
    {
        $response = $this->postShift($this->validPayload(['start_time' => '17:00', 'end_time' => '09:00']));

        $this->assertBadRequestWithErrorContaining($response, 'after');
    }

    public function testShiftLongerThanTwelveHoursReturns400(): void
    {
        $response = $this->postShift($this->validPayload(['start_time' => '06:00', 'end_time' => '18:01']));

        $this->assertBadRequestWithErrorContaining($response, '12 hours');
    }

    public function testShiftOfExactlyTwelveHoursIsAllowed(): void
    {
        $response = $this->postShift($this->validPayload(['start_time' => '06:00', 'end_time' => '18:00']));

        self::assertSame(201, $response->getStatusCode());
    }

    public function testUnsupportedCountryCodeReturns400(): void
    {
        $response = $this->postShift($this->validPayload(['country_code' => 'US']));

        $this->assertBadRequestWithErrorContaining($response, 'country_code');
    }

    public function testBackToBackShiftsForTheSameEmployeeAreAllowed(): void
    {
        $first = $this->postShift($this->validPayload(['start_time' => '09:00', 'end_time' => '13:00']));
        self::assertSame(201, $first->getStatusCode());

        $second = $this->postShift($this->validPayload(['start_time' => '13:00', 'end_time' => '17:00']));
        self::assertSame(201, $second->getStatusCode());
    }

    public function testOverlappingShiftForTheSameEmployeeReturns400(): void
    {
        $first = $this->postShift($this->validPayload(['start_time' => '09:00', 'end_time' => '17:00']));
        self::assertSame(201, $first->getStatusCode());

        $second = $this->postShift($this->validPayload(['start_time' => '16:00', 'end_time' => '20:00']));

        $this->assertBadRequestWithErrorContaining($second, 'overlaps');
        self::assertCount(1, $this->entityManager->getRepository(Shift::class)->findAll());
    }

    public function testOverlapCheckIsScopedToTheSameEmployee(): void
    {
        $first = $this->postShift($this->validPayload(['employee_id' => 'employee-A', 'start_time' => '09:00', 'end_time' => '17:00']));
        self::assertSame(201, $first->getStatusCode());

        $second = $this->postShift($this->validPayload(['employee_id' => 'employee-B', 'start_time' => '09:00', 'end_time' => '17:00']));
        self::assertSame(201, $second->getStatusCode());
    }

    public function testShiftOnAPublicHolidayReturns400(): void
    {
        $this->holidayService->expects(self::once())
            ->method('isPublicHoliday')
            ->with('LT', new \DateTimeImmutable('2026-10-06'))
            ->willReturn(true);

        $response = $this->postShift($this->validPayload());

        $this->assertBadRequestWithErrorContaining($response, 'public holiday');
        self::assertCount(0, $this->entityManager->getRepository(Shift::class)->findAll());
    }
}
