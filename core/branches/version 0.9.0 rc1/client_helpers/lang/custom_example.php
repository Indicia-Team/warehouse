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
 * @package	Client
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

global $custom_terms;

/**
 * Example providing a list of customised or localised terms used by the lang class.
 * Each listed term overrides the equivalent term in the default.php file. If not present, then
 * the default term is used.
 * To use this file, create a copy of the file in the lang folder and name it appropriately, e.g.
 * deu.php for a list of German terms. Now, transfer the terms you want to customise from the default.php file
 * in the same folder across into your own file, and change the list of term values as required. Finally
 * require your custom file in the data entry page's PHP before the data_entry_helper.php file is included.
 *
 * @package	Client
 */
$custom_terms = array(
  'species_checklist.species'=>'Taxon',
);

