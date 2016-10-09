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
}