<?php

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\Shift;
use App\Event\ShiftCreatedEvent;
use App\EventSubscriber\ShiftSubscriber;
use App\Repository\ShiftNotificationRepository;
use PHPUnit\Framework\TestCase;

final class ShiftSubscriberTest extends TestCase
{
    private function shift(): Shift
    {
        return (new Shift())
            ->setEmployeeId('employee-123')
            ->setStart(new \DateTimeImmutable('2026-10-06 09:00'))
            ->setEnd(new \DateTimeImmutable('2026-10-06 17:00'))
            ->setCountryCode('LT')
            ->setCreatedAt(new \DateTimeImmutable('2026-10-06 08:00'));
    }

    public function testSavesANotificationWhenEnabled(): void
    {
        $shift = $this->shift();

        $repository = $this->createMock(ShiftNotificationRepository::class);
        $repository->expects(self::once())
            ->method('saveFromShift')
            ->with($shift);

        $subscriber = new ShiftSubscriber($repository, notificationsEnabled: true);
        $subscriber->createNotification(new ShiftCreatedEvent($shift));
    }

    public function testDoesNothingWhenDisabled(): void
    {
        $repository = $this->createMock(ShiftNotificationRepository::class);
        $repository->expects(self::never())->method('saveFromShift');

        $subscriber = new ShiftSubscriber($repository, notificationsEnabled: false);
        $subscriber->createNotification(new ShiftCreatedEvent($this->shift()));
    }
}
