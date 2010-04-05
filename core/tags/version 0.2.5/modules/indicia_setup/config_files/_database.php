<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @package	Core
 * @subpackage Config
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

$config['default'] = array
(
    'benchmark'     => TRUE,
    'persistent'    => FALSE,
    'connection'    => array
    (
        'type'     => 'pgsql',
        'user'     => '*user*',
        'pass'     => '*password*',
        'host'     => '*host*',
        'port'     => '*port*',
        'socket'   => FALSE,
        'database' => '*database*'
    ),
    'character_set' => 'utf8',
    'table_prefix'  => '',
    'schema'        => '*schema*',
    'object'        => TRUE,
    'cache'         => FALSE,
    'escape'        => TRUE
);

$config['report'] = array
(
    'benchmark'     => TRUE,
    'persistent'    => FALSE,
    'connection'    => array
    (
        'type'     => 'pgsql',
        'user'     => '*reportuser*',
        'pass'     => '*reportpassword*',
        'host'     => '*host*',
        'port'     => '*port*',
        'socket'   => FALSE,
        'database' => '*database*'
    ),
    'character_set' => 'utf8',
    'table_prefix'  => '',
    'schema'        => '*schema*',
    'object'        => TRUE,
    'cache'         => FALSE,
    'escape'        => TRUE
);