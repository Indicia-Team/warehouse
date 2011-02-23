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
 * @package	Client
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */
 
/**
 * Link in other required php files.
 */
require_once('lang.php');
require_once('helper_base.php');

/**
 * Static helper class that provides methods for dealing with imports.
 */
class import_helper extends helper_base {

  /**
   * Outputs an import wizard. The csv file to be imported should be available in the $_POST data, unless
   * the existing_file option is specified.
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>model</b><br/>
   * Required. The name of the model data is being imported into.</li>
   * <li><b>existing_file</b><br/>
   * Optional. The full path on the server to an already uploaded file to import.</li>
   * <li><b>auth</b><br/>
   * Read and write authorisation tokens.</li>
   * </ul>
   */
  public static function importer($options) {
    if (isset($_GET['total'])) {
      return self::upload_result($options);
    } elseif (!isset($_POST['import_step'])) {
      return self::import_settings_form($options);
    } elseif ($_POST['import_step']==1) {
      return self::upload_mappings_form($options);
    } elseif ($_POST['import_step']==2) {
      return self::run_upload($options);
    }
  }
  
  /**
   * Generates the import settings form. If none available, then outputs the upload mappings form.
   */
  private static function import_settings_form($options) {
    $r = '';
    $_SESSION['uploaded_file'] = self::get_uploaded_file($options);
    // by this time, we should always have an existing file
    if (empty($_SESSION['uploaded_file'])) throw new Exception('File to upload could not be found');
    $request = parent::$base_url."index.php/services/import/get_import_settings/".$options['model'];
    $request .= '?'.self::array_to_query_string($options['auth']['read']);
    $response = self::http_post($request, array());
    if (!empty($response['output'])) {
      // get the path back to the same page
      $reload = self::get_reload_link_parts();
      $reloadPath = $reload['path'];
      $r = '<div class="page-notice ui-state-highlight ui-corner-all">Before proceeding with the import, please specify '.
          "the following settings that will apply to every record in the import file.</div>\n".
          "<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\" class=\"iform\">\n".
          "<fieldset><legend>Import Settings</legend>\n";
      $r .= self::build_params_form(array(
        'form' => json_decode($response['output'], true),
        'readAuth' => $options['auth']['read']
      ));
      $r .= '<input type="hidden" name="import_step" value="1" />';
      $r .= '<input type="submit" name="submit" value="'.lang::get('Next').'" class="ui-corner-all ui-state-default button" />';
      $r .= '</fieldset></form>';      
    } else {
      // No settings form, so output the mappings form instead which is the next step.
      $r = self::upload_mappings_form($options);
    }
    return $r;
  }
  
  /**
   * Outputs the form for mapping columns to the import fields.
   */
  private static function upload_mappings_form($options) {
    if (!file_exists($_SESSION['uploaded_file']))
      return lang::get('upload_not_available');
    $filename=basename($_SESSION['uploaded_file']);
    // capture the settings form if there is one, but only use the actually set values - others can be populated per row.
    foreach ($_POST as $key => $value) {
      if (empty($value)) {
        unset($_POST[$key]);
      }
    } 
    if (isset($_POST['import_step']))
      $settings = json_encode($_POST);
    else 
      $settings = '{}';    
    // cache the mappings
    $metadata = array('settings' => $settings);
    $post = array_merge($options['auth']['write_tokens'], $metadata);
    $request = parent::$base_url."index.php/services/import/cache_upload_metadata?uploaded_csv=$filename";
    $response = self::http_post($request, $post);
    if (!isset($response['output']) || $response['output'] != 'OK')
      return "Could not upload the settings metadata. <br/>".print_r($response, true);
      
    $request = parent::$base_url."index.php/services/import/get_import_fields/".$options['model'];
    $request .= '?'.self::array_to_query_string($options['auth']['read']);
    // include survey and website information in the request if available, as this limits the availability of custom attributes
    if (!empty($_POST['website_id']))
      $request .= '&website_id='.$_POST['website_id'];
    if (!empty($_POST['survey_id']))
      $request .= '&survey_id='.$_POST['survey_id'];
    $response = self::http_post($request, array());
    $fields = json_decode($response['output'], true);
    // We are about to build the list of fields you can choose for the column mappings. Remove any which 
    // we already have a value for in the settings.
    $fields = array_diff_key($fields, $_POST);
    $options = self::model_field_options($options['model'], $fields);
    $handle = fopen($_SESSION['uploaded_file'], "r");
    $columns = fgetcsv($handle, 1000, ",");
    $reload = self::get_reload_link_parts();
    $reloadPath = $reload['path'];
    $r ="<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\" class=\"iform\">\n".
        '<p>'.lang::get('column_mapping_instructions').'</p>'.
        '<table class="ui-widget ui-widget-content">'.
        '<thead class="ui-widget-header">'.
        '<tr><th>Column in CSV File</th><th>Maps to attribute</th></tr>'.
        '</thead>'.
        '<tbody>';
    foreach ($columns as $column) {
      $colFieldName = preg_replace('/[^A-Za-z0-9]/', '_', $column);
      $r .= "<tr><td>$column</td><td><select name=\"$colFieldName\" id=\"$colFieldName\">$options</select></td></tr>\n";
    }
    $r .= '</tbody>';
    $r .= '</table>';
    $r .= '<input type="hidden" name="import_step" value="2" />';
    $r .= '<input type="submit" name="submit" value="'.lang::get('Upload').'" class="ui-corner-all ui-state-default button" />';
    $r .= '</form>';
    return $r;
  }

