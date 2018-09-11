<?php

use yii\db\Migration;

/**
 * Handles the creation of table `ac_tran_dim`.
 */
class m180911_180201_create_ac_tran_dim_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $sql = "
            CREATE TABLE `ac_tran_dim`(  
              `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `dim_id` SMALLINT UNSIGNED NOT NULL COMMENT 'Dimension',
              `tran_id` INT(10) UNSIGNED NOT NULL COMMENT 'Transaction',
              `amt` DECIMAL(10,2) UNSIGNED COMMENT 'Amount',
              `notes` TEXT COMMENT 'Notes',
              PRIMARY KEY (`id`),
              CONSTRAINT `fk_ac_tran` FOREIGN KEY (`tran_id`) REFERENCES `ac_tran`(`id`),
              CONSTRAINT `fk_ac_dim` FOREIGN KEY (`dim_id`) REFERENCES `ac_dim`(`id`)
            );
        ";
        $this->execute($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('ac_tran_dim');
    }
}
