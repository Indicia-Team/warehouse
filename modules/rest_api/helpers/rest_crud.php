<?php

/**
 * @file
 * Helper class for CRUD operations via the REST API.
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

/**
 * Create, Read, Update and Delete support for entities via the REST API.
 */
class rest_crud {

  /**
   * Defines submodels allowed in submission for each supported entity.
   *
   * @var array
   */
  private static $submodelsForEntities = [
    'sample' => [
      'samples' => 'parent_id',
      'occurrences' => 'sample_id',
      'media' => 'sample_id',
    ],
    'occurrence' => [
      'media' => 'occurrence_id',
    ],
    'location' => [
      'locations' => 'parent_id',
      'media' => 'location_id',
    ],
  ];

  /**
   * Defines SQL for fields selected by a GET for each entity.
   *
   * @var array
   */
  private static $fieldsForEntitySelects = [
    'occurrence' => 't1.id, t1.sample_id, t1.determiner_id, t1.confidential, t1.created_on, t1.created_by_id, ' .
      't1.updated_on, t1.updated_by_id, t1.website_id, t1.external_key, t1.comment, t1.taxa_taxon_list_id, ' .
      't1.record_status, t1.verified_by_id, t1.verified_on, t1.downloaded_flag, t1.downloaded_on, ' .
      't1.all_info_in_determinations, t1.zero_abundance, t1.last_verification_check_date, t1.training, ' .
      't1.sensitivity_precision, t1.release_status, t1.record_substatus, t1.record_decision_source, t1.import_guid, ' .
      't1.metadata, t2.id as taxa_taxon_list_id, t2.taxon, t2.preferred_taxon, t2.default_common_name, ' .
      't2.taxon_group, t2.external_key as taxa_taxon_list_external_key',
    'sample' => 't1.id, t1.survey_id, t1.location_id, t1.date_start, t1.date_end, t1.sample_method_id, ' .
      'st_astext(geom) as geom, t1.parent_id, t1.group_id, t1.privacy_precision, t1.verified_by_id, ' .
      't1.verified_on, t1.licence_id, t1.created_on, t1.created_by_id, t1.updated_on, t1.updated_by_id, ' .
      't1.date_type, t1.entered_sref, t1.entered_sref_system, t1.location_name, t1.external_key, t1.recorder_names, ' .
      't1.record_status, t1.input_form, t1.comment, ' .
      'st_y(st_transform(st_centroid(t1.geom), 4326)) as lat, st_y(st_transform(st_centroid(t1.geom), 4326)) as lon',
    'location' => 't1.id, t1.name, t1.code, t1.parent_id, t1.centroid_sref, t1.centroid_sref_system, t1.created_on, ' .
      't1.created_by_id, t1.updated_on, t1.updated_by_id, t1.comment, t1.external_key, t1.deleted, ' .
      'st_astext(t1.centroid_geom), st_astext(t1.boundary_geom), t1.location_type_id, t1.public, ' .
      'st_y(st_transform(st_centroid(t1.centroid_geom), 4326)) as lat, ' .
      'st_y(st_transform(st_centroid(t1.centroid_geom), 4326)) as lon',
  ];

  private static $joinsForEntitySelects = [
    'sample' => '',
    'occurrence' => 'JOIN cache_taxa_taxon_lists t2 on t2.id=t1.taxa_taxon_list_id',
    'location' => '',
  ];

  private static $entitiesWithAttributes = [
    'sample',
    'occurrence',
    'location',
  ];

  /**
   * Create (POST) operation.
   *
   * @param string $entity
   *   Entity name (singular).
   * @param array $data
   *   Submitted data, including values.
   */
  public static function create($entity, array $data) {
    $values = $data['values'];
    if (!empty($values['id'])) {
      RestObjects::$apiResponse->fail('Bad Request', 400, json_encode(["$entity:id" => 'Cannot POST with id to update, use PUT instead.']));
    }
    $obj = ORM::factory($entity);
    if (!empty($values['survey_id']) && !empty($values['external_key'])) {
      kohana::log('debug', 'Checking for duplicate external key');
      // No need to check without survey ID in post as it will fail validation anyway.
      self::checkDuplicateExternalKey($entity, $values);
    }
    self::submit($entity, $obj, $data);
  }

