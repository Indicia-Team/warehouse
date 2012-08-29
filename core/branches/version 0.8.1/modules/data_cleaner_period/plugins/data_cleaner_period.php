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
 * Hook into the data cleaner to declare checks for the test of record period, e.g.
 * for new arrivals or extinctions. 
 * @return type array of rules.
 */
function data_cleaner_period_data_cleaner_rules() {
  return array(
    'testType' => 'period',
    'optional' => array('Metadata'=>array('StartDate','EndDate','Tvk','Taxon','TaxonMeaningId')),
    'queries' => array(
      // Slightly convoluted logic required in this test to get it to work with ranges in middle of year as well as ranges that span the end of the year.
      // Also note in these queries we use 2012 as the year for expanding dates that have just a month and day, as it is a leap
      // year so all dates are covered.
      array(
        'joins' => 
            "join verification_rule_metadata vrm on (vrm.key='Tvk' and vrm.value=co.taxa_taxon_list_external_key) ".
            "or (vrm.key='Taxon' and vrm.value=co.preferred_taxon) ".
            "or (vrm.key='TaxonMeaningId' and vrm.value=cast(co.taxon_meaning_id as character varying)) ".
            "join verification_rules vr on vr.id=vrm.verification_rule_id and vr.test_type='Period' ".
            "left join verification_rule_metadata vrmstart on vrmstart.verification_rule_id=vr.id and vrmstart.key='StartDate' and length(vrmstart.value)=8 ".
            "left join verification_rule_metadata vrmend on vrmend.verification_rule_id=vr.id and vrmend.key='EndDate' and length(vrmend.value)=8 ",
        'where' =>
            "vr.reverse_rule<>((vrmstart is null or vrmend.value is null or vrmstart.value <= vrmend.value) ".
            "and ((vrmstart.value is not null and co.date_start < cast(vrmstart.value as date)) ".
            "or (vrmend.value is not null and co.date_start > cast(vrmend.value as date))))"
      )
    )
  );
}

/** 
 * Taxon version keys should really be uppercase, so enforce this. Otherwise the query needs to be case insensitive which makes it slow.
 */
function data_cleaner_period_data_cleaner_postprocess($id, $db) {
  $db->query("update verification_rule_metadata set value=upper(value) where key ilike 'Tvk' and value<>upper(value) and verification_rule_id=$id");
}

?>