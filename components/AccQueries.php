<?php

namespace d3acc\components;

use d3acc\models\AcAccount;
use d3acc\models\AcDef;
use d3acc\models\AcRecAcc;
use d3acc\models\AcRecTable;
use yii\base\Exception;
use yii\db\ActiveQuery;

/**
 *
 */
class AccQueries
{
    /**
     * Use for getting account AcRecAcc data
     *  - joined with  all ac_rec_ref
     *  - can add in select all ref values,
     *  - can filter by acRecAcc.id
     *  - can filter ref values
     * @param int $accId account_id
     * @param int $sysCompanyId
     * @param array{id: int|int[], [ac_def.table_name]: int|int[], [ac_def.code]: int|int[],} $ref use as filter. id filter by ac_rec_acc.id, [ac_def.table_name] or [ac_def.table_name] filter by value
     * @param bool $addSelectPkValue to select add ref fields
     * @param bool $groupByPkValue group by ref fields
     * @return ActiveQuery in select ref fields add as [ac_def.table_name].pk_value or [ac_def.code].pk_value
     * @throws Exception
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
        /** @var AcDef $acDef */
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
        if ($groupByPkValue) {
            $findRecRef->addGroupBy([
                '`ac_rec_acc`.`currency_id`'
            ]);
        }
        return $findRecRef;
    }

    /**
     * create query for list account all ac_rec_table values for required acDef.code
     *
     * @param int $accountId
     * @param string $defCode
     * @return ActiveQuery
     */
    public static function getAccountRecTableRecords(
        int    $accountId,
        string $defCode
    ): ActiveQuery
    {
        return AcRecTable::find()
            ->innerJoin(
                'ac_rec_ref',
                '`ac_rec_ref`.`pk_value` = `ac_rec_table`.`id`'
            )
            ->innerJoin(
                'ac_def',
                '`ac_rec_ref`.`def_id` = `ac_def`.`id`'
            )
            ->andWhere([
                'ac_def.code' => $defCode,
                'ac_def.account_id' => $accountId
            ])
            ->orderBy(['ac_rec_table.name' => SORT_ASC]);
    }
}
