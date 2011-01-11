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
 * Prebuilt Indicia data entry form that presents taxon search box, date control, map picker,
 * survey selector and comment entry controls.
 * 
 * @package	Client
 * @subpackage PrebuiltForms
 */
class iform_basic_1 {
  
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
      	'name'=>'list_id',
        'caption'=>'Species List ID',
        'description'=>'The Indicia ID for the species list that species can be selected from.',
        'type'=>'string'
      ),
	  array(
      	'name'=>'preferred',
        'caption'=>'Preferred species only?',
        'description'=>'Should the selection of species be limited to preferred names only?',
        'type'=>'boolean',
        'required'=>false
      ),
      array(
      	'name'=>'tabs',
        'caption'=>'Use Tabbed Interface',
        'description'=>'If checked, then the page will be built using a tabbed interface style.',
        'type'=>'boolean',
        'required'=>false
      )
    );
  }
  
  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'Basic 1 - species, date, place, survey and comment';  
  }
  
  /**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args) {
    $r = "<form method=\"post\">\n";
    // Get authorisation tokens to update and read from the Warehouse.
    $r .= data_entry_helper::get_auth($args['website_id'], $args['password']);
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    $r .= "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= "<input type=\"hidden\" id=\"record_status\" name=\"record_status\" value=\"C\" />\n";
    $r .= "<div id=\"controls\">\n";
    if ($args['tabs']) {
      $r .= "<ul>
        <li><a href=\"#species\"><span>Species</span></a></li>
        <li><a href=\"#place\"><span>Place</span></a></li>
        <li><a href=\"#other\"><span>Other Information</span></a></li>
      </ul>\n";
      data_entry_helper::enable_tabs(array(
          'divId'=>'controls'
      ));
    }   
    
    $r .= "<div id=\"species\">\n";
	$extraParams = $readAuth + array('taxon_list_id' => $args['list_id']);
	if ($args['preferred']) {
	  $extraParams += array('preferred' => 't');
	}
  $species_list_args=array(
        'label'=>'Species',
        'itemTemplate' => 'select_species',
        'fieldname'=>'occurrence:taxa_taxon_list_id',
        'table'=>'taxa_taxon_list',
        'captionField'=>'taxon',
        'valueField'=>'id',
        'columns'=>2,
        'extraParams'=>$extraParams 
    );    
    // Dynamically generate the species selection control required.        
    $r .= call_user_func(array('data_entry_helper', $args['species_ctrl']), $species_list_args);
    $r .= "</div>\n";
    $r .= "<div id=\"place\">\n";
    // for this form, use virtual earth and no geoplanet lookup, since then it requires no API keys so is a good
    // quick demo of how things work.
    $r .= data_entry_helper::map(array(
      'presetLayers' => array('virtual_earth'),
      'locate' => false
    ));
    $r .= "</div>\n";    
    $r .= "<div id=\"other\">\n";
    $r .= data_entry_helper::date_picker(array(
        'label'=>'Date',
        'fieldname'=>'sample:date'
    ));
    $r .= data_entry_helper::select(array(
        'label'=>'Survey',
        'fieldname'=>'sample:survey_id',
        'table'=>'survey',
        'captionField'=>'title',
        'valueField'=>'id',
        'extraParams' => $readAuth
    ));
    $r .= data_entry_helper::textarea(array(
        'label'=>'Comment',
        'fieldname'=>'sample:comment',
        'class'=>'wide',
    ));
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
    return data_entry_helper::build_sample_occurrence_submission($values);     
  }  
  
  
  
}