<?php

namespace d3acc\models;

use d3acc\components\AccQueries;
use d3acc\models\base\AcRecAcc as BaseAcRecAcc;
use d3system\exceptions\D3ActiveRecordException;
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
     * @param int $currencyId
     * @param bool $createIfNoExist
     * @return AcRecAcc|null
     * @throws D3ActiveRecordException
     * @throws ErrorException
     * @throws Exception
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    public static function getAcc(
        int $accId,
        int $sysCompanyId,
        array $ref,
        int $currencyId,
        bool $createIfNoExist = true
    ): ?AcRecAcc
    {
        $acc = AcAccount::getValidatedAcc($accId,  $ref);

        /**
         * search account
         */
        $findRecRef = self::find()
            ->where([
                'account_id' => $accId,
                'ac_rec_acc.sys_company_id' => $sysCompanyId,
                'ac_rec_acc.currency_id' => $currencyId
            ]);
        $labelRef = [];
        if ($ref) {
            $i = 0;
            foreach ($acc
                         ->getAcDefs()
                         ->orderBy(['id'=>SORT_ASC])
                         ->all() as $acDef
            ) {
                /** REF name can be table name or code */
                $pkValue = null;
                if ($acDef->code) {
                    if (isset($ref[$acDef->code])) {
                        $pkValue = $ref[$acDef->code];
                        $refKey = $acDef->code;
                    } elseif (isset($ref[$acDef->table])) {
                        $pkValue = $ref[$acDef->table];
                        $refKey = $acDef->table;
                    }
                } else {
                    $pkValue = $ref[$acDef->table]??null;
                    $refKey = $acDef->table;
                }

                if (!$pkValue) {
                    throw new \yii\base\Exception('Missing ref parameter for def '
                        . VarDumper::dumpAsString($acDef->attributes)
                        . ' for account creation. Ref: '
                        . VarDumper::dumpAsString($ref)
                    );
                }

                $labelRef[] = [
                    'table' => $acDef->table,
                    'pkValue' => $pkValue,
                    'use_in_label' => $acDef->use_in_label
                ];

                /** join table */
                $i ++;
                $tableAsName = '`r' . $i . '_' . $acDef->table .'`';

                /** if used AcRecTable, get id  */
                if ($acDef->table === AcRecTable::tableName()) {
//                    if (!$recTable = AcRecTable::findOne(['name' => $pkValue])) {
//                        $recTable = new AcRecTable();
//                        $recTable->name = $pkValue;
//                        if (!$recTable->save()) {
//                            throw new D3ActiveRecordException($recTable);
//                        }
//                    }
                    $pkValue = AcRecTable::findIdOrCreate($pkValue);
                    $ref[$refKey] = $pkValue;
                }
                $findRecRef
                    ->join(
                        'INNER JOIN', '`ac_rec_ref` as '.$tableAsName,
                        '`ac_rec_acc`.`id` = '.$tableAsName.'.`rec_account_id`'
                    )
                    ->andWhere([
                        $tableAsName.'.`def_id`' => $acDef->id,
                        $tableAsName.'.`pk_value`' => $pkValue,
                    ]);
            }
        }
        if ($model = $findRecRef->one()) {
            return $model;
        }

        if (!$createIfNoExist) {
            return null;
        }
        /**
         * create account
         */
        $db = Yii::$app->db;
        if (!$transaction = Yii::$app->db->beginTransaction()) {
            throw new ErrorException('Can not initiate tran');
        }

        $label = [];
        if($labelRef){
            $tableModels = Yii::$app->getModule('d3acc')->tableModels;

            foreach($labelRef as $labelRefData){
                if (!$labelRefData['use_in_label']) {
                    continue;
                }
                $tableName = $labelRefData['table'];
                $pkValue = $labelRefData['pkValue'];
                $len = $labelRefData['use_in_label']??0;
                if ($len > 1) {
                    $pkValue =  substr($pkValue,0,$len);
                }
                /** for AcRecTable to label add only value */
                if ($tableName === AcRecTable::tableName()) {
                    $label[] = $pkValue;
                    continue;
                }
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
            if ($label === []) {
                $label[] = $acc->name;
            }
        }

        $model             = new AcRecAcc();
        $model->sys_company_id = $sysCompanyId;
        $model->account_id = $accId;
        $model->currency_id = $currencyId;
        $model->label      = substr(implode(',', $label),0,100);
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
                    $transaction->rollBack();
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
     * @throws \yii\base\Exception
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
    public function getRefPkValue(int $def_id){
        return $this->getAcRecRefs()->select('pk_value')->where(['def_id' => $def_id])->scalar();
    }
}