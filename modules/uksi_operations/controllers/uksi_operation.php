<?php

/**
 * @file
 * Controller for the list of UKSI operations.
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

  /**
   * Track errors for each operation.
   *
   * @var array
   */
  private $operationErrors = [];

  /**
   * Controller constructor, configures grid view.
   */
  public function __construct() {
    parent::__construct('uksi_operation', 'uksi_operation/index');
    $this->columns = [
      'sequence' => '',
      'operation' => '',
      'taxon_name' => '',
      'operation_processed' => 'Processed',
      'has_errors' => '',
      'batch_processed_on' => 'Batch date',
    ];
    $this->pagetitle = "UKSI Operations";
    $this->model = ORM::factory('uksi_operation');
  }

  /**
   * As the UKSI list is global, need to be admin to change it.
   */
  protected function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->logged_in('UKSIAdmin');
  }

  /**
   * Override the generic importer with a specific one.
   */
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
    $this->template->title = 'Processing';
    $this->template->content = $view;
  }

  public function processing_complete() {
    $view = new View('uksi_operation/processing_complete');
    $this->template->title = 'Processing complete';
    $this->template->content = $view;
  }

  public function process_next() {
    header('Content-type: application/json');
    $this->auto_render = FALSE;
    $operation = $this->db
      ->query('SELECT * FROM uksi_operations WHERE operation_processed=false ORDER BY batch_processed_on ASC, sequence ASC;')
      ->current();
    $duplicateOf = $this->operationIsDuplicate($operation);
    if ($duplicateOf) {
      $msg = "Operation skipped as it is a duplicate of $duplicateOf";
      $this->db
        ->query("UPDATE uksi_operations SET operation_processed=true, processed_on=now(), error_detail='$msg' WHERE id=$operation->id;");
      echo json_encode(['message' => $msg]);
      return;
    }
    if (!$operation) {
      echo json_encode(['message' => 'Nothing to do']);
      return;
    }
    $operationLink = '<a href="' . url::base(TRUE) . "uksi_operation/edit/$operation->id\">$operation->batch_processed_on : $operation->sequence ($operation->operation)</a>";
    if (!empty($operation->error_detail)) {
      http_response_code(400);
      echo json_encode(['error' => "Operation $operationLink had previously failed. Clear errors before proceeding."]);
      return;
    }

    // Function name camelCase.
    $fn = 'process' . str_replace(' ', '', ucwords($operation->operation));
    if (!method_exists($this, $fn)) {
      http_response_code('501');
      echo json_encode([
        'status' => 'Not Implemented',
        'error' => "Operation not supported: $operation->operation",
      ]);
      return;
    }
    $this->operationErrors = [];
    try {
      $message = $this->$fn($operation);
    }
    catch (Exception $e) {
      $this->operationErrors[] = $e->getMessage();
    }
    if (count($this->operationErrors) > 0) {
      http_response_code(400);
      $errors = pg_escape_literal($this->db->getLink(), implode("\n", array_unique($this->operationErrors)));
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
   * Checks if an operation is a duplicate.
   *
   * Counts as a duplicate if there is an identical operation within the same
   * batch.
   *
   * @param object $operation
   *   Operation details.
   *
   * @return int
   *   Sequence ID of operation this duplicates, or NULL if not.
   */
  private function operationIsDuplicate($operation) {
    $marine = $operation->marine === TRUE ? 'true' : ($operation->marine === FALSE ? 'false' : '');
    $terrestrial = $operation->terrestrial === TRUE ? 'true' : ($operation->terrestrial === FALSE ? 'false' : '');
    $freshwater = $operation->freshwater === TRUE ? 'true' : ($operation->freshwater === FALSE ? 'false' : '');
    $non_native = $operation->non_native === TRUE ? 'true' : ($operation->non_native === FALSE ? 'false' : '');
    $redundant = $operation->redundant === TRUE ? 'true' : ($operation->redundant === FALSE ? 'false' : '');
    $sql = <<<SQL
SELECT sequence
FROM uksi_operations
WHERE operation='$operation->operation'
AND COALESCE(organism_key, '')=COALESCE('$operation->organism_key', '')
AND COALESCE(taxon_version_key, '')=COALESCE('$operation->taxon_version_key', '')
AND COALESCE(rank, '')=COALESCE('$operation->rank', '')
AND COALESCE(taxon_name, '')=COALESCE('$operation->taxon_name', '')
AND COALESCE(authority, '')=COALESCE('$operation->authority', '')
AND COALESCE(attribute, '')=COALESCE('$operation->attribute', '')
AND COALESCE(parent_organism_key, '')=COALESCE('$operation->parent_organism_key', '')
AND COALESCE(parent_name, '')=COALESCE('$operation->parent_name', '')
AND COALESCE(synonym, '')=COALESCE('$operation->synonym', '')
AND COALESCE(taxon_group_key, '')=COALESCE('$operation->taxon_group_key', '')
AND COALESCE(marine::text, '')='$marine'
AND COALESCE(terrestrial::text, '')='$terrestrial'
AND COALESCE(freshwater::text, '')='$freshwater'
AND COALESCE(non_native::text, '')='$non_native'
AND COALESCE(redundant::text, '')='$redundant'
AND COALESCE(deleted_date::text, '')='$operation->deleted_date'
AND batch_processed_on::text='$operation->batch_processed_on'
AND COALESCE(current_organism_key, '')=COALESCE('$operation->current_organism_key', '')
AND sequence<$operation->sequence
SQL;
    $check = $this->db->query($sql)->current();
    return $check ? $check->sequence : NULL;
  }

  /**
   * Implements the Add synonym operation.
   *
   * Add a new junior synonym (scientific or vernacular) to an existing concept.
   * Currently always added in English.
   *
   * @param object $operation
   *   Operation details.
   */
  public function processAddSynonym($operation) {
    $this->checkOperationRequiredFields('Add synonym', $operation, [
      'taxon_version_key',
      'taxon_name',
    ]);
    // Must either have a current_organism_key, or current_name in same batch.
    if (empty($operation->current_organism_key) && empty($operation->current_name)) {
      $this->operationErrors[] = 'Add synonym operation requires a value for current_organism_key or current_name';
    }
    // Find other taxa with same organism key.
    $allExistingNames = $this->getCurrentTaxa($operation, FALSE);
    // Fail if none found.
    if (count($allExistingNames) === 0) {
      $this->operationErrors[] = "Organism key $operation->current_organism_key not found for add synonym operation";
      return 'Error';
    }
    // Add the taxon as per new taxon.
    $fields = $this->getCreateTaxonFields($operation, FALSE);
    // Check for duplication.
    foreach ($allExistingNames as $name) {
      if ($name->taxon === $fields['taxon:taxon']
          && $name->authority === $fields['taxon:authority']
          && $name->attribute === $fields['taxon:attribute']) {
        return "Synonym $operation->taxon_name already exists.";
      }
    }
    $allExistingNames->rewind();
    // Copy over details from the preferred taxon to define a synonym.
    $fields['taxa_taxon_list:taxon_meaning_id'] = $allExistingNames->current()->taxon_meaning_id;
    $fields['taxa_taxon_list:parent_id'] = $allExistingNames->current()->parent_id;
    $fields['taxon:taxon_rank_id'] = $allExistingNames->current()->taxon_rank_id;
    $fields['taxon:taxon_group_id'] = $allExistingNames->current()->taxon_group_id;
    $fields['taxon:marine_flag'] = $allExistingNames->current()->marine_flag;
    $fields['taxon:freshwater_flag'] = $allExistingNames->current()->freshwater_flag;
    $fields['taxon:terrestrial_flag'] = $allExistingNames->current()->terrestrial_flag;
    $fields['taxon:non_native_flag'] = $allExistingNames->current()->non_native_flag;
    $fields['taxon:organism_key'] = $allExistingNames->current()->organism_key;
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    $taxa_taxon_list = ORM::factory('taxa_taxon_list');
    $taxa_taxon_list->set_submission_data($fields);
    if (!$taxa_taxon_list->submit()) {
      $this->operationErrors[] = implode("\n", $taxa_taxon_list->getAllErrors());
      return 'Error';
    }
    return "Synonym $operation->taxon_name added.";
  }

  /**
   * Deprecated Amend metadata operation.
   *
   * Use Amend taxon instead.
   *
   * @param object $operation
   *   Operation details.
   */
  public function processAmendMetadata($operation) {
    return $this->processAmendTaxon($operation);
  }

  /**
   * Implements the Amend name operation.
   *
   * Allows the name, attribute, authority, rank or taxon group of a single
   * name to be updated. Note taxon group is derived from the input taxon_type
   * field by the pre-processing script.
   *
   * @param object $operation
   *   Operation details.
   */
  public function processAmendName($operation) {
    $this->checkOperationRequiredFields('Amend name', $operation, ['synonym']);
    $names = $this->getTaxaForTaxonVersionKey($operation->synonym);
    foreach ($names as $nameInfo) {
      $tx = ORM::factory('taxon', $nameInfo->taxon_id);
      if (!empty($operation->taxon_name)) {
        $tx->taxon = $operation->taxon_name;
      }
      if (!empty($operation->attribute)) {
        $tx->attribute = $operation->attribute;
      }
      if (!empty($operation->authority)) {
        $tx->authority = $operation->authority;
      }
      if (!empty($operation->rank)) {
        $tx->taxon_rank_id = $this->getTaxonRankId($operation);
      }
      if (!empty($operation->taxon_group_key)) {
        $tx->taxon_group_id = $this->getTaxonGroupId($operation);
      }
      $tx->set_metadata();
      $tx->save();
    }
    return "Name with key $operation->synonym amended.";
  }

  /**
   * Implements the Amend taxon operation.
   *
   * Change the data associated with a taxon and/or reparent it without adding
   * or changing a name.
   *
   * @param object $operation
   *   Operation details.
   */
  public function processAmendTaxon($operation) {
    $this->checkOperationRequiredFields('Amend metadata', $operation, ['current_organism_key']);
    $namesToUpdate = $this->getTaxaForOrganismKey($operation->current_organism_key);
    if (count($namesToUpdate) === 0) {
      $this->operationErrors[] = 'Organism key not found';
    }
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    $parentChanging = !empty($operation->parent_name) || !empty($operation->parent_organism_key);
    $redundantChanging = !empty($operation->redundant);
    $flagsChanging = !empty($operation->marine) || !empty($operation->terrestrial) || !empty($operation->freshwater) || !empty($operation->non_native);
    // Find any changes to taxa_taxon_lists data.
    if ($parentChanging || $redundantChanging) {
      if ($parentChanging) {
        // If parent changing, lookup the parent.
        $parentId = $this->getParentTtlId($operation);
      }
      // Apply parent and redundancy to existing taxa_taxon_lists.
      foreach ($namesToUpdate as $nameInfo) {
        $ttl = ORM::factory('taxa_taxon_list', $nameInfo->id);
        if ($parentChanging) {
          $ttl->parent_id = $parentId;
        }
        if ($redundantChanging) {
          $ttl->allow_data_entry = $operation->redundant ? 'f' : 't';
        }
        $ttl->set_metadata();
        $ttl->save();
      }
    }
    // If taxon flags changing, apply the changes.
    if ($flagsChanging) {
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
    return "Taxon with organism key $operation->current_organism_key amended.";
  }

  /**
   * Implements the deprecate name operation.
   *
   * Flags a name as not for data entry.
   *
   * @param object $operation
   *   Operation details.
   */
  public function processDeprecateName($operation) {
    $this->checkOperationRequiredFields('Deprecate name', $operation, ['synonym']);
    $namesToUpdate = $this->getTaxaForTaxonVersionKey($operation->synonym);
    // Should be only one name for TVK, but just in case do a loop.
    foreach ($namesToUpdate as $nameInfo) {
      // Flag as not for data entry.
      $ttl = ORM::factory('taxa_taxon_list', $nameInfo->id);
      $ttl->allow_data_entry = 'f';
      $ttl->set_metadata();
      $ttl->save();
    }
    return "Name with key $operation->synonym deprecated.";
  }

  /**
   * Extracts a junion synonym to create a new taxon.
   *
   * @param object $operation
   *   Operation details.
   */
  public function processExtractName($operation) {
    $this->checkOperationRequiredFields('Extract name', $operation, [
      'current_organism_key',
      'synonym',
      'organism_key',
    ]);
    $namesToUpdate = $this->getTaxaForTaxonVersionKey($operation->synonym);
    // Should be only one name for TVK, but just in case do a loop.
    foreach ($namesToUpdate as $nameInfo) {
      // Assign new taxon_meaning_id to taxa_taxon_lists.
      $newTxMeaning = ORM::factory('taxon_meaning');
      $newTxMeaning->set_submission_data([]);
      $newTxMeaning->submit();
      // Find the name to extract.
      $ttl = ORM::factory('taxa_taxon_list', $nameInfo->id);
      // Set the existing parent.
      $qry = <<<SQL
select parent_id
from taxa_taxon_lists
where taxon_meaning_id=$nameInfo->taxon_meaning_id
and preferred=true;
SQL;
      $ttl->parent_id = $this->db->query($qry)->current()->parent_id;
      // Give the name a new meaning.
      $ttl->taxon_meaning_id = $newTxMeaning->id;
      $ttl->set_metadata();
      $ttl->save();

      // Set organism_key and external_key in taxon so this is a new, preferred
      // taxon.
      $ttl->taxon->organism_key = $operation->organism_key;
      $ttl->taxon->external_key = $operation->synonym;
      $ttl->taxon->set_metadata();
      $ttl->taxon->save();
    }
    return "Name with key $operation->synonym extracted to make a new organism.";
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
    $this->checkOperationRequiredFields('Merge taxa', $operation, [
      'current_organism_key',
      'synonym',
    ]);
    $namesToKeep = $this->getTaxaForOrganismKey($operation->current_organism_key);
    $allNamesToMerge = $this->getTaxaForOrganismKey($operation->synonym);
    if (count($allNamesToMerge) === 0) {
      $this->operationErrors[] = 'Synonym (organism key) not found';
    }
    if (count($namesToKeep) === 0) {
      $this->operationErrors[] = 'Could not find existing organisms using current_organism_key';
    }
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    return $this->moveNames($allNamesToMerge, $namesToKeep->current());
  }

  /**
   * Implements the Move name operation.
   *
   * Moves a junior synonym from one organism to another.
   *
   * @param object $operation
   *   Operation details.
   */
  public function processMoveName($operation) {
    $this->checkOperationRequiredFields('Move name', $operation, [
      'current_organism_key',
      'synonym',
    ]);
    $namesToKeep = $this->getTaxaForOrganismKey($operation->current_organism_key);
    $allNamesToMerge = $this->getTaxaForTaxonVersionKey($operation->synonym);
    if (count($allNamesToMerge) === 0) {
      $this->operationErrors[] = 'Synonym (taxon version key) not found';
    }
    if (count($namesToKeep) === 0) {
      $this->operationErrors[] = 'Organism key not found';
    }
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    return $this->moveNames($allNamesToMerge, $namesToKeep->current());
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
    $this->checkOperationRequiredFields('New taxon', $operation, [
      'taxon_name',
      'organism_key',
      'taxon_version_key',
      'rank',
      'taxon_group_key',
    ]);
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
   * Implements the Promote name operation.
   *
   * Promote an existing junior synonym to be the recommended scientific name
   * on the same taxon. You can assign a new parent with this operation.
   *
   * @param object $operation
   *   Operation details.
   *
   * @todo Handle updates to ORGANISM_KEY?
   */
  public function processPromoteName($operation) {
    $this->checkOperationRequiredFields('Promote name', $operation, [
      'current_organism_key',
      'synonym',
    ]);
    $allExistingNames = $this->getTaxaForOrganismKey($operation->current_organism_key);
    $foundNameToPromote = FALSE;
    foreach ($allExistingNames as $existingNameInfo) {
      $foundNameToPromote = $foundNameToPromote || ($existingNameInfo->search_code === $operation->synonym);
    }
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    if (!$foundNameToPromote) {
      $this->operationErrors[] = 'Name identified by Synonym value could not be found for Promote name operation';
    }
    // If parent changing, lookup the parent.
    if (!empty($operation->parent_name) || !empty($operation->parent_organism_key)) {
      $parentId = $this->getParentTtlId($operation);
    }
    if (count($this->operationErrors) > 0) {
      return 'Error';
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
        if ($currentlyPreferred !== $shouldBePreferred) {
          $this->repointLinksFromSynonymsToPreferredName($ttl->id);
        }
      }
      // Taxa need external key updated.
      if ($existingNameInfo->external_key !== $operation->synonym) {
        $tx = ORM::factory('taxon', $existingNameInfo->taxon_id);
        $tx->external_key = $operation->synonym;
        $tx->set_metadata();
        $tx->save();
      }
    }
    return "$promotedName promoted.";
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
    $this->checkOperationRequiredFields('Rename taxon', $operation, [
      'taxon_name',
      'current_organism_key',
      'taxon_version_key',
      'rank',
      'taxon_group_key',
    ]);
    // Find other taxa with same organism key.
    $allExistingNames = $this->getTaxaForOrganismKey($operation->current_organism_key);
    // Fail if none found.
    if (count($allExistingNames) === 0) {
      $this->operationErrors[] = "Organism key $operation->current_organism_key not found for rename taxon operation";
      return 'Error';
    }
    $originalName = $allExistingNames->current()->taxon;
    // Add the taxon as per new taxon, using the existing taxon meaning Id.
    $fields = $this->getCreateTaxonFields($operation);
    // New name for existing taxon.
    $fields['taxon:organism_key'] = $operation->current_organism_key;
    $fields['taxa_taxon_list:taxon_meaning_id'] = $allExistingNames->current()->taxon_meaning_id;
    $fields['taxa_taxon_list:parent_id'] = $allExistingNames->current()->parent_id;
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    $taxa_taxon_list = ORM::factory('taxa_taxon_list');
    $taxa_taxon_list->set_submission_data($fields);
    if (!$taxa_taxon_list->submit()) {
      $this->operationErrors[] = implode("\n", $taxa_taxon_list->getAllErrors());
      return 'Error';
    }
    // Update the other taxa with same organism key so not preferred, same
    // group and parent correct.
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
    }
    $this->repointLinksFromSynonymsToPreferredName($taxa_taxon_list->id);
    return "Taxon $originalName renamed to $operation->taxon_name";
  }

  /**
   * If a preferred name changes for a concept, update references.
   *
   * Parent IDs of child taxa_taxon_lists as well as
   * taxa_taxon_list_attribute_values use the preferred name's
   * taxa_taxon_list.id as a key, so these need to be re-pointed to a new
   * preferred name.
   *
   * @param int $ttlId
   *   Taxa_taxon_lists.id value for the new preferred name.
   */
  private function repointLinksFromSynonymsToPreferredName($ttlId) {
    // Any existing children should be re-pointed to new preferred name.
    $qry = <<<SQL
update taxa_taxon_lists u
set parent_id=p.id
from taxa_taxon_lists p
join taxa_taxon_lists s on s.taxon_meaning_id=p.taxon_meaning_id
where u.parent_id=s.id
and p.id=$ttlId
and u.parent_id<>p.id
SQL;
    $this->db->query($qry);
    // Any existing attribute values should be re-pointed to new preferred
    // name.
    $qry = <<<SQL
update taxa_taxon_list_attribute_values u
set taxa_taxon_list_id=p.id
from taxa_taxon_lists p
join taxa_taxon_lists s on s.taxon_meaning_id=p.taxon_meaning_id
where u.taxa_taxon_list_id=s.id
and p.id=$ttlId
and u.taxa_taxon_list_id<>p.id
SQL;
    $this->db->query($qry);
  }

  /**
   * Moves a group of names into another concept.
   *
   * @param object $names
   *   Query object containing a list of names which will be moved.
   * @param object $linkToName
   *   Current record for a name in the destination concept which the moving
   *   names will be linked to.
   */
  private function moveNames($names, $linkToName) {
    $seniorName = $linkToName->taxon;
    $juniorName = $names->current()->taxon;
    $taxonMeaningId = $linkToName->taxon_meaning_id;
    $externalKey = $linkToName->external_key;
    foreach ($names as $mergedNameInfo) {
      $ttl = ORM::factory('taxa_taxon_list', $mergedNameInfo->id);
      $ttl->preferred = 'f';
      $ttl->taxon_meaning_id = $taxonMeaningId;
      $ttl->set_metadata();
      $ttl->save();
      $ttl->taxon->external_key = $externalKey;
      $ttl->taxon->organism_key = $linkToName->organism_key;
      $ttl->taxon->set_metadata();
      $ttl->taxon->save();
    }
    return "$juniorName merged into $seniorName";
  }

  /**
   * Create an array of fields and values ready to submit a new taxon.
   *
   * @param object $operation
   *   Object defining any operation which requires the addition of a new taxon.
   * @param bool $preferred
   *   Default TRUE, set to FALSE if creating a non-preferred name.
   *
   * @return array
   *   Associative array of fields & values.
   */
  private function getCreateTaxonFields($operation, $preferred = TRUE) {
    $taxonListId = $this->getTaxonListId();
    $parentId = $this->getParentTtlId($operation);
    $rankId = $this->getTaxonRankId($operation);
    $taxonGroupId = $this->getTaxonGroupId($operation);
    return [
      'taxa_taxon_list:taxon_list_id' => $taxonListId,
      'taxa_taxon_list:parent_id' => $parentId,
      'taxa_taxon_list:preferred' => $preferred ? 't' : 'f',
      'taxon:taxon' => $operation->taxon_name,
      'taxon:authority' => $operation->authority,
      'taxon:attribute' => $operation->attribute,
      'taxon:scientific' => 't',
      'taxon:taxon_rank_id' => $rankId,
      'taxon:taxon_group_id' => $taxonGroupId,
      'taxon:external_key' => $operation->taxon_version_key,
      'taxon:search_code' => $operation->taxon_version_key,
      'taxon:organism_key' => $operation->organism_key,
      'taxon:marine_flag' => empty($operation->marine) ? 'f' : $operation->marine,
      'taxon:terrestrial_flag' => empty($operation->terrestrial) ? 'f' : $operation->terrestrial,
      'taxon:freshwater_flag' => empty($operation->freshwater) ? 'f' : $operation->freshwater,
      'taxon:non_native_flag' => empty($operation->non_native) ? 'f' : $operation->non_native,
      'taxon:language_id' => $this->getLanguageId('lat'),
    ];
  }

  /**
   * Returns taxon info referred to by current_organism_key or current_name.
   *
   * Current name can be used to refer to a taxon name added in same batch.
   */
  private function getCurrentTaxa(&$operation) {
    if (empty($operation->current_organism_key) && !empty($operation->current_name)) {
      $qry = <<<SQL
SELECT organism_key
FROM uksi_operations
WHERE sequence < $operation->sequence
AND taxon_name='$operation->current_name'
AND operation='New taxon'
AND batch_processed_on='$operation->batch_processed_on'
ORDER BY sequence DESC
LIMIT 1
SQL;
      $nameAddedInOperation = $this->db->query($qry)->current();
      if ($nameAddedInOperation) {
        $operation->current_organism_key = $nameAddedInOperation->organism_key;
      }
      else {
        $this->operationErrors[] = "Could not find current_name taxon in previous operations for this batch date.";
      }

    }
    if (!empty($operation->current_organism_key)) {
      return $this->getTaxaForOrganismKey($operation->current_organism_key);
    }
    return [];
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
    return $this->db->select('ttl.id, ttl.taxon_meaning_id, ttl.taxon_id, ttl.preferred, ttl.parent_id, ' .
        't.taxon, t.authority, t.attribute, t.search_code, t.external_key, t.organism_key, t.taxon_rank_id, ' .
        ' t.taxon_group_id, t.marine_flag, t.freshwater_flag, t.terrestrial_flag, t.non_native_flag')
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
   * Retrieves some info about a taxon name linked to a TVK.
   *
   * @param string $taxonVersionKey
   *   Key to fetch names for.
   *
   * @return object
   *   Query result which can be iterated.
   */
  private function getTaxaForTaxonVersionKey($taxonVersionKey) {
    return $this->db->select('ttl.id, ttl.taxon_meaning_id, ttl.taxon_id, ttl.preferred, t.taxon, t.search_code, t.external_key, t.organism_key')
      ->from('taxa_taxon_lists AS ttl')
      ->join('taxa as t', 't.id', 'ttl.taxon_id')
      ->where([
        'ttl.taxon_list_id' => $this->getTaxonListId(),
        't.search_code' => $taxonVersionKey,
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

  /**
   * Gets the taxa_taxon_lists.id value for a parent inferred in an operation.
   *
   * @param object $operation
   *   Operation details.
   *
   * @return int
   *   Taxa_taxon_lists.id.
   */
  private function getParentTtlId($operation) {
    if (!empty($operation->parent_name) && empty($operation->parent_organism_key)) {
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
        ->where('t.search_code IS NOT NULL')
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
    if (!empty($operation->rank)) {
      $rank = $this->db->select('id')
        ->from('taxon_ranks')
        ->where('deleted', 'f')
        // Use like (=ilike) as case-insensitive.
        ->like('rank', $operation->rank)
        ->get()->current();
      if ($rank) {
        return $rank->id;
      }
      $this->operationErrors[] = "Could not find rank $operation->rank in the database.";
    }
    return NULL;
  }

  /**
   * Finds the ID of a taxon group described in an operation.
   *
   * @param object $operation
   *   Details of an operation including an taxon_group_key property to lookup
   *   against.
   *
   * @return int
   *   ID, or NULL if not found.
   */
  private function getTaxonGroupId($operation) {
    if (!empty($operation->taxon_group_key)) {
      $group = $this->db->select('id')
        ->from('taxon_groups')
        ->where([
          'external_key' => $operation->taxon_group_key,
          'deleted' => 'f',
        ])
        ->get()->current();
      if ($group) {
        return $group->id;
      }
      $this->operationErrors[] = "Could not find taxon group $operation->taxon_group_key in the database.";
    }
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
