<?php

namespace d3acc\models;

use d3acc\models\base\AcRecTable as BaseAcRecTable;
use d3system\exceptions\D3ActiveRecordException;

/**
 * This is the model class for table "ac_rec_table".
 */
class AcRecTable extends BaseAcRecTable
{
    /**
     * @throws D3ActiveRecordException
     */
    public static function findIdOrCreate(string $name): int
    {
        if ($id = self::find()
            ->select('id')
            ->where(['name' => $name])
            ->scalar()
        ) {
            return $id;
        }
        $model = new self();
        $model->name = $name;
        if (!$model->save()) {
            throw new D3ActiveRecordException($model);
        }

        return $model->id;
    }

    public static function findNameById(int $id): string
    {
        return self::find()
            ->select('name')
            ->where(['id' => $id])
            ->scalar();
    }
}

