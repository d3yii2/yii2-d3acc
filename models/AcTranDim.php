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
