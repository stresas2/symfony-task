<?php

namespace App\Event;

use App\Entity\Shift;
use Symfony\Contracts\EventDispatcher\Event;

final class ShiftCreatedEvent extends Event
{
    public function __construct(public readonly Shift $shift)
    {
    }
}
