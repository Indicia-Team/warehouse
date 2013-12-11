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
 * @package    Client
 * @subpackage PrebuiltForms
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Prebuilt Indicia data entry form.
 * NB has Drupal specific code. Relies on presence of IForm loctools and IForm Proxy.
 *
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('includes/dynamic.php');
require_once('includes/shorewatch_grid_reference_processor.php');
require_once('includes/dynamic.php');

class iform_dynamic_shorewatch_importer extends iform_dynamic {

  /**
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_dynamic_shorewatch_importer_definition() {
    return array(
      'title'=>'Shorewatch importer',
      'category' => 'Utilities',
      'description'=>'A form used for importing Shorewatch samples and occurrences.' .
        'Shorewatch has a customised sample/occurrence structure so this form is not suitable for use with other projects.'
    );
  }

  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $retVal = array_merge(
      parent::get_parameters(),
      array(
        //As the attribute ids will vary between different databases, we need to manually
        //map the attribute ids to variables in the code
        array(
          'name'=>'observer_name',
          'caption'=>'Observer Name',
          'description'=>'Indicia ID for the sample attribute that is the name of the observer.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'observer_email',
          'caption'=>'Observer Email',
          'description'=>'Indicia ID for the sample attribute that is the email of the observer.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'observer_phone_number',
          'caption'=>'Observer Phone Number',
          'description'=>'Indicia ID for the sample attribute that is the phone number of the observer.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'start_time',
          'caption'=>'Start time',
          'description'=>'Indicia ID for the sample attribute that records the start time of the watch.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'end_time',
          'caption'=>'End time',
          'description'=>'Indicia ID for the sample attribute that records the end time of the watch.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'cetaceans_seen',
          'caption'=>'Cetaceans seen?',
          'description'=>'Indicia ID for the sample attribute that records whether Cetaceans have been seen.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'cetaceans_seen_yes',
          'caption'=>'Cetaceans Seen Yes Answer',
          'description'=>'Indicia ID for the termlists_term that stores the Yes answer for Cetaceans Seen?.',
          'type'=>'string',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'sea_state',
          'caption'=>'Sea state?',
          'description'=>'Indicia ID for the sample attribute that records sea state.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'visibility',
          'caption'=>'Visibility?',
          'description'=>'Indicia ID for the sample attribute that records visibility.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'non_cetacean_marine_animals_seen',
          'caption'=>'Non cetacean marine animals seen?',
          'description'=>'Indicia ID for the sample attribute that records whether non-cetacean marine animals have been seen.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'feeding_birds_seen',
          'caption'=>'Feeding birds seen?',
          'description'=>'Indicia ID for the sample attribute that records whether feeding birds have been seen.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'number_of_people_spoken_to_during_watch',
          'caption'=>'Number of people spoken to during watch?',
          'description'=>'Indicia ID for the sample attribute that records the number of people spoken to during the watch.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'wdcs_newsletter',
          'caption'=>'WDCS newsletter opt-in',
          'description'=>'Indicia ID for the sample attribute that records whether a guest has chosen to receive
            the WDCS newsletter.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'bearing_to_sighting',
          'caption'=>'Bearing to sighting',
          'description'=>'Indicia ID for the occurrence attribute that stores the bearing to the sighting.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Occurrence Attributes'
        ),
        array(
          'name'=>'reticules',
          'caption'=>'Reticules',
          'description'=>'Indicia ID for the occurrence attribute that holds the number of reticules.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Occurrence Attributes'
        ),
        array(
          'name'=>'reticules_from',
          'caption'=>'Reticules from',
          'description'=>'Indicia ID for the occurrence attribute that stores whether the Reticules value
            is from the land or sky.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Occurrence Attributes'
        ),
        array(
          'name'=>'distance_estimate',
          'caption'=>'Distance Estimate',
          'description'=>'Indicia ID for the occurrence attribute that stores the distance estimate to the sighting.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Occurrence Attributes'
        ),
        array(
          'name'=>'adults',
          'caption'=>'Adults',
          'description'=>'Indicia ID for the occurrence attribute that stores the number of adults associated with the sighting.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Occurrence Attributes'
        ),
        array(
          'name'=>'calves',
          'caption'=>'Calves',
          'description'=>'Indicia ID for the occurrence attribute that stores the number of Calves associated with the sighting.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Occurrence Attributes'
        ),
        array(
          'name'=>'activity',
          'caption'=>'Activity',
          'description'=>'Indicia ID for the occurrence attribute that stores whether a sighting is travelling or staying.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Occurrence Attributes'
        ),
        array(
          'name'=>'behaviour',
          'caption'=>'Behaviour',
          'description'=>'Indicia ID for the occurrence attribute that stores whether a sighting is calm or active.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Occurrence Attributes'
        ),
        array(
          'name'=>'platform_height',
          'caption'=>'Platform Height',
          'description'=>'Indicia ID for the location attribute that stores the platform height.',
          'type'=>'select',
          'table'=>'location_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Location Attributes'
        ),
        array(
          'name'=>'effort',
          'caption'=>'Effort',
          'description'=>'Indicia ID for the termlists_term that stores the effort sample method id.',
          'type'=>'select',
          'table'=>'termlists_term',
          'valueField'=>'id',
          'captionField'=>'term',
          'group'=>'Shorewatch Sample Methods'
        ),
        array(
          'name'=>'reticule_sighting',
          'caption'=>'Reticule Sighting',
          'description'=>'Indicia ID for the termlists_term that stores the reticule sighting sample method id.',
          'type'=>'select',
          'table'=>'termlists_term',
          'valueField'=>'id',
          'captionField'=>'term',
          'group'=>'Shorewatch Sample Methods'
        ),
        array(
          'name'=>'efforts_survey_id',
          'caption'=>'Efforts And Online Recording Survey ID',
          'description'=>'Indicia ID for the efforts and online recording survey (not adhoc).',
          'type'=>'string',
          'group'=>'Survey Ids'
        ),
        array(
          'name'=>'efforts_and_online_recording_page_path',
          'caption'=>'Efforts And Online Recording Page Path',
          'description'=>'Path to the Efforts And Online Recording page.',
          'type'=>'string',
          'group'=>'Recording Page Paths'
        ),  
        array(
          'name'=>'ahoc_online_recording_page_path',
          'caption'=>'Adhoc Online Recording Page Path',
          'description'=>'Path to the Adhoc Online Recording page.',
          'type'=>'string',
          'group'=>'Recording Page Paths'
        ),  
        array(
          'name'=>'keep_going_after_error',
          'caption'=>'Continue import after issues detected?',
          'description'=>'The import is processed on a line-by-line basis. Does the import stop or try importing the rest of the data if problems with the data are detected? '.
              'Note that data may have already been entered into the database before the issue occurred. '.
              'Leaving this option on may result in inconsistent data being entered into the database depending on when the problem occurred during processing. '.
              'Note also that errors that occur on the Warehouse rather than in the importer\'s submission builder during processing are unaffected by this option.',
          'type'=>'boolean',
          'default'=>false,
          'group'=>'Import Mode'
        ),
        array(
          'name'=>'presetSettings',
          'caption'=>'Preset Settings',
          'description'=>'Provide a list of predetermined settings which the user does not need to specify, one on each line in the form name=value. '.
              'The preset settings available are those which are available for input on the first page of the import wizard, depending on the table you '.
              'are inputting data for. You can use the following replacement tokens in the values: {user_id}, {username}, {email} or {profile_*} (i.e. any '.
              'field in the user profile data).',
          'type'=>'textarea',
          'required'=>false
        )
      )
    );
    return $retVal;
  }

  
  
    /**
   * Return the Indicia form code
   * @param array $args Input parameters.
   * @param array $node Drupal node object
   * @return HTML string
   */
  public static function get_form($args, $node) {
    iform_load_helpers(array('import_helper','report_helper'));
    $args['model']='occurrence';
    $auth = import_helper::get_read_write_auth($args['website_id'], $args['password']);
    $model = $args['model'];
    if (isset($args['presetSettings'])) {
      $presets = get_options_array_with_user_data($args['presetSettings']);
      $presets = array_merge(array('website_id'=>$args['website_id'], 'password'=>$args['password']), $presets);
    } else {
      $presets = array('website_id'=>$args['website_id'], 'password'=>$args['password']);
    }
    $r = self::importer(array(
      'model' => $model,
      'auth' => $auth,
      'presetSettings' => $presets
    ),$args);
    return $r;
  }
  
