<?php

namespace d3acc\models;

use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Exception;

class AcAcountTranSearch extends AcTran
{

    public ?AcRecAcc $accRec = null;
    public ?AcPeriod $period = null;

    public ?string $acc_label = null;

    public function rules(): array
    {
        return array_merge(
            parent::rules(),
            [
                ['acc_label', 'string']
            ]
        );
    }

    public function attributeLabels(): array
    {
        return array_merge(
            parent::attributeLabels(),
            [
                'acc_label' => Yii::t('d3acc', 'Account')
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function search(): ActiveDataProvider
    {
        $query = $this->accPeriodTranQuery();
        $startBalance = $this->startBalance();
        $finishBalance = $this->finishBalance((clone $query));
        $finishBalance->amount += $startBalance->amount;
        $models = array_merge(
            [1 => $startBalance],
            (clone $query)->all(),
            [$finishBalance->id => $finishBalance]
        );
        return new ActiveDataProvider([
            'models' => $models,
            'pagination' => false,
        ]);
    }

    public function accPeriodTranQuery(): ActiveQuery
    {
        return self::find()
            ->select([
                'ac_tran.id',
                'ac_tran.accounting_date',
                'ac_tran.code',
                'ac_tran.notes',
                'ac_tran.ref_table',
                'ac_tran.ref_id',
                'ac_tran.t_datetime',
                'amount' => 'CASE
                    `ac_tran`.`debit_rec_acc_id`
                    WHEN ' . $this->accRec->id . '
                    THEN - `ac_tran`.`amount`
                    ELSE + `ac_tran`.`amount`
                  END',
                'acc_label' => 'CASE
                    `ac_tran`.`debit_rec_acc_id`
                    WHEN ' . $this->accRec->id . '
                    THEN `c`.`label` 
                    ELSE `d`.`label` 
                  END'

            ])
            ->innerJoin(
                'ac_rec_acc d',
                'ac_tran.debit_rec_acc_id = d.id'
            )
            ->innerJoin(
                'ac_rec_acc c',
                'ac_tran.credit_rec_acc_id = c.id'
            )
            ->where([
                'ac_tran.period_id' => $this->period->id,
                'ac_tran.sys_company_id' => $this->period->sys_company_id
            ])
            ->andWhere(
                'ac_tran.debit_rec_acc_id = :acc_id OR ac_tran.credit_rec_acc_id = :acc_id',
                [':acc_id' => $this->accRec->id]
            )
            ->orderBy(['ac_tran.t_datetime' => SORT_ASC]);
    }

    /**
     * @throws Exception
     */
    public function startBalance(): AcAcountTranSearch
    {
        $model = new self();
        $model->id = 1;
        $model->amount = AcPeriodBalance::accPeriodBalance($this->accRec, $this->period);
        $model->acc_label = Yii::t('d3acc', 'Start amount');
        return $model;
    }

    public function finishBalance($query): AcAcountTranSearch
    {
        $model = new self();
        $model->id = 999999999;
        $model->amount = $query->sum('amount');
        $model->acc_label = Yii::t('d3acc', 'Total:');
        return $model;

    }
}
