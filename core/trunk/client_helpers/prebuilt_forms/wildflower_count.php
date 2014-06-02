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
 * Prebuilt form for the Plantlife Wildflower Count
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_wildflower_count {
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_wildflower_count_definition() {
    return array(
      'title'=>'Wildflower Count',
      'category' => 'Forms for specific surveying methods',
      'description'=>'A form for inputting data against the Plantlife Wildflower Count survey.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires
   */
  public static function get_parameters() {   
    return array(
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
    // variables to tracks which parts of the plots grid are not completed, so we can display a correct message
    $someGridRefsMissing=false;
    $someHabitatsMissing=false;
    $someSqTypesMissing=false;
    data_entry_helper::$validation_mode = array('colour','hint','message');
    if (isset($_GET['sample_id'])) {
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
    global $indicia_templates;
    $indicia_templates['starredSuffix']="*<br/>\n";
    $indicia_templates['validation_message'] = "<span class=\"ui-state-error-text\">{error}</span>\n";
    data_entry_helper::enable_validation('entry-form');
    $r .= '<form method="post" action="" id="entry-form">';
    $r .= '<div id="tabs">';
    data_entry_helper::enable_tabs(array('divId'=>'tabs','navButtons'=>true)); 
    $r .= data_entry_helper::tab_header(array('tabs'=>array(
      '#your-square'=>'Find Place',
      '#your-plots'=>'Your Plots',
      '#species_1'=>'Species Page 1',
      '#species_2'=>'Species Page 2',
      '#species_3'=>'Species Page 3',
      '#species_other'=>'Other Species'
    )));
    $r .= '<div id="your-square">';
    $r .= self::get_hiddens($args, $auth);
    $r .= self::tab_your_square($args, $auth['read'], $topSampleAttrs);
    $r .= '</div>'; // your-square
    $r .= '<div id="your-plots">';
    $r .= self::tab_your_plots($args, $auth['read']);
    $r .= '</div>'; // your-plots
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
    return $r;
  }
  
  private static function load_top_sample_attrs($auth, $args, $sampleId=null) {
    $attrOpts = array(     
       'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
    );
    if ($sampleId)
      $attrOpts['id']=$sampleId;
    $attributes = data_entry_helper::getAttributes($attrOpts, false);
    $sorted = array();
    // sort attributes by ID so we can find them later
    foreach($attributes as $attr) {
      $sorted[$attr['id']] = $attr;
    }
    return $sorted;
  }
  
  private static function get_hiddens($args, $auth) {
    $r = $auth['write'];
    $r .= '<input type="hidden" name="website_id" value="'.$args['website_id'].'"/>';
    $r .= '<input type="hidden" name="survey_id" value="'.$args['survey_id'].'"/>';
    if (isset(data_entry_helper::$entity_to_load['sample:id'])) 
      $r .= '<input type="hidden" id="sample:id" name="sample:id" value="' . data_entry_helper::$entity_to_load['sample:id'] . '" />';
    return $r;
  }
  
  private static function tab_your_square($args, $auth, $attrs) {
    $r = '<fieldset class="ui-corner-all" >';
    $r .= '<legend>Place on map</legend>';
    $r .= '<div class="two columns"><div class="column">';
    $r .=  data_entry_helper::georeference_lookup(array(
        'label'=>'Enter the nearest place name',
        'labelClass'=>'control-width-5',
        'helpText' => 'Enter the name of a nearby town or village then click Search to quickly find the correct region on the map. '.
            'Or if you know the grid reference, type it into the following box.'
    ));
    $r .= data_entry_helper::sref_and_system(array(
        'label'=>'Your 1km grid reference',
        'labelClass'=>'control-width-5',
        'fieldname'=>'sample:entered_sref',
        'systems'=>array('OSGB'=>'British National Grid', 'OSIE'=>'Irish Grid'),
        'class'=>'ui-state-highlight'
    ));
    $sq_error=data_entry_helper::check_errors('smpAttr:'.$args['attr_surveyed_square']);
    $r .= '<fieldset class="ui-state-highlight ui-corner-all'.($sq_error ? ' ui-state-error' : '').'">';
    $r .= "<legend>Please also select one of the 3 choices below</legend>\n";
    if ($sq_error) {
      $r .= "$sq_error<br/>\n";
    }
    // manual output of radio buttons since data_entry_helper::radio_group does not support splicing in the textarea.
    $whichSqrAttr = $attrs['smpAttr:'.$args['attr_surveyed_square']];
    $reasonAttr = $attrs['smpAttr:'.$args['attr_surveyed_other_square_reason']];
    $fieldname = $whichSqrAttr['fieldname'];
    $value = $whichSqrAttr['default'];
    $r .= '<label class="auto">';
    $r .= '<input ';
    if ($value==$args['term_surveyed_given_square'])
      $r .= 'checked="checked" ';
    $r .= 'type="radio" id="attr_surveyed_given_square" value="'.$args['term_surveyed_given_square'].
        '" name="'.$whichSqrAttr['fieldname'].'"/> ';
    $r .= "I have surveyed the random square that I was given</label><br/>\n";
    
    
    $r .= '<label class="auto">';
    $r .= '<input ';
    if ($value==$args['term_surveyed_other_square'])
      $r .= 'checked="checked" ';
    $r .= 'type="radio" id="attr_surveyed_other_square" value="'.$args['term_surveyed_other_square'].
        '" name="'.$whichSqrAttr['fieldname'].'"/> ';
    $r .= "I have not surveyed the random square because</label><br/>\n";
    $r .= data_entry_helper::textarea(array(
      'fieldname'=>$reasonAttr['fieldname'],
      'class'=>'indented reason',
      'default'=>$reasonAttr['default'],
      'cols'=>50
    ));
    $r .= "\n<label class=\"auto\">";
    $r .= '<input ';
    if ($value==$args['term_surveyed_same_square'])
      $r .= 'checked="checked" ';
    $r .= 'type="radio" id="attr_surveyed_same_square" value="'.$args['term_surveyed_same_square'].
        '" name="'.$whichSqrAttr['fieldname'].'"/> ';
    $r .= "I have resurveyed the same square as before</label>\n";
    $r .= '</fieldset>';
    $r .= '</div><div class="column">';
    $r .= data_entry_helper::map_panel(array(
        'presetLayers' => array('google_hybrid'),
        'readAuth' => $auth,
        'class'=>'ui-widget-content',
        'clickedSrefPrecisionMin'=>4, // fix to 1km
        'clickedSrefPrecisionMax'=>4, // fix to 1km,
        'initial_lat'=>54,
        'initial_long'=>-1,
        'initial_zoom'=>5,
        'width'=>'100%',
        'tabDiv'=>'your-square'
    ));
    $r .= "</div></div></fieldset>\n";
    
    $r .= data_entry_helper::wizard_buttons(array(
      'divId' => 'tabs',
      'page'  => 'middle'
    ));
    return $r;
  }
  
  private static function tab_your_plots($args, $auth) {
    $r = data_entry_helper::date_picker(array(
      'label' => 'Date of visit',
      'fieldname' => 'sample:date',
      'class' => 'ui-state-highlight'  
    ));
    $r .= self::output_habitats_block('Path', 'path', $auth, $args);
    $r .= self::output_habitats_block('Square plot', 'square', $auth, $args);
    $r .= self::output_habitats_block('Linear plot', 'linear', $auth, $args);
    $r .= data_entry_helper::wizard_buttons(array(
      'divId' => 'tabs',
      'page'  => 'middle'
    ));
    return $r;
  }
  
  private static function output_habitats_block($title, $prefix, $auth, $args) {
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
  
  private static function tab_species($args, $auth, $offset, $limit) {
    $r = '<p>Please select the percentage cover of each species that is present in each plot from the list below.</p>';
    global $indicia_templates;
    $indicia_templates['taxon_label']='<div class="biota nobreak"><span class="vernacular">{common}</span>'.
    		'<br/><span class="sci binomial"><em>{taxon}</em></span> {authority}</div>';
    $r .= data_entry_helper::species_checklist(array(
        'id'=>"species-$offset",
        'label'=>'Species',
        'listId'=>$args['list_id'],
        'columns'=>2,
        'rowInclusionCheck'=>'hasData',
        'class'=>'checklist',
        'survey_id' => $args['survey_id'],
        'extraParams'=>$auth['read'] + array('taxon_list_id' => $args['list_id'], 'limit'=>$limit, 
            'offset'=>$offset, 'orderby'=>'taxonomic_sort_order', 'sortdir'=>'ASC', 'view'=>'detail'),
        'occAttrClasses'=>array('coverage'),
        'speciesNameFilterMode'=>'preferred',
        // prevent multiple hits to the db - the first grid can load all the species data
        'useLoadedExistingRecords' => $offset>0
    )); 
    $r .= data_entry_helper::wizard_buttons(array(
      'divId' => 'tabs',
      'page'  => 'middle'
    ));
    
    return $r;
  }
  
  private static function tab_other_species($args, $auth) {
    $r = '';
    $indicia_templates['taxon_label']='<div class="biota nobreak"><span class="vernacular">{common}</span>'.
    		'<br/><span class="sci binomial"><em>{taxon}</em></span> {authority}</div>';
    $extraParams = $auth['read'];
    $species_ctrl_opts = array(
        'id'=>"species-other",
        'label'=>'Species',
        'lookupListId'=>$args['other_list_id'],
        'cacheLookup' => isset($args['cache_lookup']) && $args['cache_lookup'],
        'PHPtaxonLabel'=>true,
        'class'=>'checklist',
        'survey_id' => $args['survey_id'],
        'extraParams'=>$extraParams,
        'occAttrClasses'=>array('coverage'),
        // don't reload species from the 3 main input grids
        'reloadExtraParams'=>array('taxon_list_id'=>$args['other_list_id']),
        'speciesNameFilterMode' => 'excludeSynonyms',
        'helpText'=>'Please provide details of any additional species you would like to record that you observed during your wildflower count. '.
            'Type the species name into the box on the left of the grid then select the correct name from the drop-down list of suggestions. '.
            'Make sure you input the information about whether the species was recorded on the path or linear/square transect.'
    );
    if (!empty($args['taxon_filter_field']) && !empty($args['taxon_filter'])) {
      $filterLines = helper_base::explode_lines($args['taxon_filter']);
      $species_ctrl_opts['taxonFilterField']=$args['taxon_filter_field'];
      $species_ctrl_opts['taxonFilter']=$filterLines;
    }
    
    self::build_grid_taxon_label_function($args);
    self::build_grid_autocomplete_function($args);
    $r .= data_entry_helper::species_checklist($species_ctrl_opts); 
    $r .= '<p class="highlight">'.lang::get('Please review all tabs of the form before submitting the survey.').'</p>';
    $r .= data_entry_helper::wizard_buttons(array(
      'divId' => 'tabs',
      'page'  => 'last',
      'captionSave' => 'Submit'
    ));
    return $r;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    $subSampleIds=array('path', 'square', 'linear');    
    $submission = data_entry_helper::build_sample_occurrences_list_submission($values, true);
    // Now because it is not standard, we need to attach the sub-samples for each plot.
    // First, extract the attributes for each subsample into their own arrays.    
    $subSamples=array();
    foreach ($values as $key=>$value) {
      if (strpos($key,':')) {
        $parts = explode(':', $key);        
        if (in_array($parts[0], $subSampleIds)) {          
          $subSamples[$parts[0]][substr($key, strlen($parts[0])+1)] = $value;
        }
      }
    }
    // Now wrap each of the subsample arrays and attach them to the main submission.
    foreach ($subSamples as $prefix => $s) {
      if (isset($values[$prefix.'_sample_id']))
        $s['sample:id']=$values[$prefix.'_sample_id'];
      // specify some default values
      $s['sample:entered_sref_system']=$values['sample:entered_sref_system'];
      $s['sample:entered_sref']=$values['sample:entered_sref'];
      $s['sample:geom']=$values['sample:geom'];
      $s['sample:date']=$values['sample:date'];
      $s['sample:survey_id']=$values['survey_id'];
      $s['location_name']=$prefix;
      $wrapped = submission_builder::wrap_with_attrs($s, 'sample', $prefix);
      $submission['subModels'][]=array('fkId'=>'parent_id', 'model'=>$wrapped);
    }
    return($submission);
  }
  
  public static function get_validation_errors($values, $args) {
    $errors = array();
    if (!self::array_attr_exists('smpAttr:'.$args['attr_surveyed_square'], $values)) {
      $errors['smpAttr:'.$args['attr_surveyed_square']]="Please tell us which square you surveyed.";
    }  
    // Ensure spatial reference is a 1km reference
    if (strlen($values['sample:entered_sref'])===0) {
      $errors['sample:entered_sref']="Please specify your 1km grid reference";
    } elseif (strlen($values['sample:entered_sref'])<5 || strlen($values['sample:entered_sref'])>6) {
      // not a 5 character Irish 1km grid or 6 character GB 1km grid
      $errors['sample:entered_sref']="The entered grid reference ".$values['sample:entered_sref']." is not a 1km square. Please enter a 1km grid square.";
    }
    return $errors;
  }
  
  /**
   * Version of array_key_exists which looks for attribute data in the posted form array, 
   * and tolerates attribute value IDs which are different per record.
   * @param type $needle Attr to find, e.g. smpAttr:10
   * @param type $haystack Array to search, e.g. $_POST
   * @return boolean True if the attribute is in the array
   */
  private function array_attr_exists($needle, $haystack) {
    // quick match on new attributes (no value ID yet)
    if (array_key_exists($needle, $haystack))
      return true;
    else {
      // now search for the attribute followed by colon and a value id.
      foreach($haystack as $key=>$value) {
        if (substr($key, 0, strlen($needle)+1)==="$needle:")
          return true;
      }
    }
    return false;
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
   * Build a PHP function  to format the species added to the grid according to the form parameters
   * autocomplete_include_both_names and autocomplete_include_taxon_group.
   */
  protected static function build_grid_autocomplete_function($args) {
    global $indicia_templates;  
    // always include the searched name. In this JavaScript we need to behave slightly differently
    // if using the cached as opposed to the standard versions of taxa_taxon_list.
    $db = data_entry_helper::get_species_lookup_db_definition(isset($args['cache_lookup']) && $args['cache_lookup']);
    // get local vars for the array
    extract($db);

    $fn = "function(item) { \n".
        "  var r;\n".
        "  if (item.$colLanguage.toLowerCase()==='$valLatinLanguage') {\n".
        "    r = '<em>'+item.$colTaxon+'</em>';\n".
        "  } else {\n".
        "    r = item.$colTaxon;\n".
        "  }\n";
    // This bit optionally adds '- common' or '- latin' depending on what was being searched
    if (isset($args['species_include_both_names']) && $args['species_include_both_names']) {
      $fn .= "  if (item.preferred='t' && item.$colCommon!=item.$colTaxon && item.$colCommon) {\n".
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
  

}
