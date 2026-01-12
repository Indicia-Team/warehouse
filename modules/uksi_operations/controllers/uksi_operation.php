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
    // Note operations processed in task type order.
    $qry = <<<SQL
SELECT * FROM uksi_operations
WHERE operation_processed=false
ORDER BY batch_processed_on asc, operation_priority asc, sequence asc;
SQL;
    $operation = $this->db
      ->query($qry)
      ->current();
    if (!$operation) {
      echo json_encode(['message' => 'Nothing to do']);
      return;
    }
    $duplicateOf = $this->operationIsDuplicate($operation);
    if ($duplicateOf) {
      $msg = "Operation skipped as it is a duplicate of $duplicateOf";
      $this->db
        ->query("UPDATE uksi_operations SET operation_processed=true, processed_on=now(), error_detail='$msg' WHERE id=$operation->id;");
      echo json_encode(['message' => $msg]);
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
    // Array for converting bool shorthand to full version, to make SQL
    // simpler.
    $boolMap = ['t' => 'true', 'f' => 'false'];
    $marine = $boolMap[$operation->marine] ?? '';
    $terrestrial = $boolMap[$operation->terrestrial] ?? '';
    $freshwater = $boolMap[$operation->freshwater] ?? '';
    $non_native = $boolMap[$operation->non_native] ?? '';
    $redundant = $boolMap[$operation->redundant] ?? '';
    $taxonName = pg_escape_literal($this->db->getLink(), $operation->taxon_name ?? '');
    $authority = pg_escape_literal($this->db->getLink(), $operation->authority ?? '');
    $attribute = pg_escape_literal($this->db->getLink(), $operation->attribute ?? '');
    $parentName = pg_escape_literal($this->db->getLink(), $operation->parent_name ?? '');
    $synonym = pg_escape_literal($this->db->getLink(), $operation->synonym ?? '');
    $sql = <<<SQL
SELECT sequence
FROM uksi_operations
WHERE LOWER(operation)=LOWER('$operation->operation')
AND COALESCE(organism_key, '')=COALESCE('$operation->organism_key', '')
AND COALESCE(taxon_version_key, '')=COALESCE('$operation->taxon_version_key', '')
AND COALESCE(rank, '')=COALESCE('$operation->rank', '')
AND COALESCE(taxon_name, '')=COALESCE($taxonName, '')
AND COALESCE(authority, '')=COALESCE($authority, '')
AND COALESCE(attribute, '')=COALESCE($attribute, '')
AND COALESCE(parent_organism_key, '')=COALESCE('$operation->parent_organism_key', '')
AND COALESCE(parent_name, '')=COALESCE($parentName, '')
AND COALESCE(synonym, '')=COALESCE($synonym, '')
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
    $allExistingNames = $this->getCurrentTaxa($operation);
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
          && $name->attribute === $fields['taxon:attribute']
          && $name->search_code === $fields['taxon:search_code']) {
        return "Synonym $operation->taxon_name already exists.";
      }
    }
    $allExistingNames->rewind();
    // Copy over details from the preferred taxon to define a synonym.
    $fields['taxa_taxon_list:taxon_meaning_id'] = $allExistingNames->current()->taxon_meaning_id;
    $fields['taxa_taxon_list:parent_id'] = $allExistingNames->current()->parent_id;
    $fields['taxa_taxon_list:common_taxon_id'] = $allExistingNames->current()->common_taxon_id;
    $fields['taxon:taxon_rank_id'] = $allExistingNames->current()->taxon_rank_id;
    $fields['taxon:taxon_group_id'] = $allExistingNames->current()->taxon_group_id;
    $fields['taxon:marine_flag'] = $allExistingNames->current()->marine_flag;
    $fields['taxon:freshwater_flag'] = $allExistingNames->current()->freshwater_flag;
    $fields['taxon:terrestrial_flag'] = $allExistingNames->current()->terrestrial_flag;
    $fields['taxon:non_native_flag'] = $allExistingNames->current()->non_native_flag;
    $fields['taxon:organism_key'] = $allExistingNames->current()->organism_key;
    $fields['taxon:external_key'] = $allExistingNames->current()->external_key;
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
    $names = $this->getTaxaForKeys(['search_code' => $operation->synonym]);
    foreach ($names as $nameInfo) {
      $tx = ORM::factory('taxon', $nameInfo->taxon_id);
      if (!empty($operation->taxon_name)) {
        $tx->taxon = $operation->taxon_name;
      }
      if (!empty($operation->attribute)) {
        if ($operation->attribute === 'NONE') {
          $tx->attribute = NULL;
        } else {
          $tx->attribute = $operation->attribute;
        }
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
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    $namesToUpdate = $this->getTaxaForKeys(['organism_key' => $operation->current_organism_key]);
    if (count($namesToUpdate) === 0) {
      // If organism not present then assumed to be deleted so operation is
      // skipped, according to info from C. Raper 13/05/2024.
      return 'Operation skipped as organism key not found';
    }
    $parentChanging = !empty($operation->parent_name) || !empty($operation->parent_organism_key);
    $redundantChanging = $operation->redundant !== NULL;
    $flagsChanging = !empty($operation->marine) || !empty($operation->terrestrial) || !empty($operation->freshwater) || !empty($operation->non_native);
    // Find any changes to taxa_taxon_lists data.
    if ($parentChanging || $redundantChanging) {
      if ($parentChanging) {
        // If parent changing, lookup the parent.
        $parentId = $this->getParentTtlId($operation, TRUE);
      }
      // Apply parent and redundancy to existing taxa_taxon_lists.
      foreach ($namesToUpdate as $nameInfo) {
        $ttl = ORM::factory('taxa_taxon_list', $nameInfo->id);
        if ($parentChanging) {
          $ttl->parent_id = $parentId;
        }
        if ($redundantChanging) {
          $ttl->allow_data_entry = $operation->redundant === 't' ? 'f' : 't';
        }
        $ttl->set_metadata();
        $ttl->save();
      }
    }
    // If taxon flags changing, apply the changes.
    if ($flagsChanging || $redundantChanging) {
      foreach ($namesToUpdate as $nameInfo) {
        $tx = ORM::factory('taxon', $nameInfo->taxon_id);
        if ($operation->marine !== NULL) {
          $tx->marine_flag = $operation->marine;
        }
        if ($operation->terrestrial !== NULL) {
          $tx->terrestrial_flag = $operation->terrestrial;
        }
        if ($operation->freshwater !== NULL) {
          $tx->freshwater_flag = $operation->freshwater;
        }
        if ($operation->non_native !== NULL) {
          $tx->non_native_flag = $operation->non_native;
        }
        if ($operation->redundant !== NULL) {
          $tx->organism_deprecated = $operation->redundant;
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
    $namesToUpdate = $this->getTaxaForKeys(['search_code' => $operation->synonym]);
    // Should be only one name for TVK, but just in case do a loop.
    foreach ($namesToUpdate as $nameInfo) {
      // Update name_deprecated flag in taxon.
      $tx = ORM::factory('taxon', $nameInfo->taxon_id);
      $tx->name_deprecated = 't';
      $tx->set_metadata();
      $tx->save();
      // Flag as not for data entry.
      $ttl = ORM::factory('taxa_taxon_list', $nameInfo->id);
      $ttl->allow_data_entry = 'f';
      $ttl->set_metadata();
      $ttl->save();
    }
    return "Name with key $operation->synonym deprecated.";
  }

  /**
   * Implements the remove deprecation operation.
   *
   * Flags a name as available for data entry.
   *
   * @param object $operation
   *   Operation details.
   */
  public function processRemoveDeprecation($operation) {
    $this->checkOperationRequiredFields('Remove deprecation', $operation, ['synonym']);
    $namesToUpdate = $this->getTaxaForKeys(['search_code' => $operation->synonym]);
    // Should be only one name for TVK, but just in case do a loop.
    foreach ($namesToUpdate as $nameInfo) {
      // Update name_deprecated flag in taxon.
      $tx = ORM::factory('taxon', $nameInfo->taxon_id);
      $tx->name_deprecated = 'f';
      $tx->set_metadata();
      $tx->save();
      // Flag as available for data entry.
      $ttl = ORM::factory('taxa_taxon_list', $nameInfo->id);
      $ttl->allow_data_entry = $nameInfo->organism_deprecated === 't' ? 'f' : 't';
      $ttl->set_metadata();
      $ttl->save();
    }
    return "Name with key $operation->synonym undeprecated.";
  }

  /**
   * Extracts a junion synonym to create a new taxon.
   *
   * @param object $operation
   *   Operation details.
   */
  public function processExtractName($operation) {
    $this->checkOperationRequiredFields('Extract name', $operation, [
      'synonym',
      'organism_key',
      'current_organism_key',
    ]);
    $namesToUpdate = $this->getTaxaForKeys([
      'search_code' => $operation->synonym,
      'organism_key' => $operation->current_organism_key,
    ]);
    // Check names found.
    if (count($namesToUpdate) === 0) {
      // If not found when checking both TVK and org key, we can try a search
      // on just TVK within the current names as the org key is sometimes
      // incorrect.
      $namesToUpdate = $this->getTaxaForKeys([
        'search_code' => $operation->synonym,
        'ttl.allow_data_entry' => 't',
      ]);
      if (count($namesToUpdate) === 0) {
        $this->operationErrors[] = 'Name with taxon version key given in Synonym for Extract Name operation not found.';
      }
      elseif (count($namesToUpdate) > 1) {
        $this->operationErrors[] = 'Multiple names found when searching for a unique name using the taxon version key given in Synonym for Extract Name operation.';
      }
    }
    foreach ($namesToUpdate as $nameInfo) {
      if ($nameInfo->preferred === 't') {
        $this->operationErrors[] = 'Name with taxon version key given in Synonym for Extract Name operation is for the accepted name, not a synonym.';
      }
    }
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
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
      // Give the name a new meaning and make it preferred.
      $ttl->taxon_meaning_id = $newTxMeaning->id;
      $ttl->preferred = TRUE;
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
    $namesToKeep = $this->getTaxaForKeys(['organism_key' => $operation->current_organism_key]);
    $allNamesToMerge = $this->getTaxaForKeys(['organism_key' => $operation->synonym]);
    if (count($allNamesToMerge) === 0) {
      $this->operationErrors[] = 'Synonym (organism key) not found';
    }
    if (count($namesToKeep) === 0) {
      $this->operationErrors[] = 'Could not find existing organisms using current_organism_key';
    }
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    return $this->moveNames($allNamesToMerge, $namesToKeep->current(), $operation);
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
    ]);
    // If synonym not present then operation is skipped, according to info from
    // C. Raper 30/04/2024.
    if (empty($operation->synonym)) {
      return 'Operation skipped as not found';
    }
    $namesToKeep = $this->getTaxaForKeys(['organism_key' => $operation->current_organism_key]);
    $allNamesToMerge = $this->getTaxaForKeys(['search_code' => $operation->synonym]);
    if (count($allNamesToMerge) === 0) {
      $this->operationErrors[] = 'Synonym (taxon version key) not found';
    }
    if (count($namesToKeep) === 0) {
      $this->operationErrors[] = 'Organism key not found';
    }
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    return $this->moveNames($allNamesToMerge, $namesToKeep->current(), $operation);
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
    $existingManuallyAdded = $this->assertOrganismKeyIsNew('New taxon', $operation->organism_key);
    $fields = $this->getCreateTaxonFields($operation);
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    if ($existingManuallyAdded) {
      // Overwrite a manually added new taxon by linking to the IDs.
      $taxa_taxon_list = ORM::factory('taxa_taxon_list', $existingManuallyAdded->id);
      $fields['taxon:id'] = $existingManuallyAdded->taxon_id;
    }
    else {
      $taxa_taxon_list = ORM::factory('taxa_taxon_list');
    }
    $taxa_taxon_list->set_submission_data($fields);
    if (!$taxa_taxon_list->submit()) {
      $this->operationErrors[] = implode("\n", $taxa_taxon_list->getAllErrors());
      return 'Error';
    }
    return "New taxon $operation->taxon_name added";
  }

  /**
   * For a promote name operation, sets the appropriate preferred value.
   *
   * Sets synonyms to preferred=f, the accepted name to preferred=t. Can also
   * set the parent. For all names, the external key gets updated to the
   * accepted TVK of the preferred name.
   *
   * @param object $operation
   *   Operation details.
   * @param object $nameInfo
   *   Object containing the ID (taxa taxon list ID) and taxon ID of the name.
   * @param bool $preferred
   *   Is this name being set as the preferred name?
   * @param int $parentId
   *   Optional parent taxa taxon list ID.
   */
  private function setNameInfoForPromoteName($operation, $nameInfo, $preferred, $parentId) {
    $ttl = ORM::factory('taxa_taxon_list', $nameInfo->id);
    $ttl->preferred = $preferred ? 't' : 'f';
    if ($parentId) {
      $ttl->parent_id = $parentId;
    }
    $ttl->set_metadata();
    $ttl->save();
    $tx = ORM::factory('taxon', $nameInfo->taxon_id);
    $tx->external_key = $operation->synonym;
    // Organism key shouldn't be altered according to the spec, but if a
    // promote name is used to move a name then the organism key of the new
    // preferred name needs to be updated.
    $tx->organism_key = $operation->current_organism_key;
    $tx->set_metadata();
    $tx->save();
    if ($preferred) {
      $this->repointLinksFromSynonymsToPreferredName($ttl->id);
    }
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
    // If parent changing, lookup the parent.
    if (!empty($operation->parent_name) || !empty($operation->parent_organism_key)) {
      $parentId = $this->getParentTtlId($operation);
    }
    else {
      $parentId = NULL;
    }
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    // Need to get names by TVK and Organism Key, because the name being
    // promoted might be for a different organism (which I think is incorrect
    // according to the spec, but it happens).
    $nameToPromote = $this->getTaxaForKeys(['search_code' => $operation->synonym]);
    $namesForOrganismKey = $this->getTaxaForKeys(['organism_key' => $operation->current_organism_key]);
    foreach ($nameToPromote as $promotedNameInfo) {
      $this->setNameInfoForPromoteName($operation, $promotedNameInfo, TRUE, $parentId);
      $promotedName = $promotedNameInfo->taxon;
    }
    foreach ($namesForOrganismKey as $nameInfo) {
      // Don't redo the promoted name.
      if ($nameInfo->search_code !== $operation['synonym']) {
        $this->setNameInfoForPromoteName($operation, $nameInfo, FALSE, $parentId);
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
    $allExistingNames = $this->getTaxaForKeys(['organism_key' => $operation->current_organism_key]);
    // Fail if none found.
    if (count($allExistingNames) === 0) {
      $this->operationErrors[] = "Organism key $operation->current_organism_key not found for rename taxon operation";
      return 'Error';
    }
    // All existing names sorted so preferred name is first.
    $previousPreferredName = $allExistingNames->current();
    $originalName = $previousPreferredName->taxon;
    $this->applyTaxonDefaultsToOperation($previousPreferredName, $operation);
    // Add the taxon as per new taxon, using the existing taxon meaning Id.
    $fields = $this->getCreateTaxonFields($operation);
    // The rename taxon operation only updates the parent from the operation
    // if for the correct organism key (in order to match UKSI operations
    // behaviour).
    if ($operation->current_organism_key !== $operation->current_organism_key) {
      $fields['taxa_taxon_list:parent_id'] = $previousPreferredName->parent_id;
    }
    $fields['taxon:organism_key'] = $operation->current_organism_key;
    $fields['taxa_taxon_list:taxon_meaning_id'] = $previousPreferredName->taxon_meaning_id;
    $fields['taxa_taxon_list:common_taxon_id'] = $previousPreferredName->common_taxon_id;
    if (empty($fields['taxa_taxon_list:parent_id'])) {
      // If parent not specified in operation, keep the original.
      $fields['taxa_taxon_list:parent_id'] = $previousPreferredName->parent_id;
    }
    // The new name inherits the existing organism deprecation state. This is
    // just a taxon operation, so only the name_deprecated flag gets set
    // according to the operation redundant flag.
    $fields['taxon:organism_deprecated'] = $previousPreferredName->organism_deprecated;
    $fields['taxa_taxon_list:allow_data_entry'] = $fields['taxon:organism_deprecated'] === 'f' && $fields['taxon:name_deprecated'] === 'f' ? 't' : 'f';
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
      // Note, the operation redundant flag does not alter the other synonyms.
      // Don't undeprecate the name if there is a better formed version of the
      // same name.
      $existingSynonym->allow_data_entry = $this->checkIfSynonymAllowsDataEntry($existingSynonym, $existingSynonyms->as_array());
      $existingSynonym->preferred = 'f';
      $existingSynonym->parent_id = $fields['taxa_taxon_list:parent_id'];
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

  private function checkIfSynonymAllowsDataEntry($synonym, array $allNamesForTaxon) {
    // If organism or name deprecated, disallow data entry.
    if ($synonym->taxon->organism_deprecated === 't' || $synonym->taxon->name_deprecated === 't') {
      return 'f';
    }
    // Apply additional Indicia logic - if UKSI does not deprecate a name, but
    // the name is ill-formed and there is a better well-formed version, then
    // also disallow data entry.
    if ($synonym->taxon->name_form === 'I' || $synonym->taxon->name_form === 'U') {
      foreach ($allNamesForTaxon as $otherName) {
        if (
          // Well-formed.
          $otherName->taxon->name_form === 'W'
          // Matching taxon type (language).
          && $otherName->taxon->language_id === $synonym->taxon->language_id
          // Matching name and attribute (fuzzy).
          && strtolower(str_replace('-', ' ', $otherName->taxon->taxon . ($otherName->taxon->attribute === NULL ? '' : ' ' . $otherName->taxon->attribute))) ===
            strtolower(str_replace('-', ' ', $synonym->taxon->taxon . ($synonym->taxon->attribute === NULL ? '' : ' ' . $synonym->taxon->attribute)))
          // Matching authority (fuzzy), or authority missing from name.
          && (strtolower(str_replace('-', ' ', $otherName->taxon->authority ?? '')) === strtolower(str_replace('-', ' ', $synonym->taxon->authority ?? ''))
            || !$synonym->taxon->authority)
          // Not redundant.
          && $otherName->taxon->name_deprecated === 'f') {
          return 'f';
        }
      }
    }
    return 't';
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
   *   Current record for the preferred name in the destination concept which
   *   the moving names will be linked to.
   * @param object $operation
   *   Operation data, which may include a new parent.
   */
  private function moveNames($names, $linkToName, $operation) {
    $seniorName = $linkToName->taxon;
    $juniorName = $names->current()->taxon;
    $taxonMeaningId = $linkToName->taxon_meaning_id;
    $externalKey = $linkToName->external_key;
    $parentId = $linkToName->parent_id;
    $preferredNameChange = FALSE;
    foreach ($names as $mergedNameInfo) {
      $ttl = ORM::factory('taxa_taxon_list', $mergedNameInfo->id);
      if ($ttl->preferred === 't') {
        $preferredNameChange = TRUE;
      }
      $ttl->preferred = 'f';
      $ttl->taxon_meaning_id = $taxonMeaningId;
      $ttl->allow_data_entry = $linkToName->allow_data_entry;
      $ttl->parent_id = $parentId;
      $ttl->set_metadata();
      $ttl->save();
      $ttl->taxon->external_key = $externalKey;
      $ttl->taxon->organism_key = $linkToName->organism_key;
      $ttl->taxon->set_metadata();
      $ttl->taxon->save();
    }
    if ($preferredNameChange) {
      $this->repointLinksFromSynonymsToPreferredName($linkToName->id);
    }
    return "$juniorName merged into $seniorName";
  }

  /**
   * If an operation doesn't specify certain flags, they are set from a taxon.
   *
   * @param object $existingName
   *   Existing name data read from the database.
   * @param object $operation
   *   Operation specification. For flags such as non_native_flag, if null then
   *   the value will be copied over from the existing taxon. This object is
   *   passed by reference and gets updated.
   */
  private function applyTaxonDefaultsToOperation($existingName, &$operation) {
    if ($operation->marine === NULL) {
      $operation->marine = $existingName->marine_flag;
    }
    if ($operation->terrestrial === NULL) {
      $operation->terrestrial = $existingName->terrestrial_flag;
    }
    if ($operation->freshwater === NULL) {
      $operation->freshwater = $existingName->freshwater_flag;
    }
    if ($operation->non_native === NULL) {
      $operation->non_native = $existingName->non_native_flag;
    }
    if ($operation->redundant === NULL) {
      $operation->redundant = $existingName->organism_deprecated;
    }

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
      'taxa_taxon_list:allow_data_entry' => ($operation->redundant === NULL || $operation->redundant === 'f') ? 't' : 'f',
      'taxa_taxon_lists:manually_entered' => 'f',
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
      'taxon:organism_deprecated' => ($operation->redundant === NULL || $operation->redundant === 'f') ? 'f' : 't',
      'taxon:name_deprecated' => ($operation->redundant === NULL || $operation->redundant === 'f') ? 'f' : 't',
    ];
  }

  /**
   * Returns taxon info referred to by current_organism_key or current_name.
   *
   * Current name can be used to refer to a taxon name added in same batch.
   */
  private function getCurrentTaxa(&$operation) {
    if (empty($operation->current_organism_key) && !empty($operation->current_name)) {
      $currentName = pg_escape_literal($this->db->getLink(), $operation->current_name);
      $qry = <<<SQL
SELECT organism_key
FROM uksi_operations
WHERE sequence < $operation->sequence
AND taxon_name=$currentName
AND LOWER(operation)='new taxon'
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
      return $this->getTaxaForKeys(['organism_key' => $operation->current_organism_key]);
    }
    return [];
  }

  /**
   * Retrieves some info about taxon names linked to certain key/value pairs.
   *
   * @param array $search
   *   Associative array of keys and values to search against the taxon table,
   *   e.g. external_key, organism_key or search_code. Can also filter against
   *   taxa_taxon_lists fields if key is prefixed "ttl.".
   *
   * @return array
   *   Query result which can be iterated, with accepted names first.
   */
  private function getTaxaForKeys(array $search) {
    $where = [
      'ttl.taxon_list_id' => $this->getTaxonListId(),
      'ttl.deleted' => 'f',
      't.deleted' => 'f',
    ];
    foreach ($search as $key => $value) {
      // Table alias defaults to t.
      $where[strpos($key, '.') === FALSE ? "t.$key" : $key] = $value;
    }
    return $this->db->select('ttl.id, ttl.taxon_meaning_id, ttl.taxon_id, ttl.preferred, ttl.parent_id, ttl.allow_data_entry, ttl.common_taxon_id, ' .
        't.taxon, t.authority, t.attribute, t.search_code, t.external_key, t.organism_key, t.taxon_rank_id, ' .
        't.taxon_group_id, t.marine_flag, t.freshwater_flag, t.terrestrial_flag, t.non_native_flag, ' .
        't.organism_deprecated, t.name_deprecated, ttl.manually_entered')
      ->from('taxa_taxon_lists AS ttl')
      ->join('taxa as t', 't.id', 'ttl.taxon_id')
      ->where($where)
      ->orderby('preferred', 'DESC')
      ->get()->result_array();
  }

  /**
   * Generates an error message if provided organism key exists.
   *
   * Only errors if the existing organism key was from a previous operation.
   * Manually added taxa (via the warehouse UI) causing the duplicate are
   * allowed and returned so they can be overwritten.
   *
   * @param string $operationName
   *   Name of the operation for the error message.
   * @param string $organismKey
   *   Key to check.
   *
   * @return object|null
   *
   */
  private function assertOrganismKeyIsNew($operationName, $organismKey) {
    $existing = $this->getTaxaForKeys([
      'organism_key' => $organismKey,
    ]);
    foreach ($existing as $taxon) {
      if ($taxon->manually_entered === 'f') {
        $this->operationErrors[] = "$operationName operation has provided an organism_key which is not new";
        return;
      }
    }
    // If the existing taxon was manually entered, we are allowed to overwrite
    // it. This allows ad-hoc addition of new taxa without breaking the sync.
    // So return it to allow the IDs to be used for update.
    if (count($existing) > 0) {
      return ((array) $existing)[0];
    }
    return NULL;
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
   * @param bool $preferAllowDataEntry
   *   Set to TRUE to allow selection of a non-redundant parent
   *   (allow_data_entry=true) even if there are other redundant possible
   *   parents, as long as there is only 1 non-redundant found. A redundant
   *   parent could still be returned if there are none that are not redundant.
   *
   * @return int
   *   Taxa_taxon_lists.id.
   */
  private function getParentTtlId($operation, $preferAllowDataEntry = FALSE) {
    if (!empty($operation->parent_name) && empty($operation->parent_organism_key)) {
      // Parent identified by name which must refer to the last added taxa with
      // the given name. So use the new taxon operation to find the parent's
      // organism key.
      $parentName = pg_escape_literal($this->db->getLink(), $operation->parent_name);
      $qry = <<<SQL
SELECT organism_key
FROM uksi_operations
WHERE (batch_processed_on<'$operation->batch_processed_on' OR (batch_processed_on='$operation->batch_processed_on' AND operation_priority<'$operation->operation_priority') OR sequence<$operation->sequence)
AND lower(taxon_name)=lower($parentName)
AND lower(operation)='new taxon'
ORDER BY batch_processed_on DESC, operation_priority DESC, sequence DESC
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
      $parent = $this->db->select('ttl.id, ttl.allow_data_entry')
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
        ->orderby('allow_data_entry', 'DESC')
        ->limit(2)
        ->get()->result_array(TRUE);
      if (count($parent) === 1) {
        return $parent[0]->id;
      }
      if (count($parent) > 1) {
        if ($preferAllowDataEntry) {
          // Check if only 1 has allow_data_entry=true, if so it can be
          // returned.
          if ($parent[0]->allow_data_entry === 't' && $parent[1]->allow_data_entry === 'f') {
            return $parent[0]->id;
          }
        }
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
        ->where("lower(rank)='" . strtolower($operation->rank) . "'")
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
