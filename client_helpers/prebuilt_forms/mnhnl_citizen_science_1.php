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
class iform_mnhnl_citizen_science_1 {
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
    return array(
      array(
        'name'=>'interface',
        'caption'=>'Interface Style Option',
        'description'=>'Choose the style of user interface, either dividing the form up onto separate tabs, '.
            'wizard pages or having all controls on a single page.',
        'type'=>'select',
        'options' => array(
          'tabs' => 'Tabs',
          'wizard' => 'Wizard',
          'one_page' => 'All One Page'
        ),
        'group' => 'User Interface'
      ),      
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
          'treeview' => 'Treeview',
          'tree_browser' => 'Tree browser'
        ),
        'group'=>'User Interface'
      ),
      array(
        'name'=>'abundance_ctrl',
        'caption'=>'Abundance Control Type',
        'description'=>'The type of control that will be available to select the approximate abundance.',
        'type'=>'select',
        'options' => array(          
          'select' => 'Select',
          'listbox' => 'List box',
          'radio_group' => 'Radio group'          
        ),
        'group'=>'User Interface'
      ),
      array(
      	'name'=>'list_id',
        'caption'=>'Species List ID',
        'description'=>'The Indicia ID for the species list that species can be selected from.',
        'type'=>'int',
        'group'=>'Misc'
      ),
	    array(
      	'name'=>'preferred',
        'caption'=>'Preferred species only?',
        'description'=>'Should the selection of species be limited to preferred names only?',
        'type'=>'boolean',
	      'group'=>'Misc'
      ),
      array(
        'name'=>'survey_id',
        'caption'=>'Survey ID',
        'description'=>'The Indicia ID for the survey that data is saved into.',
        'type'=>'int',
        'group'=>'Misc'
      ),      
      array(
        'name'=>'uid_attr_id',
        'caption'=>'User ID Attribute ID',      
        'description'=>'Indicia ID for the sample attribute that stores the CMS User ID.',
        'type'=>'smpAttr',
        'group'=>'Sample Attributes'
      ),
      array(      
        'name'=>'username_attr_id',
        'caption'=>'Username Attribute ID',      
        'description'=>'Indicia ID for the sample attribute that stores the user\'s username.',
        'type'=>'smpAttr',
        'group'=>'Sample Attributes'
      ),
      array(
        'name'=>'email_attr_id',
        'caption'=>'Email Attribute ID',      
        'description'=>'Indicia ID for the sample attribute that stores the user\'s email.',
        'type'=>'smpAttr',
        'group'=>'Sample Attributes'
      ),
      array(
        'name'=>'first_name_attr_id',
        'caption'=>'First Name Attribute ID',      
        'description'=>'Indicia ID for the sample attribute that stores the user\'s first name.',
        'type'=>'smpAttr',
        'group'=>'Sample Attributes'
      ),
      array(
        'name'=>'surname_attr_id',
        'caption'=>'Surname Attribute ID',      
        'description'=>'Indicia ID for the sample attribute that stores the user\'s surname.',
        'type'=>'smpAttr',
        'group'=>'Sample Attributes'
      ),
      array(
        'name'=>'phone_attr_id',
        'caption'=>'Phone Attribute ID',      
        'description'=>'Indicia ID for the sample attribute that stores the user\'s phone.',
        'type'=>'smpAttr',
        'group'=>'Sample Attributes'
      ),
      array(
        'name'=>'contact_attr_id',
        'caption'=>'Contactable Attribute ID',      
        'description'=>'Indicia ID for the sample attribute that if the user has opted in for being contacted regarding this record.',
        'type'=>'smpAttr',
        'group'=>'Sample Attributes'
      ),
      array(
        'name'=>'abundance_attr_id',
        'caption'=>'Abundance Attribute ID',      
        'description'=>'Indicia ID for the occurrence attribute that records the approximate abundance.',
        'type'=>'occAttr',
        'group'=>'Occurrence Attributes'
      ),
      array(
        'name'=>'abundance_termlist_id',
        'caption'=>'Abundance Termlist ID',      
        'description'=>'Indicia ID for the termlist that contains the options to select from when specifying the approximate abundance.',
        'type'=>'termlist',
        'group'=>'Termlists'
      ),
      array(
        'name'=>'map_layers',
        'caption'=>'Available Map Layers',      
        'description'=>'List of available map background layers, comma separated. Options are '. 
            'openlayers_wms, nasa_mosaic, virtual_earth, multimap_default, multimap_landranger, google_physical, google_streets, google_hybrid or google_satellite.',
        'type'=>'string',
        'group'=>'Map'
      ),
      array(
        'name'=>'map_centroid_lat',
        'caption'=>'Centre of Map Latitude',      
        'description'=>'WGS84 Latitude of the initial map centre point, in decimal form.',
        'type'=>'string',
        'group'=>'Map'
      ),
      array(
        'name'=>'map_centroid_long',
        'caption'=>'Centre of Map Longitude',      
        'description'=>'WGS84 Longitude of the initial map centre point, in decimal form.',
        'type'=>'string',
        'group'=>'Map'
      ),
      array(
        'name'=>'map_zoom',
        'caption'=>'Map Zoom Level',      
        'description'=>'Zoom level of the initially displayed map.',
        'type'=>'int',
        'group'=>'Map'
      ),
      array(
        'name'=>'spatial_systems',
        'caption'=>'Allowed Spatial Ref Systems',      
        'description'=>'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code number such as 4326).',
        'type'=>'string',
        'group'=>'Map'
      )     
    );
  }
  
  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'MNHNL Citizen Science 1 - form designed for citizen science projects.';  
  }
  
  /**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args) {
    global $user;
    $logged_in = $user->uid>0;    
    
    $r = "<form method=\"post\" id=\"entry_form\">\n";        
    // Get authorisation tokens to update and read from the Warehouse.
    $r .= data_entry_helper::get_auth($args['website_id'], $args['password']);
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    $r .= "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= "<input type=\"hidden\" id=\"survey_id\" name=\"survey_id\" value=\"".$args['survey_id']."\" />\n";
    $r .= "<input type=\"hidden\" id=\"record_status\" name=\"record_status\" value=\"C\" />\n";
    // request automatic JS validation
    data_entry_helper::enable_validation('entry_form');

    if ($logged_in) {
      // If logged in, output some hidden data about the user
      $uid = $user->uid;
      $email = $user->mail;
      $username = $user->name;
      $uid_attr_id = $args['uid_attr_id'];      
      $email_attr_id = $args['email_attr_id'];
      $username_attr_id = $args['username_attr_id'];      
      $r .= "<input type=\"hidden\" name=\"smpAttr:$uid_attr_id\" value=\"$uid\" />\n";
      $r .= "<input type=\"hidden\" name=\"smpAttr:$email_attr_id\" value=\"$email\" />\n";
      $r .= "<input type=\"hidden\" name=\"smpAttr:$username_attr_id\" value=\"$username\" />\n";    
    }
    $r .= "<div id=\"controls\">\n";
    
    if ($args['interface']!='one_page') {    	
      $r .= "<ul>\n";
      if (!$logged_in) {
        $r .= '  <li><a href="#about_you"><span>'.lang::get('about you')."</span></a></li>\n";      
      }
      $r .= '  <li><a href="#species"><span>'.lang::get('what did you see')."</span></a></li>\n";      
      $r .= '  <li><a href="#place"><span>'.lang::get('where was it')."</span></a></li>\n";
      $r .= '  <li><a href="#other"><span>'.lang::get('other information')."</span></a></li>\n";
      $r .= "</ul>\n";      
      data_entry_helper::enable_tabs(array(
          'divId'=>'controls',
          'style'=>$args['interface']
      ));
    }   
    if ($user->uid==0) {
      $r .= "<div id=\"about_you\">\n";
      $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('first name'),
        'fieldname'=>'smpAttr:'.$args['first_name_attr_id'],
        'class'=>'control-width-4'
      ));  
      $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('surname'),
        'fieldname'=>'smpAttr:'.$args['surname_attr_id'],
        'class'=>'control-width-4'
      ));  
      $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('email'),
        'fieldname'=>'smpAttr:'.$args['email_attr_id'],
        'class'=>'control-width-4'
      )); 
      $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('phone number'),
        'fieldname'=>'smpAttr:'.$args['phone_attr_id'],
        'class'=>'control-width-4'
      ));
      if ($args['interface']=='wizard') {
        $r .= data_entry_helper::wizard_buttons(array(
          'divId'=>'controls',
          'page'=>'first'
        ));      
      }
      $r .= "</div>\n";      
    }
    $r .= "<div id=\"species\">\n";
    $r .= '<p class="page-notice ui-widget-header ui-corner-all">'.lang::get('species tab instructions')."</p>";
	  $extraParams = $readAuth + array('taxon_list_id' => $args['list_id']);
	  if ($args['preferred']) {
	    $extraParams += array('preferred' => 't');
	  }
    $species_list_args=array(
        'label'=>lang::get('occurrence:taxa_taxon_list_id'),
        'fieldname'=>'occurrence:taxa_taxon_list_id',
        'table'=>'taxa_taxon_list',
        'captionField'=>'taxon',
        'valueField'=>'id',
        'columns'=>2,
        'view'=>'detail',
        'parentField'=>'parent_id',
        'extraParams'=>$extraParams
    );
    if ($args['species_ctrl']=='tree_browser') {
      // change the node template to include images
      global $indicia_templates;
    	$indicia_templates['tree_browser_node']='<div>'.
    	    '<img src="'.data_entry_helper::$base_url.'/upload/thumb-{image_path}" alt="Image of {caption}" width="80" /></div>'.
    	    '<span>{caption}</span>';
    }
    // Dynamically generate the species selection control required.        
    $r .= call_user_func(array('data_entry_helper', $args['species_ctrl']), $species_list_args);
    if ($args['interface']=='wizard') {
      $r .= data_entry_helper::wizard_buttons(array(
        'divId'=>'controls',
        'page'=>($user->id==0) ? 'first' : 'middle'        
      ));
    }    
    $r .= "</div>\n";
    $r .= "<div id=\"place\">\n";
    $r .= '<p class="page-notice ui-widget-header ui-corner-all">'.lang::get('place tab instructions')."</p>";
    // Build the array of spatial reference systems into a format Indicia can use.
    $systems=array();
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach($list as $system) {
      $systems[$system] = lang::get($system);
    }    
    $r .= data_entry_helper::sref_and_system(array(
      'label' => lang::get('sample:entered_sref'),
      'systems' => $systems
    ));
    $r .= data_entry_helper::map_panel(array(
      'presetLayers'=>explode(',', str_replace(' ', '', $args['map_layers'])),
      'width'=>760,
      'initial_lat'=>$args['map_centroid_lat'],
      'initial_long'=>$args['map_centroid_long'],
      'initial_zoom'=>(int) $args['map_zoom']
    
    ));
    if ($args['interface']=='wizard') {
      $r .= data_entry_helper::wizard_buttons(array(
        'divId'=>'controls'
      ));      
    }
    $r .= "</div>\n";    
    $r .= "<div id=\"other\">\n";
    $r .= data_entry_helper::date_picker(array(
        'label'=>lang::get('Date'),
        'fieldname'=>'sample:date'
    ));
    // Dynamically create a control for the abundance
    $abundance_args = array(
      'label'=>lang::get('Abundance'),
      'fieldname'=>'occAttr:' + $args['abundance_attr_id'],
      'table'=>'termlists_term',
      'captionField'=>'term',
      'valueField'=>'id',
      'extraParams'=>$readAuth + array('termlist_id' => $args['abundance_termlist_id']),
      'size'=>6, // for listboxes
      'sep'=>'<br/>'      
    ); 
    $r .= call_user_func(array('data_entry_helper', $args['abundance_ctrl']), $abundance_args);    
    $r .= data_entry_helper::textarea(array(
        'label'=>'Comment',
        'fieldname'=>'sample:comment',
        'class'=>'wide',
    ));
    $r .= '<div class="footer">'.data_entry_helper::checkbox(array(
        'label'=>lang::get('happy for contact'),
        'labelClass'=>'auto',
        'fieldname'=>'smpAttr:'.$args['contact_attr_id']
    )).'</div>';
    if ($args['interface']=='wizard') {
      $r .= data_entry_helper::wizard_buttons(array(
        'divId'=>'controls',
        'page'=>'last'
      ));
    } else { 
      $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"Save\" />\n";
    }
    $r .= "</div>\n";        
    $r .= "</div>\n";    
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