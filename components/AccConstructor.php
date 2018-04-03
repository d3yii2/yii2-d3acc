<?php
/**
 *
 */
namespace d3acc\components;

use Yii;
use d3acc\models\AcAccount;
use d3acc\models\AcDef;
use d3acc\models\AcRecAcc;
use d3acc\models\AcRecRef;
use yii\helpers\ArrayHelper;

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
     * @return AcRecAcc
     * @throws \Exception
     */
    public function addExtendedAccount(){
        $extendedAccount = new AcRecAcc();
        $extendedAccount->account_id = $this->account->id;
        $extendedAccount->label = $this->account->name;
        if (!$extendedAccount->save()) {
            throw new \Exception('Can not create ac_rec_ref: '.json_encode($extendedAccount->getErrors()));
        }
        return $extendedAccount;
    }

    /**
     * @param int $defId
     * @param int $pkValue
     * @throws Exception
     */
    public function addDimensionRecAcc(int $recAcountId, int $defId, int $pkValue)
    {
        /**Check if already exists ac_rec_ref*/
        $dimensionRecAcc = AcRecRef::findOne(['rec_account_id' => $recAcountId,'def_id' => $defId]);

        /**If not exists then create record*/
        if(!$dimensionRecAcc){
            $dimensionRecAcc = new AcRecRef();
            $dimensionRecAcc->rec_account_id = $recAcountId;
            $dimensionRecAcc->def_id = $defId;
            $dimensionRecAcc->pk_value = $pkValue;

            if (!$dimensionRecAcc->save()) {
                throw new \Exception('Can not create ac_rec_ref: '.json_encode($ac_rec_ref_model->getErrors()));
            }
            else{
                self::recalculateLabel($recAcountId);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function recalculateLabel(int $recAccId)
    {
        $ac_def_models = AcDef::findAll(['account_id' => $this->account->id]);
        $tableModels = \Yii::$app->getModule('d3acc')->tableModels;

        $sql = 'SELECT def_id, pk_value FROM `ac_rec_ref` WHERE `rec_account_id`='.$recAccId;
        $connection = Yii::$app->getDb();
        $command    = $connection->createCommand($sql);
        $rows = $command->queryAll();
        $acRecRefList = ArrayHelper::map($rows, 'def_id', 'pk_value');

        /**Recalculate label for ac_rec_acc*/
        $label = [$this->account->name];
        foreach ($ac_def_models as $acDef){
            $pkValue = $acRecRefList[$acDef->id];

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

        $acRecAcc = AcRecAcc::findOne($recAccId);
        $acRecAcc->label = implode(', ', $label);
        if(!$acRecAcc->update()){
            throw new \Exception('Error: ' .json_encode($model->getErrors()));
        }

    }
}
