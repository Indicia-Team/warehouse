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
          'name'=>'collectionsPerPage',
          'caption'=>'Collections per page of search results',
          'description'=>'Number of Collections per page of search results.',
          'type'=>'int',
          'default'=>5,
          'group'=>'Search'
      ),
      array(
          'name'=>'insectsPerPage',
          'caption'=>'Insects per page of search results',
          'description'=>'Number of Insects per page of search results.',
          'type'=>'int',
          'default'=>9,
          'group'=>'Search'
      ),
      array(
          'name'=>'max_features',
          'caption'=>'Max number of items returned',
          'description'=>'Maximum number of features returned by the WFS search.',
          'type'=>'int',
          'default'=>1000,
          'group'=>'Search'
      ),
      
      array(
          'name'=>'ID_tool_flower_url',
          'caption'=>'Flower ID Tool URL',
          'description'=>'The URL to call which triggers the Flower Identification Tool functionality.',
          'type'=>'string',
          'group'=>'ID Tool',
      	  'required'=>false
      ),
      array(
          'name'=>'ID_tool_flower_poll_dir',
          'caption'=>'Flower ID Tool Module poll directory',
          'description'=>'The directory which to poll for the results of the Flower ID Tool',
          'type'=>'string',
          'group'=>'ID Tool',
      	  'required'=>false
      ),
      array(
          'name'=>'ID_tool_insect_url',
          'caption'=>'Insect ID Tool URL',
          'description'=>'The URL to call which triggers the Insect Identification Tool functionality.',
          'type'=>'string',
          'group'=>'ID Tool',
      	  'required'=>false
      ),
      array(
          'name'=>'ID_tool_insect_poll_dir',
          'caption'=>'Insect ID Tool Module poll directory',
          'description'=>'The directory which to poll for the results of the Insect ID Tool',
          'type'=>'string',
          'group'=>'ID Tool',
      	  'required'=>false
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
	} else if (array_key_exists('insect', $_GET)){
        $occID = $_GET['insect'];
        $mode = 'INSECT';
	} else if (array_key_exists('flower_id', $_GET)){
        $occID = $_GET['flower_id'];
        $mode = 'FLOWER';
	} else if (array_key_exists('flower', $_GET)){
        $occID = $_GET['flower'];
        $mode = 'FLOWER';
	} else if (array_key_exists('collection_id', $_GET)){
        $smpID = $_GET['collection_id'];
        $mode = 'COLLECTION';
	} else if (array_key_exists('collection', $_GET)){
        $smpID = $_GET['collection'];
        $mode = 'COLLECTION';
	} else if (array_key_exists('user_id', $_GET)){
        $userID = $_GET['user_id'];
	} else if (array_key_exists('user', $_GET)){
        $userID = $_GET['user'];
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
    $language = iform_lang_iso_639_2($args['language']);
    $defAttrOptions = array('extraParams'=>$readAuth,
    				'lookUpListCtrl' => 'checkbox_group',
    				'booleanCtrl' => 'checkbox',
       				'sep' => ' &nbsp; ',
    				'language' => $language,
    				'suffixTemplate'=>'nosuffix',
    				'default'=>'-1');
    
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
    	    'extraParams'=>$readAuth + array('taxon_list_id' => $args['flower_list_id']),
			'suffixTemplate'=>'nosuffix'
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
    	    'extraParams'=>$readAuth + array('taxon_list_id' => $args['insect_list_id']),
			'suffixTemplate'=>'nosuffix'
	);
	$focus_insect_ctrl_args = $insect_ctrl_args;
	$focus_insect_ctrl_args['fieldname'] = 'determination:taxa_taxon_list_id';
	$options = iform_map_get_map_options($args, $readAuth);
	$olOptions = iform_map_get_ol_options($args);
    // The maps internal projection will be left at its default of 900913.
	
    $options['initialFeatureWkt'] = null;
    $options['proxy'] = '';
    $options['suffixTemplate'] = 'nosuffix';
    $options2 = $options;
    $options['searchLayer'] = 'true';
    $options['editLayer'] = 'false';
    $options['layers'] = array('polygonLayer');
    
	$options2['divId'] = "map2";
    $options2['layers'] = array('locationLayer');

    // TBD Breadcrumb
 	$r .= '
