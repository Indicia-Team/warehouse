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

/**
 * Override of the Kohana core ORM class which provides Indicia specific functionality for submission of data.
 * ORM objects are normally instantiated by calling ORM::Factory(modelname[, id]). For Indicia ORM objects,
 * there is an option to pass -1 as the ID indicating that the ORM object should not be initialised. This
 * allows access to variables such as the lookup table and search field without full instantiation of the ORM
 * object, saving hits on the database etc.
 */
class ORM extends ORM_Core {

  /**
   * Authorised website ID from the service authentication.
   *
   * @var int
   */
  public static $authorisedWebsiteId = 0;

  /**
   * Should foreign key lookups be cached? Set to true during import for example.
   *
   * @var bool
   */
  public static $cacheFkLookups = FALSE;


  /**
   * Tracks list of all inserted, updated or deleted records in this transaction.
   *
   * @var array
   */
  public static $changedRecords;

  /**
   * Values before any changes applied.
   *
   * @var array
   */
  public $initialValues = [];

  public function last_query() {
    return $this->db->last_query();
  }

  public $submission = [];

  /**
   * Describes the list of nested models that are present after a submission.
   *
   * E.g. the list of occurrences in a sample.
   *
   * @var array
   */
  private $nestedChildModelIds = [];
  private $nestedParentModelIds = [];

  /**
   * Default search field name.
   *
   * @var string
   *   The default field that is searchable is called title. Override this when
   *    a different field name is used.
   */
  public $search_field = 'title';

  protected $errors = [];

  /**
   * Flag that gets set if a unique key violation has occurred on save.
   *
   * @var bool
   */
  public $uniqueKeyViolation = FALSE;

  protected $identifiers = array(
    'website_id' => NULL,
    'survey_id' => NULL,
  );

  /**
   * @var array unvalidatedFields allows a list of fields which are not validated in anyway to be declared
   * by a model. If not declared then the model will not transfer them to the saved data when
   * posting a record.
   */
  protected $unvalidatedFields = [];

  /**
   * @var array An array which a model can populate to declare additional fields that can be submitted for csv upload.
   */
  protected $additional_csv_fields = [];

  /**
   * @var bool Does the model have custom attributes? Defaults to false.
   */
  protected $has_attributes = FALSE;

  /**
   * @var bool If the model has custom attributes, are public ones always available across the warehouse, or
   * does it require a link to a website to include the attribute in the submissable data? Defaults to FALSE.
   */
  public $include_public_attributes = FALSE;

  /**
   * @var bool Is this model for an existing record that is being saved over?
   */
  protected $existing = FALSE;

  private $cache;

  /**
   * Should metadata fields be updated?
   *
   * Default behaviour on save is to update metadata. If we detect no changes
   * we can skip this.
   *
   * @var bool
   */
  public $wantToUpdateMetadata = TRUE;

  private $metadataUpdateBubblesToParent = FALSE;

  /**
   * When submitting a parent with children, flag that the parent is changing.
   *
   * @var bool
   */
  public $parentChanging = FALSE;

  private $attrValModels = [];

  /**
   * @var array If a submission contains submodels, then the array of submodels can be keyed. This
   * allows other foreign key fields in the submisson to refer to a model which does not exist yet.
   * Normally, super/sub-models can handle foreign keys, but this approach is needed for association
   * tables which join across 2 entities created by a submission.
   */
  private $dynamicRowIdReferences = [];

  /**
   * Indicates database trigger on table which accesses a sequence.
   *
   * Kohana relies on PostgreSQL lastval() to find the ID for inserted records,
   * this fails if the table has a trigger on it which uses a sequence during
   * the insert. Setting this to TRUE causes a more reliable method of
   * detecting the inserted record ID to be used which avoids this problem.
   *
   * In order for this method to work the sequence must be associated with
   * the table. You may need to execute a query like
   * ALTER SEQUENCE occurrences_id_seq OWNED BY occurrences.id
   *
   * @var bool
   */
  protected $hasTriggerWithSequence = FALSE;

  /**
   * Constructor allows plugins to modify the data model.
   *
   * @var int $id
   *   ID of the record to load. If null then creates a new record. If -1 then
   *   the ORM object is not initialised, providing access to the variables
   *   only.
   */
  public function __construct($id = NULL) {
    if (is_object($id) || $id != -1) {
      // Use caching, so things don't slow down if there are lots of plugins.
      // The object_name does not exist yet as we haven't called the parent
      // construct, so we build our own.
      $object_name = strtolower(substr(get_class($this), 0, -6));
      $cacheId = 'orm-' . $object_name;
      $this->cache = Cache::instance();
      $ormRelations = $this->cache->get($cacheId);
      if ($ormRelations === NULL) {
        // now look for modules which plugin to tweak the orm relationships.
        foreach (Kohana::config('config.modules') as $path) {
          $plugin = basename($path);
          if (file_exists("$path/plugins/$plugin.php")) {
            require_once "$path/plugins/$plugin.php";
            if (function_exists($plugin . '_extend_orm')) {
              $extends = call_user_func($plugin . '_extend_orm');
              if (isset($extends[$object_name])) {
                if (isset($extends[$object_name]['has_one'])) {
                  $this->has_one = array_merge($this->has_one, $extends[$object_name]['has_one']);
                }
                if (isset($extends[$object_name]['has_many'])) {
                  $this->has_many = array_merge($this->has_many, $extends[$object_name]['has_many']);
                }
                if (isset($extends[$object_name]['belongs_to'])) {
                  $this->belongs_to = array_merge($this->belongs_to, $extends[$object_name]['belongs_to']);
                }
                if (isset($extends[$object_name]['has_and_belongs_to_many'])) {
                  $this->has_and_belongs_to_many = array_merge($this->has_and_belongs_to_many, $extends[$object_name]['has_and_belongs_to_many']);
                }
              }
            }
          }
        }
        $cacheArray = [
          'has_one' => $this->has_one,
          'has_many' => $this->has_many,
          'belongs_to' => $this->belongs_to,
          'has_and_belongs_to_many' => $this->has_and_belongs_to_many,
        ];
        $this->cache->set($cacheId, $cacheArray, ['orm']);
      }
      else {
        $this->has_one = $ormRelations['has_one'];
        $this->has_many = $ormRelations['has_many'];
        $this->belongs_to = $ormRelations['belongs_to'];
        $this->has_and_belongs_to_many = $ormRelations['has_and_belongs_to_many'];
      }
      parent::__construct($id);
    }
  }

  /**
   * Returns an array structure which describes the results of a submission.
   *
   * Includes key information about this model, identifier and timestamp
   * fields, plus the saved child models that were created during a submission
   * operation.
   */
  public function getSubmissionResponseMetadata() {
    $r = [
      'model' => $this->object_name,
      'id' => $this->id,
    ];
    // Add the external key and timestamps if present
    if (!empty($this->external_key)) {
      $r['external_key'] = $this->external_key;
    }
    if (!empty($this->search_code)) {
      $r['search_code'] = $this->search_code;
    }
    if (!empty($this->created_on)) {
      $r['created_on'] = $this->created_on;
    }
    if (!empty($this->updated_on)) {
      $r['updated_on'] = $this->updated_on;
    }
    if (count($this->nestedChildModelIds)) {
      $r['children'] = $this->nestedChildModelIds;
    }
    if (count($this->nestedParentModelIds)) {
      $r['parents'] = $this->nestedParentModelIds;
    }
    return $r;
  }

  /**
   * Override load_values to add in a vague date field.
   *
   * Also strips out any custom attribute values which don't go into this model.
   *
   * @param array $values
   *   Values to load.
   *
   * @return ORM
   */
  public function load_values(array $values) {
    // Clear out any values which match this attribute field prefix.
    if (isset($this->attrs_field_prefix)) {
      foreach ($values as $key => $value) {
        if (substr($key, 0, strlen($this->attrs_field_prefix) + 1) == $this->attrs_field_prefix . ':') {
          unset($values[$key]);
        }
        if (substr($key, 0, 9) === 'fkFilter:') {
          unset($values[$key]);
        }
      }
    }
    parent::load_values($values);
    // Add in date field.
    if (array_key_exists('date_type', $this->object) && !empty($this->object['date_type'])) {
      $vd = vague_date::vague_date_to_string([
        $this->object['date_start'],
        $this->object['date_end'],
        $this->object['date_type'],
      ]);
      $this->object['date'] = $vd;
    }
    return $this;
  }

  /**
   * Override the reload_columns method to add the vague_date virtual field.
   *
   * @param bool $force
   *   Reload the columns from the db even if already loaded.
   *
   * @return $this|\ORM
   */
  public function reload_columns($force = FALSE) {
    if ($force === TRUE || empty($this->table_columns)) {
      // Load table columns.
      $this->table_columns = postgreSQL::list_fields($this->table_name, $this->db);
      // Vague date.
      if (array_key_exists('date_type', $this->table_columns)) {
        $this->table_columns['date']['type'] = 'String';
      }
    }

    return $this;
  }

  /**
   * Get error for a given field name.
   *
   * Provide an accessor so that the view helper can retrieve the error for the
   * model by field name. Will also retrieve errors from linked models (models
   * that were posted in the same submission) if the field name is of the form
   * model:fieldname.
   *
   * @param string $fieldname
   *   Name of the field to retrieve errors for. The fieldname can either be
   *   simple, or of the form model:fieldname in which linked models can also
   *   be checked for errors. If the submission structure defines the
   *   fieldPrefix for the model then this is used instead of the model name.
   *
   * @return string
   *   The error text.
   */
  public function getError($fieldname) {
    $r = '';
    if (array_key_exists($fieldname, $this->errors)) {
      // Model is unspecified, so load error from this model.
      $r = $this->errors[$fieldname];
    }
    elseif (strpos($fieldname, ':') !== FALSE) {
      list($model, $field) = explode(':', $fieldname);
      // Model is specified.
      $struct = $this->get_submission_structure();
      $fieldPrefix = array_key_exists('fieldPrefix', $struct) ? $struct['fieldPrefix'] : $this->object_name;
      if ($model == $fieldPrefix) {
        // Model is this model.
        if (array_key_exists($field, $this->errors)) {
          $r = $this->errors[$field];
        }
      }
    }
    return $r;
  }

  /**
   * Retrieve an array containing all errors.
   *
   * The array entries are of the form 'entity:field => value'.
   */
  public function getAllErrors() {
    // Get this model's errors, ensuring array keys have prefixes identifying
    // the entity.
    foreach ($this->errors as $key => $value) {
      if (strpos($key, ':') === FALSE) {
        $this->errors[$this->object_name . ':' . $key] = $value;
        unset($this->errors[$key]);
      }
    }
    return $this->errors;
  }

  /**
   * Retrieve an array containing all page level errors which are marked with the key general.
   */
  public function getPageErrors() {
    $r = [];
    if (array_key_exists('general', $this->errors)) {
      array_push($r, $this->errors['general']);
    }
    return $r;
  }

  /**
   * ORM validate method.
   *
   * Override the ORM validate method to store the validation errors in an
   * array, making them accessible to the views.
   *
   * @param Validation $array
   *   Validation array object.
   * @param bool $save
   *   Optional. True if this call also saves the data, false to just validate.
   *   Default is false.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on fail.
   *
   * @throws Exception
   *   Rethrows any exceptions occurring on validate.
   */
  public function validate(Validation $array, $save = FALSE) {
    if (!empty($this->identifiers['survey_id'])) {
      $qry = $this->db
        ->select('core_validation_rules')
        ->from('surveys')
        ->where('id', $this->identifiers['survey_id'])
        ->get()
        ->current();
      if (!empty($qry->core_validation_rules)) {
        $rules = json_decode($qry->core_validation_rules, TRUE);
        if (isset($rules[$this->object_name])) {
          foreach ($rules[$this->object_name] as $field => $rules) {
            $array->add_rules($field, $rules);
          }
        }
      }
    }

    // Set the default created/updated information.
    if ($this->wantToUpdateMetadata) {
      $this->set_metadata();
    }
    // Now look for any modules which alter the submission.
    if ($save) {
      foreach (Kohana::config('config.modules') as $path) {
        $plugin = basename($path);
        if (file_exists("$path/plugins/$plugin.php")) {
          require_once "$path/plugins/$plugin.php";
          if (function_exists($plugin . '_orm_pre_save_processing')) {
            $state[$plugin] = call_user_func_array(
              $plugin . '_orm_pre_save_processing',
              [
                $this->db,
                empty($this->identifiers['website_id']) ? NULL : $this->identifiers['website_id'],
                $this->object_name,
                $this,
                &$array,
              ]
            );
          }
        }
      }
    }

    $modelFields = $array->as_array();
    $fields_to_copy = $this->unvalidatedFields;
    // The created_by_id and updated_by_id fields can be specified by web
    // service calls if the caller knows which Indicia user is making the post.
    if (!empty($modelFields['created_by_id'])) {
      $fields_to_copy[] = 'created_by_id';
    }
    if (!empty($modelFields['updated_by_id'])) {
      $fields_to_copy[] = 'updated_by_id';
    }
    foreach ($fields_to_copy as $a) {
      if (array_key_exists($a, $modelFields)) {
        // When a field allows nulls, convert empty values to null. Otherwise
        // we end up trying to store '' in non-string fields such as dates.
        if ($array[$a] === '' && isset($this->table_columns[$a]['null']) && $this->table_columns[$a]['null'] == 1) {
          $array[$a] = NULL;
        }
        $this->__set($a, $array[$a]);
      }
    }
    try {
      if (parent::validate($array, $save)) {
        if ($save) {
          foreach (Kohana::config('config.modules') as $path) {
            $plugin = basename($path);
            if (function_exists($plugin . '_orm_post_save_processing')) {
              call_user_func($plugin . '_orm_post_save_processing', $this->db, $this->object_name, $array, $state[$plugin], $this->object[$this->primary_key]);
            }
          }
        }
        return TRUE;
      }
      else {
        // Put the trimmed and processed data back into the model.
        $arr = $array->as_array();
        if (array_key_exists('created_on', $this->table_columns)) {
          $arr['created_on'] = $this->created_on;
        }
        if (array_key_exists('updated_on', $this->table_columns)) {
          $arr['updated_on'] = $this->updated_on;
        }
        $this->load_values($arr);
        $this->errors = $array->errors('form_error_messages');
        return FALSE;
      }
    }
    catch (Exception $e) {
      error_logger::log_error('Validation or save exception', $e);
      if (strpos($e->getMessage(), '_unique') !== FALSE) {
        // Duplicate key violation.
        $this->errors = ['general' => 'You cannot add the record as it would create a duplicate.'];
        $this->uniqueKeyViolation = TRUE;
        return FALSE;
      }
      else {
        throw ($e);
      }
    }
  }

