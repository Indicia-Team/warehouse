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

class iform_dynamic_sample_occurrence extends iform_dynamic {

  // The ids we are loading if editing existing data
  protected static $loadedSampleId;
  protected static $loadedOccurrenceId;
  protected static $occurrenceIds = array();

  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_dynamic_sample_occurrence_definition() {
    return array(
      'title'=>'Sample with occurrences form',
      'category' => 'General Purpose Data Entry Forms',
      'helpLink'=>'http://code.google.com/p/indicia/wiki/TutorialDynamicForm',
      'description'=>'A sample and occurrence entry form with an optional grid listing the user\'s samples so forms can be ' .
        'reloaded for editing. Can be used for entry of a single occurrence, ticking species off a checklist, or entering ' .
        'species into a grid. The attributes on the form are dynamically generated from the survey setup on the Indicia Warehouse.'
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
      parent::get_parameters(),
      array(
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
            "option value is an array, use valid JSON to encode the value. For example an array of strings could be passed as @occAttrClasses=[\"class1\",\"class2\"] ".
            "or a keyed array as @extraParams={\"preferred\":\"true\",\"orderby\":\"term\"}. " .
            "Other common options include helpText (set to a piece of additional text to display alongside the control) and class (to add css ".
            "classes to the control such as control-width-3). <br/>".
            "<strong>[*]</strong> is used to make a placeholder for putting any custom attributes that should be inserted into the current tab. When this option is ".
            "used, you can change any of the control options for an individual custom attribute control by putting @control|option=value on the subsequent line(s). ".
            "For example, if a control is for smpAttr:4 then you can update it's label by specifying @smpAttr:4|label=New Label on the line after the [*].<br/>".
            "<strong>[smpAttr:<i>n</i>]</strong> is used to insert a particular custom attribute identified by its ID number<br/>".
            "<strong>?help text?</strong> is used to define help text to add to the tab, e.g. ?Enter the name of the site.? <br/>".
            "<strong>|</strong> is used insert a split so that controls before the split go into a left column and controls after the split go into a right column.<br/>".
            "<strong>all else</strong> is copied to the output html so you can add structure for styling.",
          'type'=>'textarea',
          'default' => "=Species=\r\n".
              "?Please enter the species you saw and any other information about them.?\r\n".
              "[species]\r\n".
              "@resizeWidth=1500\r\n".
              "@resizeHeight=1500\r\n".
              "[species attributes]\r\n".
              "[*]\r\n".
              "=Place=\r\n".
              "?Please provide the spatial reference of the record. You can enter the reference directly, or search for a place then click on the map to set it.?\r\n".
              "[spatial reference]\r\n".
              "[place search]\r\n".
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
          'name'=>'users_manage_own_sites',
          'caption'=>'Users can save sites',
          'description'=>'Allow users to save named sites for recall when they add records in future. Users '.
              'are only able to use their own sites. To use this option, make sure you include a '.
              '[location autocomplete] control in the User Interface - Form Structure setting. Use @searchUpdatesSref=true '.
              'on the next line in the form structure to specify that the grid reference for the site should be automatically filled '.
              'in after a site has been selected. You can also add @useLocationName=true on a line after the location autocomplete '.
              'to force any unmatched location names to be stored as a free-text location name against the sample.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Locations'
        ),
        array(
          'name'=>'multiple_occurrence_mode',
          'caption'=>'Allow a single ad-hoc record or a list of records',
          'description'=>'Method of data entry, one occurrence at a time, via a grid allowing '.
              'entry of multiple occurrences at the same place and date, or allow the user to choose.',
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
          'fieldname'=>'list_id',
          'label'=>'Species List ',
          'helpText'=>'The species list that species can be selected from. This list is pre-populated '.
              'into the grid when doing grid based data entry, or provides the list which a species '.
              'can be picked from when doing single occurrence data entry.',
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
          'helpText'=>'The second species list that species can be selected from. This list is available for additional '.
              'taxa being added to the grid when doing grid based data entry. It is not used when the form is configured '.
              'to allow a single occurrence to be input at a time.',
          'type'=>'select',
          'table'=>'taxon_list',
          'valueField'=>'id',
          'captionField'=>'title',
          'required'=>false,
          'group'=>'Species',
          'siteSpecific'=>true
        ),
        array(
          'fieldname' => 'user_controls_taxon_filter',
          'label' => 'User can filter the Extra Species List',
          'helpText' => 'Tick this box to enable a filter button in the species column title which allows the user to control '.
              'which species groups are available for selection when adding new species to the grid, e.g. the user can filter '.
              'to allow selection from just one species group.',
          'type' => 'checkbox',
          'default' => false,
          'required' => false,
          'group'=>'Species'
        ),
        array(
          'fieldname'=>'cache_lookup',
          'label'=>'Cache lookups',
          'helpText'=>'Tick this box to select to use a cached version of the lookup list when '.
              'searching for extra species names to add to the grid, or set to false to use the '.
              'live version (default). The latter is slower and places more load on the warehouse so should only be '.
              'used during development or when there is a specific need to reflect taxa that have only '.
              'just been added to the list.',
          'type'=>'checkbox',
          'required'=>false,
          'group'=>'Species',
          'siteSpecific'=>false
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
          'name'=>'taxon_filter_field',
          'caption'=>'Field used to filter taxa',
          'description'=>'If you want to allow recording for just part of the selected list(s), then select which field you will '.
              'use to specify the filter by.',
          'type'=>'select',
          'options' => array(
            'preferred_name' => 'Preferred name of the taxa',
            'taxon_meaning_id' => 'Taxon Meaning ID',
            'taxon_group' => 'Taxon group title'
          ),
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'taxon_filter',
          'caption'=>'Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group. '.
              'If you provide a single taxon preferred name or taxon meaning ID in this box, then the form is set up for recording just this single '.
              'species. Therefore there will be no species picker control or input grid, and the form will always operate in the single record, non-grid mode. '.
              'As there is no visual indicator which species is recorded you may like to include information about what is being recorded in the '.
              'body text for the page. You may also want to configure the User Interface section of the form\'s Form Structure to move the [species] and [species] controls '.
              'to a different tab and remove the =species= tab, especially if there are no other occurrence attributes on the form.',
          'type' => 'textarea',
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'species_names_filter',
          'caption'=>'Species Names Filter',
          'description'=>'Select the filter to apply to the species names which are available to choose from.',
          'type'=>'select',          
          'options' => array(
            'all' => 'All names are available',
            'language' => 'Only allow selection of species using common names in the user\'s language',
            'preferred' => 'Only allow selection of species using names which are flagged as preferred',
            'excludeSynonyms' => 'Allow common names or preferred latin names'
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
          'name'=>'includeLocTools',
          'caption'=>'Include Location Tools',
          'description'=>'Include a tab for the allocation of locations when displaying the initial grid.',
          'type'=>'boolean',
          'required' => false,
          'default' => false,
          'group' => 'Locations'
        ),
        array(
          'name'=>'loctoolsLocTypeID',
          'caption'=>'Location Tools Location Type ID filter',
          'description'=>'When performing allocation of locations, filter available locations by this location_type_id.',
          'type'=>'int',
          'required' => false,
          'group' => 'Locations'
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
      )
    );
    return $retVal;
  }
  
  protected static function getMode($args, $node) {
    // Default to mode MODE_GRID or MODE_NEW depending on no_grid parameter
    $mode = (isset($args['no_grid']) && $args['no_grid']) ? MODE_NEW : MODE_GRID;                 
    self::$loadedSampleId = null;
    self::$loadedOccurrenceId = null;
    if ($_POST) {
      if(!array_key_exists('website_id', $_POST)) { 
        // non Indicia POST, in this case must be the location allocations. add check to ensure we don't corrupt the data by accident
        if(function_exists('iform_loctools_checkaccess') && iform_loctools_checkaccess($node,'admin') && array_key_exists('mnhnld1', $_POST)){
          iform_loctools_deletelocations($node);
          foreach($_POST as $key => $value){
            $parts = explode(':', $key);
            iform_loctools_insertlocation($node, $parts[2], $parts[1]);
          }
        }
      } else if(!is_null(data_entry_helper::$entity_to_load)){
        // errors with new sample or entity populated with post, so display this data.
        $mode = MODE_EXISTING; 
      } // else valid save, so go back to gridview: default mode 0
    }
    if (array_key_exists('sample_id', $_GET) && $_GET['sample_id']!='{sample_id}'){
      $mode = MODE_EXISTING;
      self::$loadedSampleId = $_GET['sample_id'];
    }
    if (array_key_exists('occurrence_id', $_GET) && $_GET['occurrence_id']!='{occurrence_id}'){
      $mode = MODE_EXISTING;
      self::$loadedOccurrenceId = $_GET['occurrence_id'];
      self::$occurrenceIds = array(self::$loadedOccurrenceId);
    } 
    if ($mode != MODE_EXISTING && array_key_exists('new', $_GET)){
      $mode = MODE_NEW;
      data_entry_helper::$entity_to_load = array();
    }
    return $mode;
  }

  protected static function getGrid($args, $node, $auth) {
    $r = '';
    $attributes = data_entry_helper::getAttributes(array(
      'valuetable' => 'sample_attribute_value'
      ,'attrtable' => 'sample_attribute'
      ,'key' => 'sample_id'
      ,'fieldprefix' => 'smpAttr'
      ,'extraParams' => $auth['read']
      ,'survey_id' => $args['survey_id']
    ), false);
    $tabs = array('#sampleList'=>lang::get('LANG_Main_Samples_Tab'));
    if($args['includeLocTools'] && function_exists('iform_loctools_checkaccess') && iform_loctools_checkaccess($node,'admin')){
      $tabs['#setLocations'] = lang::get('LANG_Allocate_Locations');
    }
    if (method_exists(self::$called_class, 'getExtraGridModeTabs')) {
      $extraTabs = call_user_func(array(self::$called_class, 'getExtraGridModeTabs'), false, $auth['read'], $args, $attributes);
      if(is_array($extraTabs))
        $tabs = $tabs + $extraTabs;
    }
    if(count($tabs) > 1){
      $r .= "<div id=\"controls\">".(data_entry_helper::enable_tabs(array('divId'=>'controls','active'=>'#sampleList')))."<div id=\"temp\"></div>";
      $r .= data_entry_helper::tab_header(array('tabs'=>$tabs));
    }
    $r .= "<div id=\"sampleList\">".call_user_func(array(self::$called_class, 'getSampleListGrid'), $args, $node, $auth, $attributes)."</div>";
    if($args['includeLocTools'] && function_exists('iform_loctools_checkaccess') && iform_loctools_checkaccess($node,'admin')){
      $r .= '
<div id="setLocations">
  <form method="post">
    <input type="hidden" id="mnhnld1" name="mnhnld1" value="mnhnld1" /><table border="1"><tr><td></td>';
      $url = data_entry_helper::$base_url.'/index.php/services/data/location?mode=json&view=detail' .
              '&auth_token=' . $auth['read']['auth_token'] .
              '&nonce=' . $auth['read']["nonce"] .
              "&parent_id=NULL&orderby=name" . 
              (isset($args['loctoolsLocTypeID'])&&$args['loctoolsLocTypeID']<>''?'&location_type_id='.$args['loctoolsLocTypeID']:'');
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
    <input type=\"submit\" class=\"default-button\" value=\"".lang::get('LANG_Save_Location_Allocations')."\" />
  </form>
</div>";
    }
    if (method_exists(self::$called_class, 'getExtraGridModeTabs')) {
      $r .= call_user_func(array(self::$called_class, 'getExtraGridModeTabs'), true, $auth['read'], $args, $attributes);
    }
    if(count($tabs) > 1){ // close tabs div if present
      $r .= "</div>";
    }
    return $r;  
  }
  
  protected static function getEntity($args, $auth) {
    data_entry_helper::$entity_to_load = array();
    // Displaying an existing sample. If we know the occurrence ID, and don't know the sample ID or are displaying just one occurrence
    // rather than a grid of occurrences then we must load the occurrence data to get the sample id.
    if (self::$loadedOccurrenceId && (!self::$loadedSampleId || !self::getGridMode($args))) {

      data_entry_helper::load_existing_record($auth['read'], 'occurrence', self::$loadedOccurrenceId);
      // Get the sample ID for the occurrence. This overwrites it if supply in GET but did not match the occurrence's sample
      self::$loadedSampleId = data_entry_helper::$entity_to_load['occurrence:sample_id'];
      if (self::getGridMode($args)) {
        // in grid mode, we only needed to load the occurrence to find out the sample id.
        data_entry_helper::$entity_to_load=array();
      }
    }
    if (self::$loadedSampleId)
      data_entry_helper::load_existing_record($auth['read'], 'sample', self::$loadedSampleId);    
  }
  
  protected static function getAttributes($args, $auth) {
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
    $attributes = data_entry_helper::getAttributes($attrOpts, false);
    return $attributes;
  }
  
  protected static function getReloadPath () {
    $reload = data_entry_helper::get_reload_link_parts();
    unset($reload['params']['sample_id']);
    unset($reload['params']['occurrence_id']);
    unset($reload['params']['newSample']);
    $reloadPath = $reload['path'];
    if(count($reload['params'])) $reloadPath .= '?'.http_build_query($reload['params']);
    return $reloadPath;
  }
  
  protected static function getHidden ($args) {
    $hiddens = '';
    if (!empty($args['sample_method_id'])) {
      $hiddens .= '<input type="hidden" name="sample:sample_method_id" value="'.$args['sample_method_id'].'"/> . PHP_EOL';
    }
    if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      $hiddens .= '<input type="hidden" id="sample:id\ name="sample:id" value="' . data_entry_helper::$entity_to_load['sample:id'] . '" />' . PHP_EOL;    
    }
    if (isset(data_entry_helper::$entity_to_load['occurrence:id'])) {
      $hiddens .= '<input type="hidden" id="occurrence:id" name="occurrence:id" value="' . data_entry_helper::$entity_to_load['occurrence:id'] . '\" />' . PHP_EOL;    
    }
    // Check if Record Status is included as a control. If not, then add it as a hidden.
    $arr = helper_base::explode_lines($args['structure']);
    if (!in_array('[record status]', $arr)) {
      $value = isset($args['defaults']['occurrence:record_status']) ? $args['defaults']['occurrence:record_status'] : 'C'; 
      $hiddens .= '<input type="hidden" id="occurrence:record_status" name="occurrence:record_status" value="' . $value . '" />' . PHP_EOL;    
    }
    return $hiddens;
  }
  