<div id="filter" class="ui-accordion ui-widget ui-helper-reset">
	<div id="filter-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-accordion-content-active ui-corner-top">
	  	<div id="results-collections-title">
	  		<span>'.lang::get('LANG_Filter_Title').'</span>
    	</div>
	</div>
	<div id="filter-spec" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active">
	  <div class="ui-accordion ui-widget ui-helper-reset">
		<div id="name-filter-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-all">
	  		<div id="fold-name-button" class="ui-state-default ui-corner-all fold-button">&nbsp;</div>
	  		<div id="reset-name-button" class="ui-state-default ui-corner-all reset-button">'.lang::get('LANG_Reset_Filter').'</div>
	  		<div id="general-filter-title">
		  		<span>'.lang::get('LANG_Name_Filter_Title').'</span>
      		</div>
		</div>
	    <div id="name-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-all">
	        '.data_entry_helper::text_input(array('label'=>lang::get('LANG_Name'),'fieldname'=>'username', 'suffixTemplate'=>'nosuffix')).'
  		</div>
		<div id="date-filter-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-all">
	  		<div id="fold-date-button" class="ui-state-default ui-corner-all fold-button">&nbsp;</div>
	  		<div id="reset-date-button" class="ui-state-default ui-corner-all reset-button">'.lang::get('LANG_Reset_Filter').'</div>
	  		<div id="general-filter-title">
		  		<span>'.lang::get('LANG_Date_Filter_Title').'</span>
      		</div>
		</div>
	    <div id="date-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-all">
        	<label for="start_date" >'.lang::get('LANG_Created_Between').':</label>
  			<input type="text" size="10" id="start_date" name="start_date" value="'.lang::get('click here').'" />
       		<label for="start_date" >'.lang::get('LANG_And').':</label>
  			<input type="text" size="10" id="end_date" name="end_date" value="'.lang::get('click here').'" />
  		</div>
  		<div id="flower-filter-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-all">
	  		<div id="fold-flower-button" class="ui-state-default ui-corner-all fold-button">&nbsp;</div>
	  		<div id="reset-flower-button" class="ui-state-default ui-corner-all reset-button">'.lang::get('LANG_Reset_Filter').'</div>
	  		<div id="flower-filter-title">
		  		<span>'.lang::get('LANG_Flower_Filter_Title').'</span>
      		</div>
		</div>
		<div id="flower-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-all">
		  '.data_entry_helper::select($flower_ctrl_args)
		  .data_entry_helper::outputAttribute($occurrence_attributes[$args['flower_type_attr_id']], $defAttrOptions)
    	  .data_entry_helper::outputAttribute($location_attributes[$args['habitat_attr_id']], $defAttrOptions).'
    	</div>
		<div id="insect-filter-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-all">
	  		<div id="fold-insect-button" class="ui-state-default ui-corner-all fold-button">&nbsp;</div>
			<div id="reset-insect-button" class="ui-state-default ui-corner-all reset-button">'.lang::get('LANG_Reset_Filter').'</div>
	  		<div id="insect-filter-title">
		  		<span>'.lang::get('LANG_Insect_Filter_Title').'</span>
      		</div>
		</div>
		<div id="insect-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-all">
		  '.data_entry_helper::select($insect_ctrl_args)
    	  .data_entry_helper::outputAttribute($sample_attributes[$args['sky_state_attr_id']], $defAttrOptions)
		  .data_entry_helper::outputAttribute($sample_attributes[$args['temperature_attr_id']], $defAttrOptions)
		  .data_entry_helper::outputAttribute($sample_attributes[$args['wind_attr_id']], $defAttrOptions)
		  .data_entry_helper::outputAttribute($sample_attributes[$args['shade_attr_id']], $defAttrOptions)
    	  .'
		</div>
		<div id="location-filter-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-all">
	  		<div id="fold-location-button" class="ui-state-default ui-corner-all fold-button">&nbsp;</div>
			<div id="reset-location-button" class="ui-state-default ui-corner-all reset-button">'.lang::get('LANG_Reset_Filter').'</div>
			<div id="location-filter-title">
		  		<span>'.lang::get('LANG_Location_Filter_Title').'</span>
      		</div>
		</div>
		<div id="location-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-all">
		  <div id="location-entry">
            '.data_entry_helper::georeference_lookup(array(
      		'label' => lang::get('LANG_Georef_Label'),
      		'georefPreferredArea' => $args['georefPreferredArea'],
      		'georefCountry' => $args['georefCountry'],
      		'georefLang' => $args['language'],
    	    'suffixTemplate' => 'nosuffix'
    		)).'
 	        <label for="place:INSEE">'.lang::get('LANG_Or').'</label>
 		    <input type="text" id="place:INSEE" name="place:INSEE" value="'.lang::get('LANG_INSEE').'"
	 		  onclick="if(this.value==\''.lang::get('LANG_INSEE').'\'){this.value=\'\'; this.style.color=\'#000\'}"  
              onblur="if(this.value==\'\'){this.value=\''.lang::get('LANG_INSEE').'\'; this.style.color=\'#555\'}" />
    	    <input type="button" id="search-insee-button" class="ui-corner-all ui-widget-content ui-state-default search-button" value="Search" />
 	      </div>
	  		'.data_entry_helper::map_panel($options, $olOptions).'
		</div>
      </div>
    </div>
    <div id="filter-footer" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-bottom">
	  <div id="search-insects-button" class="ui-state-default ui-corner-all search-button">'.lang::get('LANG_Search_Insects').'</div>
      <div id="search-collections-button" class="ui-state-default ui-corner-all search-button">'.lang::get('LANG_Search_Collections').'</div>
    </div>
	<div id="results-collections-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
	  <div id="results-collections-title">
	  	<span>'.lang::get('LANG_Collections_Search_Results').'</span>
      </div>
	</div>
	<div id="results-collections-results" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-bottom">
    </div>
	<div id="results-insects-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
	  <div id="results-insects-title">
	  	<span>'.lang::get('LANG_Insects_Search_Results').'</span>
      </div>
	</div>
	<div id="results-insects-results" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-bottom">
    </div>
</div>
<div id="focus-collection" class="ui-accordion ui-widget ui-helper-reset">
	<div id="collection-details" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-all">
	  <div id="flower-image">
      </div>
	  <div id="collection-description">
	    <p id="collection-date"></p>
	    <p id="collection-name"></p>
	    <p id="collection-flower-name"></p>
	    <p>'.$occurrence_attributes[$args['flower_type_attr_id']]['caption'].': <span id="collection-flower-type"></span></p>
	    <p>'.$location_attributes[$args['habitat_attr_id']]['caption'].': <span id="collection-habitat"></span></p>
	    <p id="collection-locality"></p>
	    <p id="collection-user-name"></p>
	    <div id="show-flower-button" class="ui-state-default ui-corner-all display-button">'.lang::get('LANG_Display').'</div>
	  </div>
	  <div id="environment-image">
      </div>
      <div id="map2_container">'.data_entry_helper::map_panel($options2, $olOptions).'
      </div>
    </div>
	<div id="collection-insects">
    </div>
	<div id="fc-comments-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
	    <div id="fc-new-comment-button" class="ui-state-default ui-corner-all new-comment-button">'.lang::get('LANG_New_Comment').'</div>
		<span>'.lang::get('LANG_Comments_Title').'</span>
	</div>
	<div id="fc-new-comment" class="ui-accordion-content ui-helper-reset ui-widget-content">
		<form id="fc-new-comment-form" action="'.iform_ajaxproxy_url($node, 'smp-comment').'" method="POST">
		    <input type="hidden" name="website_id" value="'.$args['website_id'].'" />
    		<input type="hidden" name="sample_comment:sample_id" value="" />
    		<label for="sample_comment:person_name">'.lang::get('LANG_Username').':</label>
		    <input type="text" name="sample_comment:person_name" value="'.$username.'" readonly="readonly" />  
    		<label for="sample_comment:email_address">'.lang::get('LANG_Email').':</label>
		    <input type="text" name="sample_comment:email_address" value="'.$email.'" readonly="readonly" />
		    '.data_entry_helper::textarea(array('label'=>lang::get('LANG_Comment'), 'fieldname'=>'sample_comment:comment', 'class'=>'required', 'suffixTemplate'=>'nosuffix')).'
    		<input type="submit" id="fc_comment_submit_button" class="ui-state-default ui-corner-all submit-button" value="'.lang::get('LANG_Submit_Comment').'" />
    	</form>
	</div>
	<div id="fc-comment-list" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
	</div>
