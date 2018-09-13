<?php
/**
 * Created by PhpStorm.
 * User: aivars
 * Date: 18.12.9
 * Time: 20:59
 */

namespace d3acc\components;

use d3acc\models\AcDim;
use d3acc\models\AcTranDim;
use d3acc\models\AcTran;
use Yii;

class Dim{

    /**
     * @var  AcTran  $acc_tran
     */
    public $acc_tran;

    /**
     * @var AcTranDim $acc_tran_dims[];
     */
    public $acc_tran_dims;

    /**
     * @var AcDim $acc_dims[];
     */
    public $acc_dims;

    /**
     * Dim constructor.
     * @param d3acc\models\AcTran $accTran
     */
    public function __construct($accTran){
        $this->acc_tran = $accTran;
        $this->acc_tran_dims = [];
        $this->acc_dims = AcDim::find()->all();
    }

    /**
     * @param integer $dimId
     * @param decimal $amt
     * @param string $notes
     */
    public function addDim($dimId, $amt, $notes){
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
    public function addDimCalcLast($dimId, $notes){
        $dimAmt = 0;
        /** @var AcTranDim $tran_dim */
        foreach ($this->acc_tran_dims as $tran_dim)
        {
            $dimAmt = $dimAmt + $tran_dim->amt;
        }
        $delta = $this->acc_tran->amount - $dimAmt;
        $this->addDim($dimId, $delta, $notes);
    }

    public function save(){
        $dimAmt = 0;
        $dimGroup = 0;
        /** @var AcTranDim $tran_dim */
        foreach ($this->acc_tran_dims as $tran_dim)
        {
            $accDim = null;
            /** @var AcDim $dim */
            foreach ($this->acc_dims as $dim){
                if($dim->id == $tran_dim->dim_id)
                {
                    $accDim = $dim;
                    break;
                }
            }

            if($dimGroup != 0 && $dimGroup != $accDim->group_id){
                throw new \Exception('Different dimmension group!');
            }
            else{
                $dimGroup = $accDim->group_id;
            }
            $dimAmt = $dimAmt + $tran_dim->amt;
        }
        if($this->acc_tran->amount != $dimAmt){
            throw new \Exception('Dimmension sum not equal to transaction amaount!');
        }

        /** @var AcTranDim $tran_dim */
        foreach ($this->acc_tran_dims as $tran_dim)
        {
            $tran_dim->save();
        }
    }

}