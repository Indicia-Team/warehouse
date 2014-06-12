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

/**
 * Prebuilt form for the NPMS Paths survey
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
require_once('wildflower_count.php');

class iform_npms_paths extends iform_wildflower_count {
  // Values that $mode can take
  const MODE_GRID = 0; // default mode when no grid set to false - display grid of existing data
  const MODE_NEW = 1; // default mode when no_grid set to true - display an empty form for adding a new sample
  const MODE_EXISTING = 2; // display existing sample for editing
  const MODE_EXISTING_RO = 3; // display existing sample for reading only
  const MODE_CLONE = 4; // display form for adding a new sample containing values of an existing sample.
  protected static $mode;
  protected static $loadedSampleId;
  protected static $loadedOccurrenceId;
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_npms_paths_definition() {
    return array(
      'title'=>'NPMS Paths',
      'category' => 'Forms for specific surveying methods',
      'description'=>'NPMS paths form based on the Wildflower Count form.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires
   */
  public static function get_parameters() {   
    return array(
      array( 
        'name' => 'grid_report',
        'caption' => 'Grid Report',
        'description' => 'Name of the report to use to populate the grid for selecting existing data from.',
        'type'=>'string',
        'group' => 'User Interface',
        'default' => 'reports_for_prebuilt_forms/dynamic_sample_occurrence_samples'
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
          'fieldname'=>'list_id',
          'label'=>'Species List ',
          'helpText'=>'The species list that species can be selected from. This list is pre-populated '.
             'into the data entry grids.',
          'type'=>'select',
          'table'=>'taxon_list',
          'valueField'=>'id',
          'captionField'=>'title',
          'group'=>'Species',
          'siteSpecific'=>true
      ),
      array(
          'fieldname'=>'other_list_id',
          'label'=>'Other Species List ',
          'helpText'=>'The species list that species can be selected from for the Other Species tab.',
          'type'=>'select',
          'table'=>'taxon_list',
          'valueField'=>'id',
          'captionField'=>'title',
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
              'species. Therefore there will be no species picker control or input grid, and the form will always operate in the single record, non-grid mode. '.
              'As there is no visual indicator which species is recorded you may like to include information about what is being recorded in the '.
              'body text for the page. You may also want to configure the User Interface section of the form\'s Form Structure to move the [species] and [species] controls '.
              'to a different tab and remove the =species= tab, especially if there are no other occurrence attributes on the form.',
          'type' => 'textarea',
          'required'=>false,
          'group'=>'Species'
      ),
      array(
          'name'=>'term_surveyed_given_square',
          'caption'=>'Termlist terms ID - surveyed given square',
          'description'=>'The termlists_terms ID corresponding to surveying your given square.',
          'type'=>'text_input',
          'siteSpecific'=>true
      ),
      array(
          'name'=>'term_surveyed_other_square',
          'caption'=>'Termlist terms ID - surveyed other square',
          'description'=>'The termlists_terms ID corresponding to surveying another square.',
          'type'=>'text_input',
          'siteSpecific'=>true
      ),
      array(
          'name'=>'term_surveyed_same_square',
          'caption'=>'Termlist terms ID - surveyed same square',
          'description'=>'The termlists_terms ID corresponding to surveying the same square as last year.',
          'type'=>'text_input',
          'siteSpecific'=>true
      ),
      array(
        'name'=>'attr_surveyed_square',
        'caption'=>'Custom attribute for surveyed square',
        'description'=>'The attribute used to store the surveyed square.',
        'type'=>'select',
        'table'=>'sample_attribute',
        'valueField'=>'id',
        'captionField'=>'caption',
        'siteSpecific'=>true
      ),
      array(
        'name'=>'attr_surveyed_other_square_reason',
        'caption'=>'Custom attribute for surveyed other square reason',
        'description'=>'The attribute used to store the reason given if another square surveyed.',
        'type'=>'select',
        'table'=>'sample_attribute',
        'valueField'=>'id',
        'captionField'=>'caption',
        'siteSpecific'=>true
      ),
      array(
        'name'=>'survey_1_attr',
        'caption'=>'Survey 1 attribute ID',
        'description'=>'The sample attribute ID that will store the ID of survey 1.',
        'type'=>'string',
        'groupd'=>'Other IForm Parameters',
        'required'=>true
      ),
    );
  }
  
  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $node The Drupal node object.
   * @param array $errors When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   */
  public static function get_form($args, $node, $errors=null) {
    $r = '';
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    // Determine how the form was requested and therefore what to output
    self::$mode = self::getMode($args, $node);
    if(self::$mode ===  self::MODE_GRID) {
      //Displayed the grid when the page opens initially
      $r .= self::getGrid($args, $node, $auth);
    } else {
      // variables to tracks which parts of the plots grid are not completed, so we can display a correct message
      $someGridRefsMissing=false;
      $someHabitatsMissing=false;
      $someSqTypesMissing=false;
      data_entry_helper::$validation_mode = array('colour','hint','message');
      if (self::$mode ===  self::MODE_EXISTING || self::$mode ===  self::MODE_CLONE) {
        data_entry_helper::load_existing_record($auth['read'], 'sample', $_GET['sample_id']);
        if (isset(data_entry_helper::$entity_to_load['sample:date']) && preg_match('/^(\d{4})/', data_entry_helper::$entity_to_load['sample:date'])) {
          // Date has 4 digit year first (ISO style) - convert date to expected output format
          // @todo The date format should be a global configurable option. It should also be applied to reloading of custom date attributes.
          $d = new DateTime(data_entry_helper::$entity_to_load['sample:date']);
          data_entry_helper::$entity_to_load['sample:date'] = $d->format('d/m/Y');
        }
        $topSampleAttrs = self::load_top_sample_attrs($auth, $args, $_GET['sample_id']);
      } else {
        $topSampleAttrs = self::load_top_sample_attrs($auth, $args);
      }
      //If in mode clone, we want to load existing data but as a new sample. So strip out all the existing attribute value ids from the form
      //html so the data is loaded but it creates a new sample.
      if (self::$mode ===  self::MODE_CLONE)
        self::cloneEntity($args, $auth, $topSampleAttrs); 
      global $indicia_templates;
      $indicia_templates['starredSuffix']="*<br/>\n";
      $indicia_templates['validation_message'] = "<span class=\"ui-state-error-text\">{error}</span>\n";
      data_entry_helper::enable_validation('entry-form');
      $r .= '<form method="post" action="" id="entry-form">';
      $r .= '<div id="tabs">';
      data_entry_helper::enable_tabs(array('divId'=>'tabs','navButtons'=>true)); 
      $r .= data_entry_helper::tab_header(array('tabs'=>array(
        '#your-square'=>'Find Place',
        '#your-plots'=>'Your Path',
        '#species_1'=>'Species Page 1',
        '#species_2'=>'Species Page 2',
        '#species_3'=>'Species Page 3',
        '#species_other'=>'Other Species'
      )));
      $r .= '<div id="your-square">';
      $r .= self::get_hiddens($args, $auth);
      $r .= self::getFirstTabAdditionalContent($args, $auth);
      $r .= self::tab_your_square($args, $auth['read'], $topSampleAttrs);
      $r .= '</div>'; // your-square
      $r .= '<div id="your-plots">';
      $r .= self::tab_your_plots($args, $auth['read']);
      $r .= '</div>'; // your-plots (now called Your Paths, the old Wildflower form was Your Plots)
      $r .= '<div id="species_1">';
      $r .= self::tab_species($args, $auth, 0, 34);
      $r .= '</div>'; // species-1
      $r .= '<div id="species_2">';
      $r .= self::tab_species($args, $auth, 34, 34);
      $r .= '</div>'; // species-2
      $r .= '<div id="species_3">';
      $r .= self::tab_species($args, $auth, 68, 34);
      $r .= '</div>'; // species-3
      $r .= '<div id="species_other">';
      $r .= self::tab_other_species($args, $auth);
      $r .= '</div>'; // species-3
      $r .= '</div>'; // tabs
      $r .= '</form>';
    }
    return $r;
  }
  
  /**
   * Override function to add the report parameter for the ID of the custom attribute which holds the linked sample.
   * Depends upon a report existing that uses the parameter e.g. pss_sample_occurrence_samples
   */
  protected static function getSampleListGrid($args, $node, $auth, $attributes) {
    global $user;
    // User must be logged in before we can access their records.
    if ($user->uid===0) {
      // Return a login link that takes you back to this form when done.
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">login</a> to the website.');
    }

    // Get the Indicia User ID to filter on.
    if (function_exists('hostsite_get_user_field')) {
      $iUserId = hostsite_get_user_field('indicia_user_id');
      if (isset($iUserId)) $filter = array (
          'survey_id' => $args['survey_id'],
          's1AttrID' => $args['survey_1_attr'],
          'iUserID' => $iUserId);
    }
    
    // Return with error message if we cannot identify the user records
    if (!isset($filter)) {
      return lang::get('LANG_No_User_Id');
    }
    $r = data_entry_helper::report_grid(array(
      'id' => 'samples-grid',
      'dataSource' => $args['grid_report'],
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => self::getReportActions(),
      'itemsPerPage' =>(isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 10),
      'autoParamsForm' => true,
      'extraParams' => $filter
    ));
    $r .= '<form>';
    if (isset($args['multiple_occurrence_mode']) && $args['multiple_occurrence_mode']=='either') {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Single').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => array('new'))).'\'">';
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Grid').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => array('new&gridmode'))).'\'">';
    } else {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => array('new'=>''))).'\'">';
    }
    $r .= '</form>';
    return $r;
  }
  
  /**
   * Override function to add hidden attribute to store linked sample id
   * When adding a survey 1 record this is given the value 0
   * When adding a survey 2 record this is given the sample_id of the corresponding survey 1 record.
   * @param type $args
   * @param type $auth
   * @return string The hidden inputs that are added to the start of the form
   */
  protected static function getFirstTabAdditionalContent($args, $auth/*, &$attributes*/) {
    $r='';
    $linkAttr = 'smpAttr:' . $args['survey_1_attr'];
    if (array_key_exists('new', $_GET)) {
      if (array_key_exists('sample_id', $_GET)) {
        // Adding a survey 2 record
        $r .= '<input id="' . $linkAttr. '" type="hidden" name="' . $linkAttr. '" value="' . $_GET['sample_id'] . '"/>' . PHP_EOL;
      } else {
        // Adding a survey 1 record
        $r .= '<input id="' . $linkAttr. '" type="hidden" name="' . $linkAttr. '" value="0"/>' . PHP_EOL;
      }
    }
    return $r;
  }
  
  /**
   * Override function to include actions to add or edit the linked sample
   * Depends upon a report existing, e.g. pss_sample_occurrence_samples, that 
   * returns the fields done1 and done2 where
   * done1 is true if there is no second sample linked to the first and
   * done2 is true when there is a second sample.
   */
  protected static function getReportActions() {
    return array(array('display' => 'Actions', 
                       'actions' => array(array('caption' => lang::get('Edit Survey 1'), 
                                                'url'=>'{currentUrl}', 
                                                'urlParams' => array('edit' => '', 'sample_id' => '{sample_id1}')
                                               ),
                                          array('caption' => lang::get('Add Survey 2'), 
                                                'url'=>'{currentUrl}', 
                                                'urlParams' => array('new' => '', 'sample_id' => '{sample_id1}'),
                                                'visibility_field' => 'done1'
                                               ),
                                          array('caption' => lang::get('Edit Survey 2'), 
                                                'url'=>'{currentUrl}', 
                                                'urlParams' => array('edit' => '', 'sample_id' => '{sample_id2}'),
                                                'visibility_field' => 'done2'
                                               ),
    )));
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
    $attributeOpts = array(
      'valuetable' => 'sample_attribute_value'
      ,'attrtable' => 'sample_attribute'
      ,'key' => 'sample_id'
      ,'fieldprefix' => 'smpAttr'
      ,'extraParams' => $auth['read']
      ,'survey_id' => $args['survey_id']
    );
    if(isset($args['sample_method_id']))
      $attributeOpts['sample_method_id'] = $args['sample_method_id'];
    $attributes = data_entry_helper::getAttributes($attributeOpts, false);
    // Here is where we get the table of samples
    $r .= "<div id=\"sampleList\">".self::getSampleListGrid($args, $node, $auth, $attributes)."</div>";
    return $r;
  }
  
  /*
   * When the page loads we need to know what mode it is in e.g. add mode, edit mode or do we just display a list of existing records.
   * @param array $args iform parameters.
   * @param object $node node being shown.
   * @return integer Mode of the page as a number.
   */
  protected static function getMode($args, $node) {
    // Use mode MODE_GRID by default
    self::$mode = self::MODE_GRID;
    self::$loadedSampleId = null;
    self::$loadedOccurrenceId = null;
    //Editing so need Mode_Existing
    if ($_POST&&!is_null(data_entry_helper::$entity_to_load)) {
      self::$mode = self::MODE_EXISTING;
    }
    if (!empty($_GET['sample_id']) && $_GET['sample_id']!='{sample_id}'){
      self::$mode = self::MODE_EXISTING;
      self::$loadedSampleId = $_GET['sample_id'];
    }
    if (!empty($_GET['occurrence_id']) && $_GET['occurrence_id']!='{occurrence_id}') {
      self::$mode = self::MODE_EXISTING;
      self::$loadedOccurrenceId = $_GET['occurrence_id'];
    }
    //If new record then Mode_New
    if (self::$mode != self::MODE_EXISTING && array_key_exists('new', $_GET)){
      self::$mode = self::MODE_NEW;
    }
    //New record but data cloned from old record, so when saving, a new record is created using existing data.
    if (self::$mode == self::MODE_EXISTING && array_key_exists('new', $_GET)){
      self::$mode = self::MODE_CLONE;
    }  
    return self::$mode;
  }
  
  /*
   * Create the "Your Paths" tab. This used to be called "Your Plots", but as we are overriding a method, we use the old method name.
   * The is different to the original Wildflower Count version of the method as the Paths page removes some of the original sections of the page
   * @param array $args iform parameters.
   * @param object $auth.
   * @return string $r HTML string for Your Paths tab.
   */
  protected static function tab_your_plots($args, $auth) {
    $r = data_entry_helper::date_picker(array(
      'label' => 'Date of visit',
      'fieldname' => 'sample:date',
      'class' => 'ui-state-highlight'  
    ));
    $r .= self::output_habitats_block('Path', 'path', $auth, $args);
    $r .= data_entry_helper::wizard_buttons(array(
      'divId' => 'tabs',
      'page'  => 'middle'
    ));
    return $r;
  }
  
  /*
   * Output the habitats block on the Your Paths tab. Override the output_habitats_block from the Wildflowers form,
   * added support for cloning.
   */
  protected static function output_habitats_block($title, $prefix, $auth, $args) {
    global $indicia_templates;
    static $existingSubSamples;
    
    $r = '<fieldset class="ui-corner-all">
  <legend>'.$title.' habitats</legend>
  <table class="habitats">
  <thead><tr>
  <td>Habitat</td>
  <td>0%</td>
  <td>1-25%</td>
  <td>26-50%</td>
  <td>51-75%</td>
  <td>76-100%</td>
  <td>Further info. about habitat e.g management, recent changes</td>';  
    $r .= '</tr></thead>
  <tbody>';
    $coverageAttrs = data_entry_helper::getAttributes(array(
        'valuetable'=>'sample_attribute_value',
        'attrtable'=>'sample_attribute',
        'fieldprefix'=>"$prefix:smpAttr",
        'extraParams'=>$auth + array('inner_structure_block'=>'Habitats'),
        'survey_id'=>$args['survey_id'],
    ));
    $otherInfoAttrs = data_entry_helper::getAttributes(array(
        'valuetable'=>'sample_attribute_value',
        'attrtable'=>'sample_attribute',
        'fieldprefix'=>"$prefix:smpAttr",
        'extraParams'=>$auth + array('inner_structure_block'=>'HabitatsOtherInfo'),
        'survey_id'=>$args['survey_id'],
    ));
    if (isset($_GET['sample_id'])) {
      // use a static here to load all the subsample data in one hit      
      if (!isset($existingSubSamples)) {
        $attrIds = array();
        foreach ($coverageAttrs as $attr)
          $attrIds[] = $attr['attributeId'];
        foreach ($otherInfoAttrs as $attr)
          $attrIds[] = $attr['attributeId'];
        $existingSubSamples = data_entry_helper::get_population_data(array(
          'nocache'=>true,
          'report'=>'library/samples/samples_list_for_parent_sample',
          'extraParams'=>$auth + array('sample_id'=>$_GET['sample_id'], 'date_from'=>'', 'date_to'=>'', 'sample_method_id'=>'',
              'smpattrs'=>implode(',', $attrIds))
        ));
      }
      /// find the set of sub-sample data that matches the current block type
      foreach ($existingSubSamples as $existingSubSample) {
        if ($existingSubSample['location_name']===$prefix) {
          $thisSubSample=$existingSubSample;
          break;
        }
      }
      
      // apply the defaults we just loaded to the list of attributes
      if (isset($thisSubSample)) {
        foreach ($coverageAttrs as $idx=>$attr) {
          if (isset($thisSubSample['attr_id_sample_'.$attr['attributeId']])) {
            $coverageAttrs[$idx]['fieldname'] .= ':'.$thisSubSample['attr_id_sample_'.$attr['attributeId']];
            $coverageAttrs[$idx]['default'] = $thisSubSample['attr_sample_'.$attr['attributeId']];
          }
        }
        foreach ($otherInfoAttrs as $idx=>$attr) {
          if (isset($thisSubSample['attr_id_sample_'.$attr['attributeId']])) {
            $otherInfoAttrs[$idx]['fieldname'] .= ':'.$thisSubSample['attr_id_sample_'.$attr['attributeId']];
            $otherInfoAttrs[$idx]['default'] = $thisSubSample['attr_sample_'.$attr['attributeId']];
          }
        }
      }
    }
    $coverageAttrs=array_values($coverageAttrs);
    $otherInfoAttrs=array_values($otherInfoAttrs);
    // put radio buttons inside table cells, without labels as these are in the header
    $labelTemplate = $indicia_templates['label'];
    $itemTemplate = $indicia_templates['check_or_radio_group_item'];
    $template = $indicia_templates['check_or_radio_group'];
    $indicia_templates['label'] = '';
    $indicia_templates['check_or_radio_group_item'] = '<td><input type="{type}" name="{fieldname}" id="{itemId}" value="{value}"{class}{checked} {disabled}/></td>';
    $indicia_templates['check_or_radio_group']='{items}';
    //We need to remove the attribute_value ids from the fieldnames if cloning data onto a new record, otherwise it will save over the 
    //top of the existing record.
    if (self::$mode ===  self::MODE_CLONE) {
      self::cloneEntity($args, $auth, $coverageAttrs); 
      self::cloneEntity($args, $auth, $otherInfoAttrs); 
    }    
    foreach($coverageAttrs as $idx => $attr) {
      $r .= '<tr><td><label>'.$attr['caption'].'</label></td>';
        $r .= data_entry_helper::outputAttribute($attr, array('extraParams'=>$auth, 'suffixTemplate'=>'nosuffix'));
      $r .= '<td>';
      if (isset($otherInfoAttrs[$idx])) {
        $r .= data_entry_helper::outputAttribute($otherInfoAttrs[$idx], array('extraParams'=>$auth, 'suffixTemplate'=>'nosuffix'));
      }
      $r .= '</td></tr>';
    }
    // reset templates
    $indicia_templates['check_or_radio_group_item'] = $itemTemplate;
    $indicia_templates['check_or_radio_group'] = $template;
    $indicia_templates['label'] = $labelTemplate;
    $r .= '</tbody></table>';
    if (isset($thisSubSample)) {
      $r .= '<input type="hidden" name="'.$prefix.'_sample_id" value="'.$thisSubSample['sample_id'].'" />';
    }
    $r .= '</fieldset>';
    return $r;
  }
  
  /*
   * This method clones existing sample data onto a new record. 
   * This is used for the "Survey 2" functionality. 
   * Note that this is different to the dynamic_sample_occurrence clone entity as we are not cloning any occurrence data
   * in this case, have also removed the code that handled multi-value fields as we don't need it for this form.
   * @param array $args iform parameters.
   * @param object $auth.
   */
  protected static function cloneEntity($args, $auth, &$attributes) {
    // First modify the sample attribute information in the $attributes array.
    // Set the sample attribute fieldnames as for a new record
    foreach($attributes as $attributeKey => $attributeValue){
      // Set the attribute fieldname to the attribute id
      $attributes[$attributeKey]['fieldname'] = $attributeValue['id'];
    }
    // Unset the sample and occurrence id from entitiy_to_load as for a new record.
    if (isset(data_entry_helper::$entity_to_load['sample:id']))
      unset(data_entry_helper::$entity_to_load['sample:id']);
    if (isset(data_entry_helper::$entity_to_load['occurrence:id']))
      unset(data_entry_helper::$entity_to_load['occurrence:id']); 
  }
}
