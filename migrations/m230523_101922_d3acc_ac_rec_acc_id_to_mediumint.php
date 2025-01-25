<?php

use yii\db\Migration;
use yii\db\Query;

class m230523_101922_d3acc_ac_rec_acc_id_to_mediumint  extends Migration {

    public function safeUp() {
        $migrationExists = (new Query())
            ->from('{{%migration}}')
            ->where(['version' => 'm241215_172602_d3acc_rec_acc_id_to_mediumint'])
            ->exists();
        if ($migrationExists) {
            echo "Migration already executed. Skipping...\n";
            return true;
        }

        $this->execute('              
            ALTER TABLE `ac_tran`
              DROP FOREIGN KEY `ac_tran_ibfk_1`,
              DROP FOREIGN KEY `ac_tran_ibfk_2`,
              CHANGE `debit_rec_acc_id` `debit_rec_acc_id` MEDIUMINT (5) UNSIGNED NOT NULL COMMENT \'Debit account\',
              CHANGE `credit_rec_acc_id` `credit_rec_acc_id` MEDIUMINT (5) UNSIGNED NOT NULL COMMENT \'Credit account\';
        ');
        $this->execute('              
            
            ALTER TABLE `ac_period_balance`
              DROP FOREIGN KEY `ac_period_balance_ibfk_2`,
              CHANGE `rec_acc_id` `rec_acc_id` MEDIUMINT (5) UNSIGNED NOT NULL COMMENT \'Account\';
        ');
        $this->execute('              
            ALTER TABLE `ac_rec_ref`
              DROP FOREIGN KEY `ac_rec_ref_ibfk_2`,
              CHANGE `rec_account_id` `rec_account_id` MEDIUMINT (5) UNSIGNED NOT NULL;
        ');
        $this->execute('
            ALTER TABLE `ac_rec_acc`
              CHANGE `id` `id` MEDIUMINT (5) UNSIGNED NOT NULL AUTO_INCREMENT;
        ');
        $this->execute('
            ALTER TABLE `ac_tran`
              ADD CONSTRAINT `ac_tran_ibfk_debit` FOREIGN KEY (`debit_rec_acc_id`) REFERENCES `ac_rec_acc` (`id`),
              ADD CONSTRAINT `ac_tran_ibfk_credit` FOREIGN KEY (`credit_rec_acc_id`) REFERENCES `ac_rec_acc` (`id`);
        ');
        $this->execute('
            ALTER TABLE `ac_tran`
              ADD CONSTRAINT `ac_tran_ibfk_period` FOREIGN KEY (`period_id`) REFERENCES `ac_period` (`id`),
              DROP FOREIGN KEY `ac_tran_ibfk_3`;
        ');
        $this->execute('
            ALTER TABLE `ac_period_balance`
              ADD CONSTRAINT `ac_period_balance_ibfk_ac_rec_acc` FOREIGN KEY (`rec_acc_id`) REFERENCES `ac_rec_acc` (`id`);
        ');
        $this->execute('
            ALTER TABLE `ac_rec_ref`
              ADD CONSTRAINT `ac_rec_ref_ibfk_acc_rec_ac` FOREIGN KEY (`rec_account_id`) REFERENCES `ac_rec_acc` (`id`);
            
        ');
    }

    public function safeDown() {
        echo "m240405_131922_d3acc_ac_rec_acc_id_to_mediumint cannot be reverted.\n";
        return false;
    }
}
