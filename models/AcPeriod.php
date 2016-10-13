<?php

namespace d3acc\models;

use Yii;
use \d3acc\models\base\AcPeriod as BaseAcPeriod;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "ac_period".
 */
class AcPeriod extends BaseAcPeriod
{
    /**
     * find active period
     * @param int $periodType
     * @param string $date date format yyy-mm-dd
     * @return \d3acc\models\base\AcPeriod
     */
    public static function getActivePeriod($periodType, $date = false)
    {
        $query = self::find()
            ->where(['period_type' => $periodType])
            ->andWhere(['status' => self::STATUS_ACTIVE]);
        if ($date) {
            $query->andWhere(" '".$date."' >= `from` and  '".$date."' <= `to`");
        }

        return $query->one();
    }

//    public function closePeriod($nextPeriodTo){
//        if($this->status != self::STATUS_ACTIVE){
//            throw new \Exception('Can not close unactive period');
//        }
//        $connection = \Yii::$app->db;
//        $transaction = $connection->beginTransaction();
//        $this->status = self::STATUS_CLOSED;
//        $this->save();
//
//        $model = new self();
//        $model->period_type = $this->period_type;
//        $model->from = date($this->to, strtotime("+1 days"));
//        $model->to = $to;
//        $model->status = self::STATUS_PLANNED;
//        if(!$model->save()){
//            $error = json_encode($model->getErrors());
//            $transaction->rollback();
//            throw new \Exception('Can not close period: ' . $error);
//        }
//        $transaction->commit();
//    }

    /**
     * Get list of period dates
     * @return array 
     */
    public function getDates(){
        $dates = [$this->from];
        $date = new \DateTime($this->from);
        while($date->format('Y-m-d') != $this->to){
            $date->modify('+1 day');
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }

    public function getNext()
    {
         return self::find()
            ->where(['period_type' => $this->period_type])
            ->andWhere("`from` > '" .$this->from."' ")
            ->orderBy(['from' => SORT_ASC])
             ->one();
    }
    
    public function getPrev()
    {
         return self::find()
            ->where(['period_type' => $this->period_type])
            ->andWhere("`from` < '" .$this->from."' ")
            ->orderBy(['from' => SORT_DESC])
             ->one();
    }
}