  /**
   * Sets the metadata created and updated field values before save.
   *
   * @param object $obj
   *   The object which will have metadata set on it. Defaults to this model.
   */
  public function set_metadata($obj = NULL) {
    global $remoteUserId;
    if ($obj == NULL) {
      $obj = $this;
    }
    $force = TRUE;
    // Find the user ID from several possible places, in order of precedence.
    if (isset($_SESSION['auth_user'])) {
      // User logged into warehouse.
      $userId = $_SESSION['auth_user']->id;
    }
    elseif (isset($remoteUserId)) {
      // User ID from request parameter.
      $userId = $remoteUserId;
    }
    else {
      // User ID from a global default.
      $defaultUserId = Kohana::config('indicia.defaultPersonId');
      $userId = ($defaultUserId ? $defaultUserId : 1);
      // Don't force overwrite of user IDs that already exist in the record, as
      // likely to be an internal/system update of the record.
      $force = FALSE;
    }
    // Set up the created and updated metadata for the record.
    if (!$obj->id && array_key_exists('created_on', $obj->table_columns)) {
      $obj->created_on = date("Ymd H:i:s");
      if ($force or !$obj->created_by_id) {
        $obj->created_by_id = $userId;
      }
    }
    // @todo Check if updated metadata present in this entity, and also use
    // correct user.
    if (array_key_exists('updated_on', $obj->table_columns)) {
      $obj->updated_on = date("Ymd H:i:s");
      if ($force or !$obj->updated_by_id) {
        if ($obj->id) {
          $obj->updated_by_id = $userId;
        }
        else {
          // Creating a new record, so it must be the same updator as creator.
          $obj->updated_by_id = $obj->created_by_id;
        }
      }
    }
  }

  /**
   * Do a default search for an item using the search_field setup for this model.
   *
   * @param string $search_text
   *   Text to look up.
   *
   * @return ORM
   *   The ORM object filtered to look up the text.
   */
  public function lookup($search_text) {
    return $this->where($this->search_field, $search_text)->find();
  }

  /**
   * Return a displayable caption for the item.
   *
   * Defined as the content of the field with the same name as search_field.
   *
   * @return string
   *   Caption for an existing entry.
   */
  public function caption() {
    if ($this->id) {
      return $this->__get($this->search_field);
    }
    else {
      return $this->getNewItemCaption();
    }
  }

  /**
   * Find the current authenticated user ID.
   *
   * @return int
   *   ID from the users table.
   */
  public function getUserId() {
    global $remoteUserId;
    return $remoteUserId ?? $_SESSION['auth_user']->id ?? Kohana::config('indicia.defaultPersonId');
  }

  /**
   * Retrieve the caption of a new entry of this model type.
   *
   * Overrideable as required.
   *
   * @return string
   *   Caption for a new entry.
   */
  protected function getNewItemCaption() {
    return ucwords(str_replace('_', ' ', $this->object_name));
  }

  /**
   * Indicates if this model type can create new instances from data supplied in its caption format.
   * Overrideable as required.
   *
   * @return bool
   *   Override to true if your model supports this.
   */
  protected function canCreateFromCaption() {
    return FALSE;
  }

  /**
   * Puts each supplied caption in a submission and sends it to the supplied model.
   *
   * @return array
   *   An array of record id values for the created records.
   */
  private function createRecordsFromCaptions() {
    $r = [];

    // Establish the right model and check it supports create from captions.
    $modelname = $this->submission['fields']['insert_captions_to_create']['value'];
    $m = ORM::factory($modelname);
    if ($m->canCreateFromCaption()) {
      // Get the array of captions.
      $fieldname = $this->submission['fields']['insert_captions_use']['value'];
      if (empty($this->submission['fields'][$fieldname])
        || empty($this->submission['fields'][$fieldname]['value'])) {
        return $r;
      }
      $captions = $this->submission['fields'][$fieldname]['value'];
      // Build a skeleton submission.
      $sub = [
        'id' => $modelname,
        'fields' => [
          'caption' => [],
        ],
      ];
      // Submit each caption to create a record, unless it exists.
      $i = 0;
      foreach ($captions as $value) {
        // Sanitize caption.
        $value = trim(preg_replace('/\s+/', ' ', $value));
        $id = $m->findByCaption($value);
        if ($id > 0) {
          // Record exists.
          $r[$i] = $id;
        }
        else {
          // Create new record.
          $sub['fields']['caption']['value'] = $value;
          $m = ORM::factory($modelname);
          $m->submission = $sub;
          // Copy down the website id and survey id.
          $m->identifiers = array_merge($this->identifiers);
          $r[$i] = $m->inner_submit();
        }
        $i++;
      }
    }

    return $r;
  }

  /**
   * When using a sublist control (or any similar multi-value control), non-existing
   * values added  to the list are posted as captions, These need to be converted to
   * IDs in the table identified
   * Puts each supplied record id into the submission to replace the captions
   * so we store IDs instead.
   *
   * @param array $ids
   *
   * @return bool
   */
  private function createIdsFromCaptions(array $ids) {
    $fieldname = $this->submission['fields']['insert_captions_use']['value'];
    if (empty($ids)) {
      $this->submission['fields'][$fieldname] = ['value' => []];
    }
    else {
      $keys = array_fill(0, count($ids), 'value');
      $a = array_fill_keys($keys, $ids);
      $this->submission['fields'][$fieldname] = $a;
    }
    return TRUE;
  }

  /**
   * Overridden if this model type can create new instances from data supplied in its caption format.
   *
   * @return int
   *   The id of the first matching record with the supplied caption or 0 if no
   *   match.
   */
  protected function findByCaption($caption) {
    return 0;
  }

  /**
   * Overridden if this model type can create new instances from data supplied in its caption format.
   * Does nothing if not overridden.
   *
   * @return bool
   *   Override to true if your model supports this.
   */
  protected function handleCaptionSubmission() {
    return FALSE;
  }

  /**
   * Ensures that the save array is validated before submission. Classes overriding
   * this method should call this parent method after their changes to perform necessary
   * checks unless they really want to skip them.
   */
  protected function preSubmit() {
    // Where fields are numeric, ensure that we don't try to submit strings to
    // them.
    foreach ($this->submission['fields'] as $field => $content) {
      if (isset($content['value']) && $content['value'] == '' && array_key_exists($field, $this->table_columns)) {
        $type = $this->table_columns[$field]['type'];
        switch ($type) {
          case 'int':
            $this->submission['fields'][$field]['value'] = null;
            break;
          }
      }
    }
    // if the current model supports attributes then
    // create records from captions if this has been requested.
    if ($this->has_attributes
      && !empty($this->submission['fields']['insert_captions_to_create'])
      && !empty($this->submission['fields']['insert_captions_to_create']['value'])
      && !empty($this->submission['fields']['insert_captions_use'])
      && !empty($this->submission['fields']['insert_captions_use']['value'])) {

      $ids = $this->createRecordsFromCaptions();
      $this->createIdsFromCaptions($ids);
      unset($this->submission['fields']['insert_captions_to_create']);
      unset($this->submission['fields']['insert_captions_use']);
    }
  }

  /**
   * Grab the survey id and website id if they are in the submission, as they are used to check
   * attributes that apply and other permissions.
   */
  protected function populateIdentifiers() {
    if (array_key_exists('website_id', $this->submission['fields'])) {
      if (is_array($this->submission['fields']['website_id'])) {
        $this->identifiers['website_id'] = $this->submission['fields']['website_id']['value'];
      }
      else {
        $this->identifiers['website_id'] = $this->submission['fields']['website_id'];
      }
    }
    if (array_key_exists('survey_id', $this->submission['fields'])) {
      if (is_array($this->submission['fields']['survey_id'])) {
        $this->identifiers['survey_id'] = $this->submission['fields']['survey_id']['value'];
      }
      else {
        $this->identifiers['survey_id'] = $this->submission['fields']['survey_id'];
      }
    }
  }

  /**
   * Wraps the process of submission in a transaction.
   *
   * @return int
   *   If successful, returns the id of the created/found record. If not,
   *   returns null - errors are embedded in the model.
   */
  public function submit() {
    Kohana::log('debug', 'Commencing new transaction.');
    $this->db->query('BEGIN;');
    try {
      $this->errors = [];
      $this->preProcess();
      $res = $this->inner_submit();
      $this->postProcess();
    }
    catch (Exception $e) {
      $this->errors['general'] = 'An error occurred when saving the information. More information is in the warehouse logs (' . date("Y-m-d H:i:s") . ').';
      error_logger::log_error('Exception during submit.', $e);
      $res = NULL;
    }
    if ($res) {
      $allowCommitToDB = (isset($_GET['allow_commit_to_db']) ? $_GET['allow_commit_to_db'] : TRUE);
      if (!empty($allowCommitToDB) && $allowCommitToDB == TRUE) {
        Kohana::log('debug', 'Committing transaction.');
        $this->db->query('COMMIT;');
      }
    }
    else {
      Kohana::log('debug', 'Rolling back transaction.');
      kohana::log('debug', var_export($this->getAllErrors(), TRUE));
      $this->db->query('ROLLBACK;');
    }
    return $res;
  }

  /**
   * Run preprocessing required before submission.
   */
  private function preProcess() {
    // Initialise the variable which tracks the records we are about to submit.
    self::$changedRecords = [
      'update' => [],
      'insert' => [],
      'delete' => [],
    ];
  }

  /**
   * Submission post-processing.
   *
   * Handles any index rebuild requirements as a result of new or updated
   * records, e.g. in samples or occurrences. Also handles joining of
   * occurrence_associations to the correct records.
   */
  private function postProcess() {
    if (class_exists('cache_builder') && (!isset($_REQUEST['cache_updates']) || $_REQUEST['cache_updates'] !== 'off')) {
      $occurrences = [];
      $deletedOccurrences = [];
      if (!empty(self::$changedRecords['insert']['occurrence'])) {
        cache_builder::insert($this->db, 'occurrences', self::$changedRecords['insert']['occurrence']);
        $occurrences = self::$changedRecords['insert']['occurrence'];
      }
      if (!empty(self::$changedRecords['update']['occurrence'])) {
        cache_builder::update($this->db, 'occurrences', self::$changedRecords['update']['occurrence']);
        $occurrences += self::$changedRecords['update']['occurrence'];
      }
      if (!empty(self::$changedRecords['delete']['occurrence'])) {
        cache_builder::delete($this->db, 'occurrences', self::$changedRecords['delete']['occurrence']);
        $deletedOccurrences = self::$changedRecords['delete']['occurrence'];
      }
      $samples = [];
      if (!empty(self::$changedRecords['insert']['sample'])) {
        $samples = self::$changedRecords['insert']['sample'];
        cache_builder::insert($this->db, 'samples', self::$changedRecords['insert']['sample']);
      }
      if (!empty(self::$changedRecords['update']['sample'])) {
        $samples += self::$changedRecords['update']['sample'];
        cache_builder::update($this->db, 'samples', self::$changedRecords['update']['sample']);
      }
      if (!empty(self::$changedRecords['delete']['sample'])) {
        cache_builder::delete($this->db, 'samples', self::$changedRecords['delete']['sample']);
      }
      if (!empty($samples)) {
        // @todo Map squares could be added to work queue.
        postgreSQL::insertMapSquaresForSamples($samples, $this->db);
      }
      elseif (!empty($occurrences)) {
        // No need to do occurrence map square update if inserting a sample, as
        // the above code does the occurrences in bulk.
        postgreSQL::insertMapSquaresForOccurrences($occurrences, $this->db);
        // Need to ensure sample tracking is updated if occurrences change
        // without a posted sample.
        cache_builder::updateSampleTrackingForOccurrences($this->db, $occurrences + $deletedOccurrences);
      }
    }
    if (!empty(self::$changedRecords['insert']['occurrence_association']) ||
        !empty(self::$changedRecords['update']['occurrence_association'])) {
      // We've got some associations between occurrences that could not have
      // the to_occurrence_id foreign key filled in yet, since the occurrence
      // referred to did not exist at the time of saving.
      foreach (Occurrence_association_Model::$to_occurrence_id_pointers as $associationId => $pointer) {
        if (!empty($this->dynamicRowIdReferences["occurrence:$pointer"])) {
          $this->db->from('occurrence_associations')
            ->set('to_occurrence_id', $this->dynamicRowIdReferences["occurrence:$pointer"])
            ->where('id', $associationId)
            ->update();
        }
      }
      // Reset important if doing an import with multiple submissions.
      Occurrence_association_Model::$to_occurrence_id_pointers = [];
    }
    if (!empty(self::$changedRecords['insert']['classification_result']) ||
        !empty(self::$changedRecords['update']['classification_result'])) {
      Classification_result_Model::createMediaJoins($this->db);
    }
    $this->createWorkQueueEntries();
  }

