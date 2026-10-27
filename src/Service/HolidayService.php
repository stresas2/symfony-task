<?php

namespace App\Service;

use App\Client\NagerHolidays\Client;
use Psr\Cache\InvalidArgumentException;

class HolidayService
{
    public function __construct(private readonly Client $client)
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function isPublicHoliday(string $countryCode, \DateTimeImmutable $date): bool
    {
        $day = $date->format('Y-m-d');

        foreach ($this->client->getHolidays($countryCode, (int) $date->format('Y')) as $holiday) {
            if ($holiday->isPublic() && $holiday->date->format('Y-m-d') === $day) {
                return true;
            }
        }

        return false;
    }
}
