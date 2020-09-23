<?php
// This class was automatically generated by a giiant build task
// You should not change it manually as it will be overwritten on next build

namespace d3acc\models\base;

use Yii;


/**
 * This is the base-model class for table "ac_dim".
 *
 * @property integer $id
 * @property integer $sys_company_id
 * @property integer $group_id
 * @property string $name
 *
 * @property \d3acc\models\AcPeriodBalanceDim[] $acPeriodBalanceDims
 * @property \d3acc\models\AcTranDim[] $acTranDims
 * @property \d3acc\models\AcDimGroup $group
 * @property string $aliasModel
 */
abstract class AcDim extends \yii\db\ActiveRecord
{



    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return 'ac_dim';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            'required' => [['group_id', 'name'], 'required'],
            'smallint Unsigned' => [['id','sys_company_id','group_id'],'integer' ,'min' => 0 ,'max' => 65535],
            [['name'], 'string', 'max' => 50],
            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => \d3acc\models\AcDimGroup::className(), 'targetAttribute' => ['group_id' => 'id']]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('d3acc', 'ID'),
            'sys_company_id' => Yii::t('d3acc', 'Sys Company ID'),
            'group_id' => Yii::t('d3acc', 'Ac_dim_group'),
            'name' => Yii::t('d3acc', 'Name'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints(): array
    {
        return array_merge(parent::attributeHints(), [
            'group_id' => Yii::t('d3acc', 'Ac_dim_group'),
            'name' => Yii::t('d3acc', 'Name'),
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAcPeriodBalanceDims()
    {
        return $this->hasMany(\d3acc\models\AcPeriodBalanceDim::className(), ['dim_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAcTranDims()
    {
        return $this->hasMany(\d3acc\models\AcTranDim::className(), ['dim_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(\d3acc\models\AcDimGroup::className(), ['id' => 'group_id']);
    }

}