  /**
   * Read (GET) operation.
   *
   * @param string $entity
   *   Entity name (singular).
   * @param int $id
   *   Record ID to read.
   */
  public static function read($entity, $id) {
    $clientUserId = RestObjects::$clientUserId;
    $table = inflector::plural($entity);
    $fields = self::$fieldsForEntitySelects[$entity];
    $joins = self::$joinsForEntitySelects[$entity];
    $qry = <<<SQL
SELECT $fields
FROM $table t1
$joins
WHERE t1.id=$id
AND t1.created_by_id=$clientUserId
AND t1.deleted=false;
SQL;
    $row = RestObjects::$db->query($qry)->current();
    if ($row) {
      if (in_array($entity, self::$entitiesWithAttributes)) {
        // @todo Support for multi-value attributes.
        $qry = <<<SQL
SELECT a.id as attribute_id, av.id as value_id, a.caption, a.data_type,
  CASE a.data_type
    WHEN 'T'::bpchar THEN av.text_value
    WHEN 'L'::bpchar THEN t.term::text
    WHEN 'I'::bpchar THEN av.int_value::text ||
    CASE
      WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
      ELSE ''::text
    END
    WHEN 'B'::bpchar THEN av.int_value::text
    WHEN 'F'::bpchar THEN av.float_value::text ||
    CASE
      WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
      ELSE ''::text
    END
    WHEN 'D'::bpchar THEN av.date_start_value::text
    WHEN 'V'::bpchar THEN indicia.vague_date_to_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
    ELSE NULL::text
  END AS value,
  CASE a.data_type
    WHEN 'T'::bpchar THEN av.text_value
    WHEN 'L'::bpchar THEN av.int_value::text
    WHEN 'I'::bpchar THEN av.int_value::text
    WHEN 'B'::bpchar THEN av.int_value::text
    WHEN 'F'::bpchar THEN av.float_value::text
    WHEN 'D'::bpchar THEN av.date_start_value::text
    WHEN 'V'::bpchar THEN indicia.vague_date_to_raw_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
    ELSE NULL::text
  END AS raw_value,
  CASE
    WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN av.upper_value
    ELSE NULL::double precision
  END AS upper_value
FROM {$entity}_attribute_values av
JOIN {$entity}_attributes a on a.id=av.{$entity}_attribute_id and a.deleted=false
LEFT JOIN cache_termlists_terms t on a.data_type='L' and t.id=av.int_value
WHERE av.deleted=false;
SQL;
        $attrValues = RestObjects::$db->query($qry);
        $attrs = [];
        foreach ($attrValues as $attr) {
          // @Todo test
          $val = array_key_exists('verbose', $_GET) ? $attr : $attr->value;
          $attrs["smpAttr:$attr->attribute_id"] = $val;
        }
        $row = array_merge((array) $row, $attrs);

      }

      RestObjects::$apiResponse->succeed(['values' => self::getValuesForResponse($row)]);
    }
    else {
      RestObjects::$apiResponse->fail('Not found', 404);
    }
  }

