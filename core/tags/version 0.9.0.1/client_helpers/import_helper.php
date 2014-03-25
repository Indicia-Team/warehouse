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
 * @author   Indicia Team
 * @license  http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Link in other required php files.
 */
require_once('lang.php');
require_once('helper_base.php');

/**
 * Static helper class that provides methods for dealing with imports.
 * @package Client
 */
class import_helper extends helper_base {

  /**
   * @var boolean Flag set to true if the host system is capable of storing our user's remembered import mappings
   * for future imports.
   */
  private static $rememberingMappings=true;

  /**
   * Outputs an import wizard. The csv file to be imported should be available in the $_POST data, unless
   * the existing_file option is specified.
   * Additionally, if there are any preset values which apply to each row in the import data then you can
   * pass these to the importer in the $_POST data. For example, you could set taxa_taxon_list:taxon_list_id=3 in
   * the $_POST data when importing species data to force it to go into list 3.
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>model</b><br/>
   * Required. The name of the model data is being imported into.</li>
   * <li><b>existing_file</b><br/>
   * Optional. The full path on the server to an already uploaded file to import.</li>
   * <li><b>auth</b><br/>
   * Read and write authorisation tokens.</li>
   * <li><b>presetSettings</b><br/>
   * Optional associative array of any preset values for the import settings. Any settings which have a presetSetting specified
   * will be ommitted from the settings form.</li>
   * </ul>
   */
  public static function importer($options) {
    if (isset($_GET['total'])) {
      return self::upload_result($options);
    } elseif (!isset($_POST['import_step'])) {
      if (count($_FILES)==1)
        return self::import_settings_form($options);
      else
        return self::upload_form();
    } elseif ($_POST['import_step']==1) {
      return self::upload_mappings_form($options);
    } elseif ($_POST['import_step']==2) {
      return self::run_upload($options);
    }
  }

