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

-- 1.0.0
INSERT INTO `llx_c_trainingsession_type` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(1, 0, 'ActionFormation', 'ActionFormation', '', 1, 1);
INSERT INTO `llx_c_trainingsession_type` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(2, 0, 'BilanCompetences', 'BilanCompetences', '', 1, 10);
INSERT INTO `llx_c_trainingsession_type` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(3, 0, 'ActionVAE', 'ActionVAE', '', 1, 20);
INSERT INTO `llx_c_trainingsession_type` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(4, 0, 'ActionFormationApprentissage', 'ActionFormationApprentissage', '', 1, 30);

-- 1.1.0
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('contrat', 'internal', 'TRAINEE', 'Trainee', 1, 'dolimeet', 1);
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('contrat', 'internal', 'SESSIONTRAINER', 'SessionTrainer', 1, 'dolimeet', 10);
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('contrat', 'external', 'TRAINEE', 'Trainee', 1, 'dolimeet', 1);
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('contrat', 'external', 'SESSIONTRAINER', 'SessionTrainer', 1, 'dolimeet', 10);

-- 1.2.0
INSERT INTO `llx_c_meeting_attendants_role` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(1, 0, 'Contributor', 'Contributor', '', 1, 1);
INSERT INTO `llx_c_meeting_attendants_role` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(2, 0, 'Responsible', 'Responsible', '', 1, 10);
INSERT INTO `llx_c_trainingsession_attendants_role` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(1, 0, 'Trainee', 'Trainee', '', 1, 1);
INSERT INTO `llx_c_trainingsession_attendants_role` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(2, 0, 'SessionTrainer', 'SessionTrainer', '', 1, 10);
INSERT INTO `llx_c_audit_attendants_role` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(1, 0, 'Auditee', 'Auditee', '', 1, 1);
INSERT INTO `llx_c_audit_attendants_role` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(2, 0, 'Auditor', 'Auditor', '', 1, 10);

-- 1.3.0
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, enabled, active, topic, joinfiles, content, content_lines) VALUES (0, 'contrat', 'contract', '', 0, null, null, 'Signature_Feuille_Présence', 10, '$conf->contrat->enabled', 1, '[__[MAIN_INFO_SOCIETE_NOM]__] Remise des liens de signature pour la convention de formation __REF__', 1, 'Bonjour,<br /><br />Nous vous envoyons ce mail afin de vous mettre au courant des sessions de formation li&eacute;es avec votre convention de formation &quot; __REF__&quot;&nbsp; -&nbsp; &quot;__DOLIMEET_CONTRACT_LABEL__&quot;.<br />Ci-dessous, vous trouverez un aper&ccedil;u des sessions, incluant les d&eacute;tails pertinents :<br /><br />__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__<br /><br />Nous vous prions de bien vouloir transmettre ces liens aux parties concern&eacute;es.<br />Nous restons &agrave; votre disposition pour toute information suppl&eacute;mentaire.<p>Bien cordialement,<br /><br />__USER_FULLNAME__<br />__USER_EMAIL__<br />__MYCOMPANY_NAME__<br />__MYCOMPANY_FULLADDRESS__<br />__MYCOMPANY_EMAIL__</p>', null);
