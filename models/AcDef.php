<?php

namespace d3acc\models;

use Yii;
use \d3acc\models\base\AcDef as BaseAcDef;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "ac_def".
 */
class AcDef extends BaseAcDef
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
