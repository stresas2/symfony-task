<?php

namespace App\EventSubscriber;

use App\Event\ShiftCreatedEvent;
use App\Repository\ShiftNotificationRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ShiftSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ShiftNotificationRepository $shiftNotificationRepository,
        private readonly bool $notificationsEnabled,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ShiftCreatedEvent::class => 'createNotification',
        ];
    }

    public function createNotification(ShiftCreatedEvent $event): void
    {
        if (!$this->notificationsEnabled) {
            return;
        }

        $this->shiftNotificationRepository->saveFromShift($event->shift);
    }
}
