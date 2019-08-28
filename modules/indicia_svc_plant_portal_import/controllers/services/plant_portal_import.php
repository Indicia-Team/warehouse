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
 * @package  Services
 * @subpackage Import
 * @author  Indicia Team
 * @license  http://www.gnu.org/licenses/gpl.html GPL
 * @link   http://code.google.com/p/indicia/
 */
 
defined('SYSPATH') or die('No direct script access.');

/**
 * Controller class for plant portal import web services
 *
 * @package  Services
 * @subpackage Data
 */
class Plant_Portal_Import_Controller extends Service_Base_Controller {

  private $submissionStruct;

  /**
   * @var array Parent model field details from the previous row. Allows us to efficiently use the same sample for
   * multiple occurrences etc.
   */
  private $previousCsvSupermodel;

  /**
   * Controller function that provides a web service services/plant_portal_import/get_import_settings/model.
   * Options for the model's specific form can be passed in $_GET.
   * @param string $model Singular name of the model entity to check.
   * @return string JSON Parameters form details for this model, or empty string if no parameters form required.
   */
  public function get_plant_portal_import_settings($model) {
    $this->authenticate('read');
    $model = ORM::factory($model);
    if (method_exists($model, 'fixed_values_form')) {
      // Pass URL parameters through to the fixed values form in case there are model specific settings.
      $options = array_merge($_GET);
      unset($options['nonce']);
      unset($options['auth_token']);
      echo json_encode($model->fixed_values_form($options));
    }
  }
  
  /**
   * Controller function that returns the list of importable fields for a model.
   * Accepts optional $_GET parameters for the website_id and survey_id, which limit the available
   * custom attribute fields as appropriate.
   * @param string $model Singular name of the model entity to check.
   * @return string JSON listing the fields that can be imported.
   */
  public function get_plant_portal_import_fields($model) {
    $this->authenticate('read');
    switch($model){
    	case 'sample': $attrTypeFilter = empty($_GET['sample_method_id']) ? null : $_GET['sample_method_id'];
    		break;
    	case 'location': $attrTypeFilter = empty($_GET['location_type_id']) ? null : $_GET['location_type_id'];
    		break;
    	default: $attrTypeFilter = null;
    		break;
    }
    $model = ORM::factory($model);
    // Identify the context of the import
    $identifiers = [];
    if (!empty($_GET['website_id'])) {
      $identifiers['website_id'] = empty($_GET['website_id']) ? null : $_GET['website_id'];
    }
    if (!empty($_GET['survey_id'])) {
      $identifiers['survey_id'] = empty($_GET['survey_id']) ? null : $_GET['survey_id'];
    }
    $use_associations = (empty($_GET['use_associations']) ? false : ($_GET['use_associations'] == "true" ? true : false));
    echo json_encode($model->getSubmittableFields(TRUE, $identifiers, $attrTypeFilter, $use_associations));
  }
  
  /**
   * Controller function that returns the list of required fields for a model.
   * Accepts optional $_GET parameters for the website_id and survey_id, which limit the available
   * custom attribute fields as appropriate.
   * @param string $model Singular name of the model entity to check.
   * @return string JSON listing the fields that are required.
   */
  public function get_plant_portal_required_fields($model) {
    $this->authenticate('read');
    $model = ORM::factory($model);
    $website_id = empty($_GET['website_id']) ? null : $_GET['website_id'];
    $survey_id = empty($_GET['survey_id']) ? null : $_GET['survey_id'];
    $use_associations = (empty($_GET['use_associations']) ? false : ($_GET['use_associations'] == "true" ? true : false));
    $fields = $model->getRequiredFields(true, $website_id, $survey_id, $use_associations);
    foreach ($fields as &$field) {
      $field = preg_replace('/:date_type$/', ':date', $field);
    }
    echo json_encode($fields);
  }
  
  /**
   * Handle uploaded files in the $_FILES array by moving them to the upload folder. The current time is prefixed to the 
   * name to make it unique. The uploaded file should be in a field called media_upload.
   */
  public function upload_csv()
  {
    try
    {
      // Ensure we have write permissions.
      $this->authenticate();
      // We will be using a POST array to send data, and presumably a FILES array for the
      // media.
      // Upload size
      $ups = Kohana::config('indicia.maxUploadSize');
      $_FILES = Validation::factory($_FILES)->add_rules(
        'media_upload', 'upload::valid', 'upload::required',
        'upload::type[csv]', "upload::size[$ups]"
      );
      if (count($_FILES)===0) {
        echo "No file was uploaded.";
      }
      elseif ($_FILES->validate())
      {
        if (array_key_exists('name_is_guid', $_POST) && $_POST['name_is_guid']=='true') 
          $finalName = strtolower($_FILES['media_upload']['name']);
        else
          $finalName = time().strtolower($_FILES['media_upload']['name']);
        $fTmp = upload::save('media_upload', $finalName);
        $this->response=basename($fTmp);
        $this->send_response();
        kohana::log('debug', 'Successfully uploaded file to '. basename($fTmp));
      }
      else
      {
        kohana::log('error', 'Validation errors uploading file '. $_FILES['media_upload']['name']);
        kohana::log('error', print_r($_FILES->errors('form_error_messages'), true));
        foreach ($_FILES as $file) {
          if (!empty($file['error'])) {
            kohana::log('error', 'PHP reports file upload error: ' . $this->codeToMessage($file['error']));
          }
        }
        Throw new ValidationError('Validation error', 2004, $_FILES->errors('form_error_messages'));
      }
    }
    catch (Exception $e)
    {
      $this->handle_error($e);
    }
  }
  
