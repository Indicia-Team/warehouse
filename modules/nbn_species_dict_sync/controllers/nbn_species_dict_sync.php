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
 * @package	NBN Species Dict Sync
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Controller class for the various NBN Sync tabs for the optional NBN Species Dict
 * Sync module.
 */
class Nbn_species_dict_sync_Controller extends Controller {
  
  /**
   * Provide a controller path for the content of the NBN Syncg tab for taxon groups.
   */
  public function taxon_groups() {
    $view = new View('nbn_species_dict_sync/taxon_group');
    $this->template = $view;
    $this->template->render(true);
  }

  /**
   * Controller method for synching taxon groups with the Species Dictionary.
   */   
  public function taxon_groups_sync() {
    require DOCROOT.'modules/nbn_species_dict_sync/lib/nusoap.php';
    $client = new nusoap_client('http://www.nbnws.net/ws_3_5/GatewayWebService?wsdl', true);
    $query1 = '<TaxonReportingCategoryListRequest xmlns="http://www.nbnws.net/TaxonReportingCategory" registrationKey="5c3c4776db01a696885c0721055f9bacd7f10ec9">'.
        '</TaxonReportingCategoryListRequest>';
    $response = $client->call('GetTaxonReportingCategoryList', $query1);
    kohana::log('debug', $response);
    $error = $client->getError();
    if ($error) {
      $this->error($error, $message, $messageType);
    } else {
      $this->sync_taxon_groups($response['TaxonReportingCategoryList']['TaxonReportingCategory']);
      $message = "Synchronisation completed OK";
    }
    if (request::is_ajax()) {
      echo $message;
    } else {
      $this->session = new Session;
      $this->session->set_flash("flash_$messageType", $message);
      url::redirect('taxon_group?tab=NBN_Sync');
    }
  }
  
  /**
   * Actually performs the task of synching the response from the Species Dictionary web services into the taxon_groups table.
   */
  private function sync_taxon_groups($list) {
    $this->db = new Database('default');
    $groups = $this->db->select('id', 'title', 'external_key')
            ->from('taxon_groups')
            ->where('external_key is not null')
            ->get();
    $existing = array();
    // get an array of the taxon groups in the db, so we don't keep hitting db
    foreach ($groups as $group) {
      $existing[$group->external_key]=array($group->id,$group->title);
    }
    // loop through the taxon reporting categories from the web service
    foreach ($list as $trc) {
      unset($groupModel);
      if (array_key_exists($trc['!taxonReportingCategoryKey'], $existing)) {
        // got an existing one in the db. Check the title is correct.
        if ($existing[$trc['!taxonReportingCategoryKey']][1]!=$trc['!']) {
          // title needs an update
          $groupModel = ORM::Factory('taxon_group', $existing[$trc['!taxonReportingCategoryKey']][0]);                
        }
        // the else case here means the taxon group exists and is up to date. 
      } else {
        // need a new record
       $groupModel = ORM::Factory('taxon_group');
      }
      if (isset($groupModel)) {
        $values = array(
          'external_key' => $trc['!taxonReportingCategoryKey'],
          'title' => $trc['!']
        );
        $groupModel->set_submission_data($values);
        $groupModel->submit(false);
      }      
    }
  }

  /**
   * Provide a controller path for the content of the NBN Sync tab for taxon designations.
   */
  public function taxon_designations() {
    $view = new View('nbn_species_dict_sync/taxon_designation');
    $this->template = $view;
    $this->template->render(true);
  }

  
  /**
   * Controller path for the service call which synchronises the taxon designations.
   */
  public function taxon_designations_sync() {
    $message="Synchronising.";
    $messageType="info";
    require DOCROOT.'modules/nbn_species_dict_sync/lib/nusoap.php';
    try {
      $client = new nusoap_client('http://www.nbnws.net/ws_3_5/GatewayWebService?wsdl', true);
      $query1 = '<DesignationListRequest xmlns="http://www.nbnws.net/Designation" registrationKey="5c3c4776db01a696885c0721055f9bacd7f10ec9">'.
          '</DesignationListRequest>';
      
      $response = $client->call('GetDesignationList', $query1);
      $error = $client->getError();
      if ($error) {
        $this->error($error, $message, $messageType);
      } else {
        $this->sync_designations($response);
        $message = "Synchronisation completed OK";
      }
    }
    catch(Exception $e) {
      $this->error($e->getMessage(), $message, $messageType);
    }
    if (request::is_ajax()) {
      echo $message;
    } else {
      $this->session = new Session;
      $this->session->set_flash("flash_$messageType", $message);
      url::redirect('taxon_group?tab=NBN_Sync');
    }
  }