  /**
   * Display the page which outputs the upload progress bar. Adds JavaScript to the page which performs the chunked upload.
   */
  private static function run_upload($options) {    
    if (!file_exists($_SESSION['uploaded_file']))
      return lang::get('upload_not_available');
    $filename=basename($_SESSION['uploaded_file']);
    // move file to server
    self::send_file_to_warehouse($filename, false, $options['auth']['read'], 'import/upload_csv');    
    
    $reload = self::get_reload_link_parts();
    $reloadPath = $reload['path'];
    
    // initiate local javascript to do the upload with a progress feedback
    $r = '
<div id="progress" class="ui-widget ui-widget-content ui-corner-all">
<div id="progress-bar" style="width: 400"></div>
<div id="progress-text">Preparing to upload.</div>
';
    // cache the mappings
    $metadata = array('mappings' => json_encode($_POST));
    $post = array_merge($options['auth']['write_tokens'], $metadata);
    $request = parent::$base_url."index.php/services/import/cache_upload_metadata?uploaded_csv=$filename";
    $response = self::http_post($request, $post);
    if (!isset($response['output']) || $response['output'] != 'OK')
      return "Could not upload the mappings metadata. <br/>".print_r($response, true);
    
    self::$onload_javascript .= "
  /**
  * Upload a single chunk of a file, by doing an AJAX get. If there is more, then on receiving the response upload the
  * next chunk.
  */
  uploadChunk = function() {
    var limit=10;
    jQuery.getJSON('".parent::$base_url."index.php/services/import/upload?offset='+total+'&limit='+limit+'&uploaded_csv=$filename&model=".$options['model']."',
      function(response) {
        total = total + response.uploaded;
        jQuery('#progress-text').html(total + ' records uploaded.');
        $('#progress-bar').progressbar ('option', 'value', response.progress);
        if (response.uploaded>=limit) {
          uploadChunk();
        } else {
          jQuery('#progress-text').html('Upload complete.');
          window.location = '$reloadPath?uploaded_csv=$filename&total='+total;
        }
      }
    );  
  };
  
  var total=0;
  jQuery('#progress-bar').progressbar ({value: 0});
  uploadChunk();
  ";
    return $r;
  }
  
