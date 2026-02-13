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
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the Occurrences table.
 */
class Occurrence_Model extends ORM {

  protected $requeuedForVerification = FALSE;

  protected $has_many = [
    'determinations',
    // Has many structure, but unique index prevents more than 1.
    'dna_occurrences',
    'occurrence_attribute_values',
    'occurrence_media',
  ];

  protected $belongs_to = [
    'determiner' => 'person',
    'sample',
    'taxa_taxon_list',
    'classification_event',
    'created_by' => 'user',
    'updated_by' => 'user',
    'verified_by' => 'user',
  ];

  // Declare that this model has child attributes, and the name of the node in
  // the submission which contains them.
  protected $has_attributes = TRUE;
  protected $attrs_submission_name = 'occAttributes';
  public $attrs_field_prefix = 'occAttr';
  protected $additional_csv_fields = [
    // Extra lookup options.
    'occurrence:fk_taxa_taxon_list:genus' => 'Genus (builds binomial name)',
    'occurrence:fk_taxa_taxon_list:specific' => 'Specific name/epithet (builds binomial name)',
    'occurrence:fk_taxa_taxon_list:external_key' => 'Species or taxon external key',
    'occurrence:fk_taxa_taxon_list:search_code' => 'Species or taxon search code',
    // Needs to be more complex version so import recognises it as same field as above.
    'occurrence:fk_taxa_taxon_list:id' => 'Species or taxon database ID (taxa_taxon_lists.id)',
    // Allow details of 4 images to be uploaded in CSV files.
    'occurrence_medium:path:1' => 'Media Path 1',
    'occurrence_medium:caption:1' => 'Media Caption 1',
    'occurrence_medium:path:2' => 'Media Path 2',
    'occurrence_medium:caption:2' => 'Media Caption 2',
    'occurrence_medium:path:3' => 'Media Path 3',
    'occurrence_medium:caption:3' => 'Media Caption 3',
    'occurrence_medium:path:4' => 'Media Path 4',
    'occurrence_medium:caption:4' => 'Media Caption 4',
  ];

  /**
   * Fields that are not shown to the user as importable.
   *
   * @var array
   */
  protected $hidden_fields = [
    'dna_derived',
  ];

  // During an import it is possible to merge different columns in a CSV row
  // to make a database field.
  public $compoundImportFieldProcessingDefn = [
    'occurrence genus + species' => [
      'template' => '%s %s',
      'columns' => [
        'occurrence:fk_taxa_taxon_list:genus',
        'occurrence:fk_taxa_taxon_list:specific',
      ],
      'destination' => 'occurrence:fk_taxa_taxon_list',
    ],
    'sample day + month + year' => [
      'template' => '%04d-%02d-%02d',
      'columns' => ['sample:date:year', 'sample:date:month', 'sample:date:day'],
      'destination' => 'sample:date',
    ],
  ];

