<?php

/**
 * @file
 * Controller for the list of taxon designations.
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Controller class for the UKSI operations plugin module.
 */
class Uksi_operation_Controller extends Gridview_Base_Controller {

  private $operationErrors = [];

  public function __construct() {
    parent::__construct('uksi_operation', 'uksi_operation/index');
    $this->columns = array(
      'sequence' => '',
      'operation' => '',
      'taxon_name' => '',
      'operation_processed' => 'Processed',
      'has_errors' => '',
      'batch_processed_on' => 'Batch date',
    );
    $this->pagetitle = "UKSI Operations";
    $this->model = ORM::factory('uksi_operation');
  }

  /**
   * As the UKSI list is global, need to be admin to change it.
   */
  protected function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('admin');
  }

  public function importer() {
    $view = new View('uksi_operation/importer');
    $this->template->title = 'Import UKSI operations';
    $this->template->content = $view;
  }

  public function process() {
    $view = new View('uksi_operation/process');
    $view->totalToProcess = $this->db
      ->query('SELECT count(*) FROM uksi_operations WHERE operation_processed=false;')
      ->current()->count;
    $this->template->title='Processing';
    $this->template->content = $view;
  }

  public function processing_complete() {
    $view = new View('uksi_operation/processing_complete');
    $this->template->title='Processing complete';
    $this->template->content = $view;
  }

  public function process_next() {
    header('Content-type: application/json');
    $this->auto_render = FALSE;
    $operation = $this->db
      ->query('SELECT * FROM uksi_operations WHERE operation_processed=false ORDER BY batch_processed_on ASC, sequence ASC;')
      ->current();
    if (!$operation) {
      echo json_encode(['message' => 'Nothing to do']);
      return;
    }
    $operationLink = '<a href="' . url::base(true) . "uksi_operation/edit/$operation->id\">$operation->sequence ($operation->operation)</a>";
    if (!empty($operation->error_detail)) {
      http_response_code(400);
      echo json_encode(['error' => "Operation $operationLink had previously failed. Clear errors before proceeding."]);
      return;
    }

    // Function name camelCase.
    $fn = 'process' . str_replace(' ', '', ucWords($operation->operation));
    if (!method_exists($this, $fn)) {
      http_response_code('501');
      echo json_encode(['status' => 'Not Implemented', 'error' => "Operation not supported: $operation->operation"]);
      return;
    }
    kohana::log('debug', "Calling $fn");
    $this->operationErrors = [];
    try {
      $message = $this->$fn($operation);
    }
    catch (Exception $e) {
      $this->operationErrors[] = $e->getMessage();
    }
    if (count($this->operationErrors) > 0) {
      http_response_code(400);
      $errors = pg_escape_literal(implode("\n", array_unique($this->operationErrors)));
      $this->db
        ->query("UPDATE uksi_operations SET error_detail=$errors WHERE id=$operation->id;");
      echo json_encode(['error' => "Operation $operationLink failed. More details provided in the error_detail field."]);
    }
    else {
      $this->db
        ->query("UPDATE uksi_operations SET operation_processed=true, processed_on=now() WHERE id=$operation->id;");
      echo json_encode(['message' => $message]);
    }
  }


  /**
   * Implements the New taxon operation.
   *
   * Add a completely new taxon to an existing parent.
   *
   * @param object $operation
   *   Operation details.
   */
  public function processNewTaxon($operation) {
    $this->checkOperationRequiredFields('New taxon', $operation, ['taxon_name', 'organism_key', 'taxon_version_key', 'rank', 'output_group']);
    $this->assertOrganismKeyIsNew('New taxon', $operation->organism_key);
    $fields = $this->getCreateTaxonFields($operation);
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    $taxa_taxon_list = ORM::factory('taxa_taxon_list');
    $taxa_taxon_list->set_submission_data($fields);
    if (!$taxa_taxon_list->submit()) {
      $this->operationErrors[] = implode("\n", $taxa_taxon_list->getAllErrors());
      return 'Error';
    }
    return "New taxon $operation->taxon_name added";
  }

  /**
   * Implements the Rename taxon operation.
   *
   * Add a new recommended scientific name to a taxon – the old recommended
   * name will become a junior synonym. If the name exists already but is a
   * junior synonym of this taxon then use a Promote name operation (below).
   * You can assign a new parent with this operation.
   *
   * @param object $operation
   *   Operation details.
   */
  public function processRenameTaxon($operation) {
    $this->checkOperationRequiredFields('Rename taxon', $operation, ['taxon_name', 'organism_key', 'taxon_version_key', 'rank', 'output_group']);
    // Find other taxa with same organism key.
    $allExistingNames = $this->getTaxaForOrganismKey($operation->organism_key);
    // Fail if none found.
    if (count($allExistingNames) === 0) {
      $this->operationErrors[] = "Organism key $operation->organism_key not found for rename taxon operation";
      return 'Error';
    }
    $originalName = $allExistingNames->current()->taxon;
    // Add the taxon as per new taxon, using the existing taxon meaning Id.
    $fields = $this->getCreateTaxonFields($operation);
    $fields['taxa_taxon_list:taxon_meaning_id'] = $allExistingNames->current()->taxon_meaning_id;
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    $taxa_taxon_list = ORM::factory('taxa_taxon_list');
    $taxa_taxon_list->set_submission_data($fields);
    if (!$taxa_taxon_list->submit()) {
      $this->operationErrors[] = implode("\n", $taxa_taxon_list->getAllErrors());
      return 'Error';
    }
    // Update the other taxa with same organism key so not preferred, same group and parent correct.
    $synonymTtlIds = [];
    foreach ($allExistingNames as $existingNameInfo) {
      $synonymTtlIds[] = $existingNameInfo->id;
    }
    $existingSynonyms = ORM::factory('taxa_taxon_list')->in('id', $synonymTtlIds)->find_all();
    foreach ($existingSynonyms as $existingSynonym) {
      $existingSynonym->parent_id = $taxa_taxon_list->parent_id;
      $existingSynonym->preferred = 'f';
      $existingSynonym->set_metadata();
      $existingSynonym->save();
      // Keep the synonym taxon details consistent.
      $existingSynonym->taxon->external_key = $fields['taxon:external_key'];
      $existingSynonym->taxon->taxon_group_id = $fields['taxon:taxon_group_id'];
      $existingSynonym->taxon->marine_flag = $fields['taxon:marine_flag'];
      $existingSynonym->taxon->terrestrial_flag = $fields['taxon:terrestrial_flag'];
      $existingSynonym->taxon->freshwater_flag = $fields['taxon:freshwater_flag'];
      $existingSynonym->taxon->non_native_flag = $fields['taxon:non_native_flag'];
      $existingSynonym->taxon->set_metadata();
      $existingSynonym->taxon->save();
      // Occurrences will need a cache table refresh.
      $addWorkQueueQuery = <<<SQL
INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
VALUES('task_cache_builder_taxonomy_occurrence', 'taxa_taxon_list', $existingSynonym->id, 100, 3, now())
ON CONFLICT DO NOTHING;
SQL;
      $this->db->query($addWorkQueueQuery);
    }
    return "Taxon $originalName renamed to $operation->taxon_name";
  }

  /**
   * Implements the Promote name operation.
   *
   * Promote an existing junior synonym to be the recommended scientific name
   * on the same taxon. You can assign a new parent with this operation.
   *
   * @param object $operation
   *   Operation details.
   */
  public function processPromoteName($operation) {
    $this->checkOperationRequiredFields('Promote name', $operation, ['organism_key', 'synonym']);
    $allExistingNames = $this->getTaxaForOrganismKey($operation->organism_key);
    $foundNameToPromote = FALSE;
    foreach ($allExistingNames as $existingNameInfo) {
      $foundNameToPromote = $foundNameToPromote || ($existingNameInfo->search_code === $operation->synonym);
    }
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    if (!$foundNameToPromote) {
      $this->operationErrors[] = 'Synonym not found for Promote name operation';
    }
    // If parent changing, lookup the parent.
    if (!empty($operation->parent_name) || !empty($operation->parent_organism_key)) {
      $parentId = $this->getParentTtlId($operation);
    }
    foreach ($allExistingNames as $existingNameInfo) {
      if ($existingNameInfo->search_code === $operation->synonym) {
        $promotedName = $existingNameInfo->taxon;
      }
      // If the preferred flag wrong or parent changing, we need to update.
      $currentlyPreferred = $existingNameInfo->preferred === 't';
      $shouldBePreferred = $existingNameInfo->search_code === $operation->synonym;
      if (isset($parentId) || ($currentlyPreferred !== $shouldBePreferred)) {
        $ttl = ORM::factory('taxa_taxon_list', $existingNameInfo->id);
        $ttl->preferred = $ttl->taxon->search_code === $operation->synonym ? 't' : 'f';
        if (isset($parentId)) {
          $ttl->parent_id = $parentId;
        }
        $ttl->set_metadata();
        $ttl->save();
      }
      // Taxa need external key updated.
      if ($existingNameInfo->external_key !== $operation->synonym) {
        $tx = ORM::factory('taxon', $existingNameInfo->taxon_id);
        $tx->external_key = $operation->synonym;
        $tx->set_metadata();
        $tx->save();
      }
      // Occurrences will need a cache table refresh as new preferred name details.
      $addWorkQueueQuery = <<<SQL
INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
VALUES('task_cache_builder_taxonomy_occurrence', 'taxa_taxon_list', $existingNameInfo->id, 100, 3, now())
ON CONFLICT DO NOTHING;
SQL;
      $this->db->query($addWorkQueueQuery);
    }
    return "$promotedName promoted.";
  }

  /**
   * Implements the Merge taxa operation.
   *
   * Merge 2 things together. The senior taxon will persist but the names on
   * the junior taxon will become junior synonyms of it.
   *
   * @param object $operation
   *   Operation details.
   */
  public function processMergeTaxa($operation) {
    $this->checkOperationRequiredFields('Merge taxa', $operation, ['organism_key', 'synonym']);
    $namesToKeep = $this->getTaxaForOrganismKey($operation->organism_key);
    $allNamesToMerge = $this->getTaxaForOrganismKey($operation->synonym);
    if (count($namesToKeep) === 0) {
      $this->operationErrors[] = 'Organism key not found';
    }
    if (count($allNamesToMerge) === 0) {
      $this->operationErrors[] = 'Synonym (organism key) not found';
    }
    $seniorName = $namesToKeep->current()->taxon;
    $juniorName = $allNamesToMerge->current()->taxon;
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    $taxonMeaningId = $namesToKeep->current()->taxon_meaning_id;
    $externalKey = $namesToKeep->current()->external_key;
    foreach ($allNamesToMerge as $mergedNameInfo) {
      $ttl = ORM::factory('taxa_taxon_list', $mergedNameInfo->id);
      $ttl->preferred = 'f';
      $ttl->taxon_meaning_id = $taxonMeaningId;
      $ttl->set_metadata();
      $ttl->save();
      $ttl->taxon->external_key = $externalKey;
      $ttl->taxon->organism_key = $operation->organism_key;
      $ttl->taxon->set_metadata();
      $ttl->taxon->save();
      // Occurrences will need a cache table refresh as new preferred name details.
      $addWorkQueueQuery = <<<SQL
INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
VALUES('task_cache_builder_taxonomy_occurrence', 'taxa_taxon_list', $mergedNameInfo->id, 100, 3, now())
ON CONFLICT DO NOTHING;
SQL;
      $this->db->query($addWorkQueueQuery);
    }
    return "$juniorName merged into $seniorName";
  }

  /**
   * Implements the Amend metadata operation.
   *
   * Change the data associated with a taxon and/or reparent it without adding
   * or changing a name.
   *
   * @param object $operation
   *   Operation details.
   */
  public function processAmendMetadata($operation) {
    $this->checkOperationRequiredFields('Amend metadata', $operation, ['organism_key']);
    $namesToUpdate = $this->getTaxaForOrganismKey($operation->organism_key);
    if (count($namesToUpdate) === 0) {
      $this->operationErrors[] = 'Organism key not found';
    }
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    // If parent changing, lookup the parent.
    if (!empty($operation->parent_name) || !empty($operation->parent_organism_key)) {
      $parentId = $this->getParentTtlId($operation);
      // Apply parent to existing taxa_taxon_lists.
      foreach ($namesToUpdate as $nameInfo) {
        $ttl = ORM::factory('taxa_taxon_list', $nameInfo->id);
        $ttl->parent_id = $parentId;
        $ttl->set_metadata();
        $ttl->save();
      }
    }
    // If taxon flags changing, apply the changes.
    if (!empty($operation->marine) || !empty($operation->terrestrial) || !empty($operation->freshwater) || !empty($operation->non_native)) {
      foreach ($namesToUpdate as $nameInfo) {
        $tx = ORM::factory('taxon', $nameInfo->taxon_id);
        if (!empty($operation->marine)) {
          $tx->marine_flag = $operation->marine;
        }
        if (!empty($operation->terrestrial)) {
          $tx->terrestrial_flag = $operation->terrestrial;
        }
        if (!empty($operation->freshwater)) {
          $tx->freshwater_flag = $operation->freshwater;
        }
        if (!empty($operation->non_native)) {
          $tx->non_native_flag = $operation->non_native;
        }
        $tx->set_metadata();
        $tx->save();
      }
    }
    // Update occurrences via work queue.
    foreach ($namesToUpdate as $nameInfo) {
      $addWorkQueueQuery = <<<SQL
INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
VALUES('task_cache_builder_taxonomy_occurrence', 'taxa_taxon_list', $nameInfo->id, 100, 3, now())
ON CONFLICT DO NOTHING;
SQL;
      $this->db->query($addWorkQueueQuery);
    }
  }


  /**
   * Create an array of fields and values ready to submit a new taxon.
   *
   * @param obj $operation
   *   Object defining any operation which requires the addition of a new taxon.
   *
   * @return array
   *   Associative array of fields & values.
   */
  private function getCreateTaxonFields($operation) {
    $taxonListId = $this->getTaxonListId();
    $parentId = $this->getParentTtlId($operation);
    $rankId = $this->getTaxonRankId($operation);
    $taxonGroupId = $this->getTaxonGroupId($operation);
    return [
      'taxa_taxon_list:taxon_list_id' => $taxonListId,
      'taxa_taxon_list:parent_id' => $parentId,
      'taxa_taxon_list:preferred' => 't',
      'taxon:taxon' => $operation->taxon_name,
      'taxon:authority' => $operation->authority,
      'taxon:attribute' => $operation->attribute,
      'taxon:scientific' => 't',
      'taxon:taxon_rank_id' => $rankId,
      'taxon:taxon_group_id' => $taxonGroupId,
      'taxon:external_key' => $operation->taxon_version_key,
      'taxon:search_code' => $operation->taxon_version_key,
      'taxon:organism_key' => $operation->organism_key,
      'taxon:marine_flag' => $operation->marine,
      'taxon:terrestrial_flag' => $operation->terrestrial,
      'taxon:freshwater_flag' => $operation->freshwater,
      'taxon:non_native_flag' => $operation->non_native,
      'taxon:language_id' => $this->getLanguageId('lat'),
    ];
  }

  /**
   * Map from Y/N values in spreadsheet to t/f for database.
   *
   * @param string $val
   *   Provided value.
   *
   * @return string
   *   Value for database bool field (t/f).
   */
  private function convertBool($val, $default = 'f') {
    $mapping = ['Y' => 't', 'N' => 'f'];
    if (array_key_exists($val, $mapping)) {
      return $mapping[$val];
    }
    return $default;
  }

  /**
   * Retrieves some info about taxon names linked to an organism key.
   *
   * @param string $organismKey
   *   Key to fetch names for.
   *
   * @return object
   *   Query result which can be iterated.
   */
  private function getTaxaForOrganismKey($organismKey) {
    return $this->db->select('ttl.id, ttl.taxon_meaning_id, ttl.taxon_id, ttl.preferred, t.taxon, t.search_code, t.external_key')
      ->from('taxa_taxon_lists AS ttl')
      ->join('taxa as t', 't.id', 'ttl.taxon_id')
      ->where([
        'ttl.taxon_list_id' => $this->getTaxonListId(),
        't.organism_key' => $organismKey,
        'ttl.deleted' => 'f',
        't.deleted' => 'f',
      ])
      ->orderby('preferred', 'DESC')
      ->get();
  }

  /**
   * Generates an error message if provided organism key exists.
   *
   * @param string $operationName
   *   Name of the operation for the error message.
   * @param string $organismKey
   *   Key to check.
   */
  private function assertOrganismKeyIsNew($operationName, $organismKey) {
    $existing = $this->getTaxaForOrganismKey($organismKey);
    if (count($existing) > 0) {
      $this->operationErrors[] = "$operationName operation has provided an organism_key which is not new";
    }
  }

  /**
   * Generates an error for any required fields missing for an operation.
   *
   * @param string $operationName
   *   Name of the operation for the error message.
   * @param object $operation
   *   Operation details object.
   * @param array $requiredFields
   *   List of required field names.
   */
  private function checkOperationRequiredFields($operationName, $operation, array $requiredFields) {
    foreach ($requiredFields as $field) {
      if ($operation->$field === NULL) {
        $this->operationErrors[] = "$operationName operation requires a value for $field";
      }
    }
  }

  private function getParentTtlId($operation) {
    if (!empty($operation->parent_name) && empty($operation->parent_organism_key)) {
      kohana::log('debug', 'Parent name lookup');
      // Parent identified by name which must refer to a recently added taxa.
      // So use the new taxon operation to find the parent's organism key.
      // @todo This query should filter to the current batch if possible
      // rather than use the date.
      $qry = <<<SQL
SELECT organism_key
FROM uksi_operations
WHERE sequence < $operation->sequence
AND taxon_name='$operation->parent_name'
AND operation='New taxon'
AND batch_processed_on='$operation->batch_processed_on'
ORDER BY sequence DESC
LIMIT 1
SQL;
      $parentAddedInOperation = $this->db->query($qry)->current();
      if ($parentAddedInOperation) {
        $operation->parent_organism_key = $parentAddedInOperation->organism_key;
      }
      else {
        $this->operationErrors[] = "Could not find parent_name taxon in previous operations for this batch date.";
      }
    }
    if (!empty($operation->parent_organism_key)) {
      $parent = $this->db->select('ttl.id')
        ->from('taxa_taxon_lists AS ttl')
        ->join('taxa as t', 't.id', 'ttl.taxon_id')
        ->where([
          'ttl.taxon_list_id' => $this->getTaxonListId(),
          't.organism_key' => $operation->parent_organism_key,
          'ttl.preferred' => 't',
          'ttl.deleted' => 'f',
          't.deleted' => 'f',
        ])
        ->get();
      if (count($parent) === 1) {
        return $parent->current()->id;
      }
      if (count($parent) > 1) {
        $this->operationErrors[] = "Identified multiple possible parents.";
      }
      elseif (count($parent) === 0) {
        $this->operationErrors[] = "Could not find parent to link to.";
      }
    }
    return NULL;
  }

  /**
   * Retrieve the configured main taxon list's ID.
   *
   * @return int
   *   Taxon_list_id pointing to the copy of UKSI.
   */
  private function getTaxonListId() {
    $taxonListId = kohana::config('uksi_operations.taxon_list_id', FALSE, FALSE);
    if (!$taxonListId) {
      $this->operationErrors[] = "The UKSI operations module configuration is incomplete - missing taxon_list_id.";
    }
    return $taxonListId;
  }

  /**
   * Finds the ID of a taxon rank described in an operation.
   *
   * @param object $operation
   *   Details of an operation including a rank property containing the rank
   *   name.
   *
   * @return int
   *   ID, or NULL if not found.
   */
  private function getTaxonRankId($operation) {
    $rank = $this->db->select('id')
      ->from('taxon_ranks')
      ->where(['rank' => $operation->rank, 'deleted' => 'f'])
      ->get()->current();
    if ($rank) {
      return $rank->id;
    }
    $this->operationErrors[] = "Could not find rank $operation->rank in the database.";
    return NULL;
  }

  /**
   * Finds the ID of a taxon group described in an operation.
   *
   * @param object $operation
   *   Details of an operation including an output_group property containing
   *   the group name.
   *
   * @return int
   *   ID, or NULL if not found.
   */
  private function getTaxonGroupId($operation) {
    $group = $this->db->select('id')
      ->from('taxon_groups')
      ->where(['title' => $operation->output_group, 'deleted' => 'f'])
      ->get()->current();
    if ($group) {
      return $group->id;
    }
    $this->operationErrors[] = "Could not find taxon group $operation->output_group in the database.";
    return NULL;
  }

  /**
   * Retrieves the ID for a language's ISO code.
   *
   * @param string $iso
   *   ISO language code which should be in the languages table.
   *
   * @return int
   *   ID, or NULL if not found.
   */
  private function getLanguageId($iso) {
    $language = $this->db->select('id')
      ->from('languages')
      ->where(['iso' => $iso, 'deleted' => 'f'])
      ->get()->current();
    if ($language) {
      return $language->id;
    }
    $this->operationErrors[] = "Could not find language code $iso in the database.";
    return NULL;
  }


}
