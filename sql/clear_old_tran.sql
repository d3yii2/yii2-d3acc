/*
*  copy unactive account transactions to ac_arch_*
*/

/*
* if after @dateTo no any ac_tran record, delete transactions, bmonth balance and accounts
*/
SET @dateTo := '2021-01-01';

/*
*  ac_def  table value for filtering accouts
*/
SET @table := 'd3p_person';


/*
* create person id table and load it
*/
DROP  TABLE IF EXISTS ac_arch_d3person_id;
CREATE TABLE ac_arch_d3person_id AS
SELECT
    `sys_company_id`,
    `person_id`,
    MAX(maxDate) maxDate,
    NOW() added
FROM
    (
        SELECT
            ac_def.`sys_company_id`,
            `ac_rec_ref`.`pk_value` person_id,
            MAX(`ac_tran`.`accounting_date`) maxDate
        FROM
            `ac_def`
                INNER JOIN `ac_rec_ref`
                           ON `ac_rec_ref`.`def_id` = `ac_def`.id
                INNER JOIN `ac_rec_acc`
                           ON `ac_rec_ref`.`rec_account_id` = `ac_rec_acc`.id
                INNER JOIN `ac_tran`
                           ON `ac_rec_acc`.id = `ac_tran`.`credit_rec_acc_id`
        WHERE ac_def.`table` = @table
        GROUP BY ac_def.`sys_company_id`,
                 `ac_rec_ref`.`pk_value`
        UNION
        SELECT
            ac_def.`sys_company_id`,
            `ac_rec_ref`.`pk_value` person_id,
            MAX(`ac_tran`.`accounting_date`) maxDate
        FROM
            `ac_def`
                INNER JOIN `ac_rec_ref`
                           ON `ac_rec_ref`.`def_id` = `ac_def`.id
                INNER JOIN `ac_rec_acc`
                           ON `ac_rec_ref`.`rec_account_id` = `ac_rec_acc`.id
                INNER JOIN `ac_tran`
                           ON `ac_rec_acc`.id = `ac_tran`.`debit_rec_acc_id`
        WHERE ac_def.`table` = @table
        GROUP BY ac_def.`sys_company_id`,
                 `ac_rec_ref`.`pk_value`

    ) a
GROUP BY `sys_company_id`,
         `person_id`
HAVING MAX(maxDate) < @dateTo;


/*
* create tmp_ac_tran and load filtered debit accountac_tran.id
*/
DROP TEMPORARY TABLE IF EXISTS tmp_ac_tran;
CREATE TEMPORARY TABLE tmp_ac_tran AS
SELECT
    `ac_tran`.id,
    ac_arch_d3person_id.person_id
FROM
    `ac_tran`
        INNER JOIN ac_rec_acc
                   ON `ac_rec_acc`.id = `ac_tran`.`debit_rec_acc_id`
        INNER JOIN `ac_rec_ref`
                   ON `ac_rec_ref`.`rec_account_id` = `ac_rec_acc`.id

        INNER JOIN `ac_def`
                   ON `ac_rec_ref`.`def_id` = `ac_def`.id
                       AND ac_def.`table` = @table
        INNER JOIN  ac_arch_d3person_id
                    ON ac_arch_d3person_id.person_id = `ac_rec_ref`.`pk_value`
                        AND  ac_arch_d3person_id.sys_company_id = `ac_rec_ref`.`sys_company_id`
WHERE ac_tran.`accounting_date` < @dateTo
ORDER BY ac_tran.`accounting_date` DESC
;

/*
* load filtered credit accountac_tran.id
*/
INSERT INTO tmp_ac_tran
SELECT
    `ac_tran`.id,
    ac_arch_d3person_id.person_id
FROM
    `ac_tran`
        INNER JOIN ac_rec_acc
                   ON `ac_rec_acc`.id = `ac_tran`.`credit_rec_acc_id`
        INNER JOIN `ac_rec_ref`
                   ON `ac_rec_ref`.`rec_account_id` = `ac_rec_acc`.id

        INNER JOIN `ac_def`
                   ON `ac_rec_ref`.`def_id` = `ac_def`.id
                       AND ac_def.`table` = @table
        INNER JOIN  ac_arch_d3person_id
                    ON ac_arch_d3person_id.person_id = `ac_rec_ref`.`pk_value`
                        AND  ac_arch_d3person_id.sys_company_id = `ac_rec_ref`.`sys_company_id`
