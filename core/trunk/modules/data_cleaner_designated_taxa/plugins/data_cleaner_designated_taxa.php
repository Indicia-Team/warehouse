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
 * @package	Data Cleaner
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Hook into the data cleaner to declare checks for the test of record time of year. 
 * @return type array of rules.
 */
function data_cleaner_designated_taxa_data_cleaner_rules() {
  return array(
    'testType' => 'designatedTaxa',
    'errorMsgField' => "'This species is designated ' || td.title",
    // nothing to import
    'queries' => array(
      array(
        'joins' => 
            "join taxa_taxon_lists ttl on ttl.taxon_meaning_id=co.taxon_meaning_id and ttl.deleted=false ".
            "join taxa_taxon_designations ttd on ttd.taxon_id=ttl.taxon_id and ttd.deleted=false ".
            "join taxon_designations td on td.id=ttd.taxon_designation_id and td.deleted=false"
      )            
    )
  );
}

?>