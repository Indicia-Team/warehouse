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
 * Hook into the data cleaner to declare checks for the difficulty of identification
 * of a species.
 * @return type array of rules.
 */
function data_cleaner_identification_difficulty_data_cleaner_rules() {
  return array(
    'testType' => 'IdentificationDifficulty',
    'optional' => array('Data'=>array('*'), 'Taxa'=>array('*'), 'INI'=>array('*')),
    'errorMsgField' => 'vrdini.value',
    'queries' => array(
      array(
        'joins' => 
            "join verification_rule_data vrd on ((vrd.key=co.taxa_taxon_list_external_key and vrd.header_name='Data') or (vrd.key=co.preferred_taxon and vrd.header_name='Taxa')) and vrd.deleted=false ".
            "join verification_rules vr on vr.id=vrd.verification_rule_id and vr.test_type='IdentificationDifficulty' and vr.deleted=false ".
            "join verification_rule_data vrdini on vrdini.verification_rule_id=vr.id and vrdini.header_name='INI' and vrdini.key=vrd.value and cast(vrdini.key as int)>1 and vrdini.deleted=false"
      )
    )
  );
}

?>