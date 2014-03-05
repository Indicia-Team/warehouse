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
    if (array_key_exists('save-site-flag', $array) && $array['save-site-flag']==='1')
      self::create_personal_site($array);
    // Initialise the wrapped array
    $sa = array(
        'id' => $entity,
        'fields' => array()
    );
    if ($field_prefix) {
      $sa['field_prefix']=$field_prefix;
    }
    $attrEntity = self::get_attr_entity_prefix($entity, false).'Attr';
    // complex json multivalue attributes need special handling
    $complexAttrs=array();
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
          // custom attribute data can also go straight into the submission for the "master" table. Array data might need 
          // special handling to link it to existing database records.
          if (is_array($value) && count($value)>0) {
            // The value is an array
            foreach ($value as $idx=>$arrayItem) {
              // does the entry contain the fieldname (required for existing values in controls which post arrays, like multiselect selects)?
              if (preg_match("/\d+:$attrEntity:\d+:\d+/", $arrayItem)) {
                $tokens=explode(':', $arrayItem, 2);
                $sa['fields'][$tokens[1]] = array('value' => $tokens[0]);
              //Additional handling for multi-value controls such as easyselect lists in species grids where the selected items are displayed under
              //the main control. These items have both the value itself and the attribute_value id in the value field
              } elseif (preg_match("/^\d+:\d+$/", $arrayItem)) {
                $tokens=explode(':', $arrayItem);
                $sa['fields']["$key:$tokens[1]:$idx"] = array('value' => $tokens[0]);
              } else
                $sa['fields']["$key::$idx"]=array('value' => $arrayItem);
            }
          } else
            $sa['fields'][$key] = array('value' => $value);
        } elseif ($attrEntity && (strpos($key, "$attrEntity+:")===0)) {
          // a complex custom attribute data value which will need to be json encoded.
          $tokens=explode(':', $key);
          if ($tokens[4]==='deleted') {
            if ($value==='t') {
              $complexAttrs[$key]='deleted';
            } 
          } else {
            $attrKey = str_replace('+', '', $tokens[0]) . ':' . $tokens[1];
            if (!empty($tokens[2]))
              // existing value record
              $attrKey .= ':'.$tokens[2];
            $exists = isset($complexAttrs[$attrKey]) ? $complexAttrs[$attrKey] : array();
            if ($exists!=='deleted') {
              $exists[$tokens[3]][$tokens[4]] = $value;
              $complexAttrs[$attrKey]=$exists;
            }
          }
        }
      } 
    }
    foreach($complexAttrs as $attrKey=>$data) {
      if ($data==='deleted')
        $sa['fields'][$attrKey]=array('value'=>'');
      else {
        $sa['fields'][$attrKey]=array('value'=>array());
        $exists = count(explode(':', $attrKey))===3;
        foreach (array_values($data) as $row) {
          // find any term submissions in form id:term, and split into 2 json fields. Also process checkbox groups into suitable array form.
          $terms=array();
          foreach ($row as $key=>&$val) {
            if (is_array($val)) {
              // array from a checkbox_group
              $subvals=array();
              $subterms=array();
              foreach ($val as $subval) {
                $split=explode(':', $subval, 2);
                $subvals[] =  $split[0];
                $subterms[] =  $split[1];
              }
              $val=$subvals;
              $terms[$key.'_term']=$subterms;
            } else {
              if (preg_match('/^[0-9]+\:.+$/', $val)) {
                $split=explode(':', $val, 2);
                $val = $split[0];
                $terms[$key.'_term']=$split[1];
              }
            }
          }
          $row += $terms;
          if (implode('', array_values($row))<>'') {
            if ($exists)
              // existing value, so no need to send an array
              $sa['fields'][$attrKey]['value'] = json_encode($row);
            else
              // could be multiple new values, so send an array
              $sa['fields'][$attrKey]['value'][] = json_encode($row);
          } elseif ($exists) {
            // submitting an empty set for existing row, so deleted
            $sa['fields'][$attrKey]=array('value'=>'');
          }
        }
      }
    }
    if ($entity==='occurrence' && function_exists('hostsite_get_user_field') && hostsite_get_user_field('training')) 
      $sa['fields']['training'] = array('value' => 'on');
    // useLocationName is a special flag to indicate that an unmatched location can go
    // in the locaiton_name field.
    if (isset($array['useLocationName'])) {
      if ($entity==='sample') {
        if ((empty($sa['fields']['location_id']) || empty($sa['fields']['location_id']['value']))
            && !empty($array['imp-location:name']))
          $sa['fields']['location_name']=array('value'=>$array['imp-location:name']);
        else 
          $sa['fields']['location_name']=array('value'=>'');
      }
      unset($array['useLocationName']);
    }
    return $sa;
  }
  
  /**
   * Creates a site using the form submission data and attaches the location_id to the
   * sample information in the submission.
   * @param array Form submission data. 
   */
  private static function create_personal_site(&$array) {
    // Check we don't already have a location ID, and have the other stuff we require
    if (!empty($array['sample:location_id']) || !array_key_exists('imp-location:name', $array)
        || !array_key_exists('sample:entered_sref', $array) || !array_key_exists('sample:entered_sref_system', $array))
      return;
    $loc = array(
      'location:name'=>$array['imp-location:name'],
      'location:centroid_sref'=>$array['sample:entered_sref'],
      'location:centroid_sref_system'=>$array['sample:entered_sref_system'],
      'locations_website:website_id' => $array['website_id']
    );
    if (!empty($array['sample:geom']))
      $loc['location:centroid_geom']=$array['sample:geom'];
    $submission = self::build_submission($loc, array('model'=>'location',
        'subModels'=>array('locations_website'=>array('fk'=>'location_id'))));
    $request = parent::$base_url."index.php/services/data/save";
    $postargs = 'submission='.urlencode(json_encode($submission));
    // Setting persist_auth allows the write tokens to be re-used
    $postargs .= '&persist_auth=true&auth_token='.$array['auth_token'];
    $postargs .= '&nonce='.$array['nonce'];
    if (function_exists('hostsite_get_user_field')) 
      $postargs .= '&user_id='.hostsite_get_user_field('indicia_user_id');
    $response = data_entry_helper::http_post($request, $postargs);
    // The response should be in JSON if it worked
    if (isset($response['output'])) {
      $output = json_decode($response['output'], true);
      if (!$output)
        throw new exception(print_r($response, true));
      elseif (isset($output['success']) && $output['success']==='multiple records')
        $array['sample:location_id']=$output['outer_id'];
      elseif (isset($output['success']))
        $array['sample:location_id']=$output['success'];
      else
        throw new exception(print_r($response, true));
    }
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
    
    // Build sub-models for the media files. Don't post to the warehouse until after validation success. This 
    // also moves any simple uploaded files to the interim image upload folder.
    $media = data_entry_helper::extract_media_data($values, $modelName.'_medium', true, true);
    
    foreach ($media as $item) {
      $wrapped = self::wrap($item, $modelName.'_medium');      
      $modelWrapped['subModels'][] = array(
          'fkId' => $modelName.'_id',
          'model' => $wrapped
      );
    }
    return $modelWrapped;
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
      case 'identifier':
        $prefix = 'idn';
        break;
      case 'identifiers_subject_observation':
        $prefix = 'iso';
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