</div>
<div id="focus-occurrence" class="ui-accordion ui-widget ui-helper-reset">
	<h1 id="fo-taxon"></h1>
	<div id="fo-header" class="ui-accordion-content ui-helper-reset ui-state-active ui-corner-top ui-accordion-content-active">
	  <div id="fo-header-buttons">
 	      <span id="fo-collection-button" class="ui-state-default ui-corner-all collection-button">'.lang::get('LANG_Collection').'</span>
	      <span id="fo-prev-button" class="ui-state-default ui-corner-all previous-button">'.lang::get('LANG_Previous').'</span>
	      <span id="fo-next-button" class="ui-state-default ui-corner-all next-button">'.lang::get('LANG_Next').'</span>
	  </div>
	</div>
	<div id="fo-picture" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
	  <div id="fo-warning"></div>
	  <div id="fo-image">
      </div>
    </div>
	<div id="fo-identification" class="ui-accordion-header ui-helper-reset ui-corner-top ui-state-active">
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
		'.($args['ID_tool_insect_url'] != '' && $args['ID_tool_insect_poll_dir'] ?  '<label for="insect-id-button">'.lang::get('LANG_Insect_ID_Key_label').' :</label><span id="insect-id-button" class="ui-state-default ui-corner-all poll-id-button" >'.lang::get('LANG_Launch_ID_Key').'</span>' : '')
		.'<span id="insect-id-cancel" class="ui-state-default ui-corner-all poll-id-cancel" >'.lang::get('LANG_Cancel_ID').'</span>'
        .data_entry_helper::select($focus_insect_ctrl_args).'
        <input type="submit" id="id_submit_button" class="ui-state-default ui-corner-all submit-button" value="'.lang::get('LANG_Validate').'" />
      </form>
	</div>';
    }
    if(user_access('IForm n'.$node->nid.' flower expert')){
    	$r .= '
    <div id="fo-new-flower-id" class="ui-accordion-content ui-helper-reset ui-widget-content">
	  <form id="fo-new-flower-id-form" action="'.iform_ajaxproxy_url($node, 'determination').'" method="POST">
		<input type="hidden" name="website_id" value="'.$args['website_id'].'" />
    	<input type="hidden" name="determination:occurrence_id" value="" />
		<input type="hidden" name="determination:cms_ref" value="'.$uid.'" />  
    	<input type="hidden" name="determination:person_name" value="'.$username.'" />  
		<input type="hidden" name="determination:email_address" value="'.$email.'" />
		'.($args['ID_tool_flower_url'] != '' && $args['ID_tool_flower_poll_dir'] ?  '<label for="flower-id-button">'.lang::get('LANG_Flower_ID_Key_label').' :</label><span id="flower-id-button" class="ui-state-default ui-corner-all poll-id-button" >'.lang::get('LANG_Launch_ID_Key').'</span>' : '')
		.'<span id="flower-id-cancel" class="ui-state-default ui-corner-all poll-id-cancel" >'.lang::get('LANG_Cancel_ID').'</span>
        '.data_entry_helper::select($focus_flower_ctrl_args).'
        <input type="submit" id="id_submit_button" class="ui-state-default ui-corner-all submit-button" value="'.lang::get('LANG_Validate').'" />
      </form>
	</div>';
    }
    $r .= '
	<div id="fo-id-history" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active"></div>
	<div id="fo-id-buttons" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active">';
    if(user_access('IForm n'.$node->nid.' insect expert')){
    	$r .= '<div id="fo-new-insect-id-button" class="ui-state-default ui-corner-all new-id-button">'.lang::get('LANG_New_ID').'</div>';
    }  
    if(user_access('IForm n'.$node->nid.' flower expert')){
    	$r .= '<div id="fo-new-flower-id-button" class="ui-state-default ui-corner-all new-id-button">'.lang::get('LANG_New_ID').'</div>';
    }
    $r .= ' 
	  <div id="fo-doubt-button" class="ui-state-default ui-corner-all doubt-button">'.lang::get('LANG_Doubt').'</div>
    </div>
	
	<div id="fo-insect-addn-info" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
	    <span class="addn-info-title">'.lang::get('LANG_Additional_Info_Title').'</span>
	    <p>'.lang::get('LANG_Date').': <span id="fo-insect-date"></span></p>
	    <p>'.lang::get('LANG_Time').': <span id="fo-insect-start-time"></span> '.lang::get('LANG_To').' <span id="fo-insect-end-time"></span></p>
	    <p>'.$sample_attributes[$args['sky_state_attr_id']]['caption'].': <span id="fo-insect-date"></span></p>
	    <p>'.$sample_attributes[$args['temperature_attr_id']]['caption'].': <span id="fo-insect-temp"></span></p>
	    <p>'.$sample_attributes[$args['wind_attr_id']]['caption'].': <span id="fo-insect-wind"></span></p>
	    <p>'.$sample_attributes[$args['shade_attr_id']]['caption'].': <span id="fo-insect-sahed"></span></p>
	</div>
	<div id="fo-flower-addn-info" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
	    <p>'.$occurrence_attributes[$args['flower_type_attr_id']]['caption'].': <span id="focus-flower-type"></span></p>
	    <p>'.$location_attributes[$args['habitat_attr_id']]['caption'].': <span id="focus-habitat"></span></p>
	</div>
	<div id="fo-comments-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
	    <div id="fo-new-comment-button" class="ui-state-default ui-corner-all new-comment-button">'.lang::get('LANG_New_Comment').'</div>
		<span>'.lang::get('LANG_Comments_Title').'</span>
	</div>
	<div id="fo-new-comment" class="ui-accordion-content ui-helper-reset ui-widget-content">
		<form id="fo-new-comment-form" action="'.iform_ajaxproxy_url($node, 'occ-comment').'" method="POST">
		    <input type="hidden" name="website_id" value="'.$args['website_id'].'" />
    		<input type="hidden" name="occurrence_comment:occurrence_id" value="" />
    		<label for="occurrence_comment:person_name">'.lang::get('LANG_Username').':</label>
		    <input type="text" name="occurrence_comment:person_name" value="'.$username.'" readonly="readonly" />  
    		<label for="occurrence_comment:email_address">'.lang::get('LANG_Email').':</label>
		    <input type="text" name="occurrence_comment:email_address" value="'.$email.'" readonly="readonly" />
		    '.data_entry_helper::textarea(array('label'=>lang::get('LANG_Comment'), 'fieldname'=>'occurrence_comment:comment', 'class'=>'required', 'suffixTemplate'=>'nosuffix')).'
    		<input type="submit" id="comment_submit_button" class="ui-state-default ui-corner-all submit-button" value="'.lang::get('LANG_Submit_Comment').'" />
    	</form>
	</div>
	<div id="fo-comment-list" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
	</div>
	<div style="display:none" />
      <form id="fo-doubt-form" action="'.iform_ajaxproxy_url($node, 'determination').'" method="POST">
		<input type="hidden" name="website_id" value="'.$args['website_id'].'" />
    	<input type="hidden" name="determination:occurrence_id" value="" />
    	<input type="hidden" name="determination:id" value="" />
    	<input type="hidden" name="determination:dubious" value="Y" />
      </form>
    </div>	
</div>
';

    data_entry_helper::$javascript .= "
jQuery('#imp-georef-search-btn').removeClass('indicia-button').addClass('search-button');
    
