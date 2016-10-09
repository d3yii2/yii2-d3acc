<?php

use yii\db\Migration;

class m161009_181508_init extends Migration
{
    public function up()
    {
        $sql = "   
            SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
            SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
            SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
            SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0;

            CREATE TABLE `ac_account` (
              `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
              `code` char(10) CHARACTER SET latin1 NOT NULL COMMENT 'Code',
              `name` varchar(50) CHARACTER SET latin1 NOT NULL COMMENT 'Name',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


            CREATE TABLE `ac_def` (
              `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
              `account_id` smallint(5) unsigned NOT NULL COMMENT 'Account',
              `table` varchar(100) DEFAULT NULL COMMENT 'Table',
              `pk_field` varchar(100) DEFAULT NULL COMMENT 'Primary key field',
              PRIMARY KEY (`id`),
              KEY `account_id` (`account_id`),
              CONSTRAINT `ac_def_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `ac_account` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


            CREATE TABLE `ac_period` (
              `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
              `period_type` tinyint(3) unsigned NOT NULL COMMENT 'Type',
              `from` date NOT NULL COMMENT 'From',
              `to` date NOT NULL COMMENT 'To',
              `status` enum('Planned','Active','Closed') DEFAULT NULL COMMENT 'Status',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


            CREATE TABLE `ac_rec_acc` (
              `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
              `account_id` smallint(5) unsigned NOT NULL COMMENT 'Account',
              `label` varchar(100) DEFAULT NULL COMMENT 'Label',
              PRIMARY KEY (`id`),
              KEY `account_id` (`account_id`),
              CONSTRAINT `ac_rec_acc_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `ac_account` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


            CREATE TABLE `ac_rec_ref` (
              `id` smallint(5) NOT NULL AUTO_INCREMENT,
              `def_id` smallint(5) unsigned NOT NULL,
              `rec_account_id` smallint(5) unsigned NOT NULL,
              `pk_value` bigint(20) unsigned NOT NULL,
              PRIMARY KEY (`id`),
              KEY `rec_account_id` (`rec_account_id`),
              KEY `def_id` (`def_id`),
              CONSTRAINT `ac_rec_ref_ibfk_3` FOREIGN KEY (`def_id`) REFERENCES `ac_def` (`id`),
              CONSTRAINT `ac_rec_ref_ibfk_2` FOREIGN KEY (`rec_account_id`) REFERENCES `ac_rec_acc` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

            CREATE TABLE `ac_tran` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `period_id` smallint(5) unsigned NOT NULL COMMENT 'Period',
              `accounting_date` date NOT NULL COMMENT 'Accountig Date',
              `debit_rec_acc_id` smallint(5) unsigned NOT NULL COMMENT 'Debit account',
              `credit_rec_acc_id` smallint(5) unsigned NOT NULL COMMENT 'Credit account',
              `amount` decimal(10,2) unsigned NOT NULL COMMENT 'Amount',
              `notes` text COMMENT 'Notes',
              `t_user_id` smallint(5) unsigned NOT NULL COMMENT 'User',
              `t_datetime` datetime NOT NULL COMMENT 'Date',
              PRIMARY KEY (`id`),
              KEY `debit_rec_acc_id` (`debit_rec_acc_id`),
              KEY `credit_rec_acc_id` (`credit_rec_acc_id`),
              KEY `period_id` (`period_id`),
              CONSTRAINT `ac_tran_ibfk_3` FOREIGN KEY (`period_id`) REFERENCES `ac_period` (`id`),
              CONSTRAINT `ac_tran_ibfk_1` FOREIGN KEY (`debit_rec_acc_id`) REFERENCES `ac_rec_acc` (`id`),
              CONSTRAINT `ac_tran_ibfk_2` FOREIGN KEY (`credit_rec_acc_id`) REFERENCES `ac_rec_acc` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

            SET SQL_MODE=@OLD_SQL_MODE;
            SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
            SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
            SET SQL_NOTES=@OLD_SQL_NOTES;
             ";
        $this->execute($sql);
    }

    public function down()
    {
        echo "m161009_181508_init cannot be reverted.\n";

        return false;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
