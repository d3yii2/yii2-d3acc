<?php
/**
 *
 */
namespace d3acc\components;

use d3acc\models\AcAccount;
use d3acc\models\AcDef;
use d3acc\models\AcRecAcc;
use d3acc\models\AcRecRef;

class AccConstructor
{
    /** @var AcAccount */
    private $account;
    
    /**
     * @param string $code
     * @param string $name
     */
    public function create(string $code, string $name)
    {
        $this->account = new AcAccount();
        $this->account->code = $code;
        $this->account->name = $name;

        if (!$this->account->save()) {
            throw new \Exception('Can not create AcAccount: '.json_encode($model->getErrors()));
        }

    }

    /**
     * @param int $accountId
     */
    public function load(int $accountId)
    {
        $this->account = AcAccount::findOne($accountId);
    }

    /**
     * @param string $table
     * @param string $pkField
     * @return d3acc\models\AcDef created model
     */
    public function addDimension(string $table, string $pkField = 'id')
    {
        $model = new AcDef();
        $model->account_id = $this->account->id;
        $model->table = $table;
        $model->pk_field = $pkField;

        if (!$model->save()) {
            throw new \Exception('Can not create AcDef: '.json_encode($model->getErrors()));
        }
        return $model;
    }

    /**
     * @param int $defId
     * @param int $pkValue
     * @throws Exception
     */
    public function addDimensionRecAcc(int $defId, int $pkValue, array $validation = null)
    {
        if(!AcDef::findOne(['id' => $defId])){
            throw new \Exception('Can not find ac_def with id:'.$defId);
        }

        foreach ($this->account->getAcRecAccs()->all() as $ac_rec_acc){
            /**Check if already exists ac_rec_ref*/
            $ac_rec_ref_model = AcRecRef::findOne([
                'rec_account_id' => $ac_rec_acc->id,
                'def_id' => $defId
            ]);

            /**If not exists then create record*/
            if(!$ac_rec_ref_model){

                /**Add custom validation logic*/
                if(isset($validation)){
                    /**If not valid the do nothing*/
                    if(!$this->validateDimensionSet($ac_rec_acc->id, $validation)){
                        continue;
                    }
                }

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
     * @param int $recAccId
     * @param int $defId
     * @param int $pkValue
     * @param array $validation 0 key maps function, rest is custom params
     * @return bool
     */
    public function validateDimensionSet(int $recAccId, array $validation){
        if($validation['function'] === 1){
            /**Check if there is AcRecRef for specific AcDef and PkValue*/
            $ac_rec_ref_model = AcRecRef::findOne([
                'rec_account_id' => $recAccId,
                'def_id' => $validation['def_id'],
                'pk_value' => $validation['pk_value']
            ]);
            /**Return true if record is found*/
            if($ac_rec_ref_model){
                return true;
            }
        }
        /**Default return false for safety*/
        return false;
    }

    /**
     * @throws Exception
     */
    public function recalculateLabel()
    {
        /**Get data which is static in all function*/
        $acc_defs_model = AcDef::findAll(['account_id' => $this->account->id]);
        $tableModels = \Yii::$app->getModule('d3acc')->tableModels;

        /**Single transaction for all records*/
        $db = \Yii::$app->db;
        $transaction = $db->beginTransaction();

        foreach (AcRecAcc::findAll(['account_id' => $this->account->id])  as $ac_rec_acc){

            /**Get array of ac_rec_def, where def_id are keys*/
            $acc_rec_ref_array = AcRecRef::find([
                'rec_account_id' => $ac_rec_acc->id
            ])->indexBy('def_id')->all();

            /**Recalculate label for ac_rec_acc*/
            $label = [$this->account->name];
            foreach ($acc_defs_model as $acDef){
                $pkValue = $acc_rec_ref_array[$acDef->id]->pk_value;

                if(!isset($tableModels[$acDef->table])){
                    $label[] = $acDef->table . '=' . $pkValue;
                    continue;
                }
                $tm = $tableModels[$acDef->table];
                if(!method_exists($tm,'itemLabel')){
                    $label[] = $acDef->table . '=' . $pkValue;
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
