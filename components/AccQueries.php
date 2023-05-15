<?php

namespace d3acc\components;

use d3acc\models\AcAccount;
use d3acc\models\AcRecAcc;
use yii\base\Exception;
use yii\db\ActiveQuery;

class AccQueries
{
    /**
     * @throws \yii\base\Exception
     */
    public static function joinRefs(
        int   $accId,
        int   $sysCompanyId,
        array $ref = [],
        bool  $addSelectPkValue = false,
        bool  $groupByPkValue = false
    ): ActiveQuery
    {
        if (!$acc = AcAccount::findOne($accId)) {
            throw new Exception('Illegal $accId: ' . $accId);
        }

        /**
         * search account
         */
        $findRecRef = AcRecAcc::find()
            ->select('ac_rec_acc.*')
            ->where([
                'ac_rec_acc.account_id' => $accId,
                'ac_rec_acc.sys_company_id' => $sysCompanyId
            ]);
        if (isset($ref['id'])) {
            $findRecRef->andWhere([
                'ac_rec_acc.id' => $ref['id']
            ]);
        }
        $i = 0;
        /** @var \d3acc\models\AcDef $acDef */
        foreach ($acc->getAcDefs()->orderBy(['id' => SORT_ASC])->all() as $acDef) {
            $pkValue = null;
            if ($ref) {
                if ($acDef->code) {
                    $pkValue = $ref[$acDef->code] ?? $ref[$acDef->table] ?? null;
                } else {
                    $pkValue = $ref[$acDef->table] ?? null;
                }
                if ($pkValue === null && !$addSelectPkValue && !$groupByPkValue) {
                    continue;
                }
            }
            $i++;
            $tableAsName = '`r' . $i . '_' . $acDef->table . '`';
            if ($addSelectPkValue) {
                $fieldPrefix = $acDef->table;
                if ($acDef->code) {
                    $fieldPrefix = $acDef->code;
                }
                $findRecRef->addSelect([
                    $fieldPrefix . '_pk_value' => $tableAsName . '.`pk_value`'
                ]);
            }
            $findRecRef->join('INNER JOIN', '`ac_rec_ref` as ' . $tableAsName,
                '`ac_rec_acc`.`id` = ' . $tableAsName . '.`rec_account_id`');
            $where = [
                $tableAsName . '.`def_id`' => $acDef->id,
            ];
            if ($pkValue) {
                $where[$tableAsName . '.`pk_value`'] = $pkValue;
            }
            $findRecRef->andWhere($where);

            if ($groupByPkValue) {
                $findRecRef->addGroupBy([
                    $tableAsName . '.`pk_value`'
                ]);
            }
        }
        return $findRecRef;
    }
}
