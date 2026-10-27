<?php

namespace App\Client\NagerHolidays\Exception;

/**
 * Thrown when the public-holiday calendar for a country/year cannot be determined
 * (external API unreachable, erroring, or returning something we can't parse) and
 * there is no usable cached result to fall back on.
 */
final class HolidayLookupException extends \RuntimeException
{
}
