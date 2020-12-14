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
      'batch_processed_on' => 'Batch date',
      'has_errors' => '',
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
      ->query('SELECT * FROM uksi_operations WHERE operation_processed=false ORDER BY sequence ASC;')
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

  public function processNewTaxon($operation) {
    $this->checkOperationRequiredFields('New taxon', $operation, ['taxon_name', 'organism_key', 'taxon_version_key', 'rank', 'output_group']);
    $taxonListId = $this->getTaxonListId();
    $parentId = $this->getParentTtlId($operation);
    $rankId = $this->getTaxonRankId($operation);
    $taxonGroupId = $this->getTaxonGroupId($operation);
    // Assert organism_key does not exist.
    // If parent_organism_key, find parent_id or fail
    // Find taxon rank ID
    if (count($this->operationErrors) > 0) {
      return 'Error';
    }
    $fields = [
      'taxa_taxon_list:taxon_list_id' => $taxonListId,
      'taxa_taxon_list:parent_id' => $parentId,
      'taxa_taxon_list:preferred' => 't',
      'taxon:taxon' => $operation->taxon_name,
      'taxon:authority' => $operation->authority,
      'taxon:attribute' => $operation->attribute,
      'taxon:scientific' => 't',
      'taxon:taxon_rank_id' => $rankId,
      'taxon:taxon_group_id' => $taxonGroupId,
      'taxon:taxon_version_key' => $operation->taxon_version_key,
      'taxon:search_code' => $operation->taxon_version_key,
      'taxon:organism_key' => $operation->organism_key,
      'taxon:marine_flag' => $this->convertBool($operation->marine),
      'taxon:terrestrial_flag' => $this->convertBool($operation->terrestrial),
      'taxon:freshwater_flag' => $this->convertBool($operation->freshwater),
      'taxon:non_native_flag' => $this->convertBool($operation->non_native),
      'taxon:language_id' => $this->getLanguageId('lat'),
    ];
    $taxa_taxon_list = ORM::factory('taxa_taxon_list');
    //$submission = submission_builder::build_submission($fields, $taxa_taxon_list->get_submission_structure());
    $taxa_taxon_list->set_submission_data($fields);
    if (!$taxa_taxon_list->submit()) {
      $this->operationErrors[] = implode("\n", $taxa_taxon_list->getAllErrors());
      return 'Error';
    }
    // @todo Store the link to the taxon in the operation.
    return "New taxon $operation->taxon_name added";
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

  private function checkOperationRequiredFields($operationName, $operation, array $fields) {
    foreach ($fields as $field) {
      if ($operation->$field === NULL) {
        $this->operationErrors[] = "$operationName operation requires a value for $field";
        $this->operationErrors[] = var_export($operation, TRUE);
      }
    }
  }

  private function getParentTtlId($operation) {
    if (!empty($operation->parent_name) && empty($operation->parent_organism_key)) {
      // Parent identified by name which must refer to a recently added taxa.
      // So use the new taxon operation to find the parent's organism key.
      // @todo This query should filter to the current batch if possible.
      $parentAddedInOperation = $this->db->select('organism_key')
        ->from('uksi_operations')
        ->where([
          'sequence < $operation->sequence',
          'taxon_name' => $operation->parent_name,
          'operation' => 'New taxon',
          'batch_processed_on' => $operation->batch_processed_on,
        ])
        ->orderby('sequence', 'DESC')
        ->limit(1)
        ->get()->current();
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

  private function getTaxonListId() {
    $taxonListId = kohana::config('uksi_operations.taxon_list_id', FALSE, FALSE);
    if (!$taxonListId) {
      $this->operationErrors[] = "The UKSI operations module configuration is incomplete - missing taxon_list_id.";
    }
    return $taxonListId;
  }

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
