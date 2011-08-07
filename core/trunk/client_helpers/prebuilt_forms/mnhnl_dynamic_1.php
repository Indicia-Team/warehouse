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

require_once('includes/map.php');
require_once('includes/language_utils.php');
require_once('includes/form_generation.php');

class iform_mnhnl_dynamic_1 {

  // A list of the taxon ids we are loading
  protected static $occurrenceIds = array();

  protected static $auth = array();
  
  protected static $mode;
  
  protected static $node;
  
  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_mnhnl_dynamic_1_definition() {
    return array(
      'title'=>'MNHNL Dynamic 1 - dynamically generated data entry form',
      'category' => 'General Purpose Data Entry Forms',
      'helpLink'=>'http://code.google.com/p/indicia/wiki/TutorialDynamicForm',
      'description'=>'A data entry form with an optional grid listing the user\'s records so forms can be reloaded for editing. Can be used for '.
          'entry of a single occurrence, ticking species off a checklist, or entering species into a grid. The attributes on the form are dynamically '.
          'generated from the survey setup on the Indicia Warehouse.'
    );
  }
  
  /* TODO
   *  
   *   Survey List
   *     Put in "loading" message functionality.
   *    Add a map and put samples on it, clickable
   *  
   *  Sort out {common}.
   * 
   * The report paging will not be converted to use LIMIT & OFFSET because we want the full list returned so 
   * we can display all the occurrences on the map.
   * When displaying transects, we should display children locations as well as parent.
   */
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {    
    $retVal = array_merge(
      iform_map_get_map_parameters(),
      iform_map_get_georef_parameters(),
      array(
        array(
          'name'=>'interface',
          'caption'=>'Interface Style Option',
          'description'=>'Choose the style of user interface, either dividing the form up onto separate tabs, '.
              'wizard pages or having all controls on a single page.',
          'type'=>'select',
          'options' => array(
            'tabs' => 'Tabs',
            'wizard' => 'Wizard',
            'one_page' => 'All One Page'
          ),
          'group' => 'User Interface'
        ),
        array(
          'name'=>'tabProgress',
          'caption'=>'Show Progress through Wizard/Tabs',
          'description'=>'For Wizard or Tabs interfaces, check this option to show a progress summary above the controls.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name'=>'emailShow',
          'caption'=>'Show email field even if logged in',
          'description'=>'If the survey requests an email address, it is sent implicitly for logged in users. Check this box to show it explicitly.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name'=>'nameShow',
          'caption'=>'Show user profile fields even if logged in',
          'description'=>'If the survey requests first name and last name or any field which matches a field in the users profile, these are hidden. '.
              'Check this box to show these fields. Always show these fields if they are required at the warehouse unless the profile module is enabled, '.
              '<em>copy field values from user profile</em> is selected and the fields are required in the profile.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name'=>'copyFromProfile',
          'caption'=>'Copy field values from user profile',
          'description'=>'Copy any matching fields from the user\'s profile into the fields with matching names in the sample data. This works for fields '.
              'defined in the Drupal Profile module. Applies whether fields are shown or not.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface',
          'visible' => function_exists('profile_load_profile')
        ),
        array(
          'name'=>'clientSideValidation',
          'caption'=>'Client Side Validation',
          'description'=>'Enable client side validation of controls using JavaScript. Note that there are bugs in Internet Explorer which can cause errors when '.
              'clicking on the map if this box is ticked.',
          'type'=>'boolean',
          'default' => true,
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name'=>'structure',
          'caption'=>'Form Structure',
          'description'=>'Define the structure of the form. Each component goes on a new line and is nested inside the previous component where appropriate. The following types of '.
            "component can be specified. <br/>".
            "<strong>=tab/page name=</strong> is used to specify the name of a tab or wizard page. (Alpha-numeric characters only)<br/>".
            "<strong>=*=</strong> indicates a placeholder for putting any custom attribute tabs not defined in this form structure. <br/>".
            "<strong>[control name]</strong> indicates a predefined control is to be added to the form with the following predefined controls available: <br/>".
                "&nbsp;&nbsp;<strong>[species]</strong> - a species grid or input control<br/>".
                "&nbsp;&nbsp;<strong>[species attributes]</strong> - any custom attributes for the occurrence, if not using the grid. Also includes a file upload ".
                "box if relevant. The attrubutes @resizeWidth and @resizeHeight can specified on subsequent lines, otherwise they default to 1600.<br/>".
                "&nbsp;&nbsp;<strong>[date]</strong><br/>".
                "&nbsp;&nbsp;<strong>[map]</strong><br/>".
                "&nbsp;&nbsp;<strong>[spatial reference]</strong><br/>".
                "&nbsp;&nbsp;<strong>[location name]</strong><br/>".
                "&nbsp;&nbsp;<strong>[location autocomplete]</strong><br/>".
                "&nbsp;&nbsp;<strong>[location select]</strong><br/>".
                "&nbsp;&nbsp;<strong>[place search]</strong><br/>".
                "&nbsp;&nbsp;<strong>[recorder names]</strong><br/>".
                "&nbsp;&nbsp;<strong>[record status]</strong><br/>".
                "&nbsp;&nbsp;<strong>[sample comment]</strong>. <br/>".
            "<strong>@option=value</strong> on the line(s) following any control allows you to override one of the options passed to the control. The options ".
        "available depend on the control. For example @label=Abundance would set the untranslated label of a control to Abundance. Where the ".
        "option value is an array, use valid JSON to encode the value. For example an array of strings could be passed as @occAttrClasses=[\"class1\",\"class2\"]. ".
        "Other common options include helpText (set to a piece of additional text to display alongside the control) and class (to add css ".
        "classes to the control such as control-width-3). <br/>".
        "<strong>[*]</strong> is used to make a placeholder for putting any custom attributes that should be inserted into the current tab. When this option is ".
        "used, you can change any of the control options for an individual custom attribute control by putting @control|option=value on the subsequent line(s). ".
        "For example, if a control is for smpAttr:4 then you can update it's label by specifying @smpAttr:4|label=New Label on the line after the [*].<br/>".
            "<strong>?help text?</strong> is used to define help text to add to the tab, e.g. ?Enter the name of the site.?",
          'type'=>'textarea',
          'default' => "=Species=\r\n".
              "?Please enter the species you saw and any other information about them.?\r\n".
              "[species]\r\n".
              "@resizeWidth=1500\r\n".
              "@resizeHeight=1500\r\n".
              "[species attributes]\r\n".
              "[*]\r\n".
              "=Place=\r\n".
              "?Please provide the spatial reference of the record. You can enter the reference directly, or search for a place then click on the map.?\r\n".
              "[place search]\r\n".
              "[spatial reference]\r\n".
              "[map]\r\n".
              "[*]\r\n".
              "=Other Information=\r\n".
              "?Please provide the following additional information.?\r\n".
              "[date]\r\n".
              "[sample comment]\r\n".
              "[*]\r\n".
              "=*=",
          'group' => 'User Interface'
        ),
        array(
          'name'=>'attribute_termlist_language_filter',
          'caption'=>'Attribute Termlist Language filter',
          'description'=>'Enable filtering of termlists for attributes using the iso language.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name'=>'no_grid',
          'caption'=>'Skip initial grid of data',
          'description'=>'If checked, then when initially loading the form the data entry form is immediately displayed, as opposed to '.
              'the default of displaying a grid of the user\'s data which they can add to. By ticking this box, it is possible to use this form '.
              'for data entry by anonymous users though they cannot then list the data they have entered.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface'
        ),    
        array(
          'name' => 'grid_report',
          'caption' => 'Grid Report',
          'description' => 'Name of the report to use to populate the grid for selecting existing data from. The report must return a sample_id '.
              'field or occurrence_id field for linking to the data entry form. As a starting point, try reports_for_prebuilt_forms/simple_occurrence_list_1 or '.
              'reports_for_prebuilt_forms/simple_sample_list_1 for a list of occurrences or samples respectively.',
          'type'=>'string',
          'group' => 'User Interface',
          'default' => 'reports_for_prebuilt_forms/simple_sample_list_1'
        ),
        array(
          'name'=>'save_button_below_all_pages',
          'caption'=>'Save button below all pages?',
          'description'=>'Should the save button be present below all the pages (checked), or should it be only on the last page (unchecked)? '.
              'Only applies to the Tabs interface style.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface'
        ),        
        array(
          'name'=>'multiple_occurrence_mode',
          'caption'=>'Single or multiple occurrences per sample',
          'description'=>'Method of data entry, via a grid of occurrences, one occurrence at a time, or allow the user to choose.',
          'type'=>'select',
          'options' => array(
            'single' => 'Only allow entry of one occurrence at a time',
            'multi' => 'Only allow entry of multiple occurrences using a grid',
            'either' => 'Allow the user to choose single or multiple occurrence data entry.'
          ),
          'default' => 'multi',
          'group' => 'Species'
        ),
        array(
          'name'=>'species_ctrl',
          'caption'=>'Single Species Selection Control Type',
          'description'=>'The type of control that will be available to select a single species.',
          'type'=>'select',
          'options' => array(
            'autocomplete' => 'Autocomplete',
            'select' => 'Select',
            'listbox' => 'List box',
            'radio_group' => 'Radio group',
            'treeview' => 'Treeview',
            'tree_browser' => 'Tree browser'
          ),
          'default' => 'autocomplete',
          'group'=>'Species'
        ),
        array(
          'name' => 'species_include_both_names',
          'caption' => 'Include both names in species controls and added rows',
          'description' => 'When using a species grid with the ability to add new rows, the autocomplete control by default shows just the searched taxon name in the drop down. '.
              'Set this to include both the latin and common names, with the searched one first. This also controls the label when adding a new taxon row into the grid.',
          'type' => 'boolean',
          'required' => false,
          'group' => 'Species'
        ),
        array(
          'name' => 'species_include_taxon_group',
          'caption' => 'Include taxon group name in species autocomplete and added rows',
          'description' => 'When using a species grid with the ability to add new rows, the autocomplete control by default shows just the searched taxon name in the drop down. '.
              'Set this to include the taxon group title.  This also controls the label when adding a new taxon row into the grid.',
          'type' => 'boolean',
          'required' => false,
          'group' => 'Species'
        ),
        array(
          'name'=>'occurrence_comment',
          'caption'=>'Occurrence Comment',
          'description'=>'Should an input box be present for a comment against each occurrence?',
          'type'=>'boolean',
          'required' => false,
          'default'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'occurrence_confidential',
          'caption'=>'Occurrence Confidential',
          'description'=>'Should a checkbox be present for confidential status of each occurrence?',
          'type'=>'boolean',
          'required' => false,
          'default'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'occurrence_images',
          'caption'=>'Occurrence Images',
          'description'=>'Should occurrences allow images to be uploaded?',
          'type'=>'boolean',
          'required' => false,
          'default'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'col_widths',
          'caption'=>'Grid Column Widths',
          'description'=>'Provide percentage column widths for each species checklist grid column as a comma separated list. To leave a column at its default with, put a blank '.
              'entry in the list. E.g. "25,,20" would set the first column to 25% width and the 3rd column to 20%, leaving the other columns as they are.',
          'type'=>'string',
          'group'=>'Species',
          'required' => false
        ),
        array(
          'fieldname'=>'list_id',
          'label'=>'Initial Species List',
          'helpText'=>'The Indicia ID for the species list that species can be selected from. This list is pre-populated '.
              'into the grid when doing grid based data entry.',
          'type'=>'select',
          'table'=>'taxon_list',
          'valueField'=>'id',
          'captionField'=>'title',
          'required'=>false,
          'group'=>'Species',
          'siteSpecific'=>true
        ),
        array(
          'fieldname'=>'extra_list_id',
          'label'=>'Extra Species List',
          'helpText'=>'The Indicia ID for the second species list that species can be selected from. This list is available for additional '.
              'taxa being added to the grid when doing grid based data entry.',
          'type'=>'select',
          'table'=>'taxon_list',
          'valueField'=>'id',
          'captionField'=>'title',
          'required'=>false,
          'group'=>'Species',
          'siteSpecific'=>true
        ),
        array(
          'name'=>'species_names_filter',
          'caption'=>'Species Names Filter',
          'description'=>'Select the filter to apply to the species names which are available to choose from.',
          'type'=>'select',          
          'options' => array(
            'all' => 'All names are available',
            'language' => 'Only allow selection of species using common names in the user\'s language',
            'preferred' => 'Only allow selection of species using names which are flagged as preferred'            
          ),
          'default' => 'all',
          'group'=>'Species'
        ),
        array(
          'name'=>'link_species_popups',
          'caption'=>'Create popups for certain species',
          'description'=>'You can mark some blocks of the form to only be shown as a popup when a certain species is entered into the species grid. For each popup block, '.
              'put the species name on a newline, followed by | then the outer block name, followed by | then the inner block name if relevant. For example, '.
              '"Lasius niger|Additional info|Colony info" pops up the controls from the block Additional Info > Colony info when a species is entered with this '.
              'name. For the species name, specify the preferred name from list.',
          'type' => 'textarea',
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'spatial_systems',
          'caption'=>'Allowed Spatial Ref Systems',
          'description'=>'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code number such as 4326). '.
              'Set to "default" to use the settings defined in the IForm Settings page.',
          'type'=>'string',
          'group'=>'Other Map Settings'
        ),
        array(
          'name'=>'survey_id',
          'caption'=>'Survey',
          'description'=>'The survey that data will be posted into.',
          'type'=>'select',
          'table'=>'survey',
          'captionField'=>'title',
          'valueField'=>'id',
          'siteSpecific'=>true
        ),
        array(
          'name' => 'sample_method_id',
          'caption' => 'Sample Method',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:sample_methods'),
          'required' => false,
          'helpText' => 'The sample method that will be used for created samples.'
        ),
        array(
          'name'=>'defaults',
          'caption'=>'Default Values',
          'description'=>'Supply default values for each field as required. On each line, enter fieldname=value. For custom attributes, '.
              'the fieldname is the untranslated caption. For other fields, it is the model and fieldname, e.g. occurrence.record_status. '.
              'For date fields, use today to dynamically default to today\'s date. NOTE, currently only supports occurrence:record_status and '.
              'sample:date but will be extended in future.',
          'type'=>'textarea',
          'default'=>'occurrence:record_status=C'
        ),
        array(
          'name'=>'includeLocTools',
          'caption'=>'Include Location Tools',
          'description'=>'Include a tab for the allocation of locations when displaying the initial grid.',
          'type'=>'boolean',
          'required' => false,
          'default' => false,
          'group' => 'User Interface'
        )        
      )
    );
    return $retVal;
  }
  
