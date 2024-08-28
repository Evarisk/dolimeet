-- Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.

-- 1.1.0
ALTER TABLE `llx_dolimeet_session` CHANGE `date_start` `date_start` DATETIME NOT NULL;
ALTER TABLE `llx_dolimeet_session` CHANGE `date_end` `date_end` DATETIME NOT NULL;
ALTER TABLE `llx_dolimeet_session` CHANGE `tms` `tms` TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `llx_dolimeet_session` CHANGE `status` `status` INT(11) NOT NULL;
ALTER TABLE `llx_dolimeet_session` CHANGE `import_key` `import_key` VARCHAR(14) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `llx_saturne_object_signature` ADD `attendance` SMALLINT NULL AFTER `transaction_url`;

-- 1.2.0
ALTER TABLE `llx_c_trainingsession_type` CHANGE `active` `active` TINYINT(4) NULL DEFAULT '1';
ALTER TABLE `llx_c_trainingsession_type` ADD `position` INT NULL DEFAULT '0' AFTER `active`;
ALTER TABLE `llx_dolimeet_session` DROP `model_pdf`, DROP `last_main_doc`, DROP `document_url`;

-- 1.5.0
UPDATE `llx_c_email_templates` SET `joinfiles` = '0' WHERE `llx_c_email_templates`.`label` = 'Signature_Feuille_Présence';
