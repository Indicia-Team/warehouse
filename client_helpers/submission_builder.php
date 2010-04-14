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
 * @package  Client
 * @author  Indicia Team
 * @license  http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link   http://code.google.com/p/indicia/
 */

require_once('lang.php');
require_once('helper_config.php');
require_once('data_entry_helper.php');

/**
 * Provides a helper to build submissions.
 *
 * @package  Client
 */

class submission_builder extends helper_config {

  /**
   * Helper function to simplify building of a submission. Does simple submissions that do not involve
   * species checklist grids.
   * @param array $values List of the posted values to create the submission from.
   * @param array $structure Describes the structure of the submission. The form should be:
   * array(
   *     'model' => 'main model name',
   *     'fieldPrefix' => 'Optional prefix for HTML form fields in the main model. If not specified then the main model name is used.',
   *     'subModels' => array('child model name' =>  array(
   *         'fieldPrefix'=>'Optional prefix for HTML form fields in the sub model. If not specified then the sub model name is used.',
   *         'fk' => 'foreign key name',
   *         'image_entity' => 'name of image entity if present'
   *     )),
   *     'superModels' => array('child model name' =>  array(
   *         'fieldPrefix'=>'Optional prefix for HTML form fields in the sub model. If not specified then the sub model name is used.',
   *         'fk' => 'foreign key name',
   *         'image_entity' => 'name of image entity if present'
   *     )),
   *     'metaFields' => array('fieldname1', 'fieldname2', ...),
   *     'joinsTo' => array('model that this has a many to many join with', ...)
   * )
   */
  public static function build_submission($values, $structure) {
    // Handle metaFields and joinsTo first so they don't end up in other parts of the submission (specially handled fields)
    if (array_key_exists('metaFields', $structure)) {
      $metaFields=array();
      foreach($structure['metaFields'] as $metaField) {
        if (array_key_exists("metaFields:$metaField", $values)) {
          $metaFields[$metaField] = array('value'=>$values["metaFields:$metaField"]);
          unset($values["metaFields:$metaField"]);
        }
      }
    }
    if (array_key_exists('joinsTo', $structure)) {
      $joinsTo=array();
      foreach($structure['joinsTo'] as $joinsToTable) {
        // find any POST data that indicates a join to this table (key=joinsTo:table:id)
        $joinModel = inflector::singular($joinsToTable);
        $joinsTo[$joinModel]=array();
        $joinsToModel = preg_grep('/^joinsTo:'.$joinModel.':.+$/', array_keys($values) );
        foreach($joinsToModel as $key=>$value) {
          array_push($joinsTo[$joinModel], substr($value, strlen("joinsTo:$joinModel:")));
          // Remove the handled joinFields so they don't clutter the rest of the submission
          unset($values[$value]);
        }
      }
    }
    // Wrap the main model and attrs into JSON
    $modelWrapped = self::wrap_with_attrs($values, array_key_exists('fieldPrefix', $structure) ? $structure['fieldPrefix'] : $structure['model']);
    // Attach the specially handled fields to the model
    if (array_key_exists('metaFields', $structure)) {
      // need to be careful merging metafields in the structure and those auto generated in wrap_with_attrs (ie sample/location/occurrence attributes)
      if(!array_key_exists('metaFields', $modelWrapped))
	      $modelWrapped['metaFields']=array();
	  foreach ($metaFields as $metaField) {$modelWrapped['metaFields'][]=$metaField;}
    }
    if (array_key_exists('joinsTo', $structure)) {
      $modelWrapped['joinsTo']=$joinsTo;
    }
    // Handle the child model if present
    if (array_key_exists('subModels', $structure)) {
      // need to be careful merging submodels in the structure and those auto generated in wrap_with_attrs (ie images)
      if(!array_key_exists('subModels', $modelWrapped))
	      $modelWrapped['subModels']=array();
      foreach ($structure['subModels'] as $name => $struct) {
        $submodelWrapped = self::wrap_with_attrs($values, array_key_exists('fieldPrefix', $struct) ? $struct['fieldPrefix'] : $name);
        // Join the parent and child models together
        array_push($modelWrapped['subModels'], array('fkId' => $struct['fk'], 'model' => $submodelWrapped));
      }
    }
    if (array_key_exists('superModels', $structure)) {
      $modelWrapped['superModels']=array();
      foreach ($structure['superModels'] as $name => $struct) {
        $supermodelWrapped = self::wrap_with_attrs($values, array_key_exists('fieldPrefix', $struct) ? $struct['fieldPrefix'] : $name);
        // Join the parent and child models together
        array_push($modelWrapped['superModels'], array(
          'fkId' => $struct['fk'],
          'model' => $supermodelWrapped
        ));
      }
    }
    return $modelWrapped;

  }

