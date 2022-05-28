<?php

use yii\db\Migration;

class m220528_081204_d3acc_ac_tran_optimise_index  extends Migration {

    public function safeUp() { 
        $this->execute('
            ALTER TABLE `ac_tran`
              DROP INDEX `period_id`,
              ADD KEY `period_id` (
                `period_id`,
                `sys_company_id`,
                `debit_rec_acc_id`,
                `amount`
              );        
        ');
    }

    public function safeDown() {
        echo "m220528_081204_d3acc_ac_tran_optimise_index cannot be reverted.\n";
        return false;
    }
}
