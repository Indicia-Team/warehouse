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
 * @package    Survey Structure Export
 * @subpackage Controllers
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Controller class for the survey structure export plugin module.
 */
class Survey_structure_export_Controller extends Indicia_Controller {
  //Note, do not change the ordering of this array as it is important items are processed in correct order.
  //For instance you need to add a language before you can point a term at it.
  private $tableNames = array("languages", "terms", "termlists", "termlists_terms",
                              "occurrence_attributes","occurrence_attributes_websites", 
                              "sample_attributes", "sample_attributes_websites");
  
  private $tableLookupToDrillDown = array('sample_attributes_websites'=>array('sample_attribute_id'=>'sample_attributes', 'restrict_to_sample_method_id'=>'termlists_terms'),
                                          'sample_attributes'=>array('termlist_id'=>'termlists'),
                                          'occurrence_attributes_websites'=>array('occurrence_attribute_id'=>'occurrence_attributes'),
                                          'occurrence_attributes'=>array('termlist_id'=>'termlists'),
                                          'termlists_terms'=>array('term_id'=>'terms','termlist_id'=>'termlists'),
                                          'terms'=>array('language_id'=>'languages'));
  
  private $joinTables = array('termlists_terms'=>array('termlist_id'=>'termlists','term_id'=>'terms'));
  
  //An array of table names as the key. The values are arrays of items we don't want to use when hashing (lookups).
  private $itemsToFilterPerTable = array('sample_attributes_websites'=>array('id','restrict_to_survey_id','sample_attribute_id','restrict_to_sample_method_id'),
                                   'sample_attributes'=>array('id','termlist_id'),
                                   'occurrence_attributes_websites'=>array('id','restrict_to_survey_id','occurrence_attribute_id'),
                                   'occurrence_attributes'=>array('id','termlist_id'),
                                   'termlists_terms'=>array('id','termlist_id','parent_id','term_id','meaning_id'),
                                   'termlists'=>array('id','parent_id'),
                                   'terms'=>array('id','language_id'),
                                   'languages'=>array('id'));
  
  private $queryTypes = array('get_termlists_from_sample_attribute', 
                        'get_termlists_from_restrict_to_sample_method', 
                        'get_termlists_from_occurrence_attribute');
 
  /**Display the export page*/
  public function index() {
    $this->view = new View('survey_structure_export/index');
    $this->view->surveyId=$this->uri->last_segment();
    //Get the attribute data (including termlists) associated with the survey ready to export
    $export = $this->getDatabaseData($this->view->surveyId);
    $this->view->export = json_encode($export);
    $this->template->content = $this->view;
  }
 
  /**Perform the import*/
  //UNCLEAN
  //BROKEN
  public function save() {
    $surveyId = $this->uri->last_segment();
    try {
      $importData = json_decode($_POST['import_survey_structure'], true);
      $this->doImport($importData,$_POST['survey_id']);
      kohana::log('debug', 'setting template title');
      $this->template->title = 'The Import Has Been Completed Successfully';
      $this->view = new View('survey_structure_export/import_complete');
      $this->template->content = $this->view;
      
    } catch (Exception $e) {
      error::log_error('Exception during survey structure import', $e);
      $this->template->title = 'Oh No!';
      $this->view = new View('templates/error_message');
      $this->view->message='I tried to complete the import but something went wrong!
                            Please make make sure the import data is valid.';
      $this->template->content = $this->view;
    }
  }

