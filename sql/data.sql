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