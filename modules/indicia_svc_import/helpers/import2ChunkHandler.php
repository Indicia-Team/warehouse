<?php

/**
 * @file
 * Helper class to handle a single chunk of an import.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

defined('SYSPATH') or die('No direct script access.');

use PhpOffice\PhpSpreadsheet\Shared\Date as ImportDate;

/**
 * Exception class for failure to find columns in a list.
 */
class ColNotFoundException extends Exception {}

/**
 * Helper class to handle a single chunk of an import.
 */
class import2ChunkHandler {

  /**
   * Number of records in import file which trigger a background import.
   *
   * @var int
   */
  public const BACKGROUND_PROCESSING_THRESHOLD = 10000;

  /**
   * Number of parent rows to process in one batch.
   *
   * Overridden to set a larger limit when processing on the background work
   * queue.
   *
   * @var int
   */
  public static $batchRowLimit = 50;

  /**
   * Import or validate a chunk of an import file.
   *
   * @param Database $db
   *   Database connection
   * @param array $params
   *   Parameters array including if this in validation phase (precheck), or
   *   restarting when validation done.
   *
   * @return array
   *   Array containing summary of processing after this step.
   */
  public static function importChunk($db, $params) {
    try {
      $configId = $params['config-id'];
      $isPrecheck = !empty($params['precheck']);
      // Don't process cache tables immediately to improve performance.
      cache_builder::$delayCacheUpdates = TRUE;
      $config = self::getConfig($configId);
      // If request to start again sent, go from beginning.
      if (!empty($params['restart'])) {
        $config['rowsProcessed'] = 0;
        $config['parentEntityRowsProcessed'] = 0;
        self::saveConfig($configId, $config);
      }
      $isBackground = $config['processingMode'] === 'background';
      self::getChunkSize($isBackground, $isPrecheck);

      // @todo Correctly set parent entity for other entities.
      // @todo Handling for entities without parent entity.
      $parentEntityColumns = self::findEntityColumns($config['parentEntity'], $config);
      $childEntityColumns = self::findEntityColumns($config['entity'], $config);
      if ($config['supportDnaDerivedOccurrences']) {
        $dnaEntityColumns = self::findEntityColumns('dna_occurrence', $config);
      }
      $parentEntityDataRows = self::fetchParentEntityData($db, $parentEntityColumns, $isPrecheck, $config);

      // Check for compound field handling which require presence of a set of
      // fields (e.g. build date from day, month, year).
      $parentEntityCompoundFields = self::getCompoundFieldsToProcessForEntity($config['parentEntity'], $parentEntityColumns);
      $childEntityCompoundFields = self::getCompoundFieldsToProcessForEntity($config['entity'], $childEntityColumns);
      foreach ($parentEntityDataRows as $parentEntityDataRow) {
        // @todo Updating existing data.
        $parent = ORM::factory($config['parentEntity']);
        $submission = [];
        self::applyGlobalValues($config, $config['parentEntity'], $parent->attrs_field_prefix ?? NULL, $submission);
        self::copyFieldsFromRowToSubmission($parentEntityDataRow, $parentEntityColumns, $config, $submission, $parentEntityCompoundFields);
        $identifiers = [
          'website_id' => $config['global-values']['website_id'],
          'survey_id' => $submission['survey_id'] ?? NULL,
        ];
        if ($config['parentEntitySupportsImportGuid']) {
          $submission["$config[parentEntity]:import_guid"] = $config['importGuid'];
        }
        $parent->set_submission_data($submission);
        if ($isPrecheck) {
          $parentErrors = $parent->precheck($identifiers);
          // A fake ID to allow check on children.
          $parent->id = 1;
        }
        else {
          try {
            $parent->submit();
            $parentErrors = $parent->getAllErrors();
          } catch (Exception $e) {
            // If the parent entity fails to save, record the error.
            $parentErrors['sample:general'] = $e->getMessage();
          }
        }
        $childEntityDataRows = self::fetchChildEntityData($db, $parentEntityColumns, $isPrecheck, $config, $parentEntityDataRow);
        if (count($parentErrors) > 0) {
          $config['errorsCount'] += count($childEntityDataRows);
          if (!$isPrecheck) {
            // As we won't individually process the occurrences due to error in
            // the sample, add them to the count.
            $config['rowsProcessed'] += count($childEntityDataRows);
          }
          $keyFields = self::getDestFieldsForColumns($parentEntityColumns);
          self::saveErrorsToRows($db, $parentEntityDataRow, $keyFields, $parentErrors, $config);
        }
        // If sample saved OK, or we are just prechecking, process the matching
        // occurrences.
        if (count($parentErrors) === 0 || $isPrecheck) {
          foreach ($childEntityDataRows as $childEntityDataRow) {
            $child = ORM::factory($config['entity']);
            $submission = [
              'sample_id' => $parent->id,
            ];
            self::applyGlobalValues($config, $config['entity'], $child->attrs_field_prefix ?? NULL, $submission);
            self::copyFieldsFromRowToSubmission($childEntityDataRow, $childEntityColumns, $config, $submission, $childEntityCompoundFields);
            if ($config['entitySupportsImportGuid']) {
              $submission["$config[entity]:import_guid"] = $config['importGuid'];
            }
            $child->set_submission_data($submission);
            if ($isPrecheck) {
              $errors = $child->precheck($identifiers);
            }
            else {
              try {
                $child->submit();
                $errors = $child->getAllErrors();
              } catch (Exception $e) {
                // If the child entity fails to save, record the error.
                $errors['occurrence:general'] = $e->getMessage();
              }
            }
            if (count($errors) > 0) {
              // Register additional error row, but only if not already
              // registered due to error in parent.
              if (count($parentErrors) === 0) {
                $config['errorsCount']++;
              }
              self::saveErrorsToRows($db, $childEntityDataRow, ['_row_id'], $errors, $config);
            }
            else {
              self::setRowDone($db, $childEntityDataRow->_row_id, $isPrecheck, $config);
              if (!$isPrecheck) {
                if (!empty($submission['occurrence:id'])) {
                  $config['rowsUpdated']++;
                }
                else {
                  $config['rowsInserted']++;
                }
              }
              if ($config['supportDnaDerivedOccurrences'] ?? FALSE && $config['entity'] === 'occurrence') {
                if (!self::importDnaIfValuesProvided(
                  $db,
                  $childEntityDataRow,
                  $dnaEntityColumns,
                  $child,
                  $isPrecheck,
                  $identifiers,
                  $config
                )) {
                  // An error occurred saving the DNA occurrence. Increment the
                  // overall error count if not already done for this row.
                  if (count($parentErrors) + count($errors) === 0) {
                    $config['errorsCount']++;
                  }
                };
              }
            }
            $config['rowsProcessed']++;
          }
        }
        $config['parentEntityRowsProcessed']++;
      }

      $progress = 100 * $config['rowsProcessed'] / $config['totalRows'];
      if ($progress === 100 && $config['errorsCount'] === 0 && !$isPrecheck) {
        self::tidyUpAfterImport($db, $configId, $config);
      }
      else {
        self::saveConfig($configId, $config);
      }
      if (!$isPrecheck) {
        self::saveImportRecord($config);
      }
      return [
        // Additional check for count of parentDataEntityRows will apply if an
        // import is restarted by refreshing the browser page, as the
        // rowsProcessed won't reach the total rows in this case.
        'status' => $config['rowsProcessed'] >= $config['totalRows'] || count($parentEntityDataRows) === 0 ? 'done' : ($isPrecheck ? 'checking' : 'importing'),
        'progress' => 100 * $config['rowsProcessed'] / $config['totalRows'],
        'rowsProcessed' => $config['rowsProcessed'],
        'totalRows' => $config['totalRows'],
        'errorsCount' => $config['errorsCount'],
      ];
    }
    catch (Exception $e) {
      if ($e instanceof RequestAbort) {
        // Abort request implies response already sent.
        return;
      }
      // Save config as it tells us how far we got, making diagnosis and
      // continuation easier.
      if (isset($config)) {
        self::saveConfig($configId, $config);
        self::saveImportRecord($config);
      }
      error_logger::log_error('Error in import_chunk', $e);
      kohana::log('debug', 'Error in import_chunk: ' . $e->getMessage());
      http_response_code(400);
      if ($isBackground) {
        // Error handling differs in background mode.
        throw $e;
      }
      return [
        'status' => 'error',
        'msg' => $e->getMessage(),
      ];
    }
  }

