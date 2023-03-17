ALTER TABLE `statement`
    ADD COLUMN `code` CHAR(3) NULL AFTER `netbalance`;

ALTER TABLE `statement`
    RENAME COLUMN `reference` TO `referenceid`;

ALTER TABLE `statement`
    ADD COLUMN `referencesource` VARCHAR(50) NULL AFTER `referenceid`,
    ADD INDEX `fk_statement_referenceid_idx` (`referenceid`),
    ADD INDEX `fk_statement_referencesource_idx` (`referencesource`);