  /**
   * @var boolean Flag set to true if the host system is capable of storing our user's remembered import mappings
   * for future imports. This is only supported in the standard imported and is currently unsupported for the Shorewatch Importer but may be supported in future versions.
   */
  private static $rememberingMappings=true;

  /**
   * Outputs an import wizard. 
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>model</b><br/>
   * Required. The name of the model data is being imported into.</li>
   * <li><b>existing_file</b><br/>
   * Optional. The full path on the server to an already uploaded file to import. Note that this is taken from the standard import helper and this option is untested in the Shorewatch Importer.</li>
   * <li><b>auth</b><br/>
   * Read and write authorisation tokens.</li>
   * <li><b>presetSettings</b><br/>
   * Optional associative array of any preset values for the import settings. Any settings which have a presetSetting specified
   * will be ommitted from the settings form. Note that this is taken from the standard import helper and this option is untested in the Shorewatch Importer.</li>
   * </ul>
   */
  public static function importer($options,$args) {
    if (!isset($_POST['import_step'])) {
      if (count($_FILES)==1)
        return self::import_settings_form($options);
      else
        return self::upload_form();
    } elseif ($_POST['import_step']==1) {
      return self::upload_mappings_form($options);
    } elseif ($_POST['import_step']==2) {
      if (!file_exists($_SESSION['uploaded_file']))
        return lang::get('upload_not_available');
      $filename=basename($_SESSION['uploaded_file']);
      $r = self::upload_shorewatch_subsamples_and_data($filename, false, $options['auth']['write_tokens'], 'import/upload_csv',$args);
    }
  }

