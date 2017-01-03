<?php
/**
 * registre transactions and get data of transactions
 * 
 * @author Uldis Nelsons
 */

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

    /**
     * registre transaction
     * @param \d3acc\models\AcRecAcc $debitAcc
     * @param \d3acc\models\AcRecAcc $creditAcc
     * @param decimal $amt
     * @param date $date
     * @param int $periodType
     * @return \d3acc\models\AcTran
     * @return string $code transaction code
     * @throws \Exception
     */
    public static function registre(
    AcRecAcc $debitAcc, AcRecAcc $creditAcc, $amt, $date, $periodType,
    $code = false
    )
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
        $model->t_user_id         = \Yii::$app->user->identity->id;;
        $model->t_datetime        = new Expression('NOW()');
        if ($code) {
            $model->code = $code;
        }
        if (!$model->save()) {
            throw new \Exception('Can not create transaction: '.json_encode($model->getErrors()));
        }

        return $model;
    }

    /**
     * get account balance for period
     * @param \d3acc\models\AcPeriod $period
     * @param boolean $addPrevPalance
     * @return decimal
     */
    public static function periodBalance(AcPeriod $period, $addPrevPalance = true)
    {
        $unionPrevBalanceSql = '';
        if($addPrevPalance){
            $unionPrevBalanceSql = '
                UNION
                  SELECT
                    rec_acc_id,
                    amount
                  FROM
                    ac_period_balance
                  WHERE period_id = :prev_period_id
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
                  GROUP BY debit_rec_acc_id
                  UNION
                  SELECT
                    credit_rec_acc_id rec_acc_id,
                    IFNULL(SUM(amount), 0) amount
                  FROM
                    ac_tran
                  WHERE period_id = :period_id
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
        ]);

        return $command->queryAll();
    }

    /**
     * get account balance for period
     * @param \d3acc\models\AcPeriod $period
     * @return decimal
     */
    public static function periodBalanceTotal(AcPeriod $period)
    {
        $connection = Yii::$app->getDb();

        $command    = $connection->createCommand('
                SELECT
                  rec_acc_id,
                  account.name label,
                  ra.account_id,
                  SUM(amount) amount,
                  0 total_amount
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
        ]);
        $tranData = $command->queryAll();
        $tranData = ArrayHelper::index($tranData, 'account_id');

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
            WHERE b.period_id = :prev_period_id
            GROUP BY ra.account_id
            ORDER BY ra.label
          ',
            [
            ':prev_period_id' => $period->prev_period,
        ]);
        $balanceData = $command->queryAll();
        $balanceData = ArrayHelper::index($balanceData, 'account_id');

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
        //dump($tranData);die();
        return $tranData;
    }

    /**
     * get account balance for period
     * @param \d3acc\models\AcPeriod $period
     * @return decimal
     */
    public static function periodBalanceByCodeTotal(AcPeriod $period, $accountId)
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
        ]);
        return  $command->queryAll();
    }

    /**
     * get account balance for period
     * @param \d3acc\models\AcPeriod $period
     * @return decimal
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
                  GROUP BY debit_rec_acc_id
                  UNION
                  SELECT
                    credit_rec_acc_id rec_acc_id,
                    IFNULL(SUM(amount), 0) amount,
                    0 prev_balance
                  FROM
                    ac_tran
                  WHERE period_id = :period_id
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
        ]);

        $data = $command->queryAll();
        return $data;
    }

    /**
     * get account balance for period
     * @param \d3acc\models\AcRecAcc $acc
     * @param \d3acc\models\AcPeriod $period
     * @param boolean $addPrevPalance
     * @return decimal
     */
    public static function accPeriodBalance(AcRecAcc $acc, AcPeriod $period,
                                            $addPrevPalance = true)
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
            AND  (debit_rec_acc_id = :acc_id OR credit_rec_acc_id = :acc_id)
          ',
            [
            ':acc_id' => $acc->id,
            ':period_id' => $period->id,
        ]);

        $actualBalance = $command->queryScalar();

        if ($addPrevPalance) {
            $actualBalance += AcPeriodBalance::accPeriodBalance($acc, $period);
        }

        return $actualBalance;
    }

    /**
     * get account balance for period grouped by CODE
     * @param int $accountId
     * @param \d3acc\models\AcPeriod $period
     * @return array
     */
    public static function accPeriodBalanceGroupedByCode($accountId,
                                                         AcPeriod $period)
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
            GROUP BY t.code
          ',
            [
            ':account_id' => $accountId,
            ':period_id' => $period->id,
        ]);

        return $command->queryAll();
    }

    /**
     * Get period transactions for account fith start balance
     * @param \d3acc\models\AcRecAcc $acc
     * @param \d3acc\models\AcPeriod $period
     * @return array [accounting_date,+/-amount, acc_label, code,notes,ref_table, ref_id]
     */
    public static function accPeriodTran(AcRecAcc $acc, AcPeriod $period, $startBalance = true)
    {
        $connection = Yii::$app->getDb();
        $command    = $connection->createCommand('
            SELECT
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
            ORDER BY t_datetime
          ',
            [
            ':acc_id' => $acc->id,
            ':period_id' => $period->id,
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
     * @param \d3acc\models\AcRecAcc $acc
     * @param \d3acc\models\AcPeriod $period
     * @return decimal
     */
    public static function accPeriodBalanceByDays(AcRecAcc $acc,
                                                  AcPeriod $period,
                                                  $addPrevToFirstDay = true)
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
        ]);

        $days = $command->queryAll();

        if ($addPrevToFirstDay) {
            $periodDays = $period->getDates();
            if(!$days || $days[0]['date'] != $periodDays[0]){
                array_unshift($days,['date' => $periodDays[0], 'amount' => 0]);
            }
            $days[0]['amount'] += AcPeriodBalance::accPeriodBalance($acc,
                    $period);
        }

        return $days;
    }

    /**
     * get account balance for period filtered by other account and grouped by days
     * @param \d3acc\models\AcRecAcc $acc
     * @param \d3acc\models\AcRecAcc|array $accFilter
     * @param \d3acc\models\AcPeriod $period
     * @return decimal
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
        ]);

        return $command->queryAll();
    }

    /**
     * for account list get total balance by days for period
     * 
     * @param array $accList array of \d3acc\models\AcRecAcc elements
     * @param \d3acc\models\AcPeriod $period
     * @return array
     * @throws \Exception
     */
    public static function accFilterExtPeriodBalanceByDays($accList,
                                                           AcPeriod $period)
    {

        if (!$accList) {
            return [];
        }

        /**
         * get common account_id
         */
        $accId = false;
        foreach ($accList as $acc) {

            if ($accId && $accId !== $acc->account_id) {
                throw new \Exception('In Acc list mixed diferents accounts');
            }
            $accId = $acc->account_id;
        }

        $innerJoin = $where     = $broupBy   = [];
        foreach (AcAccount::findOne($accId)->getAcDefs()->all() as $acDef) {
            $tableAsName = '`r'.$acDef->table.'`';

            $select[] = ','.$tableAsName.'.`pk_value` '.$acDef->table.'_pk_value';
            $join[]   = 'LEFT OUTER JOIN `ac_rec_ref` as '.$tableAsName.
                ' ON `ac_rec_acc`.`id` = '.$tableAsName.'.`rec_account_id`
                    AND '.$tableAsName.'.`def_id` = '.$acDef->id;

            $broupBy[] = ','.$tableAsName.'.`pk_value`';
        }

        $accIdList = ArrayHelper::getColumn($accList, 'id');

        $accdIdInList = "'".implode("','", $accIdList)."'";

        $connection = Yii::$app->getDb();
        $command    = $connection->createCommand('
            SELECT
              accounting_date `date`,
              IFNULL(SUM(
                CASE
                  WHEN credit_rec_acc_id in ('.$accdIdInList.')
                  THEN amount
                  ELSE - amount
                END
              ),0) amount,
              CASE
                  WHEN credit_rec_acc_id in ('.$accdIdInList.')
                  THEN credit_rec_acc_id
                  ELSE debit_rec_acc_id
                END rec_acc_id
              '.implode(PHP_EOL, $select).'
            FROM
              ac_tran
              INNER JOIN ac_rec_acc
                ON ac_rec_acc.id = CASE
                                    WHEN credit_rec_acc_id in ('.$accdIdInList.')
                                    THEN credit_rec_acc_id
                                    ELSE debit_rec_acc_id
                                   END
              '.implode(PHP_EOL, $join).'
            WHERE
                period_id = :period_id
                AND  (
                    credit_rec_acc_id in ('.$accdIdInList.')
                    OR
                    debit_rec_acc_id in ('.$accdIdInList.')
                    )
            GROUP BY
                accounting_date
                '.implode(PHP_EOL, $broupBy).'
            ORDER BY
                accounting_date
          ', [':period_id' => $period->id]);

        return $command->queryAll();
    }

    /**
     * Balance account filtered by table values  
     * 
     * @param int $accountId
     * @param \d3acc\models\AcPeriod $period
     * @param array $filter
     * @return int
     */
    public static function accBalanceFilterOld($accountId, AcPeriod $period,
                                            $filter, $addPrevPalance = false)
    {
        $selectSql = '   
            IFNULL(SUM(
                CASE ac_rec_acc.id
                  WHEN credit_rec_acc_id
                  THEN ac_tran.amount
                  ELSE - ac_tran.amount
                END
              ),0) amount
              ';
        if($addPrevPalance){
            $selectSql = '
                IFNULL(SUM(
                    CASE ac_rec_acc.id
                      WHEN credit_rec_acc_id
                      THEN ac_tran.amount
                      ELSE - ac_tran.amount
                    END
                  ),0)
                  +
                  IFNULL(SUM(IFNULL(b.amount,0)),0)
              ';
        }

        $join = [];
        foreach (AcAccount::findOne($accountId)->getAcDefs()->all() as $acDef) {

            if (!isset($filter[$acDef->table])) {
                continue;
            }

            $tableAsName = '`r'.$acDef->table.'`';
            $join[]      = 'INNER JOIN `ac_rec_ref` as '.$tableAsName.
                ' ON `ac_rec_acc`.`id` = '.$tableAsName.'.`rec_account_id`
                    AND '.$tableAsName.'.`def_id` = '.$acDef->id
                .' AND '.$tableAsName.'.`pk_value` = '.$filter[$acDef->table];
        }

        $connection = Yii::$app->getDb();
        $command    = $connection->createCommand('
            SELECT
              ' . $selectSql . '
            FROM
              ac_tran
              INNER JOIN ac_rec_acc
                ON ac_rec_acc.id in (credit_rec_acc_id,debit_rec_acc_id)
              '.implode(PHP_EOL, $join).'
              LEFT OUTER JOIN ac_period_balance b
                ON ac_rec_acc.id = b.rec_acc_id
                  AND b.period_id = :prev_period_id
            WHERE
                ac_rec_acc.account_id = :account_id
                AND ac_tran.period_id = :period_id

          ',
            [
            ':period_id' => $period->id,
            ':prev_period_id' => $period->prev_period,
            ':account_id' => $accountId,
        ]);

        return $command->queryScalar();
    }

    /**
     * Balance account filtered by table values
     *
     * @param int $accountId
     * @param \d3acc\models\AcPeriod $period
     * @param array $filter
     * @return int
     */
    public static function accBalanceFilter($accountId, AcPeriod $period,
                                           $filter, $addPrevPalance = false)
    {
        $join = [];
        foreach (AcAccount::findOne($accountId)->getAcDefs()->all() as $acDef) {

            if (!isset($filter[$acDef->table])) {
                continue;
            }

            $tableAsName = '`r'.$acDef->table.'`';
            $join[]      = 'INNER JOIN `ac_rec_ref` as '.$tableAsName.
                ' ON `ac_rec_acc`.`id` = '.$tableAsName.'.`rec_account_id`
                    AND '.$tableAsName.'.`def_id` = '.$acDef->id
                .' AND '.$tableAsName.'.`pk_value` = '.$filter[$acDef->table];
        }

        $connection = Yii::$app->getDb();
        $command    = $connection->createCommand('
            SELECT
              IFNULL(SUM(
                CASE ac_rec_acc.id
                  WHEN credit_rec_acc_id
                  THEN ac_tran.amount
                  ELSE - ac_tran.amount
                END
              ),0) amount
            FROM
              ac_tran
              INNER JOIN ac_rec_acc
                ON ac_rec_acc.id in (credit_rec_acc_id,debit_rec_acc_id)
              '.implode(PHP_EOL, $join).'
            WHERE
                ac_rec_acc.account_id = :account_id
                AND ac_tran.period_id = :period_id

          ',
            [
            ':period_id' => $period->id,
            ':account_id' => $accountId,
        ]);

        $balance =  $command->queryScalar();

        if($addPrevPalance){
            $command    = $connection->createCommand('
                SELECT
                    IFNULL(SUM(IFNULL(b.amount,0)),0)
                FROM
                  ac_period_balance b
                  INNER JOIN ac_rec_acc
                    ON ac_rec_acc.id = b.rec_acc_id
                '.implode(PHP_EOL, $join).'
                WHERE
                    ac_rec_acc.account_id = :account_id
                    AND b.period_id = :prev_period_id

              ',
                [
                ':prev_period_id' => $period->prev_period,
                ':account_id' => $accountId,
            ]);

            $balance +=  $command->queryScalar();
        }
        return $balance;
    }

    /**
     * Balance account filtered by table values
     *
     * @param int $accountId
     * @param \d3acc\models\AcPeriod $period
     * @param array $filter
     * @return int
     */
    public static function accByDaysFilter($accountId, AcPeriod $period, $filter)
    {

        $join    = $where   = $broupBy = [];
        foreach (AcAccount::findOne($accountId)->getAcDefs()->all() as $acDef) {

            $tableAsName = '`r'.$acDef->table.'`';

            $select[] = ','.$tableAsName.'.`pk_value` '.$acDef->table.'_pk_value';

            $joinSql = 'INNER JOIN `ac_rec_ref` as '.$tableAsName.
                ' ON `ac_rec_acc`.`id` = '.$tableAsName.'.`rec_account_id`
                    AND '.$tableAsName.'.`def_id` = '.$acDef->id;

            if (isset($filter[$acDef->table])) {
                $joinSql .= ' AND '.$tableAsName.'.`pk_value` = '.$filter[$acDef->table];
                ;
            }
            $join[] = $joinSql;
        }
        $sql        = '
            SELECT
              IFNULL(SUM(
                CASE ac_rec_acc.id
                  WHEN credit_rec_acc_id
                  THEN amount
                  ELSE - amount
                END
              ),0) amount,
              accounting_date `date`
              '.implode(PHP_EOL, $select).'
            FROM
              ac_tran
              INNER JOIN ac_rec_acc
                ON ac_rec_acc.id in (credit_rec_acc_id,debit_rec_acc_id)
              '.implode(PHP_EOL, $join).'
            WHERE
                ac_rec_acc.account_id = :account_id
                AND period_id = :period_id
            GROUP BY
                accounting_date
          ';

        $connection = Yii::$app->getDb();
        $command    = $connection->createCommand($sql,
            [
            ':period_id' => $period->id,
            ':account_id' => $accountId,
        ]);

        return $command->queryAll();
    }
}