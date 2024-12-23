<?php

namespace utest;

use yii;
use d3acc\models\AcAccount;
use d3acc\models\AcDef;
use d3acc\models\AcRecAcc;
use d3acc\models\AcTran;
use d3acc\models\AcPeriod;
use d3acc\components\PeriodMonth;
use d3acc\models\AcPeriodBalance;

class AcAccountTest extends \PHPUnit_Framework_TestCase
{
    const PERIOD_TYPE = 7;
    public $acc;
    public $accD;
    public $accDef1;
    public $accDef2;

    public function setUp(): void
    {

        if ($accs = AcAccount::findAll(['code' => 'Test'])) {
            foreach ($accs as $acc) {
                $this->deleteAcc($acc);
            }
        }
        $this->deletePeriodType(self::PERIOD_TYPE);

        $this->acc       = new AcAccount();
        $this->acc->code = 'Test';
        $this->acc->name = 'Name Test';
        $this->acc->save();

        $this->acDef1             = new AcDef();
        $this->acDef1->sys_company_id = 1;
        $this->acDef1->account_id = $this->acc->id;
        $this->acDef1->table      = 'Test01';
        $this->acDef1->pk_field   = 'id';
        $this->acDef1->save();

        $this->acDef2             = new AcDef();
        $this->acDef2->sys_company_id = 1;
        $this->acDef2->account_id = $this->acc->id;
        $this->acDef2->table      = 'Test02';
        $this->acDef2->pk_field   = 'id';
        $this->acDef2->save();


        $this->accD       = new AcAccount();
        $this->accD->code = 'Test';
        $this->accD->name = 'Name Test 2';
        $this->accD->save();

        $acDef1             = new AcDef();
        $acDef1->account_id = $this->accD->id;
        $acDef1->table      = 'Test03';
        $acDef1->pk_field   = 'id';
        $acDef1->save();

        $period = new AcPeriod();
        $period->sys_company_id = 1;
        $period->period_type = self::PERIOD_TYPE;
        $period->status = AcPeriod::STATUS_CLOSED;
        $period->from = '2016-09-01';
        $period->to = '2016-09-30';
        $period->save();

        $period = new AcPeriod();
        $period->sys_company_id = 1;
        $period->period_type = self::PERIOD_TYPE;
        $period->status = AcPeriod::STATUS_ACTIVE;
        $period->from = '2016-10-01';
        $period->to = '2016-10-31';
        $period->save();

        AcAccount::$allTableRows = [];

    }

    public function tearDown(): void
    {
        $this->deleteAcc($this->acc);
        $this->deleteAcc($this->accD);
        $this->deletePeriodType(self::PERIOD_TYPE);
    }

    public function deleteAcc($acc)
    {
        if (!$acc) {
            return;
        }
        foreach ($acc->getAcDefs()->all() as $defAcc) {
            foreach ($defAcc->getAcRecRefs()->all() as $acRef) {
                $acRef->delete();
            }

            $defAcc->delete();
        }
        foreach ($acc->getAcRecAccs()->all() as $recAcc) {
            foreach ($recAcc->getAcTrans()->all() as $tran) {
                $tran->delete();
            }
            foreach($recAcc->getAcPeriodBalances()->all() as $balance){
                $balance->delete();
            }
            $recAcc->delete();
        }

        $acc->delete();
    }

    public function deletePeriodType($type){
        foreach(AcPeriod::find()->where([
            'period_type' => self::PERIOD_TYPE,
            'sys_company_id' => 1
        ])->orderBy(['id'=> SORT_DESC])->all() as $period){
            $period->delete();
        }
    }

    public function testGetValidatedAccc()
    {
        $acc = AcAccount::getValidatedAcc($this->acc->id,
                ['Test01' => 1, 'Test02' => 2]);
        $this->assertEquals($acc->id, $this->acc->id);
    }

    public function testAcRecAccGetValidatedAccc()
    {
        /**
         * get accounts
         */
        $recAccDebit = AcRecAcc::getAcc($this->acc->id,
                ['Test01' => 1, 'Test02' => 2]);
        $this->assertEquals($recAccDebit->getAccount()->one()->id, $this->acc->id);

        $recAcc2 = AcRecAcc::getAcc($this->acc->id,
                ['Test01' => 1, 'Test02' => 2]);
        $this->assertEquals($recAccDebit->id, $recAcc2->id);

        $recAccCredit = AcRecAcc::getAcc($this->accD->id, ['Test03' => 22]);
        $this->assertEquals($recAccDebit->id, $recAcc2->id);

        /**
         * get periods
         */
        $period = AcPeriod::getActivePeriod(1,self::PERIOD_TYPE, '2016-09-02');
        $this->assertTrue(!$period);

        $period = AcPeriod::getActivePeriod(1,self::PERIOD_TYPE, '2016-10-02');
        $this->assertInstanceOf('\d3acc\models\AcPeriod', $period);

        /**
         * registre transaction
         */
        $amt = 100;
        $tran = Yii::$app->acc->regTran($recAccDebit, $recAccCredit, $amt, '2016.10.11');
        $this->assertInstanceOf('\d3acc\models\AcTran', $tran);

        $debitBalance = AcTran::accPeriodBalance($recAccDebit, $period);
        $this->assertEquals($amt,-$debitBalance);

        $creditBalance = AcTran::accPeriodBalance($recAccCredit, $period);
        $this->assertEquals($amt,$creditBalance);

        /**
         * validate filtering
         */
        $recAccList = AcRecAcc::filterAcc($this->acc->id,['Test01' => 1]);
        $this->assertEquals($recAccDebit->id,$recAccList[0]->id);

        $data = AcTran::accFilterAccPeriodBalanceByDays($recAccCredit,$recAccDebit,$period);
        $this->assertEquals($recAccDebit->id,$data[0]['rec_acc_id']);
        $data = AcTran::accFilterAccPeriodBalanceByDays($recAccCredit,[$recAccDebit],$period);
        $this->assertEquals($recAccDebit->id,$data[0]['rec_acc_id']);

        $data = AcTran::accFilterExtPeriodBalanceByDays($recAccList,$period);
        $this->assertEquals($recAccDebit->id,$data[0]['rec_acc_id']);

        $balance = AcTran::accBalanceFilter($this->acc->id, $period, ['Test01' => 1]);
        $this->assertEquals(-$amt,$balance);

        $balanceByDays = AcTran::accByDaysFilter($this->acc->id, $period, ['Test01' => 1]);
        
        $this->assertEquals(-$amt,$balanceByDays[0]['amount']);

        /**
         * close period
         */
        $newPeriod = PeriodMonth::close(self::PERIOD_TYPE,1);

        /**
         * check new period balance
         */
        $debitBalance = AcTran::accPeriodBalance($recAccDebit, $newPeriod);
        $this->assertEquals($amt,-$debitBalance);

        $creditBalance = AcTran::accPeriodBalance($recAccCredit, $newPeriod);
        $this->assertEquals($amt,$creditBalance);

        $balance = AcPeriodBalance::accPeriodBalance($recAccDebit,$newPeriod);
        $this->assertEquals($amt,-$balance);

        $data = AcPeriodBalance::accBalanceFilter($recAccDebit->account_id,$newPeriod, ['Test01' => 1]);
        $this->assertEquals($amt, -$data[0]['amount']);

    }
}