  /**
   * Wraps an array (e.g. Post or Session data generated by a form) into a structure
   * suitable for submission.
   * <p>The attributes in the array are all included, unless they
   * named using the form entity:attribute (e.g. sample:date) in which case they are
   * only included if wrapping the matching entity. This allows the content of the wrap
   * to be limited to only the appropriate information.</p>
   * <p>Do not prefix the survey_id or website_id attributes being posted with an entity
   * name as these IDs are used by Indicia for all entities.</p>
   *
   * @param array $array Array of data generated from data entry controls.
   * @param string $entity Name of the entity to wrap data for.
   * @param string $field_prefix Name of the prefix each field on the form has. Used to construct an error
   * message array that can be linked back to the source fields easily.
   */
  public static function wrap($array, $entity, $field_prefix=null)
  {
    // Initialise the wrapped array
    $sa = array(
        'id' => $entity,
        'fields' => array()
    );
    if ($field_prefix) {
      $sa['field_prefix']=$field_prefix;
    }

    // Iterate through the array
    foreach ($array as $key => $value)
    {
      // Don't wrap the authentication tokens, or any attributes tagged as belonging to another entity
      if ($key!='auth_token' && $key!='nonce' && (strpos($key, "$entity:")===0 || !strpos($key, ':')))
      {
        // strip the entity name tag if present, as should not be in the submission attribute names
        $key = str_replace("$entity:", '', $key);
        // This should be a field in the model.
        // Add a new field to the save array
        $sa['fields'][$key] = array('value' => utf8_encode($value));
      }
    }
    return $sa;
  }

  /**
  * Wraps attribute fields (entered as normal) into a suitable container for submission.
  * Throws an error if $entity is not something for which attributes are known to exist.
  *
  * @return array Attribute part of submission structure.
  */
  public static function wrap_attributes($arr, $entity) {
    $prefix=self::get_attr_entity_prefix($entity).'Attr';
    $oap = array();
    $occAttrs = array();
    foreach ($arr as $key => $value) {
      // Null out any blank dates
      if ($value==lang::get('click here')) {
        $value='';
      }
      if (strpos($key, $prefix) === 0) {
        $a = explode(':', $key);
        // Attribute in the form occAttr:36 for attribute with attribute id
        // of 36.
        // for multiple values where existing value did not exist, the value will be an array.
        if(is_array($value)){
        foreach ($value as $individual) {
            $oap[] = array($entity."_attribute_id" => $a[1], 'value' => $individual);
        }
        } else {
          $oap[] = array(
            $entity."_attribute_id" => $a[1], 'value' => $value
          );
        }
        // If it is an existing attribute value, we need to also put the ID in the array so it
        // can update the same value rather than create a new one
        if (count($a)>2) {
          $oap[count($oap)-1]['id'] = $a[2];
        }

      }
    }
    foreach ($oap as $oa) {
      $occAttrs[] = array(
        'id' => $entity,
        'fields' => $oa
      );
    }
    return $occAttrs;
  }

  /**
   * Wraps a set of values for a model into JSON suitable for submission to the Indicia data services,
   * and also grabs the custom attributes (if there are any) and links them to the model.
   *
   * @param array $values Array of form data (e.g. $_POST).
   * @param string $modelName Name of the model to wrap data for. If this is sample, occurrence or location
   * then custom attributes will also be wrapped. Furthermore, any attribute called $modelName:image can
   * contain an image upload (as long as a suitable entity is available to store the image in).
   */
  public static function wrap_with_attrs($values, $modelName, $fieldPrefix=null) {
    // Now search for an input controls with the name image_upload.
    // For example, this occurs on the taxon_image edit page of the Warehouse.
    // We do this first so the uploaded image path can be put into the submission
    if (array_key_exists('image_upload', $_FILES) && $_FILES['image_upload']['name']) {
      $file = $_FILES['image_upload'];
      // Get the original file's extension
      $parts = explode(".",$file['name']);
      $fext = array_pop($parts);
      // Generate a file id to store the image as
      $destination = time().rand(0,1000).".".$fext;
      $uploadpath = 'C:/webserver/apache/htdocs/indicia/upload/';
      if (move_uploaded_file($file['tmp_name'], $uploadpath.$destination)) {
        // record the new file name, also note it in the $_POST data so it can be tracked after a validation failure
        $_FILES['image_upload']['name'] = $destination;
        $values['path'] = $destination;
        // This is the final file destination, so create the image files.
        Image::create_image_files($uploadpath, $destination);
      } 
    }
    // Get the parent model into JSON
    $modelWrapped = self::wrap($values, $modelName, $fieldPrefix);
    // Might it have custom attributes?
    if (strcasecmp($modelName, 'occurrence')==0 ||
        strcasecmp($modelName, 'sample')==0 ||
        strcasecmp($modelName, 'location')==0) {
      // Get the attributes
      $attrs = self::wrap_attributes($values, $modelName);
      // If any exist, then store them in the model
      if (count($attrs)>0) {
        $modelWrapped['metaFields'][self::get_attr_entity_prefix($modelName).'Attributes']['value']=$attrs;
      }
    }
    // Build sub-models for the image files. Don't post to the warehouse until after validation success. This 
    // also moves any simple uploaded files to the interim image upload folder.
    $images = data_entry_helper::extract_image_data($values, $modelName.'_image', true, true);
    foreach ($images as $image) {
      $imageWrap = self::wrap($image, $modelName.'_image');      
      $modelWrapped['subModels'][] = array(
          'fkId' => $modelName.'_id',
          'model' => $imageWrap
      );
    }
    return $modelWrapped;
  }
  

