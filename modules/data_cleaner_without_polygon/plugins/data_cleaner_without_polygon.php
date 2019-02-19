<?php

/**
 * @file
 * Plugin file for the Data Cleaner WithoutPolygon module.
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
 * @package Data Cleaner
 * @subpackage Plugins
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link http://code.google.com/p/indicia/
 */

/**
 * Updates the cached copy of period within year rules.
 *
 * After saving a record, does an insert of the updated cache entry, helping
 * to impprove performance.
 */
function data_cleaner_without_polygon_cache_sql() {
  return <<<SQL
insert into cache_verification_rules_without_polygon
select distinct vr.id as verification_rule_id,
  vr.reverse_rule,
  vrmkey.value as taxa_taxon_list_external_key,
  vrd.value_geom as geom,
  vr.error_message
from verification_rules vr
join verification_rule_metadata vrmkey on vrmkey.key='DataRecordId' and vrmkey.deleted=false
join verification_rule_metadata isSpecies on isSpecies.value='Species' and isSpecies.deleted=false
join verification_rule_data vrd on vrd.verification_rule_id=vr.id and vrd.header_name='geom' and vrd.deleted=false
where vr.test_type='WithoutPolygon'
and vr.deleted=false
and vr.id=#id#;
SQL;
}

/**
 * Implements data_cleaner_rules().
 *
 * Hook into the data cleaner to declare checks for the difficulty of
 * identification of a species.
 *
 * @return array
 *   Definition of the rule.
 */
function data_cleaner_without_polygon_data_cleaner_rules() {
  $joinSql = <<<SQL
join cache_verification_rules_without_polygon vr on vr.taxa_taxon_list_external_key=co.taxa_taxon_list_external_key
left join cache_occurrences_functional o2 on o2.id<>co.id
  -- same species
  and o2.taxa_taxon_list_external_key=co.taxa_taxon_list_external_key
  -- accepted
  and o2.record_status='V'
  -- same grid square
  and o2.map_sq_10km_id=co.map_sq_10km_id
SQL;
  $whereSql = <<<SQL
vr.reverse_rule=(st_intersects(co.public_geom, vr.geom) and (st_geometrytype(co.public_geom)='ST_Point' or not st_touches(co.public_geom, vr.geom)))
SQL;
  $groupBy = <<<SQL
group by co.id, co.taxa_taxon_list_external_key,
  co.verification_checks_enabled, co.record_status, vr.error_message
-- at least 2 similar records
having count(o2.id) < 2
SQL;

  return array(
    'testType' => 'WithoutPolygon',
    'required' => array('Metadata' => array('DataFieldName', 'DataRecordId')),
    'optional' => array(
      '10km_GB' => array('*'),
      '10km_Ireland' => array('*'),
      '10km_CI' => array('*'),
      '1km_GB' => array('*'),
      '1km_Ireland' => array('*'),
      '1km_CI' => array('*'),
    ),
    'queries' => array(
      // Species identified by taxon version key (DataRecordId).
      array(
        'joins' => $joinSql,
        'where' => $whereSql,
        'groupBy' => $groupBy
      ),
    ),
  );
}

/**
 * Postprocessing for building a geom from the list of grid squares.
 *
 * Designed to make an SQL based check easy.
 */
function data_cleaner_without_polygon_data_cleaner_postprocess($id, $db) {
  $db->query('create temporary table geoms_without_polygon (geom geometry)');
  try {
    $r = $db->select('key, header_name')
      ->from('verification_rule_data')
      ->where('verification_rule_id', $id)
      ->in('header_name', array(
        '10km_GB', '10km_Ireland',
        '1km_GB',
        '1km_Ireland',
        '10km_CI',
        '1km_CI',
      ))
      ->get()->result();
    $wktList = array();
    foreach ($r as $gridSquare) {
      switch ($gridSquare->header_name) {
        case '10km_GB':
        case '1km_GB':
          $system = 'osgb';
          break;

        case '10km_Ireland':
        case '1km_Ireland':
          $system = 'osie';
          break;

        case '10km_CI':
        case '1km_CI':
          $system = 'utm30ed50';
          break;

        default:
          // We don't know this grid square type - should not have come back
          // from the query.
          continue;
      }
      $srid = kohana::config('sref_notations.internal_srid');
      try {
        $wktList[] = "(st_geomfromtext('" . spatial_ref::sref_to_internal_wkt($gridSquare->key, $system) . "', $srid))";
      }
      catch (Exception $e) {
        kohana::debug('alert', "Did not import grid square $gridSquare->key for rule $id");
        error_logger::log_error('Importing without polygon rules', $e);
      }
    }
    if (!empty($wktList)) {
      $db->query("insert into geoms_without_polygon values " . implode(',', $wktList));
    }
    $date = date("Ymd H:i:s");
    $uid = $_SESSION['auth_user']->id;
    $db->query("delete from verification_rule_data where verification_rule_id=$id and header_name='geom'");
    $db->query('insert into verification_rule_data (verification_rule_id, header_name, data_group, key, value, ' .
      'value_geom, created_on, created_by_id, updated_on, updated_by_id) ' .
      "select $id, 'geom', 1, 'geom', '-', st_union(geom), '$date', $uid, '$date', $uid from geoms_without_polygon");
    $db->query('drop table geoms_without_polygon');
  }
  catch (Exception $e) {
    $db->query('drop table geoms_without_polygon');
    throw $e;
  }
}
