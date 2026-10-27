<?php

namespace App\Serializer;

use Brick\DateTime\DateTimeException;
use Brick\DateTime\Field\DayOfMonth;
use Brick\DateTime\Field\MonthOfYear;
use Brick\DateTime\Field\Year;
use Brick\DateTime\LocalDate;
use Brick\DateTime\Parser\PatternParser;
use Brick\DateTime\Parser\PatternParserBuilder;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalizes LocalDate strictly from "YYYY-MM-DD": brick's default ISO parser also accepts
 * signed or 5+ digit years, which the API does not allow.
 */
final class LocalDateDenormalizer implements DenormalizerInterface
{
    public const FORMAT = 'YYYY-MM-DD';

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): LocalDate
    {
        if (!\is_string($data)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                sprintf('must be a string in %s format.', self::FORMAT),
                $data,
                ['string'],
                $context['deserialization_path'] ?? null,
                true,
            );
        }

        try {
            return LocalDate::from($this->parser()->parse($data));
        } catch (DateTimeException) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                sprintf('must be a valid value in %s format, "%s" given.', self::FORMAT, $data),
                $data,
                ['string'],
                $context['deserialization_path'] ?? null,
                true,
            );
        }
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return LocalDate::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [LocalDate::class => true];
    }

    private function parser(): PatternParser
    {
        static $parser = null;

        return $parser ??= (new PatternParserBuilder())
            ->appendCapturePattern('[0-9]{4}', Year::NAME)
            ->appendLiteral('-')
            ->appendCapturePattern(MonthOfYear::PATTERN, MonthOfYear::NAME)
            ->appendLiteral('-')
            ->appendCapturePattern(DayOfMonth::PATTERN, DayOfMonth::NAME)
            ->toParser();
    }
}