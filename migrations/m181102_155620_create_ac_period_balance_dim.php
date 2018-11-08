<?php

use yii\db\Migration;

/**
 * Class m181102_155620_create_ac_period_balance_dim
 */
class m181102_155620_create_ac_period_balance_dim extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $sql = '
            DROP TABLE IF EXISTS`ac_period_balance_dim`;
        ';
        $this->execute($sql);
        $sql = "
            CREATE TABLE `ac_period_balance_dim`(  
              `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `period_id` smallint(5) unsigned NOT NULL  COMMENT 'Period' ,
              `dim_id` SMALLINT UNSIGNED NOT NULL COMMENT 'Dimension',
              `amount` decimal(12,2) NOT NULL  COMMENT 'Amount',
              PRIMARY KEY (`id`),
              CONSTRAINT `fk_ac_period_balance_dim_ac_period` FOREIGN KEY (`period_id`) REFERENCES `ac_period`(`id`),
              CONSTRAINT `fk_ac_period_balance_dim_ac_dim` FOREIGN KEY (`dim_id`) REFERENCES `ac_dim`(`id`)
            );
        ";
        $this->execute($sql);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m181102_155620_create_ac_period_balance_dim cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181102_155620_create_ac_period_balance_dim cannot be reverted.\n";

        return false;
    }
    */
}
