<?php

namespace utest;

use yii;
use d3acc\components\PeriodMonth;
use d3acc\models\AcPeriod;

class PeriodMonthTest extends \PHPUnit_Framework_TestCase
{
    const PERIOD_TYPE = 8;

    public function setUp()
    {
        $this->deletePeriodType(self::PERIOD_TYPE);
    }

    public function tearDown()
    {
        $this->deletePeriodType(self::PERIOD_TYPE);
    }

    public function deletePeriodType($type)
    {
        foreach (AcPeriod::findAll(['period_type' => $type]) as $period) {
            if($nextPeriod = $period->getNextPeriod()->one()){
                $nextPeriod->prev_period = null;
                $nextPeriod->save();
            }
            if($prevPeriod = $period->getPrevPeriod()->one()){
                $prevPeriod->next_period = null;
                $prevPeriod->save();
            }
            $period->delete();
        }
    }

    public function testInit()
    {
        //INIT
        $period = PeriodMonth::init('2015-01-05', self::PERIOD_TYPE);
        $this->assertInstanceOf('\d3acc\models\AcPeriod', $period);

        //VALIDATE ACTIVE PERIOD
        $activePeriod = AcPeriod::getActivePeriod(self::PERIOD_TYPE);
        $this->assertEquals($activePeriod->id, $period->id);

        //ADD NEXT
        $period = PeriodMonth::close(self::PERIOD_TYPE);
        $this->assertInstanceOf('\d3acc\models\AcPeriod', $period);

        //VALIDATE ACTIVE PERIOD
        $activePeriod = AcPeriod::getActivePeriod(self::PERIOD_TYPE);
        $this->assertEquals($activePeriod->id, $period->id);
    }
}