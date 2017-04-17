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
 * @package	Taxon Designations
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

// @todo Scheduled tasks hook to clean expired lists.

/**
 * Hook to ORM enable the relationship between taxon designations and taxa from the taxon end.
 */
function scratchpad_extend_orm() {
  return array(
    'scratchpad_list'=>array('has_many'=>array('scratchpad_list_entries')),
    'scratchpad_list_entry'=>array('belongs_to'=>array('scratchpad_list'))
  );
}

function scratchpad_extend_data_services() {
  return array(
    'scratchpad_lists'=>array(),
    'scratchpad_list_entries'=>array()
  );
}