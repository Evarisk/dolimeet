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

-- Migration of a former DoliMeet install to Doliopi.
-- Loaded automatically on activation by modDoliopi::init() -> _load_tables().
-- This sql/migration/ folder is scanned before sql/session/ ("migration" sorts
-- before "session"), so the table RENAME below runs BEFORE the doliopi_session
-- CREATE TABLE, which then becomes a no-op (table already exists).
--
-- Fully idempotent: run_sql() accepts "no such table" and "already exists"
-- errors, and the UPDATE/DELETE become no-ops once converted.

-- Rename the data tables (rows are preserved).
RENAME TABLE llx_dolimeet_session TO llx_doliopi_session;
RENAME TABLE llx_dolimeet_session_extrafields TO llx_doliopi_session_extrafields;

-- Constant names: DOLIMEET_* and MAIN_MODULE_DOLIMEET*.
UPDATE llx_const SET name = REPLACE(name, 'DOLIMEET', 'DOLIOPI') WHERE name LIKE '%DOLIMEET%';

-- Constant values: paths, picto, @lang refs, tab labels, hooks, menu codes.
-- REPLACE() is case-sensitive in MySQL, hence one nested pass per casing.
UPDATE llx_const SET value = REPLACE(REPLACE(REPLACE(REPLACE(value, 'DOLIMEET', 'DOLIOPI'), 'DoliMeet', 'Doliopi'), 'Dolimeet', 'Doliopi'), 'dolimeet', 'doliopi') WHERE value LIKE '%dolimeet%';

-- Shared Saturne tables tagged by module_name (existing documents & signatures).
UPDATE llx_saturne_object_documents SET module_name = 'doliopi' WHERE module_name = 'dolimeet';
UPDATE llx_saturne_object_signature SET module_name = 'doliopi' WHERE module_name = 'dolimeet';

-- Legacy menus and permission rows are rebuilt right after by _init(); drop the
-- orphan DoliMeet ones. Granted user/group rights survive because the numeric
-- right ids are unchanged (the module number is preserved).
DELETE FROM llx_menu WHERE mainmenu = 'dolimeet';
DELETE FROM llx_rights_def WHERE module = 'dolimeet';