$.validator.messages.required = \"".lang::get('validation_required')."\";
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
    jQuery('#filter-header').addClass('ui-state-active').removeClass('ui-state-default').addClass('ui-corner-top').removeClass('ui-corner-all');
	jQuery('#filter-spec,#filter-footer').removeClass('filter-hide');
    jQuery('#results-collections-header,#results-insects-header').removeClass('ui-state-active').addClass('ui-state-default');
    jQuery('#focus-occurrence,#focus-flower,#focus-collection,#results-insects-results,#results-collections-results').addClass('filter-hide');
});
jQuery('#results-collections-header').click(function(){
    jQuery('#results-collections-header').addClass('ui-state-active').removeClass('ui-state-default');
	jQuery('#results-collections-results').removeClass('filter-hide');
    jQuery('#filter-header').removeClass('ui-state-active').addClass('ui-state-default').removeClass('ui-corner-top').addClass('ui-corner-all');
	jQuery('#filter-spec,#filter-footer,#focus-occurrence,#focus-flower,#focus-collection,#results-insects-results').addClass('filter-hide');
});
jQuery('#reset-name-button').click(function(){
	jQuery('[name=username]').val('');
});
jQuery('#fold-name-button').click(function(){
	jQuery('#name-filter-header').toggleClass('ui-state-active').toggleClass('ui-state-default');
	jQuery('#fold-name-button').toggleClass('fold-button-folded');
	jQuery('#name-filter-body').toggleClass('filter-hide');
});
jQuery('#reset-date-button').click(function(){
	jQuery('[name=start_date]').val('".lang::get('click here')."');
	jQuery('[name=end_date]').val('".lang::get('click here')."');
});
jQuery('#fold-date-button').click(function(){
	jQuery('#date-filter-header').toggleClass('ui-state-active').toggleClass('ui-state-default');
	jQuery('#fold-date-button').toggleClass('fold-button-folded');
	jQuery('#date-filter-body').toggleClass('filter-hide');
});

jQuery('#reset-flower-button').click(function(){
	jQuery('[name=flower\\:taxa_taxon_list_id]').val('');
	jQuery('#flower-filter-body').find(':checkbox').removeAttr('checked');
});
jQuery('#fold-flower-button').click(function(){
	jQuery('#flower-filter-header').toggleClass('ui-state-active').toggleClass('ui-state-default');
	jQuery('#fold-flower-button').toggleClass('fold-button-folded');
	jQuery('#flower-filter-body').toggleClass('filter-hide');
});

jQuery('#reset-insect-button').click(function(){
	jQuery('[name=insect\\:taxa_taxon_list_id]').val('');
	jQuery('#insect-filter-body').find(':checkbox').removeAttr('checked');
});

