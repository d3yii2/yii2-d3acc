<?php

use yii\db\Migration;

class m230523_092309_d3acc_ac_def_add_use_in_label  extends Migration {

    public function safeUp() { 
        $this->execute('
            ALTER TABLE `ac_def`
              ADD COLUMN `use_in_label` TINYINT UNSIGNED DEFAULT 1 NOT NULL AFTER `pk_field`;
            
                    
        ');
    }

    public function safeDown() {
        echo "m230523_092309_d3acc_ac_def_add_use_in_label cannot be reverted.\n";
        return false;
    }
}