  /**
   * Displays the upload result page.
   */
  public static function upload_result($options) {
    $request = parent::$base_url."index.php/services/import/get_upload_result?uploaded_csv=".$_GET['uploaded_csv'];
    $request .= '&'.self::array_to_query_string($options['auth']['read']);
    $response = self::http_post($request, array());
    
    if (isset($response['output'])) {
      $output = json_decode($response['output'], true);
      if (!is_array($output) || !isset($output['problems']))
        return 'An error occurred during the upload.<br/>'.print_r($response, true);
    
      if ($output['problems']!==0) {
        $r = $output['problems'].' problems were detected during the import. <a href="'.$output['file'].'">Download the records that did not import.</a>';
      } else {
        $r = 'The upload was successful.';
      }
    } else {
      return 'An error occurred during the upload.<br/>'.print_r($response, true);
    }
    return $r;
  }
  
  
 /**
  * Returns a list of columns as an list of <options> for inclusion in an HTML drop down,
  * loading the columns from a model that are available to import data into
  * (excluding the id and metadata).
  * @param string $model Name of the model
  * @param array $fields List of the available import columns
  * @param string $default The text to display for the unselected "please select" item.
  * @param string $selected The name of the initially selected field if there is one.  
  */
  private static function model_field_options($model, $fields, $selected='') {
    $r = '';
    $skipped = array('id', 'created_by_id', 'created_on', 'updated_by_id', 'updated_on',
        'fk_created_by', 'fk_updated_by', 'fk_meaning', 'fk_taxon_meaning', 'deleted', 'image_path');
    $heading='';
    foreach ($fields as $field=>$caption) {
      list($prefix,$fieldname)=explode(':',$field);
      // If this is a new section of data, output a heading
      if ($prefix!=$heading) {
        $heading = $prefix;
        if (!empty($r)) $r .= '</optgroup>';
        $r .= '<optgroup label="'.self::leadingCaps(lang::get($heading)).'">';
      }
      if (empty($caption)) {        
        // Skip the metadata fields
        if (!in_array($fieldname, $skipped)) {
          // make a clean looking caption
          if (substr($fieldname,0,3)=='fk_') {
            $captionSuffix=' ('.lang::get('lookup existing record').')';
          } else {
            $captionSuffix='';
          }
          $fieldname=str_replace(array('fk_','_id'), array('',''), $fieldname);
          if ($prefix==$model || $prefix=="metaFields" || $prefix==substr($fieldname,0,strlen($prefix))) {
            $caption = self::leadingCaps($fieldname).$captionSuffix;
          } else {
            $caption = self::leadingCaps("$fieldname").$captionSuffix;
          }
          $r .= self::model_field_option($field, $caption, $selected);
        }
      } else {
        $r .= self::model_field_option($field, $caption, $selected);
      }
    }
    $r = '<option>&lt;'.lang::get('Please select').'&gt;</option>'.$r.'</optgroup>';
    return $r;
  }
  
  /**
   * Method to upload the file in the $_FILES array, or return the existing file if already uploaded.
   * @access private
   */
  private static function get_uploaded_file($options) {
    if (!isset($options['existing_file']) && !isset($_POST['import_step'])) {
      // No existing file, but on the first step, so the $_POST data must contain the single file.
      if (count($_FILES)!=1) throw new Exception('There must be a single file uploaded to import');
      // reset gets the first array element
      $file = reset($_FILES);
      // Get the original file's extension
      $parts = explode(".",$file['name']);
      $fext = array_pop($parts);
      if ($fext!='csv') throw new Exception('Uploaded file must be a csv file');
      // Generate a file id to store the upload as
      $destination = time().rand(0,1000).".".$fext;
      $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
      $relpath = self::relative_client_helper_path();
      if (move_uploaded_file($file['tmp_name'], $relpath.$interim_image_folder.$destination)) {
        return $relpath.$interim_image_folder.$destination;
      }
    } elseif (isset($options['existing_file']))
      return $options['existing_file'];
    return isset($_POST['existing_file']) ? $_POST['existing_file'] : '';
  }
  
  /**
   * Humanize a piece of text by inserting spaces instead of underscores, and making first letter
   * of each word capital.
   *
   * @param string $text The text to alter.
   * @return The altered string.
   */
  private static function leadingCaps($text) {
    return ucwords(preg_replace('/[\s_]+/', ' ', $text));
  }
  
  /**
   * Private method to build a single select option for the model field options. 
   * Option is selected if selected=caption (case insensitive).
   */
  private static function model_field_option($field, $caption, $selected) {
    $selHtml = (strcasecmp($caption,$selected)==0) ? ' selected="selected"' : '';
    // look in the translation settings to see if this column name needs overriding
    $trans = lang::get("dd:$field");
    // Only update the caption if this actually did anything
    if ($trans != "dd:$field" ) {
      $caption=$trans;
    }
    return '<option class="sub-option" value="'.htmlspecialchars($field)."\"$selHtml>".htmlspecialchars($caption).'</option>';
  }

}

?>