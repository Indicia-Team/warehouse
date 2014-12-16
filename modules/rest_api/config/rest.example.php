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
 * @package	Modules
 * @subpackage Cache builder
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

// The following configuration is a temporary definition of the projects available for 
// each website.
// @todo Move this configuration into a database table.
$config['projects']=array(
  // keyed by client website ID
  1 => array(
    // list of available projects
    1 => array(
      'id' => 1,
      'title'=>'BRC birds',
      'description'=>'Bird records entered onto the BRC warehouse made available for verification on iRecord.',
      'filter_id' => 53,
      'sharing' => 'verification'
    )
  )
);