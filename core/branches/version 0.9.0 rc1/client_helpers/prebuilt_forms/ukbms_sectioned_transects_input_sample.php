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
 * @package Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

require_once 'includes/map.php';
require_once 'includes/user.php';
require_once 'includes/language_utils.php';
require_once 'includes/form_generation.php';


// TODO
// Convert List creation on subsidiary grids to look up taxon details for the taxons we have rather than driving from list.
// Add species auto completes to bottom of grids
// When auto complete selected, new row added.
// Add check to prevent duplicate rows
// add filtering ability to auto complete look up and discriminator between subsidiary grids.
/**
 * A custom function for usort which sorts by the location code of a list of sections.
 */
function ukbms_stis_sectionSort($a, $b)
{
  $aCode = substr($a['code'], 1);
  $bCode = substr($b['code'], 1);
  if ($aCode===$bCode) {
    return 0;
  }
  return ((int)$aCode < (int)$bCode) ? -1 : 1;
}

/**
 *
 *
 * @package Client
 * @subpackage PrebuiltForms
 * A form for data entry of transect data by entering counts of each for sections along the transect.
 */
class iform_ukbms_sectioned_transects_input_sample {

  /**
   * Return the form metadata. Note the title of this method includes the name of the form file. This ensures
   * that if inheritance is used in the forms, subclassed forms don't return their parent's form definition.
   * @return array The definition of the form.
   */
  public static function get_ukbms_sectioned_transects_input_sample_definition() {
    return array(
      'title'=>'UKBMS Sectioned Transects Sample Input',
      'category' => 'Sectioned Transects',
      'description'=>'A form for inputting the counts of species observed at each section along a transect. Can be called with site=<id> in the URL to force the '.
          'selection of a fixed site, or sample=<id> to edit an existing sample.'
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
   */
  public static function get_parameters() {
    return array_merge(
      iform_map_get_map_parameters(),
      array(
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
          'name'=>'occurrence_attribute_id',
          'caption'=>'Occurrence Attribute',
          'description'=>'The attribute (typically an abundance attribute) that will be presented in the grid for input. Entry of an attribute value will create '.
              ' an occurrence.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'captionField'=>'caption',
          'valueField'=>'id',
          'required' => true,
          'siteSpecific'=>true
        ),
        array(
          'name'=>'transect_type_term',
          'caption'=>'Transect type term',
          'description'=>'Select the term used for transect location types.',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'term',
          'extraParams' => array('termlist_external_key'=>'indicia:location_types'),
          'required' => true,
          'group'=>'Transects Editor Settings'
        ),
        array(
          'name'=>'section_type_term',
          'caption'=>'Section type term',
          'description'=>'Select the term used for section location types.',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'term',
          'extraParams' => array('termlist_external_key'=>'indicia:location_types'),
          'required' => true,            
          'group'=>'Transects Editor Settings'
        ), 
        array(
          'name'=>'species_tab_1',
          'caption'=>'Species Tab 1 Title',
          'description'=>'The title to be used on the species checklist for the main tab.',
          'type'=>'string',
          'required' => true,
          'group'=>'Species'
        ),
        array(
          'name'=>'taxon_list_id',
          'caption'=>'All Species List',
          'description'=>'The species checklist used to populate the grid on the main grid when All Species is selected. Also used to drive the autocomplete when other options selected.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'siteSpecific'=>true,
          'group'=>'Species'
        ),
        array(
          'name'=>'main_taxon_filter_field',
          'caption'=>'All Species List: Field used to filter taxa',
          'description'=>'If you want to allow recording for just part of the selected All Species List, then select which field you will '.
              'use to specify the filter by.',
          'type'=>'select',
          'options' => array(
            'taxon' => 'Taxon',
            'taxon_meaning_id' => 'Taxon Meaning ID',
            'taxon_group' => 'Taxon group title'
          ),
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'main_taxon_filter',
          'caption'=>'All Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'common_taxon_list_id',
          'caption'=>'Common Species List',
          'description'=>'The species checklist used to populate the grid on the main grid when Common Species is selected.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'required'=>false,
          'siteSpecific'=>true,
          'group'=>'Species'
        ),
        array(
          'name'=>'common_taxon_filter_field',
          'caption'=>'Common Species List: Field used to filter taxa',
          'description'=>'If you want to allow recording for just part of the selected Common Species List, then select which field you will '.
              'use to specify the filter by.',
          'type'=>'select',
          'options' => array(
            'taxon' => 'Taxon',
            'taxon_meaning_id' => 'Taxon Meaning ID',
            'taxon_group' => 'Taxon group title'
          ),
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'common_taxon_filter',
          'caption'=>'Common Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name' => 'start_with_common_species',
          'caption' => 'Start with common species',
          'description' => 'Tick this box to preselect the common species list rather than the all species list.',
          'type' => 'boolean',
          'required' => false,
          'default' => false,
          'group' => 'Species'
        ),
        array(
          'name'=>'species_tab_2',
          'caption'=>'Species Tab 2 Title',
          'description'=>'The title to be used on the species checklist for the second tab.',
          'type'=>'string',
          'required'=>false,
          'group'=>'Species 2'
        ),
        array(
          'name' => 'force_second',
          'caption' => 'Include full species list in second tab',
          'description' => 'In the second species tab, include the full species list: if not selected a species control will be provided to add the required taxon to the list.',
          'type' => 'boolean',
          'required' => false,
          'group' => 'Species 2'
        ),
        array(
          'name'=>'second_taxon_list_id',
          'caption'=>'Second Tab Species List',
          'description'=>'The species checklist used to drive the autocomplete in the optional second grid. If not provided, the second grid and its tab are omitted.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'required'=>false,
          'siteSpecific'=>true,
          'group'=>'Species 2'
        ),
        array(
          'name'=>'second_taxon_filter_field',
          'caption'=>'Second Tab Species List: Field used to filter taxa',
          'description'=>'If you want to allow recording for just part of the selected Species List, then select which field you will '.
              'use to specify the filter by.',
          'type'=>'select',
          'options' => array(
            'taxon' => 'Taxon',
            'taxon_meaning_id' => 'Taxon Meaning ID',
            'taxon_group' => 'Taxon group title'
          ),
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species 2'
        ),
        array(
          'name'=>'second_taxon_filter',
          'caption'=>'Second Tab Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species 2'
        ),
        array(
          'name'=>'occurrence_attribute_id_2',
          'caption'=>'Second Tab Occurrence Attribute',
          'description'=>'The attribute that will be presented in the Second Species Tab grid for input, if different to the Occurrence Attribute above. Omit if using the same.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'captionField'=>'caption',
          'valueField'=>'id',
          'required' => false,
          'siteSpecific'=>true,
          'group'=>'Species 2'
        ),
        array(
          'name'=>'species_tab_3',
          'caption'=>'Species Tab 3 Title',
          'description'=>'The title to be used on the species checklist for the third tab.',
          'type'=>'string',
          'required'=>false,
          'group'=>'Species 3'
        ),
        array(
          'name' => 'force_third',
          'caption' => 'Include full species list in third tab',
          'description' => 'In the third species tab, include the full species list: if not selected a species control will be provided to add the required taxon to the list.',
          'type' => 'boolean',
          'required' => false,
          'group' => 'Species 3'
        ),
        array(
          'name'=>'third_taxon_list_id',
          'caption'=>'Third Tab Species List',
          'description'=>'The species checklist used to drive the autocomplete in the optional third grid. If not provided, the third grid and its tab are omitted.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'required'=>false,
          'siteSpecific'=>true,
          'group'=>'Species 3'
        ),
        array(
          'name'=>'third_taxon_filter_field',
          'caption'=>'Third Tab Species List: Field used to filter taxa',
          'description'=>'If you want to allow recording for just part of the selected Species List, then select which field you will '.
              'use to specify the filter by.',
          'type'=>'select',
          'options' => array(
            'taxon' => 'Taxon',
            'taxon_meaning_id' => 'Taxon Meaning ID',
            'taxon_group' => 'Taxon group title'
          ),
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species 3'
        ),
        array(
          'name'=>'third_taxon_filter',
          'caption'=>'Third Tab Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species 3'
        ),
        array(
          'name'=>'occurrence_attribute_id_3',
          'caption'=>'Third Tab Occurrence Attribute',
          'description'=>'The attribute that will be presented in the Third Species Tab grid for input, if different to the Occurrence Attribute above. Omit if using the same.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'captionField'=>'caption',
          'valueField'=>'id',
          'required' => false,
          'siteSpecific'=>true,
          'group'=>'Species 3'
        ),
        array(
          'name'=>'species_tab_4',
          'caption'=>'Fourth Species Tab Title',
          'description'=>'The title to be used on the species checklist for the fourth tab.',
          'type'=>'string',
          'required'=>false,
          'group'=>'Species 4'
        ),
        array(
          'name' => 'force_fourth',
          'caption' => 'Include full species list in fourth tab',
          'description' => 'In the fourth species tab, include the full species list: if not selected a species control will be provided to add the required taxon to the list.',
          'type' => 'boolean',
          'required' => false,
          'group' => 'Species 4'
        ),
        array(
          'name'=>'fourth_taxon_list_id',
          'caption'=>'Fourth Tab Species List',
          'description'=>'The species checklist used to drive the autocomplete in the optional fourth grid. If not provided, the fourth grid and its tab are omitted.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'required'=>'false',
          'siteSpecific'=>true,
          'group'=>'Species 4'
        ),
        array(
          'name'=>'fourth_taxon_filter_field',
          'caption'=>'Fourth Tab Species List: Field used to filter taxa',
          'description'=>'If you want to allow recording for just part of the selected Species List, then select which field you will '.
              'use to specify the filter by.',
          'type'=>'select',
          'options' => array(
            'taxon' => 'Taxon',
            'taxon_meaning_id' => 'Taxon Meaning ID',
            'taxon_group' => 'Taxon group title'
          ),
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species 4'
        ),
        array(
          'name'=>'fourth_taxon_filter',
          'caption'=>'Fourth Tab Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species 4'
        ),
        array(
          'name'=>'occurrence_attribute_id_4',
          'caption'=>'Fourth Tab Occurrence Attribute',
          'description'=>'The attribute that will be presented in the Fourth Species Tab grid for input, if different to the Occurrence Attribute above. Omit if using the same.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'captionField'=>'caption',
          'valueField'=>'id',
          'required' => false,
          'siteSpecific'=>true,
          'group'=>'Species 4'
        ),
        array(
          'name'=>'map_taxon_list_id',
          'caption'=>'Map based data entry Species List',
          'description'=>'The species checklist used to drive the autocomplete in the optional map based grid. If not provided, the species map and its tab are omitted.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'required'=>false,
          'siteSpecific'=>true,
          'group'=>'Species Map'
        ),
        array(
          'name'=>'species_map_tab',
          'caption'=>'Map based data entry Tab Title',
          'description'=>'The title to be used on the Map based data entry tab.',
          'type'=>'string',
          'required' => false,
          'group'=>'Species Map'
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
          'group'=>'Species Map',
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
          'group'=>'Species Map'
        ),
        array(
          'name'=>'map_taxon_filter_field',
          'caption'=>'Map based data entry Tab Species List: Field used to filter taxa',
          'description'=>'If you want to allow recording for just part of the selected Species List, then select which field you will '.
              'use to specify the filter by.',
          'type'=>'select',
          'options' => array(
            'taxon' => 'Taxon',
            'taxon_meaning_id' => 'Taxon Meaning ID',
            'taxon_group' => 'Taxon group title'
          ),
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species Map'
        ),
        array(
          'name'=>'map_taxon_filter',
          'caption'=>'Map based data entry Tab Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species Map'
        ),
        array(
          'name' => 'species_include_both_names',
          'caption' => 'Include both names in species controls and added rows',
          'description' => 'When using a species grid with the ability to add new rows, the autocomplete control by default shows just the searched taxon name in the drop down. '.
              'Set this to include both the latin and common names, with the searched one first. This also controls the label when adding a new taxon row into the grid.',
          'type' => 'boolean',
          'required' => false,
          'group' => 'Species Map'
        ),
        array(
          'name' => 'species_include_taxon_group',
          'caption' => 'Include taxon group name in species autocomplete and added rows',
          'description' => 'When using a species grid with the ability to add new rows, the autocomplete control by default shows just the searched taxon name in the drop down. '.
              'Set this to include the taxon group title.  This also controls the label when adding a new taxon row into the grid.',
          'type' => 'boolean',
          'required' => false,
          'group' => 'Species Map'
        ),
        array(
          'name'=>'occurrence_comment',
          'caption'=>'Occurrence Comment',
          'description'=>'Should an input box be present for a comment against each occurrence?',
          'type'=>'boolean',
          'required' => false,
          'default'=>false,
          'group'=>'Species Map'
        ),
        array(
          'name'=>'occurrence_sensitivity',
          'caption'=>'Occurrence Sensitivity',
          'description'=>'Should a control be present for sensitivity of each record?  This applies when using grid entry mode or when using the [species attributes] control '.
              'to output all the occurrence related input controls automatically. The [sensitivity] control outputs a sensitivity input control independently of this setting.',
          'type'=>'boolean',
          'required' => false,
          'default'=>false,
          'group'=>'Species Map'
        ),
        array(
          'name'=>'occurrence_images',
          'caption'=>'Occurrence Images',
          'description'=>'Should occurrences allow images to be uploaded? This applies when using grid entry mode or when using the [species attributes] control '.
              'to output all the occurrence related input controls automatically. The [photos] control outputs a photos input control independently of this setting.',
          'type'=>'boolean',
          'required' => false,
          'default'=>false,
          'group'=>'Species Map'
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
          'group'=>'Species Map'
        ),
        array(
          'name' => 'edit_taxa_names',
          'caption' => 'Include option to edit entered taxa',
          'description' => 'Include an icon to allow taxa to be edited after they has been entered into the species grid.',
          'type'=>'checkbox',
          'default'=>false,
          'required'=>false,
          'group' => 'Species Map',
        ),
        array(
          'name'=>'col_widths',
          'caption'=>'Grid Column Widths',
          'description'=>'Provide percentage column widths for each species checklist grid column as a comma separated list. To leave a column at its default with, put a blank '.
              'entry in the list. E.g. "25,,20" would set the first column to 25% width and the 3rd column to 20%, leaving the other columns as they are.',
          'type'=>'string',
          'group'=>'Species Map',
          'required' => false
        ),
        array(
          'name'=>'spatial_systems',
          'caption'=>'Allowed Spatial Ref Systems',
          'description'=>'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code number such as 4326). '.
              'Set to "default" to use the settings defined in the IForm Settings page.',
          'type'=>'string',
          'default' => 'default',
          'group'=>'Other Map Settings'
        ),
        array(
          'name'=>'defaults',
          'caption'=>'Default Values',
          'description'=>'Supply default values for each field as required. On each line, enter fieldname=value. For custom attributes, '.
              'the fieldname is the untranslated caption. For other fields, it is the model and fieldname, e.g. occurrence.record_status. '.
              'For date fields, use today to dynamically default to today\'s date. NOTE, currently only supports occurrence:record_status and '.
              'sample:date but will be extended in future.',
              'type'=>'textarea',
              'default'=>'occurrence:record_status=C',
          'group'=>'Species Map',
          'required' => false
        ),
        array(
          'name'=>'custom_attribute_options',
          'caption'=>'Options for custom attributes',
          'description'=>'A list of additional options to pass through to custom attributes, one per line. Each option should be specified as '.
              'the attribute name followed by | then the option name, followed by = then the value. For example, smpAttr:1|class=control-width-5.',
          'type'=>'textarea',
          'required'=>false,
          'siteSpecific'=>true
        ),
        array(
          'name'=>'my_walks_page',
          'caption'=>'Path to My Walks',
          'description'=>'Path used to access the My Walks page after a successful submission.',
          'type'=>'text_input',
          'required'=>true,
          'siteSpecific'=>true
        ),
        array(
            'name'=>'managerPermission',
            'caption'=>'Drupal Permission for Manager mode',
            'description'=>'Enter the Drupal permission name to be used to determine if this user is a manager. Entering this will allow the identified users access to the full locations list when entering a walk.',
            'type'=>'string',
            'required' => false,
            'group' => 'Transects Editor Settings'
        ),
        array(
            'name' => 'branch_assignment_permission',
            'label' => 'Drupal Permission name for Branch Manager',
            'type' => 'string',
            'description' => 'Enter the Drupal permission name to be used to determine if this user is a Branch Manager. Entering this will allow the identified users access to locations identified as theirs using the Branch CMS User ID integer attribute on the locations.',
            'required'=>false,
            'group' => 'Transects Editor Settings'
        ),
        array(
          'name' => 'user_locations_filter',
          'caption' => 'User locations filter',
          'description' => 'Should the locations available be filtered to those which the user is linked to, by a multivalue CMS User ID attribute ' .
              'in the location data? If not ticked, then all locations are available.',
          'type' => 'boolean',
          'required' => false,
          'default' => true,
          'group' => 'Transects Editor Settings'
        ),
        array(
          'name' => 'include_map_samples_form',
          'caption' => 'Include map',
          'description' => 'Should a map be displayed on the sample details page? This shows the transect picked.',
          'type' => 'boolean',
          'required' => false,
          'default' => false,
          'group' => 'Transects Editor Settings'
        ),
        array(
          'name'=>'percent_width',
          'caption'=>'Map Percent Width',
          'description'=>'The percentage width that the map will take on the front page.',
          'type'=>'int',
          'required' => true,
          'default' => 50,
          'group' => 'Transects Editor Settings'
        ),
        array(
          'name' => 'supress_tab_msg',
          'caption' => 'Supress voluntary message',
          'description' => 'On the 2nd, 3rd and 4th Species tabs there is a optional message stating that completing the data on the tab is optional. Select this option to remove this message.',
          'type' => 'boolean',
          'required' => false,
          'default' => false,
          'group' => 'Transects Editor Settings'
        ),
        array(
          'name'=>'sensitiveAttrID',
          'caption' => 'Location attribute used to filter out sensitive sites',
          'description' => 'A boolean location attribute, set to true if a site is sensitive.',
          'type' => 'locAttr',
          'required' => false,
          'group' => 'Sensitivity Handling'
        ),
        array(
          'name' => 'sensitivityPrecision',
          'caption' => 'Sensitivity Precision',
          'description' => 'Precision to be applied to new occurrences recorded at sensitive sites. Existing occurrences are not changed. A number representing the square size in metres - e.g. enter 1000 for 1km square.',
          'type' => 'int',
          'required' => false,
          'group' => 'Sensitivity Handling'
        )
      )
    );
  }

  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $node The Drupal node object.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   * @todo: Implement this method
   */
  public static function get_form($args, $node, $response=null) {
    if (isset($response['error']))
      data_entry_helper::dump_errors($response);
    if ((isset($_REQUEST['page']) && $_REQUEST['page']==='mainSample' && !isset(data_entry_helper::$validation_errors) && !isset($response['error'])) ||
        (isset($_REQUEST['page']) && $_REQUEST['page']==='notes')) {
      // we have just saved the sample page, so move on to the occurrences list,
      // or we have had an error in the notes page
      return self::get_occurrences_form($args, $node, $response);
    } else {
      return self::get_sample_form($args, $node, $response);
    }
  }

  public static function get_sample_form($args, $node, $response) {
    global $user;
    if (!module_exists('iform_ajaxproxy'))
      return 'This form must be used in Drupal with the Indicia AJAX Proxy module enabled.';
    iform_load_helpers(array('map_helper'));
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $sampleId = isset($_GET['sample_id']) ? $_GET['sample_id'] : null;
    if ($sampleId) {
      data_entry_helper::load_existing_record($auth['read'], 'sample', $sampleId);
      $locationId = data_entry_helper::$entity_to_load['sample:location_id'];
    } else {
      $locationId = isset($_GET['site']) ? $_GET['site'] : null;
      // location ID also might be in the $_POST data after a validation save of a new record
      if (!$locationId && isset($_POST['sample:location_id']))
        $locationId = $_POST['sample:location_id'];
    }
    $url = explode('?', $args['my_walks_page'], 2);
    $params = NULL;
    $fragment = NULL;
    // fragment is always at the end.
    if(count($url)>1){
      $params = explode('#', $url[1], 2);
      if(count($params)>1) $fragment=$params[1];
      $params=$params[0];
    } else {
      $url = explode('#', $url[0], 2);
      if (count($url)>1) $fragment=$url[1];
    }
    $args['my_walks_page'] = url($url[0], array('query' => $params, 'fragment' => $fragment, 'absolute' => TRUE));
    $r = '<form method="post" id="sample">';
    $r .= $auth['write'];
    // we pass through the read auth. This makes it possible for the get_submission method to authorise against the warehouse
    // without an additional (expensive) warehouse call, so it can get location details.
    $r .= '<input type="hidden" name="page" value="mainSample"/>';
    $r .= '<input type="hidden" name="read_nonce" value="'.$auth['read']['nonce'].'"/>';
    $r .= '<input type="hidden" name="read_auth_token" value="'.$auth['read']['auth_token'].'"/>';
    $r .= '<input type="hidden" name="website_id" value="'.$args['website_id'].'"/>';
    if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      $r .= '<input type="hidden" name="sample:id" value="'.data_entry_helper::$entity_to_load['sample:id'].'"/>';
    }
    $r .= '<input type="hidden" name="sample:survey_id" value="'.$args['survey_id'].'"/>';

    if(isset($args['include_map_samples_form']) && $args['include_map_samples_form'])
      $r .= '<div id="cols" class="ui-helper-clearfix"><div class="left" style="width: '.(98-(isset($args['percent_width']) ? $args['percent_width'] : 50)).'%">';

    if ($locationId) {
      $site = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $auth['read'] + array('view'=>'detail','id'=>$locationId,'deleted'=>'f')
      ));
      $site = $site[0];
      $r .= '<input type="hidden" name="sample:location_id" value="'.$locationId.'"/>';
      $r .= '<input type="hidden" name="sample:entered_sref" value="'.$site['centroid_sref'].'"/>';
      $r .= '<input type="hidden" name="sample:entered_sref_system" value="'.$site['centroid_sref_system'].'"/>';
    }
    if ($locationId && (isset(data_entry_helper::$entity_to_load['sample:id']) || isset($_GET['site']))) {
      // for reload of existing or the the site is specified in the URL, don't let the user switch the transect as that would mess everything up.
      $r .= '<label>'.lang::get('Transect').':</label> <span class="value-label">'.$site['name'].'</span><br/>';
    } else {
      // Output only the locations for this website and transect type. Note we load both transects and sections, just so that
      // we always use the same warehouse call and therefore it uses the cache.
      $typeTerms = array(
        empty($args['transect_type_term']) ? 'Transect' : $args['transect_type_term'],
        empty($args['section_type_term']) ? 'Section' : $args['section_type_term']
      );
      $locationTypes = helper_base::get_termlist_terms($auth, 'indicia:location_types', $typeTerms);
      $siteParams = $auth['read'] + array('website_id' => $args['website_id'], 'location_type_id'=>$locationTypes[0]['id']);
      if ((!isset($args['user_locations_filter']) || $args['user_locations_filter']) &&
          (!isset($args['managerPermission']) || !user_access($args['managerPermission']))) {
        $siteParams += array('locattrs'=>'CMS User ID', 'attr_location_cms_user_id'=>$user->uid);
      } else
        $siteParams += array('locattrs'=>'');
      $availableSites = data_entry_helper::get_population_data(array(
        'report'=>'library/locations/locations_list',
        'extraParams' => $siteParams,
        'nocache' => true
      ));
      // convert the report data to an array for the lookup, plus one to pass to the JS so it can keep the hidden sref fields updated
      $sitesLookup = array();
      $sitesJs = array();
      foreach ($availableSites as $site) {
        $sitesLookup[$site['location_id']]=$site['name'];
        $sitesJs[$site['location_id']] = $site;
      }
      // bolt in branch locations. Don't assume that branch list is superset of normal sites list.
      // Only need to do if not a manager - they have already fetched the full list anyway.
      if(isset($args['branch_assignment_permission']) && user_access($args['branch_assignment_permission']) && $siteParams['locattrs']!='') {
        $siteParams['locattrs']='Branch CMS User ID';
        $siteParams['attr_location_branch_cms_user_id']=$user->uid;
        unset($siteParams['attr_location_cms_user_id']);
        $availableSites = data_entry_helper::get_population_data(array(
            'report'=>'library/locations/locations_list',
            'extraParams' => $siteParams,
            'nocache' => true
        ));
        foreach ($availableSites as $site) {
          $sitesLookup[$site['location_id']]=$site['name'];
          $sitesJs[$site['location_id']] = $site;
        }
        natcasesort($sitesLookup); // merge into original list in alphabetic order.
      }
      data_entry_helper::$javascript .= "indiciaData.sites = ".json_encode($sitesJs).";\n";
      $options = array(
        'label' => lang::get('Select Transect'),
        'validation' => array('required'),
        'blankText'=>lang::get('please select'),
        'lookupValues' => $sitesLookup,
      );
      if ($locationId)
        $options['default'] = $locationId;
      $r .= data_entry_helper::location_select($options);
    }
    if (!$locationId) {
      $r .= '<input type="hidden" name="sample:entered_sref" value="" id="entered_sref"/>';
      $r .= '<input type="hidden" name="sample:entered_sref_system" value="" id="entered_sref_system"/>';
      // sref values for the sample will be populated automatically when the submission is built.
    }
    $sampleMethods = helper_base::get_termlist_terms($auth, 'indicia:sample_methods', array('Transect'));
    $attributes = data_entry_helper::getAttributes(array(
      'id' => $sampleId,
      'valuetable'=>'sample_attribute_value',
      'attrtable'=>'sample_attribute',
      'key'=>'sample_id',
      'fieldprefix'=>'smpAttr',
      'extraParams'=>$auth['read'],
      'survey_id'=>$args['survey_id'],
      'sample_method_id'=>$sampleMethods[0]['id']
    ));
    $r .= get_user_profile_hidden_inputs($attributes, $args, '', $auth['read']);
    if(isset($_GET['date'])){
      $r .= '<input type="hidden" name="sample:date" value="'.$_GET['date'].'"/>';
      $r .= '<label>'.lang::get('Date').':</label> <span class="value-label">'.$_GET['date'].'</span><br/>';
    } else {
      if (isset(data_entry_helper::$entity_to_load['sample:date']) && preg_match('/^(\d{4})/', data_entry_helper::$entity_to_load['sample:date'])) {
        // Date has 4 digit year first (ISO style) - convert date to expected output format
        // @todo The date format should be a global configurable option. It should also be applied to reloading of custom date attributes.
        $d = new DateTime(data_entry_helper::$entity_to_load['sample:date']);
        data_entry_helper::$entity_to_load['sample:date'] = $d->format('d/m/Y');
      }
      $r .= data_entry_helper::date_picker(array(
        'label' => lang::get('Date'),
        'fieldname' => 'sample:date',
      ));
    }
    // are there any option overrides for the custom attributes?
    if (isset($args['custom_attribute_options']) && $args['custom_attribute_options']) 
      $blockOptions = get_attr_options_array_with_user_data($args['custom_attribute_options']);
    else 
      $blockOptions=array();
    $r .= get_attribute_html($attributes, $args, array('extraParams'=>$auth['read']), null, $blockOptions);
    $r .= '<input type="hidden" name="sample:sample_method_id" value="'.$sampleMethods[0]['id'].'" />';
    $r .= '<input type="submit" value="'.lang::get('Next').'" />';
    $r .= '<a href="'.$args['my_walks_page'].'" class="button">'.lang::get('Cancel').'</a>';
    if (isset(data_entry_helper::$entity_to_load['sample:id']))
      $r .= '<button id="delete-button" type="button" class="ui-state-default ui-corner-all" />'.lang::get('Delete').'</button>';

    if(isset($args['include_map_samples_form']) && $args['include_map_samples_form']){
      $r .= "</div>" .
            '<div class="right" style="width: '.(isset($args['percent_width']) ? $args['percent_width'] : 50).'%">';
      // no place search: [map]
      $options = iform_map_get_map_options($args, $auth['read']);
      if (!empty(data_entry_helper::$entity_to_load['sample:wkt'])) {
        $options['initialFeatureWkt'] = data_entry_helper::$entity_to_load['sample:wkt'];
      }
      $olOptions = iform_map_get_ol_options($args);
      if (!isset($options['standardControls']))
        $options['standardControls']=array('layerSwitcher','panZoomBar');
      $r .= map_helper::map_panel($options, $olOptions);
      $r .= "</div>"; // right
    }

    $r .= '</form>';
    // Recorder Name - assume Easy Login uid
    if (function_exists('module_exists') && module_exists('easy_login')) {
      $userId = hostsite_get_user_field('indicia_user_id');
 // For non easy login test only     $userId = 1;
      foreach($attributes as $attrID => $attr){
        if(strcasecmp('Recorder Name', $attr["untranslatedCaption"]) == 0 && !empty($userId)){
          // determining which you have used is difficult from a services based autocomplete, esp when the created_by_id is not available on the data.
          data_entry_helper::add_resource('autocomplete');
          data_entry_helper::$javascript .= "bindRecorderNameAutocomplete(".$attrID.", '".$userId."', '".data_entry_helper::$base_url."', '".$args['survey_id']."', '".$auth['read']['auth_token']."', '".$auth['read']['nonce']."');\n";
        }
      }
    }
    if (isset(data_entry_helper::$entity_to_load['sample:id'])){
      // allow deletes if sample id is present.
      data_entry_helper::$javascript .= "jQuery('#delete-button').click(function(){
  if(confirm(\"".lang::get('Are you sure you want to delete this walk?')."\")){
    jQuery('#delete-form').submit();
  } // else do nothing.
});\n";
      // note we only require bare minimum in order to flag a sample as deleted.
      $r .= '<form method="post" id="delete-form" style="display: none;">';
      $r .= $auth['write'];
      $r .= '<input type="hidden" name="page" value="delete"/>';
      $r .= '<input type="hidden" name="website_id" value="'.$args['website_id'].'"/>';
      $r .= '<input type="hidden" name="sample:id" value="'.data_entry_helper::$entity_to_load['sample:id'].'"/>';
      $r .= '<input type="hidden" name="sample:deleted" value="t"/>';
      $r .= '</form>';
    }
    data_entry_helper::enable_validation('sample');
    return $r;
  }

  public static function get_occurrences_form($args, $node, $response) {
    global $user;
  	if (!module_exists('iform_ajaxproxy'))
      return 'This form must be used in Drupal with the Indicia AJAX Proxy module enabled.';
  	drupal_add_js('misc/tableheader.js'); // for sticky heading
    data_entry_helper::add_resource('jquery_form');
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    // did the parent sample previously exist? Default is no.
    $existing=false;
    $url = explode('?', $args['my_walks_page'], 2);
    $params = NULL;
    $fragment = NULL;
    // fragment is always at the end.
    if(count($url)>1){
      $params = explode('#', $url[1], 2);
      if(count($params)>1) $fragment=$params[1];
      $params=$params[0];
    } else {
      $url = explode('#', $url[0], 2);
      if (count($url)>1) $fragment=$url[1];
    }
    $args['my_walks_page'] = url($url[0], array('query' => $params, 'fragment' => $fragment, 'absolute' => TRUE));
    if (isset($_POST['sample:id'])) {
      // have just posted an edit to the existing parent sample, so can use it to get the parent location id.
      $parentSampleId = $_POST['sample:id'];
      $existing=true;
      data_entry_helper::load_existing_record($auth['read'], 'sample', $parentSampleId);
    } else {
      if (isset($response['outer_id']))
        // have just posted a new parent sample, so can use it to get the parent location id.
        $parentSampleId = $response['outer_id'];
      else {
        $parentSampleId = $_GET['sample_id'];
        $existing=true;
      }
    }
    $sample = data_entry_helper::get_population_data(array(
      'table' => 'sample',
      'extraParams' => $auth['read'] + array('view'=>'detail','id'=>$parentSampleId,'deleted'=>'f')
    ));
    $sample=$sample[0];
    $parentLocId = $sample['location_id'];
    $date=$sample['date_start'];
    if (!function_exists('module_exists') || !module_exists('easy_login')) {
      // work out the CMS User sample ID.
      $sampleMethods = helper_base::get_termlist_terms($auth, 'indicia:sample_methods', array('Transect'));
      $attributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'sample_attribute_value',
        'attrtable'=>'sample_attribute',
        'key'=>'sample_id',
        'fieldprefix'=>'smpAttr',
        'extraParams'=>$auth['read'],
        'survey_id'=>$args['survey_id'],
        'sample_method_id'=>$sampleMethods[0]['id']
      ));
      if (false== ($cmsUserAttr = extract_cms_user_attr($attributes)))
        return 'Easy Login not active: This form is designed to be used with the CMS User ID attribute setup for samples in the survey.';
    }
    // find any attributes that apply to transect section samples.
    $sampleMethods = helper_base::get_termlist_terms($auth, 'indicia:sample_methods', array('Transect Section'));
    $attributes = data_entry_helper::getAttributes(array(
      'valuetable'=>'sample_attribute_value',
      'attrtable'=>'sample_attribute',
      'key'=>'sample_id',
      'fieldprefix'=>'smpAttr',
      'extraParams'=>$auth['read'],
      'survey_id'=>$args['survey_id'],
      'sample_method_id'=>$sampleMethods[0]['id'],
      'multiValue'=>false // ensures that array_keys are the list of attribute IDs.
    ));
    //  the parent sample and sub-samples have already been created: can't cache in case a new section added.
    // need to specify sample_method as this must be different to those used in species map.
    // Only returns section based subsamples, not map.
    $subSamples = data_entry_helper::get_population_data(array(
      'report' => 'library/samples/samples_list_for_parent_sample',
      'extraParams' => $auth['read'] + array('sample_id'=>$parentSampleId,'date_from'=>'','date_to'=>'', 'sample_method_id'=>$sampleMethods[0]['id'], 'smpattrs'=>implode(',', array_keys($attributes))),
      'nocache'=>true
    ));
    // transcribe the response array into a couple of forms that are useful elsewhere - one for outputting JSON so the JS knows about
    // the samples, and another for lookup of sample data by code later.
    $subSampleJson = array();
    $subSamplesByCode = array();
    foreach ($subSamples as $subSample) {
      $subSampleJson[] = '"'.$subSample['code'].'": '.$subSample['sample_id'];
      $subSamplesByCode[$subSample['code']] = $subSample;
    }
    data_entry_helper::$javascript .= "indiciaData.samples = { ".implode(', ', $subSampleJson)."};\n";
    if ($existing) {
      // Only need to load the occurrences for a pre-existing sample
      $attrs = array($args['occurrence_attribute_id']);
      if(isset($args['occurrence_attribute_id_2']) && $args['occurrence_attribute_id_2'] != "") $attrs[] = $args['occurrence_attribute_id_2'];
      if(isset($args['occurrence_attribute_id_3']) && $args['occurrence_attribute_id_3'] != "") $attrs[] = $args['occurrence_attribute_id_3'];
      if(isset($args['occurrence_attribute_id_4']) && $args['occurrence_attribute_id_4'] != "") $attrs[] = $args['occurrence_attribute_id_4'];
      $o = data_entry_helper::get_population_data(array(
        'report' => 'reports_for_prebuilt_forms/UKBMS/ukbms_occurrences_list_for_parent_sample',
        'extraParams' => $auth['read'] + array('view'=>'detail','sample_id'=>$parentSampleId,'survey_id'=>$args['survey_id'],'date_from'=>'','date_to'=>'','taxon_group_id'=>'',
            'smpattrs'=>'', 'occattrs'=>implode(',',$attrs)),
        // don't cache as this is live data
        'nocache' => true
      ));
      // build an array keyed for easy lookup
      $occurrences = array();
      foreach($o as $occurrence) {
        $occurrences[$occurrence['sample_id'].':'.$occurrence['taxon_meaning_id']] = array(
          'ttl_id'=>$occurrence['taxa_taxon_list_id'],
          'taxon_meaning_id'=>$occurrence['taxon_meaning_id'],
          'o_id'=>$occurrence['occurrence_id'],
          'processed'=>false
        );
        foreach($attrs as $attr){
          $occurrences[$occurrence['sample_id'].':'.$occurrence['taxon_meaning_id']]['value_'.$attr] = $occurrence['attr_occurrence_'.$attr];
          $occurrences[$occurrence['sample_id'].':'.$occurrence['taxon_meaning_id']]['a_id_'.$attr] = $occurrence['attr_id_occurrence_'.$attr];
        }
      }
      // store it in data for JS to read when populating the grid
      data_entry_helper::$javascript .= "indiciaData.existingOccurrences = ".json_encode($occurrences).";\n";
    } else {
      data_entry_helper::$javascript .= "indiciaData.existingOccurrences = {};\n";
    }
    $occ_attributes = data_entry_helper::getAttributes(array(
    		'valuetable'=>'occurrence_attribute_value',
    		'attrtable'=>'occurrence_attribute',
    		'key'=>'occurrence_id',
    		'fieldprefix'=>'occAttr',
    		'extraParams'=>$auth['read'],
    		'survey_id'=>$args['survey_id'],
    		'multiValue'=>false // ensures that array_keys are the list of attribute IDs.
    ));
    data_entry_helper::$javascript .= "indiciaData.occurrence_attribute = [];\n";
    data_entry_helper::$javascript .= "indiciaData.occurrence_attribute_ctrl = [];\n";
    $defAttrOptions = array('extraParams'=>$auth['read']+array('orderby'=>'id'), 'suffixTemplate' => 'nosuffix');
    foreach(array($args['occurrence_attribute_id'],
              (isset($args['occurrence_attribute_id_2']) && $args['occurrence_attribute_id_2']!="" ? $args['occurrence_attribute_id_2'] : $args['occurrence_attribute_id']),
              (isset($args['occurrence_attribute_id_3']) && $args['occurrence_attribute_id_3']!="" ? $args['occurrence_attribute_id_3'] : $args['occurrence_attribute_id']),
              (isset($args['occurrence_attribute_id_4']) && $args['occurrence_attribute_id_4']!="" ? $args['occurrence_attribute_id_4'] : $args['occurrence_attribute_id']))
            as $idx => $attr){
      unset($occ_attributes[$attr]['caption']);
      $ctrl = data_entry_helper::outputAttribute($occ_attributes[$attr], $defAttrOptions);
      data_entry_helper::$javascript .= "indiciaData.occurrence_attribute[".($idx+1)."] = $attr;\n";
      data_entry_helper::$javascript .= "indiciaData.occurrence_attribute_ctrl[".($idx+1)."] = jQuery('".(str_replace("\n","",$ctrl))."');\n";
    }
    $sections = data_entry_helper::get_population_data(array(
      'table' => 'location',
      'extraParams' => $auth['read'] + array('view'=>'detail','parent_id'=>$parentLocId,'deleted'=>'f'),
      'nocache' => true
    ));
    usort($sections, "ukbms_stis_sectionSort");
    $location = data_entry_helper::get_population_data(array(
      'table' => 'location',
      'extraParams' => $auth['read'] + array('view'=>'detail','id'=>$parentLocId)
    ));
    $r = "<h2>".$location[0]['name']." on ".$date."</h2><div id=\"tabs\">\n";
    $tabs = array('#grid1'=>t($args['species_tab_1'])); // tab 1 is required.
    if(isset($args['second_taxon_list_id']) && $args['second_taxon_list_id']!='')
      $tabs['#grid2']=t(isset($args['species_tab_2']) && $args['species_tab_2'] != '' ? $args['species_tab_2'] : 'Species Tab 2');
    if(isset($args['third_taxon_list_id']) && $args['third_taxon_list_id']!='')
      $tabs['#grid3']=t(isset($args['species_tab_3']) && $args['species_tab_3'] != '' ? $args['species_tab_3'] : 'Species Tab 3');
    if(isset($args['fourth_taxon_list_id']) && $args['fourth_taxon_list_id']!='')
      $tabs['#grid4']=t(isset($args['species_tab_4']) && $args['species_tab_4'] != '' ? $args['species_tab_4'] : 'Species Tab 4');
    if(isset($args['map_taxon_list_id']) && $args['map_taxon_list_id']!='')
      $tabs['#gridmap']=t(isset($args['species_map_tab']) && $args['species_map_tab'] != '' ? $args['species_map_tab'] : 'Map Based Tab');
    $tabs['#notes']=lang::get('Notes');
    $r .= data_entry_helper::tab_header(array('tabs'=>$tabs));
    data_entry_helper::enable_tabs(array(
        'divId'=>'tabs',
        'style'=>'Tabs'
    ));
    $commonSelected = isset($args['start_with_common_species']) && $args['start_with_common_species'] ? 'selected="selected"' : '';
    // will assume that first table is based on abundance count, so do totals
    $r .= '<div id="grid1">'.
          '<label for="listSelect">'.lang::get('Use species list').' :</label><select id="listSelect"><option value="full">'.lang::get('All species').
              '</option><option value="common"'.$commonSelected.'>'.lang::get('Common species').'</option><option value="here">'.lang::get('Species known at this site').
              '</option><option value="mine">'.lang::get('Species I have recorded').'</option><option value="filled">'.lang::get('Species with data').'</option></select>'.
          '<span id="listSelectMsg"></span>';
    $r .= '<table id="transect-input1" class="ui-widget species-grid"><thead class="table-header">';
    $r .= '<tr><th class="ui-widget-header">' . lang::get('Sections') . '</th>';
    foreach ($sections as $idx=>$section) {
      $r .= '<th class="ui-widget-header col-'.($idx+1).'">' . $section['code'] . '</th>';
    }
    $r .= '<th class="ui-widget-header">' . lang::get('Total') . '</th>';
    $r .= '</tr></thead>';
    $r .= '<tbody class="ui-widget-content">';
    // output rows at the top for any transect section level sample attributes
    $rowClass='';
    foreach ($attributes as $attr) {
      $r .= '<tr '.$rowClass.' id="smp-'.$attr['attributeId'].'"><td>'.$attr['caption'].'</td>';
      $rowClass=$rowClass=='' ? 'class="alt-row"':'';
      unset($attr['caption']);
      foreach ($sections as $idx=>$section) {
        // output a cell with the attribute - tag it with a class & id to make it easy to find from JS.
        $attrOpts = array(
            'class' => 'smp-input smpAttr-'.$section['code'],
            'id' => $attr['fieldname'].':'.$section['code'],
            'extraParams'=>$auth['read']
        );
        // if there is an existing value, set it and also ensure the attribute name reflects the attribute value id.
        if (isset($subSamplesByCode[$section['code']])) {
          // but have to take into account possibility that this field has been blanked out, so deleting the attribute.
          if(isset($subSamplesByCode[$section['code']]['attr_id_sample_'.$attr['attributeId']]) && $subSamplesByCode[$section['code']]['attr_id_sample_'.$attr['attributeId']] != ''){
            $attrOpts['fieldname'] = $attr['fieldname'] . ':' . $subSamplesByCode[$section['code']]['attr_id_sample_'.$attr['attributeId']];
            $attr['default'] = $subSamplesByCode[$section['code']]['attr_sample_'.$attr['attributeId']];
          } else
            $attr['default']=isset($_POST[$attr['fieldname']]) ? $_POST[$attr['fieldname']] : '';
        } else {
          $attr['default']=isset($_POST[$attr['fieldname']]) ? $_POST[$attr['fieldname']] : '';
        }
        $r .= '<td class="col-'.($idx+1).' '.($idx % 5 == 0 ? 'first' : '').'">' . data_entry_helper::outputAttribute($attr, $attrOpts) . '</td>';
      }
      $r .= '<td class="ui-state-disabled first"></td>';
      $r .= '</tr>';
    }
    $r .= '</tbody>';
    $r .= '<tbody class="ui-widget-content occs-body"></tbody>';
    $r .= '<tfoot><tr><td>Total</td>';
    foreach ($sections as $idx=>$section) {
      $r .= '<td class="col-'.($idx+1).' '.($idx % 5 == 0 ? 'first' : '').' col-total"></td>';
    }
    $r .= '<td class="ui-state-disabled first"></td></tr></tfoot>';
    $r .= '</table>'.
          '<span id="taxonLookupControlContainer"><label for="taxonLookupControl" class="auto-width">'.lang::get('Add species to list').':</label> <input id="taxonLookupControl" name="taxonLookupControl" ></span>';
    $r .= '<br /><a href="'.$args['my_walks_page'].'" class="button">'.lang::get('Finish').'</a></div>';

    $extraParams = array_merge($auth['read'],
                   array('taxon_list_id' => $args['taxon_list_id'],
                         'preferred' => 't', // important
                         'allow_data_entry' => 't',
                         'view' => 'cache',
                         'orderby' => 'taxonomic_sort_order'));
    if (!empty($args['main_taxon_filter_field']) && !empty($args['main_taxon_filter']))
      $extraParams[$args['main_taxon_filter_field']] = helper_base::explode_lines($args['main_taxon_filter']);
    $taxa = data_entry_helper::get_population_data(array('table' => 'taxa_taxon_list', 'extraParams' => $extraParams));
    data_entry_helper::$javascript .= "indiciaData.speciesList1List = [";
    $first = true;
    foreach($taxa as $taxon){
      data_entry_helper::$javascript .= ($first ? "\n" : ",\n")."{'id':".$taxon['id'].",'taxon_meaning_id':".$taxon['taxon_meaning_id'].",'preferred_language_iso':'".$taxon["preferred_language_iso"]."','default_common_name':'".str_replace("'","\\'", $taxon["default_common_name"])."'}";
      $first = false;
    }
    data_entry_helper::$javascript .= "];\n";
    
    if (!empty($args['common_taxon_list_id'])) {
      $extraParams = array_merge($auth['read'],
          array('taxon_list_id' => $args['common_taxon_list_id'],
              'preferred' => 't',
              'allow_data_entry' => 't',
              'view' => 'cache',
              'orderby' => 'taxonomic_sort_order'));
      if (!empty($args['common_taxon_filter_field']) && !empty($args['common_taxon_filter']))
        $extraParams[$args['common_taxon_filter_field']] = helper_base::explode_lines($args['common_taxon_filter']);
      $taxa = data_entry_helper::get_population_data(array('table' => 'taxa_taxon_list', 'extraParams' => $extraParams));
      data_entry_helper::$javascript .= "indiciaData.speciesList1SubsetList = [";
      $first = true;
      foreach($taxa as $taxon){
        data_entry_helper::$javascript .= ($first ? "\n" : ",\n")."{'id':".$taxon['id'].",'taxon_meaning_id':".$taxon['taxon_meaning_id'].",'preferred_language_iso':'".$taxon["preferred_language_iso"]."','default_common_name':'".str_replace("'","\\'", $taxon["default_common_name"])."'}";
        $first = false;
      }
      data_entry_helper::$javascript .= "];\n";
      $swc = isset($args['start_with_common_species']) && $args['start_with_common_species'] ? 'true' : 'false';
      data_entry_helper::$javascript .= "indiciaData.startWithCommonSpecies=$swc;\n";
    }

    $allTaxonMeaningIdsAtTransect = data_entry_helper::get_population_data(array(
        'report' => 'reports_for_prebuilt_forms/UKBMS/ukbms_taxon_meanings_at_transect',
        'extraParams' => $auth['read'] + array('location_id' => $parentLocId, 'survey_id'=>$args['survey_id']),
        // don't cache as this is live data
        'nocache' => true
    ));
    
    data_entry_helper::$javascript .= "indiciaData.allTaxonMeaningIdsAtTransect = [";
    $first = true;
    foreach($allTaxonMeaningIdsAtTransect as $taxon){
    	data_entry_helper::$javascript .= ($first ? "" : ",").$taxon['taxon_meaning_id'];
    	$first = false;
    }
    data_entry_helper::$javascript .= "];\n";

    if(isset($args['second_taxon_list_id']) && $args['second_taxon_list_id']!=''){
      $isNumber = ($occ_attributes[(isset($args['occurrence_attribute_id_2']) && $args['occurrence_attribute_id_2']!="" ?
      		    $args['occurrence_attribute_id_2'] : $args['occurrence_attribute_id'])]["data_type"] == 'I');
      $r .= '<div id="grid2"><p id="grid2-loading">' . lang::get('Loading - Please Wait') . '</p>' . (isset($args['supress_tab_msg']) && $args['supress_tab_msg'] ? '' : '<p>' . lang::get('LANG_Tab_Msg') . '</p>') . '<table id="transect-input2" class="ui-widget species-grid"><thead class="table-header">';
      $r .= '<tr><th class="ui-widget-header">' . lang::get('Sections') . '</th>';
      foreach ($sections as $idx=>$section) {
        $r .= '<th class="ui-widget-header col-'.($idx+1).'">' . $section['code'] . '</th>';
      }
      $r .= ($isNumber ? '<th class="ui-widget-header">' . lang::get('Total') . '</th>' : '').'</tr></thead>';
      // No output rows at the top for any transect section level sample attributes in second grid.
      $r .= '<tbody class="ui-widget-content occs-body"></tbody>';
      if($isNumber) {
        $r .= '<tfoot><tr><td>Total</td>';
        foreach ($sections as $idx=>$section) {
          $r .= '<td class="col-'.($idx+1).' '.($idx % 5 == 0 ? 'first' : '').' col-total"></td>';
        }
        $r .= '<td class="ui-state-disabled first"></td></tr></tfoot>';
      }
      $r .= '</table>';
      if(!isset($args['force_second']) || !$args['force_second'])
        $r .= '<label for="taxonLookupControl2" class="auto-width">'.lang::get('Add species to list').':</label> <input id="taxonLookupControl2" name="taxonLookupControl2" >';
      $r .= '<br /><a href="'.$args['my_walks_page'].'" class="button">'.lang::get('Finish').'</a></div>';
    }
    if(isset($args['third_taxon_list_id']) && $args['third_taxon_list_id']!=''){
      $isNumber = ($occ_attributes[(isset($args['occurrence_attribute_id_3']) && $args['occurrence_attribute_id_3']!="" ?
          $args['occurrence_attribute_id_3'] : $args['occurrence_attribute_id'])]["data_type"] == 'I');
      $r .= '<div id="grid3"><p id="grid3-loading">' . lang::get('Loading - Please Wait') . '</p>' . (isset($args['supress_tab_msg']) && $args['supress_tab_msg'] ? '' : '<p>' . lang::get('LANG_Tab_Msg') . '</p>') . '<table id="transect-input3" class="ui-widget species-grid"><thead class="table-header">';
      $r .= '<tr><th class="ui-widget-header">' . lang::get('Sections') . '</th>';
      foreach ($sections as $idx=>$section) {
        $r .= '<th class="ui-widget-header col-'.($idx+1).'">' . $section['code'] . '</th>';
      }
      $r .= ($isNumber ? '<th class="ui-widget-header">' . lang::get('Total') . '</th>' : '').'</tr></thead>';
      // No output rows at the top for any transect section level sample attributes in second grid.
      $r .= '<tbody class="ui-widget-content occs-body"></tbody>';
      if($isNumber) {
        $r .= '<tfoot><tr><td>Total</td>';
        foreach ($sections as $idx=>$section) {
          $r .= '<td class="col-'.($idx+1).' '.($idx % 5 == 0 ? 'first' : '').' col-total"></td>';
        }
        $r .= '<td class="ui-state-disabled first"></td></tr></tfoot>';
      }
      $r .= '</table>';
      if(!isset($args['force_third']) || !$args['force_third'])
        $r .= '<label for="taxonLookupControl3" class="auto-width">'.lang::get('Add species to list').':</label> <input id="taxonLookupControl3" name="taxonLookupControl3" >';
      $r .= '<br /><a href="'.$args['my_walks_page'].'" class="button">'.lang::get('Finish').'</a></div>';
    }
    if(isset($args['fourth_taxon_list_id']) && $args['fourth_taxon_list_id']!=''){
      $isNumber = ($occ_attributes[(isset($args['occurrence_attribute_id_4']) && $args['occurrence_attribute_id_4']!="" ?
          $args['occurrence_attribute_id_4'] : $args['occurrence_attribute_id'])]["data_type"] == 'I');
      $r .= '<div id="grid4"><p id="grid4-loading">' . lang::get('Loading - Please Wait') . '</p>' . (isset($args['supress_tab_msg']) && $args['supress_tab_msg'] ? '' : '<p>' . lang::get('LANG_Tab_Msg') . '</p>') . '<table id="transect-input4" class="ui-widget species-grid"><thead class="table-header">';
      $r .= '<tr><th class="ui-widget-header">' . lang::get('Sections') . '</th>';
      foreach ($sections as $idx=>$section) {
        $r .= '<th class="ui-widget-header col-'.($idx+1).'">' . $section['code'] . '</th>';
      }
      $r .= ($isNumber ? '<th class="ui-widget-header">' . lang::get('Total') . '</th>' : '').'</tr></thead>';
      // No output rows at the top for any transect section level sample attributes in second grid.
      $r .= '<tbody class="ui-widget-content occs-body"></tbody>';
      if($isNumber) {
        $r .= '<tfoot><tr><td>Total</td>';
        foreach ($sections as $idx=>$section) {
          $r .= '<td class="col-'.($idx+1).' '.($idx % 5 == 0 ? 'first' : '').' col-total"></td>';
        }
        $r .= '<td class="ui-state-disabled first"></td></tr></tfoot>';
      }
      $r .= '</table>';
      if(!isset($args['force_fourth']) || !$args['force_fourth'])
        $r .= '<label for="taxonLookupControl4" class="auto-width">'.lang::get('Add species to list').':</label> <input id="taxonLookupControl4" name="taxonLookupControl4" >';
      $r .= '<br /><a href="'.$args['my_walks_page'].'" class="button">'.lang::get('Finish').'</a></div>';
    }
    $reloadPath = self::getReloadPath();
    if(isset($args['map_taxon_list_id']) && $args['map_taxon_list_id']!=''){
      // TODO convert to AJAX.
      data_entry_helper::enable_validation('entry_form');
      $value = helper_base::explode_lines_key_value_pairs($args['defaults']);
      $value = isset($value['occurrence:record_status']) ? $value['occurrence:record_status'] : 'C';
      
      $r .= '<div id="gridmap">'."\n".'<form method="post" id="entry_form" action="'.$reloadPath.'">'.$auth['write'].
            '<p>When using this page, please remember that the data is not saved to the database as you go (which is the case for the previous tabs). In order to save the data entered in this page you must click on the Save button at the bottom of the page.</p>'.
            '<input type="hidden" id="website_id" name="website_id" value="'.$args["website_id"].'" />'.
            '<input type="hidden" id="survey_id" name="sample:survey_id" value="'.$args["survey_id"].'" />'.
            '<input type="hidden" id="occurrence:record_status" name="occurrence:record_status" value="'.$value.'" />'.
            '<input type="hidden" name="sample:id" value="'.data_entry_helper::$entity_to_load['sample:id'].'"/>'.
            '<input type="hidden" name="page" value="speciesmap"/>'.
            '<input type="hidden" name="sample:location_id" value="'.$parentLocId.'"/>';
      if (preg_match('/^(\d{4})/', data_entry_helper::$entity_to_load['sample:date'])) {
        // Date has 4 digit year first (ISO style) - convert date to expected output format
        $d = new DateTime(data_entry_helper::$entity_to_load['sample:date']);
        data_entry_helper::$entity_to_load['sample:date'] = $d->format('d/m/Y');
      }
      $r .= '<input type="hidden" name="sample:date" value="'.data_entry_helper::$entity_to_load['sample:date'].'"/>';
      // leave the sample_method as it is stored now.
      // Dont need the place search, as we will zoom in to the main location. TODO
      $options = iform_map_get_map_options($args, $auth["read"]);
      if (isset(data_entry_helper::$entity_to_load["sample:geom"])) {
        $options["initialFeatureWkt"] = data_entry_helper::$entity_to_load["sample:wkt"];
      }
      $options["tabDiv"] = "gridmap";
      $olOptions = iform_map_get_ol_options($args);
      if (!isset($options["standardControls"]))
        $options["standardControls"]=array("layerSwitcher","panZoomBar");
      $r .= data_entry_helper::map_panel($options, $olOptions);
      // [species map]
      $r .= self::control_speciesmap($auth, $args, "gridmap", array());
      /**
       * The speciesmapsummary is not implemented here
       */
      $r .= '<input type="submit" class="indicia-button" id="save-button" value="'.lang::get('Save').'" /></form></div>';
      data_entry_helper::$javascript .= "var speciesMapTabHandler = function(event, ui) {
  if (ui.panel.id=='".$options["tabDiv"]."') {
    if (indiciaData.ParentSampleLayer.features.length > 0) {
      var bounds=indiciaData.ParentSampleLayer.getDataExtent();
      bounds.extend(indiciaData.SubSampleLayer.getDataExtent());
      // extend the boundary to include a buffer, so the map does not zoom too tight.
      bounds.scale(1.2);
      indiciaData.ParentSampleLayer.map.zoomToExtent(bounds);
    }
  }
};
jQuery(jQuery('#".$options["tabDiv"]."').parent()).bind('tabsshow', speciesMapTabHandler);\n";
    } else // enable validation on the comments form in order to include the simplified ajax queuing for the autocomplete.
      data_entry_helper::enable_validation('notes_form');

    // for the comment form, we want to ensure that if there is a timeout error that it reloads the 
    // data as stored in the DB.
    $reloadParts = explode('?', $reloadPath, 2);
    // fragment is always at the end. discard this.
    if(count($reloadParts)>1){
    	$params = explode('#', $reloadParts[1], 2);
    	$params=$params[0]."&sample_id=".$parentSampleId;
    } else {
    	$reloadParts = explode('#', $reloadParts[0], 2);
    	$params = "sample_id=".$parentSampleId;
    }
    
    $r .= "<div id=\"notes\">\n";
    $r .= "<form method=\"post\" id=\"notes_form\" action=\"".$reloadParts[0].'?'.$params."#notes\">\n";
    $r .= $auth['write'];
    $r .= '<input type="hidden" name="sample:id" value="'.$parentSampleId.'" />';
    $r .= '<input type="hidden" name="website_id" value="'.$args['website_id'].'"/>';
    $r .= '<input type="hidden" name="survey_id" value="'.$args['survey_id'].'"/>';
    $r .= '<input type="hidden" name="page" value="notes"/>';
    $r .= '<p  class="page-notice ui-state-highlight ui-corner-all">'.
          lang::get('When using this page, please remember that the data is not saved to the database as you go (which is the case for the previous tabs). In order to save the data entered in this page you must click on the Submit button at the bottom of the page.').
          '</p>';
    $r .= data_entry_helper::textarea(array(
      'fieldname'=>'sample:comment',
      'label'=>lang::get('Notes'),
      'helpText'=>"Use this space to input comments about this week's walk."
    ));    
    $r .= '<input type="submit" value="'.lang::get('Submit').'" id="save-button"/>';
    $r .= '</form>';
    $r .= '<br /><a href="'.$args['my_walks_page'].'" class="button">'.lang::get('Finish').'</a>';
    $r .= '</div></div>';
    // A stub form for AJAX posting when we need to create an occurrence
    $r .= '<form style="display: none" id="occ-form" method="post" action="'.iform_ajaxproxy_url($node, 'occurrence').'">';
    $r .= '<input name="website_id" value="'.$args['website_id'].'"/>';
    $r .= '<input name="survey_id" value="'.$args["survey_id"].'" />';
    $r .= '<input name="occurrence:id" id="occid" />';
    $r .= '<input name="occurrence:deleted" id="occdeleted" />';
    $r .= '<input name="occurrence:zero_abundance" id="occzero" />';
    $r .= '<input name="occurrence:taxa_taxon_list_id" id="ttlid" />';
    $r .= '<input name="occurrence:sample_id" id="occ_sampleid"/>';
    if(isset($args["sensitiveAttrID"]) && $args["sensitiveAttrID"] != "" && isset($args["sensitivityPrecision"]) && $args["sensitivityPrecision"] != "") {
      $locationTypes = helper_base::get_termlist_terms($auth, 'indicia:location_types', array(empty($args['transect_type_term']) ? 'Transect' : $args['transect_type_term']));
      $site_attributes = data_entry_helper::getAttributes(array(
            'valuetable'=>'location_attribute_value'
            ,'attrtable'=>'location_attribute'
            ,'key'=>'location_id'
            ,'fieldprefix'=>'locAttr'
            ,'extraParams'=>$auth['read'] + array('id'=>$args["sensitiveAttrID"])
            ,'location_type_id'=>$locationTypes[0]['id']
            ,'survey_id'=>$args['survey_id']
            ,'id' => $parentLocId // location ID
      ));
      $r .= '<input name="occurrence:sensitivity_precision" id="occSensitive" value="'.
            (count($site_attributes)>0 && $site_attributes[$args["sensitiveAttrID"]]['default']=="1" ? $args["sensitivityPrecision"] : '')
            .'"/>';
    }
    $r .= '<input name="occAttr:' . $args['occurrence_attribute_id'] . '" id="occattr"/>';
    $r .= '<input name="transaction_id" id="transaction_id"/>';
    $r .= '<input name="user_id" value="'.hostsite_get_user_field('user_id', 1).'"/>';
    $r .= '</form>';
    // A stub form for AJAX posting when we need to update a sample
    $r .= '<form style="display: none" id="smp-form" method="post" action="'.iform_ajaxproxy_url($node, 'sample').'">';
    $r .= '<input name="website_id" value="'.$args['website_id'].'"/>';
    $r .= '<input name="sample:id" id="smpid" />';
    $r .= '<input name="sample:parent_id" value="'.$parentSampleId.'" />';
    $r .= '<input name="sample:survey_id" value="'.$args['survey_id'].'" />';
    $r .= '<input name="sample:sample_method_id" value="'.$sampleMethods[0]['id'].'" />';
    $r .= '<input name="sample:entered_sref" id="smpsref" />';
    $r .= '<input name="sample:entered_sref_system" id="smpsref_system" />';
    $r .= '<input name="sample:location_id" id="smploc" />';
    $r .= '<input name="sample:date" value="'.$date.'" />';
    // include a stub input for each transect section sample attribute
    foreach ($attributes as $attr) {
      $r .= '<input id="'.$attr['fieldname'].'" />';
    }
    $r .= '</form>';
    // tell the Javascript where to get species from.
    // @todo handle diff species lists.
    data_entry_helper::add_resource('jquery_ui');
    data_entry_helper::add_resource('json');
    data_entry_helper::add_resource('autocomplete');

    data_entry_helper::$javascript .= "indiciaData.speciesList1 = ".$args['taxon_list_id'].";\n";
    if (!empty($args['main_taxon_filter_field']) && !empty($args['main_taxon_filter'])) {
      data_entry_helper::$javascript .= "indiciaData.speciesList1FilterField = '".$args['main_taxon_filter_field']."';\n";
      $filterLines = helper_base::explode_lines($args['main_taxon_filter']);
      data_entry_helper::$javascript .= "indiciaData.speciesList1FilterValues = '".json_encode($filterLines)."';\n";
    }
    data_entry_helper::$javascript .= "bindSpeciesAutocomplete(\"taxonLookupControl\",\"table#transect-input1\",\"".data_entry_helper::$base_url."index.php/services/data\", \"".$args['taxon_list_id']."\",
  indiciaData.speciesList1FilterField, indiciaData.speciesList1FilterValues, {\"auth_token\" : \"".$auth['read']['auth_token']."\", \"nonce\" : \"".$auth['read']['nonce']."\"},
  \"".lang::get('LANG_Duplicate_Taxon')."\", 25, 1);\n
indiciaData.speciesList1Subset = ".(isset($args['common_taxon_list_id']) && $args['common_taxon_list_id']!="" ? $args['common_taxon_list_id'] : "-1").";\n";
    if (!empty($args['common_taxon_filter_field']) && !empty($args['common_taxon_filter'])) {
      data_entry_helper::$javascript .= "indiciaData.speciesList1SubsetFilterField = '".$args['common_taxon_filter_field']."';\n";
      $filterLines = helper_base::explode_lines($args['common_taxon_filter']);
      data_entry_helper::$javascript .= "indiciaData.speciesList1SubsetFilterValues = '".json_encode($filterLines)."';\n";
    }

    data_entry_helper::$javascript .= "indiciaData.speciesList2 = ".(isset($args['second_taxon_list_id']) && $args['second_taxon_list_id'] != "" ? $args['second_taxon_list_id'] : "-1").";\n";
    if (!empty($args['second_taxon_filter_field']) && !empty($args['second_taxon_filter'])) {
      data_entry_helper::$javascript .= "indiciaData.speciesList2FilterField = '".$args['second_taxon_filter_field']."';\n";
      $filterLines = helper_base::explode_lines($args['second_taxon_filter']);
      data_entry_helper::$javascript .= "indiciaData.speciesList2FilterValues = ".json_encode($filterLines).";\n";
    }
    data_entry_helper::$javascript .= "indiciaData.speciesList2Force = ".(isset($args['force_second']) && $args['force_second'] ? 'true' : 'false').";\n";
    if(!isset($args['force_second']) || !$args['force_second'])
      data_entry_helper::$javascript .= "bindSpeciesAutocomplete(\"taxonLookupControl2\",\"table#transect-input2\",\"".data_entry_helper::$base_url."index.php/services/data\", indiciaData.speciesList2,
  indiciaData.speciesList2FilterField, indiciaData.speciesList2FilterValues, {\"auth_token\" : \"".$auth['read']['auth_token']."\", \"nonce\" : \"".$auth['read']['nonce']."\"},
  \"".lang::get('LANG_Duplicate_Taxon')."\", 25, 2);\n\n";

    data_entry_helper::$javascript .= "indiciaData.speciesList3 = ".(isset($args['third_taxon_list_id']) && $args['third_taxon_list_id'] != "" ? $args['third_taxon_list_id'] : "-1").";\n";
    if (!empty($args['third_taxon_filter_field']) && !empty($args['third_taxon_filter'])) {
      data_entry_helper::$javascript .= "indiciaData.speciesList3FilterField = '".$args['third_taxon_filter_field']."';\n";
      $filterLines = helper_base::explode_lines($args['third_taxon_filter']);
      data_entry_helper::$javascript .= "indiciaData.speciesList3FilterValues = ".json_encode($filterLines).";\n";
    }
    data_entry_helper::$javascript .= "indiciaData.speciesList3Force = ".(isset($args['force_third']) && $args['force_third'] ? 'true' : 'false').";\n";
    if(!isset($args['force_third']) || !$args['force_third'])
      data_entry_helper::$javascript .= "bindSpeciesAutocomplete(\"taxonLookupControl3\",\"table#transect-input3\",\"".data_entry_helper::$base_url."index.php/services/data\", indiciaData.speciesList3,
    indiciaData.speciesList3FilterField, indiciaData.speciesList3FilterValues, {\"auth_token\" : \"".$auth['read']['auth_token']."\", \"nonce\" : \"".$auth['read']['nonce']."\"},
    \"".lang::get('LANG_Duplicate_Taxon')."\", 25, 3);\n\n";
    
    data_entry_helper::$javascript .= "indiciaData.speciesList4 = ".(isset($args['fourth_taxon_list_id']) && $args['fourth_taxon_list_id'] != "" ? $args['fourth_taxon_list_id'] : "-1").";\n";
    if (!empty($args['fourth_taxon_filter_field']) && !empty($args['fourth_taxon_filter'])) {
      data_entry_helper::$javascript .= "indiciaData.speciesList4FilterField = '".$args['fourth_taxon_filter_field']."';\n";
      $filterLines = helper_base::explode_lines($args['fourth_taxon_filter']);
      data_entry_helper::$javascript .= "indiciaData.speciesList4FilterValues = ".json_encode($filterLines).";\n";
    }
    data_entry_helper::$javascript .= "indiciaData.speciesList4Force = ".(isset($args['force_fourth']) && $args['force_fourth'] ? 'true' : 'false').";\n";
    // allow js to do AJAX by passing in the information it needs to post forms
    if(!isset($args['force_fourth']) || !$args['force_fourth'])
      data_entry_helper::$javascript .= "bindSpeciesAutocomplete(\"taxonLookupControl4\",\"table#transect-input4\",\"".data_entry_helper::$base_url."index.php/services/data\", indiciaData.speciesList4,
  indiciaData.speciesList4FilterField, indiciaData.speciesList4FilterValues, {\"auth_token\" : \"".$auth['read']['auth_token']."\", \"nonce\" : \"".$auth['read']['nonce']."\"},
  \"".lang::get('LANG_Duplicate_Taxon')."\", 25, 4);\n\n";

    data_entry_helper::$javascript .= "indiciaData.indiciaSvc = '".data_entry_helper::$base_url."';\n";
    data_entry_helper::$javascript .= "indiciaData.readAuth = {nonce: '".$auth['read']['nonce']."', auth_token: '".$auth['read']['auth_token']."'};\n";
    data_entry_helper::$javascript .= "indiciaData.transect = ".$parentLocId.";\n";
    data_entry_helper::$javascript .= "indiciaData.parentSample = ".$parentSampleId.";\n";
    data_entry_helper::$javascript .= "indiciaData.sections = ".json_encode($sections).";\n";
    if (function_exists('module_exists') && module_exists('easy_login')) {
      data_entry_helper::$javascript .= "indiciaData.easyLogin = true;\n";
      $userId = hostsite_get_user_field('indicia_user_id');
      if (!empty($userId)) data_entry_helper::$javascript .= "indiciaData.UserID = ".$userId.";\n";
      else return '<p>Easy Login active but could not identify user</p>'; // something is wrong 
    } else {
      data_entry_helper::$javascript .= "indiciaData.easyLogin = false;\n";
      data_entry_helper::$javascript .= "indiciaData.CMSUserAttrID = ".$cmsUserAttr['attributeId'] .";\n";
      data_entry_helper::$javascript .= "indiciaData.CMSUserID = ".$user->uid.";\n";
    }
    // Do an AJAX population of the grid rows.
    data_entry_helper::$javascript .= "loadSpeciesList();
jQuery('#tabs').bind('tabsshow', function(event, ui) {
    var target = ui.panel;
    // first get rid of any previous tables
    jQuery('table.sticky-header').remove();
    jQuery('table.sticky-enabled thead.tableHeader-processed').removeClass('tableHeader-processed');
    jQuery('table.sticky-enabled.tableheader-processed').removeClass('tableheader-processed');
    jQuery('table.species-grid.sticky-enabled').removeClass('sticky-enabled');
    var table = jQuery('#'+target.id+' table.species-grid');
    if(table.length > 0) {
        table.addClass('sticky-enabled');
        if(typeof Drupal.behaviors.tableHeader == 'object') // Drupal 7
          Drupal.behaviors.tableHeader.attach(table.parent());
        else // Drupal6 : it is a function
          Drupal.behaviors.tableHeader(target);
    }
    // remove any hanging autocomplete select list.
    jQuery('.ac_results').hide();
});";
    return $r;
  }

  protected static function getReloadPath () {
  	$reload = data_entry_helper::get_reload_link_parts();
  	unset($reload['params']['sample_id']);
  	unset($reload['params']['new']);
  	$reloadPath = $reload['path'];
  	if(count($reload['params'])) {
  		// decode params prior to encoding to prevent double encoding.
  		foreach ($reload['params'] as $key => $param) {
  			$reload['params'][$key] = urldecode($param);
  		}
  		$reloadPath .= '?'.http_build_query($reload['params']);
  	}
  	return $reloadPath;
  }

  /**
   * Get the control for map based species input, assumed to be multiple entry: ie a grid. Can be single species though.
   * Uses the normal species grid, so all options that apply to that, apply to this.
   * An option called sampleMethodId can be used to specify the sample method used for subsamples, and therefore the
   * controls to show for subsamples.
   */
  protected static function control_speciesmap($auth, $args, $tabAlias, $options) {
  	// The ID must be done here so it can be accessed by both the species grid and the buttons.
  	$code = rand(0,1000);
  	$defaults = array('id' => 'species-grid-'.$code, buttonsId => 'species-grid-buttons-'.$code);
  	$options = array_merge($defaults, $options);
    // assume gridmode = true
  	// Force a new option
  	$options['speciesControlToUseSubSamples'] = true;
  	$sampleMethods = helper_base::get_termlist_terms($auth, 'indicia:sample_methods', array('Field Observation'));
  	$options['subSampleSampleMethodID'] = $sampleMethods[0]['id'];
  	$options['base_url'] = data_entry_helper::$base_url;
  	if (!isset($args['cache_lookup']) || ($args['species_ctrl'] !== 'autocomplete'))
  		$args['cache_lookup']=false; // default for old form configurations or when not using an autocomplete
  	//The filter can be a URL or on the edit tab, so do the processing to work out the filter to use
    $filterLines = helper_base::explode_lines($args['map_taxon_filter']);
  	// store in the argument so that it can be used elsewhere
  	$args['map_taxon_filter'] = implode("\n", $filterLines);
  	//Single species mode only ever applies if we have supplied only one filter species and we aren't in taxon group mode
  	if ($args['map_taxon_filter_field']!=='taxon_group' && $args['map_taxon_filter_field']!=='' && count($filterLines)===1) {
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
  	self::build_grid_autocomplete_function($args);
  	// the imp-sref & imp-geom are within the dialog so it is updated.
  	$speciesCtrl = self::get_control_species_checklist($auth, $args, $extraParams, $options); // this preloads the subsample data.
  	$systems = explode(',', str_replace(' ', '', $args['spatial_systems']));
  	// note that this control only uses the first spatial reference system in the list.
  	$system = '<input type="hidden" id="imp-sref-system" name="sample:entered_sref_system" value="'.$systems[0].'" />';
  	// since we handle the system ourself, we need to include the system handled js files.
  	data_entry_helper::include_sref_handler_js(array($systems[0]=>''));
  	/** Switch off sample attributes for this until Indicia can handle allocation better
  	$args['sample_method_id'] = $sampleMethods[0]['id'];
  	$sampleAttrs = self::getAttributes($args, $auth);
  	foreach ($sampleAttrs as &$attr) {
  		$attr['fieldname'] = 'sc:n::'.$attr['fieldname'];
  		$attr['id'] = 'sc:n::'.$attr['id'];
  	}
  	$attrOptions = self::get_attr_specific_options($options);
  	$sampleCtrls = get_attribute_html($sampleAttrs, $args, array('extraParams' => $auth['read']), null, $attrOptions);
  	$r .= '<div id="'.$options['id'].'-subsample-ctrls" style="display: none">'.$sampleCtrls.'</div>';
  	**/
  	$r .= '<div id="'.$options['id'].'-subsample-ctrls" style="display: none"></div>';
  	$r .= '<div id="'.$options['id'].'-container" style="display: none">'.
  			'<input type="hidden" id="imp-sref" />'. // a dummy to capture feedback from the map
  			'<input type="hidden" id="imp-geom" />'. // a dummy to capture feedback from the map
  			'<input type="hidden" name="sample:entered_sref" value="'.data_entry_helper::check_default_value('sample:entered_sref', '').'">'.
  			'<input type="hidden" name="sample:geom" value="'.data_entry_helper::check_default_value('sample:geom', '').'" >'.
  			$system.
  			'<div id="'.$options['id'].'-blocks">'.
  			self::get_control_speciesmap_controls($auth, $args, $options).
  			'</div>'.
  			'<input type="hidden" value="true" name="speciesgridmapmode" />'.
  			$speciesCtrl.
  			'</div>';
  	return $r;
  }
  
  
  /* Set up the control JS and also return the existing data subsample blocks */
  protected static function get_control_speciesmap_controls($auth, $args, $options){
  	$langStrings = array('AddLabel' => lang::get("Add records to map"),
  			'AddMessage' => lang::get("Please click on the map where you would like to add your records. Zoom the map in for greater precision."),
  			'AddDataMessage' => lang::get("Please enter all the species records for this position into the grid below. When you have finished, click the Finish button to return to the map where you may choose another grid reference to enter data for."),
  
  			'MoveLabel' => lang::get("Move records"),
  			'MoveMessage1' => lang::get("Please select the records on the map you wish to move."),
  			'MoveMessage2' => lang::get("Please click on the map to choose the new position. Press the Cancel button to choose another set of records to move instead."),
  
  			'ModifyLabel' => lang::get("Modify records"),
  			'ModifyMessage1' => lang::get("Please select the records on the map you wish to change."),
  			'ModifyMessage2' => lang::get("Change (or add to) the records for this position. When you have finished, click the Finish button: this will return you to the map where you may choose another set of records to change."),
  
  			'DeleteLabel' => lang::get("Delete records"),
  			'DeleteMessage' => lang::get("Please select the records on the map you wish to delete."),
  			'ConfirmDeleteTitle' => lang::get("Confirm deletion of records"),
  			'ConfirmDeleteText' => lang::get("Are you sure you wish to delete all the records at {OLD}?"),
  
  			'CancelLabel' => lang::get("Cancel"),
  			'FinishLabel' => lang::get("Finish"),
  			'Yes' => lang::get("Yes"),
  			'No' => lang::get("No"),
  			'SRefLabel' => lang::get('LANG_SRef_Label'));
  	// make sure we load the JS.
  	data_entry_helper::add_resource('control_speciesmap_controls');
  	data_entry_helper::$javascript .= "control_speciesmap_addcontrols(".json_encode($options).",".json_encode($langStrings).");\n";
  	$blocks = "";
  	if (isset(data_entry_helper::$entity_to_load)) {
  		foreach(data_entry_helper::$entity_to_load as $key => $value){
  			$a = explode(':', $key, 4);
  			if(count($a)==4 && $a[0] == 'sc' && $a[3] == 'sample:entered_sref'){
  				$geomKey = $a[0].':'.$a[1].':'.$a[2].':sample:geom';
  				$idKey = $a[0].':'.$a[1].':'.$a[2].':sample:id';
  				$deletedKey = $a[0].':'.$a[1].':'.$a[2].':sample:deleted';
  				// dont need to worry about sample_method_id for existing subsamples.
  				$blocks .= '<div id="scm-'.$a[1].'-block" class="scm-block">'.
  						'<label>'.lang::get('LANG_SRef_Label').':</label> '.
  						'<input type="text" value="'.$value.'" readonly="readonly" name="'.$key.'">'.
  						'<input type="hidden" value="'.data_entry_helper::$entity_to_load[$geomKey].'" name="'.$geomKey.'">'.
  						'<input type="hidden" value="'.(isset(data_entry_helper::$entity_to_load[$deletedKey]) ? data_entry_helper::$entity_to_load[$deletedKey] : 'f').'" name="'.$deletedKey.'">'.
  						(isset(data_entry_helper::$entity_to_load[$idKey]) ? '<input type="hidden" value="'.data_entry_helper::$entity_to_load[$idKey].'" name="'.$idKey.'">' : '');
  				/** Switch off sample attributes for this until Indicia can handle allocation better
  				if (!empty($options['sampleMethodId'])) {
  					$sampleAttrs = self::getAttributesForSample($args, $auth, $a[2]);
  					foreach ($sampleAttrs as &$attr) {
  						$attr['fieldname'] = 'sc:'.$a[1].':'.$a[2].':'.$attr['fieldname'];
  						$attr['id'] = 'sc:'.$a[1].':'.$a[2].':'.$attr['id'];
  					}
  					$attrOptions = self::get_attr_specific_options($options);
  					$sampleCtrls = get_attribute_html($sampleAttrs, $args, array('extraParams' => $auth['read']), null, $attrOptions);
  					$blocks .= '<div id="scm-'.$a[1].'-subsample-ctrls">' .
  							$sampleCtrls .
  							'</div>';
  				}
  				**/
  				$blocks .= '</div>';
  			}
  		}
  	}
  	return $blocks;
  }
  
  /**
   * Parses an options array to extract the attribute specific option settings, e.g. smpAttr:4|caption=Habitat etc.
   * Commented out until attribute handling sorted on warehouse.
   *
  private static function get_attr_specific_options($options) {
  	$attrOptions = array();
  	foreach ($options as $option => $value) {
  		if (preg_match('/^(?P<controlname>[a-z][a-z][a-z]Attr:[0-9]*)\|(?P<option>.*)$/', $option, $matches)) {
  			if (!isset($attrOptions[$matches['controlname']]))
  				$attrOptions[$matches['controlname']] = array();
  			$attrOptions[$matches['controlname']][$matches['option']] = $value;
  		}
  	}
  	return $attrOptions;
  }
  */
  
  /**
   * Get the species data for the page in single species mode
   */
  protected static function get_single_species_data($auth, $args, $filterLines) {
  	//The form is configured for filtering by taxon name, meaning id or external key. If there is only one specified, then the form
  	//cannot display a species checklist, as there is no point. So, convert our preferred taxon name, meaning ID or external_key to find the
  	//preferred taxa_taxon_list_id from the selected checklist
  	$filter = array(
  			'preferred'=>'t',
  			'taxon_list_id'=>$args['map_taxon_list_id']
  	);
  	if ($args['map_taxon_filter_field']=='preferred_name') {
  		$filter['taxon']=$filterLines[0];
  	} else {
  		$filter[$args['map_taxon_filter_field']]=$filterLines[0];
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
  	if (isset($options['view']))
  		$extraParams['view'] = $options['view'];
  	// make sure that if extraParams is specified as a config option, it does not replace the essential stuff
  	if (isset($options['extraParams']))
  		$options['extraParams'] = array_merge($extraParams, $options['extraParams']);
  	$species_ctrl_opts=array_merge(array(
  			'occAttrOptions' => array(),
  			'listId' => '',
  			'label' => lang::get('occurrence:taxa_taxon_list_id'),
  			'columns' => 1,
  			'extraParams' => $extraParams,
  			'survey_id' => $args['survey_id'],
  			'occurrenceComment' => $args['occurrence_comment'],
  			'occurrenceSensitivity' => (isset($args['occurrence_sensitivity']) ? $args['occurrence_sensitivity'] : false),
  			'occurrenceImages' => $args['occurrence_images'],
  			'PHPtaxonLabel' => true,
  			'language' => iform_lang_iso_639_2(hostsite_get_user_field('language')), // used for termlists in attributes
  			'cacheLookup' => $args['cache_lookup'],
  			'speciesNameFilterMode' => self::getSpeciesNameFilterMode($args),
  			'userControlsTaxonFilter' => false,
  			'subSpeciesColumn' => false,
  			'copyDataFromPreviousRow' => false,
  			'editTaxaNames' => !empty($args['edit_taxa_names']) && $args['edit_taxa_names']
  	), $options);
  	if ($groups=hostsite_get_user_field('taxon_groups')) {
  		$species_ctrl_opts['usersPreferredGroups'] = unserialize($groups);
  	}
  	if ($args['map_taxon_list_id']) $species_ctrl_opts['lookupListId']=$args['map_taxon_list_id'];
  	//We only do the work to setup the filter if the user has specified a filter in the box
  	if (!empty($args['map_taxon_filter_field']) && (!empty($args['map_taxon_filter']))) {
  		$species_ctrl_opts['taxonFilterField']=$args['map_taxon_filter_field'];
  		$filterLines = helper_base::explode_lines($args['map_taxon_filter']);
  		$species_ctrl_opts['taxonFilter']=$filterLines;
  	}
  	if (isset($args['col_widths']) && $args['col_widths']) $species_ctrl_opts['colWidths']=explode(',', $args['col_widths']);
  	self::build_grid_taxon_label_function($args, $options);
  	// Start by outputting a hidden value that tells us we are using a grid when the data is posted,
  	// then output the grid control
  	return '<input type="hidden" value="true" name="gridmode" />'.
  			data_entry_helper::species_checklist($species_ctrl_opts);
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
   * Build a JavaScript function  to format the display of existing taxa added to the species input grid
   * when an existing sample is loaded.
   */
  protected static function build_grid_taxon_label_function($args, $options) {
  	global $indicia_templates;
  	if (!empty($options['taxonLabelTemplate']) && !empty($indicia_templates[$options['taxonLabelTemplate']])) {
  		$indicia_templates['taxon_label'] = $indicia_templates[$options['taxonLabelTemplate']];
  		return;
  	}
  	// Set up the indicia templates for taxon labels according to options, as long as the template has been left at it's default state
  	if ($indicia_templates['taxon_label'] == '<div class="biota"><span class="nobreak sci binomial"><em>{taxon}</em></span> {authority} '.
  			'<span class="nobreak vernacular">{common}</span></div>') {
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
  	$indicia_templates['taxon_label'] = $php;
  	}
  }
  
  /**
   * Load the attributes for the sample defined by $entity_to_load
   */
  protected static function getAttributes($args, $auth) {
  	return self::getAttributesForSample($args, $auth, data_entry_helper::$entity_to_load['sample:id']);
  }
  
  /**
   * Load the attributes for the sample defined by a supplied Id.
   */
  private static function getAttributesForSample($args, $auth, $id) {
  	$attrOpts = array(
  			'valuetable'=>'sample_attribute_value'
  			,'attrtable'=>'sample_attribute'
  			,'key'=>'sample_id'
  			,'fieldprefix'=>'smpAttr'
  			,'extraParams'=>$auth['read']
  			,'survey_id'=>$args['survey_id']
  	);
  	if (!empty($id))
  		$attrOpts['id'] = $id;
  	// select only the custom attributes that are for this sample method or all sample methods, if this
  	// form is for a specific sample method.
  	if (!empty($args['sample_method_id']))
  		$attrOpts['sample_method_id']=$args['sample_method_id'];
  	$attributes = data_entry_helper::getAttributes($attrOpts, false);
  	return $attributes;
  }
  

  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   * @todo: Implement this method
   */
  public static function get_submission($values, $args) {
    $subsampleModels = array();
    if (isset($values['page']) && ($values['page']=='speciesmap')) {
      $submission = data_entry_helper::build_sample_subsamples_occurrences_submission($values);
    } else {
    if (!isset($values['page']) || ($values['page']=='mainSample')) {
      // submitting the first page, with top level sample details
      $read = array(
        'nonce' => $values['read_nonce'],
        'auth_token' => $values['read_auth_token']
      );
      if (!isset($values['sample:entered_sref'])) {
        // the sample does not have sref data, as the user has just picked a transect site at this point. Copy the
        // site's centroid across to the sample. Should this be cached?
        $site = data_entry_helper::get_population_data(array(
          'table' => 'location',
          'extraParams' => $read + array('view'=>'detail','id'=>$values['sample:location_id'],'deleted'=>'f')
        ));
        $site = $site[0];
        $values['sample:entered_sref'] = $site['centroid_sref'];
        $values['sample:entered_sref_system'] = $site['centroid_sref_system'];
      }
      // Build the subsamples
      $sections = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $read + array('view'=>'detail','parent_id'=>$values['sample:location_id'],'deleted'=>'f'),
        'nocache' => true // may have recently added or removed a section
      ));
      if(isset($values['sample:id'])){
        $existingSubSamples = data_entry_helper::get_population_data(array(
          'table' => 'sample',
          'extraParams' => $read + array('view'=>'detail','parent_id'=>$values['sample:id'],'deleted'=>'f'),
          'nocache' => true  // may have recently added or removed a section
        ));
      } else $existingSubSamples = array();
      $sampleMethods = helper_base::get_termlist_terms(array('read'=>$read), 'indicia:sample_methods', array('Transect Section'));
      $attributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'sample_attribute_value',
        'attrtable'=>'sample_attribute',
        'key'=>'sample_id',
        'fieldprefix'=>'smpAttr',
        'extraParams'=>$read,
        'survey_id'=>$values['sample:survey_id'],
        'sample_method_id'=>$sampleMethods[0]['id'],
        'multiValue'=>false // ensures that array_keys are the list of attribute IDs.
      ));
      $smpDate = self::parseSingleDate($values['sample:date']);
      foreach($sections as $section){
      	$smp = false;
        $exists=false;
        foreach($existingSubSamples as $existingSubSample){
          if($existingSubSample['location_id'] == $section['id']){
            $exists = $existingSubSample;
            break;
          }
        }
        if(!$exists){
          $smp = array('fkId' => 'parent_id',
                   'model' => array('id' => 'sample',
                     'fields' => array('survey_id' => array('value' => $values['sample:survey_id']),
                                       'website_id' => array('value' => $values['website_id']),
                                       'date' => array('value' => $values['sample:date']),
                                       'location_id' => array('value' => $section['id']),
                                       'entered_sref' => array('value' => $section['centroid_sref']),
                                       'entered_sref_system' => array('value' => $section['centroid_sref_system']),
                                       'sample_method_id' => array('value' => $sampleMethods[0]['id'])
                     )),
                   'copyFields' => array('date_start'=>'date_start','date_end'=>'date_end','date_type'=>'date_type'));
          foreach ($attributes as $attr) {
            foreach ($values as $key => $value){
              $parts = explode(':',$key);
              if(count($parts)>1 && $parts[0]=='smpAttr' && $parts[1]==$attr['attributeId']){
                $smp['model']['fields']['smpAttr:'.$attr['attributeId']] = array('value' => $value);
              }
            }
          }
        } else { // need to ensure any date change is propagated: only do if date has changed for performance reasons.
          $subSmpDate = self::parseSingleDate($exists['date_start']);
          if(strcmp($smpDate,$subSmpDate))
        	$smp = array('fkId' => 'parent_id',
        			'model' => array('id' => 'sample',
        					'fields' => array('survey_id' => array('value' => $values['sample:survey_id']),
        							'website_id' => array('value' => $values['website_id']),
        							'id' => array('value' => $exists['id']),
                                    'date' => array('value' => $values['sample:date']),
        							'location_id' => array('value' => $exists['location_id'])
        					)),
        			'copyFields' => array('date_start'=>'date_start','date_end'=>'date_end','date_type'=>'date_type'));
        }
        if($smp) $subsampleModels[] = $smp;
      }
    }
    $submission = submission_builder::build_submission($values, array('model' => 'sample'));
    if(count($subsampleModels)>0)
      $submission['subModels'] = $subsampleModels;
    }
    return($submission);
  }
  
  // we assume that this is not quite vague: we are looking for variations of YYYY-MM-DD, with diff separators
  // and possibly reverse ordered: month always in middle.
  protected static function parseSingleDate($string){
    if(preg_match('#^\d{2}/\d{2}/\d{4}$#', $string)){ // DD/MM/YYYY
      $results = preg_split('#/#', $string);
      return $results[2].'-'.$results[1].'-'.$results[0];
    }
    return $string;
  }
  
  /**
   * Override the form redirect to go back to My Walks after the grid is submitted. Leave default redirect (current page)
   * for initial submission of the parent sample.
   */
  public static function get_redirect_on_success($values, $args) {
    return  ($values['page']==='speciesmap' || $values['page']==='delete') ? $args['my_walks_page'] : '';
  }

}
