-- Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
INSERT INTO `llx_c_trainingsession_type` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(2, 0, 'BilanCompetences', 'BilanCompetences', '', 0, 10);
INSERT INTO `llx_c_trainingsession_type` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(3, 0, 'ActionVAE', 'ActionVAE', '', 0, 20);
INSERT INTO `llx_c_trainingsession_type` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(4, 0, 'ActionFormationApprentissage', 'ActionFormationApprentissage', '', 0, 30);

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
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, enabled, active, topic, joinfiles, content, content_lines) VALUES (0, 'contrat', 'contract', '', 0, null, null, 'Signature_Feuille_Présence', 10, '$conf->contrat->enabled', 1, '[__[MAIN_INFO_SOCIETE_NOM]__] Remise des liens de signature pour la convention de formation __REF__', 0, 'Bonjour,<br /><br />Nous vous envoyons ce mail afin de vous mettre au courant des sessions de formation li&eacute;es avec votre convention de formation &quot; __REF__&quot;&nbsp; -&nbsp; &quot;__DOLIMEET_CONTRACT_LABEL__&quot;.<br />Ci-dessous, vous trouverez un aper&ccedil;u des sessions, incluant les d&eacute;tails pertinents :<br /><br />__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__<br /><br />Nous vous prions de bien vouloir transmettre ces liens aux parties concern&eacute;es.<br />Nous restons &agrave; votre disposition pour toute information suppl&eacute;mentaire.<p>Bien cordialement,<br /><br />__USER_FULLNAME__<br />__USER_EMAIL__<br />__MYCOMPANY_NAME__<br />__MYCOMPANY_FULLADDRESS__<br />__MYCOMPANY_EMAIL__</p>', null);

INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('contrat', 'internal', 'OPCO', 'OPCO', 1, 'dolimeet', 20);
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('contrat', 'external', 'OPCO', 'OPCO', 1, 'dolimeet', 20);

-- 1.5.0
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('propal', 'internal', 'TRAINEE', 'Trainee', 1, 'dolimeet', 1);
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('propal', 'internal', 'SESSIONTRAINER', 'SessionTrainer', 1, 'dolimeet', 10);
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('propal', 'internal', 'OPCO', 'OPCO', 1, 'dolimeet', 20);
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('propal', 'external', 'TRAINEE', 'Trainee', 1, 'dolimeet', 1);
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('propal', 'external', 'SESSIONTRAINER', 'SessionTrainer', 1, 'dolimeet', 10);
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('propal', 'external', 'OPCO', 'OPCO', 1, 'dolimeet', 20);
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('project', 'internal', 'TRAINEE', 'Trainee', 1, 'dolimeet', 1);
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('project', 'internal', 'SESSIONTRAINER', 'SessionTrainer', 1, 'dolimeet', 10);
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('project', 'internal', 'OPCO', 'OPCO', 1, 'dolimeet', 20);
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('project', 'external', 'TRAINEE', 'Trainee', 1, 'dolimeet', 1);
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('project', 'external', 'SESSIONTRAINER', 'SessionTrainer', 1, 'dolimeet', 10);
INSERT INTO `llx_c_type_contact` (`element`, `source`, `code`, `libelle`, `active`, `module`, `position`) VALUES('project', 'external', 'OPCO', 'OPCO', 1, 'dolimeet', 20);


