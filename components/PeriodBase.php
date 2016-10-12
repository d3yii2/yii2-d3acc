<?php

namespace d3acc\components;

use d3acc\models\AcPeriod;

class PeriodBase
{

    /**
     * creat first record for period
     * @param string $date  date format yyyy-mm-dd
     * @param int $periodType
     * @return AcPeriod
     * @throws \Exception
     */
    public static function init($date, $periodType)
    {

        if (AcPeriod::findAll(['period_type' => $periodType])) {
            throw new \Exception('Period type '.$periodType.' already exist');
        }

        $period              = new AcPeriod();
        $period->period_type = $periodType;
        $period->from        = static::getFrom($date);
        $period->to          = static::getTo($date);
        $period->status      = AcPeriod::STATUS_ACTIVE;
        if (!$period->save()) {
            throw new \Exception('Can not init period: '.json_encode($period->getErrors()));
        }

        return $period;
    }

    /**
     * add next period
     *
     * @param int $periodType
     * @return AcPeriod
     * @throws \Exception
     */
    public static function addNext($periodType)
    {

        if (!$lastPeriod = AcPeriod::find()
            ->where(['period_type' => $periodType])
            ->orderBy(['from' => SORT_DESC])
            ->one()
        ) {
            throw new \Exception('Period type '.$periodType.' do not exist');
        }

        $lastPeriod->status = AcPeriod::STATUS_CLOSED;
        if (!$lastPeriod->save()) {
            throw new \Exception('Can not close prev. period: '.json_encode($period->$lastPeriod()));
        }

        $date = new \DateTime($lastPeriod->to);
        $date->modify('+1 day');
        $from = $date->format('Y-m-d');

        $period              = new AcPeriod();
        $period->period_type = $periodType;
        $period->from        = $from;
        $period->to          = static::getTo($from);
        $period->status      = AcPeriod::STATUS_ACTIVE;
        if (!$period->save()) {
            throw new \Exception('Can not add next period: '.json_encode($period->getErrors()));
        }

        return $period;
    }
}