  /** 
   * Implement the link_species_popups parameter. This hides any identified blocks and pops them up when a certain species is entered.
   */
  protected static function link_species_popups($args) {
    $r='';
    if (isset($args['link_species_popups']) && !empty($args['link_species_popups'])) {
      data_entry_helper::add_resource('fancybox');
      $popups = helper_base::explode_lines($args['link_species_popups']);    
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
  
  /**
   * Get the control for species input, either a grid or a single species input control.
   */
  protected static function get_control_species($auth, $args, $tabalias, $options) {
    global $user;
    $extraParams = $auth['read'];
    if (!empty($args['taxon_filter_field']) && !empty($args['taxon_filter'])) {
      $filterLines = helper_base::explode_lines($args['taxon_filter']);
      if ($args['taxon_filter_field']!=='taxon_group' && count($filterLines)===1) {
        // The form is configured for filtering by taxon name or meaning id. If there is only one specified then the form
        // cannot display a species checklist, as there is no point. So, convert our preferred taxon name or meaning ID to find the 
        // preferred taxa_taxon_list_id from the selected checklist, and then output a hidden ID.
        if (empty($args['list_id']))
          throw new exception(lang::get('Please configure the Initial Species List parameter to define which list the species to record is selected from.'));
        $filter = array(
          'preferred'=>'t',
          'taxon_list_id'=>$args['list_id']
        );
        if ($args['taxon_filter_field']=='preferred_name')
          $filter['taxon']=$filterLines[0];
        else
          $filter[$args['taxon_filter_field']]=$filterLines[0];
        $options = array(
          'table' => 'taxa_taxon_list',
          'extraParams' => $auth['read'] + $filter
        );
        $response =data_entry_helper::get_population_data($options);
        if (count($response)===0)
          throw new exception(lang::get('Failed to find the single species that this form is setup to record in the defined list.'));
        if (count($response)>1)
          throw new exception(lang::get('This form is setup for single species recording, but more than one species with the same name exists in the list.'));          
        return '<input type="hidden" name="occurrence:taxa_taxon_list_id" value="'.$response[0]['id']."\"/>\n";
      }
    }
    call_user_func(array(self::$called_class, 'build_grid_autocomplete_function'), $args);
    if (call_user_func(array(self::$called_class, 'getGridMode'), $args)) {      
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
          'language' => iform_lang_iso_639_2($user->lang), // used for termlists in attributes
          'cacheLookup' => isset($args['cache_lookup']) && $args['cache_lookup'],
          'speciesNameFilterMode' => self::getSpeciesNameFilterMode($args), 
          'userControlsTaxonFilter' => isset($args['user_controls_taxon_filter']) ? $args['user_controls_taxon_filter'] : false
      ), $options);
      if ($groups=hostsite_get_user_field('taxon_groups')) {
        $species_ctrl_opts['usersPreferredGroups'] = unserialize($groups);
      }
      if ($args['extra_list_id']) $species_ctrl_opts['lookupListId']=$args['extra_list_id'];
      if (!empty($args['taxon_filter_field']) && !empty($args['taxon_filter'])) {
        $species_ctrl_opts['taxonFilterField']=$args['taxon_filter_field'];
        $species_ctrl_opts['taxonFilter']=$filterLines;
      }
      if (isset($args['col_widths']) && $args['col_widths']) $species_ctrl_opts['colWidths']=explode(',', $args['col_widths']);
      call_user_func(array(self::$called_class, 'build_grid_taxon_label_function'), $args);
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
      if (!empty($args['taxon_filter_field']) && !empty($args['taxon_filter']))
        // filter the taxa available to record
        $query = array('in'=>array($args['taxon_filter_field'], helper_base::explode_lines($args['taxon_filter'])));
      else 
        $query = array();
      // Apply the species names filter to the single species picker control
      if (isset($args['species_names_filter'])) {
        $languageFieldName = isset($args['cache_lookup']) && $args['cache_lookup'] ? 'language_iso' : 'language';
        switch($args['species_names_filter']) {
          case 'preferred' :
            $extraParams += array('preferred'=>'t');
            break;
          case 'language' :
            if (isset($options['language'])) {
              $extraParams += array($languageFieldName=>$options['language']);
            } else {
              $extraParams += array($languageFieldName=>iform_lang_iso_639_2($user->lang));
            }
            break;
          case 'excludeSynonyms':
            $query['where'] = array("(preferred='t' OR $languageFieldName<>'lat')");
            break;
        }
      }
      if (count($query)) 
        $extraParams['query'] = json_encode($query);
      global $indicia_templates;
      $species_ctrl_opts=array_merge(array(
          'label'=>lang::get('occurrence:taxa_taxon_list_id'),
          'fieldname'=>'occurrence:taxa_taxon_list_id',
          'table'=>'taxa_taxon_list',
          'captionField'=>'taxon',
          'valueField'=>'id',
          'columns'=>2,
          'parentField'=>'parent_id',
          'extraParams'=>$extraParams,
          'blankText'=>'Please select',
          'formatFunction'=>$indicia_templates['format_species_autocomplete_fn']
      ), $options);
      if (isset($args['cache_lookup']) && $args['cache_lookup'])
        $species_ctrl_opts['extraParams']['view']='cache';
      // if using something other than an autocomplete, then set the caption template to include the appropriate names. Autocompletes
      // use a JS function instead.
      if ($args['species_ctrl']!=='autcomplete' && isset($args['species_include_both_names']) && $args['species_include_both_names']) {
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
   * Function to map from the species_names_filter argument to the speciesNamesFilterMode required by the 
   * checklist grid. For legacy reasons they don't quite match.
   */
  protected static function getSpeciesNameFilterMode($args) {
    if (isset($args['species_names_filter'])) {
      switch ($args['species_names_filter']) {
        case 'language':
          return 'currentLanguage';
        default:
          return $args['species_names_filter'];
      }
    }
    // default is no species name filter.
    return false;
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
  protected static function get_control_samplecomment($auth, $args, $tabalias, $options) {
    return data_entry_helper::textarea(array_merge(array(
      'fieldname'=>'sample:comment',
      'label'=>lang::get('Overall Comment')
    ), $options)); 
  }
  
  /**
   * Get the block of custom attributes at the species (occurrence) level
   */
  protected static function get_control_speciesattributes($auth, $args, $tabalias, $options) {
    if (!(call_user_func(array(self::$called_class, 'getGridMode'), $args))) {  
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
      $attributes = data_entry_helper::getAttributes($attrArgs, false);
      $defAttrOptions = array('extraParams'=>$auth['read']);
      $blockOptions = array();
      // look for options specific to each attribute
      foreach ($options as $option => $value) {
        // split the id of the option into the control name and option name.
        $optionId = explode('|', $option);
        if (!isset($blockOptions[$optionId[0]])) $blockOptions[$optionId[0]]=array();
        $blockOptions[$optionId[0]][$optionId[1]] = $value;
      }
      $r = get_attribute_html($attributes, $args, $defAttrOptions, $tabAlias, $blockOptions);
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
  protected static function get_control_date($auth, $args, $tabalias, $options) {
    if (isset(data_entry_helper::$entity_to_load['sample:date']) && preg_match('/^(\d{4})/', data_entry_helper::$entity_to_load['sample:date'])) {
      // Date has 4 digit year first (ISO style) - convert date to expected output format
      // @todo The date format should be a global configurable option. It should also be applied to reloading of custom date attributes.
      $d = new DateTime(data_entry_helper::$entity_to_load['sample:date']);
      data_entry_helper::$entity_to_load['sample:date'] = $d->format('d/m/Y');
    }
    if($args['language'] != 'en')
      data_entry_helper::add_resource('jquery_ui_'.$args['language']); // this will autoload the jquery_ui resource. The date_picker does not have access to the args.
    if(lang::get('LANG_Date_Explanation')!='LANG_Date_Explanation')
      data_entry_helper::$javascript .= "\njQuery('[name=sample\\:date]').next().after('<span class=\"date-explanation\"> ".lang::get('LANG_Date_Explanation')."</span>');\n";
    return data_entry_helper::date_picker(array_merge(array(
      'label'=>lang::get('LANG_Date'),
      'fieldname'=>'sample:date',
      'default' => isset($args['defaults']['sample:date']) ? $args['defaults']['sample:date'] : ''
    ), $options));
  }
  
  /** 
   * Get the location control as an autocomplete.
   */
  protected static function get_control_locationautocomplete($auth, $args, $tabalias, $options) {
    $location_list_args=array_merge(array(
        'label'=>lang::get('LANG_Location_Label'),
        'extraParams'=>array_merge(array('orderby'=>'name', 'website_id'=>$args['website_id']), $auth['read'])
    ), $options);
    if (isset($args['users_manage_own_sites']) && $args['users_manage_own_sites']) {
      $userId = hostsite_get_user_field('indicia_user_id');
      if (!empty($userId))
        $location_list_args['extraParams']['created_by_id']=$userId;
      $location_list_args['extraParams']['view']='detail';
      $location_list_args['allowCreate']=true;
    }
    return data_entry_helper::location_autocomplete($location_list_args);
  }
  
  /** 
   * Get the location control as a select dropdown.
   */
  protected static function get_control_locationselect($auth, $args, $tabalias, $options) {
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
  protected static function get_control_locationname($auth, $args, $tabalias, $options) {
    return data_entry_helper::text_input(array_merge(array(
      'label' => lang::get('LANG_Location_Name'),
      'fieldname' => 'sample:location_name',
      'class' => 'control-width-5'
    ), $options));
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
  protected static function get_control_recordstatus($auth, $args) {    
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
        $userIdAttr = $attr['attributeId'];
        break;
      }
    }
    if ($user->uid===0) {
      // Return a login link that takes you back to this form when done.
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">login</a> to the website.');
    }
    if (!isset($userIdAttr)) {
      return lang::get('This form is configured to show the user a grid of their existing records which they can add to or edit. To do this, the form requires that '.
          'it must be used with a survey that includes the CMS User ID attribute in the list of attributes configured for the survey on the warehouse. This allows records to '.
          'be tagged against the user. Alternatively you can tick the box "Skip initial grid of data" in the "User Interface" section of the Edit page for the form.');
    }
    if(method_exists(self::$called_class, 'getSampleListGridPreamble'))
      $r = call_user_func(array(self::$called_class, 'getSampleListGridPreamble'));
    else
      $r = '';
    $r .= data_entry_helper::report_grid(array(
      'id' => 'samples-grid',
      'dataSource' => $args['grid_report'],
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => call_user_func(array(self::$called_class, 'getReportActions')),
      'itemsPerPage' =>(isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 10),
      'autoParamsForm' => true,
      'extraParams' => array(
        'survey_id'=>$args['survey_id'], 
        'userID_attr_id'=>$userIdAttr,
        'userID'=>$user->uid
      )
    ));    
    $r .= '<form>';    
    if (isset($args['multiple_occurrence_mode']) && $args['multiple_occurrence_mode']=='either') {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Single').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'new')).'\'">';
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Grid').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'new&gridmode')).'\'">';
    } else {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'new')).'\'">';    
    }
    $r .= '</form>';
    return $r;
  }
  
  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the 
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
  protected function getArgDefaults($args) {
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
    if (!isset($args['grid_report']))
      $args['grid_report'] = 'reports_for_prebuilt_forms/simple_sample_list_1';
    return $args;      
  }

  protected function getReportActions() {
    return array(array('display' => 'Actions', 'actions' => 
        array(array('caption' => lang::get('Edit'), 'url'=>'{currentUrl}', 'urlParams'=>array('sample_id'=>'{sample_id}','occurrence_id'=>'{occurrence_id}')))));
  }
  
}

