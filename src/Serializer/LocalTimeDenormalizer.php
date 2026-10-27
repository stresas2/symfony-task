<?php

namespace App\Serializer;

use Brick\DateTime\DateTimeException;
use Brick\DateTime\Field\HourOfDay;
use Brick\DateTime\Field\MinuteOfHour;
use Brick\DateTime\LocalTime;
use Brick\DateTime\Parser\PatternParser;
use Brick\DateTime\Parser\PatternParserBuilder;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalizes LocalTime strictly from "HH:mm": brick's default ISO parser also accepts
 * seconds and fractions, which the API does not allow.
 */
final class LocalTimeDenormalizer implements DenormalizerInterface
{
    public const FORMAT = 'HH:mm';

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): LocalTime
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
            return LocalTime::from($this->parser()->parse($data));
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
        return LocalTime::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [LocalTime::class => true];
    }

    private function parser(): PatternParser
    {
        static $parser = null;

        return $parser ??= (new PatternParserBuilder())
            ->appendCapturePattern(HourOfDay::PATTERN, HourOfDay::NAME)
            ->appendLiteral(':')
            ->appendCapturePattern(MinuteOfHour::PATTERN, MinuteOfHour::NAME)
            ->toParser();
    }
}