  /**
   *
   * Call the methods required to do the import.
   *
   * @param array $importData
   * @param int $surveyId The ID of the survey in the database we are importing into.
   */
  public function doImport($importData, $surveyId) {
    //Get the data from the database before the import is done. Otherwise we can't do duplicate comparisons with it.
    $existingData = $this->getDatabaseData(null);
    //We need to get the website_id for the survey we are importing into so we can add it to the rows we are adding for tables
    //that support it
    $surveyWebsiteIdArray = $this->db
      ->select('website_id')
      ->from('surveys')
      ->where(array('id'=>$surveyId))
      ->get()->result_array(FALSE);
    $surveyWebsiteId = $surveyWebsiteIdArray[0]['website_id'];
    //Create the hashes for the data we are importing and also the existing data in the database.
    //This is allows us to detect duplicates if hashes match.
    $importHashes = $this->getHashes($importData);
    $existingHashes = $this->getHashes($existingData);
    //Cycle through each table and do the import.
    //The lookups variable is an array of table arrays. The table array keys are the old ids from the import data and
    //the values are the new ids in the databases. This array is created by the importTable method as it does its work.
    $newLookups = array();
    foreach ($this->tableNames as $tableName)
      $this->importTable($importData, $tableName, $importHashes, $existingHashes, $newLookups,$surveyWebsiteId);

    //We need to fix any columns which are self-referencing (such as parent_id) as this can only be done after 
    //the import is finished.
    //Note we are not importing the parent_id from the termlists table, so the only
    //parent_id column to fix is from termlists_terms
    $this->fixSelfReferencingIds($importData, $newLookups, 'termlists_terms', 'parent_id');
  }
 
 
  /**
   *
   * @param array $data The data to be imported
   * @param string $tableName Name of table we are importing
   * @param array $importHashes The hash codes for the rows in the import data
   * @param array $existingHashes The hash codes for the rows in the existing database data
   * @param array $newLookups An array of arrays. There is an array for each table processed so far.
   * In each table array, the keys are the ids for the items in the import data. The values are the new ids in the database.
   * So the value is either a new row id, or for duplicates if is an existing row id.
   * @param id $surveyWebsiteId The id of the website for the survey we are importing into
   */
  public function importTable($data, $tableName, $importHashes, $existingHashes, &$newLookups,$surveyWebsiteId) {
    $newMeaningIds = array();
    if (!empty($data[$tableName])) {
      foreach($data[$tableName] as $rowData) {
        if ((in_array($importHashes[$tableName][$rowData['id']], $existingHashes[$tableName]))) {
          //If we detect the row to add is a duplicate, then we search through the hashes of the existing data
          //until we find the matching row. When we find it, we can note the existing
          //row's ID (which is the key in the existingHash row)
          foreach ($existingHashes[$tableName] as $existingHashRowId=>$existingHash) {
            if ($existingHash===$importHashes[$tableName][$rowData['id']])
              $newLookups[$tableName][$rowData['id']] = $existingHashRowId;
          }
        //If we don't find a duplicate, then we add a new row to the database and note the new id.
        } else {
          $newLookups[$tableName][$rowData['id']] =  $this->importRow($tableName, $rowData, $newLookups,$surveyWebsiteId,$newMeaningIds);   
        } 
      }
    }
  }
 
  /**
   *
   * @param string $tableName The table name of the row to be imported.
   * @param integer $rowToAdd The row to add.
   * @param array $newLookups An array telling us the ids from the import data against the new ids of any rows already added.
   * @param integer $surveyWebsiteId The website_id of the survey we are importing into.
   * @param array $newMeaningIds An array holding the meaning ids we have added with the old id as the key.
   * This means that if we add two items that used to have the same meaning id, we can give them the same new
   * meaning id as well. 
   * 
   * @return integer The id of a newly generated row.
   */
  public function importRow($tableName,$rowToAdd,$newLookups,$surveyWebsiteId, &$newMeaningIds) {
    //add updated by/created by info
    $this->setMetadata($rowToAdd, $tableName);
    //Point any lookups at new lookup values created when we add data to the database.
    if (!empty($this->tableLookupToDrillDown[$tableName])) {
      foreach ($this->tableLookupToDrillDown[$tableName] as $lookupColumn=>$tablePointedAt)
        $rowToAdd[$lookupColumn] = $newLookups[$tablePointedAt][$rowToAdd[$lookupColumn]];
    }

    //Meanings are a special case as we always have a new meaning unless we have already added a new meaning
    //that is equivalent to the old meaning id (meanings are a special case because the table only consists of an id)
    if ($tableName==='termlists_terms') {
      //If we have already added a new meaning that is equivalent to the old meaning id, then use it
      if (!empty($newMeaningIds) && array_key_exists($rowToAdd['meaning_id'],$newMeaningIds)) {
        $rowToAdd['meaning_id']=$newMeaningIds[$rowToAdd['meaning_id']];
      } else {
        $meaningToAdd= array();
        //When we add a meaning, we need to generate a new id ouselves and manually add it to the database.
        //This is different to normal because we only have an id column and we aren't allowed add an
        //empty row to the database.
        $meaningToAdd['id'] = $this->db->query("SELECT nextval('meanings_id_seq'::regclass)")
        ->current()->nextval;
        //Save the new meaning id against the old id.
        $newMeaningIds[$rowToAdd['meaning_id']] = $meaningToAdd['id'];
        //Set the meaning_id of the termlists_term we are adding to the new meaning id
        $rowToAdd['meaning_id'] = $meaningToAdd['id'];
        
        $this->db
        ->from('meanings')
        ->set($meaningToAdd)
        ->insert(); 
      }
    }
    if ($tableName==='sample_attributes_websites'||$tableName==='occurrence_attributes_websites'||$tableName==='termlists')
      $rowToAdd['website_id'] = $surveyWebsiteId;

    unset($rowToAdd['id']);
    $insert = $this->db
      ->from($tableName)
      ->set($rowToAdd)
      ->insert();
    return $insert->insert_id();
  }
 
