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
 *
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the Samples table.
 */
class Sample_Model extends ORM_Tree {
  protected $requeuedForVerification = FALSE;

  public $search_field = 'id';

  protected $ORM_Tree_children = 'samples';

  protected $has_many = [
    'occurrences',
    'sample_attribute_values',
    'sample_media',
  ];

  protected $belongs_to = [
    'survey',
    'location',
    'licence',
    'created_by' => 'user',
    'updated_by' => 'user',
    'sample_method' => 'termlists_term',
  ];

  // Declare that this model has child attributes, and the name of the node in
   // the submission which contains them.
  protected $has_attributes = TRUE;
  protected $attrs_submission_name = 'smpAttributes';
  public $attrs_field_prefix = 'smpAttr';

  /**
   * Declare additional fields required when posting via CSV.
   */
  protected $additional_csv_fields=array(
    'survey_id' => 'Survey ID',
    'website_id' => 'Website ID',
    // Extra lookup options.
    'sample:fk_location:id' => 'Location Indicia ID',
    'sample:fk_location:code' => 'Location Code',
    'sample:fk_location:external_key' => 'Location external key',
    'sample:fk_parent:external_key' => 'Parent sample external key',
    'sample:date:day' => 'Day (Builds date)',
    'sample:date:month' => 'Month (Builds date)',
    'sample:date:year' => 'Year (Builds date)',
    'sample:fk_licence:code' => 'Licence code',
  );

  // Define underlying fields which the user would not normally see, e.g. so
  // they can be hidden from selection during a csv import.
  protected $hidden_fields = [
    'geom',
  ];

  /**
   * Custom processing for special import fields.
   *
   * During an import it is possible to merge different columns in a CSV row to
   * make a database field.
   */
  public $compoundImportFieldProcessingDefn = [
    'sample day + month + year' => [
      'template' => '%04d-%02d-%02d',
      'columns' => ['sample:date:year', 'sample:date:month', 'sample:date:day'],
      'destination' => 'sample:date',
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
      'description' => 'Sample ID',
      'fields' => [
        ['fieldName' => 'survey_id', 'notInMappings' => TRUE],
        ['fieldName' => 'sample:id'],
      ],
    ],
    [
      'description' => 'Sample External Key',
      'fields' => [
        ['fieldName' => 'survey_id', 'notInMappings' => TRUE],
        ['fieldName' => 'sample:sample_method_id'],
        ['fieldName' => 'sample:external_key'],
      ],
    ],
    [
      'description' => 'Grid Ref and Date',
      'fields' => [
        ['fieldName' => 'survey_id', 'notInMappings' => TRUE],
        ['fieldName' => 'sample:sample_method_id'],
        ['fieldName' => 'sample:entered_sref'],
        ['fieldName' => 'sample:date'],
      ],
    ],
    [
      'description' => 'Location Record and Date',
      'fields' => [
        ['fieldName' => 'survey_id', 'notInMappings' => TRUE],
        ['fieldName' => 'sample:sample_method_id'],
        ['fieldName' => 'sample:location_id'],
        ['fieldName' => 'sample:date'],
      ],
    ],
  ];

