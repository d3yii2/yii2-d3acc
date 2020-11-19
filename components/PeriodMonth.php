<?php

namespace d3acc\components;

use DateTime;

class PeriodMonth extends PeriodBase
{

    public static function getFrom($date): string
    {
        $d = new DateTime($date);
        $d->modify('first day of this month');
        return $d->format('Y-m-d');
    }

    public static function getTo($date): string
    {
        $d = new DateTime($date);
        $d->modify('last day of this month');
        return $d->format('Y-m-d');
    }

}