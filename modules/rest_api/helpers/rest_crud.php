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
   * List of definitions for fields required by a GET request.
   *
   * @var array
   */
  private static $fieldDefs;

  private static $verboseExtraInfoQueries = [
    'group' => [
      'pages' => <<<SQL
        SELECT gp.group_id as id, gp.caption, gp.path
        FROM group_pages gp
        LEFT JOIN groups_users gu ON gu.group_id=gp.group_id
          AND gu.deleted=false
          AND gu.pending=false
          AND gu.user_id={{ user_id }}
        WHERE gp.group_id IN ({{ ids }})
        AND (
          gp.administrator IS NULL
          OR (gp.administrator=false AND gu.id IS NOT NULL)
          OR gp.administrator=true AND gu.administrator=true
        )
        AND (
          gp.access_level IS NULL
          OR gu.administrator=true
          OR COALESCE(gu.access_level, 0)>=COALESCE(gp.access_level, 0)
        )
SQL,
    ]
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
    self::loadEntityConfig($entity);
    if (empty($data['values'])) {
      RestObjects::$apiResponse->fail('Bad Request', 400, json_encode(["$entity:id" => 'Values not submitted when attempting to POST.']));
    }
    $values = $data['values'];
    if (!empty($values['id'])) {
      RestObjects::$apiResponse->fail('Bad Request', 400, json_encode(["$entity:id" => 'Cannot POST with id to update, use PUT instead.']));
    }
    if (empty(RestObjects::$clientUserId)) {
      $allowAnonPostCheck = RestObjects::$db->query('SELECT allow_anon_jwt_post FROM websites WHERE deleted=false AND id=' . RestObjects::$clientWebsiteId)->current();
      if ($allowAnonPostCheck->allow_anon_jwt_post === 'f') {
        RestObjects::$apiResponse->fail('Bad Request', 400, json_encode(["$entity:created_by_id" => 'Cannot POST without user authentication.']));
      }
    }
    $obj = ORM::factory($entity);
    if (isset(self::$entityConfig[$entity]->duplicateCheckFields)) {
      self::checkDuplicateFields($entity, $values, $data);
    }
    return self::submit($entity, $obj, $data);
  }

  /**
   * Retrieve the configuration for the current entity from JSON file.
   *
   * @param string $entity
   *   Entity name (singular).
   */
  private static function loadEntityConfig($entity) {
    if (!isset(self::$entityConfig[$entity])) {
      $folder = dirname(dirname(dirname(__FILE__))) . '/' . RestObjects::$handlerModule;
      $config = json_decode(file_get_contents("$folder/entities/$entity.json"));
      if (!$config) {
        RestObjects::$apiResponse->fail('Internal Server Error', 500, "JSON entity definition for $entity invalid.");
      }
      self::$entityConfig[$entity] = $config;
    }
  }

  /**
   * Retrieves a list of definitions for fields required by a GET request.
   *
   * Populates self::$fieldDefs with a list of SQL field definitions keyed by
   * field name.
   *
   * @param string $entity
   *   Entity name.
   */
  private static function loadFieldDefs($entity) {
    if (empty(self::$fieldDefs)) {
      self::getFieldDefsForOneTable(self::$entityConfig[$entity]->fields, 't1');
      if (!empty(self::$entityConfig[$entity]->joins)) {
        foreach (self::$entityConfig[$entity]->joins as $idx => $joinDef) {
          // Joined table alias starts at 2.
          $alias = 't' . ($idx + 2);
          self::getFieldDefsForOneTable($joinDef->fields, $alias);
        }
      }
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
  private static function getSqlFieldsForOneTable(array $fields, $alias) {
    $list = [];
    foreach ($fields as $fieldDef) {
      // If field SQL is a simple fieldname we can alias it to the table.
      $fieldSql = preg_match('/^[a-z_]+$/', $fieldDef->sql) ? "$alias.$fieldDef->sql" : $fieldDef->sql;
      // Arrays can be json formatted.
      if (!empty($fieldDef->array)) {
        $fieldSql = 'array_to_json(' . $fieldSql . ')';
      }
      // Add named alias if required.
      if (!empty($fieldDef->name)) {
        $fieldSql .= " AS $fieldDef->name";
      }
      elseif (!empty($fieldDef->array)) {
        $fieldSql .= " AS $fieldDef->sql";
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
    $list = [];
    if (!empty(self::$entityConfig[$entity]->joins)) {
      foreach (self::$entityConfig[$entity]->joins as $joinDef) {
        $list[] = $joinDef->sql;
      }
    }
    return implode("\n", $list);
  }

  /**
   * Gets the list of SQL field definitions for filtering one table in a query.
   *
   * Populates self::$fieldDefs with a list of SQL field definitions keyed by
   * field name, for a single table.
   *
   * @param array $fields
   *   Field list from configuration for entity or join.
   * @param string $alias
   *   Table alias to use.
   */
  private static function getFieldDefsForOneTable(array $fields, $alias) {
    foreach ($fields as $fieldDef) {
      // If field SQL is a simple fieldname we can alias it to the table.
      $fieldSql = preg_match('/^[a-z_]+$/', $fieldDef->sql) ? "$alias.$fieldDef->sql" : $fieldDef->sql;
      $fieldName = empty($fieldDef->name) ? $fieldDef->sql : $fieldDef->name;
      self::$fieldDefs[$fieldName] = array_merge((array) $fieldDef, ['sql' => $fieldSql]);
    }
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
    self::loadFieldDefs($entity);
    $fields = self::getSqlFields($entity);
    $joins = self::getSqlJoins($entity);
    $createdByFilter = $userFilter ? 'AND t1.created_by_id=' . RestObjects::$clientUserId : '';
    if (!empty($_GET)) {
      // Apply query string parameters.
      foreach ($_GET as $param => $value) {
        if (isset(self::$fieldDefs[$param])) {
          if (in_array(self::$fieldDefs[$param]['type'], [
            'string',
            'date',
            'time',
            'json',
            'boolean',
          ])) {
            RestObjects::$db->connect();
            $value = pg_escape_literal(RestObjects::$db->getLink(), $value);
          }
          elseif (in_array(self::$fieldDefs[$param]['type'], [
            'integer',
            'float',
          ])) {
            if (!is_numeric($value)) {
              RestObjects::$apiResponse->fail('Bad Request', 400, "Invalid filter on numeric field $param");
            }
          }
          else {
            RestObjects::$apiResponse->fail('Internal Server Error', 400, "Unsupported field type for $param");
          }
          $extraFilter .= "\nAND " . self::$fieldDefs[$param]['sql'] . "=$value";
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
   * @todo Support for reading a survey structure including attribute metadata.
   * @todo Add test case
   */
  public static function readList($entity, $extraFilter = '', $userFilter = TRUE) {
    self::loadEntityConfig($entity);
    $qry = self::getReadSql($entity, $extraFilter, $userFilter);
    $rows = RestObjects::$db->query($qry);
    kohana::log('debug', 'REST GET query: ' . $qry);

    $attrs = [];
    $verboseAdditions = [];
    // Get attribute values.
    $ids = [];
    foreach ($rows as $row) {
      $ids[] = $row->id;
    }
    if (!empty(self::$entityConfig[$entity]->attributes)) {
      // ReadAttributes automatically handles verbose mode to expand attr.
      $attrs = self::readAttributes($entity, $ids);
    }
    // If requested (and there are some rows), check for verbose additions.
    if (array_key_exists('verbose', $_GET) && $rows->count() > 0) {
      $verboseAdditions = self::getVerboseModeExtraInfo($entity, $ids);
    }

    $r = [];
    foreach ($rows as $row) {
      unset($row->xmin);
      $id = $row->id;
      if (array_key_exists($id, $attrs)) {
        $row = array_merge((array) $row, $attrs[$id]);
      }
      if (array_key_exists('verbose', $_GET)) {
        if (array_key_exists($id, $verboseAdditions)) {
          $row = array_merge((array) $row, $verboseAdditions[$id]);
        }
      }
      $r[] = ['values' => self::getValuesForResponse($row)];
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
    self::loadEntityConfig($entity);
    $qry = self::getReadSql($entity, $extraFilter, $userFilter);
    $qry .= "AND t1.id = $id";
    $row = RestObjects::$db->query($qry)->result(FALSE)->current();
    kohana::log('debug', 'REST GET ID query: ' . $qry);
    if ($row) {
      // Transaction ID that last updated row is returned as ETag header.
      header("ETag: $row[xmin]");
      unset($row['xmin']);
      if (!empty(self::$entityConfig[$entity]->attributes)) {
        // ReadAttributes automatically handles verbose mode to expand attr.
        $attrs = self::readAttributes($entity, [$id]);
        if (array_key_exists($id, $attrs)) {
          $row = array_merge((array) $row, $attrs[$id]);
        }
      }
      if (isset($_GET['verbose'])) {
        $verboseAdditions = self::getVerboseModeExtraInfo($entity, [$id]);
        if (array_key_exists($id, $verboseAdditions)) {
          $row = array_merge((array) $row, $verboseAdditions[$id]);
        }
      }
      RestObjects::$apiResponse->succeed(array_merge(self::getExtraData($entity, $row), ['values' => self::getValuesForResponse($row)]));
    }
    else {
      RestObjects::$apiResponse->fail('Not found', 404);
    }
  }

  /**
   * Read attributes for records.
   *
   * @param string $entity
   *   Entity name (singular).
   * @param int[] $ids
   *   Array of record IDs to obtain attributes for.
   *
   * @return array
   *   List of attributes for records. First dimension is record id. Second
   *   dimension is attribute key, e.g. locAttr:3
   */
  private static function readAttributes($entity, array $ids) {
    $idList = implode(',', $ids);
    $qry = <<<SQL
SELECT av.{$entity}_id as record_id, a.id as attribute_id, av.id as value_id,
  a.caption, a.data_type, a.multi_value,
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
JOIN {$entity}_attributes a on a.id = av.{$entity}_attribute_id and a.deleted = false
LEFT JOIN cache_termlists_terms t on a.data_type = 'L' and t.id = av.int_value
WHERE av.deleted = false
AND av.{$entity}_id IN ($idList);
SQL;
    $attrValues = RestObjects::$db->query($qry);
    $attrs = [];
    foreach ($attrValues as $attr) {
      $recordKey = $attr->record_id;
      unset($attr->record_id);
      $attrKey = self::$entityConfig[$entity]->attributePrefix . "Attr:$attr->attribute_id";
      $val = array_key_exists('verbose', $_GET) ? (array) $attr : $attr->value;

      if (!isset($attrs[$recordKey])) {
        $attrs[$recordKey] = [];
      }

      if ($attr->multi_value === 't') {
        if (!isset($attrs[$recordKey][$attrKey])) {
          $attrs[$recordKey][$attrKey] = [];
        }
        $attrs[$recordKey][$attrKey][] = $val;
      }
      else {
        $attrs[$recordKey][$attrKey] = $val;
      }
    }
    return $attrs;
  }

  /**
   * Read extra information for verbose mode for records.
   *
   * E.g. retrieves pages for a list of groups.
   *
   * @param string $entity
   *   Entity name (singular).
   * @param int[] $ids
   *   Array of record IDs to obtain data for.
   *
   * @return array
   *   List of extra data for records, First dimension is record id. Second
   *   dimension is the container name for the list of information being added
   *   to the record.
   */
  private static function getVerboseModeExtraInfo($entity, array $ids) {
    if (!isset(self::$verboseExtraInfoQueries[$entity])) {
      // Nothing to do.
      return [];
    }
    $idList = implode(',', $ids);
    $r = [];
    foreach (self::$verboseExtraInfoQueries[$entity] as $container => $qry) {
      $qry = str_replace([
          '{{ ids }}',
          '{{ user_id }}',
        ], [
          $idList,
          RestObjects::$clientUserId,
        ],
        $qry);
      $extraData = RestObjects::$db->query($qry)->result_array(FALSE);
      foreach ($extraData as $dataItem) {
        $thisItemId = $dataItem['id'];
        unset($dataItem['id']);
        if (!isset($r[$thisItemId])) {
          $r[$thisItemId] = [];
        }
        if (!isset($r[$thisItemId][$container])) {
          $r[$thisItemId][$container] = [];
        }
        $r[$thisItemId][$container][] = $dataItem;
      }
    }
    return $r;
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
   * @param array $fieldChecks
   *   Key value pairs of field value checks that should be done before
   *   allowing the update.
   */
  public static function update($entity, $id, array $data, array $fieldChecks) {
    if (empty(RestObjects::$clientUserId)) {
      RestObjects::$apiResponse->fail('Bad Request', 400, json_encode(["$entity:created_by_id" => 'Cannot PUT without user authentication.']));
    }
    self::loadEntityConfig($entity);
    $values = $data['values'];
    // ID is optional, but must match URL segment.
    if (!empty($values['id'])) {
      if ($values['id'] != $id) {
        RestObjects::$apiResponse->fail('Bad Request', 400, json_encode(["$entity:id" => 'Provided id does not match URI']));
      }
    }
    $obj = ORM::factory($entity, $id);
    self::checkETags($entity, $id);
    if (isset(self::$entityConfig[$entity]->duplicateCheckFields)) {
      self::checkDuplicateFields($entity, array_merge($obj->as_array(), $values), $data);
    }
    foreach ($fieldChecks as $key => $value) {
      if ($obj->{$key} !== $value) {
        if ($key === 'created_by_id') {
          RestObjects::$apiResponse->fail('Not Found', 404, $entity . ' Attempt to update record belonging to different user.');
        }
        else {
          RestObjects::$apiResponse->fail('Not Found', 404, "$entity $key not " . var_export($value, TRUE));
        }
      }
    }
    // Keep existing values unless replaced by PUT data.
    $data['values'] = array_merge(
      $obj->as_array(),
      $values
    );
    return self::submit($entity, $obj, $data);
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
    if (empty(RestObjects::$clientUserId)) {
      RestObjects::$apiResponse->fail('Bad Request', 400, json_encode(["$entity:created_by_id" => 'Cannot PUT without user authentication.']));
    }
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
    }
    else {
      RestObjects::$apiResponse->fail('Not found', 404);
    }
  }

  /**
   * For some entities, include extra data in the GET response.
   *
   * An example is lookup terms which are included in the response for
   * attributes. Extra data can be defined in the entity json config files,
   * e.g. see sample_attribute.json.
   *
   * @param string $entity
   *   Entity name.
   * @param array $row
   *   Attribute data from database.
   *
   * @return array
   *   List of key/value pairs to include in the response.
   */
  private static function getExtraData($entity, array $row) {
    $extraData = [];
    if (!empty(self::$entityConfig[$entity]->extras)) {
      // Attach extra information to the GET response such as termlist terms.
      foreach (self::$entityConfig[$entity]->extras as $name => $extraCfg) {
        // If parameter field filled in, use it to run the query to get extra data.
        if (!empty($row[$extraCfg->parameter])) {
          $sql = str_replace("{{ $extraCfg->parameter }}", $row[$extraCfg->parameter], $extraCfg->sql);
          $extraData[$name] = json_decode(RestObjects::$db->query($sql)->current()->extra);
        }
      }
    }
    return $extraData;
  }

  /**
   * Link any supermodels to a submission.
   *
   * Includes one to many relationships.
   */
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
        kohana::log('debug', 'Submission updated sub: ' . var_export($s, TRUE));
      }
    }
  }

  /**
   * Link any supermodels to a submission.
   *
   * Includes many to one relationships.
   */
  private static function includeSupermodels($entity, array $postObj, $websiteId, &$s) {
    $superModels = [];
    if (isset(self::$entityConfig[$entity]->superModels)) {
      $superModels = array_intersect_key((array) self::$entityConfig[$entity]->superModels, $postObj);
      unset($superModels['values']);
    }
    foreach ($superModels as $superModelTable => $superModelCfg) {
      $s['superModels'][] = [
        'fkId' => $superModelCfg->fk,
        'model' => self::convertNewToOldSubmission(inflector::singular($superModelTable), $postObj[$superModelTable], $websiteId),
      ];
    }
  }

  /**
   * Attach any posted metaFields to the submission.
   */
  private static function includeMetafields($entity, array $postObj, $websiteId, &$s) {
    if (isset($postObj['metaFields'])) {
      $s['metaFields'] = $postObj['metaFields'];
    }
  }

  /**
   * Coverts new REST API submission format to old Data Services format.
   *
   * @param string $entity
   *   Model name.
   * @param array $postObj
   *   Posted submission to convert.
   * @param int $websiteId
   *   Website ID.
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
      kohana::log('debug', $entity . ': ' . var_export($postObj, TRUE));
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
    self::includeSupermodels($entity, $postObj, $websiteId, $s);
    self::includeMetafields($entity, $postObj, $websiteId, $s);
    return $s;
  }

  /**
   * Fails if there is an existing record.
   *
   * Duplicate check uses fields defined in entity's duplicateCheckFields
   * setting.
   *
   * @param int $entity
   *   Entity name.
   * @param array $values
   *   Values for the record being checked. For updates, will merge the
   *   submitted values into the existing values.
   * @param array $data
   *   Submitted data.
   */
  private static function checkDuplicateFields($entity, array $values, array $data) {
    kohana::log('debug', "Doing duplicate check for $entity");
    $table = inflector::plural($entity);
    $filters = [];
    $joins = [];
    // If we are updating, then don't match the same record.
    if (!empty($values['id'])) {
      $filters[] = "id<>$values[id]";
    }
    foreach (self::$entityConfig[$entity]->duplicateCheckFields as $field) {
      $fieldParts = explode('.', $field);
      $fieldName = array_pop($fieldParts);
      // Anything left must be a duplicate check on a sub-model field.
      if (count($fieldParts) === 1) {
        $subModelTable = $fieldParts[0];
        // Skip if sub-model not present, or does not contain a field value.
        if (!isset($data[$subModelTable]) || !isset($data[$subModelTable][0]) || !isset($data[$subModelTable][0]['values']) || empty($data[$subModelTable][0]['values'][$fieldName])) {
          return;
        }
        $value = $data[$subModelTable][0]['values'][$fieldName];
        $joins[$subModelTable] = "\njoin $subModelTable on $subModelTable.{$entity}_id=$table.id and $subModelTable.$fieldName='$value'";
      }
      else {
        if (empty($values[$field])) {
          // No need for check if some duplicate check values missing.
          return;
        }
        $value = $values[$field];
        $filters[] = "$table.$fieldName='$value'";
      }
    }
    $hit = RestObjects::$db
      ->query("select $table.id from $table" . implode('', $joins) . " where " . implode(' and ', $filters))
      ->current();
    if ($hit) {
      $href = url::base() . "index.php/services/rest/$table/$hit->id";
      RestObjects::$apiResponse->fail('Conflict', 409, 'Duplicate external_key would be created', [
        'duplicate_of' => [
          'id' => $hit->id,
          'href' => $href,
        ],
      ]);
    }
  }

  private static function checkETags($entity, $id) {
    $headers = apache_request_headers();
    if (isset($headers['If-Match'])) {
      $table = inflector::plural($entity);
      // A precondition based on ETag which must be met.
      $eTag = RestObjects::$db->query("SELECT xmin FROM $table WHERE id=$id")->current()->xmin;
      if ($headers['If-Match'] !== $eTag) {
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
   * @param array $fields
   *   Optional list of fields to restrict to.
   *
   * @return array
   *   Associative array of field names and values.
   */
  private static function getValuesForResponse($data, array $fields = NULL) {
    if (is_object($data)) {
      $data = (array) $data;
    }
    $values = $fields ? array_intersect_key($data, array_flip($fields)) : $data;
    foreach ($values as $field => &$value) {
      if (isset(self::$fieldDefs[$field])) {
        if (self::$fieldDefs[$field]['type'] === 'date' && !empty($value)) {
          // Date values need reformatting.
          $value = date('c', strtotime($value));
        }
        if (!empty(self::$fieldDefs[$field]['array']) && preg_match('/^\[.+\]$/', $value)) {
          $value = json_decode($value);
        }
      }
      if (substr($field, -10) === 'date_start') {
        $prefix = substr($field, 0, strlen($field) - 10);
        $values["{$prefix}date"] = vague_date::vague_date_to_string([
          $value,
          $values["{$prefix}date_end"],
          $values["{$prefix}date_type"],
        ]);
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
    self::loadFieldDefs($entity);
    $table = inflector::plural($entity);
    $href = url::base() . "index.php/services/rest/$table/$responseMetadata[id]";
    $r = [
      'values' => self::getValuesForResponse($responseMetadata, [
        'id',
        'created_on',
        'updated_on',
      ]),
      'href' => $href,
    ];
    // Recursively process sub-model info.
    if (!empty(self::$entityConfig[$entity]->subModels) && !empty($responseMetadata['children'])) {
      foreach ($responseMetadata['children'] as $child) {
        $subTable = inflector::plural($child['model']);
        if (preg_match('/_media$/', $subTable)) {
          $subTable = 'media';
        }
        if (property_exists(self::$entityConfig[$entity]->subModels, $subTable)) {
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
   * @param string $entity
   *   Entity name.
   * @param object $obj
   *   ORM object.
   * @param array $postObj
   *   Submission data.
   */
  private static function submit($entity, $obj, array $postObj) {
    $obj->submission = rest_crud::convertNewToOldSubmission($entity, $postObj, RestObjects::$clientWebsiteId);
    $id = $obj->submit();
    if ($id) {
      $table = inflector::plural($entity);
      // ETag to provide version check on updates.
      $eTag = RestObjects::$db->query("SELECT xmin FROM $table WHERE id=$id")->current()->xmin;
      header("ETag: $eTag");
      // Include href and basic record metadata.
      $responseMetadata = $obj->getSubmissionResponseMetadata();
      return self::getResponseMetadata($responseMetadata);
    }
    else {
      RestObjects::$apiResponse->fail('Bad Request', 400, $obj->getAllErrors());
    }
  }

}
