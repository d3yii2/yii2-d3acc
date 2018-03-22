<?php
/**
 *
 */
use d3acc\models\AcAccount;
use d3acc\models\AcDef;
use d3acc\models\AcRecAcc;
use d3acc\models\AcRecRef;

class AccConstructor
{
    /**
     * @param string $code
     * @param string $name
     * @return created d3acc\models\AcAccount model
     */
    public function create(string $code, string $name)
    {
        $model = new AcAccount();
        $model->code = $code;
        $model->name = $name;

        if (!$model->save()) {
            throw new \Exception('Can not create AcAccount: '.json_encode($model->getErrors()));
        }
        return $model;
    }

    /**
     * @param int $accountId
     * @return d3acc\models\AcAccount model
     */
    public function load(int $accountId)
    {
        return AcAccount::findOne($accountId);
    }

    /**
     * @param int $accountId
     * @param string $table
     * @param string $pkField
     * @return created d3acc\models\AcDef model
     */
    public function addDimension(int $accountId, string $table, string $pkField)
    {
        $model = new AcDef();
        $model->account_id = $accountId;
        $model->table = $table;
        $model->pk_field = $pkField;

        if (!$model->save()) {
            throw new \Exception('Can not create AcDef: '.json_encode($model->getErrors()));
        }
        return $model;
    }

    /**
     * @param int $recAccountId
     * @param int $defId
     * @param int $pkValue
     */
    public function addDimensionRecAcc(int $recAccountId, int $defId, int $pkValue)
    {
        $acc_model = $this->load($recAccountId);
        if(!AcDef::findOne(['id' => $defId])){
            throw new \Exception('Can not find ac_def with id:'.$defId);
        }

        foreach ($acc_model->getAcRecAccs()->all() as $ac_rec_acc){
            /**Check if already exists ac_rec_ref*/
            $ac_rec_ref_model = AcRecRef::findOne([
                'rec_account_id' => $ac_rec_acc->id,
                'def_id' => $defId
            ]);

            /**If not exists then create record*/
            if(!$ac_rec_ref_model){
                $ac_rec_ref_model = new AcRecRef();
                $ac_rec_ref_model->rec_account_id = $ac_rec_acc->id;
                $ac_rec_ref_model->def_id = $defId;
                $ac_rec_ref_model->pk_value = $pkValue;
                ;
                if (!$ac_rec_ref_model->save()) {
                    throw new \Exception('Can not create ac_rec_ref: '.json_encode($ac_rec_ref_model->getErrors()));
                }
            }
            else{
                /**For now do nothing*/
                continue;
            }
        }
    }

    /**
     * @param $accountId
     * @throws Exception
     */
    public function recalculateLabel($accountId)
    {
        /**Get data which is static in all function*/
        $acc_model = $this->load($accId);
        $acc_defs_model = AcDef::findAll(['account_id' => $acc_model->id]);
        $tableModels = \Yii::$app->getModule('d3acc')->tableModels;

        /**Single transaction for all records*/
        $db = \Yii::$app->db;
        $transaction = $db->beginTransaction();

        foreach (AcRecAcc::findAll(['account_id' => $acc_model->id])  as $ac_rec_acc){

            /**Get array of ac_rec_def, where def_id are keys*/
            $acc_rec_ref_array = AcRecRef::find([
                'rec_account_id' => $ac_rec_acc->id
            ])->indexBy('def_id')->all();

            /**Recalculate label for ac_rec_acc*/
            $label = [$acc->name];
            foreach ($acc_defs_model as $acDef){
                $pkValue = $acc_rec_ref_array[$acDef->id]->pk_value;

                if(!isset($tableModels[$acDef->table])){
                    $label[] = $tableName . '=' . $pkValue;
                    continue;
                }
                $tm = $tableModels[$acDef->table];
                if(!method_exists($tm,'itemLabel')){
                    $label[] = $tableName . '=' . $pkValue;
                    continue;
                }
                $label[] = $tm::findOne($pkValue)->itemLabel();
            }

            $ac_rec_acc->label = implode(', ', $label);
            if(!$ac_rec_acc->update()){
                $transaction->rolback();
                throw new \Exception('Error: ' .json_encode($model->getErrors()));
            }
        }

        $transaction->commit();
    }
}