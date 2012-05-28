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
 * NB relies on the individuals and associations optional module being enabled in the warehouse.
 * 
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('includes/map.php');
require_once('includes/language_utils.php');
require_once('includes/form_generation.php');

class iform_wwt_colour_marked_report {

  // A list of the subject observation ids we are loading if editing existing data
  protected static $subjectObservationIds = array();

  protected static $auth = array();
  
  protected static $mode;
  
  protected static $node;
  
  protected static $submission = array();
  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_wwt_colour_marked_report_definition() {
    return array(
      'title'=>'WWT Colour-marked Wildfowl - dynamically generated data entry form',
      'category' => 'General Purpose Data Entry Forms',
      //'helpLink'=>'http://code.google.com/p/indicia/wiki/TutorialDynamicForm',
      'description'=>'A data entry form reporting observations of colour-marked individuals.'
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
   * we can display all the subject observations on the map.
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
          'default' => 'wizard',
          'group' => 'User Interface'
        ),
        array(
          'name'=>'tabProgress',
          'caption'=>'Show Progress through Wizard/Tabs',
          'description'=>'For Wizard or Tabs interfaces, check this option to show a progress summary above the controls.',
          'type'=>'boolean',
          'default' => true,
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
                "&nbsp;&nbsp;<strong>[date]</strong><br/>".
                "&nbsp;&nbsp;<strong>[map]</strong><br/>".
                "&nbsp;&nbsp;<strong>[spatial reference]</strong><br/>".
                "&nbsp;&nbsp;<strong>[location name]</strong><br/>".
                "&nbsp;&nbsp;<strong>[location autocomplete]</strong><br/>".
                "&nbsp;&nbsp;<strong>[location select]</strong><br/>".
                "&nbsp;&nbsp;<strong>[place search]</strong><br/>".
                "&nbsp;&nbsp;<strong>[sample comment]</strong>. <br/>".
                "&nbsp;&nbsp;<strong>[species identifier]</strong>. <br/>".
                "&nbsp;&nbsp;<strong>[show added sample comments]</strong>. <br/>".
                "&nbsp;&nbsp;<strong>[add sample comment]</strong>. <br/>".
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
          'default' => "=When and Where=\r\n".
              "?Please tell us when you saw the colour-marked bird.?\r\n".
              "[date]\r\n".
              "[*]\r\n".
              "?Please tell us where you saw the marked bird. You can do this in any of the following ways:-<ol>".
              "<li>enter GPS co-ordinates or an OS grid reference directly,</li>".
              "<li>enter a place name and search for it,</li>".
              "<li>search for the place on the map and then click to set it.</li>".
              "</ol>?\r\n".
              "[spatial reference]\r\n".
              "[place search]\r\n".
              "[map]\r\n".
              "?What name do you know this location by?\r\n".
              "[location name]\r\n".
              "[*]\r\n".
              "=Colour Marks=\r\n".
              "?Please pick the species from the following list and enter the details for the colour identifiers.?\r\n".
              "[species identifier]\r\n".
              "[*]\r\n".
              "=Added Comments=\r\n".
              "?Please add any comments for review or editing of this report.?\r\n".
              "[show added sample comments]\r\n".
              "[add sample comment]\r\n".
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
              'field and subject_observation_id field for linking to the data entry form. As a starting point, try reports_for_prebuilt_forms/simple_subject_observation_identifier_list_1 '.
              'for a list of subject observations.',
          'type'=>'string',
          'group' => 'User Interface',
          'default' => 'reports_for_prebuilt_forms/simple_subject_observation_identifier_list_1'
        ),
        array(
          'name'=>'grid_num_rows',
          'caption'=>'Number of rows displayed in grid',
          'description'=>'Number of rows display on each page of the grid.',
          'type'=>'int',
          'default' => 10,
          'group' => 'User Interface'
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
          'fieldname'=>'list_id',
          'label'=>'Species List ',
          'helpText'=>'The species list that species can be selected from. This list is pre-populated '.
              'into the grid when doing grid based data entry, or provides the list which a species '.
              'can be picked from when doing single subject observation data entry.',
          'type'=>'select',
          'table'=>'taxon_list',
          'valueField'=>'id',
          'captionField'=>'title',
          'required'=>false,
          'group'=>'Species',
          'siteSpecific'=>true
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
          'default' => 'select',
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
              'species. Therefore there will be no species picker control, and the form will always operate in the single record, non-grid mode. '.
              'As there is no visual indicator which species is recorded you may like to include information about what is being recorded in the '.
              'header. You may also want to configure the User Interface section of the form\'s Form Structure to move the [species] and [species] controls '.
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
          'name' => 'subject_type_id',
          'caption' => 'Subject Type',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:subject_type'),
          'required' => true,
          'helpText' => 'The subject type that will be used for created subject observations for each colour-marked individual.'
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
          'name'=>'neck_collar_type',
          'caption'=>'Neck Collar Type',
          'description'=>'The type of identifier which indicates a neck collar.',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_type'),
          'required' => true,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'neck_collar_max_length',
          'caption'=>'Neck collar maximum length',
          'description'=>'Maximum length for a neck-collar identifier sequence.',
          'type'=>'string',
          'required' => false,
          'group'=>'Identifiers'
        ),
        array(
          'name'=>'neck_collar_regex',
          'caption'=>'Neck collar validation pattern',
          'description'=>'The validation pattern (as a regular expression) for a neck-collar identifier sequence. '.
              'Eg. /^([A-Z]{2}[0-9]{2}|[A-Z]{3}[0-9])$/ would only permit sequences of either 2 uppercase letters followed by 2 digits, '.
              'or 3 uppercase letters followed by 1 digit.',
          'type'=>'string',
          'required' => false,
          'group'=>'Identifiers'
        ),
        array(
          'name'=>'enscribed_colour_ring_type',
          'caption'=>'Enscribed Colour Ring Type',
          'description'=>'The type of identifier which indicates an enscribed colour ring (\'darvic\').',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_type'),
          'required' => true,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'enscribed_colour_ring_max_length',
          'caption'=>'Colour ring maximum length',
          'description'=>'Maximum length for an enscribed colour ring identifier sequence.',
          'type'=>'string',
          'required' => false,
          'group'=>'Identifiers'
        ),
        array(
          'name'=>'enscribed_colour_ring_regex',
          'caption'=>'Colour ring validation pattern',
          'description'=>'The validation pattern (as a regular expression) for an enscribed colour ring identifier sequence. '.
              'Eg. /^([A-Z]{2}[0-9]{2}|[A-Z]{3}[0-9])$/ would only permit sequences of either 2 uppercase letters followed by 2 digits, '.
              'or 3 uppercase letters followed by 1 digit.',
          'type'=>'string',
          'required' => false,
          'group'=>'Identifiers'
        ),
        array(
          'name'=>'metal_ring_type',
          'caption'=>'Metal Ring Type',
          'description'=>'The type of identifier which indicates a metal ring.',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_type'),
          'required' => true,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'metal_ring_max_length',
          'caption'=>'Metal ring maximum length',
          'description'=>'Maximum length for a metal identifier sequence.',
          'type'=>'string',
          'required' => false,
          'group'=>'Identifiers'
        ),
        array(
          'name'=>'metal_ring_regex',
          'caption'=>'Metal ring validation pattern',
          'description'=>'The validation pattern (as a regular expression) for a metal ring identifier sequence. '.
              'Eg. /^([A-Z]{2}[0-9]{2}|[A-Z]{3}[0-9])$/ would only permit sequences of either 2 uppercase letters followed by 2 digits, '.
              'or 3 uppercase letters followed by 1 digit.',
          'type'=>'string',
          'required' => false,
          'group'=>'Identifiers'
        ),
        array(
          'name'=>'base_colours',
          'caption'=>'Base Colours',
          'description'=>'The colours we want to let users record for the background of the coloured identifiers. Tick all that apply.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:ring_colour'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'text_colours',
          'caption'=>'Text Colours',
          'description'=>'The colours we want to let users record for the text enscribed on the coloured identifiers. Tick all that apply.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:ring_colour'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'position',
          'caption'=>'Identifier Position',
          'description'=>'The positions on the organism we want to let users record for the identifiers. Tick all that apply.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_position','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'default_leg_vertical',
          'caption'=>'Default Position on Leg',
          'description'=>'If you are not specifying if a leg mark is above or below the \'knee\' in the above choices, '.
             'you can optionally specify a default position here.',
          'type'=>'select',
          'options' => array(
            '?' => 'No Default',
            'A' => 'Above the \'Knee\'',
            'B' => 'Below the \'Knee\'',
          ),
          'required'=>false,
          'group'=>'Identifiers'
        ),
        array(
          'name'=>'use_colour_picker',
          'caption'=>'Use Colour Picker',
          'description'=>'Tick this to use a colour-picker control for choosing colours rather than a select control of colour names.',
          'type'=>'boolean',
          'required' => false,
          'default' => false,
          'group' => 'Identifiers'
        ),
        array(
          'name'=>'other_devices',
          'caption'=>'Other Devices',
          'description'=>'What other devices (such as transmitters/trackers/loggers do you want to record? Tick all that apply.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:attachment_type'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Subject observation',
        ),
        array(
          'name'=>'observation_comment',
          'caption'=>'Allow Comment For Colour-marked Individual',
          'description'=>'Tick this to allow a comment to be input for each reported colour-marked individual. '.
            'This comment is stored on the subject observation record',
          'type'=>'boolean',
          'required' => false,
          'default' => false,
          'group' => 'Subject observation'
        ),
        array(
          'name'=>'request_gender_values',
          'caption'=>'Request Gender Values',
          'description'=>'What (if any) gender options do you want to present for the colour-marked individual? '.
            'Leave un-ticked to hide all gender options.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:gender','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Subject observation',
        ),
        array(
          'name'=>'request_stage_values',
          'caption'=>'Request Age Values',
          'description'=>'What (if any) age/stage options do you want to present for the colour-marked individual? '.
            'Leave un-ticked to hide all age options.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:stage','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Subject observation',
        ),
        array(
          'name'=>'request_life_status_values',
          'caption'=>'Request Life Status Values',
          'description'=>'What (if any) life status options do you want to present for the colour-marked individual? '.
            'Leave un-ticked to hide all life status options.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:life_status','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Subject observation',
        ),
        array(
          'name'=>'debug_info',
          'caption'=>'Provide debug information',
          'description'=>'Tick this to provide debug info on the form, DO NOT USE IN PRODUCTION!!!!',
          'type'=>'boolean',
          'required' => false,
          'default' => false,
          'group' => 'Debug'
        ),
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
    self::$node = $node;
    
    // hard-wire some 'dynamic' options to simplify the form. Todo: take out the dynamic code for these
    $args['multiple_subject_observation_mode'] = 'single';
    $args['extra_list_id'] = '';
    $args['occurrence_comment'] = false;
    $args['col_widths'] = '';
    $args['includeLocTools'] = false;
    $args['loctoolsLocTypeID'] = 0;
    $args['subject_observation_confidential'] = false;
    $args['observation_images'] = false;
    
    // Get authorisation tokens to update and read from the Warehouse.
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $svcUrl = self::warehouseUrl().'index.php/services';
    self::$auth = $auth;
    
    drupal_add_js(iform_media_folder_path() . 'js/jquery.form.js', 'module');
    
    $mode = (isset($args['no_grid']) && $args['no_grid']) 
        ? MODE_NEW_SAMPLE // default mode when no_grid set to true - display new sample
        : MODE_GRID; // default mode when no grid set to false - display grid of existing data
                // mode MODE_EXISTING: display existing sample
    $loadedSampleId = null;
    $loadedSubjectObservationId = null;
    if ($_POST) {
      if(!array_key_exists('website_id', $_POST)) { // non Indicia POST, in this case must be the location allocations. add check to ensure we don't corrept the data by accident
        if(function_exists('iform_loctools_checkaccess') && iform_loctools_checkaccess($node,'admin') && array_key_exists('mnhnld1', $_POST)){
          iform_loctools_deletelocations($node);
          foreach($_POST as $key => $value){
            $parts = explode(':', $key);
            iform_loctools_insertlocation($node, $parts[2], $parts[1]);
          }
        }
      } else if(!is_null(data_entry_helper::$entity_to_load)) {
        $mode = MODE_EXISTING; // errors with new sample, entity populated with post, so display this data.
      } // else valid save, so go back to gridview: default mode 0
    }
    if (array_key_exists('sample_id', $_GET) && $_GET['sample_id']!='{sample_id}') {
      $mode = MODE_EXISTING;
      $loadedSampleId = $_GET['sample_id'];
    }
    if (array_key_exists('subject_observation_id', $_GET) && $_GET['subject_observation_id']!='{subject_observation_id}') {
      $mode = MODE_EXISTING;
      // single subject_observation case
      $loadedSubjectObservationId = $_GET['subject_observation_id'];
      self::$subjectObservationIds = array($loadedSubjectObservationId);
    } 
    if ($mode!=MODE_EXISTING && array_key_exists('newSample', $_GET)) {
      $mode = MODE_NEW_SAMPLE;
      data_entry_helper::$entity_to_load = array();
    } // else default to mode MODE_GRID or MODE_NEW_SAMPLE depending on no_grid parameter
    self::$mode = $mode;
    // default mode  MODE_GRID : display grid of the samples to add a new one 
    // or edit an existing one.
    if($mode ==  MODE_GRID) {
      $r = '';
      // debug section
      if (!empty($args['debug_info']) && $args['debug_info']) {
        $r .= '<input type="button" value="Debug info" onclick="$(\'#debug-info-div\').slideToggle();" /><br />'.
          '<div id="debug-info-div" style="display: none;">';
        $r .= '<p>$_GET is:<br /><pre>'.print_r($_GET, true).'</pre></p>';
        $r .= '<p>$_POST is:<br /><pre>'.print_r($_POST, true).'</pre></p>';
        $r .= '<p>Entity to load is:<br /><pre>'.print_r(data_entry_helper::$entity_to_load, true).'</pre></p>';
        $r .= '<p>Submission was:<br /><pre>'.print_r(self::$submission, true).'</pre></p>';
        $r .= '<input type="button" value="Hide debug info" onclick="$(\'#debug-info-div\').slideToggle();" />';
        $r .= '</div>';
      }
      if (method_exists(get_called_class(), 'getHeaderHTML')) {
        $r .= call_user_func(array(get_called_class(), 'getHeaderHTML'), true, $args);
      }
      $attributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
      ), false);
      $tabs = array('#sampleList'=>lang::get('LANG_Main_Samples_Tab'));
      if($args['includeLocTools'] 
        && function_exists('iform_loctools_checkaccess') 
        && iform_loctools_checkaccess($node,'admin')) {
        $tabs['#setLocations'] = lang::get('LANG_Allocate_Locations');
      }
      if (method_exists(get_called_class(), 'getExtraGridModeTabs')) {
        $extraTabs = call_user_func(array(get_called_class(), 'getExtraGridModeTabs'), false, $auth['read'], $args, $attributes);
        if(is_array($extraTabs)) {
          $tabs = $tabs + $extraTabs;
        }
      }
      if(count($tabs) > 1) {
        $r .= "<div id=\"controls\">".(data_entry_helper::enable_tabs(array('divId'=>'controls','active'=>'#sampleList')))."<div id=\"temp\"></div>";
        $r .= data_entry_helper::tab_header(array('tabs'=>$tabs));
      }
      $r .= "<div id=\"sampleList\">".call_user_func(array(get_called_class(), 'getSampleListGrid'), $args, $node, $auth, $attributes)."</div>";
      if($args['includeLocTools'] 
        && function_exists('iform_loctools_checkaccess') 
        && iform_loctools_checkaccess($node,'admin')) {
        $r .= '
  <div id="setLocations">
    <form method="post">
      <input type="hidden" id="mnhnld1" name="mnhnld1" value="mnhnld1" /><table border="1"><tr><td></td>';
        $url = $svcUrl.'/data/location?mode=json&view=detail&auth_token='.$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&parent_id=NULL&orderby=name".(isset($args['loctoolsLocTypeID'])&&$args['loctoolsLocTypeID']<>''?'&location_type_id='.$args['loctoolsLocTypeID']:'');
        $session = curl_init($url);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        $entities = json_decode(curl_exec($session), true);
        $userlist = iform_loctools_listusers($node);
        foreach($userlist as $uid => $a_user) {
          $r .= '<td>'.$a_user->name.'</td>';
        }
        $r .= "</tr>";
        if(!empty($entities)) {
          foreach($entities as $entity) {
            if(!$entity["parent_id"]) { // only assign parent locations.
              $r .= "<tr><td>".$entity["name"]."</td>";
              $defaultuserids = iform_loctools_getusers($node, $entity["id"]);
              foreach($userlist as $uid => $a_user) {
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
      if(count($tabs)>1) { // close tabs div if present
        $r .= "</div>";
      }
      if (method_exists(get_called_class(), 'getTrailerHTML')) {
        $r .= call_user_func(array(get_called_class(), 'getTrailerHTML'), true, $args);
      }
      return $r;
    }
    // from this point on, we are MODE_EXISTING or MODE_NEW_SAMPLE
    if ($mode == MODE_EXISTING && is_null(data_entry_helper::$entity_to_load)) { // only load if not in error situation
      // Displaying an existing sample. If we know the subject_observation ID, and don't know the sample ID 
      // then we must get the sample id from the subject_observation data.
      if ($loadedSubjectObservationId && !$loadedSampleId) {
        data_entry_helper::load_existing_record($auth['read'], 'subject_observation', $loadedSubjectObservationId);
        $loadedSampleId = data_entry_helper::$entity_to_load['subject_observation:sample_id'];
      }
      data_entry_helper::$entity_to_load = self::reload_form_data($loadedSampleId, $args, $auth);
    }
    // get the sample attributes
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
    //// Make sure the form action points back to this page
    $reload = data_entry_helper::get_reload_link_parts();
    unset($reload['params']['sample_id']);
    unset($reload['params']['subject_observation_id']);
    unset($reload['params']['newSample']);
    $reloadPath = $reload['path'];
    // don't url-encode the drupal path id using dirty url
    $pathParam = (function_exists('variable_get') && variable_get('clean_url', 0)=='0') ? 'q' : '';
    if(count($reload['params'])) {
      if ($pathParam==='q' && array_key_exists('q', $reload['params'])) {
        $reloadPath .= '?q='.$reload['params']['q'];
        unset($reload['params']['q']);
        if (count($reload['params'])) {
          $reloadPath .= '&'.http_build_query($reload['params']);
        }
      } else {
        $reloadPath .= '?'.http_build_query($reload['params']);
      }
    }
    $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\">\n";
    // debug section
    if (!empty($args['debug_info']) && $args['debug_info']) {
      $r .= '<input type="button" value="Debug info" onclick="$(\'#debug-info-div\').slideToggle();" /><br />'.
        '<div id="debug-info-div" style="display: none;">';
      $r .= '<p>$_GET is:<br /><pre>'.print_r($_GET, true).'</pre></p>';
      $r .= '<p>$_POST is:<br /><pre>'.print_r($_POST, true).'</pre></p>';
      $r .= '<p>Entity to load is:<br /><pre>'.print_r(data_entry_helper::$entity_to_load, true).'</pre></p>';
      $r .= '<p>Submission was:<br /><pre>'.print_r(self::$submission, true).'</pre></p>';
      $r .= '<input type="button" value="Hide debug info" onclick="$(\'#debug-info-div\').slideToggle();" />';
      $r .= '</div>';
    }
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
    // request automatic JS validation
    if (!isset($args['clientSideValidation']) || $args['clientSideValidation']) {
      data_entry_helper::enable_validation('entry_form');
      // By default, validate doesn't validate any ':hidden' fields, 
      // but we need to validate hidden with display: none; fields in accordions
      data_entry_helper::$javascript .= "jQuery.validator.setDefaults({ 
        ignore: \"input[type='hidden']\"
      });\n";
    }
    if (method_exists(get_called_class(), 'getHeaderHTML')) {
      $r .= call_user_func(array(get_called_class(), 'getHeaderHTML'), true, $args);
    }
    if ($mode==MODE_EXISTING && ($loadedSampleId || $loadedSubjectObservationId)) {
      $existing = true;
    } else {
      $existing = false;
    }
    $hiddens .= get_user_profile_hidden_inputs($attributes, $args, $existing, $auth['read']);
    $customAttributeTabs = get_attribute_tabs($attributes);
    // remove added comment controls unless editing an existing sample
    if ($mode!==MODE_EXISTING || helper_base::$form_mode==='ERRORS') {
      $controls = helper_base::explode_lines($args['structure']);
      $new_controls = array();
      foreach ($controls as $control) {
        if ($control!=='[show added sample comments]' && $control!=='[add sample comment]') {
          $new_controls[] = $control;
        }
      }
      $args['structure'] = implode("\r\n", $new_controls);
    }
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
    
    if (method_exists(get_called_class(), 'getTrailerHTML')) $r .= call_user_func(array(get_called_class(), 'getTrailerHTML'), true, $args);
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
          while ($i < count($tabContent)-1 && substr($tabContent[$i+1],0,1)=='@' || trim($tabContent[$i])==='') {
            $i++;
            // ignore empty lines
            if (trim($tabContent[$i])!=='') {
              $option = explode('=',substr($tabContent[$i],1));
              $options[$option[0]]=json_decode($option[1]);
              // if not json then need to use option value as it is
              if ($options[$option[0]]=='') $options[$option[0]]=$option[1];            
            }
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
   * Finds the list of all tab names that are going to be required, either by the form
   * structure, or by custom attributes.
   */
  protected static function get_all_tabs($structure, $attrTabs) {    
    $structureArr = helper_base::explode_lines($structure);
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
  
  /*
   * helper function to reload data for existing sample 
   * @param $loadedSampleId Required. id for required sample.
   * if not supplied, all subject_observations in the sample are loaded
   * @return array of data values matching the form control names. 
   */
  private static function reload_form_data($loadedSampleId, $args, $auth) {
    $form_data = array();
    if (!$loadedSampleId) { // required
      return $form_data;
    }
    
    // load the sample
    data_entry_helper::load_existing_record($auth['read'], 'sample', $loadedSampleId);
    $form_data = array_merge(data_entry_helper::$entity_to_load, $form_data);
    
    // if we have a subject_observation, then we just load that,
    // otherwise we need all the subjects_observations in the sample
    $filter = array();
    if (count(self::$subjectObservationIds)===1) {
      $filter = array('id'=>self::$subjectObservationIds[0]);
      self::$subjectObservationIds = array();
    }
  
    // load the subject_observation(s) for this sample
    $options = array(
      'table' => 'subject_observation',
      'extraParams' => $auth['read'] + array('sample_id'=>$loadedSampleId, 'view'=>'detail') + $filter,
      'nocache' => true,
    );
    $subjectObservations = data_entry_helper::get_population_data($options);
    // add each subject_observation to the form data
    for ($idx=0; $idx<count($subjectObservations); $idx++) {
      $subjectObservation=$subjectObservations[$idx];
      self::$subjectObservationIds[] = $subjectObservation['id'];
      // prefix the keys and load to form data
      $fieldprefix = 'idn:'.$idx.':subject_observation:';
      $keys = array_keys($subjectObservation);
      foreach ($keys as $key) {
        $form_data[$fieldprefix.$key] = $subjectObservation[$key];
      }
    }
  
    // load the subject_observation_attribute(s) for this sample
    $query = array('in'=>array('subject_observation_id', self::$subjectObservationIds));
    $filter = array('query'=>json_encode($query),);
    $options = array(
      'table' => 'subject_observation_attribute_value',
      'extraParams' => $auth['read'] + $filter,
      'nocache' => true,
    );
    $sjoAttrs = data_entry_helper::get_population_data($options);
    // add each subject_observation_attribute to the form data
    for ($idx=0; $idx<count($subjectObservations); $idx++) {
      $subjectObservation=$subjectObservations[$idx];
      // prefix the keys and load to form data
      $fieldprefix = 'idn:'.$idx.':sjoAttr:';
      foreach ($sjoAttrs as $sjoAttr) {
        if ($sjoAttr['subject_observation_id']===$subjectObservation['id']) {
          if ($sjoAttr['multi_value']==='t') {
            $form_data[$fieldprefix.$sjoAttr['subject_observation_attribute_id']][] = $sjoAttr['raw_value'];
          } else {
            $form_data[$fieldprefix.$sjoAttr['subject_observation_attribute_id']] = $sjoAttr['raw_value'];
          }
        }
      }
    }
    
    // load the occurrence(s) for this sample
    $query = array('in'=>array('subject_observation_id', self::$subjectObservationIds));
    $filter = array('query'=>json_encode($query),);
    $options = array(
      'table' => 'occurrences_subject_observation',
      'extraParams' => $auth['read'] + array('view'=>'detail') + $filter,
      'nocache' => true,
    );
    $osos = data_entry_helper::get_population_data($options);
    $occurrenceIds = array();
    foreach ($osos as $oso) {
      $occurrenceIds[] = $oso['occurrence_id'];
    }
    $query = array('in'=>array('id', $occurrenceIds));
    $filter = array('query'=>json_encode($query),);
    $options = array(
      'table' => 'occurrence',
      'extraParams' => $auth['read'] + array('view'=>'detail') + $filter,
      'nocache' => true,
    );
    $occurrences = data_entry_helper::get_population_data($options);
    // add each occurrence and occurrences_subject_observation to the form data
    for ($idx=0; $idx<count($subjectObservations); $idx++) {
      $subjectObservation=$subjectObservations[$idx];
      // note, this code would break with more than one occurrence on the subject_observation
      // fortunately, that can't happen with this form yet, but may do with associations?
      // prefix the keys and load to form data
      foreach ($osos as $oso) {
        if ($oso['subject_observation_id']===$subjectObservation['id']) {
          foreach ($occurrences as $occurrence) {
            if ($oso['occurrence_id']===$occurrence['id']) {
              $fieldprefix = 'idn:'.$idx.':occurrences_subject_observation:';
              $keys = array_keys($oso);
              foreach ($keys as $key) {
                $form_data[$fieldprefix.$key] = $oso[$key];
              }
              $fieldprefix = 'idn:'.$idx.':occurrence:';
              $keys = array_keys($occurrence);
              foreach ($keys as $key) {
                $form_data[$fieldprefix.$key] = $occurrence[$key];
                if ($key=='taxon' && $args['species_ctrl']=='autocomplete') {
                  $form_data[$fieldprefix.'taxa_taxon_list_id:taxon'] = $occurrence[$key];
                }
              }
            }
          }
        }
      }
    }
    
    // load the identifier(s) for this sample
    $query = array('in'=>array('subject_observation_id', self::$subjectObservationIds));
    $filter = array('query'=>json_encode($query),);
    $options = array(
      'table' => 'identifiers_subject_observation',
      'extraParams' => $auth['read'] + array('view'=>'detail') + $filter,
      'nocache' => true,
    );
    $isos = data_entry_helper::get_population_data($options);
    $identifierIds = array();
    foreach ($isos as $iso) {
      $identifierIds[] = $iso['identifier_id'];
    }
    $query = array('in'=>array('id', $identifierIds));
    $filter = array('query'=>json_encode($query),);
    $options = array(
      'table' => 'identifier',
      'extraParams' => $auth['read'] + array('view'=>'detail') + $filter,
      'nocache' => true,
    );
    $identifiers = data_entry_helper::get_population_data($options);
    $query = array('in'=>array('identifier_id', $identifierIds));
    $filter = array('query'=>json_encode($query),);
    $options = array(
      'table' => 'identifier_attribute_value',
      'extraParams' => $auth['read'] + $filter,
      'nocache' => true,
    );
    $idnAttrs = data_entry_helper::get_population_data($options);
    // add each identifier to the form data
    for ($idx=0; $idx<count($subjectObservations); $idx++) {
      $subjectObservation=$subjectObservations[$idx];
      // prefix the keys and load to form data
      foreach ($isos as $iso) {
        if ($iso['subject_observation_id']===$subjectObservation['id']) {
          foreach ($identifiers as $identifier) {
            if ($iso['identifier_id']===$identifier['id']) {
              if ($identifier['identifier_type_id']==$args['neck_collar_type']) {
                $identifier_type = 'neck-collar';
              } elseif ($identifier['identifier_type_id']==$args['enscribed_colour_ring_type']) {
                if (substr($identifier['coded_value'], 0, 1)=='L') {
                  $identifier_type = 'colour-left';
                } elseif (substr($identifier['coded_value'], 0, 1)=='R') {
                  $identifier_type = 'colour-right';
                }
              } elseif ($identifier['identifier_type_id']==$args['metal_ring_type']) {
                $identifier_type = 'metal';
              } else {
                $identifier_type = '';
              }
              $fieldprefix = 'idn:'.$idx.':'.$identifier_type.':identifiers_subject_observation:';
              $keys = array_keys($iso);
              foreach ($keys as $key) {
                $form_data[$fieldprefix.$key] = $iso[$key];
              }
              $fieldprefix = 'idn:'.$idx.':'.$identifier_type.':identifier:';
              $form_data[$fieldprefix.'checkbox'] = 'on';
              $keys = array_keys($identifier);
              foreach ($keys as $key) {
                $form_data[$fieldprefix.$key] = $identifier[$key];
              }
              $fieldprefix = 'idn:'.$idx.':'.$identifier_type.':idnAttr:';
              foreach ($idnAttrs as $idnAttr) {
                if ($iso['identifier_id']===$idnAttr['identifier_id']) {
                  if ($idnAttr['multi_value']==='t') {
                    $form_data[$fieldprefix.$idnAttr['identifier_attribute_id']][] = $idnAttr['raw_value'];
                  } else {
                    $form_data[$fieldprefix.$idnAttr['identifier_attribute_id']] = $idnAttr['raw_value'];
                  }
                }
              }
            }
          }
        }
      }
    }

    //print_r($form_data);exit;
    return $form_data;
  }

  /**
   * Get the control for species input, either a grid or a single species input control.
   */
  protected static function get_control_species($auth, $args, $tabalias, $options) {
    global $user;
    $extraParams = $auth['read'];
    $fieldPrefix = !empty($options['fieldprefix']) ? $options['fieldprefix'] : '';
    if (!empty($args['taxon_filter_field']) && !empty($args['taxon_filter'])) {
      $filterLines = helper_base::explode_lines($args['taxon_filter']);
      if ($args['multiple_subject_observation_mode'] !== 'single' && $args['taxon_filter_field']!=='taxon_group' && count($filterLines)===1) {
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
        return '<input type="hidden" name="'.$fieldPrefix.'occurrence:taxa_taxon_list_id" value="'.$response[0]['id']."\"/>\n";
      }
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
          'occurrenceConfidential'=>(isset($args['subject_observation_confidential']) ? $args['subject_observation_confidential'] : false),
          'occurrenceImages'=>$args['observation_images'],
          'PHPtaxonLabel' => true,
          'language' => iform_lang_iso_639_2($user->lang), // used for termlists in attributes
          'cacheLookup' => isset($args['cache_lookup']) && $args['cache_lookup'],
          'speciesNameFilterMode' => self::getSpeciesNameFilterMode($args),          
      ), $options);
      if ($args['extra_list_id']) $species_ctrl_opts['lookupListId']=$args['extra_list_id'];
      if (!empty($args['taxon_filter_field']) && !empty($args['taxon_filter'])) {
        $species_ctrl_opts['taxonFilterField']=$args['taxon_filter_field'];
        $species_ctrl_opts['taxonFilter']=$filterLines;
      }
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
          case 'currentLanguage' :
            if (isset($options['language']))
              $extraParams += array($languageFieldName=>$options['language']);
            break;
          case 'excludeSynonyms':
            $query['where'] = array("(preferred='t' OR $languageFieldName<>'lat')");
            break;
        }
      }
      if (count($query)) 
        $extraParams['query'] = json_encode($query);
      $species_ctrl_opts=array_merge(array(
          'label'=>lang::get('occurrence:taxa_taxon_list_id'),
          'fieldname'=>$fieldPrefix.'occurrence:taxa_taxon_list_id',
          'table'=>'taxa_taxon_list',
          'captionField'=>'taxon',
          'valueField'=>'id',
          'columns'=>2,
          'parentField'=>'parent_id',
          'extraParams'=>$extraParams,
          'blankText'=>'Please select'
      ), $options);
      if (isset($args['cache_lookup']) && $args['cache_lookup'])
        $species_ctrl_opts['extraParams']['view']='cache';
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
            '<img src="'.self::warehouseUrl().'/upload/thumb-{image_path}" alt="Image of {caption}" width="80" /></div>'.
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
  private static function get_control_samplecomment($auth, $args, $tabalias, $options) {
    return data_entry_helper::textarea(array_merge(array(
      'fieldname'=>'sample:comment',
      'label'=>lang::get('Overall Comment')
    ), $options)); 
  }
  
  /**
   * Get the observation comment control
   */
  private static function get_control_observationcomment($auth, $args, $tabalias, $options) {
    $fieldPrefix = !empty($options['fieldprefix']) ? $options['fieldprefix'] : '';
    return data_entry_helper::textarea(array_merge(array(
      'fieldname'=>$fieldPrefix.'subject_observation:comment',
      'label'=>lang::get('Any information you might like to add'),
      'class'=>'control-width-5',
    ), $options)); 
  }
  
  /**
   * Get the add sample comment control. This is for additional comments by other people after the 
   * colour-marked individual has been reported.
   */
  private static function get_control_showaddedsamplecomments($auth, $args, $tabalias, $options) {
    $r = '';
    if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      $reportName = 'reports_for_prebuilt_forms/sample_comments_list';
      $r .= data_entry_helper::report_grid(array(
        'id' => 'sample-comments-grid',
        'dataSource' => $reportName,
        'mode' => 'report',
        'readAuth' => $auth['read'],
        'itemsPerPage' =>(isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 10),
        'autoParamsForm' => true,
        'extraParams' => array(
          'sample_id'=>data_entry_helper::$entity_to_load['sample:id'], 
        )
      ));    
    }
    return $r;
  }
  
  /**
   * Get the add sample comment control. This is for additional comments by other people after the 
   * colour-marked individual has been reported.
   */
  private static function get_control_addsamplecomment($auth, $args, $tabalias, $options) {
    return data_entry_helper::textarea(array_merge(array(
      'fieldname'=>'sample_comment:comment',
      'label'=>lang::get('Add a comment about this report'),
      'class'=>'control-width-6',
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
      if (count(self::$subjectObservationIds)==1) {
        // if we have a single subject observation Id to load, use it to get attribute values
        $attrArgs['id'] = self::$subjectObservationIds[0];
      }
      $attributes = data_entry_helper::getAttributes($attrArgs, false);
      $defAttrOptions = array('extraParams'=>$auth['read']);
      $r = get_attribute_html($attributes, $args, $defAttrOptions);
      if ($args['occurrence_comment'])
        $r .= data_entry_helper::textarea(array(
          'fieldname'=>'occurrence:comment',
          'label'=>lang::get('Record Comment')
        ));
      if ($args['subject_observation_confidential'])
        $r .= data_entry_helper::checkbox(array(
          'fieldname'=>'occurrence:confidential',
          'label'=>lang::get('Record Confidental')
        ));
      if ($args['observation_images']){
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
      'systems' => $systems,
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
    $georefOpts = iform_map_get_georef_options($args, $auth['read']);
    if ($georefOpts['driver']=='geoplanet' && empty(helper_config::$geoplanet_api_key))
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
    
  /*
   * Get the species picker with selected colour identifier controls
   */
  
  private static function get_control_speciesidentifier($auth, $args, $tabalias, $options) {
    static $taxIdx = 0; 
    
    $svcUrl = self::warehouseUrl().'index.php/services';
    // get the identifier type data
    $filter = array(
      'termlist_external_key' => 'indicia:assoc:identifier_type',
    );
    $dataOpts = array(
      'table' => 'termlists_term',
      'extraParams' => $auth['read'] + $filter,
    );
    $options['identifierTypes'] = data_entry_helper::get_population_data($dataOpts);
    // get the identifier attribute data
    $dataOpts = array(
      'table' => 'identifier_attribute',
      'extraParams' => $auth['read'],
    );
    $options['idnAttributeTypes'] = data_entry_helper::get_population_data($dataOpts);
    // set up the known system types
    $options['baseColourId'] = -1;
    $options['textColourId'] = -1;
    $options['sequenceId'] = -1;
    $options['positionId'] = -1;
    foreach ($options['idnAttributeTypes'] as $idnAttributeType) {
      if (!empty($idnAttributeType['system_function'])) {
        switch ($idnAttributeType['system_function']) {
          case 'base_colour' :
            $options['baseColourId'] = $idnAttributeType['id'];
            break;
          case 'text_colour' :
            $options['textColourId'] = $idnAttributeType['id'];
            break;
          case 'sequence' :
            $options['sequenceId'] = $idnAttributeType['id'];
            break;
          case 'position' :
            $options['positionId'] = $idnAttributeType['id'];
            break;
        }
      }
    }
    // get the subject observation attribute data
    $dataOpts = array(
      'table' => 'subject_observation_attribute',
      'extraParams' => $auth['read'],
    );
    $options['sjoAttributeTypes'] = data_entry_helper::get_population_data($dataOpts);
    // set up the known system types
    $options['attachmentId'] = -1;
    $options['genderId'] = -1;
    $options['stageId'] = -1;
    $options['lifeStatusId'] = -1;
    foreach ($options['sjoAttributeTypes'] as $sjoAttributeType) {
      if (!empty($sjoAttributeType['system_function'])) {
        switch ($sjoAttributeType['system_function']) {
          case 'attachment' :
            $options['attachmentId'] = $sjoAttributeType['id'];
            break;
          case 'gender' :
            $options['genderId'] = $sjoAttributeType['id'];
            break;
          case 'stage' :
            $options['stageId'] = $sjoAttributeType['id'];
            break;
          case 'life_status' :
            $options['lifeStatusId'] = $sjoAttributeType['id'];
            break;
        }
      }
    }
    $validate = $args['clientSideValidation'] ? 'true' : 'false';
    // configure the identifiers javascript
    // write it late so it happens after any locked values are applied
    if (!$options['inNewIndividual']) {
      data_entry_helper::$late_javascript .= "indicia.wwt.initForm (
        '".$svcUrl."', 
        '".$auth['read']['nonce']."',
        '".$auth['read']['auth_token']."',
        '".$options['baseColourId']."',
        '".$options['textColourId']."',
        '".$options['sequenceId']."',
        '".$options['positionId']."',
        '".$args['default_leg_vertical']."',
        '".(!empty($args['neck_collar_regex']) ? $args['neck_collar_regex'] : '')."',
        '".(!empty($args['enscribed_colour_ring_regex']) ? $args['enscribed_colour_ring_regex'] : '')."',
        '".(!empty($args['metal_ring_regex']) ? $args['metal_ring_regex'] : '')."',
        '".$validate."'\n".
        ");\n";
    }
    
    $r = '';
    $options['fieldprefix'] = 'idn:'.$taxIdx.':';
    if (!$options['inNewIndividual']) {;
      $r .= '<div id="idn:subject:accordion" class="idn-subject-accordion">';
    }
    $r .= '<h3><a href="" data-heading="'.lang::get('Colour-marked individual').' '.($taxIdx+1).'">'.lang::get('Colour-marked individual').' '.($taxIdx+1).'</a></h3>';
    $r .= '<div id="'.$options['fieldprefix'].'individual:panel" class="individual_panel ui-helper-clearfix">';
    $r .= '<fieldset id="'.$options['fieldprefix'].'individual:fieldset" class="taxon_individual ui-corner-all">';
    // output the hiddens
    if (isset(data_entry_helper::$entity_to_load[$options['fieldprefix'].'subject_observation:id'])) {
      $r .= '<input type="hidden" id="'.$options['fieldprefix'].'subject_observation:id" name="'.$options['fieldprefix'].'subject_observation:id" '.
        'value="'.data_entry_helper::$entity_to_load[$options['fieldprefix'].'subject_observation:id'].'" />'."\n";    
    }
    if (isset(data_entry_helper::$entity_to_load[$options['fieldprefix'].'occurrences_subject_observation:id'])) {
      $r .= '<input type="hidden" id="'.$options['fieldprefix'].'occurrences_subject_observation:id" name="'.$options['fieldprefix'].'occurrences_subject_observation:id" '.
        'value="'.data_entry_helper::$entity_to_load[$options['fieldprefix'].'occurrences_subject_observation:id'].'" />'."\n";    
    }
    if (isset(data_entry_helper::$entity_to_load[$options['fieldprefix'].'occurrence:id'])) {
      $r .= '<input type="hidden" id="'.$options['fieldprefix'].'occurrence:id" name="'.$options['fieldprefix'].'occurrence:id" '.
        'value="'.data_entry_helper::$entity_to_load[$options['fieldprefix'].'occurrence:id'].'" />'."\n";    
    }
    // Check if Record Status is included as a control. If not, then add it as a hidden.
    $arr = helper_base::explode_lines($args['structure']);
    if (!in_array('[record status]', $arr)) {
      if (isset(data_entry_helper::$entity_to_load[$options['fieldprefix'].'occurrence:record_status'])) {
        $value = data_entry_helper::$entity_to_load[$options['fieldprefix'].'occurrence:record_status'];
      } else {
        $value = isset($args['defaults']['occurrence:record_status']) ? $args['defaults']['occurrence:record_status'] : 'C'; 
      }
      $r .= '<input type="hidden" id="'.$options['fieldprefix'].'occurrence:record_status" '.
        'name="'.$options['fieldprefix'].'occurrence:record_status" value="'.$value.'" />'."\n";    
    }
    // add subject type and count as a hidden
    $value = '';
    if (isset(data_entry_helper::$entity_to_load[$options['fieldprefix'].'subject_observation:subject_type_id'])) {
      $value = data_entry_helper::$entity_to_load[$options['fieldprefix'].'subject_observation:subject_type_id'];
    } else if (isset($args['subject_type_id'])) {
      $value = $args['subject_type_id']; 
    }
    if ($value!=='') {
      $r .= '<input type="hidden" id="'.$options['fieldprefix'].'subject_observation:subject_type_id" '.
        'name="'.$options['fieldprefix'].'subject_observation:subject_type_id" value="'.$value.'" />'."\n";
    }
    if (isset(data_entry_helper::$entity_to_load[$options['fieldprefix'].'subject_observation:count'])) {
      $value = data_entry_helper::$entity_to_load[$options['fieldprefix'].'subject_observation:count'];
    } else  {
      $value = '1'; 
    }
    if ($value!=='') {
      $r .= '<input type="hidden" id="'.$options['fieldprefix'].'subject_observation:count" '.
        'name="'.$options['fieldprefix'].'subject_observation:count" value="'.$value.'" />'."\n";
    }

    // output the species selection control
    $options['blankText'] = '<Please select>';
    if ($args['species_ctrl']=='autocomplete') {
      $temp = data_entry_helper::$javascript;
    }
    $r .= self::get_control_species($auth, $args, $tabalias, $options+array('validation' => array('required'), 'class' => 'select_taxon'));
    if ($args['species_ctrl']=='autocomplete') {
      if (!$options['inNewIndividual']) {
        $autoJavascript = substr(data_entry_helper::$javascript, strlen($temp));
      } else {
        data_entry_helper::$javascript = $temp;
      }
      unset($temp);
    } else {
      $autoJavascript = '';
    }
    // gender
    if ($options['genderId'] > 0
      && !empty($args['request_gender_values'])
      && count($args['request_gender_values']) > 0) {
      // filter the genders available
      $query = array('in'=>array('id', $args['request_gender_values']));
      $filter = array('query'=>json_encode($query),);
      $extraParams = array_merge($filter, $auth['read']);
      $r .= data_entry_helper::select(array_merge(array(
        'label' => lang::get('Sex of the bird'),
        'fieldname' => $options['fieldprefix'].'sjoAttr:'.$options['genderId'],
        'table'=>'termlists_term',
        'captionField'=>'term',
        'valueField'=>'id',
        'extraParams' => $extraParams,
      ), $options));
    }
    // age
    if ($options['stageId'] > 0
      && !empty($args['request_stage_values'])
      && count($args['request_stage_values']) > 0) {
      // filter the stages available
      $query = array('in'=>array('id', $args['request_stage_values']));
      $filter = array('query'=>json_encode($query),);
      $extraParams = array_merge($filter, $auth['read']);
      $r .= data_entry_helper::select(array_merge(array(
        'label' => lang::get('Age of the bird'),
        'fieldname' => $options['fieldprefix'].'sjoAttr:'.$options['stageId'],
        'table'=>'termlists_term',
        'captionField'=>'term',
        'valueField'=>'id',
        'extraParams' => $extraParams,
      ), $options));
    }
    // subject status
    if ($options['lifeStatusId'] > 0
      && !empty($args['request_life_status_values'])
      && count($args['request_life_status_values']) > 0) {
      // filter the life status's available
      $query = array('in'=>array('id', $args['request_life_status_values']));
      $filter = array('query'=>json_encode($query),);
      $extraParams = array_merge($filter, $auth['read']);
      $r .= data_entry_helper::select(array_merge(array(
        'label' => lang::get('Circumstances of this report'),
        'fieldname' => $options['fieldprefix'].'sjoAttr:'.$options['lifeStatusId'],
        'table'=>'termlists_term',
        'captionField'=>'term',
        'valueField'=>'id',
        'extraParams' => $extraParams,
      ), $options));
    }
    
    // output each required identifier
    $r .= '<div class="idn-accordion">';
    
    // setup and call function for neck collar
    $options['identifierName'] = '';
    $options['identifierTypeId'] = '';
    foreach ($options['identifierTypes'] as $identifier_type) {
      if ($identifier_type['id']==$args['neck_collar_type']) {
        $options['identifierName'] = $identifier_type['term'];
        $options['identifierTypeId'] = $identifier_type['id'];
        break;
      }
    }
    $options['identifierAttrList'] = array(
      $options['baseColourId'],
      $options['textColourId'],
      $options['sequenceId'],
    );
    $options['fieldprefix'] = 'idn:'.$taxIdx.':neck-collar:';
    $options['classprefix'] = 'idn-neck-collar-';
    $options['seq_maxlength'] = (!empty($args['neck_collar_max_length'])) ? $args['neck_collar_max_length'] : '';
    if (!empty($args['neck_collar_regex'])) {
      $options['seq_format_class'] = 'collarFormat';
    }
    $r .= self::get_control_identifier($auth, $args, $tabalias, $options);
    if (!empty($args['neck_collar_regex'])) {
      unset($options['seq_format_class']);
    }
    
    // setup and call function for left enscribed colour ring
    $options['identifierName'] = '';
    $options['identifierTypeId'] = '';
    foreach ($options['identifierTypes'] as $identifier_type) {
      if ($identifier_type['id']==$args['enscribed_colour_ring_type']) {
        $options['identifierName'] = $identifier_type['term'].lang::get(' (Left leg)');
        $options['identifierTypeId'] = $identifier_type['id'];
        break;
      }
    }
    $options['identifierAttrList'] = array(
      $options['baseColourId'],
      $options['textColourId'],
      $options['sequenceId'],
    );
    $options['fieldprefix'] = 'idn:'.$taxIdx.':colour-left:';
    $options['classprefix'] = 'idn-colour-left-';
    $options['seq_maxlength'] = (!empty($args['enscribed_colour_ring_max_length'])) ? $args['enscribed_colour_ring_max_length'] : '';
    if (!empty($args['enscribed_colour_ring_regex'])) {
      $options['seq_format_class'] = 'colourRingFormat';
    }
    $r .= self::get_control_identifier($auth, $args, $tabalias, $options);
    if (!empty($args['enscribed_colour_ring_regex'])) {
      unset($options['seq_format_class']);
    }
    
    // setup and call function for right enscribed colour ring
    $options['identifierName'] = '';
    $options['identifierTypeId'] = '';
    foreach ($options['identifierTypes'] as $identifier_type) {
      if ($identifier_type['id']==$args['enscribed_colour_ring_type']) {
        $options['identifierName'] = $identifier_type['term'].lang::get(' (Right leg)');
        $options['identifierTypeId'] = $identifier_type['id'];
        break;
      }
    }
    $options['identifierAttrList'] = array(
      $options['baseColourId'],
      $options['textColourId'],
      $options['sequenceId'],
    );
    $options['fieldprefix'] = 'idn:'.$taxIdx.':colour-right:';
    $options['classprefix'] = 'idn-colour-right-';
    $options['seq_maxlength'] = (!empty($args['enscribed_colour_ring_max_length'])) ? $args['enscribed_colour_ring_max_length'] : '';
    if (!empty($args['enscribed_colour_ring_regex'])) {
      $options['seq_format_class'] = 'colourRingFormat';
    }
    $r .= self::get_control_identifier($auth, $args, $tabalias, $options);
    if (!empty($args['enscribed_colour_ring_regex'])) {
      unset($options['seq_format_class']);
    }
    
    // setup and call function for metal ring
    $options['identifierName'] = '';
    $options['identifierTypeId'] = '';
    foreach ($options['identifierTypes'] as $identifier_type) {
      if ($identifier_type['id']==$args['metal_ring_type']) {
        $options['identifierName'] = $identifier_type['term'];
        $options['identifierTypeId'] = $identifier_type['id'];
        break;
      }
    }
    $options['identifierAttrList'] = array(
      $options['positionId'],
      $options['sequenceId'],
    );
    $options['fieldprefix'] = 'idn:'.$taxIdx.':metal:';
    $options['classprefix'] = 'idn-metal-';
    $options['seq_maxlength'] = (!empty($args['metal_ring_max_length'])) ? $args['metal_ring_max_length'] : '';
    $options['seq_maxlength'] = (!empty($args['metal_ring_max_length'])) ? $args['metal_ring_max_length'] : '';
    if (!empty($args['metal_ring_regex'])) {
      $options['seq_format_class'] = 'metalRingFormat';
    }
    $r .= self::get_control_identifier($auth, $args, $tabalias, $options);
    if (!empty($args['metal_ring_regex'])) {
      unset($options['seq_format_class']);
    }
    
    unset($options['seq_maxlength']);
    
    $r .= '</div>';    
    //---------------------------------
      
    // other devices (trackers etc.)
    if ($options['attachmentId'] > 0
        && !empty($args['other_devices'])
        && count($args['other_devices']) > 0) {
      // reset prefix
      $options['fieldprefix'] = 'idn:'.$taxIdx.':';
      // filter the devices available
      $query = array('in'=>array('id', $args['other_devices']));
      $filter = array('termlist_external_key'=>'indicia:assoc:identifier_type',);
      $filter = array('query'=>json_encode($query),);
      $extraParams = array_merge($filter, $auth['read']);
      $r .= data_entry_helper::checkbox_group(array_merge(array(
        'label' => lang::get('What other devices did you see on the bird'),
        'fieldname' => $options['fieldprefix'].'sjoAttr:'.$options['attachmentId'],
        'table'=>'termlists_term',
        'captionField'=>'term',
        'valueField'=>'id',
        'extraParams' => $extraParams,
      ), $options));
    }
    if ($args['observation_comment']) {
      $r .= self::get_control_observationcomment($auth, $args, $tabalias, $options);
    }
    // occurrence images
    $opts = array(
      'table'=>'occurrence_image',
      'label'=>lang::get('Upload your photos'),
    );
    if ($args['interface']!=='one_page')
      $opts['tabDiv']=$tabalias;
    $opts['resizeWidth'] = isset($options['resizeWidth']) ? $options['resizeWidth'] : 1600;
    $opts['resizeHeight'] = isset($options['resizeHeight']) ? $options['resizeHeight'] : 1600;
    $opts['caption'] = lang::get('Photos');
    $opts['id'] = 'idn:0';
    if ($options['inNewIndividual']) {
      $opts['codeGenerated'] = 'php';
    }
    $r .= data_entry_helper::file_box($opts);
        
    $r .= '</fieldset>';
    // output identifier visualisations
    $r .= '<div id="idn:'.$taxIdx.':neck-collar:colourbox" class="neck-collar-indentifier-colourbox ui-corner-all">&nbsp;</div>';
    $r .= '<div id="idn:'.$taxIdx.':colour-left:colourbox" class="colour-left-indentifier-colourbox ui-corner-all">&nbsp;</div>';
    $r .= '<div id="idn:'.$taxIdx.':colour-right:colourbox" class="colour-right-indentifier-colourbox ui-corner-all">&nbsp;</div>';
    $r .= '</div>';
    if (!$options['inNewIndividual']) {
      $r .= '</div>';
      if (is_null(data_entry_helper::$entity_to_load)) {
        $new_individual = $r;
      } else {
        $temp = data_entry_helper::$entity_to_load;
        data_entry_helper::$entity_to_load = null;
        $options['inNewIndividual'] = true;
        $new_individual = self::get_control_speciesidentifier($auth, $args, $tabalias, $options);
        data_entry_helper::$entity_to_load = $temp;
        unset($options['inNewIndividual']);
      }
      data_entry_helper::$javascript .= "window.indicia.wwt.newIndividual = '".str_replace(array('\'', "\n"), array('\\\'', ' '), $new_individual)."';\n";
      $opts['codeGenerated'] = 'js';
      $photoJavascript = data_entry_helper::file_box($opts);
      // save the javascript needed for an additional colour-marked individual
      // process it to sanitise the string and remove comments (works now but not 100% reliable)
      data_entry_helper::$javascript .= "window.indicia.wwt.newJavascript = '"
        .str_replace(array('\'', "\n"), array('\\\'', ' '), str_replace('\\', '\\\\', preg_replace('#^\s*//.+$#m', '', $photoJavascript)))
        .str_replace(array('\'', "\n", "\r"), array('\\\'', ' ', ' '), str_replace('\\', '\\\\', preg_replace('#^\s*//.+$#m', '', $autoJavascript)))."';\n";
      $r .= '<input type="button" id="idn:add-another" value="Add Another Colour-marked Bird" /><br />';
    }

    return $r;
  }
  
  /*
   * Get the colour identifier control
   */
  
  private static function get_control_identifier($auth, $args, $tabalias, $options) {
    $fieldPrefix = !empty($options['fieldprefix']) ? $options['fieldprefix'] : '';
    $r = '';
    $r .= '<h2><a href="#">'.$options['identifierName'].'</a></h2>';
    $r .= '<div id="'.$fieldPrefix.'panel" class="idn:accordion:panel">';
    $r .= '<input type="hidden" name="'.$fieldPrefix.'identifier:identifier_type_id" value="'.$options['identifierTypeId'].'" />'."\n";
    $r .= '<input type="hidden" name="'.$fieldPrefix.'identifier:identifier_name" id="'.$fieldPrefix.'identifier:identifier_name" value="'.$options['identifierName'].'" />'."\n";
    $r .= '<input type="hidden" name="'.$fieldPrefix.'identifier:coded_value" id="'.$fieldPrefix.'identifier:coded_value" class="identifier:coded_value" value="" />'."\n";
    $r .= '<input type="hidden" name="'.$fieldPrefix.'identifier:identifier_id" id="'.$fieldPrefix.'identifier:identifier_id" class="identifier_id" value="-1" />'."\n";
    if (isset(data_entry_helper::$entity_to_load[$fieldPrefix.'identifiers_subject_observation:id'])) {
      $r .= '<input type="hidden" id="'.$fieldPrefix.'identifiers_subject_observation:id" name="'.$fieldPrefix.'identifiers_subject_observation:id" '.
        'value="'.data_entry_helper::$entity_to_load[$fieldPrefix.'identifiers_subject_observation:id'].'" />'."\n";    
    }
    
    // temp checkbox - probably remove later?
    $r .= data_entry_helper::checkbox(array_merge(array(
      'label' => lang::get('Is this identifier being recorded?'),
      'fieldname' => $fieldPrefix.'identifier:checkbox',
      'class'=>'identifier_checkbox identifierRequired',
    ), $options));
      
    // loop through the requested attributes and output an appropriate control
    $classes = $options['class'];
    foreach ($options['identifierAttrList'] as $attrId) {
      // find the definition of this attribute
      $found = false;
      foreach ($options['idnAttributeTypes'] as $attrType) {
        if ($attrType['id']===$attrId) {
          $found = true;
          break;
        }
      }
      if (!$found) {
        throw new exception(lang::get('Unknown identifier attribute type id ['.$attrId.'] specified for '.
          $options['identifierName'].' in Identifier Attributes array.'));
      }
      // setup any data filters
      if ($options['baseColourId']==$attrId) {
        if (!empty($args['base_colours'])) {
          // filter the colours available
          $query = array('in'=>array('id', $args['base_colours']));
        }
        $attr_name = 'base-colour';
        $colourIdentifier = true;
      } elseif ($options['textColourId']==$attrId) {
        if (!empty($args['text_colours'])) {
          // filter the colours available
          $query = array('in'=>array('id', $args['text_colours']));
        }
        $attr_name = 'text-colour';
        $colourIdentifier = true;
      } elseif ($options['positionId']==$attrId) {
        $attr_name = 'position';
        if (count($args['position']) > 0) {
          // filter the identifier position available
          $query = array('in'=>array('id', $args['position']));
        }
      } elseif ($options['sequenceId']==$attrId) {
        $attr_name = 'sequence';
        $options['maxlength'] = $options['seq_maxlength'] ? $options['seq_maxlength'] : '';
        if ($options['seq_format_class']) {
          $options['class'] = empty($options['class']) ? $options['seq_format_class'] : 
            (strstr($options['class'], $options['seq_format_class']) ? $options['class'] : $options['class'].' '.$options['seq_format_class']);
        }
      }

      $options['class'] = empty($options['class']) ? $options['classprefix'].$attr_name : 
        (strstr($options['class'], $options['classprefix'].$attr_name) ? $options['class'] : $options['class'].' '.$options['classprefix'].$attr_name);
      if ($args['use_colour_picker']) {
        $options['class'] = empty($options['class']) ? 'select_colour' : 
          (strstr($options['class'], 'select_colour') ? $options['class'] : $options['class'].' select_colour');
      }
          
      switch ($attrType['data_type']) {
        case 'D':
        case 'V':
          $r .= data_entry_helper::date_picker(array_merge(array(
            'label' => lang::get($attrType['caption']),
            'fieldname' => $fieldPrefix.'idnAttr:'.$attrType['id'],
          ), $options));
          break;
        case 'L':
          $filter = array('termlist_id'=>$attrType['termlist_id'],);
          if (!empty($query)) {
            $filter += array('query'=>json_encode($query),);
          }
          $extraParams = array_merge($filter, $auth['read']);
          $r .= data_entry_helper::select(array_merge(array(
            'label' => lang::get($attrType['caption']),
            'fieldname' => $fieldPrefix.'idnAttr:'.$attrType['id'],
            'table'=>'termlists_term',
            'captionField'=>'term',
            'valueField'=>'id',
            'blankText' => '<Please select>',
            'extraParams' => $extraParams,
          ), $options));
          break;
        case 'B':
          $r .= data_entry_helper::checkbox(array_merge(array(
            'label' => lang::get($attrType['caption']),
            'fieldname' => $fieldPrefix.'idnAttr:'.$attrType['id'],
          ), $options));
          break;
        default:
          $r .= data_entry_helper::text_input(array_merge(array(
            'label' => lang::get($attrType['caption']),
            'fieldname' => $fieldPrefix.'idnAttr:'.$attrType['id'],
          ), $options));
      }
      $options['class'] = $classes;
      if (isset($options['maxlength'])) {
        unset($options['maxlength']);
      }
    }
    $r .= '</div>';

    return $r;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    // tidy away the OpenLayers fields which we don't need
    $ol_keys = preg_grep('/^OpenLayers_/', array_keys($values));
    foreach ($ol_keys as $ol_key) {
      unset($values[$ol_key]);
    }
    // build a simple sample submission
    $submission = submission_builder::build_submission($values, array('model'=>'sample',));
    // add observation/occurrence and identifier data to sample in submission
    $submission = self::add_observation_submissions($submission, $values, $args);
    // add new sample comment
    $submission = self::add_sample_comment_submissions($submission, $values);
    
    if (isset($args['debug_info']) && $args['debug_info']) {
      self::$submission = $submission;
    }
    return($submission);
  }
  
  /**
   * Adds the sample comment data to the submission array from the form values.
   * @param array $sample The sample submission. 
   * @param array $values Associative array of form data values. 
   * @return array Submission structure with the sample comment added.
   */
  private static function add_sample_comment_submissions($sample, $values) {
    if (array_key_exists('sample_comment:comment', $values) && $values['sample_comment:comment']!=='') {
      // add new sample comment
      $sample_comment = submission_builder::build_submission($values, array('model'=>'sample_comment',));
      // add to the main sample submission
      $sample['subModels'][] = array('fkId' => 'sample_id', 'model' => $sample_comment);
    }
    return $sample;
  }
  
  /**
   * Adds the observation data and identifiers (if new) to the submission array from the form values.
   * @param array $sample The sample submission. 
   * @param array $values Associative array of form data values. 
   * @param array $args Associative array of form configuration parameters. 
   * @return array Submission structure with observations/identifiers added.
   */
  private static function add_observation_submissions($sample, $values, $args) {
    // get submission for each observation and add it to the sample submission
    $keys = preg_grep('/^idn:[0-9]+:occurrence:taxa_taxon_list_id$/', array_keys($values));
    foreach ( $keys as $key )
    {
      // build the observation submission
      $key_parts = explode(':', $key);
      $idx = $key_parts[1];
      $so_keys = preg_grep('/^idn:'.$idx.':(subject_observation|occurrence|occurrences_subject_observation|occAttr|sjoAttr):/', array_keys($values));
      foreach ($so_keys as $so_key) {
        $so_key_parts = explode(':', $so_key, 3);
        $values[$so_key_parts[2]] = $values[$so_key];
      }
      $so = submission_builder::build_submission($values, array('model'=>'subject_observation',));
      // create submodel for join to occurrence and add it
      $oso = self::build_occurrence_observation_submission($values);
      $so['subModels'][] = array('fkId' => 'subject_observation_id', 'model' => $oso,);
      // create submodel for each join to identifier (plus identifier models if new) and add it
      foreach (array('neck-collar', 'colour-left', 'colour-right', 'metal') as $identifier_type) {
        $ident_keys = preg_grep('/^idn:'.$idx.':'.$identifier_type.':(identifier|identifiers_subject_observation|idnAttr):/', array_keys($values));
        foreach ($ident_keys as $i_key) {
          $i_key_parts = explode(':', $i_key, 4);
          $values[$i_key_parts[3]] = $values[$i_key];
        }
        // if identifier checkbox set, this identifier is being reported
        if ($values['identifier:checkbox']==1) {
          $iso = self::build_identifier_observation_submission($values);
          $so['subModels'][] = array('fkId' => 'subject_observation_id', 'model' => $iso,);
        }
        // clean up the flattened keys
        foreach ($ident_keys as $i_key) {
          $i_key_parts = explode(':', $i_key, 4);
          unset($values[$i_key_parts[3]]);
        }
      }
      // clean up the flattened subject_observation keys
      foreach ($so_keys as $so_key) {
        $so_key_parts = explode(':', $so_key, 3);
        unset($values[$so_key_parts[2]]);
      }
      // add it all to the main sample submission
      $sample['subModels'][] = array('fkId' => 'sample_id', 'model' => $so,);
    }
    return $sample;
  }
    
  /**
   * Builds a submission for occurrences_subject_observation join data from the form values.
   * @param array $values Associative array of form data values. 
   * @return array occurences_subject_observation Submission structure.
   */
  private static function build_occurrence_observation_submission($values) {
    // provide defaults if these keys not present
    $values = array_merge(array(
      ), $values);
    
    // build submission
    $submission = submission_builder::build_submission($values, array('model'=>'occurrences_subject_observation',));
      
    // add super model for occurrence
    // provide defaults if these keys not present
    // Todo: get sample_id from somewhere???
    $values = array_merge(array(
      'occurrence:sample_id' => 0, // place holder, this will be populated in subject_observation model
      ), $values);

    // build submission
    $occ =  submission_builder::build_submission($values, array('model'=>'occurrence',));
    $submission['superModels'] = array(
      array('fkId' => 'occurrence_id', 'model' => $occ,),
    );
    
    return $submission;
  }
  
  /**
   * Builds a submission for identifiers_subject_observation join data 
   * from the form values. Also adds identifier if it doesn't exist.
   * @param array $values Associative array of form data values. 
   * @return array occurences_subject_observation Submission structure.
   */
  private static function build_identifier_observation_submission($values) {
    // provide defaults if these keys not present
    $values = array_merge(array(
      'identifiers_subject_observation:verified_status' => 'U',
      'identifiers_subject_observation:matched' => $values['identifier:identifier_id']!==-1,
      ), $values);

    // build submission
    $submission = submission_builder::build_submission(
      $values, array('model'=>'identifiers_subject_observation',));
      
    // add super model for identifier if it doesn't exist
    if ($values['identifier:identifier_id']==='-1') {
      // provide defaults if these keys not present
      $values = array_merge(array(
        'identifier:status' => 'U',
        ), $values);
  
      // build submission
      $i =  submission_builder::build_submission($values, array('model'=>'identifier',));
      $submission['superModels'] = array(
        array('fkId' => 'identifier_id', 'model' => $i,),
      );
    } else {
      if (empty($submission['fields']['identifier_id'])) {
        $submission['fields']['identifier_id'] = $values['identifier:identifier_id'];
      }
    }
    return $submission;
  }
  
  /**
   * Retrieves a list of the css files that this form requires in addition to the standard
   * Drupal, theme or Indicia ones.
   * 
   * @return array List of css files to include for this form.
   */
  public static function get_css() {
    return array();
  }
  
  /**
   * Convert the unstructured textarea of default values into a structured array.
   */
  protected static function parse_defaults(&$args) {
    $result=array();
    if (isset($args['defaults']))
      $result = helper_base::explode_lines_key_value_pairs($args['defaults']);     
    $args['defaults']=$result;
  }
  
  /**
   * Returns true if this form should be displaying a multiple subject observation entry grid.
   */
  protected static function getGridMode($args) {
    // if loading an existing sample and we are allowed to display a grid or single species selector
    if ($args['multiple_subject_observation_mode']=='either') {
      // Either we are in grid mode because we were instructed to externally, or because the form is reloading
      // after a validation failure with a hidden input indicating grid mode.
      return isset($_GET['gridmode']) || 
          isset(data_entry_helper::$entity_to_load['gridmode']) ||
          ((array_key_exists('sample_id', $_GET) && $_GET['sample_id']!='{sample_id}') &&
           (!array_key_exists('subject_observation_id', $_GET) || $_GET['subject_observation_id']=='{subject_observation_id}'));
    } else
      return 
          // a form saved using a previous version might not have this setting, so default to grid mode=true
          (!isset($args['multiple_subject_observation_mode'])) ||
          // Are we fixed in grid mode?
          $args['multiple_subject_observation_mode']=='multi';
  }
  
  /**
   * When viewing the list of samples for this user, get the grid to insert into the page.
   */
  protected static function getSampleListGrid($args, $node, $auth, $attributes) {
    global $user;
    // get the CMS User ID attribute so we can filter the grid to this user
    /*
    foreach($attributes as $attrId => $attr) {
      if (strcasecmp($attr['caption'],'CMS User ID')==0) {
        $userIdAttr = $attr['attributeId'];
        break;
      }
    }
    */
    if ($user->uid===0) {
      // Return a login link that takes you back to this form when done.
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">login</a> to the website.');
    }
    // use drupal profile to get warehouse user id
    if (function_exists('profile_load_profile')) {
      profile_load_profile($user);
      $userId = $user->profile_indicia_user_id;
    }
    if (!isset($userId)) {
      return lang::get('This form must be used with the indicia \'Easy Login\' module so records can '.
          'be tagged against the warehouse user id.');
    }
    if (isset($args['grid_report']))
      $reportName = $args['grid_report'];
    else
      // provide a default in case the form settings were saved in an old version of the form
      $reportName = 'reports_for_prebuilt_forms/simple_subject_observation_identifier_list_1';
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
      'itemsPerPage' =>(isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 10),
      'autoParamsForm' => true,
      'extraParams' => array(
        'survey_id'=>$args['survey_id'], 
        'userID'=>$userId,
      )
    ));    
    $r .= '<form>';    
    if (isset($args['multiple_subject_observation_mode']) && $args['multiple_subject_observation_mode']=='either') {
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
  }
  
  protected function getReportActions() {
    return array(array('display' => 'Actions', 'actions' => 
        array(array('caption' => lang::get('Edit'), 'url'=>'{currentUrl}', 'urlParams'=>array('sample_id'=>'{sample_id}','subject_observation_id'=>'{subject_observation_id}')))));
  }
  
  /*
   * helper function to return a proxy-aware warehouse url
   */
  protected function warehouseUrl() {
    return !empty(data_entry_helper::$warehouse_proxy) ? data_entry_helper::$warehouse_proxy : data_entry_helper::$base_url;
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