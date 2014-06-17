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
 * NB has Drupal specific code.
 * 
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('dynamic_sample_occurrence.php');

class iform_npms_sample_occurrence extends iform_dynamic_sample_occurrence {
  public static function get_parameters() {    
    return array_merge(
      parent::get_parameters(),
      array(
        array(
          'name'=>'survey_1_attr',
          'caption'=>'Survey 1 attribute ID',
          'description'=>'The sample attribute ID that will store the ID of survey 1.',
          'type'=>'string',
          'groupd'=>'Other IForm Parameters',
          'required'=>true
        ),
      )
    ); 
  }

  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_npms_sample_occurrence_definition() {
    return array(
      'title'=>'Sample-occurrence entry form for NPMS',
      'category' => 'Forms for specific surveying methods',
      'description'=>'A sample and occurrence entry form with an optional grid listing the user\'s samples so forms can be ' .
        'reloaded for editing. Can be used for entry of a single occurrence, ticking species off a checklist, or entering ' .
        'species into a grid. The attributes on the form are dynamically generated from the survey setup on the Indicia Warehouse.'.
        'With customisations for the Plant Surveillance Scheme.'
    );
  }

  protected static function get_form_html($args, $auth, $attributes) {
    //remove default validation mode of 'message' as species grid goes spazzy
    data_entry_helper::$validation_mode = array('colour');
    return parent::get_form_html($args, $auth, $attributes);
  }

  /**
   * Override function to output species name for checklist
   */
  protected static function build_grid_taxon_label_function($args, $options) {
    global $indicia_templates;
    // This bit optionally adds '- common' or '- latin' depending on what was being searched
    if (isset($args['species_include_both_names']) && $args['species_include_both_names']) {
      $php = '$r = "<span class=\"scCommon\">{common}</span> <span class=\"scTaxon\"><em>{taxon}</em></span>";' . "\n";
    } else {
      $php = '$r = "<em>{taxon}</em>";' . "\n";
    }
    // this bit optionally adds the taxon group
    if (isset($args['species_include_taxon_group']) && $args['species_include_taxon_group']) {
      $php .= '$r .= "<br/><strong>{taxon_group}</strong>";' . "\n";
    }
    if (isset($options['useCommonName'])&&$options['useCommonName']==true) 
      $php = '$r = "<span class=\"scCommon\">{common}</span>";' . "\n";
    // Close the function
    $php .= 'return $r;' . "\n";
    // Set it into the indicia templates
    $indicia_templates['taxon_label'] = $php;
  }
  
  /**
   * Override function to add hidden attribute to store linked sample id
   * When adding a survey 1 record this is given the value 0
   * When adding a survey 2 record this is given the sample_id of the corresponding survey 1 record.
   * @param type $args
   * @param type $auth
   * @param type $attributes
   * @return string The hidden inputs that are added to the start of the form
   */
  protected static function getFirstTabAdditionalContent($args, $auth, &$attributes) {
    $r = parent::getFirstTabAdditionalContent($args, $auth, $attributes);    
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
   * Depends upon a report existing, e.g. npms_sample_occurrence_samples, that 
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
   * Override function to add the report parameter for the ID of the custom attribute which holds the linked sample.
   * Depends upon a report existing that uses the parameter e.g. npms_sample_occurrence_samples
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
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Single').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => array('new'))).'\'">';
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Grid').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => array('new&gridmode'))).'\'">';
    } else {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => array('new'=>''))).'\'">';
    }
    $r .= '</form>';
    return $r;
  }
  
  /* Overrides function in class iform_dynamic.
   * 
   * This function removes ID information from the entity_to_load, fooling the 
   * system in to building a form for a new record with default values from the entity_to_load.
   * Note that for NPMS no occurrences are loaded
   */
  protected static function cloneEntity($args, $auth, &$attributes) {
    // First modify the sample attribute information in the $attributes array.
    // Set the sample attribute fieldnames as for a new record
    foreach($attributes as $attributeKey => $attributeValue){
      if ($attributeValue['multi_value'] == 't') {
         // Set the attribute fieldname to the attribute id plus brackets for multi-value attributes
        $attributes[$attributeKey]['fieldname'] = $attributeValue['id'] . '[]';
        foreach($attributeValue['default'] as $defaultKey => $defaultValue) {
          $attributes[$attributeKey]['default'][$defaultKey]['fieldname']=null;   
        }
      } else {
        // Set the attribute fieldname to the attribute id for single values
        $attributes[$attributeKey]['fieldname'] = $attributeValue['id'];
      }
    }
    // Unset the sample and occurrence id from entitiy_to_load as for a new record.
    if (isset(data_entry_helper::$entity_to_load['sample:id']))
      unset(data_entry_helper::$entity_to_load['sample:id']);
    if (isset(data_entry_helper::$entity_to_load['occurrence:id']))
      unset(data_entry_helper::$entity_to_load['occurrence:id']);   
  }
}

?>