  /**
   * Handle any DNA derived occurrences linked to this occurrence.
   *
   * @param Database $db
   *   Database connection.
   * @param mixed $childEntityDataRow
   * @param mixed $dnaEntityColumns
   * @param mixed $child
   * @param bool $isPrecheck
   * @param mixed $identifiers
   * @param array $config
   *
   * @return bool
   *   FALSE if errors occurred, TRUE if successful or no DNA to save for this
   *   occurrence.
   */
  private static function importDnaIfValuesProvided($db, $childEntityDataRow, $dnaEntityColumns, $child, $isPrecheck, $identifiers, array &$config) {
    $dnaSubmission = [];
    self::copyFieldsFromRowToSubmission($childEntityDataRow, $dnaEntityColumns, $config, $dnaSubmission, []);
    // Skip DNA occurrence if no DNA fields provided.
    if (!empty($dnaSubmission)) {
      if (!empty($childEntityDataRow->_dna_occurrence_id)) {
        // Overwrite an existing DNA occurrence record.
        $dnaSubmission['id'] = $childEntityDataRow->_dna_occurrence_id;
      }
      $dnaSubmission['occurrence_id'] = $child->id;
      $dnaObject = ORM::factory('dna_occurrence');
      $dnaObject->set_submission_data($dnaSubmission);
      if ($isPrecheck) {
        $errors = $dnaObject->precheck($identifiers);
      }
      else {
        try {
          $dnaObject->submit();
          $errors = $dnaObject->getAllErrors();
        } catch (Exception $e) {
          // If the child entity fails to save, record the error.
          $errors['occurrence:general'] = $e->getMessage();
        }
      }
      if (count($errors) > 0) {
        // Register additional error row, but only if not already
        // registered due to error in parents.
        self::saveErrorsToRows($db, $childEntityDataRow, ['_row_id'], $errors, $config);
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Finds mapped database import columns that relate to an entity.
   *
   * @param string $entity
   *   Entity name, e.g. sample, occurrence.
   * @param array $config
   *   Import metadata configuration object.
   *
   * @return array
   *   List of column definitions.
   */
  public static function findEntityColumns($entity, array $config) {
    $columns = [];
    $attrPrefix = self::getAttrPrefix($entity);
    $allColumns = array_merge($config['columns'], $config['systemAddedColumns']);
    foreach ($allColumns as $info) {
      if (isset($info['warehouseField'])) {
        $destFieldParts = explode(':', $info['warehouseField']);
        // If a field targeting the destination entity, or an attribute table
        // linked to the entity, or a media table linked to the table, then
        // include the column.
        if ($destFieldParts[0] === $entity
            || ($attrPrefix && $destFieldParts[0] === $attrPrefix)
            || $destFieldParts[0] === inflector::singular($entity) . '_medium') {
          $columns[] = $info;
        }
      }
    }
    return $columns;
  }

  /**
   * Gets the saved config for an import.
   *
   * @param string $configId
   *
   * @return array
   *   Configuration data.
   */
  public static function getConfig($configId) {
    // If call from older import client, the configId is the full file name.
    $baseName = preg_replace('/(.csv|.xls|.xlsx|.json)$/i', '', $configId);
    $configFile = DOCROOT . "import/$baseName.json";
    if (file_exists($configFile)) {
      $f = fopen($configFile, "r");
      $config = fgets($f);
      fclose($f);
      return json_decode($config, TRUE);
    }
    else {
      throw new Exception("Config file $configFile missing.");
    }
  }

  /**
   * Saves config to a JSON file, allowing process info to persist.
   *
   * @param string $configId
   *   Unique ID of the config file.
   * @param array $config
   *   The data to save.
   */
  public static function saveConfig($configId, array $config) {
    $configFile = DOCROOT . "import/$configId.json";
    $f = fopen($configFile, "w");
    fwrite($f, json_encode($config));
    fclose($f);
  }

  /**
   * Saves metadata about the import to the imports table.
   *
   * @param array $config
   *   Import metadata configuration object.
   * @param object $importInfo
   *   Additional field values to save (e.g. description).
   */
  public static function saveImportRecord(array $config, $importInfo = NULL) {
    $import = ORM::factory('import', ['import_guid' => $config['importGuid']]);
    $import->set_metadata();
    $import->entity = $config['entity'];
    $import->website_id = $config['global-values']['website_id'];
    $import->inserted = $config['rowsInserted'];
    $import->updated = $config['rowsUpdated'];
    $import->errors = $config['errorsCount'];
    $import->reversible = TRUE;
    $import->mappings = json_encode($config['columns']);
    $import->global_values = json_encode($config['global-values']);
    if ($importInfo) {
      if (!empty($importInfo->description)) {
        // This will only get specified on initial save.
        $import->description = $importInfo->description;
      }
      if (!empty($importInfo->training)) {
        $import->training = 't';
      }

    }
    $import->import_guid = $config['importGuid'];
    $import->save();
    $errors = $import->getAllErrors();
    if (count($errors) > 0) {
      // This should never happen.
      throw new Exception(json_encode($errors, TRUE));
    }
  }

  /**
   * Sets a suitable chunk size for a batch.
   *
   * @param bool $isBackground
   *   True if background processing a large import.
   * @param bool $isPrecheck
   *   True if prechecking.
   */
  private static function getChunkSize($isBackground, $isPrecheck) {
    // Config can override the defaults.
    $moduleConfig = kohana::config('indicia_svc_import', FALSE, FALSE);
    $key = 'chunk_size_' . ($isBackground ? 'background_' : '') . ($isPrecheck ? 'preprocess' : 'import');
    $defaults = [
      'chunk_size_preprocess' => 100,
      'chunk_size_import' => 50,
      'chunk_size_background_preprocess' => 1000,
      'chunk_size_background_import' => 500,
    ];
    self::$batchRowLimit = $moduleConfig[$key] ?? $defaults[$key];
  }

  /**
   * For a parent entity record (e.g. sample), find the child data rows.
   *
   * @param object $db
   *   Database connection.
   * @param array $columns
   *   List of parent columns that will be filtered to find the child data rows.
   * @param bool $isPrecheck
   *   Whether this is a precheck or an import.
   * @param array $config
   *   Import metadata configuration object.
   * @param object $parentEntityDataRow
   *   Data row holding the parent record values.
   *
   * @return object
   *   Database result containing child rows.
   */
  private static function fetchChildEntityData($db, array $columns, $isPrecheck, array $config, $parentEntityDataRow) {
    $dbIdentifiers = self::getEscapedDbIdentifiers($db, $config);
    $fields = self::getDestFieldsForColumns($columns);
    // Build a filter to extract rows for this parent entity.
    $fieldToTrackDoneBy = $isPrecheck ? 'checked' : 'imported';
    $wheresList = [
      "$fieldToTrackDoneBy=false",
      'errors IS NULL',
    ];
    foreach ($fields as $field) {
      $fieldEsc = pg_escape_identifier($db->getLink(), $field);
      $value = pg_escape_literal($db->getLink(), $parentEntityDataRow->$field ?? '');
      $wheresList[] = "COALESCE($fieldEsc::text, '')=$value";
    }
    $wheres = implode("\nAND ", $wheresList);
    // Now retrieve the sub-entity rows.
    $sql = <<<SQL
SELECT *
FROM import_temp.$dbIdentifiers[tempTableName]
WHERE $wheres
ORDER BY _row_id;
SQL;
    return $db->query($sql)->result();
  }

  /**
   * When importing to a parent entity, need distinct data values.
   *
   * One row will be created per distinct set of field values, so this returns
   * a db result with the required rows.
   *
   * @param object $db
   *   Database connection.
   * @param bool $isPrecheck
   *   Whether this is a precheck or an import.
   * @param array $columns
   *   List of column definitions to look for uniqueness in the values of.
   * @param array $config
   *   Import metadata configuration object.
   */
  private static function fetchParentEntityData($db, array $columns, $isPrecheck, array $config) {
    $fields = self::getDestFieldsForColumns($columns);
    $fields = array_map(function ($s) use ($db) {
      return pg_escape_identifier($db->getLink(), $s);
    }, $fields);
    $fieldsAsCsv = implode(', ', $fields);
    $dbIdentifiers = self::getEscapedDbIdentifiers($db, $config);
    $fieldToTrackDoneBy = $isPrecheck ? 'checked' : 'imported';
    // Because this skips the already imported rows, no need to do OFFSET.
    $sql = <<<SQL
      SELECT DISTINCT $fieldsAsCsv
      FROM import_temp.$dbIdentifiers[tempTableName]
      WHERE $fieldToTrackDoneBy=false
      AND errors IS NULL
      ORDER BY $fieldsAsCsv
      LIMIT ?;
    SQL;
    return $db->query($sql, self::$batchRowLimit)->result();
  }

  /**
   * Retreive the prefix for an entity's attribute field names, or null.
   *
   * @param string $entity
   *   Entity name, e.g. occurrence.
   *
   * @return string
   *   Attribute prefix, e.g. smpAttr or occAttr.
   */
  private static function getAttrPrefix($entity) {
    $entityPrefixes = [
      'occurrence' => 'occAttr',
      'location' => 'locAttr',
      'sample' => 'smpAttr',
      'survey' => 'srvAttr',
      'taxa_taxon_list' => 'ttlAttr',
      'termlists_term' => 'trmAttr',
      'person' => 'psnAttr',
    ];
    return $entityPrefixes[$entity] ?? NULL;
  }

  /**
   * Finds the column info for a column identified by any property value.
   *
   * E.g. find the column info by tempDbField, or warehouseField.
   *
   * @param array $columns
   *   Columns config to search.
   * @param string $property
   *   Property name to search in (tempDbField or warehouseField).
   * @param string $value
   *   Value to search for.
   *
   * @return array
   *   Column info array.
   */
  public static function getColumnInfoByProperty(array $columns, $property, $value) {
    foreach ($columns as $columnLabel => $info) {
      if (isset($info[$property]) && $info[$property] === $value) {
        return array_merge($info, ['columnLabel' => $columnLabel]);
      }
    }
    throw new ColNotFoundException("Property value $property=$value not found");
  }

  /**
   * Get escaped versions of database identifiers used in SQL queries.
   *
   * @param object $db
   *   Database connection object.
   * @param array $config
   *   Import metadata configuration object.
   *
   * @return array
   *   List of escaped identifiers keyed by identifier name.
   */
  public static function getEscapedDbIdentifiers($db, array $config) {
    return [
      'importDestTable' => pg_escape_identifier($db->getLink(), inflector::plural($config['entity'])),
      'importDestParentTable' => pg_escape_identifier($db->getLink(), inflector::plural($config['parentEntity'])),
      'pkFieldInTempTable' => pg_escape_identifier($db->getLink(), $config['pkFieldInTempTable'] ?? ''),
      'parentEntityFkIdFieldInDestTable' => pg_escape_identifier($db->getLink(), "$config[parentEntity]_id"),
      'tempDbFkIdField' => pg_escape_identifier($db->getLink(), "_$config[entity]_id"),
      'tempDbParentFkIdField' => pg_escape_identifier($db->getLink(), "_$config[parentEntity]_id"),
      'tempTableName' => pg_escape_identifier($db->getLink(), $config['tableName']),
    ];
  }

  /**
   * Applies global values to a submission array.
   *
   * These are the values provided by the user that apply to every row in the
   * import.
   *
   * @param array $config
   *   Import metadata configuration object.
   * @param string $entity
   *   Name of the entity to copy over values for.
   * @param string $attrPrefix
   *   Attribute fieldname prefix, e.g. smp or occ. Leave empty if not an
   *   attribute table.
   * @param array $submission
   *   Submission data array that will be updated with the global values.
   */
  private static function applyGlobalValues(array $config, $entity, $attrPrefix, array &$submission) {
    foreach ($config['global-values'] as $field => $value) {
      if (in_array($field, ['survey_id', 'website_id'])
          || substr($field, 0, strlen($entity) + 1) === "$entity:"
          || ($attrPrefix && substr($field, 0, strlen($attrPrefix) + 1) === "{$attrPrefix}:")) {
        $submission[$field] = $value;
      }
    }
  }

  /**
   * Copies a set of fields from a data row into a submission array.
   *
   * @param object $dataRow
   *   Data read from the import file.
   * @param array $columns
   *   List of column definitions to copy the field value for.
   * @param array $config
   *   Import metadata configuration object.
   * @param array $submission
   *   Submission data array that will be updated with the copied values.
   */
  private static function copyFieldsFromRowToSubmission($dataRow, array $columns, array $config, array &$submission, array $compoundFields) {
    $skipColumns = [];
    $skippedValues = [];
    foreach ($compoundFields as $def) {
      $skipColumns = $skipColumns + $def['columns'];
    }
    foreach ($columns as $info) {
      $srcFieldName = $info['tempDbField'];
      $destFieldName = $info['warehouseField'];
      // Fk fields need to alter the fake field name to a real one and use the
      // mapped source field.
      if (!empty($info['isFkField'])) {
        $srcFieldName .= '_id';
        $destFieldParts = explode(':', $destFieldName);
        $destFieldName = "$destFieldParts[0]:" .
            // Fieldname without fk_ prefix.
            substr($destFieldParts[1], 3) .
            // Append _id if not a custom attribute lookup.
            (preg_match('/^[a-z]{3}Attr$/', $destFieldParts[0]) ? '' : '_id');
      }
      if (in_array($info['warehouseField'], $skipColumns)) {
        $skippedValues[$info['warehouseField']] = $dataRow->$srcFieldName;
        continue;
      }
      if (empty($dataRow->$srcFieldName)) {
        if (empty($config['global-values'][$destFieldName])) {
          // An empty field shouldn't overwrite a global value.
          continue;
        }
        elseif (!empty($info['skipIfEmpty'])) {
          // Some fields (e.g. existing record ID mappings) should be skipped
          // if empty.
          continue;
        }
      }
      // @todo Look for date fields more intelligently.
      if ($config['isExcel'] && preg_match('/date(_start|_end)?$/', $destFieldName) && preg_match('/^\d+$/', $dataRow->$srcFieldName)) {
        // Date fields are integers when read from Excel.
        $date = ImportDate::excelToDateTimeObject($dataRow->$srcFieldName);
        $submission[$destFieldName] = $date->format('d/m/Y');
      }
      else {
        $submission[$destFieldName] = $dataRow->$srcFieldName;
      }
    }
    foreach ($compoundFields as $def) {
      $submission[$def['destination']] = vsprintf(
        $def['template'],
        array_map(function ($column) use ($skippedValues) {
          return $skippedValues[$column];
        },
        $def['columns'])
      );
    }
  }

   /**
   * Check for model compound fields which need to be applied.
   *
   * E.g. a sample date field is a compound field which can be constructed from
   * day, month and year values in an import. If the day, month and year are
   * mapped then capture the information about the compound field so it can be
   * used later.
   *
   * @param string $entity
   *   Database entity name.
   * @param array $mappedColumns
   *   List of mapped column information.
   *
   * @return array
   *   List of compound field definitions keyed by the field name.
   */
  private static function getCompoundFieldsToProcessForEntity($entity, $mappedColumns) {
    $compoundFields = [];
    $model = ORM::factory($entity);
    if (isset($model->compoundImportFieldProcessingDefn)) {
      foreach ($model->compoundImportFieldProcessingDefn as $def) {
        $foundMappedColumns = [];
        foreach ($mappedColumns as $mappedCol) {
          if (in_array($mappedCol['warehouseField'], $def['columns'])) {
            $foundMappedColumns[$mappedCol['warehouseField']] = TRUE;
          }
        }
        if (count($foundMappedColumns) === count($def['columns'])) {
          // Include this compound field as it's required columns are all mapped.
          $compoundFields[] = $def;
        }
      }
    }
    return $compoundFields;
  }

  /**
   * Find the list of destination fields for a list of column definitions.
   *
   * @param array $columns
   *   List of column definitions.
   *
   * @return array
   *   List of destination field names.
   */
  private static function getDestFieldsForColumns(array $columns) {
    $fields = [];
    foreach ($columns as $info) {
      $fields[] = empty($info['isFkField']) ? $info['tempDbField'] : "$info[tempDbField]_id";
    }
    return $fields;
  }

  /**
   * Adds error data to rows identified by a set of key field values.
   *
   * @param object $db
   *   Database connection.
   * @param object $rowData
   *   Current import row's data.
   * @param array $keyFields
   *   List of field names that should be looked up against.
   * @param array $errors
   *   List of errors to attach to the rows.
   * @param array $config
   *   Import configuration metadata object.
   */
  private static function saveErrorsToRows($db, $rowData, array $keyFields, array $errors, array $config) {
    $whereList = [];
    foreach ($keyFields as $field) {
      $fieldEsc = pg_escape_identifier($db->getLink(), $field);
      $value = pg_escape_literal($db->getLink(), $rowData->$field ?? '');
      $whereList[] = "COALESCE($fieldEsc::text, '')=$value";
    }
    $wheres = implode(' AND ', $whereList);
    $errorsList = [];
    foreach ($errors as $fieldName => $error) {
      list($entity, $field) = explode(':', $fieldName);
      $errorI18n = kohana::lang("form_error_messages.$field.$error");
      $errorStr = $errorI18n === "form_error_messages.$field.$error" ? $error : $errorI18n;
      // A date error might be reported against a vague date component
      // field, but can map back to the calculated date field if separate
      // date fields not being used.
      $field = preg_replace('/date_(start|end|type)$/', 'date', $field);
      try {
        $columnInfo = self::getColumnInfoByProperty($config['columns'], 'warehouseField', "$entity:$field");
        $errorsList[$columnInfo['columnLabel']] = $errorStr;
      }
      catch (ColNotFoundException $e) {
        // Shouldn't happen, but means we need better logic from mapping from
        // the errored field to the mapped field name.
        // If geom field causes error, no need to notify if the entered sref
        // has an error.
        if ($field !== 'geom' || !isset($errors['sample:entered_sref'])) {
          $errorsList[$field] = $errorStr;
        }
      }
    }
    $errorsJson = pg_escape_literal($db->getLink(), json_encode($errorsList));
    $dbIdentifiers = self::getEscapedDbIdentifiers($db, $config);
    $sql = <<<SQL
UPDATE import_temp.$dbIdentifiers[tempTableName]
SET errors = COALESCE(errors, '{}'::jsonb) || $errorsJson::jsonb
WHERE $wheres;
SQL;
    $db->query($sql);
  }

  /**
   * Set the imported or checked flag on a processed row to true in the temp table.
   *
   * @param Database $db
   *   Database connection.
   * @param int $rowId
   *   Import row's ID.
   * @param bool $isPrecheck
   *   Whether this is a precheck or an import.
   * @param array $config
   *   Import configuration settings.
   */
  private static function setRowDone($db, $rowId, $isPrecheck, array $config) {
    $dbIdentifiers = self::getEscapedDbIdentifiers($db, $config);
    $fieldToUpdate = $isPrecheck ? 'checked' : 'imported';
    $sql = <<<SQL
      UPDATE import_temp.$dbIdentifiers[tempTableName]
      SET $fieldToUpdate = true
      WHERE _row_id = $rowId;
    SQL;
    $db->query($sql);
  }

  /**
   * If an import completes successfully, remove the temporary table and files.
   *
   * @param object $db
   *   Database connection.
   * @param string $configId
   *   Unique ID of the config file.
   * @param array $config
   *   Import metadata configuration object.
   */
  private static function tidyUpAfterImport($db, $configId, array $config) {
    foreach(array_keys($config['files']) as $fileName) {
      @unlink(DOCROOT . "import/$fileName");
    }
    @unlink(DOCROOT . "import/$configId.json");

    $dbIdentifiers = self::getEscapedDbIdentifiers($db, $config);
    $db->query("DROP TABLE IF EXISTS import_temp.$dbIdentifiers[tempTableName]");
  }

}