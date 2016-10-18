<?php

namespace d3acc;

class Module extends \yii\base\Module
{
    public $tableModels = [];
    
    public function getLabel(){
        return \Yii::t('d3acc', 'D3Acc');
    }
}
