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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Helper class for the OccurrenceLinkedLocationCodeField import plugin.
 *
 * Adds an import field allowing the linked location ID sample attributes to be
 * imported using a location code (e.g. a Vice County number) rather than
 * needing to know the location ID in the database.
 */
class importPluginOccurrenceLinkedLocationCodeField {

  /**
   * Adds linked location code to the field list.
   *
   * @param array $params
   *   Plugin parameters.
   * @param bool $required
   *   If collecting required fields, the plugin should only add the column if
   *   it should be required.
   * @param array $fields
   *   Available database fields for importing into. The plugin will add a new
   *   column for looking up the location by code.
   */
  public static function alterAvailableDbFields(array $params, $required, array &$fields) {
    // Linked location by code will not be required, otherwise it should be
    // returned regardless of whether $required is true or false.
    if (!$required) {
      // Find all the sample attributes in the available fields.
      $attrFields = preg_grep('/^smpAttr:\d+$/', array_keys($fields));
      $attrIds = array_map(function($v) {
        return explode(':', $v)[1];
      }, $attrFields);
      // If the first parameter correctly points to an available sample
      // attribute, then add an extra option for looking up by code.
      if (in_array($params[0], $attrIds)) {
        $fields["smpAttr:$params[0]:code"] = $fields['smpAttr:' . $params[0]] . ' (lookup using location code)';
        $fields['smpAttr:' . $params[0]] .= ' (using database location ID)';
      }
    }
  }

  /**
   * This plugin is applicable only if the user selects the field we added.
   *
   * @param array $params
   *   Plugin parameters.
   * @param array $config
   *   Import configuration.
   *
   * @return boolean
   *   True if the plugin's column was selected.
   */
  public static function isApplicable(array $params, array $config) {
    $found = FALSE;
    foreach ($config['columns'] as $column) {
      if (isset($column['warehouseField']) && $column['warehouseField'] === "smpAttr:$params[0]:code") {
        $found = TRUE;
      }
    }
    kohana::log('debug', 'Plugin is applicable ' . ($found ? 'yes':'no'));
    return $found;
  }

  /**
   * Adds a preprocess step for handling the linked location code field.
   *
   * @param array $params
   *   Plugin parameters.
   * @param array $config
   *   Import configuration.
   * @param array $steps
   */
  public static function alterPreprocessSteps(array $params, array $config, array &$steps) {
    $steps[] = [
      'preprocessLinkedLocationCodeField',
      "Matching imported location codes to locations",
      'importPluginOccurrenceLinkedLocationCodeField',
      $params,
    ];
  }

  /**
   * Preprocess function that handles data in the column we added.
   */
  public static function preprocessLinkedLocationCodeField(array $params, array &$config) {
    $db = new Database();
    // Create a column to hold the raw import value - first checking it doesn't
    // already exist.
    $found = FALSE;
    foreach ($config['columns'] as $label => $column) {
      // The raw version of this field might already exist, e.g. if page
      // refreshed or the user mapped both versions of the import field, so
      // check.
      if (isset($column['warehouseField']) && $column['warehouseField'] === "smpAttr:$params[0]") {
        $found = TRUE;
        $rawTempDbField = pg_escape_identifier($db->getLink(), $column['tempDbField']);
      }
      // Find the temp db column name for the code field.
      if (isset($column['warehouseField']) && $column['warehouseField'] === "smpAttr:$params[0]:code") {
        $tempDbField = pg_escape_identifier($db->getLink(), $column['tempDbField']);
        $importColumnLabel = $label;
      }
    }
    if (!isset($tempDbField)) {
      return [
        'message' => ['No linked location code processing required.'],
      ];
    }
    $tempTableNameEsc = pg_escape_identifier($db->getLink(), $config['tableName']);
    if (!$found) {
      $rawTempDbField = pg_escape_identifier($db->getLink(), "smpattr_$params[0]_location_id");
      $sql = <<<SQL
        ALTER TABLE import_temp.$tempTableNameEsc
        ADD COLUMN IF NOT EXISTS $rawTempDbField integer;
SQL;
      $db->query($sql);
      // Switch the import field in the config to the new mapped one.
      $config['columns'][$importColumnLabel]['tempDbField'] = "smpattr_$params[0]_location_id";
      // Remember the original field though as needed for correct label
      // generation on client summary page.
      $config['columns'][$importColumnLabel]['userSelectedWarehouseField'] = $config['columns'][$importColumnLabel]['warehouseField'];
      $config['columns'][$importColumnLabel]['warehouseField'] = "smpAttr:$params[0]";
    }
    $sql = <<<SQL
      UPDATE import_temp.$tempTableNameEsc u
      SET $rawTempDbField=l.id
      FROM locations l
      WHERE l.location_type_id=?
      AND l.code=u.$tempDbField
      AND l.deleted=false
      AND u.$rawTempDbField IS NULL;
SQL;
    $count = $db->query($sql, [$params[1]])->count();
    // Now check that all records which should have mapped to a location did.
    $errorsJson = pg_escape_literal($db->getLink(), json_encode([
      $importColumnLabel => 'Location code specified could not be found in the list of available locations.',
    ]));
    $sql = <<<SQL
UPDATE import_temp.$tempTableNameEsc u
SET errors = COALESCE(u.errors, '{}'::jsonb) || $errorsJson::jsonb
WHERE $rawTempDbField IS NULL AND $tempDbField IS NOT NULL AND $tempDbField<>'';
SQL;
    $updated = $db->query($sql)->count();
    if ($updated > 0) {
      return [
        // Error message to show to the user.
        'error' => 'Some of the specified location codes could not be found in the list of available locations.',
        // Specify errorCount to enable the download link so the user can
        // review the rows with errors. Skip this for a whole table error.
        'errorCount' => $updated,
      ];
    }
    kohana::log('debug', 'Columns after processing: '. var_export($config['columns'], TRUE));
    return [
      'message' => [
        "Finding {1} locations by code.",
        $count
      ],
    ];
  }

}
