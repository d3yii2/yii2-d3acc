<?php

use yii\db\Migration;

/**
 * Class m180722_194651_tran_alter
 */
class m180722_194651_tran_alter extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->execute('
            ALTER TABLE `ac_tran` DROP INDEX `period_id`, ADD INDEX `period_id` (`period_id`, `debit_rec_acc_id`); 
        ');

        $this->execute('
            ALTER TABLE `ac_rec_ref` DROP INDEX `def_id`, ADD INDEX `def_id` (`def_id`, `pk_value`);  
        ');

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m180722_194651_tran_alter cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180722_194651_tran_alter cannot be reverted.\n";

        return false;
    }
    */
}
