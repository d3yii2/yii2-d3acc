<?php

use yii\db\Migration;

/**
 * Handles the creation of table `ac_dim`.
 */
class m180911_175101_create_ac_dim_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $sql = "
            CREATE TABLE `ac_dim`(  
                `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
                `group_id` SMALLINT(5) UNSIGNED NOT NULL COMMENT 'Ac_dim_group',
                `name` VARCHAR(50) NOT NULL COMMENT 'Name',
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_ac_dim_group` FOREIGN KEY (`group_id`) REFERENCES `ac_dim_group`(`Id`)
);
        ";

        $this->execute($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('ac_dim');
    }
}