jQuery('#fold-insect-button').click(function(){
	jQuery('#insect-filter-header').toggleClass('ui-state-active').toggleClass('ui-state-default');
	jQuery('#fold-insect-button').toggleClass('fold-button-folded');
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
jQuery('#fold-location-button').click(function(){
	jQuery('#location-filter-header').toggleClass('ui-state-active').toggleClass('ui-state-default');
	jQuery('#fold-location-button').toggleClass('fold-button-folded');
	jQuery('#location-filter-body').toggleClass('filter-hide');
});

jQuery('#flower-image').click(function(){
	if(jQuery('#flower-image').attr('occID') != 'none'){
		loadFlower(jQuery('#flower-image').attr('occID'));
	}
});
jQuery('#show-flower-button').click(function(){
	if(jQuery('#flower-image').attr('occID') != 'none'){
		loadFlower(jQuery('#flower-image').attr('occID'));
	}
});

jQuery('#fo-doubt-button').click(function(){
	if(confirm(\"".lang::get('LANG_Confirm_Express_Doubt')."\")){
		jQuery('#fo-doubt-form').submit();
	}
});



loadCollection = function(id){
    jQuery('[name=sample_comment\\:sample_id]').val(id);
    locationLayer.destroyFeatures();
	jQuery('#fc-new-comment-button').".(user_access('IForm n'.$node->nid.' create collection comment') ? "show()" : "hide()").";
    jQuery('#focus-occurrence,#filter-spec,#filter-footer,#results-insects-header,#results-insects-results,#results-collections-results').addClass('filter-hide');
    jQuery('#filter-header').removeClass('ui-state-active').addClass('ui-state-default').removeClass('ui-corner-top').addClass('ui-corner-all');
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
   					\"&occurrence_id=\" + flowerData[0].id + \"&iso=".$language."&callback=?\", function(attrdata) {
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
			        var filter = new OpenLayers.Filter.Spatial({
  						type: OpenLayers.Filter.Spatial.CONTAINS ,
    					property: 'the_geom',
    					value: feature.geometry
				  	});
					var locality = jQuery('#collection-locality');
				  	var scope = {target: locality};
					inseeProtocol.read({filter: filter, callback: test, scope: scope, async: false});
				}
			});
			$.getJSON(\"".$svcUrl."/data/location_attribute_value\"  +
   					\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   					\"&location_id=\" + collectionData[0].location_id + \"&iso=".$language."&callback=?\", function(attrdata) {
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
						\"&sample_id=\"+sessiondata[i].id+\"&orderby=id&callback=?\", function(insectData) {
					if (insectData.length>0) {
						for (var j=0;j<insectData.length;j++){
							var insect=jQuery('<div class=\"ui-widget-content ui-corner-all collection-insect\" />').attr('occID', insectData[j].id).appendTo('#collection-insects');
							jQuery('<p class=\"insect-tag insect-unknown\" />').appendTo(insect);
							var image = jQuery('<div class=\"insect-image\" />').appendTo(insect);
							jQuery('<p class=\"insect-determination\" />').appendTo(insect);
							var displayButton = jQuery('<div class=\"ui-state-default ui-corner-all display-button\">".lang::get('LANG_Display')."</div>')
								.appendTo(insect).attr('value',insectData[j].id);
							displayButton.click(function(){
								loadInsect(jQuery(this).attr('value'));
							});
							loadImage('occurrence_image', 'occurrence_id', insectData[j].id, image);
							// have to do this synchronously due to multiple targets
							$.getJSON(\"".$svcUrl."/data/determination\" + 
					    			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
					    			\"&occurrence_id=\" + insectData[j].id + \"&deleted=f&callback=?\", function(detData) {
   								if (detData.length>0) {
					    			var insect = jQuery('.collection-insect').filter('[occID='+detData[0].occurrence_id+']');
					    			var tag = insect.find('.insect-tag');
					    			var det = insect.find('.insect-determination');
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
									jQuery('<div><p>".lang::get('LANG_Last_ID').":</p><p><strong>'+string+'</strong></p></div>').addClass('insect-id').appendTo(det);
									if(detData[i].dubious == 'Y'){
										tag.removeClass('insect-unknown').addClass('insect-dubious');
									} else {
										tag.removeClass('insect-unknown').addClass('insect-ok');
									}
  								}});  					
						}
					}
				});
			}
  		}
	});
	loadComments(id, '#fc-comment-list', 'sample_comment', 'sample_id', 'sample-comment-block', 'sample-comment-body');
};
test = function(a1)
{
	jQuery(this.target).empty();
   	jQuery('<span>'+a1.features[0].attributes.NOM+' ('+a1.features[0].attributes.INSEE_NEW+'), '+a1.features[0].attributes.DEPT_NOM+' ('+a1.features[0].attributes.DEPT_NUM+'), '+a1.features[0].attributes.REG_NOM+' ('+a1.features[0].attributes.REG_NUM+')</span>').appendTo(this.target);
}
addCollection = function(attributes, geom){
	// first the text, then the flower and environment picture, then the small insect pictures, then the afficher button
	var collection=jQuery('<div class=\"ui-widget-content ui-corner-all filter-collection\" />').appendTo('#results-collections-results');
	var details = jQuery('<div class=\"collection-details\" />').appendTo(collection); 
	var flower = jQuery('<div class=\"collection-image collection-flower\" />').attr('occID', attributes.flower_id).click(function(){
		loadFlower(jQuery(this).attr('occID'));
	});
	var filter = new OpenLayers.Filter.Spatial({
  			type: OpenLayers.Filter.Spatial.CONTAINS ,
    		property: 'the_geom',
    		value: geom
  	});
	flower.appendTo(collection);
	var img = new Image();
	$(img).load(function () {flower.append(this);})
	    .attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."med-'+attributes.flower_image_path)
	    .css('max-width', flower.width()).css('max-height', flower.height())
	    .css('vertical-align', 'middle').css('margin-left', 'auto').css('margin-right', 'auto').css('display', 'block');
	var location = jQuery('<div class=\"collection-image collection-environment\" />').appendTo(collection);
	img = new Image();
	$(img).load(function () {location.append(this)})
	    .attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."med-'+attributes.location_image_path)
	    .css('max-width', location.width()).css('max-height', location.height())
	    .css('vertical-align', 'middle').css('margin-left', 'auto').css('margin-right', 'auto').css('display', 'block');
	var photoReel = jQuery('<div class=\"collection-photoreel\"></div>').appendTo(collection);
	var displayButtonContainer = jQuery('<div class=\"collection-buttons\"></div>').appendTo(collection);
	var displayButton = jQuery('<div class=\"ui-state-default ui-corner-all display-button\">".lang::get('LANG_Display')."</div>');
	displayButton.click(function(){
		loadCollection(jQuery(this).attr('value'));
	}).appendTo(displayButtonContainer).attr('value',attributes.collection_id);
	if(attributes.date_start == attributes.date_end){
	  jQuery('<p class=\"collection-date\">'+attributes.date_start.substring(0,10)+'</p>').appendTo(details);
    } else {
	  jQuery('<p class=\"collection-date\">'+attributes.date_start+' - '+attributes.date_end+'</p>').appendTo(details);
    }
	jQuery('<p class=\"collection-name\">'+attributes.location_name+'</p>').appendTo(details);
	var locality = jQuery('<p  class=\"collection-locality\"></p>').appendTo(details);
	var scope = {target: locality};
	inseeProtocol.read({filter: filter, callback: test, scope: scope, async: false});
    $.getJSON(\"".$svcUrl."/data/sample\" + 
    		\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&parent_id=\"+attributes.collection_id+\"&callback=?\", function(sessiondata) {
		for (var i=0;i<sessiondata.length;i++){
			$.getJSON(\"".$svcUrl."/data/occurrence/\" +
					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&orderby=id\" +
					\"&sample_id=\"+sessiondata[i].id+\"&callback=?\", function(insectData) {
		    	if (insectData.length>0) {
 					for (var j=0;j<insectData.length;j++){
						var container = jQuery('<div/>').addClass('thumb').attr('occId', insectData[j].id.toString()).click(function () {loadInsect(jQuery(this).attr('occId'));});
						jQuery('<span>".lang::get('LANG_Unknown')."</span>').addClass('thumb-text').appendTo(container);
						photoReel.append(container);
						$.getJSON(\"".$svcUrl."/data/occurrence_image\" +
					   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   								\"&occurrence_id=\" + insectData[j].id + \"&callback=?\", function(imageData) {
							  if (imageData.length>0) {
						      	var container = jQuery('.thumb').filter('[occId='+imageData[0].occurrence_id.toString()+']');
							    var img = new Image();
								jQuery(img).attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."thumb-'+imageData[0].path)
			    					.attr('width', container.width()).attr('height', container.height()).addClass('thumb-image').appendTo(container);
							  }}); 
						$.getJSON(\"".$svcUrl."/data/determination\" + 
					    		\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
					    		\"&occurrence_id=\" + insectData[j].id + \"&deleted=f&callback=?\", function(detData) {
						      if (detData.length>0) {
						        var container = jQuery('.thumb').filter('[occId='+detData[0].occurrence_id.toString()+']');
						        container.find('.thumb-text').remove();
						        if(detData[detData.length-1].dubious == 'Y'){
									jQuery('<span>".lang::get('LANG_Dubious')."</span>').addClass('thumb-text').appendTo(container);
							  	}
  							  }});  					
					}
				}});
		}});
};
addInsect = function(attributes){
	var container=jQuery('<div class=\"ui-widget-content ui-corner-all filter-insect\" />').appendTo('#results-insects-results');
	var flag = jQuery('<div />').addClass('insect-ok').appendTo(container);
	var insect = jQuery('<div class=\"insect-image\" />').attr('occID', attributes.insect_id).click(function(){
		loadInsect(jQuery(this).attr('occID'));
	});
	insect.appendTo(container);
	var img = new Image();
	$(img).load(function () {insect.append(this);})
	    .attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."med-'+attributes.insect_image_path)
		.css('max-width', insect.width()).css('max-height', insect.width()*imageRatio)
	    .css('vertical-align', 'middle').css('margin-left', 'auto').css('margin-right', 'auto').css('display', 'block');
	$.getJSON(\"".$svcUrl."/data/determination\" +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&occurrence_id=\" + attributes.insect_id + \"&callback=?\", function(detData) {
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
				string = (string == '' ? '' : string + ', ') + detData[i].taxon_extra_info;
			}
			jQuery('<p>'+string+'</p>').appendTo(container)
			if(detData[i].dubious == 'Y'){
				jQuery(flag).removeClass('insect-ok').addClass('insect-dubious');
			}
		} else {
			jQuery(flag).removeClass('insect-ok').addClass('insect-unknown');
			jQuery(\"<p>".lang::get('LANG_No_Determinations')."</p>\")
					.appendTo(container);
		}
	});
	var displayButton = jQuery('<div class=\"ui-state-default ui-corner-all display-button\">".lang::get('LANG_Display')."</div>');
	displayButton.click(function(){
		loadInsect(jQuery(this).attr('value'));
	}).appendTo(container).attr('value',attributes.insect_id);
};


