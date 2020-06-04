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
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * Model class for the Occurrences table.
 */
class Occurrence_Model extends ORM {
  protected $requeuedForVerification = FALSE;

  protected $has_many = array(
    'occurrence_attribute_values',
    'determinations',
    'occurrence_media'
  );
  protected $belongs_to = array(
    'determiner' => 'person',
    'sample',
    'taxa_taxon_list',
    'created_by' => 'user',
    'updated_by' => 'user',
    'verified_by' => 'user'
  );
  // Declare that this model has child attributes, and the name of the node in the submission which contains them.
  protected $has_attributes = TRUE;
  protected $attrs_submission_name = 'occAttributes';
  public $attrs_field_prefix = 'occAttr';
  protected $additional_csv_fields = array(
    // Extra lookup options.
    'occurrence:fk_taxa_taxon_list:genus' => 'Genus (builds binomial name)',
    'occurrence:fk_taxa_taxon_list:specific' => 'Specific name/epithet (builds binomial name)',
    'occurrence:fk_taxa_taxon_list:external_key' => 'Species or taxon external key',
    'occurrence:fk_taxa_taxon_list:search_code' => 'Species or taxon search code',
    // needs to be more complex version so import recognises it as same field as above
    'occurrence:fk_taxa_taxon_list:id' => 'Species or taxon taxa_taxon_lists.id',
    // Allow details of 4 images to be uploaded in CSV files.
    'occurrence_medium:path:1' => 'Media Path 1',
    'occurrence_medium:caption:1' => 'Media Caption 1',
    'occurrence_medium:path:2' => 'Media Path 2',
    'occurrence_medium:caption:2' => 'Media Caption 2',
    'occurrence_medium:path:3' => 'Media Path 3',
    'occurrence_medium:caption:3' => 'Media Caption 3',
    'occurrence_medium:path:4' => 'Media Path 4',
    'occurrence_medium:caption:4' => 'Media Caption 4'
  );

  // During an import it is possible to merge different columns in a CSV row to make a database field
  public $specialImportFieldProcessingDefn = [
    'occurrence:fk_taxa_taxon_list' => [
      'template' => '%s %s',
      'columns' => ['occurrence:fk_taxa_taxon_list:genus', 'occurrence:fk_taxa_taxon_list:specific'],
    ],
    'sample:date' => [
      'template' => '%04d-%02d-%02d',
      'columns' => ['sample:date:year', 'sample:date:month', 'sample:date:day'],
    ],
  ];

  /**
   * Methods of identifying duplicates during import.
   *
   * Define field combinations that can be used to lookup existing records for
   * updates during import.
   *
   * @var array
   */
  public $importDuplicateCheckCombinations = [
    [
      'description' => 'Occurrence ID',
      'fields' => [
        ['fieldName' => 'website_id', 'notInMappings' => TRUE],
        ['fieldName' => 'occurrence:id'],
      ],
    ],
    [
      'description' => 'Occurrence External Key',
      'fields' => [
        ['fieldName' => 'website_id', 'notInMappings' => TRUE],
        ['fieldName' => 'occurrence:external_key'],
      ],
    ],
    [
      'description' => 'Sample and Taxon',
      'fields' => [
        ['fieldName' => 'website_id', 'notInMappings' => TRUE],
        ['fieldName' => 'occurrence:sample_id', 'notInMappings' => TRUE],
        ['fieldName' => 'occurrence:taxa_taxon_list_id'],
      ],
    ],
  ];

  /**
   * Should a determination be logged if this is a changed record?
   *
   * @var bool
   */
  protected $logDetermination = FALSE;

  /**
   * Returns a caption to identify this model instance.
   */
  public function caption() {
    return 'Record of ' . $this->taxa_taxon_list->taxon->taxon;
  }

