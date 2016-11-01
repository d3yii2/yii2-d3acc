<?php

namespace d3acc\components;

use d3acc\models\AcPeriod;

class PeriodMonth extends PeriodBase
{

    public static function getFrom($date)
    {
        $d = new \DateTime($date);
        $d->modify('first day of this month');
        return $d->format('Y-m-d');
    }

    public static function getTo($date)
    {
        $d = new \DateTime($date);
        $d->modify('last day of this month');
        return $d->format('Y-m-d');
    }

    /**
     * find active period
     * @param int $periodType
     * @param string $date date format yyy-mm-dd
     * @return \d3acc\models\base\AcPeriod
     */
    public static function getActivePeriod($periodType, $date = false)
    {
        $query = AcPeriod::find()
            ->where(['period_type' => $periodType])
            ->andWhere(['status' => self::STATUS_ACTIVE]);
        if ($date) {
            $query->andWhere(" '".$date."' >= `from` and  '".$date."' <= `to`");
        }

        return $query->one();
    }
}