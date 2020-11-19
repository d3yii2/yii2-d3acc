<?php

namespace d3acc\models;

use DateTime;
use Exception;
use \d3acc\models\base\AcPeriod as BaseAcPeriod;

/**
 * This is the model class for table "ac_period".
 */
class AcPeriod extends BaseAcPeriod
{
    /**
     * find active period
     * @param int $sysCompanyId
     * @param int $periodType
     * @param string|bool $date date format yyy-mm-dd
     * @return AcPeriod
     */
    public static function getActivePeriod(int $sysCompanyId, int $periodType, string $date = ''): AcPeriod
    {
        $query = self::find()
            ->where([
                'period_type' => $periodType,
                'sys_company_id' => $sysCompanyId
            ])
            ->orderBy(['from' => SORT_ASC]);

        if ($date) {
            $query->andWhere(" '".$date."' >= `from` and  '".$date."' <= `to`");
        }else{
            $query->andWhere(['status' => self::STATUS_ACTIVE]);
        }
        /** @var AcPeriod $period */
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
     * @throws Exception
     */
    public function getDates(): array
    {
        $dates = [$this->from];
        $date = new DateTime($this->from);
        while($date->format('Y-m-d') !== $this->to){
            $date->modify('+1 day');
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }

    /**
     *
     * @return AcPeriod
     */
    public function getNext()
    {
        return $this->getNextPeriod()->one();
    }
    
    /**
     * 
     * @return AcPeriod
     */
    public function getPrev()
    {
         return $this->getPrevPeriod()->one();
    }

    public function delete()
    {
        if($prev = $this->getPrevPeriod()->one()){
            $prev->next_period = null;
            $prev->save();
        }
        
        return parent::delete();
    }

    /**
     * 
     * @return boolean
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}