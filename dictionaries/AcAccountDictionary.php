<?php

namespace d3acc\dictionaries;

use Yii;
use d3acc\models\AcAccount;
use yii\helpers\ArrayHelper;

class AcAccountDictionary{

    private const CACHE_KEY_LIST = 'AcAccountDictionaryListC';

    public static function getCodeList(int $sysCompanyId, string $translationCategory = null): array
    {
        $isCached = true;
        if (!$list = Yii::$app->cache->get(self::createKey($sysCompanyId))) {
            $isCached = false;
            $list = [
                'base' => ArrayHelper::map(
                AcAccount::find()
                    ->select([
                        'id' => 'id',
                        'name' => 'code',
                    ])
                    ->where(['sys_company_id' => $sysCompanyId])
                    ->orderBy(['code' => SORT_ASC])
                    ->asArray()
                    ->all(),
                    'id',
                    'name'
                )
            ];
        }
        if ($translationCategory && !isset($list[$translationCategory])) {
            $isCached = false;
            foreach($list['base'] as $id => $name) {
                $list[$translationCategory][$id] = Yii::t($translationCategory,$name);
            }
        }
        if (!$isCached) {
            Yii::$app->cache->set(self::createKey($sysCompanyId),$list);
        }
        if ($translationCategory) {
            return $list[$translationCategory];
        }

        return $list['base'];
    }
    public static function getNameList(int $sysCompanyId, string $translationCategory = null): array
    {
        $isCached = true;
        if (!$list = Yii::$app->cache->get(self::createNameKey($sysCompanyId))) {
            $isCached = false;
            $list = [
                'base' => ArrayHelper::map(
                AcAccount::find()
                    ->select([
                        'id' => 'id',
                        'name' => 'name',
                    ])
                    ->where(['sys_company_id' => $sysCompanyId])
                    ->orderBy(['code' => SORT_ASC])
                    ->asArray()
                    ->all(),
                    'id',
                    'name'
                )
            ];
        }
        if ($translationCategory && !isset($list[$translationCategory])) {
            $isCached = false;
            foreach($list['base'] as $id => $name) {
                $list[$translationCategory][$id] = Yii::t($translationCategory,$name);
            }
        }
        if (!$isCached) {
            Yii::$app->cache->set(self::createNameKey($sysCompanyId),$list);
        }
        if ($translationCategory) {
            return $list[$translationCategory];
        }

        return $list['base'];
    }

    public static function getIdByCode(int $sysCompanyId,string $code)
    {
        return array_flip(self::getCodeList($sysCompanyId))[$code]??false;
    }

    public static function getCodeById(int $sysCompanyId, int $accountId)
    {
        return self::getCodeList($sysCompanyId)[$accountId]??false;
    }

    public static function getNameById(int $sysCompanyId, int $accountId, string $translationCategory = null)
    {
        return self::getNameList($sysCompanyId, $translationCategory)[$accountId]??false;
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
            Yii::$app->cache->delete(self::createNameKey($sysCompanyId));
        }
    }

    private static function createKey(int $sysCompanyId): string
    {
        return self::CACHE_KEY_LIST . '-' . $sysCompanyId;
    }

    private static function createNameKey(int $sysCompanyId): string
    {
        return self::CACHE_KEY_LIST . '-name-' . $sysCompanyId;
    }
}
