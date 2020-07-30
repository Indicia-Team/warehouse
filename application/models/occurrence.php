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
      if (kohana::config('indicia.auto_log_determinations') === TRUE && $this->all_info_in_determinations !== 'Y') {
        $determination = [
          // We log the old taxon.
          'taxa_taxon_list_id' => $this->taxa_taxon_list_id,
          'determination_type' => 'B',
          'occurrence_id' => $this->id,
          // Last change to the occurrence is really the create metadata for this
          // determination, since we are copying it out of the existing
          // occurrence record.
          'created_by_id' => $this->updated_by_id,
          'updated_by_id' => $this->updated_by_id,
          'created_on' => $this->getWhenRecordLastDetermined(),
          'updated_on' => date("Ymd H:i:s"),
          'person_name' => $this->getPreviousDeterminerName(),
        ];
        $insert = $this->db
          ->from('determinations')
          ->set($determination)
          ->insert();
      }
      if (!empty($this->submission['fields']['determiner_id']) && !empty($this->submission['fields']['determiner_id']['value'])) {
        // Redetermination by user ID provided in submission.
        $redetByUserId = (int) $this->submission['fields']['determiner_id']['value'];
      } else {
        // Redetermination doesn't specify user ID, so use logged in user account.
        $redetByUserId = $this->get_current_user_id();
        if ($redetByUserId !== 1) {
          // Store in the occurrences.determiner_id field.
          $array->determiner_id = $redetByUserId;
        }
      }
      // Update any determiner occurrence attributes.
      if ($redetByUserId !== 1) {
        $sql = <<<SQL
UPDATE occurrence_attribute_values v
SET text_value=CASE a.system_function
  WHEN 'det_full_name' THEN TRIM(COALESCE(p.first_name || ' ', '') || p.surname)
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
AND u.id=$redetByUserId
AND u.deleted=false
SQL;
        $this->db->query($sql);
      }
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

  /**
   * Calculates the value to populate in determinations.person_name.
   *
   * @return string
   *   Name to store for previous determination.
   */
  private function getPreviousDeterminerName() {
    $oldValues = $this->as_array();
    $determinerName = '';
    // Work through a list of possible places to find a determiner name in
    // priority order.
    // Occurrences.determiner_id
    if (!empty($oldValues['determiner_id'])) {
      return getPersonNameFromUserId($oldValues['determiner_id']);
    }
    // Attribute values det_*_name
    $attrValues =  $this->db
      ->select('v.text_value', 'a.system_function')
      ->from('occurrence_attribute_values as v')
      ->join('occurrence_attributes as a', 'a.id', 'v.occurrence_attribute_id')
      ->where([
        'v.occurrence_id' => $oldValues['id']
      ])
      ->like('a.system_function', 'det_%')
      ->get();
    $detData = [];
    foreach ($attrValues as $attrValue) {
      $detData[$attrValue['system_function']] = $attrValue['text_value'];
    }
    if (!empty($detData['det_full_name'])) {
      return $detData['det_full_name'];
    }
    if (!empty($detData['det_surname']) && !empty($detData['det_first_name'])) {
      return $detData['det_surname'] . ', ' . $detData['det_first_name'];
    }
    if (!empty($detData['det_surname'])) {
      return $detData['det_surname'];
    }
    // Cached recorders - this gets the value from multiple possible sources so
    // simplifies the code required here.
    $recorders = $this->db
      ->select('recorders')
      ->from('cache_samples_nonfunctional')
      ->where('id', $oldValues['sample_id'])
      ->get()
      ->current();
    if (!empty($recorders) && !empty($recorders->recorders)) {
      return $recorders->recorders;
    }
    // If after working through all the rules we still haven't found a person name, the set to 'Unknown'.
    if (empty($determinerName)) {
      $determinerName = 'Unknown';
    }
    return $determinerName;
  }

  private function getPersonNameFromUserId($userId) {
    $p = $this->db
      ->select('p.first_name', 'p.surname')
      ->from('people as p')
      ->join('users as u', 'u.person_id', 'p.id')
      ->where([
        'u.id', $userId
      ])
      ->get()->current();
    return $p->surname . (empty($p->first_name) ? '' : ', ' . p.first_name);
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
