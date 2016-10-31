<?php

namespace d3acc\models;

use Yii;
use d3acc\models\base\AcAccount as BaseAcAccount;
use yii\helpers\ArrayHelper;
use Exception;

/**
 * This is the model class for table "ac_account".
 */
class AcAccount extends BaseAcAccount
{

    public static $allTableRows = [];
    /**
     *      * 
     * @param int $accId
     * @param array $ref
     * @return AcAccount 
     * @throws Exception
     */
    public static function getValidatedAcc($accId, $ref = false)
    {

        if(!$model = self::findOne($accId)){
            throw new Exception('Undefined accId: ' . $accId);
        }
        
        if(!$ref){
            return $model;
        }

        if ($def = $model->getAcDefs()->all()) {
            foreach ($def as $defRecord) {
                if (!isset($ref[$defRecord->table])) {
                    throw new Exception('Ilegal definition for accId=' . $accId . ' ref=' . json_encode($ref));
                }
                unset($ref[$defRecord->table]);
            }
        }

        if (count($ref) != 0) {
            throw new Exception('Ilegal definition for accId=' . $accId . ' ref=' . json_encode($ref));
        }
        
        return $model;
    }

    /**
     * @inheritdoc
     * @return static|null ActiveRecord instance matching the condition, or `null` if nothing matches.
     */
    public static function findOne($condition)
    {
        if(!is_array($condition)){
            if(self::$allTableRows){
                return self::$allTableRows[$condition];
            }
            foreach(self::find()->all() as $row){
                self::$allTableRows[$row->id] = $row;
            }
            return self::$allTableRows[$condition];
        }
        return parent::findByCondition($condition)->one();
    }
}
