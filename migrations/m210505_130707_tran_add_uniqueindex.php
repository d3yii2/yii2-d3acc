<?php

use yii\db\Migration;

class m210505_130707_tran_add_uniqueindex  extends Migration {

    public function safeUp() {

        for($i=1;$i<20;$i++) {
            $this->execute('
        UPDATE `ac_tran` INNER JOIN (
            SELECT 
              MAX(id) maxId,
              MAX(id) - MIN(id),
              DATE_ADD(
                `t_datetime`, INTERVAL (MAX(id) - MIN(id)) SECOND
              ) t_datetime 
            FROM
              `ac_tran` 
            GROUP BY `accounting_date`,
              `debit_rec_acc_id`,
              `credit_rec_acc_id`,
              `amount`,
              `t_datetime` 
            HAVING COUNT(*) > 1 
            ) main ON ac_tran.id = main.maxId
            SET ac_tran.t_datetime = main.t_datetime
                    
        ');
        }
        $this->execute('
            ALTER TABLE `ac_tran`   
              ADD UNIQUE INDEX (`accounting_date`, `debit_rec_acc_id`, `credit_rec_acc_id`, `amount`, `t_datetime`);
                    
        ');
    }

    public function safeDown() {
        echo "m210505_130707_tran_add_uniqueindex cannot be reverted.\n";
        return false;
    }
}