  /**
   * When we get the data from the database we give the columns names. For convenience
   * this method puts these names into a list as an array
   *
   * @param array $databaseData The data we have collected from the database.
   * @return array The array of names we gave the database columns
   */
  public function getVitualColumnNames($databaseData) {
    $virtualColumns = array();
    if (!empty($databaseData[0])) {
      $virtualColumnCounter = 0;
      //To get the column names we only need the keys from one row of data.
      $virtualColumNames = array_keys($databaseData[0]);
      //take each name and put in an array where the keys are simple ascending numbers
      foreach ($virtualColumNames as $virtualColumnName) {   
        $virtualColumns[$virtualColumnCounter] = $virtualColumnName;
        $virtualColumnCounter++;
      }
    }
    return $virtualColumns;
  }
 
  /**
   *
   * @param type $databaseData Raw database data.
   * @param type $virtualColumnNames An array of sql names we used when we collected the data from the database. The
   * format of this data is as <table name>-<column name> e.g. termlists_terms-termlist_id or languages-language
   * @param type $tableName Name of the table we are working with
   * @return $array An array of rows from the database. Each row is an array of columns and values. This is designed
   * to be saved into an array containing the table name when the method is called.
   */
  public function convertDataIntoStructuredArray($databaseData, $virtualColumnNames, $tableName) {
    //When we collected the data from the database we used the naming standard <table name>-<column name> for the data items.
    $tablePrefix = $tableName.'-';
    $structuredData = null;
    if (!empty($databaseData) && !empty($virtualColumnNames)) {
      foreach ($databaseData as $dataKey=>$collectedData) {
        //Currently the data from the database is a large array of columns from lots of tables.
        //We cycle through the list of possible column names to find matches.
        foreach($virtualColumnNames as $virtualColumnName) {
          if (!empty($collectedData[$virtualColumnName])) {
            //When we examine the data, if the name of the data starts with the table name we are
            //currently processing then we know we have found some data relevant to the table.
            if (substr($virtualColumnName, 0, strlen($tablePrefix)) == $tablePrefix) {
              //If we take the end of the sql query name for the data then we get the actual column name from the database.
              $realColumnName = preg_replace('/^' . preg_quote($tablePrefix, '/') . '/', '', $virtualColumnName);
              //Save the data value into an array. This will get placed into another array with the table name
              //by the caller. The table name is not included here as it causes issues doing it that way.
              $structuredData[$dataKey][$realColumnName] = $collectedData[$virtualColumnName];
            }
          }     
        }
      }
    }
    return $structuredData;
  }
 
