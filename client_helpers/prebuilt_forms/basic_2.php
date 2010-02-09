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
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {    
    return array(
      array(
      	'name'=>'survey_id',
        'caption'=>'Survey ID',
        'description'=>'The ID of the survey that data will be posted into.',
        'type'=>'int'
      )
    );
  }
  
  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'Basic 2 - species, date, place';  
  }
  
  /**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form() {
    $r = "<form method=\"post\">\n";
    // Get authorisation tokens to update and read from the Warehouse.
    $r .= data_entry_helper::get_auth(1, 'password');
    $readAuth = data_entry_helper::get_read_auth(1, 'password');
    $r .= "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"1\" />\n";
    $r .= "<input type=\"hidden\" id=\"record_status\" name=\"record_status\" value=\"C\" />\n";
    $r .= data_entry_helper::autocomplete(array(
        'label'=>'Species',
        'fieldname'=>'occurrence:taxa_taxon_list_id',
        'table'=>'taxa_taxon_list',
        'captionField'=>'taxon',
        'valueField'=>'id',
        'extraParams'=>$readAuth + array('taxon_list_id' => 1)
    ));
    $r .= data_entry_helper::date_picker(array(
        'label'=>'Date',
        'fieldname'=>'sample:date'
    ));
    $r .= data_entry_helper::map();
    $r .= "<input type=\"hidden\" name=\"sample:survey_id\" value=\"1\" />\n";
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