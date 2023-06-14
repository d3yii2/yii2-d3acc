<?php

use yii\db\Migration;

class m230607_100039_d3acc_rec_acc_add_currency_id  extends Migration {

    public function safeUp() { 
        $this->execute('
            ALTER TABLE `ac_rec_acc`
              ADD COLUMN `currency_id` TINYINT UNSIGNED DEFAULT 0 NOT NULL AFTER `account_id`;
            
                    
        ');
    }

    public function safeDown() {
        echo "m230607_100039_d3acc_rec_acc_add_currency_id cannot be reverted.\n";
        return false;
    }
}
