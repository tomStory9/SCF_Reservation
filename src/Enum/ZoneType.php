<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ZoneType: string implements TranslatableInterface
{
    case TRAINING = 'training';
    case BEDROOM = 'bedroom';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('admin.enum.zone_type.'.$this->value, locale: $locale);
    }
}
