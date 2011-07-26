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

/**
 * 
 * 
 * @package Client
 * @subpackage PrebuiltForms
 * Form for adding or editing the site details on a transect which contains a number of sections.
 */
class iform_transect_with_sections_editor {
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_transect_with_sections_editor_definition() {
    return array(
      'title'=>'Transect with sections editor',
      'category' => 'Transects',
      'description'=>'Form for adding or editing the site details on a transect which has a number of sub-sections.'
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
            'name' => 'maxSectionCount',
            'label' => lang::get('Max. Section Count'),
            'type' => 'text_input',
            'description' => lang::get('The maximum number of sections a user is allowed to create for a transect site.'),
            'group' => 'UKBMS Settings'
          ), array(
            'name'=>'survey_id',
            'caption'=>'Survey',
            'description'=>'The survey that data will be posted into.',
            'type'=>'select',
            'table'=>'survey',
            'captionField'=>'title',
            'valueField'=>'id',
            'siteSpecific'=>true
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
    require_once drupal_get_path('module', 'iform').'/client_helpers/map_helper.php';
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    $locationId = isset($_GET['id']) ? $_GET['id'] : null;
    if ($locationId) {
      data_entry_helper::load_existing_record($readAuth, 'location', $locationId);
      $attributes = data_entry_helper::getAttributes(array(
          'id' => data_entry_helper::$entity_to_load['location:id'],
          'valuetable'=>'location_attribute_value',
          'attrtable'=>'location_attribute',
          'key'=>'location_id',
          'fieldprefix'=>'locAttr',
          'extraParams'=>$readAuth,
          'survey_id'=>$args['survey_id']
      ));
    }
    $r = '<form method="post">';
    $r .= '<fieldset class="left"><legend>'.lang::get('Transect Details').'</legend>';
    $r .= data_entry_helper::text_input(array(
      'fieldname' => 'location:name',
      'label' => lang::get('Transect Name'),
      'class' => 'control-width-4'
    ));
    $r .= data_entry_helper::sref_textbox(array(
      'fieldname' => 'location:centroid_sref',
      'label' => 'Grid Ref.'
    ));
    $sectionArr = array('' => htmlspecialchars(lang::get('<please select>')));
    for ($i=1; $i<=$args['maxSectionCount']; $i++)
      $sectionArr[$i] = $i;
    $r .= data_entry_helper::select(array(
      'fieldname' => 'locAttr:'.$sectionCountAttrId,
      'id' => 'section-count',
      'label' => lang::get('Number of transect sections'),
      'lookupValues' => $sectionArr
    ));
    // setup the map options
    $options = iform_map_get_map_options($args, $readAuth);
    $options['toolbarDiv'] = 'top';
    if ($locationId) {
      // set the list of available sections to draw lines for
      $sectionArr = array_chunk($sectionArr, data_entry_helper::$entity_to_load['locAttr:'.$sectionCountAttrId]+1);
      $sectionArr = $sectionArr[0];
    }
    else
      $sectionArr = array();
    $options['toolbarPrefix'] = data_entry_helper::select(array(
      'id' => 'current-section',
      'label' => lang::get('Current section'),
      'lookupValues' => $sectionArr,
      'suffixTemplate' => 'nosuffix'
    ));
    $olOptions = iform_map_get_ol_options($args);
    $r .= '<input type="submit" value="'.lang::get('Save').'" class="ui-state-default ui-corner-all" />';
    $r .= '</fieldset>';
    $r .= '<div class="right" style="border: solid silver 1px">';
    $r .= map_helper::map_panel($options, $olOptions);
    $r .= '</div>';
    $r .='</form>';
    data_entry_helper::link_default_stylesheet();
    return $r;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * For example, the following represents a submission structure for a simple
   * sample and 1 occurrence submission
   * return data_entry_helper::build_sample_occurrence_submission($values);
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   * @todo: Implement this method
   */
  public static function get_submission($values, $args) {
        
  }  
  
  
  
}
