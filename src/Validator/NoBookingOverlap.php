<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class NoBookingOverlap extends Constraint
{
    public string $message = 'errors.booking_overlap';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
