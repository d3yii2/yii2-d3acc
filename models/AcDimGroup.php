<?php

namespace d3acc\models;

use Yii;
use \d3acc\models\base\AcDimGroup as BaseAcDimGroup;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "ac_dim_group".
 */
class AcDimGroup extends BaseAcDimGroup
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
