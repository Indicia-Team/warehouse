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
 * Prebuilt Indicia data entry form.
 * NB has Drupal specific code.
 *
 * @package	Client
 * @subpackage PrebuiltForms
 */

require_once('includes/map.php');
require_once('includes/language_utils.php');
require_once('includes/user.php');

class iform_pollenator_gallery {

	/* TODO
	 * TODO photoreel: validate insect -> success posts, adds to photoreel, clears insect.
	 * 					clicking on photo -> validates existing insect (as above), sets insects
	 * 		occurrence attributes
	 * 		floral station.
	 * TODO L2 validation rules for radio buttons.
	 * TODO L4 convert uploads to flash to give progress bar.
	 * TODO nsp on floral station - "do not know"
	 * TODO convert ajaxsubmits to ajaxforms.
	 */
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $retVal = array_merge(
     iform_map_get_map_parameters(), 
     array(
        array(
          'name'=>'georefPreferredArea',
          'caption'=>'Preferred area for georeferencing.',
          'description'=>'Preferred area to look within when trying to resolve a place name. For example set this to the region name you are recording within.',
          'type'=>'string',
          'default'=>'fr',
          'group'=>'Map'
        ),
        array(
          'name'=>'georefCountry',
          'caption'=>'Preferred country for georeferencing.',
          'description'=>'Preferred country to look within when trying to resolve a place name.',
          'type'=>'string',
          'default'=>'France',
          'group'=>'Map'
        ),
     
      array(
      	'name'=>'survey_id',
        'caption'=>'Survey ID',
        'description'=>'The Indicia ID of the survey that data will be posted into.',
        'type'=>'int'
        ),

      array(
          'name'=>'search_url',
          'caption'=>'URL for Search WFS service',
          'description'=>'The URL used for the WFS feature lookup when searching.',
          'type'=>'string',
          'group'=>'Search'
      ),
      array(
          'name'=>'search_prefix',
          'caption'=>'Feature type prefix for Search',
          'description'=>'The Feature type prefix used for the WFS feature lookup when searching.',
          'type'=>'string',
          'group'=>'Search'
      ),
      array(
          'name'=>'search_ns',
          'caption'=>'Name space for Search',
          'description'=>'The Name space used for the WFS feature lookup when searching.',
          'type'=>'string',
          'group'=>'Search'
      ),
      array(
          'name'=>'INSEE_url',
          'caption'=>'URL for INSEE Search WFS service',
          'description'=>'The URL used for the WFS feature lookup when search for INSEE numbers.',
          'type'=>'string',
          'group'=>'INSEE Search'
      ),
      array(
          'name'=>'INSEE_prefix',
          'caption'=>'Feature type prefix for INSEE Search',
          'description'=>'The Feature type prefix used for the WFS feature lookup when search for INSEE numbers.',
          'type'=>'string',
          'group'=>'INSEE Search'
      ),
      array(
          'name'=>'INSEE_type',
          'caption'=>'Feature type for INSEE Search',
          'description'=>'The Feature type used for the WFS feature lookup when search for INSEE numbers.',
          'type'=>'string',
          'group'=>'INSEE Search'
      ),
      array(
          'name'=>'INSEE_ns',
          'caption'=>'Name space for INSEE Search',
          'description'=>'The Name space used for the WFS feature lookup when search for INSEE numbers.',
          'type'=>'string',
          'group'=>'INSEE Search'
      ),
      
      array(
          'name'=>'complete_attr_id',
          'caption'=>'Completeness Attribute ID',      
          'description'=>'Indicia ID for the sample attribute that stores whether the collection is complete.',
          'type'=>'int',
          'group'=>'Collection Attributes'
        ),
      array(      
          'name'=>'username_attr_id',
          'caption'=>'Username Attribute ID',      
          'description'=>'Indicia ID for the sample attribute that stores the user\'s username.',
          'type'=>'smpAttr',
          'group'=>'Collection Attributes'
        ),
      array(
          'name'=>'flower_type_attr_id',
          'caption'=>'Flower Type Attribute ID',      
          'description'=>'Indicia ID for the occurrence attribute that stores how the flower got there.',
          'type'=>'int',
          'group'=>'Floral Station Attributes'
            ),
      array(
          'name'=>'habitat_attr_id',
          'caption'=>'habitat Attribute ID',      
          'description'=>'Indicia ID for the location attribute that describes the habitat.',
          'type'=>'int',
          'group'=>'Floral Station Attributes'
            ),
        
      array(
          'name'=>'start_time_attr_id',
          'caption'=>'Start Time Attribute ID',      
          'description'=>'Indicia ID for the sample attribute that stores the start time.',
          'type'=>'int',
          'group'=>'Session Attributes'
            ),
      array(
          'name'=>'end_time_attr_id',
          'caption'=>'End Time Attribute ID',      
          'description'=>'Indicia ID for the sample attribute that stores the end time.',
          'type'=>'int',
          'group'=>'Session Attributes'
            ),
            
        array(
          'name'=>'sky_state_attr_id',
          'caption'=>'Sky State Attribute ID',      
          'description'=>'Indicia ID for the sample attribute that stores the state of the sky.',
          'type'=>'int',
          'group'=>'Session Attributes'
            ),
      array(
          'name'=>'temperature_attr_id',
          'caption'=>'Temperature Attribute ID',      
          'description'=>'Indicia ID for the sample attribute that stores the temperature.',
          'type'=>'int',
          'group'=>'Session Attributes'
            ),
      array(
          'name'=>'wind_attr_id',
          'caption'=>'Wind Attribute ID',      
          'description'=>'Indicia ID for the sample attribute that stores the Wind.',
          'type'=>'int',
          'group'=>'Session Attributes'
            ),
      array(
          'name'=>'shade_attr_id',
          'caption'=>'Shade Attribute ID',      
          'description'=>'Indicia ID for the sample attribute that stores the shade.',
          'type'=>'int',
          'group'=>'Session Attributes'
      ) ,
        array(
          'name'=>'flower_list_id',
          'caption'=>'Flower Species List ID',
          'description'=>'The Indicia ID for the species list that flowers can be selected from.',
          'type'=>'int',
          'group'=>'Floral Station Attributes'
          ),
      array(
          'name'=>'insect_list_id',
          'caption'=>'Insect Species List ID',
          'description'=>'The Indicia ID for the species list that insects can be selected from.',
          'type'=>'int',
          'group'=>'Insect Attributes'
      )
    ));
    return $retVal;
  	
  }

  /**
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'Pollenators: Gallery Filter and Focus on Collection, Insect and Flower';
  }

  public static function get_perms($nid) {
    return array('IForm n'.$nid.' access',
    			'IForm n'.$nid.' flower expert',
    			'IForm n'.$nid.' flag dubious flower',
    			'IForm n'.$nid.' create flower comment',
    			'IForm n'.$nid.' insect expert',
    			'IForm n'.$nid.' flag dubious insect',
    			'IForm n'.$nid.' create insect comment',
    			'IForm n'.$nid.' create collection comment'
    );
  }
  
/**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args, $node) {
  	global $user;
  	// There is a language entry in the args parameter list: this is derived from the $language DRUPAL global.
  	// It holds the 2 letter code, used to pick the language file from the lang subdirectory of prebuilt_forms.
  	// There should be no explicitly output text in this file.
  	// We must translate any field names and ensure that the termlists and taxonlists use the correct language.
  	// For attributes, the caption is automatically translated by data_entry_helper.
    $logged_in = $user->uid>0;
    $uid = $user->uid;
    $email = $user->mail;
    $username = $user->name;

    if(!user_access('IForm n'.$node->nid.' access')){
    	return "<p>".lang::get('LANG_Insufficient_Privileges')."</p>";
    }
    
  	$r = '';

    // Get authorisation tokens to update and read from the Warehouse.
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
	$svcUrl = data_entry_helper::$base_url.'/index.php/services';

	drupal_add_js(drupal_get_path('module', 'iform') .'/media/js/jquery.form.js', 'module');
	data_entry_helper::link_default_stylesheet();
	data_entry_helper::add_resource('jquery_ui');
	data_entry_helper::enable_validation('new-comments-form'); // don't care about ID itself, just want resources
	
	$occID= '';
	$smpID = '';
	$userID = '';
	$mode = 'FILTER';
	if (array_key_exists('insect_id', $_GET)){
        $occID = $_GET['insect_id'];
        $mode = 'INSECT';
	} else if (array_key_exists('flower_id', $_GET)){
        $occID = $_GET['flower_id'];
        $mode = 'FLOWER';
	} else if (array_key_exists('collection_id', $_GET)){
        $smpID = $_GET['collection_id'];
        $mode = 'COLLECTION';
	} else if (array_key_exists('user_id', $_GET)){
        $userID = $_GET['user_id'];
	}

	
//	data_entry_helper::enable_validation('cc-1-collection-details'); // don't care about ID itself, just want resources
	
	// The only things that will be editable after the collection is saved will be the identifiaction of the flower/insects.
	// no id - just getting the attributes, rest will be filled in using AJAX
	$sample_attributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$readAuth
       ,'survey_id'=>$args['survey_id']
    ));
    $occurrence_attributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'occurrence_attribute_value'
       ,'attrtable'=>'occurrence_attribute'
       ,'key'=>'occurrence_id'
       ,'fieldprefix'=>'occAttr'
       ,'extraParams'=>$readAuth
       ,'survey_id'=>$args['survey_id']
    ));
    $location_attributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'location_attribute_value'
       ,'attrtable'=>'location_attribute'
       ,'key'=>'location_id'
       ,'fieldprefix'=>'locAttr'
       ,'extraParams'=>$readAuth
       ,'survey_id'=>$args['survey_id']
    ));
    $defAttrOptions = array('extraParams'=>$readAuth,
    				'lookUpListCtrl' => 'checkbox_group',
    				'sep' => ' &nbsp; ',
    				'language' => iform_lang_iso_639_2($args['language']));
    
	// note we have to proxy the post. Every time a write transaction is carried out, the write nonce is trashed.
	// For security reasons we don't want to give the user the ability to generate their own nonce, so we use
	// the fact that the user is logged in to drupal as the main authentication/authorisation/identification
	// process for the user. The proxy packages the post into the correct format	

	$flower_ctrl_args=array(
    	    'label'=>lang::get('LANG_Flower_Species'),
        	'fieldname'=>'flower:taxa_taxon_list_id',
	        'table'=>'taxa_taxon_list',
    	    'captionField'=>'taxon',
        	'valueField'=>'id',
	        'columns'=>2,
    		'blankText'=>lang::get('LANG_Choose_Taxon'),
    	    'extraParams'=>$readAuth + array('taxon_list_id' => $args['flower_list_id'])
	);
	$focus_flower_ctrl_args = $flower_ctrl_args;
	$focus_flower_ctrl_args['fieldname'] = 'determination:taxa_taxon_list_id';
	$insect_ctrl_args=array(
    	    'label'=>lang::get('LANG_Insect_Species'),
        	'fieldname'=>'insect:taxa_taxon_list_id',
	        'table'=>'taxa_taxon_list',
    	    'captionField'=>'taxon',
        	'valueField'=>'id',
	        'columns'=>2,
    		'blankText'=>lang::get('LANG_Choose_Taxon'),
    	    'extraParams'=>$readAuth + array('taxon_list_id' => $args['insect_list_id'])
	);
	$focus_insect_ctrl_args = $insect_ctrl_args;
	$focus_insect_ctrl_args['fieldname'] = 'determination:taxa_taxon_list_id';
	$options = iform_map_get_map_options($args, $readAuth);
	$olOptions = iform_map_get_ol_options($args);
    // The maps internal projection will be left at its default of 900913.
	
    $options['initialFeatureWkt'] = null;
    $options['proxy'] = '';
	$options2 = $options;
    $options['searchLayer'] = 'true';
    $options['editLayer'] = 'false';
    $options['layers'] = array('polygonLayer');
    
	$options2['divId'] = "map2";
    $options2['layers'] = array('locationLayer');
	
 	$r .= '
<div id="filter" class="ui-accordion ui-widget ui-helper-reset">
	<div id="filter-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-accordion-content-active ui-corner-top">
	  	<div id="results-collections-title">
	  		<span>'.lang::get('LANG_Main_Title').'</span>
    	</div>
	</div>
	<div id="filter-spec" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active">
	  <div class="ui-accordion ui-widget ui-helper-reset">
		<div id="name-filter-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
			<div id="general-filter-title">
		  		<span>'.lang::get('LANG_Name_Filter_Title').'</span>
      		</div>
		</div>
	    <div id="name-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-bottom">
	  		<div id="reset-name-button" class="right ui-state-default ui-corner-all reset-name-button">'.lang::get('LANG_Reset_Filter').'</div>
	        '.data_entry_helper::text_input(array('label'=>lang::get('LANG_Name'),'fieldname'=>'username')).'
  		</div>
		<div id="date-filter-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
			<div id="general-filter-title">
		  		<span>'.lang::get('LANG_Date_Filter_Title').'</span>
      		</div>
		</div>
	    <div id="date-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-bottom">
	  		<div id="reset-date-button" class="right ui-state-default ui-corner-all reset-date-button">'.lang::get('LANG_Reset_Filter').'</div>
        	<label for="start_date" >'.lang::get('LANG_Created_Between').':</label>
  			<input type="text" size="10" id="start_date" name="start_date" value="'.lang::get('click here').'" />
        	&nbsp;'.lang::get('LANG_And').'&nbsp;
  			<input type="text" size="10" id="end_date" name="end_date" value="'.lang::get('click here').'" />
  		</div>
  		<div id="flower-filter-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
			<div id="flower-filter-title">
		  		<span>'.lang::get('LANG_Flower_Filter_Title').'</span>
      		</div>
		</div>
		<div id="flower-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-bottom">
	  		<div id="reset-flower-button" class="right ui-state-default ui-corner-all reset-flower-button">'.lang::get('LANG_Reset_Filter').'</div>
		  '.data_entry_helper::select($flower_ctrl_args)
		  .data_entry_helper::outputAttribute($occurrence_attributes[$args['flower_type_attr_id']], $defAttrOptions)
    	  .data_entry_helper::outputAttribute($location_attributes[$args['habitat_attr_id']], $defAttrOptions).'
    	</div>
		<div id="insect-filter-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
			<div id="insect-filter-title">
		  		<span>'.lang::get('LANG_Insect_Filter_Title').'</span>
      		</div>
		</div>
		<div id="insect-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-bottom">
	  		<div id="reset-insect-button" class="right ui-state-default ui-corner-all reset-insect-button">'.lang::get('LANG_Reset_Filter').'</div>
		  '.data_entry_helper::select($insect_ctrl_args)
    	  .data_entry_helper::outputAttribute($sample_attributes[$args['sky_state_attr_id']], $defAttrOptions)
		  .data_entry_helper::outputAttribute($sample_attributes[$args['temperature_attr_id']], $defAttrOptions)
		  .data_entry_helper::outputAttribute($sample_attributes[$args['wind_attr_id']], $defAttrOptions)
		  .data_entry_helper::outputAttribute($sample_attributes[$args['shade_attr_id']], $defAttrOptions)
    	  .'
		</div>
		<div id="location-filter-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
			<div id="location-filter-title">
		  		<span>TBD Location</span>
      		</div>
		</div>
		<div id="location-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-bottom">
	  		<div id="reset-location-button" class="right ui-state-default ui-corner-all reset-location-button">'.lang::get('LANG_Reset_Filter').'</div>
		  <div id="location-entry">
            '.data_entry_helper::georeference_lookup(array(
      		'label' => lang::get('LANG_Georef_Label'),
      		'georefPreferredArea' => $args['georefPreferredArea'],
      		'georefCountry' => $args['georefCountry'],
      		'georefLang' => $args['language']
    		)).'
 	        <label for="place:INSEE">'.lang::get('LANG_Or').'</label>
 		    <input type="text" id="place:INSEE" name="place:INSEE" value="'.lang::get('LANG_INSEE').'"
	 		  onclick="if(this.value==\''.lang::get('LANG_INSEE').'\'){this.value=\'\'; this.style.color=\'#000\'}"  
              onblur="if(this.value==\'\'){this.value=\''.lang::get('LANG_INSEE').'\'; this.style.color=\'#555\'}" />
    	    <input type="button" id="search-insee-button" class="ui-corner-all ui-widget-content ui-state-default indicia-button" value="Search" /><br />
 	      </div>
	  		'.data_entry_helper::map_panel($options, $olOptions).'
		</div>
      </div>
    </div>
    <div id="filter-footer" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-bottom">
	  <div id="search-insects-button" class="right ui-state-default ui-corner-all search-insects-button">'.lang::get('LANG_Search_Insects').'</div>
      <div id="search-collections-button" class="right ui-state-default ui-corner-all search-collections-button">'.lang::get('LANG_Search_Collections').'</div>
    </div>
	<div id="results-collections-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
	  <div id="results-collections-title">
	  	<span>TBD Collections Filter results</span>
      </div>
	</div>
	<div id="results-collections-results" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-bottom">
    </div>
	<div id="results-insects-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
	  <div id="results-insects-title">
	  	<span>TBD Insect Filter results</span>
      </div>
	</div>
	<div id="results-insects-results" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-bottom">
    </div>
</div>
<div id="focus-collection" class="ui-accordion ui-widget ui-helper-reset">
	<div id="collection-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
	  <div id="collection-title">
	  	<span>TBD Breadcrumb</span>
      </div>
	</div>
	<div id="collection-details" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active">
	  <div id="flower-image">
      </div>
	  <div id="collection-description">
	    <p id="collection-date"></p>
	    <p id="collection-name"></p>
	    <p id="collection-flower-name"></p>
	    <p>'.$occurrence_attributes[$args['flower_type_attr_id']]['caption'].': <span id="collection-flower-type"></span></p>
	    <p>'.$location_attributes[$args['habitat_attr_id']]['caption'].': <span id="collection-habitat"></span></p>
	    <span>TBD Locality description via SPIPOLLVers Shape File</span><br />
	    <p id="collection-user-name"></p>
	  <div id="search-collections-button" class="right ui-state-default ui-corner-all search-collections-button">'.lang::get('LANG_register').'</div>
	  </div>
	  <div id="environment-image">
      </div>
      <div id="map2_container">'.data_entry_helper::map_panel($options2, $olOptions).'
      </div>
    </div>
	<div id="collection-insects" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active">
    </div>
	<div id="collection-comments" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
	TBD Collection Comments
    </div>
</div>
<div id="focus-occurrence" class="ui-accordion ui-widget ui-helper-reset">
	<div id="fo-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
	  <div class="right">
 	      <span id="fo-collection-button" class="ui-state-default ui-corner-all collection-button">'.lang::get('LANG_Collection').'</span>
	      <span id="fo-prev-button" class="ui-state-default ui-corner-all previous-button">'.lang::get('LANG_Previous').'</span>
	      <span id="fo-next-button" class="ui-state-default ui-corner-all next-button">'.lang::get('LANG_Next').'</span>
	  </div>
	  <div id="fo-breadcrumb">
	  	<span>TBD Breadcrumb</span>
      </div>
	</div>
	<div id="fo-picture" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
	  <div id="fo-image">
      </div>
    </div>
	<div id="fo-identification" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">';
    if(user_access('IForm n'.$node->nid.' insect expert')){
    	$r .= '<div id="fo-new-insect-id-button" class="right ui-state-default ui-corner-all new-id-button">'.lang::get('LANG_New_ID').'</div>';
    }  
    if(user_access('IForm n'.$node->nid.' flower expert')){
    	$r .= '<div id="fo-new-flower-id-button" class="right ui-state-default ui-corner-all new-id-button">'.lang::get('LANG_New_ID').'</div>';
    }
    $r .= ' 
	  <div id="fo-doubt-button" class="right ui-state-default ui-corner-all doubt-button">'.lang::get('LANG_Doubt').'</div>
	  <div id="fo-id-title">
	  	<span>'.lang::get('LANG_Indentification_Title').'</span>
      </div>
    </div>
	<div id="fo-current-id" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active">
	</div>';
    if(user_access('IForm n'.$node->nid.' insect expert')){
    	$r .= '
	<div id="fo-new-insect-id" class="ui-accordion-content ui-helper-reset ui-widget-content">
	  <form id="fo-new-insect-id-form" action="'.iform_ajaxproxy_url($node, 'determination').'" method="POST">
		<input type="hidden" name="website_id" value="'.$args['website_id'].'" />
    	<input type="hidden" name="determination:occurrence_id" value="" />
		<input type="hidden" name="determination:cms_ref" value="'.$uid.'" />  
    	<input type="hidden" name="determination:person_name" value="'.$username.'" />  
		<input type="hidden" name="determination:email_address" value="'.$email.'" />
        <p>TBD '.lang::get('LANG_Launch_ID_Key').'</p>
        '.data_entry_helper::select($focus_insect_ctrl_args).'
        <input type="submit" id="id_submit_button" class="ui-state-default ui-corner-all" value="'.lang::get('LANG_Validate').'" />
      </form>
	</div>';
    }
    if(user_access('IForm n'.$node->nid.' insect expert')){
    	$r .= '
    <div id="fo-new-flower-id" class="ui-accordion-content ui-helper-reset ui-widget-content">
	  <form id="fo-new-flower-id-form" action="'.iform_ajaxproxy_url($node, 'determination').'" method="POST">
		<input type="hidden" name="website_id" value="'.$args['website_id'].'" />
    	<input type="hidden" name="determination:occurrence_id" value="" />
		<input type="hidden" name="determination:cms_ref" value="'.$uid.'" />  
    	<input type="hidden" name="determination:person_name" value="'.$username.'" />  
		<input type="hidden" name="determination:email_address" value="'.$email.'" />
        <p>TBD '.lang::get('LANG_Launch_ID_Key').'</p>
        '.data_entry_helper::select($focus_flower_ctrl_args).'
        <input type="submit" id="id_submit_button" class="ui-state-default ui-corner-all" value="'.lang::get('LANG_Validate').'" />
      </form>
	</div>';
    }
    $r .= '
	<div id="fo-id-history" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
	</div>
	<div id="fo-addn-info-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
	  <div id="fo-addn-info-title">
	  	<span>'.lang::get('LANG_Additional_Info_Title').'</span>
      </div>
	</div>
	<div id="fo-insect-addn-info" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
		<label for="sample_date">'.lang::get('LANG_Date').'</label>
		<input type="text" id="sample_date" readonly="readonly">
		<label for="sample_start_time">'.lang::get('LANG_Time').'</label>
		<input type="text" id="sample_start_time" readonly="readonly">
		'.lang::get('LANG_To').'
		<input type="text" id="sample_end_time" readonly="readonly"><br />
		<label for="sample_sky">'.$sample_attributes[$args['sky_state_attr_id']]['caption'].'</label>
		<input type="text" id="sample_sky" readonly="readonly">
		<label for="sample_temp">'.$sample_attributes[$args['temperature_attr_id']]['caption'].'</label>
		<input type="text" id="sample_temp" readonly="readonly">
		<label for="sample_wind">'.$sample_attributes[$args['wind_attr_id']]['caption'].'</label>
		<input type="text" id="sample_wind" readonly="readonly"><br />
		<label for="sample_shade">'.$sample_attributes[$args['shade_attr_id']]['caption'].'</label>
		<input type="text" id="sample_wind" readonly="readonly"><br />
	</div>
	<div id="fo-flower-addn-info" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
	    <p>'.$occurrence_attributes[$args['flower_type_attr_id']]['caption'].': <span id="focus-flower-type"></span></p>
	    <p>'.$location_attributes[$args['habitat_attr_id']]['caption'].': <span id="focus-habitat"></span></p>
	</div>
	<div id="fo-comments-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
	    <div id="fo-new-comment-button" class="right ui-state-default ui-corner-all new-comment-button">'.lang::get('LANG_New_Comment').'</div>
		<span>'.lang::get('LANG_Comments_Title').'</span>
	</div>
	<div id="fo-new-comment" class="ui-accordion-content ui-helper-reset ui-widget-content">
		<form id="fo-new-comment-form" action="'.iform_ajaxproxy_url($node, 'occ-comment').'" method="POST">
		    <input type="hidden" name="website_id" value="'.$args['website_id'].'" />
    		<input type="hidden" name="occurrence_comment:occurrence_id" value="" />
    		<label for="occurrence_comment:person_name">'.lang::get('LANG_Username').':</label>
		    <input type="text" name="occurrence_comment:person_name" value="'.$username.'" readonly="readonly" /><br />  
    		<label for="occurrence_comment:email_address">'.lang::get('LANG_Email').':</label>
		    <input type="text" name="occurrence_comment:email_address" value="'.$email.'" readonly="readonly" /><br />
		    '.data_entry_helper::textarea(array('label'=>lang::get('LANG_Comment'), 'fieldname'=>'occurrence_comment:comment', 'class'=>'required')).'
    		<input type="submit" id="comment_submit_button" class="ui-state-default ui-corner-all" value="'.lang::get('LANG_Submit_Comment').'" />
    	</form>
	</div>
	<div id="fo-comment-list" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
	</div>
</div>
';

    data_entry_helper::$javascript .= "
jQuery('#start_date').datepicker({
  dateFormat : 'yy-mm-dd',
  constrainInput: false,
  maxDate: '0'
});
jQuery('#end_date').datepicker({
  dateFormat : 'yy-mm-dd',
  constrainInput: false,
  maxDate: '0'
});
  
jQuery('#filter-header').click(function(){
    jQuery('#filter-header').addClass('ui-state-active');
	jQuery('#filter-spec,#filter-footer').removeClass('filter-hide');
    jQuery('#results-collections-header,#results-insects-header').removeClass('ui-state-active');
    jQuery('#focus-occurrence,#focus-flower,#focus-collection,#results-insects-results,#results-collections-results').addClass('filter-hide');
});
jQuery('#results-collections-header').click(function(){
    jQuery('#results-collections-header').addClass('ui-state-active');
	jQuery('#results-collections-results').removeClass('filter-hide');
    jQuery('#filter-header').removeClass('ui-state-active');
	jQuery('#filter-spec,#filter-footer,#focus-occurrence,#focus-flower,#focus-collection,#results-insects-results').addClass('filter-hide');
});
jQuery('#reset-name-button').click(function(){
	jQuery('[name=username]').val('');
});
jQuery('#name-filter-header').click(function(){
	jQuery('#name-filter-header').toggleClass('ui-state-active');
    jQuery('#name-filter-body').toggleClass('filter-hide');
});
jQuery('#reset-date-button').click(function(){
	jQuery('[name=start_date]').val('".lang::get('click here')."');
	jQuery('[name=end_date]').val('".lang::get('click here')."');
});
jQuery('#date-filter-header').click(function(){
	jQuery('#date-filter-header').toggleClass('ui-state-active');
    jQuery('#date-filter-body').toggleClass('filter-hide');
});

jQuery('#reset-flower-button').click(function(){
	jQuery('[name=flower\\:taxa_taxon_list_id]').val('');
	jQuery('#flower-filter-body').find(':checkbox').removeAttr('checked');
});
jQuery('#flower-filter-header').click(function(){
	jQuery('#flower-filter-header').toggleClass('ui-state-active');
    jQuery('#flower-filter-body').toggleClass('filter-hide');
});

jQuery('#reset-insect-button').click(function(){
	jQuery('[name=insect\\:taxa_taxon_list_id]').val('');
	jQuery('#insect-filter-body').find(':checkbox').removeAttr('checked');
});

jQuery('#insect-filter-header').click(function(){
	jQuery('#insect-filter-header').toggleClass('ui-state-active');
    jQuery('#insect-filter-body').toggleClass('filter-hide');
});

jQuery('#reset-location-button').click(function(){
	polygonLayer.destroyFeatures();
	polygonLayer.map.searchLayer.destroyFeatures();
	if(inseeLayer != null)
		inseeLayer.destroyFeatures();
	jQuery('#imp-georef-search').val('');
	jQuery('[name=place\\:INSEE]').val('".lang::get('LANG_INSEE')."');
});
jQuery('#location-filter-header').click(function(){
	jQuery('#location-filter-header').toggleClass('ui-state-active');
    jQuery('#location-filter-body').toggleClass('filter-hide');
});

jQuery('#flower-image').click(function(){
	if(jQuery('#flower-image').attr('occID') != 'none'){
		loadFlower(jQuery('#flower-image').attr('occID'));
	}
});

loadCollection = function(id){
    locationLayer.destroyFeatures();
    jQuery('#focus-occurrence,#filter-spec,#filter-footer,#results-insects-header,#results-insects-results,#results-collections-results').addClass('filter-hide');
	jQuery('#focus-collection').removeClass('filter-hide');
	jQuery('#map2').width(jQuery('#map2_container').width());
	jQuery('#flower-image').attr('occID', 'none');
	jQuery('#collection-insects,#collection-date,#collection-name,#collection-flower-name,#collection-flower-type,#collection-habitat,#collection-user-name').empty();
	// this has a fixed target so can be done asynchronously.
	$.getJSON(\"".$svcUrl."/data/occurrence\" +
			\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
			\"&sample_id=\"+id+\"&callback=?\", function(flowerData) {
   		if (flowerData.length>0) {
			loadImage('occurrence_image', 'occurrence_id', flowerData[0].id, '#flower-image');
			jQuery('#flower-image').attr('occID', flowerData[0].id);
			$.getJSON(\"".$svcUrl."/data/determination\" + 
					\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
					\"&occurrence_id=\" + flowerData[0].id + \"&deleted=f&callback=?\", function(detData) {
   				if (detData.length>0) {
					var i = detData.length-1;
					var string = '';
					if(detData[i].taxon != '' && detData[i].taxon != null){
						string = string + detData[i].taxon + ', ';
					}
					if(detData[i].taxon_text_description != '' && detData[i].taxon_text_description != null){
						string = string + detData[i].taxon_text_description + ', ';
					}
					if(detData[i].taxon_extra_info != '' && detData[i].taxon_extra_info != null){
						string = string + detData[i].taxon_extra_info;
					}
					jQuery('<span>".lang::get('LANG_Flower_Name').": '+string+'</span>').appendTo('#collection-flower-name');
				}});
			$.getJSON(\"".$svcUrl."/data/occurrence_attribute_value\"  +
   					\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   					\"&occurrence_id=\" + flowerData[0].id + \"&callback=?\", function(attrdata) {
				if (attrdata.length>0) {
					for(i=0; i< attrdata.length; i++){
						if (attrdata[i].id){
							switch(parseInt(attrdata[i].occurrence_attribute_id)){
								case ".$args['flower_type_attr_id'].":
									jQuery('<span>'+attrdata[i].value+'</span>').appendTo('#collection-flower-type');
									break;
  			}}}}});
				
		}
	});
	$.getJSON(\"".$svcUrl."/data/sample_attribute_value\"  +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&sample_id=\" + id + \"&callback=?\", function(attrdata) {
		if (attrdata.length>0) {
			for(i=0; i< attrdata.length; i++){
				if (attrdata[i].id){
					switch(parseInt(attrdata[i].sample_attribute_id)){
						case ".$args['username_attr_id'].":
							jQuery('<span>".lang::get('LANG_Comment_By')."'+attrdata[i].value+'</span>').appendTo('#collection-user-name');
							break;
  	}}}}});
  	
	// this has a fixed target so can be done asynchronously.
	$.getJSON(\"".$svcUrl."/data/sample/\" +id+
			\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
			\"&callback=?\", function(collectionData) {
   		if (collectionData.length>0) {
			if(collectionData[0].date_start == collectionData[0].date_end){
	  			jQuery('<span>'+collectionData[0].date_start.substring(0,10)+'</span>').appendTo('#collection-date');
    		} else {
	  			jQuery('<span>'+collectionData[0].date_start+' - '+collectionData[0].date_end+'</span>').appendTo('#collection-date');
    		}
	  		jQuery('<span>'+collectionData[0].location_name+'</span>').appendTo('#collection-name');
   		    $.getJSON(\"".$svcUrl."/data/location/\" +collectionData[0].location_id +
					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
					\"&callback=?\", function(locationData) {
   				if (locationData.length>0) {
					loadImage('location_image', 'location_id', locationData[0].id, '#environment-image');
					var parser = new OpenLayers.Format.WKT();
					var feature = parser.read(locationData[0].centroid_geom);
					locationLayer.addFeatures([feature]);
					var bounds=locationLayer.getDataExtent();
			        locationLayer.map.setCenter(bounds.getCenterLonLat(), 13);
				}
			});
			$.getJSON(\"".$svcUrl."/data/location_attribute_value\"  +
   					\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   					\"&location_id=\" + collectionData[0].location_id + \"&callback=?\", function(attrdata) {
				if (attrdata.length>0) {
					for(i=0; i< attrdata.length; i++){
						if (attrdata[i].id){
							switch(parseInt(attrdata[i].location_attribute_id)){
								case ".$args['habitat_attr_id'].":
									jQuery('<span>'+attrdata[i].value+' / </span>').appendTo('#collection-habitat');
									break;
  			}}}}});
		}
	});
	$.getJSON(\"".$svcUrl."/data/sample\" + 
			\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&parent_id=\"+id+\"&callback=?\", function(sessiondata) {
  		if (sessiondata.length>0) {
			for (var i=0;i<sessiondata.length;i++){
				$.getJSON(\"".$svcUrl."/data/occurrence/\" +
						\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
						\"&sample_id=\"+sessiondata[i].id+\"&callback=?\", function(insectData) {
					if (insectData.length>0) {
						for (var j=0;j<insectData.length;j++){
							var insect=jQuery('<div class=\"ui-widget-content ui-corner-all collection-insect\" />').appendTo('#collection-insects');
							var tag = jQuery('<p />').addClass('insect-ok').appendTo(insect);
							var image = jQuery('<div />').appendTo(insect);
							loadImage('occurrence_image', 'occurrence_id', insectData[j].id, image);
							// have to do this synchronously due to multiple targets.
							jQuery.ajax({ 
						    	type: 'GET', 
						    	url: \"".$svcUrl."/data/determination\" + 
					    			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
					    			\"&occurrence_id=\" + insectData[j].id + \"&deleted=f&callback=?\", 
						    	dataType: 'json', 
						    	success: function(detData) {
   									if (detData.length>0) {
										var i = detData.length-1;
										var string = '';
										if(detData[i].taxon != '' && detData[i].taxon != null){
											string = string + detData[i].taxon + ', ';
										}
										if(detData[i].taxon_text_description != '' && detData[i].taxon_text_description != null){
											string = string + detData[i].taxon_text_description + ', ';
										}
										if(detData[i].taxon_extra_info != '' && detData[i].taxon_extra_info != null){
											string = string + detData[i].taxon_extra_info;
										}
										jQuery('<div><p>".lang::get('LANG_Last_ID').":<br /><strong>'+string+'</strong></p></div>').appendTo(insect);
									} else {
						    			// no determination records, so no attempt made at identification. Put up a question mark.
						    			// TDB more sophisticated - if flagged as dubious or > 5 possibilities
										tag.removeClass('insect-ok').addClass('insect-unknown');
							  		}}, 
						    	data: {}, 
						    	async: false 
							});  					
							var displayButton = jQuery('<div class=\"ui-state-default ui-corner-all display-button\">".lang::get('LANG_Display')."</div><br />')
								.appendTo(insect).attr('value',insectData[j].id);
							displayButton.click(function(){
								loadInsect(jQuery(this).attr('value'));
							});
						}
					}
				});
			}
  		}
	});	
};

addCollection = function(attributes){
	var collection=jQuery('<div class=\"ui-widget-content ui-corner-all filter-collection\" />').appendTo('#results-collections-results');
	var flower = jQuery('<div class=\"collection-image\" />').attr('occID', attributes.flower_id).click(function(){
		loadFlower(jQuery(this).attr('occID'));
	});
	flower.appendTo(collection);
	var img = new Image();
	$(img).load(function () {flower.append(this);})
	    .attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."med-'+attributes.flower_image_path)
	    .css('max-width', flower.width()).css('max-height', flower.height())
	    .css('vertical-align', 'middle').css('margin-left', 'auto').css('margin-right', 'auto').css('display', 'block');
	var location = jQuery('<div class=\"collection-image\" />').appendTo(collection);
	img = new Image();
	$(img).load(function () {location.append(this)})
	    .attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."med-'+attributes.location_image_path)
	    .css('max-width', location.width()).css('max-height', location.height())
	    .css('vertical-align', 'middle').css('margin-left', 'auto').css('margin-right', 'auto').css('display', 'block');
	var details = jQuery('<div class=\"collection-details\" />').appendTo(collection); 
	var displayButton = jQuery('<div class=\"right ui-state-default ui-corner-all display-button\">".lang::get('LANG_Display')."</div><br />');
	displayButton.click(function(){
		loadCollection(jQuery(this).attr('value'));
	}).appendTo(details).attr('value',attributes.collection_id);
	if(attributes.date_start == attributes.date_end){
	  jQuery('<span>'+attributes.date_start.substring(0,10)+'</span><br />').appendTo(details);
    } else {
	  jQuery('<span>'+attributes.date_start+' - '+attributes.date_end+'</span><br />').appendTo(details);
    }
	jQuery('<span>'+attributes.location_name+'</span><br />').appendTo(details);
	jQuery('<span>TBD Locality description via SPIPOLLVers Shape File</span><br />').appendTo(details);
	var creatorTag = '{|".$args['username_attr_id']."|,';
	var creatorPosition = attributes.collection_attributes.indexOf(creatorTag)+creatorTag.length;
	var creator = attributes.collection_attributes.substring(creatorPosition);
	var endPos = creator.indexOf('}');
	jQuery('<span>".lang::get('LANG_Comment_By')."'+creator.substring(0,endPos)+'</span><br />').appendTo(details);
	var photoReel = jQuery('<div></div>').appendTo(collection);
    $.getJSON(\"".$svcUrl."/data/sample\" + 
    		\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&parent_id=\"+attributes.collection_id+\"&callback=?\", function(sessiondata) {
		for (var i=0;i<sessiondata.length;i++){
			$.getJSON(\"".$svcUrl."/data/occurrence/\" +
					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&orderby=id\" +
					\"&sample_id=\"+sessiondata[i].id+\"&callback=?\", function(insectData) {
		    	if (insectData.length>0) {
 					for (var j=0;j<insectData.length;j++){
						var container = jQuery('<div/>').addClass('thumb').attr('occId', insectData[j].id.toString()).click(function () {loadInsect(jQuery(this).attr('occId'));});
						photoReel.append(container);
						jQuery.ajax({ 
						    type: 'GET', 
						    url: \"".$svcUrl."/data/occurrence_image\" +
					   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   								\"&occurrence_id=\" + insectData[j].id + \"&callback=?\", 
						    dataType: 'json', 
						    success: function(imageData) {
							  if (imageData.length>0) {
								var img = new Image();
								jQuery(img).attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."thumb-'+imageData[0].path)
			    					.attr('width', container.width()).attr('height', container.height()).addClass('thumb-image').appendTo(container);
							  }}, 
						    data: {}, 
						    async: false 
						}); 
						jQuery.ajax({ 
						    type: 'GET', 
						    url: \"".$svcUrl."/data/determination\" + 
					    		\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
					    		\"&occurrence_id=\" + insectData[j].id + \"&deleted=f&callback=?\", 
						    dataType: 'json', 
						    success: function(detData) {
						      if (detData.length==0) {
						    	// no determination records, so no attempt made at identification. Put up a question mark.
						    	// TDB more sophisticated - if flagged as dubious or > 5 possibilities
								jQuery('<span>?</span>').addClass('thumb-text').appendTo(container);
							  }}, 
						    data: {}, 
						    async: false 
						});  					
					}
				}});
		}});
};

// searchLayer in map is used for georeferencing.
// map editLayer is switched off.
searchLayer = null;
inseeLayer = null;
polygonLayer = new OpenLayers.Layer.Vector('Polygon Layer', {
	styleMap: new OpenLayers.StyleMap({
                \"default\": new OpenLayers.Style({
                    fillColor: \"Red\",
                    strokeColor: \"Red\",
                    fillOpacity: 0,
                    strokeWidth: 1
                  })
	}),
	displayInLayerSwitcher: false
});
polygonLayer.events.register('featuresadded', {}, function(a1){
	polygonLayer.map.searchLayer.destroyFeatures();
	if(inseeLayer != null)
		inseeLayer.destroyFeatures();
});          
locationLayer = new OpenLayers.Layer.Vector('Location Layer',
	{displayInLayerSwitcher: false});

jQuery('#search-insee-button').click(function(){
	if(inseeLayer != null)
		inseeLayer.destroy();
	polygonLayer.map.searchLayer.destroyFeatures();
	polygonLayer.destroyFeatures();
	var filters = [];
  	var place = jQuery('input[name=place\\:INSEE]').val();
  	if(place == '".lang::get('LANG_INSEE')."') return;
  	filters.push(new OpenLayers.Filter.Comparison({
  			type: OpenLayers.Filter.Comparison.EQUAL_TO ,
    		property: 'INSEE_NEW',
    		value: place
  		}));
  	filters.push(new OpenLayers.Filter.Comparison({
  			type: OpenLayers.Filter.Comparison.EQUAL_TO ,
    		property: 'INSEE_OLD',
    		value: place
  		}));

	var strategy = new OpenLayers.Strategy.Fixed({preload: false, autoActivate: false});
	var styleMap = new OpenLayers.StyleMap({
                \"default\": new OpenLayers.Style({
                    fillColor: \"Red\",
                    strokeColor: \"Red\",
                    fillOpacity: 0,
                    strokeWidth: 1
                  })
	});
	inseeLayer = new OpenLayers.Layer.Vector('INSEE Layer', {
		  styleMap: styleMap,
          strategies: [strategy],
          displayInLayerSwitcher: false,
	      protocol: new OpenLayers.Protocol.WFS({
              url:  '".$args['INSEE_url']."',
              featurePrefix: '".$args['INSEE_prefix']."',
              featureType: '".$args['INSEE_type']."',
              geometryName:'the_geom',
              featureNS: '".$args['INSEE_ns']."',
              srsName: 'EPSG:900913',
              version: '1.1.0'                  
      		  ,propertyNames: ['the_geom']
  			})
    });
	inseeLayer.events.register('featuresadded', {}, function(a1){
		var div = jQuery('#map')[0];
		div.map.searchLayer.destroyFeatures();
		var bounds=inseeLayer.getDataExtent();
    	var dy = (bounds.top-bounds.bottom)/10;
    	var dx = (bounds.right-bounds.left)/10;
    	bounds.top = bounds.top + dy;
    	bounds.bottom = bounds.bottom - dy;
    	bounds.right = bounds.right + dx;
    	bounds.left = bounds.left - dx;
    	// if showing a point, don't zoom in too far
    	if (dy===0 && dx===0) {
    		div.map.setCenter(bounds.getCenterLonLat(), div.settings.maxZoom);
    	} else {
    		div.map.zoomToExtent(bounds);
    	}
    });
	jQuery('#map')[0].map.addLayer(inseeLayer);
	strategy.load({filter: new OpenLayers.Filter.Logical({
			      type: OpenLayers.Filter.Logical.OR,
			      filters: filters
		  	  })});
});

jQuery('#search-collections-button').click(function(){
  	var ORgroup = [];
	
  	if(searchLayer != null)
		searchLayer.destroy();
		
	var use_insects = false;
    jQuery('#results-collections-results').empty();
	jQuery('#results-collections-header,#results-collections-results').removeClass('filter-hide');
	jQuery('#results-collections-header').addClass('ui-state-active');
	jQuery('#focus-occurrence,#focus-collection,#results-insects-header,#results-insects-results').addClass('filter-hide');
	var filters = [];
	// By default restrict selection to area displayed on map. When using the georeferencing system the map searchLayer
	// will contain a single point zoomed in appropriately.
	filters.push(new OpenLayers.Filter.Spatial({
    	type: OpenLayers.Filter.Spatial.BBOX,
    	property: 'geom',
    	value: jQuery('#map')[0].map.getExtent()
  	}));
  	if(inseeLayer != null){
  		if(inseeLayer.features.length > 0){
  			// should only be one entry in the inseeLayer
			filters.push(new OpenLayers.Filter.Spatial({
    			type: OpenLayers.Filter.Spatial.WITHIN,
    			property: 'geom',
    			value: inseeLayer.features[0].geometry
		  	}));
  		}
  	}
  	if(polygonLayer != null){
  		if(polygonLayer.features.length > 0){
  			ORgroup = [];
  			for(i=0; i< polygonLayer.features.length; i++){
				ORgroup.push(new OpenLayers.Filter.Spatial({
    				type: OpenLayers.Filter.Spatial.WITHIN,
	    			property: 'geom',
    				value: polygonLayer.features[i].geometry
		  		}));
  			}
		  	if(ORgroup.length > 1){
				filters.push(new OpenLayers.Filter.Logical({
					type: OpenLayers.Filter.Logical.OR,
					filters: ORgroup
				}));
			} else {
  				if(ORgroup.length == 1){
			 		filters.push(ORgroup[0]);
	 			}
		  	} 	
  		}
  	}
  	
  	filters.push(new OpenLayers.Filter.Comparison({
  		type: OpenLayers.Filter.Comparison.LIKE,
    	property: 'collection_attributes',
    	value: '*{|".$args['complete_attr_id']."|,1}*'
  	}));
  	
  	var user = jQuery('input[name=username]').val();
  	if(user != ''){
  		filters.push(new OpenLayers.Filter.Comparison({
  			type: OpenLayers.Filter.Comparison.LIKE,
    		property: 'collection_attributes',
    		value: '*{|".$args['username_attr_id']."|,'+user+'}*'
  		}));
  	}
  	
  	var start_date = jQuery('input[name=start_date]').val();
  	var end_date = jQuery('input[name=end_date]').val();
  	if(start_date != '".lang::get('click here')."' && start_date != ''){
  		filters.push(new OpenLayers.Filter.Comparison({
  			type: OpenLayers.Filter.Comparison.GREATER_THAN  ,
    		property: 'date_end',
    		value: start_date
  		}));
  	}
  	if(end_date != '".lang::get('click here')."' && end_date != ''){
  		filters.push(new OpenLayers.Filter.Comparison({
  			type: OpenLayers.Filter.Comparison.LESS_THAN  ,
    		property: 'date_start',
    		value: end_date
  		}));
  	}
  	
  	var flower = jQuery('select[name=flower\\:taxa_taxon_list_id]').val();
  	if(flower != ''){
  		filters.push(new OpenLayers.Filter.Comparison({
  			type: OpenLayers.Filter.Comparison.LIKE ,
    		property: 'flower_taxon',
    		value: '*|'+flower+'|*'
  		}));
  	}
 
  	ORgroup = [];
  	jQuery('#flower-filter-body').find('[name^=occAttr:".$args['flower_type_attr_id']."]').filter('[checked]').each(function(index, elem){
  		ORgroup.push(new OpenLayers.Filter.Comparison({
  			type: OpenLayers.Filter.Comparison.LIKE ,
    		property: 'flower_attributes',
    		value: '*{|".$args['flower_type_attr_id']."|,'+elem.value+'}*'
  		}));
  	});
  	if(ORgroup.length > 1){
		filters.push(new OpenLayers.Filter.Logical({
			type: OpenLayers.Filter.Logical.OR,
			filters: ORgroup
		}));
	} else {
  		if(ORgroup.length == 1){
	 		filters.push(ORgroup[0]);
	 	}
  	}
 
  	ORgroup = [];
  	jQuery('#flower-filter-body').find('[name^=locAttr:".$args['habitat_attr_id']."]').filter('[checked]').each(function(index, elem){
  		ORgroup.push(new OpenLayers.Filter.Comparison({
  			type: OpenLayers.Filter.Comparison.LIKE ,
    		property: 'location_attributes',
    		value: '*{|".$args['habitat_attr_id']."|,'+elem.value+'}*'
  		}));
  	});
  	if(ORgroup.length > 1){
		filters.push(new OpenLayers.Filter.Logical({
			type: OpenLayers.Filter.Logical.OR,
			filters: ORgroup
		}));
	} else {
  		if(ORgroup.length == 1){
	 		filters.push(ORgroup[0]);
	 	}
  	}
  	
  	var insect = jQuery('select[name=insect\\:taxa_taxon_list_id]').val();
  	if(insect != ''){
  		use_insects = true;
  		filters.push(new OpenLayers.Filter.Comparison({
  			type: OpenLayers.Filter.Comparison.LIKE ,
    		property: 'insects',
    		value: '*|'+insect+'|*'
  		}));
  	}

  	ORgroup = [];
  	jQuery('#insect-filter-body').find('[name^=smpAttr:".$args['sky_state_attr_id']."]').filter('[checked]').each(function(index, elem){
  		use_insects = true;
  		ORgroup.push(new OpenLayers.Filter.Comparison({
  			type: OpenLayers.Filter.Comparison.LIKE ,
    		property: 'session_attributes',
    		value: '*{|".$args['sky_state_attr_id']."|,'+elem.value+'}*'
  		}));
  	});
  	if(ORgroup.length > 1){
		filters.push(new OpenLayers.Filter.Logical({
			type: OpenLayers.Filter.Logical.OR,
			filters: ORgroup
		}));
	} else {
  		if(ORgroup.length == 1){
	 		filters.push(ORgroup[0]);
	 	}
  	}

  	ORgroup = [];
  	jQuery('#insect-filter-body').find('[name^=smpAttr:".$args['temperature_attr_id']."]').filter('[checked]').each(function(index, elem){
  		use_insects = true;
  		ORgroup.push(new OpenLayers.Filter.Comparison({
  			type: OpenLayers.Filter.Comparison.LIKE ,
    		property: 'session_attributes',
    		value: '*{|".$args['temperature_attr_id']."|,'+elem.value+'}*'
  		}));
  	});
  	if(ORgroup.length > 1){
		filters.push(new OpenLayers.Filter.Logical({
			type: OpenLayers.Filter.Logical.OR,
			filters: ORgroup
		}));
	} else {
  		if(ORgroup.length == 1){
	 		filters.push(ORgroup[0]);
	 	}
  	}

  	ORgroup = [];
  	jQuery('#insect-filter-body').find('[name^=smpAttr:".$args['wind_attr_id']."]').filter('[checked]').each(function(index, elem){
  		use_insects = true;
  		ORgroup.push(new OpenLayers.Filter.Comparison({
  			type: OpenLayers.Filter.Comparison.LIKE ,
    		property: 'session_attributes',
    		value: '*{|".$args['wind_attr_id']."|,'+elem.value+'}*'
  		}));
  	});
  	if(ORgroup.length > 1){
		filters.push(new OpenLayers.Filter.Logical({
			type: OpenLayers.Filter.Logical.OR,
			filters: ORgroup
		}));
	} else {
  		if(ORgroup.length == 1){
	 		filters.push(ORgroup[0]);
	 	}
  	}

	// TODO need to do shade : this needs to be altered so that the attribute is a termlist
  	
	var strategy = new OpenLayers.Strategy.Fixed({preload: false, autoActivate: false});
	searchLayer = new OpenLayers.Layer.Vector('Search Layer', {
          strategies: [strategy],
          displayInLayerSwitcher: false,
	      protocol: new OpenLayers.Protocol.WFS({
              url: '".$args['search_url']."',
              featurePrefix: '".$args['search_prefix']."',
              featureType: use_insects ? 'spipoll_insects' : 'spipoll_collections',
              geometryName:'geom',
              featureNS: '".$args['search_ns']."',
              srsName: 'EPSG:900913',
              version: '1.1.0',                  
      		  propertyNames: ['collection_id','date_start','date_end','geom','location_name','location_image_path','flower_image_path','flower_id','flower_taxon','collection_attributes','location_attributes','flower_attributes']
  			})
	});
	searchLayer.events.register('featuresadded', {}, function(a1){
		for (var i = 0; i < a1.features.length; i++){
			addCollection(a1.features[i].attributes);
		}
	});
	
	jQuery('#map')[0].map.addLayer(searchLayer);
	strategy.load({filter: new OpenLayers.Filter.Logical({
			      type: OpenLayers.Filter.Logical.AND,
			      filters: filters
		  	  })});
});

  
previous_insect = '';
next_insect = '';
collection = '';

jQuery('form#fo-new-insect-id-form').ajaxForm({ 
	// dataType identifies the expected content type of the server response 
	dataType:  'json', 
	// success identifies the function to invoke when the server response 
	// has been received 
	beforeSubmit:   function(data, obj, options){
		if (!jQuery('form#fo-new-insect-id-form').valid()) { return false; }
		return true;
	},
	success:   function(data){
		if(data.error == undefined){
			jQuery('[name=determination\\:taxa_taxon_list_id]').val('');
			jQuery('#fo-new-insect-id').removeClass('ui-accordion-content-active');
			loadDeterminations(jQuery('[name=determination\\:occurrence_id]').val(), '#fo-id-history', '#fo-current-id');
		} else {
			alert(data.error);
		}
	} 
});
jQuery('form#fo-new-flower-id-form').ajaxForm({ 
	// dataType identifies the expected content type of the server response 
	dataType:  'json', 
	// success identifies the function to invoke when the server response 
	// has been received 
	beforeSubmit:   function(data, obj, options){
		if (!jQuery('form#fo-new-flower-id-form').valid()) { return false; }
		return true;
	},
	success:   function(data){
		if(data.error == undefined){
			jQuery('[name=determination\\:taxa_taxon_list_id]').val('');
			jQuery('#fo-new-flower-id').removeClass('ui-accordion-content-active');
			loadDeterminations(jQuery('[name=determination\\:occurrence_id]').val(), '#fo-id-history', '#fo-current-id');
		} else {
			alert(data.error);
		}
	} 
});
jQuery('#fo-new-comment-form').ajaxForm({ 
	// dataType identifies the expected content type of the server response 
	dataType:  'json', 
	// success identifies the function to invoke when the server response 
	// has been received 
	beforeSubmit:   function(data, obj, options){
		if (!jQuery('form#fo-new-comment-form').valid()) { return false; }
		return true;
	},
	success:   function(data){
		if(data.error == undefined){
			jQuery('[name=occurrence_comment\\:comment]').val('');
			jQuery('#fo-new-comment').removeClass('ui-accordion-content-active');
			loadComments(jQuery('[name=occurrence_comment\\:occurrence_id]').val(), '#fo-comment-list');
  		} else {
			alert(data.error);
		}
	} 
});

loadSampleAttributes = function(keyValue){
	$.getJSON(\"".$svcUrl."/data/sample_attribute_value\"  +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&sample_id=\" + keyValue + \"&callback=?\", function(attrdata) {
		if (attrdata.length>0) {
			for(i=0; i< attrdata.length; i++){
				if (attrdata[i].id){
					switch(parseInt(attrdata[i].sample_attribute_id)){
						case ".$args['start_time_attr_id'].":
							jQuery('#sample_start_time').val(attrdata[i].value);
							break;
						case ".$args['end_time_attr_id'].":
							jQuery('#sample_end_time').val(attrdata[i].value);
							break;
  						case ".$args['sky_state_attr_id'].":
							jQuery('#sample_sky').val(attrdata[i].value);
							break;
  						case ".$args['temperature_attr_id'].":
							jQuery('#sample_temp').val(attrdata[i].value);
							break;
  						case ".$args['wind_attr_id'].":
							jQuery('#sample_wind').val(attrdata[i].value);
							break;
  						case ".$args['shade_attr_id'].":
							jQuery('#sample_shade').val(attrdata[i].value);
							break;
  					}
				}
			}
		}
	});
}
loadOccurrenceAttributes = function(keyValue){
	jQuery('#focus-flower-type').empty();
	$.getJSON(\"".$svcUrl."/data/occurrence_attribute_value\"  +
			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&occurrence_id=\" + keyValue + \"&callback=?\", function(attrdata) {
		if (attrdata.length>0) {
			for(i=0; i< attrdata.length; i++){
				if (attrdata[i].id){
					switch(parseInt(attrdata[i].occurrence_attribute_id)){
						case ".$args['flower_type_attr_id'].":
							jQuery('<span>'+attrdata[i].value+'</span>').appendTo('#focus-flower-type');
							break;
  	}}}}});
}
loadLocationAttributes = function(keyValue){
	jQuery('#focus-habitat').empty();
	habitat_string = '';
	$.getJSON(\"".$svcUrl."/data/location_attribute_value\"  +
			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&location_id=\" + keyValue + \"&callback=?\", function(attrdata) {
		if (attrdata.length>0) {
			for(i=0; i< attrdata.length; i++){
				if (attrdata[i].id){
					switch(parseInt(attrdata[i].location_attribute_id)){
						case ".$args['flower_type_attr_id'].":
							habitat_string = (habitat_string == '' ? attrdata[i] : (habitat_string + ' | ' + attrdata[i]));
							break;
			}}}
			jQuery('<span>'+habitat_string+'</span>').appendTo('#focus-habitat');
  }});
}
imageRatio = 3/4;

loadImage = function(imageTable, key, keyValue, target){
	jQuery(target).empty();
	$.getJSON(\"".$svcUrl."/data/\" + imageTable +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&\" + key + \"=\" + keyValue + \"&callback=?\", function(imageData) {
		if (imageData.length>0) {
			var img = new Image();
			jQuery(img)
        		.load(function () {
        			jQuery(target).empty().append(this);
			    })
			    .attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."'+imageData[0].path)
				.css('max-width', $(target).width()).css('max-height', $(target).width()*imageRatio)
				.css('vertical-align', 'middle').css('margin-left', 'auto').css('margin-right', 'auto').css('display', 'block');
		}
	});
}

loadDeterminations = function(keyValue, historyID, currentID){
	jQuery(historyID).empty().append('<strong>".lang::get('LANG_History_Title')."</strong>');
	jQuery(currentID).empty();
	$.getJSON(\"".$svcUrl."/data/determination\" +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&occurrence_id=\" + keyValue + \"&callback=?\", function(detData) {
   		if (detData.length>0) {
			var i = detData.length-1;
			var string = '';
			if(detData[i].taxon != '' && detData[i].taxon != null){
				string = detData[i].taxon;
			}
			if(detData[i].taxon_text_description != '' && detData[i].taxon_text_description != null){
				string = (string == '' ? '' : string + ', ') + detData[i].taxon_text_description;
			}
			if(detData[i].taxon_extra_info != '' && detData[i].taxon_extra_info != null){
				string = (string == '' ? '' : string + ', ') + detData[i].taxon_text_description;
			}
			jQuery('<p><strong>'+string+ '</strong> ".lang::get('LANG_Comment_By')."' + detData[i].person_name + ' ' + detData[i].updated_on + '</p>').appendTo(currentID)
   			for(i=detData.length - 2; i >= 0; i--){ // deliberately miss last one, in reverse order
				var string = detData[i].updated_on + ' : ';
				if(detData[i].taxon != '' && detData[i].taxon != null){
					string = string + detData[i].taxon + ', ';
				}
				if(detData[i].taxon_text_description != '' && detData[i].taxon_text_description != null){
					string = string + detData[i].taxon_text_description + ', ';
				}
				if(detData[i].taxon_extra_info != '' && detData[i].taxon_extra_info != null){
					string = string + detData[i].taxon_text_description ;
				}
				jQuery('<p>'+string+ ' ".lang::get('LANG_Comment_By')."' + detData[i].person_name+'</p>').appendTo(historyID)
			}
		} else {
			jQuery('<p>".lang::get('LANG_No_Determinations')."</p>')
					.appendTo(historyID);
			jQuery('<p>".lang::get('LANG_No_Determinations')."</p>')
					.appendTo(currentID);
		}
	});
};
loadComments = function(keyValue, block){
	jQuery(block).empty();
	$.getJSON(\"".$svcUrl."/data/occurrence_comment\" +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&occurrence_id=\" + keyValue + \"&callback=?\", function(commentData) {
   		if (commentData.length>0) {
   			for(i=commentData.length - 1; i >= 0; i--){
	   			var newCommentDetails = jQuery('<div class=\"insect-comment-details\"/>')
					.appendTo(block);
				jQuery('<span>".lang::get('LANG_Comment_By')."' + commentData[i].person_name + ' ' + commentData[i].updated_on + '</span>')
					.appendTo(newCommentDetails);
	   			var newComment = jQuery('<div class=\"insect-comment-body\"/>')
					.appendTo(block);
				jQuery('<p>' + commentData[i].comment + '</p>')
					.appendTo(newComment);
			}
		} else {
			jQuery('<p>".lang::get('LANG_No_Comments')."</p>')
					.appendTo(block);
		}
	});
};

loadInsectAddnInfo = function(keyValue){
	// TODO convert buttons into thumbnails
	previous_insect = '';
	next_insect = '';
	collection = '';
	jQuery('#previous-button').hide();
	jQuery('#next-button').hide();
	
	// fetch occurrence details first to get the sample_id.
	// Get the sample to get the parent_id.
	// get all the samples (sessions) with the same parent_id;
	// fetch all the occurrences of the sessions.
	$.getJSON(\"".$svcUrl."/data/occurrence/\" + keyValue +
   			\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&callback=?\", function(occData) {
   		if (occData.length > 0) {
			$.getJSON(\"".$svcUrl."/data/sample/\" + occData[0].sample_id +
   					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   					\"&callback=?\", function(smpData) {
   				if (smpData.length > 0) {
   					collection = smpData[0].parent_id;
					jQuery('#sample_date').val(smpData[0].date_start);
					loadSampleAttributes(smpData[0].id);
					$.getJSON(\"".$svcUrl."/data/sample/\" +
   							\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   							\"&parent_id=\" + smpData[0].parent_id + \"&callback=?\", function(smpList) {
   						if (smpList.length > 0) {
   							for(j=0; j< smpList.length; j++){
		   						$.getJSON(\"".$svcUrl."/data/occurrence\" +
   										\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   										\"&sample_id=\" + smpList[j].id + \"&callback=?\", function(occList) {
	   								if(occList.length > 0){
   										for(i=0; i< occList.length; i++){
   											if(parseInt(occList[i].id) < parseInt(keyValue) && (previous_insect == '' || parseInt(occList[i].id) > parseInt(previous_insect))){
   												previous_insect = occList[i].id;
												jQuery('#previous-button').show();
											}
   											if(parseInt(occList[i].id) > parseInt(keyValue) && (next_insect == '' || parseInt(occList[i].id) > parseInt(next_insect))){
   												next_insect = occList[i].id;
												jQuery('#next-button').show();
  											}
   										}
   									}
   								});
   							}
   						}
  					});
  				}
   		   	});
   		}
   	});
}
loadFlowerAddnInfo = function(keyValue){
	// fetch occurrence details first to get the collection id.
	loadOccurrenceAttributes(keyValue);
	$.getJSON(\"".$svcUrl."/data/occurrence/\" + keyValue +
   			\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&callback=?\", function(occData) {
   		if (occData.length > 0) {
			$.getJSON(\"".$svcUrl."/data/sample/\" + occData[0].sample_id +
   					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   					\"&callback=?\", function(collection) {
   				if (collection.length > 0) {
					loadLocationAttributes(collection.location_id);
  				}
   		   	});
   		}
   	});
}

loadInsect = function(insectID){
    jQuery('#focus-collection,#filter-spec,#filter-footer,#results-insects-header,#results-collections-header,#results-insects-header,#results-insects-results,#results-collections-results,#fo-flower-addn-info').addClass('filter-hide');
	jQuery('#focus-occurrence,#fo-addn-info-header,#fo-insect-addn-info').removeClass('filter-hide');
	jQuery('[name=determination\\:occurrence_id]').val(insectID);
	jQuery('[name=occurrence_comment\\:occurrence_id]').val(insectID);
	jQuery('#fo-new-comment,#fo-new-id').removeClass('ui-accordion-content-active');
	jQuery('#fo-new-insect-id-button').show();
	jQuery('#fo-new-flower-id-button').hide();
	jQuery('#fo-doubt-button').".((user_access('IFrom n'.$node->nid.' insect expert') || user_access('IFrom n'.$node->nid.' flag dubious insect')) ? "show()" : "hide()").";
	jQuery('#fo-new-comment-button').".((user_access('IFrom n'.$node->nid.' insect expert') || user_access('IFrom n'.$node->nid.' create insect comment')) ? "show()" : "hide()").";
	loadImage('occurrence_image', 'occurrence_id', insectID, '#fo-image');
	loadDeterminations(insectID, '#fo-id-history', '#fo-current-id');
	loadInsectAddnInfo(insectID);
	loadComments(insectID, '#fo-comment-list');
	jQuery('#fo-prev-button,#fo-next-button').show();
}
loadFlower = function(flowerID){
	jQuery('#fo-prev-button,#fo-next-button').hide();
	jQuery('#focus-collection,#filter-spec,#filter-footer,#results-insects-header,#results-collections-header,#results-insects-header,#results-insects-results,#results-collections-results,#fo-insect-addn-info').addClass('filter-hide');
	jQuery('#focus-occurrence,#fo-addn-info-header,#fo-flower-addn-info').removeClass('filter-hide');
	jQuery('#fo-new-comment,#fo-new-id').removeClass('ui-accordion-content-active');
	jQuery('[name=determination\\:occurrence_id]').val(flowerID);
	jQuery('[name=occurrence_comment\\:occurrence_id]').val(flowerID);
	jQuery('#fo-new-flower-id-button').show();
	jQuery('#fo-new-insect-id-button').hide();
	// TODO dubious identification processing.
	jQuery('#fo-doubt-button').".((user_access('IFrom n'.$node->nid.' flower expert') || user_access('IFrom n'.$node->nid.' flag dubious flower')) ? "show()" : "hide()").";
	jQuery('#fo-new-comment-button').".((user_access('IFrom n'.$node->nid.' flower expert') || user_access('IFrom n'.$node->nid.' create flower comment')) ? "show()" : "hide()").";
	loadImage('occurrence_image', 'occurrence_id', flowerID, '#fo-image');
	loadDeterminations(flowerID, '#fo-id-history', '#fo-current-id');
	loadFlowerAddnInfo(flowerID);
	loadComments(flowerID, '#fo-comment-list');
}

jQuery('#fo-new-comment-button').click(function(){ 
	jQuery('#fo-new-comment').toggleClass('ui-accordion-content-active');
});
jQuery('#fo-new-insect-id-button').click(function(){ 
	jQuery('#fo-new-insect-id').toggleClass('ui-accordion-content-active');
});
jQuery('#fo-new-flower-id-button').click(function(){ 
	jQuery('#fo-new-flower-id').toggleClass('ui-accordion-content-active');
});
jQuery('#collection-button').click(function(){
	alert('TBD');
//	loadCollection('ui-accordion-content-active');
});
jQuery('#fo-prev-button').click(function(){
	if(previous_insect != '') {
		loadInsect(previous_insect);
	}
});
jQuery('#fo-next-button').click(function(){
	if(next_insect != '') {
		loadInsect(next_insect);
	}
});
  ";
    
    data_entry_helper::$onload_javascript .= "
function addDrawnGeomToSelection (geometry) {
    // Create the polygon as drawn
    var feature = new OpenLayers.Feature.Vector(geometry, {});
    polygonLayer.addFeatures([feature]);
};
polygonControl = new OpenLayers.Control.DrawFeature(polygonLayer, OpenLayers.Handler.Polygon, {drawFeature: addDrawnGeomToSelection});
polygonLayer.map.addControl(this.polygonControl);
polygonControl.activate();
polygonLayer.map.searchLayer.events.register('featuresadded', {}, function(a1){
	if(inseeLayer != null)
		inseeLayer.destroyFeatures();
	polygonLayer.destroyFeatures();
});          
";

    switch($mode){
    	case 'INSECT':
		    data_entry_helper::$javascript .= "loadInsect(".$occID.");
			";
		    break;
    	case 'FLOWER':
		    data_entry_helper::$javascript .= "loadFlower(".$occID.");
			";
		    break;
		case 'COLLECTION':
		    data_entry_helper::$javascript .= "
    		jQuery('#focus-occurrence,#filter-spec,#filter-footer,#results-insects-header,#results-collections-header,#results-insects-results,#results-collections-results').addClass('filter-hide');
			loadCollection(".$smpID.");
    		";
    		break;
    	default:
    		data_entry_helper::$javascript .= "
    		jQuery('#focus-occurrence,#focus-collection,#results-insects-header,#results-collections-header,#results-insects-results,#results-collections-results').addClass('filter-hide');
    		";
    		if($userID != ''){
    			$thisuser = user_load($userID);
    			data_entry_helper::$onload_javascript .= "
    			jQuery('[name=username]').val('".($thisuser->name)."');
    			jQuery('#search-collections-button').click();
    			";
    		}
       		break;
    }
    return $r;
  }

    /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
  	// Submission is AJAX based.
  	return false;
  }

  /**
   * Retrieves a list of the css files that this form requires in addition to the standard
   * Drupal, theme or Indicia ones.
   *
   * @return array List of css files to include for this form.
   */
  public static function get_css() {
    return array('pollenator_gallery.css');
  }
}