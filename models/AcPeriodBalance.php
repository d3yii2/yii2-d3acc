<?php

namespace d3acc\models;

use Yii;
use \d3acc\models\base\AcPeriodBalance as BaseAcPeriodBalance;
use yii\db\DataReader;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "ac_period_balance".
 */
class AcPeriodBalance extends BaseAcPeriodBalance
{

    /**
     * save period balance
     * @param AcPeriod $period
     * @return DataReader
     * @throws Exception
     */
    public static function savePeriodBalance(AcPeriod $period)
    {
        $connection = Yii::$app->getDb();
        $command    = $connection->createCommand('
            INSERT INTO ac_period_balance (
                sys_company_id,
                period_id, 
                rec_acc_id, 
                amount
            )
            SELECT
              :sysCompanyId,
              :period_id,
              rec_acc_id,
              SUM(amount) amount
            FROM
              (SELECT
                debit_rec_acc_id rec_acc_id,
                - IFNULL(SUM(amount), 0) amount
              FROM
                ac_tran
              WHERE period_id = :period_id
              GROUP BY debit_rec_acc_id
              UNION
              SELECT
                credit_rec_acc_id rec_acc_id,
                IFNULL(SUM(amount), 0) amount
              FROM
                ac_tran
              WHERE period_id = :period_id
              GROUP BY credit_rec_acc_id
              UNION 
              SELECT
                rec_acc_id,
                amount
              FROM
                ac_period_balance
              WHERE period_id = :prev_period_id
              ) a
            GROUP BY rec_acc_id
        ',
            [
            ':sysCompanyId' => $period->sys_company_id,
            ':period_id' => $period->id,
            ':prev_period_id' => $period->prev_period,
        ]);

        return $command->query();
    }

    /**
     * get account period start balance
     * @param AcRecAcc $acc
     * @param AcPeriod $period
     * @return int
     * @throws Exception
     */
    public static function accPeriodBalance(AcRecAcc $acc, AcPeriod $period)
    {
        $connection = Yii::$app->getDb();

        $command = $connection->createCommand('
            SELECT
               amount
            FROM
              ac_period_balance
            WHERE
                period_id = :period_id
                AND  rec_acc_id = :acc_id
          ',
            [
            ':acc_id' => $acc->id,
            ':period_id' => $period->prev_period,
        ]);


        if (!$amount = $command->queryScalar()) {
            return 0;
        }

        return $amount;
    }

    public static function accBalanceFilter($accountId, AcPeriod $period,
                                            $filter)
    {

        $select = $join   = [];
        foreach (AcAccount::findOne($accountId)->getAcDefs()->all() as $acDef) {

            $tableAsName = '`r'.$acDef->table.'`';

            $select[] = ','.$tableAsName.'.`pk_value` '.$acDef->table.'_pk_value';
            $joinSql   = 'INNER JOIN `ac_rec_ref` as '.$tableAsName.
                ' ON `ac_rec_acc`.`id` = '.$tableAsName.'.`rec_account_id`
                    AND '.$tableAsName.'.`def_id` = '.$acDef->id;

            if (isset($filter[$acDef->table])) {
                $joinSql .= ' AND '.$tableAsName.'.`pk_value` = '.$filter[$acDef->table];
            }

            $join[] = $joinSql;
        }

        $connection = Yii::$app->getDb();
        $command    = $connection->createCommand('
            SELECT
                ac_rec_acc.id,
                b.amount
                '.implode(PHP_EOL, $select).'
            FROM
              ac_period_balance b
              INNER JOIN ac_rec_acc
                ON ac_rec_acc.id = b.rec_acc_id
              '.implode(PHP_EOL, $join).'
            WHERE
                ac_rec_acc.account_id = :account_id
                AND b.period_id = :period_id
          ',
            [
            ':period_id' => $period->prev_period,
            ':account_id' => $accountId,
        ]);

        return $command->queryAll();
    }
}