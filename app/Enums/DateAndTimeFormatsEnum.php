<?php

namespace App\Enums;

enum DateAndTimeFormatsEnum: string
{
    case US_DATE_FORMAT = 'm/d/y';
    case US_DATE_TIME_FORMAT = 'm/d/y g:i A';
    case TWELVE_HOUR_TIME_FORMAT = 'g:i A';
}
