ALTER TABLE `statement`
    ADD COLUMN `code` CHAR(3) NULL AFTER `netbalance`;

ALTER TABLE `statement`
    RENAME COLUMN `reference` TO `referenceid`;

ALTER TABLE `statement`
    ADD COLUMN `referencesource` VARCHAR(255) NULL AFTER `referenceid`;