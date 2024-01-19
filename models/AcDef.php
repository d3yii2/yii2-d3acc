<?php

namespace d3acc\models;

use d3acc\dictionaries\AcDefDictionary;
use d3acc\models\base\AcDef as BaseAcDef;
use yii\helpers\ArrayHelper;


/**
 * This is the model class for table "ac_def".
 */
class AcDef extends BaseAcDef
{

    /**
     * @var self[]
     */
    private static ?array $_all = null;

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        AcDefDictionary::clearCache();
    }

    public function afterDelete()
    {
        parent::afterDelete();
        AcDefDictionary::clearCache();
    }

    public static function findByAcAccount(int $acAccountId)
    {
        $list = [];
        foreach (self::findAllModels() as $model) {
            if ((int)$model->account_id === $acAccountId) {
                $list[] = $model;
            }
        }
        return $list;
    }

    /**
     * @return AcDef[]
     */
    public static function findAllModels(): array
    {
        if (self::$_all) {
            return self::$_all;
        }
        return self::$_all = ArrayHelper::index(self::find()->all(), 'id');
    }


}
