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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/Indicia-Team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the taxon_designations table.
 */
class Verification_rule_Model extends ORM {

  protected $belongs_to = [
    'created_by' => 'user',
    'updated_by' =>'user',
  ];

  protected $has_many = [
    'verification_rule_data',
    'verification_rule_metadata',
  ];

  /**
   * Is this model instance being used to create a new rule?
   *
   * @var bool
   */
  protected $isNewRule = FALSE;

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('title', 'required', 'length[1,100]');
    $array->add_rules('test_type', 'required');
    $array->add_rules('error_message', 'required');
    // Source_url is not validated as a url because the NBN zip file paths
    // don't validate but must be accepted.
    $this->unvalidatedFields = [
      'description',
      'source_filename',
      'deleted',
      'source_url',
      'reverse_rule',
    ];
    return parent::validate($array, $save);
  }

  /**
   * Return the submission structure for a verification rule.
   *
   * Includes defining the text for the data in the rule file as a metaField
   * which is specially handled.
   *
   * @return array
   *   Submission structure for a verification_rule entry.
   */
  public function get_submission_structure() {
    return [
      'model' => $this->object_name,
      'metaFields' => ['data', 'metadata'],
    ];
  }

  /**
   * Save a verification rule.
   *
   * Saves the part of the rule file metadata section which needs to go into
   * the verification_rule record. A rule file will be overwritten if it comes
   * from the same source url and filename.
   *
   * @param string $source_url
   *   URL the file was sourced from. The model instance points to the
   *   created/updated verification record after the operation.
   * @param string $filename
   *   Rule file filename.
   * @param array $metadata
   *   Array of key/value pairs read from the file's metadata section.
   */
  public function save_verification_rule($source_url, $filename, &$metadata) {
    // Find existing or new verification rule record. Empty string stored in db
    // as null.
    if (empty($source_url)) {
      $source_url = NULL;
    }
    $this->where([
      'source_url' => $source_url,
      'source_filename' => $filename,
      'deleted' => 'f',
    ])->find();
    // Because in a previous version the filename got stored without the path,
    // look for existing rules to overwrite so we don't end up with duplicate
    // rules.
    if ($this->id === 0) {
      $this->where([
        'source_url' => $source_url,
        'source_filename' => basename($filename),
        'deleted' => 'f',
      ])->find();
    }
    if (isset($metadata['shortname'])) {
      $title = $metadata['shortname'];
    }
    else {
      // No short name in the rule, so build a valid title.
      $titleArr = array($metadata['testtype']);
      if (isset($metadata['organisation'])) {
        $titleArr[] = $metadata['organisation'];
      }
      $title = implode(' - ', $titleArr);
    }
    if (isset($metadata['errormsg'])) {
      $errorMsg = $metadata['errormsg'];
    }
    else {
      $errorMsg = 'Test failed';
    }
    $reverseRule = isset($metadata['reverserule']) && strtolower($metadata['reverserule']) === 'true' ? 't' : 'f';

    $submission = [
      'verification_rule:title' => $title,
      'verification_rule:test_type' => $metadata['testtype'],
      'verification_rule:source_url' => $source_url,
      'verification_rule:source_filename' => $filename,
      'verification_rule:error_message' => $errorMsg,
      // The error message gives us a useful description in the absence of a
      // specific one.
      'verification_rule:description' => isset($metadata['description']) ? $metadata['description'] : $errorMsg,
      'verification_rule:reverse_rule' => $reverseRule,
    ];
    $this->isNewRule = $this->id === 0;
    if (!$this->isNewRule) {
      $submission['verification_rule:id'] = $this->id;
    }
    $this->set_submission_data($submission);
    $this->submit();
    $vr = ORM::factory('verification_rule', $this->id);
    kohana::log('debug', 'Saved as: ' . print_r($vr->as_array(), TRUE));
    // Remove things from the metadata which we have put in the
    // verification_rule record so they don't get processed again later.
    unset($metadata['testtype']);
    unset($metadata['description']);
    unset($metadata['shortname']);
    unset($metadata['organisation']);
    unset($metadata['errormsg']);
    unset($metadata['group']);
    unset($metadata['lastchanged']);
    unset($metadata['reverserule']);
    if (count($this->getAllErrors()) > 0) {
      throw new exception("Errors saving $filename to database - " . print_r($this->getAllErrors(), TRUE));
    }
  }

  /**
   * Saves verification rule metadata.
   *
   * Uses to associative array of metadata values to ensure that the correct
   * list of metadata records exists for the current rule instance.
   *
   * @param array $currentRule
   *   Definition of the current rule.
   * @param array $metadata
   *   Associative array of metadata key/value pairs.
   */
  public function save_verification_rule_metadata(array $currentRule, array $metadata) {
    $recordsInSubmission = [];
    // Work out the fields to submit into the metadata table.
    $fields = array_merge_recursive($currentRule['required'], $currentRule['optional']);
    if (isset($fields['Metadata'])) {
      // A quick test to ensure we don't have keys in the data we shouldn't.
      $got = array_keys(array_change_key_case($metadata));
      $expect = array_map('strtolower', $fields['Metadata']);
      $dontWant = array_diff($got, $expect);
      if (count($dontWant)) {
        throw new exception('The following metadata items are not recognised for this rule type: ' . implode(',', $dontWant));
      }
      // Force keys lowercase for case-insensitive lookup.
      $metadata = array_change_key_case($metadata, CASE_LOWER);
      foreach ($fields['Metadata'] as $idx => $field) {
        if (array_key_exists(strtolower($field), $metadata) && trim($metadata[strtolower($field)]) !== '') {
          $fieldEsc = pg_escape_literal($this->db->getLink(), $field);
          $recordsInSubmission[] = "(verification_rule_id='$this->id' and key=$fieldEsc)";
          $vrm = ORM::Factory('verification_rule_metadatum')->where([
            'verification_rule_id' => $this->id,
            'key' => $field,
          ])->find();
          $submission = [
            'verification_rule_metadatum:key' => $field,
            'verification_rule_metadatum:value' => $metadata[strtolower($field)],
            'verification_rule_metadatum:verification_rule_id' => $this->id,
            'verification_rule_metadatum:deleted' => 'f',
          ];
          if ($vrm->id !== 0) {
            $submission['verification_rule_metadatum:id'] = $vrm->id;
          }
          $vrm->set_submission_data($submission);
          $vrm->submit();
          if (count($vrm->getAllErrors()) > 0) {
            throw new exception("Errors saving verification rule to database - " . print_r($vrm->getAllErrors(), TRUE));
          }
        }
        elseif (isset($currentRule['required']['Metadata']) && in_array($field, $currentRule['required']['Metadata'])) {
          throw new exception("Required field $field missing from the metadata");
        }
      }
    }
    // Construct a query to remove any existing records.
    $deleteQuery = "update verification_rule_metadata set deleted=true where verification_rule_id=$this->id";
    if (count($recordsInSubmission)) {
      $deleteQuery .= ' and not (' . implode(' or ', $recordsInSubmission) . ')';
    }
    $this->db->query($deleteQuery);
  }

  public function save_verification_rule_data($currentRule, $data) {
    $fields = array_merge_recursive($currentRule['required'], $currentRule['optional']);
    unset($fields['Metadata']);
    // Counter to keep track of groups of related field values in a data
    // section. Not implemented properly at the moment but we are likely to
    // need this e.g. for periodInYear checks with multiple stages.
    $rows = [];
    // A quick test to ensure we don't have sections in the data we shouldn't.
    $got = array_keys(array_change_key_case($data));
    $expect = array_keys(array_change_key_case($fields));
    $dontWant = array_diff($got, $expect);
    if (count($dontWant)) {
      // This is the nested values, should be top level.
      kohana::log('debug', 'got ' . print_r($got, TRUE));
      // This is the top level category.
      kohana::log('debug', 'expect ' . print_r($expect, TRUE));
      kohana::log('debug', 'dontWant ' . print_r($dontWant, TRUE));
      throw new exception('The following data sections are not recognised for this rule type: ' .
        implode(',', $dontWant));
    }
    foreach ($fields as $dataSection => $dataContent) {
      if (isset($data[strtolower($dataSection)])) {
        foreach ($data[strtolower($dataSection)] as $dataGroupNum => $dataGroup) {
          // Unset geom, as we auto-calculate it for withoutPolygons, not really
          // part of the rule definition.
          unset($dataGroup['geom']);
          if (!in_array('*', $dataContent)) {
            // A quick test to ensure we don't have keys in the data we
            // shouldn't. Test not required if we have a wildcard key allowed.
            $got = array_keys(array_change_key_case($dataGroup));
            $expect = array_map('strtolower', $dataContent);
            $dontWant = array_diff($got, $expect);
            if (count($dontWant)) {
              throw new exception('The following data keys are not recognised for this rule type: ' .
                implode(',', $dontWant) . print_r($dataContent, TRUE));
            }
          }

          foreach ($dataContent as $key) {
            if ($key === '*') {
              // * means that any field value is allowed.
              foreach ($dataGroup as $anyField => $anyValue) {
                $rows[] = [
                  'dataSection' => $dataSection,
                  'dataGroup' => $dataGroupNum + 1,
                  'key' => $anyField,
                  'value' => $anyValue,
                ];
              }
            }
            elseif (isset($dataGroup[strtolower($key)])) {
              // Doing specific named fields.
              $rows[] = [
                'dataSection' => $dataSection,
                'dataGroup' => $dataGroupNum + 1,
                'key' => $key,
                'value' => $dataGroup[strtolower($key)],
              ];
            }
            else {
              if (isset($currentRule['required'][$dataSection]) && in_array($key, $currentRule['required'][$dataSection])) {
                throw new exception("Required field $key missing from the data for section $dataSection");
              }
            }
          }
        }
      }
    }
    $this->saveVerificationRuleDataRecords($rows);
  }

  /**
   * Additional processing post verification rule update.
   *
   * Call this method after making any updates to a verification rule including
   * the data and metadata.
   *
   * @param array $currentRule
   *   Definition of the rule.
   */
  public function postProcessRule($currentRule) {
    $ppMethod = $currentRule['plugin'] . '_data_cleaner_postprocess';
    require_once MODPATH . "$currentRule[plugin]/plugins/$currentRule[plugin].php";
    if (function_exists($ppMethod)) {
      call_user_func($ppMethod, $this->id, $this->db);
    }

  }

  /**
   * Save a list of verification rule data records.
   *
   * Either overwrites existing or creates a new one. Avoids ORM for
   * performance reasons as some files can be pretty big.
   *
   * @param array $rows
   *   Data rows.
   */
  private function saveVerificationRuleDataRecords(array $rows) {
    $done = [];
    $recordsInSubmission = [];
    // Only worth trying an update if we are updating an existing rule.
    if (!$this->isNewRule) {
      foreach ($rows as $idx => $row) {
        $updated = $this->db->update('verification_rule_data',
          [
            'value' => $row['value'],
            'updated_on' => date("Ymd H:i:s"),
            'updated_by_id' => $_SESSION['auth_user']->id,
            'deleted' => 'f',
          ],
          [
            'header_name' => $row['dataSection'],
            'data_group' => $row['dataGroup'],
            'verification_rule_id' => $this->id,
            'key' => strval($row['key']),
          ]
        );
        if (count($updated)) {
          $done[] = $idx;
        }
        $recordsInSubmission[] = "(header_name='$row[dataSection]' and data_group='$row[dataGroup]'" .
            " and verification_rule_id='$this->id' and key='" . strval($row['key']) . "')";
      }
      // Construct a query to remove any existing records.
      $deleteQuery = "update verification_rule_data set deleted=true where verification_rule_id=$this->id";
      if (count($recordsInSubmission)) {
        $deleteQuery .= ' and not (' . implode(' or ', $recordsInSubmission) . ')';
      }
      $this->db->query($deleteQuery);
    }
    // Build a multirow insert as it is faster than doing lots of single
    // inserts.
    $rowList = [];
    $userId = (int) $_SESSION['auth_user']->id;
    foreach ($rows as $idx => $row) {
      if (array_search($idx, $done) === FALSE) {
        $dataSection = pg_escape_literal($this->db->getLink(), $row['dataSection']);
        $dataGroup = (int) $row['dataGroup'];
        $value = pg_escape_literal($this->db->getLink(), $row['value']);
        $key = pg_escape_literal($this->db->getLink(), strval($row['key']));
        $now = date('Ymd H:i:s');
        $rowList[] = "($dataSection, $dataGroup, $this->id, $key, $value, '$now', $userId, '$now', $userId)";
      }
    }
    if ($rowList) {
      $this->db->query(
        'insert into verification_rule_data(header_name, data_group, verification_rule_id, key, ' .
        'value, updated_on, updated_by_id, created_on, created_by_id) ' .
        'values ' . implode(', ', $rowList));
    }
  }

  /**
   * Overrides the postSubmit function to perform additional db changes.
   *
   * Only applies when handling submissions from warehouse UI, not file uploads.
   * * Update metadata and data tables
   * * Add in additional metadata calculated from other rule data.
   * * Allows rules which use a cache table for performance to update the
   *   cache.
   */
  protected function postSubmit($isInsert) {
    require_once DOCROOT . 'client_helpers/helper_base.php';
    if (isset($this->submission['metaFields']['metadata'])) {
      // Submission is from warehouse UI.
      $currentRule = data_cleaner::getRule($this->test_type);
      if (isset($this->submission['metaFields']['metadata']['value'])) {
        $metadata = helper_base::explode_lines_key_value_pairs($this->submission['metaFields']['metadata']['value']);
        $this->save_verification_rule_metadata($currentRule, $metadata);
        $data = data_cleaner::parseTestFile($this->submission['metaFields']['data']['value']);
        $this->save_verification_rule_data($currentRule, $data);
        $this->postProcessRule($currentRule);
        $this->updateCache();
      }
    }
    return TRUE;
  }

  /**
   * Update cache tables.
   *
   * Call this method after making any updates to a verification rule including
   * the data and metadata.
   */
  public function updateCache() {
    // If the rule type uses a cache table to improve performance, update it.
    $rule = trim(strtolower(preg_replace('/([A-Z])/', '_$1', $this->test_type)), '_');
    require_once MODPATH . "data_cleaner_$rule/plugins/data_cleaner_$rule.php";
    if (function_exists("data_cleaner_{$rule}_cache_sql")) {
      // Delete old cached values.
      $cacheTable = pg_escape_identifier($db->getLink(), "cache_verification_rules_$rule");
      $this->db->query("delete from $cacheTable where verification_rule_id=$this->id");
      // Only add back to cache if not deleting.
      // Note, when importing new rules from file, deleted is null.
      if ($this->deleted !== 't') {
        $sql = call_user_func("data_cleaner_{$rule}_cache_sql");
        $sql = str_replace('#id#', $this->id, $sql);
        $this->db->query($sql);
      }
    }
  }

}
