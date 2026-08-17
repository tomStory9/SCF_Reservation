<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum UserStatus: string implements TranslatableInterface
{
    case PENDING = 'pending';
    case APPROVED = 'approved';

    case DECLINED = 'declined';
    case SUSPENDED = 'suspended';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('admin.enum.user_status.'.$this->value, locale: $locale);
    }
}
