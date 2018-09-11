<?php

use yii\db\Migration;

/**
 * Handles the creation of table `ac_dim_group`.
 */
class m180911_172355_create_ac_dim_group_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $sql = "
            CREATE TABLE `ac_dim_group`(  
                `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(50) NOT NULL COMMENT 'Name',
            PRIMARY KEY (`id`)
         )";
        $this->execute($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('ac_dim_group');
    }
}
