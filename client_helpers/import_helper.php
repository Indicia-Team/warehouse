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
   * <li><b>readAuth</b><br/>
   * Read authorisation tokens.</li>
   * </ul>
   */
  public static function importer($options) {
    if (!isset($_POST['import_step'])) {
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
    $request .= '?'.self::array_to_query_string($options['readAuth']);
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
        'readAuth' => $options['readAuth']
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
    // capture the settings form if there is one
    if (isset($_POST['import_step']))
      $_SESSION['upload_settings'] = $_POST;
    else 
      $_SESSION['upload_settings'] = array();
    $request = parent::$base_url."index.php/services/import/get_import_fields/".$options['model'];
    $request .= '?'.self::array_to_query_string($options['readAuth']);
    $response = self::http_post($request, array());
    $fields = json_decode($response['output'], true);
    $options = self::model_field_options($options['model'], $fields);
    $handle = fopen($_SESSION['uploaded_file'], "r");
    $columns = fgetcsv($handle, 1000, ",");
    $reload = self::get_reload_link_parts();
    $reloadPath = $reload['path'];
    $r ="<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\" class=\"iform\">\n".
        '<p>Please map each column in the CSV file you are uploading to the associated attribute in the destination list.</p>'.
        '<table class="ui-widget ui-widget-content">'.
        '<thead class="ui-widget-header">'.
        '<tr><th>Column in CSV File</th><th>Maps to attribute</th></tr>'.
        '</thead>'.
        '<tbody>';
    foreach ($columns as $column) {
      $colFieldName = preg_replace('/[^A-Za-z0-9]/', '_', $column);
      $r .= "<tr><td>$column</td><td><select name=\"$colFieldName\">$options</select></td></tr>\n";
    }
    $r .= '</tbody>';
    $r .= '</table>';
    $r .= '<input type="hidden" name="import_step" value="2" />';
    $r .= '<input type="submit" name="submit" value="'.lang::get('Next').'" class="ui-corner-all ui-state-default button" />';
    $r .= '</form>';
    return $r;
  }
  
  private static function run_upload($options) {
    echo "Doing upload<br/>";
    echo $_SESSION['uploaded_file'];
    echo self::send_file_to_warehouse(basename($_SESSION['uploaded_file']), false, $options['readAuth']);
    // move file to server
    // send mappings plus settings to server
    // initiate local javascript to do the upload with a progress feedback
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
    $r = '<option>&lt;'.lang::get('Please select').'&gt;</option>';
    $skipped = array('id', 'created_by_id', 'created_on', 'updated_by_id', 'updated_on',
        'fk_created_by', 'fk_updated_by', 'fk_meaning', 'fk_taxon_meaning', 'deleted', 'image_path');
    foreach ($fields as $field=>$caption) {
      if (empty($caption)) {
        list($prefix,$fieldname)=explode(':',$field);
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
            $caption = self::leadingCaps("$prefix $fieldname").$captionSuffix;
          }
          $r .= self::model_field_option($field, $caption, $selected, $model);
        }
      } else {
        $r .= self::model_field_option($field, $caption, $selected, $model);
      }
    }
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
  private static function model_field_option($field, $caption, $selected, $modelName) {
    $selHtml = (strcasecmp($caption,$selected)==0) ? ' selected="selected"' : '';
    // look in the translation settings to see if this column name needs overriding
    $langKey = "$modelName.$caption";
    $trans = lang::get($langKey);
    // Only update the caption if this actually did anything
    if ($trans != $langKey) {
      $caption=$trans;
    }
    return '<option value="'.htmlspecialchars($field)."\"$selHtml>".htmlspecialchars($caption).'</option>';
  }

}

?>