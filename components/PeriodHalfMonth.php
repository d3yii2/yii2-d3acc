<?php

namespace d3acc\components;

use d3acc\models\AcPeriod;

class PeriodHalfMonth extends PeriodBase
{

    public static function getFrom($date)
    {
        $d = new \DateTime($date);
        if ((int) $d->format('d') < 16) {
            $d->modify('first day of this month');
            return $d->format('Y-m-d');
        }

        return $d->format('Y-m-16');
    }

    public static function getTo($date)
    {
        $d = new \DateTime($date);
        if ((int) $d->format('d') < 16) {

            return $d->format('Y-m-15');
        }

        $d->modify('last day of this month');
        return $d->format('Y-m-d');
    }
}