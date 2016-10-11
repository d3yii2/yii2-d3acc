<?php
// This class was automatically generated by a giiant build task
// You should not change it manually as it will be overwritten on next build

namespace d3acc\models\base;

use Yii;

/**
 * This is the base-model class for table "ac_account".
 *
 * @property integer $id
 * @property string $code
 * @property string $name
 *
 * @property \d3acc\models\AcDef[] $acDefs
 * @property \d3acc\models\AcRecAcc[] $acRecAccs
 * @property string $aliasModel
 */
abstract class AcAccount extends \yii\db\ActiveRecord
{



    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ac_account';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code', 'name'], 'required'],
            [['code'], 'string', 'max' => 10],
            [['name'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('poker', 'ID'),
            'code' => Yii::t('poker', 'Code'),
            'name' => Yii::t('poker', 'Name'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'code' => Yii::t('poker', 'Code'),
            'name' => Yii::t('poker', 'Name'),
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAcDefs()
    {
        return $this->hasMany(\d3acc\models\AcDef::className(), ['account_id' => 'id'])->inverseOf('account');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAcRecAccs()
    {
        return $this->hasMany(\d3acc\models\AcRecAcc::className(), ['account_id' => 'id'])->inverseOf('acount');
    }




}