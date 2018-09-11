<?php

namespace d3acc\models;

use Yii;
use \d3acc\models\base\AcTranDim as BaseAcTranDim;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "ac_tran_dim".
 */
class AcTranDim extends BaseAcTranDim
{
    /**
     * @param \d3acc\models\AcTran $transaction
     * @param integer $dimId
     * @param deciaml $amt
     */
    public static function register($transaction, $dimId, $amt){
        //ToDo
        //Must check, perhaps check before this call
        //Ac_tran.amt = sum(ac_tran_dim.amt) group by tran_id, group_id
    }

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
}