WHERE ac_tran.`accounting_date` < @dateTo
;

-- process ac_tran_dim
CREATE TABLE `ac_arch_tran_dim` LIKE `ac_tran_dim`;
INSERT INTO ac_arch_tran_dim SELECT DISTINCT `ac_tran_dim`.* FROM `ac_tran_dim` INNER JOIN  tmp_ac_tran ON tmp_ac_tran.id = `ac_tran_dim`.tran_id;
DELETE `ac_tran_dim` FROM `ac_tran_dim` INNER JOIN  tmp_ac_tran ON tmp_ac_tran.id = `ac_tran_dim`.tran_id;

-- process ac_tran
CREATE TABLE `ac_arch_tran` LIKE `ac_tran`;
INSERT INTO ac_arch_tran SELECT DISTINCT `ac_tran`.* FROM `ac_tran` INNER JOIN  tmp_ac_tran ON tmp_ac_tran.id = `ac_tran`.id;
DELETE `ac_tran` FROM `ac_tran` INNER JOIN  tmp_ac_tran ON tmp_ac_tran.id = `ac_tran`.id;

-- load ac_rec_acc
DROP TEMPORARY TABLE IF EXISTS tmp_ac_rec_acc;
CREATE TEMPORARY TABLE tmp_ac_rec_acc AS
SELECT
    `ac_rec_ref`.`rec_account_id` id,
    ac_arch_d3person_id.person_id
FROM
    `ac_rec_ref`
        INNER JOIN `ac_def`
                   ON `ac_rec_ref`.`def_id` = `ac_def`.id
                       AND ac_def.`table` = @table
        INNER JOIN  ac_arch_d3person_id
                    ON ac_arch_d3person_id.person_id = `ac_rec_ref`.`pk_value`
                        AND  ac_arch_d3person_id.sys_company_id = `ac_rec_ref`.`sys_company_id`

;

-- load rec_account_id
INSERT INTO tmp_ac_rec_acc
SELECT
    `ac_rec_ref`.`rec_account_id` id,
    ac_arch_d3person_id.person_id
FROM
    `ac_rec_ref`
        INNER JOIN `ac_def`
                   ON `ac_rec_ref`.`def_id` = `ac_def`.id
                       AND ac_def.`table` = @table
        INNER JOIN  ac_arch_d3person_id
                    ON ac_arch_d3person_id.person_id = `ac_rec_ref`.`pk_value`
                        AND  ac_arch_d3person_id.sys_company_id = `ac_rec_ref`.`sys_company_id`;

-- process ac_rec_ref
CREATE TABLE `ac_arch_rec_ref` LIKE `ac_rec_ref`;
INSERT INTO ac_arch_rec_ref SELECT DISTINCT `ac_rec_ref`.* FROM `ac_rec_ref` INNER JOIN  tmp_ac_rec_acc ON tmp_ac_rec_acc.id = `ac_rec_ref`.`rec_account_id`;
DELETE `recRef` FROM `ac_rec_ref` recRef INNER JOIN  tmp_ac_rec_acc ON tmp_ac_rec_acc.id = `recRef`.`rec_account_id`;

-- process ac_period_balance
CREATE TABLE `ac_arch_period_balance` LIKE `ac_period_balance`;
INSERT INTO ac_arch_period_balance SELECT DISTINCT `ac_period_balance`.* FROM `ac_period_balance` INNER JOIN  tmp_ac_rec_acc ON tmp_ac_rec_acc.id = `ac_period_balance`.`rec_acc_id`;
DELETE `acBalance` FROM `ac_period_balance` acBalance INNER JOIN  tmp_ac_rec_acc ON tmp_ac_rec_acc.id = `acBalance`.`rec_acc_id`;

-- process ac_rec_acc
CREATE TABLE `ac_arch_rec_acc` LIKE `ac_rec_acc`;
INSERT INTO ac_arch_rec_acc SELECT DISTINCT `ac_rec_acc`.* FROM `ac_rec_acc` INNER JOIN  tmp_ac_rec_acc ON tmp_ac_rec_acc.id = `ac_rec_acc`.`id`;
DELETE `recAcc` FROM `ac_rec_acc` recAcc INNER JOIN  tmp_ac_rec_acc ON tmp_ac_rec_acc.id = `recAcc`.`id`;

select 'Finished';


