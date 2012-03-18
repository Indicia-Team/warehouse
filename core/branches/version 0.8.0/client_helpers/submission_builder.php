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

/**
 * Link in other required php files.
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
   *     'superModels' => array('parent model name' =>  array(
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
          $joinId = substr($value, strlen("joinsTo:$joinModel:"));
          if (is_numeric($joinId)) {
            array_push($joinsTo[$joinModel], $joinId);
          } elseif ($joinId==='id' || $joinId==='id[]') {
            if (is_array($values[$value])) {
              foreach ($values[$value] as $innerValue) {
                if (is_numeric($innerValue)) array_push($joinsTo[$joinModel], $innerValue);
              }
            } else {
              if (is_numeric($values[$value])) array_push($joinsTo[$joinModel], $values[$value]);
            }
          }
          // array_push($joinsTo[$joinModel], substr($value, strlen("joinsTo:$joinModel:")));
          // Remove the handled joinFields so they don't clutter the rest of the submission
          unset($values[$value]);
        }
      }
    }
    // Wrap the main model and attrs into JSON
    $modelWrapped = self::wrap_with_images($values, array_key_exists('fieldPrefix', $structure) ? $structure['fieldPrefix'] : $structure['model']);
    // Attach the specially handled fields to the model
    if (array_key_exists('metaFields', $structure)) {
       // need to be careful merging metafields in the structure and those auto generated in wrap_with_attrs (ie sample/location/occurrence attributes)
      if(!array_key_exists('metaFields', $modelWrapped))
	      $modelWrapped['metaFields']=array();
      foreach ($metaFields as $key=>$value) {
        $modelWrapped['metaFields'][$key]=$value;
      }
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
        $submodelWrapped = self::wrap_with_images($values, array_key_exists('fieldPrefix', $struct) ? $struct['fieldPrefix'] : $name);
        // Join the parent and child models together
        array_push($modelWrapped['subModels'], array('fkId' => $struct['fk'], 'model' => $submodelWrapped));
      }
    }
    if (array_key_exists('superModels', $structure)) {
      $modelWrapped['superModels']=array();
      foreach ($structure['superModels'] as $name => $struct) {
        // skip the supermodel if the foreign key is already populated in the main table.
        if (!isset($modelWrapped['fields'][$struct['fk']]['value']) || empty($modelWrapped['fields'][$struct['fk']]['value'])) {
          $supermodelWrapped = self::wrap_with_images($values, array_key_exists('fieldPrefix', $struct) ? $struct['fieldPrefix'] : $name);
          // Join the parent and child models together
          array_push($modelWrapped['superModels'], array(
            'fkId' => $struct['fk'],
            'model' => $supermodelWrapped
          ));
        }
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
    $attrEntity = self::get_attr_entity_prefix($entity, false).'Attr';
    // Iterate through the array
    foreach ($array as $key => $value)
    {
      // Don't wrap the authentication tokens, or any attributes tagged as belonging to another entity
      if ($key!='auth_token' && $key!='nonce') {
        if (strpos($key, "$entity:")===0 || !strpos($key, ':'))
        {
          // strip the entity name tag if present, as should not be in the submission attribute names
          $key = str_replace("$entity:", '', $key);
          // This should be a field in the model.
          // Add a new field to the save array
          $sa['fields'][$key] = array('value' => $value);
        } elseif ($attrEntity && (strpos($key, "$attrEntity:")===0)) {
          // custom attribute data can also go straight into the submission for the "master" table
          $sa['fields'][$key] = array('value' => $value);
        }
      } 
    }
    return $sa;
  }

  /**
   * Wraps a set of values for a model into JSON suitable for submission to the Indicia data services,
   * and also grabs the images and links them to the model. In previous versions of this method, this
   * included wrapping the attributes but this is no longer necessary so this method just delegates to 
   * wrap_with_images and is here for backwards compatibility only.
   * @deprecated
   *
   * @param array $values Array of form data (e.g. $_POST).
   * @param string $modelName Name of the model to wrap data for. If this is sample, occurrence or location
   * then custom attributes will also be wrapped. Furthermore, any attribute called $modelName:image can
   * contain an image upload (as long as a suitable entity is available to store the image in).
   */
  public static function wrap_with_attrs($values, $modelName, $fieldPrefix=null) {
    return self::wrap_with_images($values, $modelName, $fieldPrefix);
  }
  
  /**
   * Wraps a set of values for a model into JSON suitable for submission to the Indicia data services,
   * and also grabs the images and links them to the model.
   *
   * @param array $values Array of form data (e.g. $_POST).
   * @param string $modelName Name of the model to wrap data for. If this is sample, occurrence or location
   * then custom attributes will also be wrapped. Furthermore, any attribute called $modelName:image can
   * contain an image upload (as long as a suitable entity is available to store the image in).
   */
  public static function wrap_with_images($values, $modelName, $fieldPrefix=null) {
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
      $uploadpath = dirname($_SERVER['SCRIPT_FILENAME']).'/'.(isset(parent::$indicia_upload_path) ? parent::$indicia_upload_path : 'upload/');
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
   * @param string $entity Entity name (location, sample, occurrence, taxa_taxon_list or person).
   * Also 3 entities from the Individuals and Associations module (known_subject, subject_observation and mark).
   * @param boolean $except If true, raises an exception if the entity name does not have custom attributes. 
   * Otherwise returns false. Default true.
   * @access private
   */
  private static function get_attr_entity_prefix($entity, $except=true) {
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
      case 'taxa_taxon_list':
        $prefix = 'tax';
        break;
      case 'person':
        $prefix = 'psn';
        break;
      case 'known_subject':
        $prefix = 'ksj';
        break;
      case 'subject_observation':
        $prefix = 'sjo';
        break;
      case 'mark':
        $prefix = 'mrk';
        break;
      default:
        if ($except) 
		  throw new Exception('Unknown attribute type. ');
		else
		  return false;
    }
    return $prefix;
  }

}
?>