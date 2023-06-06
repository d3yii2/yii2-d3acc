<?php

namespace d3acc\dictionaries;

use d3acc\models\AcAccount;
use Yii;
use d3acc\models\AcDef;
use yii\helpers\ArrayHelper;

class AcDefDictionary{

    private const CACHE_KEY_LIST = 'AcDefDictionaryList';


    public static function getCodeList(int $sysCompanyId): array
    {
        return Yii::$app->cache->getOrSet(
            self::createKey($sysCompanyId),
            static function () use($sysCompanyId) {
                $acDefs = AcDef::find()
                    ->select([
                        'id' => 'id',
                        'account_id',
                        'code',
                    ])
                    ->where(['sys_company_id' => $sysCompanyId])
                    ->andWhere('NOT code IS NULL')
                    ->orderBy([
                        'code' => SORT_ASC,
                    ])
                    ->asArray()
                    ->all();
                $list = [];
                foreach ($acDefs as $acDef) {
                    $list[$acDef['account_id']][$acDef['code']] = $acDef['id'];
                }
                return $list;
            }
        );
    }
    public static function getIdByCode(
        int $sysCompanyId,
        int $accId,
        string $code
    )
    {
        return self::getCodeList($sysCompanyId)[$accId][$code]??false;
    }

    public static function clearCache(): void
    {
        foreach(AcAccount::find()
                    ->distinct()
                    ->select('sys_company_id')
                    ->column()
                as $sysCompanyId
        ) {
            Yii::$app->cache->delete(self::createKey($sysCompanyId));
        }
    }

    private static function createKey(int $sysCompanyId): string
    {
        return self::CACHE_KEY_LIST . '=' . $sysCompanyId;
    }
}
