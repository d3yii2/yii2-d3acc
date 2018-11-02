<?php

namespace d3acc\models;

use Yii;
use \d3acc\models\base\AcPeriodBalanceDim as BaseAcPeriodBalanceDim;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "ac_period_balance_dim".
 */
class AcPeriodBalanceDim extends BaseAcPeriodBalanceDim
{
    /**
     * save period dim balance
     * @param \d3acc\models\AcPeriod $period
     * @return type
     */
    public static function saveDimPeriodBalance(AcPeriod $period)
    {
        $connection = Yii::$app->getDb();
        $command    = $connection->createCommand('
            INSERT INTO ac_period_balance_dim (period_id,dim_id,amount)
            SELECT :period_id AS period_id, tmp.dim_id, SUM(tmp.amount) AS amount
            FROM(
                SELECT 
                  acd.id AS dim_id,
                  IFNULL(SUM(actd.amt), 0) amount
                FROM
                  ac_dim AS acd
                  INNER JOIN ac_tran_dim AS actd
                    ON actd.dim_id = acd.id
                  INNER JOIN ac_tran AS act
                    ON act.id = actd.tran_id AND act.period_id = :period_id
                  GROUP BY acd.id
                UNION
                SELECT
                  dim_id AS dim_id,
                  amount
                FROM
                  ac_period_balance_dim
                WHERE period_id = :prev_period_id
            ) tmp
            GROUP BY tmp.dim_id
            ',
            [
                ':period_id' => $period->id,
                ':prev_period_id' => $period->prev_period,
            ]);

        return $command->query();
    }
}