  /**
   * Validate and save the data.
   *
   * @todo add a validation rule for valid date types.
   * @todo validate at least a location_name or sref required
   */
  public function validate(Validation $array, $save = FALSE) {
    $orig_values = $array->as_array();
    // Uses PHP trim() to remove whitespace from beginning and end of all
    // fields before validation.
    $array->pre_filter('trim');

    if ($this->id && preg_match('/[RDV]/', $this->record_status) &&
        (empty($this->submission['fields']['record_status']) || $this->submission['fields']['record_status']['value'] === 'C') &&
        $this->wantToUpdateMetadata) {
      // If we update a processed occurrence but don't set the verification
      // state, revert it to completed/awaiting verification.
      $array->verified_by_id = NULL;
      $array->verified_on = NULL;
      $array->record_status = 'C';
      $this->requeuedForVerification = TRUE;
    }

    // Any fields that don't have a validation rule need to be copied into the model manually
    $this->unvalidatedFields = [
      'date_end',
      'location_name',
      'survey_id',
      'deleted',
      'recorder_names',
      'parent_id',
      'comment',
      'sample_method_id',
      'input_form',
      'external_key',
      'group_id',
      'privacy_precision',
      'record_status',
      'import_guid',
      'verified_by_id',
      'verified_on',
      'licence_id',
      'training',
      'forced_spatial_indexer_location_ids',
    ];
    $array->add_rules('survey_id', 'required');
    // When deleting a sample, only need the id and the deleted flag, don't
    // need the date or location details, but copy over if they are there.
    if (array_key_exists('deleted', $orig_values) && $orig_values['deleted'] == 't') {
      $this->unvalidatedFields = array_merge($this->unvalidatedFields, [
        'date_type',
        'date_start',
        'date_end',
        'location_id',
        'entered_sref',
        'entered_sref_system',
        'geom',
      ]);
    }
    else {
      $array->add_rules('date_type', 'required', 'length[1,2]');
      $array->add_rules('date_start', 'date_in_past');
      // We need either at least one of the location_id and sref/geom : in some
      // cases may have both.
      // If a location is provided, we don't need an sref.
      if (empty($orig_values['location_id'])) {
        // Without a location_id, default to requires an sref. No need to copy
        // over location_id, as not present.
        $array->add_rules('entered_sref', "required");
        $array->add_rules('entered_sref_system', 'required');
        $array->add_rules('geom', 'required');
        // Even though our location_id is empty, still mark it as unvalidated
        // so it gets copied over.
        $this->unvalidatedFields[] = 'location_id';
        if (array_key_exists('entered_sref_system', $orig_values) && $orig_values['entered_sref_system'] !== '') {
          $system = $orig_values['entered_sref_system'];
          $array->add_rules('entered_sref', "sref[$system]");
          $array->add_rules('entered_sref_system', 'sref_system');
        }
      }
      else {
        // Got a location_id so may as well require it to make sure it gets
        // copied across.
        $array->add_rules('location_id', 'required');
        // If any of the sref fields are also supplied, need all 3 fields.
        if (!empty($orig_values['entered_sref']) || !empty($orig_values['entered_sref_system']) || !empty($orig_values['geom'])) {
          $this->add_sref_rules($array, 'entered_sref', 'entered_sref_system');
        }
        else {
          // We are not requiring  the fields so they must go in unvalidated
          // fields, allowing them to get blanked out on edit.
          $this->unvalidatedFields[] = 'entered_sref';
          $this->unvalidatedFields[] = 'entered_sref_system';
        }
        $this->unvalidatedFields[] = 'geom';
      }
    }

    // The trim pre_filter will convert a null date_start to an empty string
    // which causes an SQL exception when we attempt to insert it. Null is a
    // valid value for date_types of -Y. -C and U.
    $untrim = function ($val) {
      return $val == '' ? NULL : $val;
    };
    $array->post_filter($untrim, 'date_start');
    $this->clearForcedLinkedLocationIfMoving($array);

    return parent::validate($array, $save);
  }

  /**
   * Pre submission tasks.
   *
   * Before submission:
   * * Map vague date strings to their underlying database fields.
   * * Fill in the geom field using the supplied spatial reference, if not
   *   already filled in.
   * * Fill in the licence for the sample, if user has one, and not already
   *   filled in.
   */
  protected function preSubmit() {
    if (class_exists('cache_builder') && isset($this->submission['subModels']) && count($this->submission['subModels']) >= 100) {
      // If processing a large sample/subsample submission, then delay cache
      // table updates to improve performance.
      cache_builder::$delayCacheUpdates = TRUE;
    }
    $this->preSubmitFillInVagueDate();
    $this->preSubmitInheritFromParent();
    $this->preSubmitFillInGeom();
    $this->preSubmitFillInLicence();
    $this->preSubmitTidySref();
    return parent::presubmit();
  }

