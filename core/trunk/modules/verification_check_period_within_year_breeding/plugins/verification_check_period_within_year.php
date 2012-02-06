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
 * Hook into the verification checker to declare checks for the test of breeding record time of year against known breeding time. 
 * There should be a checkbox/boolean attribute to identify breeding records, whose attribute ID is defined in the config file. 
 * @return type array of rules.
 */
function verification_check_period_within_year_verification_rules() {
  $breedingAttrId = kohana::config('verification_check_period_within_year.breeding_attribute_id', false, false);
  if ($breedingAttrId) {
    return array(
      // slightly convoluted logic required in this test to get it to work with ranges in middle of year as well as ranges that span the end of the year
      array(
        'message'=>'This record was outside the expected breeding time for this species.',
        'query' => array(
          'joins' => 
              "join samples s on s.id=occlist.sample_id ".
              "join taxa_taxon_list_attribute_values avstart on avstart.taxa_taxon_list_id=occlist.taxa_taxon_list_id and avstart.deleted=false \n".
              "join taxa_taxon_list_attributes astart on astart.id=avstart.taxa_taxon_list_attribute_id and astart.deleted=false and astart.caption='Breeding period start date' \n".
              "join taxa_taxon_list_attribute_values avend on avend.taxa_taxon_list_id=occlist.taxa_taxon_list_id and avend.deleted=false \n".
              "join taxa_taxon_list_attributes aend on aend.id=avend.taxa_taxon_list_attribute_id and aend.deleted=false and aend.caption='Breeding period end date' \n".
              "join occurrence_attribute_values breeding on breeding.occurrence_id=occlist.occurrence_id and breeding.deleted=false ".
                  "and breeding.int_value=1 and breeding.occurrence_attribute_id=$breedingAttrId",
          'where'=>"where (extract(doy from avstart.date_start_value)<=extract(doy from avend.date_start_value) \n".
              "and (extract(doy from avstart.date_start_value) >= extract(doy from s.date_start) or extract(doy from avend.date_start_value) <= extract(doy from s.date_start))) \n".
              "or (extract(doy from avstart.date_start_value)>extract(doy from avend.date_start_value) \n".
              "and extract(doy from avstart.date_start_value) >= extract(doy from s.date_start) and extract(doy from avend.date_start_value) <= extract(doy from s.date_start))"
        )
      )
    );
  } else {
    return array();
  }
}

?>