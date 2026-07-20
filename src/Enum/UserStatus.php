<?php

namespace App\Enum;

enum UserStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';

    case DECLINED = 'declined';
    case SUSPENDED = 'suspended';
}
