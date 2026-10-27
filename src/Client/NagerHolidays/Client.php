<?php

namespace App\Client\NagerHolidays;

use App\Client\NagerHolidays\DTO\Holiday;
use App\Client\NagerHolidays\Exception\HolidayLookupException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Client
{
    private const CACHE_TTL_SECONDS = 86400;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly string $baseUrl,
        private readonly int $timeout,
    ) {
    }

    /**
     * GET {baseUrl}/Holidays/{countryCode}/{year}
     *
     * @return Holiday[]
     *
     * @throws HolidayLookupException
     * @throws InvalidArgumentException
     */
    public function getHolidays(string $countryCode, int $year): array
    {
        $cacheKey = sprintf('nager.holidays.%s.%d', $countryCode, $year);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($countryCode, $year): array {
            $item->expiresAfter(self::CACHE_TTL_SECONDS);

            return $this->fetchHolidays($countryCode, $year);
        });
    }

    /**
     * @return Holiday[]
     */
    private function fetchHolidays(string $countryCode, int $year): array
    {
        $url = sprintf('%s/Holidays/%s/%d', rtrim($this->baseUrl, '/'), $countryCode, $year);

        try {
            $response = $this->httpClient->request('GET', $url, ['timeout' => $this->timeout]);
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface|\Throwable $e) {
            $this->logger->error('Holiday service unreachable.', ['url' => $url, 'exception' => $e->getMessage()]);

            throw new HolidayLookupException('Could not reach the public holiday service.');
        }

        if (Response::HTTP_OK !== $statusCode) {
            $this->logger->error('Holiday service returned a non-200 status.', ['url' => $url, 'status' => $statusCode]);

            throw new HolidayLookupException(sprintf('Holiday service responded with HTTP %d.', $statusCode));
        }

        try {
            $data = json_decode($response->getContent(false), associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (TransportExceptionInterface|\Throwable $e) {
            $this->logger->error('Could not read the holiday service response.', ['url' => $url, 'exception' => $e->getMessage()]);

            throw new HolidayLookupException('Could not read the public holiday service response.');
        }

        if (!\is_array($data) || !array_is_list($data)) {
            $this->logger->error('Holiday service returned an unexpected response structure.', ['url' => $url]);

            throw new HolidayLookupException('Holiday service returned an unexpected response structure.');
        }

        try {
            $holidays = [];
            foreach ($data as $holidayData) {
                $holidays[] = $this->toHoliday($holidayData);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Holiday service returned an unexpected response structure.', ['exception' => $e->getMessage()]);

            throw new HolidayLookupException('Holiday service returned an unexpected response structure.');
        }

        return $holidays;
    }

    private function toHoliday(array $holidayData): Holiday
    {
        if (!isset($holidayData['date'], $holidayData['nationalHoliday'], $holidayData['holidayTypes'])) {
            throw new \UnexpectedValueException('Holiday entry is missing date, nationalHoliday or holidayTypes.');
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $holidayData['date']);

        return new Holiday($date, $holidayData['nationalHoliday'], array_values(array_filter($holidayData['holidayTypes'], 'is_string')));
    }
}
