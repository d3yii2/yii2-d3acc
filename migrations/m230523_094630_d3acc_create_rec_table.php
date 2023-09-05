<?php

use yii\db\Migration;

class m230523_094630_d3acc_create_rec_table  extends Migration {

    public function safeUp() {

        $this->execute("
            DROP TABLE  IF EXISTS `ac_rec_table`; 
        ");
        $this->execute('
            CREATE TABLE `ac_rec_table` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` VARCHAR (255) CHARSET utf8 NOT NULL,
              PRIMARY KEY (`id`)
            );        
        ');

        $this->execute('
            ALTER TABLE `ac_rec_table`
              ADD INDEX (`name` (6));
        ');

    }

    public function safeDown() {
        $this->execute("
            DROP TABLE  IF EXISTS `ac_rec_table`; 
        ");
        return true;
    }
}
