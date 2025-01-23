<?php

/**
 * @file
 * Plugin methods for the idenfication difficulty rule.
 *
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
 * @link http://code.google.com/p/indicia/
 */

/**
 * Updates the cached copy of identification difficulty rules.
 *
 * After saving a record, does an insert of the updated cache entry, helping
 * to impprove performance.
 */
function data_cleaner_identification_difficulty_cache_sql() {
  return <<<SQL
insert into cache_verification_rules_identification_difficulty
select distinct vr.id as verification_rule_id,
  coalesce(vrdtvk.key, cttltaxon.external_key) as taxa_taxon_list_external_key,
  coalesce(vrdtvk.value, vrdtaxa.value)::int as id_difficulty
from verification_rules vr
left join verification_rule_data vrdtvk on vrdtvk.verification_rule_id=vr.id
  and vrdtvk.header_name='Data' and vrdtvk.deleted=false
left join verification_rule_data vrdtaxa on vrdtaxa.verification_rule_id=vr.id
  and vrdtaxa.header_name='Taxa' and vrdtaxa.deleted=false
left join cache_taxa_taxon_lists cttltaxon on cttltaxon.preferred_taxon=vrdtaxa.value and cttltaxon.preferred=true
where vr.test_type='IdentificationDifficulty'
and vr.deleted=false
and vr.id=#id#;
SQL;
}

/**
 * Returns definitions of the rule queries.
 *
 * Hook into the data cleaner to declare checks for the difficulty of
 * identification of a species.
 *
 * @return array
 *   array of rules.
 */
function data_cleaner_identification_difficulty_data_cleaner_rules() {
  $joinSql = <<<SQL
join cache_verification_rules_identification_difficulty vr on vr.taxa_taxon_list_external_key=co.taxa_taxon_list_external_key
join verification_rule_data vrdini on vrdini.verification_rule_id=vr.verification_rule_id
  and vrdini.header_name='INI'
  and vrdini.key=vr.id_difficulty
  and vrdini.key::int>1 and vrdini.deleted=false
SQL;
  return array(
    'testType' => 'IdentificationDifficulty',
    'optional' => array(
      'Data' => array('*'),
      'Taxa' => array('*'),
      'INI' => array('*'),
    ),
    'errorMsgField' => 'vrdini.value',
    'queries' => [
      [
        'joins' => $joinSql,
        'subtypeField' => 'vrdini.key',
      ],
    ],
  );
}

/**
 * Postprocess saved rules.
 *
 * Taxon version keys should really be uppercase, so enforce this. Otherwise
 * the query needs to be case insensitive which makes it slow. Also, we need to
 * store the identification difficulty results into cache_taxon_searchterms so
 * they are available when searching for taxa.
 */
function data_cleaner_identification_difficulty_data_cleaner_postprocess(int $id, $db) {
  $db->query("update verification_rule_data set key=upper(key) where header_name='Data' and key<>upper(key) and verification_rule_id=?", [$id]);
  $db->query("update cache_taxon_searchterms set identification_difficulty=null, id_diff_verification_rule_id=null " .
      "where id_diff_verification_rule_id=?", [$id]);
  $db->query("update cache_taxon_searchterms cts " .
      "set identification_difficulty=vrd.value::integer, id_diff_verification_rule_id=vrd.verification_rule_id " .
      "from cache_taxa_taxon_lists cttl " .
      "join verification_rule_data vrd on vrd.header_name='Data' and upper(vrd.key)=cttl.external_key and vrd.deleted=false " .
      "join verification_rules vr on vr.id=vrd.verification_rule_id and vr.deleted=false " .
      "where cttl.id=cts.preferred_taxa_taxon_list_id and vr.id=?", [$id]);
}