 /**
  * 
  * @param type $queryType When we get the data from the database we re-use the query several times.
   * However it does vary somewhat, so we need to know the situation we are getting the data for.
  * @return array Query statement
  */
  public function getQuery($queryType) {
    //This is the part of the select statement we always use no matter what survey structure data we are getting.
    $universlPartOfSelect = array('languages.id as languages-id','languages.iso as languages-iso','languages.language as languages-language',
                                  'terms.id as terms-id','terms.term as terms-term','terms.language_id as terms-language_id',
                                  'termlists_terms.id as termlists_terms-id','termlists_terms.termlist_id as termlists_terms-termlist_id',
                                  'termlists_terms.term_id as termlists_terms-term_id','termlists_terms.parent_id as termlists_terms-parent_id', 
                                  'termlists_terms.meaning_id as termlists_terms-meaning_id','termlists_terms.preferred as termlists_terms-preferred',
                                  'termlists_terms.sort_order as termlists_terms-sort_order',
                                  'termlists.id as termlists-id','termlists.title as termlists-title','termlists.description as termlists-description',
                                  'termlists.parent_id as termlists-parent_id','termlists.external_key as termlists-external_key');
    
    switch ($queryType) {
      case 'get_termlists_from_sample_attribute':
        $varyingPartOfSelect = array('sample_attributes_websites.id as sample_attributes_websites-id',
                                     'sample_attributes_websites.sample_attribute_id as sample_attributes_websites-sample_attribute_id',
                                     'sample_attributes_websites.restrict_to_survey_id as sample_attributes_websites-restrict_to_survey_id',
                                     'sample_attributes_websites.form_structure_block_id as sample_attributes_websites-form_structure_block_id',
                                     'sample_attributes_websites.validation_rules as sample_attributes_websites-validation_rules',
                                     'sample_attributes_websites.weight as sample_attributes_websites-weight',
                                     'sample_attributes_websites.control_type_id as sample_attributes_websites-control_type_id',
                                     'sample_attributes_websites.default_text_value as sample_attributes_websites-default_text_value',
                                     'sample_attributes_websites.default_float_value as sample_attributes_websites-default_float_value',
                                     'sample_attributes_websites.default_int_value as sample_attributes_websites-default_int_value',
                                     'sample_attributes_websites.default_date_start_value as sample_attributes_websites-default_date_start_value',
                                     'sample_attributes_websites.default_date_end_value as sample_attributes_websites-default_date_end_value',
                                     'sample_attributes_websites.default_date_type_value as sample_attributes_websites-default_date_type_value',
                                     'sample_attributes_websites.restrict_to_sample_method_id as sample_attributes_websites-restrict_to_sample_method_id',
                                     'sample_attributes.id as sample_attributes-id', 'sample_attributes.caption as sample_attributes-caption',
                                     'sample_attributes.data_type as sample_attributes-data_type',
                                     'sample_attributes.applies_to_location as sample_attributes-applies_to_location',
                                     'sample_attributes.validation_rules as sample_attributes-validation_rules',
                                     'sample_attributes.termlist_id as sample_attributes-termlist_id',
                                     'sample_attributes.multi_value as sample_attributes-multi_value', 'sample_attributes.public as sample_attributes-public',
                                     'sample_attributes.applies_to_recorder as sample_attributes-applies_to_recorder',
                                     'sample_attributes.system_function as sample_attributes-system_function');
      break;
    
      case 'get_termlists_from_restrict_to_sample_method':
        $varyingPartOfSelect = array('sample_attributes_websites.id as sample_attributes_websites-id',
                                     'sample_attributes_websites.sample_attribute_id as sample_attributes_websites-sample_attribute_id',
                                     'sample_attributes_websites.restrict_to_survey_id as sample_attributes_websites-restrict_to_survey_id',
                                     'sample_attributes_websites.form_structure_block_id as sample_attributes_websites-form_structure_block_id',
                                     'sample_attributes_websites.validation_rules as sample_attributes_websites-validation_rules',
                                     'sample_attributes_websites.weight as sample_attributes_websites-weight',
                                     'sample_attributes_websites.control_type_id as sample_attributes_websites-control_type_id',
                                     'sample_attributes_websites.default_text_value as sample_attributes_websites-default_text_value',
                                     'sample_attributes_websites.default_float_value as sample_attributes_websites-default_float_value',
                                     'sample_attributes_websites.default_int_value as sample_attributes_websites-default_int_value',
                                     'sample_attributes_websites.default_date_start_value as sample_attributes_websites-default_date_start_value',
                                     'sample_attributes_websites.default_date_end_value as sample_attributes_websites-default_date_end_value',
                                     'sample_attributes_websites.default_date_type_value as sample_attributes_websites-default_date_type_value',
                                     'sample_attributes_websites.restrict_to_sample_method_id as sample_attributes_websites-restrict_to_sample_method_id');
      break;  
        
      case 'get_termlists_from_occurrence_attribute': 
        $varyingPartOfSelect = array('occurrence_attributes_websites.id as occurrence_attributes_websites-id',
                                     'occurrence_attributes_websites.occurrence_attribute_id as occurrence_attributes_websites-occurrence_attribute_id',
                                     'occurrence_attributes_websites.restrict_to_survey_id as occurrence_attributes_websites-restrict_to_survey_id',
                                     'occurrence_attributes_websites.form_structure_block_id as occurrence_attributes_websites-form_structure_block_id',
                                     'occurrence_attributes_websites.validation_rules as occurrence_attributes_websites-validation_rules',
                                     'occurrence_attributes_websites.weight as occurrence_attributes_websites-weight',
                                     'occurrence_attributes_websites.control_type_id as occurrence_attributes_websites-control_type_id',
                                     'occurrence_attributes_websites.default_text_value as occurrence_attributes_websites-default_text_value',
                                     'occurrence_attributes_websites.default_float_value as occurrence_attributes_websites-default_float_value',
                                     'occurrence_attributes_websites.default_int_value as occurrence_attributes_websites-default_int_value',
                                     'occurrence_attributes_websites.default_date_start_value as occurrence_attributes_websites-default_date_start_value',
                                     'occurrence_attributes_websites.default_date_end_value as occurrence_attributes_websites-default_date_end_value',
                                     'occurrence_attributes_websites.default_date_type_value as occurrence_attributes_websites-default_date_type_value',
                                     'occurrence_attributes.id as occurrence_attributes-id', 'occurrence_attributes.caption as occurrence_attributes-caption',
                                     'occurrence_attributes.data_type as occurrence_attributes-data_type',
                                     'occurrence_attributes.validation_rules as occurrence_attributes-validation_rules',
                                     'occurrence_attributes.termlist_id as occurrence_attributes-termlist_id',
                                     'occurrence_attributes.multi_value as occurrence_attributes-multi_value',
                                     'occurrence_attributes.public as occurrence_attributes-public', 
                                     'occurrence_attributes.system_function as occurrence_attributes-system_function');
      break;
    }
    
    $selectStatement = array_merge($universlPartOfSelect,$varyingPartOfSelect);
    
    //This is the part of the query statement we always use no matter what survey structure data we are getting.
    $query =  $this->db
      ->select($selectStatement)
      ->from('termlists_terms')
      ->join('terms','terms.id','termlists_terms.term_id')
      ->join('termlists','termlists.id','termlists_terms.termlist_id')
      ->join('languages','languages.id','terms.language_id', 'LEFT');

    switch ($queryType) {
      case 'get_termlists_from_sample_attribute':
        $finishQuery=$this->db
        ->join('sample_attributes','sample_attributes.termlist_id','termlists.id')
        ->join('sample_attributes_websites','sample_attributes_websites.sample_attribute_id','sample_attributes.id');
        break;  
        
      case 'get_termlists_from_restrict_to_sample_method':
        $finishQuery=$this->db
        ->join('sample_attributes_websites', 'sample_attributes_websites.restrict_to_sample_method_id','termlists_terms.id');
        break;   
      
      case 'get_termlists_from_occurrence_attribute':
        $finishQuery=$this->db
        ->join('occurrence_attributes','occurrence_attributes.termlist_id','termlists.id')
        ->join('occurrence_attributes_websites','occurrence_attributes_websites.occurrence_attribute_id','occurrence_attributes.id');        break;   
        break;
    }
    
    //merge this part of the query with the main query
    foreach($finishQuery AS $var=>$value){
      $query->$var = $value;
    }
    return $query;
  }
  
