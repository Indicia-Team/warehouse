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
 * Prebuilt Indicia data entry form based on the Orthoptera and Allied Insects site 
 * survey recording form.
 * 
 * @package	Client
 * @subpackage PrebuiltForms
 */
class iform_site_survey_recording_form {
  
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
      ),
      array(
      	'name'=>'list_id_1',
        'caption'=>'Species List ID 1',
        'description'=>'The Indicia ID for the species list that species can be selected from on the first species tab.',
        'type'=>'string'
      ),
      array(
      	'name'=>'tab_title_1',
        'caption'=>'Tab Title 1',
        'description'=>'The title of the tab containing Species List ID 1.',
        'type'=>'string'
      ),
      array(
      	'name'=>'list_id_2',
        'caption'=>'Species List ID 2',
        'description'=>'Optional. The Indicia ID for the species list that species can be selected from on the second species tab.',
        'type'=>'string',
        'required'=>false
      ),
      array(
      	'name'=>'tab_title_2',
        'caption'=>'Tab Title 2',
        'description'=>'The title of the tab containing Species List ID 2.',
        'type'=>'string',
        'required'=>false
      ),
      array(
      	'name'=>'list_id_3',
        'caption'=>'Species List ID 3',
        'description'=>'Optional. The Indicia ID for the species list that species can be selected from on the third species tab.',
        'type'=>'string',
        'required'=>false
      ),
      array(
      	'name'=>'tab_title_3',
        'caption'=>'Tab Title 3',
        'description'=>'The title of the tab containing Species List ID 3.',
        'type'=>'string',
        'required'=>false
      ),
      array(
      	'name'=>'list_id_4',
        'caption'=>'Species List ID 4',
        'description'=>'Optional. The Indicia ID for the species list that species can be selected from on the fourth species tab.',
        'type'=>'string',
        'required'=>false
      ), 
      array(
      	'name'=>'tab_title_4',
        'caption'=>'Tab Title 4',
        'description'=>'The title of the tab containing Species List ID 1.',
        'type'=>'string',
        'required'=>false
      ),
    );
  }
  
  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'Site Survey Recording Form (based on Orthoptera and Allied Insects)';  
  }
  
/**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args) {
    data_entry_helper::enable_tabs(array(
        'divId'=>'controls'
    ));
    $r = "<form method=\"post\">\n";
    // Get authorisation tokens to update and read from the Warehouse.
    $r .= data_entry_helper::get_auth($args['website_id'], $args['password']);
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    $r .= "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= "<input type=\"hidden\" id=\"record_status\" name=\"record_status\" value=\"C\" />\n";    
    $r .= "<div id=\"controls\">\n";
    // Create a list which jQuery can parse to create the tabs.
    $r .= "<ul>
      <li><a href=\"#recorder\"><span>Recorder</span></a></li>
      <li><a href=\"#site\"><span>Site</span></a></li>
      <li><a href=\"#species_tab_1\"><span>".$args['tab_title_1']."</span></a></li>\n";
      if ($args['list_id_2']) {
        $r .= "<li><a href=\"#species_tab_2\"><span>".$args['tab_title_2']."</span></a></li>\n";
      }
      if ($args['list_id_3']) {
        $r .= "<li><a href=\"#species_tab_3\"><span>".$args['tab_title_3']."</span></a></li>\n";
      }
      if ($args['list_id_4']) {
        $r .= "<li><a href=\"#species_tab_4\"><span>".$args['tab_title_4']."</span></a></li>\n";
      }
    $r .= "</ul>\n";        
    $r .= "<div id=\"recorder\">\n";
    $r .= data_entry_helper::select(array(
        'label'=>'Title',
        'fieldname'=>'smpAttr:5',
        'table'=>'termlists_term',
    		'captionField'=>'term',
    		'valueField'=>'id',
        'extraParams'=>$readAuth + array('termlist_external_key'=>'indicia:titles')
    ));
    $r .= data_entry_helper::text_input(array(
        'label'=>'First name',
        'fieldname'=>'smpAttr:6'        
    ));
    $r .= data_entry_helper::text_input(array(
        'label'=>'Last name',
        'fieldname'=>'smpAttr:7'        
    ));
    $r .= data_entry_helper::text_input(array(
        'label'=>'Email',
        'fieldname'=>'smpAttr:8'        
    ));
    // Postcode before address since entering the postcode auto-populates part of the address.
    $r .= data_entry_helper::postcode_textbox(array(
        'label'=>'Postcode',
        'fieldname'=>'smpAttr:10',
        'linkedAddressBoxId'=>'address',
        'hiddenFields'=>false    
    ));
    $r .= data_entry_helper::textarea(array(
        'label'=>'Address',
        'fieldname'=>'smpAttr:9',
        'id'=>'address'        
    ));    
    $r .= "</div>\n"; 
    $r .= "<div id=\"site\">\n";
    $r .= data_entry_helper::map();
    $r .= data_entry_helper::date_picker(array(
        'label'=>'Date',
        'fieldname'=>'sample:date'
    ));
    $r .= "</div>\n";
    $r .= "<div id=\"species_tab_1\">\n";
    $species_list_args=array(
        'label'=>'Species',
        'fieldname'=>'occurrence:taxa_taxon_list_id',
        'table'=>'taxa_taxon_list',
        'captionField'=>'taxon',
        'valueField'=>'id',
        'columns'=>2,
        'extraParams'=>$readAuth + array('taxon_list_id' => $args['list_id_1'])
    );    
    $r .= data_entry_helper::species_checklist($species_list_args);
    $r .= "</div>\n";
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
    return data_entry_helper::build_sample_occurrences_list_submission($values);     
  } 

}