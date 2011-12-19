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
 * @package	Verification Check
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Hook into the verification checker to declare checks for the test of record time of year. 
 * @return type array of rules.
 */
function verification_check_phenology_verification_rules() {
  return array(
    array(
      'message'=>'This record was before the expected time of year for observations of this species.',
      'query' => array(
        'joins' => 
            "join samples s on s.id=occlist.sample_id ".
            "join taxa_taxon_list_attribute_values ttlav on ttlav.taxa_taxon_list_id=occlist.taxa_taxon_list_id\n" .
            "join taxa_taxon_list_attributes ttla on ttla.id=ttlav.taxa_taxon_list_attribute_id and ttla.deleted=false and ttla.caption='First expected date in year'",
        'where'=>'where extract(doy from ttlav.date_start_value) > extract(doy from s.date_start)'
      )
    ),
    array(
      'message'=>'This record was after the expected time of year for observations of this species.',
      'query' => array(
        'joins' =>
            "join samples s on s.id=occlist.sample_id ".
            "join taxa_taxon_list_attribute_values ttlav on ttlav.taxa_taxon_list_id=occlist.taxa_taxon_list_id\n" .
            "join taxa_taxon_list_attributes ttla on ttla.id=ttlav.taxa_taxon_list_attribute_id and ttla.deleted=false and ttla.caption='Last expected date in year'",
        'where'=>'where extract(doy from ttlav.date_start_value) < extract(doy from s.date_start)'
      )
    )
  );
}

?>