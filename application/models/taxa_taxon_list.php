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
 * Model class for the Taxa_Taxon_Lists table.
 */
class Taxa_taxon_list_Model extends Base_Name_Model {
  public $search_field = 'taxon';

  public $lookup_against = 'lookup_taxa_taxon_list';

  protected $belongs_to = [
    'taxon',
    'taxon_list',
    'taxon_meaning',
    'created_by' => 'user',
    'updated_by' => 'user',
  ];

  // Declare that this model has child attributes, and the name of the node in
  // the submission which contains them.
  protected $has_attributes = TRUE;
  protected $attrs_submission_name = 'taxAttributes';
  public $attrs_field_prefix = 'taxAttr';

  protected $ORM_Tree_children = 'taxa_taxon_lists';
  protected $list_id_field = 'taxon_list_id';

  // During an import it is possible to merge different columns in a CSV row to
  // make a database field.
  protected $additional_csv_fields = [
    // Extra lookup options.
    'taxon:taxon:genus' => 'Genus (builds binomial name)',
    'taxon:taxon:specific' => 'Specific name/epithet (builds binomial name)',
    'taxon:taxon:qualifier' => 'Qualifier (builds binomial name)',
  ];

  public $compoundImportFieldProcessingDefn = [
    'taxon genus + species + qualifier' => [
      'template' => '%s %s %s',
      'columns' => [
        'taxon:taxon:genus',
        'taxon:taxon:specific',
        'taxon:taxon:qualifier',
      ],
      'destination' => 'taxon:taxon',
    ],
  ];

  /**
   * Declare combinations of fields that can be used to lookup existing value.
   *
   * @var array
   */
  public $importDuplicateCheckCombinations = [
    [
      'description' => 'Species list and taxa taxon list ID',
      'fields' => [
        ['fieldName' => 'taxa_taxon_list:taxon_list_id'],
        ['fieldName' => 'taxa_taxon_list:id'],
        ['fieldName' => 'taxa_taxon_list:taxon_id', 'notInMappings' => TRUE],
      ],
    ],
    [
      'description' => 'Species list and taxon name',
      'fields' => [
        ['fieldName' => 'taxa_taxon_list:taxon_list_id'],
        ['fieldName' => 'taxon:taxon'],
        ['fieldName' => 'taxa_taxon_list:taxon_id', 'notInMappings' => TRUE],
      ],
    ],
    [
      'description' => 'Species list and taxon external key',
      'fields' => [
        ['fieldName' => 'taxa_taxon_list:taxon_list_id'],
        ['fieldName' => 'taxon:external_key'],
        ['fieldName' => 'taxa_taxon_list:taxon_id', 'notInMappings' => TRUE],
      ],
    ],
    [
      'description' => 'Species list and taxon search code',
      'fields' => [
        ['fieldName' => 'taxa_taxon_list:taxon_list_id'],
        ['fieldName' => 'taxon:search_code'],
        ['fieldName' => 'taxa_taxon_list:taxon_id', 'notInMappings' => TRUE],
      ],
    ],
    [
      'description' => 'Species list, parent taxon name and taxon name',
      'fields' => [
        ['fieldName' => 'taxa_taxon_list:taxon_list_id'],
        ['fieldName' => 'taxon:taxon'],
        ['fieldName' => 'taxa_taxon_list:taxon_id', 'notInMappings' => TRUE],
        ['fieldName' => 'taxa_taxon_list:parent_id'],
      ],
    ],
  ];

  public $process_synonyms = TRUE;

  /**
   * Does an update change fields which are in the occurrences cache tables?
   *
   * @var bool
   */
  private $updateAffectsOccurrenceCache = FALSE;

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('taxon_id', 'required');
    $array->add_rules('taxon_list_id', 'required');
    $array->add_rules('taxon_meaning_id', 'required');
#		$array->add_callbacks('deleted', array($this, '__dependents'));

