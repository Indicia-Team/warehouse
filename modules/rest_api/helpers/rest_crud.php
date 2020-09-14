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
   * Cache parsed entity configurations loaded from JSON files.
   *
   * @var array
   */
  private static $entityConfig = [];

  /**
   * Create (POST) operation.
   *
   * @param string $entity
   *   Entity name (singular).
   * @param array $data
   *   Submitted data, including values.
   */
  public static function create($entity, array $data) {
    self::loadEntityConfig($entity);
    $values = $data['values'];
    if (!empty($values['id'])) {
      RestObjects::$apiResponse->fail('Bad Request', 400, json_encode(["$entity:id" => 'Cannot POST with id to update, use PUT instead.']));
    }
    $obj = ORM::factory($entity);
    if (in_array($entity, ['occurrence', 'sample']) && !empty($values['external_key'])) {
      self::checkDuplicateExternalKey($entity, $values);
    }
    self::submit($entity, $obj, $data);
  }

  /**
   * Retrieve the configuration for the current entity from JSON file.
   *
   * @param string $entity
   *   Entity name (singular).
   */
  private static function loadEntityConfig($entity) {
    if (!isset(self::$entityConfig[$entity])) {
      $config = json_decode(file_get_contents(dirname(dirname(__FILE__)) . "/entities/$entity.json"));
      if (!$config) {
        RestObjects::$apiResponse->fail('Internal Server Error', 500, "JSON entity definition for $entity invalid.");
      }
      self::$entityConfig[$entity] = $config;
    }
  }

  /**
   * Gets the list of SQL field strings for one table in a query.
   *
   * @param array $fields
   *   Field list from configuration for entity or join.
   * @param string $alias
   *   Table alias to use.
   *
   * @return array
   *   List of SQL field strings to include in SELECT.
   */
  private static function getSqlFieldsForOneTable($fields, $alias) {
    $list = [];
    foreach ($fields as $fieldDef) {
      // If field SQL is a simple fieldname we can alias it to the table.
      $fieldSql = preg_match('/^[a-z_]+$/', $fieldDef->sql) ? "$alias.$fieldDef->sql" : $fieldDef->sql;
      // Add named alias if required.
      if (!empty($fieldDef->name)) {
        $fieldSql .= " AS $fieldDef->name";
      }
      $list[] = $fieldSql;
    }
    return $list;
  }

  /**
   * Retrieves the SQL for a list of fields for an entity's SELECT.
   *
   * @param string $entity
   *   Entity name.
   */
  private static function getSqlFields($entity) {
    self::loadEntityConfig($entity);
    $list = self::getSqlFieldsForOneTable(self::$entityConfig[$entity]->fields, 't1');
    if (!empty(self::$entityConfig[$entity]->joins)) {
      foreach (self::$entityConfig[$entity]->joins as $idx => $joinDef) {
        // Joined table alias starts at 2.
        $alias = 't' . ($idx + 2);
        $list = array_merge($list, self::getSqlFieldsForOneTable($joinDef->fields, $alias));
      }
    }
    return implode(', ', $list);
  }

  /**
   * Retrieves the SQL for a list of joins for an entity's SELECT.
   *
   * @param string $entity
   *   Entity name.
   */
  private static function getSqlJoins($entity) {
    self::loadEntityConfig($entity);
    $list = [];
    if (!empty(self::$entityConfig[$entity]->joins)) {
      foreach (self::$entityConfig[$entity]->joins as $idx => $joinDef) {
        $list[] = $joinDef->sql;
      }
    }
    return implode ("\n", $list);
  }

  /**
   * Gets the list of SQL field definitions for filtering one table in a query.
   *
   * @param array $fields
   *   Field list from configuration for entity or join.
   * @param string $alias
   *   Table alias to use.
   *
   * @return array
   *   List of SQL field definitions containing information required to filter,
   *   keyed by field name.
   */
  private static function getFilterFieldsForOneTable($fields, $alias) {
    $list = [];
    foreach ($fields as $fieldDef) {
      // If field SQL is a simple fieldname we can alias it to the table.
      $fieldSql = preg_match('/^[a-z_]+$/', $fieldDef->sql) ? "$alias.$fieldDef->sql" : $fieldDef->sql;
      $fieldName = empty($fieldDef->name) ? $fieldDef->sql : $fieldDef->name;
      $list[$fieldName] = [
        'sql' => $fieldSql,
        'type' => $fieldDef->type,
      ];
    }
    return $list;
  }

  /**
   * Gets the SQL field definitions for filtering against any table in a query.
   *
   * @param string $entity
   *   Entity name.
   *
   * @return array
   *   List of SQL field definitions containing information required to filter,
   *   keyed by field name.
   */
  private static function getAvailableFilterFields($entity) {
    self::loadEntityConfig($entity);
    $list = self::getFilterFieldsForOneTable(self::$entityConfig[$entity]->fields, 't1');
    if (!empty(self::$entityConfig[$entity]->joins)) {
      foreach (self::$entityConfig[$entity]->joins as $idx => $joinDef) {
        // Joined table alias starts at 2.
        $alias = 't' . ($idx + 2);
        $list = array_merge($list, self::getFilterFieldsForOneTable($joinDef->fields, $alias));
      }
    }
    return $list;
  }

  /**
   * Builds the SQL required for a read operation.
   *
   * @param string $entity
   *   Singular name of the entity being read.
   * @param string $extraFilter
   *   SQL for any additional filters required.
   * @param bool $userFilter
   *   Set to TRUE to restrict the output to rows where created_by_id is the
   *   authenticated user.
   *
   * @return string
   *   SQL statement.
   */
  private static function getReadSql($entity, $extraFilter, $userFilter) {
    $table = inflector::plural($entity);
    $fields = self::getSqlFields($entity);
    $joins = self::getSqlJoins($entity);
    $createdByFilter = $userFilter ? 'AND t1.created_by_id=' . RestObjects::$clientUserId : '';
    if (!empty($_GET)) {
      // Apply query string parameters.
      $availableFilterFields = self::getAvailableFilterFields($entity);
      foreach ($_GET as $param => $value) {
        if (isset($availableFilterFields[$param])) {
          if (in_array($availableFilterFields[$param]['type'], ['string', 'date', 'json'])) {
            $value = pg_escape_literal($value);
          }
          elseif (in_array($availableFilterFields[$param]['type'], ['integer', 'float'])) {
            if (!is_numeric($value)) {
              RestObjects::$apiResponse->fail('Bad Request', 400, "Invalid filter on numeric field $param");
            }
          }
          else {
            RestObjects::$apiResponse->fail('Internal Server Error', 400, "Unsupported field type for $param");
          }
          $extraFilter .= "\nAND " . $availableFilterFields[$param]['sql'] . "=$value";
        }
      }
    }
    return <<<SQL
SELECT t1.xmin, $fields
FROM $table t1
$joins
WHERE t1.deleted=false
$createdByFilter
$extraFilter

SQL;
  }

  /**
   * Read (GET) operation for a list.
   *
   * @param string $entity
   *   Entity name (singular).
   * @param string $extraFilter
   *   Additional filter SQL, e.g. where a website ID limit required.
   * @param bool $userFilter
   *   Should a filter on created_by_id be applied? Default TRUE.
   *
   * @todo Support for attribute values + verbose option.
   * @todo Support for reading a survey structure including attribute metadata.
   * @todo Add test case
   */
  public static function readList($entity, $extraFilter, $userFilter = TRUE) {
    $qry = self::getReadSql($entity, $extraFilter, $userFilter);
    $rows = RestObjects::$db->query($qry);
    $r = [];
    foreach ($rows as $row) {
      unset($row->xmin);
      $r[] = ['values' => (array) $row];
    }
    RestObjects::$apiResponse->succeed($r);
  }

  /**
   * Read (GET) operation.
   *
   * @param string $entity
   *   Entity name (singular).
   * @param int $id
   *   Record ID to read.
   * @param string $extraFilter
   *   Additional filter SQL, e.g. where a website ID limit required.
   * @param bool $userFilter
   *   Should a filter on created_by_id be applied? Default TRUE.
   */
  public static function read($entity, $id, $extraFilter = '', $userFilter = TRUE) {
    $qry = self::getReadSql($entity, $extraFilter, $userFilter);
    $qry .= "AND t1.id=$id";
    $row = RestObjects::$db->query($qry)->current();
    if ($row) {
      // Transaction ID that last updated row is returned as ETag header.
      header("ETag: $row->xmin");
      unset($row->xmin);
      if (!empty(self::$entityConfig[$entity]->attributes)) {
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
WHERE av.deleted=false
AND av.{$entity}_id=$id;
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
    // Must exist and match preconditions (e.g. belong to user).
    if (!$obj->id || $obj->deleted === 't') {
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

  private static function includeSubmodels($entity, array $postObj, $websiteId, &$s) {
    $subModels = [];
    if (isset(self::$entityConfig[$entity]->subModels)) {
      $subModels = array_intersect_key((array) self::$entityConfig[$entity]->subModels, $postObj);
      unset($subModels['values']);
      // Include missing subModels if tagged as required.
      foreach (self::$entityConfig[$entity]->subModels as $subModelTable => $subModel) {
        if (!empty($subModel->required) && empty($subModels[$subModelTable])) {
          $subModels[$subModelTable] = $subModel;
          // Add a stub to make code simpler.
          $postObj[$subModelTable] = [
            ['values' => []]
          ];
        }
      }
    }
    foreach ($subModels as $subModelTable => $subModelCfg) {
      foreach ($postObj[$subModelTable] as $obj) {
        if ($subModelTable === 'media') {
          // Media subModel doesn't need prefix for simplicity.
          $subModelTable = "{$entity}_media";
        }
        $s['subModels'][] = [
          'fkId' => $subModelCfg->fk,
          'model' => self::convertNewToOldSubmission(inflector::singular($subModelTable), $obj, $websiteId),
        ];
      }
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
    self::loadEntityConfig($entity);
    $s = [
      'id' => $entity,
      'fields' => [],
    ];
    if (!isset($postObj['values'])) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Incorrect submission format');
    }
    foreach ($postObj['values'] as $field => $value) {
      $s['fields'][$field] = ['value' => $value];
    }
    if (isset(self::$entityConfig[$entity]->forceValuesOnCreate)) {
      foreach ((array) self::$entityConfig[$entity]->forceValuesOnCreate as $field => $value) {
        $value = ($value === '{website_id}') ? RestObjects::$clientWebsiteId : $value;
        $s['fields'][$field] = ['value' => $value];
      }
    }
    if (isset(self::$entityConfig[$entity]->subModels)) {
      $s['subModels'] = [];
    }
    self::includeSubmodels($entity, $postObj, $websiteId, $s);
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
    switch ($entity) {
      case 'sample':
        $extraFilter = " and survey_id=$values[survey_id]";
        break;

      case 'occurrence':
        $extraFilter = " and website_id=$values[website_id]";
        break;

      default:
        $extraFilter = '';
    }
    $hit = RestObjects::$db
      ->query("select id from $table where external_key='$values[external_key]'$extraFilter")
      ->current();
    if ($hit) {
      $href = url::base() . "index.php/services/rest/$table/$hit->id";
      RestObjects::$apiResponse->fail('Conflict', 409, 'Duplicate external_key would be created', ['duplicate_of' => [
        'id' => $hit->id,
        'href' => $href,
      ]]);
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
    if (!empty(self::$entityConfig[$entity]->subModels) && !empty($responseMetadata['children'])) {
      foreach ($responseMetadata['children'] as $child) {
        $subTable = inflector::plural($child['model']);
        if (preg_match('/_media$/', $subTable)) {
          $subTable = 'media';
        }
        if (array_key_exists($subTable, self::$entityConfig[$entity]->subModels)) {
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