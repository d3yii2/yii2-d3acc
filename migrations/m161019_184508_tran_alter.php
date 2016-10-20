<?php

use yii\db\Migration;

class m161019_184508_tran_alter extends Migration
{
    public function safeUp()
    {
        $sql = "   
            ALTER TABLE `ac_tran`
              ADD COLUMN `code` VARCHAR(20) NULL  COMMENT 'Code' AFTER `amount`,
              ADD COLUMN `ref_table` VARCHAR(256) NULL  COMMENT 'RefTable' AFTER `t_datetime`,
              ADD COLUMN `ref_id` INT UNSIGNED NULL  COMMENT 'RefId' AFTER `ref_table`;
             ";
        $this->execute($sql);
    }

    public function safeDown()
    {
        echo "m161009_181508_init cannot be reverted.\n";

        return false;
    }
}
