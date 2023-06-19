<?php
/**
 * registre transactions and get data of transactions
 * 
 * @author Uldis Nelsons
 */

namespace d3acc\models;

use d3acc\components\AccQueries;
use d3system\exceptions\D3ActiveRecordException;
use Exception;
use Yii;
use d3acc\models\base\AcTran as BaseAcTran;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\db\Expression;

/**
 * This is the model class for table "ac_tran".
 */
class AcTran extends BaseAcTran
{


    /**
     * registre transaction
     * @param AcRecAcc $debitAcc
     * @param AcRecAcc $creditAcc
     * @param float $amt
     * @param string $date
     * @param AcPeriod $period
     * @param int $userId
     * @param string|Expression|bool $tranTime
     * @param string $code
     * @return AcTran
     * @throws D3ActiveRecordException
     * @throws Exception
     */
    public static function registre2(
        AcRecAcc $debitAcc,
        AcRecAcc $creditAcc,
        float $amt,
        string $date,
        AcPeriod $period,
        int $userId,
        string $tranTime = '',
        string $code = ''

    )
    {
        if (!$debitAcc) {
            throw new \yii\base\Exception('Undefined debit account');
        }
        if (!$creditAcc) {
            throw new \yii\base\Exception('Undefined credit account');
        }
        if (!$amt) {
            throw new \yii\base\Exception('Undefined amount');
        }
        if ($amt < 0) {
            throw new \yii\base\Exception('Ilegal transaction amount: ' . $amt);
        }
        if (!$date) {
            throw new \yii\base\Exception('Undefined date');
        }
        if (!$userId) {
            throw new \yii\base\Exception('undefined user');
        }

        if(!$tranTime ){
            $tranTime = new Expression('NOW()');
        }

        $model                    = new self();
        $model->sys_company_id = $period->sys_company_id;
        $model->period_id         = $period->id;
        $model->accounting_date   = $date;
        $model->debit_rec_acc_id  = $debitAcc->id;
        $model->credit_rec_acc_id = $creditAcc->id;
        $model->amount            = $amt;
        $model->t_user_id         = $userId;
        $model->t_datetime        = $tranTime;
        if ($code) {
            $model->code = $code;
        }
        if (!$model->save()) {
            throw new D3ActiveRecordException($model);
        }

        return $model;
    }

    /**
     * get rec account balance for period
     *
     * @param AcPeriod $period
     * @param bool $addPrevBalance
     * @param array $acRecAccIds
     * @return array
     */
    public static function periodBalance(
        AcPeriod $period,
        $addPrevBalance = true,
        array $acRecAccIds = []
    )
    {
        $addDebitWhere = '';
        $addCreditWhere = '';
        $addBalanceWhere = '';
        if ($acRecAccIds) {
            $addDebitWhere = ' AND debit_rec_acc_id IN (' . implode(',',$acRecAccIds) . ') ';
            $addCreditWhere = ' AND credit_rec_acc_id IN (' . implode(',',$acRecAccIds) . ') ';
            $addBalanceWhere = ' AND rec_acc_id IN (' . implode(',',$acRecAccIds) . ') ';
        }

        $unionPrevBalanceSql = '';
        if($addPrevBalance){
            $unionPrevBalanceSql = '
                UNION
                  SELECT
                    rec_acc_id,
                    amount
                  FROM
                    ac_period_balance
                  WHERE period_id = :prev_period_id
                  ' . $addBalanceWhere . '
                  ';
        }
        $connection = Yii::$app->getDb();
        $command    = $connection->createCommand('
                SELECT
                  rec_acc_id,
                  ra.label,
                  ra.account_id,
                  SUM(amount) amount
                FROM
                  (SELECT
                    debit_rec_acc_id rec_acc_id,
                    - IFNULL(SUM(amount), 0) amount
                  FROM
                    ac_tran
                  WHERE period_id = :period_id
                    AND ac_tran.sys_company_id = :sysCompanyId
                    ' . $addDebitWhere . '
                  GROUP BY debit_rec_acc_id
                  UNION
                  SELECT
                    credit_rec_acc_id rec_acc_id,
                    IFNULL(SUM(amount), 0) amount
                  FROM
                    ac_tran
                  WHERE period_id = :period_id
                    AND ac_tran.sys_company_id = :sysCompanyId
                    ' . $addCreditWhere . '
                  GROUP BY credit_rec_acc_id
                  '.$unionPrevBalanceSql.'
                  ) a
                  INNER JOIN ac_rec_acc ra
                    ON a.rec_acc_id = ra.id
                GROUP BY rec_acc_id
                order by ra.label
          ',
            [
            ':period_id' => $period->id,
            ':prev_period_id' => $period->prev_period,
            ':sysCompanyId' => $period->sys_company_id
        ]);

        return $command->queryAll();
    }


