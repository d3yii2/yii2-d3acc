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
        $model             = new AcRecAcc();
        $model->account_id = $accId;
        $model->label      = json_encode($ref);
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