  /**
   * Uses the changedRecords after an ORM save event to add tasks to the queue.
   */
  private function createWorkQueueEntries() {
    $queueHelpers = $this->cache->get('work-queue-helpers');
    if ($queueHelpers === NULL) {
      $queueHelpers = [];
      foreach (Kohana::config('config.modules') as $path) {
        $plugin = basename($path);
        if (function_exists($plugin . '_orm_work_queue')) {
          $queueHelpers = array_merge($queueHelpers, call_user_func($plugin . '_orm_work_queue'));
        }
      }
      $this->cache->set('work-queue-helpers', $queueHelpers);
    }
    foreach ($queueHelpers as $cfg) {
      foreach ($cfg['ops'] as $op) {
        if (!empty(self::$changedRecords[$op][$cfg['entity']])) {
          $proceed = TRUE;
          if (!empty($cfg['limit_to_field_changes'])) {
            $proceed = FALSE;
            $currentValues = $this->as_array();
            foreach ($cfg['limit_to_field_changes'] as $field) {
              if (array_key_exists($field, $this->initialValues) && array_key_exists($field, $currentValues)) {
                $oldVal = $this->initialValues[$field];
                if ($oldVal === 't') {
                  $oldVal = '1';
                }
                elseif ($oldVal === 'f') {
                  $oldVal = '0';
                }
                $proceed = $proceed || ($oldVal !== $currentValues[$field]);
              }
            }
          }
          if ($proceed) {
            $this->queueWork($cfg, self::$changedRecords[$op][$cfg['entity']]);
          }
        }
      }
    }
  }

  /**
   * Queues an item of work for later processing.
   *
   * @param array $cfg
   *   Configuration array containing the entity, task name, cost_estimate and
   *   priority of the work item.
   * @param array $records
   *   List of record IDs to queue.
   */
  private function queueWork(array $cfg, array $records) {
    $q = new WorkQueue();
    foreach ($records as $id) {
      $q->enqueue($this->db, [
        'task' => $cfg['task'],
        'entity' => $cfg['entity'],
        'record_id' => $id,
        'cost_estimate' => $cfg['cost_estimate'],
        'priority' => $cfg['priority'],
      ]);
    }
  }

  /**
   * Finds the name of a value field according to the attr's datatype.
   *
   * @param array $allAttributes
   *   Loaded list of attributes for the data being saved.
   * @param int $id
   *   Attribute ID.
   *
   * @return string
   *   Attribute value field name.
   */
  private function getValueField($allAttributes, $id) {
    foreach ($allAttributes as $attr) {
      if ($attr->id == $id) {
        switch ($attr->data_type) {
          case 'T':
            return 'text_value';

          case 'F':
            return 'float_value';

          case 'D':
          case 'V':
            return 'date_start_value';

          case 'G':
            return 'geom_value';

          case 'I':
          case 'B':
          case 'L':
            return 'int_value';
        }
      }
    }
    // Fallback.
    return 'text_value';
  }

  /**
   * Performs a basic validation precheck on the submission fields.
   *
   * @param array $identifiers
   *   Identifier values, e.g. website_id, survey_id or taxon_list_id.
   *
   * @return array
   *   Key/value pairs of validation errors found.
   */
  public function precheck(array $identifiers) {
    $this->identifiers = $identifiers;
    // Need to call pre-submit as it fills in certain fields, e.g. geom from
    // the spatial ref fields.
    $this->preSubmit();
    $vArray = [];
    foreach ($this->submission['fields'] as $key => $value) {
      if (isset($value['value'])) {
        $vArray[$key] = $value['value'];
      }
    }
    $validationObj = new Validation($vArray);
    $this->validate($validationObj);
    // Errors can be in $this->errors from preSubmit function, so merge them.
    $errors = array_merge($this->errors, $validationObj->errors());
    // Ensure fieldname prefixed with entity.
    $errors = array_combine(
      array_map(fn($k) => "$this->object_name:$k", array_keys($errors)),
      $errors
    );
    if ($this->has_attributes) {
      // @todo The getAttributes call should use a type filter e.g. to filter on sample_method_id.
      $requiredAttributes = $this->getAttributes(TRUE);
      foreach ($requiredAttributes as $attr) {
        if (empty($vArray["$this->attrs_field_prefix:$attr->id"])) {
          $errors["$this->attrs_field_prefix:$attr->id"] = 'A value for this attribute is required.';
        }
      }
      $allAttributes = $this->getAttributes(FALSE);
      foreach ($vArray as $field => $value) {
        if (preg_match("/^$this->attrs_field_prefix\:(?<id>\d+)/", $field, $matches)) {
          $attrObj = ORM::factory($this->object_name . '_attribute_value');
          $valueField = $this->getValueField($allAttributes, $matches['id']);
          $attrArray = [
            $this->object_name . '_id' => 1,
            $this->object_name . '_attribute_id' => $matches['id'],
            $valueField => $value,
          ];
          $attrValidationObj = new Validation($attrArray);
          $attrObj->validate($attrValidationObj);
          if (count($attrValidationObj->errors())) {
            $fieldErrors = [];
            // Allow translation of error types, also to map to readable
            // English.
            foreach ($attrValidationObj->errors() as $error) {
              $fieldErrors[] = kohana::lang("attribute_validation.$error");
            }
            $errors[$field] = implode(';', $fieldErrors);
          }
        }
      }
    }
    return $errors;
  }

  /**
   * Submits the data by:
   * - For each entry in the "supermodels" array, calling the submit function
   *   for that model and linking in the resultant object.
   * - Calling the preSubmit function to clean data.
   * - Linking in any foreign fields specified in the "fk-fields" array.
   * - Checking (by a where clause for all set fields) that an existing
   *   record does not exist. If it does, return that.
   * - Calling the validate method for the "fields" array.
   * If successful, returns the id of the created/found record.
   * If not, returns null - errors are embedded in the model.
   */
  public function inner_submit(){
    $this->wantToUpdateMetadata = TRUE;
    $isInsert = $this->id === 0
        && (empty($this->submission['fields']['id']) || empty($this->submission['fields']['id']['value']));
    $this->handleCaptionSubmission();
    $return = $this->populateFkLookups();
    $this->populateIdentifiers();
    $return = $this->createParentRecords() && $return;
    // No point doing any more if the parent records did not post.
    if ($return) {
      $this->initialValues = $this->as_array();
      $this->preSubmit();
      if (count($this->errors) > 0) {
        return FALSE;
      }
      $this->removeUnwantedFields();
      $return = $this->validateAndSubmit();
      $return = $this->checkRequiredAttributes() ? $return : NULL;
      if ($this->id) {
        // Make sure we got a record to save against before attempting to post children. Post attributes first
        // before child records because the parent (e.g. Sample) attribute values sometimes affect the cached data
        // (e.g. the recorders stored in cache_occurrences)
        $return = $this->createAttributes($isInsert) ? $return : NULL;
        $return = $this->createChildRecords() ? $return : NULL;
        $return = $this->createJoinRecords() ? $return : NULL;
        if ($isInsert) {
          $addTo = &self::$changedRecords['insert'];
        }
        elseif (isset($this->deleted) && $this->deleted === 't') {
          $addTo = &self::$changedRecords['delete'];
        }
        else {
          $addTo = &self::$changedRecords['update'];
        }
        if (!isset($addTo[$this->object_name])) {
          $addTo[$this->object_name] = [];
        }
        $addTo[$this->object_name][] = $this->id;
      }
      // Call postSubmit.
      if ($return) {
        $ps = $this->postSubmit($isInsert);
        if ($ps == NULL) {
          $return = NULL;
        }
      }
      if (kohana::config('config.log_threshold') == '4') {
        kohana::log('debug', "Done inner submit of model $this->object_name with result $return");
      }
    }
    if (!$return) kohana::log('debug', kohana::debug($this->getAllErrors()));
    return $return;
  }

  /**
   * Remove any fields from the submission that are not in the model and are not custom attributes of the model.
   */
  private function removeUnwantedFields() {
    foreach($this->submission['fields'] as $field => $content) {
      if ( !array_key_exists($field, $this->table_columns) && !(isset($this->attrs_field_prefix) && preg_match('/^'.$this->attrs_field_prefix.'\:/', $field)) )
        unset($this->submission['fields'][$field]);
    }
  }

  /**
   * Permissions check if accessing a different website to the one authorised.
   *
   * @param int $authorisedWebsiteId
   *   Website ID passed with authentication.
   * @param int $otherWebsiteId
   *   Website ID that is being accessed.
   * @param string $sharingMode
   *   Sharing mode to check for, e.g. editing.
   */
  private function checkHasWebsiteRights($authorisedWebsiteId, $otherWebsiteId, $sharingMode) {
    if ((integer) $authorisedWebsiteId === 0 || $authorisedWebsiteId == $otherWebsiteId) {
      // Warehouse access with full rights, or authorised on the same website.
      return;
    }
    $checkField = pg_escape_identifier($this->db->getLink(), "provide_for_$sharingMode");
    $sql = <<<SQL
      SELECT count(*) FROM index_websites_website_agreements
      WHERE to_website_id=? and from_website_id=?
      AND $checkField=true
    SQL;
    if ((int) $this->db->query($sql, [$authorisedWebsiteId, $otherWebsiteId])->current()->count === 0) {
      throw new Exception('Access to this website denied.', 2001);
    }
  }

  /**
   * Actually validate and submit the inner submission.
   *
   * @return int
   *   Id of the submitted record, or NULL if this failed.
   *
   * @throws Exception
   *   On access denied to the website of an existing record.
   */
  protected function validateAndSubmit() {
    $return = NULL;
    // Flatten the array to one that can be validated.
    $vArray = array_map(function ($arr) {
      return is_array($arr) ? $arr["value"] : $arr;
    }, $this->submission['fields']);
    if (!empty($vArray['website_id']) && $vArray['website_id'] !== self::$authorisedWebsiteId) {
      $this->checkHasWebsiteRights(self::$authorisedWebsiteId, $vArray['website_id'], 'editing');
    }
    // If we're editing an existing record, merge with the existing data.
    // NB id is 0, not null, when creating a new user.
    if (array_key_exists('id', $vArray) && $vArray['id'] != NULL && $vArray['id'] != 0) {
      $this->find($vArray['id']);
      $thisValues = $this->as_array();
      unset($thisValues['updated_by_id']);
      unset($thisValues['updated_on']);
      // Don't overwrite existing website_ids otherwise things like shared
      // verification portals end up grabbing records to their own website ID.
      if (!empty($thisValues['website_id']) && !empty($vArray['website_id'])) {
        unset($vArray['website_id']);
        // This means we have a request to update a record come in from a
        // different website to the origin. So check edit rights on the
        // origin...
        $this->checkHasWebsiteRights(self::$authorisedWebsiteId, $thisValues['website_id'], 'editing');
      }
      // If there are no changed fields between the current and new record,
      // skip the metadata update. We have the problem that array objects
      // appear as strings in $thisValues "{x,y}" but as PHP arrays in $vArray.
      // The function array_intersect_assoc can't handle this.
      // The easiest thing here is pretend the current value of any array
      // column doesn't match. These array columns are used so rarely that this
      // less optimised solution is not important.
      $exactMatches = [];
      foreach ($thisValues as $column => $value) {
        if (array_key_exists($column, $vArray) &&
            !is_array($vArray[$column]) && !is_array($value) &&
            (string) $vArray[$column] === (string) $value) {
          $exactMatches[$column] = $value;
        }
      }
      // Allow for different ways of submitting bool. Don't want to trigger metadata updates if submitting 'on' instead of true
      // for example.
      foreach ($vArray as $key => $value) {
        if (isset($this->$key)
            && (($this->$key === 't' && ($value === 'on' || $value === 1 || $value === '1'))
            ||  ($this->$key === 'f' && ($value === 'off' || $value === 0 || $value === '0')))) {
          $exactMatches[$key] = $this->$key;
        }
      }
      $fieldsWithValuesInSubmission = array_intersect_key($thisValues, $vArray);
      $this->wantToUpdateMetadata = count($exactMatches) !== count($fieldsWithValuesInSubmission);
      $vArray = array_merge($thisValues, $vArray);
      $this->existing = TRUE;
    }
    foreach ($vArray as $key => &$value) {
      $value = security::xss_clean($value);
    }
    Kohana::log("debug", "About to validate the following array in model $this->object_name");
    Kohana::log("debug", kohana::debug($this->sanitise($vArray)));
    $isInsert = empty($this->id) && (empty($this->submission['fields']['id']) || empty($this->submission['fields']['id']['value']));
    try {
      if (array_key_exists('deleted', $vArray) && $vArray['deleted'] === 't') {
        // For a record deletion, we don't want to validate and save anything. Just mark delete it.
        $this->deleted = 't';
        $this->set_metadata();
        $v = $this->save();
      }
      else {
        // Create a new record by calling the validate method.
        $v = $this->validate(new Validation($vArray), TRUE);
      }
    }
    catch (Exception $e) {
      $v = FALSE;
      if (preg_match('/violates unique constraint "unique_([a-z0-9_]+)"/', $e->getMessage(), $matches)) {
        // If the constraint is called unique_{table}_{field} then we can work out the field name.
        $fieldname = preg_replace("/^{$this->object_name}_/", '', $matches[1]);
        $fieldnameReadable = str_replace('_', ' ', $fieldname);
        $this->errors[$fieldname] = "A record with the same $fieldnameReadable already exists. Please create a unique $fieldnameReadable.";
      }
      else {
        $this->errors['general'] = 'An error occurred whilst validating the information on the warehouse. More information is in the warehouse logs (' . date("Y-m-d H:i:s") . ').';
      }
      error_logger::log_error('Exception during validation', $e);
    }
    if ($v) {
      // Record has successfully validated so return the id. If the entity uses
      // a trigger containing a sequence, we have to recalculate the ID as the
      // Kohana reliance on lastval() doesn't work.
      if ($isInsert && !empty($this->hasTriggerWithSequence)) {
        $this->id = $this->db->query("SELECT currval(pg_get_serial_sequence(?,'id')) as last_id", [$this->object_plural])->current()->last_id;
      }
      Kohana::log("debug", "Record $this->id has validated successfully");
      $return = $this->id;
    }
    else {
      // Errors.
      Kohana::log("debug", "Record did not validate");
      // Log more detailed information on why.
      foreach ($this->errors as $f => $e) {
        Kohana::log("debug", "Field $f: $e");
      }
    }
    return $return;
  }