  /**
   * Update (PUT) operation.
   *
   * @param string $entity
   *   Entity name (singular).
   * @param int $id
   *   Record ID to update.
   * @param array $data
   *   Submitted data, including values.
   */
  public static function update($entity, $id, array $data) {
    $values = $data['values'];
    // ID is optional, but must match URL segment.
    if (!empty($values['id'])) {
      if ($values['id'] != $id) {
        RestObjects::$apiResponse->fail('Bad Request', 400, json_encode(["$entity:id" => 'Provided id does not match URI']));
      }
    }
    $obj = ORM::factory($entity, $id);
    self::checkETags($entity, $id);
    if (!empty($values['external_key']) && (string) $values['external_key'] !== $obj->external_key) {
      self::checkDuplicateExternalKey($entity, array_merge($obj->as_array(), $values));
    }
    if ($obj->created_by_id !== RestObjects::$clientUserId) {
      RestObjects::$apiResponse->fail('Not Found', 404);
    }
    // Keep existing values unless replaced by PUT data.
    $data['values'] = array_merge(
      $obj->as_array(),
      $values
    );
    self::submit($entity, $obj, $data);
  }

  /**
   * Delete (DELETE) operation.
   *
   * @param string $entity
   *   Entity name (singular).
   * @param int $id
   *   Record ID to delete.
   * @param array $preconditions
   *   List of fields & values to check before allowing the deletion, e.g.
   *   created_by_id=current user.
   */
  public static function delete($entity, $id, array $preconditions = []) {
    $obj = ORM::factory($entity, $id);
    $proceed = TRUE;
    // Must exist and belong to the user.
    if (!$obj->id) {
      $proceed = FALSE;
    }
    if ($proceed) {
      foreach ($preconditions as $field => $value) {
        $proceed = $proceed && $obj->$field == $value;
      }
    }
    if ($proceed) {
      $obj->deleted = 't';
      $obj->set_metadata();
      $obj->save();
      http_response_code(204);
    } else {
      RestObjects::$apiResponse->fail('Not found', 404);
    }
  }

  /**
   * Coverts new REST API submission format to old Data Services format.
   *
   * @param string $entity
   *   Model name.
   * @param array $postObj
   *   Posted submission to convert.
   *
   * @return array
   *   Converted submission.
   */
  public static function convertNewToOldSubmission($entity, array $postObj, $websiteId) {
    $s = [
      'id' => $entity,
      'fields' => [],
    ];
    foreach ($postObj['values'] as $field => $value) {
      $s['fields'][$field] = ['value' => $value];
    }
    if (isset(self::$submodelsForEntities[$entity])) {
      $submodels = array_intersect_key(self::$submodelsForEntities[$entity], $postObj);
      if (!isset($s['subModels'])) {
        $s['subModels'] = [];
      }
      foreach ($submodels as $submodel => $fk) {
        foreach ($postObj[$submodel] as $obj) {
          if ($submodel === 'occurrences') {
            $obj['values']['website_id'] = $websiteId;
          }
          elseif ($submodel === 'media') {
            // Media submodel doesn't need prefix for simplicity.
            $submodel = "{$entity}_media";
          }
          $subModel = [
            'fkId' => $fk,
            'model' => self::convertNewToOldSubmission(inflector::singular($submodel), $obj, $websiteId),
          ];
          $s['subModels'][] = $subModel;
        }
      }
    }
    return $s;
  }

  /**
   * Fails if there is existing record with same external key.
   *
   * @param int $survey_id
   *   ID of survey dataset.
   * @param array $values
   *   VAalues, including the external_key.
   */
  private static function checkDuplicateExternalKey($entity, $values) {
    $table = inflector::plural($entity);
    // Sample external key only needs to be unique within survey.
    // @todo Same for occurrences.
    $extraFilter = $entity === 'sample' ? " and survey_id=$values[survey_id]" : '';
    $hit = RestObjects::$db
      ->query("select 1 from $table where external_key='$values[external_key]'$extraFilter")
      ->current();
    kohana::log('debug', RestObjects::$db->last_query());
    if ($hit) {
      RestObjects::$apiResponse->fail('Conflict', 409, 'Duplicate external_key would be created');
    }
  }

