<?php

namespace App\Client\NagerHolidays\DTO;

final class Holiday
{
    /**
     * @param string[] $types
     */
    public function __construct(
        public readonly \DateTimeImmutable $date,
        public readonly bool $nationalHoliday,
        public readonly array $types,
    ) {
    }

    public function isPublic(): bool
    {
        return $this->nationalHoliday && \in_array('Public', $this->types, true);
    }
}