  /**
   * When a field is present in the model that is an fkField, this means it contains a lookup
   * caption that must be searched for in the fk entity. This method does the searching and
   * puts the fk id back into the main model so when it is saved, it links to the correct fk
   * record.
   * Respects the setting $cacheFkLookups to use the cache if possible.
   *
   * @return boolean True if all lookups populated successfully.
   */
  private function populateFkLookups() {
    $r=TRUE;
    // The row might have more than one parent, keep track of database matches for previous column so we can check they are consistent.
    $previousParentFksArray = [];
    // Does the row have more than one parent column, where there is no exact match for both in the database.
    $inconsistentParentFkFound = FALSE;
    if (array_key_exists('fkFields', $this->submission)) {
      foreach ($this->submission['fkFields'] as $a => $b) {
        if (!empty($b['fkSearchValue'])) {
          // if doing a parent lookup in a list based entity (terms or taxa), then filter to lookup within the list.
          if (isset($this->list_id_field) && $b['fkIdField'] === 'parent_id' && !isset($b['fkSearchFilterField'])) {
            $b['fkSearchFilterField'] = $this->list_id_field;
            $b['fkSearchFilterValue'] = $this->submission['fields'][$this->list_id_field]['value'];
          }
          $fk = $this->fkLookup($b);
          if ($fk) {
            if ($b['fkIdField'] === 'parent_id') {
              // A parent might match more than one item, for instance more than one location could have same name.
              $allParentFksArray = [];
              // Get all possible matches for parent column.
              $allFks = $this->fkLookup($b, TRUE);
              // Convert to single dimensional array.
              foreach ($allFks as $idx => $fkItem) {
                $allParentFksArray[$idx] = $fkItem->id;
              }
              // If the matches for the current parent column don't include any of the matches for the previous parent column, then we need to warn the user their data is inconsistent with the database.
              if (!empty($previousParentFksArray) && empty(array_intersect($allParentFksArray, $previousParentFksArray))) {
                $inconsistentParentFkFound = TRUE;
              }
              // If there is only 1 match for the current parent and it appears in the matches for the previous parent, then we know that must be the lookup we what to use.
              if (count(array_intersect($allParentFksArray, $previousParentFksArray)) === 1) {
                $fk = $allParentFksArray[0];
              }
              $previousParentFksArray = $allParentFksArray;
            }
            $this->submission['fields'][$b['fkIdField']] = ['value' => $fk];
          } else {
            // look for a translation of the field name
            $lookingIn = kohana::lang("default.dd:{$this->object_name}:$a");
            if ($lookingIn === "default.dd:$this->object_name:$a") {
              $fields = $this->getSubmittableFields();
              $lookingIn = empty($fields[$this->object_name . ':' . $a]) ?
                $b['readableTableName'] . ' ' . ucwords($b['fkSearchField']) :
                $fields[$this->object_name . ':' . $a];
            }
            $this->errors[$a] = "Could not find \"$b[fkSearchValue]\" in $lookingIn";
            $r=FALSE;
          }
        }
      }
    }
    if ($inconsistentParentFkFound === TRUE) {
      $this->errors[$a] = "More than one parent column has been specified, " .
          "but there is no data on the system that matches all the parent columns in this row";
      $r = FALSE;
    }
    return $r;
  }

  /**
   * Function to return key of item defined in the fkArr parameter.
   *
   * @param array $fkArr
   *   Contains definition of item to look up. Contains the following fields
   *   * fkTable => table in which to perform lookup
   *   * fkSearchField => field in table to search
   *   * fkSearchValue => value to find in search field
   *   * fkSearchFilterField => field by which to filter search
   *   * fkSearchFilterValue => filter value
   *   * fkExcludeDeletedRecords => whether to include a where clause to exclude
   *     deleted records.
   *   * fkWebsite => optional website_id filter.
   * @param bool $returnAllResults
   *   Should all possible matches be returned, or just one match chosen by the
   *   function logic.
   *
   * @return mixed
   *   Object or array of foreign key value, or false if not found.
   */
  protected function fkLookup(array $fkArr, $returnAllResults = FALSE) {
    $key = '';
    if (isset($fkArr['fkSearchFilterValue'])) {
      if (is_array($fkArr['fkSearchFilterValue'])) {
        $filterValue = $fkArr['fkSearchFilterValue']['value'];
      }
      else {
        $filterValue = $fkArr['fkSearchFilterValue'];
      }
    }
    else {
      $filterValue = '';
    }

    if (ORM::$cacheFkLookups && $returnAllResults == FALSE) {
      $keyArr = [
        'lookup',
        $fkArr['fkTable'],
        $fkArr['fkSearchField'],
        $fkArr['fkSearchValue'],
      ];
      // Cache must be unique per filtered value (e.g. when lookup up a taxa in
      // a taxon list).
      if ($filterValue != '') {
        $keyArr[] = $filterValue;
      }
      $key = implode('-', $keyArr);
      $r = $this->cache->get($key);
    }

    if (!isset($r)) {
      $where = [$fkArr['fkSearchField'] => $fkArr['fkSearchValue']];
      // Does the lookup need to be filtered, e.g. to a taxon or term list?
      if (isset($fkArr['fkSearchFilterField']) && $fkArr['fkSearchFilterField']) {
        $where[$fkArr['fkSearchFilterField']] = $filterValue;
      }
      if (isset($fkArr['fkExcludeDeletedRecords']) && $fkArr['fkExcludeDeletedRecords']) {
        $where['deleted'] = 'f';
      }
      if (isset($fkArr['fkWebsite'])) {
        $where['website_id'] = $fkArr['fkWebsite'];
      }
      // For locations we have to filter by the website.
      if ($returnAllResults == TRUE) {
        $matches = $this->db
          ->select('id')
          ->from(inflector::plural($fkArr['fkTable']))
          ->where($where)
          ->get()->result_array();
      }
      else {
        $matches = $this->db
          ->select('id')
          ->from(inflector::plural($fkArr['fkTable']))
          ->where($where)
          ->limit(1)
          ->get();
      }
      if (count($matches) === 0 && $fkArr['fkSearchField'] != 'id') {
        // Try a slower case insensitive search before giving up, but don't
        // bother if id specified as ints don't like ilike.
        $searchValue = strtolower(str_replace("'", "''", $fkArr['fkSearchValue']));
        $this->db
          ->select('id')
          ->from(inflector::plural($fkArr['fkTable']))
          ->where("($fkArr[fkSearchField] ilike '$searchValue')");
        if (isset($fkArr['fkSearchFilterField']) && $fkArr['fkSearchFilterField']) {
          $this->db->where([$fkArr['fkSearchFilterField'] => $filterValue]);
        }
        if ($returnAllResults == TRUE) {
          $matches = $this->db
            ->get()->result_array();
        }
        else {
          $matches = $this->db
            ->limit(1)
            ->get();
        }
      }
      if ($returnAllResults == TRUE) {
        $r = $matches;
      }
      else {
        if (count($matches) > 0) {
          $r = $matches[0]->id;
        }
        else {
          $r = FALSE;
        }
        // Cache the result, even if a match not found.
        if (ORM::$cacheFkLookups) {
          $this->cache->set($key, $r, ['lookup']);
        }
      }
    }
    return $r;
  }

