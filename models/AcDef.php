<?php

namespace d3acc\models;

use d3acc\dictionaries\AcDefDictionary;
use d3acc\models\base\AcDef as BaseAcDef;


/**
 * This is the model class for table "ac_def".
 */
class AcDef extends BaseAcDef
{

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        AcDefDictionary::clearCache();
    }

    public function afterDelete()
    {
        parent::afterDelete();
        AcDefDictionary::clearCache();
    }


}