    // Explicitly add those fields for which we don't do validation.
    $this->unvalidatedFields = [
      'taxonomic_sort_order',
      'parent_id',
      'deleted',
      'allow_data_entry',
      'preferred',
      'description',
      'common_taxon_id',
      'manually_entered',
    ];
    return parent::validate($array, $save);
  }

  /**
   * If we want to delete the record, we need to check that no dependents exist.
   */
  public function __dependents(Validation $array, $field) {
    if ($array['deleted'] == 'true') {
      $record = ORM::factory('taxa_taxon_list', $array['id']);
      if ($record->children->count() != 0) {
        $array->add_error($field, 'has_children');
      }
    }
  }

  /**
   * Return a displayable caption for the item.
   */
  public function caption() {
    if ($this->id) {
      return ($this->taxon_id != NULL ? $this->taxon->taxon : '');
    }
    else {
      return 'Taxon in List';
    }
  }

  /**
   * Handle tasks before submission.
   *
   * Check if any fields are being updated that imply the occurrence cache
   * tables will need an update.
   */
  protected function preSubmit() {
    // Call the parent preSubmit function.
    parent::preSubmit();
    // For existing records, need to assess if the occurrences cache data needs
    // a taxonomy refresh.
    if ($this->id) {
      $triggerFields = [
        'parent_id',
        'taxon_meaning_id',
      ];
      foreach ($triggerFields as $triggerField) {
        if (isset($this->submission['fields'][$triggerField]) && isset($this->submission['fields'][$triggerField]['value'])) {
          if ($this->submission['fields'][$triggerField]['value'] !== (string) $this->$triggerField) {
            $this->updateAffectsOccurrenceCache = TRUE;
            break;
          }
        }
      }
    }
  }

  /**
   * Overrides the postSubmit function.
   *
   * Adds in synonomies and common names as well as search codes. This only
   * applies when adding a preferred name, not a synonym or common name. Also
   * adds work_queue tasks if the update implies the occurrence cache data
   * need updating for this taxon.
   */
  protected function postSubmit($isInsert) {
    $result = TRUE;
    // If we have synonyms or common names in the submission, handle them.
    // Skip if not in the submission as may be an update not from the warehouse
    // UI which doesn't include them.
    if ($this->submission['fields']['preferred']['value'] == 't' && array_key_exists('metaFields', $this->submission)
        && (array_key_exists('commonNames', $this->submission['metaFields']) || array_key_exists('synonyms', $this->submission['metaFields']))) {
      if (array_key_exists('commonNames', $this->submission['metaFields'])) {
        $arrCommonNames = $this->parseRelatedNames(
            $this->submission['metaFields']['commonNames']['value'],
            'setCommonNameSubArray'
        );
      }
      else {
        $arrCommonNames = [];
      }
      Kohana::log("debug", "Number of common names is: " . count($arrCommonNames));
      if (array_key_exists('synonyms', $this->submission['metaFields'])) {
        $arrSyn = $this->parseRelatedNames(
          $this->submission['metaFields']['synonyms']['value'],
          'setSynonymSubArray'
        );
      }
      else {
        $arrSyn = [];
      }
      Kohana::log("debug", "Number of synonyms is: " . count($arrSyn));

      $arrSyn = array_merge($arrSyn, $arrCommonNames);

      Kohana::log("debug", "Looking for existing taxa with meaning $this->taxon_meaning_id");
      $existingSyn = $this->getSynonomy('taxon_meaning_id', $this->taxon_meaning_id);

      // Iterate through existing synonomies, discarding those that have been
      // deleted and removing existing ones from the list to add.
      foreach ($existingSyn as $syn) {
        // Is the taxon from the db in the list of synonyms?
        $key = str_replace('|', '', $syn->taxon->taxon) . '|' . $syn->taxon->language->iso . '|' . $syn->taxon->authority;
        if (array_key_exists($key, $arrSyn) && $this->submission['fields']['deleted']['value'] !== 't') {
          unset($arrSyn[$key]);
          Kohana::log("debug", "Known synonym: " . $syn->taxon->taxon . ', language ' . $syn->taxon->language->iso .
            ', Authority ' . $syn->taxon->authority);
        }
        elseif ($syn->taxon->language->iso !== 'lat' || $this->process_synonyms || $this->submission['fields']['deleted']['value'] == 't') {
          // Only delete if a common name (not latin) OR process_synonyms is
          // switched on, or the preferred name is being deleted.
          // Synonym not in new list has been deleted - remove it from the db.
          $syn->deleted = 't';
          $syn->updated_on = date("Ymd H:i:s");
          $syn->updated_by_id = security::getUserId();
          if ($this->common_taxon_id == $syn->taxon->id) {
            $this->common_taxon_id = NULL;
          }
          Kohana::log("debug", "Deleting synonym: " . $syn->taxon->taxon . ', language ' . $syn->taxon->language->iso .
            ', Authority ' . $syn->taxon->authority);
          $syn->save();
          unset($arrSyn[$key]);
        }
      }

      // $arraySyn should now be left only with those synonyms we wish to add
      // to the database.
      Kohana::log("debug", "Number of synonyms remaining to add: " . count($arrSyn));
      $sm = ORM::factory('taxa_taxon_list');
      foreach ($arrSyn as $key => $syn) {
        $sm->clear();
        $taxon = $syn['taxon'];
        $lang = $syn['lang'];
        $auth = $syn['auth'];

        // Wrap a new submission.
        Kohana::log("info", "Wrapping submission for synonym $taxon");

        $lang_id = ORM::factory('language')->where(['iso' => $lang])->find()->id;
        // If language not found, use english as the default. Future versions
        // may wish this to be user definable.
        $lang_id = $lang_id ? $lang_id : ORM::factory('language')->where(['iso' => 'eng'])->find()->id;
        // Copy the original post array to pick up the common things, first the
        // taxa_taxon_list data.
        $this->copySharedFieldsFromSubmission('taxa_taxon_list', $this->submission['fields'], [
          'description',
          'parent',
          'taxonomic_sort_order',
          'allow_data_entry',
          'taxon_list_id',
        ], $syn);

        // Next do the data in the taxon supermodel - we have to search for it
        // rather than rely on it being in a particular position in the list.
        foreach ($this->submission['superModels'] as $supermodel) {
          if ($supermodel['model']['id'] === 'taxon') {
            $this->copySharedFieldsFromSubmission('taxon', $supermodel['model']['fields'], [
              'description',
              'external_key',
              'search_code',
              'taxon_group_id',
              'taxon_rank_id',
            ], $syn);
            break;
          }
        }
        // Now update the record with specifics for this synonym.
        $syn['taxon:id'] = NULL;
        $syn['taxon:taxon'] = $taxon;
        $syn['taxon:authority'] = $auth;
        $syn['taxon:language_id'] = $lang_id;
        $syn['taxa_taxon_list:id'] = '';
        $syn['taxa_taxon_list:preferred'] = 'f';
        // Taxon meaning Id cannot be copied from the submission, since for new
        // data it is generated when saved.
        $syn['taxa_taxon_list:taxon_meaning_id'] = $this->taxon_meaning_id;
        $sub = $this->wrap($syn);
        // Don't resubmit the meaning record, again we can't rely on the order
        // of the supermodels in the list.
        foreach ($sub['superModels'] as $idx => $supermodel) {
          if ($supermodel['model']['id'] === 'taxon_meaning') {
            unset($sub['superModels'][$idx]);
            break;
          }
        }
        $sm->submission = $sub;
        if (!$sm->submit()) {
          $result = FALSE;
          foreach ($sm->errors as $key => $value) {
            $this->errors[$sm->object_name . ':' . $key] = $value;
          }
        }
        else {
          // If synonym is not latin (a common name), and we have no common name for this object, use it.
          if ($this->common_taxon_id == NULL && $syn['taxon:language_id'] != 2) {
            $this->common_taxon_id = $sm->taxon->id;
          }
        }
      }
      if ($result && array_key_exists('codes', $this->submission['metaFields'])) {
        $result = $this->saveCodeMetafields($this->submission['metaFields']['codes']);
      }
      if ($result && array_key_exists('parent_external_key', $this->submission['metaFields'])) {
        $result = $this->postSubmitLinkUsingParentExternalKey();
      }
      // Post the common name or parent id change if required.
      if (isset($this->changed['common_taxon_id']) || isset($this->changed['parent_id'])) {
        $this->save();
      }
    }
    $this->updateOccurrencesCache($isInsert);
    return $result;
  }

  /**
   * Trigger any necessary updates of cache_occurrences_* table data.
   *
   * @param bool $isInsert
   *   TRUE for inserts, false for updates.
   */
  private function updateOccurrencesCache($isInsert) {
    $addWorkQueueQuery = NULL;
    if (in_array(MODPATH . 'cache_builder', Kohana::config('config.modules'))) {
      // Preferred name inserts can affect other pre-existing names.
      // Any update can affect the name, or other pre-existing names if
      // preferred name being updated.
      $updateRequired = ($isInsert && $this->preferred === 't') || (!$isInsert && $this->updateAffectsOccurrenceCache);
      if ($updateRequired) {
        // Only a preferred name update affects the other names for the taxon.
        $namesFilter = $this->preferred === 't' ? "taxon_meaning_id=$this->taxon_meaning_id" : "id=$this->id";
        $addWorkQueueQuery = <<<SQL
          INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
          SELECT 'task_cache_builder_taxonomy_occurrence', 'taxa_taxon_list', id, 100, 3, now()
          FROM taxa_taxon_lists
          WHERE $namesFilter
          -- Ignore other names unless pre-existing
          AND (id=$this->id OR created_on<'$this->created_on')
          AND deleted=false
          ON CONFLICT DO NOTHING;
        SQL;
        $this->db->query($addWorkQueueQuery);
      }
    }
  }

  /**
   * Attach to parent using parent external key.
   *
   * If there is a parent external key in the metafields data, then we use this
   * to lookup the preferred taxon from this list which has the same external
   * key and will set that as the parent. Used during import to build
   * hierarchies.
   */
  private function postSubmitLinkUsingParentExternalKey() {
    $parentExtKey = $this->submission['metaFields']['parent_external_key']['value'];
    if (!empty($parentExtKey)) {
      $query = $this->db->select('ttl.id')
        ->from('taxa_taxon_lists as ttl')
        ->join('taxa as t', 't.id', 'ttl.taxon_id')
        ->where([
          't.external_key' => $parentExtKey,
          'ttl.taxon_list_id' => $this->taxon_list_id,
          'ttl.preferred' => 't',
          't.deleted' => 'f',
          'ttl.deleted' => 'f',
        ]);
      $result = $query->get()->result_array(FALSE);
      // Only set the parent id if there is a unique hit within the list's
      // preferred taxa.
      if (count($result) === 1) {
        if ($this->parent_id !== $result[0]['id']) {
          $this->parent_id = $result[0]['id'];
        }
      }
      else {
        $this->errors['parent_external_key'] = "Could not find a unique parent using external key $parentExtKey";
        return FALSE;
      }
    }
    else {
      $this->parent_id = NULL;
    }
    return TRUE;
  }

  /**
   * Handle any taxon codes submitted in a CSV file as metadata.
   */
  protected function saveCodeMetafields($codes) {
    $temp = str_replace("\r\n", "\n", $codes['value']);
    $temp = str_replace("\r", "\n", $temp);
    $codeList = explode("\n", trim($temp));
    foreach ($codeList as $code) {
      // Code should be formatted type|code. e.g. Bradley Fletcher|1234.
      $tokens = explode('|', $code);
      // Find the ID of the codes termlist.
      $codeTypesListId = $this->fkLookup([
        'fkTable' => 'termlist',
        'fkSearchField' => 'external_key',
        'fkSearchValue' => 'indicia:taxon_code_types',
      ]);
      // Find the id of the term that matches the input.
      $typeId = $this->fkLookup([
        'fkTable' => 'list_termlists_term',
        'fkSearchField' => 'term',
        'fkSearchValue' => $tokens[0],
        'fkSearchFilterField' => 'termlist_id',
        'fkSearchFilterValue' => $codeTypesListId,
      ]);
      if (!$typeId) {
        throw new Exception("The taxon code type $tokens[0] could not be found in the code types termlist");
      }
      // Save a taxon code.
      $tc = ORM::Factory('taxon_code');
      $tc->set_submission_data([
        'code' => $tokens[1],
        'taxon_meaning_id' => $this->taxon_meaning_id,
        'code_type_id' => $typeId,
      ]);
      if (!$tc->submit()) {
        foreach ($tc->errors as $key => $value) {
          $this->errors[$tc->object_name . ':' . $key] = $value;
        }
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Copy values into common names and synonyms being created.
   *
   * When posting synonyms or common names, some field values can be re-used
   * from the preferred term such as the descriptions and taxon group. This is
   * a utility method for copying submission data matching a list of fields
   * into the save array for the synonym/common name.
   *
   * @param string $modelName
   *   The name of the model data is being copied for, used as a prefix when
   *   building the save array.
   * @param array $source
   *   The array of fields and values for the part of the submission being
   *   copied (i.e. 1 model's values).
   * @param array $fields
   *   List of fields whose value should be copied.
   * @param array $saveArray
   *   Submission array to copy the values into.
   */
  protected function copySharedFieldsFromSubmission($modelName, array $source, array $fields, array &$saveArray) {
    foreach ($fields as $field) {
      if (isset($source[$field])) {
        $saveArray["$modelName:$field"] = is_array($source[$field]) ? $source[$field]['value'] : $source[$field];
      }
    }
  }

  /**
   * Build the array that stores the language for submitted common names.
   *
   * Note: Author is assumed blank.
   */
  protected function setCommonNameSubArray($tokens, &$array) {
    $lang = (count($tokens) == 2 ? trim($tokens[1]) : kohana::config('indicia.default_lang'));
    $array[str_replace('|', '', $tokens[0]) . '|' . $lang . '|'] = [
      'taxon' => $tokens[0],
      'lang' => $lang,
      'auth' => '',
    ];
  }

  /**
   * Build the array that stores the author for submitted synonyms.
   *
   * Note: Synonym Language is Latin.
   */
  protected function setSynonymSubArray($tokens, &$array) {
    $auth = (count($tokens) == 2 ? trim($tokens[1]) : '');
    $array[str_replace('|', '', $tokens[0]) . '|lat|' . $auth] = [
      'taxon' => $tokens[0],
      'lang' => 'lat',
      'auth' => $auth,
    ];
  }

  /**
   * Return the submission structure.
   *
   * Includes defining taxon and taxon_meaning as the parent (super) models,
   * and the synonyms and commonNames as metaFields which are specially
   * handled.
   *
   * @return array
   *   Submission structure for a taxa_taxon_list entry.
   */
  public function get_submission_structure() {
    return [
      'model' => $this->object_name,
      'superModels' => [
        'taxon_meaning' => ['fk' => 'taxon_meaning_id'],
        'taxon' => ['fk' => 'taxon_id'],
      ],
      'metaFields' => [
        'synonyms',
        'commonNames',
        'codes',
        'parent_external_key',
      ],
    ];
  }

  /**
   * Set default values for a new entry.
   */
  public function getDefaults() {
    return [
      'preferred' => 't',
      'taxa_taxon_list:allow_data_entry' => 't',
    ];
  }

  /**
   * Define values that can apply to a whole immport.
   *
   * Define a form that is used to capture a set of predetermined values that
   * apply to every record during an import.
   */
  public function fixedValuesForm() {
    return [
      'taxa_taxon_list:taxon_list_id' => [
        'display' => 'Species List',
        'description' => 'Select the list to import into.',
        'datatype' => 'lookup',
        'population_call' => 'direct:taxon_list:id:title',
      ],
      'taxon:language_id' => [
        'display' => 'Language',
        'description' => 'Select the language to import preferred taxa for.',
        'datatype' => 'lookup',
        'population_call' => 'direct:language:id:language',
      ],
      'taxon:taxon_group_id' => [
        'display' => 'Taxon Group',
        'description' => 'Select the taxon group to import taxa for.',
        'datatype' => 'lookup',
        'population_call' => 'direct:taxon_group:id:title',
      ],
    ];
  }

}
