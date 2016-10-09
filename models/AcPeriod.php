<?php

namespace d3acc\models;

use Yii;
use \d3acc\models\base\AcPeriod as BaseAcPeriod;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "ac_period".
 */
class AcPeriod extends BaseAcPeriod
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

    public static function getActivePeriod($periodType, $date)
    {
        return self::find()
                ->where(['period_type' => $periodType])
                ->andWhere(['status' => self::STATUS_ACTIVE])
                ->andWhere(" '".$date."' >= `from` and  '".$date."' <= `to`")
                ->one();
    }
}