  /**
   * Caches various metadata to do with the upload, including the upload mappings and the error count. This action 
   * is called by the JavaScript code responsible for a chunked upload, before the upload actually starts.
   */
  public function cache_upload_metadata() {
    $this->authenticate();
    $metadata = array_merge($_POST);
    if (isset($metadata['mappings']))
      $metadata['mappings']=json_decode($metadata['mappings'], true);
    if (isset($metadata['settings']))
      $metadata['settings']=json_decode($metadata['settings'], true);
    // the metadata can also hold auth tokens and user_id, though they do not need decoding.
    self::internal_cache_upload_metadata($metadata);
    echo "OK";
  }
  
  private function codeToMessage($code) {
    switch ($code) {
      case UPLOAD_ERR_INI_SIZE:
        $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
        break;
      case UPLOAD_ERR_FORM_SIZE:
        $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
        break;
      case UPLOAD_ERR_PARTIAL:
        $message = "The uploaded file was only partially uploaded";
        break;
      case UPLOAD_ERR_NO_FILE:
        $message = "No file was uploaded";
        break;
      case UPLOAD_ERR_NO_TMP_DIR:
        $message = "Missing a temporary folder";
        break;
      case UPLOAD_ERR_CANT_WRITE:
        $message = "Failed to write file to disk";
        break;
      case UPLOAD_ERR_EXTENSION:
        $message = "File upload stopped by extension";
        break;
      default:
        $message = "Unknown upload error";
        break;
    }
      return $message;
  } 
  
  /**
   * Saves a set of metadata for an upload to a file, so it can persist across requests.
   */
  private function internal_cache_upload_metadata($metadata) {
    $previous = self::_get_metadata($_GET['uploaded_csv']);
    $metadata = array_merge($previous, $metadata);
    $this->auto_render=false;
    $mappingFile = str_replace('.csv','-metadata.txt',$_GET['uploaded_csv']);
    $mappingHandle = fopen(DOCROOT . "upload/$mappingFile", "w");
    fwrite($mappingHandle, json_encode($metadata));
    fclose($mappingHandle);
  }
  
  /*
   * Determines if the provided module has been activated in the indicia configuration.
   */
  private function _check_module_active($module)
  {
  	$config=kohana::config_load('core');
  	foreach ($config['modules'] as $path) {
  		if(strlen($path) >= strlen($module) &&
  				substr_compare($path, $module , strlen($path)-strlen($module), strlen($module), true) === 0)
  			return true;
  	}
  	return false;
  }
  