  /**
   * 
   * @param type $queryType We need to know the query we are using so we can supply the right where clause.
   * @param type $surveyId The survey id for the survey we are getting import data for.
   * @return string The where clause itself
   */
  public function buildWhere($queryType, $surveyId) {
    switch($queryType) {
      case 'get_termlists_from_sample_attribute':
        $where = array('sample_attributes_websites.restrict_to_survey_id'=>$surveyId,'sample_attributes_websites.deleted'=>'f','sample_attributes.deleted'=>'f', 'termlists_terms.deleted'=>'f', 
                       'termlists.deleted'=>'f', 'terms.deleted'=>'f', 'languages.deleted'=>'f');
      break;
      case 'get_termlists_from_restrict_to_sample_method':
        $where = array('sample_attributes_websites.restrict_to_survey_id'=>$surveyId,'sample_attributes_websites.deleted'=>'f', 'termlists_terms.deleted'=>'f', 
                       'termlists.deleted'=>'f','terms.deleted'=>'f', 'languages.deleted'=>'f');
      break;
      case 'get_termlists_from_occurrence_attribute':
        $where = array('occurrence_attributes_websites.restrict_to_survey_id'=>$surveyId,'occurrence_attributes_websites.deleted'=>'f','occurrence_attributes.deleted'=>'f', 'termlists_terms.deleted'=>'f', 
                       'termlists.deleted'=>'f', 'terms.deleted'=>'f', 'languages.deleted'=>'f');
      break;        
    }
    //If survey id isn't supplied then we need to remove the survey check from the where clause.
    if ($surveyId===null) {
      foreach($where as $tableColumn=>$value) {
        if (stripos($tableColumn, 'restrict_to_survey_id') !== false) {
          unset($where[$tableColumn]);
        }
      }
    }
    return $where;
  }
  
