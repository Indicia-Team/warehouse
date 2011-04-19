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
 * @package	Client
 * @subpackage PrebuiltForms
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

require_once('includes/form_generation.php');

/**
 * Prebuilt Indicia data entry form that presents taxon search box, date control, map picker,
 * survey selector and comment entry controls.
 * 
 * @package	Client
 * @subpackage PrebuiltForms
 */
class iform_change_identification {

  /**
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_change_identification_definition() {
    return array(
      'title'=>'Change identification of a record',
      'category' => 'Utilities',
      'description'=>'A form allowing updating of the identification of an existing record. The form should be accessed '.
          'by calling the url with a parameter occurrence_id set to the ID of the occurrence being changed. Displays a summary '.
          'of the record with a list of the sample and occurrence attributes and a control for changing the identification. '
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
    return array(      
      array(
      	'name'=>'species_ctrl',
        'caption'=>'Species Control Type',
        'description'=>'The type of control that will be available to select a species.',
        'type'=>'select',
        'options' => array(
          'autocomplete' => 'Autocomplete',
          'select' => 'Select',
          'listbox' => 'List box',
          'radio_group' => 'Radio group',
          'species_checklist' => 'Checkbox grid'         
        )
      ),
      array(
      	'fieldname'=>'list_id',
        'label'=>'Species List',
        'helpText'=>'The species list that species can be selected from.',
        'type'=>'select',
        'table'=>'taxon_list',
        'valueField'=>'id',
        'captionField'=>'title'
      ),
      array(
      	'name'=>'preferred',
        'caption'=>'Preferred species only?',
        'description'=>'Should the selection of species be limited to preferred names only?',
        'type'=>'boolean',
        'required'=>false
      )
    );
  }
  
  /**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args) {
    if (empty($_GET['occurrence_id'])) {
      return 'This form requires an occurrence_id parameter in the URL.';
    }
    $r = "<form method=\"post\">\n";
    // Get authorisation tokens to update and read from the Warehouse.
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    data_entry_helper::load_existing_record($auth['read'], 'occurrence', $_GET['occurrence_id']);
    data_entry_helper::load_existing_record($auth['read'], 'sample', data_entry_helper::$entity_to_load['occurrence:sample_id']);
    $r .= $auth['write'];    
    $r .= "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= "<input type=\"hidden\" id=\"occurrence:id\" name=\"occurrence:id\" value=\"".$_GET['occurrence_id']."\" />\n";
    $r .= "<input type=\"hidden\" id=\"occurrence:sample_id\" name=\"occurrence:sample_id\" value=\"".data_entry_helper::$entity_to_load['sample:id']."\" />\n";
    $r .= "<div id=\"controls\">\n";
    $r .= "<table>\n";
    $r .= "<tr><td><strong>Date</strong></td><td>".data_entry_helper::$entity_to_load['sample:date']."</td></tr>\n";
    $r .= "<tr><td><strong>Spatial Reference</strong></td><td>".data_entry_helper::$entity_to_load['sample:entered_sref']."</td></tr>\n";
    $siteLabels = array();
    if (!empty(data_entry_helper::$entity_to_load['sample:location'])) $siteLabels[] = data_entry_helper::$entity_to_load['sample:location'];
    if (!empty(data_entry_helper::$entity_to_load['sample:location_name'])) $siteLabels[] = data_entry_helper::$entity_to_load['sample:location_name'];
    $r .= "<tr><td><strong>Site</strong></td><td>".implode(' | ', $siteLabels)."</td></tr>\n";
    $smpAttrs = data_entry_helper::getAttributes(array(
        'id' => data_entry_helper::$entity_to_load['sample:id'],
        'valuetable'=>'sample_attribute_value',
        'attrtable'=>'sample_attribute',
        'key'=>'sample_id',
        'extraParams'=>$auth['read'],
        'survey_id'=>data_entry_helper::$entity_to_load['occurrence:survey_id']
    ));
    $occAttrs = data_entry_helper::getAttributes(array(
        'id' => $_GET['occurrence_id'],
        'valuetable'=>'occurrence_attribute_value',
        'attrtable'=>'occurrence_attribute',
        'key'=>'occurrence_id',
        'extraParams'=>$auth['read'],
        'survey_id'=>data_entry_helper::$entity_to_load['occurrence:survey_id']
    ));
    $attributes = array_merge($smpAttrs, $occAttrs);
    foreach($attributes as $attr) {
      
      $r .= "<tr><td><strong>".$attr['caption']."</strong></td><td>".$attr['displayValue']."</td></tr>\n";
    }    
    $extraParams = $auth['read'] + array('taxon_list_id' => $args['list_id']);
    if ($args['preferred']) {
      $extraParams += array('preferred' => 't');
    }
    $species_list_args=array(
        'itemTemplate' => 'select_species',
        'fieldname'=>'occurrence:taxa_taxon_list_id',
        'table'=>'taxa_taxon_list',
        'captionField'=>'taxon',
        'valueField'=>'id',
        'columns'=>2,
        'extraParams'=>$extraParams
    );    
    // Dynamically generate the species selection control required.        
    $r .= '<tr/><td><strong>Species</strong></td><td>'.call_user_func(array('data_entry_helper', $args['species_ctrl']), $species_list_args)."</td></tr>\n";
    $r .= "</table>\n";
    $r .= "</div>\n";
    $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"Save\" />\n";    
    $r .= "</form>";
        
    return $r;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    return submission_builder::build_submission($values, array('model'=>'occurrence'));
  }  
  
}