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

/**
 * Declares the ecobat occurrences table is available via data services for
 * uploading into.
 * @return array
 */
function ecobat_extend_data_services() {
  return array(
    'ecobat_occurrences'=>array()
  );
}

/**
 * Plugs into the scheduled tasks system to build some of the additional field
 * values required in the ecobat occurrences table.
 * @param $last_run_date
 * @param $db
 */
function ecobat_scheduled_task($last_run_date, $db) {
  _ecobat_update_reporting_fields($last_run_date, $db);
  _ecobat_update_map_sq($last_run_date, $db);
  // Insert the occurrences that have been imported into the reference range which can be made public.
  // We'll only do this once a day.
  if (substr(date('c', time()), 0, 10) <> substr($last_run_date, 0, 10)) {
    echo 'Inserting ecobat occurrences<br/>';
    _ecobat_insert_occurrences($db);
  }
}

function _ecobat_update_reporting_fields($last_run_date, $db) {
  if ($last_run_date===null)
    // first run, so get all records changed in last day.
    $last_run_date=date('Y-m-d', time()-60*60*24);
  $db->query(<<<QRY
UPDATE ecobat_occurrences eo SET
  easting=ST_X(ST_Centroid(ST_Transform(geom, 27700))),
  northing=ST_Y(ST_Centroid(ST_Transform(geom, 27700))),
  external_key=cttl.external_key
FROM cache_taxa_taxon_lists cttl
WHERE eo.created_on>='$last_run_date'
AND cttl.id=eo.taxa_taxon_list_id
QRY
  );
}

function _ecobat_update_map_sq($last_run_date, $db) {
  static $srid;
  if (!isset($srid)) {
    $srid = kohana::config('sref_notations.internal_srid');
  }
  // Seems much faster to break this into small queries than one big left join.
  $requiredSquares = $db->query(
    "SELECT DISTINCT id as ecobat_occurrence_id, st_astext(geom) as geom,
          round(st_x(st_centroid(reduce_precision(geom, false, 10000, 'osgb')))) as x,
          round(st_x(st_centroid(reduce_precision(geom, false, 10000, 'osgb')))) as y
        FROM ecobat_occurrences
        WHERE geom is not null and created_on>='$last_run_date' and map_sq_10km_id is null")->result_array(TRUE);
  foreach ($requiredSquares as $s) {
    $existing = $db->query("SELECT id FROM map_squares WHERE x={$s->x} AND y={$s->y} AND size=10000")->result_array(FALSE);
    if (count($existing)===0) {
      $qry=$db->query("INSERT INTO map_squares (geom, x, y, size)
            VALUES (reduce_precision(st_geomfromtext('{$s->geom}', $srid), false, 10000, 'osgb'), {$s->x}, {$s->y}, 10000)");
      $msqId=$qry->insert_id();
    }
    else
      $msqId=$existing[0]['id'];
    $db->query("UPDATE ecobat_occurrences SET map_sq_10km_id=$msqId WHERE id={$s->ecobat_occurrence_id}");
  }
}

function _ecobat_insert_occurrences($db) {
  $smpAttrs = kohana::config('ecobat.sample_attrs');
  $occAttrs = kohana::config('ecobat.occurrence_attrs');
  $passTerms = kohana::config('ecobat.pass_terms');
  // Find up to 2000 occurrences that need to be generated
  $occs = $db->query('SELECT * from ecobat_occurrences WHERE occurrence_id IS NULL AND sensitivity<3 LIMIT 2000')
     ->result_array(FALSE);
  $lastSample = '';
  $allSampleFields = array_merge(array(
    'entered_sref',
    'entered_sref_system',
    'date_start',
    'group_id'
  ), array_keys($smpAttrs));
  echo count($occs) . ' ecobat occurrences to process<br/>';
  foreach($occs as $ecobat_occurrence) {
    $thisSampleFields = array_intersect_key($ecobat_occurrence, array_combine($allSampleFields, $allSampleFields));
    $thisSample = implode('|', $thisSampleFields);
    if ($thisSample!==$lastSample) {
      // if a new sample, create the sample record
      $s = array(
        'website_id' => kohana::config('ecobat.website_id'),
        'survey_id' => kohana::config('ecobat.survey_id'),
        'date_start'=>$ecobat_occurrence['date_start'],
        'date_end'=>$ecobat_occurrence['date_start'],
        'date_type'=>'D',
        'entered_sref' => $ecobat_occurrence['entered_sref'],
        'entered_sref_system' => $ecobat_occurrence['entered_sref_system'],
        'privacy_precision' => $ecobat_occurrence['sensitivity']===2 ? 10000 : null
      );
      foreach ($smpAttrs as $ecobatFieldName => $attrId) {
        $s[$attrId] = $ecobat_occurrence[$ecobatFieldName];
      }
      $sample = ORM::Factory('sample');
      $sample->set_submission_data($s);
      $sample->submit();
      if ($errors = $sample->getAllErrors()) {
        kohana::log('error', 'Unable to save ecobat sample: ' . var_export($s, true));
        foreach ($errors as $error)
          kohana::log('error', $error);
        continue;
      }
      // @todo Error Check
      $currentSampleId = $sample->id;
      $thisSample===$lastSample;
    }
    // create the occurrence record
    $s = array(
      'website_id' => kohana::config('ecobat.website_id'),
      'survey_id' => kohana::config('ecobat.survey_id'),
      'sample_id' => $currentSampleId,
      'taxa_taxon_list_id' => $ecobat_occurrence['taxa_taxon_list_id'],
      'sensitivity_precision' => $ecobat_occurrence['sensitivity']===2 ? 10000 : null
    );
    foreach ($occAttrs as $ecobatFieldName => $attrId) {
      $s[$attrId] = $passTerms[$ecobat_occurrence[$ecobatFieldName]];
    }
    $occurrence = ORM::Factory('occurrence');
    $occurrence->set_submission_data($s);
    $occurrence->submit();
    if ($errors = $occurrence->getAllErrors()) {
      kohana::log('error', 'Unable to save ecobat occurrence: ' . var_export($s, TRUE));
      foreach ($errors as $error) {
        kohana::log('error', $error);
      }
    } else {
      // Create a link between the 2 occurrence records
      $db->update('ecobat_occurrences',
        array('occurrence_id' => $occurrence->id),
        array('id' => $ecobat_occurrence['id'])
      );
    }
  }
}

?>