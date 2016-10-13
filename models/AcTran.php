<?php

namespace d3acc\models;

use Yii;
use d3acc\models\base\AcTran as BaseAcTran;
use yii\helpers\ArrayHelper;
use yii\db\Expression;

/**
 * This is the model class for table "ac_tran".
 */
class AcTran extends BaseAcTran
{

    public function behaviors()
    {
        return ArrayHelper::merge(
                parent::behaviors(),
                [
                # custom behaviors
                ]
        );
    }

    public function rules()
    {
        return ArrayHelper::merge(
                parent::rules(),
                [
                # custom validation rules
                ]
        );
    }

    /**
     * registre transaction
     * @param \d3acc\models\AcRecAcc $debitAcc
     * @param \d3acc\models\AcRecAcc $creditAcc
     * @param decimal $amt
     * @param date $date
     * @param int $periodType
     * @return \d3acc\models\AcTran
     * @throws \Exception
     */
    public static function registre(AcRecAcc $debitAcc, AcRecAcc $creditAcc,
                                    $amt, $date, $periodType)
    {
        if (!$debitAcc) {
            throw new \Exception('Undefined debit account');
        }
        if (!$creditAcc) {
            throw new \Exception('Undefined credit account');
        }
        if (!$amt) {
            throw new \Exception('Undefined amount');
        }
        if (!$date) {
            throw new \Exception('Undefined date');
        }
        if (!$periodType) {
            throw new \Exception('Undefined period type');
        }

        $period = AcPeriod::getActivePeriod($periodType, $date);

        $model                    = new AcTran();
        $model->period_id         = $period->id;
        $model->accounting_date   = $date;
        $model->debit_rec_acc_id  = $debitAcc->id;
        $model->credit_rec_acc_id = $creditAcc->id;
        $model->amount            = $amt;
        $model->t_user_id         = 7;
        $model->t_datetime        = new Expression('NOW()');
        if (!$model->save()) {
            throw new \Exception('Can not create transaction: '.json_encode($model->getErrors()));
        }

        return $model;
    }

    /**
     * get account balance for period
     * @param \d3acc\models\AcRecAcc $acc
     * @param \d3acc\models\AcPeriod $period
     * @return decimal
     */
    public static function accPeriodBalance(AcRecAcc $acc,AcPeriod $period)
    {
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand('
            SELECT
              IFNULL(SUM(
                CASE
                  :acc_id
                  WHEN debit_rec_acc_id
                  THEN - amount
                  ELSE amount
                END
              ),0) amount
            FROM
              ac_tran
            WHERE
            period_id = :period_id
            AND  (debit_rec_acc_id = :acc_id OR credit_rec_acc_id = :acc_id)
          ', [
              ':acc_id' => $acc->id,
              ':period_id' => $period->id,
              ]);

        return $command->queryScalar();
    }

    /**
     * get account balance for period grouped by days
     * @param \d3acc\models\AcRecAcc $acc
     * @param \d3acc\models\AcPeriod $period
     * @return decimal
     */
    public static function accPeriodBalanceByDays(AcRecAcc $acc,AcPeriod $period)
    {
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand('
            SELECT
              accounting_date `date`,
              IFNULL(SUM(
                CASE
                  :acc_id
                  WHEN debit_rec_acc_id
                  THEN - amount
                  ELSE amount
                END
              ),0) amount
            FROM
              ac_tran
            WHERE
                period_id = :period_id
                AND  (
                    debit_rec_acc_id = :acc_id
                    OR
                    credit_rec_acc_id = :acc_id
                    )
            GROUP BY
                accounting_date
            ORDER BY
                accounting_date
          ', [
              ':acc_id' => $acc->id,
              ':period_id' => $period->id,
              ]);

        return $command->queryAll();
    }
    /**
     * get account balance for period filtered by other account and grouped by days
     * @param \d3acc\models\AcRecAcc $acc
     * @param \d3acc\models\AcRecAcc $accFilter
     * @param \d3acc\models\AcPeriod $period
     * @return decimal
     */
    public static function accFilterAccPeriodBalanceByDays(AcRecAcc $acc,AcRecAcc $accFilter, AcPeriod $period)
    {
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand('
            SELECT
              accounting_date `date`,
              IFNULL(SUM(
                CASE
                  :acc_id
                  WHEN debit_rec_acc_id
                  THEN - amount
                  ELSE amount
                END
              ),0) amount
            FROM
              ac_tran
            WHERE
                period_id = :period_id
                AND  (
                    debit_rec_acc_id = :acc_id AND credit_rec_acc_id = :acc_filter_id
                    OR
                    credit_rec_acc_id = :acc_id AND debit_rec_acc_id = :acc_filter_id
                    )
            GROUP BY
                accounting_date
            ORDER BY
                accounting_date
          ', [
              ':acc_id' => $acc->id,
              ':acc_filter_id' => $accFilter->id,
              ':period_id' => $period->id,
              ]);

        return $command->queryAll();
    }

}