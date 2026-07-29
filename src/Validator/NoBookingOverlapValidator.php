<?php

namespace App\Validator;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NoBookingOverlapValidator extends ConstraintValidator
{
    public function __construct(
        private readonly BookingRepository $bookingRepository
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoBookingOverlap) {
            throw new UnexpectedTypeException($constraint, NoBookingOverlap::class);
        }

        if (!$value instanceof Booking) {
            return;
        }

        $start = $value->getStartDate();
        $end = $value->getEndDate();
        $zone = $value->getZone();

        if (!$start || !$end || !$zone) {
            return;
        }

        if ($this->bookingRepository->hasOverlap($zone, $start, $end, $value->getId())) {
            $this->context->buildViolation($constraint->message)
                ->atPath('startDate')
                ->addViolation();
        }
    }
}
