<?php
/**
 * Search transactions
 *
 * @author Uldis Nelsons
 */

namespace d3acc\models;

use Yii;
use yii\db\Expression;
use yii\helpers\Json;

/**
 * This is the model class for table "ac_tran".
 */
class AcTranSearch extends AcTran
{

    public ?string $accountLabel = null;
    public ?string $dimNotes = null;
    public ?string $username = null;

    public ?int $filterAccountId = null;
    public ?int $filterPeriodId = null;
    public ?int $filterDimId = null;
    public ?string $filterCode = null;
    public ?bool $filterWithDim = null;
    public ?bool $filterAddStartBalance = null;
    public ?string $filterTitle = null;

    public string $action = 'acc-tran';
    public ?string $checksum = null;
    public ?int $userId = null;

    //?bool $addDimId = true,

    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'accountLabel', 'dimNotes', 'username', 'filterCode', 'filterTitle',
                'filterAccountId', 'filterPeriodId', 'filterDimId',
                'filterWithDim', 'filterAddStartBalance', 'checksum',
                'userId'
            ]
        );
    }

    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['required']);
        return array_merge(
            $rules,
            [
                [['userId', 'sys_company_id', 'checksum', 'filterPeriodId', 'filterTitle', 'filterAccountId'], 'required'],
                [['checksum', 'accountLabel', 'dimNotes', 'username', 'filterCode', 'filterTitle'], 'string'],
                [['filterAccountId', 'filterPeriodId', 'filterDimId', 'userId'], 'integer'],
                [['filterWithDim', 'filterAddStartBalance'], 'boolean'],
                ['checksum', 'validateChecksum']
            ]
        );
    }

    public function validateChecksum(): void
    {
        if ($this->checksum !== $this->getFilterChecksum($this->getFilter())) {
            $this->addError('checksum', 'Invalid');
        }
    }

    public function attributeLabels(): array
    {
        return array_merge(
            parent::attributeLabels(),
            [
                'accountLabel' => Yii::t('d3acc', 'Account'),
                'dimNotes' => Yii::t('d3acc', 'Dimension notes'),
                'username' => Yii::t('d3acc', 'User'),
                'filterAccountId' => Yii::t('d3acc', 'Filter account'),
                'filterPeriodId' => Yii::t('d3acc', 'Filter period'),
                'filterDimId' => Yii::t('d3acc', 'Filter dimension'),
                'filterCode' => Yii::t('d3acc', 'Filter code'),
                'filterWithDim' => Yii::t('d3acc', 'Filter with dimension'),
                'filterAddStartBalance' => Yii::t('d3acc', 'Filter add start balance'),
            ]
        );
    }

    private static function filterNames(): array
    {
        return [
            'filterAccountId',
            'filterPeriodId',
            'filterDimId',
            'filterCode',
            'filterWithDim',
            'filterAddStartBalance',
            'filterTitle'
        ];
    }

    /**
     * Get period transactions for account with start balance
     *
     */
    public function filter()
    {

        $query = self::find()
            ->select([
                'ac_tran.id tran_id',
                'ac_tran.accounting_date',
                'acc_label' => 'CASE
                    `ac_tran`.`debit_rec_acc_id`
                    WHEN :account_id
                    THEN `c`.`label` 
                    ELSE `d`.`label` 
                  END',
                'ac_tran.code',
                'ac_tran.notes',
                'ac_tran.t_datetime',
                'ac_tran.t_user_id',
                'ac_tran.ref_table',
                'ac_tran.ref_id'
            ])
            ->innerJoin(
                'ac_rec_acc AS d',
                '`ac_tran`.`debit_rec_acc_id` = `d`.`id`'
            )
            ->innerJoin(
                'ac_rec_acc AS c',
                '`ac_tran`.`credit_rec_acc_id` = `c`.`id`'
            )
            ->where([
                'ac_tran.period_id' => $this->filterPeriodId,
                'ac_tran.sys_company_id' => $this->sys_company_id,
            ])
            ->andWhere(
                '(d.account_id = :account_id OR c.account_id = :account_id)',
                [':account_id' => $this->filterAccountId]
            )
            ->andFilterWhere(['ac_tran.code' => $this->filterCode])
            ->orderBy(['ac_tran.t_datetime' => SORT_ASC]);

        if ($this->filterWithDim) {
            if ($this->filterDimId) {
                $query
                    ->addSelect([
                        'amount' => 'CASE `d`.`account_id`
                              WHEN :account_id
                              THEN - `ac_tran_dim`.`amt`
                              ELSE + `ac_tran_dim`.`amt`
                          END',
                        'dim_notes' => '`ac_tran_dim`.`notes`'
                    ])
                    ->innerJoin(
                        'ac_tran_dim',
                        '`ac_tran_dim`.`tran_id` = `ac_tran`.`id`'
                    )
                    ->andWhere(['ac_tran_dim.dim_id' => $this->filterDimId])
                    ->addParams([
                        ':account_id' => $this->filterAccountId
                    ]);
            } else {
                $query
                    ->addSelect([
                        'amount' => '
                          CASE `d`.`account_id`
                            WHEN :account_id
                            THEN - `ac_tran`.`amount`
                            ELSE + `ac_tran`.`amount`
                          END
                        ',
                        'dim_notes' => new Expression('NULL')
                    ])
                    ->leftJoin(
                        'ac_tran_dim',
                        '`ac_tran_dim`.`tran_id` = `ac_tran`.`id`'
                    )
                    ->andWhere([
                        '`ac_tran_dim`.`dim_id`' => $this->filterDimId,
                        '`ac_tran_dim`.`id`' => null
                    ])
                    ->addParams([
                        ':account_id' => $this->filterAccountId
                    ]);
            }
        } else {
            $query
                ->addSelect([
                    'amount' => '
                              CASE `d`.`account_id`
                                WHEN :account_id
                                THEN - `ac_tran`.`amount`
                                ELSE + `ac_tran`.`amount`
                              END
                            ',
                    'dim_notes' => new Expression('NULL')
                ]);
        }
        $query
            ->addSelect([
                'username' => '`user`.`username`'
            ])
            ->leftJoin(
                'user',
                '`user`.`id` = `ac_tran`.`t_user_id`'
            );
        $tran = $query->asArray()->all();
        if ($this->filterAddStartBalance) {
            $balanceRow = new self();
            $balanceRow->amount = AcPeriodBalance::accPeriodBalanceById($this->filterAccountId, $this->filterPeriodId);
            array_unshift($tran, $balanceRow);
        }
        return $tran;
    }

    public function createFilterUrl(): array
    {
        $url = $this->getFilter();
        $url['checksum'] = $this->getFilterChecksum($url);
        array_unshift($url, $this->action);
        return $url;
    }

    private function getFilterChecksum(array $url): string
    {
        foreach ($url as $name => $value) {
            $url[$name] = (string)$value;
        }
        return substr(
            md5(
                Json::encode($url) .
                $this->sys_company_id . '-' .
                $this->userId . '-' .
                Yii::$app->name .
                'fujaks'
            ),
            5,
            15
        );
    }

    /**
     * @return array
     */
    private function getFilter(): array
    {
        $filter = [];
        foreach (self::filterNames() as $name) {
            if ($this->$name !== null) {
                $filter[$name] = $this->$name;
            }
        }
        return $filter;
    }
}