  /**
   * If a child sample, may need to inherit some properties.
   *
   * E.g. can submit a child sample without a survey ID and collect from the
   * parent.
   */
  protected function preSubmitInheritFromParent() {
    if (!empty($this->submission['fields']['parent_id']) && !empty($this->submission['fields']['parent_id']['value'])) {
      $data = [];
      foreach ($this->submission['fields'] as $key => $value) {
        if (isset($value['value'])) {
          $data[$key] = $value['value'];
        }
      }
      $parent = ORM::factory('sample', $data['parent_id']);
      $fieldsToCopyDown = [
        'survey_id',
        'entered_sref',
        'entered_sref_system',
        'licence_id',
        'privacy_precision',
        'group_id',
      ];
      foreach ($fieldsToCopyDown as $field) {
        if (empty($data[$field])) {
          $this->submission['fields'][$field] = ['value' => $parent->$field];
        }
      }
      if (empty($data['date_type']) && empty($data['date'])) {
        $this->submission['fields']['date_start'] = ['value' => $parent->date_start];
        $this->submission['fields']['date_end'] = ['value' => $parent->date_end];
        $this->submission['fields']['date_type'] = ['value' => $parent->date_type];
      }
    }
  }

  /**
   * Populate and check vague date related fields.
   *
   * If a date is supplied in a submission as a string, fill in the underlying
   * database vague date fields. If the date is supplied in date_start,
   * date_end and date_type format then throw an exception if the format is
   * wrong.
   */
  private function preSubmitFillInVagueDate() {
    if (array_key_exists('date', $this->submission['fields'])) {
      $dateString = $this->submission['fields']['date']['value'];
    }
    elseif (array_key_exists('date_type', $this->submission['fields'])) {
      // Force an exception if a bad date structure provided.
      try {
        $dateString = vague_date::vague_date_to_string([
          $this->submission['fields']['date_start']['value'],
          $this->submission['fields']['date_end']['value'],
          $this->submission['fields']['date_type']['value'],
        ]);
      }
      catch (InvalidVagueDateException $e) {
        $this->errors['date_type'] = $e->getMessage();
        return;
      }
    }
    if (isset($dateString)) {
      $vagueDate = vague_date::string_to_vague_date($dateString);
      if ($vagueDate !== FALSE) {
        $this->submission['fields']['date_start']['value'] = $vagueDate[0];
        $this->submission['fields']['date_end']['value'] = $vagueDate[1];
        $this->submission['fields']['date_type']['value'] = $vagueDate[2];
      }
      else {
        $this->errors['date_type'] = 'The date could not be recognised.';
      }
    }
  }

  /**
   * Fill in geometry before submission.
   *
   * Allow a sample to be submitted with a spatial ref and system but no Geom.
   * If so we can work out the geom and fill it in.
   */
  private function preSubmitFillInGeom() {
    if (array_key_exists('entered_sref', $this->submission['fields']) &&
      array_key_exists('entered_sref_system', $this->submission['fields']) &&
      !(array_key_exists('geom', $this->submission['fields']) && $this->submission['fields']['geom']['value']) &&
      $this->submission['fields']['entered_sref']['value'] &&
      $this->submission['fields']['entered_sref_system']['value']) {
      try {
        $this->submission['fields']['geom']['value'] = spatial_ref::sref_to_internal_wkt(
          $this->submission['fields']['entered_sref']['value'],
          $this->submission['fields']['entered_sref_system']['value']
        );
      }
      catch (Exception $e) {
        $this->errors['entered_sref'] = $e->getMessage();
      }
    }
  }

  /**
   * Fill in the user's preferred licence ID.
   *
   * If a submission is for an insert and does not contain the licence ID for
   * the data it contains, look it up from the user's settings and apply it to
   * the submission.
   */
  private function preSubmitFillInLicence() {
    if (!(array_key_exists('id', $this->submission['fields']) || array_key_exists('licence_id', $this->submission['fields']))) {
      $userId = $this->getUserId();
      $row = $this->db
        ->select('licence_id')
        ->from('users_websites')
        ->where([
          'user_id' => $userId,
          'website_id' => $this->identifiers['website_id'],
        ])
        ->get()->current();
      if ($row) {
        $this->submission['fields']['licence_id']['value'] = $row->licence_id;
      }
    }
  }