  public function validate(Validation $array, $save = FALSE) {
    if ($save) {
      $this->handleRedeterminations($array);
      $fields = array_merge($this->submission['fields']);
      $newStatus = empty($fields['record_status']) ? $this->record_status : $fields['record_status']['value'];
      $newSubstatus = empty($fields['record_substatus']) ? $this->record_substatus : $fields['record_substatus']['value'];
      $releaseStatusChanging = !empty($fields['release_status']) && $fields['release_status']['value'] !== $this->release_status;
      $metadataFieldChanging = !empty($fields['metadata']) && $fields['metadata']['value'] !== $this->metadata;
      $identChanging = !empty($fields['taxa_taxon_list_id']) && $fields['taxa_taxon_list_id']['value'] !== $this->metadata;
      $isAlreadyReviewed = preg_match('/[RDV]/', $this->record_status) || $this->record_substatus === 3;
      // Is this post going to change the record status or substatus?
      if ($newStatus !== $this->record_status || $newSubstatus !== $this->record_substatus) {
        if ($newStatus === 'V' || $newStatus === 'R') {
          // If verifying or rejecting, then set the verification metadata.
          $array->verified_by_id = $this->get_current_user_id();
          $array->verified_on = date("Ymd H:i:s");
        }
        else {
          // If any status other than verified or rejected we don't want
          // the verification metadata filled in.
          $array->verified_by_id = NULL;
          $array->verified_on = NULL;
        }
      }
      elseif (($this->parentChanging || $this->wantToUpdateMetadata) && $isAlreadyReviewed) {
        // We are making a change to a previously reviewed record that doesn't
        // explicitly set the status. If the change is to the release status
        // or occurrence metadata field, then we don't do anything, otherwise
        // we reset the verification data.
        if ((!$releaseStatusChanging && !$metadataFieldChanging) || $identChanging) {
          $array->verified_by_id = NULL;
          $array->verified_on = NULL;
          $array->record_status = 'C';
          $array->record_substatus = NULL;
          $this->requeuedForVerification = TRUE;
        }
      }
    }
    if (!empty($array->record_status) && !empty($array->record_substatus)
        && $array->record_status === 'C' && $array->record_substatus != 3) {
      // Plausible the only valid substatus for C (pending review). Other cases
      // can occur if record form only posts a status.
      $array->record_substatus = NULL;
    }
    $array->pre_filter('trim');
    $array->add_rules('sample_id', 'required');
    $array->add_rules('website_id', 'required');
    $fieldlist = $array->as_array();
    if (!array_key_exists('all_info_in_determinations', $fieldlist) || $fieldlist['all_info_in_determinations'] == 'N') {
      $array->add_rules('taxa_taxon_list_id', 'required');
    }
    // Explicitly add those fields for which we don't do validation.
    $this->unvalidatedFields = array(
      'comment',
      'determiner_id',
      'deleted',
      'record_status',
      'release_status',
      'record_substatus',
      'downloaded_flag',
      'verified_by_id',
      'verified_on',
      'confidential',
      'all_info_in_determinations',
      'external_key',
      'zero_abundance',
      'last_verification_check_date',
      'training',
      'sensitivity_precision',
      'import_guid',
      'metadata',
    );
    if (array_key_exists('id', $fieldlist)) {
      // Existing data must not be set to download_flag=F (final download) otherwise it
      // is read only.
      $array->add_rules('downloaded_flag', 'chars[N,I]');
    }
    return parent::validate($array, $save);
  }

  private function getWhenRecordLastDetermined() {
    if (empty($this->id)) {
      // Use now as default for new records - should not really happen.
      return date("Ymd H:i:s");
    }
    else {
      $rows = $this->db
        ->select('max(updated_on) as last_update')
        ->from('determinations')
        ->where(array(
          'occurrence_id' => $this->id,
          'deleted' => 'f',
        ))->get()->result_array();
      kohana::log('debug', 'Query: ' . $this->db->last_query());
    }
    if (count($rows) > 0 && !empty($rows[0]->last_update)) {
      return $rows[0]->last_update;
    }
    else {
      return $this->created_on;
    }
  }

