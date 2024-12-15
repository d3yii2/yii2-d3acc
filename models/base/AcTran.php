<?php
// This class was automatically generated by a giiant build task
// You should not change it manually as it will be overwritten on next build

namespace d3acc\models\base;

use Yii;
use d3acc\models\AcPeriod;
use d3acc\models\AcRecAcc;
use d3acc\models\AcTranDim;
use d3system\behaviors\D3DateTimeBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the base-model class for table "ac_tran".
 *
 * @property integer $id
 * @property integer $sys_company_id
 * @property integer $period_id
 * @property string $accounting_date
 * @property integer $debit_rec_acc_id
 * @property integer $credit_rec_acc_id
 * @property float $amount
 * @property string $code
 * @property string $notes
 * @property integer $t_user_id
 * @property string $t_datetime
 * @property string $ref_table
 * @property integer $ref_id
 *
 * @property AcTranDim[] $acTranDims
 * @property AcRecAcc $creditRecAcc
 * @property AcRecAcc $debitRecAcc
 * @property AcPeriod $period
 * @property string $aliasModel
 */
abstract class AcTran extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return 'ac_tran';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            'required' => [['period_id', 'accounting_date', 'debit_rec_acc_id', 'credit_rec_acc_id', 'amount', 't_user_id', 't_datetime'], 'required'],
            'decimal-unsigned-10-2' => [
                ['amount'],
                    'number',
                    'numberPattern' => '/^(\+?((\d{1,8})|(\d{0,8}\.\d{0,2})|(\.\d{1,2})))$/',
                    'message' =>  Yii::t('crud', 'Invalid number format')
                ],
            'smallint Unsigned' => [['sys_company_id','period_id','t_user_id'],'integer' ,'min' => 0 ,'max' => 65535],
            'integer Unsigned' => [['id','debit_rec_acc_id','credit_rec_acc_id','ref_id'],'integer' ,'min' => 0 ,'max' => 4294967295],
            [['accounting_date', 't_datetime'], 'safe'],
            [['amount'], 'number'],
            [['notes'], 'string'],
            [['code'], 'string', 'max' => 20],
            [['ref_table'], 'string', 'max' => 256],
            [['accounting_date', 'debit_rec_acc_id', 'credit_rec_acc_id', 'amount', 't_datetime', 'ref_id'], 'unique', 'targetAttribute' => ['accounting_date', 'debit_rec_acc_id', 'credit_rec_acc_id', 'amount', 't_datetime', 'ref_id']],
            [['period_id'], 'exist', 'skipOnError' => true, 'targetClass' => AcPeriod::class, 'targetAttribute' => ['period_id' => 'id']],
            [['credit_rec_acc_id'], 'exist', 'skipOnError' => true, 'targetClass' => AcRecAcc::class, 'targetAttribute' => ['credit_rec_acc_id' => 'id']],
            [['debit_rec_acc_id'], 'exist', 'skipOnError' => true, 'targetClass' => AcRecAcc::class, 'targetAttribute' => ['debit_rec_acc_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('d3acc', 'ID'),
            'sys_company_id' => Yii::t('d3acc', 'Sys Company ID'),
            'period_id' => Yii::t('d3acc', 'Period'),
            'accounting_date' => Yii::t('d3acc', 'Accountig Date'),
            'debit_rec_acc_id' => Yii::t('d3acc', 'Debit account'),
            'credit_rec_acc_id' => Yii::t('d3acc', 'Credit account'),
            'amount' => Yii::t('d3acc', 'Amount'),
            'code' => Yii::t('d3acc', 'Code'),
            'notes' => Yii::t('d3acc', 'Notes'),
            't_user_id' => Yii::t('d3acc', 'User'),
            't_datetime' => Yii::t('d3acc', 'Date'),
            'ref_table' => Yii::t('d3acc', 'RefTable'),
            'ref_id' => Yii::t('d3acc', 'RefId'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints(): array
    {
        return array_merge(parent::attributeHints(), [
            'period_id' => Yii::t('d3acc', 'Period'),
            'accounting_date' => Yii::t('d3acc', 'Accountig Date'),
            'debit_rec_acc_id' => Yii::t('d3acc', 'Debit account'),
            'credit_rec_acc_id' => Yii::t('d3acc', 'Credit account'),
            'amount' => Yii::t('d3acc', 'Amount'),
            'code' => Yii::t('d3acc', 'Code'),
            'notes' => Yii::t('d3acc', 'Notes'),
            't_user_id' => Yii::t('d3acc', 'User'),
            't_datetime' => Yii::t('d3acc', 'Date'),
            'ref_table' => Yii::t('d3acc', 'RefTable'),
            'ref_id' => Yii::t('d3acc', 'RefId'),
        ]);
    }

    /**
     * @return ActiveQuery
     */
    public function getAcTranDims(): ActiveQuery
    {
        return $this
            ->hasMany(AcTranDim::class, ['tran_id' => 'id'])
            ->inverseOf('tran');
    }

    /**
     * @return ActiveQuery
     */
    public function getCreditRecAcc(): ActiveQuery
    {
        return $this
            ->hasOne(AcRecAcc::class, ['id' => 'credit_rec_acc_id'])
            ->inverseOf('acTrans');
    }

    /**
     * @return ActiveQuery
     */
    public function getDebitRecAcc(): ActiveQuery
    {
        return $this
            ->hasOne(AcRecAcc::class, ['id' => 'debit_rec_acc_id'])
            ->inverseOf('acTrans0');
    }

    /**
     * @return ActiveQuery
     */
    public function getPeriod(): ActiveQuery
    {
        return $this
            ->hasOne(AcPeriod::class, ['id' => 'period_id'])
            ->inverseOf('acTrans');
    }
}
