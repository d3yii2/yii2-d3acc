<?php

use yii\db\Migration;

class m200923_100707_add_sys_company_id  extends Migration {

    public function safeUp() {

        $this->execute('
            ALTER TABLE `ac_account`   
              ADD COLUMN `sys_company_id` SMALLINT UNSIGNED DEFAULT 0  NOT NULL AFTER `id`;
        ');
        $this->execute('
            ALTER TABLE `ac_def`   
              ADD COLUMN `sys_company_id` SMALLINT UNSIGNED DEFAULT 0  NOT NULL AFTER `id`,
              ADD COLUMN `code` CHAR(20) CHARSET latin1 NULL AFTER `sys_company_id`;

        ');
        $this->execute('
            ALTER TABLE `ac_dim`   
              ADD COLUMN `sys_company_id` SMALLINT UNSIGNED DEFAULT 0  NOT NULL AFTER `id`;
        ');
        $this->execute('
            ALTER TABLE `ac_dim_group`   
              ADD COLUMN `sys_company_id` SMALLINT UNSIGNED DEFAULT 0  NOT NULL AFTER `id`;
        ');

        $this->execute('
            ALTER TABLE `ac_period`   
              ADD COLUMN `sys_company_id` SMALLINT UNSIGNED DEFAULT 0  NOT NULL AFTER `id`;
        ');

        $this->execute('
            ALTER TABLE `ac_period_balance`   
              ADD COLUMN `sys_company_id` SMALLINT UNSIGNED DEFAULT 0  NOT NULL AFTER `id`;
        ');

        $this->execute('
            ALTER TABLE `ac_period_balance_dim`   
              ADD COLUMN `sys_company_id` SMALLINT UNSIGNED DEFAULT 0  NOT NULL AFTER `id`;
        ');

        $this->execute('
            ALTER TABLE `ac_rec_acc`   
              ADD COLUMN `sys_company_id` SMALLINT UNSIGNED DEFAULT 0  NOT NULL AFTER `id`;
        ');

        $this->execute('
            ALTER TABLE `ac_rec_ref`   
              ADD COLUMN `sys_company_id` SMALLINT UNSIGNED DEFAULT 0  NOT NULL AFTER `id`;
        ');

        $this->execute('
            ALTER TABLE `ac_tran`   
              ADD COLUMN `sys_company_id` SMALLINT UNSIGNED DEFAULT 0  NOT NULL AFTER `id`;
        ');

    }

    public function safeDown() {
        echo "m200923_100707_add_sys_company_id cannot be reverted.\n";
        return false;
    }
}
