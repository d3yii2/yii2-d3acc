<?php

namespace d3acc\components;

use d3acc\models\AcDim;
use d3acc\models\AcTranDim;
use d3acc\models\AcTran;

class Dim{

    /**
     * @var  AcTran  $acc_tran
     * Transaction for virtual dimensions
     */
    public $acc_tran;

    /**
     * @var AcTranDim $acc_tran_dims[];
     * Array of transaction dimension amounts
     */
    public $acc_tran_dims = [];

    /**
     * @var AcDim $acc_dims[];
     *  All available virtual dimensions
     */
    public $acc_dims;

    /**
     * Dim constructor.
     * @param AcTran $accTran
     */
    public function __construct(AcTran $accTran){
        $this->acc_tran = $accTran;
        $this->acc_tran_dims = [];
    }

    /**
     * @param integer $acDimId
     * @return AcDim
     * @throws \Exception
     */
    private function getAccDim($acDimId): AcDim
    {
        if(!$this->acc_dims){
            foreach(AcDim::find()->all() as $acDim) {
                $this->acc_dims[$acDim->id] = $acDim;
            }
            if(!$this->acc_dims) {
                throw new \Exception('No virtual dimensions found!');
            }
        }
        return $this->acc_dims[$acDimId];
    }

    /**
     * @param integer $dimId
     * @param float $amt
     * @param string $notes
     */
    public function addDim($dimId, $amt, $notes)
    {
        $tranDim = new AcTranDim();
        $tranDim->dim_id = $dimId;
        $tranDim->tran_id = $this->acc_tran->id;
        $tranDim->amt = $amt;
        $tranDim->notes = $notes;
        $this->acc_tran_dims[] = $tranDim;
    }

    /**
     * @param integer $dimId
     * @param string $notes
     */
    public function addDimCalcLast($dimId, $notes = null){
        $dimAmt = 0;
        /** @var AcTranDim $tran_dim */
        foreach ($this->acc_tran_dims as $tran_dim)
        {
            $dimAmt += (float)$tran_dim->amt;
        }
        $delta = $this->acc_tran->amount - $dimAmt;
        $this->addDim($dimId, $delta, $notes);
    }

    /**
     * @throws \Exception
     */
    public function save(){
        $dimAmt = 0;
        $dimGroup = false;

        /** @var AcTranDim $tran_dim */
        foreach ($this->acc_tran_dims as $tran_dim) {
            /**
             * First check if all transaction dimensions has same dimension group
             */
            if(count($this->acc_tran_dims) > 1) {
                $accDim = $this->getAccDim($tran_dim->dim_id);
                if ($dimGroup && $dimGroup !== $accDim->group_id) {
                    throw new \Exception('Different dimmension group!');
                }
                $dimGroup = $accDim->group_id;
            }
            $dimAmt += $tran_dim->amt;
        }

        /**
         * total must be equal transaction amount
         */
        if((float)$this->acc_tran->amount !== $dimAmt){
            throw new \Exception('Dimmension sum not equal to transaction amount!');
        }


        /** @var AcTranDim $tran_dim */
        foreach ($this->acc_tran_dims as $tran_dim)
        {
            $tran_dim->save();
        }
    }

}