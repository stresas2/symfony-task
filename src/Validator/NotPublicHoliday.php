<?php

namespace App\Validator;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class NotPublicHoliday extends Constraint
{
    public string $message = '{{ date }} is a public holiday in {{ country_code }}; shifts cannot be scheduled on this date.';
    public string $errorToCheck = 'Error to check public holiday';
    #[HasNamedArguments]
    public function __construct(
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