  /**
   * 
   * @param type $surveyId
   * @return array A version of the data which has been changed into structured
   * arrays of the data from the tables.
   */
  public function getDatabaseData($surveyId=null) {
    //Collect the raw data from the database
    //Note - Confusingly, if you use result_array(FALSE) this returns the data
    //as an array of arrays.
    foreach ($this->queryTypes as $queryType) {
      $dataFromQuery[$queryType] = $this->getQuery($queryType)   
        ->where($this->buildWhere($queryType, $surveyId))
        ->get()->result_array(FALSE);
    }
    //Get a nice linear array of all the column names we used in the SQL.
    $virtualSampleDataColumns = $this->getVitualColumnNames($dataFromQuery['get_termlists_from_sample_attribute']);
    $virtualOccurrenceDataColumns = $this->getVitualColumnNames($dataFromQuery['get_termlists_from_occurrence_attribute']);
    //Cycle through all the types of data we returned.
    foreach($this->queryTypes as $queryType) {
      //Cycle through all the table names in that data
      foreach ($this->tableNames as $key=>$tableName) {
        //Transform the raw data into a nice structured array of arrays using the table names as keys for the outer array.
        //The inner arrays are the rows of data
        $structuredTable = $this->convertDataIntoStructuredArray($dataFromQuery[$queryType],$virtualSampleDataColumns,$tableName);
        if (!empty($structuredTable))
          $structuredData[$queryType][$tableName] = $structuredTable;
      }
    }
    //contine the transformation into a nice structured array
    foreach ($this->tableNames as $key=>$tableName) {
      foreach($this->queryTypes as $queryType) {
        if (empty($structuredData[$queryType][$tableName]))
          $structuredData[$queryType][$tableName] = array();     
      }
      $data[$tableName] = array_merge($structuredData['get_termlists_from_sample_attribute'][$tableName],
                                      $structuredData['get_termlists_from_restrict_to_sample_method'][$tableName],
                                      $structuredData['get_termlists_from_occurrence_attribute'][$tableName]);
      $this->getUniqueArrayofArrays($data[$tableName]);
    }
    return $data; 
  }
  
  /**
   * We drill through all the tables and create hashes of the row data. We can then detect duplicates between the
   * import data and existing data by comparing these hashes.
   * 
   * @param array $data The database data we are going to collect hashes for
   * @return array $unifiedHashData An array of table arrays. Each table array key is the id of the database row, the value is the hash
   */
  public function getHashes($data) {
    //Firstly we need to determine the top-level tables we are going to drill
    //down through.
    $possibleTablesToStartDrill = array_keys($this->tableLookupToDrillDown);
    foreach ($possibleTablesToStartDrill as $tableName) {
      //Assume we will drill from a table until it is proved otherwise.
      $startDrillFromTable[$tableName]=true;
      //Collect the information about the lookups to child tables for each table
      foreach ($this->tableLookupToDrillDown as $drillDownInfo) {
        //If we fid the table we are currently interested in is a child of another
        //table then we know we don't want to start our drill through the tables from there
        if (in_array($tableName, $drillDownInfo))
          $startDrillFromTable[$tableName]=false;
      }
    }
    
    foreach ($startDrillFromTable as $tableName => $drill) {
      //Only drill from the top-level tables we found
      if ($drill) {
        $hashData[$tableName] = array();
        //Start drilling through the tables to create the hash data.
        $this->drillSideways($data,$hashData[$tableName],$tableName);
      }
    }
    
    $unifiedHashData = array();
    foreach($hashData as $theHashes)
      $unifiedHashData = array_merge($unifiedHashData, $theHashes);
    return $unifiedHashData;
  }
  