  /**
   * Handle cases where an existing record is redetermined.
   *
   * This includes logging of the change to the determinations table and
   * updating the identified by field value.
   *
   * @param Validation $array
   *   Validation data.
   */
  private function handleRedeterminations(Validation $array) {
    if (!empty($this->taxa_taxon_list_id) &&
        !empty($this->submission['fields']['taxa_taxon_list_id']['value']) &&
        $this->taxa_taxon_list_id != $this->submission['fields']['taxa_taxon_list_id']['value']) {
      // Only log a determination for the occurrence if the species is changed.
      // Also the all_info_in_determinations flag must be off to avoid clashing with other functionality
      // and the config setting must be enabled.
      $currentUserId = $this->get_current_user_id();
      if (kohana::config('indicia.auto_log_determinations') === TRUE && $this->all_info_in_determinations !== 'Y') {
        $this->logDetermination = TRUE;
        // We log the old taxon.
        $rowToAdd['taxa_taxon_list_id'] = $this->taxa_taxon_list_id;
        $rowToAdd['determination_type'] = 'B';
        $rowToAdd['occurrence_id'] = $this->id;
        // Last change to the occurrence is really the create metadata for this
        // determination, since we are copying it out of the existing
        // occurrence record.
        $rowToAdd['created_by_id'] = $this->updated_by_id;
        $rowToAdd['updated_by_id'] = $this->updated_by_id;
        $rowToAdd['created_on'] = $this->getWhenRecordLastDetermined();
        $rowToAdd['updated_on'] = date("Ymd H:i:s");
        $rowToAdd['person_name'] = $this->get_person_name_and_update_determiner($this->as_array(), $this->updated_by_id);

        $insert = $this->db
          ->from('determinations')
          ->set($rowToAdd)
          ->insert();

        if ($currentUserId !== 1) {
          $this->submission['fields']['determiner_id']['value'] = $currentUserId;
        }
      }
      // Update any determiner occurrence attributes.
      $sql = <<<SQL
UPDATE occurrence_attribute_values v
SET text_value=CASE a.system_function
  WHEN 'det_full_name' THEN COALESCE(p.first_name || ' ', '') || p.surname
  WHEN 'det_first_name' THEN p.first_name
  WHEN 'det_last_name' THEN p.surname
END
FROM occurrence_attributes a, users u
JOIN people p ON p.id=u.person_id
  AND p.deleted=false
WHERE a.deleted=false
AND v.deleted=false
AND v.occurrence_attribute_id=a.id
AND v.occurrence_id=$this->id
AND a.system_function in ('det_full_name', 'det_first_name', 'det_last_name')
AND u.id=$currentUserId
AND u.deleted=false
SQL;
      $this->db->query($sql);
    }
  }

  /**
   * Method that adds a created by, created date, updated by, updated date to a row of data
   * we are going to add/update to the database.
   *
   * @param array $row
   *   A row of data we are adding/updating to the database.
   * @param string $tableName
   *   The name of the table we are adding the row to. We need this as the attribute_websites tables don't have
   *   updated by and updated on fields.
   */
  public function set_metadata_for_row_array(&$row = NULL, $tableName = NULL) {
    if (isset($_SESSION['auth_user']))
      $userId = $_SESSION['auth_user']->id;
    else {
      global $remoteUserId;
      if (isset($remoteUserId))
        $userId = $remoteUserId;
      else {
        $defaultUserId = Kohana::config('indicia.defaultPersonId');
        $userId = ($defaultUserId ? $defaultUserId : 1);
      }
    }
    $row['created_on'] = date("Ymd H:i:s");
    $row['created_by_id'] = $userId;
    // Attribute websites tables don't have updated by/date details columns so we need a special case not to set them.
    if ($tableName !== 'sample_attributes_websites'&&$tableName !== 'occurrence_attributes_websites') {
      $row['updated_on'] = date("Ymd H:i:s");
      $row['updated_by_id'] = $userId;
    }
  }

  /*
   * Collect the user id for the current user, this will be 1 unless logged into warehouse or Easy Login is enabled in instant-indicia.
   */
  public function get_current_user_id() {
    if (isset($_SESSION['auth_user']))
      $userId = $_SESSION['auth_user']->id;
    else {
      global $remoteUserId;
      if (isset($remoteUserId))
        $userId = $remoteUserId;
      else {
        // Don't force overwrite of user IDs that already exist in the record, since
        // we are just using a default.
        $defaultUserId = Kohana::config('indicia.defaultPersonId');
        $userId = ($defaultUserId ? $defaultUserId : 1);
      }
    }
    return $userId;
  }

