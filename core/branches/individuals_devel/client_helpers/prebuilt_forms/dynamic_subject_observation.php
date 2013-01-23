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
require_once('includes/individuals.php');

class iform_dynamic_subject_observation extends iform_dynamic {

  // The ids we are loading if editing existing data
  protected static $loadedSampleId;
  protected static $loadedOccurrenceId;
  protected static $occurrenceIds = array();
protected static $loadedSubjectObservationId;
protected static $subjectObservationIds;
protected static $submission = array();
  /**
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_dynamic_subject_observation_definition() {
    return array(
      'title'=>'Subject observation form',
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
                "&nbsp;&nbsp;<strong>[species attributes]</strong> - any custom attributes for the occurrence, if not using the grid. Also includes a file upload ".
                "box if relevant. The attrubutes @resizeWidth and @resizeHeight can specified on subsequent lines, otherwise they default to 1600.<br/>".
                "&nbsp;&nbsp;<strong>[date]</strong> - a sample must always have a date.<br/>".
                "&nbsp;&nbsp;<strong>[map]</strong> - a map that links to the spatial reference and location select/autocomplete controls<br/>".
                "&nbsp;&nbsp;<strong>[spatial reference]</strong> - a sample must always have a spatial reference.<br/>".
                "&nbsp;&nbsp;<strong>[location name]</strong> - a text box to enter a place name.<br/>".
                "&nbsp;&nbsp;<strong>[location autocomplete]</strong> - an autocomplete control for picking a stored location. A spatial reference is still required.<br/>".
                "&nbsp;&nbsp;<strong>[location select]</strong> - a select control for picking a stored location. A spatial reference is still required.<br/>".
                "&nbsp;&nbsp;<strong>[location map]</strong> - combines location select, map and spatial reference controls for recording only at stored locations.<br/>".
                "&nbsp;&nbsp;<strong>[place search]</strong> - zooms the map to the entered location.<br/>".
                "&nbsp;&nbsp;<strong>[recorder names]</strong> - a text box for names. The logged-in user's id is always stored with the record.<br/>".
                "&nbsp;&nbsp;<strong>[record status]</strong> - allow recorder to mark record as in progress or complete<br/>".
                "&nbsp;&nbsp;<strong>[sample comment]</strong> - a text box for sample level comment. (Each occurrence may also have a comment.) <br/>".
                "&nbsp;&nbsp;<strong>[sample photo]</strong>. - a photo upload for sample level images. (Each occurrence may also have photos.) <br/>".
            "<strong>@option=value</strong> on the line(s) following any control allows you to override one of the options passed to the control. The options ".
            "available depend on the control. For example @label=Abundance would set the untranslated label of a control to Abundance. Where the ".
            "option value is an array, use valid JSON to encode the value. For example an array of strings could be passed as @occAttrClasses=[\"class1\",\"class2\"] ".
            "or a keyed array as @extraParams={\"preferred\":\"true\",\"orderby\":\"term\"}. " .
            "Other common options include helpText (set to a piece of additional text to display alongside the control) and class (to add css ".
            "classes to the control such as control-width-3). <br/>".
            "<strong>[*]</strong> is used to make a placeholder for putting any custom attributes that should be inserted into the current tab. When this option is ".
            "used, you can change any of the control options for an individual custom attribute control by putting @control|option=value on the subsequent line(s). ".
            "For example, if a control is for smpAttr:4 then you can update it's label by specifying @smpAttr:4|label=New Label on the line after the [*]. ".
            "You can define the value for a control using the standard replacement tokens for user data, namely {user_id}, {username}, {email} and {profile_*}; ".
            "replace * in the latter to construct an existing profile field name. For example you could set the default value of an email input using @smpAttr:n|default={email} ".
            "where n is the attribute ID.<br/>".
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
          'name'=>'neck_collar_type',
          'caption'=>'Neck Collar Type',
          'description'=>'The type of identifier which indicates a neck collar.',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_type'),
          'required' => true,
          'helpText' => 'The helptext. Todo: change this once you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'neck_collar_position',
          'caption'=>'Neck Collar Position',
          'description'=>'The body position to record for a neck collar.',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_position','orderby'=>'sort_order'),
          'required' => true,
          'helpText' => 'The helptext. Todo: change this once you see where it shows on screen!!',
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
              'Eg. ^([A-Z]{2}[0-9]{2}|[A-Z]{3}[0-9])$ would only permit sequences of either 2 uppercase letters followed by 2 digits, '.
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
          'name'=>'right_enscribed_colour_ring_position',
          'caption'=>'Right Leg Enscribed Colour Ring Position',
          'description'=>'The body position to record for an enscribed colour ring (\'darvic\') on the right leg.',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_position','orderby'=>'sort_order'),
          'required' => true,
          'helpText' => 'The helptext. Todo: change this once you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'left_enscribed_colour_ring_position',
          'caption'=>'Left Leg Enscribed Colour Ring Position',
          'description'=>'The body position to record for an enscribed colour ring (\'darvic\') on the left leg.',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_position','orderby'=>'sort_order'),
          'required' => true,
          'helpText' => 'The helptext. Todo: change this once you see where it shows on screen!!',
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
              'Eg. ^([A-Z]{2}[0-9]{2}|[A-Z]{3}[0-9])$ would only permit sequences of either 2 uppercase letters followed by 2 digits, '.
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
          'name'=>'metal_ring_position',
          'caption'=>'Metal Ring Position',
          'description'=>'The body position to record for a metal ring.',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_position','orderby'=>'sort_order'),
          'required' => true,
          'helpText' => 'The helptext. Todo: change this once you see where it shows on screen!!',
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
              'Eg. ^([A-Z]{2}[0-9]{2}|[A-Z]{3}[0-9])$ would only permit sequences of either 2 uppercase letters followed by 2 digits, '.
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
          'name'=>'neck_collar_conditions',
          'caption'=>'Neck Collar Conditions',
          'description'=>'The identifier conditions we want to be reportable by recorders when observing a neck collar. Tick all that apply.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_condition','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'coloured_ring_conditions',
          'caption'=>'Coloured Ring Conditions',
          'description'=>'The identifier conditions we want to be reportable by recorders when observing a coloured ring Tick all that apply.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_condition','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'metal_ring_conditions',
          'caption'=>'Metal Ring Conditions',
          'description'=>'The identifier conditions we want to be reportable by recorders when observing a metal ring Tick all that apply.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_condition','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
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
          'name'=>'default_gender',
          'caption'=>'Default Gender',
          'description'=>'What (if any) gender should be the default for the colour-marked individual?',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'blankText'=>'No default',
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
          'name'=>'default_stage',
          'caption'=>'Default Age',
          'description'=>'What (if any) age/stage should be the default for the colour-marked individual?',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'blankText'=>'No default',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:stage','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Subject observation',
        ),
        array(
          'name'=>'request_life_status_values',
          'caption'=>'Request Subject Status Values',
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
          'name'=>'default_life_status',
          'caption'=>'Default Subject Status',
          'description'=>'What (if any) subject status should be the default for the colour-marked individual?',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'blankText'=>'No default',
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

public static function get_perms($nid) {
    return array(
      'IForm n'.$nid.' enter data by proxy',
    );
  }


  
  
public function get_tabs(&$tabs,$auth,$args,$attributes){
//return self::get_form_html($args, $auth, $attributes);

$hiddens=self::get_hiddens($args,$attributes);//get html for hiddens
    $r = "<div id=\"controls\">\n";

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
    if (method_exists(self::$called_class, 'getFooter'))
      $r .= call_user_func(array(self::$called_class, 'getFooter'), $args);
    
    if (method_exists(self::$called_class, 'link_species_popups')) 
      $r .= call_user_func(array(self::$called_class, 'link_species_popups'), $args);

    return $r;
        }

  /**
   * Determine whether to show a gird of existing records or a form for either adding a new record or editing an existing one.
   * @param array $args iform parameters.
   * @param object $node node being shown.
   * @return const The mode [MODE_GRID|MODE_NEW|MODE_EXISTING].
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

public function get_hiddens($args,$attributes){
    // Get authorisation tokens to update the Warehouse, plus any other hidden data.
    $hiddens = self::$auth['write'].
          "<input type=\"hidden\" id=\"read_auth_token\" name=\"read_auth_token\" value=\"".self::$auth['read']['auth_token']."\" />\n".
          "<input type=\"hidden\" id=\"read_nonce\" name=\"read_nonce\" value=\"".self::$auth['read']['nonce']."\" />\n".
          "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n".
          "<input type=\"hidden\" id=\"survey_id\" name=\"survey_id\" value=\"".$args['survey_id']."\" />\n";



if (!empty($args['sample_method_id'])){$hiddens .= '<input type="hidden" name="sample:sample_method_id" value="'.$args['sample_method_id'].'"/>';}
if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      $hiddens .= "<input type=\"hidden\" id=\"sample:id\" name=\"sample:id\" value=\"".data_entry_helper::$entity_to_load['sample:id']."\" />\n";    
      
  $existing=(self::$mode==MODE_EXISTING && (self::$loadedSampleId || self::$loadedSubjectObservationId))?true:false;
 $hiddens .= get_user_profile_hidden_inputs($attributes, $args, $existing, self::$auth['read']);
 
      return $hiddens;
}
}


  protected static function getHidden ($args) {
    $hiddens = '';
    if (!empty($args['sample_method_id'])) {
      $hiddens .= '<input type="hidden" name="sample:sample_method_id" value="'.$args['sample_method_id'].'"/>' . PHP_EOL;
    }
    if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      $hiddens .= '<input type="hidden" id="sample:id" name="sample:id" value="' . data_entry_helper::$entity_to_load['sample:id'] . '" />' . PHP_EOL;
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
  protected static function get_control_species($auth, $args, $tabalias, $options) {
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
        'occurrenceConfidential'=>(isset($args['occurrence_confidential']) ? $args['occurrence_confidential'] : false),
        'occurrenceImages'=>$args['occurrence_images'],
        'PHPtaxonLabel' => true,
        'language' => iform_lang_iso_639_2($user->lang), // used for termlists in attributes
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
  protected static function get_control_samplecomment($auth, $args, $tabalias, $options) {
    return data_entry_helper::textarea(array_merge(array(
      'fieldname'=>'sample:comment',
      'label'=>lang::get('Overall Comment')
    ), $options));
  }

  /**
   * Get the sample photo control
   */
  protected static function get_control_samplephoto($auth, $args, $tabalias, $options) {
    return data_entry_helper::file_box(array_merge(array(
      'table'=>'sample_image',
      'caption'=>lang::get('Overall Photo')
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
   * Get the sref by way of choosing a location.
   */
  protected static function get_control_locationmap($auth, $args, $tabalias, $options) {
    // add a location select control
    $options = array_merge(array(
        'searchUpdatesSref' => true,
        'validation' => "required",
        'blankText' => "Select...",
    ), $options);
    $r = self::get_control_locationselect($auth, $args, $tabalias, $options);

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
    $r .= self::get_control_map($auth, $args, $tabalias, $options);

    return $r;
  }
//
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

    // Get the CMS User ID attribute so we can filter the grid to this user
    foreach($attributes as $attrId => $attr) {
      if (strcasecmp($attr['caption'],'CMS User ID')==0) {
        $userIdAttr = $attr['attributeId'];
        break;
      }
      if (isset($userIdAttr)) $filter = array (
          'survey_id' => $args['survey_id'],
          'userID_attr_id' => $userIdAttr,
          'userID' => $user->uid,
          'iUserID' => 0);
    }
    // Alternatively get the Indicia User ID and use that instead
    if (function_exists('hostsite_get_user_field')) {
      $iUserId = hostsite_get_user_field('indicia_user_id');
      if (isset($iUserId)) $filter = array (
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
 /*
   * helper function to reload data for existing sample 
   * @param $loadedSampleId Required. id for required sample.
   * if not supplied, all subject_observations in the sample are loaded
   * @return array of data values matching the form control names. 
   */

  public static function reload_form_data($loadedSampleId, $args, $auth) {
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
//RFJ      $filter = array('id'=>self::$subjectObservationIds[0]);
//RFJ      self::$subjectObservationIds = array();
    }

  //Actually need to account for parent_ids too so that they are in order
    // load the subject_observation(s) for this sample
    $options = array(
      'table' => 'subject_observation',
      'extraParams' => $auth['read'] + array('sample_id'=>$loadedSampleId, 'view'=>'detail') + $filter,
      'nocache' => true,
    );
    $subjectObservations = data_entry_helper::get_population_data($options);
//    file_put_contents('/var/www/vhosts/monitoring.wwt.org.uk/httpdocs/recording/tmp/debug.txt',print_r($subjectObservations,true));

    // add each subject_observation to the form data
    //needs to get EACH linked table separately for each sobs
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
	self::rfj_get_subject_observation_attribute_values($auth,$form_data,$subjectObservations);  
    
    
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

    // xxxxxxxxxx
    $query = array('in'=>array('id', $occurrenceIds));
    $filter = array('query'=>json_encode($query),);
    $options = array(
      'table' => 'occurrence',
      'extraParams' => $auth['read'] + array('view'=>'detail') + $filter,
      'nocache' => true,
    );
    $occurrences = data_entry_helper::get_population_data($options);
    // xxxxxxxxxx
    $query = array('in'=>array('occurrence_id', $occurrenceIds));
    $filter = array('query'=>json_encode($query),);
    $options = array(
      'table' => 'occurrence_image',
      'extraParams' => $auth['read'] + array('view'=>'list') + $filter,
      'nocache' => true,
    );
    $occurrence_images = data_entry_helper::get_population_data($options);

    // add each occurrence, occurrences_subject_observation and occurrence_image to the form data
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
          foreach ($occurrence_images as $occurrence_image) {
            if ($oso['occurrence_id']===$occurrence_image['occurrence_id']) {
              $fieldprefix = 'idn:'.$idx.':occurrence_image:';
              $fieldsuffix = ':'.$occurrence_image['path'];
              $keys = array_keys($occurrence_image);
              foreach ($keys as $key) {
                $form_data[$fieldprefix.$key.$fieldsuffix] = $occurrence_image[$key];
              }
            }
          }
        }
      }
    }
    


    // load the identifiers_subject_observation(s) for this sample
    $isos=self::rfj_populate($auth,'subject_observation_id',self::$subjectObservationIds,'identifiers_subject_observation');
    // load the identifiers_subject_observation_attributes(s) for this sample
    $isoIds = array();
    foreach ($isos as $iso) {
      $isoIds[] = $iso['id'];
    }

    // load the identifiers_subject_observation_attribute_value(s) for this sample
    $isoAttrs=self::rfj_populate($auth,'identifiers_subject_observation_id',$isoIds,'identifiers_subject_observation_attribute_value','list');

    // load the identifier(s) for this sample
    $identifierIds = array();
    foreach ($isos as $iso) {
      $identifierIds[] = $iso['identifier_id'];
    }

   // load the identifiers for this sample
    $identifiers =self::rfj_populate($auth,'id',$identifierIds,'identifier','detail');
    
   // load the identifier_attributes(s) for this sample
    $idnAttrs=self::rfj_populate($auth,'identifier_id',$identifierIds,'identifier_attribute_value','list');

   // add each identifier to the form data
    for ($idx=0; $idx<count($subjectObservations); $idx++) {
      $subjectObservation=$subjectObservations[$idx];
      // prefix the keys and load to form data
      foreach ($isos as $iso) {
        if ($iso['subject_observation_id']===$subjectObservation['id']) {
          foreach ($identifiers as $identifier) {
            if ($iso['identifier_id']===$identifier['id']) {
              switch($identifier['identifier_type_id']){
              	case $args['neck_collar_type'] :
              		$identifier_type = 'neck-collar';
              		break;
              	case $args['enscribed_colour_ring_type'] :
              		$identifier_type = 'colour-ring';
              		break;
              	case $args['metal_ring_type'] :
	              	$identifier_type = 'metal';
              		break;
              	default:
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

              $fieldprefix = 'idn:'.$idx.':'.$identifier_type.':isoAttr:';
              foreach ($isoAttrs as $isoAttr) {
                if ($iso['id']===$isoAttr['identifiers_subject_observation_id']) {
                  if (!empty($isoAttr['id'])) {
                    $form_data[$fieldprefix.$isoAttr['identifiers_subject_observation_attribute_id'].':'.$isoAttr['id']] = $isoAttr['raw_value'];
                  }
                }
              }
            }
          }
        }
      }
    }

    return $form_data;
  }


  public function rfj_check_speciesidentifier_javascript($options,$args){
    // configure the identifiers javascript
    // write it late so it happens after any locked values are applied
    if (!$options['inNewIndividual']) {
      data_entry_helper::$late_javascript .= "indicia.wwt.initForm (
        '".$options['baseColourId']."',
        '".$options['textColourId']."',
        '".$options['sequenceId']."',
        '".$options['positionId']."',
        '".$args['default_leg_vertical']."',
        '".(!empty($args['neck_collar_regex']) ? $args['neck_collar_regex'] : '')."',
        '".(!empty($args['enscribed_colour_ring_regex']) ? $args['enscribed_colour_ring_regex'] : '')."',
        '".(!empty($args['metal_ring_regex']) ? $args['metal_ring_regex'] : '')."',
        '".($args['clientSideValidation'] ? 'true' : 'false')."',
        '".($args['subjectAccordion'] ? 'true' : 'false')."'\n".
        ");\n";
    }
  
  }
  
    public function rfj_check_speciesidentifier_attributes_present($options){
    // throw an exception if any of the required custom attributes is missing
    $errorMessages = array();
    foreach (array('baseColourId', 'textColourId', 'sequenceId', 'positionId', 
      'attachmentId', 'genderId', 'stageId', 'lifeStatusId', 'conditionsId', ) as $attrId) {
      if ($options[$attrId]===-1) {
        $errorMessages[] = lang::get('Required custom attribute for '.$attrId.' has not been found. '
        .'Please check this has been created on the warehouse and is associated with the correct system function.');
      }
    }
    if (count($errorMessages)>0) {
      $errorMessage = implode('<br />', $errorMessages);
      throw new exception($errorMessage);
    }
    
  }
  public function rfj_individual_template($options,$auth,$tabalias,$args,$opts){
//      $r = '</div>'; #  Here is the end of the accordian panel
###    $r .= '</fieldset>';// Family Panel
      $temp = data_entry_helper::$entity_to_load;
      data_entry_helper::$entity_to_load = null;
      $options['inNewIndividual'] = true;
      $options['lockable'] = $options['identifiers_lockable'];
      $new_individual = self::get_control_individuals($auth, $args, $tabalias, $options);
      unset($options['lockable']);
      $opts['codeGenerated'] = 'js';
      $photoJavascript = data_entry_helper::file_box($opts);
      data_entry_helper::$entity_to_load = $temp;
      unset($options['inNewIndividual']);
        
      data_entry_helper::$javascript .= "window.indicia.wwt.newIndividual = '".str_replace(array('\'', "\n"), array('\\\'', ' '), $new_individual)."';\n";
      // save the javascript needed for an additional colour-marked individual
      // process it to sanitise the string and remove comments (works now but not 100% reliable)
      data_entry_helper::$javascript .= "window.indicia.wwt.newJavascript = '"
        .str_replace(array('\'', "\n"), array('\\\'', ' '), str_replace('\\', '\\\\', preg_replace('#^\s*//.+$#m', '', $photoJavascript)))
        .str_replace(array('\'', "\n", "\r"), array('\\\'', ' ', ' '), str_replace('\\', '\\\\', preg_replace('#^\s*//.+$#m', '', $autoJavascript)))."';\n";
//      $r .= '</fieldset>';  
      $r .= '<input type="button" id="idn:add-another" class="ui-state-default ui-corner-all" '
        .'value="'.lang::get('Add Another Bird at the Same Date and Location').'" />';  
        return $r;
  }

  
  public function rfj_species_identifier_init(&$options,&$filter,&$dataOpts,$auth){
    // we need to control which items are lockable if locking requested
    if (!empty($options['lockable']) && $options['lockable']==true) {
      $options['identifiers_lockable'] = $options['lockable'];
    } else {
      $options['identifiers_lockable'] = '';
    }
    unset($options['lockable']);
    // get the identifier type data
    $filter = array(
      'termlist_external_key' => 'indicia:assoc:identifier_type',
    );
    $dataOpts = array(
      'table' => 'termlists_term',
      'extraParams' => $auth['read'] + $filter,
    );
    $options['identifierTypes'] = data_entry_helper::get_population_data($dataOpts);
    
    // get the identifier attribute type data
    $dataOpts = array(
      'table' => 'identifier_attribute',
      'extraParams' => $auth['read'],
    );
    $options['idnAttributeTypes'] = data_entry_helper::get_population_data($dataOpts);
    
    // set up the known system types for identifier attributes
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
    
    // get the subject observation attribute type data
    $dataOpts = array(
      'table' => 'subject_observation_attribute',
      'extraParams' => $auth['read'],
    );
    $options['sjoAttributeTypes'] = data_entry_helper::get_population_data($dataOpts);
//    file_put_contents('/var/www/vhosts/monitoring.wwt.org.uk/httpdocs/recording/tmp/debug.txt',print_r($options['sjoAttributeTypes'],true));
    // set up the known system types for subject_observation attributes
    $options['attachmentId'] = -1;
    $options['genderId'] = -1;
    $options['stageId'] = -1;
    $options['lifeStatusId'] = -1;
    $options['unmarkedAdults'] = -1; // not a system function
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
//          case 'unmarked_adults' :
//            $options['unmarkedAdults'] = $sjoAttributeType['id'];
//            break;
        }
      }
    }
    
    // get the identifiers subject observation attribute type data
    $dataOpts = array(
      'table' => 'identifiers_subject_observation_attribute',
      'extraParams' => $auth['read'],
    );
    $options['isoAttributeTypes'] = data_entry_helper::get_population_data($dataOpts);
    
    // set up the known system types for subject_observation attributes
    $options['conditionsId'] = -1;
    foreach ($options['isoAttributeTypes'] as $isoAttributeType) {
      if (!empty($isoAttributeType['system_function'])) {
        switch ($isoAttributeType['system_function']) {
          case 'identifier_condition' :
            $options['conditionsId'] = $isoAttributeType['id'];
            break;
        }
      }
    }
      
  }
   public static function rfj_set_mode($args) {
    self::$mode = (isset($args['no_grid']) && $args['no_grid'])     
        ? MODE_NEW_SAMPLE // default mode when no_grid set to true - display new sample
        : MODE_GRID; // default mode when no grid set to false - display grid of existing data
                // mode MODE_EXISTING: display existing sample

    if ($_POST) {
      if(!array_key_exists('website_id', $_POST)) { // non Indicia POST, in this case must be the location allocations. add check to ensure we don't corrupt the data by accident
        if(function_exists('iform_loctools_checkaccess') && iform_loctools_checkaccess(self::$node,'admin') && array_key_exists('mnhnld1', $_POST)){
          iform_loctools_deletelocations(self::$node);
          foreach($_POST as $key => $value){
            $parts = explode(':', $key);
            iform_loctools_insertlocation(self::$node, $parts[2], $parts[1]);
          }
        }
      } else if(!is_null(data_entry_helper::$entity_to_load)) {
        self::$mode = MODE_EXISTING; // errors with new sample, entity populated with post, so display this data.
      } // else valid save, so go back to gridview: default mode 0
    }
    if (array_key_exists('sample_id', $_GET) && $_GET['sample_id']!='{sample_id}') {
      self::$mode = MODE_EXISTING;
      self::$loadedSampleId = $_GET['sample_id'];
    }
    //Subject id from get params
    if (array_key_exists('subject_observation_id', $_GET) && $_GET['subject_observation_id']!='{subject_observation_id}') {
      self::$mode = MODE_EXISTING;
      // single subject_observation case
      self::$loadedSubjectObservationId = $_GET['subject_observation_id'];
    } 
    if (self::$mode!=MODE_EXISTING && array_key_exists('newSample', $_GET)) {
      self::$mode = MODE_NEW_SAMPLE;
      data_entry_helper::$entity_to_load = array();
      self::$subjectObservationIds = array(self::$loadedSubjectObservationId);

    } // else default to mode MODE_GRID or MODE_NEW_SAMPLE depending on no_grid parameter
//    self::$mode = $mode;
 

   }
public function rfj_optional_buttons(){
// These should be enabled via the form definition
$r='';
    // reset button
    $r .= '<input type="button" class="ui-state-default ui-corner-all" value="'.lang::get('Abandon Form and Reload').'" '
      .'onclick="window.location.href=\''.url('node/'.(self::$node->nid), array('query' => 'newSample')).'\'">';    
    // clear all padlocks button
    $r .= ' <input type="button" class="ui-state-default ui-corner-all" value="'.lang::get('Clear All Padlocks').'" '
      .'onclick="if (indicia && indicia.locks) indicia.locks.unlockRegion(\'body\');">';    
      return $r;
}
public function rfj_client_validate($args){
    // request automatic JS validation
    if (!isset($args['clientSideValidation']) || $args['clientSideValidation']) {
      data_entry_helper::enable_validation('entry_form');
      // override the default invalidHandler to activate the first accordion panels which has an error
      global $indicia_templates;  
      $indicia_templates['invalid_handler_javascript'] = "function(form, validator) {
          var tabselected=false;
          var accordion$=jQuery('.ui-accordion');
          jQuery.each(validator.errorMap, function(ctrlId, error) {
            // select the tab containing the first error control
            var ctrl = jQuery('[name=' + ctrlId.replace(/:/g, '\\\\:').replace(/\[/g, '\\\\[').replace(/]/g, '\\\\]') + ']');
            if (!tabselected && typeof(tabs)!=='undefined') {
              tabs.tabs('select',ctrl.filter('input,select').parents('.ui-tabs-panel')[0].id);
              tabselected = true;
            }
            ctrl.parents('fieldset').removeClass('collapsed');
            ctrl.parents('.fieldset-wrapper').show();
            // for each accordion, activate the first panel which has an error
            ctrl.parents('.ui-accordion-content').each(function (n) {
              var acc$ = $(this).closest('.ui-accordion');
              var accId = acc$[0].id.replace(/:/g, '\\\\:').replace(/\[/g, '\\\\[').replace(/]/g, '\\\\]');
              if (accordion$.is('#'+accId)) {
                var header$ = $(this).prev('h3');
                var accHeaderId = header$.attr('id').replace(/:/g, '\\\\:').replace(/\[/g, '\\\\[').replace(/]/g, '\\\\]');
                acc$.accordion('activate', '#'+accHeaderId);
                accordion$ = accordion$.not('#'+accId);
              }
            });
          });
        }";
      // By default, validate doesn't validate any ':hidden' fields, 
      // but we need to validate hidden with display: none; fields in accordions
      data_entry_helper::$javascript .= "jQuery.validator.setDefaults({ 
        ignore: \"input[type='hidden']\"
      });\n";
    }}