  /**
   * Method that takes the output of the NBN Web services designation list request
   * and ensures that the data is all in the taxon designations part of the database.
   * @param array $response
   */
  private function sync_designations($response) {
    $catsDone = array();
    $this->db = new Database('default');
    $designations = $this->db->select('id', 'code')
            ->from('taxon_designations')
            ->get();
    $existing = array();
    // get an array of the designations in the db, so we don't keep hitting db
    foreach ($designations as $designation) {
      $existing[$designation->code]=$designation->id;
    }

    // get the id of the termlist that will hold categories
    $query = $this->db->select('id')
        ->from('termlists')
        ->where('external_key','indicia:taxon_designation_categories')->get();
    if (count($query)===0)
      throw new Exception('Taxon designation categories termlist not found');
    $row = $query[0];
    $catListId = $row->id;
    foreach ($response['DesignationCategory'] as $category) {
      $catName = $category['!name'];
      // check $catName is in the termlist, insert if required. Only check each
      // category once
      if (!array_key_exists($catName, $catsDone)) {
        $existingCat = $this->db
            ->select('id')
            ->from('list_termlists_terms')
            ->where(array(
                'termlist_external_key'=>'indicia:taxon_designation_categories',
                'term'=>$catName
            ))
            ->get();
        if (count($existingCat)===0) {
          $submission = array(
            'termlists_term:termlist_id'=>$catListId,
            'termlists_term:preferred'=>'t',
            'term:term'=>$catName,
            'term:fk_language' => 'eng'
          );
          $termModel = ORM::factory('termlists_term');
          $termModel->set_submission_data($submission);
          $termModel->submit(false);
          $catsDone[$catName] = $termModel->id;
          $currentCatId = $termModel->id;
        } else {
          $existingCat = $existingCat[0];
          $currentCatId = $existingCat->id;
        }
      } else {
        $currentCatId = $catDone[$catName];
      }
      foreach ($category['DesignationList']['Designation'] as $designation) {
        // link to existing model if there is already a record for this designation key
        if (array_key_exists($designation['key'], $existing))
          $desModel = ORM::Factory('taxon_designation', $existing[$designation['key']]);
        else
          $desModel = ORM::Factory('taxon_designation');
        $values = array(
            'title' => $designation['name'],
            'code' => $designation['key'],
            'abbreviation' => $designation['abbreviation'],
            'description' => $designation['description'],
            'category_id' => $currentCatId
        );
        $desModel->validate(new Validation($values), true);
        // @todo Do we need to check for errors?
      }
    }
  }
  
  private function error($error, &$message, &$messageType) {
    kohana::log('error', "NBN Dictionary Sync failed.\n$error");
    $message .= "The synchronisation operation failed. More information is in the log.";
    $messageType="error";
  }
  
  /**
   * Provide a controller path for the content of the NBN Sync tab for taxon lists.
   */
  public function taxon_lists($id) {
    $view = new View('nbn_species_dict_sync/taxon_list');
    $view->taxon_list_id=$id;
    $this->template = $view;
    $this->template->render(true);
  }
  
  public function taxon_list_sync($id) {
    kohana::log('debug', 'in sync method');
    $message="Synchronising.";
    $messageType="info";
    require DOCROOT.'modules/nbn_species_dict_sync/lib/nusoap.php';
    try {
      $client = new nusoap_client('http://www.nbnws.net/ws_3_5/GatewayWebService?wsdl', true);
      $query1 = '<tax:SpeciesListRequest xmlns:tax1="http://www.nbnws.net/TaxonReportingCategory" '.
          'xmlns:tax="http://www.nbnws.net/Taxon" registrationKey="5c3c4776db01a696885c0721055f9bacd7f10ec9">'.
          '<tax1:TaxonReportingCategoryKey>NHMSYS0000080067</tax1:TaxonReportingCategoryKey>'.
          '</tax:SpeciesListRequest>';
      
      $response = $client->call('GetSpeciesList', $query1);
      kohana::log('debug', 'got response');
      $error = $client->getError();
      if ($error) {
        kohana::log('debug', 'got error '.$error);
        $this->error($error, $message, $messageType);
      } else {
        $this->sync_species($response['SpeciesList']['Species'], $id);
        $message = "Synchronisation completed OK";
      }
    }
    catch(Exception $e) {
      $this->error($e->getMessage(), $message, $messageType);
    }
    echo $message;
  }

  /**
   * Method that takes the output of the NBN Web services species list request
   * and ensures that the data is all in the taxa_taxon_list part of the database.
   * @param array $list
   * @param int $list_id
   */
  private function sync_species($list, $list_id) {
    kohana::log('debug', 'synching list');
    $this->db = new Database('default');
    $species_list = $this->db->select('id', 'external_key')
            ->from('list_taxa_taxon_lists')
            ->where('taxon_list_id', $list_id)
            ->get();
    $existing = array();
    // get an array of the species in the db, so we don't keep hitting db
    foreach ($species_list as $species) {
      $existing[$species->external_key]=$species->id;
    }    
    foreach ($list as $species) {    
      kohana::log('debug', $species['!taxonVersionKey']);
      // link to existing model if there is already a record for this taxon version key
      if (array_key_exists($species['!taxonVersionKey'], $existing))
        $speciesModel = ORM::Factory('taxa_taxon_list', $existing[$species['!taxonVersionKey']]);
      else
        $speciesModel = ORM::Factory('taxa_taxon_list');
      $values = array(
        'taxon:taxon' => $species['ScientificName'],
        'taxon:fk_language' => 'lat',
        'taxon:external_key' => $species['!taxonVersionKey']
      );
      $speciesModel->set_submission_data($values);
      $speciesModel->submit(false);      
    }

  }

}

?>