  /**
   * Tidy spatial references before submission.
   *
   * Gives sref modules the chance to tidy the format of input values, e.g.
   * OSGB grid refs are capitalised and spaces stripped.
   */
  private function preSubmitTidySref() {
    if (array_key_exists('entered_sref', $this->submission['fields']) &&
        !empty($this->submission['fields']['entered_sref']['value']) &&
        array_key_exists('entered_sref_system', $this->submission['fields'])) {
      $this->submission['fields']['entered_sref']['value'] = spatial_ref::sref_format_tidy(
          $this->submission['fields']['entered_sref']['value'],
          $this->submission['fields']['entered_sref_system']['value']
      );
    }
  }

  /**
   * If moving a sample, clear forced linked location IDs.
   *
   * If a sample is being moved to a new geometry, the forced linked location
   * IDs previously set should be cleared.
   *
   * @param Validation $array
   *   Data being submitted.
   */
  private function clearForcedLinkedLocationIfMoving(Validation $array) {
    if (
        // Existing record being updated which has
        // forced_spatial_indexer_location_ids set.
        !empty($this->id) && !empty($this->forced_spatial_indexer_location_ids)
        // Forced_spatial_indexer_location_ids is not specified in the submission.
        && !empty($array['forced_spatial_indexer_location_ids'])
        // Geometry is in the submission.
        && isset($array['geom'])
        // Geometry is changing.
        && $array['geom'] !== $this->geom
      ) {
      // Clearing forced_spatial_indexer_location_ids.
      $array['forced_spatial_indexer_location_ids'] = NULL;
    }
  }

  /**
   * Override set handler to translate WKT to PostGIS internal spatial data.
   */
  public function __set($key, $value) {
    if (substr($key, -4) == 'geom') {
      if ($value) {
        $row = $this->db->query("SELECT ST_MakeValid(ST_GeomFromText(?, ?)) AS geom", [$value, kohana::config('sref_notations.internal_srid')])->current();
        $value = $row->geom;
      }
    }
    parent::__set($key, $value);
  }

  /**
   * Override get handler to translate PostGIS internal spatial data to WKT.
   */
  public function __get($column) {
    $value = parent::__get($column);

    if (substr($column, -4) == 'geom' && $value !== NULL) {
      $row = $this->db->query('SELECT ST_asText(?) AS wkt', [$value])->current();
      $value = $row->wkt;
    }
    return $value;
  }

  /**
   * Return a displayable caption for the item.
   *
   * For samples this is a combination of the date and spatial reference.
   */
  public function caption() {
    return ('Sample on ' . $this->date . ' at ' . $this->entered_sref);
  }

