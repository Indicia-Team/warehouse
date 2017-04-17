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
      // test where the rule is keyed by TVK
      array(
        'joins' => 
          "join cache_taxa_taxon_lists cttl on cttl.id=co.taxa_taxon_list_id ".
          "join verification_rule_data vrd on vrd.header_name='Data' and vrd.key = co.taxa_taxon_list_external_key and vrd.deleted=false ".
          "join verification_rules vr on vr.id=vrd.verification_rule_id and vr.test_type='IdentificationDifficulty' and vr.deleted=false ".
          "join verification_rule_data vrdini on vrdini.verification_rule_id=vr.id and vrdini.header_name='INI' and vrdini.key=vrd.value and cast(vrdini.key as int)>1 and vrdini.deleted=false",
        'subtypeField' => 'vrdini.key'
      ),
      // repeat test where the rule is keyed by species name
      array(
        'joins' =>
          "join cache_taxa_taxon_lists cttl on cttl.id=co.taxa_taxon_list_id ".
          "join verification_rule_data vrd on vrd.header_name='Taxa' and vrd.key = cttl.preferred_taxon and vrd.deleted=false ".
          "join verification_rules vr on vr.id=vrd.verification_rule_id and vr.test_type='IdentificationDifficulty' and vr.deleted=false ".
          "join verification_rule_data vrdini on vrdini.verification_rule_id=vr.id and vrdini.header_name='INI' and vrdini.key=vrd.value and cast(vrdini.key as int)>1 and vrdini.deleted=false",
        'subtypeField' => 'vrdini.key'
      )
    )
  );
}

/** 
 * Taxon version keys should really be uppercase, so enforce this. Otherwise the query needs to be case insensitive which makes it slow.
 * Also, we need to store the identification difficulty results into cache_taxon_searchterms so they are available when searching
 * for taxa.
 */
function data_cleaner_identification_difficulty_data_cleaner_postprocess($id, $db) {
  $db->query("update verification_rule_data set key=upper(key) where header_name='Data' and key<>upper(key) and verification_rule_id=$id");
  $db->query("update cache_taxon_searchterms set identification_difficulty=null, id_diff_verification_rule_id=null " .
      "where id_diff_verification_rule_id=$id"); 
  $db->query("update cache_taxon_searchterms cts " .
      "set identification_difficulty=vrd.value::integer, id_diff_verification_rule_id=vrd.verification_rule_id ".
      "from cache_taxa_taxon_lists cttl ".
      "join verification_rule_data vrd on vrd.header_name='Data' and upper(vrd.key)=cttl.external_key and vrd.deleted=false ".
      "join verification_rules vr on vr.id=vrd.verification_rule_id and vr.deleted=false ".
      "where cttl.id=cts.preferred_taxa_taxon_list_id ".
      "and vr.id=$id");
}