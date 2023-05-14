<?php

namespace d3acc\models;

use d3acc\components\AccQueries;
use d3acc\models\base\AcRecAcc as BaseAcRecAcc;
use Yii;
use yii\base\ErrorException;
use yii\db\Exception;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "ac_rec_acc".
 */
class AcRecAcc extends BaseAcRecAcc
{

    /**
     * get record accounts
     * @param int $accId
     * @param int $sysCompanyId
     * @param array|null $ref
     * @return AcRecAcc
     * @throws Exception
     * @throws \yii\base\ErrorException|\yii\base\Exception
     * @throws \Exception
     */
    public static function getAcc(
        int $accId,
        int $sysCompanyId,
        array $ref = null
    )
    {
        $acc = AcAccount::getValidatedAcc($accId,  $ref);

        /**
         * search account
         */
        $findRecRef = self::find()->where([
            'account_id' => $accId,
            'ac_rec_acc.sys_company_id' => $sysCompanyId
        ]);
        $labelRef = [];
        if ($ref) {
            $i = 0;
            foreach ($acc
                         ->getAcDefs()
                         ->orderBy(['id'=>SORT_ASC])
                         ->all() as $acDef
            ) {
                $i ++;
                $tableAsName = '`r' . $i . '_' . $acDef->table.'`';

                /** REF name can be table name or code */
                if ($acDef->code) {
                    $pkValue = $ref[$acDef->code]??$ref[$acDef->table]??null;
                } else {
                    $pkValue = $ref[$acDef->table]??null;
                }
                $labelRef[] = [
                    'table' => $acDef->table,
                    'pkValue' => $pkValue
                ];
                if (!$pkValue) {
                    throw new \yii\base\Exception('Missing ref parameter for def '
                        . VarDumper::dumpAsString($acDef->attributes)
                        . ' for account creation. Ref: '
                        . VarDumper::dumpAsString($ref)
                    );
                }
                $findRecRef->join('INNER JOIN', '`ac_rec_ref` as '.$tableAsName,
                    '`ac_rec_acc`.`id` = '.$tableAsName.'.`rec_account_id`')
                    ->andWhere([
                        $tableAsName.'.`def_id`' => $acDef->id,
                        $tableAsName.'.`pk_value`' => $pkValue,
                    ]
                    );
            }
        }
        if ($model = $findRecRef->one()) {
            return $model;
        }

        /**
         * create account
         */
        $db = Yii::$app->db;
        if (!$transaction = Yii::$app->db->beginTransaction()) {
            throw new ErrorException('Can not initiate tran');
        }

        $label = [$acc->name];
        if($labelRef){
            $tableModels = Yii::$app->getModule('d3acc')->tableModels;

            foreach($labelRef as $labelRefData){
                $tableName = $labelRefData['table'];
                $pkValue = $labelRefData['pkValue'];
                if(!isset($tableModels[$tableName])){
                    $label[] = $tableName . '=' . $pkValue;
                    continue;
                }
                $tm = $tableModels[$tableName];
                if(method_exists($tm,'accItemLabel')){
                    $label[] = $tm::findOne($pkValue)->accItemLabel();
                    continue;
                }
                if(method_exists($tm,'itemLabel')){
                    $label[] = $tm::findOne($pkValue)->itemLabel();
                    continue;
                }
                $label[] = $tableName . '=' . $pkValue;

            }
        }

        $model             = new AcRecAcc();
        $model->sys_company_id = $sysCompanyId;
        $model->account_id = $accId;
        $model->label      = implode(', ', $label);
        if(!$model->save()){
            $transaction->rollBack();
            throw new \Exception('Error: ' .json_encode($model->getErrors()));
        }

        if ($ref) {
            foreach ($acc->getAcDefs()->all() as $acDef) {
                $modelRecRef                 = new AcRecRef();
                $modelRecRef->sys_company_id = $sysCompanyId;
                $modelRecRef->def_id         = $acDef->id;
                $modelRecRef->rec_account_id = $model->id;
                $modelRecRef->pk_value       = $ref[$acDef->code]??$ref[$acDef->table];
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
     * @param int $sysCompanyId
     * @param array $ref
     * @return self[]
     * @deprecated use AccQueries::joinRefs()->all()
     */
    public static function filterAcc(int $accId, int $sysCompanyId, $ref): array
    {
        return AccQueries::joinRefs($accId, $sysCompanyId, $ref)->all();

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