  /*
   * Method that is called when attempting to fill in determinations.person_name using a determination
   * occurrence attribute value. Code has its own method as the code could be called several times for
   * different determination occurrence attributes (first name, last name, full name)
   */
  public function get_and_update_occ_attr_determiner($determinerNameAttrId, $occurrenceId, $detNameType, $currentUserNames) {
    // Firstly try and get the determiner for the occurrence from an attribute value.
    $determinerNameAttempt = $this->db
      ->select('id', 'text_value')
      ->from('occurrence_attribute_values')
      ->where(array(
        'occurrence_attribute_id' => $determinerNameAttrId,
        'occurrence_id' => $occurrenceId
      ))
      ->get()->as_array();
    if (!empty($determinerNameAttempt)) {
      $determinerNameAttrValId = $determinerNameAttempt[0]->id;
      $determinerName = $determinerNameAttempt[0]->text_value;
    }
    // If we have successfully retrieved a determiner name that we can log into the determinations table
    // then we can overwrite the determiner attribute with the current user (who is changing the occurrence
    // species so becomes the new determiner).
    if (!empty($determinerName)) {
      switch ($detNameType) {
        case 'det_full_name':
          $theNameToAdd = $currentUserNames->surname . ', ' . $currentUserNames->first_name;
          break;

        case 'det_last_name':
          $theNameToAdd = $currentUserNames->surname;
          break;

        case 'det_first_name':
          $theNameToAdd = $currentUserNames->first_name;
          break;

        default:
          $theNameToAdd = 'Unknown';
      }

      $nameToAddRow = array('text_value' => $theNameToAdd);
      $this->set_metadata_for_row_array($nameToAddRow, 'occurrence_attribute_values');
      $update = $this->db
        ->from('occurrence_attribute_values')
        ->set($nameToAddRow)
        ->where(array('id' => $determinerNameAttrValId))
        ->update();
      return $determinerName;
    }
  }

