<?php

use yii\db\Migration;

/**
* Class m190413_155446_rec_ref_add_index*/
class m190413_155446_rec_ref_add_index extends Migration
{
    /**
    * {@inheritdoc}
    */
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE `ac_rec_ref`   
              DROP INDEX `def_id`,
              ADD  INDEX `def_id` (`def_id`, `pk_value`, `rec_account_id`);
        ');
    }

    public function safeDown()
    {
        echo "m190413_155446_rec_ref_add_index cannot be reverted.\n";
        return false;
    }

}