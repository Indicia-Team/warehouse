<?php

/**
 * @file
 * Helper class for synchronising records from an Indicia server.
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
 * @link https://github.com/indicia-team/warehouse/
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Helper class for extending the REST API with sync endpoints.
 */
class rest_api_sync_rest {

  public static function syncTaxonObservationsGet($foo, $clientConfig, $projectId) {
    $statuses = [
      'V0' => 'Accepted',
      'V1' => 'Accepted - correct',
      'V2' => 'Accepted - considered correct',
      'C0' => 'Unconfirmed - not reviewed',
      'C3' => 'Unconfirmed - plausible',
      'R0' => 'Not accepted',
      'R4' => 'Not accepted - unable to verify',
      'R5' => 'Not accepted - incorrect',
    ];
    error_logger::log_trace(debug_backtrace());
    if (empty($clientConfig['elasticsearch']) || count($clientConfig['elasticsearch']) !== 1) {
      RestObjects::$apiResponse->fail('Internal Server Error', 500, 'Incorrect elasticsearch configuration for client.');
    }
    $es = new RestApiElasticsearch($clientConfig['elasticsearch'][0]);
    $format = 'json';
    $response = json_decode($es->elasticRequest((object) ['size'=>30], $format, TRUE, '_search'));
    $total = count($response->hits->hits);
    $project = ($clientConfig && $projectId) ? $clientConfig['projects'][$projectId] : [];

    echo "[\n";
    foreach ($response->hits->hits as $idx => $hit) {
      $doc = $hit->_source;
      // Mandatory content.
      $obj = [
        'event' => [
          'eventDate' => $doc->event->date_start || ($doc->event->date_end === $doc->event->date_start ? '' : '|' . $doc->event->date_end),
          'eventId' => (!empty($project['id_prefix']) ? $project['id_prefix'] : '') . $doc->event->event_id,
        ],
        'identification' => [
          'identificationVerificationStatus' => $statuses[$doc->identification->verification_status . $doc->identification->verification_substatus],
        ],
        'location' => [],
        'occurrence' => [
          'occurrenceID' => (!empty($project['id_prefix']) ? $project['id_prefix'] : '') . $doc->id,
          'occurrenceStatus' => 'Present',
        ],
        'record-level' => [
          'basisOfRecord' => 'HumanObservation',
          'collectionCode' => $doc->metadata->website->title . ' | ' . $doc->metadata->survey->title,
          'datasetName' => !empty($project['title']) ? $project['title'] : 'Unknown',
        ],
        'taxon' => [
          'scientificName' => $doc->taxon->accepted_name,
          'taxonID' => $doc->taxon->taxon_id,
        ],
      ];
      // Optional stuff.
      // record-level datasetID
      // record-level dynamicProperties
      // occurrence associatedMedia
      // occurrence otherCatalogNumbers
      // occurrence reproductiveCondition
      // occurrence sensitivityBlur
      if (!empty($doc->identification->identified_by)) {
        $obj['identification']['identifiedBy'] = $doc->identification->identified_by;
      }
      if (!empty($doc->event->event_remarks)) {
        $obj['event']['eventRemarks'] = $doc->event->event_remarks;
      }
      if (!empty($doc->event->sampling_protocol)) {
        $obj['event']['samplingProtocol'] = $doc->event->sampling_protocol;
      }
      if (!empty($doc->location->coordinate_uncertainty_in_meters)) {
        $obj['location']['coordinateUncertaintyInMeters'] = $doc->location->coordinate_uncertainty_in_meters;
      }
      if (in_array($doc->location->input_sref_system, ['OSGB', 'OSI'])) {
        $obj['location']['gridReference'] = $doc->location->input_sref;
      }
      else {
        $point = explode(',', $doc->location->point);
        $obj['location']['decimalLatitude'] = $point[1];
        $obj['location']['decimalLongitude'] = $point[0];
        $obj['location']['geodeticDatum'] = 'WGS84';
      }
      if (!empty($doc->location->verbatim_locality)) {
        $obj['location']['locality'] = $doc->location->verbatim_locality;
      }
      if (!empty($doc->occurrence->individual_count)) {
        $obj['occurrence']['individualCount'] = $doc->occurrence->individual_count;
      }
      elseif (!empty($doc->occurrence->organism_quantity)) {
        $obj['occurrence']['individualCount'] = $doc->occurrence->organism_quantity;
      }
      if (!empty($doc->occurrence->life_stage)) {
        $obj['occurrence']['lifeStage'] = $doc->occurrence->life_stage;
      }
      if (!empty($doc->occurrence->occurrence_remarks)) {
        $obj['occurrence']['occurrenceRemarks'] = $doc->occurrence->occurrence_remarks;
      }
      if (!empty($doc->occurrence->recorded_by)) {
        $obj['occurrence']['recordedBy'] = $doc->occurrence->recorded_by;
      }
      if (!empty($doc->occurrence->sensitivity_precision)) {
        $obj['occurrence']['sensitivityBlur'] = $doc->occurrence->sensitivity_precision;
      }
      if (!empty($doc->occurrence->sex)) {
        $obj['occurrence']['sex'] = $doc->occurrence->sex;
      }
      if (!empty($doc->metadata->licence_code)) {
        $obj['record-level']['licence'] = $doc->metadata->licence_code;
      }
      echo json_encode($obj, JSON_PRETTY_PRINT);
      echo $idx < $total - 1 ? ',' : '';
    }
    echo "\n]";

  }

}