  /*
   * When logging species into the determinations table, this method uses various rules to
   * work out what the determinations.person_name field will be.
   * It also calls get_and_update_occ_attr_determiner to update the occurrence attribute
   * determiner to the current user (who is changing the occurrence species).
   */
  public function get_person_name_and_update_determiner($oldValues, $currentUserId) {
    // We can only set the person name from an determiner system function occurrence attribute
    // if we have easy login enable ($currentUserId !== 1) because after we have logged the determination
    // we need to update the determiner for the original occurrence to the current user as the current
    // user has changed the occurrence.
    if ($currentUserId !== 1) {
      $currentUserNames = $this->get_user_firstname_and_surname($currentUserId);
      // Find the occurrence attributes that have a determiner name system function set.
      $occurrenceAttributesWithDetFuncs = $this->db
        ->select('id', 'system_function')
        ->from('occurrence_attributes')
        ->where(array('system_function' => 'det_first_name'))
        ->orwhere(array('system_function' => 'det_last_name'))
        ->orwhere(array('system_function' => 'det_full_name'))
        ->get()->as_array();

      // Go through all the occurrence attributes with determiner name system functions
      // and split them up into a first name, a surname name and a full name function.
      // As the system can actually have more than one occurrence_attribute with each
      // determiner name type system function, then we just take the first one we come accross.
      // In practice there will probably be only one.
      foreach ($occurrenceAttributesWithDetFuncs as $occurrenceAttrRow) {
        if ($occurrenceAttrRow->system_function === 'det_full_name' && empty($determinerFullNameAttributeId)) {
          $determinerFullNameAttributeId = $occurrenceAttrRow->id;
        }
        if ($occurrenceAttrRow->system_function === 'det_last_name' && empty($determinerLastNameAttributeId)) {
          $determinerLastNameAttributeId = $occurrenceAttrRow->id;
        }

        if ($occurrenceAttrRow->system_function === 'det_first_name' && empty($determinerFirstNameAttributeId)) {
          $determinerFirstNameAttributeId = $occurrenceAttrRow->id;
        }
      }
      // There are several rules we can use to collect the name for the person_name field in the determination.
      // The first rule is to see if we can collected it from the occurrence attribute with the determiner full name
      // system function.
      $occurrenceId = $oldValues['id'];
      if (!empty($determinerFullNameAttributeId)) {
        // Try and get the name from the occurrence attribute with the full name system function first.
        $determinerName = $this->get_and_update_occ_attr_determiner($determinerFullNameAttributeId, $occurrenceId, 'det_full_name', $currentUserNames);
      }
      // if we can't set the person_name from the fullname occurrence attribute, see if we can do it with the last name
      if (!empty($determinerLastNameAttributeId) && empty($determinerName)) {
        $determinerName = $this->get_and_update_occ_attr_determiner($determinerLastNameAttributeId, $occurrenceId, 'det_last_name', $currentUserNames);
        // If we have managed to find a surname then we can attempt to find their first name
        if (!empty($determinerName)) {
          $determinerFirstName = $this->get_and_update_occ_attr_determiner($determinerFirstNameAttributeId, $occurrenceId, 'det_first_name', $currentUserNames);
          // If we have found a first name then add it to the variable we are going to put in
          // determinations.person_name.
          if (!empty($determinerFirstName))
            $determinerName = $determinerName . ', ' . $determinerFirstName;
        }
      }
    }
    // if we still haven't found a person name, apply further rules to find one.
    if (empty($determinerName)) {
      // If there aren't currently any logged determinations for the occurrence we want to
      // attempt to get a person_name from recorders in cache_occurrences.
      $determinationsForOccurrence = $this->db
        ->select('id')
        ->from('determinations')
        ->where(array('occurrence_id' => $oldValues['id']))
        ->get()->as_array();
      if (empty($determinationsForOccurrence)) {
        $determinerNameArray = $this->db
          ->select('recorders')
          ->from('cache_occurrences')
          ->where(array('id' => $oldValues['id']))
          ->get()->as_array();
        if (!empty($determinerNameArray[0]->recorders))
          $determinerName = $determinerNameArray[0]->recorders;
      }
    }
    // If we still haven't got a person name, try to get the previous updater's name.
    if (empty($determinerName)) {
      if ($oldValues['updated_by_id'] !== 1) {
        $determinerNames = $this->get_user_firstname_and_surname($oldValues['updated_by_id']);
        $determinerName = $determinerNames->surname . ', ' . $determinerNames->first_name;
      }
    }
    // If after working through all the rules we still haven't found a person name, the set to 'Unknown'.
    if (empty($determinerName)) {
      $determinerName = 'Unknown';
    }
    return $determinerName;
  }

  /*
   * Return a single row array which contains an object with surname and first_name properties.
   * It is more flexible to have the first and surnames seperately like this as we can use them seperately
   * or format them as surname, first name etc
   */
  public function get_user_firstname_and_surname($userId) {
    $updatedByPersonId = $this->db
      ->select('person_id')
      ->from('users')
      ->where(array('id' => $userId))
      ->get()->as_array();
    $determinerNameArray = $this->db
      ->select('first_name', 'surname')
      ->from('people')
      ->where(array('id' => $updatedByPersonId[0]->person_id))
      ->get()->as_array();
    return $determinerNameArray[0];
  }

  // Override preSubmit to add in the verifier (verified_by_id) and verification date (verified_on) if the
  // occurrence is being set to status=V(erified) or R(ejected).
  protected function preSubmit() {
    // If determination logging is on and the occurrence species has changed ($logDetermination is true), we can
    // set the determiner_id on the occurrence to the current user providing easy login is on ($currentUserId!==1).
    if ($this->logDetermination) {
      $currentUserId = $this->get_current_user_id();
      if ($currentUserId !==1 )
        $this->submission['fields']['determiner_id']['value'] = $currentUserId;
    }
    parent::preSubmit();
  }

