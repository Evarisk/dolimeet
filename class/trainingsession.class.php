<?php
/* Copyright (C) 2021-2023 EVARISK <dev@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/trainingsession.class.php
 * \ingroup     dolimeet
 * \brief       This file is a CRUD class file for TrainingSession (Create/Read/Update/Delete)
 */

require_once __DIR__ . '/../../saturne/class/signature.class.php';
require_once __DIR__ . '/session.class.php';

/**
 * Class for TrainingSession
 */
class TrainingSession extends Session
{
    /**
     * @var string Element type of object.
     */
    public $element = 'trainingsession';

    /**
     * @var string Name of icon for trainingsession. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'trainingsession@dolimeet' if picto is file 'img/object_trainingsession.png'.
     */
    public string $picto = 'fontawesome_fa-people-arrows_fas_#fcba03';

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db);
        $this->type = 'trainingsession';
    }
}