setCollectionPage = function(pageNum){
	jQuery('#results-collections-results').empty();
	var numPages = Math.ceil(searchResults.features.length/".$args['collectionsPerPage'].");
	if(numPages > 1) {
		var pageCtrl = jQuery('<div>').addClass('page-control').appendTo('#results-collections-results');
		var pageCtrl2 = jQuery('<div>').addClass('page-control');
		var first = true;
		for (var j = (pageNum < 6  ? 1 : pageNum - 5); j <= numPages && j <= (pageNum + 5); j++){
			if (first != true){
				jQuery('<span>|</span>').appendTo(pageCtrl);
				jQuery('<span>|</span>').appendTo(pageCtrl2);
  			}
  			first = false;
			if( j != pageNum) {
				jQuery('<a>'+j+'</a>').attr('value',j).click(function(){setCollectionPage(jQuery(this).attr('value'))}).appendTo(pageCtrl);
				jQuery('<a>'+j+'</a>').attr('value',j).click(function(){setCollectionPage(jQuery(this).attr('value'))}).appendTo(pageCtrl2);
  			} else {
				jQuery('<span>'+j+'</span>').appendTo(pageCtrl);
				jQuery('<span>'+j+'</span>').appendTo(pageCtrl2);
  			}
	    }
	}
    for (var i = (pageNum-1)*".$args['collectionsPerPage']."; i < searchResults.features.length && i < pageNum*".$args['collectionsPerPage']."; i++){
		addCollection(searchResults.features[i].attributes,searchResults.features[i].geometry);
	}
	if(numPages > 1) {
		pageCtrl2.appendTo('#results-collections-results');
	}
}
setInsectPage = function(pageNum){
	jQuery('#results-insects-results').empty();
	var numPages = Math.ceil(searchResults.features.length/".$args['insectsPerPage'].");
	if(numPages > 1) {
		var pageCtrl = jQuery('<div>').addClass('page-control').appendTo('#results-insects-results');
		var pageCtrl2 = jQuery('<div>').addClass('page-control');
		var first = true;
		for (var j = (pageNum < 6  ? 1 : pageNum - 5); j <= numPages && j <= (pageNum + 5); j++){
			if (first != true){
				jQuery('<span>|</span>').appendTo(pageCtrl);
				jQuery('<span>|</span>').appendTo(pageCtrl2);
  			}
  			first = false;
			if( j != pageNum) {
				jQuery('<a>'+j+'</a>').attr('value',j).click(function(){setInsectPage(jQuery(this).attr('value'))}).appendTo(pageCtrl);
				jQuery('<a>'+j+'</a>').attr('value',j).click(function(){setInsectPage(jQuery(this).attr('value'))}).appendTo(pageCtrl2);
  			} else {
				jQuery('<span>'+j+'</span>').appendTo(pageCtrl);
				jQuery('<span>'+j+'</span>').appendTo(pageCtrl2);
  			}
	    }
	}
    for (var i = (pageNum-1)*".$args['insectsPerPage']."; i < searchResults.features.length && i < pageNum*".$args['insectsPerPage']."; i++){
		addInsect(searchResults.features[i].attributes);
	}
	if(numPages > 1) {
		pageCtrl2.appendTo('#results-insects-results');
	}
}

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
inseeProtocol = new OpenLayers.Protocol.WFS({
              url:  '".str_replace("{HOST}", $_SERVER['HTTP_HOST'], $args['INSEE_url'])."',
              featurePrefix: '".$args['INSEE_prefix']."',
              featureType: '".$args['INSEE_type']."',
              geometryName:'the_geom',
              featureNS: '".$args['INSEE_ns']."',
              srsName: 'EPSG:900913',
              version: '1.1.0'                  
      		  ,propertyNames: ['NOM', 'INSEE_NEW', 'DEPT_NUM', 'DEPT_NOM', 'REG_NUM', 'REG_NOM']
  			});


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
              url:  '".str_replace("{HOST}", $_SERVER['HTTP_HOST'], $args['INSEE_url'])."',
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
	inseeLayer.events.register('loadend', {}, function(){
		if(inseeLayer.features.length == 0){
			alert(\"".lang::get('LANG_NO_INSEE')."\");
		}
    });
    jQuery('#map')[0].map.addLayer(inseeLayer);
	strategy.load({filter: new OpenLayers.Filter.Logical({
			      type: OpenLayers.Filter.Logical.OR,
			      filters: filters
		  	  })});
});

jQuery('#search-collections-button').click(function(){
	jQuery('#results-insects-header,#results-insects-results').addClass('filter-hide');
	jQuery('#results-collections-header,#results-collections-results').removeClass('filter-hide');
	jQuery('#results-collections-header').addClass('ui-state-active').removeClass('ui-state-default');
	runSearch(true);
});
jQuery('#search-insects-button').click(function(){
	jQuery('#results-collections-header,#results-collections-results').addClass('filter-hide');
	jQuery('#results-insects-header,#results-insects-results').removeClass('filter-hide');
	jQuery('#results-insects-header').addClass('ui-state-active').removeClass('ui-state-default');
	runSearch(false);
});