  /**
   * Takes uploaded files from the form submission and saves them on the server. Optionally sends it
   * to the warehouse via the data services handle_media method, in which case the local copy is
   * deleted.
   *
   * @param String $media_id Base name of the file entry in the $_FILES array e.g. occurrence:image.
   * Multiple files can be uploaded if they have a suffix 0f :0, :1 ... :n
   * @param Boolean $submit If true (default), then the file is uploaded first into the local
   * server's directory structure, then sent to the Indicia Warehouse using the data services.
   * If false, then it is uploaded to the local server only.
   * @return Array Returns with key success, set to true if files were uploaded, false if nothing was uploaded or there
   * was an error. If an error, then there can be a single error key or an array caller errors with details of errors encountered 
   * when uploading. On success, the return array also has a key 'files' populated with final filenames of the uploaded files 
   * so that they can be inserted in a field which stores the file path.
   */
  public static function handle_media($media_id, $submit=true) {

    $files = self::get_uploaded_files($media_id);
    $return = array();
    $uploadpath = parent::$upload_path;
    $target_url = parent::$base_url."/index.php/services/data/handle_media";
    $i = 0;

    foreach ($files as $file) {
      $name = $file['name'];
      $fname = $file['tmp_name'];
      $parts = explode(".",$name);
      $fext = array_pop($parts);
      // Generate a file id to store the image as
      $destination = time().rand(0,1000).".".$fext;

      if (move_uploaded_file($fname, $uploadpath.$destination)) {
        //successfully stored locally
        if ($submit) {
          //send to the warehouse
          $postargs = array();
          if (array_key_exists('auth_token', $_POST)) {
            $postargs['auth_token'] = $_POST['auth_token'];
          }
          if (array_key_exists('nonce', $_POST)) {
            $postargs['nonce'] = $_POST['nonce'];
          }
          $file_to_upload = array('media_upload'=>'@'.realpath($uploadpath.$destination));          
          $response = data_entry_helper::http_post($target_url, $file_to_upload + $postargs);
          $output = json_decode($response['output'], true);
          if (is_array($output)) {
            //an array signals an error
            if (array_key_exists('error', $output)) {
              $return['error'] = $output['error'];
              //attach the errors to the control that caused them
              //PROBLEM assumes numeric suffix
              if (array_key_exists('errors', $output)) {
                $return['errors']["$media_id:$i"] = $output['errors']['media_upload'];
              }
            }
          } else {
            //filenames are returned without structure
            //the output of json_decode may not be valid.
            $return['files'][] = $response['output'];
          }
          //remove local copy
          unlink($uploadpath.$destination);
        } else {
          // store locally so create the thumbnails or other image sizes.
          Image::create_image_files($uploadpath, $destination);
          $return['files'][] = $destination;
        }
      } 
      else {
        $return['error'] = 'Upload error';
        //attach the errors to the control that caused them
        //PROBLEM assumes numeric suffix
        $return['errors']["$media_id:$i"] = 'Sorry, there was a problem uploading this file.';
      }      
      $i++;
    }
    //finished looping through files. If no errors in the response array, all went well.
    $return['success'] = !(array_key_exists('error', $return) || array_key_exists('errors', $return)) && count($files)>0;

    return $return;
  }
 
  /**
   * Interprets the $_FILES information and returns an array of files uploaded
   * @param String $media_id Base name of the file entry in the $_FILES array e.g. occurrence:image.
   * Multiple upload fields can exist on a form if they have a suffix 0f :0, :1 ... :n
   * @access private
   * @return Array of file details of the uploaded files.
   */
  private static function get_uploaded_files($media_id) {

    $files = array();

    if (array_key_exists($media_id, $_FILES)) {
      //there is a single upload field
      if($_FILES[$media_id]['name'] != '') {
        //that field has a file
        $files[] = $_FILES[$media_id];
      }
    }
    elseif (array_key_exists($media_id .':0', $_FILES)) {
      //there are multiple upload fields
      $i = 0;
      $key = $media_id .':'. $i;
      do {
        //loop through those fields
        if($_FILES[$key]['name'] != '') {
          //the field has a file
          $files[] = $_FILES[$key];
        }
        $i++;
        $key = $media_id .':'. $i;
      } while (array_key_exists($key, $_FILES));
    }
    else {
      //there are no upload fields so an empty array is returned
    }

    return $files;
  }

  /**
   * Returns a 3 character prefix representing an entity name that can have
   * custom attributes attached.
   * @param string $entity Entity name (location, sample or occurrence).
   * @access private
   */
  private static function get_attr_entity_prefix($entity) {
    switch ($entity) {
      case 'occurrence':
        $prefix = 'occ';
        break;
      case 'location':
        $prefix = 'loc';
        break;
      case 'sample':
        $prefix = 'smp';
        break;
      default:
        throw new Exception('Unknown attribute type. ');
    }
    return $prefix;
  }

}
?>