public function rfj_static_content($args,$attributes,$reloadPath,&$tabs){

    $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\">\n";
    //Optional buttons
    $r.=self::rfj_optional_buttons();//get html for debig abandon and padlock button
    self::rfj_client_validate($args);    // request automatic JS validation
    
    //we don't have a header or footer
//    if (method_exists(get_called_class(), 'getHeaderHTML')) {
//      $r .= call_user_func(array(get_called_class(), 'getHeaderHTML'), true, $args);
//    }
    $customAttributeTabs = get_attribute_tabs($attributes);

    // remove added comment controls unless editing an existing sample
    if (self::$mode!==MODE_EXISTING || helper_base::$form_mode==='ERRORS') {
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

    return $r;
}

public function rfj_get_subject_observation_attribute_values($auth,&$form_data,$subRecords){ 
// now generic function to process form attributes from data 
$table = 'subject_observation_attribute_value';
$keyfield='subject_observation_id';
$keyvals=self::$subjectObservationIds;
$prefix='sjoAttr';
$attrib_id='subject_observation_attribute_id';


    // load the xxx_attribute value(s) for this sample
    $query = array('in'=>array($keyfield, $keyvals));
    $filter = array('query'=>json_encode($query),);
    $options = array(
      'table' => $table,
      'extraParams' => $auth['read'] + $filter,
      'nocache' => true,
    );
    $xxxAttrs = data_entry_helper::get_population_data($options);

    // add each xxx_attribute to the form data
    for ($idx=0; $idx<count($subRecords); $idx++) {
      $subRecord=$subRecords[$idx];
      // prefix the keys and load to form data
      $fieldprefix = 'idn:'.$idx.":$prefix:";
      foreach ($xxxAttrs as $xxxAttr) {
        if ($xxxAttr[$keyfield]===$subRecord['id']) {
          if (!empty($xxxAttr['id'])) {
            $form_data[$fieldprefix.$xxxAttr[$attrib_id].':'.$xxxAttr['id']] = $xxxAttr['raw_value'];
          }
        }
      }
    }
    }
public function rfj_populate($auth,$keyfield,$keyvals,$table,$viewtype='detail'){
//$keyfield='subject_observation_id'
//$keyvals=self::$subjectObservationIds;
//$table='identifiers_subject_observation';



    // generic load table
    $query = array('in'=>array($keyfield, $keyvals));
    $filter = array('query'=>json_encode($query),);
    $options = array(
      'table' => $table,
      'extraParams' => $auth['read'] + array('view'=>$viewtype) + $filter,
      'nocache' => true,
    );
    return data_entry_helper::get_population_data($options);
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

