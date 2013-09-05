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
  /**
   * List of tables supported by the exporter. The order of the array should be in top
   * down order of processing. Each table name contains a sub array with metadata detailing the
   * joins to follow upwards from the table plus the joins to follow downwards.
   * @var array 
   */
  private $tableMetadata = array(
    'surveys' => array(
      'referredToBy' => array(
        'sample_attributes_websites'=>'restrict_to_survey_id',
        'occurrence_attributes_websites'=>'restrict_to_survey_id'
      ),
    ),
    'sample_attributes_websites'=>array(
      'refersTo' => array('sample_attributes'=>'sample_attribute_id', 'termlists_terms'=>'restrict_to_sample_method_id')    
    ),
    'sample_attributes' => array(
      'refersTo' => array('termlists'=>'termlist_id')
    ),
    'occurrence_attributes_websites'=>array(
      'refersTo' => array('occurrence_attributes'=>'occurrence_attribute_id')    
    ),
    'occurrence_attributes' => array(
      'refersTo' => array('termlists'=>'termlist_id')
    ),
    'termlists' => array(
      'referredToBy' => array('termlists_terms'=>'termlist_id')
    ),
    'termlists_terms' => array(
      'refersTo' => array('terms'=>'term_id')
    ),
    'terms' => array(
      'refersTo' => array('languages'=>'language_id')
    ),
    'languages' => array()
  );
  
  /**
   * A simple list of table names, autopopulated from the metadata array.
   * @var array
   */
  private $tableNames = array();
  
  /**
   * An array of hash data used during the building of the hash information for a dataset.
   * @var array
   */
  private $hashWorkingData = array();
  
  /**
   * The import survey's title. Use a default until we know better.
   * @var string 
   */
  private $surveyTitle = 'This survey';
  
  /**
   * An output log.
   * @var array
   */
  private $log = array();
  
 
  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct();
    $this->tableNames = array_keys($this->tableMetadata);
  }
  
  /**
   * Controller action for the export tab content. Display the export page.
   */
  public function index() {
    $this->view = new View('survey_structure_export/index');
    $this->view->surveyId=$this->uri->last_segment();
    //Get the attribute data (including termlists) associated with the survey ready to export
    $export = $this->getDatabaseData($this->view->surveyId);
    $this->view->export = json_encode($export);
    $this->template->content = $this->view;
  }
 
  /**
   * Perform the import
   */
  public function save() {
    $surveyId = $_POST['survey_id'];
    try {
      $importData = json_decode($_POST['import_survey_structure'], true);
      $this->doImport($importData,$_POST['survey_id']);
      $this->template->title = 'Import Complete';
      $this->view = new View('survey_structure_export/import_complete');
      $this->view->log = $this->log;
      $this->template->content = $this->view;
    } catch (Exception $e) {
      error::log_error('Exception during survey structure import', $e);
      $this->template->title = 'Error during survey structure import';
      $this->view = new View('templates/error_message');
      $this->view->message='An error occurred during the survey structure import. ' .
                           'Please make sure the import data is valid. More information can be found in the warehouse logs.';
      $this->template->content = $this->view;
    }
    $this->page_breadcrumbs[] = html::anchor('survey', 'Surveys');
    $this->page_breadcrumbs[] = html::anchor('survey/edit/'.$surveyId, $this->surveyTitle);
    $this->page_breadcrumbs[] = $this->template->title;
  }

  /**
   * Call the methods required to do the import.
   *
   * @param array $importData
   * @param int $surveyId The ID of the survey in the database we are importing into.
   * @todo Is the load of existing data scalable? Does it just load data available to
   * the website for the current survey?
   */
  public function doImport($importData, $surveyId) {
    //Get the data from the database before the import is done. Otherwise we can't do duplicate comparisons with it.
    $existingData = $this->getDatabaseData(null);
    //We need to get the website_id for the survey we are importing into so we can add it to the rows we are adding for tables
    //that support it
    $survey = $this->db
      ->select('website_id, title')
      ->from('surveys')
      ->where(array('id'=>$surveyId))
      ->get()->result_array(FALSE);
    print_r($survey);
    $surveyWebsiteId = $survey[0]['website_id'];
    $this->surveyTitle = $survey[0]['title'];
    //Create the hashes for the data we are importing and also the existing data in the database.
    //This is allows us to detect duplicates if hashes match.
    $importHashes = $this->getHashes($importData);
    $existingHashes = $this->getHashes($existingData);
    //Cycle through each table and do the import.
    //The mappings variable is an array of table arrays. The table array keys are the old ids from the import data and
    //the values are the new ids in the databases. This array is created by the importTable method as it does its work.
    $idMappings = array();
    // process the tables backwards, so we do the leaf tables first
    $tables = array_reverse($this->tableNames);
    foreach ($tables as $tableName)
      if (!empty($importData[$tableName])) 
        $this->importTable($importData[$tableName], $tableName, $importHashes, $existingHashes, $idMappings, $surveyWebsiteId);
    //We need to fix any columns which are self-referencing (such as parent_id) as this can only be done after 
    //the import is finished.
    //Note we are not importing the parent_id from the termlists table, so the only
    //parent_id column to fix is from termlists_terms
    $this->fixSelfReferencingIds($importData, $idMappings, 'termlists_terms', 'parent_id');
  }
 
 
  /**
   *
   * @param array $importData The data to be imported, as decoded from the JSON string.
   * @param string $tableName Name of table we are importing
   * @param array $importHashes The hash codes for the rows in the import data
   * @param array $existingHashes The hash codes for the rows in the existing database data
   * @param array $idMappings An array of arrays. There is an array for each table processed so far.
   * In each table array, the keys are the ids for the items in the import data. The values are the new ids in the database.
   * So the value is either a new row id, or for duplicates if is an existing row id.
   * @param id $surveyWebsiteId The id of the website for the survey we are importing into
   */
  public function importTable($recordData, $tableName, $importHashes, $existingHashes, &$idMappings, $surveyWebsiteId) {
    $newMeaningIds = array();
    foreach($recordData as $rowData) {
      if ($existingHashRowId = array_search($importHashes[$tableName][$rowData['id']], $existingHashes[$tableName])) {
        $idMappings[$tableName][$rowData['id']] = $existingHashRowId;
      }
      else {
        //If we don't find a duplicate, then we add a new row to the database and note the new id.
        $idMappings[$tableName][$rowData['id']] =  $this->importRow($tableName, $rowData, $idMappings, $surveyWebsiteId, $newMeaningIds);
        $this->log[] = "Creating new row in $tableName for ID ".$idMappings[$tableName][$rowData['id']];
      } 
    }
  }
 
  /**
   *
   * @param string $tableName The table name of the row to be imported.
   * @param integer $rowToAdd The row to add.
   * @param array $idMappings An array telling us the ids from the import data against the new ids of any rows already added.
   * @param integer $surveyWebsiteId The website_id of the survey we are importing into.
   * @param array $newMeaningIds An array holding the meaning ids we have added with the old id as the key.
   * This means that if we add two items that used to have the same meaning id, we can give them the same new
   * meaning id as well. 
   * 
   * @return integer The id of a newly generated row.
   */
  public function importRow($tableName, $rowToAdd, $idMappings, $surveyWebsiteId, &$newMeaningIds) {
    //add updated by/created by info
    $this->setMetadata($rowToAdd, $tableName);
    //Point any lookups at new lookup values created when we add data to the database.
    if (!empty($this->tableMetadata[$tableName]['refersTo'])) {
      foreach ($this->tableMetadata[$tableName]['refersTo'] as $fkTable=>$fkColumn) {
        if (!empty($rowToAdd[$fkColumn]))
          $rowToAdd[$fkColumn] = $idMappings[$fkTable][$rowToAdd[$fkColumn]];
      }
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
   * 
   * @param type $surveyId
   * @return array A version of the data which has been changed into structured
   * arrays of the data from the tables.
   */
  public function getDatabaseData($id=null) {
    $data = array();
    $startTable = $this->tableNames[0];
    if ($id) {
      $rows = $this->db->select('*')
              ->from($startTable)
              ->where('id', $id)
              ->get()->result_array(FALSE);
      $this->loadDataAndRecurse($startTable, $rows, $data);
    } else {
      // as we are not loading from a specific top level record, go to the next level and get all the records.
      $linkedTables = array();
      if (isset($this->tableMetadata[$startTable]['referredToBy']))
        $linkedTables += array_keys($this->tableMetadata[$startTable]['referredToBy']);
      if (isset($this->tableMetadata[$startTable]['refersTo']))
        $linkedTables += array_keys($this->tableMetadata[$startTable]['refersTo']);
      foreach($linkedTables as $table) {
        $rows = $this->db->select('*')
                ->from($table)
                ->get()->result_array(FALSE);
        $this->loadDataAndRecurse($table, $rows, $data);
      }
    }
    // if loading a specific ID, then we can drop the specific record we loaded as it is not
    // part of the import/export process. I.e. we don't actually export/import the survey record, 
    // just the stuff it contains.
    unset($data[$this->tableNames[0]]);
    return $data;
  }
  
  /**
   * Takes the rows for a table, loads them into the data array, and recurses into 
   * any foreign key dependencies the table may have so we end up with a complete set
   * of export data.
   * @param string $table
   * @param array $rows
   * @param array $data
   * @return type 
   */
  public function loadDataAndRecurse($table, $rows, &$data) {
    if (!empty($rows)) {
      if (!isset($data[$table]))
        $data[$table] = array();
      $references = $this->tableMetadata[$table];
      foreach($rows as $row) {
        $data[$table][$row['id']] = $this->stripUnwantedFields($table, $row);
        if (isset($references['referredToBy'])) {
          foreach($references['referredToBy'] as $fktable => $fk) {
            $referredToByRows = $this->db->select('*')
              ->from($fktable)
              ->where($fk, $row['id'])
              ->get()->result_array(FALSE);
            $this->loadDataAndRecurse($fktable, $referredToByRows, $data);
          } 
        }
        if (isset($references['refersTo'])) {
          foreach($references['refersTo'] as $fktable => $fk) {
            $refersToRows = $this->db->select('*')
              ->from($fktable)
              ->where('id', $row[$fk])
              ->get()->result_array(FALSE);
            $this->loadDataAndRecurse($fktable, $refersToRows, $data);
          }

        }
      }
    }
  }
  
  /**
   * Removes the fields we don't want for a table export from the row data array.
   * @param type $table
   * @param type $row 
   */
  private function stripUnwantedFields($table, $row) {
    unset($row['created_by_id']);
    unset($row['updated_by_id']);
    unset($row['created_on']);
    unset($row['updated_on']);
    unset($row['deleted']);
    switch ($table) {
      case 'surveys':
        unset($row['owner_id']);  
        break;
    }
    return $row;
  }
  
  /**
   * We drill through all the tables and create hashes of the row data. We can then detect duplicates between the
   * import data and existing data by comparing these hashes.
   * 
   * @param array $data The database data we are going to collect hashes for
   * @return array $unifiedHashData An array of table arrays. Each table array key is the id of the database row, the value is the hash
   */
  public function getHashes($data) {
    // reset the hash working data
    $this->hashWorkingData = array($this->tableNames[0] => array());
    // start drilling into the data. Note we don't bother getting hashes for the first
    // table as that's the one we are importing into - so we can't compare with anything.
    // So we use the references from the first table to work out where else to start
    $linkedTables = array();
    if (isset($this->tableMetadata[$this->tableNames[0]]['referredToBy']))
      $linkedTables += array_keys($this->tableMetadata[$this->tableNames[0]]['referredToBy']);
    if (isset($this->tableMetadata[$this->tableNames[0]]['refersTo']))
      $linkedTables += array_keys($this->tableMetadata[$this->tableNames[0]]['refersTo']);
    foreach($linkedTables as $table) {
      foreach(array_keys($data[$table]) as $id) {
        $this->recurseToGetHashes($data, $table, $id);
      }
    }
    
    return $this->hashWorkingData;
  }
  
  public function recurseToGetHashes($data, $table, $id) {
    $row = $data[$table][$id];
    $references = $this->tableMetadata[$table];
    if (isset($references['referredToBy'])) {
      foreach($references['referredToBy'] as $fkTable => $fk) {
        // find the rows that refer to this record
        foreach($data[$fkTable] as $fkId => $fkRow) {
          if ($fkRow[$fk] === $row['id']) {
            // include the hash in the parent row data, so that the hash uniquely reflects the children
            $data[$table][$id]["hash-$fkTable-$fk-$fkId"] = $this->recurseToGetHashes($data, $fkTable, $fkId);
          }
        }
      }
    }
    if (isset($references['refersTo'])) {
      foreach($references['refersTo'] as $fkTable => $fk) {
        if (!empty($row[$fk])) {
          // find the records referred to by this row
          $joinFound = false;
          foreach($data[$fkTable] as $fkId => $fkRow) {
            if ($fkRow['id'] === $row[$fk]) {
              // include the hash in the parent row data, so that the hash uniquely reflects the children
              $data[$table][$id]["hash-$fkTable-$fk-$fkId"] = $this->recurseToGetHashes($data, $fkTable, $fkId);
              $joinFound = true;
              break;
            }
          }
          if (!$joinFound)
            // big problem
            throw new exception("Could not find join for $fkTable id=".$row[$fk]);
        }
      }
    }
    $this->hashWorkingData[$table][$id] = md5(serialize($this->prepareHashRow($data[$table][$id], $table)));
    return $this->hashWorkingData[$table][$id];
  }
  
  /**
   * Prepare a row's data for hashing.
   * 
   * We create a hash of different data rows to make it easier to compare
   * the data we are importing to existing data. This means we avoid
   * importing duplicate rows. When we create a hash for a row, there are id, fk 
   * and date metadata columns we don't want included in the hash. This is 
   * because we can still have a duplicate if the lookup id numbers between the 
   * data items we are comparing are different just because the record came from 
   * a different server.
   * @param array $row Row we are processing
   * @param string $tableName
   * $return array Version of the row ready for hashing
   */
  public function prepareHashRow($row, $tableName) {
    foreach($row as $field => $value) {
      // skip id, created_on, updated_on etc.
      if (preg_match('/^(^hash)(.*_)?id$/', $field) || preg_match('/^.*_on$/', $field))
        unset($row[$field]);  
    }
    return $row;
  }

  /**
   * After we have done the import, if there are any columns that reference other rows in the same table (e.g parent_id) then
   * we need to import all the data from the table before we can correct the IDs to point at the new rows.
   *
   * @param array $importData
   * @param array $idMappings An array of table arrays. Each table array has keys which are the old IDs from the table from the import
   * data. The values are the new ids for those rows.
   * @param string $tableToFix Name of the table we are going to fix
   * @param string $columnToFix Name of the column we are going to fix
   */
  public function fixSelfReferencingIds($importData, $idMappings, $tableToFix, $columnToFix) {
    //Go through each row in the table
    foreach ($importData[$tableToFix] as $dataRow) {
      //Only continue if there is a value in the column to fix
      if (!empty($dataRow[$columnToFix])) {
        //Only continue if there is a new lookup for the old value
        if (array_key_exists($dataRow[$columnToFix],$idMappings[$tableToFix])) {
          //We don't want to update any rows which are duplicates of existing rows
          //as this would overwrite anything already in the columnToFix.
          //Programmer note, do not change this to !== as it won't work
          if ($dataRow['id'] != $idMappings[$tableToFix][$dataRow['id']]) {
            //Set the lookup to the id for the new row 
            $dataToUpdate[$columnToFix] = $idMappings[$tableToFix][$dataRow[$columnToFix]];
            //get created/updated values for the row
            $this->setMetadata($dataToUpdate, $tableToFix);
            //we are doing an update of data already in the database so we don't need created_on or created_by_id
            unset($dataToUpdate['created_on']);
            unset($dataToUpdate['created_by_id']);
            $updateId = $idMappings[$tableToFix][$dataRow['id']];

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