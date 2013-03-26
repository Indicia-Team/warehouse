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
   * The list of attributes loaded for occurrences. Keep a class level variable, so that we can track the ones we have already
   * emitted into the form globally.
   * @var array
   */
  protected static $occAttrs;

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
              'defined in the Drupal Profile module which must be enabled to use this feature. Applies whether fields are shown or not.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface',
          // Note that we can't test Drupal module availability whilst loading this form for a new iform, using Ajax. So 
          // in this case we show the control even though it is not usable (the help text explains the module requirement).          
          'visible' => !function_exists('module_exists') || module_exists('profile')
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
                "&nbsp;&nbsp;<strong>[species map]</strong> - a species grid or input control: this is the same as the species control, but the sample is broken down ".
        		"into subsamples, each of which has its own location picked from the map. Only the part of the species grid which is being added to or modified at the ".
        		"time is displayed. This control should be placed after the map control, with which it integrates. Species recording must be set to a List (grid mode) rather than single entry.<br/>".
                "&nbsp;&nbsp;<strong>[species map summary]</strong> - a read only grid showing a summary of the data entered using the species map control.<br/>".
                "&nbsp;&nbsp;<strong>[species attributes]</strong> - any custom attributes for the occurrence, if not using the grid. Also includes a file upload ".
                    "box and sensitivity input control if relevant. The attrubutes @resizeWidth and @resizeHeight can specified on subsequent lines, otherwise they ".
                    "default to 1600. Note that this control provides a quick way to output all occurrence custom attributes plus photo and sensitivity input controls. ".
                    "For finer control of the output, see the [occAttr:n], [photos] and [sensitivity] controls.<br/>".
                "&nbsp;&nbsp;<strong>[date]</strong> - a sample must always have a date.<br/>".
                "&nbsp;&nbsp;<strong>[map]</strong> - a map that links to the spatial reference and location select/autocomplete controls<br/>".
                "&nbsp;&nbsp;<strong>[spatial reference]</strong> - a sample must always have a spatial reference.<br/>".
                "&nbsp;&nbsp;<strong>[location name]</strong> - a text box to enter a place name.<br/>".
                "&nbsp;&nbsp;<strong>[location autocomplete]</strong> - an autocomplete control for picking a stored location. A spatial reference is still required.<br/>".
                "&nbsp;&nbsp;<strong>[location select]</strong> - a select control for picking a stored location. A spatial reference is still required.<br/>".
                "&nbsp;&nbsp;<strong>[location map]</strong> - combines location select, map and spatial reference controls for recording only at stored locations.<br/>".
                "&nbsp;&nbsp;<strong>[photos]</strong> - use when in single record entry mode to provide a control for uploading occurrence photos. Alternatively use the ".
                    "[species attributes] control to output all input controls for the species automatically. The [photos] control overrides the setting <strong>Occurrence Images</strong>.<br/>".
                "&nbsp;&nbsp;<strong>[place search]</strong> - zooms the map to the entered location.<br/>".
                "&nbsp;&nbsp;<strong>[recorder names]</strong> - a text box for names. The logged-in user's id is always stored with the record.<br/>".
                "&nbsp;&nbsp;<strong>[record status]</strong> - allow recorder to mark record as in progress or complete<br/>".
                "&nbsp;&nbsp;<strong>[sample comment]</strong> - a text box for sample level comment. (Each occurrence may also have a comment.) <br/>".
                "&nbsp;&nbsp;<strong>[sample photo]</strong>. - a photo upload for sample level images. (Each occurrence may also have photos.) <br/>".
                "&nbsp;&nbsp;<strong>[sensitivity]</strong> - outputs a control for setting record sensitivity and the public viewing precision. This control will also output ".
                    "any other occurrence custom attributes which are on an outer block called Sensitivity. Any such attributes will then be disabled when the record is ".
                    "not sensitive, so they can be used to capture information that only relates to sensitive records.<br/>".
                "&nbsp;&nbsp;<strong>[zero abundance]</strong>. - use when in single record entry mode to provide a checkbox for specifying negative records.<br/>".
            "<strong>@option=value</strong> on the line(s) following any control allows you to override one of the options passed to the control. The options ".
            "available depend on the control. For example @label=Abundance would set the untranslated label of a control to Abundance. Where the ".
            "option value is an array, use valid JSON to encode the value. For example an array of strings could be passed as @occAttrClasses=[\"class1\",\"class2\"] ".
            "or a keyed array as @extraParams={\"preferred\":\"true\",\"orderby\":\"term\"}. " .
            "Other common options include helpText (set to a piece of additional text to display alongside the control) and class (to add css ".
            "classes to the control such as control-width-3). <br/>".
            "<strong>[*]</strong> is used to make a placeholder for putting any custom attributes that should be inserted into the current tab. When this option is ".
            "used, you can change any of the control options for an individual custom attribute control by putting @control|option=value on the subsequent line(s). ".
            "For example, if a control is for smpAttr:4 then you can update it's label by specifying @smpAttr:4|label=New Label on the line after the [*]. ".
            "You can also set an option for all the controls output by the [*] block by specifying @option=value as for non-custom controls, e.g. ".
            "set @label=My label to define the same label for all controls in this custom attribute block. ". 
            "You can define the value for a control using the standard replacement tokens for user data, namely {user_id}, {username}, {email} and {profile_*}; ".
            "replace * in the latter to construct an existing profile field name. For example you could set the default value of an email input using @smpAttr:n|default={email} ".
            "where n is the attribute ID.<br/>".
            "<strong>[smpAttr:<i>n</i>]</strong> is used to insert a particular custom sample attribute identified by its ID number<br/>".
            "<strong>[occAttr:<i>n</i>]</strong> is used to insert a particular custom occurrence attribute identified by its ID number when inputting single records at a time. ".
            "Or use [species attributes] to output the whole lot.<br/>".
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
              'field or occurrence_id field for linking to the data entry form. As a starting point, try ' .
              'reports_for_prebuilt_forms/dynamic_sample_occurrence_samples for a list of samples.',
          'type'=>'string',
          'group' => 'User Interface',
          'default' => 'reports_for_prebuilt_forms/dynamic_sample_occurrence_samples'
        ),
        array(
          'name' => 'verification_panel',
          'caption' => 'Include verification precheck button',
          'description' => 'Include a "Precheck my records" button which allows the user to request an automated '.
              'verification check to be run against their records before submission, enabling them to provide '.
              'additional information for any records which are likely to be contentious.',
          'type'=>'checkbox',
          'group' => 'User Interface',
          'default' => false,
          'required' => false
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
          'name'=>'sub_species_column',
          'caption'=>'Include sub-species in a separate column?',
          'description'=>'If checked and doing grid based data entry letting the recorder add species they choose to '.
            'the bottom of the grid, sub-species will be displayed in a separate column so the recorder picks the species '.
            'first then the subspecies. The species checklist must be configured so that species are parents of the subspecies. '.
            'This setting also forces the Cache Lookups option therefore it requires the Cache Builder module to be installed '.
            'on the Indicia warehouse.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Species'
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
          'name'=>'occurrence_sensitivity',
          'caption'=>'Occurrence Sensitivity',
          'description'=>'Should a control be present for sensitivity of each record?  This applies when using grid entry mode or when using the [species attributes] control '.
              'to output all the occurrence related input controls automatically. The [sensitivity] control outputs a sensitivity input control independently of this setting.',
          'type'=>'boolean',
          'required' => false,
          'default'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'occurrence_images',
          'caption'=>'Occurrence Images',
          'description'=>'Should occurrences allow images to be uploaded? This applies when using grid entry mode or when using the [species attributes] control '.
              'to output all the occurrence related input controls automatically. The [photos] control outputs a photos input control independently of this setting.',
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
            'taxon_group' => 'Taxon group title',
            'external_key' => 'Taxon external key'
              
          ),
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'use_url_taxon_parameter',
          'caption'=>'Use URL taxon parameter',
          'description'=>'Use a URL parameter called taxon to get the filter? Case sensitive. Uses the "Field used to filter taxa" setting to control '.
            'what is being filtered against, e.g. &taxon=Passer+domesticus,Turdus+merula',
          'type'=>'boolean',
          'required' => false,
          'default'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'taxon_filter',
          'caption'=>'Taxon filter items',
          'description'=>'Taxa can be filtered by entering values into this box. '. 
              'Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group. '.
              'If you provide a single taxon preferred name, taxon meaning ID or external key in this box, then the form is set up for recording just this single '.
              'species. Therefore there will be no species picker control or input grid, and the form will always operate in the single record, non-grid mode. '.
              'You may like to include information about what is being recorded in the body text for the page or by using the '.
              '\'Include a message stating which species you are recording in single species mode?\' checkbox to automatically add a message to the screen.'.
              'You may also want to configure the User Interface section of the form\'s Form Structure to move the [species] and [species] controls '.
              'to a different tab and remove the =species= tab, especially if there are no other occurrence attributes on the form.'.
              'The \'Use URL taxon parameter\' option can be used to override the filters specified here.',
          'type' => 'textarea',
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'single_species_message',
          'caption'=>'Include a message stating which species you are recording in single species mode?',
          'description'=>'Message which displays the species you are recording against in single species mode. When selected, this will automatically be displayed where applicable.',
          'type'=>'boolean',
          'required' => false,
          'default'=>false,
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
        array(
          'name'=>'edit_permission',
          'caption'=>'Permission required for editing other people\'s data',
          'description'=>'Set to the name of a permission which is required in order to be able to edit other people\'s data.',
          'type'=>'text_input',
          'required'=>false,
          'default'=>'indicia data admin'
        ),
      )
    );
    return $retVal;
  }

  /**
   * Determine whether to show a gird of existing records or a form for either adding a new record, editing an existing one,
   * or creating a new record from an existing one.
   * @param array $args iform parameters.
   * @param object $node node being shown.
   * @return const The mode [MODE_GRID|MODE_NEW|MODE_EXISTING|MODE_CLONE].
   */
  protected static function getMode($args, $node) {
    // Default to mode MODE_GRID or MODE_NEW depending on no_grid parameter
    $mode = (isset($args['no_grid']) && $args['no_grid']) ? self::MODE_NEW : self::MODE_GRID;
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
        $mode = self::MODE_EXISTING;
      } // else valid save, so go back to gridview: default mode 0
    }
    if (array_key_exists('sample_id', $_GET) && $_GET['sample_id']!='{sample_id}'){
      $mode = self::MODE_EXISTING;
      self::$loadedSampleId = $_GET['sample_id'];
    }
    if (array_key_exists('occurrence_id', $_GET) && $_GET['occurrence_id']!='{occurrence_id}'){
      $mode = self::MODE_EXISTING;
      self::$loadedOccurrenceId = $_GET['occurrence_id'];
      self::$occurrenceIds = array(self::$loadedOccurrenceId);
    }
    if ($mode != self::MODE_EXISTING && array_key_exists('new', $_GET)){
      $mode = self::MODE_NEW;
      data_entry_helper::$entity_to_load = array();
    }
    if ($mode == self::MODE_EXISTING && array_key_exists('new', $_GET)){
      $mode = self::MODE_CLONE;
    }
    return $mode;
  }

  /**
   * Construct a grid of existing records.
   * @param array $args iform parameters.
   * @param object $node node being shown.
   * @param array $auth authentication tokens for accessing the warehouse.
   * @return string HTML for grid.
   */
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

    // Add in a tab for the allocation of locations if this option was selected
    if($args['includeLocTools'] && function_exists('iform_loctools_checkaccess') && iform_loctools_checkaccess($node,'admin')){
      $tabs['#setLocations'] = lang::get('LANG_Allocate_Locations');
    }

    // An option for derived classes to add in extra tabs
    if (method_exists(self::$called_class, 'getExtraGridModeTabs')) {
      $extraTabs = call_user_func(array(self::$called_class, 'getExtraGridModeTabs'), false, $auth['read'], $args, $attributes);
      if(is_array($extraTabs))
        $tabs = $tabs + $extraTabs;
    }

    // Only actually need to show tabs if there is more than one
    if(count($tabs) > 1){
      $r .= "<div id=\"controls\">".(data_entry_helper::enable_tabs(array('divId'=>'controls','active'=>'#sampleList')))."<div id=\"temp\"></div>";
      $r .= data_entry_helper::tab_header(array('tabs'=>$tabs));
    }

    // Here is where we get the table of samples
    $r .= "<div id=\"sampleList\">".call_user_func(array(self::$called_class, 'getSampleListGrid'), $args, $node, $auth, $attributes)."</div>";

    // Add content to the Allocate Locations tab if this option was selected
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

    // Add content to extra tabs that derived classes may have added
    if (method_exists(self::$called_class, 'getExtraGridModeTabs')) {
      $r .= call_user_func(array(self::$called_class, 'getExtraGridModeTabs'), true, $auth['read'], $args, $attributes);
    }

    // Close tabs div if present
    if(count($tabs) > 1){
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
    // Ensure that if we are used to load a different survey's data, then we get the correct survey attributes. We can change args
    // because the caller passes by reference.
    $args['survey_id']=data_entry_helper::$entity_to_load['sample:survey_id'];
    $args['sample_method_id']=data_entry_helper::$entity_to_load['sample:sample_method_id'];
    // enforce that people only access their own data, unless explicitly have permissions
    $editor = !empty($args['edit_permission']) && function_exists('user_access') && user_access($args['edit_permission']);
    if (!$editor && function_exists('hostsite_get_user_field') &&
        data_entry_helper::$entity_to_load['sample:created_by_id'] !== 1 &&
        data_entry_helper::$entity_to_load['sample:created_by_id'] !== hostsite_get_user_field('indicia_user_id'))
      throw new exception(lang::get('Attempt to access a record you did not create'));
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

  /* Overrides function in class iform_dynamic.
   * 
   * This function removes ID information from the entity_to_load, fooling the 
   * system in to building a form for a new record with default values from the entity_to_load.
   * This feels like it could be easily broken by changes to how the form is built, 
   * particularly the species checklist.
   * I would have preferred to modify the completed html but I perceived a problem with
   * multi-value inputs and knowing whether to replace e.g. smpAttr:123:12345 with
   * smpAttr:123 or smpAttr:123[]
   * 
   * At the time of calling, the entity_to_load contains the sample and the 
   * $attributes array contains the sample attributes. No occurrences are loaded.
   * This function calls preload_species_checklist_occurrences which loads the 
   * occurrence and occurrence attribute information in to the entity_to_load. Having
   * modified the occurrence information in entity_to_load the species checklist must 
   * be called with option['useLoadedExistingRecords'] = true so that the modifications
   * are not overwritten
   */
  protected static function cloneEntity($args, $auth, &$attributes) {
    // First modify the sample attribute information in the $attributes array.
    // Set the sample attribute fieldnames as for a new record
    foreach($attributes as $attributeKey => $attributeValue){
      if ($attributeValue['multi_value'] == 't') {
        // Set the attribute fieldname to the attribute id plus brackets for multi-value attributes
       $attributes[$attributeKey]['fieldname'] = $attributeValue['id'] . '[]';
       foreach($attributeValue['default'] as $defaultKey => $defaultValue) {
         // Set the fieldname in the defaults array to the attribute id plus brackets as well
         $attributes[$attributeKey]['default'][$defaultKey]['fieldname'] = $attributeValue['id'] . '[]';
       }
      } else {
        // Set the attribute fieldname to the attribute id for single values
        $attributes[$attributeKey]['fieldname'] = $attributeValue['id'];
      }
    }
    
    // Now load the occurrences and their attributes.
    $loadImages = $args['occurrence_images'];
    $subSamples = array();
    data_entry_helper::preload_species_checklist_occurrences(data_entry_helper::$entity_to_load['sample:id'], 
              $auth['read'], $loadImages, array(), $subSamples, false);
    // If using a species grid $entity_to_load will now contain elements in the form
    //  sc:row_num:occ_id:occurrence:field_name
    //  sc:row_num:occ_id:present
    //  sc:row_num:occ_id:occAttr:occAttr_id:attrValue_id
    // We are going to strip out the occ_id and the attrValue_id
    $keysToDelete = array();
    $elementsToAdd = array();
    foreach(data_entry_helper::$entity_to_load as $key => $value) {
      $parts = explode(':', $key);
      // Is this an occurrence?
      if ($parts[0] === 'sc') {
        // We'll be deleting this
        $keysToDelete[] = $key;
        // And replacing it
        $parts[2] = '';
        if (count($parts) == 6) unset($parts[5]);
        $keyToCreate = implode(':', $parts);
        $elementsToAdd[$keyToCreate] = $value;
      }
    }
    foreach($keysToDelete as $key) {
      unset(data_entry_helper::$entity_to_load[$key]);
    }
    data_entry_helper::$entity_to_load = array_merge(data_entry_helper::$entity_to_load, $elementsToAdd);
    
    // Unset the sample and occurrence id from entitiy_to_load as for a new record.
    unset(data_entry_helper::$entity_to_load['sample:id']);
    unset(data_entry_helper::$entity_to_load['occurrence:id']);
    
}

  protected static function getFirstTabAdditionalContent($args, $auth, &$attributes) {
    // Get authorisation tokens to update the Warehouse, plus any other hidden data.
    $r = $auth['write'].
          "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n".
          "<input type=\"hidden\" id=\"survey_id\" name=\"survey_id\" value=\"".$args['survey_id']."\" />\n";
    if (!empty($args['sample_method_id'])) {
      $r .= '<input type="hidden" name="sample:sample_method_id" value="'.$args['sample_method_id'].'"/>' . PHP_EOL;
    }
    if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      $r .= '<input type="hidden" id="sample:id" name="sample:id" value="' . data_entry_helper::$entity_to_load['sample:id'] . '" />' . PHP_EOL;
    }
    if (isset(data_entry_helper::$entity_to_load['occurrence:id'])) {
      $r .= '<input type="hidden" id="occurrence:id" name="occurrence:id" value="' . data_entry_helper::$entity_to_load['occurrence:id'] . '" />' . PHP_EOL;
    }
    // Check if Record Status is included as a control. If not, then add it as a hidden.
    $arr = helper_base::explode_lines($args['structure']);
    if (!in_array('[record status]', $arr)) {
      $value = isset($args['defaults']['occurrence:record_status']) ? $args['defaults']['occurrence:record_status'] : 'C';
      $r .= '<input type="hidden" id="occurrence:record_status" name="occurrence:record_status" value="' . $value . '" />' . PHP_EOL;
    }
    $r .= get_user_profile_hidden_inputs($attributes, $args, isset(data_entry_helper::$entity_to_load['sample:id']), $auth['read']);
    return $r;
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
        data_entry_helper::$javascript .= "hook_species_checklist_new_row.push(function(data) {
  if (data.preferred_name=='$tokens[0]') {
    $('#click-$fieldset').fancybox({showCloseButton: false}).trigger('click');
  }
});\n";
      }
    }
    return $r;
  }

  /**
   * Get the map control.
   */
  protected static function get_control_map($auth, $args, $tabAlias, $options) {
    $options = array_merge(
      iform_map_get_map_options($args, $auth['read']),
      $options
    );
    if (isset(data_entry_helper::$entity_to_load['sample:geom'])) {
      $options['initialFeatureWkt'] = data_entry_helper::$entity_to_load['sample:wkt'];
    }
    if ($args['interface']!=='one_page')
      $options['tabDiv'] = $tabAlias;
    $olOptions = iform_map_get_ol_options($args);
    if (!isset($options['standardControls']))
      $options['standardControls']=array('layerSwitcher','panZoom');
    return data_entry_helper::map_panel($options, $olOptions);
  }

  /**
   * Get the control for map based species input, assumed to be multiple entry: ie a grid. Can be single species though.
   * Uses the normal species grid, so all options that apply to that, apply to this.
   */
  protected static function get_control_speciesmap($auth, $args, $tabAlias, $options) {
  	// The ID must be done here so it can be accessed by both the species grid and the buttons.
  	$code = rand(0,1000);
  	$defaults = array('id' => 'species-grid-'.$code, buttonsId => 'species-grid-buttons-'.$code);
  	$options = array_merge($defaults, $options);
  	
  	$gridmode = call_user_func(array(self::$called_class, 'getGridMode'), $args);
  	if(!$gridmode)
  		return "<b>The SpeciesMap control must be used in gridmode.</b><br/>";
  	// Force a new option
  	$options['speciesControlToUseSubSamples'] = true;
  	$options['base_url'] = data_entry_helper::$base_url;
  	if (!isset($args['cache_lookup']) || ($args['species_ctrl'] !== 'autocomplete'))
  		$args['cache_lookup']=false; // default for old form configurations or when not using an autocomplete
  	//The filter can be a URL or on the edit tab, so do the processing to work out the filter to use
  	$filterLines = self::get_species_filter($args);
  	// store in the argument so that it can be used elsewhere
  	$args['taxon_filter'] = implode("\n", $filterLines);
  	//Single species mode only ever applies if we have supplied only one filter species and we aren't in taxon group mode
  	if ($args['taxon_filter_field']!=='taxon_group' && count($filterLines)===1) {
  		$response = self::get_single_species_data($auth, $args, $filterLines);
  		//Optional message to display the single species on the page
  		if ($args['single_species_message'])
  			self::$singleSpeciesName=$response[0]['taxon'];
  		if (count($response)==0)
  			//if the response is empty there is no matching taxon, so clear the filter as we can try and display the checklist with all data
  			$args['taxon_filter']='';
  		elseif (count($response)==1)
  		//Keep the id of the single species in a hidden field for processing if in single species mode
  		// TBD
  		return '<input type="hidden" name="occurrence:taxa_taxon_list_id" value="'.$response[0]['id']."\"/>\n";
  	}
  	$extraParams = $auth['read'];
  	call_user_func(array(self::$called_class, 'build_grid_autocomplete_function'), $args);
  	// the imp-sref & imp-geom are within the dialog so it is updated.
  	$speciesCtrl = self::get_control_species_checklist($auth, $args, $extraParams, $options); // this preloads the subsample data.
  	$list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    $systems=array();
    foreach($list as $system) {
      $systems[$system] = lang::get($system);
    }
  	if (count($systems) == 1) {
      // Hidden field for the system
      $keys = array_keys($systems);
      $system = '<input type="hidden" id="imp-sref-system" name="sample:entered_sref_system" value="'.$keys[0].'" />';
    } else {
      $options['systems']=$systems;
      $system = data_entry_helper::sref_system_select($options);
    }
  	return '<div id="'.$options['id'].'-container" style="display: none">'.
  	       '<input type="hidden" name="sample:entered_sref" value="'.data_entry_helper::check_default_value('sample:entered_sref', '').'">'.
  	       '<input type="hidden" name="sample:geom" value="'.data_entry_helper::check_default_value('sample:geom', '').'" >'.
  	       $system.
  	       '<div id="'.$options['id'].'-blocks">'.
  	       self::get_control_speciesmap_controls($options).
  	       '</div>'.
  	       '<input type="hidden" value="true" name="speciesgridmapmode" />'.
           $speciesCtrl.
  			'</div>';
  }

  /**
   * Get the control for the summary for the map based species input.
   */
  protected static function get_control_speciesmapsummary($auth, $args, $tabAlias, $options) {
    // don't have access to the id for the species map control, and visa versa (has a random element)
    // have to use a clas to identify it.
  	return '<div class="control_speciesmapsummary"><table class="ui-widget ui-widget-content species-grid-summary"><thead class="ui-widget-header"/><tbody/></table></div>';
  }
  
  /* Set up the control JS and also return the existing data subsample blocks */
  protected static function get_control_speciesmap_controls($options){
    $langStrings = array('InitMessage' => lang::get("Click on a button to choose what you would like to do."),
    		'AddLabel' => lang::get("Add grid ref"),
            'AddMessage' => lang::get("Please click on the map to add data."),
            'AddDataMessage' => lang::get("Please enter all the species records for this grid reference. When you have finished, click the Finish button: this will return you to the map where you may choose another grid reference to enter data for."),
    		'ConfirmAddTitle' => lang::get("Accept this grid square?"),
    		'ConfirmAddText' => lang::get("Do you wish to add data for this grid reference?"),
    		
    		'MoveLabel' => lang::get("Move grid ref"),
            'MoveMessage1' => lang::get("Please select the square on the map you wish to move."),
            'MoveMessage2' => lang::get("Please click on the map to choose the new position. Press the Cancel button to choose another square to move instead."),
            'ConfirmMove1Title' => lang::get("Confirm Move Grid Square"),
            'ConfirmMove1Text' => lang::get("Are you sure you wish to move the {OLD} grid square?"),
    		'ConfirmMove2Title' => lang::get("Confirm New Position"),
    		'ConfirmMove2Text' => lang::get("Are you sure you wish to move the data held for grid square {OLD} to the following new location?"),
    		
    		'ModifyLabel' => lang::get("Modify grid ref data"),
            'ModifyMessage1' => lang::get("Please select the square on the map you wish to change the recorded data for."),
    		'ModifyMessage2' => lang::get("Change (or add to) the data for this grid reference. When you have finished, click the Finish button: this will return you to the map where you may choose another grid reference to change."),
    		
    		'DeleteLabel' => lang::get("Delete grid ref"),
            'DeleteMessage' => lang::get("Please select the square on the map you wish to delete."),
    		'ConfirmDeleteTitle' => lang::get("Confirm Delete Grid Square"),
    		'ConfirmDeleteText' => lang::get("Are you sure you wish to delete all the data for the {OLD} grid square?"),
    		
    		'CancelLabel' => lang::get("CANCEL"),
    		'FinishLabel' => lang::get("Finish"),
    		'Yes' => lang::get("Yes"),
    		'No' => lang::get("No"),
    		'SRefLabel' => lang::get('LANG_SRef_Label'));
    // make sure we load the JS.
    data_entry_helper::add_resource('control_speciesmap_controls');
    data_entry_helper::$javascript .= "control_speciesmap_addcontrols(".json_encode($options).",".json_encode($langStrings).");\n";
    $blocks = "";
    foreach(data_entry_helper::$entity_to_load as $key => $value){
      $a = explode(':', $key, 4);
      if(count($a)==4 && $a[0] == 'sc' && $a[3] == 'sample:entered_sref'){
      	$geomKey = $a[0].':'.$a[1].':'.$a[2].':sample:geom';
      	$idKey = $a[0].':'.$a[1].':'.$a[2].':sample:id';
      	$deletedKey = $a[0].':'.$a[1].':'.$a[2].':sample:deleted';
      	$blocks .= '<div id="scm-'.$a[1].'-block" class="scm-block">'.
                  '<label>'.lang::get('LANG_SRef_Label').'</label>'.
                  '<input type="text" value="'.$value.'" readonly="readonly" name="'.$key.'">'.
                  '<input type="hidden" value="'.data_entry_helper::$entity_to_load[$geomKey].'" name="'.$geomKey.'">'.
                  '<input type="hidden" value="'.(isset(data_entry_helper::$entity_to_load[$deletedKey]) ? data_entry_helper::$entity_to_load[$deletedKey] : 'f').'" name="'.$deletedKey.'">'.
                  (isset(data_entry_helper::$entity_to_load[$idKey]) ? '<input type="hidden" value="'.data_entry_helper::$entity_to_load[$idKey].'" name="'.$idKey.'">' : '').
                  '</div>';
      }
    }
    return $blocks;
  }
  /**
   * The species filter can be taken from the edit tab or overridden by a URL filter.
   * This method determines the filter to be used.
   * @param array $args Form arguments
   * @return array List of items to filter against, e.g. species names or meaning IDs.
   */  
  protected static function get_species_filter($args) {
    // we must have a filter field specified in order to apply a filter
    if (!empty($args['taxon_filter_field'])) {
      // if URL params are enabled and we have one, then this is the top priority filter to apply
      if (!empty($_GET['taxon']) && $args['use_url_taxon_parameter'])  
        // convert commas to newline, so url provided filters are the same format as those
        // on the edit tab, also allowing for url encoding.
        return explode(',', urldecode($_GET['taxon']));
      elseif (!empty($args['taxon_filter']))
        // filter is provided on the edit tab
        return helper_base::explode_lines($args['taxon_filter']);
    }
    // default - no filter to apply
    return array();
  }
  
  /**
   * Get the species data for the page in single species mode
   */
  protected static function get_single_species_data($auth, $args, $filterLines) {
    //The form is configured for filtering by taxon name, meaning id or external key. If there is only one specified, then the form
    //cannot display a species checklist, as there is no point. So, convert our preferred taxon name, meaning ID or external_key to find the 
    //preferred taxa_taxon_list_id from the selected checklist
    if (empty($args['list_id']))
      throw new exception(lang::get('Please configure the Initial Species List parameter to define which list the species to record is selected from.'));
    $filter = array(
      'preferred'=>'t',
      'taxon_list_id'=>$args['list_id']
    );
    if ($args['taxon_filter_field']=='preferred_name') {
      $filter['taxon']=$filterLines[0];
    } else {
      $filter[$args['taxon_filter_field']]=$filterLines[0];
    }
    $options = array(
      'table' => 'taxa_taxon_list',
      'extraParams' => $auth['read'] + $filter
    );
    $response =data_entry_helper::get_population_data($options);
    // Call code that handles the error logs
    self::get_single_species_logging($auth, $args, $filterLines, $response);
    return $response;
  }
    
  /**
   * Error logging code for the page in single species mode
   */
  protected static function get_single_species_logging($auth, $args, $filterLines, $response) {
    //Go through each filter line and add commas between the values so it looks nice in the log
    $filters = implode(', ', $filterLines);
    //If only one filter is supplied but more than one match is found, we can't continue as we don't know which one to match against.
    if (count($response)>1 and count($filterLines)==1 and empty($response['error'])) {
      if (function_exists('watchdog')) {
        watchdog('indicia', 'Multiple matches have been found when using the filter \''.$args['taxon_filter_field'].'\'. '.
          'The filter was passed the following value(s)'.$filters);
        throw new exception(lang::get('This form is setup for single species recording, but more than one species matching the criteria exists in the list.'));
      }    
    }
    //If our filter returns nothing at all, we log it, we return string 'no matches' which the system then uses to clear the filter
    if (count($response)==0) {
      if (function_exists('watchdog')) 
        watchdog('missing sp.', 'No matches were found when using the filter \''.$args['taxon_filter_field'].'\'. '.
          'The filter was passed the following value(s)'.$filters); 
    }
  }
    
  /**
   * Get the control for species input, either a grid or a single species input control.
   */
  protected static function get_control_species($auth, $args, $tabAlias, $options) {
    $gridmode = call_user_func(array(self::$called_class, 'getGridMode'), $args);
    if (!isset($args['cache_lookup']) || ($args['species_ctrl'] !== 'autocomplete' && !$gridmode))
      $args['cache_lookup']=false; // default for old form configurations or when not using an autocomplete
    //The filter can be a URL or on the edit tab, so do the processing to work out the filter to use
    $filterLines = self::get_species_filter($args);
    // store in the argument so that it can be used elsewhere
    $args['taxon_filter'] = implode("\n", $filterLines);
    //Single species mode only ever applies if we have supplied only one filter species and we aren't in taxon group mode
    if ($args['taxon_filter_field']!=='taxon_group' && count($filterLines)===1) {
      $response = self::get_single_species_data($auth, $args, $filterLines);
      //Optional message to display the single species on the page
      if ($args['single_species_message']) 
        self::$singleSpeciesName=$response[0]['taxon'];
      if (count($response)==0)
        //if the response is empty there is no matching taxon, so clear the filter as we can try and display the checklist with all data
        $args['taxon_filter']='';
      elseif (count($response)==1)
        //Keep the id of the single species in a hidden field for processing if in single species mode
        return '<input type="hidden" name="occurrence:taxa_taxon_list_id" value="'.$response[0]['id']."\"/>\n";
    }
    $extraParams = $auth['read'];
    call_user_func(array(self::$called_class, 'build_grid_autocomplete_function'), $args);
    if ($gridmode)
      return self::get_control_species_checklist($auth, $args, $extraParams, $options);
    else
      return self::get_control_species_single($auth, $args, $extraParams, $options);
  }

    
    
  /**
   * Returns the species checklist input control.
   * @param array $auth Read authorisation tokens
   * @param array $args Form configuration
   * @param array $extraParams Extra parameters array, pre-configured with filters for taxa and name types.
   * @param array $options additional options for the control, e.g. those configured in the form structure.
   * @return HTML for the species_checklist control.
   */
  protected static function get_control_species_checklist($auth, $args, $extraParams, $options) {
    global $user;
    // Build the configuration options
    $species_ctrl_opts=array_merge(array(
        'listId'=>$args['list_id'],
        'label'=>lang::get('occurrence:taxa_taxon_list_id'),
        'columns'=>1,
        'extraParams'=>$extraParams,
        'survey_id'=>$args['survey_id'],
        'occurrenceComment'=>$args['occurrence_comment'],
        'occurrenceSensitivity'=>(isset($args['occurrence_sensitivity']) ? $args['occurrence_sensitivity'] : false),
        'occurrenceImages'=>$args['occurrence_images'],
        'PHPtaxonLabel' => true,
        'language' => iform_lang_iso_639_2(hostsite_get_user_field('language')), // used for termlists in attributes
        'cacheLookup' => $args['cache_lookup'],
        'speciesNameFilterMode' => self::getSpeciesNameFilterMode($args),
        'userControlsTaxonFilter' => isset($args['user_controls_taxon_filter']) ? $args['user_controls_taxon_filter'] : false,
        'subSpeciesColumn' => $args['sub_species_column']
    ), $options);
    if ($groups=hostsite_get_user_field('taxon_groups')) {
      $species_ctrl_opts['usersPreferredGroups'] = unserialize($groups);
    }
    if ($args['extra_list_id']) $species_ctrl_opts['lookupListId']=$args['extra_list_id'];
    //We only do the work to setup the filter if the user has specified a filter in the box
    if (!empty($args['taxon_filter_field']) && (!empty($args['taxon_filter']))) {
      $species_ctrl_opts['taxonFilterField']=$args['taxon_filter_field'];
      $filterLines = helper_base::explode_lines($args['taxon_filter']);
      $species_ctrl_opts['taxonFilter']=$filterLines;
    }
    if (isset($args['col_widths']) && $args['col_widths']) $species_ctrl_opts['colWidths']=explode(',', $args['col_widths']);
    call_user_func(array(self::$called_class, 'build_grid_taxon_label_function'), $args);
    if (self::$mode == self::MODE_CLONE)
      $species_ctrl_opts['useLoadedExistingRecords'] = true;
    // Start by outputting a hidden value that tells us we are using a grid when the data is posted,
    // then output the grid control
    return '<input type="hidden" value="true" name="gridmode" />'.
        data_entry_helper::species_checklist($species_ctrl_opts);
  }

  /**
   * Returns a control for picking a single species
   * @global type $indicia_templates
   * @param array $auth Read authorisation tokens
   * @param array $args Form configuration
   * @param array $extraParams Extra parameters pre-configured with taxon and taxon name type filters.
   * @param array $options additional options for the control, e.g. those configured in the form structure.
   * @return string HTML for the control.
   */
  protected static function get_control_species_single($auth, $args, $extraParams, $options) {
    if ($args['extra_list_id']=='')
      $extraParams['taxon_list_id'] = $args['list_id'];
    // @todo At the moment the controls do not support 2 lists. So use just the extra list. Should
    // update to support 2 lists. This is an edge case anyway.
    else
      $extraParams['taxon_list_id'] = empty($args['extra_list_id']) ? $args['list_id'] : $args['extra_list_id'];
    $options['speciesNameFilterMode'] = self::getSpeciesNameFilterMode($args);
    global $indicia_templates;
    $ctrl = $args['species_ctrl'] === 'autocomplete' ? 'species_autocomplete' : $args['species_ctrl'];
    $species_ctrl_opts=array_merge(array(
        'fieldname'=>'occurrence:taxa_taxon_list_id',
        'label'=>lang::get('occurrence:taxa_taxon_list_id'),
        'extraParams'=>$extraParams,
        'columns'=>2, // applies to radio buttons
        'parentField'=>'parent_id', // applies to tree browsers
        'blankText'=>lang::get('Please select'), // applies to selects
        'cacheLookup'=>$args['cache_lookup']
    ), $options);
    if (!empty($args['taxon_filter'])) {
      $species_ctrl_opts['taxonFilterField']=$args['taxon_filter_field']; // applies to autocompletes
      $species_ctrl_opts['taxonFilter']=helper_base::explode_lines($args['taxon_filter']); // applies to autocompletes
    }
    if ($ctrl!=='species_autocomplete') {
      // The species autocomplete has built in support for the species name filter.
      // For other controls we need to apply the species name filter to the params used for population
      if (!empty($species_ctrl_opts['taxonFilter']))
        $species_ctrl_opts['extraParams'] = array_merge($species_ctrl_opts['extraParams'], data_entry_helper::get_species_names_filter($species_ctrl_opts));
      // for controls which don't know how to do the lookup, we need to tell them
      $species_ctrl_opts = array_merge(array(
        'table'=>'taxa_taxon_list',
        'captionField'=>'taxon',
        'valueField'=>'id',
      ), $species_ctrl_opts);
    }
    // if using something other than an autocomplete, then set the caption template to include the appropriate names. Autocompletes
    // use a JS function instead.
    $db = data_entry_helper::get_species_lookup_db_definition($args['cache_lookup']);
    // get local vars for the array
    extract($db);
    if ($ctrl!=='autocomplete' && isset($args['species_include_both_names']) && $args['species_include_both_names']) {
      if ($args['species_names_filter']==='all')
        $indicia_templates['species_caption'] = '{'.$colTaxon.'}';
      elseif ($args['species_names_filter']==='language')
        $indicia_templates['species_caption'] = '{'.$colTaxon.'} - {'.$colPreferred.'}';
      else
        $indicia_templates['species_caption'] = '{'.$colTaxon.'} - {'.$colCommon.'}';
      $species_ctrl_opts['captionTemplate'] = 'species_caption';
    }
    if ($ctrl=='tree_browser') {
      // change the node template to include images
      $indicia_templates['tree_browser_node']='<div>'.
          '<img src="'.data_entry_helper::$base_url.'/upload/thumb-{image_path}" alt="Image of {caption}" width="80" /></div>'.
          '<span>{caption}</span>';
    }
    // Dynamically generate the species selection control required.
    return call_user_func(array('data_entry_helper', $ctrl), $species_ctrl_opts);
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
    // always include the searched name. In this JavaScript we need to behave slightly differently
    // if using the cached as opposed to the standard versions of taxa_taxon_list.
    $db = data_entry_helper::get_species_lookup_db_definition($args['cache_lookup']);
    // get local vars for the array
    extract($db);

    $fn = "function(item) { \n".
        "  var r;\n".
        "  if (item.$colLanguage!==null && item.$colLanguage.toLowerCase()==='$valLatinLanguage') {\n".
        "    r = '<em>'+item.$colTaxon+'</em>';\n".
        "  } else {\n".
        "    r = item.$colTaxon;\n".
        "  }\n";
    // This bit optionally adds '- common' or '- latin' depending on what was being searched
    if (isset($args['species_include_both_names']) && $args['species_include_both_names']) {
      $fn .= "  if (item.preferred==='t' && item.$colCommon!=item.$colTaxon && item.$colCommon) {\n".
        "    r += ' - ' + item.$colCommon;\n".
        "  } else if (item.preferred='f' && item.$colPreferred!=item.$colTaxon && item.$colPreferred) {\n".
        "    r += ' - <em>' + item.$colPreferred + '</em>';\n".
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
   * Build a JavaScript function  to format the display of existing taxa added to the species input grid
   * when an existing sample is loaded.
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
  protected static function get_control_samplecomment($auth, $args, $tabAlias, $options) {
    return data_entry_helper::textarea(array_merge(array(
      'fieldname'=>'sample:comment',
      'label'=>lang::get('Overall Comment')
    ), $options));
  }

  /**
   * Get the sample photo control
   */
  protected static function get_control_samplephoto($auth, $args, $tabAlias, $options) {
    return data_entry_helper::file_box(array_merge(array(
      'table'=>'sample_image',
      'caption'=>lang::get('Overall Photo')
    ), $options));
  }

  /**
   * Get the block of custom attributes at the species (occurrence) level
   */
  protected static function get_control_speciesattributes($auth, $args, $tabAlias, $options) {
    if (!(call_user_func(array(self::$called_class, 'getGridMode'), $args))) {
      self::load_custom_occattrs($auth['read'], $args['survey_id']);
      $ctrlOptions = array('extraParams'=>$auth['read']);
      $attrSpecificOptions = array();
      self::parseForAttrSpecificOptions($options, $ctrlOptions, $attrSpecificOptions);
      $r = '';
      if ($args['occurrence_sensitivity']) {
        $sensitivity_controls = get_attribute_html(self::$occAttrs, $args, $ctrlOptions, 'sensitivity', $attrSpecificOptions);
        $r .= data_entry_helper::sensitivity_input(array(
          'additionalControls' => $sensitivity_controls
        ));
      }
      $r .= get_attribute_html(self::$occAttrs, $args, $ctrlOptions, $tabAlias, $attrSpecificOptions);
      if ($args['occurrence_comment'])
        $r .= data_entry_helper::textarea(array(
          'fieldname'=>'occurrence:comment',
          'label'=>lang::get('Record Comment')
        ));
      if ($args['occurrence_images']){
        $r .= self::occurrence_photo_input($options, $tabAlias);
      }
      return $r;
    } else
      // in grid mode the attributes are embedded in the grid.
      return '';
  }

  /**
   * Get the date control.
   */
  protected static function get_control_date($auth, $args, $tabAlias, $options) {
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
   * As well as the standard location_autocomplete options, set personSiteAttrId to the attribute ID of 
   * a multi-value person attribute used to link people to the sites they record at.
   */
  protected static function get_control_locationautocomplete($auth, $args, $tabAlias, $options) {
    $location_list_args=array_merge_recursive(array(
        'label'=>lang::get('LANG_Location_Label'),
        'extraParams'=>array_merge(array('orderby'=>'name'), $auth['read'])
    ), $options);
    if (isset($args['users_manage_own_sites']) && $args['users_manage_own_sites']) {
      $userId = hostsite_get_user_field('indicia_user_id');
      if (!empty($userId)) {
        if (!empty($options['personSiteAttrId'])) {
          $location_list_args['extraParams']['user_id']=$userId;
          $location_list_args['extraParams']['person_site_attr_id']=$options['personSiteAttrId'];
          $location_list_args['report'] = 'library/locations/my_sites_lookup';
        } else 
          $location_list_args['extraParams']['created_by_id']=$userId;
      }
      $location_list_args['extraParams']['view']='detail';
      $location_list_args['allowCreate']=true;
    }
    return data_entry_helper::location_autocomplete($location_list_args);
  }

  /**
   * Get the location control as a select dropdown.
   */
  protected static function get_control_locationselect($auth, $args, $tabAlias, $options) {
    $location_list_args=array_merge_recursive(array(
        'label'=>lang::get('LANG_Location_Label'),
        'view'=>'detail',
        'extraParams'=>array_merge(array('orderby'=>'name'), $auth['read'])
    ), $options);
    return data_entry_helper::location_select($location_list_args);
  }

  /**
   * Get the sref by way of choosing a location.
   */
  protected static function get_control_locationmap($auth, $args, $tabAlias, $options) {
    // add a location select control
    $options = array_merge(array(
        'searchUpdatesSref' => true,
        'validation' => "required",
        'blankText' => "Select...",
    ), $options);
    $r = self::get_control_locationselect($auth, $args, $tabAlias, $options);

    //only show helpText once
    unset($options['helpText']);

    // add hidden sref controls
    $r .= data_entry_helper::sref_hidden($options);

    // add a map control
    $options = array_merge(array(
        'locationLayerName' => 'indicia:detail_locations',
        'locationLayerFilter' => "website_id=" . $args['website_id'],
        'clickForSpatialRef' => false,
    ), $options);
    $r .= self::get_control_map($auth, $args, $tabAlias, $options);

    return $r;
  }
//
  /**
   * Get the location name control.
   */
  protected static function get_control_locationname($auth, $args, $tabAlias, $options) {
    return data_entry_helper::text_input(array_merge(array(
      'label' => lang::get('LANG_Location_Name'),
      'fieldname' => 'sample:location_name',
      'class' => 'control-width-5'
    ), $options));
  }
  
  /**
   * Get an occurrence attribute control.
   */
  protected static function get_control_occattr($auth, $args, $tabAlias, $options) {
    if ($args['multiple_occurrence_mode']==='single') {
      self::load_custom_occattrs($auth['read'], $args['survey_id']);
      $attribName = 'occAttr:' . $options['ctrlId'];
      foreach (self::$occAttrs as $idx => $attr) {
        if ($attr['id'] === $attribName) {
          self::$occAttrs[$idx]['handled'] = true;
          return data_entry_helper::outputAttribute(self::$occAttrs[$idx], $options);
        }
      }
      return "Occurrence attribute $attribName not found.";
    } 
    else 
      return "Occurrence attribute $attribName cannot be included in form when in grid entry mode.";
  }
  
  /**
   * Get the photos control
   */
  protected static function get_control_photos($auth, $args, $tabAlias, $options) {
    if ($args['multiple_occurrence_mode']==='single') {
      return self::occurrence_photo_input($options, $tabAlias);
    }
    else 
      return "[photos] control cannot be included in form when in grid entry mode, since photos are automatically included in the grid.";
  }

  /**
   * Get the recorder names control
   */
  protected static function get_control_recordernames($auth, $args, $tabAlias, $options) {
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
   * Get the sensitivity control
   */
  protected static function get_control_sensitivity($auth, $args, $tabAlias, $options) {
    if ($args['multiple_occurrence_mode']==='single') {
      $ctrlOptions = array('extraParams'=>$auth['read']);
      $attrSpecificOptions = array();
      self::parseForAttrSpecificOptions($options, &$ctrlOptions, &$attrSpecificOptions);
      $sensitivity_controls = get_attribute_html(self::$occAttrs, $args, $ctrlOptions, 'sensitivity', $attrSpecificOptions);
      return data_entry_helper::sensitivity_input(array(
        'additionalControls' => $sensitivity_controls
      ));
    }
    else 
      return "[sensitivity] control cannot be included in form when in grid entry mode, since photos are automatically included in the grid.";
  }
  
  /**
   * Get the zero abundance checkbox control
   */
  protected static function get_control_zeroabundance($auth, $args, $tabAlias, $options) {
    if ($args['multiple_occurrence_mode']==='single') {
      $options = array_merge(array(
        'label' => 'Zero Abundance',
        'fieldname' => 'occurrence:zero_abundance',
        'helpText' => 'Tick this box if this is a record that the species was not found.'
      ), $options);
      $options['helpText'] = lang::get($options['helpText']);
      $options['helpText'] = lang::get($options['helpText']);
      return data_entry_helper::checkbox($options);
    }
    else 
      return "[zero abundance] control cannot be included in form when in grid entry mode.";
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
    if (isset($values['speciesgridmapmode']))
      $submission = data_entry_helper::build_sample_subsamples_occurrences_submission($values);
  	else if (isset($values['gridmode']))
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
    return array('dynamic_sample_occurrence.css');
  }

  /**
   * Returns true if this form should be displaying a multiple occurrence entry grid.
   */
  protected static function getGridMode($args) {
    // if loading an existing sample and we are allowed to display a grid or single species selector
    if (isset($args['multiple_occurrence_mode']) && $args['multiple_occurrence_mode']=='either') {
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
    // User must be logged in before we can access their records.
    if ($user->uid===0) {
      // Return a login link that takes you back to this form when done.
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">login</a> to the website.');
    }
    $filter = array();
    // Get the CMS User ID attribute so we can filter the grid to this user
    foreach($attributes as $attrId => $attr) {
      if (strcasecmp($attr['caption'],'CMS User ID')==0) {
        $filter = array (
          'survey_id' => $args['survey_id'],
          'userID_attr_id' => $attr['attributeId'],
          'userID' => $user->uid,
          'iUserID' => 0);
        break;
      }
    }
    // Alternatively get the Indicia User ID and use that instead
    if (function_exists('hostsite_get_user_field')) {
      $iUserId = hostsite_get_user_field('indicia_user_id');
      if (isset($iUserId) && $iUserId!=false) $filter = array (
          'survey_id'=>$args['survey_id'],
          'userID_attr_id' => 0,
          'userID' => 0,
          'iUserID' => $iUserId);
    }

    // Return with error message if we cannot identify the user records
    if (!isset($filter)) {
      return lang::get('LANG_No_User_Id');
    }

    // An option for derived classes to add in extra html before the grid
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
      'extraParams' => $filter
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
  
  /**
   * Load the list of occurrence attributes into a static variable. 
   *
   * By maintaining a single list of attributes we can track which have already been output.
   * @param array $readAuth Read authorisation tokens.
   * @param integer $surveyId ID of the survey to load occurrence attributes for.
   */
  protected static function load_custom_occattrs($readAuth, $surveyId) {
    if (!isset(self::$occAttrs)) {
      // Add any dynamically generated controls
      $attrArgs = array(
         'valuetable'=>'occurrence_attribute_value',
         'attrtable'=>'occurrence_attribute',
         'key'=>'occurrence_id',
         'fieldprefix'=>'occAttr',
         'extraParams'=>$readAuth,
         'survey_id'=>$surveyId
      );
      if (count(self::$occurrenceIds)==1) {
        // if we have a single occurrence Id to load, use it to get attribute values
        $attrArgs['id'] = self::$occurrenceIds[0];
      }
      self::$occAttrs = data_entry_helper::getAttributes($attrArgs, false);
    }
  }
  
  /**
   * Provides a control for inputting photos against the record, when in single record mode.
   *
   * @param $options Options array for the control.
   * @param $tabAlias ID of the tab's div if this is being loaded onto a div.
   */
  protected static function occurrence_photo_input($options, $tabAlias) {
    $defaults = array(
      'table'=>'occurrence_image',
      'label'=>lang::get('Upload your photos'),
      'caption'=>lang::get('Photos'),
      'resizeWidth' => 1600,
      'resizeHeight' => 1600,
    );
    if ($args['interface']!=='one_page')
      $opts['tabDiv']=$tabAlias;
    foreach ($options as $key => $value) {
      // skip attribute specific options as they break the JavaScript.
      if (strpos($key, ':')===false)
        $opts[$key]=$value;
    }
    return data_entry_helper::file_box($opts);
  }
  
  /** 
   * Parses the options provided to a control in the user interface definition and splits the options which 
   * apply to the entire control (@label=Grid Ref) from ones which apply to a specific custom attribute
   * (smpAttr:3|label=Quantity).
   */
  protected static function parseForAttrSpecificOptions($options, &$ctrlOptions, &$attrSpecificOptions) {
    // look for options specific to each attribute
    foreach ($options as $option => $value) {
      // split the id of the option into the control name and option name.
      if (strpos($option, '|')!==false) {
        $optionId = explode('|', $option);
        if (!isset($attrSpecificOptions[$optionId[0]])) $attrSpecificOptions[$optionId[0]]=array();
        $attrSpecificOptions[$optionId[0]][$optionId[1]] = $value;
      } else {
        $ctrlOptions[$option]=$value;
      }
    }
  }
  
}
  