  /**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args, $node) {
    define ("MODE_GRID", 0);
    define ("MODE_NEW_SAMPLE", 1);
    define ("MODE_EXISTING", 2);
    self::parse_defaults($args);
    self::getArgDefaults($args);
    global $user;
    $logged_in = $user->uid>0;
    self::$node = $node;
    
    // Get authorisation tokens to update and read from the Warehouse.
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $svcUrl = data_entry_helper::$base_url.'/index.php/services';
    self::$auth = $auth;
    
    $mode = (isset($args['no_grid']) && $args['no_grid']) 
        ? MODE_NEW_SAMPLE // default mode when no_grid set to true - display new sample
        : MODE_GRID; // default mode when no grid set to false - display grid of existing data
                // mode MODE_EXISTING: display existing sample
    $loadedSampleId = null;
    $loadedOccurrenceId = null;
    if ($_POST) {
      if(!array_key_exists('website_id', $_POST)) { // non Indicia POST, in this case must be the location allocations. add check to ensure we don't corrept the data by accident
        if(function_exists('iform_loctools_checkaccess') && iform_loctools_checkaccess($node,'admin') && array_key_exists('mnhnld1', $_POST)){
          iform_loctools_deletelocations($node);
          foreach($_POST as $key => $value){
            $parts = explode(':', $key);
            iform_loctools_insertlocation($node, $parts[2], $parts[1]);
          }
        }
      } else if(!is_null(data_entry_helper::$entity_to_load)){
        $mode = MODE_EXISTING; // errors with new sample, entity populated with post, so display this data.
      } // else valid save, so go back to gridview: default mode 0
    }
    if (array_key_exists('sample_id', $_GET) && $_GET['sample_id']!='{sample_id}'){
      $mode = MODE_EXISTING;
      $loadedSampleId = $_GET['sample_id'];
    }
    if (array_key_exists('occurrence_id', $_GET) && $_GET['occurrence_id']!='{occurrence_id}'){
      $mode = MODE_EXISTING;
      $loadedOccurrenceId = $_GET['occurrence_id'];
      self::$occurrenceIds = array($loadedOccurrenceId);
    } 
    if ($mode!=MODE_EXISTING && array_key_exists('newSample', $_GET)){
      $mode = MODE_NEW_SAMPLE;
      data_entry_helper::$entity_to_load = array();
    } // else default to mode MODE_GRID or MODE_NEW_SAMPLE depending on no_grid parameter
    self::$mode = $mode;
    // default mode  MODE_GRID : display grid of the samples to add a new one 
    // or edit an existing one.
    if($mode ==  MODE_GRID) {
      $r = '';
      $attributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
      ));
      $tabs = array('#sampleList'=>lang::get('LANG_Main_Samples_Tab'));
      if($args['includeLocTools'] && function_exists('iform_loctools_checkaccess') && iform_loctools_checkaccess($node,'admin')){
        $tabs['#setLocations'] = lang::get('LANG_Allocate_Locations');
      }
      if (method_exists(get_called_class(), 'getExtraGridModeTabs')) {
        $extraTabs = call_user_func(array(get_called_class(), 'getExtraGridModeTabs'), false, $auth['read'], $args, $attributes);
        if(is_array($extraTabs))
          $tabs = $tabs + $extraTabs;
      }
      if(count($tabs) > 1){
        $r .= "<div id=\"controls\">".(data_entry_helper::enable_tabs(array('divId'=>'controls','active'=>'#sampleList')))."<div id=\"temp\"></div>";
        $r .= data_entry_helper::tab_header(array('tabs'=>$tabs));
      }
      $r .= "<div id=\"sampleList\">".call_user_func(array(get_called_class(), 'getSampleListGrid'), $args, $node, $auth, $attributes)."</div>";
      if($args['includeLocTools'] && function_exists('iform_loctools_checkaccess') && iform_loctools_checkaccess($node,'admin')){
        $r .= '
  <div id="setLocations">
    <form method="post">
      <input type="hidden" id="mnhnld1" name="mnhnld1" value="mnhnld1" /><table border="1"><tr><td></td>';
        $url = $svcUrl.'/data/location?mode=json&view=detail&auth_token='.$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&parent_id=NULL&orderby=name";
        $session = curl_init($url);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        $entities = json_decode(curl_exec($session), true);
        $userlist = iform_loctools_listusers($node);
        foreach($userlist as $uid => $a_user){
          $r .= '<td>'.$a_user->name.'</td>';
        }
        $r .= "</tr>";
        if(!empty($entities)){
          foreach($entities as $entity){
            if(!$entity["parent_id"]){ // only assign parent locations.
              $r .= "<tr><td>".$entity["name"]."</td>";
              $defaultuserids = iform_loctools_getusers($node, $entity["id"]);
              foreach($userlist as $uid => $a_user){
                $r .= '<td><input type="checkbox" name="location:'.$entity["id"].':'.$uid.(in_array($uid, $defaultuserids) ? '" checked="checked"' : '"').'></td>';
              }
              $r .= "</tr>";
            }
          }
        }
        $r .= "</table>
      <input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save_Location_Allocations')."\" />
    </form>
  </div>";
      }
      if (method_exists(get_called_class(), 'getExtraGridModeTabs')) {
        $r .= call_user_func(array(get_called_class(), 'getExtraGridModeTabs'), true, $auth['read'], $args, $attributes);
      }
      if(count($tabs)>1){ // close tabs div if present
        $r .= "</div>";
      }
      return $r;
    }
    if ($mode == MODE_EXISTING && is_null(data_entry_helper::$entity_to_load)) { // only load if not in error situation
      data_entry_helper::$entity_to_load = array();
      // Displaying an existing sample. If we know the occurrence ID, and don't know the sample ID or are displaying just one occurrence
      // rather than a grid of occurrences then we must load the occurrence data to get the sample id.
      if ($loadedOccurrenceId && (!$loadedSampleId || !self::getGridMode($args))) {
        data_entry_helper::load_existing_record($auth['read'], 'occurrence', $loadedOccurrenceId);
        // Get the sample ID for the occurrence. This overwrites it if supply in GET but did not match the occurrence's sample
        $loadedSampleId = data_entry_helper::$entity_to_load['occurrence:sample_id'];
        if (self::getGridMode($args)) {
          // in grid mode, we only needed to load the occurrence to find out the sample id.
          data_entry_helper::$entity_to_load=array();
        }
      }
      if ($loadedSampleId)
        data_entry_helper::load_existing_record($auth['read'], 'sample', $loadedSampleId);
    }
    // attributes must be fetched after the entity to load is filled in - this is because the id gets filled in then!
    $attrOpts = array(
        'id' => data_entry_helper::$entity_to_load['sample:id']
       ,'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
    );
    // select only the custom attributes that are for this sample method or all sample methods, if this
    // form is for a specific sample method.
    if (!empty($args['sample_method_id']))
      $attrOpts['sample_method_id']=$args['sample_method_id'];
    $attributes = data_entry_helper::getAttributes($attrOpts);
    //// Make sure the form action points back to this page
    $reload = data_entry_helper::get_reload_link_parts();
    unset($reload['params']['sample_id']);
    unset($reload['params']['occurrence_id']);
    unset($reload['params']['newSample']);
    $reloadPath = $reload['path'];
    if(count($reload['params'])) $reloadPath .= '?'.http_build_query($reload['params']);
    $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\">\n";
    // Get authorisation tokens to update the Warehouse, plus any other hidden data.
    $hiddens = $auth['write'].
          "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n".
          "<input type=\"hidden\" id=\"survey_id\" name=\"survey_id\" value=\"".$args['survey_id']."\" />\n";
    if (!empty($args['sample_method_id'])) {
      $hiddens .= '<input type="hidden" name="sample:sample_method_id" value="'.$args['sample_method_id'].'"/>';
    }
    if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      $hiddens .= "<input type=\"hidden\" id=\"sample:id\" name=\"sample:id\" value=\"".data_entry_helper::$entity_to_load['sample:id']."\" />\n";    
    }
    if (isset(data_entry_helper::$entity_to_load['occurrence:id'])) {
      $hiddens .= "<input type=\"hidden\" id=\"occurrence:id\" name=\"occurrence:id\" value=\"".data_entry_helper::$entity_to_load['occurrence:id']."\" />\n";    
    }
    // Check if Record Status is included as a control. If not, then add it as a hidden.
    $arr = explode("\r\n", $args['structure']);
    if (!in_array('[record status]', $arr)) {
      $value = isset($args['defaults']['occurrence:record_status']) ? $args['defaults']['occurrence:record_status'] : 'C'; 
      $hiddens .= "<input type=\"hidden\" id=\"occurrence:record_status\" name=\"occurrence:record_status\" value=\"$value\" />\n";    
    }
    // request automatic JS validation
    if (!isset($args['clientSideValidation']) || $args['clientSideValidation'])
      data_entry_helper::enable_validation('entry_form');

    // If logged in, output some hidden data about the user
    if (isset($args['copyFromProfile']) && $args['copyFromProfile']==true) {
      self::profile_load_all_profile($user);
    }
    foreach($attributes as &$attribute) {
      $attrPropName = 'profile_'.strtolower(str_replace(' ','_',$attribute['caption']));
      if (isset($args['copyFromProfile']) && $args['copyFromProfile']==true && isset($user->$attrPropName)) {
        if ($args['nameShow'] == true) 
          $attribute['default'] = $user->$attrPropName;
        else {
          // profile attributes are not displayed as the user is logged in
          $attribute['handled']=true;
          $attribute['value'] = $user->$attrPropName;
        }
      }
      elseif (strcasecmp($attribute['caption'], 'cms user id')==0) {
        if ($logged_in) $attribute['value'] = $user->uid;
        $attribute['handled']=true; // user id attribute is never displayed
      }
      elseif (strcasecmp($attribute['caption'], 'cms username')==0) {
        if ($logged_in) $attribute['value'] = $user->name;
        $attribute['handled']=true; // username attribute is never displayed
      }
      elseif (strcasecmp($attribute['caption'], 'email')==0) {
        if ($logged_in) {
          if ($args['emailShow'] != true)
          {// email attribute is not displayed
            $attribute['value'] = $user->mail;
            $attribute['handled']=true; 
          }
          else
            $attribute['default'] = $user->mail;
        }
      }
      elseif ((strcasecmp($attribute['caption'], 'first name')==0 ||
          strcasecmp($attribute['caption'], 'last name')==0 ||
          strcasecmp($attribute['caption'], 'surname')==0) && $logged_in) {
        if ($args['nameShow'] != true) {  
          // name attributes are not displayed because we have the users login
          $attribute['handled']=true;
        }
      }
      // If we have a value for one of the user login attributes then we need to output this value. BUT, for existing data
      // we must not overwrite the user who created the record.
      if (isset($attribute['value']) && $mode != MODE_EXISTING) {
        $hiddens .= '<input type="hidden" name="'.$attribute['fieldname'].'" value="'.$attribute['value'].'" />'."\n";
      }
    }
    $customAttributeTabs = self::get_attribute_tabs($attributes);
    $tabs = self::get_all_tabs($args['structure'], $customAttributeTabs);
    $r .= "<div id=\"controls\">\n";
    // Build a list of the tabs that actually have content
    $tabHtml = self::get_tab_html($tabs, $auth, $args, $attributes, $hiddens);
    // Output the dynamic tab headers
    if ($args['interface']!='one_page') {
      $headerOptions = array('tabs'=>array());
      foreach ($tabHtml as $tab=>$tabContent) {
        $alias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
        $tabtitle = lang::get("LANG_Tab_$alias");
        if ($tabtitle=="LANG_Tab_$alias") {
          // if no translation provided, we'll just use the standard heading
          $tabtitle = $tab;
        }
        $headerOptions['tabs']['#'.$alias] = $tabtitle;        
      }
      $r .= data_entry_helper::tab_header($headerOptions);
      data_entry_helper::enable_tabs(array(
          'divId'=>'controls',
          'style'=>$args['interface'],
          'progressBar' => isset($args['tabProgress']) && $args['tabProgress']==true
      ));
    }
    
    // Output the dynamic tab content
    $pageIdx = 0;
    foreach ($tabHtml as $tab=>$tabContent) {
      // get a machine readable alias for the heading
      $tabalias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
      $r .= '<div id="'.$tabalias.'">'."\n";
      // For wizard include the tab title as a header.
      if ($args['interface']=='wizard') {
        $r .= '<h1>'.$headerOptions['tabs']['#'.$tabalias].'</h1>';        
      }
      $r .= $tabContent;    
      // Add any buttons required at the bottom of the tab   
      if ($args['interface']=='wizard') {
        $r .= data_entry_helper::wizard_buttons(array(
          'divId'=>'controls',
          'page'=>$pageIdx===0 ? 'first' : (($pageIdx==count($tabHtml)-1) ? 'last' : 'middle')
        ));        
      } elseif ($pageIdx==count($tabHtml)-1 && !($args['interface']=='tabs' && $args['save_button_below_all_pages']))
        // last part of a non wizard interface must insert a save button, unless it is tabbed interface with save button beneath all pages 
        $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" id=\"save-button\" value=\"".lang::get('LANG_Save')."\" />\n";      
      $pageIdx++;
      $r .= "</div>\n";      
    }
    $r .= "</div>\n";
    if ($args['interface']=='tabs' && $args['save_button_below_all_pages']) {
      $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" id=\"save-button\" value=\"".lang::get('LANG_Save')."\" />\n";
    }
    if(!empty(data_entry_helper::$validation_errors)){
      $r .= data_entry_helper::dump_remaining_errors();
    }   
    $r .= "</form>";
    
    $r .= self::link_species_popups($args);
    return $r;
  }
  
  /** 
   * Implement the link_species_popups parameter. This hides any identified blocks and pops them up when a certain species is entered.
   */
  protected static function link_species_popups($args) {
    $r='';
    if (isset($args['link_species_popups']) && !empty($args['link_species_popups'])) {
      data_entry_helper::add_resource('fancybox');
      $popups = explode("\n", $args['link_species_popups']);
      foreach ($popups as $popup) {
        $tokens = explode("|", $popup);
        if (count($tokens)==2) 
          $fieldset = get_fieldset_id($tokens[1]);
        else if (count($tokens)==3) 
          $fieldset = get_fieldset_id($tokens[1],$tokens[2]);
        else
          throw new Exception('The link species popups form argument contains an invalid value');
        // insert a save button into the fancyboxed fieldset, since the normal close X looks like it cancels changes
        data_entry_helper::$javascript .= "$('#$fieldset').append('<input type=\"button\" value=\"".lang::get('Close')."\" onclick=\"$.fancybox.close();\" ?>');\n";
        // create an empty link that we can fire to fancybox the popup fieldset
        $r .= "<a href=\"#$fieldset\" id=\"click-$fieldset\"></a>\n";
        // add a hidden div to the page so we can put the popup fieldset into it when not popped up
        data_entry_helper::$javascript .= "$('#$fieldset').after('<div style=\"display:none;\" id=\"hide-$fieldset\"></div>');\n";
        // put the popup fieldset into the hidden div
        data_entry_helper::$javascript .= "$('#hide-$fieldset').append($('#$fieldset'));\n";
        // capture new row events on the grid
        data_entry_helper::$javascript .= "hook_species_checklist_new_row=function(data) { 
  if (data.preferred_name=='$tokens[0]') {
    $('#click-$fieldset').fancybox({showCloseButton: false}).trigger('click');
  }
}\n";
      }
    }
    return $r;
  }
  
  protected static function get_tab_html($tabs, $auth, $args, $attributes, $hiddens) {
    $defAttrOptions = array('extraParams'=>$auth['read']);
    if(isset($args['attribute_termlist_language_filter']) && $args['attribute_termlist_language_filter'])
        $defAttrOptions['language'] = iform_lang_iso_639_2($args['language']);
    $tabHtml = array();
    foreach ($tabs as $tab=>$tabContent) {
      // keep track on if the tab actually has real content, so we can avoid floating instructions if all the controls 
      // were removed by user profile integration for example.
      $hasControls = false;
      // get a machine readable alias for the heading
      $tabalias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
      $html = '';
      if (count($tabHtml)===0)
        // output the hidden inputs on the first tab
        $html .= $hiddens;
      // Now output the content of the tab. Use a for loop, not each, so we can treat several rows as one object
      for ($i = 0; $i < count($tabContent); $i++) {
        $component = $tabContent[$i];
        if (preg_match('/\A\?[^�]*\?\z/', trim($component))===1) {
          // Component surrounded by ? so represents a help text
          $helpText = substr(trim($component), 1, -1);
          $html .= '<div class="page-notice ui-state-highlight ui-corner-all">'.lang::get($helpText)."</div>";
        } elseif (preg_match('/\A\[[^�]*\]\z/', trim($component))===1) {
          // Component surrounded by [] so represents a control or control block
          $method = 'get_control_'.preg_replace('/[^a-zA-Z0-9]/', '', strtolower($component));
          // Anything following the component that starts with @ is an option to pass to the control
          $options = array();
          while ($i < count($tabContent)-1 && substr($tabContent[$i+1],0,1)=='@') {
            $i++;
            $option = explode('=',substr($tabContent[$i],1));
            $options[$option[0]]=json_decode($option[1]);
            // if not json then need to use option value as it is
            if ($options[$option[0]]=='') $options[$option[0]]=$option[1];            
          }
          if (method_exists(get_called_class(), $method)) { 
            $html .= call_user_func(array(get_called_class(), $method), $auth, $args, $tabalias, $options);
            $hasControls = true;
          } elseif (trim($component)==='[*]'){
            // this outputs any custom attributes that remain for this tab. The custom attributes can be configured in the 
            // settings text using something like @smpAttr:4|label=My label. The next bit of code parses these out into an 
            // array used when building the html.
            $blockOptions = array();
            foreach ($options as $option => $value) {
              // split the id of the option into the control name and option name.
              $optionId = explode('|', $option);
              if (!isset($blockOptions[$optionId[0]])) $blockOptions[$optionId[0]]=array();
              $blockOptions[$optionId[0]][$optionId[1]] = $value;
            }
            $defAttrOptions = array_merge($defAttrOptions, $options);
            $attrHtml = get_attribute_html($attributes, $args, $defAttrOptions, $tab, $blockOptions);
            if (!empty($attrHtml))
              $hasControls = true;
            $html .= $attrHtml;
          } else          
            $html .= "The form structure includes a control called $component which is not recognised.<br/>";
        }      
      }
      if (!empty($html) && $hasControls) {
        $tabHtml[$tab] = $html;
      }
    }
    return $tabHtml;
  }
  
  /**
   * Finds the list of tab names that are going to be required by the custom attributes.
   */
  protected static function get_attribute_tabs(&$attributes) {
    $r = array();
    foreach($attributes as &$attribute) {
      if (!isset($attribute['handled']) || $attribute['handled']!=true) {
        // Assign any ungrouped attributes to a block called Other Information 
        if (empty($attribute['outer_structure_block'])) 
          $attribute['outer_structure_block']='Other Information';
        if (!array_key_exists($attribute['outer_structure_block'], $r))
          // Create a tab for this structure block and mark it with [*] so the content goes in
          $r[$attribute['outer_structure_block']] = array("[*]");
      }
    }
    return $r;
  }
  
  /**
   * Finds the list of all tab names that are going to be required, either by the form
   * structure, or by custom attributes.
   */
  protected static function get_all_tabs($structure, $attrTabs) {
    // tolerate any line ending format
    $structure = str_replace("\r\n", "\n", $structure);
    $structure = str_replace("\r", "\n", $structure);
    $structureArr = explode("\n", trim($structure));
    $structureTabs = array();
    foreach ($structureArr as $component) {
      if (preg_match('/^=[A-Za-z0-9 \-\*\?]+=$/', trim($component), $matches)===1) {
        $currentTab = substr($matches[0], 1, -1);
        $structureTabs[$currentTab] = array();
      } else {
        if (!isset($currentTab)) 
          throw new Exception('The form structure parameter must start with a tab title, e.g. =Species=');
        $structureTabs[$currentTab][] = $component;
      }
    }
    // If any additional tabs are required by attributes, add them to the position marked by a dummy tab named [*].
    // First get rid of any tabs already in the structure
    foreach ($attrTabs as $tab => $tabContent) {
      // case -insensitive check if attribute tab already in form structure
      if (in_array(strtolower($tab), array_map('strtolower', array_keys($structureTabs))))
        unset($attrTabs[$tab]);
    }
    // Now we have a list of form structure tabs, with the position of the $attrTabs marked by *. So join it all together.
    // Maybe there is a better way to do this?
    $allTabs = array();
    foreach($structureTabs as $tab => $tabContent) {
      if ($tab=='*') 
        $allTabs += $attrTabs;
      else {
        $allTabs[$tab] = $tabContent;
      }
    }
    return $allTabs;
  }
  
  /** 
   * Get the map control.
   */
  protected static function get_control_map($auth, $args, $tabalias, $options) {
    $options = array_merge(
      iform_map_get_map_options($args, $auth['read']),
      $options
    );
    if (isset(data_entry_helper::$entity_to_load['sample:geom'])) {
      $options['initialFeatureWkt'] = data_entry_helper::$entity_to_load['sample:wkt'];
    }
    if ($args['interface']!=='one_page')
      $options['tabDiv'] = $tabalias;
    $olOptions = iform_map_get_ol_options($args);
    if (!isset($options['standardControls']))
      $options['standardControls']=array('layerSwitcher','panZoom');
    return data_entry_helper::map_panel($options, $olOptions);
  }
  
  /**
   * Get the control for species input, either a grid or a single species input control.
   */
  protected static function get_control_species($auth, $args, $tabalias, $options) {
    $extraParams = $auth['read'];
    if ($args['species_names_filter']=='preferred') {
      $extraParams += array('preferred' => 't');
    }
    if ($args['species_names_filter']=='language') {
      $extraParams += array('language' => iform_lang_iso_639_2($user->lang));
    }  
    if (call_user_func(array(get_called_class(), 'getGridMode'), $args)) {      
      // multiple species being input via a grid      
      $species_ctrl_opts=array_merge(array(
          'listId'=>$args['list_id'],
          'label'=>lang::get('occurrence:taxa_taxon_list_id'),
          'columns'=>1,          
          'extraParams'=>$extraParams,
          'survey_id'=>$args['survey_id'],
          'occurrenceComment'=>$args['occurrence_comment'],
          'occurrenceConfidential'=>(isset($args['occurrence_confidential']) ? $args['occurrence_confidential'] : false),
          'occurrenceImages'=>$args['occurrence_images'],
          'PHPtaxonLabel' => true,
          'language' => iform_lang_iso_639_2($user->lang) // used for termlists in attributes
      ), $options);
      if ($args['extra_list_id']) $species_ctrl_opts['lookupListId']=$args['extra_list_id'];
      if (isset($args['col_widths']) && $args['col_widths']) $species_ctrl_opts['colWidths']=explode(',', $args['col_widths']);
      
      call_user_func(array(get_called_class(), 'build_grid_taxon_label_function'), $args);
      call_user_func(array(get_called_class(), 'build_grid_autocomplete_function'), $args);
      
      // Start by outputting a hidden value that tells us we are using a grid when the data is posted,
      // then output the grid control
      return '<input type="hidden" value="true" name="gridmode" />'.
          data_entry_helper::species_checklist($species_ctrl_opts);
    }
    else {
      // A single species entry control of some kind
      if ($args['extra_list_id']=='')
        $extraParams['taxon_list_id'] = $args['list_id'];
      // @todo At the moment the autocomplete control does not support 2 lists. So use just the extra list. Should 
      // update to support 2 lists.
      elseif ($args['species_ctrl']=='autocomplete')
        $extraParams['taxon_list_id'] = empty($args['extra_list_id']) ? $args['list_id'] : $args['extra_list_id'];
      else
        $extraParams['taxon_list_id'] = array($args['list_id'], $args['extra_list_id']);
      $species_ctrl_opts=array_merge(array(
          'label'=>lang::get('occurrence:taxa_taxon_list_id'),
          'fieldname'=>'occurrence:taxa_taxon_list_id',
          'table'=>'taxa_taxon_list',
          'captionField'=>'taxon',
          'valueField'=>'id',
          'columns'=>2,
          'parentField'=>'parent_id',
          'extraParams'=>$extraParams,
          'blankText'=>'Please select'
      ), $options);
      global $indicia_templates;
      if (isset($args['species_include_both_names']) && $args['species_include_both_names']) {
        if ($args['species_names_filter']=='all')
          $indicia_templates['species_caption'] = '{taxon}';
        elseif ($args['species_names_filter']=='language')
          $indicia_templates['species_caption'] = '{taxon} - {preferred_name}';
        else
          $indicia_templates['species_caption'] = '{taxon} - {common}';
        $species_ctrl_opts['captionTemplate'] = 'species_caption';
      }
      if ($args['species_ctrl']=='tree_browser') {
        // change the node template to include images
        $indicia_templates['tree_browser_node']='<div>'.
            '<img src="'.data_entry_helper::$base_url.'/upload/thumb-{image_path}" alt="Image of {caption}" width="80" /></div>'.
            '<span>{caption}</span>';
      }
      // Dynamically generate the species selection control required.
      return call_user_func(array('data_entry_helper', $args['species_ctrl']), $species_ctrl_opts);
    }
  }
  
  /**
   * Build a PHP function  to format the species added to the grid according to the form parameters
   * autocomplete_include_both_names and autocomplete_include_taxon_group.
   */
  protected static function build_grid_autocomplete_function($args) {
    global $indicia_templates;  
    // always include the searched name
    $fn = "function(item) { \n".
        "  var r;\n".
        "  if (item.language=='lat') {\n".
        "    r = '<em>'+item.taxon+'</em>';\n".
        "  } else {\n".
        "    r = item.taxon;\n".
        "  }\n";
    // This bit optionally adds '- common' or '- latin' depending on what was being searched
    if (isset($args['species_include_both_names']) && $args['species_include_both_names']) {
      $fn .= "  if (item.preferred='t' && item.common!=item.taxon && item.common) {\n".
        "    r += ' - ' + item.common;\n".
        "  } else if (item.preferred='f' && item.preferred_name!=item.taxon && item.preferred_name) {\n".
        "    r += ' - <em>' + item.preferred_name + '</em>';\n".
        "  }\n";
    }
    // this bit optionally adds the taxon group
    if (isset($args['species_include_taxon_group']) && $args['species_include_taxon_group'])
      $fn .= "  r += '<br/><strong>' + item.taxon_group + '</strong>'\n";
    // Close the function
    $fn .= " return r;\n".
        "}\n";
    // Set it into the indicia templates
    $indicia_templates['format_species_autocomplete_fn'] = $fn;
  }
  
  /**
   * Build a JavaScript function  to format the autocomplete item list according to the form parameters
   * autocomplete_include_both_names and autocomplete_include_taxon_group.
   */
  protected static function build_grid_taxon_label_function($args) {
    global $indicia_templates;  
    // always include the searched name
    $php = '$r="";'."\n".
        'if ("{language}"=="lat") {'."\n".
        '  $r = "<em>{taxon}</em>";'."\n".
        '} else {'."\n".
        '  $r = "{taxon}";'."\n".
        '}'."\n";
    // This bit optionally adds '- common' or '- latin' depending on what was being searched
    if (isset($args['species_include_both_names']) && $args['species_include_both_names']) {
      $php .= "\n\n".'if ("{preferred}"=="t" && "{common}"!="{taxon}" && "{common}"!="") {'."\n\n\n".
        '  $r .= " - {common}";'."\n".
        '} else if ("{preferred}"=="f" && "{preferred_name}"!="{taxon}" && "{preferred_name}"!="") {'."\n".
        '  $r .= " - <em>{preferred_name}</em>";'."\n".
        '}'."\n";
    }
    // this bit optionally adds the taxon group
    if (isset($args['species_include_taxon_group']) && $args['species_include_taxon_group'])
      $php .= '$r .= "<br/><strong>{taxon_group}</strong>";'."\n";
    // Close the function
    $php .= 'return $r;'."\n";
    // Set it into the indicia templates
    $indicia_templates['taxon_label'] = $php;
  }
  
    
  /**
   * Get the sample comment control
   */
  private static function get_control_samplecomment($auth, $args, $tabalias, $options) {
    return data_entry_helper::textarea(array_merge(array(
      'fieldname'=>'sample:comment',
      'label'=>lang::get('Overall Comment')
    ), $options)); 
  }
  
  /**
   * Get the block of custom attributes at the species (occurrence) level
   */
  private static function get_control_speciesattributes($auth, $args, $tabalias, $options) {
    if (!(call_user_func(array(get_called_class(), 'getGridMode'), $args))) {  
      // Add any dynamically generated controls
      $attrArgs = array(
         'valuetable'=>'occurrence_attribute_value',
         'attrtable'=>'occurrence_attribute',
         'key'=>'occurrence_id',
         'fieldprefix'=>'occAttr',
         'extraParams'=>$auth['read'],
         'survey_id'=>$args['survey_id']
      );
      if (count(self::$occurrenceIds)==1) {
        // if we have a single occurrence Id to load, use it to get attribute values
        $attrArgs['id'] = self::$occurrenceIds[0];
      }
      $attributes = data_entry_helper::getAttributes($attrArgs);
      $defAttrOptions = array('extraParams'=>$auth['read']);
      $r = get_attribute_html($attributes, $args, $defAttrOptions);
      if ($args['occurrence_comment'])
        $r .= data_entry_helper::textarea(array(
          'fieldname'=>'occurrence:comment',
          'label'=>lang::get('Record Comment')
        ));
      if ($args['occurrence_confidential'])
        $r .= data_entry_helper::checkbox(array(
          'fieldname'=>'occurrence:confidential',
          'label'=>lang::get('Record Confidental')
        ));
      if ($args['occurrence_images']){
        $opts = array(
          'table'=>'occurrence_image',
          'label'=>lang::get('Upload your photos'),
        );
        if ($args['interface']!=='one_page')
          $opts['tabDiv']=$tabalias;
        $opts['resizeWidth'] = isset($options['resizeWidth']) ? $options['resizeWidth'] : 1600;
        $opts['resizeHeight'] = isset($options['resizeHeight']) ? $options['resizeHeight'] : 1600;
        $opts['caption'] = lang::get('Photos');
        $r .= data_entry_helper::file_box($opts);
      }
      return $r;
    } else 
      // in grid mode the attributes are embedded in the grid.
      return '';
  }
  
  /** 
   * Get the date control.
   */
  private static function get_control_date($auth, $args, $tabalias, $options) {
    if (isset(data_entry_helper::$entity_to_load['sample:date']) && preg_match('/^(\d{4})/', data_entry_helper::$entity_to_load['sample:date'])) {
      // Date has 4 digit year first (ISO style) - convert date to expected output format
      // @todo The date format should be a global configurable option. It should also be applied to reloading of custom date attributes.
      $d = new DateTime(data_entry_helper::$entity_to_load['sample:date']);
      data_entry_helper::$entity_to_load['sample:date'] = $d->format('d/m/Y');
    }
    if($args['language'] != 'en')
      data_entry_helper::add_resource('jquery_ui_'.$args['language']); // this will autoload the jquery_ui resource. The date_picker does not have access to the args.
    return data_entry_helper::date_picker(array_merge(array(
      'label'=>lang::get('LANG_Date'),
      'fieldname'=>'sample:date',
      'default' => isset($args['defaults']['sample:date']) ? $args['defaults']['sample:date'] : ''
    ), $options));
  }
  
  /** 
   * Get the spatial reference control.
   */
  private static function get_control_spatialreference($auth, $args, $tabalias, $options) {
    // Build the array of spatial reference systems into a format Indicia can use.
    $systems=array();
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach($list as $system) {
      $systems[$system] = lang::get($system);
    }
    return data_entry_helper::sref_and_system(array_merge(array(
      'label' => lang::get('LANG_SRef_Label'),
      'systems' => $systems
    ), $options));
  }
  
  /** 
   * Get the location control as an autocomplete.
   */
  private static function get_control_locationautocomplete($auth, $args, $tabalias, $options) {
    $location_list_args=array_merge(array(
        'label'=>lang::get('LANG_Location_Label'),
        'view'=>'detail',
        'extraParams'=>array_merge(array('orderby'=>'name', 'website_id'=>$args['website_id']), $auth['read'])
    ), $options);
    return data_entry_helper::location_autocomplete($location_list_args);
  }
  
  /** 
   * Get the location control as a select dropdown.
   */
  private static function get_control_locationselect($auth, $args, $tabalias, $options) {
    $location_list_args=array_merge(array(
        'label'=>lang::get('LANG_Location_Label'),
        'view'=>'detail',
        'extraParams'=>array_merge(array('orderby'=>'name', 'website_id'=>$args['website_id']), $auth['read'])
    ), $options);
    return data_entry_helper::location_select($location_list_args);
  }
  
  /** 
   * Get the location name control.
   */
  private static function get_control_locationname($auth, $args, $tabalias, $options) {
    return data_entry_helper::text_input(array_merge(array(
      'label' => lang::get('LANG_Location_Name'),
      'fieldname' => 'sample:location_name',
      'class' => 'control-width-5'
    ), $options));
  }
  
  /** 
   * Get the location search control.
   */
  private static function get_control_placesearch($auth, $args, $tabalias, $options) {
    $georefOpts = iform_map_get_georef_options($args);
    if (($georefOpts['driver']=='geoplanet' && empty(helper_config::$geoplanet_api_key)) 
        || ($georefOpts['driver']=='google_search_api' && empty(helper_config::$google_search_api_key)))
      // can't use place search without the driver API key
      return '';
    return data_entry_helper::georeference_lookup(array_merge(
      $georefOpts,
      $options
    ));
  }

   /**
   * Get the recorder names control
   */
  protected static function get_control_recordernames($auth, $args, $tabalias, $options) {
    return data_entry_helper::textarea(array_merge(array(
      'fieldname'=>'sample:recorder_names',
      'label'=>lang::get('Recorder names')
    ), $options));
  }

  /**
   * Get the control for the record status.
   */
  private static function get_control_recordstatus($auth, $args) {    
    $default = isset(data_entry_helper::$entity_to_load['occurrence:record_status']) ? 
        data_entry_helper::$entity_to_load['occurrence:record_status'] :
        isset($args['defaults']['occurrence:record_status']) ? $args['defaults']['occurrence:record_status'] : 'C';
    $values = array('I', 'C'); // not initially doing V=Verified
    $r = '<label for="occurrence:record_status">'.lang::get('LANG_Record_Status_Label')."</label>\n";
    $r .= '<select id="occurrence:record_status" name="occurrence:record_status">';
    foreach($values as $value){
      $r .= '<option value="'.$value.'"';
      if ($value == $default){
        $r .= ' selected="selected"';
      }
      $r .= '>'.lang::get('LANG_Record_Status_'.$value).'</option>';
    }
    $r .= "</select><br/>\n";
      return $r;
  }   
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    // default for forms setup on old versions is grid - list of occurrences
    // Can't call getGridMode in this context as we might not have the $_GET value to indicate grid
    if (isset($values['gridmode']))
      $submission = data_entry_helper::build_sample_occurrences_list_submission($values);
    else
      $submission = data_entry_helper::build_sample_occurrence_submission($values);
    return($submission);
  }

  /**
   * Retrieves a list of the css files that this form requires in addition to the standard
   * Drupal, theme or Indicia ones.
   * 
   * @return array List of css files to include for this form.
   */
  public static function get_css() {
    return array('mnhnl_collaborators_1.css');
  }
  
  /**
   * Convert the unstructured textarea of default values into a structured array.
   */
  protected static function parse_defaults(&$args) {
    $result=array();
    if (isset($args['defaults'])) {
      $defaults = explode("\n", $args['defaults']);
      foreach($defaults as $default) {
        $tokens = explode('=', $default);
        $result[trim($tokens[0])] = trim($tokens[1]);
      }      
    }  
    $args['defaults']=$result;
  }
  
  /**
   * Returns true if this form should be displaying a multiple occurrence entry grid.
   */
  protected static function getGridMode($args) {
    // if loading an existing sample and we are allowed to display a grid or single species selector
    if ($args['multiple_occurrence_mode']=='either') {
      // Either we are in grid mode because we were instructed to externally, or because the form is reloading
      // after a validation failure with a hidden input indicating grid mode.
      return isset($_GET['gridmode']) || 
          isset(data_entry_helper::$entity_to_load['gridmode']) ||
          ((array_key_exists('sample_id', $_GET) && $_GET['sample_id']!='{sample_id}') &&
           (!array_key_exists('occurrence_id', $_GET) || $_GET['occurrence_id']=='{occurrence_id}'));
    } else
      return 
          // a form saved using a previous version might not have this setting, so default to grid mode=true
          (!isset($args['multiple_occurrence_mode'])) ||
          // Are we fixed in grid mode?
          $args['multiple_occurrence_mode']=='multi';
  }
  
  /**
   * When viewing the list of samples for this user, get the grid to insert into the page.
   */
  protected static function getSampleListGrid($args, $node, $auth, $attributes) {
    global $user;
    // get the CMS User ID attribute so we can filter the grid to this user
    foreach($attributes as $attrId => $attr) {
      if (strcasecmp($attr['caption'],'CMS User ID')==0) {
        $userIdAttr = $attrId;
        break;
      }
    }
    if ($user->uid===0) {
      // Return a login link that takes you back to this form when done.
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">login</a> to the website.');
    }
    if (!isset($userIdAttr)) {
      return lang::get('This form must be used with a survey that has the CMS User ID attribute associated with it so records can '.
          'be tagged against the user.');
    }
    if (isset($args['grid_report']))
      $reportName = $args['grid_report'];
    else
      // provide a default in case the form settings were saved in an old version of the form
      $reportName = 'reports_for_prebuilt_forms/simple_sample_list_1';
    if(method_exists(get_called_class(), 'getSampleListGridPreamble'))
      $r = call_user_func(array(get_called_class(), 'getSampleListGridPreamble'));
    else
      $r = '';
    $r .= data_entry_helper::report_grid(array(
      'id' => 'samples-grid',
      'dataSource' => $reportName,
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => call_user_func(array(get_called_class(), 'getReportActions')),
      'itemsPerPage' =>10,
      'autoParamsForm' => true,
      'extraParams' => array(
        'survey_id'=>$args['survey_id'], 
        'userID_attr_id'=>$userIdAttr,
        'userID'=>$user->uid
      )
    ));    
    $r .= '<form>';    
    if (isset($args['multiple_occurrence_mode']) && $args['multiple_occurrence_mode']=='either') {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Single').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Grid').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample&gridmode')).'\'">';
    } else {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';    
    }
    $r .= '</form>';
    return $r;
  }
  
  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the 
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
  protected function getArgDefaults(&$args) {
     if (!isset($args['structure']) || empty($args['structure']))
      $args['structure'] = "=Species=\r\n".
              "?Please enter the species you saw and any other information about them.?\r\n".
              "[species]\r\n".
              "[species attributes]\r\n".
              "[*]\r\n".
              "=Place=\r\n".
              "?Please provide the spatial reference of the record. You can enter the reference directly, or search for a place then click on the map.?\r\n".
              "[place search]\r\n".
              "[spatial reference]\r\n".
              "[map]\r\n".
              "[*]\r\n".
              "=Other Information=\r\n".
              "?Please provide the following additional information.?\r\n".
              "[date]\r\n".
              "[sample comment]\r\n".
              "[*]\r\n".
              "=*=";
    if (!isset($args['occurrence_comment']))
      $args['occurrence_comment'] == false; 
    if (!isset($args['occurrence_images']))
      $args['occurrence_images'] == false; 
	if (!isset($args['attribute_termlist_language_filter']))
	  $args['attribute_termlist_language_filter'] == false; 
  }

  protected function getReportActions() {
    return array(array('display' => 'Actions', 'actions' => 
        array(array('caption' => 'Edit', 'url'=>'{currentUrl}', 'urlParams'=>array('sample_id'=>'{sample_id}','occurrence_id'=>'{occurrence_id}')))));
  }
  
  /**
   * Variant on the profile modules profile_load_profile, that also gets empty profile values. 
   */
  private static function profile_load_all_profile(&$user) {
    // don't do anything unless in Drupal, with the profile module enabled, and the user logged in.
    if ($user->uid>0 && function_exists('profile_load_profile')) {
      $result = db_query('SELECT f.name, f.type, v.value FROM {profile_fields} f LEFT JOIN {profile_values} v ON f.fid = v.fid AND uid = %d', $user->uid);
      while ($field = db_fetch_object($result)) {
        if (empty($user->{$field->name})) {
          if (empty($field->value)) 
            $user->{$field->name} = '';
          else
            $user->{$field->name} = _profile_field_serialize($field->type) ? unserialize($field->value) : $field->value;
        }
      }
    }
  }
  
}

/**
 * For PHP 5.2, declare the get_called_class method which allows us to use subclasses of this form.
 */
if(!function_exists('get_called_class')) {
function get_called_class() {
    $matches=array();
    $bt = debug_backtrace();
    $l = 0;
    do {
        $l++;
        if(isset($bt[$l]['class']) AND !empty($bt[$l]['class'])) {
            return $bt[$l]['class'];
        }
        $lines = file($bt[$l]['file']);
        $callerLine = $lines[$bt[$l]['line']-1];
        preg_match('/([a-zA-Z0-9\_]+)::'.$bt[$l]['function'].'/',
                   $callerLine,
                   $matches);
        if (!isset($matches[1])) $matches[1]=NULL; //for notices
        if ($matches[1] == 'self') {
               $line = $bt[$l]['line']-1;
               while ($line > 0 && strpos($lines[$line], 'class') === false) {
                   $line--;                 
               }
               preg_match('/class[\s]+(.+?)[\s]+/si', $lines[$line], $matches);
       }
    }
    while ($matches[1] == 'parent'  && $matches[1]);
    return $matches[1];
  } 
} 