runSearch = function(forCollections){
  	var ORgroup = [];
	
  	if(searchLayer != null)
		searchLayer.destroy();
		
	var use_insects = false;
    jQuery('#results-collections-results,#results-insects-results').empty();
	jQuery('#focus-occurrence,#focus-collection').addClass('filter-hide');
	var filters = [];
	// By default restrict selection to area displayed on map. When using the georeferencing system the map searchLayer
	// will contain a single point zoomed in appropriately.
	var mapBounds = jQuery('#map')[0].map.getExtent();
	if (mapBounds != null){
	  filters.push(new OpenLayers.Filter.Spatial({
    	type: OpenLayers.Filter.Spatial.BBOX,
    	property: 'geom',
    	value: mapBounds
  	  }));
  	}
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
  	// Only deal with completed collections on our survey.
  	filters.push(new OpenLayers.Filter.Comparison({
  		type: OpenLayers.Filter.Comparison.LIKE,
    	property: 'collection_attributes',
    	value: '*{|".$args['complete_attr_id']."|,1}*'
  	}));
  	filters.push(new OpenLayers.Filter.Comparison({
  		type: OpenLayers.Filter.Comparison.EQUAL_TO,
    	property: 'survey_id',
    	value: '".$args['survey_id']."'
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
    		property: forCollections ? 'insects' : 'insect_taxon',
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
              url: '".str_replace("{HOST}", $_SERVER['HTTP_HOST'], $args['search_url'])."',
              featurePrefix: '".$args['search_prefix']."',
              featureType: forCollections ? (use_insects ? 'poll_collection_insects' : 'poll_collections') : 'poll_insects',
              geometryName:'geom',
              featureNS: '".$args['search_ns']."',
              srsName: 'EPSG:900913',
              version: '1.1.0',   
              maxFeatures: ".$args['max_features'].",               
//      		  propertyNames: forCollections ? ['collection_id','date_start','date_end','geom','location_name','location_image_path','flower_image_path','flower_id','flower_taxon','collection_attributes','location_attributes','flower_attributes']
              propertyNames: forCollections ? ['collection_id','date_start','date_end','geom','location_name','location_image_path','flower_image_path','flower_id','flower_taxon','collection_attributes','location_attributes','flower_attributes']
      		  							: ['insect_id','collection_id','geom','insect_image_path']
		  })
	});
	if(forCollections) {
		searchLayer.events.register('featuresadded', {}, function(a1){
			searchResults = a1;
			if(searchResults.features.length >= ".$args['max_features']."){
				alert(\"".lang::get('LANG_Max_Features_Reached')."\");
			}
			setCollectionPage(1);
		});
	} else {
		searchLayer.events.register('featuresadded', {}, function(a1){
			searchResults = a1;
			if(searchResults.features.length >= ".$args['max_features']."){
				alert(\"".lang::get('LANG_Max_Features_Reached')."\");
			}
			setInsectPage(1);
		});
	}
	jQuery('#map')[0].map.addLayer(searchLayer);
	strategy.load({filter: new OpenLayers.Filter.Logical({
			      type: OpenLayers.Filter.Logical.AND,
			      filters: filters
		  	  })});
};

searchResults = null;  
collection = '';

jQuery('form#fo-doubt-form').ajaxForm({ 
	dataType:  'json', 
	success:   function(data){
		if(data.error == undefined){
			loadDeterminations(jQuery('[name=determination\\:occurrence_id]').val(), '#fo-id-history', '#fo-current-id');
			jQuery('[name=occurrence_comment\\:comment]').val(\"".lang::get('LANG_Doubt_Comment')."\");
			jQuery('#fo-new-comment-button').click();
		} else {
			alert(data.error);
		}
	} 
});
jQuery('form#fo-new-insect-id-form').ajaxForm({ 
	dataType:  'json', 
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
	dataType:  'json', 
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
			loadComments(jQuery('[name=occurrence_comment\\:occurrence_id]').val(), '#fo-comment-list', 'occurrence_comment', 'occurrence_id', 'occurrence-comment-block', 'occurrence-comment-body');
  		} else {
			alert(data.error);
		}
	} 
});
jQuery('#fc-new-comment-form').ajaxForm({ 
	dataType:  'json', 
	beforeSubmit:   function(data, obj, options){
		if (!jQuery('form#fc-new-comment-form').valid()) { return false; }
		return true;
	},
	success:   function(data){
		if(data.error == undefined){
			jQuery('[name=sample_comment\\:comment]').val('');
			jQuery('#fc-new-comment').removeClass('ui-accordion-content-active');
			loadComments(jQuery('[name=sample_comment\\:sample_id]').val(), '#fc-comment-list', 'sample_comment', 'sample_id', 'sample-comment-block', 'sample-comment-body');
  		} else {
			alert(data.error);
		}
	} 
});

