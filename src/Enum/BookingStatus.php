<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum BookingStatus: string implements TranslatableInterface
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case DECLINED = 'declined';
    case PAID = 'paid';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('admin.enum.booking_status.'.$this->value, locale: $locale);
    }
}