INSERT INTO `llx_c_trainingsession_specialities` (`rowid`, `entity`, `ref`, `label`, `description`, `code`, `active`, `position`) VALUES
(1, 0, 'GeneralTraining', 'GeneralTraining', '', 100, 1, 10),
(2, 0, 'MultiScienceSpecialities', 'MultiScienceSpecialities', '', 110, 1, 20),
(3, 0, 'PhysicsAndChemistry', 'PhysicsAndChemistry', '', 111, 1, 30),
(4, 0, 'ChemistryBiology', 'ChemistryBiology', '', 112, 1, 40),
(5, 0, 'NaturalSciences', 'NaturalSciences', '', 113, 1, 50),
(6, 0, 'Mathematics', 'Mathematics', '', 114, 1, 60),
(7, 0, 'Physics', 'Physics', '', 115, 1, 70),
(8, 0, 'Chemistry', 'Chemistry', '', 116, 1, 80),
(9, 0, 'EarthSciences', 'EarthSciences', '', 117, 1, 90),
(10, 0, 'LifeSciences', 'LifeSciences', '', 118, 1, 100),
(11, 0, 'MultiDiscHumanLaw', 'MultiDiscHumanLaw', '', 120, 1, 110),
(12, 0, 'Geography', 'Geography', '', 121, 1, 120),
(13, 0, 'Economy', 'Economy', '', 122, 1, 130),
(14, 0, 'SocialSciences', 'SocialSciences', '', 123, 1, 140),
(15, 0, 'Psychology', 'Psychology', '', 124, 1, 150),
(16, 0, 'Linguistics', 'Linguistics', '', 125, 1, 160),
(17, 0, 'History', 'History', '', 126, 1, 170),
(18, 0, 'PhilosophyEthics', 'PhilosophyEthics', '', 127, 1, 180),
(19, 0, 'LawPoliticalSciences', 'LawPoliticalSciences', '', 128, 1, 190),
(20, 0, 'LiteraryArtistic', 'LiteraryArtistic', '', 130, 1, 200),
(21, 0, 'FrenchLiterature', 'FrenchLiterature', '', 131, 1, 210),
(22, 0, 'VisualArts', 'VisualArts', '', 132, 1, 220),
(23, 0, 'MusicPerformingArts', 'MusicPerformingArts', '', 133, 1, 230),
(24, 0, 'OtherArtisticDisciplines', 'OtherArtisticDisciplines', '', 134, 1, 240),
(25, 0, 'AncientLanguages', 'AncientLanguages', '', 135, 1, 250),
(26, 0, 'ModernLanguages', 'ModernLanguages', '', 136, 1, 260),
(27, 0, 'IndustrialTechnologies', 'IndustrialTechnologies', '', 200, 1, 270),
(28, 0, 'IndustrialCommandTech', 'IndustrialCommandTech', '', 201, 1, 280),
(29, 0, 'AgronomyAgriculture', 'AgronomyAgriculture', '', 210, 1, 290),
(30, 0, 'PlantProduction', 'PlantProduction', '', 211, 1, 300),
(31, 0, 'AnimalProduction', 'AnimalProduction', '', 212, 1, 310),
(32, 0, 'ForestsWildlifeFishing', 'ForestsWildlifeFishing', '', 213, 1, 320),
(33, 0, 'LandscapePlanning', 'LandscapePlanning', '', 214, 1, 330),
(34, 0, 'MultiTechTransformations', 'MultiTechTransformations', '', 220, 1, 340),
(35, 0, 'AgroFoodCuisine', 'AgroFoodCuisine', '', 221, 1, 350),
(36, 0, 'ChemicalTransformations', 'ChemicalTransformations', '', 222, 1, 360),
(37, 0, 'Metallurgy', 'Metallurgy', '', 223, 1, 370),
(38, 0, 'BuildingMaterialsGlass', 'BuildingMaterialsGlass', '', 224, 1, 380),
(39, 0, 'PlasticsComposites', 'PlasticsComposites', '', 225, 1, 390),
(40, 0, 'PaperCardboard', 'PaperCardboard', '', 226, 1, 400),
(41, 0, 'EnergyClimateEngineering', 'EnergyClimateEngineering', '', 227, 1, 410),
(42, 0, 'MultiTechConstructionWood', 'MultiTechConstructionWood', '', 230, 1, 420),
(43, 0, 'MinesTopography', 'MinesTopography', '', 231, 1, 430),
(44, 0, 'BuildingConstructionRoofing', 'BuildingConstructionRoofing', '', 232, 1, 440),
(45, 0, 'BuildingFinishing', 'BuildingFinishing', '', 233, 1, 450),
(46, 0, 'WoodworkFurniture', 'WoodworkFurniture', '', 234, 1, 460),
(47, 0, 'MultiTechSoftMaterials', 'MultiTechSoftMaterials', '', 240, 1, 470),
(48, 0, 'Textile', 'Textile', '', 241, 1, 480),
(49, 0, 'ClothingFashion', 'ClothingFashion', '', 242, 1, 490),
(50, 0, 'LeatherSkins', 'LeatherSkins', '', 243, 1, 500),
(51, 0, 'MultiTechMechElec', 'MultiTechMechElec', '', 250, 1, 510),
(52, 0, 'GeneralMechanicsMachining', 'GeneralMechanicsMachining', '', 251, 1, 520),
(53, 0, 'AutoEnginesMechanics', 'AutoEnginesMechanics', '', 252, 1, 530),
(54, 0, 'AeroSpaceMechanics', 'AeroSpaceMechanics', '', 253, 1, 540),
(55, 0, 'MetalStructures', 'MetalStructures', '', 254, 1, 550),
(56, 0, 'ElectricityElectronics', 'ElectricityElectronics', '', 255, 1, 560),
(57, 0, 'ServicesSpecialities', 'ServicesSpecialities', '', 300, 1, 570),
(58, 0, 'ExchangesManagementSpecialities', 'ExchangesManagementSpecialities', '', 310, 1, 580),
(59, 0, 'TransportHandlingStorage', 'TransportHandlingStorage', '', 311, 1, 590),
(60, 0, 'CommerceSales', 'CommerceSales', '', 312, 1, 600),
(61, 0, 'FinanceBankInsurance', 'FinanceBankInsurance', '', 313, 1, 610),
(62, 0, 'AccountingManagement', 'AccountingManagement', '', 314, 1, 620),
(63, 0, 'HumanResourcesManagement', 'HumanResourcesManagement', '', 315, 1, 630),
(64, 0, 'CommunicationSpecialities', 'CommunicationSpecialities', '', 320, 1, 640),
(65, 0, 'JournalismCommunication', 'JournalismCommunication', '', 321, 1, 650),
(66, 0, 'PrintingPublishingTechniques', 'PrintingPublishingTechniques', '', 322, 1, 660),
(67, 0, 'ImageSoundTechniques', 'ImageSoundTechniques', '', 323, 1, 670),
(68, 0, 'SecretarialOffice', 'SecretarialOffice', '', 324, 1, 680),
(69, 0, 'DocumentationLibraries', 'DocumentationLibraries', '', 325, 1, 690),
(70, 0, 'InformationTechnology', 'InformationTechnology', '', 326, 1, 700),
(71, 0, 'HealthSpecialities', 'HealthSpecialities', '', 330, 1, 710),
(72, 0, 'Health', 'Health', '', 331, 1, 720),
(73, 0, 'SocialWork', 'SocialWork', '', 332, 1, 730),
(74, 0, 'EducationTraining', 'EducationTraining', '', 333, 1, 740),
(75, 0, 'HospitalityTourism', 'HospitalityTourism', '', 334, 1, 750),
(76, 0, 'CulturalSportsLeisure', 'CulturalSportsLeisure', '', 335, 1, 760),
(77, 0, 'PersonalCare', 'PersonalCare', '', 336, 1, 770),
(78, 0, 'TerritoryPlanning', 'TerritoryPlanning', '', 341, 1, 780),
(79, 0, 'HeritageProtection', 'HeritageProtection', '', 342, 1, 790),
(80, 0, 'EnvironmentalSanitation', 'EnvironmentalSanitation', '', 343, 1, 800),
(81, 0, 'Security', 'Security', '', 344, 1, 810),
(82, 0, 'LegalRights', 'LegalRights', '', 345, 1, 820),
(83, 0, 'MilitarySpecialties', 'MilitarySpecialties', '', 346, 1, 830),
(84, 0, 'MultipleSkills', 'MultipleSkills', '', 410, 1, 840),
(85, 0, 'SportsPractices', 'SportsPractices', '', 411, 1, 850),
(86, 0, 'MentalDevelopment', 'MentalDevelopment', '', 412, 1, 860),
(87, 0, 'BehavioralDevelopment', 'BehavioralDevelopment', '', 413, 1, 870),
(88, 0, 'OrganizationalDevelopment', 'OrganizationalDevelopment', '', 414, 1, 880),
(89, 0, 'SocialIntegration', 'SocialIntegration', '', 415, 1, 890),
(90, 0, 'LeisureActivities', 'LeisureActivities', '', 421, 1, 900),
(91, 0, 'DomesticEconomy', 'DomesticEconomy', '', 422, 1, 910),
(92, 0, 'FamilyLife', 'FamilyLife', '', 423, 1, 920);
