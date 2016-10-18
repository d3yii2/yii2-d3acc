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

        $label[] = [$acc->name];
        if($ref){
            dump(\Yii::$app->getModule('d3acc'));
            $tableModels = \Yii::$app->getModule('d3acc')->tableModels;

            foreach($ref as $tableName => $pkValue){
                if(!isset($tableModels[$tableAsName])){
                    $label[] = $tableAsName . '=' . $pkValue;
                    continue;
                }
                $tm = $tableModels[$tableAsName];
                if(!method_exists($tm,'itemLabel')){
                    $label[] = $tableAsName . '=' . $pkValue;
                    continue;
                }
                $label[] = $tm::findOne($pkValue)->itemLabel();
            }
        }

        $model             = new AcRecAcc();
        $model->account_id = $accId;
        $model->label      = implode(', ', $label);
        $model->save();

        if ($ref) {
            foreach ($acc->getAcDefs()->all() as $acDef) {
                $modelRecRef                 = new AcRecRef();
                $modelRecRef->def_id         = $acDef->id;
                $modelRecRef->rec_account_id = $model->id;
                $modelRecRef->pk_value       = $ref[$acDef->table];
                $modelRecRef->save();
            }
        }

        return $model;
    }
}