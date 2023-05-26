<?php
/**
 *
 */
namespace d3acc\components;

use d3acc\models\AcRecTable;
use d3acc\Module;
use Exception;
use Throwable;
use Yii;
use d3acc\models\AcAccount;
use d3acc\models\AcDef;
use d3acc\models\AcRecAcc;
use d3acc\models\AcRecRef;
use yii\db\StaleObjectException;
use yii\helpers\ArrayHelper;

class AccConstructor
{
    /** @var AcAccount */
    private $account;
    /**
     * @var int
     */
    private $sysCompanyId;

    public function __construct(int $sysCompanyId)
    {
        $this->sysCompanyId = $sysCompanyId;
    }

    /**
     * @param string $code
     * @param string $name
     * @throws Exception
     */
    public function create(string $code, string $name): void
    {
        $this->account = new AcAccount();
        $this->account->sys_company_id = $this->sysCompanyId;
        $this->account->code = $code;
        $this->account->name = $name;

        if (!$this->account->save()) {
            throw new \yii\base\Exception('Can not create AcAccount: '.json_encode($this->account->getErrors()));
        }

    }

    /**
     * @param int $accountId
     */
    public function load(int $accountId): void
    {
        $this->account = AcAccount::findOne($accountId);
    }

    /**
     * @param string $table
     * @param string $pkField
     * @param string $code
     * @return AcDef created model
     * @throws \yii\base\Exception
     */
    public function addAccDef(
        string $table,
        string $pkField = 'id',
        string $code = '',
        int $useInLabel = 1
    ): AcDef
    {
        $model = new AcDef();
        $model->sys_company_id = $this->sysCompanyId;
        $model->account_id = $this->account->id;
        $model->table = $table;
        $model->pk_field = $pkField;
        $model->use_in_label = $useInLabel;
        if($code){
            $model->code = $code;
        }
        if (!$model->save()) {
            throw new \yii\base\Exception('Can not create AcDef: '.json_encode($model->getErrors()));
        }
        return $model;
    }

    public function addAccDefRecTable(
        string $code = '',
        int $useInLabel = 1
    ): AcDef
    {
        $model = new AcDef();
        $model->sys_company_id = $this->sysCompanyId;
        $model->account_id = $this->account->id;
        $model->table = AcRecTable::tableName();
        $model->pk_field = 'id';
        $model->use_in_label = $useInLabel;
        if($code){
            $model->code = $code;
        }
        if (!$model->save()) {
            throw new \yii\base\Exception('Can not create AcDef: '.json_encode($model->getErrors()));
        }
        return $model;
    }

    /**
     * @param string $oldTable
     * @param string $newTable
     * @throws StaleObjectException
     * @throws Throwable
     * @throws \yii\db\Exception
     */
    public function changeDimensionName(string $oldTable, string $newTable): void
    {
        $def = AcDef::findOne([
            'sys_company_id' => $this->sysCompanyId,
            'account_id' => $this->account->id,
            'table' => $oldTable
        ]);

        if($def){
            $def->table = $newTable;
            if (!$def->update()) {
                throw new \yii\base\Exception('Can not update AcDef: '.json_encode($def->getErrors()));
            }
        }

        $accountRecAccList = AcRecAcc::findAll([
            'account_id' => $this->account->id,
            'sys_company_id' => $this->sysCompanyId,
        ]);
        foreach ($accountRecAccList as $recAcc){
            $this->recalculateLabel($recAcc->id);
        }
    }

    /**
     * @return AcRecAcc
     * @throws Exception
     */
    public function addExtendedAccount(): AcRecAcc
    {
        $extendedAccount = new AcRecAcc();
        $extendedAccount->sys_company_id = $this->sysCompanyId;
        $extendedAccount->account_id = $this->account->id;
        $extendedAccount->label = $this->account->name;
        if (!$extendedAccount->save()) {
            throw new \yii\base\Exception('Can not create ac_rec_ref: '.json_encode($extendedAccount->getErrors()));
        }
        return $extendedAccount;
    }

    /**
     * @param int $recAccountId
     * @param int $defId
     * @param int $pkValue
     * @throws StaleObjectException
     * @throws Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     */
    public function addDimensionRecAcc(int $recAccountId, int $defId, int $pkValue): void
    {
        /**Check if already exists ac_rec_ref*/
        $dimensionRecAcc = AcRecRef::findOne([
            'rec_account_id' => $recAccountId,
            'def_id' => $defId,
            'sys_company_id' => $this->sysCompanyId
        ]);

        /**If not exists then create record*/
        if(!$dimensionRecAcc){
            $dimensionRecAcc = new AcRecRef();
            $dimensionRecAcc->sys_company_id = $this->sysCompanyId;
            $dimensionRecAcc->rec_account_id = $recAccountId;
            $dimensionRecAcc->def_id = $defId;
            $dimensionRecAcc->pk_value = $pkValue;

            if (!$dimensionRecAcc->save()) {
                throw new \yii\base\Exception('Can not create ac_rec_ref: '.json_encode($dimensionRecAcc->getErrors()));
            }

            $this->recalculateLabel($recAccountId);
        }
    }

    /**
     * @param int $recAccId
     * @throws Throwable
     * @throws \yii\db\Exception
     * @throws StaleObjectException
     */
    private function recalculateLabel(int $recAccId): void
    {
        $ac_def_models = AcDef::findAll([
            'account_id' => $this->account->id,
            'sys_company_id' => $this->sysCompanyId,
        ]);

        /** @var $d3AccModule Module */
        if(!$d3AccModule = Yii::$app->getModule('d3acc')){
            throw new \yii\base\Exception('Can not load d3acc module');
        }
        $tableModels = $d3AccModule->tableModels;

        $sql = '
            SELECT 
                def_id, 
                pk_value 
            FROM 
                `ac_rec_ref` 
            WHERE 
                `rec_account_id`='.$recAccId .'
                AND sys_company_id = ' .$this->sysCompanyId ;
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

        if(!$acRecAcc = AcRecAcc::findOne($recAccId)){
            throw new \yii\base\Exception('Neatrada AcRecAcc. id: ' . $recAccId);
        }
        $acRecAcc->label = implode(', ', $label);
        if(!$acRecAcc->update()){
            $error = $acRecAcc->getErrors();
            if(count($error)>0){
                throw new \yii\base\Exception('Error: ' .json_encode($error));
            }

        }

    }

    public function deleteRecAcc(int $recAccId)
    {
        /** @var AcRecAcc $recAcc */
        if (!$recAcc = AcRecAcc::findOne($recAccId)) {
            throw new \yii\db\Exception('Can not find AcRecAcc');
        }
        if ($recAcc->sys_company_id !== $this->sysCompanyId) {
            throw new \yii\db\Exception('Mismatch syscompany');
        }
        if ($recAcc->acTrans) {
            throw new \yii\db\Exception('Can not delete! Used in transactions debit');
        }
        if ($recAcc->acTrans0) {
            throw new \yii\db\Exception('Can not delete! Used in transactions debit');
        }

        foreach ($recAcc->acRecRefs as $ref) {
            $ref->delete();
        }
        $recAcc->delete();
    }
}