  /**
   * Controller action that performs the import of data in an uploaded CSV file.
   * Allows $_GET parameters to specify the filepos, offset and limit when uploading just a chunk at a time.
   * This method is called to perform the entire upload when JavaScript is not enabled, or can 
   * be called to perform part of an AJAX csv upload where only a part of the data is imported
   * on each call.
   * Requires a $_GET parameter for uploaded_csv - the uploaded file name.
   */
  public function upload() {
    $csvTempFile = DOCROOT . "upload/" . $_GET['uploaded_csv'];
    $metadata = $this->_get_metadata($_GET['uploaded_csv']);
    if (!empty($metadata['user_id'])) {
      global $remoteUserId;
      $remoteUserId = $metadata['user_id'];
    }
    // Check if details of the last supermodel (e.g. sample for an occurrence) are in the cache from a previous iteration of 
    // this bulk operation
    $cache= Cache::instance();
    $this->getPreviousRowSupermodel($cache);
    // enable caching of things like language lookups
    ORM::$cacheFkLookups = true;
    // make sure the file still exists
    if (file_exists($csvTempFile))
    {
      // Following helps for files from Macs
      ini_set('auto_detect_line_endings',1);
      // create the file pointer, plus one for errors
      $handle = fopen ($csvTempFile, "r");
      $this->checkIfUtf8($metadata, $handle);
      $errorHandle = $this->_get_error_file_handle($csvTempFile, $handle);
      $count=0;
      $limit = (isset($_GET['limit']) ? $_GET['limit'] : false);
      $filepos = (isset($_GET['filepos']) ? $_GET['filepos'] : 0);
      $offset = (isset($_GET['offset']) ? $_GET['offset'] : 0);
      if ($filepos==0) {
        // first row, so skip the header
        fseek($handle, 0);
        fgetcsv($handle, 1000, ",");
        // also clear the lookup cache
        $cache->delete_tag('lookup');
      } else
        // skip rows to allow for the last file position
        fseek($handle, $filepos);
      $model = ORM::Factory($_GET['model']);
      $this->submissionStruct = $model->get_submission_structure();
      // special date processing.
      $index = 0;
      $dayColumn = false; 
      $monthColumn = false;
      $yearColumn = false;
      foreach ($metadata['mappings'] as $col=>$attr) {
      	// skip cols to do with remembered mappings
      	if ($col!=='RememberAll' && substr($col, -9)!=='_Remember') {
      		switch($attr) {
      			case 'sample:date:day': $dayColumn = $index;
      			case 'sample:date:month': $monthColumn = $index;
      			case 'sample:date:year': $yearColumn = $index;
      		}
      		$index++;
      	}
      }
      $processDate = $dayColumn !== false && $monthColumn !== false && $yearColumn !== false; // initially has to have all 3 fields: TODO vaguer dates?
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE && ($limit===false || $count<$limit)) {
        if(!array_filter($data))
          // skip empty rows
          continue;
        $count++;
        $index = 0;
        $saveArray = $model->getDefaults();
        // Note, the mappings will always be in the same order as the columns of the CSV file
        foreach ($metadata['mappings'] as $col=>$attr) {
          // skip cols to do with remembered mappings
          if ($col!=='RememberAll' && substr($col, -9)!=='_Remember') {
            if (isset($data[$index])) {
              // '<Please select>' is a value fixed in import_helper::model_field_options
              if ($attr != '<Please select>' && $data[$index]!=='') {
                // Add the data to the record save array. Utf8 encode if file does not have UTF8 BOM.
                $saveArray[$attr] = $metadata['isUtf8'] ? $data[$index] : utf8_encode($data[$index]);
              }
            } else {
              // This is one of our static fields at the end
              $saveArray[$col] = $attr;
            }
            $index++;
          }
        }

        if((!isset($saveArray['sample:date']) || $saveArray['sample:date']=='') && $processDate) {
        	$saveArray['sample:date'] = $data[$yearColumn].'-'.sprintf('%02d', $data[$monthColumn]).'-'.sprintf('%02d', $data[$dayColumn]); // initially has to have all 3 fields: TODO vaguer dates?
      		unset($saveArray['sample:date:day']);
      		unset($saveArray['sample:date:month']);
      		unset($saveArray['sample:date:year']);
        }
        // copy across the fixed values, including the website id, into the data to save.
        if ($metadata['settings']) {
          $saveArray = array_merge($metadata['settings'], $saveArray);
        }
        if (!empty($saveArray['website_id'])) {
          // automatically join to the website if relevant
          if (isset($this->submissionStruct['joinsTo']) && in_array('websites', $this->submissionStruct['joinsTo'])) {
            $saveArray['joinsTo:website:'.$saveArray['website_id']]=1;
          }
        }
        // Check if in an association situation
        $associationExists = false;
        if (self::_check_module_active($this->submissionStruct['model'].'_associations')) {
        	// assume model has attributes.
        	$attrDetails = $model->get_attr_details();
        	$associatedSuffix = '_2';
         	$associatedRecordSubmissionStructure = $this->submissionStruct;
        	$originalRecordPrefix      = $this->submissionStruct['model'];
        	$originalAttributePrefix   = $attrDetails['attrs_field_prefix'];
        	$originalMediaPrefix       = $originalRecordPrefix.'_media';
        	$associatedRecordPrefix    = $originalRecordPrefix.$associatedSuffix;
        	$associatedAttributePrefix = $originalAttributePrefix.$associatedSuffix;
        	$associatedMediaPrefix     = $originalMediaPrefix.$associatedSuffix;
        	$associationRecordPrefix   = $originalRecordPrefix.'_association';
        	// find out if association or associated records exist
        	foreach ($saveArray as $assocField=>$assocValue) {
        		$associationExists = $associationExists ||
        			substr($assocField, 0, strlen($associationRecordPrefix)) == $associationRecordPrefix ||
        			substr($assocField, 0, strlen($associatedRecordPrefix)) == $associatedRecordPrefix;
        	}
        }
        
        // If posting a supermodel, are the details of the supermodel the same as for the previous CSV row? If so, we can link to that
        // record rather than create a new supermodel record.
        $updatedPreviousCsvSupermodelDetails=$this->checkForSameSupermodel($saveArray, $model, $associationExists);
        // Clear the model, so nothing else from the previous row carries over.
        $model->clear();
        // Save the record with automatically generated spatial reference from the Vice County/Country where needed
        $saveArray=self::auto_generate_grid_references($saveArray);
        $model->set_submission_data($saveArray, true);         
        $associationExists = false;
        if (($id = $model->submit()) == null) {
          // Record has errors - now embedded in model, so dump them into the error file
          $errors = array();
          foreach($model->getAllErrors() as $field=>$msg) {
            $fldTitle = array_search($field, $metadata['mappings']);
            $fldTitle = $fldTitle ? $fldTitle : $field;
            $errors[] = "$fldTitle: $msg";
          }
          $errors = implode("\n", array_unique($errors));
          $data[] = $errors;
          $data[] = $count + $offset + 1; // 1 for header
          fputcsv($errorHandle, $data);
          kohana::log('debug', 'Failed to import CSV row: '.$errors);
          $metadata['errorCount'] = $metadata['errorCount'] + 1;
        } else {
          // now the record has successfully posted, we need to store the details of any new supermodels and their Ids, 
          // in case they are duplicated in the next csv row.
          $this->previousCsvSupermodel['details'] = array_merge($this->previousCsvSupermodel['details'], $updatedPreviousCsvSupermodelDetails);
          $this->captureSupermodelIds($model, $associationExists);
        }
        // get file position here otherwise the fgetcsv in the while loop will move it one record too far. 
        $filepos = ftell($handle);
      }
      // Get percentage progress
      $progress = $filepos * 100 / filesize($csvTempFile);
      $r = "{\"uploaded\":$count,\"progress\":$progress,\"filepos\":$filepos}";
      // allow for a JSONP cross-site request
      if (array_key_exists('callback', $_GET)) {
        $r = $_GET['callback']."(".$r.")";
      }
      echo $r;
      fclose($handle);
      fclose($errorHandle);
      self::internal_cache_upload_metadata($metadata);
      
      // An AJAX upload request will just receive the number of records uploaded and progress
      $this->auto_render=false;      
      $cache->set(basename($csvTempFile).'previousSupermodel', $this->previousCsvSupermodel);      
    }
  }
  
  /*
   * Create new plots with data passed in from the website
   */
  public function create_new_plots() {
    $db = new Database();
    //The plot names, srefs and sref systems are passed in from the website in the warehouse call, these can be collected from the $_GET
    $plotNames = (isset($_GET['plotNames']) ? $_GET['plotNames'] : false);
    $plotSrefs = (isset($_GET['plotSrefs']) ? $_GET['plotSrefs'] : false);
    $plotSrefSystems = (isset($_GET['plotSrefSystems']) ? $_GET['plotSrefSystems'] : false);
    $plotLocationType = (isset($_GET['plotLocationType']) ? $_GET['plotLocationType'] : false);
    $websiteId = (isset($_GET['websiteId']) ? $_GET['websiteId'] : false);
    $userId = (isset($_GET['userId']) ? $_GET['userId'] : false);
    $locationAttributeIdThatHoldsPlotGroup = (isset($_GET['attributeIdToHoldPlotGroup']) ? $_GET['attributeIdToHoldPlotGroup'] : false);
    //Date is in batches with several items sent together, these are comma separated so explode them to deal with them
    $explodedPlotNames = explode(',',$plotNames);
    $explodedPlotSrefs = explode(',',$plotSrefs);
    $explodedPlotSrefSystems = explode(',',$plotSrefSystems);
    //Foreach plot name we need to create create a location record and an associated locations_websites row
    foreach ($explodedPlotNames as $plotIdx=>$plotName) {
      //To DO AVB, don't we want to insert a location type too?
      $db->query("insert into locations "
                  ."("
                    ."name,"
                    ."centroid_sref,"
                    ."centroid_sref_system,"
                    ."location_type_id,"
                    ."created_on,"
                    ."created_by_id,"
                    ."updated_on,"
                    ."updated_by_id"
                  .")"
                ."select"
                . "'".$plotName."',"
                . "'".$explodedPlotSrefs[$plotIdx]."',"
                . "'".$explodedPlotSrefSystems[$plotIdx]."',"
                . "$plotLocationType"
                . ",now()"
                . ",".$userId
                . ",now()"
                . ",".$userId
                . "WHERE
                     NOT EXISTS (
                       SELECT id 
                       FROM locations 
                       WHERE 
                       name = '".$plotName."' AND
                       centroid_sref = '".$explodedPlotSrefs[$plotIdx]."' AND 
                       centroid_sref_system = '".$explodedPlotSrefSystems[$plotIdx]."'
                     );"
              . "insert into locations_websites"
              . "("
                  . "location_id,"
                  . "website_id,"
                  . "created_on,"
                  . "created_by_id,"
                  . "updated_on,"
                  . "updated_by_id"
              . ")"
              . "select"
                  . "(select id from locations where name = '".$plotName."' AND deleted=false order by id desc limit 1),"
                  . "".$websiteId.","
                  . "now()"
                  . ",".$userId
                  . ",now()"
                  . ",".$userId
              . "WHERE
                   NOT EXISTS (
                     SELECT id 
                     FROM locations_websites 
                     WHERE 
                     location_id = (select id from locations where name = '".$plotName."' AND deleted=false order by id desc limit 1) AND
                     website_id = ".$websiteId."
                   );"
      );
    }
  }
  
  /*
   * Create new groups with data passed in from the website
   */
  public function create_new_groups() {
    $db = new Database();
    $groupNames = (isset($_GET['names']) ? $_GET['names'] : false);
    //Groups names set in batches, these are comma separated so explode them to deal with them
    $explodedGroupNames = explode(',',$groupNames);
    $userId = (isset($_GET['userId']) ? $_GET['userId'] : false);
    $personattributeIdToHoldPlotGroups = (isset($_GET['personAttributeId']) ? $_GET['personAttributeId'] : false);
    if ($userId!==false && $personattributeIdToHoldPlotGroups!==false) {
      foreach ($explodedGroupNames as $groupName) {
        //Groups are terms, we have built in database function for adding those (and associated termlists_terms etc)
        $db->query("select insert_term('".$groupName."','eng',null,'indicia:plant_portal_plot_groups');")->result();
        //We must assign the group to a user once it is created
        self::assign_user_to_new_group($db,$groupName,$userId,$personattributeIdToHoldPlotGroups);
      }
    }
  }
  
  /*
   * After creating the groups, we actually need to assign the group to the user automatically (as they have just imported the group this makes sense to do)
   */
  private function assign_user_to_new_group($db,$groupName,$userId,$personattributeIdToHoldPlotGroups) {
    $personId=self::get_person_from_user_id($db,$userId);
    //To Do AVB - The NOT exists is needed at the moment, however in the future in should only be there as a precaution as
    //duplicate detection should be much earlier, possibly remove entirely if performance becomes an issue
    $db->query("
      insert into person_attribute_values (person_id,person_attribute_id,int_value, created_on, created_by_id, updated_on, updated_by_id)
      select ".$personId.", 
      ".$personattributeIdToHoldPlotGroups.",
      (select tt.id
      from termlists_terms tt
      join terms t on t.id = tt.term_id AND t.term = '".$groupName."' AND t.deleted=false
      where tt.deleted=false
      order by tt.id desc
      limit 1),
      now(),
      ".$userId.",
      now(),
      ".$userId."
      WHERE
        NOT EXISTS (
          SELECT id 
          FROM person_attribute_values 
          WHERE 
          person_id = ".$personId." AND
          person_attribute_id = ".$personattributeIdToHoldPlotGroups." AND  
          int_value = (
            select tt.id
            from termlists_terms tt
            join terms t on t.id = tt.term_id AND t.term = '".$groupName."' AND t.deleted=false
            where tt.deleted=false
            order by tt.id desc
            limit 1
          )
      );")->result();
  }
  
  public function create_new_plot_to_group_attachments() {
    $db = new Database();
    $websiteId = (isset($_GET['websiteId']) ? $_GET['websiteId'] : false);
    $userId = (isset($_GET['userId']) ? $_GET['userId'] : false);
    $currentPersonId=self::get_person_from_user_id($db,$userId);
    $locationAttributeIdThatHoldsPlotGroup = (isset($_GET['locationAttributeIdThatHoldsPlotGroup']) ? $_GET['locationAttributeIdThatHoldsPlotGroup'] : false);

    //Need this attribute as when we collect the sample ID we need to attach to, we only want to be looking at samples associated with the current person
    $personAttributeIdThatHoldsPlotGroup = (isset($_GET['personAttributeIdThatHoldsPlotGroup']) ? $_GET['personAttributeIdThatHoldsPlotGroup'] : false);
    $plotPairsForPlotGroupAttachment = (isset($_GET['plotPairsForPlotGroupAttachment']) ? $_GET['plotPairsForPlotGroupAttachment'] : false);
    $explodedPlotPairsForPlotGroupAttachment = explode(',',$plotPairsForPlotGroupAttachment);
    if (!empty($explodedPlotPairsForPlotGroupAttachment))
      $plotIdsToCreateAttachmentsFor=self::get_new_plot_attachments_plot_ids_to_create($db,$explodedPlotPairsForPlotGroupAttachment,$currentPersonId);
    if (!empty($explodedPlotPairsForPlotGroupAttachment))
      $groupIdsToCreateAttachmentsFor=self::get_new_plot_attachments_group_ids_to_create($db,$explodedPlotPairsForPlotGroupAttachment,$currentPersonId,$personAttributeIdThatHoldsPlotGroup);
    if (!empty($explodedPlotPairsForPlotGroupAttachment)&&!empty($plotIdsToCreateAttachmentsFor)&&!empty($groupIdsToCreateAttachmentsFor))
      $explodedPlotPairsForPlotGroupAttachmentAsIds=self::get_new_plot_attachments_to_create($explodedPlotPairsForPlotGroupAttachment,$plotIdsToCreateAttachmentsFor,$groupIdsToCreateAttachmentsFor);
    $databaseInsertionString='';
    if (!empty($explodedPlotPairsForPlotGroupAttachmentAsIds)) {
      $databaseInsertionString=self::create_database_insertion_string($explodedPlotPairsForPlotGroupAttachmentAsIds,$userId,$locationAttributeIdThatHoldsPlotGroup);
      //There will be an extra comma at the end which needs removing
      $databaseInsertionString=substr($databaseInsertionString, 0, -1);
      $db->query($databaseInsertionString)->result_array(false);
    }
  }
  
  private static function get_new_plot_attachments_plot_ids_to_create($db,$explodedPlotPairsForPlotGroupAttachment,$personId) {
    $plotNamesForAttachmentSet = '(';
    foreach ($explodedPlotPairsForPlotGroupAttachment as $plotPairsForPlotGroupAttachment) {
      $explodedPlotNameGroupNamePair = explode('|',$plotPairsForPlotGroupAttachment);
      $plotNamesForAttachmentSet.="'".$explodedPlotNameGroupNamePair[0]."',";
    }
    $plotNamesForAttachmentSet=substr($plotNamesForAttachmentSet, 0, -1);
    $plotNamesForAttachmentSet .= ')';  
    $returnArray=$db->
    query(
    "select l.id as id, l.name as name
     from locations l
     join locations_websites lw on lw.location_id = l.id 
     where l.deleted=false AND l.name in ".$plotNamesForAttachmentSet."
     order by l.id desc limit ".count($explodedPlotPairsForPlotGroupAttachment)                   
    )->result_array(false);
    return $returnArray;
  }
  
  private static function get_new_plot_attachments_group_ids_to_create($db,$explodedPlotPairsForPlotGroupAttachment,$personId,$personAttributeIdThatHoldsPlotGroup) {
    $plotGroupNamesForAttachmentSet = '(';
    foreach ($explodedPlotPairsForPlotGroupAttachment as $plotPairsForPlotGroupAttachment) {
      $explodedPlotNameGroupNamePair = explode('|',$plotPairsForPlotGroupAttachment);
      $plotGroupNamesForAttachmentSet.="'".$explodedPlotNameGroupNamePair[1]."',";
    }
    $plotGroupNamesForAttachmentSet=substr($plotGroupNamesForAttachmentSet, 0, -1);
    $plotGroupNamesForAttachmentSet .= ')';
    $returnArray=$db->
    query(
    "select tt.id as id, t.term as name
     from terms t
     join termlists_terms tt on tt.term_id = t.id AND tt.deleted=false
     join person_attribute_values pav on pav.int_value = tt.id AND pav.person_attribute_id = ".$personAttributeIdThatHoldsPlotGroup." AND pav.deleted=false
     where t.deleted=false AND t.term in ".$plotGroupNamesForAttachmentSet                    
    )->result_array(false);
    return $returnArray;
  }
  
  /*
   * Build a string for inserting the plot location to group attachments
   */
  private static function create_database_insertion_string($explodedPlotPairsForPlotGroupAttachmentAsIds,$userId,$locationAttributeIdThatHoldsPlotGroup) {
    $insertionString='';
    //TO DO AVB, is there an easy way to do this in a single statement? It is complicated by the NOT EXISTS
    //Cycle through each attachement to make
    foreach ($explodedPlotPairsForPlotGroupAttachmentAsIds as $explodedPlotPairForPlotGroupAttachmentAsIds) {
      $insertionString.='insert into location_attribute_values(location_id,location_attribute_id,int_value,created_on,created_by_id,updated_on,updated_by_id) select ';
      $insertionString.=$explodedPlotPairForPlotGroupAttachmentAsIds['0'].','.$locationAttributeIdThatHoldsPlotGroup.','.$explodedPlotPairForPlotGroupAttachmentAsIds['1'].','.'now()'.','.$userId.','.'now()'.','.$userId;
      //Double check the attachment doesn't already exist
      $insertionString.=' WHERE NOT EXISTS ('
              . 'select id from location_attribute_values'
              . ' WHERE location_id ='.$explodedPlotPairForPlotGroupAttachmentAsIds['0']
              . ' AND location_attribute_id = '.$locationAttributeIdThatHoldsPlotGroup
              . ' AND int_value = '.$explodedPlotPairForPlotGroupAttachmentAsIds['1'].");\n";
    }
    return $insertionString;
  }
  
  private static function get_new_plot_attachments_to_create($explodedPlotPairsForPlotGroupAttachments,$plotIdsToCreateAttachmentsFor,$groupIdsToCreateAttachmentsFor) {
    $explodedPlotPairsForPlotGroupAttachmentAsIds=array();
    foreach ($explodedPlotPairsForPlotGroupAttachments as $plotPairForPlotGroupAttachment) {
      $explodedPlotPairForPlotGroupAttachment=explode('|',$plotPairForPlotGroupAttachment);
      $plotName=$explodedPlotPairForPlotGroupAttachment[0];
      $plotGroupName=$explodedPlotPairForPlotGroupAttachment[1];
      $plotPairForPlotGroupAttachmentAsIds=array();
      foreach ($plotIdsToCreateAttachmentsFor as $idNamePair) {
        if ($idNamePair['name']===$plotName) {
          $plotPairForPlotGroupAttachmentAsIds[0]=$idNamePair['id'];
        }
      }
      foreach ($groupIdsToCreateAttachmentsFor as $idNamePair) {
        if ($idNamePair['name']===$plotGroupName) {
          $plotPairForPlotGroupAttachmentAsIds[1]=$idNamePair['id'];
        }
      }
      $explodedPlotPairsForPlotGroupAttachmentAsIds[]=$plotPairForPlotGroupAttachmentAsIds;
      
    }
    return $explodedPlotPairsForPlotGroupAttachmentAsIds;
  }
  
  private function get_person_from_user_id($db,$userId) {
    $returnObj=$db->query("select u.person_id AS id from users u where u.id = ".$userId.";")->current();
    if (!empty($returnObj->id))
      $returnVal=$returnObj->id;
    else 
      $returnVal=null;
    return $returnVal;
  }
  
  /* 
   * If spatial reference is missing then automatically generate one using the vice county name or country name 
   * Note this has an equivalent function with the same name in the Drupal prebuilt form.
   * Changes to the logic here should also occur in that function 
   */
  private static function auto_generate_grid_references($saveArray) {
    $viceCountyPairs = explode(',',kohana::config('plant_portal_import.vice_counties_list'));
    $countryPairs = explode(',',kohana::config('plant_portal_import.countries_list'));
    //If the spatial reference is empty we need to do some work to try and get it from the vice county
    if (empty($saveArray['sample:entered_sref'])) {
      //All the stored vice counties are a name with a grid reference (separated by a |)
      foreach ($viceCountyPairs as $viceCountyNameGridRefPair) {
        $viceCountyNameGridRefPairExploded=explode('|',$viceCountyNameGridRefPair);
        //If we find a match for the vice county then we can set the spatial reference and spatial reference system from the vice county
        if (!empty($saveArray['smpAttr:'.kohana::config('plant_portal_import.vice_county_attr_id')])&& 
                !empty($viceCountyNameGridRefPairExploded[0]) && 
                $saveArray['smpAttr:'.kohana::config('plant_portal_import.vice_county_attr_id')]==$viceCountyNameGridRefPairExploded[0]) {
          $saveArray['sample:entered_sref']=$viceCountyNameGridRefPairExploded[1];
          $saveArray['sample:entered_sref_system']='4326';
        }
      }
    }
    //If spatial reference is still empty we can do the same with countries
    if (empty($saveArray['sample:entered_sref'])) {
      foreach ($countryPairs as $countryNameGridRefPair) {
        $countryNameGridRefPairExploded=explode('|',$countryNameGridRefPair);
        if (!empty($saveArray['smpAttr:'.kohana::config('plant_portal_import.country_attr_id')])&&
                !empty($countryNameGridRefPairExploded[0]) && 
                $saveArray['smpAttr:'.kohana::config('plant_portal_import.country_attr_id')]==$countryNameGridRefPairExploded[0]) {
          $saveArray['sample:entered_sref']=$countryNameGridRefPairExploded[1];
          $saveArray['sample:entered_sref_system']='4326';
        }
      }
    }
    return $saveArray;
  }
  
  /**
   * Display the end result of an upload. Either displayed at the end of a non-AJAX upload, or redirected
   * to directly by the AJAX code that is performing a chunked upload when the upload completes.
   * Requires a get parameter for the uploaded_csv filename.
   * @return string JSON containing the problems cound and error file name.
   */
  public function get_upload_result() {
    $this->authenticate('read');
    $metadataFile = str_replace('.csv','-metadata.txt', $_GET['uploaded_csv']);    
    $errorFile = str_replace('.csv','-errors.csv',$_GET['uploaded_csv']);
    $metadata = $this->_get_metadata($_GET['uploaded_csv']);
    echo json_encode(array('problems'=>$metadata['errorCount'], 'file' => url::base().'upload/'.basename($errorFile)));
    // clean up the uploaded file and mapping file, but only remove the error file if no errors, otherwise we make it downloadable
    if (file_exists(DOCROOT . "upload/" . $_GET['uploaded_csv']))
      unlink(DOCROOT . "upload/" . $_GET['uploaded_csv']);
    if (file_exists(DOCROOT . "upload/" . $metadataFile))
      unlink(DOCROOT . "upload/" . $metadataFile);
    if ($metadata['errorCount'] == 0 && file_exists(DOCROOT . "upload/" . $errorFile))
      unlink(DOCROOT . "upload/" . $errorFile);
    // clean up cached lookups
    $cache= Cache::instance();
    $cache->delete_tag('lookup');
  }
  
  /**
   * When looping through csv import data, if the import data includes a supermodel (e.g. the sample for an occurrence)
   * then this method checks to see if the supermodel part of the submission is repeated. If so, then rather than create
   * a new record for the supermodel, we just link this new record to the existing supermodel record. E.g. a spreadsheet
   * containing several occurrences in a single sample can repeat the sample details but only one sample gets created.
   * BUT, there are situations (like building an association based submission) where we need to keep the structure, in which
   * case we just set the id, rather than remove all the supermodel entries.
   */
  private function checkForSameSupermodel(&$saveArray, $model, $linkOnly = false) {
    $updatedPreviousCsvSupermodelDetails = array();
    if (isset($this->submissionStruct['superModels'])) {
      // loop through the supermodels
      foreach($this->submissionStruct['superModels'] as $modelName=>$modelDetails) {
        // meaning models do not get shared across rows - we always generate a new meaning ID.
        if ($modelName=='taxon_meaning' || $modelName=='meaning') 
          continue;
        $sm = ORM::factory($modelName);
        $smAttrsPrefix = isset($sm->attrs_field_prefix) ? $sm->attrs_field_prefix : null;
        // look for data in that supermodel and build something we can use for comparison. We must capture both normal and custom attributes.
        $hash='';
        foreach ($saveArray as $field=>$value) {          
          if (substr($field, 0, strlen($modelName)+1)=="$modelName:")            
            $hash.="$field|$value|";
          elseif ($smAttrsPrefix && substr($field, 0, strlen($smAttrsPrefix)+1)=="$smAttrsPrefix:")          
            $hash.="$field|$value|";          
        }
        // if we have previously stored a hash for this supermodel, check if they are the same. If so we can get the ID.
        if (isset($this->previousCsvSupermodel['details'][$modelName]) && $this->previousCsvSupermodel['details'][$modelName]==$hash) {
          // the details for this supermodel point to an existing record, so we need to re-use it. 
          if($linkOnly) {
          	// now link the existing supermodel record to the save array
          	$saveArray[$modelName.':id'] = $this->previousCsvSupermodel['id'][$modelName];
          } else {
            // First, remove the data from the submission array so we don't re-submit it.
            foreach ($saveArray as $field=>$value) {
              if (substr($field, 0, strlen($modelName)+1)=="$modelName:")
                unset($saveArray[$field]);
            }
            // now link the existing supermodel record to the save array
            $saveArray[$model->object_name.':'.$modelDetails['fk']] = $this->previousCsvSupermodel['id'][$modelName];
          }
        } else {
          // this is a new supermodel (e.g. a new sample for the occurrences). So just save the details in case it is repeated
          $updatedPreviousCsvSupermodelDetails[$modelName]=$hash;
        }
      }
    }
    return $updatedPreviousCsvSupermodelDetails;
  }
  
  /**
  * When saving a model with supermodels, we don't want to duplicate the supermodel record if all the details are the same across 2
  * spreadsheet rows. So this method captures the ID of the supermodels that we have just posted, in case their details are replicated
  * in the next record.
  * Handles case where the submission has been flipped (associations), and supermodel has been made the main model.
  */
  private function captureSupermodelIds($model, $flipped=false) {
  	if ($flipped) {
  		// supermodel is now main model - just look for the ID field...
  		$array = $model->as_array();
  		$subStruct = $model->get_submission_structure();
  		$this->previousCsvSupermodel['id'][$subStruct['model']] = $model->id;		
  	} else if (isset($this->submissionStruct['superModels'])) {
      $array = $model->as_array();
      // loop through the supermodels
      foreach($this->submissionStruct['superModels'] as $modelName=>$modelDetails) {
        $id = $modelName . '_id';
        // Expect that the fk field is called fkTable_id (e.g. if the super model is called sample, then
        // the field should be sample_id). If it is not, then we revert to using ORM to find the ID, which 
        // incurs a database hit.
        $this->previousCsvSupermodel['id'][$modelName]=
          isset($array[$id]) ? $array[$id] : $model->$modelName->id;
      }
    }
  }
  
  /**
   * Internal function that retrieves the metadata for a CSV upload. For AJAX requests, this comes 
   * from a cached file. For normal requests, the mappings should be in the $_POST data.
   */
  private function _get_metadata($csvTempFile) {
    $metadataFile = DOCROOT . "upload/" . str_replace('.csv','-metadata.txt', $csvTempFile);
    if (file_exists($metadataFile)) {      
      $metadataHandle = fopen($metadataFile, "r");
      $metadata = fgets($metadataHandle);
      fclose($metadataHandle);
      return json_decode($metadata, true);
    } else {
      // no previous file, so create default new metadata      
      return array('mappings'=>array(), 'settings'=>array(), 'errorCount'=>0);
    }
  }
  
  /**
   * During a csv upload, this method is called to retrieve a resource handle to a file that can 
   * contain errors during the upload. The file is created if required, and the headers from the 
   * uploaded csv file (referred to by handle) are copied into the first row of the new error file
   * along with a header for the problem description and row number.
   * @param string $csvTempFile File name of the imported CSV file.
   * @param resource $handle File handle
   * @return resource The error file's handle.
   */
  private function _get_error_file_handle($csvTempFile, $handle) {
    // move the file to the beginning, so we can load the first row of headers.
    fseek($handle, 0);
    $errorFile = str_replace('.csv','-errors.csv',$csvTempFile);
    $needHeaders = !file_exists($errorFile);
    $errorHandle = fopen($errorFile, "a");
    // skip the header row, but add it to the errors file with additional field for row number.
    $headers = fgetcsv($handle, 1000, ",");
    if ($needHeaders) {
      $headers[] = 'Problem';
      $headers[] = 'Row no.';
      fputcsv($errorHandle, $headers);
    }
    return $errorHandle;
  }

  /**
   * Runs at the start of each batch of rows. Checks if the previous imported row defined a supermodel. If so, we'll load
   * it from the Kohana cache. This allows us to determine if the new row can link to the same supermodel or not. An example
   * would be linking several occurrences to the same sample.
   * @param $cache
   */
  private function getPreviousRowSupermodel($cache)
  {
    $this->previousCsvSupermodel = $cache->get(basename($_GET['uploaded_csv']) . 'previousSupermodel');
    if (!$this->previousCsvSupermodel) {
      $this->previousCsvSupermodel = array(
          'id' => array(),
          'details' => array()
      );
    }
  }

  /**
   * Checks if there is a byte order marker at the beginning of the file (BOM). If so, sets this information in the $metadata.
   * Rewinds the file to the beginning.
   * @param $metadata
   * @param $handle
   * @return mixed
   */
  private function checkIfUtf8(&$metadata, $handle)
  {
    if (!isset($metadata['isUtf8'])) {
      fseek($handle, 0);
      $BOMCheck = fread($handle, 3);
      // Flag if this file has a UTF8 BOM at the start
      $metadata['isUtf8'] = $BOMCheck === chr(0xEF) . chr(0xBB) . chr(0xBF);
    }
  }
}