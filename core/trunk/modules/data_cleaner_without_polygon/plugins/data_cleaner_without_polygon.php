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
function data_cleaner_without_polygon_data_cleaner_rules() {
  return array(
    'testType' => 'WithoutPolygon',
    'required' => array('Metadata'=>array('DataFieldName','DataRecordId')),
    'optional' => array('10km_GB'=>array('*')),
    'queries' => array(
      array(
        'joins' => 
            "join verification_rule_metadata vrm on vrm.value=co.taxa_taxon_list_external_key and vrm.key='DataRecordId' ".
            "join verification_rules vr on vr.id=vrm.verification_rule_id and vr.test_type='WithoutPolygon' ".
            "join verification_rule_metadata isSpecies on isSpecies.value='Species' and isSpecies.key='DataFieldName' and isSpecies.verification_rule_id=vr.id ".
            "join verification_rule_data vrd on vrd.verification_rule_id=vr.id and vrd.header_name='geom' and not (vrd.value_geom && co.public_geom) "
      )
    )
  );
}

/** 
 * Postprocessing for building a geom from the list of grid squares to make an SQL based check easy
 */
function data_cleaner_without_polygon_data_cleaner_postprocess($id, $db) {
  $db->query('create temporary table geoms_without_polygon (geom geometry)');
  $r = $db->select('key')
    ->from('verification_rule_data')
    ->where(array('verification_rule_id'=>$id, 'header_name'=>'10km_GB'))
    ->get()->result();
  foreach($r as $gridSquare) {
    $wkt = spatial_ref::sref_to_internal_wkt($gridSquare->key, 'osgb');
    kohana::log('debug', "wkt $wkt");
    $db->query("insert into geoms_without_polygon values(st_geomfromtext('".$wkt."', ".kohana::config('sref_notations.internal_srid')."))");
  }
  $date=date("Ymd H:i:s");
  $uid=$_SESSION['auth_user']->id;
  $db->query("delete from verification_rule_data where verification_rule_id=$id and header_name='geom'");
  $db->query('insert into verification_rule_data (verification_rule_id, header_name, data_group, key, value, value_geom, created_on, created_by_id, updated_on, updated_by_id) '.
      "select $id, 'geom', 1, 'geom', '-', st_union(geom), '$date', $uid, '$date', $uid from geoms_without_polygon");
  // probably not necessary as it is dropped at end of session
  $db->query('drop table geoms_without_polygon');
}

?>