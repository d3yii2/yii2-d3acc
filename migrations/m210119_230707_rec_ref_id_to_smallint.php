<?php

use yii\db\Migration;

class m210119_230707_rec_ref_id_to_smallint  extends Migration {

    public function safeUp() { 
        $this->execute('
            ALTER TABLE `ac_rec_ref`   
              CHANGE `id` `id` MEDIUMINT(5) UNSIGNED NOT NULL AUTO_INCREMENT;
                    
        ');
    }

    public function safeDown() {
        echo "m210119_230707_rec_ref_id_to_smallint cannot be reverted.\n";
        return false;
    }
}
