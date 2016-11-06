<?php

namespace d3acc\models;

use Yii;
use \d3acc\models\base\AcRecAcc as BaseAcRecAcc;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "ac_rec_acc".
 */
class AcRecAcc extends BaseAcRecAcc
{

    /**
     * get record accounts
     * @param int $accId
     * @param array $ref
     * @return AcRecAcc
     */
    public static function getAcc($accId, $ref = false)
    {
        $acc = AcAccount::getValidatedAcc($accId, $ref);

        /**
         * search account
         */
        $findRecRef = self::find()->where(['account_id' => $accId]);
        if ($ref) {
            foreach ($acc->getAcDefs()->all() as $acDef) {
                $tableAsName = '`r'.$acDef->table.'`';
                $findRecRef->join('INNER JOIN', '`ac_rec_ref` as '.$tableAsName,
                    '`ac_rec_acc`.`id` = '.$tableAsName.'.`rec_account_id`');
                $findRecRef->andWhere(
                    [
                        $tableAsName.'.`def_id`' => $acDef->id,
                        $tableAsName.'.`pk_value`' => $ref[$acDef->table],
                ]);
            }
        }
        if ($model = $findRecRef->one()) {
            return $model;
        }

        /**
         * create account
         */
        $db = \Yii::$app->db;
        $transaction = $db->beginTransaction();
        $label = [$acc->name];
        if($ref){
            $tableModels = \Yii::$app->getModule('d3acc')->tableModels;

            foreach($ref as $tableName => $pkValue){
                if(!isset($tableModels[$tableName])){
                    $label[] = $tableName . '=' . $pkValue;
                    continue;
                }
                $tm = $tableModels[$tableName];
                if(!method_exists($tm,'itemLabel')){
                    $label[] = $tableName . '=' . $pkValue;
                    continue;
                }
                $label[] = $tm::findOne($pkValue)->itemLabel();
            }
        }

        $model             = new AcRecAcc();
        $model->account_id = $accId;
        $model->label      = implode(', ', $label);
        if(!$model->save()){
            $transaction->rolback();
            throw new \Exception('Error: ' .json_encode($model->getErrors()));
        }

        if ($ref) {
            foreach ($acc->getAcDefs()->all() as $acDef) {
                $modelRecRef                 = new AcRecRef();
                $modelRecRef->def_id         = $acDef->id;
                $modelRecRef->rec_account_id = $model->id;
                $modelRecRef->pk_value       = $ref[$acDef->table];
                if(!$modelRecRef->save()){
                    $transaction->rolback();
                    throw new \Exception('Error: ' .json_encode($modelRecRef->getErrors()));

                }
            }
        }

        $transaction->commit();

        return $model;
    }

    /**
     * search accounts
     * @param int $accId
     * @param array $ref
     * @return array
     */
    public static function filterAcc($accId, $ref)
    {
        $acc = AcAccount::findOne($accId);

        /**
         * search account
         */
        $findRecRef = self::find()->where(['account_id' => $accId]);

        foreach ($acc->getAcDefs()->all() as $acDef) {
            if(!isset($ref[$acDef->table])){
                continue;
            }
            $tableAsName = '`r'.$acDef->table.'`';
            $findRecRef->join('INNER JOIN', '`ac_rec_ref` as '.$tableAsName,
                '`ac_rec_acc`.`id` = '.$tableAsName.'.`rec_account_id`');
            $findRecRef->andWhere(
                [
                    $tableAsName.'.`def_id`' => $acDef->id,
                    $tableAsName.'.`pk_value`' => $ref[$acDef->table],
            ]);
        }

        return $findRecRef->all();
    }

    /**
     * Get ref PK value 
     * @param int $def_id id from table ac_def
     * @return int
     */
    public function getRefPkValue($def_id){
        return $this->getAcRecRefs()->select('pk_value')->where(['def_id' => $def_id])->scalar();
    }
}