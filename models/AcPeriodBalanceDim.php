<?php

namespace d3acc\models;

use Yii;
use d3acc\models\base\AcPeriodBalanceDim as BaseAcPeriodBalanceDim;
use yii\db\Exception;

/**
 * This is the model class for table "ac_period_balance_dim".
 */
class AcPeriodBalanceDim extends BaseAcPeriodBalanceDim
{
    /**
     * save period dim balance
     * @param \d3acc\models\AcPeriod $period
     * @return type
     * @throws Exception
     */
    public static function saveDimPeriodBalance(AcPeriod $period)
    {
        $connection = Yii::$app->getDb();
        $command    = $connection->createCommand('
            INSERT INTO ac_period_balance_dim (
                sys_company_id,
                period_id,
                dim_id,
                amount, 
                account_id
            )
            SELECT 
                :sysCompanyId AS sysCompanyId,    
                :period_id AS period_id, 
                tmp.dim_id, 
                SUM(tmp.amount) AS amount, 
                accountId AS account_id
            FROM(
            SELECT 
		          account.Id AS accountId,
                  a.dim_id,
                  SUM(a.amount) AS amount
            FROM
              (
                  SELECT 
                        debit_rec_acc_id rec_acc_id,
                        td.dim_id,
                        - IFNULL(SUM(td.amt), 0) amount
                  FROM
                      ac_tran t
                  INNER JOIN ac_tran_dim td ON t.id=td.tran_id
                  WHERE period_id = :period_id
                  GROUP BY debit_rec_acc_id
                  UNION
                  SELECT
                        credit_rec_acc_id rec_acc_id,
                        td.dim_id,
                        IFNULL(SUM(td.amt), 0) amount
                  FROM
                      ac_tran t
                  INNER JOIN ac_tran_dim td ON t.id=td.tran_id
                  WHERE period_id = :period_id
                  GROUP BY credit_rec_acc_id              
              ) a
              INNER JOIN ac_rec_acc ra
                ON a.rec_acc_id = ra.id
              INNER JOIN ac_account account 
                ON ra.account_id = account.id
              GROUP BY dim_id, accountId
                
              UNION
              SELECT
                account_id AS accountId,
                dim_id AS dim_id,
                amount
              FROM ac_period_balance_dim
              WHERE period_id = :prev_period_id
            ) tmp
            GROUP BY tmp.dim_id, tmp.accountId
            ',
            [
                ':sysCompanyId' => $period->sys_company_id,
                ':period_id' => $period->id,
                ':prev_period_id' => $period->prev_period,
            ]);

        return $command->query();
    }
}