    /**
     * get account balance for period
     *
     * @param AcPeriod $period
     * @param array $acRecAccIds - use for filtering
     * @param bool $addToSelectTranIds add column transIds with all ac_tran.id separated by comma
     * @return array {
     *     rec_acc_id: string,
     *     label: string,
     *     account_id: string,
     *     amount: string,
     *     total_amount: float,
     *     tranIds: CSV
     * }
     */
    public static function periodBalanceTotal(
        AcPeriod $period,
        array $acRecAccIds = [],
        bool $addToSelectTranIds = false
    ): array
    {
        $connection = Yii::$app->getDb();
        $addDebitWhere = '';
        $addCreditWhere = '';
        $addBalanceWhere = '';
        if ($acRecAccIds) {
            $addDebitWhere = ' AND debit_rec_acc_id IN (' . implode(',',$acRecAccIds) . ') ';
            $addCreditWhere = ' AND credit_rec_acc_id IN (' . implode(',',$acRecAccIds) . ') ';
            $addBalanceWhere = ' AND b.rec_acc_id IN (' . implode(',',$acRecAccIds) . ') ';
        }
        $selectDebitCreditTranIds = '';
        $mainSelectIds = '';
        if ($addToSelectTranIds) {
            $selectDebitCreditTranIds = ',GROUP_CONCAT(ac_tran.id SEPARATOR ",") AS tranIds';
            $mainSelectIds = ',GROUP_CONCAT(tranIds SEPARATOR ",") AS tranIds';
        }

        $command    = $connection->createCommand('
                SELECT
                  rec_acc_id,
                  account.name label,
                  ra.account_id,
                  SUM(amount) amount,
                  0 total_amount
                  ' . $mainSelectIds . '
                FROM
                  (SELECT
                    debit_rec_acc_id rec_acc_id,
                    - IFNULL(SUM(amount), 0) amount
                    '.$selectDebitCreditTranIds.'
                  FROM
                    ac_tran
                  WHERE period_id = :period_id
                    AND ac_tran.sys_company_id = :sysCompanyId
                    '.$addDebitWhere.'
                  GROUP BY debit_rec_acc_id
                  UNION
                  SELECT
                    credit_rec_acc_id rec_acc_id,
                    IFNULL(SUM(amount), 0) amount
                    '.$selectDebitCreditTranIds.'                    
                  FROM
                    ac_tran
                  WHERE period_id = :period_id
                    AND ac_tran.sys_company_id = :sysCompanyId
                    '.$addCreditWhere.'
                  GROUP BY credit_rec_acc_id
                    ) a
                  INNER JOIN ac_rec_acc ra
                    ON a.rec_acc_id = ra.id
                  INNER JOIN ac_account account
                    ON ra.account_id = account.id
                GROUP BY ra.account_id
                order by ra.label
          ',
            [
            ':period_id' => $period->id,
            ':sysCompanyId' => $period->sys_company_id
        ]);

        $tranData = ArrayHelper::index(
            $command->queryAll(),
            'account_id'
        );

        $command    = $connection->createCommand('
            SELECT
              rec_acc_id,
              account.name label,
              ra.account_id,
              SUM(amount) amount
            FROM
              ac_period_balance b
              INNER JOIN ac_rec_acc ra
                ON b.rec_acc_id = ra.id
              INNER JOIN ac_account account
                ON ra.account_id = account.id
            WHERE 
              b.period_id = :prev_period_id
              '.$addBalanceWhere.'
            GROUP BY ra.account_id
            ORDER BY ra.label
          ',
            [
            ':prev_period_id' => $period->prev_period,
        ]);

        $balanceData = ArrayHelper::index(
            $command->queryAll(),
            'account_id'
        );

        foreach($tranData as $accId => $dRow){
            $prevBalance = 0;
            if(isset($balanceData[$accId])){
                $prevBalance = $balanceData[$accId]['amount'];
            }
            $tranData[$accId]['total_amount'] = $dRow['amount']
                + $prevBalance;
        }
        foreach($balanceData as $accId => $bRow){
            if(!isset($tranData[$accId])){
                $tranData[$accId] = $bRow;
                $tranData[$accId]['total_amount'] = $bRow['amount'];
                $tranData[$accId]['amount'] = 0;
            }
        }
        return $tranData;
    }