  /**
   * Indicates database trigger on table which accesses a sequence.
   *
   * Set to true as set_occurrence_to_training_from_sample_trigger was
   * added.
   *
   * @var bool
   */
  protected $hasTriggerWithSequence = TRUE;

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
   *
   * @return string
   *   Caption for instance.
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
      $identChanging = !empty($fields['taxa_taxon_list_id']) && (string) $fields['taxa_taxon_list_id']['value'] !== (string) $this->taxa_taxon_list_id;
      $isAlreadyReviewed = (!empty($this->record_status) && preg_match('/[RDV]/', $this->record_status)) || $this->record_substatus === 3;
      // Is this post going to change the record status or substatus?
      if ($newStatus !== $this->record_status || (string) $newSubstatus !== (string) $this->record_substatus) {
        if ($newStatus === 'V' || $newStatus === 'R') {
          // If verifying or rejecting, then set the verification metadata to
          // provided values, if present, else current values.
          $array->verified_by_id = empty($fields['verified_by_id']) ? $this->getUserId() : $fields['verified_by_id']['value'];
          $array->verified_on = empty($fields['verified_on']) ? date("Ymd H:i:s") : $fields['verified_on']['value'];
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
    $array->add_rules('classification_event_id', 'integer');
    $array->add_rules('machine_involvement', 'integer', 'minimum[0]', 'maximum[5]');
    $fieldlist = $array->as_array();
    if (!array_key_exists('all_info_in_determinations', $fieldlist) || $fieldlist['all_info_in_determinations'] == 'N') {
      $array->add_rules('taxa_taxon_list_id', 'required');
    }
    // Explicitly add those fields for which we don't do validation.
    $this->unvalidatedFields = [
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
      'verifier_only_data',
    ];
    if (array_key_exists('id', $fieldlist)) {
      // Existing data must not be set to download_flag=F (final download)
      // otherwise it is read only.
      $array->add_rules('downloaded_flag', 'chars[N,I]');
    }
    return parent::validate($array, $save);
  }

  /**
   * Before saving the record, check if the zero abundance flag should be set.
   */
  protected function preSubmit() {
    // If the zero abundance field not being submitted, check if there is an
    // attribute set up for auto-handling of this field value.
    if (empty($this->submission['fields']['zero_abundance']['value'] ?? NULL)) {
      $cacheId = 'survey-auto-zero-abundance-' . $this->identifiers['survey_id'];
      $cache = Cache::instance();
      $attrs = $cache->get($cacheId);
      if ($attrs === NULL) {
        $attrs = $this->db->select([
          'occurrence_attributes_websites.occurrence_attribute_id',
          'occurrence_attributes.data_type',
          'occurrence_attributes.termlist_id',
        ])
          ->from('occurrence_attributes_websites')
          ->join('occurrence_attributes', 'occurrence_attributes.id', 'occurrence_attributes_websites.occurrence_attribute_id')
          ->where([
            'occurrence_attributes_websites.restrict_to_survey_id' => (integer) $this->identifiers['survey_id'],
            'occurrence_attributes_websites.auto_handle_zero_abundance' => 't',
          ])
          ->get()->result_array(FALSE);
        $cache->set($cacheId, $attrs);
      }
      // Set zero_abundance flag if one of the abundance attributes found has a
      // 0 (or similar) value.
      foreach ($attrs as $attr) {
        if (isset($this->submission['fields']["occAttr:$attr[occurrence_attribute_id]"])) {
          $value = $this->submission['fields']["occAttr:$attr[occurrence_attribute_id]"]['value'];
          if (in_array(strtolower($value), [0, '0', 'absent', 'absence', 'none', 'not present', 'not detected', 'not found', 'zero'])) {
            $this->submission['fields']['zero_abundance'] = ['value' => 't'];
          }
        }
      }
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
      $logDeterminations = kohana::config('indicia.auto_log_determinations') === TRUE ? 'true' : 'false';
      $resetClassification = empty($this->submission['fields']['classification_event_id']) ? 'true' : 'false';
      $currentUserId = $this->getCurrentUserId();
      if (empty($this->submission['fields']['determiner_id']) || empty($this->submission['fields']['determiner_id']['value'])) {
        // Determiner ID not provided, so use the authorised user_id to work
        // it out.
        $userInfo = $this->db->select('person_id')->from('users')->where('id', $currentUserId)->get()->current();
        $determinerPersonId = $userInfo->person_id;
        if ((int) $determinerPersonId !== 1) {
          // Store in the occurrences.determiner_id field.
          $array->determiner_id = $determinerPersonId;
        }
        else {
          // Anonymous user redet is likely to be a record refresh from REST API Sync.
          // Remove determiner ID as anything previously set is not valid.
          $array->determiner_id = NULL;
        }
      }
      else {
        $determinerPersonId = $this->submission['fields']['determiner_id']['value'];
      }
      if ((int) $determinerPersonId === -1) {
        // Determiner person ID -1 is special case, means don't assign new
        // determiner name on redet.
        unset($array->determiner_id);
      }
      if (empty($this->submission['fields']['machine_involvement'])) {
        $array->machine_involvement = NULL;
      }
      if (empty($this->submission['fields']['classification_event_id'])) {
        $array->classification_event_id = NULL;
      }
      $sql = "SELECT f_handle_determination(ARRAY[?], ?, ?, ?, ?);";
      $this->db->query($sql, [$this->id, $currentUserId, $determinerPersonId, $logDeterminations, $resetClassification]);
      $array->last_verification_check_date = NULL;
    }
  }

  /**
   * Adds a create/update metadata to a row of data.
   *
   * @param array $row
   *   A row of data we are adding/updating to the database.
   * @param string $tableName
   *   The name of the table we are adding the row to. We need this as the
   *   attribute_websites tables don't have updated by and updated on fields.
   */
  public function set_metadata_for_row_array(&$row = NULL, $tableName = NULL) {
    $userId = $this->getUserId();
    $row['created_on'] = date("Ymd H:i:s");
    $row['created_by_id'] = $userId;
    // Attribute websites tables don't have updated by/date details columns so
    // we need a special case not to set them.
    if ($tableName !== 'sample_attributes_websites'&&$tableName !== 'occurrence_attributes_websites') {
      $row['updated_on'] = date("Ymd H:i:s");
      $row['updated_by_id'] = $userId;
    }
  }

  /**
   * If this record status was reset after an edit then log a comment.
   */
  public function postSubmit($isInsert) {
    if ($this->requeuedForVerification && !$isInsert) {
      $data = [
        'occurrence_id' => $this->id,
        'comment' => kohana::lang('misc.recheck_verification'),
        'auto_generated' => 't',
      ];
      $comment = ORM::factory('occurrence_comment');
      $comment->validate(new Validation($data), TRUE);
    }
    return TRUE;
  }

  /**
   * Defines a submission structure for occurrences.
   *
   * Lets samples be submitted at the same time, e.g. during CSV upload.
   *
   * @return array
   *   Submission structure.
   */
  public function get_submission_structure() {
    return [
      'model' => $this->object_name,
      'superModels' => [
        'sample' => ['fk' => 'sample_id'],
      ],
    ];
  }

  /**
   * Returns details of attributes for this model.
   */
  public function get_attr_details() {
    return ['attrs_field_prefix' => $this->attrs_field_prefix];
  }

  /**
   * Determines if the provided module has been activated in the configuration.
   */
  private function checkModuleActive($module) {
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
   * Fixed values form for import.
   *
   * Define a form that is used to capture a set of predetermined values that
   * apply to every record during an import.
   *
   * @param array $options
   *   Model specific options, including
   *   * **occurrence_associations** - Set to 't' to enable occurrence
   *     associations options. The relevant warehouse module must also be
   *     enabled.
   *
   * @return array
   *   List of control definitions.
   */
  public function fixedValuesForm(array $options = []) {
    $srefs = [];
    $systems = spatial_ref::system_list();
    foreach ($systems as $code => $title) {
      $srefs[] = str_replace([',', ':'], ['&#44', '&#58'], $code) .
            ":" .
            str_replace([',', ':'], ['&#44', '&#58'], $title);
    }

    $sample_methods = [];
    $parent_sample_methods = [":No filter"];
    $terms = $this->db->select('id, term')->from('list_termlists_terms')->where('termlist_external_key', 'indicia:sample_methods')->orderby('term', 'asc')->get()->result();
    foreach ($terms as $term) {
      $sample_method = str_replace([',', ':'], ['&#44', '&#58'], $term->id) .
        ":" .
        str_replace([',', ':'], ['&#44', '&#58'], $term->term);
      $sample_methods[] = $sample_method;
      $parent_sample_methods[] = $sample_method;
    }

    $locationTypes = [":No filter"];
    $terms = $this->db->select('id, term')->from('list_termlists_terms')->where('termlist_external_key', 'indicia:location_types')->orderby('term', 'asc')->get()->result();
    foreach ($terms as $term) {
      $locationTypes[] = str_replace([',', ':'], ['&#44', '&#58'], $term->id) .
        ":" .
        str_replace([',', ':'], ['&#44', '&#58'], $term->term);
    }
    $retVal = [
      'website_id' => [
        'display' => 'Website',
        'description' => 'Select the website to import records into.',
        'datatype' => 'lookup',
        'population_call' => 'direct:website:id:title' ,
        'filterIncludesNulls' => TRUE,
        'validation' => ['required'],
      ],
      'survey_id' => [
        'display' => 'Survey dataset',
        'description' => 'Select the survey dataset to import records into.',
        'datatype' => 'lookup',
        'population_call' => 'direct:survey:id:title',
        'linked_to' => 'website_id',
        'linked_filter_field' => 'website_id',
        'validation' => ['required'],
      ],
      'sample:entered_sref_system' => [
        'display' => 'Spatial ref. system',
        'description' => 'Select the spatial reference system used in this import file. Note, if you have an import ' .
          'file with a mix of spatial reference systems then you need to include a column in the file that shows ' .
          'the spatial reference system code, so that his can be mapped to the Sample Spatial Reference System ' .
          'field on the next page.',
        'datatype' => 'lookup',
        'lookup_values' => implode(',', $srefs),
      ],
      // Also allow a field to be defined which defines the taxon list to look
      // in when searching for species during a csv upload.
      'occurrence:fkFilter:taxa_taxon_list:taxon_list_id' => [
        'display' => 'Species list',
        'description' => 'Select the species checklist which will be used when attempting to match species names.',
        'datatype' => 'lookup',
        'population_call' => 'direct:taxon_list:id:title',
        'linked_to' => 'website_id',
        'linked_filter_field' => 'website_id',
        'filterIncludesNulls' => TRUE,
      ],
      'occurrence:record_status' => [
        'display' => 'Record status',
        'description' => 'Select the initial status for imported species records',
        'datatype' => 'lookup',
        'lookup_values' => 'C:Unconfirmed - not reviewed,V:Accepted,I:Data entry still in progress',
        'default' => 'C',
      ]
    ];
    if (!empty($options['activate_global_sample_method']) && ($options['activate_global_sample_method'] === 't' || $options['activate_global_sample_method'] === TRUE)) {
      $retVal['sample:sample_method_id'] = [
        'display' => 'Sample Method',
        'description' => 'Select the sample method used for records in this import file. Note, if you have a file with a mix of sample methods then you need a ' .
        'column in the import file which is mapped to the Sample Sample Method field, containing the sample method.',
        'datatype' => 'lookup',
        'lookup_values' => implode(',', $sample_methods),
      ];
    }
    if (!empty($options['activate_parent_sample_method_filter']) && ($options['activate_parent_sample_method_filter'] === 't' || $options['activate_parent_sample_method_filter'] === TRUE)) {
      $retVal['fkFilter:sample:sample_method_id'] = [
        'display' => 'Parent Sample Method',
        'description' => 'If this import file includes samples which reference parent sample records, you can restrict the type of samples looked ' .
        'up by setting this sample method type. It is not currently possible to use a column in the file to do this on a sample by sample basis.',
        'datatype' => 'lookup',
        'lookup_values' => implode(',', $parent_sample_methods),
      ];
    }
    if (!empty($options['activate_location_location_type_filter']) && $options['activate_location_location_type_filter'] === 't') {
      $retVal['fkFilter:location:location_type_id'] = [
        'display' => 'Location Type',
        'description' => 'If this import file includes samples which reference locations records, you can restrict the type of locations looked ' .
        'up by setting this location type. It is not currently possible to use a column in the file to do this on a sample by sample basis.',
        'datatype' => 'lookup',
        'lookup_values' => implode(',', $locationTypes),
      ];
    }

    if (!empty($options['occurrence_associations']) && ($options['occurrence_associations'] === 't' || $options['occurrence_associations'] === TRUE) &&
        $this->checkModuleActive('occurrence_associations')) {
      $retVal['useAssociations'] = [
        'display' => 'Use associations',
        'description' => 'Select if this import uses occurrence associations: implies two species records uploaded for each entry in the file.',
        'datatype' => 'checkbox',
      ]; // default off
      $retVal['occurrence_association:fkFilter:association_type:termlist_id'] = [
        'display' => 'Term list for association types',
        'description' => 'Select the term list which will be used to match the association types.',
        'datatype' => 'lookup',
        'population_call' => 'direct:termlist:id:title',
        // ,'linked_to' => 'website_id',
        // 'linked_filter_field' => 'website_id',
        // 'filterIncludesNulls' => TRUE
      ];
      $retVal['occurrence_2:fkFilter:taxa_taxon_list:taxon_list_id'] = [
        'display' => 'Associated occurrence species list',
        'description' => 'Select the species checklist which will be used when attempting to match associated occurrence species names.',
        'datatype' => 'lookup',
        'population_call' => 'direct:taxon_list:id:title',
        'linked_to' => 'website_id',
        'linked_filter_field' => 'website_id',
        'filterIncludesNulls' => TRUE,
      ];
      $retVal['occurrence_2:record_status'] = [
        'display' => 'Associated occurrence record status',
        'description' => 'Select the initial status for imported associated occurrences.',
        'datatype' => 'lookup',
        'lookup_values' => 'C:Data entry complete/unverified,V:Verified,I:Data entry still in progress',
        'default' => 'C',
      ];
    }
    return $retVal;
  }

}
