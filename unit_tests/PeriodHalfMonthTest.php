<?php

namespace utest;

use d3acc\components\PeriodHalfMonth;
use d3acc\models\AcPeriod;

class PeriodHalfMonthTest extends \PHPUnit_Framework_TestCase
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
        foreach (AcPeriod::find()
                     ->where([
                         'period_type' => $type,
                         'sys_company_id' => 1
                     ])
                     ->orderBy(['id'=> SORT_DESC])
                     ->all()
                 as $period
        ) {
            $period->delete();
        }
    }

    public function testInit()
    {
        //INIT
        $period = PeriodHalfMonth::init('2015-01-05', self::PERIOD_TYPE,1);
        $this->assertInstanceOf('\d3acc\models\AcPeriod', $period);

        //VALIDATE ACTIVE PERIOD
        $activePeriod = AcPeriod::getActivePeriod(1,self::PERIOD_TYPE);
        $this->assertEquals($activePeriod->id, $period->id);

        //ADD NEXT
        $period = PeriodHalfMonth::close(self::PERIOD_TYPE,1);
        $this->assertInstanceOf('\d3acc\models\AcPeriod', $period);

        //VALIDATE ACTIVE PERIOD
        $activePeriod = AcPeriod::getActivePeriod(1,self::PERIOD_TYPE);
        $this->assertEquals($activePeriod->id, $period->id);

        $dates = $activePeriod->getDates();
        $this->assertEquals(count($dates), 16);
    }
}