<?php

namespace d3acc\components;


class PeriodHalfMonth extends PeriodBase
{

    public static function getFrom($date)
    {
        $d = new \DateTime($date);
        if ((int) $d->format('d') < 15) {
            $d->modify('first day of this month');
            return $d->format('Y-m-d');
        }

        return $d->format('Y-m-15');
    }

    public static function getTo($date)
    {
        $d = new \DateTime($date);
        if ((int) $d->format('d') < 15) {

            return $d->format('Y-m-15');
        }

        $d->modify('last day of this month');
        return $d->format('Y-m-d');
    }
}