  /**
   * Fixed values form for sample import.
   *
   * Define a form that is used to capture a set of predetermined values that
   * apply to every record during an import.
   */
  public function fixedValuesForm($options = []) {
    $srefs = [];
    $systems = spatial_ref::system_list();
    foreach ($systems as $code => $title) {
      $srefs[] = str_replace([',', ':'], ['&#44', '&#56'], $code) .
          ":" .
          str_replace([',', ':'], ['&#44', '&#56'], $title);
    }
    $sample_methods = [];
    $parent_sample_methods = [":No filter"];
    $terms = $this->db->select('id, term')->from('list_termlists_terms')->where('termlist_external_key', 'indicia:sample_methods')->orderby('term', 'asc')->get()->result();
    foreach ($terms as $term) {
      $sample_method = str_replace([',', ':'], ['&#44', '&#56'], $term->id) .
          ":" .
          str_replace([',', ':'], ['&#44', '&#56'], $term->term);
      $sample_methods[] = $sample_method;
      $parent_sample_methods[] = $sample_method;
    }

    $location_types = [":No filter"];
    $terms = $this->db->select('id, term')->from('list_termlists_terms')->where('termlist_external_key', 'indicia:location_types')->orderby('term', 'asc')->get()->result();
    foreach ($terms as $term) {
      $location_types[] = str_replace([',', ':'], ['&#44', '&#56'], $term->id) .
          ":" .
          str_replace([',', ':'], ['&#44', '&#56'], $term->term);
    }
    $retval = [
      'website_id' => [
        'display' => 'Website',
        'description' => 'Select the website to import records into.',
        'datatype' => 'lookup',
        'population_call' => 'direct:website:id:title',
      ],
      'survey_id' => [
        'display' => 'Survey dataset',
        'description' => 'Select the survey dataset to import records into.',
        'datatype' => 'lookup',
        'population_call' => 'direct:survey:id:title',
        'linked_to' => 'website_id',
        'linked_filter_field' => 'website_id',
      ],
      'sample:entered_sref_system' => [
        'display' => 'Spatial Ref. System',
        'description' => 'Select the spatial reference system used in this import file. Note, if you have an import ' .
          'file with a mix of spatial reference systems then you need to include a column in the file that shows ' .
          'the spatial reference system code, so that his can be mapped to the Sample Spatial Reference System ' .
          'field on the next page.',
        'datatype' => 'lookup',
        'lookup_values' => implode(',', $srefs),
      ],
      'sample:sample_method_id' => [
        'display' => 'Sample Method',
        'description' => 'Select the sample method used for records in this import file. Note, if you have a file with a mix of sample methods then you need a ' .
            'column in the import file which is mapped to the Sample Sample Method field, containing the sample method.',
        'datatype' => 'lookup',
        'lookup_values' => implode(',', $sample_methods),
      ],
      'fkFilter:location:location_type_id' => [
        'display' => 'Location Type',
        'description' => 'If this import file includes samples which reference locations records, you can restrict the type of locations looked ' .
            'up by setting this location type. It is not currently possible to use a column in the file to do this on a sample by sample basis.',
        'datatype' => 'lookup',
        'lookup_values' => implode(',', $location_types),
      ],
    ];
    if (!empty($options['activate_parent_sample_method_filter']) && $options['activate_parent_sample_method_filter'] === 't') {
      // Uses format :table:.
      $retval['fkFilter:sample:sample_method_id'] = [
        'display' => 'Parent Sample Method',
        'description' => 'If this import file includes samples which reference parent sample records, you can restrict the type of samples looked ' .
        'up by setting this sample method type. It is not currently possible to use a column in the file to do this on a sample by sample basis.',
        'datatype' => 'lookup',
        'lookup_values' => implode(',', $parent_sample_methods),
      ];
    }
    return $retval;
  }

  /**
   * Handle post submission tasks.
   *
   * Post submit, use the sample's group.private_records to set the occurrence
   * release status. Also, if this was a verified sample but it has been
   * modified, add a comment to explain why its been requeued for verification.
   */
  public function postSubmit($isInsert) {
    if ($this->group_id) {
      $group = $this->db->select('id')
        ->from('groups')
        ->where([
          'id' => $this->group_id,
          'private_records' => 't',
          'deleted' => 'f',
        ])->get()->result_array();
      if (count($group)) {
        // This sample is associated with a group that does not release its
        // records. So ensure the release_status flag is set.
        $this->db->update('occurrences', ['release_status' => 'U'], ['sample_id' => $this->id, 'release_status' => 'R']);
        $this->db->update('cache_occurrences_functional', ['release_status' => 'U'], ['sample_id' => $this->id, 'release_status' => 'R']);
      }
    }
    if ($this->requeuedForVerification && !$isInsert) {
      $data = [
        'sample_id' => $this->id,
        'comment' => kohana::lang('misc.recheck_verification'),
        'auto_generated' => 't'
      ];
      $comment = ORM::factory('sample_comment');
      $comment->validate(new Validation($data), TRUE);
    }
    return TRUE;
  }

}
