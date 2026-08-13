<?php

namespace App\Enum;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case DECLINED = 'declined';
    case PAID = 'paid';
}
