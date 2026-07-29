<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class NoBookingOverlap extends Constraint
{
    public string $message = 'créneau déjà réservé';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