  /**
   * Returns the HTML for a simple file upload form.
   */
  private static function upload_form() {
    $reload = self::get_reload_link_parts();
    $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);
    $r = '<form action="'.$reloadpath.'" method="post" enctype="multipart/form-data">';
    $r .= '<label for="id">'.lang::get('Select file to upload').':</label>';
    $r .= '<input type="file" name="upload" id="upload"/>';
    $r .= '<input type="Submit" value="'.lang::get('Upload').'"></form>';
    return $r;
  }

  /**
   * Generates the import settings form. If none available, then outputs the upload mappings form.
   * @param array $options Options array passed to the import control.
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
      $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);
      $r = '<div class="page-notice ui-state-highlight ui-corner-all">'.lang::get('import_settings_instructions')."</div>\n".
          "<form method=\"post\" id=\"entry_form\" action=\"$reloadpath\" class=\"iform\">\n".
          "<fieldset><legend>Import Settings</legend>\n";
      $formArray = json_decode($response['output'], true);
      if (!is_array($formArray)) {
        if (class_exists('kohana')) {
          kohana::log('error', 'Problem occurred during upload. Sent request to get_import_settings and received invalid response.');
          kohana::log('error', "Request: $request");
          kohana::log('error', 'Response: '.print_r($response, true));
        }
        return 'Could not upload file. Please check that the indicia_svc_import module is enabled on the Warehouse.';
      }
      $formOptions = array(
        'form' => $formArray,
        'readAuth' => $options['auth']['read'],
        'nocache'=>true
      );
      if (isset($options['presetSettings'])) {
        // skip parts of the form we have a preset value for
        $formOptions['extraParams'] = $options['presetSettings'];
      }
      $form = self::build_params_form($formOptions, $hasVisibleContent);
      // If there are no settings required, skip to the next step.
      if (!$hasVisibleContent)
        return self::upload_mappings_form($options);
      $r .= $form;      
      if (isset($options['presetSettings'])) {
        // The presets might contain some extra values to apply to every row - must be output as hiddens
        $extraHiddens = array_diff_key($options['presetSettings'], $formArray);
        foreach ($extraHiddens as $hidden=>$value)
          $r .= "<input type=\"hidden\" name=\"$hidden\" value=\"$value\" />\n";
      }
      $r .= '<input type="hidden" name="import_step" value="1" />';
      $r .= '<input type="submit" name="submit" value="'.lang::get('Next').'" class="ui-corner-all ui-state-default button" />';
      // copy any $_POST data into the form, as this would mean preset values that are provided by the form which the uploader
      // was triggered from. E.g. if on a species checklist, this could be this checklists ID which the user does not need to pick.
      foreach ($_POST as $key=>$value)
        $r .= "<input type=\"hidden\" name=\"$key\" value=\"$value\" />\n";
      $r .= '</fieldset></form>';
      return $r;
    } else {
      // No settings form, so output the mappings form instead which is the next step.
      return self::upload_mappings_form($options);
    }
  }

  /**
   * Outputs the form for mapping columns to the import fields.
   * @param array $options Options array passed to the import control.
   */
  private static function upload_mappings_form($options) {
    if (!file_exists($_SESSION['uploaded_file']))
      return lang::get('upload_not_available');
    self::add_resource('jquery_ui');
    $filename=basename($_SESSION['uploaded_file']);
    // If the last step was skipped because the user did not have any settings to supply, presetSettings contains the presets.
    // Otherwise we'll use the settings form content which already in $_POST so will overwrite presetSettings.
    if (isset($options['presetSettings'])) {
      $settings = array_merge(
        $options['presetSettings'],
        $_POST
      );
    } else 
      $settings = $_POST;
    // only want defaults that actually have a value - others can be set on a per-row basis by mapping to a column
    foreach ($settings as $key => $value) {
      if (empty($value)) {
        unset($settings[$key]);
      }
    }
    // cache the mappings
    $metadata = array('settings' => json_encode($settings));
    $post = array_merge($options['auth']['write_tokens'], $metadata);
    $request = parent::$base_url."index.php/services/import/cache_upload_metadata?uploaded_csv=$filename";
    $response = self::http_post($request, $post);
    if (!isset($response['output']) || $response['output'] != 'OK')
      return "Could not upload the settings metadata. <br/>".print_r($response, true);

    $request = parent::$base_url."index.php/services/import/get_import_fields/".$options['model'];
    $request .= '?'.self::array_to_query_string($options['auth']['read']);
    // include survey and website information in the request if available, as this limits the availability of custom attributes
    if (!empty($settings['website_id']))
      $request .= '&website_id='.trim($settings['website_id']);
    if (!empty($settings['survey_id']))
      $request .= '&survey_id='.trim($settings['survey_id']);
    $response = self::http_post($request, array());
    $fields = json_decode($response['output'], true);
    if (!is_array($fields))
      return "curl request to $request failed. Response ".print_r($response, true);
    $request = str_replace('get_import_fields', 'get_required_fields', $request);
    $response = self::http_post($request);
    $responseIds = json_decode($response['output'], true);
    if (!is_array($responseIds))
      return "curl request to $request failed. Response ".print_r($response, true);
    $model_required_fields = self::expand_ids_to_fks($responseIds);
    if (!empty($settings))
      $preset_fields = self::expand_ids_to_fks(array_keys($settings));
    else
      $preset_fields=array();
    if (!empty($preset_fields))
      $unlinked_fields = array_diff_key($fields, array_combine($preset_fields, $preset_fields));
    else
      $unlinked_fields = $fields;
    // only use the required fields that are available for selection - the rest are handled somehow else
    $unlinked_required_fields = array_intersect($model_required_fields, array_keys($unlinked_fields));

    $handle = fopen($_SESSION['uploaded_file'], "r");
    $columns = fgetcsv($handle, 1000, ",");
    $reload = self::get_reload_link_parts();
    $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);

    self::clear_website_survey_fields($unlinked_fields, $settings);
    self::clear_website_survey_fields($unlinked_required_fields, $settings);
    $savedFieldMappings=array();
    //get the user's checked preference for the import page
    if (function_exists('hostsite_get_user_field')) {
      $json = hostsite_get_user_field('import_field_mappings');
      if ($json===false) {
        if (!hostsite_set_user_field('import_field_mappings', '[]'))
          self::$rememberingMappings=false;
      } else
        $savedFieldMappings=json_decode($json, true);
    } else
      // host does not support user profiles, so we can't remember mappings
      self::$rememberingMappings=false;
    //  if the user checked the Remember All checkbox, save it in a variable  
    if (isset($savedFieldMappings['RememberAll']))
      $checked['RememberAll']='checked';

    $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadpath\" class=\"iform\">\n".
      '<p>'.lang::get('column_mapping_instructions').'</p>'.
      '<div class="ui-helper-clearfix import-mappings-table"><table class="ui-widget ui-widget-content">'.
      '<thead class="ui-widget-header">'.
      "<tr><th>Column in CSV File</th><th>Maps to attribute</th>";
    if (self::$rememberingMappings) 
      $r .= "<th id='remember-all-header' name='remember-all-header'>".lang::get('Remember choice?').
         "<br/><input type='checkbox' name='RememberAll' id='RememberAll' value='1' title='Tick all boxes to remember every column mapping next time you import.' {$checked['RememberAll']} onclick='
           if (this.checked) {
             $(\".rememberField\").attr(\"checked\",\"checked\")
           } else {
             $(\".rememberField\").removeAttr(\"checked\")
           }'/></th>";
    $r .= '</tr></thead><tbody>';
    foreach ($columns as $column) {
      $colFieldName = preg_replace('/[^A-Za-z0-9]/', '_', $column);
      $r .= "<tr><td>$column</td><td><select name=\"$colFieldName\" id=\"$colFieldName\">";
      $r .= self::get_column_options($options['model'], $unlinked_fields, $column,' ', $savedFieldMappings);
      $r .=  "</select></td></tr>\n";
    }
    $r .= '</tbody>';
    $r .= '</table>';
    $r .= '<div id="required-instructions" class="import-mappings-instructions"><h2>'.lang::get('Tasks').'</h2><span>'.
      lang::get('The following database attributes must be matched to a column in your import file before you can continue').':</span><ul></ul><br/></div>';
    $r .= '<div id="duplicate-instructions" class="import-mappings-instructions"><span id="duplicate-instruct">'.
      lang::get('There are currently two or more drop-downs allocated to the same value.').'</span><ul></ul><br/></div></div>';
    $r .= '<input type="hidden" name="import_step" value="2" />';
    $r .= '<input type="submit" name="submit" id="submit" value="'.lang::get('Upload').'" class="ui-corner-all ui-state-default button" />';
    $r .= '</form>';
    
    self::$javascript .= "function detect_duplicate_fields() {
      var valueStore = [];
      var duplicateStore = [];
      var valueStoreIndex = 0;
      var duplicateStoreIndex = 0;
      $.each($('#entry_form select'), function(i, select) {
        if (valueStoreIndex==0) {
          valueStore[valueStoreIndex] = select.value;
          valueStoreIndex++;
        } else {
          for(i=0; i<valueStoreIndex; i++) {
            if (select.value==valueStore[i] && select.value != '<".lang::get('Not imported').">') {
              duplicateStore[duplicateStoreIndex] = select.value;
              duplicateStoreIndex++;
            }
             
          }
          valueStore[valueStoreIndex] = select.value;
          valueStoreIndex++;
        }      
      })
      if (duplicateStore.length==0) {
        DuplicateAllowsUpload = 1;
        $('#duplicate-instruct').css('display', 'none');
      } else {
        DuplicateAllowsUpload = 0;
        $('#duplicate-instruct').css('display', 'inline');
      }
    }\n";  
    self::$javascript .= "function update_required_fields() {
      // copy the list of required fields
      var fields = $.extend(true, {}, required_fields);
      $('#required-instructions li').remove();
      var sampleVagueDates = [];
      // strip out the ones we have already allocated
      $.each($('#entry_form select'), function(i, select) {
        delete fields[select.value];
        // special case for vague dates - if we have a complete sample vague date, then can strike out the sample:date required field
        if (select.value.substr(0,12)=='sample:date_') {
          sampleVagueDates.push(select.value);
        }
      });
      if (sampleVagueDates.length==3) {
        // got a full vague date, so can remove the required date field
        delete fields['sample:date'];
      }
      var output = '';
      $.each(fields, function(field, caption) {
        output += '<li>'+caption+'</li>';
      });
      if (output==='') {
        $('#required-instructions').css('display', 'none');
        RequiredAllowsUpload = 1;
      } else {
        $('#required-instructions').css('display', 'inline');
        RequiredAllowsUpload = 0;
      }
      if (RequiredAllowsUpload == 1 && DuplicateAllowsUpload == 1) {
        $('#submit').attr('disabled', false);
      } else {
        $('#submit').attr('disabled', true);
      }
      $('#required-instructions ul').html(output);
    }\n";
    self::$javascript .= "required_fields={};\n";
    foreach ($unlinked_required_fields as $field) {
      $caption = $unlinked_fields[$field];
      if (empty($caption)) {
        $tokens = explode(':', $field);
        $fieldname = $tokens[count($tokens)-1];
        $caption = lang::get(self::leadingCaps(preg_replace(array('/^fk_/', '/_id$/'), array('', ''), $fieldname)));
      }
      $caption = self::translate_field($field, $caption);
      self::$javascript .= "required_fields['$field']='$caption';\n";
    }
    self::$javascript .= "detect_duplicate_fields();\n";
    self::$javascript .= "update_required_fields();\n";
    self::$javascript .= "$('#entry_form select').change(function() {detect_duplicate_fields(); update_required_fields();});\n";
    return $r;
  }

  /**
   * When an array (e.g. $_POST containing preset import values) has values with actual ids in it, we need to
   * convert these to fk_* so we can compare the array of preset data with other arrays of expected data.
   * @param array $arr Array of IDs.
   */
  private static function expand_ids_to_fks($arr) {
    $ids = preg_grep('/_id$/', $arr);
    foreach ($ids as &$id) {
      $id = str_replace('_id', '', $id);
      if (strpos($id, ':')===false)
        $id = "fk_$id";
      else
        $id = str_replace(':', ':fk_', $id);
    }
    return array_merge($arr, $ids);
  }

  /**
   * Takes an array of fields, and removes the website ID or survey ID fields within the arrays if
   * the website and/or survey id are set in the $settings data.
   * @param array $array Array of fields.
   * @param array $settings Global settings which apply to every row, which may include the website_id 
   * and survey_id.
   */
  private static function clear_website_survey_fields(&$array, $settings) {
    foreach ($array as $idx => $field) {
      if (!empty($settings['website_id']) && (preg_match('/:fk_website$/', $idx) || preg_match('/:fk_website$/', $field))) {
        unset($array[$idx]);
      }
      if (!empty($settings['survey_id']) && (preg_match('/:fk_survey$/', $idx) || preg_match('/:fk_survey$/', $field))) {
        unset($array[$idx]);
      }
    }
  }

  /**
   * Display the page which outputs the upload progress bar. Adds JavaScript to the page which performs the chunked upload.
   * @param array $options Array of options passed to the import control.
   */
  private static function run_upload($options) {
    self::add_resource('jquery_ui');
    if (!file_exists($_SESSION['uploaded_file']))
      return lang::get('upload_not_available');
    $filename=basename($_SESSION['uploaded_file']);
    // move file to server
    $r = self::send_file_to_warehouse($filename, false, $options['auth']['write_tokens'], 'import/upload_csv');
    if ($r===true) {
      $reload = self::get_reload_link_parts();
      $reload['params']['uploaded_csv']=$filename;
      $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);

      // initiate local javascript to do the upload with a progress feedback
      $r = '
  <div id="progress" class="ui-widget ui-widget-content ui-corner-all">
  <div id="progress-bar" style="width: 400"></div>
  <div id="progress-text">Preparing to upload.</div>
  </div>
  ';
      $metadata = array('mappings' => json_encode($_POST));
      // cache the mappings
      if (function_exists('hostsite_set_user_field')) {
        foreach ($_POST as $column => $setting) {
          $userSettings[str_replace("_", " ", $column)] = $setting;
        }
        //if the user has not selected the Remember checkbox for a column setting and the Remember All checkbox is not selected
        //then forget the user's saved setting for that column.
        foreach ($userSettings as $column => $setting) {
          if (!isset($userSettings[$column.' '.'Remember']) && $column!='RememberAll')
            unset($userSettings[$column]);
        }
        hostsite_set_user_field("import_field_mappings", json_encode($userSettings));
      }
      $post = array_merge($options['auth']['write_tokens'], $metadata);
      // store the warehouse user ID if we know it.
      if (function_exists('hostsite_get_user_field')) 
        $post['user_id'] = hostsite_get_user_field('indicia_user_id');
      $request = parent::$base_url."index.php/services/import/cache_upload_metadata?uploaded_csv=$filename";
      $response = self::http_post($request, $post);
      if (!isset($response['output']) || $response['output'] != 'OK')
        return "Could not upload the mappings metadata. <br/>".print_r($response, true);
      if (!empty(parent::$warehouse_proxy))
        $warehouseUrl = parent::$warehouse_proxy;
      else
        $warehouseUrl = parent::$base_url;
      self::$onload_javascript .= "
    /**
    * Upload a single chunk of a file, by doing an AJAX get. If there is more, then on receiving the response upload the
    * next chunk.
    */
    uploadChunk = function() {
      var limit=50;
      $.ajax({
        url: '".$warehouseUrl."index.php/services/import/upload?offset='+total+'&limit='+limit+'&filepos='+filepos+'&uploaded_csv=$filename&model=".$options['model']."',
        dataType: 'jsonp',
        success: function(response) {
          total = total + response.uploaded;
          filepos = response.filepos;
          jQuery('#progress-text').html(total + ' records uploaded.');
          $('#progress-bar').progressbar ('option', 'value', response.progress);
          if (response.uploaded>=limit) {
            uploadChunk();
          } else {
            jQuery('#progress-text').html('Upload complete.');
            window.location = '$reloadpath&total='+total;
          }
        }
      });
    };

    var total=0, filepos=0;
    jQuery('#progress-bar').progressbar ({value: 0});
    uploadChunk();
    ";
    }
    return $r;
  }

  /**
   * Displays the upload result page.
   * @param array $options Array of options passed to the import control.
   */
  private static function upload_result($options) {
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
      $r = 'An error occurred during the upload.<br/>'.print_r($response, true);
    }
    $reload = self::get_reload_link_parts();
    unset($reload['params']['total']);
    unset($reload['params']['uploaded_csv']);
    $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);
    $r = "<p>$r</p><p>".lang::get('Would you like to ')."<a href=\"$reloadpath\">".lang::get('import another file?')."</a></p>";
    return $r;
  }


 /**
  * Returns a list of columns as an list of <options> for inclusion in an HTML drop down,
  * loading the columns from a model that are available to import data into
  * (excluding the id and metadata). Triggers the handling of remembered checkboxes and the
  * associated labelling. 
  * This method also attempts to automatically find a match for the columns based on a number of rules
  * and gives the user the chance to save their settings for use the next time they do an import.
  * @param string $model Name of the model
  * @param array  $fields List of the available possible import columns
  * @param string $column The name of the column from the CSV file currently being worked on.
  * @param string $selected The name of the initially selected field if there is one.
  * @param array $savedFieldMappings An array containing the user's custom saved settings for the page.
  */
  private static function get_column_options($model, $fields, $column, $selected='', $savedFieldMappings) {
    $skipped = array('id', 'created_by_id', 'created_on', 'updated_by_id', 'updated_on',
      'fk_created_by', 'fk_updated_by', 'fk_meaning', 'fk_taxon_meaning', 'deleted', 'image_path');
    //strip the column of spaces for use in html ids
    $idColumn = str_replace(" ", "", $column);
    $r = '';
    $heading='';
    $labelListIndex = 0;
    $itWasSaved[$column] = 0;

    foreach ($fields as $field=>$caption) {
      if (strpos($field,":"))
        list($prefix,$fieldname)=explode(':',$field);
      else {
        $prefix=$model;
        $fieldname=$field;
      }
      // Skip the metadata fields
      if (!in_array($fieldname, $skipped)) {
        // make a clean looking caption
        $caption = self::make_clean_caption($caption, $prefix, $fieldname, $model);
       /*
        * The following creates an array called $labelList which is a list of all captions
        * in the drop-down lists. Using array_count_values the array values are calculated as the number of times
        * each caption occurs for use in duplicate detection.
        * $labelListHeading is an array where the keys are each column we work with concatenated to the heading of the caption we
        * are currently working on. 
        */
        $strippedScreenCaption = str_replace(" (lookup existing record)","",self::translate_field($field, $caption));
        $labelList[$labelListIndex] = strtolower($strippedScreenCaption);
        $labelListIndex++;
        if (isset ($labelListHeading[$column.$prefix]))
          $labelListHeading[$column.$prefix] = $labelListHeading[$column.$prefix].':'.strtolower($strippedScreenCaption);
        else
          $labelListHeading[$column.$prefix] = strtolower($strippedScreenCaption); 
      }
    } 
    $labelList = array_count_values($labelList);
    $multiMatch=array();
    foreach ($fields as $field=>$caption) {
      if (strpos($field,":"))
        list($prefix,$fieldname)=explode(':',$field);
      else {
        $prefix=$model;
        $fieldname=$field;
      }
      // make a clean looking default caption. This could be provided by the $fields array, or we have to construct it.
      $defaultCaption = self::make_clean_caption($caption, $prefix, $fieldname, $model);
      // Allow the default caption to be translated or overridden by language files.
      $translatedCaption=self::translate_field($field, $defaultCaption);
      //need a version of the caption without "Lookup existing record" as we ignore that for matching.
      $strippedScreenCaption = str_replace(" (lookup existing record)","",$translatedCaption);
      $fieldname=str_replace(array('fk_','_id'), array('',''), $fieldname);
      unset($option);     
      // Skip the metadata fields
      if (!in_array($fieldname, $skipped)) {
        $selected = false;             
        //get user's saved settings, last parameter is 2 as this forces the system to explode into a maximum of two segments.
        //This means only the first occurrence for the needle is exploded which is desirable in the situation as the field caption
        //contains colons in some situations.
        if (isset($savedFieldMappings[$column])) {
          $savedData = explode(':',$savedFieldMappings[$column],2);
          $savedSectionHeading = $savedData[0];
          $savedMainCaption = $savedData[1];
        } else {
          $savedSectionHeading = '';
          $savedMainCaption = '';
        }
        //Detect if the user has saved a column setting that is not 'not imported' then call the method that handles the auto-match rules.
        if (strcasecmp($prefix,$savedSectionHeading)===0 && strcasecmp($field,$savedSectionHeading.':'.$savedMainCaption)===0) {
          $selected=true;
          $itWasSaved[$column] = 1;
          //even though we have already detected the user has a saved setting, we need to call the auto-detect rules as if it gives the same result then the system acts as if it wasn't saved.
          $saveDetectRulesResult = self::auto_detection_rules($column, $defaultCaption, $strippedScreenCaption, $prefix, $labelList, $itWasSaved[$column], true);
          $itWasSaved[$column] = $saveDetectRulesResult['itWasSaved'];
        } else {
          //only use the auto field selection rules to select the drop-down if there isn't a saved option
          if (!isset($savedFieldMappings[$column])) {
            $nonSaveDetectRulesResult = self::auto_detection_rules($column, $defaultCaption, $strippedScreenCaption, $prefix, $labelList, $itWasSaved[$column], false);
            $selected = $nonSaveDetectRulesResult['selected'];
          }
        }
        //As a last resort. If we have a match and find that there is more than one caption with this match, then flag a multiMatch to deal with it later
        if (strcasecmp($strippedScreenCaption, $column)==0 && $labelList[strtolower($strippedScreenCaption)] > 1) {
          $multiMatch[] = $column;
          $optionID = $idColumn.'Duplicate';  
        } else 
          $optionID = $idColumn.'Normal';
        $option = self::model_field_option($field, $defaultCaption, $selected, $optionID);
      }
      
      // if we have got an option for this field, add to the list
      if (isset($option)) {
        // first check if we need a new heading
        if ($prefix!=$heading) {
          $heading = $prefix;
          if (isset($labelListHeading[$column.$heading])) {
            $subOptionList = explode(':', $labelListHeading[$column.$heading]);
            $foundDuplicate=false;
            foreach ($subOptionList as $subOption) {
              if ($labelList[$subOption] > 1) {
                $theID = $idColumn.'Duplicate';
                $foundDuplicate = true;
              }
              if ($labelList[$subOption] == 1 and $foundDuplicate == false)
                $theID = $idColumn.'Normal';
            }
          }
          if (!empty($r)) 
            $r .= '</optgroup>';
            $r .= "<optgroup class=\"$theID\" label=\"";
            $r .= self::leadingCaps(lang::get($heading)).'">';
        }
        $r .= $option;
      }
    }  
    $r = self::items_to_draw_once_per_import_column($r, $column, $itWasSaved, $savedFieldMappings, $multiMatch);
    return $r;
  }

  
 /**
  * This method is used by the mode_field_options method.
  * It has two modes:
  * When $saveDetectedMode is false, the method uses several rules in an attempt to automatically determine
  * a value for one of the csv column drop-downs on the import page.
  * When $saveDetectedMode is true, the method uses the same rules to see if the system would have retrieved the
  * same drop-down value as the one that was saved by the user. If this is the case, the system acts
  * as if the value had been automatically determined rather than saved.
  * 
  * @param string $column The CSV column we are currently working with from the import file.
  * @param string $defaultCaption The default, untranslated caption.
  * @param string $strippedScreenCaption A version of an item in the column selection drop-down that has 'lookup existing record'stripped
  * @param string $prefix Caption prefix
  * each item having a list of regexes to match against
  * @param array $labelList A list of captions and the number of times they occur.
  * @param integer $itWasSaved This is set to 1 if the system detects that the user has a custom saved preference for a csv column drop-down.
  * @param boolean $saveDetectedMode Determines the mode the method is running in
  * @return array Depending on the mode, we either are interested in the $selected value or the $itWasSaved value.
  */ 
  private static function auto_detection_rules($column, $defaultCaption, $strippedScreenCaption, $prefix, $labelList, $itWasSaved, $saveDetectedMode) {
    /*
    * This is an array of drop-down options with a list of possible column headings the system will use to match against that option.
    * The key is in the format heading:option, all lowercase e.g. occurrence:comment 
    * The value is an array of regexes that the system will automatically match against.
    */
    $alternatives = array(
      "sample:entered sref"=>array("/(sample)?(spatial|grid)ref(erence)?/"),
      "occurrence:taxa taxon list (lookup existing record)"=>array("/(species(latin)?|taxon(latin)?|latin)(name)?/"),
      "sample:location name"=>array("/(site|location)(name)?/"),
      "smpAttr:eunis habitat (lookup existing record)" => array("/(habitat|eunishabitat)/")
    );
    $selected=false;
    //handle situation where there is a unique exact match
    if (strcasecmp($strippedScreenCaption, $column)==0 && $labelList[strtolower($strippedScreenCaption)] == 1) {
      if ($saveDetectedMode) 
        $itWasSaved = 0; 
      else 
        $selected=true;
    } else {
      //handle the situation where a there isn' a unqiue match, but there is if you take the heading into account also
      if (strcasecmp($prefix.' '.$strippedScreenCaption, $column)==0) {
        if ($saveDetectedMode) 
          $itWasSaved = 0; 
        else 
          $selected=true;
      }
      //handle the situation where there is a match with one of the items in the alternatives array.
      if (isset($alternatives[$prefix.':'.strtolower($defaultCaption)])) {
        foreach ($alternatives[$prefix.':'.strtolower($defaultCaption)] as $regexp) {
          if (preg_match($regexp, strtolower(str_replace(' ', '', $column)))) {
            if ($saveDetectedMode) 
              $itWasSaved = 0; 
            else 
              $selected=true;
          }
        } 
      }
    }
    return array (
      'itWasSaved'=>$itWasSaved,
      'selected'=>$selected
    );
  }
  
  
 /**
  * Used by the get_column_options to draw the items that appear once for each of the import columns on the import page.
  * These are the checkboxes, the warning the drop-down setting was saved and also the non-unique match warning
  * @param string $r The HTML to be returned.
  * @param string $column Column from the import CSV file we are currently working with
  * @param integer $itWasSaved This is 1 if a setting is saved for the column and the column would not have been automatically calculated as that value anyway.
  * @param array $savedFieldMappings An array containing the user' preferences for the import page.
  * @param integer $multiMatch Array of columns where there are multiple matches for the column and this cannot be resolved.
  * @return string HTMl string 
  */
  private static function items_to_draw_once_per_import_column($r, $column, $itWasSaved, $savedFieldMappings, $multiMatch) {
    $checked[$column] = ($itWasSaved[$column] == 1 || isset($savedFieldMappings['RememberAll'])) ? 'checked' : '';
    $optionID = str_replace(" ", "", $column).'Normal';
    $r = "<option value=\"&lt;Not imported&gt;\">&lt;".lang::get('Not imported').'&gt;</option>'.$r.'</optgroup>';
    if (self::$rememberingMappings) 
      $r .= "<td class=\"centre\"><input type='checkbox' name='$column.Remember' class='rememberField'id='$column.Remember' value='1' {$checked[$column]} onclick='
      if (!this.checked) {
        $(\"#RememberAll\").removeAttr(\"checked\");
      }' 
      title='If checked, your selection for this particular column will be saved and automatically selected during future imports. ".
          "Any alterations you make to this default selection in the future will also be remembered until you deselect the checkbox.'></td>";

    if ($itWasSaved[$column] == 1) {
      $r .= "<tr><td></td><td class=\"note\">The above mapping is a remembered previous choice.</td></tr>";
    }
    //If we find there is a match we cannot resolve uniquely, then give the user a checkbox to reduce the drop-down to suggestions only.
    //Do this by hiding items whose class has "Normal" at the end as these are the items that do not contain the duplicates.
    if (in_array($column, $multiMatch) && $itWasSaved[$column] == 0) {
      $r .= "<tr><td></td><td class=\"note\">There are multiple possible matches for ";
      $r .= "\"$column\"";
      $r .=  "<br/><form><input type='checkbox' id='$column.OnlyShowMatches' value='1' onclick='
       if (this.checked) {
         $(\".$optionID\").hide();
       } else {
         $(\".$optionID\").show();
      }'
      > Only show likely matches in drop-down<br></form></td></tr>";
    }
    return $r;
  }
  
  
 /**
  * Used by the get_column_options method to add "lookup existing record" to the appropriate captions
  * in the drop-downs on the import page.
  * @param type $caption The drop-down item currently being worked on
  * @param type $prefix Caption prefix
  * @param type $fieldname The database field that the caption relates to.
  * @param type $model Name of the model
  * @return string $caption A caption for the column drop-down on the import page.
  */
  private static function make_clean_caption($caption, $prefix, $fieldname, $model) {
    if (empty($caption)) {
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
    } else {
      if (substr($fieldname,0,3)=='fk_') 
        $caption .=' ('.lang::get('lookup existing record').')'; 
      }
    return $caption;
  }
  
  
  /**
   * Method to upload the file in the $_FILES array, or return the existing file if already uploaded.
   * @param array $options Options array passed to the import control.
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
      $interim_path = dirname(__FILE__).'/'.$interim_image_folder;
      if (move_uploaded_file($file['tmp_name'], "$interim_path$destination")) {
        return "$interim_path$destination";
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
   * @param string $field Name of the field being output.
   * @param string $caption Caption of the field being output.
   * @param boolean $selected Set to true if outputing the currently selected option.
   * @param string $optionID Id of the current option.
   */
  private static function model_field_option($field, $caption, $selected, $optionID) {
    $selHtml = ($selected) ? ' selected="selected"' : '';
    $caption = self::translate_field($field, $caption);
    $r =  '<option class=';
    $r .= $optionID;
    $r .= ' value="'.htmlspecialchars($field)."\"$selHtml>".htmlspecialchars($caption).'</option>';
    return $r;
  }

  /**
   * Provides optional translation of field captions by looking for a translation code dd:model:fieldname. If not
   * found returns the original caption.
   * @param string $field Name of the field being output.
   * @param string $caption Untranslated caption of the field being output.
   * @return string Translated caption.
   */
  private static function translate_field($field, $caption) {
    // look in the translation settings to see if this column name needs overriding
    $trans = lang::get("dd:$field");
    // Only update the caption if this actually did anything
    if ($trans != "dd:$field" ) {
      return $trans;
    } else {
      return $caption;
    }
  }

}

?>