  /**
   * When we are searching for rows to create hashes for, then we need to search individual rows for lookups to drill down
   * as a single row might have multiple columns to drill-down e.g. sample_attributes_websites points to sample_attributes but
   * also can point to termlists_terms through the restrict_to_sample_method_ID column
   * 
   * @param array $data The data from the database we are creating hashes for
   * @param array $hashes An array of hash values we have accumulated so far.
   * @param string $tableToDrillFrom The name of the table we are currently looking at.
   * @param integer $currentRowId The id of the table row we are currently looking at.
   */
  public function drillSideways(&$data,&$hashes,$tableToDrillFrom,$currentRowId=null) { 
    if (!empty($data[$tableToDrillFrom])) {
      //search through every row of data in the table we are currently looking at
      foreach ($data[$tableToDrillFrom] as $index => &$row) {
        //cycle through each column in the table that is a column we need to drill-down from
        foreach ($this->tableLookupToDrillDown[$tableToDrillFrom] as $drillFromColumn=>$drillToTable) {
          //if the lookup column is populated then we need to drill-down.
          if (!empty($row[$drillFromColumn])) {       
            $this->drillDown($data,$hashes,$drillToTable,$row[$drillFromColumn], $tableToDrillFrom);
            //Once we have finished drilling down the lookup, then we can save the hash value from the lookup
            //against a field in the row. This means the hash for the row will be different if any of the child table 
            //data is different.
            $row[$drillFromColumn.'_hash']=$hashes[$drillToTable][$row[$drillFromColumn]]; 
          }
        }
      $hashes[$tableToDrillFrom][$row['id']] = md5(serialize($this->prepareHashRow($row,$tableToDrillFrom)));
      }
    }
  }
  
  public function drillThoughTermlistJoin(&$data,&$hashes,$currentRowId) {
    $collectedTermIds = array();
    
    foreach ($data['termlists_terms'] as $termlistsTermsRow) {
      if ($currentRowId===$termlistsTermsRow['termlist_id']) {
        //drill through each term that is linked to our termlist through termlist_terms
        $this->drillSideways(&$data,&$hashes,'terms',$termlistsTermsRow['term_id']);
        //make a note of all the terms linked to the termlist
        array_push($collectedTermIds, $termlistsTermsRow['term_id']);
      }
    }
    $fullTermHash = null;
    //put all the hashes from the terms into one big hash
    foreach($collectedTermIds as $hashedTermId) {
      $fullTermHash = $fullTermHash.$hashes['terms'][$hashedTermId];
    }
    //save the term hash onto the termlists_terms
    foreach ($data['termlists_terms'] as $termlistsTermsRow) {
      if ($currentRowId===$termlistsTermsRow['termlist_id']) {
        $hashes['termlists_terms'][$termlistsTermsRow['id']]['term_id_hash']=$fullTermHash;
      }
    }
    //save the hash onto the termlists
    return $fullTermHash;
  }
  
  /**
   * When we are searching for rows to create hashes for, then we need to drill-down through the lookups in the database.
   * 
   * @param array $data The data from the database we are creating hashes for
   * @param array $hashes An array of hash values we have accumulated so far.
   * @param string $currentTableName The name of the table we are currently looking at.
   * @param integer $currentRowId The id of the table row we are currently looking at.
   */
  public function drillDown(&$data,&$hashes,$currentTableName,$currentRowId) {
    //If the current table has at least one column that is a lookup that we need to drill down,
    //then call the drillSideways method to see if there is more than one column to drill.
    //If not then we know this is a leaf and there are no further drills to make below this table.
    if(!empty($this->tableLookupToDrillDown[$currentTableName]))
      $this->drillSideways($data,$hashes,$currentTableName,$currentRowId);
    if ($currentTableName==='termlists') {
          $hashes[$currentTableName][$currentRowId]=$this->drillThoughTermlistJoin($data,$hashes,$currentRowId);
    }
    //If we reach this point we know we have reached a leaf table.
    //So we search through all the data rows for the table until we find the row that is the one the lookup was pointing to.
    //We can then create a hash for it.
    foreach ($data[$currentTableName] as $index=>$row) {
      if ($row['id']===$currentRowId)
        $hashes[$currentTableName][$currentRowId] = md5(serialize($this->prepareHashRow($data[$currentTableName][$index],$currentTableName))); 
    }
  }
  
