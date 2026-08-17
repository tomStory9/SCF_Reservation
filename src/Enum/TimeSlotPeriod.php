<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TimeSlotPeriod: string implements TranslatableInterface
{
    case HOURLY = 'hourly';
    case MORNING = 'morning';
    case AFTERNOON = 'afternoon';
    case EVENING = 'evening';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('admin.enum.time_slot_period.'.$this->value, locale: $locale);
    }
}
