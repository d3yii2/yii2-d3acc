<?php

use yii\db\Migration;

/**
 * Class m181102_155720_alter_ac_period_balance_dim
 */
class m181102_155720_alter_ac_period_balance_dim extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $sql = "
            TRUNCATE ac_period_balance_dim;
            
            ALTER TABLE ac_period_balance_dim 
            ADD account_id SMALLINT(5) UNSIGNED NOT NULL COMMENT 'Account';
            
            ALTER TABLE ac_period_balance_dim 
            ADD CONSTRAINT fk_ac_period_balance_dim_ac_account FOREIGN KEY (account_id) REFERENCES ac_account(id);
        ";
        $this->execute($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m181102_155720_alter_ac_period_balance_dim cannot be reverted.\n";

        return false;
    }
}