  private static function checkETags($entity, $id) {
    $headers = apache_request_headers();
    if (isset($headers['If-Match'])) {
      $table = inflector::plural($entity);
      // A precondition based on ETag which must be met.
      $ETag = RestObjects::$db->query("SELECT xmin FROM $table WHERE id=$id")->current()->xmin;
      if ($headers['If-Match'] !== $ETag) {
        RestObjects::$apiResponse->fail('Precondition Failed', 412, 'If-Match condition not met. Record may have been updated by another user.');
      }
    }
  }

  /**
   * Retrieve the values from an associative data array to return from API.
   *
   * Dates will be ISO formatted.
   *
   * @param mixed $data
   *   Associative array or object of field names and values.
   * @param array $fields Optional list of fields to restrict to.
   *
   * @return array
   *   Associative array of field names and values.
   */
  private static function getValuesForResponse($data, array $fields = NULL) {
    if (is_object($data)) {
      $data = (array) $data;
    }
    $values = $fields ?  array_intersect_key($data, array_flip($fields)) : $data;
    foreach ($values as $field => &$value) {
      if (substr($field, -3) === '_on') {
        // Date values need reformatting.
        $value = date('c', strtotime($value));
      }
      if (substr($field, -10) === 'date_start') {
        $prefix = substr($field, 0, strlen($field) - 10);
        $values["{$prefix}date"] = vague_date::vague_date_to_string([$value, $values["{$prefix}date_end"], $values["{$prefix}date_type"]]);
      }
    }
    return $values;
  }

  /**
   * Converts submission response metadata provided by ORM into a REST response object.
   *
   * @param array $responseMetadata
   *   Data provided describing the results of a submission.
   *
   * @return array
   *   Reformatted data.
   */
  private static function getResponseMetadata(array $responseMetadata) {
    $entity = $responseMetadata['model'];
    $table = inflector::plural($entity);
    $href = url::base() . "index.php/services/rest/$table/$responseMetadata[id]";
    $r = [
      'values' => self::getValuesForResponse($responseMetadata, ['id', 'created_on', 'updated_on']),
      'href' => $href,
    ];
    // Recursively process sub-model info.
    if (!empty(self::$submodelsForEntities[$entity]) && !empty($responseMetadata['children'])) {
      foreach ($responseMetadata['children'] as $child) {
        $subTable = inflector::plural($child['model']);
        if (preg_match('/_media$/', $subTable)) {
          $subTable = 'media';
        }
        if (array_key_exists($subTable, self::$submodelsForEntities[$entity])) {
          if (!isset($r[$subTable])) {
            $r[$subTable] = [];
          }
          $r[$subTable][] = self::getResponseMetadata($child);
        }
      }
    }
    return $r;
  }

  /**
   * Function to save a submission into a sample model.
   *
   * The API response is echoed and appropriate http status set.
   *
   * @param obj $obj
   *   ORM object.
   * @param array $postObj
   *   Submission data.
   */
  private static function submit($entity, $obj, $postObj) {
    $obj->submission = rest_crud::convertNewToOldSubmission($entity, $postObj, RestObjects::$clientWebsiteId);
    // Different http code for create vs update.
    $httpCodeOnSuccess = $obj->id ? 200 : 201;
    $id = $obj->submit();
    if ($id) {
      http_response_code($httpCodeOnSuccess);
      $table = inflector::plural($entity);
      // ETag to provide version check on updates.
      $ETag = RestObjects::$db->query("SELECT xmin FROM $table WHERE id=$id")->current()->xmin;
      header("ETag: $ETag");
      // Include href and basic record metadata.
      $responseMetadata = $obj->getSubmissionResponseMetadata();
      $reformattedResponse = self::getResponseMetadata($responseMetadata);
      if ($httpCodeOnSuccess === 201) {
        // Location header points to created resource.
        header("Location: $reformattedResponse[href]");
      }
      echo json_encode($reformattedResponse);
    } else {
      RestObjects::$apiResponse->fail('Bad Request', 400, $obj->getAllErrors());
    }
  }

}