loadSampleAttributes = function(keyValue){
	jQuery('#fo-insect-start-time,#fo-insect-end-time,#fo-insect-sky,#fo-insect-temp,#fo-insect-wind,#fo-insect-shade').empty();
	$.getJSON(\"".$svcUrl."/data/sample_attribute_value\"  +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&sample_id=\" + keyValue + \"&callback=?\", function(attrdata) {
		if (attrdata.length>0) {
			for(i=0; i< attrdata.length; i++){
				if (attrdata[i].id && (attrdata[i].iso == null || attrdata[i].iso == '' || attrdata[i].iso == '".$language."')){
					switch(parseInt(attrdata[i].sample_attribute_id)){
						case ".$args['start_time_attr_id'].":
							jQuery('#fo-insect-start-time').append(attrdata[i].value);
							break;
						case ".$args['end_time_attr_id'].":
							jQuery('#fo-insect-end-time').append(attrdata[i].value);
							break;
  						case ".$args['sky_state_attr_id'].":
							jQuery('#fo-insect-sky').append(attrdata[i].value);
							break;
  						case ".$args['temperature_attr_id'].":
							jQuery('#fo-insect-temp').append(attrdata[i].value);
							break;
  						case ".$args['wind_attr_id'].":
							jQuery('#fo-insect-wind').append(attrdata[i].value);
							break;
  						case ".$args['shade_attr_id'].":
							jQuery('#fo-insect-shade').append(attrdata[i].value);
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
   			\"&occurrence_id=\" + keyValue + \"&iso=".$language."&callback=?\", function(attrdata) {
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
   			\"&location_id=\" + keyValue + \"&iso=".$language."&callback=?\", function(attrdata) {
		if (attrdata.length>0) {
			for(i=0; i< attrdata.length; i++){
				if (attrdata[i].id){
					switch(parseInt(attrdata[i].location_attribute_id)){
						case ".$args['habitat_attr_id'].":
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
	jQuery('#fo-taxon').empty();
	jQuery('#fo-warning').addClass('insect-ok').removeClass('insect-dubious').removeClass('insect-unknown');
	$.getJSON(\"".$svcUrl."/data/determination\" +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&occurrence_id=\" + keyValue + \"&callback=?\", function(detData) {
   		if (detData.length>0) {
			var i = detData.length-1;
   			jQuery('#fo-doubt-form').find('[name=determination\\:id]').val(detData[i].id);
   			jQuery('#fo-doubt-form').find('[name=determination\\:occurrence_id]').val(detData[i].occurrence_id);
   			var string = '';
			if(detData[i].taxon != '' && detData[i].taxon != null){
				string = detData[i].taxon;
				jQuery('#fo-taxon').append(detData[i].taxon);
  			}
			if(detData[i].taxon_text_description != '' && detData[i].taxon_text_description != null){
				string = (string == '' ? '' : string + ', ') + detData[i].taxon_text_description;
			}
			if(detData[i].taxon_extra_info != '' && detData[i].taxon_extra_info != null){
				string = (string == '' ? '' : string + ', ') + detData[i].taxon_text_description;
			}
			jQuery('<p><strong>'+string+ '</strong> ".lang::get('LANG_Comment_By')."' + detData[i].person_name + ' ' + detData[i].updated_on + '</p>').appendTo(currentID)
			if(detData[i].dubious == 'Y'){
				jQuery(\"<p>".lang::get('LANG_Doubt_Expressed')."</p>\").appendTo(currentID)
				jQuery('#fo-warning').removeClass('insect-ok').addClass('insect-dubious');
			}
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
			jQuery('#fo-doubt-button').hide();
			jQuery('#fo-warning').removeClass('insect-ok').addClass('insect-unknown');
			jQuery('<p>".lang::get('LANG_No_Determinations')."</p>')
					.appendTo(historyID);
			jQuery('<p>".lang::get('LANG_No_Determinations')."</p>')
					.appendTo(currentID);
		}
	});
};
loadComments = function(keyValue, block, table, key, blockClass, bodyClass){
	jQuery(block).empty();
	$.getJSON(\"".$svcUrl."/data/\" + table +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&\" + key + \"=\" + keyValue + \"&callback=?\", function(commentData) {
   		if (commentData.length>0) {
   			for(i=commentData.length - 1; i >= 0; i--){
	   			var newCommentDetails = jQuery('<div class=\"'+blockClass+'\"/>')
					.appendTo(block);
				jQuery('<span>".lang::get('LANG_Comment_By')."' + commentData[i].person_name + ' ' + commentData[i].updated_on + '</span>')
					.appendTo(newCommentDetails);
	   			var newComment = jQuery('<div class=\"'+bodyClass+'\"/>')
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
	collection = '';
	jQuery('#fo-prev-button,#fo-next-button').hide();
	
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
					jQuery('#fo-insect-date').empty().append(smpData[0].date_start);
					loadSampleAttributes(smpData[0].id);
					jQuery('#fo-collection-button').attr('smpID',smpData[0].parent_id).show();
					$.getJSON(\"".$svcUrl."/data/sample/\" +
   							\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   							\"&parent_id=\" + smpData[0].parent_id + \"&callback=?\", function(smpList) {
   						if (smpList.length > 0) {
   							for(j=0; j< smpList.length; j++){
		   						$.getJSON(\"".$svcUrl."/data/occurrence\" +
   										\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   										\"&sample_id=\" + smpList[j].id + \"&orderby=id&callback=?\", function(occList) {
	   								if(occList.length > 0){
   										for(i=0; i< occList.length; i++){
   											if(parseInt(occList[i].id) == parseInt(keyValue)){
   												if(i>0){
													jQuery('#fo-prev-button').attr('occID',occList[i-1].id).show();
  												}
   												if(i< occList.length-1){
													jQuery('#fo-next-button').attr('occID',occList[i+1].id).show();
  												}
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
			jQuery('#fo-collection-button').attr('smpID',occData[0].sample_id).show();
   			$.getJSON(\"".$svcUrl."/data/sample/\" + occData[0].sample_id +
   					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   					\"&callback=?\", function(collection) {
   				if (collection.length > 0) {
					loadLocationAttributes(collection[0].location_id);
  				}
   		   	});
   		}
   	});
}

loadInsect = function(insectID){
    jQuery('#focus-collection,#filter,#fo-flower-addn-info').addClass('filter-hide');
    jQuery('#filter-header').removeClass('ui-state-active').addClass('ui-state-default').removeClass('ui-corner-top').addClass('ui-corner-all');
    jQuery('#focus-occurrence,#fo-addn-info-header,#fo-insect-addn-info').removeClass('filter-hide');
	jQuery('[name=determination\\:occurrence_id]').val(insectID);
	jQuery('[name=occurrence_comment\\:occurrence_id]').val(insectID);
	jQuery('#fo-new-comment,#fo-new-id').removeClass('ui-accordion-content-active');
	jQuery('#fo-new-insect-id-button').show();
	jQuery('#fo-new-flower-id-button').hide();
	jQuery('#fo-doubt-button').".((user_access('IForm n'.$node->nid.' insect expert') || user_access('IForm n'.$node->nid.' flag dubious insect')) ? "show()" : "hide()").";
	jQuery('#fo-new-comment-button').".((user_access('IForm n'.$node->nid.' insect expert') || user_access('IForm n'.$node->nid.' create insect comment')) ? "show()" : "hide()").";
	loadImage('occurrence_image', 'occurrence_id', insectID, '#fo-image');
	loadDeterminations(insectID, '#fo-id-history', '#fo-current-id');
	loadInsectAddnInfo(insectID);
	loadComments(insectID, '#fo-comment-list', 'occurrence_comment', 'occurrence_id', 'occurrence-comment-block', 'occurrence-comment-body');
}
loadFlower = function(flowerID){
	jQuery('#fo-prev-button,#fo-next-button').hide();
	jQuery('#focus-collection,#filter,#fo-insect-addn-info').addClass('filter-hide');
    jQuery('#filter-header').removeClass('ui-state-active').addClass('ui-state-default').removeClass('ui-corner-top').addClass('ui-corner-all');
	jQuery('#focus-occurrence,#fo-addn-info-header,#fo-flower-addn-info').removeClass('filter-hide');
	jQuery('#fo-new-comment,#fo-new-id').removeClass('ui-accordion-content-active');
	jQuery('[name=determination\\:occurrence_id]').val(flowerID);
	jQuery('[name=occurrence_comment\\:occurrence_id]').val(flowerID);
	jQuery('#fo-new-flower-id-button').show();
	jQuery('#fo-new-insect-id-button').hide();
	jQuery('#fo-doubt-button').".((user_access('IForm n'.$node->nid.' flower expert') || user_access('IForm n'.$node->nid.' flag dubious flower')) ? "show()" : "hide()").";
	jQuery('#fo-new-comment-button').".((user_access('IForm n'.$node->nid.' flower expert') || user_access('IForm n'.$node->nid.' create flower comment')) ? "show()" : "hide()").";
	loadImage('occurrence_image', 'occurrence_id', flowerID, '#fo-image');
	loadDeterminations(flowerID, '#fo-id-history', '#fo-current-id');
	loadFlowerAddnInfo(flowerID);
	loadComments(flowerID, '#fo-comment-list', 'occurrence_comment', 'occurrence_id', 'occurrence-comment-block', 'occurrence-comment-body');
}

jQuery('#fo-new-comment-button').click(function(){ 
	jQuery('#fo-new-comment').toggleClass('ui-accordion-content-active');
});
jQuery('#fc-new-comment-button').click(function(){ 
	jQuery('#fc-new-comment').toggleClass('ui-accordion-content-active');
});
jQuery('#fo-new-insect-id-button').click(function(){ 
	jQuery('#fo-new-insect-id').toggleClass('ui-accordion-content-active');
});
jQuery('#fo-new-flower-id-button').click(function(){ 
	jQuery('#fo-new-flower-id').toggleClass('ui-accordion-content-active');
});
jQuery('#fo-collection-button').click(function(){
	loadCollection(jQuery(this).attr('smpID'));
});
jQuery('#fo-prev-button').click(function(){
	loadInsect(jQuery(this).attr('occID'));
});
jQuery('#fo-next-button').click(function(){
	loadInsect(jQuery(this).attr('occID'));
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
		    data_entry_helper::$onload_javascript .= "loadInsect(".$occID.");
			";
		    break;
    	case 'FLOWER':
		    data_entry_helper::$onload_javascript .= "loadFlower(".$occID.");
			";
		    break;
		case 'COLLECTION':
		    data_entry_helper::$onload_javascript .= "
    		jQuery('#focus-occurrence,#filter-spec,#filter-footer,#results-insects-header,#results-collections-header,#results-insects-results,#results-collections-results').addClass('filter-hide');
    		jQuery('#filter-header').removeClass('ui-state-active').addClass('ui-state-default').removeClass('ui-corner-top').addClass('ui-corner-all');
    		loadCollection(".$smpID.");
    		";
    		break;
    	default:
    		data_entry_helper::$onload_javascript .= "
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