    /**
     * get account balance wirh dim for period
     *
     * @param AcPeriod $period
     * @param int[] $accList
     * @param int $accDimGroupId
     * @return array
     * @throws \yii\db\Exception
     */
    public static function periodBalanceDimTotal(AcPeriod $period, array $accList,int $accDimGroupId): array
    {
        $connection = Yii::$app->getDb();

        $command    = $connection->createCommand('
                SELECT
                  rec_acc_id,
                  concat(account.name , \' \',IFNULL(acd.name,\'\')) label,
                  concat(ra.account_id,\'-\',IFNULL(a.dim_id,0)) account_id,
                  SUM(amount) amount,
                  0 total_amount
                FROM
                  (SELECT
                    debit_rec_acc_id rec_acc_id,
                    td.dim_id,
                    - IFNULL(SUM(amount), 0) amount
                  FROM
                    ac_tran
                  LEFT OUTER JOIN ac_tran_dim td 
                    ON ac_tran.id = td.tran_id                         
                  WHERE 
                    period_id = :period_id
                    AND ac_tran.sys_company_id = :sysCompanyId
                  GROUP BY 
                    debit_rec_acc_id,
                    td.dim_id
                  UNION
                  SELECT
                    credit_rec_acc_id rec_acc_id,
                    td.dim_id,
                    IFNULL(SUM(amount), 0) amount
                  FROM
                    ac_tran
                    LEFT OUTER JOIN ac_tran_dim td 
                        ON ac_tran.id = td.tran_id                       
                  WHERE 
                    period_id = :period_id
                    AND ac_tran.sys_company_id = :sysCompanyId
                  GROUP BY 
                    credit_rec_acc_id,
                    td.dim_id
                    ) a
                  INNER JOIN ac_rec_acc ra
                    ON a.rec_acc_id = ra.id
                  INNER JOIN ac_account account
                    ON ra.account_id = account.id
                  LEFT OUTER JOIN ac_dim AS acd 
                    ON acd.id = a.dim_id 
                  LEFT OUTER JOIN ac_dim_group AS acdg 
                    ON acd.group_id = acdg.id 
                    AND acdg.id = :ac_dim_group_id                    
                WHERE 
                  ra.account_id in ('.implode(',',$accList).') 
                GROUP BY 
                  ra.account_id,
                  a.dim_id
                ORDER BY 
                  ra.label
          ',[
            ':period_id' => $period->id,
            ':ac_dim_group_id' => $accDimGroupId,
            ':sysCompanyId' => $period->sys_company_id
        ]);
        $tranData = $command->queryAll();
        return ArrayHelper::index($tranData, 'account_id');
    }

    /**
     * get account balance for period
     *
     * @param AcPeriod $period
     * @param int $accountId
     * @return array
     * @throws \yii\db\Exception
     */
    public static function periodBalanceByCodeTotal(AcPeriod $period,int $accountId): array
    {
        $connection = Yii::$app->getDb();

        $command    = $connection->createCommand('
                SELECT
                  rec_acc_id,
                  account.name label,
                  ra.account_id,
                  a.code,
                  SUM(amount) amount,
                  0 total_amount
                FROM
                  (SELECT
                    debit_rec_acc_id rec_acc_id,
                    code,
                    - IFNULL(SUM(amount), 0) amount
                  FROM
                    ac_tran
                  WHERE period_id = :period_id
                    AND ac_tran.sys_company_id = :sysCompanyId
                  GROUP BY
                    debit_rec_acc_id,
                    code
                  UNION
                  SELECT
                    credit_rec_acc_id rec_acc_id,
                    code,
                    IFNULL(SUM(amount), 0) amount
                  FROM
                    ac_tran
                  WHERE period_id = :period_id
                    AND ac_tran.sys_company_id = :sysCompanyId
                  GROUP BY
                    credit_rec_acc_id,
                    code
                    ) a
                  INNER JOIN ac_rec_acc ra
                    ON a.rec_acc_id = ra.id
                  INNER JOIN ac_account account
                    ON ra.account_id = account.id
                WHERE
                    ra.account_id = :account_id
                GROUP BY 
                    ra.account_id,
                    a.code
                order by ra.label
          ',
            [
            ':period_id' => $period->id,
            ':account_id' => $accountId,
            ':sysCompanyId' => $period->sys_company_id
        ]);
        return  $command->queryAll();
    }

    /**
     * get account balance for period
     *
     * @param AcPeriod $period
     * @param int $accountId
     * @return array
     * @throws \yii\db\Exception
     */
    public static function periodBalanceWithDimByCodeTotal(AcPeriod $period,int $accountId): array
    {
        $connection = Yii::$app->getDb();

        $command    = $connection->createCommand('
                SELECT
                  rec_acc_id,
                  account.name label,
                  ra.account_id,
                  a.code,
                  ac_dim.name dimName,
                  SUM(amount) amount,
                  0 total_amount
                FROM
                  (SELECT
                    debit_rec_acc_id rec_acc_id,
                    code,
                    dim.dim_id,
                    - IFNULL(SUM(IFNULL(dim.amt,amount)), 0) amount
                  FROM
                    ac_tran
                    LEFT OUTER JOIN ac_tran_dim dim
                      ON ac_tran.id = dim.tran_id
                  WHERE period_id = :period_id
                    AND ac_tran.sys_company_id = :sysCompanyId
                  GROUP BY
                    debit_rec_acc_id,
                    dim.dim_id,
                    code
                  UNION
                  SELECT
                    credit_rec_acc_id rec_acc_id,
                    code,
                    dim.dim_id,
                    IFNULL(SUM(IFNULL(dim.amt,amount)), 0) amount
                  FROM
                    ac_tran
                    LEFT OUTER JOIN ac_tran_dim dim
                      ON ac_tran.id = dim.tran_id                    
                  WHERE period_id = :period_id
                    AND ac_tran.sys_company_id = :sysCompanyId
                  GROUP BY
                    credit_rec_acc_id,
                    dim.dim_id,
                    code
                    ) a
                  INNER JOIN ac_rec_acc ra
                    ON a.rec_acc_id = ra.id
                  INNER JOIN ac_account account
                    ON ra.account_id = account.id
                  LEFT OUTER JOIN ac_dim 
                    ON a.dim_id = ac_dim.id  
                WHERE
                    ra.account_id = :account_id
                GROUP BY 
                    ra.account_id,
                    a.code,
                    a.dim_id
                order by ra.label
          ',
            [
            ':period_id' => $period->id,
            ':account_id' => $accountId,
            ':sysCompanyId' => $period->sys_company_id
        ]);
        return  $command->queryAll();
    }


    /**
     * get account balance for period
     *
     * @param AcPeriod $period
     * @return array
     * @throws \yii\db\Exception
     */
    public static function periodBalanceTotal1x(AcPeriod $period)
    {

        $connection = Yii::$app->getDb();
        $command    = $connection->createCommand('
                SELECT
                  rec_acc_id,
                  account.name label,
                  ra.account_id,
                  SUM(amount) amount,
                  SUM(prev_balance) + SUM(amount) total_amount
                FROM
                  (SELECT
                    debit_rec_acc_id rec_acc_id,
                    - IFNULL(SUM(amount), 0) amount,
                    0 prev_balance
                  FROM
                    ac_tran
                  WHERE period_id = :period_id
                    AND ac_tran.sys_company_id = :sysCompanyId
                  GROUP BY debit_rec_acc_id
                  UNION
                  SELECT
                    credit_rec_acc_id rec_acc_id,
                    IFNULL(SUM(amount), 0) amount,
                    0 prev_balance
                  FROM
                    ac_tran
                  WHERE period_id = :period_id
                    AND ac_tran.sys_company_id = :sysCompanyId
                UNION
                select 
                    credit_rec_acc_id rec_acc_id,
                    0 amount,
                    amount prev_balance
                from
                (
                  SELECT
                    rec_acc_id credit_rec_acc_id,
                    amount
                  FROM
                    ac_period_balance
                  WHERE period_id = :prev_period_id
                ) prevb
                  GROUP BY credit_rec_acc_id
                    ) a
                  INNER JOIN ac_rec_acc ra
                    ON a.rec_acc_id = ra.id
                  INNER JOIN ac_account account
                    ON ra.account_id = account.id
                GROUP BY ra.account_id
                order by ra.label
          ',
            [
            ':period_id' => $period->id,
            ':prev_period_id' => $period->prev_period,
            ':sysCompanyId' => $period->sys_company_id
        ]);

        return $command->queryAll();
    }


    /**
     * get account balance for period
     *
     * @param AcRecAcc $acc
     * @param AcPeriod $period
     * @param bool $addPrevBalance
     * @return false|int|string|null
     * @throws \yii\db\Exception
     */
    public static function accPeriodBalance(AcRecAcc $acc, AcPeriod $period,
                                            $addPrevBalance = true)
    {
        $connection = Yii::$app->getDb();
        $command    = $connection->createCommand('
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
              AND ac_tran.sys_company_id = :sysCompanyId
              AND  (debit_rec_acc_id = :acc_id OR credit_rec_acc_id = :acc_id)
          ',
            [
            ':acc_id' => $acc->id,
            ':period_id' => $period->id,
            ':sysCompanyId' => $period->sys_company_id
        ]);

        $actualBalance = $command->queryScalar();

        if ($addPrevBalance) {
            $actualBalance += AcPeriodBalance::accPeriodBalance($acc, $period);
        }

        return $actualBalance;
    }


    /**
     * get account balance for period grouped by CODE
     *
     * @param $accountId
     * @param AcPeriod $period
     * @return array
     * @throws \yii\db\Exception
     */
    public static function accPeriodBalanceGroupedByCode($accountId, AcPeriod $period)
    {
        $connection = Yii::$app->getDb();
        $command    = $connection->createCommand('
            SELECT
              IFNULL(SUM(
                CASE
                  racc.id 
                  WHEN t.debit_rec_acc_id
                  THEN - t.amount
                  ELSE t.amount
                END
              ),0) amount,
              code label,
              0 account_id
            FROM
              ac_tran t
            INNER JOIN ac_rec_acc racc
              ON (t.debit_rec_acc_id = racc.id OR t.credit_rec_acc_id = racc.id)
                AND racc.account_id = :account_id
            WHERE
                t.period_id = :period_id
                AND t.sys_company_id = :sysCompanyId
            GROUP BY t.code
          ',
            [
            ':account_id' => $accountId,
            ':period_id' => $period->id,
            ':sysCompanyId' => $period->sys_company_id
        ]);

        return $command->queryAll();
    }

    /**
     * Get period transactions for account fith start balance
     *
     * @param AcRecAcc $acc
     * @param AcPeriod $period
     * @param bool $startBalance
     * @return array [accounting_date,+/-amount, acc_label, code,notes,ref_table, ref_id]
     * @throws \yii\db\Exception
     */
    public static function accPeriodTran(AcRecAcc $acc, AcPeriod $period, $startBalance = true)
    {
        $connection = Yii::$app->getDb();
        $command    = $connection->createCommand('
            SELECT
              ac_tran.id tran_id,
              accounting_date,
              CASE
                ac_tran.debit_rec_acc_id
                WHEN :acc_id
                THEN - amount
                ELSE + amount
              END amount,
              CASE
                ac_tran.debit_rec_acc_id
                WHEN :acc_id
                THEN c.label 
                ELSE d.label 
              END acc_label,
              code,
              notes,
              ref_table,
              ref_id
            FROM
              ac_tran
              INNER JOIN ac_rec_acc d
                ON ac_tran.debit_rec_acc_id = d.id
              INNER JOIN ac_rec_acc c
                ON ac_tran.credit_rec_acc_id = c.id
            WHERE
                period_id = :period_id
                AND  (debit_rec_acc_id = :acc_id OR credit_rec_acc_id = :acc_id)
                AND ac_tran.sys_company_id = :sysCompanyId
            ORDER BY t_datetime
          ',
            [
            ':acc_id' => $acc->id,
            ':period_id' => $period->id,
            ':sysCompanyId' => $period->sys_company_id
        ]);

        $tran        = $command->queryAll();
        if($startBalance){
            $startRecord = [
                'amount' => AcPeriodBalance::accPeriodBalance($acc, $period),
                'acc_label' => 'Start amount',
                'accounting_date' => '',
                'code' => '',
                'notes' => '',
                'ref_table' => '',
                'ref_id' => '',
            ];
            $tran           = array_merge([$startRecord], $tran);
        }
        return $tran;
    }


    /**
     * get account balance for period grouped by days
     *
     * @param AcRecAcc $acc
     * @param AcPeriod $period
     * @param bool $addPrevToFirstDay
     * @return array
     * @throws \yii\db\Exception
     * @throws Exception
     */
    public static function accPeriodBalanceByDays(AcRecAcc $acc, AcPeriod $period, $addPrevToFirstDay = true)
    {
        $connection = Yii::$app->getDb();
        $command    = $connection->createCommand('
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
                AND ac_tran.sys_company_id = :sysCompanyId
                AND  (
                    debit_rec_acc_id = :acc_id
                    OR
                    credit_rec_acc_id = :acc_id
                    )
            GROUP BY
                accounting_date
            ORDER BY
                accounting_date
          ',
            [
            ':acc_id' => $acc->id,
            ':period_id' => $period->id,
            ':sysCompanyId' => $period->sys_company_id
        ]);

        $days = $command->queryAll();

        if ($addPrevToFirstDay) {
            $periodDays = $period->getDates();
            if(!$days || $days[0]['date'] !== $periodDays[0]){
                array_unshift($days,['date' => $periodDays[0], 'amount' => 0]);
            }
            $days[0]['amount'] += AcPeriodBalance::accPeriodBalance($acc,
                    $period);
        }

        return $days;
    }

    /**
     * get account balance for period filtered by other account and grouped by days
     * @param AcRecAcc $acc
     * @param AcRecAcc|array $accFilter
     * @param AcPeriod $period
     * @return mixed
     */
    public static function accFilterAccPeriodBalanceByDays(AcRecAcc $acc,
                                                           $accFilter,
                                                           AcPeriod $period)
    {
        if (!is_array($accFilter)) {
            $accFilter = [$accFilter];
        }
        $accFilterIdList = ArrayHelper::getColumn($accFilter, 'id');
        $accFilterCsv    = implode(',', $accFilterIdList);
        $connection      = Yii::$app->getDb();
        $command         = $connection->createCommand('
            SELECT
              accounting_date `date`,
              IFNULL(SUM(
                CASE
                  :acc_id
                  WHEN debit_rec_acc_id
                  THEN - amount
                  ELSE amount
                END
              ),0) amount,
              CASE
                  :acc_id
                  WHEN debit_rec_acc_id
                  THEN credit_rec_acc_id
                  ELSE debit_rec_acc_id
                END rec_acc_id
            FROM
              ac_tran
            WHERE
                period_id = :period_id
                AND ac_tran.sys_company_id = :sysCompanyId
                AND  (
                    debit_rec_acc_id = :acc_id AND credit_rec_acc_id in ('.$accFilterCsv.')
                    OR
                    credit_rec_acc_id = :acc_id AND debit_rec_acc_id in ('.$accFilterCsv.')
                    )
            GROUP BY
                accounting_date
            ORDER BY
                accounting_date
          ',
            [
            ':acc_id' => $acc->id,
            ':period_id' => $period->id,
            ':sysCompanyId' => $period->sys_company_id
        ]);

        return $command->queryAll();
    }

    /**
     * for account list get total balance by days for period
     * 
     * @param array $accList array of \d3acc\models\AcRecAcc elements
     * @param AcPeriod $period
     * @return array
     * @throws Exception
     */
    public static function accFilterExtPeriodBalanceByDays(
        array $accList,
        AcPeriod $period
    ) {

        if (!$accList) {
            return [];
        }

        /**
         * get common account_id
         */
        $accId = false;
        foreach ($accList as $acc) {

            if ($accId && $accId !== $acc->account_id) {
                throw new \yii\base\Exception('In Acc list mixed different accounts');
            }
            $accId = $acc->account_id;
        }

        $accIdList = ArrayHelper::getColumn($accList, 'id');

        $accdIdInList = "'".implode("','", $accIdList)."'";
        $query = AccQueries::joinRefs(
            $accId,
            $period->sys_company_id,
            [],
            true,
            true
        );

        return $query
            ->addSelect([
                'date' => 'ac_tran.accounting_date',
                'amount' => '
                    IFNULL(SUM(
                        CASE
                          WHEN credit_rec_acc_id in ('.$accdIdInList.')
                          THEN amount
                          ELSE - amount
                        END
                      ),
                      0
                   )                
                ',
                'rec_acc_id' => '
                    CASE
                      WHEN credit_rec_acc_id in ('.$accdIdInList.')
                      THEN credit_rec_acc_id
                      ELSE debit_rec_acc_id
                    END                
                '
            ])
            ->innerJoin(
                'ac_tran',
                'ac_rec_acc.id = CASE
                        WHEN credit_rec_acc_id in ('.$accdIdInList.')
                        THEN credit_rec_acc_id
                        ELSE debit_rec_acc_id
                     END'
            )
            ->andWhere([
                'ac_tran.period_id' => $period->id,
                'ac_tran.sys_company_id' => $period->sys_company_id
            ])
            ->andWhere('
                    ac_tran.credit_rec_acc_id in ('.$accdIdInList.')
                    OR
                    ac_tran.debit_rec_acc_id in ('.$accdIdInList.')
            ')
            ->addGroupBy('ac_tran.accounting_date')
            ->asArray()
            ->all()
        ;
//        $connection = Yii::$app->getDb();
//        $command    = $connection->createCommand('
//            SELECT
//              accounting_date `date`,
//              IFNULL(SUM(
//                CASE
//                  WHEN credit_rec_acc_id in ('.$accdIdInList.')
//                  THEN amount
//                  ELSE - amount
//                END
//              ),0) amount,
//              CASE
//                  WHEN credit_rec_acc_id in ('.$accdIdInList.')
//                  THEN credit_rec_acc_id
//                  ELSE debit_rec_acc_id
//                END rec_acc_id
//              '.implode(PHP_EOL, $select).'
//            FROM
//              ac_tran
//              INNER JOIN ac_rec_acc
//                ON ac_rec_acc.id = CASE
//                                    WHEN credit_rec_acc_id in ('.$accdIdInList.')
//                                    THEN credit_rec_acc_id
//                                    ELSE debit_rec_acc_id
//                                   END
//              '.implode(PHP_EOL, $join).'
//            WHERE
//                period_id = :period_id
//                AND ac_tran.sys_company_id = :sysCompanyId
//                AND  (
//                    credit_rec_acc_id in ('.$accdIdInList.')
//                    OR
//                    debit_rec_acc_id in ('.$accdIdInList.')
//                    )
//            GROUP BY
//                accounting_date
//                '.implode(PHP_EOL, $broupBy).'
//            ORDER BY
//                accounting_date
//          ',
//            [
//                ':period_id' => $period->id,
//                ':sysCompanyId' => $period->sys_company_id
//            ]);
//
//        return $command->queryAll();
    }

    /**
     * Balance account filtered by table values
     *
     * @param int $accountId
     * @param AcPeriod $period
     * @param array $filter
     * @return int
     * @throws \yii\base\Exception
     * @deprecated  netiekizmantota
     */
    public static function accBalanceFilterOld(
        int $accountId,
        AcPeriod $period,
        array $filter,
        bool $addPrevPalance = false
    ) {

//        $join = [];
//        foreach (AcAccount::findOne($accountId)->getAcDefs()->all() as $acDef) {
//
//            /**
//             */
//            if (!isset($filter[$acDef->table])) {
//                continue;
//            }
//
//            $tableAsName = '`r'.$acDef->table.'`';
//            $join[]      = 'INNER JOIN `ac_rec_ref` as '.$tableAsName.
//                ' ON `ac_rec_acc`.`id` = '.$tableAsName.'.`rec_account_id`
//                    AND '.$tableAsName.'.`def_id` = '.$acDef->id
//                .' AND '.$tableAsName.'.`pk_value` = '.$filter[$acDef->table];
//        }
        $query = AccQueries::joinRefs(
            $accountId,
            $period->sys_company_id,
            $filter
        );
        if($addPrevPalance){
            $query->select('
                IFNULL(SUM(
                    CASE ac_rec_acc.id
                      WHEN credit_rec_acc_id
                      THEN ac_tran.amount
                      ELSE - ac_tran.amount
                    END
                  ),0)
                  +
                  IFNULL(SUM(IFNULL(b.amount,0)),0)
          ');
        } else {
            $query->select('   
                IFNULL(SUM(
                    CASE ac_rec_acc.id
                      WHEN credit_rec_acc_id
                      THEN ac_tran.amount
                      ELSE - ac_tran.amount
                    END
                  ),0) amount
          ');
        }
        return $query
            ->innerJoin(
                'ac_tran',
                'ac_rec_acc.id in (credit_rec_acc_id,debit_rec_acc_id)'
            )
            ->leftJoin(
                'ac_period_balance b',
                'ac_rec_acc.id = b.rec_acc_id
                  AND b.period_id = :prev_period_id',
                [
                    ':prev_period_id' => $period->prev_period
                ]

            )
            ->andWhere([
                'ac_rec_acc.account_id' => $accountId,
                'ac_tran.period_id' => $period->id,
                'ac_tran.sys_company_id' => $period->sys_company_id

            ])
            ->scalar();
//        $connection = Yii::$app->getDb();
//        $command    = $connection->createCommand('
//            SELECT
//              ' . $selectSql . '
//            FROM
//              ac_tran
//              INNER JOIN ac_rec_acc
//                ON ac_rec_acc.id in (credit_rec_acc_id,debit_rec_acc_id)
//              '.implode(PHP_EOL, $join).'
//              LEFT OUTER JOIN ac_period_balance b
//                ON ac_rec_acc.id = b.rec_acc_id
//                  AND b.period_id = :prev_period_id
//            WHERE
//                ac_rec_acc.account_id = :account_id
//                AND ac_tran.period_id = :period_id
//                AND ac_tran.sys_company_id = :sysCompanyId
//
//          ',
//          [
//            ':period_id' => $period->id,
//            ':prev_period_id' => $period->prev_period,
//            ':account_id' => $accountId,
//            ':sysCompanyId' => $period->sys_company_id
//        ]);
//
//        return $command->queryScalar();
    }

    /**
     * Balance account filtered by table values
     *
     * @param int $accountId
     * @param AcPeriod $period
     * @param array $filter
     * @param bool $addPrevBalance
     * @return float
     * @throws \yii\db\Exception
     * @throws \yii\base\Exception
     */
    public static function accBalanceFilter(
        int $accountId,
        AcPeriod $period,
        array $filter,
        bool $addPrevBalance = false
    ): float
    {
        $query = AccQueries::joinRefs(
            $accountId,
            $period->sys_company_id,
            $filter
        );
        $queryCredit = clone $query;
        $queryCredit
            ->select(['amount' => 'IFNULL(SUM(ac_tran.amount),0)'])
            ->innerJoin(
                'ac_tran',
                'ac_rec_acc.id = ac_tran.credit_rec_acc_id'
            )
            ->andWhere([
                'ac_rec_acc.account_id' => $accountId,
                'ac_tran.period_id' => $period->id,
                'ac_tran.sys_company_id' => $period->sys_company_id
            ]);
        $queryDebit = clone $query;
        $queryDebit
            ->select(['amount' => '-IFNULL(SUM(ac_tran.amount),0)'])
            ->innerJoin(
                'ac_tran',
                'ac_rec_acc.id = ac_tran.debit_rec_acc_id'
            )
            ->andWhere([
                'ac_rec_acc.account_id' => $accountId,
                'ac_tran.period_id' => $period->id,
                'ac_tran.sys_company_id' => $period->sys_company_id
            ]);
        $balance = (new Query())
            ->select('SUM(amount)')
            ->from([
                'main' => $queryCredit->union($queryDebit)
            ])
            ->scalar();

        if($addPrevBalance){
            $queryBalance = clone $query;
            $balance += $queryBalance
                ->select(['bslsnce' =>'IFNULL(SUM(IFNULL(b.amount,0)),0)'])
                ->innerJoin(
                    'ac_period_balance b',
                    'ac_rec_acc.id = b.rec_acc_id'
                )
                ->andWhere([
                    'ac_rec_acc.account_id' => $accountId,
                    'b.period_id' => $period->prev_period,
                    'b.sys_company_id' => $period->sys_company_id
                ])
                ->scalar();
        }
        return $balance;
    }


    /**
     * Balance account filtered by table values
     *
     * @param $accountId
     * @param AcPeriod $period
     * @param $filter
     * @return array
     * @throws \yii\db\Exception|\yii\base\Exception
     */
    public static function accByDaysFilter($accountId, AcPeriod $period, $filter)
    {

        $query = AccQueries::joinRefs(
            $accountId,
            $period->sys_company_id,
            $filter
        );
        return $query
            ->select([
                'amount' => '
                IFNULL(SUM(
                    CASE ac_rec_acc.id
                        WHEN credit_rec_acc_id
                        THEN amount
                        ELSE - amount
                    END
                ),0)                
                ',
                'date' => 'ac_tran.accounting_date'
            ])
            ->innerJoin(
                'ac_tran',
                ' ac_rec_acc.id in (credit_rec_acc_id,debit_rec_acc_id)'
            )
            ->andWhere([
                'ac_rec_acc.account_id' => $accountId,
                'ac_tran.period_id' => $period->id,
                'ac_tran.sys_company_id' => $period->sys_company_id
            ])
            ->groupBy('accounting_date')
            ->asArray()
            ->all();
    }
}