  /**
   * If this occurrence record status was reset after an edit, then log a comment.
   */
  public function postSubmit($isInsert) {
    if ($this->requeuedForVerification && !$isInsert) {
      $data = array(
        'occurrence_id' => $this->id,
        'comment' => kohana::lang('misc.recheck_verification'),
        'auto_generated' => 't'
      );
      $comment = ORM::factory('occurrence_comment');
      $comment->validate(new Validation($data), TRUE);
    }
    return TRUE;
  }

  /**
   * Defines a submission structure for occurrences that lets samples be submitted at the same time, e.g. during CSV upload.
   */
  public function get_submission_structure() {
    return array(
        'model' => $this->object_name,
        'superModels' => array(
          'sample' => array('fk' => 'sample_id')
        )
    );
  }

  /**
   * Returns details of attributes for this model.
   */
  public function get_attr_details() {
    return array('attrs_field_prefix' => $this->attrs_field_prefix);
  }

  /*
   * Determines if the provided module has been activated in the indicia configuration.
   */
  private function _check_module_active($module) {
    $config = kohana::config_load('core');
    foreach ($config['modules'] as $path) {
      if (strlen($path) >= strlen($module) &&
          substr_compare($path, $module, strlen($path) - strlen($module), strlen($module), TRUE) === 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Define a form that is used to capture a set of predetermined values that apply to every record during an import.
   *
   * @param array $options
   *   Model specific options, including
   *   * **occurrence_associations** - Set to 't' to enable occurrence associations options. The
   *     relevant warehouse module must also be enabled.
   */
  public function fixedValuesForm($options = array()) {
    $srefs = array();
    $systems = spatial_ref::system_list();
    foreach ($systems as $code => $title) {
      $srefs[] = str_replace(array(',', ':'), array('&#44', '&#58'), $code) .
            ":" .
            str_replace(array(',', ':'), array('&#44', '&#58'), $title);
    }

    $sample_methods = array(":Defined in file");
    $parent_sample_methods = array(":No filter");
    $terms = $this->db->select('id, term')->from('list_termlists_terms')->where('termlist_external_key', 'indicia:sample_methods')->orderby('term', 'asc')->get()->result();
    foreach ($terms as $term) {
      $sample_method = str_replace(array(',', ':'), array('&#44', '&#58'), $term->id) .
        ":" .
        str_replace(array(',', ':'), array('&#44', '&#58'), $term->term);
      $sample_methods[] = $sample_method;
      $parent_sample_methods[] = $sample_method;
    }

    $locationTypes = array(":No filter");
    $terms = $this->db->select('id, term')->from('list_termlists_terms')->where('termlist_external_key', 'indicia:location_types')->orderby('term', 'asc')->get()->result();
    foreach ($terms as $term) {
      $locationTypes[] = str_replace(array(',', ':'), array('&#44', '&#58'), $term->id) .
        ":" .
        str_replace(array(',', ':'), array('&#44', '&#58'), $term->term);
    }
    $retVal = array(
      'website_id' => array(
        'display' => 'Website',
        'description' => 'Select the website to import records into.',
        'datatype' => 'lookup',
        'population_call' => 'direct:website:id:title' ,
        'filterIncludesNulls' => TRUE,
        'validation' => ['required'],
      ),
      'survey_id' => array(
        'display' => 'Survey dataset',
        'description' => 'Select the survey dataset to import records into.',
        'datatype' => 'lookup',
        'population_call' => 'direct:survey:id:title',
        'linked_to' => 'website_id',
        'linked_filter_field' => 'website_id',
        'validation' => ['required'],
      ),
      'sample:entered_sref_system' => array(
        'display' => 'Spatial ref. system',
        'description' => 'Select the spatial reference system used in this import file. Note, if you have a file with a mix of spatial reference systems then you need a ' .
            'column in the import file which is mapped to the Sample Spatial Reference System field containing the spatial reference system code.',
        'datatype' => 'lookup',
        'lookup_values' => implode(',', $srefs)
      ),
      // Also allow a field to be defined which defines the taxon list to look in when searching for species during a
      // csv upload.
      'occurrence:fkFilter:taxa_taxon_list:taxon_list_id' => array(
        'display' => 'Species list',
        'description' => 'Select the species checklist which will be used when attempting to match species names.',
        'datatype' => 'lookup',
        'population_call' => 'direct:taxon_list:id:title',
        'linked_to' => 'website_id',
        'linked_filter_field' => 'website_id',
        'filterIncludesNulls' => TRUE
      ),
      'occurrence:record_status' => array(
        'display' => 'Record status',
        'description' => 'Select the initial status for imported species records',
        'datatype' => 'lookup',
        'lookup_values' => 'C:Unconfirmed - not reviewed,V:Accepted,I:Data entry still in progress',
        'default' => 'C'
      )
    );
    if (!empty($options['activate_global_sample_method']) && ($options['activate_global_sample_method'] === 't' || $options['activate_global_sample_method'] === true)) {
      $retVal['sample:sample_method_id'] = array(
        'display' => 'Sample Method',
        'description' => 'Select the sample method used for records in this import file. Note, if you have a file with a mix of sample methods then you need a ' .
        'column in the import file which is mapped to the Sample Sample Method field, containing the sample method.',
        'datatype' => 'lookup',
        'lookup_values' => implode(',', $sample_methods)
      );
    }
    if (!empty($options['activate_parent_sample_method_filter']) && ($options['activate_parent_sample_method_filter']==='t' || $options['activate_parent_sample_method_filter']=== true)) {
      $retVal['fkFilter:sample:sample_method_id'] = array(
        'display' => 'Parent Sample Method',
        'description' => 'If this import file includes samples which reference parent sample records, you can restrict the type of samples looked ' .
        'up by setting this sample method type. It is not currently possible to use a column in the file to do this on a sample by sample basis.',
        'datatype' => 'lookup',
        'lookup_values' => implode(',', $parent_sample_methods)
      );
    }
    if (!empty($options['activate_location_location_type_filter']) && $options['activate_location_location_type_filter']==='t') {
      $retVal['fkFilter:location:location_type_id'] = array(
        'display' => 'Location Type',
        'description' => 'If this import file includes samples which reference locations records, you can restrict the type of locations looked ' .
        'up by setting this location type. It is not currently possible to use a column in the file to do this on a sample by sample basis.',
        'datatype' => 'lookup',
        'lookup_values' => implode(',', $locationTypes)
      );
    }

    if (!empty($options['occurrence_associations']) && ($options['occurrence_associations'] === 't' || $options['occurrence_associations'] === TRUE) &&
        self::_check_module_active('occurrence_associations')) {
      $retVal['useAssociations'] = array(
        'display' => 'Use associations',
        'description' => 'Select if this import uses occurrence associations: implies two species records uploaded for each entry in the file.',
        'datatype' => 'checkbox'
      ); // default off
      $retVal['occurrence_association:fkFilter:association_type:termlist_id'] = array(
        'display' => 'Term list for association types',
        'description' => 'Select the term list which will be used to match the association types.',
        'datatype' => 'lookup',
        'population_call' => 'direct:termlist:id:title'
        // ,'linked_to' => 'website_id',
        // 'linked_filter_field' => 'website_id',
        // 'filterIncludesNulls' => TRUE
      );
      $retVal['occurrence_2:fkFilter:taxa_taxon_list:taxon_list_id'] = array(
        'display' => 'Second species list',
        'description' => 'Select the species checklist which will be used when attempting to match second species names.',
        'datatype' => 'lookup',
        'population_call' => 'direct:taxon_list:id:title',
        'linked_to' => 'website_id',
        'linked_filter_field' => 'website_id',
        'filterIncludesNulls' => TRUE
      );
      $retVal['occurrence_2:record_status'] = array(
        'display' => 'Record status',
        'description' => 'Select the initial status for second imported species records',
        'datatype' => 'lookup',
        'lookup_values' => 'C:Data entry complete/unverified,V:Verified,I:Data entry still in progress',
        'default' => 'C'
      );
    }
    return $retVal;
  }

}
