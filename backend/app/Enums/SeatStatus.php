<?php

namespace App\Enums;

enum SeatStatus: string
{
    case Available = 'available';
    case Locked = 'locked';
    case Sold = 'sold';
    case Blocked   = 'blocked';
}