<?php

namespace d3acc\models;

use d3acc\dictionaries\AcAccountDictionary;
use d3acc\models\base\AcAccount as BaseAcAccount;
use Exception;
use yii\helpers\Json;

/**
 * This is the model class for table "ac_account".
 */
class AcAccount extends BaseAcAccount
{

    public static $allTableRows = [];
    /**
     * @var mixed
     */
    private static array $_validateAcc = [];
    /**
     * @var array|AcAccount[]|\yii\db\ActiveRecord[]
     */
    private static array $_selfAll = [];

    /**
     *      * 
     * @param int $accId
     * @param array $ref
     * @return AcAccount 
     * @throws Exception
     */
    public static function getValidatedAcc(int $accId, $ref = false)
    {
        $keys = [$accId];
        if ($ref) {
            $keys[] = Json::encode($ref);
        }
        $key = implode('-',$keys);
        if (self::$_validateAcc[$key]??null) {
            return self::$_validateAcc[$key];
        }
        if(!$model = self::findOne($accId)){
            throw new Exception('Undefined accId: ' . $accId);
        }
        
        if(!$ref){
            return self::$_validateAcc[$key] = $model;
        }

        if ($def = $model->getAcDefs()->all()) {
            foreach ($def as $defRecord) {
                if (isset($ref[$defRecord->code])) {
                    unset($ref[$defRecord->code]);
                    continue;
                }
                if (isset($ref[$defRecord->table])) {
                    unset($ref[$defRecord->table]);
                    continue;
                }
                throw new Exception('Illegal definition for accId=' . $accId . ' ref=' . json_encode($ref));
            }
        }

        if (count($ref) > 0) {
            throw new Exception('Illegal definition for accId=' . $accId . ' ref=' . json_encode($ref));
        }
        
        return self::$_validateAcc[$key] = $model;
    }

    /**
     * @inheritdoc
     * @return static|null ActiveRecord instance matching the condition, or `null` if nothing matches.
     */
    public static function findOne($condition)
    {
        if(!is_array($condition)){
            if(self::$allTableRows && isset(self::$allTableRows[$condition])){
                return self::$allTableRows[$condition];
            }
            if (!self::$_selfAll) {
                self::$_selfAll = self::find()->all();
            }
            foreach(self::$_selfAll as $row){
                self::$allTableRows[$row->id] = $row;
            }
            return self::$allTableRows[$condition];
        }
        return parent::findByCondition($condition)->one();
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        AcAccountDictionary::clearCache();
    }

    public function afterDelete()
    {
        parent::afterDelete();
        AcAccountDictionary::clearCache();
    }
}
