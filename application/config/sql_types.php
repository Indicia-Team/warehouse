<?php

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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct access allowed.');

$config = array(
  'tinyint'	=> array('type' => 'int', 'max' => 127),
  'smallint' => array('type' => 'int', 'max' => 32767),
  'mediumint' => array('type' => 'int', 'max' => 8388607),
  'int' => array('type' => 'int', 'max' => 2147483647),
  'integer' => array('type' => 'int', 'max' => 2147483647),
  // Max bigint reduced by one due to bug in json-c PHP module not accepting
  // max_int_value.
  'bigint' => array('type' => 'int', 'max' => 9223372036854775806),
  'float' => array('type' => 'float'),
  'float unsigned' => array('type' => 'float', 'min' => 0),
  'boolean' => array('type' => 'bool'),
  'time' => array('type' => 'string', 'format' => '00:00:00'),
  'time with time zone' => array('type' => 'string'),
  'date' => array('type' => 'string', 'format' => '0000-00-00'),
  'year' => array('type' => 'string', 'format' => '0000'),
  'datetime' => array('type' => 'string', 'format' => '0000-00-00 00:00:00'),
  'timestamp with time zone' => array('type' => 'string'),
  'char' => array('type' => 'string', 'exact' => TRUE),
  'binary' => array('type' => 'string', 'binary' => TRUE, 'exact' => TRUE),
  'varchar' => array('type' => 'string'),
  'varbinary' => array('type' => 'string', 'binary' => TRUE),
  'blob' => array('type' => 'string', 'binary' => TRUE),
  'text' => array('type' => 'string'),
  // Arrays map to strings as they can be written and read as strings as long
  // as encoding correct.
  'array' => array('type' => 'string', 'array' => TRUE),
  'json' => array('type' => 'string'),
  'jsonb' => array('type' => 'string', 'binary' => TRUE),
);

// DOUBLE.
$config['double'] = $config['double precision'] = $config['decimal'] = $config['real'] = $config['numeric'] = $config['float'];
$config['double unsigned'] = $config['float unsigned'];

// BIT.
$config['bit'] = $config['boolean'];

// TIMESTAMP.
$config['timestamp'] = $config['timestamp without time zone'] = $config['datetime'];

// ENUM.
$config['enum'] = $config['set'] = $config['varchar'];

// TEXT.
$config['tinytext'] = $config['mediumtext'] = $config['longtext'] = $config['text'];

// BLOB.
$config['user-defined'] = $config['tsvector'] = $config['tinyblob'] = $config['mediumblob'] = $config['longblob'] = $config['clob'] = $config['bytea'] = $config['blob'];

// CHARACTER
$config['character'] = $config['char'];
$config['character varying'] = $config['varchar'];

// TIME
$config['time without time zone'] = $config['time'];