  /**We create a hash of different data rows to make it easier to compare
   * the data we are importing to existing data. This means we avoid
   * importing duplicate rows.
   * When we create a hash for a row, there are id and lookup columns we don't want included
   * in the hash. This is because we can still have a duplicate if the lookup id numbers between the
   * data items we are comparing are different.
   * @param array $row Row we are processing
   * @param string $tableName
   * $return array Version of the row ready for hashing
   *
   */
  public function prepareHashRow($row, $tableName) {
    //Cycle through each column we don't want to include for the table we are currently processing
    if (!empty($this->itemsToFilterPerTable[$tableName])) {
      foreach ($this->itemsToFilterPerTable[$tableName] as $columnToFilter)
        unset($row[$columnToFilter]);
    }
    return $row;
  }
 
  /**
   * array_unique in php doesn't work with an array of arrays.
   * This method takes an array of arrays and removes duplicate arrays from the outer array
   * @param array $array
   */
  public function getUniqueArrayofArrays(&$array) {
    if (!empty($array)) {
      foreach ($array as $key=>$arrayRow)
        $serializedArray[$key] = serialize($arrayRow);
      $serializedArrayUnique = array_unique($serializedArray);
      foreach ($serializedArrayUnique as $key=>$arrayRow)
        $unserializedArray[$key] = unserialize($arrayRow);
      $array = $unserializedArray;
    } else {
      $array = array();
    }
  }
 
  /**
   * After we have done the import, if there are any columns that reference other rows in the same table (e.g parent_id) then
   * we need to import all the data from the table before we can correct the IDs to point at the new rows.
   *
   * @param array $importData
   * @param array $newLookups An array of table arrays. Each table array has keys which are the old IDs from the table from the import
   * data. The values are the new ids for those rows.
   * @param string $tableToFix Name of the table we are going to fix
   * @param string $columnToFix Name of the column we are going to fix
   */
  public function fixSelfReferencingIds($importData, $newLookups, $tableToFix, $columnToFix) {
    //Go through each row in the table
    foreach ($importData[$tableToFix] as $dataRow) {
      //Only continue if there is a value in the column to fix
      if (!empty($dataRow[$columnToFix])) {
        //Only continue if there is a new lookup for the old value
        if (array_key_exists($dataRow[$columnToFix],$newLookups[$tableToFix])) {
          //We don't want to update any rows which are duplicates of existing rows
          //as this would overwrite anything already in the columnToFix.
          //Programmer note, do not change this to !== as it won't work
          if ($dataRow['id'] != $newLookups[$tableToFix][$dataRow['id']]) {
            //Set the lookup to the id for the new row 
            $dataToUpdate[$columnToFix] = $newLookups[$tableToFix][$dataRow[$columnToFix]];
            //get created/updated values for the row
            $this->setMetadata($dataToUpdate, $tableToFix);
            //we are doing an update of data already in the database so we don't need created_on or created_by_id
            unset($dataToUpdate['created_on']);
            unset($dataToUpdate['created_by_id']);
            $updateId = $newLookups[$tableToFix][$dataRow['id']];

            $this->db
              ->from($tableToFix)
              ->set($dataToUpdate)
              ->where(array('id'=>$updateId))
              ->update(); 
          }
        }
      }
    }
  }

  /**Method that adds a created by, created date, updated by, updated date to a row of data
     we are going to add/update to the database.
   * @param array $row A row of data we are adding/updating to the database.
   * @param string $tableName The name of the table we are adding the row to. We need this as the
   * attribute_websites tables don't have updated by and updated on fields.
   */
  public function setMetadata(&$row=null, $tableName=null) {
    if (isset($_SESSION['auth_user']))
      $userId = $_SESSION['auth_user']->id;
    else {
      global $remoteUserId;
      if (isset($remoteUserId))
        $userId = $remoteUserId;
      else {
        $defaultUserId = Kohana::config('indicia.defaultPersonId');
        $userId = ($defaultUserId ? $defaultUserId : 1);
      }
    }
    $row['created_on'] = date("Ymd H:i:s");
    $row['created_by_id'] = $userId;
    //attribute websites tables don't have updated by/date details columns so we need a special case not to set them
    if ($tableName!=='sample_attributes_websites'&&$tableName!=='occurrence_attributes_websites') {
      $row['updated_on'] = date("Ymd H:i:s");
      $row['updated_by_id'] = $userId;
    }
  }
}
?>