  /**
   * Generate any records that this model contains an FK reference to in the
   * Supermodels part of the submission.
   */
  private function createParentRecords() {
    // Iterate through supermodels, calling their submit methods with
    // subarrays.
    if (array_key_exists('superModels', $this->submission)) {
      foreach ($this->submission['superModels'] as &$a) {
        // Establish the right model - either an existing one or create a new
        // one.
        $id = array_key_exists('id', $a['model']['fields']) ? $a['model']['fields']['id']['value'] : NULL;
        // If a re-import of an existing record with a lookup based on field
        // matching, then we will know the main model ID but not the
        // supermodel. In this case we need to look it up.
        if ($id === NULL && !empty($this->submission['fields']['id']) && !empty($this->submission['fields']['id']['value'])) {
          $fk = $this->db->select("$a[fkId] as fk")
            ->from(inflector::plural($this->object_name))
            ->where('id', $this->submission['fields']['id']['value'])
            ->get()->current();
          $id = $fk->fk;
          $a['model']['fields']['id'] = [
            'value' => $id,
          ];
        }
        if ($id) {
          $m = ORM::factory($a['model']['id'], $id);
        }
        else {
          $m = ORM::factory($a['model']['id']);
        }
        // Don't accidentally delete a parent when deleting a child.
        unset($a['model']['fields']['deleted']);
        // Call the submit method for that model and check whether it returns
        // correctly.
        $m->submission = $a['model'];
        // Copy up the website id and survey id.
        $m->identifiers = array_merge($this->identifiers);
        $result = $m->inner_submit();
        $this->nestedParentModelIds[] = $m->getSubmissionResponseMetadata();
        // Copy the submission back so we pick up updated foreign keys that have
        // been looked up. E.g. if submitting a taxa taxon list, and the taxon
        // supermodel has an fk lookup, we need to keep it so that it gets
        // copied into common names and synonyms.
        $a['model'] = $m->submission;
        if ($result) {
          $this->submission['fields'][$a['fkId']]['value'] = $result;
        }
        else {
          $fieldPrefix = (array_key_exists('field_prefix', $a['model'])) ? $a['model']['field_prefix'] . ':' : '';
          foreach ($m->errors as $key => $value) {
            $this->errors[$fieldPrefix . $key] = $value;
          }
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  /**
   * Generate any records that refer to this model in the subModela part of the
   * submission.
   */
  private function createChildRecords() {
    $r = TRUE;
    if (array_key_exists('subModels', $this->submission)) {
      // Iterate through the subModel array, linking them to this model.
      foreach ($this->submission['subModels'] as $key => $a) {
                Kohana::log("debug", "Submitting submodel " . $a['model']['id'] . ".");
        // Establish the right model.
        $modelName = $a['model']['id'];
        // Alias old images tables to new media tables.
        $modelName = preg_replace('/^([a-z_]+)_image$/', '${1}_medium', $modelName);
        $m = ORM::factory($modelName);
        // Set the correct parent key in the subModel.
        $fkId = $a['fkId'];
        // Inform the child if the parent is actually changing.
        $m->parentChanging = $this->wantToUpdateMetadata;
        if (isset($a['fkField'])) {
          $a['model']['fields'][$fkId] = ['value' => $this->{$a['fkField']}];
        }
        else {
          $a['model']['fields'][$fkId]['value'] = $this->id;
        }
        // Copy any request fields.
        if (isset($a['copyFields'])) {
          foreach ($a['copyFields'] as $from => $to) {
            Kohana::log("debug", "Setting $to field (from parent record $from field) to value " . $this->$from);
            $a['model']['fields'][$to]['value'] = $this->$from;
          }
        }
        // Call the submit method for that model and check whether it returns
        // correctly.
        $m->submission = $a['model'];
        // Copy down the website id and survey id.
        $m->identifiers = array_merge($this->identifiers);
        $result = $m->inner_submit();
        $this->nestedChildModelIds[] = $m->getSubmissionResponseMetadata();
        if ($m->wantToUpdateMetadata && !$this->wantToUpdateMetadata && preg_match('/_(image|medium|value)$/', $m->object_name)) {
          // We didn't update the parent's metadata. But a child image or
          // attribute value has been changed, so we want to update the parent
          // record metadata. I.e. adding an image to a record causes the
          // record to be edited and therefore to get its status reset.
          $this->wantToUpdateMetadata = TRUE;
          $this->set_metadata();
          $this->validate(new Validation($this->as_array()), TRUE);
        }

        if (!$result) {
          $fieldPrefix = (array_key_exists('field_prefix', $a['model'])) ? $a['model']['field_prefix'] . ':' : '';
          // Remember this model so that its errors can be reported.
          foreach ($m->errors as $field => $value) {
            $this->errors[$fieldPrefix . $field] = $value;
          }
          $r = FALSE;
        }
        elseif (!preg_match('/^\d+$/', $key)) {
          // sub-model list is an associative array. This means there might be references
          // to these keys elsewhere in the submission. Basically dynamic references to
          // rows which don't yet exist.
          $this->dynamicRowIdReferences["$modelName:$key"] = $m->id;
        }
      }
    }
    return $r;
  }

  /**
   * Generate any records that represent joins from this model to another.
   */
  private function createJoinRecords() {
    if (array_key_exists('joinsTo', $this->submission)) {
      foreach ($this->submission['joinsTo'] as $model => $ids) {
        // $ids is now a list of the related ids that should be linked to this model via
        // a join table.
        $table = inflector::plural($model);
        // Get the list of ids that are missing from the current state.
        $to_add = array_diff($ids, $this->$table->as_array());
        // Get the list of ids that are currently joined but need to be
        // disconnected.
        $to_delete = array_diff($this->$table->as_array(), $ids);
        $joinModel = inflector::singular($this->join_table($table));
        // Remove any joins that are to records that should no longer be joined.
        foreach ($to_delete as $id) {
          // @todo: This could be optimised by not using ORM to do the deletion.
          $delModel = ORM::factory($joinModel,
            array($this->object_name.'_id' => $this->id, $model.'_id' => $id));
          $delModel->delete();
        }
        // And add any new joins
        foreach ($to_add as $id) {
          $addModel = ORM::factory($joinModel);
          $addModel->validate(new Validation(array(
              $this->object_name.'_id' => $this->id, $model.'_id' => $id
          )), TRUE);
        }
      }
      $this->save();
    }
    return TRUE;
  }

  /**
   * Function that iterates through the required attributes of the current model, and
   * ensures that each of them has a submodel in the submission.
   */
  private function checkRequiredAttributes() {
    $r = TRUE;
    $typeFilter = NULL;
    // Test if this model has an attributes sub-table. Also to have required attributes, we must be posting into a
    // specified survey or website at least.
    if ($this->has_attributes) {
      $got_values = [];
      $empties = [];
      if (isset($this->submission['metaFields'][$this->attrs_submission_name]))
      {
        // Old way of submitting attribute values but still supported - attributes are stored in a metafield. Find the ones we actually have a value for
        // Provided for backwards compatibility only
        foreach ($this->submission['metaFields'][$this->attrs_submission_name]['value'] as $attr) {
          if ($attr['fields']['value']) {
            array_push($got_values, $attr['fields'][$this->object_name.'_attribute_id']);
          }
        }
        // check for location type or sample method which can be used to filter the attributes available
        foreach($this->submission['fields'] as $field => $content)
          // if we have a location type or sample method, we will use it as a filter on the attribute list
          if ($field === 'location_type_id' || $field === 'sample_method_id')
            $typeFilter = $content['value'];
      } else {
        // New way of submitting attributes embeds attr values direct in the main table submission values.
        foreach($this->submission['fields'] as $field => $content) {
          // look for pattern smpAttr:(fk_)nn (or occAttr, taxAttr, trmAttr, locAttr, srvAttr or psnAttr)
          $isAttribute = preg_match('/^'.$this->attrs_field_prefix.'\:(fk_)?[0-9]+/', $field, $baseAttrName);
          if ($isAttribute) {
            // extract the nn, this is the attribute id
            preg_match('/[0-9]+/', $baseAttrName[0], $attrId);
            if (isset($content['value']) && $content['value'] !== '')
              array_push($got_values, $attrId[0]);
            else {
              // keep track of the empty field names, so we can attach any required validation errors
              // directly to the exact field name
              $empties[$baseAttrName[0]] = $field;
            }
          }
          // if we have a location type or sample method, we will use it as a filter on the attribute list
          if ($field === 'location_type_id' || $field === 'sample_method_id')
            $typeFilter = $content['value'];
        }
      }
      $fieldPrefix = (array_key_exists('field_prefix', $this->submission)) ? $this->submission['field_prefix'] . ':' : '';
      // as the required fields list is relatively static, we use the cache. This cache entry gets cleared when
      // a custom attribute is saved so it should always be up to date.
      $key = $this->getRequiredFieldsCacheKey($typeFilter);
      $result = $this->cache->get($key);
      if ($result === NULL) {
        // Setup basic query to get custom attrs.
        $result = $this->getAttributes(TRUE, $typeFilter);
        $this->cache->set($key, $result, array('required-fields'));
      }

      foreach($result as $row) {
        if (!in_array($row->id, $got_values)) {
          // There is a required attr which we don't have a value for the submission for. But if posting an existing occurrence, the
          // value may already exist in the db, so only validate any submitted blank attributes which will be in the empties array and
          // skip any attributes that were not in the submission.
          $fieldname = $fieldPrefix.$this->attrs_field_prefix.':'.$row->id;
          if (empty($this->submission['fields']['id']['value']) || isset($empties[$fieldname])) {
            // map to the exact name of the field if it is available
            if (isset($empties[$fieldname])) $fieldname = $empties[$fieldname];
            $this->errors[$fieldname] = "Please specify a value for the $row->caption.";
            kohana::log('debug', 'No value for ' . $row->caption . ' in ' . print_r($got_values, TRUE));
            $r=FALSE;
          }
        }
      }
    }
    return $r;
  }

  /**
   * Default implementation of a method which retrieves the cache key required to store the list
   * of required fields. Override when there are other values which define the required fields
   * in the cache, e.g. for people each combination of website IDs defines a cache entry.
   * @param type $typeFilter
   * @return string The cache key.
   */
  protected function getRequiredFieldsCacheKey($typeFilter) {
    $keyArr = array_merge(['required', $this->object_name], $this->identifiers);
    if ($typeFilter) {
      $keyArr[] = $typeFilter;
    }
    return implode('-', $keyArr);
  }

  /**
   * Gets the list of custom attributes for this model.
   *
   * This is just a default implementation for occurrence & sample attributes
   * which can be overridden if required.
   *
   * @param bool $required
   *   Optional. Set to TRUE to only return required attributes (requires the
   *   website and survey identifier to be set).
   * @param int $typeFilter
   *   Specify a location type meaning id or a sample method meaning id to
   *   filter the returned attributes to those which apply to the given type or
   *   method.
   * @param bool $hasSurveyRestriction
   *   TRUE if this objects attributes can be restricted to survey scope.
   */
  protected function getAttributes($required = FALSE, $typeFilter = NULL, $hasSurveyRestriction = TRUE) {
    if (empty($this->identifiers['website_id']) && empty($this->identifiers['taxon_list_id'])) {
      return [];
    }
    $cacheId = "list-attrs-$this->object_name-" .
      ($this->identifiers['website_id'] ?? '') . '-' .
      ($this->identifiers['survey_id'] ?? '') . '-' .
      ($this->identifiers['taxon_list_id'] ?? '') . '-' .
      ($required ? 't' : 'f') .
      $typeFilter;
    $cache = Cache::instance();
    $attrs = $cache->get($cacheId);
    if ($attrs === NULL) {
      $attrEntity = $this->object_name . '_attribute';
      $attrTable = inflector::plural($this->object_name . '_attribute');

      $this->db->select("$attrTable.id", "$attrTable.caption", "$attrTable.data_type");
      $this->db->from($attrTable);
      $this->db->where("$attrTable.deleted", 'f');
      if ((!empty($this->identifiers['website_id']) || !empty($this->identifiers['survey_id']))
          && $this->db->table_exists("{$attrTable}_websites")) {
        $this->db->join("{$attrTable}_websites", "{$attrTable}_websites.{$attrEntity}_id", "$attrTable.id");
        $this->db->where("{$attrTable}_websites.deleted", 'f');
        if (!empty($this->identifiers['website_id'])) {
          $this->db->where("{$attrTable}_websites.website_id", $this->identifiers['website_id']);
        }
        if (!empty($this->identifiers['survey_id']) && $hasSurveyRestriction) {
          $this->db->in("{$attrTable}_websites.restrict_to_survey_id", [$this->identifiers['survey_id'], NULL]);
        }
        // Note we concatenate the validation rules to check both global and
        // website specific rules for requiredness.
        if ($required) {
          $this->db->where("({$attrTable}_websites.validation_rules like '%required%' or {$attrTable}.validation_rules like '%required%')");
        }
        // Ensure that only attrs for the record's sample method or location
        // type, or unrestricted attrs, are returned.
        if ($this->object_name === 'location' || $this->object_name === 'sample') {
          if ($this->object_name === 'location') {
            $this->db->join('termlists_terms as tlt', 'tlt.id',
                'location_attributes_websites.restrict_to_location_type_id', 'left');
          }
          elseif ($this->object_name === 'sample') {
            $this->db->join('termlists_terms as tlt', 'tlt.id',
                'sample_attributes_websites.restrict_to_sample_method_id', 'left');
          }
          $this->db->join('termlists_terms as tlt2', 'tlt2.meaning_id', 'tlt.meaning_id', 'left');
          $ttlIds = [NULL];
          if ($typeFilter) {
            $ttlIds[] = $typeFilter;
          }
          $this->db->in('tlt2.id', $ttlIds);
        }
        // For taxon or stage restrictions, the attributes are not loaded into
        // the entry form unless the correct taxon/stage are chosen. Therefore
        // we don't enforce the required state of these fields on the server
        // and instead allow it to be enforced on the client.
        if ($required && ($this->object_name === 'sample' || $this->object_name === 'occurrence')) {
          $this->db->join("{$attrEntity}_taxon_restrictions AS tr", "tr.{$attrTable}_website_id", "{$attrTable}_websites.id", 'LEFT');
          $this->db->where("tr.id IS NULL");
        }
      }
      elseif (!empty($this->identifiers['taxon_list_id']) && $this->object_name === 'taxa_taxon_list') {
        $this->db->join('taxon_lists_taxa_taxon_list_attributes', "taxon_lists_taxa_taxon_list_attributes.{$attrEntity}_id", "$attrTable.id");
        $this->db->where('taxon_lists_taxa_taxon_list_attributes.deleted', 'f');
        $this->db->where('taxon_lists_taxa_taxon_list_attributes.taxon_list_id', $this->identifiers['taxon_list_id']);
      }
      elseif ($required) {
        $this->db->like("$attrTable.validation_rules", '%required%');
      }
      $this->db->orderby("$attrTable.caption", 'ASC');
      $attrs = $this->db->get()->result_array(TRUE);
      $cache->set($cacheId, $attrs, ['attribute-lists']);
    }
    return $attrs;
  }

  /**
   * Returns an array of fields that this model will take when submitting.
   * By default, this will return the fields of the underlying table, but where
   * supermodels are involved this may be overridden to include those also.
   *
   * When called with true, this will also add fk_ columns for any _id columns
   * in the model unless the column refers to a model in the submission structure
   * supermodels list. For example, when adding an occurrence via import, you supply
   * the fields for the sample to create rather than a lookup value for the existing
   * samples.
   *
   * @param bool $fk
   *   If TRUE, then foreign key ID columns are replaced by FK lookups.
   * @param bool $keepFkIds
   *   IF TRUE and $fk is TRUE, then the foreign key lookups are provided in
   *   addition to the foreign key ID fields, not as a replacement for.
   * @param array $identifiers
   *   Website ID, survey ID and/or taxon list ID that define the context of
   *   the list of fields, used to determine the custom attributes to include.
   * @param int $attrTypeFilter
   *   Specify a location type meaning id or a sample method meaning id to
   *   filter the returned attributes to those which apply to the given type or
   *   method.
   *
   * @return array
   *   The list of submittable field definitions.
   */
  public function getSubmittableFields($fk = FALSE, $keepFkIds = FALSE, array $identifiers = [], $attrTypeFilter = NULL, $use_associations = FALSE) {
    $this->identifiers = $identifiers;
    $fields = $this->getPrefixedColumnsArray($fk, $keepFkIds);
    $additionals = array_filter($this->additional_csv_fields, function($k) use ($fk) {
      return $fk || !preg_match('/^(fk_|.+:fk_)/', $k);
    }, ARRAY_FILTER_USE_KEY);
    $fields = array_merge($fields, $additionals);
    ksort($fields);
    if ($this->has_attributes) {
      $result = $this->getAttributes(FALSE, $attrTypeFilter);
      foreach ($result as $row) {
        if ($row->data_type == 'L' && $fk) {
          // Lookup lists store a foreign key
          $fieldname = $this->attrs_field_prefix . ':fk_' . $row->id;
        }
        else {
          $fieldname = $this->attrs_field_prefix . ':' . $row->id;
        }
        $fields[$fieldname] = $row->caption;
      }
    }
    $struct = $this->get_submission_structure();
    if (array_key_exists('superModels', $struct)) {
      // Currently can only have associations if a single superModel exists.
      if ($use_associations && count($struct['superModels']) === 1) {
        // Duplicate all the existing fields, but rename adding a 2 to model end.
        $newFields = [];
        foreach ($fields as $name => $caption){
          $parts = explode(':', $name);
          if ($parts[0] == $struct['model'] || $parts[0] == $struct['model'] . '_image' || $parts[0] == $this->attrs_field_prefix) {
            $parts[0] .= '_2';
            $newFields[implode(':', $parts)] = ($caption != '' ? $caption .' (2)' : '');
          }
        }
        $fields = array_merge($fields, ORM::factory($struct['model'] . '_association')->getSubmittableFields($fk, $keepFkIds, $identifiers, NULL, FALSE));
        $fields = array_merge($fields, $newFields);
      }
      foreach ($struct['superModels'] as $super => $content) {
        $fields = array_merge($fields, ORM::factory($super)->getSubmittableFields($fk, $keepFkIds, $identifiers, $attrTypeFilter, FALSE));
      }
    }
    if (array_key_exists('metaFields', $struct)) {
      foreach ($struct['metaFields'] as $metaField) {
        $fields["metaFields:$metaField"] = '';
      }
    }
    return $fields;
  }

  /**
   * Retrieves a list of the required fields for this model and its related models.
   *
   * @param bool $fk
   *   True if foreign key field types (fk_*) should be returned where
   *   appropriate.
   * @param array $identifiers
   *   Website ID, survey ID and/or taxon list ID that define the context of
   *   the list of fields, used to determine the custom attributes to include.
   * @param bool $use_associations
   *   TRUE if occurrence associations data included.
   *
   * @return array
   *   List of the fields which are required.
   */
  public function getRequiredFields($fk = FALSE, array $identifiers = [], $use_associations = FALSE) {
    $this->identifiers = $identifiers;
    $sub = $this->get_submission_structure();
    $arr = new Validation(['id' => 1]);
    $this->validate($arr, FALSE);
    $fields = [];
    foreach ($arr->errors() as $column => $error) {
      if ($error == 'required') {
        if ($fk && substr($column, -3) == "_id") {
          // Don't include the fk link field if the submission is supposed to
          // contain full data for the supermodel record rather than just a
          // link.
          if (!isset($sub['superModels'][substr($column, 0, -3)])) {
            $fields[] = $this->object_name . ":fk_" . substr($column, 0, -3);
          }
        }
        else {
          $fields[] = $this->object_name . ":$column";
        }
      }
    }
    if ($this->has_attributes) {
      $result = $this->getAttributes(TRUE);
      foreach ($result as $row) {
        if ($row->data_type == 'L' && $fk) {
          // Lookup lists store a foreign key.
          $fields[] = "$this->attrs_field_prefix:fk_$row->id";
        }
        else {
          $fields[] = "$this->attrs_field_prefix:$row->id";
        }
      }
    }

    if (array_key_exists('superModels', $sub)) {
      // Currently can only have associations if a single superModel exists.
      if ($use_associations && count($sub['superModels']) === 1) {
        // Duplicate all the existing fields, but rename adding a 2 to model
        // end.
        $newFields = [];
        foreach ($fields as $id) {
          $parts = explode(':', $id);
          if ($parts[0] == $sub['model'] || $parts[0] == $sub['model'] . '_image' || $parts[0] == $this->attrs_field_prefix) {
            $parts[0] .= '_2';
            $newFields[] = implode(':', $parts);
          }
        }
        $fields = array_merge($fields, $newFields);
        $fields = array_merge($fields, ORM::factory($sub['model'] . '_association')->getRequiredFields($fk, $identifiers, FALSE));
      }

      foreach ($sub['superModels'] as $super => $content) {
        $fields = array_merge($fields, ORM::factory($super)->getRequiredFields($fk, $identifiers, FALSE));
      }
    }
    return $fields;
  }

  /**
   * Returns the array of values, with each key prefixed by the model name then :.
   *
   * @param string $prefix
   *   Optional prefix, only required when overriding the model name being used
   *   as a prefix.
   *
   * @return array
   *   Prefixed key value pairs.
   */
  public function getPrefixedValuesArray($prefix = NULL) {
    $r = [];
    if (!$prefix) {
      $prefix = $this->object_name;
    }
    foreach ($this->as_array() as $key => $val) {
      $r["$prefix:$key"] = $val;
    }
    return $r;
  }

  /**
   * Return the columns array, each column prefixed by the model name then :.
   *
   * @param bool $fk
   *   If TRUE, then foreign key ID columns are replaced by FK lookups.
   * @param bool $keepFkIds
   *   IF TRUE and $fk is TRUE, then the foreign key lookups are provided in
   *   addition to the foreign key ID fields, not as a replacement for.
   * @param bool $skipHiddenFields
   *   If TRUE, then any hidden fields are skipped and not included in the
   *   returned array.
   *
   * @return array
   *   Prefixed columns.
   */
  private function getPrefixedColumnsArray($fk = FALSE, $keepFkIds = FALSE, $skipHiddenFields = TRUE) {
    $r = [];
    $prefix = $this->object_name;
    $sub = $this->get_submission_structure();
    foreach ($this->table_columns as $column => $type) {
      if ($skipHiddenFields && isset($this->hidden_fields) && in_array($column, $this->hidden_fields)) {
        continue;
      }
      if ($fk && substr($column, -3) == "_id") {
        // Don't include the fk link field if the submission is supposed to
        // contain full data for the supermodel record rather than just a link.
        if (!isset($sub['superModels'][substr($column, 0, -3)])) {
          $r["$prefix:fk_" . substr($column, 0, -3)] = '';
          if ($keepFkIds) {
            // If we are keeping the ID field, then add it too.
            $r["$prefix:$column"] = '';
          }
        }
      }
      else {
        $r["$prefix:$column"] = '';
      }
    }
    return $r;
  }

 /**
  * Create the records for any attributes attached to the current submission.
  *
  * @param bool $isInsert
  *   TRUE for when the parent of the attributes is a fresh insert, FALSE for
  *   an update.
  *
  * @return bool
  *   TRUE if success.
  */
  protected function createAttributes($isInsert) {
    if ($this->has_attributes) {
      // Deprecated submission format attributes are stored in a metafield.
      if (isset($this->submission['metaFields'][$this->attrs_submission_name])) {
        return self::createAttributesFromMetafields();
      }
      else {
        // Loop to find the custom attributes embedded in the table fields.
        $attrs = [];
        foreach ($this->submission['fields'] as $field => $content) {
          if (preg_match('/^'.$this->attrs_field_prefix.':(fk_)?[\d]+(:([\d]+)?(:[^:|upper]*)?)?$/', $field)) {
            $value = $content['value'];
            if (is_null($value)) {
              // The mobile apps do this and it upsets PHP8.1 later on.
              // Just skip attributes submitted with a null value.
              continue;
            }
            // Attribute name is of form tblAttr:attrId:valId:uniqueIdx
            $arr = explode(':', $field);
            $attrId = $arr[1];
            $valueId = count($arr)>2 ? $arr[2] : NULL;
            $attrDef = $this->loadAttrDef($this->object_name, $attrId);
            if ($attrDef->allow_ranges === 't' && !empty($this->submission['fields']["$field:upper"])
                && !empty($this->submission['fields']["$field:upper"]['value'])) {
              $value .= ' - ' . $this->submission['fields']["$field:upper"]['value'];
            }
            $allowTermCreationLang = empty($this->submission['fields']["$this->attrs_field_prefix:$attrId:allowTermCreationLang"])
              ? NULL : $this->submission['fields']["$this->attrs_field_prefix:$attrId:allowTermCreationLang"]['value'];
            $attr = $this->createAttributeRecord($attrId, $valueId, $value, $attrDef, $allowTermCreationLang);
            if ($attr === FALSE) {
              // Failed to create attribute so drop out.
              return FALSE;
            }
            // If this attribute is a multivalue array, then any existing
            // attributes which are not in the submission for the same attr ID
            // should be removed. We need to keep an array of the multi-value
            // attribute IDs, with a sub-array for the existing value IDs that
            // were included in the submission, so that we can mark-delete the
            // ones that are not in the submission.
            if ($attrDef->multi_value === 't' && count($arr)) {
              if (substr($attrId, 0, 3) == 'fk_') {
                $attrId = substr($attrId, 3);
              }
              if (!isset($multiValueData["attr:$attrId"])) {
                $multiValueData["attr:$attrId"] = array('attrId' => $attrId, 'ids' => []);
              }
              $multiValueData["attr:$attrId"]['ids'] = array_merge($multiValueData["attr:$attrId"]['ids'], $attr);
            }
          }
        }
        // Delete any old values from a mult-value attribute. No need to worry
        // for inserting new records.
        if (!$isInsert && !empty($multiValueData)) {
          // If we did any multivalue updates for existing records, then any
          // attributes whose values were not included in the submission must
          // be removed. Note that we may have more than one multivalue field
          // in the record, so process each.
          foreach ($multiValueData as $spec) {
            $this->db
              ->from($this->object_name.'_attribute_values')
              ->set([
                'deleted' => 't',
                'updated_on' => date("Ymd H:i:s"),
                'updated_by_id' => security::getUserId(),
              ])
              ->where([
                $this->object_name.'_attribute_id' => $spec['attrId'],
                $this->object_name.'_id' => $this->id,
                'deleted' => 'f'])
              ->notin('id', $spec['ids'])
              ->update();
          }
        }
      }
    }
    return TRUE;
  }

  /**
   * Up to Indicia v0.4, the custom attributes associated with a submission where held in a sub-structure of the submission
   * called metafields. This code is used to provide backwards compatibility with this submission format.
   */
  protected function createAttributesFromMetafields() {
    foreach ($this->submission['metaFields'][$this->attrs_submission_name]['value'] as $attr) {
      $value = $attr['fields']['value'];
      if ($value != '') {
        // work out the *_attribute this is attached to, to figure out the field(s) to store the value in.
        $attrId = $attr['fields'][$this->object_name.'_attribute_id'];
        // If this is an existing attribute value, get the record id to overwrite
        $valueId = (array_key_exists('id', $attr['fields'])) ? $attr['fields']['id'] : NULL;
        $attrDef = $this->loadAttrDef($this->object_name, $attrId);
        if ($this->createAttributeRecord($attrId, $valueId, $value, $attrDef) === FALSE) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  /**
   * Creates an attribute value record or records for a multi-value.
   *
   * @return mixed
   *   FALSE on fail, else array of attribute value IDs objects.
   */
  protected function createAttributeRecord($attrId, $valueId, $value, $attrDef, $allowTermCreationLang = NULL) {
    // There are particular circumstances when $value is actually an array: when a attribute is multi value,
    // AND has yet to be created, AND is passed in as multiple ***Attr:<n>[] POST variables. This should only happen when
    // the attribute has yet to be created, as after this point the $valueID is filled in and that specific attribute POST variable
    // is no longer multivalue - only one value is stored per attribute value record, though more than one record may exist
    // for a given attribute. There may be others with th same <n> without a $valueID.
    // If attrId = fk_* (e.g. when importing data) then the value is a term whose id needs to be looked up.
    if (is_array($value)) {
      if (is_null($valueId)) {
        $r = [];
        foreach($value as $singlevalue) { // recurse over array.
          $attrIds = $this->createAttributeRecord($attrId, $valueId, $singlevalue, $attrDef, $allowTermCreationLang);
          if ($attrIds === FALSE) {
            return FALSE;
          }
          else {
            $r = array_merge($r, $attrIds);
          }
        }
        return $r;
      } else {
        $this->errors['general'] = "INTERNAL ERROR: multiple values passed in for $this->object_name $valueId " . print_r($value, TRUE);
        return FALSE;
      }
    }
    $fk = FALSE;
    $value = trim($value);
    if (substr($attrId, 0, 3) == 'fk_') {
      // value is a term that needs looking up
      $fk = TRUE;
      $attrId = substr($attrId, 3);
    }
    // Create a attribute value, loading the existing value id if it exists, or search for the existing record
    // if not multivalue but no id supplied and not a new record
    // @todo: Optimise attribute saving by using query builder rather than ORM
    if (!empty($this->attrValModels[$this->object_name])) {
      $attrValueModel = $this->attrValModels[$this->object_name];
      $attrValueModel->clear();
      $attrValueModel->wantToUpdateMetadata = TRUE;
    } else {
      $attrValueModel = ORM::factory($this->object_name . '_attribute_value');
      $this->attrValModels[$this->object_name] = $attrValueModel;
    }
    if (!empty($valueId)) {
      // If we know the value ID, load the model.
      $attrValueModel->find($valueId);
    }
    elseif ($this->existing && $attrDef->multi_value === 'f') {
      // If we don't know the ID, but an existing record, then we can search
      // for the existing value as there should only be one.
      $attrValueModel->where([
        $this->object_name . '_attribute_id' => $attrId,
        $this->object_name . '_id' => $this->id,
        'deleted' => 'f',
      ])->find();
    }

    $oldValues = array_merge($attrValueModel->as_array());
    $dataType = $attrDef->data_type;
    $vf = NULL;

    $fieldPrefix = (array_key_exists('field_prefix', $this->submission)) ? $this->submission['field_prefix'] . ':' : '';
    // For attribute value errors, we need to report e.g smpAttr:attrId[:attrValId] as the error key name, not
    // the table and field name as normal.
    $fieldId = $fieldPrefix . $this->attrs_field_prefix . ':' . $attrId;
    if ($attrValueModel->id) {
      $fieldId .= ':' . $attrValueModel->id;
    }

    switch ($dataType) {
      case 'T':
        $vf = 'text_value';
        break;

      case 'F':
        // Preseerve the value entered as text because, when converted to float,
        // we may lose trailing zeroes.
        $attrValueModel->text_value = $value;
        $vf = 'float_value';
        break;

      case 'D':
      case 'V':
        // Date.
        if (!empty($value)) {
          $vd = vague_date::string_to_vague_date($value);
          if ($vd) {
            $attrValueModel->date_start_value = $vd[0];
            $attrValueModel->date_end_value = $vd[1];
            $attrValueModel->date_type_value = $vd[2];
          }
          else {
            $this->errors[$fieldId] = "Invalid value $value for attribute ".$attrDef->caption;
            kohana::log('debug', "Could not accept value $value into date fields for attribute $fieldId.");
            return FALSE;
          }
        }
        else {
          $attrValueModel->date_start_value = NULL;
          $attrValueModel->date_end_value = NULL;
          $attrValueModel->date_type_value = NULL;
        }
        break;

      case 'G':
        $vf = 'geom_value';
        break;

      case 'B':
        // Boolean
        $vf = 'int_value';
        if (!empty($value)) {
          $lower = strtolower($value);
          if ($lower == 'false' || $lower == 'f' || $lower == 'no' || $lower == 'n' || $lower == 'off') {
            $value = 0;
          } elseif ($lower == 'true' || $lower == 't' || $lower == 'yes' || $lower == 'y' || $lower == 'on') {
            $value = 1;
          }
        }
        break;

      case 'L':
        // Lookup list.
        $vf = 'int_value';
        if (!empty($value)) {
          $creatingTerm = $allowTermCreationLang && substr($value, 0, 11) === 'createTerm:';
          if ($creatingTerm) {
            // Chop off prefix.
            $value = substr($value, 11);
          }
          // Find existing value.
          $r = $this->fkLookup([
            'fkTable' => 'lookup_term',
            'fkSearchField' => 'term',
            'fkSearchValue' => $value,
            'fkSearchFilterField' => 'termlist_id',
            'fkSearchFilterValue' => $attrDef->termlist_id,
          ]);
          if (($fk || $creatingTerm) && $r) {
            // Term lookup succeeded and we are submitting fk_field, or a
            // normal field that allows term creation. In the latter case
            // we use the lookup to avoid duplication.
            $value = $r;
          }
          elseif ($fk) {
            $this->errors[$fieldId] = "Invalid value $value for attribute ".$attrDef->caption;
            kohana::log('debug', "Could not accept value $value into field $vf  for attribute $fieldId.");
            return FALSE;
          }
          elseif ($creatingTerm) {
            $value = $this->db
              ->query("select insert_term(?, ?, null, ?, null);", [
                $value,
                $allowTermCreationLang,
                $attrDef->termlist_id,
              ])
              ->insert_id();
          }
        }
        break;

      default:
        // Integer.
        $vf = 'int_value';
        break;
    }
    if ($vf != NULL) {
      // If a numeric range value provided, split into 2 fields.
      if (($dataType == 'I' || $dataType === 'F') && $attrDef->allow_ranges === 't'
          && preg_match('/^(?P<from>-?\d+(\.\d+)?)\s*-\s*(?P<to>-?\d+(\.\d+)?)$/', $value, $match)) {
        $value = $match['from'];
        $attrValueModel->upper_value = security::xss_clean($match['to']);
        if ($attrValueModel->upper_value != $match['to']) {
          $this->errors[$fieldId] = "Invalid range $match[from] - $match[to] for attribute ".$attrDef->caption;
          kohana::log('debug', "Could not accept value $match[from] - $match[to] into field $vf for attribute $fieldId.");
          return FALSE;
        }
        elseif ((float) $value > (float) $attrValueModel->upper_value) {
          $this->errors[$fieldId] = "Invalid range $match[from] - $match[to]. Values are the wrong way round for attribute ".$attrDef->caption;
          kohana::log('debug', "Could not accept value $match[from] - $match[to] into field $vf for attribute $fieldId.");
          return FALSE;
        }
      }
      else {
        $attrValueModel->upper_value = NULL;
      }
      $attrValueModel->$vf = $dataType === 'T' ? security::xss_clean($value) : $value;
      // Test that ORM accepted the new value - it will reject if the wrong data type for example.
      // Use a string compare to get a proper test but with type tolerance.
      // A wkt geometry gets translated to a proper geom so this will look different - just check it is not empty.
      // A float may loose precision or trailing 0 - just check for small percentage difference
      if (strcmp((string) $attrValueModel->$vf, (string) $value) === 0 ||
          ($dataType === 'G' && !empty($attrValueModel->$vf))) {
        kohana::log('debug', "Accepted value $value into field $vf for attribute $fieldId.");
      }
      elseif ($dataType === 'F' && preg_match('/^-?\d+(\.\d+)?$/', $value)
          && abs($attrValueModel->$vf - $value) <= abs(0.00001 * $attrValueModel->$vf)) {
        kohana::log('alert', "Lost precision accepting value $value into field $vf for attribute $fieldId. Value=".$attrValueModel->$vf);
      } elseif ($dataType !== 'T') {
        // For non-text values, raise error if the value was not storable for
        // that data type.
        $this->errors[$fieldId] = "Invalid value $value for attribute ".$attrDef->caption;
        kohana::log('debug', "Could not accept value $value into field $vf for attribute $fieldId.");
        return FALSE;
      }
      else {
        kohana::log('debug', "Value $value was XSS cleaned to " . (string) $attrValueModel->$vf . " in field $vf for attribute $fieldId.");
      }
    }
    // Set metadata.
    $exactMatches = array_intersect_assoc($oldValues, $attrValueModel->as_array());
    // Which fields do we have in the submission?
    $fieldsWithValuesInSubmission = array_intersect_key($oldValues, $attrValueModel->as_array());
    // Hook to the owning entity (the sample, location, taxa_taxon_list or
    // occurrence).
    $thisFk = $this->object_name . '_id';
    $attrValueModel->$thisFk = $this->id;
    // Hook to the attribute.
    $attrFk = $this->object_name . '_attribute_id';
    $attrValueModel->$attrFk = $attrId;
    // We'll update metadata only if at least one of the fields have changed.
    $wantToUpdateAttrMetadata = count($exactMatches) !== count($fieldsWithValuesInSubmission);
    if (!$wantToUpdateAttrMetadata) {
      $attrValueModel->wantToUpdateMetadata = FALSE;
    }
    try {
      $v = $attrValueModel->validate(new Validation($attrValueModel->as_array()), TRUE);
    }
    catch (Exception $e) {
      $v = FALSE;
      $this->errors[$fieldId] = $e->getMessage();
      error_logger::log_error('Exception during validation', $e);
    }
    if (!$v) {
      foreach ($attrValueModel->errors as $key => $value) {
        // Concatenate the errors if more than one per field.
        $this->errors[$fieldId] = array_key_exists($fieldId, $this->errors) ? $this->errors[$fieldId] . '  ' . $value : $value;
      }
      return FALSE;
    }
    $attrValueModel->save();
    if ($wantToUpdateAttrMetadata && !$this->wantToUpdateMetadata) {
      // We didn't update the parent's metadata. But a custom attribute value
      // has changed, so it makes sense to update it now.
      $this->wantToUpdateMetadata = TRUE;
      $this->set_metadata();
      $this->validate(new Validation($this->as_array()), TRUE);
    }
    $this->nestedChildModelIds[] = $attrValueModel->getSubmissionResponseMetadata();

    return [$attrValueModel->id];
  }

  /**
   * Load the definition of an attribute from the database (cached)
   *
   * @param string $attrTable
   *   Attribute type name, e.g. sample or occurrence
   * @param int $attrId
   *   The ID of the attribute
   * @return object
   *   The definition of the attribute.
   *
   * @throws Exception When attribute ID not found.
   */
  protected function loadAttrDef($attrType, $attrId) {
    if (substr($attrId, 0, 3) == 'fk_') {
      // An attribute value lookup.
      $attrId = substr($attrId, 3);
    }
    // Cache ID includes 2 - version number, to prevent old cache records being
    // used after inclusion of allow_ranges field.
    $cacheId = "attrInfo.2_{$attrType}_{$attrId}";
    $this->cache = Cache::instance();
    $attr = $this->cache->get($cacheId);
    if (!is_object($attr)) {
      $attr = $this->db
        ->select('caption', 'data_type', 'multi_value', 'termlist_id', 'validation_rules', 'allow_ranges')
        ->from($attrType . '_attributes')
        ->where(['id' => $attrId])
        ->get()->current();
      if (!is_object($attr)) {
        throw new Exception("Invalid $attrType attribute ID $attrId");
      }
      $this->cache->set($cacheId, $attr);
    }
    return $attr;
  }

  /**
   * Overrideable function to allow some models to handle additional records created on submission.
   *
   * @param bool
   *   True if this is a new inserted record, false for an update.
   *
   * @return bool
   *   True if successful.
   */
  protected function postSubmit($isInsert) {
    return TRUE;
  }

  /**
   * Accessor for children.
   * @return string The children in this model or an empty string.
   */
  public function getChildren() {
    if (isset($this->ORM_Tree_children)) {
      return $this->ORM_Tree_children;
    } else {
      return '';
    }
  }

  /**
   * Set the submission data for the model using an associative array (normally the
   * form post data). The submission is built as a wrapped structure ready to be
   * saved.
   *
   * @param array $array
   *   Associative array of data to submit.
   * @param bool $fklink
   */
  public function set_submission_data($array, $fklink = FALSE) {
    $this->submission = $this->wrap($array, $fklink);
  }

  /**
   * Wraps a standard $_POST type array into a save array suitable for use in saving
   * records.
   *
   * @param array $array
   *  Array to wrap
   * @param bool $fkLink
   *   Link foreign keys?
   *
   * @return array
   *   Wrapped array.
   */
  protected function wrap($array, $fkLink = FALSE) {
    // share the wrapping library with the client helpers
    require_once(DOCROOT.'client_helpers/submission_builder.php');
    $r = submission_builder::build_submission($array, $this->get_submission_structure());
    // Map fk_* fields to the looked up id.
    if ($fkLink) {
      $r = $this->getFkFields($r, $array);
    }
    if (array_key_exists('superModels', $r)) {
      $idx = 0;
      foreach ($r['superModels'] as $super) {
        $r['superModels'][$idx]['model'] = $this->getFkFields($super['model'], $array);
        $idx++;
      }
    }
    return $r;
  }

  /**
   * Converts any fk_* fields in a save array into the fkFields structure ready to be looked up.
   * [occ|smp|loc|srv|psn]Attr:fk_* are looked up in createAttributeRecord()
   *
   * @param $submission array
   *   Submission containing the foreign key field definitions to convert
   * @param $saveArray array
   *   Original form data being wrapped, which can contain filters to operate
   *   against the lookup table of the form fkFilter:table:field=value.
   *
   * @return array
   *   The submission structure containing the fkFields element.
   */
  public function getFkFields($submission, $saveArray) {
    if ($this->object_name != $submission['id']) {
      $submissionModel = ORM::Factory($submission['id'], -1);
    }
    else {
      $submissionModel = $this;
    }
    foreach ($submission['fields'] as $field => $value) {
      if (substr($field, 0, 3) === 'fk_') {
        // This field is a fk_* field which contains the text caption of a record which we need to lookup.
        // First work out the model to lookup against. The format is fk_{fieldname}(:{search field override})?
        $fieldTokens = explode(':', substr($field, 3));
        $fieldName = $fieldTokens[0];
        if (array_key_exists($fieldName, $submissionModel->belongs_to)) {
          $fkTable = $submissionModel->belongs_to[$fieldName];
        }
        elseif (array_key_exists($fieldName, $submissionModel->has_one)) { // this ignores the ones which are just models in list: the key is used to point to another model
          $fkTable = $submissionModel->has_one[$fieldName];
        }
        elseif ($submissionModel instanceof ORM_Tree && $fieldName == 'parent') {
          $fkTable = inflector::singular($submissionModel->getChildren());
        }
        else {
           $fkTable = $fieldName;
        }
        // Create model without initialising, so we can just check the lookup variables
        $fkModel = ORM::Factory($fkTable, -1);
        // allow the linked lookup field to override the default model search field
        if (count($fieldTokens)>1)
          $fkModel->search_field = $fieldTokens[1];
        // let the model map the lookup against a view if necessary
        $lookupAgainst = isset($fkModel->lookup_against) ? $fkModel->lookup_against : $fkTable;
        // Generate a foreign key instance
        $submission['fkFields'][$field] = [
          // Foreign key id field is table_id
          'fkIdField' => "$fieldName"."_id",
          'fkTable' => $lookupAgainst,
          'fkSearchField' => $fkModel->search_field,
          'fkSearchValue' => trim($value['value'] ?? ''),
          'readableTableName' => ucfirst(preg_replace('/[\s_]+/', ' ', $fkTable)),
          'fkExcludeDeletedRecords' => ($lookupAgainst === $fkTable),
        ];
        $struct = $submissionModel->get_submission_structure();
        // if the save array defines a filter against the lookup table then also store that.
        // 2 formats: field level or table level : "fkFilter:[fieldname|tablename]:[column]=[value]
        // E.g. a search in the taxa_taxon_list table may want to filter by the taxon list. This is done
        // by adding a value such as fkFilter:taxa_taxon_list:taxon_list_id=2.
        // Search through the save array for a filter value
        foreach ($saveArray as $filterfield=>$filtervalue) {
          if (substr($filterfield, 0, strlen("fkFilter:$fieldName:")) == "fkFilter:$fieldName:" ||
              substr($filterfield, 0, strlen("fkFilter:$fkTable:")) == "fkFilter:$fkTable:") {
            // found a filter for this field or fkTable. So extract the field name as the 3rd part
            $arr = explode(':', $filterfield);
            $submission['fkFields'][$field]['fkSearchFilterField'] = $arr[2];
            // and remember the value
            $submission['fkFields'][$field]['fkSearchFilterValue'] = $filtervalue;
            }
        }
        // Alternative location is in the submission array itself:
        // this allows for multiple records with different filters, E.G. when submitting occurrences as associations,
        // may want different taxon lists, will be entered as occurrence<n>:fkFilter:<table>:<field> = <value>
        foreach ($submission['fields'] as $filterfield=>$filtervalue) {
          if (substr($filterfield, 0, strlen("fkFilter:$fieldName:")) == "fkFilter:$fieldName:" ||
                  substr($filterfield, 0, strlen("fkFilter:$fkTable:")) == "fkFilter:$fkTable:") {
            // found a filter for this field or fkTable. So extract the field name as the 3rd part
            $arr = explode(':', $filterfield);
            $submission['fkFields'][$field]['fkSearchFilterField'] = $arr[2];
            // and remember the value
            $submission['fkFields'][$field]['fkSearchFilterValue'] = $filtervalue;
          }
        }

      }
    }
    return $submission;
  }

  /**
   * Returns the structure which defines the relationship between the records that can
   * be submitted when submitting this model. This is the default, which just submits the
   * model and no related records, but it is overrideable to define more complex structures.
   *
   * @return array Submission structure array
   */
  public function get_submission_structure() {
    return ['model' => $this->object_name];
  }


  /**
   * Overrideable method allowing models to declare any default values for loading into a form
   * on creation of a new record.
   */
  public function getDefaults() {
    return [];
  }

  /**
  * Convert an array of field data (a record) into a sanitised version, with email and password hidden.
  */
  private function sanitise($array) {
    // make a copy of the array
    $r = $array;
    if (array_key_exists('password', $r)) $r['password'] = '********';
    if (array_key_exists('email', $r)) $r['email'] = '********';
    return $r;
  }

  /**
   * Override the ORM clear method to clean up errors and identifier tracking.
   */
  public function clear() {
    parent::clear();
    $this->errors = [];
    $this->identifiers = ['website_id' => NULL, 'survey_id' => NULL];
  }

  /**
   * Method which can be used in a model to add the validation rules required for a set of mandatory spatial fields (sref and system).
   * Although the geom field technically could also be set required here, because the models which call this should automatically
   * generate the geom when it is missing in their preSubmit methods, there is no need to report it as required.
   * @param $validation object The validation object to add rules to.
   * @param string $sref_field The sref field name.
   * @param string $sref_system_field The sref system field name.
   */
  public function add_sref_rules(&$validation, $sref_field, $sref_system_field) {
    $values = $validation->as_array();
    $validation->add_rules($sref_field, 'required');
    $validation->add_rules($sref_system_field, 'required');
    if (!empty($values[$sref_system_field])) {
      $system = $values[$sref_system_field];
      $validation->add_rules($sref_field, "sref[$system]");
      $validation->add_rules($sref_system_field, 'sref_system');
    }
  }

 /**
   * Override the ORM load_type method: modifies float behaviour.
   * Loads a value according to the types defined by the column metadata.
   *
   * @param   string $column Column name
   * @param   mixed $value Value to load
   * @return  mixed
   */
  protected function load_type($column, $value)
  {
    $type = gettype($value);
    if ($type == 'object' OR $type == 'array' OR ! isset($this->table_columns[$column]))
      return $value;

    // Load column data
    $column = $this->table_columns[$column];

    if ($value === NULL AND ! empty($column['null']))
      return $value;

    if ( ! empty($column['binary']) AND ! empty($column['exact']) AND (int) $column['length'] === 1)
    {
      // Use boolean for BINARY(1) fields
      $column['type'] = 'boolean';
    }

    switch ($column['type'])
    {
      case 'int':
        if ($value === '' AND ! empty($column['null']))
        {
          // Forms will only submit strings, so empty integer values must be null
          $value = NULL;
        }
        elseif ((float) $value > PHP_INT_MAX)
        {
          // This number cannot be represented by a PHP integer, so we convert it to a string
          $value = (string) $value;
        }
        else
        {
          $value = (int) $value;
        }
      break;
      case 'float':
        if ($value === '' AND ! empty($column['null']))
        {
          // Forms will only submit strings, so empty float values must be null
          $value = NULL;
        }
        else
        {
          $value = (float) $value;
        }
        break;
      case 'boolean':
        // Any database column of type 'boolean' is mapped to 'bool' in
        // application/helpers/postgreSQL.php::sql_type(), thanks to the setting
        // in application/config/sql_types.php.
        // As a consequence, this case does not arise and there is no PHP type
        // conversion except for binary fields from above.
        $value = (bool) $value;
      break;
      case 'bool':
        // Instead, we can submit any boolean representation acceptable to the
        // database. See
        // https://www.postgresql.org/docs/current/datatype-boolean.html
        // Integer values of 1/0 may arise from file importing and must be cast
        // to string.
        $value = (string) $value;
      case 'string':
        $value = (string) $value;
      break;
    }

    return $value;
  }

  /**
   * Build a sql query to fetch an existing record.
   *
   * Based on the contents of the save array.
   *
   * There are several possiblities:
   * The field list only refers to _id, not fk_
   * 1) There is a straight match between [<model>:]fieldname in the saveArray, and [<model>:]fieldname in the fields list.
   *    The where clause is just the fieldname => value from saveArray.
   * 2) There is a straight match between [<model>:]fieldname_id in the saveArray, and [<model>:]fieldname_id in the fields list.
   *    The where clause is just the fieldname_id => value from saveArray.
   * 3) There is a match between [<model>:]fk_fieldname[:lookupfield] in the saveArray, and [<model>:]fieldname_id in the fields list.
   *    The value in the saveArray is looked up, and the where clause is the fieldname_id => looked up value.
   * If the model is included in one, it must be included in the other.
   *
   * @todo Review this code as it doesn't handle lookup of existing records
   * where the filter field is not in the main table in a tidy way.
   */
  public function buildWhereFromSaveArray($saveArray, $fields, $wheres, &$join, $assocSuffix = "") {
    $wheresUpdated = FALSE;
    $struct = $this->get_submission_structure();
    $table = inflector::plural($this->object_name);
    if (isset($struct['joinsTo']) && in_array('websites', $struct['joinsTo'])) {
      $join = 'JOIN ' . inflector::plural($this->object_name) . "_websites w ON (w.website_id=" . (int) $saveArray['website_id'] .
          " AND w.{$this->object_name}_id = $table.id) ";
      $fields = array_diff( $fields, array('website_id') );
    } else {
      $join = "";
    }
    foreach ($fields as $field) {
      $fieldTokens = explode(':',$field);
      $prefix = ($fieldTokens[0] === $this->object_name . $assocSuffix ? $this->object_name . $assocSuffix . ':' : '');
      if ($fieldTokens[0] === $this->object_name . $assocSuffix) {
        array_shift($fieldTokens);
      }
      elseif (count($fieldTokens) > 1) { // different table specified.
        continue;
      }

      if (substr($fieldTokens[0], -3) !== '_id') {
          if (!isset($saveArray[$field])) {
            return FALSE;
          }
          $wheresUpdated = TRUE;
          if ($fieldTokens[0] === 'date') {
            $vd = vague_date::string_to_vague_date($saveArray[$field]);
            $wheres .= " AND ($table.date_start = '$vd[0]')";
            $wheres .= " AND ($table.date_end = '$vd[1]')";
            $wheres .= " AND ($table.date_type = '$vd[2]')";
          }
          else {
            $wheres .= " AND (".$table . "." . $fieldTokens[0] . " = '".$saveArray[$field]."')";
          }
      } else {
          // There is a possibility that we are looking for for a supermodel id
          // which is represented as an ID in the supermodel itself. At this
          // point the $correctedField ends in _id.
          $superModelIDField = substr($fieldTokens[0], 0, -3); // cut off _id
          if (isset($saveArray[$superModelIDField . ':id'])) {
            $wheresUpdated = TRUE;
            $wheres .= " AND ($table.$fieldTokens[0] = " . $saveArray[$superModelIDField . ':id'] . ')';
          }
          else {
            foreach ($saveArray as $saveField => $saveValue) {
              $saveTokens = explode(':', $saveField);
              if (($prefix !== '' && $saveTokens[0] === $this->object_name . $assocSuffix) || ($prefix === '' && $saveTokens[0] !== $this->object_name . $assocSuffix)) {
                if ($saveTokens[0] === $this->object_name . $assocSuffix) {
                  array_shift($saveTokens);
                }
                $correctedField = (substr($saveTokens[0], 0, 3) == 'fk_' ? substr($saveTokens[0], 3) . '_id' : $saveTokens[0]);
                if ($fieldTokens[0] === $correctedField) {
                  $wheresUpdated = TRUE;
                  if ($saveTokens[0] !== $correctedField) {
                    // saveTokens points to fk_, whilst corrected points to _id
                    // This field is a fk_* field which contains the text caption of a record which we need to lookup.
                    // First work out the model to lookup against. The format is fk_{fieldname}(:{search field override})?
                    // Create model without initialising, so we can just check the lookup variables
                    // allow the linked lookup field to override the default model search field
                    // let the model map the lookup against a view if necessary
                    $fieldName = substr($saveTokens[0],3);
                    if (array_key_exists($fieldName, $this->belongs_to)) {
                      $fkTable = $this->belongs_to[$fieldName];
                    }
                    elseif (array_key_exists($fieldName, $this->has_one)) {
                      // This ignores the ones which are just models in list:
                      // the key is used to point to another model.
                      $fkTable = $this->has_one[$fieldName];
                    }
                    elseif ($fieldName === 'parent'){
                      $fkTable = $this->object_name;
                    }
                    else {
                      $fkTable = $fieldName;
                    }
                    $fkModel = ORM::Factory($fkTable, -1);
                    if (count($saveTokens)>1)
                      $fkModel->search_field = $saveTokens[1];
                    $lookupAgainst = isset($fkModel->lookup_against) ? $fkModel->lookup_against : $fkTable;
                    $fkLookup = [
                        'fkTable' => $lookupAgainst,
                        'fkSearchField' => $fkModel->search_field,
                        'fkSearchValue' => trim($saveValue),
                        'fkExcludeDeletedRecords' => ($lookupAgainst === $fkTable),
//                              'fkWebsite' => $saveArray['website_id'],
                    ];
                    $struct = $fkModel->get_submission_structure();
                    if (isset($struct['joinsTo']) && in_array('websites', $struct['joinsTo'])) {
                      $fkLookup['fkWebsite'] = $saveArray['website_id'];
                    }
                    foreach ($saveArray as $filterfield=>$filtervalue) {
                      if (substr($filterfield, 0, strlen("fkFilter:$fieldName:")) == "fkFilter:$fieldName:" ||
                          substr($filterfield, 0, strlen("fkFilter:$fkTable:")) == "fkFilter:$fkTable:") {
                        // Found a filter for this field or fkTable. So extract
                        // the field name as the 3rd part.
                        $arr = explode(':', $filterfield);
                        if ($arr[0] === $this->object_name) {
                          array_shift($arr);
                        }
                        $fkLookup['fkSearchFilterField'] = $arr[2];
                        $fkLookup['fkSearchFilterValue'] = $filtervalue;
                      }
                    }
                    $fk = $this->fkLookup($fkLookup);
                    if ($fk) {
                      $wheres .= " AND ($table.$correctedField = '$fk')";
                    }
                  } else {
                    $wheres .= " AND ($table.$correctedField = '$saveValue')";
                  }
                }
              }
            }
            if (!$wheresUpdated) {
              return FALSE;
            }
          }
      }
    }
    return $wheres;
  }

  /**
   * Override __set to process array fields.
   *
   * * Incoming data for ORM int[] fields will be decoded as an array of
   *   strings. Change back to integers in order for the SQL value to be cast
   *   correctly by ORM.
   * * Single values submitted for array fields are converted to arrays.
   *
   * @param string $column
   *   Column name.
   * @param mixed $value
   *   Value to set.
   */
  public function __set($column, $value) {
    if (!empty($value) && !empty($this->table_columns[$column])) {
      $colDef = $this->table_columns[$column];
      if (!empty($colDef['array'])) {
        if (!is_array($value)) {
          $value = [$value];
        }
        if ($colDef['subtype'] === 'int') {
          foreach ($value as $i => $single) {
            $value[$i] = (int) $single;
          }
        }
      }
    }
    return parent::__set($column, $value);
  }

}