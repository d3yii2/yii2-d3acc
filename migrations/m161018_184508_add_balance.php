<?php

use yii\db\Migration;

class m161018_184508_add_balance extends Migration
{
    public function safeUp()
    {
        $sql = "   
            SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
            SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
            SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
            SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0;

            ALTER TABLE `ac_period`
                ADD COLUMN `prev_period` smallint(5) unsigned   NULL COMMENT 'Previous period' after `status` ,
                ADD COLUMN `next_period` smallint(5) unsigned   NULL COMMENT 'Next Period' after `prev_period` ,
                ADD KEY `next_period`(`next_period`) ,
                ADD KEY `prev_period`(`prev_period`) ;
            ALTER TABLE `ac_period`
                ADD CONSTRAINT `ac_period_ibfk_1`
                FOREIGN KEY (`prev_period`) REFERENCES `ac_period` (`id`) ,
                ADD CONSTRAINT `ac_period_ibfk_2`
                FOREIGN KEY (`next_period`) REFERENCES `ac_period` (`id`) ;


            /* Create table in target */
            CREATE TABLE `ac_period_balance`(
                `id` smallint(5) unsigned NOT NULL  auto_increment ,
                `period_id` smallint(5) unsigned NOT NULL  COMMENT 'Period' ,
                `rec_acc_id` smallint(5) unsigned NOT NULL  COMMENT 'Account' ,
                `amount` decimal(12,2) NOT NULL  COMMENT 'Amount' ,
                PRIMARY KEY (`id`) ,
                KEY `period_id`(`period_id`) ,
                KEY `rec_acc_id`(`rec_acc_id`) ,
                CONSTRAINT `ac_period_balance_ibfk_2`
                FOREIGN KEY (`rec_acc_id`) REFERENCES `ac_rec_acc` (`id`) ,
                CONSTRAINT `ac_period_balance_ibfk_1`
                FOREIGN KEY (`period_id`) REFERENCES `ac_period` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET='utf8' COLLATE='utf8_general_ci';
            SET SQL_MODE=@OLD_SQL_MODE;
            SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
            SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
            SET SQL_NOTES=@OLD_SQL_NOTES;
             ";
        $this->execute($sql);
    }

    public function safeDown()
    {
        echo "m161009_181508_init cannot be reverted.\n";

        return false;
    }
}
