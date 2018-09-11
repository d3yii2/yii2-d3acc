<?php
/**
 * Created by PhpStorm.
 * User: Baltix
 * Date: 21.03.2018.
 * Time: 19:05
 */

namespace d3acc\components;

use d3acc\models\AcTranDim;
use Yii;
use d3acc\models\AcTran;
use d3acc\models\AcRecAcc;
use d3acc\models\AcPeriod;


class Transaction
{
    /**
     * @var \d3acc\models\AcRecAcc $debitAcc
     */
    public $debitAcc;

    /**
     * @var \d3acc\models\AcRecAcc $creditAcc
     */
    public $creditAcc;

    /**
     * @var decimal $amount
     */
    public $amount;

    /**
     * @var date $date
     */
    public $date;

    /**
     * @var int $periodType
     */
    public $periodType;

    /**
     * @var \d3acc\models\AcPeriod $period
     */
    public $period;

    /**
     * @var string $code transaction code
     */
    public $code;

    /**
     * @var date $tranTime transaction date
     */
    public $tranTime;

    /**
     * @var  \d3acc\models\AcTran $model
     */
    public $model;

    public function __construct()
    {
        $this->code = false;
        $this->tranTime = false;
    }

    public function registre()
    {
        $this->model = AcTran::registre(
            $this->debitAcc,
            $this->creditAcc,
            $this->amount,
            $this->date,
            $this->periodType,
            $this->code,
            $this->tranTime
        );
        return $this->model;
    }

    /**
     * @param integer $dimId
     * @param decimal $amt
     */
    public function registerDim($dimId, $amt){
        return AcTranDim::register($this->model, $dimId, $amt);
    }

}