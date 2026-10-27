<?php

namespace App\Validator;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class ValidShiftDuration extends Constraint
{
    public string $endNotAfterStartMessage = 'end_time must be after start_time.';
    public string $tooLongMessage = 'Shift duration must not exceed {{ hours }} hours.';

    #[HasNamedArguments]
    public function __construct(
        public int $maxDurationMinutes = 12 * 60,
        ?string $endNotAfterStartMessage = null,
        ?string $tooLongMessage = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->endNotAfterStartMessage = $endNotAfterStartMessage ?? $this->endNotAfterStartMessage;
        $this->tooLongMessage = $tooLongMessage ?? $this->tooLongMessage;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}