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
   * Provide a controller path for the content of the NBN Sync tab for taxon groups.
   */
  public function taxon_groups() {
    try {
      $regKey = kohana::config('nbn_species_dict_sync.registration_key');
      $view = new View('nbn_species_dict_sync/taxon_group');
      $this->template = $view;
      $this->template->render(true);
    } catch (Kohana_Exception $e) {
      self::requestRegKey();
    }
  }
  
  /**
   * If the registration key config is missing, we need to output a message requesting that it is sorted.
   */
  private function requestRegKey() {
    $view = new View('templates/error_message');
    $view->message = '<p><strong>Before using the NBN synchronisation facilities, please create a configuration file for your NBN registration key. </strong></p>'.
        '<p>To do this, find the folder '.DOCROOT.'modules'.DIRECTORY_SEPARATOR.'nbn_species_dict_sync'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.
        ' then rename the file nbn_species_dict_sync.php.example to nbn_species_dict_sync.php. Now, edit the file using '.
        'a text editor and enter your registration key between the quotes on the line $config[\'registration_key\']=\'\';</p>';
    $this->template = $view;
    $this->template->render(true);
  }  

  /**
   * Controller method for synching taxon groups with the Species Dictionary.
   */   
  public function taxon_groups_sync() {
    require DOCROOT.'modules/nbn_species_dict_sync/lib/nusoap.php';
    $client = new nusoap_client('http://www.nbnws.net/ws_3_5/GatewayWebService?wsdl', true);
    $client->setGlobalDebugLevel(0);
    $query1 = '<TaxonReportingCategoryListRequest xmlns="http://www.nbnws.net/TaxonReportingCategory" registrationKey="'.
        kohana::config('nbn_species_dict_sync.registration_key').'">'.
        '</TaxonReportingCategoryListRequest>';
    $response = $client->call('GetTaxonReportingCategoryList', $query1);
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
    try {
      $regKey = kohana::config('nbn_species_dict_sync.registration_key');
      $view = new View('nbn_species_dict_sync/taxon_designation');
      $this->template = $view;
      $this->template->render(true);
    } catch (Kohana_Exception $e) {
      self::requestRegKey();
    }
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
      $query1 = '<DesignationListRequest xmlns="http://www.nbnws.net/Designation" registrationKey="'.
          kohana::config('nbn_species_dict_sync.registration_key').'">'.
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
  
  /**
   * Error handler for non-chunked synching methods
   */
  private function error($error, &$message, &$messageType) {
    kohana::log('error', "NBN Dictionary Sync failed.\n$error");
    $message .= "The synchronisation operation failed. More information is in the log.";
    $messageType="error";
  }
  
  /**
   * Provide a controller path for the content of the NBN Sync tab for taxon lists.
   */
  public function taxon_lists($id) {
    try {
      $regKey = kohana::config('nbn_species_dict_sync.registration_key');
      $view = new View('nbn_species_dict_sync/taxon_list');
      $view->taxon_list_id=$id;
      $this->template = $view;
      $this->template->render(true);
    } catch (Kohana_Exception $e) {
      self::requestRegKey();
    }
  }
  
  /**
   * Controller action for the synchronisation of a taxon list with NBN Species Dictionary content. This is a chunked process, so
   * the synchronise button will trigger a JavaScript function which repetitively calls this method until the process is complete.
   * Uses a simple state machine to keep track of which step of the import it is on. 
   */
  public function taxon_list_sync($id) {
    $complete=false;
    $this->db = new Database('default');
    if (!isset($_GET['task_id'])) {
      // First call to the sync method from the client. Create a task ID and return it. Set a value in the cache to indicate our current state.
      $task_id = time().rand(0,1000);
      $cacheId = 'dict-sync-'.$task_id;
      $cache = Cache::instance();
      $stateData = array(
        'task_id' => $task_id,
        'state'=>0,
        'list_id'=>$id,
        'progress' => 0,
        'errors' => array(),
        'statusText' => 'Loading existing data from database',
        'mode' => isset($_GET['mode']) ? $_GET['mode'] : 'all',
        'speciesIdx' => 0,
        'groupIdx' => 0,
        'groups' => array('dummy')
      );
      $cache->set($cacheId, json_encode($stateData));    
    } else {
      $task_id = $_GET['task_id'];
      $cacheId = 'dict-sync-'.$task_id;
      $cache = Cache::instance();
      // @todo handle timeout of the cache data.
      $stateData = json_decode($cache->get($cacheId), true);
      switch ($stateData['state']) {
        case 0: 
          $this->loadExistingList($stateData);
          // can skip loading taxon groups if just handling an existing list of taxa
          $stateData['state']=1;
          $stateData['statusText']='Loading taxon groups list from database';
          $cache->set($cacheId, json_encode($stateData));
          break;
        case 1:
          $this->loadTaxonGroups($stateData);
          $stateData['state']=2;
          $cache->set($cacheId, json_encode($stateData));
          $stateData['statusText']='Waiting for data from Species Dictionary';
          break;
        case 2:
          $complete = $this->processNextDictBlock($stateData);
          $cache->set($cacheId, json_encode($stateData));
      }
    }
    // return some status data to the JavaScript so it can output progress, then call us back again.
    echo json_encode(array(
      'task_id' => $task_id,
      'progress' => $stateData['progress'],
      'complete' => $complete,
      'errors' => $stateData['errors'],
      'statusText' => $stateData['statusText']
    ));
  }
  
  /**
   * To improve performance a bit, we cache content from the existing species list so we know which species are already in the data. 
   */
  private function loadExistingList(&$stateData) {
    kohana::log('debug', 'loadExistingList');
    $species_list = $this->db->select('id', 'external_key')
            ->from('list_taxa_taxon_lists')
            ->where(array('taxon_list_id'=>$stateData['list_id'], 'preferred'=>'t'))
            ->get();
    $existing = array();
    // get an array of the species in the db, so we don't keep hitting db
    foreach ($species_list as $species) {
      $existing[$species->external_key]=$species->id;
    }
    $stateData['existing'] = $existing;
  }
  
  /**
   * Load the taxon groups into the state data from the taxon groups table. This should have been pre-populated with the 
   * Species Dictionary Reporting Categories data.
   */
  private function loadTaxonGroups(&$stateData) {
    // when only loading data for existing taxa, no need for taxon groups to loop through
    if ($stateData['mode']=='existing' || $stateData['mode']=='designations')
      return;
    kohana::log('debug', 'loadTaxonGroups');
    $this->db->select('taxon_groups.id, taxon_groups.external_key')
          ->from('taxon_groups')
          ->where('external_key is not null');
    // if taxon groups taxon lists module installed, then we only load the groups associated with the current list.
    if (in_array(MODPATH.'taxon_groups_taxon_lists', kohana::config('config.modules')))
      $this->db
          ->join('taxon_groups_taxon_lists', 'taxon_groups_taxon_lists.taxon_group_id', 'taxon_groups.id')
          ->where('taxon_groups_taxon_lists.taxon_list_id', $stateData['list_id']);
    $stateData['groups'] = $this->db
          ->get()
          ->result_array(false);
    $stateData['groupIdx'] = 0;
  }
  
  /**
   * For each taxon group, first call will request the taxonomy data for the group. Subsequent calls will each process
   * a single taxon till the list is done. Then moves on to the next group. 
   * @return boolean True if there is no more work to do.
   */
  private function processNextDictBlock(&$stateData) {
    kohana::log('debug', 'processNextDictBlock');
    if ($stateData['groupIdx']>=count($stateData['groups']))
      // have done the last group, so everything now complete.
      return true;
    // note we don't bother getting species list data from the web service if only updating the existing list.
    if (isset($stateData['web_service_response']) || $stateData['mode']=='existing' || $stateData['mode']=='designations') {
      $this->processWebServiceResponse($stateData);
    } else {
      $this->getWebServiceResponse($stateData);
    }
    return false;
  }
  
  /**
   * Requests the content of the next taxon group (reporting category) from the web service.
   */
  private function getWebServiceResponse(&$stateData) {
    kohana::log('debug', 'getWebServiceResponse');
    $group=$stateData['groups'][$stateData['groupIdx']];
    require DOCROOT.'modules/nbn_species_dict_sync/lib/nusoap.php';
    $client = new nusoap_client('http://www.nbnws.net/ws_3_5/GatewayWebService?wsdl', true);
    $query1 = '<tax:SpeciesListRequest xmlns:tax1="http://www.nbnws.net/TaxonReportingCategory" '.
        'xmlns:tax="http://www.nbnws.net/Taxon" registrationKey="'.kohana::config('nbn_species_dict_sync.registration_key').'">'.
        '<tax1:TaxonReportingCategoryKey>'.$group['external_key'].'</tax1:TaxonReportingCategoryKey>'.
        '</tax:SpeciesListRequest>';
    $stateData['web_service_response'] = $client->call('GetSpeciesList', $query1);
    $stateData['speciesIdx']=0;
    $error = $client->getError();
    if ($error) {
      kohana::log('debug', 'got error '.$error);
      throw new Exception($error);
    }
  }
  
  /**
   * Method that takes the output of the NBN Web services species list request
   * and ensures that the data is all in the taxa_taxon_list part of the database.
   */
  private function processWebServiceResponse(&$stateData) {
    kohana::log('debug', 'processWebServiceResponse');
    if ($stateData['mode']=='existing' || $stateData['mode']=='designations') {
      // just use the existing list species data to pull in taxonomy info for. No need to look for new species.
      if ($stateData['speciesIdx']>=count($stateData['existing'])) {
        $stateData['complete']=true;
        return;
      }
      $tvks = array_keys($stateData['existing']);
      $tvk = $tvks[$stateData['speciesIdx']];
      $identifier = $tvk;
      $total = count($stateData['existing']);
    } else {
      $list = $stateData['web_service_response']['SpeciesList']['Species'];
      if ($stateData['speciesIdx']>=count($list)) {
        unset($stateData['web_service_response']);
        $stateData['groupIdx'] = $stateData['groupIdx'] + 1;
        $stateData['statusText']='Waiting for data from Species Dictionary';
        $stateData['speciesIdx']=0;
        return;
      }
      $species = $list[$stateData['speciesIdx']];
      $tvk = $species['!taxonVersionKey'];
      $identifier = $species['ScientificName'];
      $total = count($list);
    }
    $stateData['speciesIdx'] = $stateData['speciesIdx']+1;
    kohana::log('debug', 'found:'.$tvk);
    // link to existing model if there is already a record for this taxon version key
    if (array_key_exists($tvk, $stateData['existing'])) {
      if ($stateData['mode']=='new') {
        $stateData['statusText']="Skipping $identifier";
        kohana::log('debug', 'taxon already exists:'.$tvk);
        // don't bother returning for each species in this case as the web service call overhead slows us down. Return every so often
        // to prevent freezing.
        if ($stateData['speciesIdx']/20 == round($stateData['speciesIdx']/20)) $this->processWebServiceResponse($stateData);
      }
      else {
        $speciesModel = ORM::Factory('taxa_taxon_list', $stateData['existing'][$tvk]);
        kohana::log('debug', 'using existing model for '.$tvk);
      }
    } else {
      $speciesModel = ORM::Factory('taxa_taxon_list');
      kohana::log('debug', 'using new model for '.$tvk);
    }
    if (isset($speciesModel)) 
      $this->taxonomySearch($tvk, $speciesModel, $stateData);
    // calculate a percentage progress.
    $stateData['progress'] = 100 * ($stateData['speciesIdx'] + $stateData['groupIdx']*$total) / (count($stateData['groups'])*$total);
  }

  /**
   * For an individual taxon requests full taxonomy information from the web services and creates or edits the taxon, synonyms, 
   * common names and designations for it.
   */
  private function taxonomySearch($tvk, $speciesModel, &$stateData) {
    kohana::log('debug', 'taxonomySearch '.$tvk);
    require DOCROOT.'modules/nbn_species_dict_sync/lib/nusoap.php';
    $client = new nusoap_client('http://www.nbnws.net/ws_3_5/GatewayWebService?wsdl', true);
    $query1 = '<tax:TaxonomySearchRequest xmlns:tax="http://www.nbnws.net/Taxon/Taxonomy" xmlns:tax1="http://www.nbnws.net/Taxon" '.
        'registrationKey="'.kohana::config('nbn_species_dict_sync.registration_key').'" '.
        'includeDesignation="true">'.
        "<tax1:TaxonVersionKey>$tvk</tax1:TaxonVersionKey>".
        '</tax:TaxonomySearchRequest>';
    $response = $client->call('GetTaxonomySearch', $query1);
    $error = $client->getError();
    if ($error) {
      $stateData['errors'][] = "An error occurred inserting taxon $tvk. The error was: $error";          
      return;
    }
    $taxon = $response['Taxa']['Taxon'];
    if ($stateData['mode']!='designations') {
      $values = array(
        'taxa_taxon_list:taxon_list_id' => $stateData['list_id'],
        'taxa_taxon_list:preferred' => 't',
        'taxon:taxon' => $taxon['TaxonName']['!'],
        'taxon:fk_language' => 'lat',
        'taxon:external_key' => $tvk,
        'taxon:taxon_group_id' =>  $stateData['groups'][$stateData['groupIdx']]['id']
      );
      if (!empty($taxon['Authority']))
        $values['taxon:authority'] = utf8_encode($taxon['Authority']);
      if ($speciesModel->loaded) {
        kohana::log('debug', 'Using existing model '.$speciesModel->id);
        $values['taxa_taxon_list:id']=$speciesModel->id;
        $values['taxon:id']=$speciesModel->taxon_id;
        $values['taxa_taxon_list:taxon_meaning_id']=$speciesModel->taxon_meaning_id;
      }
      $commonNames = array();
      $synonyms = array();
      
      if (!empty($taxon['SynonymList'])) {
        // web service can return an array or a single item. To make it easier, we will convert to an array
        if (isset($taxon['SynonymList']['Taxon']['TaxonName']))
          $synonymSet = $taxon['SynonymList'];
        else 
          $synonymSet = $taxon['SynonymList']['Taxon'];
        foreach ($synonymSet as $synonym) {
          if ($synonym['TaxonName']['!isScientific']) {
            $synAuthority = isset($synonym['Authority']) ? $synonym['Authority'] : '';
            $taxAuthority = isset($taxon['Authority']) ? $taxon['Authority'] : '';
            if ($synonym['TaxonName']['!'] != $taxon['TaxonName']['!'] || $synAuthority != $taxAuthority) {
              $synAsString = $synonym['TaxonName']['!'];
              if (!empty($synonym['Authority'])) $synAsString .= '|' . $synonym['Authority'];
              $synonyms[] = $synAsString;
            }
          } else {
            // We have to assume common names are english as we don't have any other info
            $commonNames[] = $synonym['TaxonName']['!'] . '|eng'; 
          }
        }
      }
      $values['metaFields:commonNames'] = utf8_encode(implode("\n", $commonNames));
      $values['metaFields:synonyms'] = utf8_encode(implode("\n", $synonyms));
      $speciesModel->set_submission_data($values);
      try {
        $id = $speciesModel->submit(false);
      } catch (Exception $e) {
        $stateData['errors'][] = "An error occurred inserting taxon ".$taxon['TaxonName']['!'].'. The error was: '.
            $e->getMessage();
      }
      if (!$id) {
        $stateData['errors'][] = "An error occurred inserting taxon ".$taxon['TaxonName']['!'].'. The error was: '.
            implode(';', $speciesModel->getAllErrors());
      }
    } elseif ($speciesModel->loaded)
      // just linking designations to the existing taxon
      $id = $speciesModel->id;
      
    if ($id) 
      $this->attachDesignations($speciesModel, $taxon, $stateData);
    else {
      kohana::log('debug', 'Taxon values which failed: '.print_r($values, true));
    }
    $stateData['statusText']='Processed '.$taxon['TaxonName']['!'];
  }
  
  /**
   * For an existing taxon in the database, attaches any designations in the web service response.
   */
  private function attachDesignations($speciesModel, $taxon, &$stateData) {
    if (!empty($taxon['TaxonDesignations'])) {
      $results = $this->db->select('taxa_taxon_designations.id, taxa_taxon_designations.taxon_designation_id, taxon_designations.code, '.
          'taxa_taxon_designations.start_date, taxa_taxon_designations.source, taxa_taxon_designations.geographical_constraint')
          ->from('taxa_taxon_lists')
          ->join('taxa', 'taxa.id', 'taxa_taxon_lists.taxon_id')
          ->join('taxa_taxon_designations', 'taxa_taxon_designations.taxon_id', 'taxa.id')
          ->join('taxon_designations', 'taxon_designations.id', 'taxa_taxon_designations.taxon_designation_id')
          ->where('taxa_taxon_lists.id', $speciesModel->id)
          ->get()->result_array(false);
      // sort these by code so we can look them up easily
      $existingDesignations = array();
      foreach ($results as $row) {
        $existingDesignations[$row['code']] = $row;
      }
      if (isset($taxon['TaxonDesignations']['TaxonDesignation']['Designation']))
        $designations = $taxon['TaxonDesignations'];
      else
        $designations = $taxon['TaxonDesignations']['TaxonDesignation'];
      foreach ($designations as $designation) {
        $key=$designation['Designation']['key'];
        unset($model);
        if (array_key_exists($key, $existingDesignations)) {
          // check if there are any changes
          $existingSource = empty($existingDesignations[$key]['source']) ? '' : $existingDesignations[$key]['source'];
          $existingGeoConstraint = empty($existingDesignations[$key]['geographical_constraint']) ? '' : $existingDesignations[$key]['geographical_constraint'];
          $newSource = empty($designation['source']) ? '' : $designation['source'];
          $newGeoConstraint = empty($designation['geographical_constraint']) ? '' : $designation['geographical_constraint'];
          if (strtotime($existingDesignations[$key]['start_date'])!=strtotime($designation['startDate'])
              || $existingSource != $newSource
              || $existingGeoConstraint != $newGeoConstraint) {
            $model = ORM::Factory('taxa_taxon_designation', $existingDesignations[$key]['id']);
            $taxon_designation_id = $existingDesignations[$key]['taxon_designation_id'];
            kohana::log('debug', "Existing Designation $key linked to taxon ".$taxon['TaxonName']['!']);
          }
        } else {          
          $des = $this->db->select('id')->from('taxon_designations')->where('code', $key)->get();
          if (count($des)===0)
            $stateData['errors'] = "Designation $key could not be found in the database, so it was not linked to taxon ".$taxon['TaxonName']['!'];
          else {
            // create a model ready for a new record
            $model = ORM::Factory('taxa_taxon_designation');
            $des = $des[0];
            $taxon_designation_id = $des->id;
             kohana::log('debug', "New Designation $key linked to taxon ".$taxon['TaxonName']['!']);
          }
        }
        if (isset($model)) {
          $values = array(
            'start_date' => $designation['startDate'],
            'source' => utf8_encode($designation['source']),
            'geographical_constraint' => isset($designation['geographicalConstraint']) ? utf8_encode($designation['geographicalConstraint']) : '',
            'taxon_id' => $speciesModel->taxon_id,
            'taxon_designation_id' => $taxon_designation_id
          );
          $model->set_submission_data($values);
          try {
            $id = $model->submit(false);
          } catch (Exception $e) {
            $stateData['errors'][] = "An error occurred inserting designation $key for taxon ".$taxon['TaxonName']['!'].'. The error was: '.
                $e->getMessage();
          }
          if (!$id) {
            $stateData['errors'][] = "An error occurred inserting designation $key for taxon ".$taxon['TaxonName']['!'].'. The error was: '.
                implode(';', $model->getAllErrors());
          }
        }
      }
    }
  }

}

?>