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
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/Indicia-Team/warehouse
 */

/**
 * Class providing utility functions for uploading custom verification rules.
 */
class Custom_verification_rules_Controller extends Data_Service_Base_Controller {

  /**
   * Handle the upload of an import file.
   *
   * Controller action that provides a web service services/import/upload_file
   * and handles uploaded files in the $_FILES array by moving them to the
   * import folder. The current time is prefixed to the name to make it unique.
   * The uploaded file should be in a field called media_upload.
   */
  public function upload_file() {
    header("Content-Type: application/json");
    try {
      // Ensure we have write permissions.
      $this->authenticate('write');
      $importTools = new ImportTools();
      $uploadedFile = $importTools->uploadFile();
      echo json_encode([
        'status' => 'ok',
        'uploadedFile' => basename($uploadedFile),
      ]);
    }
    catch (Exception $e) {
      kohana::log('debug', 'Error in upload_file: ' . $e->getMessage());
      http_response_code(400);
      echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Handle validation of the structure of a rule import file.
   *
   * Checks the columns in the import file are valid. Controller action for a
   * web request so the respose is echoed.
   */
  public function validate_structure() {
    header("Content-Type: application/json");
    try {
      if (empty($_POST['uploadedFile'])) {
        throw new exception('Parameter for uploadedFile is required.');
      }
      // Ensure we have write permissions.
      $this->authenticate('write');
      $importTools = new ImportTools();
      $columnTitles = $importTools->loadColumnTitlesFromFile($_POST['uploadedFile'], TRUE);
      $wrongCols = array_diff($columnTitles, [
        'taxon',
        'taxon id',
        'fail icon',
        'fail message',
        'limit to stages',
        'limit to grid refs',
        'limit to min longitude',
        'limit to min latitude',
        'limit to max longitude',
        'limit to max latitude',
        'limit to location ids',
        'rule type',
        'reverse rule',
        'max individual count',
        'grid refs',
        'grid ref system',
        'min longitude',
        'min latitude',
        'max longitude',
        'max latitude',
        'location ids',
        'min year',
        'max year',
        'min month',
        'max month',
        'min day',
        'max day',
      ]);
      $errors = [];
      if (count($wrongCols) > 0) {
        $errors[] = 'The rules spreadsheet cannot be imported because there are unexpected columns in uploaded spreadsheet: ' . implode('; ', $wrongCols);
      }
      if (!in_array('taxon', $columnTitles) && !in_array('taxon id', $columnTitles)) {
        $errors[] = 'The rules spreadsheet cannot be imported because rules need either a taxon or taxon id column to determine which taxon the rule applies to.';
      }
      if (!in_array('rule type', $columnTitles)) {
        $errors[] = 'The rules spreadsheet cannot be imported because rules need a rule type column.';
      }
      if (in_array('grid refs', $columnTitles) && !in_array('grid ref system', $columnTitles)) {
        $errors[] = 'The rules spreadsheet cannot be imported because there is a grid refs column but no grid ref system column to qualify the grid refs.';
      }
      if (in_array('limit to grid refs', $columnTitles) && !in_array('grid ref system', $columnTitles)) {
        $errors[] = 'The rules spreadsheet cannot be imported because there is a limit grid refs column but no grid ref system column to qualify the grid refs.';
      }
      if (empty($errors)) {
        echo json_encode([
          'status' => 'ok',
        ]);
      }
      else {
        http_response_code(400);
        echo json_encode([
          'status' => 'error',
          'errorList' => $errors,
        ]);
      }
    }
    catch (Exception $e) {
      kohana::log('debug', 'Error in validate_structure: ' . $e->getMessage());
      http_response_code(400);
      echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Handle validation of the contents of a rule import file.
   *
   * Checks the rows in the import file are valid. Controller action for a
   * web request so the respose is echoed.
   */
  public function validate_contents() {
    header("Content-Type: application/json");
    try {
      if (empty($_POST['uploadedFile'])) {
        throw new exception('Parameter for uploadedFile is required.');
      }
      // Ensure we have write permissions.
      $this->authenticate('write');
      $db = new Database();
      $importTools = new ImportTools();
      $config = ['rowsRead' => 0];
      $columnTitles = $importTools->loadColumnTitlesFromFile($_POST['uploadedFile'], TRUE);
      $titleIndexes = array_flip($columnTitles);
      $rows = $importTools->openSpreadsheet($_POST['uploadedFile'], $config);
      $errors = [];
      foreach ($rows as $rowIndex => $rowObject) {
        // Skip empty rows.
        if (!$rowObject->isEmpty()) {
          $row = $importTools->rowToArray($rowObject, $columnTitles);
          // Convert zero-indexed to 1-indexed for reporting sheet rows.
          $spreadsheetRow = $rowIndex + 1;
          // Basic format validation.
          $this->validateIntegers($errors, $row, $spreadsheetRow, $titleIndexes, [
            'min year',
            'max year',
            'min month',
            'max month',
            'min day',
            'max day',
            'max individual count',
          ]);
          $this->validateFloats($errors, $row, $spreadsheetRow, $titleIndexes, [
            'min longitude',
            'min latitude',
            'max longitude',
            'max latitude',
            'limit to min longitude',
            'limit to min latitude',
            'limit to max longitude',
            'limit to max latitude',
          ]);
          // Validate taxon.
          try {
            $this->getTaxonExternalKey($db, $row, $spreadsheetRow, $titleIndexes);
          }
          catch (ValueError $e) {
            $errors[] = $e->getMessage();
          }
          // Validate geography limits.
          $this->validateGridRefFields($errors, $row, $spreadsheetRow, $titleIndexes, 'limit to grid refs', 'grid ref system');
          $this->validateLocationIdFields($errors, $row, $spreadsheetRow, $titleIndexes, 'limit to grid ids');
          $reverseRule = $this->getValue($row, $titleIndexes, 'reverse rule');
          if (!in_array($reverseRule, ['yes', 'no', NULL])) {
            $errors[] = "Invalid value $reverseRule for rule type on row $spreadsheetRow";
          }
          switch (strtolower($this->getValue($row, $titleIndexes, 'rule type'))) {
            case 'abundance':
              $this->validateRequired($errors, $row, $spreadsheetRow, $titleIndexes, ['max individual count']);
              break;

            case 'geography':
              // Check at least one of the geography fields provided.
              $this->validateAnyRequired($errors, $row, $spreadsheetRow, $titleIndexes, [
                'grid refs',
                'min longitude',
                'min latitude',
                'max longitude',
                'max latitude',
                'location ids',
              ]);
              $this->validateGridRefFields($errors, $row, $spreadsheetRow, $titleIndexes, 'grid refs', 'grid ref system');
              $this->validateLocationIdFields($errors, $row, $spreadsheetRow, $titleIndexes, 'location ids');
              break;

            case 'phenology':
              // Check at least one of the phenology fields provided.
              $this->validatePhenology($errors, $row, $spreadsheetRow, $titleIndexes);
              break;

            case 'period':
              // Check at least one of the period fields provided.
              $this->validateAnyRequired($errors, $row, $spreadsheetRow, $titleIndexes, [
                'min year',
                'max year',
              ]);
              break;

            case 'species recorded':
              // No further checks required.
              break;

            default:
              throw new Exception("Missing or invalid rule type on row $spreadsheetRow.");
          }
        }
      }
      if (empty($errors)) {
        echo json_encode([
          'status' => 'ok',
        ]);
      }
      else {
        http_response_code(400);
        echo json_encode([
          'status' => 'error',
          'errorList' => $errors,
        ]);
      }
    }
    catch (Exception $e) {
      kohana::log('debug', 'Error in validate_contents: ' . $e->getMessage());
      http_response_code(400);
      echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Check that grid refs have a valid system and are valid themselves.
   *
   * @param array $errors
   *   Errors list to add to.
   * @param array $row
   *   Row data.
   * @param int $spreadsheetRow
   *   Spreadsheet row number for reporting any errors.
   * @param array $titleIndexes
   *   Array of column position indexes by column title.
   * @param string $gridRefsColTitle
   *   Title of the column holding the grid references.
   * @param string $systemColTitle
   *   Title of the column holding the grid reference system.
   */
  private function validateGridRefFields(array &$errors, array $row, $spreadsheetRow, array $titleIndexes, $gridRefsColTitle, $systemColTitle) {
    $gridRefs = $this->getValueAsArray($row, $titleIndexes, $gridRefsColTitle);
    $gridRefSystem = $this->getValue($row, $titleIndexes, $systemColTitle);
    if (!empty($gridRefs) && empty($gridRefSystem)) {
      $errors[] = "Missing $systemColTitle value for $gridRefsColTitle rule on row $spreadsheetRow.";
    }
    elseif (!empty($gridRefs) && !empty($gridRefSystem)) {
      if (!array_key_exists(strtolower($gridRefSystem), spatial_ref::system_metadata())) {
        $errors[] = "Unrecognised $systemColTitle ($gridRefSystem) on row $spreadsheetRow.";
      }
      else {
        foreach ($gridRefs as $gridRef) {
          if (!spatial_ref::is_valid($gridRef, $gridRefSystem)) {
            $errors[] = "Unrecognised $gridRefsColTitle ($gridRef) on row $spreadsheetRow.";
          }
        }
      }
    }
  }

  /**
   * Check that location ID fields are a valid list of integers.
   *
   * @param array $errors
   *   Errors list to add to.
   * @param array $row
   *   Row data.
   * @param int $spreadsheetRow
   *   Spreadsheet row number for reporting any errors.
   * @param array $titleIndexes
   *   Array of column position indexes by column title.
   * @param string $locationIdsColTitle
   *   Title of the column holding the location Ids.
   */
  private function validateLocationIdFields(array &$errors, array $row, $spreadsheetRow, array $titleIndexes, $locationIdsColTitle) {
    $locationIds = $this->getValue($row, $titleIndexes, $locationIdsColTitle);
    if (!empty($locationIds) && !preg_match('/^\d+(;\s*\d+)*$/', $locationIds)) {
      $errors[] = "Invalid format for $locationIdsColTitle ($locationIds) on row $spreadsheetRow.";
    }
  }

  /**
   * Checks that any phenology rule parameters are valid.
   *
   * @param array $errors
   *   Errors list to add to.
   * @param array $row
   *   Row data.
   * @param int $spreadsheetRow
   *   Spreadsheet row number for reporting any errors.
   * @param array $titleIndexes
   *   Array of column position indexes by column title.
   */
  private function validatePhenology(array &$errors, array $row, $spreadsheetRow, array $titleIndexes) {
    $origErrorCount = count($errors);
    $this->validateAnyRequired($errors, $row, $spreadsheetRow, $titleIndexes, [
      'min month',
      'max month',
    ]);
    // Abort if missing values or non-integers already found.
    if (count($errors) > $origErrorCount) {
      return;
    }
    $minMonth = $this->getValue($row, $titleIndexes, 'min month');
    $maxMonth = $this->getValue($row, $titleIndexes, 'max month');
    $minDay = $this->getValue($row, $titleIndexes, 'min day');
    $maxDay = $this->getValue($row, $titleIndexes, 'max day');
    if (!empty($minMonth) && ($minMonth < 1 || $minMonth > 12)) {
      $errors[] = "Invalid month number for min month in row $spreadsheetRow.";
    }
    if (!empty($maxMonth) && ($maxMonth < 1 || $maxMonth > 12)) {
      $errors[] = "Invalid month number for max month in row $spreadsheetRow.";
    }
    if (!empty($maxDay) && empty($maxMonth)) {
      $errors[] = "Row $spreadsheetRow specifies a min day, but does not specify the min month so it is meaningless.";
    }
    if (!empty($maxDay) && empty($maxMonth)) {
      $errors[] = "Row $spreadsheetRow specifies a max day, but does not specify the max month so it is meaningless.";
    }
    // Abort if invalid month numbers or missing months as remaining tests require them.
    if (count($errors) > $origErrorCount) {
      return;
    }
    if (!empty($minDay) && !empty($minMonth) && ($minDay < 1 || $minDay > customVerificationRules::getDaysInMonth($minMonth))) {
      $errors[] = "Invalid day number for min day in row $spreadsheetRow.";
    }
    if (!empty($maxDay) && !empty($maxMonth) && ($maxDay < 1 || $maxDay > customVerificationRules::getDaysInMonth($maxMonth))) {
      $errors[] = "Invalid day number for max day in row $spreadsheetRow.";
    }
    // Abort if invalid day numbers as remaining tests require them.
    if (count($errors) > $origErrorCount) {
      return;
    }
    // Check if min and max wrong way round.
    if (!empty($minMonth) && !empty($maxMonth)) {
      // Convert to day of year for easy comparison.
      $minDoy = customVerificationRules::dayInMonthToDayInYear(FALSE, $minMonth, $minDay);
      $maxDoy = customVerificationRules::dayInMonthToDayInYear(TRUE, $maxMonth, $maxDay);
      if ($minDoy > $maxDoy) {
        $errors[] = "Min and max dates wrong way round in row $spreadsheetRow.";
      }
    }
  }

  /**
   * Finds the value for a named column in the import data.
   *
   * If the column is missing, or the value empty, returns null.
   *
   * @param array $row
   *   Row data.
   * @param array $titleIndexes
   *   Array of column position indexes by column title.
   * @param string $col
   *   Column title.
   *
   * @return string|null
   *   Data value.
   */
  private function getValue(array $row, array $titleIndexes, $col) {
    return isset($titleIndexes[$col]) && trim($row[$titleIndexes[$col]] ?? '') !== ''
      ? trim($row[$titleIndexes[$col]]) : NULL;
  }

  /**
   * Finds the value for a named array column in the import data.
   *
   * If the column is missing, or the value empty, returns null. Array values
   * in the import value are in a single semi-colon separated string which is
   * split and returned as an array.
   *
   * @param array $row
   *   Row data.
   * @param array $titleIndexes
   *   Array of column position indexes by column title.
   * @param string $col
   *   Column title.
   *
   * @return array
   *   Data array.
   */
  private function getValueAsArray(array $row, array $titleIndexes, $col) {
    $value = $this->getValue($row, $titleIndexes, $col);
    if (!empty($value)) {
      $valueArr = explode(";", $value);
      array_walk($valueArr, 'trim');
      return $valueArr;
    }
    else {
      return NULL;
    }
  }

  /**
   * Finds the value for a named column in the import data.
   *
   * If the column is missing, or the value empty, returns null. Array values
   * in the import value are in a single semi-colon separated string which is
   * reformatted as a PostreSQL format array for an SQL statement.
   *
   * @param array $row
   *   Row data.
   * @param array $titleIndexes
   *   Array of column position indexes by column title.
   * @param string $col
   *   Column title.
   *
   * @return string|null
   *   Data value in plpgsql array format.
   */
  private function getValueAsPgArray($db, array $row, array $titleIndexes, $col) {
    $value = $this->getValue($row, $titleIndexes, $col);
    if (!empty($value)) {
      $valueArr = explode(";", $value);
      foreach ($valueArr as &$item) {
        $item = pg_escape_literal($db->getLink(), trim($item));
      }
      return 'ARRAY[' . implode(',', $valueArr) . ']';
    }
    else {
      return NULL;
    }
  }

  /**
   * Find the taxon external key for a given row.
   *
   * Throws an exception if a unique taxon key cannot be found.
   *
   * @return string
   *   Taxon external key (e.g. Taxon Version Key).
   *
   * @todo Consider case sensitivity.
   */
  private function getTaxonExternalKey($db, $row, $spreadsheetRow, $titleIndexes) {
    $taxonId = $this->getValue($row, $titleIndexes, 'taxon id');
    $taxon = $this->getValue($row, $titleIndexes, 'taxon');
    $taxonListId = (int) $_POST['taxon_list_id'];
    if (!empty($taxonId)) {
      // Search on accepted name keys.
      $query = <<<SQL
        SELECT external_key
        FROM cache_taxa_taxon_lists
        WHERE taxon_list_id=?
        AND external_key=?
        AND preferred=true
        AND allow_data_entry=true;
      SQL;
      // A second search on synonym name keys if the first search fails.
      $altQuery = <<<SQL
        SELECT external_key
        FROM cache_taxa_taxon_lists
        WHERE taxon_list_id=?
        AND search_code=?
      SQL;
      $params = [$taxonListId, $taxonId];
    }
    elseif (!empty($taxon)) {
      $query = <<<SQL
        SELECT external_key
        FROM cache_taxa_taxon_lists
        WHERE taxon_list_id=?
        AND taxon=? AND preferred=true;
      SQL;
      $altQuery = <<<SQL
        SELECT external_key
        FROM cache_taxa_taxon_lists
        WHERE taxon_list_id=?
        AND taxon=? AND preferred=false;
      SQL;
      $params = [$taxonListId, $taxon];
    }
    else {
      throw new ValueError("No taxon identifier for row $spreadsheetRow.");
    }
    $rows = $db->query($query, $params);
    if (count($rows) > 1) {
      $reason = empty($taxonId)
        ? count($rows) . " taxa were found with the same accepted name ($taxon)."
        : count($rows) . " taxa were found with the same accepted name TVK ($taxonId).";
      $msg = "Failed to find a unique taxon using the information given for row $spreadsheetRow because $reason";
      throw new ValueError($msg);
    }
    elseif (count($rows) === 1) {
      return $rows->current()->external_key;
    }
    elseif (count($rows) === 0) {
      // Found nothing, so try the alt query which casts the net wider.
      $rows = $db->query($altQuery, $params);
      if (count($rows) > 1) {
        $reason = empty($taxonId)
          ? count($rows) . " taxa were found with the same synonym name ($taxon)."
          : count($rows) . " taxa were found with the same synonym name TVK ($taxonId).";
        throw new ValueError("Failed to find a unique taxon using the information given for row $spreadsheetRow.");
      }
      elseif (count($rows) === 1) {
        return $rows->current()->external_key;
      }
      elseif (count($rows) === 0) {
        $identifier = empty($taxonId) ? 'taxon name' : 'TVK';
        throw new ValueError("Failed to find a taxon using the $identifier given for row $spreadsheetRow.");
      }
    }
  }

  /**
   * Validates that values for fields in a row are all integer format.
   *
   * @param array $errors
   *   Errors list to add to.
   * @param array $row
   *   Row data.
   * @param int $spreadsheetRow
   *   Spreadsheet row number for reporting any errors.
   * @param array $titleIndexes
   *   Array of column position indexes by column title.
   * @param array $cols
   *   Columns to check.
   */
  private function validateIntegers(array &$errors, array $row, $spreadsheetRow, array $titleIndexes, array $cols) {
    foreach ($cols as $col) {
      $value = $this->getValue($row, $titleIndexes, $col);
      if (!empty($value) && !preg_match('/^\d+$/', $value)) {
        $errors[] = "Positive whole number required for the $col value on row $spreadsheetRow.";
      }
    }
  }

  /**
   * Validates that values for fields in a row are all float format.
   *
   * @param array $errors
   *   Errors list to add to.
   * @param array $row
   *   Row data.
   * @param int $spreadsheetRow
   *   Spreadsheet row number for reporting any errors.
   * @param array $titleIndexes
   *   Array of column position indexes by column title.
   * @param array $cols
   *   Columns to check.
   */
  private function validateFloats(array &$errors, array $row, $spreadsheetRow, array $titleIndexes, array $cols) {
    foreach ($cols as $col) {
      $value = $this->getValue($row, $titleIndexes, $col);
      if (!empty($value) && !preg_match('/^\-?\d+(.\d+)?$/', $value)) {
        $errors[] = "Number required for the $col value on row $spreadsheetRow.";
      }
    }
  }

  /**
   * Validates that values are provided for all specified fields in a row.
   *
   * @param array $errors
   *   Errors list to add to.
   * @param array $row
   *   Row data.
   * @param int $spreadsheetRow
   *   Spreadsheet row number for reporting any errors.
   * @param array $titleIndexes
   *   Array of column position indexes by column title.
   * @param array $cols
   *   Columns to check.
   */
  private function validateRequired(array &$errors, array $row, $spreadsheetRow, array $titleIndexes, array $cols) {
    foreach ($cols as $col) {
      if ($this->getValue($row, $titleIndexes, $col) === NULL) {
        $errors[] = "Value required for the $col value on row $spreadsheetRow.";
      }
    }
  }

  /**
   * Ensure that a value is provided for at least one specified field in a row.
   *
   * @param array $errors
   *   Errors list to add to.
   * @param array $row
   *   Row data.
   * @param int $spreadsheetRow
   *   Spreadsheet row number for reporting any errors.
   * @param array $titleIndexes
   *   Array of column position indexes by column title.
   * @param array $cols
   *   Columns to check.
   */
  private function validateAnyRequired(array &$errors, array $row, $spreadsheetRow, array $titleIndexes, array $cols) {
    foreach ($cols as $col) {
      if (!empty($this->getValue($row, $titleIndexes, $col))) {
        return;
      }
    }
    $errors[] = "Value required for at least one of the values for fields " . implode(';', $cols) . " on row $spreadsheetRow.";
  }

  /**
   * Controller method to import the contents of a rules spreadsheet.
   */
  public function import_contents() {
    header("Content-Type: application/json");
    try {
      if (empty($_POST['uploadedFile'])) {
        throw new exception('Parameter for uploadedFile is required.');
      }
      // Ensure we have write permissions.
      $this->authenticate('write');
      $db = new Database();
      $importTools = new ImportTools();
      $config = ['rowsRead' => 0];
      $columnTitles = $importTools->loadColumnTitlesFromFile($_POST['uploadedFile'], TRUE);
      $titleIndexes = array_flip($columnTitles);
      $rows = $importTools->openSpreadsheet($_POST['uploadedFile'], $config);
      $rulesetId = $_POST['custom_verification_ruleset_id'];
      foreach ($rows as $rowIndex => $rowObject) {
        // Skip empty rows.
        if (!$rowObject->isEmpty()) {
          $row = $importTools->rowToArray($rowObject, $columnTitles);
          // Convert zero-indexed to 1-indexed for reporting sheet rows.
          $spreadsheetRow = $rowIndex + 1;
          $ruleType = strtolower($this->getValue($row, $titleIndexes, 'rule type'));
          $taxonKey = $this->getTaxonExternalKey($db, $row, $spreadsheetRow, $titleIndexes);
          $reverseRule = strtolower($this->getValue($row, $titleIndexes, 'reverse rule') ?? '') === 'yes' ? 't' : 'f';
          switch ($ruleType) {
            case 'abundance':
              $definition = $this->getAbundanceDefinition($row, $titleIndexes);
              break;

            case 'geography':
              $definition = $this->getGeographyDefinition($row, $titleIndexes);
              break;

            case 'phenology':
              $definition = $this->getPhenologyDefinition($row, $titleIndexes);
              break;

            case 'period':
              $definition = $this->getPeriodDefinition($row, $titleIndexes);
              break;

            case 'species recorded':
              // No further checks required.
              $definition = [];
              break;

            default:
              throw new Exception("Missing or invalid rule type.");
          }
          // Reformat the limit to stages value ready for SQL insert query.
          $limitToStages = $this->getValueAsPgArray($db, $row, $titleIndexes, 'limit to stages');

          // Reformat the limit to geography values ready for SQL insert query.
          $limitToGeographyArr = [];
          $limitToGridRefs = $this->getValueAsArray($row, $titleIndexes, 'limit to grid refs');
          if (!empty($limitToGridRefs)) {
            $limitToGeographyArr['grid_refs'] = $limitToGridRefs;
            $limitToGeographyArr['grid_ref_system'] = $this->getValue($row, $titleIndexes, 'grid ref system');
          }
          if ($limitToMinLon = $this->getValue($row, $titleIndexes, 'limit to min longitude')) {
            $limitToGeographyArr['min_lng'] = $limitToMinLon;
          }
          if ($limitToMinLat = $this->getValue($row, $titleIndexes, 'limit to min latitude')) {
            $limitToGeographyArr['min_lat'] = $limitToMinLat;
          }
          if ($limitToMaxLng = $this->getValue($row, $titleIndexes, 'limit to max longitude')) {
            $limitToGeographyArr['max_lng'] = $limitToMaxLng;
          }
          if ($limitToMaxLat = $this->getValue($row, $titleIndexes, 'limit to max latitude')) {
            $limitToGeographyArr['max_lat'] = $limitToMaxLat;
          }
          $limitToLocationIds = $this->getValueAsArray($row, $titleIndexes, 'limit to location ids');
          if (!empty($limitToLocationIds)) {
            $limitToGeographyArr['location_ids'] = $limitToLocationIds;
          }
          // @todo Check limit to location ids are validated.
          $limitToGeography = empty($limitToGeographyArr) ? NULL : json_encode($limitToGeographyArr);
          // LimitToStages is injection safe anyway, so splice into query
          // directly to avoid the query param adding quotes as it is a string.
          $insertSql = <<<SQL
            INSERT INTO custom_verification_rules(custom_verification_ruleset_id, taxon_external_key,
              fail_icon, fail_message, limit_to_stages, limit_to_geography, rule_type, definition, reverse_rule,
              created_by_id, created_on, updated_by_id, updated_on)
            VALUES (?, ?, ?, ?, $limitToStages, ?::json, ?, ?::json, ?,
              $this->auth_user_id, now(), $this->auth_user_id, now());
          SQL;
          $db->query($insertSql, [
            $rulesetId,
            $taxonKey,
            $this->getValue($row, $titleIndexes, 'fail icon'),
            $this->getValue($row, $titleIndexes, 'fail message'),
            $limitToGeography,
            $ruleType,
            json_encode($definition),
            $reverseRule,
          ]);
        }
      }
      echo json_encode([
        'status' => 'ok',
      ]);
    }
    catch (Exception $e) {
      kohana::log('debug', 'Error in import_contents: ' . $e->getMessage());
      http_response_code(400);
      echo json_encode([
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Returns the definition field value object for an abundance rule.
   *
   * @param array $row
   *   Row data.
   * @param array $titleIndexes
   *   Array of column titles with their index position.
   *
   * @return array
   *   Definition data.
   */
  private function getAbundanceDefinition(array $row, array $titleIndexes) {
    $definition = [
      'max_individual_count' => $this->getValue($row, $titleIndexes, 'max individual count'),
    ];
    return $definition;
  }

  /**
   * Returns the definition field value object for a geography rule.
   *
   * @param array $row
   *   Row data.
   * @param array $titleIndexes
   *   Array of column titles with their index position.
   *
   * @return array
   *   Definition data.
   */
  private function getGeographyDefinition(array $row, array $titleIndexes) {
    $definition = [];
    if ($gridRefs = $this->getValueAsArray($row, $titleIndexes, 'grid refs')) {
      $definition['grid_refs'] = $gridRefs;
    }
    if ($gridRefSystem = $this->getValue($row, $titleIndexes, 'grid ref system')) {
      $definition['grid_ref_system'] = $gridRefSystem;
    }
    if ($minLng = $this->getValue($row, $titleIndexes, 'min longitude')) {
      $definition['min_lng'] = $minLng;
    }
    if ($minLat = $this->getValue($row, $titleIndexes, 'min latitude')) {
      $definition['min_lat'] = $minLat;
    }
    if ($maxLng = $this->getValue($row, $titleIndexes, 'max longitude')) {
      $definition['max_lng'] = $maxLng;
    }
    if ($maxLat = $this->getValue($row, $titleIndexes, 'max latitude')) {
      $definition['max_lat'] = $maxLat;
    }
    if ($locationIds = $this->getValueAsArray($row, $titleIndexes, 'location ids')) {
      $definition['location_ids'] = $locationIds;
    }
    return $definition;
  }

  /**
   * Returns the definition field value object for a phenology rule.
   *
   * @param array $row
   *   Row data.
   * @param array $titleIndexes
   *   Array of column titles with their index position.
   *
   * @return array
   *   Definition data.
   */
  private function getPhenologyDefinition(array $row, array $titleIndexes) {
    $definition = [];
    if ($minMonth = $this->getValue($row, $titleIndexes, 'min month')) {
      $definition['min_month'] = $minMonth;
    }
    if ($maxMonth = $this->getValue($row, $titleIndexes, 'max month')) {
      $definition['max_month'] = $maxMonth;
    }
    if ($minDayOfYear = $this->getValue($row, $titleIndexes, 'min day')) {
      $definition['min_day'] = $minDayOfYear;
    }
    if ($maxDayOfYear = $this->getValue($row, $titleIndexes, 'max day')) {
      $definition['max_day'] = $maxDayOfYear;
    }
    return $definition;
  }

  /**
   * Returns the definition field value object for an period rule.
   *
   * @param array $row
   *   Row data.
   * @param array $titleIndexes
   *   Array of column titles with their index position.
   *
   * @return array
   *   Definition data.
   */
  private function getPeriodDefinition(array $row, array $titleIndexes) {
    if ($minYear = $this->getValue($row, $titleIndexes, 'min year')) {
      $definition['min_year'] = $minYear;
    }
    if ($maxYear = $this->getValue($row, $titleIndexes, 'max year')) {
      $definition['max_year'] = $maxYear;
    }
    return $definition;
  }

}
