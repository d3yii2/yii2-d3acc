<?php

namespace d3acc\dictionaries;

use Yii;
use d3acc\models\AcAccount;
use yii\helpers\ArrayHelper;

class AcAccountDictionary{

    private const CACHE_KEY_LIST = 'AcAccountDictionaryList';

    public static function getCodeList(int $sysCompanyId): array
    {
        return Yii::$app->cache->getOrSet(
            self::createKey($sysCompanyId),
            static function () use($sysCompanyId) {
                return ArrayHelper::map(
                    AcAccount::find()
                    ->select([
                        'id' => 'id',
                        'name' => 'code',
                    ])
                    ->where(['sys_company_id' => $sysCompanyId])
                    ->orderBy([
                        'code' => SORT_ASC,
                    ])
                    ->asArray()
                    ->all()
                ,
                'id',
                'name'
                );
            }
        );
    }

    public static function getIdByCode(int $sysCompanyId,string $code)
    {
        return array_flip(self::getCodeList($sysCompanyId))[$code]??false;
    }

    public static function getCodeById(int $sysCompanyId, int $accountId)
    {
        return self::getCodeList($sysCompanyId)[$accountId]??false;
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
        return self::CACHE_KEY_LIST . '-' . $sysCompanyId;
    }
}
