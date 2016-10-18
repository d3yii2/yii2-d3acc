<?php

namespace d3acc\models;

use Yii;
use \d3acc\models\base\AcPeriodBalance as BaseAcPeriodBalance;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "ac_period_balance".
 */
class AcPeriodBalance extends BaseAcPeriodBalance
{


    /**
     * save period balance
     * @param \d3acc\models\AcPeriod $period
     * @return type
     */
    public static function savePeriodBalance(AcPeriod $period)
    {
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand('
            INSERT INTO ac_period_balance (period_id, rec_acc_id, amount)
            SELECT
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
        ', [
              ':period_id' => $period->id,
              ':prev_period_id' => $period->prev_period,
              ]);

        return $command->query();
    }

    public static function accPeriodBalance(AcRecAcc $acc,AcPeriod $period)
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
          ', [
              ':acc_id' => $acc->id,
              ':period_id' => $period->prev_period,
              ]);


        if(!$amount = $command->queryScalar()){
            return 0;
        }

        return $amount;

    }

}
