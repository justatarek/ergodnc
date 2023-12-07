<?php

namespace App\Enums;

enum ReservationStatus: int
{
    case Active = 1;

    case Canceled = 2;
}
