<?php

use yii\db\Migration;

/**
 * Class m190101_193802_period_balance_alter
 */
class m190101_193802_period_balance_alter extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('
            ALTER TABLE `ac_period_balance`   
              CHANGE `id` `id` MEDIUMINT(5) UNSIGNED NOT NULL AUTO_INCREMENT;
        ');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190101_193802_period_balance_alter cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190101_193802_period_balance_alter cannot be reverted.\n";

        return false;
    }
    */
}
