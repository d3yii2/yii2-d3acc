<?php

namespace utest;

use d3acc\models\AcRecTable;
use PHPUnit\Framework\TestCase;
use yii;
use d3acc\models\AcAccount;
use d3acc\models\AcDef;
use d3acc\models\AcRecAcc;
use d3acc\models\AcTran;
use d3acc\models\AcPeriod;
use d3acc\components\PeriodMonth;
use d3acc\models\AcPeriodBalance;
use yii\db\Exception;

class AcAccountTest extends TestCase
{
    private const PERIOD_TYPE = 7;
    private const SYS_COMPANY_ID = 1;
    const ACC_1_REF = [
        'Test01' => 1,
        'Test02' => 2,
        'ac_rec_table' => 'BimBim'
    ];
    public $acc;
    public $accD;
    public $accW;
    public $accDef1;
    public $accDef2;
    public $currencyId;

    public function setUp(): void
    {
        $this->currencyId = 1;

        if ($accs = AcAccount::findAll(['code' => 'Test'])) {
            foreach ($accs as $acc) {
                $this->deleteAcc($acc);
            }
        }
        $this->deletePeriodType(self::PERIOD_TYPE);

        /** set acc component */
        Yii::$app->acc->userId = 5;
        Yii::$app->acc->periodType = self::PERIOD_TYPE;
        Yii::$app->acc->sysCompanyId = self::SYS_COMPANY_ID;

        $this->acc       = new AcAccount();
        $this->acc->code = 'Test';
        $this->acc->name = 'Name Test';
        $this->acc->save();

        $this->acDef1             = new AcDef();
        $this->acDef1->sys_company_id = self::SYS_COMPANY_ID;
        $this->acDef1->account_id = $this->acc->id;
        $this->acDef1->table      = 'Test01';
        $this->acDef1->pk_field   = 'id';
        $this->acDef1->use_in_label   = 1;
        $this->acDef1->save();

        $this->acDef2             = new AcDef();
        $this->acDef2->sys_company_id = self::SYS_COMPANY_ID;
        $this->acDef2->account_id = $this->acc->id;
        $this->acDef2->table      = 'Test02';
        $this->acDef2->pk_field   = 'id';
        $this->acDef1->use_in_label   = 1;
        $this->acDef2->save();

        $this->acDef2             = new AcDef();
        $this->acDef2->sys_company_id = self::SYS_COMPANY_ID;
        $this->acDef2->account_id = $this->acc->id;
        $this->acDef2->table      = AcRecTable::tableName();
        $this->acDef2->pk_field   = 'id';
        $this->acDef1->use_in_label   = 1;
        $this->acDef2->save();

        $this->accD       = new AcAccount();
        $this->accD->code = 'Test';
        $this->accD->name = 'Name Test 2';
        $this->accD->save();

        $acDef1             = new AcDef();
        $acDef1->account_id = $this->accD->id;
        $acDef1->table      = 'Test03';
        $acDef1->pk_field   = 'id';
        $this->acDef1->use_in_label   = 1;
        $acDef1->save();

        $this->accW       = new AcAccount();
        $this->accW->code = 'TestW';
        $this->accW->name = 'Name TestW';
        $this->acDef1->use_in_label   = 1;
        $this->accW->save();

        $acwDef1             = new AcDef();
        $acwDef1->account_id = $this->accW->id;
        $acwDef1->code      = 'A-Test0W';
        $acwDef1->table      = 'Test0W';
        $acwDef1->pk_field   = 'id';
        $this->acDef1->use_in_label   = 1;
        $acwDef1->save();

        $acwDef2             = new AcDef();
        $acwDef2->account_id = $this->accW->id;
        $acwDef2->code      = 'B-Test0W';
        $acwDef2->table      = 'Test0W';
        $acwDef2->pk_field   = 'id';
        $this->acDef1->use_in_label   = 1;
        $acwDef2->save();

        $period = new AcPeriod();
        $period->sys_company_id = self::SYS_COMPANY_ID;
        $period->period_type = self::PERIOD_TYPE;
        $period->status = AcPeriod::STATUS_CLOSED;
        $period->from = '2016-09-01';
        $period->to = '2016-09-30';
        $period->save();

        $period = new AcPeriod();
        $period->sys_company_id = self::SYS_COMPANY_ID;
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
            'sys_company_id' => self::SYS_COMPANY_ID
        ])->orderBy(['id'=> SORT_DESC])->all() as $period){
            $period->delete();
        }
    }

    public function testGetValidatedAccc()
    {
        $acc = AcAccount::getValidatedAcc($this->acc->id,
            self::ACC_1_REF);
        $this->assertEquals($acc->id, $this->acc->id);
    }

    public function testGetValidatedAcccW()
    {
        $acc = AcAccount::getValidatedAcc(
            $this->accW->id,
                [
                    'A-Test0W' => 1,
                    'B-Test0W' => 2
                ]
        );
        $this->assertEquals($acc->id, $this->accW->id);
    }

    /**
     * @throws Exception
     */
    public function testAcRecAccGetValidatedAccc()
    {
        /**
         * get accounts
         */
        $recAccDebit = AcRecAcc::getAcc(
            $this->acc->id,
            self::SYS_COMPANY_ID,
            self::ACC_1_REF,
            $this->currencyId

        );
        $this->assertEquals($recAccDebit->getAccount()->one()->id, $this->acc->id);

        $recAcc2 = AcRecAcc::getAcc(
            $this->acc->id,
            self::SYS_COMPANY_ID,
            self::ACC_1_REF,
            $this->currencyId
        );
        $this->assertEquals($recAccDebit->id, $recAcc2->id);

        $recAccCredit = AcRecAcc::getAcc(
            $this->accD->id,
            self::SYS_COMPANY_ID,
            ['Test03' => 22],
            $this->currencyId
        );
        $this->assertEquals($recAccCredit->getAccount()->one()->id, $this->accD->id);

        $recAccW = AcRecAcc::getAcc(
            $this->accW->id,
            self::SYS_COMPANY_ID,
            [
                'A-Test0W' => 1,
                'B-Test0W' => 2
            ],
            $this->currencyId
        );
        $this->assertEquals($recAccW->getAccount()->one()->id, $this->accW->id);


        /**
         * get periods
         */
        $period = AcPeriod::getActivePeriod(self::SYS_COMPANY_ID,self::PERIOD_TYPE, '2016-08-02');
        $this->assertTrue(!$period);

        $period = AcPeriod::getActivePeriod(self::SYS_COMPANY_ID,self::PERIOD_TYPE, '2016-10-02');
        $this->assertInstanceOf('\d3acc\models\AcPeriod', $period);

        /**
         * registre transaction
         */
        $amt = 100;
        $tran = Yii::$app->acc->regTran(
            $recAccDebit,
            $recAccCredit,
            $amt,
            '2016.10.11'
        );
        $this->assertInstanceOf('\d3acc\models\AcTran', $tran);

        $debitBalance = AcTran::accPeriodBalance($recAccDebit, $period);
        $this->assertEquals($amt,-$debitBalance);

        $creditBalance = AcTran::accPeriodBalance($recAccCredit, $period);
        $this->assertEquals($amt,$creditBalance);

        /**
         * validate filtering
         */
        $recAccList = AcRecAcc::filterAcc(
            $this->acc->id,
            self::SYS_COMPANY_ID,
            ['Test01' => 1]
        );
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


        $amt = 200;
        $tran = Yii::$app->acc->regTran(
            $recAccDebit,
            $recAccW,
            $amt,
            '2016.10.12'
        );

        $this->assertInstanceOf('\d3acc\models\AcTran', $tran);
        $debitBalance = AcTran::accPeriodBalance($recAccW, $period);
        $this->assertEquals($amt,$debitBalance);
    }
}
