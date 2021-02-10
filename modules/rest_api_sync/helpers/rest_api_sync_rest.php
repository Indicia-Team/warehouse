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

  /**
   * Attribute types to exclude, either for privacy or duplication reasons.
   *
   * @var array
   */
  private static $discardedAttributeTypes = [
    'email',
    'cms_user_id',
    'cms_username',
    'sref_precision',
    'linked_location_id',
    'sample_method',
    'det_first_name',
    'det_full_name',
    'det_last_name',
    'sex_stage',
    'reproductive_condition',
    'sex',
    'stage',
    'sex_stage_count',
  ];

  /**
   * Record status mappings.
   *
   * @var array
   */
  private static $statuses = [
    'V0' => 'Accepted',
    'V1' => 'Accepted - correct',
    'V2' => 'Accepted - considered correct',
    'C0' => 'Unconfirmed - not reviewed',
    'C3' => 'Unconfirmed - plausible',
    'R0' => 'Not accepted',
    'R4' => 'Not accepted - unable to verify',
    'R5' => 'Not accepted - incorrect',
  ];

  public static function syncTaxonObservationsGet($foo, $clientConfig, $projectId) {
    if (empty($clientConfig['elasticsearch']) || count($clientConfig['elasticsearch']) !== 1) {
      RestObjects::$apiResponse->fail('Internal Server Error', 500, 'Incorrect elasticsearch configuration for client.');
    }
    $project = ($clientConfig && $projectId) ? $clientConfig['projects'][$projectId] : [];
    $response = self::getEsTaxonObservationsResponse($clientConfig, $project);
    $total = count($response->hits->hits);
    echo "[\n";
    foreach ($response->hits->hits as $idx => $hit) {
      $doc = $hit->_source;
      $obj = self::getBasicObservationStructure($doc);
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
      if (!empty($doc->occurrence->media)) {
        $paths = [];
        foreach ($doc->occurrence->media as $file) {
          $paths[] = substr($file->path, 0, 4) === 'http' ? $file->path : url::site() . 'upload/' . $file->path;
        }
        $obj['occurrence']['associatedMedia'] = implode('|', $paths);
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
      if (!empty($doc->occurrence->source_system_key)) {
        $obj['occurrence']['otherCatalogNumbers'] = $doc->occurrence->source_system_key;
      }
      if (!empty($doc->occurrence->recorded_by)) {
        $obj['occurrence']['recordedBy'] = $doc->occurrence->recorded_by;
      }
      if (!empty($doc->occurrence->reproductive_condition)) {
        $obj['occurrence']['reproductiveCondition'] = $doc->occurrence->reproductive_condition;
      }
      if (!empty($doc->occurrence->sensitivity_precision)) {
        $obj['occurrence']['sensitivityBlur'] = $doc->occurrence->sensitivity_precision;
      }
      if (!empty($doc->occurrence->sex)) {
        $obj['occurrence']['sex'] = $doc->occurrence->sex;
      }
      if (!empty($project['dataset_id_attr_id']) && !empty($doc->event->attributes)) {
        foreach ($doc->event->attributes as $attr) {
          if ($attr->id == $project['dataset_id_attr_id']) {
            $obj['record-level']['datasetID'] = $attr->value;
          }
        }
      }
      // Dynamic properties will contain all custom attribute data unless it
      // contains personal data, or for a system function that is output
      // elsewhere.
      $properties = [];
      if (!empty($doc->event->attributes)) {
        foreach ($doc->event->attributes as $attr) {
          if (empty($project['dataset_id_attr_id']) || ($attr->id != $project['dataset_id_attr_id'])) {
            $attrObj = ORM::factory('sample_attribute', $attr->id);
            if (empty($attrObj->system_function) || !in_array($attrObj->system_function, self::$discardedAttributeTypes)) {
              $properties[$attrObj->caption] = $attr->value;
            }
          }
        }
      }
      if (!empty($doc->occurrence->attributes)) {
        foreach ($doc->occurrence->attributes as $attr) {
          $attrObj = ORM::factory('occurrence_attribute', $attr->id);
          if (empty($attrObj->system_function) || !in_array($attrObj->system_function, self::$discardedAttributeTypes)) {
            $properties[$attrObj->caption] = $attr->value;
          }
        }
      }
      if (count($properties) > 0) {
        $obj['record-level']['dynamicProperties'] = $properties;
      }
      if (!empty($doc->metadata->licence_code)) {
        $obj['record-level']['licence'] = $doc->metadata->licence_code;
      }
      echo json_encode($obj, JSON_PRETTY_PRINT);
      if ($idx < $total - 1) {
        echo ',';
      }
      else {
        variable::set("rest-api-sync-tx-obs-$projectId", $doc->metadata->tracking);
      }
    }
    echo "\n]";
  }

  /**
   * Returns the next chunk of ES documents for a client.
   */
  private static function getEsTaxonObservationsResponse($clientConfig, $project) {
    $es = new RestApiElasticsearch($clientConfig['elasticsearch'][0]);
    $format = 'json';
    if (isset($_GET['tracking_from']) ) {
      if (!preg_match('/^\d+$/', $_GET['tracking_from'])) {
        RestObjects::$apiResponse->fail('Bad Request', 400, 'Invalid tracking from parameter');
      }
      $trackingFrom = $_GET['tracking_from'];
      unset($_GET['tracking_from']);
    }
    else {
      $trackingFrom = variable::get("rest-api-sync-tx-obs-$project[id]", 0);
    }
    $query = [
      'bool' => [
        'must' => [
          ['exists' => ['field' => 'taxon.taxon_id']],


// Sensitivity_blur empty or F/B.



        ],
      ],
    ];
    if (isset($project['es_bool_query'])) {
      foreach ($project['es_bool_query'] as $class => $filters) {
        if (!isset($query['bool'][$class])) {
          $query['bool'][$class] = [];
        }
        // Filters can be associative array if multiple, or just single item.
        if (array_keys($filters) === range(0, count($filters) - 1)) {
          $query['bool'][$class] = array_merge($query['bool'][$class], $filters);
        }
        else {
          $query['bool'][$class][] = $filters;
        }
      }
    }
    return json_decode($es->elasticRequest((object) [
      'size' => 2,
      'sort' => [
        ['metadata.tracking' => ['order' => 'asc']],
      ],
      'search_after' => [$trackingFrom],
      'query' => $query,
    ], $format, TRUE, '_search'));
  }

  /**
   * Returns the basic taxon observation structure.
   *
   * Includes mandatory attributes.
   *
   * @param object $doc
   *   Elasticsearch document.
   *
   * @return array
   *   Taxon observation structure.
   */
  private static function getBasicObservationStructure($doc) {
    return [
      'event' => [
        'eventDate' => $doc->event->date_start || ($doc->event->date_end === $doc->event->date_start ? '' : '|' . $doc->event->date_end),
        'eventId' => (!empty($project['id_prefix']) ? $project['id_prefix'] : '') . $doc->event->event_id,
      ],
      'identification' => [
        'identificationVerificationStatus' => self::$statuses[$doc->identification->verification_status . $doc->identification->verification_substatus],
      ],
      'location' => [],
      'metadata' => [
        'tracking' => $doc->metadata->tracking,
      ],
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
        'scientificName' => isset($doc->taxon->accepted_name) ? $doc->taxon->accepted_name : $doc->taxon->taxon_name,
        'taxonID' => $doc->taxon->taxon_id,
      ],
    ];
  }

}
