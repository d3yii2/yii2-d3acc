<?php

use yii\db\Migration;

class m210427_100707_add_inex  extends Migration {

    public function safeUp() { 
        $this->execute('
            ALTER TABLE `ac_tran`   
              DROP INDEX `period_id`,
              ADD  INDEX `period_id` (`period_id`, `sys_company_id`, `debit_rec_acc_id`);
            ALTER TABLE `ac_rec_ref`   
              DROP INDEX `def_id`,
              ADD  INDEX `def_id` (`def_id`, `pk_value`, `rec_account_id`, `sys_company_id`);        
        ');
    }

    public function safeDown() {
        echo "m210427_100707_add_inex cannot be reverted.\n";
        return false;
    }
}