  /**
   * Returns the HTML for a simple file upload form.
   */
  private static function upload_form() {
    $reload = data_entry_helper::get_reload_link_parts();
    $reloadpath = $reload['path'] . '?' . data_entry_helper::array_to_query_string($reload['params']);
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
    $request = data_entry_helper::$base_url."index.php/services/import/get_import_settings/".$options['model'];
    $request .= '?'.data_entry_helper::array_to_query_string($options['auth']['read']);
    $response = data_entry_helper::http_post($request, array());
    if (!empty($response['output'])) {
      // get the path back to the same page
      $reload = data_entry_helper::get_reload_link_parts();
      $reloadpath = $reload['path'] . '?' . data_entry_helper::array_to_query_string($reload['params']);
      $r = '<div class="page-notice ui-state-highlight ui-corner-all">'.lang::get('import_settings_instructions')."</div>\n".
          "<form method=\"post\" id=\"entry_form\" action=\"$reloadpath\" class=\"iform\">\n".
          "<fieldset><legend>Import Settings</legend>\n";
      $formArray = json_decode($response['output'], true);
      //Unlike standard importers, the shorewatch importer must use the 4326 spatial reference system, so don't
      //display the option to the user.
      unset($formArray['sample:entered_sref_system']);
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
      $form = data_entry_helper::build_params_form($formOptions, $hasVisibleContent);
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
    //The Shorewatch importer only supports 4326 as this is required for the occurrence sample grid reference
    //calculations to work. This can be hardcoded.
    $_POST['sample:entered_sref_system']=4326;
    $_SESSION['importSettingsToCarryForward'] = $_POST;
    if (!file_exists($_SESSION['uploaded_file']))
      return lang::get('upload_not_available');
    data_entry_helper::add_resource('jquery_ui');
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
    //The Shorewatch importer only supports 4326 as this is required for the occurrence sample grid reference
    //calculations to work. This can be hardcoded.
    $settings['sample:entered_sref_system']=4326;
    // cache the mappings
    $metadata = array('settings' => json_encode($settings));
    $post = array_merge($options['auth']['write_tokens'], $metadata);
    $request = data_entry_helper::$base_url."index.php/services/import/cache_upload_metadata?uploaded_csv=$filename";
    $response = data_entry_helper::http_post($request, $post);
    if (!isset($response['output']) || $response['output'] != 'OK')
      return "Could not upload the settings metadata. <br/>".print_r($response, true);

    $request = data_entry_helper::$base_url."index.php/services/import/get_import_fields/".$options['model'];
    $request .= '?'.data_entry_helper::array_to_query_string($options['auth']['read']);
    // include survey and website information in the request if available, as this limits the availability of custom attributes
    if (!empty($settings['website_id']))
      $request .= '&website_id='.trim($settings['website_id']);
    if (!empty($settings['survey_id']))
      $request .= '&survey_id='.trim($settings['survey_id']);
    $response = data_entry_helper::http_post($request, array());
    $fields = json_decode($response['output'], true);
    if (!is_array($fields))
      return "curl request to $request failed. Response ".print_r($response, true);
    $request = str_replace('get_import_fields', 'get_required_fields', $request);
    $response = data_entry_helper::http_post($request);
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
    ini_set('auto_detect_line_endings',1);
    $handle = fopen($_SESSION['uploaded_file'], "r");
    $columns = fgetcsv($handle, 1000, ",");
    $reload = data_entry_helper::get_reload_link_parts();
    $reloadpath = $reload['path'] . '?' . data_entry_helper::array_to_query_string($reload['params']);

    self::clear_website_survey_fields($unlinked_fields, $settings);
    self::clear_website_survey_fields($unlinked_required_fields, $settings);
    $savedFieldMappings=array();
    // Note the Shorewatch importer doesn't currently support remembered fields, so set this to false (we are reusing a lot of the import_helper code, so leave the variable in the code as it already has proven reliability).
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
    
    data_entry_helper::$javascript .= "function detect_duplicate_fields() {
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
    data_entry_helper::$javascript .= "function update_required_fields() {
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
    data_entry_helper::$javascript .= "required_fields={};\n";
    foreach ($unlinked_required_fields as $field) {
      $caption = $unlinked_fields[$field];
      if (empty($caption)) {
        $tokens = explode(':', $field);
        $fieldname = $tokens[count($tokens)-1];
        $caption = lang::get(self::leadingCaps(preg_replace(array('/^fk_/', '/_id$/'), array('', ''), $fieldname)));
      }
      $caption = self::translate_field($field, $caption);
      data_entry_helper::$javascript .= "required_fields['$field']='$caption';\n";
    }
    data_entry_helper::$javascript .= "detect_duplicate_fields();\n";
    data_entry_helper::$javascript .= "update_required_fields();\n";
    data_entry_helper::$javascript .= "$('#entry_form select').change(function() {detect_duplicate_fields(); update_required_fields();});\n";
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
      $interim_image_folder = isset(data_entry_helper::$interim_image_folder) ? data_entry_helper::$interim_image_folder : 'upload/';
      //The dynamic_shorewatch_importer is similar to the import_helper code, the Uploads folder is in the same folder as import_helper.php.
      //dirname(__FILE__) can be used to get the directory of the current php code.
      //However the dynamic_shorewatch_importer is a prebuilt form, so chop prebuilt form off the end of the path
      //to get to the same directory as the Uploads folder (which is in the same directory as import_heper.php).
      $uploadsPath = preg_replace('/prebuilt_forms$/', '', dirname(__FILE__));
      $interim_path = $uploadsPath.'/'.$interim_image_folder;
      
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
  
  /*
   * Read the import file and then create a $values array which is the same as if the data had been entered on a dynamic_shorewatch form.
   * We then submit to the same submission building code as dynamic_shorewatch does as each parent sample and set of sub-samples/occurrences is read from the import file.
   */
  protected static function upload_shorewatch_subsamples_and_data($path, $persist_auth=false, $writeAuth, $service='data/handle_media', $args) {
    //Assume to start with that there aren't any import errors
    $importErrorDetected=false;
    $interim_image_folder = isset(data_entry_helper::$interim_image_folder) ? data_entry_helper::$interim_image_folder : 'upload/';
    //The dynamic_shorewatch_importer is similar to the import_helper code, the Uploads folder is in the same folder as import_helper.php.
    //dirname(__FILE__) can be used to get the directory of the current php code.
    //However the dynamic_shorewatch_importer is a prebuilt form, so chop prebuilt form off the end of the path
    //to get to the same directory as the Uploads folder (which is in the same directory as import_heper.php).
    $uploadsPath = preg_replace('/prebuilt_forms$/', '', dirname(__FILE__));
    $interim_path = $uploadsPath.'/'.$interim_image_folder;
    $csvTempFile = $interim_path.$path;
    if (!file_exists($csvTempFile))
      return "The file $interim_path$path does not exist and cannot be uploaded to the Warehouse.";
    ini_set('auto_detect_line_endings',1);
    $handle = fopen ($csvTempFile, "r");      
    if (file_exists($csvTempFile))
    {
      // Following helps for files from Macs
      // create the file pointer
      $handle = fopen ($csvTempFile, "r");      
      $count=0;
      $limit = (isset($_GET['limit']) ? $_GET['limit'] : false);
      $filepos = (isset($_GET['filepos']) ? $_GET['filepos'] : 0);
      $offset = (isset($_GET['offset']) ? $_GET['offset'] : 0);
      if ($filepos==0) {
        // first row, so skip the header
        fseek($handle, 0);
        fgetcsv($handle, 1000, ",");
      } else
        // skip rows to allow for the last file position
        fseek($handle, $filepos);
      $values = array();
      //We need to build a $values array to subit, there are certain basic elements of this array such as website_id, survey_id that we can set straightaway.
      self::get_initial_values_array_values($values,$args);
      //In non-adhoc mode the code checks the state of the Cetacean's seen box to see if existing occurrences need removing.
      //However in the importer this box is not physically present, neither do we ever need to delete existing data. So we can just set 
      //adhoc mode to 1 always
      $args['adhocMode']=1;
      $occurrenceNumber=0;
      //Cycle through each line in the CSV file. The data is held in $data.
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE && ($limit===false || $count<$limit)) {
        $species=null;
        //When data relating to the main sample in the import file is different to the previous row, we know to create a new sample.
        //This function tests for this and submits the sample to the database if a new sample is required, otherwise the code continues building the sub-sample/occurrence sub-models for the existing submission.
        //If a new sample is required, the number of occurrences created for the parent sample is reset back to 0.
        if ($importErrorDetected===false||$args['keep_going_after_error']) {
          $returnArray = self::submit_data_if_new_parent_sample_required($values, $data,$args,$writeAuth,$occurrenceNumber,$submission);
          $occurrenceNumber=$returnArray[0];
          $importErrorDetected=$returnArray[1];
        }
        $count++;
        $index = 0;
        // Note, the mappings will always be in the same order as the columns of the CSV file
        $columnNum = 0;
        foreach($_POST AS $column=>$destination) {  
          if (!empty($data[$columnNum])) {
            if ($column == 'Species')
              $species = $data[$columnNum];
          }
          $columnNum++; 
        }       
        if ($importErrorDetected===false||$args['keep_going_after_error']) {
          $importErrorDetected = self::create_values_array_to_submit_sample($values, $data, $occurrenceNumber,$species,$args,$importErrorDetected);
          $importErrorDetected = self::create_values_array_to_submit_occurrence($values, $data, $occurrenceNumber,$species,$args,$importErrorDetected);
        }      
      }
      if ($importErrorDetected===false||$args['keep_going_after_error']) {
        $submission=self::get_submission($values, $args, true);
        $response = data_entry_helper::forward_post_to('save', $submission,$writeAuth);
        drupal_set_message('Import complete');
      } else {
        drupal_set_message('There was a problem with the import data. I have stopped the import, but some data may have already been processed.');
      }
    }
  }
  
  /*
   * Set some basic elements of the values array to pass to submission.
   * These are elements that can be set straightaway without dealing with submodels etc.
   * This includes setting the website_id, survey_id. Most of the items are taken from the options
   * set by the user on the previous page.
   */
  protected function get_initial_values_array_values(&$values,$args) {
    $values['gridmode']=true;
    $values['website_id']=$args['website_id'];
    //importSettingsToCarryForward contains the data the user has entered on the import settings page
    $values['survey_id']=$_SESSION['importSettingsToCarryForward']['survey_id'];
    $values['occurrence:record_status'] = $_SESSION['importSettingsToCarryForward']['occurrence:record_status'];
    $values['sample:entered_sref_system']=$_SESSION['importSettingsToCarryForward']['sample:entered_sref_system'];
    $values['{fieldname}'] = '{default}';
    //Even though we are importing the data, we still need to insert an input_form so the system will know which page to open when
    //the user clicks on a record.
    if ($args['efforts_survey_id']==$values['survey_id']) {
      $values['sample:input_form'] = $args['efforts_and_online_recording_page_path'];
    } else {
      $values['sample:input_form'] = $args['ahoc_online_recording_page_path'];
    }     
    return $values;
  }
  
  /*
   * As we read each line of the import file, we know to attach the occurrence/sub-sample to the same parent sample
   * as the previous line if the main data relating to the parent sample remains unchanged.
   * If we detect a difference with any of the main data, then we know a new sample is required. In the situation
   * that a new sample is required, we submit the existing submission to the database and start a new submission, else we continue building the existing submodel.
   */
  protected function submit_data_if_new_parent_sample_required(&$values, $data,$args,$writeAuth,$occurrenceNumber,&$submission) {
    //There are 2 arrays we are dealing with. $_POST contains the name of the column as a key and then the format
    //of the key required for submission as its value. The $data array is a numbered list of columns with a data
    //value. So in order to submit to the database, we need to use the $_POST data value as the key and the value from the $data
    //array as the value. The two arrays match up with each other e.g. the second item in the $_POST array relates to the
    //second item in the data array. So by counting as we look at each item in the $_POST array, we can then get the equivalent
    //item in the $data array as required.
    $postCounter=0;
    //Assume to start with that we are going to use the parent sample from the previous row.
    $newSampleRequired = false;
    foreach ($_POST as $fieldName=>$fieldInPostFormat) {
      //Check if any of the sample attributes have changed and also data held in the sample row in the database (as opposed to data held in the sample_attributes table)
      if ($fieldInPostFormat == 'sample:entered_sref'||$fieldInPostFormat == 'sample:date'||$fieldInPostFormat == 'sample:comment'||$fieldInPostFormat == 'sample:location_name'||substr($fieldInPostFormat, 0, 8 ) === "smpAttr:") {
        if (!empty($values[$fieldInPostFormat]) && $values[$fieldInPostFormat]!=$data[$postCounter])      
          $newSampleRequired = true;
      }
      //For the Site we submit an id rather than the raw name.
      if ($fieldName == 'Site') {
        //If there is no main location, this is still a valid scenerio as there can be an "Other Site"
        if (empty($data[$postCounter])) {
          $values['sample:location_id'] = '';
        } else {
          $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
          $locationRecord = data_entry_helper::get_population_data(array(
            'table' => 'location',
            'extraParams' => $readAuth + array('name' => $data[$postCounter]), 
          ));
          //If we can't find an id for the location name in the database, then warn the user.
          if (!empty($locationRecord[0]['id'])) {
            //Still need to do the same check for location_id to see if it has changed from the previous row.
            if (!empty($values['sample:location_id']) && $values['sample:location_id']!=$locationRecord[0]['id'])      
              $newSampleRequired = true; 
            $values['sample:location_id'] = $locationRecord[0]['id'];
          } else {
            drupal_set_message('<B>Warning: The location '.$data[$postCounter].' is not currently present in the database. .</B>');
            if ($args['keep_going_after_error'])
              drupal_set_message('<i>I will attempt to continue with the import but errors may occur or there might be inconsistent data entered into the database.</i>');
            else
              drupal_set_message('<i>The import has been stopped.</i>');
            return array($occurrenceNumber,true);
          }
        }
      }  
      //Move onto the next column.
      $postCounter++;
    }
    if ($newSampleRequired===true) {
      $submission=self::get_submission($values, $args, true);
      $authentication = import_helper::get_read_write_auth($args['website_id'], $args['password']);  
      $response = data_entry_helper::forward_post_to('save', $submission,$authentication['write_tokens']);
      //Once we know we need a new sample, we start building the values array again.
      $occurrenceNumber=0;
      $values = array();
      self::get_initial_values_array_values($values,$args);
    }
    return array($occurrenceNumber,false);
  }
  
  /*
   * When a term name is provided, this function returns the termlist_terms id for the term name.
   * An example use of this is the reticules field, where we need to submit as extra item in the $values array
   * which also contains the termlists_term id which is then used by the sub-sample grid reference calculator (this
   * calculator is existing code before the importer existed, so we don't want to change the way that works).
   */
  protected function get_termlists_terms_id_for_occurrence_term($occAttrId, $term, $args) {
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    $reportOptions = array(
      'dataSource'=>'reports_for_prebuilt_forms/Shorewatch/get_ids_from_occ_terms_for_import',
      'readAuth'=>$readAuth,
      'mode'=>'report',
      'extraParams' => array('occurrence_attr_id'=>$occAttrId,'term'=>$term)
    );
    $termlistsTermIdData = report_helper::get_report_data($reportOptions);
    return $termlistsTermIdData[0]['id'];
  }
  
  /*
   * Function that adds data for the sample to the $values array to be passed in for submission.
   * This data is then added to the samples and sub-samples by the submission building function.
   */
  protected function create_values_array_to_submit_sample(&$values, $data, $occurrenceNumber,$species,$args) { 
    $postCounter=0;
    //Cycle through all the import columns
    foreach ($_POST as $fieldName=>$fieldInPostFormat) {
      if (substr($fieldInPostFormat, 0, 8 ) === "smpAttr:")
        $values[$fieldInPostFormat]=$data[$postCounter];
      //If a column is present in the import, then set it in the $values array
      switch($fieldInPostFormat) {
        case 'sample:entered_sref':
          $values['sample:entered_sref'] = $data[$postCounter];
          break;
        case 'sample:date':
          $values['sample:date'] = $data[$postCounter];
          break;
        case 'sample:comment':
          $values['sample:comment'] = $data[$postCounter];
          break;
        case 'sample:location_name':
          $values['sample:location_name'] = $data[$postCounter];
          break;
      }
      //Site is different as we save the location_id to the database, so get the Id for the name from the database.
      if ($fieldName == 'Site') {
        //If there is no main location, this is still a valid scenerio as there can be an "Other Site"
        if (empty($data[$postCounter])) {
          $values['sample:location_id']='';
        } else {
          $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
          $locationRecord = data_entry_helper::get_population_data(array(
            'table' => 'location',
            'extraParams' => $readAuth + array('name' => $data[$postCounter]),
          ));
          //If the name doesn't exist in the database then warn the user.
          if (!empty($locationRecord[0]['id']))
            $values['sample:location_id'] = $locationRecord[0]['id'];
          else {
            drupal_set_message('<B>Warning: The location '.$data[$postCounter].' is not currently present in the database.</B>');
            if ($args['keep_going_after_error'])
              drupal_set_message('<i>I will attempt to continue with the import but errors may occur or there might be inconsistent data entered into the database.</i>');
            else
              drupal_set_message('<i>The import has been stopped.</i>');
            return true; 
          }
        }
      }  
      //We need to save the input form even though the data hasn't come from a form, so the system knows which edit page to open which editing the record.
      if ($args['efforts_survey_id']==$values['survey_id']) {
        $values['sample:input_form'] = $args['efforts_and_online_recording_page_path'];
      } else {
        $values['sample:input_form'] = $args['ahoc_online_recording_page_path'];
      }     
      $postCounter++;
    }
    return false;
  }
  
  /*
   * Function that adds data for occurrences to the $values array to be passed in for submission.
   * This data is then added to the submodels by the submission building function.
   */
  protected function create_values_array_to_submit_occurrence(&$values, $data, &$occurrenceNumber,$species,$args) {
    $postCounter=0;
    //Cycle through all the import data columns
    foreach ($_POST as $fieldName=>$fieldInPostFormat) {
      if (!empty($data[$postCounter])) {
        //The $_POST array matches up with the columns in the $data array, so if we count the number of columns we have cycled through in the
        //post array then we can get the data we need from the $data array.
        if ($fieldName == 'Species')
          $species = $data[$postCounter];
        if ($fieldName == 'species_Notes')
          $values['sc:species-grid-111-'.$occurrenceNumber.'::occurrence:comment'] = $data[$postCounter];
      }
      if (!empty($species)) {
        if (substr($fieldInPostFormat, 0, 8 ) === "occAttr:") {
          //We need to get the occurrence attribute number from the end of the column in the post.
          $explodedFieldInPostFormat = explode('fk_',$fieldInPostFormat);
          //If it is an occurrence attribute
          if (!empty($explodedFieldInPostFormat[1])) {
            //For reticules we need to put  termlists_term id in the values array rather than the raw data. This is because the existing code that does the sub-sample
            //grid calculation is expecting a termlists_term id instead of the term itself.
            //Firstly, we only go ahead if the current occurrence_attribute we are looking at is actually the reticules one.
            if ($explodedFieldInPostFormat[1]==$args['reticules'])
              $termlistsTermsLookupId=self::get_termlists_terms_id_for_occurrence_term($explodedFieldInPostFormat[1], $data[$postCounter],$args);
          }
          //Create the special item for reticules, otherwise create a standard occurrence attribute data item in the $values array.
          if (!empty($termlistsTermsLookupId)) {
             $occurrenceAttrKeyWithoutFk=str_replace('fk_','',$fieldInPostFormat);
             $values['sc:species-grid-111-'.$occurrenceNumber.'::'.$occurrenceAttrKeyWithoutFk]=$termlistsTermsLookupId;
          } else {
            $values['sc:species-grid-111-'.$occurrenceNumber.'::'.$fieldInPostFormat]=$data[$postCounter];
          }
        }
        
      }
      $termlistsTermsLookupId=null;
      $postCounter++;
    }
    //For the taxon itself, we need to submit a taxa_taxon_list_id instead of a name.
    if (!empty($species)) {
      $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
      $taxaTaxonListRecord = data_entry_helper::get_population_data(array(
        'table' => 'taxa_taxon_list',
        'extraParams' => $readAuth + array('taxon' => $species,'taxon_list_id'=>$_SESSION['importSettingsToCarryForward']['fkFilter:taxa_taxon_list:taxon_list_id']),
      ));
      if (!empty($taxaTaxonListRecord[0]['id']))
        $values['sc:species-grid-111-'.$occurrenceNumber.'::present']=$taxaTaxonListRecord[0]['id'];
      else {
        drupal_set_message('<B>Warning: The taxon '.$species.' is not currently present in the database.</B>');
        if ($args['keep_going_after_error'])
          drupal_set_message('<i>I will attempt to continue with the import but errors may occur or there might be inconsistent data entered into the database.</i>');
        else
          drupal_set_message('<i>The import has been stopped.</i>');
        return true;
      }
      //Set the sub-sample index
      $values['sc:species-grid-111-'.$occurrenceNumber.'::occurrence:sampleIDX'] = $occurrenceNumber;
      $occurrenceNumber++;
    }
    return false;
  }
 
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @param array $runImport As the importer has several pages which submit a $_POST, we use this flag to make sure we
   * only submit to the submission builder at the end.
   * @return array Submission structure.
   */  
  public static function get_submission($values, $args, $runImport=null) {
    if ($runImport)
      return create_submission($values, $args);
  }
}

  

