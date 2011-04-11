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

/**
 * Prebuilt Indicia data entry form that presents taxon search box, date control and map picker
 * controls. The survey data is posted into must be specified as a form parameter.
 * 
 * @package	Client
 * @subpackage PrebuiltForms
 */
class iform_basic_2 {

  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_basic_2_definition() {
    return array(
      'title'=>'Basic 2 - species, date, place',
      'category' => 'Training/Testing forms',      
      'description'=>'A second very simple form designed to illustrate the prebuilt form development and setup process.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
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
          'valueField'=>'id'
        ),
      array(
        'fieldname'=>'taxon_list_id',
        'label'=>'Species List',
        'helpText'=>'The species list that species can be selected from.',
        'type'=>'select',
        'table'=>'taxon_list',
        'valueField'=>'id',
        'captionField'=>'title'
      ),
    );
  }

  /**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args) {
    $r = "<form method=\"post\">\n";
    // Get authorisation tokens to update and read from the Warehouse.
    $r .= data_entry_helper::get_auth(1, 'password');
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    $r .= '<input type="hidden"  name="website_id" value="'.$args['website_id'].'" />'."\n";
    $r .= '<input type="hidden" name="sample:survey_id" value="'.$args['survey_id'].'" />'."\n";
    $r .= "<input type=\"hidden\" id=\"record_status\" value=\"C\" />\n";
    $r .= data_entry_helper::autocomplete(array(
        'label'=>'Species',
        'fieldname'=>'occurrence:taxa_taxon_list_id',
        'table'=>'taxa_taxon_list',
        'captionField'=>'taxon',
        'valueField'=>'id',
        'extraParams'=>$readAuth + array('taxon_list_id' => $args['taxon_list_id'])
    ));
    $r .= data_entry_helper::date_picker(array(
        'label'=>'Date',
        'fieldname'=>'sample:date'
    ));
    $r .= data_entry_helper::map();
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
    return data_entry_helper::build_sample_occurrence_submission($values);     
  } 
  
}