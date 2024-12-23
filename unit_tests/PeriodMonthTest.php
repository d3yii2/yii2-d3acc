<?php

namespace utest;

use d3acc\components\PeriodMonth;
use d3acc\models\AcPeriod;

class PeriodMonthTest extends \PHPUnit_Framework_TestCase
{
    const PERIOD_TYPE = 8;

    public function setUp(): void
    {
        $this->deletePeriodType(self::PERIOD_TYPE);
    }

    public function tearDown(): void
    {
        $this->deletePeriodType(self::PERIOD_TYPE);
    }

    public function deletePeriodType($type)
    {
        foreach (AcPeriod::findAll([
            'period_type' => $type,
            'sys_company_id' => 1
        ]) as $period) {
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
        $period = PeriodMonth::init('2015-01-05', self::PERIOD_TYPE,1);
        $this->assertInstanceOf('\d3acc\models\AcPeriod', $period);

        //VALIDATE ACTIVE PERIOD
        $activePeriod = AcPeriod::getActivePeriod(1,self::PERIOD_TYPE);
        $this->assertEquals($activePeriod->id, $period->id);

        //ADD NEXT
        $period = PeriodMonth::close(1,self::PERIOD_TYPE,1);
        $this->assertInstanceOf('\d3acc\models\AcPeriod', $period);

        //VALIDATE ACTIVE PERIOD
        $activePeriod = AcPeriod::getActivePeriod(1,self::PERIOD_TYPE);
        $this->assertEquals($activePeriod->id, $period->id);
    }
}