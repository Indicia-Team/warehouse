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
     iform_map_get_georef_parameters(),
     array(
       array(
         'name'=>'2nd_map_height',
         'caption'=>'Second Map Height (px)',
         'description'=>'Height in pixels of the second (focus on collection) map.',
         'type'=>'int',
         'group'=>'Initial Map View',
         'default'=>300
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
          'name'=>'insectsPerRow',
          'caption'=>'Number of Insects per row.',
          'description'=>'Number of Insects per row of search results and on collection insect list.',
          'type'=>'int',
          'default'=>3,
          'group'=>'Search'
      ),
      array(
          'name'=>'insectsRowsPerPage',
          'caption'=>'Number of rows of insects per page of search results',
          'description'=>'Number of rows of insects per page of search results.',
          'type'=>'int',
          'default'=>3,
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
          'name'=>'uid_attr_id',
          'caption'=>'User ID Attribute ID',      
          'description'=>'Indicia ID for the sample attribute that stores the CMS User ID.',
          'type'=>'smpAttr',
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
          'name'=>'flower_type_dont_know',
          'caption'=>'Flower Type Dont Know ID',      
          'description'=>'Indicia ID for the term meaning_id that shows a Flower type of dont know.',
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
      ),
      array(
          'name'=>'ID_tool_flower_url',
          'caption'=>'Flower ID Tool URL',
          'description'=>'The URL to call which triggers the Flower Identification Tool functionality.',
          'type'=>'string',
          'group'=>'ID Tool',
      	  'default'=>'http://spipoll.org/identification/flore.php?requestId='
      ),
      array(
          'name'=>'ID_tool_flower_poll_dir',
          'caption'=>'Flower ID Tool Module poll directory',
          'description'=>'The directory which to poll for the results of the Flower ID Tool',
          'type'=>'string',
          'group'=>'ID Tool',
      	  'default'=>'http://{HOST}/cgi-bin/proxy.cgi?url=http://ns367998.ovh.net/identification/resultats/flore/'
      ),
      array(
          'name'=>'ID_tool_insect_url',
          'caption'=>'Insect ID Tool URL',
          'description'=>'The URL to call which triggers the Insect Identification Tool functionality.',
          'type'=>'string',
          'group'=>'ID Tool',
      	  'default'=>'http://spipoll.org/identification/insectes.php?requestId='
      ),
      array(
          'name'=>'ID_tool_insect_poll_dir',
          'caption'=>'Insect ID Tool Module poll directory',
          'description'=>'The directory which to poll for the results of the Insect ID Tool',
          'type'=>'string',
          'group'=>'ID Tool',
      	  'default'=>'http://{HOST}/cgi-bin/proxy.cgi?url=http://ns367998.ovh.net/identification/resultats/insectes/'
      ),
      array(
          'name'=>'ID_tool_poll_interval',
          'caption'=>'Time in ms between polls of results directory',
          'description'=>'Time in ms between polls of results directory',
          'type'=>'int',
          'group'=>'ID Tool',
          'default'=>1500,
      ),
      array(
          'name'=>'ID_tool_poll_timeout',
          'caption'=>'Time in ms before the ID Tool is aborted.',
          'description'=>'Time in ms before the ID Tool is aborted.',
          'type'=>'int',
          'group'=>'ID Tool',
          'default'=>1800000,
      ),
      array(
          'name'=>'Flower_Image_Ratio',
          'caption'=>'Flower image aspect ratio.',
          'description'=>'Expected Ratio of width to height for flower images - 4/3 is horizontal, 3/4 is vertical.',
          'type'=>'string',
          'group'=>'Images',
          'default'=>'4/3'
      ),
      array(
          'name'=>'Environment_Image_Ratio',
          'caption'=>'Environment image aspect ratio.',
          'description'=>'Expected Ratio of width to height for environment images - 4/3 is horizontal, 3/4 is vertical.',
          'type'=>'string',
          'group'=>'Images',
          'default'=>'4/3'
      ),
      array(
          'name'=>'Insect_Image_Ratio',
          'caption'=>'Insect image aspect ratio.',
          'description'=>'Expected Ratio of width to height for insect images - 4/3 is horizontal, 3/4 is vertical.',
          'type'=>'string',
          'group'=>'Images',
          'default'=>'1/1'
      ),
      array(
          'name'=>'alert_js_function',
          'caption'=>'Alert JS Function',
          'description'=>'JS function called when an alert is generated',
          'type'=>'string',
          'group'=>'JS Calls',
      	  'required'=>false
      ),
      array(
          'name'=>'preferred_js_function',
          'caption'=>'Preferred JS Function',
          'description'=>'JS function called when an object is to be added to the users preferred list',
          'type'=>'string',
          'group'=>'JS Calls',
      	  'required'=>false
      )
    ));
    return $retVal;
  	
  }

  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_pollenator_gallery_definition() {
    return array(
      'title'=>self::get_title(),
      'category' => 'SPIPOLL forms',      
      'description'=>'Pollenators: Gallery Filter and Focus on Collection, Insect and Flower.'
    );
  }

  /**
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'Pollenators: Gallery';
  }

  public static function get_perms($nid) {
    return array('IForm n'.$nid.' access',
    			'IForm n'.$nid.' flower expert',
    			'IForm n'.$nid.' flag dubious flower',
    			'IForm n'.$nid.' create flower comment',
    			'IForm n'.$nid.' insect expert',
    			'IForm n'.$nid.' flag dubious insect',
    			'IForm n'.$nid.' create insect comment',
    			'IForm n'.$nid.' create collection comment',
    			'IForm n'.$nid.' save filter',
    			'IForm n'.$nid.' add preferred collection'
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
    $language = iform_lang_iso_639_2($args['language']);
	
	drupal_add_js(drupal_get_path('module', 'iform') .'/media/js/jquery.form.js', 'module');
	data_entry_helper::link_default_stylesheet();
	data_entry_helper::add_resource('jquery_ui');
	if($args['language'] != 'en')
		data_entry_helper::add_resource('jquery_ui_'.$args['language']);
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
    $defAttrOptions = array('extraParams'=>$readAuth + array('orderby' => 'id'),
    				'lookUpListCtrl' => 'checkbox_group',
    				'lookUpKey' => 'meaning_id',
    				'booleanCtrl' => 'checkbox_group',
       				'sep' => ' &nbsp; ',
    				'language' => $language,
    				'suffixTemplate'=>'nosuffix',
    				'default'=>'-1');
    
	// note we have to proxy the post. Every time a write transaction is carried out, the write nonce is trashed.
	// For security reasons we don't want to give the user the ability to generate their own nonce, so we use
	// the fact that the user is logged in to drupal as the main authentication/authorisation/identification
	// process for the user. The proxy also packages the post into the correct format	

    // the controls for the filter include all taxa, not just the ones allowed for data entry, as does the one for checking the tool, just to be on the safe side.
	$flower_ctrl_args=array(
    	    'label'=>lang::get('LANG_Flower_Species'),
        	'fieldname'=>'flower:taxa_taxon_list_id',
	        'table'=>'taxa_taxon_list',
    	    'captionField'=>'taxon',
        	'valueField'=>'id',
	        'columns'=>2,
    		'blankText'=>lang::get('LANG_Choose_Taxon'),
    	    'extraParams'=>$readAuth + array('taxon_list_id' => $args['flower_list_id'], 'view'=>'detail','orderby'=>'taxonomic_sort_order'),
			'suffixTemplate'=>'nosuffix'
	);
	$focus_flower_ctrl_args = $flower_ctrl_args;
	$focus_flower_ctrl_args['fieldname'] = 'determination:taxa_taxon_list_id';
	$focus_flower_ctrl_args['extraParams'] = $readAuth + array('taxon_list_id' => $args['flower_list_id'], 'view'=>'detail','orderby'=>'taxonomic_sort_order', 'allow_data_entry'=>'t');
	$insect_ctrl_args=array(
    	    'label'=>lang::get('LANG_Insect_Species'),
        	'fieldname'=>'insect:taxa_taxon_list_id',
	        'table'=>'taxa_taxon_list',
    	    'captionField'=>'taxon',
        	'valueField'=>'id',
	        'columns'=>2,
    		'blankText'=>lang::get('LANG_Choose_Taxon'),
    	    'extraParams'=>$readAuth + array('taxon_list_id' => $args['insect_list_id'], 'view'=>'detail','orderby'=>'taxonomic_sort_order'),
			'suffixTemplate'=>'nosuffix'
	);
	$focus_insect_ctrl_args = $insect_ctrl_args;
	$focus_insect_ctrl_args['fieldname'] = 'determination:taxa_taxon_list_id';
	$focus_insect_ctrl_args['extraParams'] = $readAuth + array('taxon_list_id' => $args['insect_list_id'], 'view'=>'detail','orderby'=>'taxonomic_sort_order', 'allow_data_entry'=>'t');
	$options = iform_map_get_map_options($args, $readAuth);
	$olOptions = iform_map_get_ol_options($args);
    // The maps internal projection will be left at its default of 900913.
	
    $options['initialFeatureWkt'] = null;
    $options['proxy'] = '';
    $options['suffixTemplate'] = 'nosuffix';
    if( lang::get('msgGeorefSelectPlace') != 'msgGeorefSelectPlace')
    	$options['msgGeorefSelectPlace'] = lang::get('msgGeorefSelectPlace');
    if( lang::get('msgGeorefNothingFound') != 'msgGeorefNothingFound')
    	$options['msgGeorefNothingFound'] = lang::get('msgGeorefNothingFound');
    
    $options2 = $options;
    $options['searchLayer'] = 'true';
    $options['editLayer'] = 'false';
    $options['layers'] = array('polygonLayer');
    
	$options2['divId'] = "map2";
    $options2['layers'] = array('locationLayer');
    $options2['height'] = $args['2nd_map_height'];

    data_entry_helper::$javascript .= "var flowerTaxa = [";
	$extraParams = $readAuth + array('taxon_list_id' => $args['flower_list_id'], 'view'=>'list');
    $species_data_def=array('table'=>'taxa_taxon_list','extraParams'=>$extraParams);
	$taxa = data_entry_helper::get_population_data($species_data_def);
	$first = true;
	foreach ($taxa as $taxon) {
		data_entry_helper::$javascript .= ($first ? '' : ',')."{id: ".$taxon['id'].", taxon: \"".htmlSpecialChars($taxon['taxon'])."\"}\n";
		$first=false;
	}
    data_entry_helper::$javascript .= "];\nvar insectTaxa = [";
    $extraParams['taxon_list_id'] = $args['insect_list_id'];
    $species_data_def['extraParams']=$extraParams;
	$taxa = data_entry_helper::get_population_data($species_data_def);
	$first = true;
	foreach ($taxa as $taxon) {
		data_entry_helper::$javascript .= ($first ? '' : ',')."{id: ".$taxon['id'].", taxon: \"".htmlSpecialChars($taxon['taxon'])."\"}\n";
		$first=false;
	}
    data_entry_helper::$javascript .= "];";
    // TBD Breadcrumb
 	$r .= '<h1 id="poll-banner">'.lang::get('LANG_Main_Title').'</h1>
<div id="refresh-message" style="display:none" ><p>'.lang::get('LANG_Please_Refresh_Page').'</p></div>
<div id="filter" class="ui-accordion ui-widget ui-helper-reset">
	<div id="filter-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-accordion-content-active ui-corner-top">
	  	<div id="results-collections-title">
	  		<span>'.lang::get('LANG_Filter_Title').'</span>
    	</div>
	</div>';
 	if(user_access('IForm n'.$node->nid.' save filter')){
    	$r .= '<div id="filter-save" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active"><div id="gallery-filter-retrieve-wrapper">
<div id="gallery-filter-retrieve-image"><img
src="/'. path_to_theme() .'/css/gallery_filter.png" 
alt="Mes filtres" title="Mes filtres" /></div> <div id="gallery-filter-retrieve"></div>
</div>
   <input value="'.lang::get('LANG_Enter_Filter_Name').'" type="text" id="gallery-filter-save-name" /><input value="'.lang::get('LANG_Save_Filter_Button').'" type="button" id="gallery-filter-save-button" /></div>';
    }
 	$r .= '<div id="filter-spec" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active">
	  <div class="ui-accordion ui-widget ui-helper-reset">
		<div id="name-filter-header" class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all">
	  		<div id="fold-name-button" class="ui-state-default ui-corner-all fold-button fold-button-folded">&nbsp;</div>
	  		<div id="reset-name-button" class="ui-state-default ui-corner-all reset-button">'.lang::get('LANG_Reset_Filter').'</div>
	  		<div id="general-filter-title">
		  		<span>'.lang::get('LANG_Name_Filter_Title').'</span>
      		</div>
		</div>
	    <div id="name-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all">
	        '.data_entry_helper::text_input(array('label'=>lang::get('LANG_Name'),'fieldname'=>'username', 'suffixTemplate'=>'nosuffix')).'
  		</div>
		<div id="date-filter-header" class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all">
	  		<div id="fold-date-button" class="ui-state-default ui-corner-all fold-button fold-button-folded">&nbsp;</div>
	  		<div id="reset-date-button" class="ui-state-default ui-corner-all reset-button">'.lang::get('LANG_Reset_Filter').'</div>
	  		<div id="general-filter-title">
		  		<span>'.lang::get('LANG_Date_Filter_Title').'</span>
      		</div>
		</div>
	    <div id="date-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all">
        	<label for="start_date" >'.lang::get('LANG_Created_Between').':</label>
  			<input type="text" size="10" id="start_date" name="start_date" value="'.lang::get('click here').'" />
  			<input type="hidden" id="real_start_date" name="real_start_date" />
  			<label for="end_date" >'.lang::get('LANG_And').':</label>
  			<input type="text" size="10" id="end_date" name="end_date" value="'.lang::get('click here').'" />
  			<input type="hidden" id="real_end_date" name="real_end_date" />
  		</div>
  		<div id="flower-filter-header" class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all">
	  		<div id="fold-flower-button" class="ui-state-default ui-corner-all fold-button fold-button-folded">&nbsp;</div>
	  		<div id="reset-flower-button" class="ui-state-default ui-corner-all reset-button">'.lang::get('LANG_Reset_Filter').'</div>
	  		<div id="flower-filter-title">
		  		<span>'.lang::get('LANG_Flower_Filter_Title').'</span>
      		</div>
		</div>
		<div id="flower-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all">
		  '.data_entry_helper::select($flower_ctrl_args).'
 		  <input type="text" name="flower:taxon_extra_info" class="taxon-info" value="'.lang::get('LANG_More_Precise').'"
	 		onclick="if(this.value==\''.lang::get('LANG_More_Precise').'\'){this.value=\'\'; this.style.color=\'#000\'}"  
            onblur="if(this.value==\'\'){this.value=\''.lang::get('LANG_More_Precise').'\'; this.style.color=\'#555\'}" />
		  '.str_replace("\n", "", data_entry_helper::outputAttribute($occurrence_attributes[$args['flower_type_attr_id']], $defAttrOptions))
    	  .str_replace("\n", "", data_entry_helper::outputAttribute($location_attributes[$args['habitat_attr_id']], $defAttrOptions)).'
    	</div>
		<div id="insect-filter-header" class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all">
	  		<div id="fold-insect-button" class="ui-state-default ui-corner-all fold-button fold-button-folded">&nbsp;</div>
			<div id="reset-insect-button" class="ui-state-default ui-corner-all reset-button">'.lang::get('LANG_Reset_Filter').'</div>
	  		<div id="insect-filter-title">
		  		<span>'.lang::get('LANG_Insect_Filter_Title').'</span>
      		</div>
		</div>
		<div id="insect-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all">
		  '.data_entry_helper::select($insect_ctrl_args).'
 		  <input type="text" name="insect:taxon_extra_info" class="taxon-info" value="'.lang::get('LANG_More_Precise').'"
	 		onclick="if(this.value==\''.lang::get('LANG_More_Precise').'\'){this.value=\'\'; this.style.color=\'#000\'}"  
            onblur="if(this.value==\'\'){this.value=\''.lang::get('LANG_More_Precise').'\'; this.style.color=\'#555\'}" />
		</div>
		<div id="conditions-filter-header" class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all">
	  		<div id="fold-conditions-button" class="ui-state-default ui-corner-all fold-button fold-button-folded">&nbsp;</div>
			<div id="reset-conditions-button" class="ui-state-default ui-corner-all reset-button">'.lang::get('LANG_Reset_Filter').'</div>
	  		<div id="conditions-filter-title">
		  		<span>'.lang::get('LANG_Conditions_Filter_Title').'</span>
      		</div>
		</div>
		<div id="conditions-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all">
    	  '.str_replace("\n", "", data_entry_helper::outputAttribute($sample_attributes[$args['sky_state_attr_id']], $defAttrOptions))
		  .str_replace("\n", "", data_entry_helper::outputAttribute($sample_attributes[$args['temperature_attr_id']], $defAttrOptions))
		  .str_replace("\n", "", data_entry_helper::outputAttribute($sample_attributes[$args['wind_attr_id']], $defAttrOptions))
		  .str_replace("\n", "", data_entry_helper::outputAttribute($sample_attributes[$args['shade_attr_id']], $defAttrOptions)).'
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
		    '.data_entry_helper::georeference_lookup(iform_map_get_georef_options($args)).'
    		<span id="location-georef-notes" >'.lang::get('LANG_Georef_Notes').'</span>
    		<label for="place:INSEE">'.lang::get('LANG_Or').'</label>
 		    <input type="text" id="place:INSEE" name="place:INSEE" value="'.lang::get('LANG_INSEE').'"
	 		  onclick="if(this.value==\''.lang::get('LANG_INSEE').'\'){this.value=\'\'; this.style.color=\'#000\'}"  
              onblur="if(this.value==\'\'){this.value=\''.lang::get('LANG_INSEE').'\'; this.style.color=\'#555\'}" />
    	    <input type="button" id="search-insee-button" class="ui-corner-all ui-widget-content ui-state-default search-button" value="'.lang::get('search').'" />
 	      </div>';
    // this is a bit of a hack, because the apply_template method is not public in data entry helper.
    $tempScript = data_entry_helper::$onload_javascript;
    data_entry_helper::$onload_javascript = '';
    $r .= data_entry_helper::map_panel($options, $olOptions);
    $map1JS = data_entry_helper::$onload_javascript;
    data_entry_helper::$onload_javascript = $tempScript;
    $r .= '
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
	<div id="fc-header" class="ui-accordion-content ui-helper-reset ui-state-active ui-corner-top ui-accordion-content-active">
	  <div id="fc-header-buttons">';
    if(user_access('IForm n'.$node->nid.' add preferred collection')){
    	$r .= '<span id="fc-add-preferred" class="ui-state-default ui-corner-all preferred-button">'.lang::get('LANG_Add_Preferred_Collection').'</span>';
    }
    $r .= '  
	    <span id="fc-prev-button" class="ui-state-default ui-corner-all previous-button">'.lang::get('LANG_Previous').'</span>
	    <span id="fc-next-button" class="ui-state-default ui-corner-all next-button">'.lang::get('LANG_Next').'</span>
	  	<span id="fc-filter-button" class="ui-state-default ui-corner-all collection-button">'.lang::get('LANG_List').'</span>
	  </div>
	</div>
	<div id="collection-details" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-bottom">
	  <div id="flower-image-container" ><div id="flower-image" class="flower-image"></div>
        <div id="show-flower-button" class="ui-state-default ui-corner-all display-button">'.lang::get('LANG_Display').'</div>
      </div>
      <div id="environment-image" class="environment-image"></div>
      <div id="collection-description">
	    <p id="collection-date"></p>
	    <p id="collection-flower-name"></p>
	    <p>'.lang::get($occurrence_attributes[$args['flower_type_attr_id']]['caption']).': <span id="collection-flower-type" class=\"collection-value\"></span></p>
	    <p>'.lang::get($location_attributes[$args['habitat_attr_id']]['caption']).': <span id="collection-habitat" class=\"collection-value\"></span></p>
	    <p id="collection-locality"></p>
	    <p id="collection-user-name"></p>
	    <a id="collection-user-link">'.lang::get('LANG_User_Link').'</a>
	  </div>
      <div id="map2_container">';
    // this is a bit of a hack, because the apply_template method is not public in data entry helper.
    $tempScript = data_entry_helper::$onload_javascript;
    data_entry_helper::$onload_javascript = '';
    $r .= data_entry_helper::map_panel($options2, $olOptions);
    $map2JS = data_entry_helper::$onload_javascript;
    data_entry_helper::$onload_javascript = $tempScript;
    $r .= '</div>
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
	<div id="fo-header" class="ui-accordion-content ui-helper-reset ui-state-active ui-corner-top ui-accordion-content-active">
	  <div id="fo-header-buttons">
 	    <span id="fo-collection-button" class="ui-state-default ui-corner-all collection-button">'.lang::get('LANG_Collection').'</span>
	    <span id="fo-prev-button" class="ui-state-default ui-corner-all previous-button">'.lang::get('LANG_Previous').'</span>
	    <span id="fo-next-button" class="ui-state-default ui-corner-all next-button">'.lang::get('LANG_Next').'</span>
	  	<span id="fo-filter-button" class="ui-state-default ui-corner-all collection-button">'.lang::get('LANG_List').'</span>
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
	</div>
	<div id="fo-new-insect-id" class="ui-accordion-content ui-helper-reset ui-widget-content">
	  <form id="fo-new-insect-id-form" action="'.iform_ajaxproxy_url($node, 'determination').'" method="POST">
		<input type="hidden" name="website_id" value="'.$args['website_id'].'" />
    	<input type="hidden" name="determination:occurrence_id" value="" />
		<input type="hidden" name="determination:cms_ref" value="'.$uid.'" />  
    	<input type="hidden" name="determination:person_name" value="'.$username.'" />  
		<input type="hidden" name="determination:email_address" value="'.$email.'" />';
	if(user_access('IForm n'.$node->nid.' insect expert')){
		$r .= '		<select name="determination:determination_type" />
			<option value="C" selected>'.lang::get('LANG_Det_Type_C').'</option>
			<option value="X">'.lang::get('LANG_Det_Type_X').'</option>
		</select>';
	} else {
		$r .= '		<input type="hidden" name="determination:determination_type" value="A" />';
	}
		$r .= '		<div class="id-tool-group">
          <input type="hidden" name="determination:taxon_details" />
          <span id="insect-id-button" class="ui-state-default ui-corner-all poll-id-button" >'.lang::get('LANG_Launch_ID_Key').'</span>
		  <span id="insect-id-cancel" class="ui-state-default ui-corner-all poll-id-cancel" >'.lang::get('LANG_Cancel_ID').'</span>
 	      <p id="insect_taxa_list"></p>
 	    </div>
 	    <div class="id-specified-group">
 	      '.data_entry_helper::select($focus_insect_ctrl_args).'
          <label for="insect:taxon_extra_info" class="follow-on">'.lang::get('LANG_More_Precise').' </label> 
          <input type="text" id="insect:taxon_extra_info" name="determination:taxon_extra_info" class="taxon-info" />
        </div>
 	    <div class="id-comment">
          <label for="insect:comment" class="follow-on">'.lang::get('LANG_ID_Comment').' </label>
          <textarea id="insect:comment" name="determination:comment" class="taxon-comment" rows="5" ></textarea>
        </div>
        <input type="submit" id="insect_id_submit_button" class="ui-state-default ui-corner-all submit-button" value="'.lang::get('LANG_Validate').'" />
      </form>
	</div>
    <div id="fo-new-flower-id" class="ui-accordion-content ui-helper-reset ui-widget-content">
	  <form id="fo-new-flower-id-form" action="'.iform_ajaxproxy_url($node, 'determination').'" method="POST">
		<input type="hidden" name="website_id" value="'.$args['website_id'].'" />
    	<input type="hidden" name="determination:occurrence_id" value="" />
		<input type="hidden" name="determination:cms_ref" value="'.$uid.'" />  
    	<input type="hidden" name="determination:person_name" value="'.$username.'" />  
		<input type="hidden" name="determination:email_address" value="'.$email.'" />';
	if(user_access('IForm n'.$node->nid.' flower expert')){
		$r .= '		<select name="determination:determination_type" />
			<option value="C" selected>'.lang::get('LANG_Det_Type_C').'</option>
			<option value="X">'.lang::get('LANG_Det_Type_X').'</option>
		</select>';
	} else {
		$r .= '		<input type="hidden" name="determination:determination_type" value="A" />';
	}
		$r .= '		<div class="id-tool-group">
          <input type="hidden" name="determination:taxon_details" />
          <span id="flower-id-button" class="ui-state-default ui-corner-all poll-id-button" >'.lang::get('LANG_Launch_ID_Key').'</span>
		  <span id="flower-id-cancel" class="ui-state-default ui-corner-all poll-id-cancel" >'.lang::get('LANG_Cancel_ID').'</span>
 	      <p id="flower_taxa_list" class="taxa_list" ></p>
 	    </div>
 	    <div class="id-specified-group">
 	      '.data_entry_helper::select($focus_flower_ctrl_args).'
          <label for="flower:taxon_extra_info" class="follow-on">'.lang::get('LANG_More_Precise').' </label> 
          <input type="text" id="flower:taxon_extra_info" name="determination:taxon_extra_info" class="taxon-info" />
        </div>
 	    <div class="id-comment">
          <label for="flower:comment" class="follow-on">'.lang::get('LANG_ID_Comment').' </label>
          <textarea id="flower:comment" name="determination:comment" class="taxon-comment" rows="5" ></textarea>
        </div>
        <input type="submit" id="flower_id_submit_button" class="ui-state-default ui-corner-all submit-button" value="'.lang::get('LANG_Validate').'" />
      </form>
	</div>
	<div id="fo-express-doubt" class="ui-accordion-content ui-helper-reset ui-widget-content">
	  <form id="fo-express-doubt-form" action="'.iform_ajaxproxy_url($node, 'determination').'" method="POST">
		<input type="hidden" name="website_id" value="'.$args['website_id'].'" />
    	<input type="hidden" name="determination:occurrence_id" value="" />
		<input type="hidden" name="determination:cms_ref" value="'.$uid.'" />  
    	<input type="hidden" name="determination:person_name" value="'.$username.'" />  
		<input type="hidden" name="determination:email_address" value="'.$email.'" />
    	<input type="hidden" name="determination:determination_type" value="B" />
        <input type="hidden" name="determination:taxon_extra_info" />
        <input type="hidden" name="determination:taxa_taxon_list_id" />
 	    <div class="doubt-comment">
          <label for="determination:comment" class="follow-on">'.lang::get('LANG_Doubt_Comment').' </label>
          <textarea id="determination:comment" name="determination:comment" class="taxon-comment" rows="5" ></textarea>
        </div>
        <input type="submit" id="doubt_submit_button" class="ui-state-default ui-corner-all submit-button" value="'.lang::get('LANG_Validate').'" />
      </form>
	</div>
	
	<div id="fo-id-history" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active"></div>
	<div id="fo-id-buttons" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active">
		<div id="fo-new-insect-id-button" class="ui-state-default ui-corner-all new-id-button">'.lang::get('LANG_New_ID').'</div>
		<div id="fo-new-flower-id-button" class="ui-state-default ui-corner-all new-id-button">'.lang::get('LANG_New_ID').'</div>
		<div id="fo-doubt-button" class="ui-state-default ui-corner-all doubt-button">'.lang::get('LANG_Doubt').'</div>
    </div>	
	<div id="fo-insect-addn-info" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
	    <span class="addn-info-title">'.lang::get('LANG_Additional_Info_Title').'</span>
	    <p>'.lang::get('LANG_Date').': <span id="fo-insect-date"></span></p>
	    <p>'.lang::get('LANG_Time').': <span id="fo-insect-start-time"></span> '.lang::get('LANG_To').' <span id="fo-insect-end-time"></span></p>
	    <p>'.$sample_attributes[$args['sky_state_attr_id']]['caption'].': <span id="fo-insect-sky"></span></p>
	    <p>'.$sample_attributes[$args['temperature_attr_id']]['caption'].': <span id="fo-insect-temp"></span></p>
	    <p>'.$sample_attributes[$args['wind_attr_id']]['caption'].': <span id="fo-insect-wind"></span></p>
	    <p>'.$sample_attributes[$args['shade_attr_id']]['caption'].': <span id="fo-insect-shade"></span></p>
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
</div>
';

    data_entry_helper::$javascript .= "
// We need to leave the AJAX calls for the search alone, but abort other focus-on calls,
// so we put a dummy REMOVEABLEJSONP in the URL, and search on that. This is ignored by the service call itself.
ajaxStack = [];
abortAjax = function()
{
	jQuery('script').each(function(){
		if(this.src.indexOf('REMOVEABLEJSONP')>0){
			var test = this.src.match(/jsonp\d*/);
			window[test] = function(){};
			jQuery(this).remove();
		}
	});
	// This deals with any non cross domain calls.
	while(ajaxStack.length > 0){
		var request = ajaxStack.shift();
		if(!(typeof request == 'undefined')) request.abort();
	}
}


alertIndiciaError = function(data){
	var errorString = \"".lang::get('LANG_Indicia_Warehouse_Error')."\";
	if(data.error){	errorString = errorString + ' : ' + data.error;	}
	if(data.errors){
		for (var i in data.errors){
			errorString = errorString + ' : ' + data.errors[i];
		}				
	}
	alert(errorString);
	// the most likely cause is authentication failure - eg the read authentication has timed out.
	// prevent further use of the form:
	jQuery('#filter,#focus-occurrence,#focus-collection').hide();
	jQuery('#refresh-message').show();
};

jQuery('#imp-georef-search-btn').removeClass('indicia-button').addClass('search-button');
$.validator.messages.required = \"".lang::get('validation_required')."\";

// remove the (don't know) entry from flower type filter.
jQuery('[name=occAttr\\:".$args['flower_type_attr_id']."]').filter('[value=".$args['flower_type_dont_know']."]').parent().remove();
 
jQuery('#start_date').datepicker({
  dateFormat : 'dd/mm/yy',
  constrainInput: false,
  maxDate: '0',
  altField : '#real_start_date',
  altFormat : 'yy-mm-dd'
});
jQuery('#end_date').datepicker({
  dateFormat : 'dd/mm/yy',
  constrainInput: false,
  maxDate: '0',
  altField : '#real_end_date',
  altFormat : 'yy-mm-dd'
});

myScrollTo = function(selector){
	jQuery(selector).filter(':visible').each(function(){
		window.scroll(0, jQuery(this).offset().top);
	});
};

jQuery('#reset-name-button').click(function(){
	jQuery('[name=username]').val('');
});
jQuery('#fold-name-button').click(function(){
	jQuery('#name-filter-header').toggleClass('ui-state-active').toggleClass('ui-state-default');
	jQuery('#fold-name-button').toggleClass('fold-button-folded');
	jQuery('#name-filter-body').toggleClass('ui-accordion-content-active');
});
jQuery('#reset-date-button').click(function(){
	jQuery('[name=start_date]').val('".lang::get('click here')."');
	jQuery('[name=real_start_date]').val('');
	jQuery('[name=end_date]').val('".lang::get('click here')."');
	jQuery('[name=real_end_date]').val('');
});
jQuery('#fold-date-button').click(function(){
	jQuery('#date-filter-header').toggleClass('ui-state-active').toggleClass('ui-state-default');
	jQuery('#fold-date-button').toggleClass('fold-button-folded');
	jQuery('#date-filter-body').toggleClass('ui-accordion-content-active');
});

jQuery('#reset-flower-button').click(function(){
	jQuery('[name=flower\\:taxa_taxon_list_id]').val('');
	jQuery('[name=flower\\:taxon_extra_info]').val(\"".lang::get('LANG_More_Precise')."\");
	jQuery('#flower-filter-body').find(':checkbox').removeAttr('checked');
});
jQuery('#fold-flower-button').click(function(){
	jQuery('#flower-filter-header').toggleClass('ui-state-active').toggleClass('ui-state-default');
	jQuery('#fold-flower-button').toggleClass('fold-button-folded');
	jQuery('#flower-filter-body').toggleClass('ui-accordion-content-active');
});

jQuery('#reset-insect-button').click(function(){
	jQuery('[name=insect\\:taxa_taxon_list_id]').val('');
	jQuery('[name=insect\\:taxon_extra_info]').val(\"".lang::get('LANG_More_Precise')."\");
	jQuery('#insect-filter-body').find(':checkbox').removeAttr('checked');
});

jQuery('#fold-insect-button').click(function(){
	jQuery('#insect-filter-header').toggleClass('ui-state-active').toggleClass('ui-state-default');
	jQuery('#fold-insect-button').toggleClass('fold-button-folded');
	jQuery('#insect-filter-body').toggleClass('ui-accordion-content-active');
});

jQuery('#reset-conditions-button').click(function(){
	jQuery('#conditions-filter-body').find(':checkbox').removeAttr('checked');
});

jQuery('#fold-conditions-button').click(function(){
	jQuery('#conditions-filter-header').toggleClass('ui-state-active').toggleClass('ui-state-default');
	jQuery('#fold-conditions-button').toggleClass('fold-button-folded');
	jQuery('#conditions-filter-body').toggleClass('ui-accordion-content-active');
});

jQuery('#reset-location-button').click(function(){
	polygonLayer.destroyFeatures();
	polygonLayer.map.searchLayer.destroyFeatures();
	if(inseeLayer != null)
		inseeLayer.destroyFeatures();
	jQuery('#imp-georef-search').val('');
	jQuery('[name=place\\:INSEE]').val('".lang::get('LANG_INSEE')."');
	var div = jQuery('#map')[0];
	var center = new OpenLayers.LonLat(".$args['map_centroid_long'].", ".$args['map_centroid_lat'].");
	center.transform(div.map.displayProjection, div.map.projection);
	div.map.setCenter(center, ".((int) $args['map_zoom']).");
});
jQuery('#fold-location-button').click(function(){
	jQuery('#location-filter-header').toggleClass('ui-state-active').toggleClass('ui-state-default');
	jQuery('#fold-location-button').toggleClass('fold-button-folded');
	jQuery('#location-filter-body').toggleClass('ui-accordion-content-active');
});

jQuery('#flower-image').click(function(){
	if(jQuery('#flower-image').data('occID') != 'none'){
		loadFlower(jQuery('#flower-image').data('occID'), jQuery('#flower-image').data('collectionIndex'));
	}
});
jQuery('#show-flower-button').click(function(){
	if(jQuery('#flower-image').data('occID') != 'none'){
		loadFlower(jQuery('#flower-image').data('occID'), jQuery('#flower-image').data('collectionIndex'));
	}
});

jQuery('#fo-doubt-button').click(function(){
	jQuery('#fo-new-insect-id,#fo-new-flower-id').removeClass('ui-accordion-content-active');
	jQuery('#fo-express-doubt [name=determination\\:comment]').val(\"".lang::get('LANG_Default_Doubt_Comment')."\");
	jQuery('#fo-express-doubt').toggleClass('ui-accordion-content-active');
});

jQuery('#fc-next-button,#fc-prev-button').click(function(){
	var index = jQuery(this).data('index');
	var id = searchResults.features[index].attributes.collection_id;
	loadCollection(id, index);
});

jQuery('#fc-filter-button,#fo-filter-button').click(function(){
    jQuery('#filter').show();
	jQuery('#focus-occurrence,#focus-collection,#results-insects-header,#results-collections-header,#results-insects-results,#results-collections-results').hide();
    loadFilter();
    if(searchResults != null){
    	if(searchResults.type == 'C')
    		jQuery('#results-collections-header,#results-collections-results').show();
    	else 
    		jQuery('#results-insects-header,#results-insects-results').show();
	}
});

htmlspecialchars = function(value){
	return value.replace(/[<>\"'&]/g, function(m){return replacechar(m)})
};

replacechar = function(match){
	if (match==\"<\") return \"&lt;\"
	else if (match==\">\") return \"&gt;\"
	else if (match=='\"') return \"&quot;\"
	else if (match==\"'\") return \"&#039;\"
	else if (match==\"&\") return \"&amp;\"
};

convertDate = function(dateStr, incTime){
	var retDate = '';
	// assume date is in in YYYY/MM/DD[+Time] format.
	// if language is french convert to DD/MM/YYYY[+Time] format.
	if('".$args['language']."' == 'fr'){
		retDate = dateStr.slice(8,10)+'-'+dateStr.slice(5,7)+'-'+dateStr.slice(0,4);
		if(incTime) retDate = retDate+dateStr.slice(10);
	} else if(incTime)
		retDate = dateStr;
	else
		retDate = dateStr.slice(0,10);
	return retDate;
} 

loadCollection = function(id, index){
	abortAjax();
    jQuery('[name=sample_comment\\:sample_id]').val(id);
	jQuery('#fc-add-preferred').attr('smpID', id);
	collection_preferred_object.collection_id = id;
	jQuery('#fc-new-comment-button').".(user_access('IForm n'.$node->nid.' create collection comment') ? "show()" : "hide()").";
    jQuery('#focus-occurrence,#filter,#fc-next-button,#fc-prev-button').hide();
    jQuery('#focus-collection').show();
    if(index != null){
    	if(index < (searchResults.features.length-1) )
    		jQuery('#fc-next-button').show().data('index', index+1);
    	if(index > 0)
    		jQuery('#fc-prev-button').show().data('index', index-1);
    }
    if(jQuery('#map2').children().length == 0) {
    	locationLayer = new OpenLayers.Layer.Vector('Location Layer',{displayInLayerSwitcher: false});
    	".$map2JS."
 	};
    locationLayer.destroyFeatures();
 	jQuery('#map2').width('auto');
	jQuery('#flower-image').data('occID', 'none').data('collectionIndex', index);
	jQuery('#collection-insects,#collection-date,#collection-flower-name,#collection-flower-type,#collection-habitat,#collection-user-name').empty();
	loadComments(id, '#fc-comment-list', 'sample_comment', 'sample_id', 'sample-comment-block', 'sample-comment-body', true);
	// only need to reset the timeout on the first fetch as rest follow on quickly.
	ajaxStack.push($.getJSON(\"".$svcUrl."/data/occurrence\" +
			\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
			\"&sample_id=\"+id+\"&deleted=f&REMOVEABLEJSONP&callback=?\", function(flowerData) {
   		if(!(flowerData instanceof Array)){
   			alertIndiciaError(flowerData);
   		} else if (flowerData.length>0) {
   			loadImage('occurrence_image', 'occurrence_id', flowerData[0].id, '#flower-image', ".$args['Flower_Image_Ratio'].", function(imageRecord){collection_preferred_object.flower_image_path = imageRecord.path}, 'med-', false);
			jQuery('#flower-image').data('occID', flowerData[0].id);
			collection_preferred_object.flower_id = flowerData[0].id;
			ajaxStack.push($.getJSON(\"".$svcUrl."/data/determination\" + 
					\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
					\"&occurrence_id=\" + flowerData[0].id + \"&deleted=f&orderby=id&REMOVEABLEJSONP&callback=?\", function(detData) {
   				if(!(detData instanceof Array)){
   					alertIndiciaError(detData);
   				} else if (detData.length>0) {
   					var i = detData.length-1;
					var string = '';
					if(detData[i].taxon != '' && detData[i].taxon != null){
						string = detData[i].taxon;
		  			}
					if(detData[i].taxa_taxon_list_id_list != '' && detData[i].taxa_taxon_list_id_list != null){
						detData[i].taxa_taxon_list_id_list;
			  			var resultsIDs = detData[i].taxa_taxon_list_id_list.substring(1, detData[i].taxa_taxon_list_id_list.length - 1).split(',');
						if(resultsIDs[0] != '') {
							for(var j=0; j < resultsIDs.length; j++){
								for(k = 0; k< flowerTaxa.length; k++){
									if(flowerTaxa[k].id == resultsIDs[j]){
										string = (string == '' ? '' : string + ', ') + flowerTaxa[k].taxon;
									}
								};
					  		}
					  	}
					}
		  			if(detData[i].taxon_extra_info != '' && detData[i].taxon_extra_info != null){
						string = (string == '' ? '' : string + ' ') + '('+detData[i].taxon_extra_info+')';
					}
					jQuery('<span>".lang::get('LANG_Flower_Name').": <span class=\"collection-value\">'+htmlspecialchars(string)+'</span></span>').appendTo('#collection-flower-name');
				}}));
			ajaxStack.push($.getJSON(\"".$svcUrl."/data/occurrence_attribute_value\"  +
   					\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   					\"&occurrence_id=\" + flowerData[0].id + \"&iso=".$language."&REMOVEABLEJSONP&callback=?\", function(attrdata) {
				if(!(attrdata instanceof Array)){
   					alertIndiciaError(attrdata);
   				} else if (attrdata.length>0) {
   					for(i=0; i< attrdata.length; i++){
						if (attrdata[i].id){
							switch(parseInt(attrdata[i].occurrence_attribute_id)){
								case ".$args['flower_type_attr_id'].":
									jQuery('<span>'+attrdata[i].value+'</span>').appendTo('#collection-flower-type');
									break;
  			}}}}}));
				
		}
	}));
	ajaxStack.push($.getJSON(\"".$svcUrl."/data/sample_attribute_value\"  +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&sample_id=\" + id + \"&REMOVEABLEJSONP&callback=?\", function(attrdata) {
		if(!(attrdata instanceof Array)){
   			alertIndiciaError(attrdata);
   		} else if (attrdata.length>0) {
			for(i=0; i< attrdata.length; i++){
				if (attrdata[i].id){
					switch(parseInt(attrdata[i].sample_attribute_id)){
						case ".$args['username_attr_id'].":
							jQuery('<span>".lang::get('LANG_Comment_By')."'+attrdata[i].value+'</span>').appendTo('#collection-user-name');
							break;
						case ".$args['uid_attr_id'].":
							collection_preferred_object.user_id = attrdata[i].value
			       		    jQuery('#collection-user-link').attr('href', '".url('node/'.$node->nid)."?user_id='+attrdata[i].value);
							break;
    }}}}}));
	ajaxStack.push($.getJSON(\"".$svcUrl."/data/sample/\" +id+
			\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
			\"&REMOVEABLEJSONP&callback=?\", function(collectionData) {
   		if(!(collectionData instanceof Array)){
   			alertIndiciaError(collectionData);
   		} else if (collectionData.length>0) {
			if(collectionData[0].date_start == collectionData[0].date_end){
				collection_preferred_object.date = collectionData[0].date_start.slice(0,10);
				jQuery('<span>'+convertDate(collectionData[0].date_start, false)+'</span>').appendTo('#collection-date');
			} else {
				collection_preferred_object.date = collectionData[0].date_start.slice(0,10)+' - '+collectionData[0].date_end.slice(0,10);
				jQuery('<span>'+convertDate(collectionData[0].date_start, false)+' - '+convertDate(collectionData[0].date_end, false)+'</span>').appendTo('#collection-date');
			}
			jQuery('#poll-banner').empty().append(collectionData[0].location_name);
	  		collection_preferred_object.collection_name = collectionData[0].location_name;
	        ajaxStack.push($.getJSON(\"".$svcUrl."/data/location/\" +collectionData[0].location_id +
					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
					\"&REMOVEABLEJSONP&callback=?\", function(locationData) {
   				if(!(locationData instanceof Array)){
   					alertIndiciaError(locationData);
   				} else if (locationData.length>0) {
					loadImage('location_image', 'location_id', locationData[0].id, '#environment-image', ".$args['Environment_Image_Ratio'].", function(imageRecord){collection_preferred_object.environment_image_path = imageRecord.path}, 'med-', false);
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
					inseeProtocol.read({filter: filter, callback: fillLocationDetails, scope: scope});
				}
			}));
	        ajaxStack.push($.getJSON(\"".$svcUrl."/data/location_attribute_value\"  +
   					\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   					\"&location_id=\" + collectionData[0].location_id + \"&iso=".$language."&REMOVEABLEJSONP&callback=?\", function(attrdata) {
				if(!(attrdata instanceof Array)){
   					alertIndiciaError(attrdata);
   				} else if (attrdata.length>0) {
					for(i=0; i< attrdata.length; i++){
						if (attrdata[i].id){
							switch(parseInt(attrdata[i].location_attribute_id)){
								case ".$args['habitat_attr_id'].":
									if(attrdata[i].raw_value > 0) jQuery('<span>'+attrdata[i].value+' / </span>').appendTo('#collection-habitat');
									break;
  			}}}}}));
		}
	}));
	// we want to tag end of row pictuure, so we need to keep track of its position in list.
	collection_preferred_object.insects = [];
	ajaxStack.push(jQuery.ajax({
		url: \"".$svcUrl."/data/sample?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&REMOVEABLEJSONP&callback=?&parent_id=\"+id,
		dataType: 'json',
		myIndex: index,
		success: function(sessiondata) {
          if(!(sessiondata instanceof Array)){
   			alertIndiciaError(sessiondata);
   		  } else if (sessiondata.length>0) {
   			var sessList=[];
   			// code has been changed to fetch all insects at once, so we can now go async
			for (var i=0;i<sessiondata.length;i++)
				sessList.push(sessiondata[i].id);
				ajaxStack.push(jQuery.ajax({
					url: \"".$svcUrl."/data/occurrence?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&deleted=f&orderby=id&REMOVEABLEJSONP&callback=?&query=\"+escape(JSON.stringify({'in': ['sample_id', sessList]})),
					dataType: 'json',
					myIndex: this.myIndex,
					success: function(insectData) {
					  if(!(insectData instanceof Array)){
   						alertIndiciaError(insectData);
   		  			  } else if (insectData.length>0) {
						for (var j=0;j<insectData.length;j++){
							var insect=jQuery('<div class=\"ui-widget-content ui-corner-all collection-insect\" />').attr('occID', insectData[j].id).appendTo('#collection-insects');
							if((j+1)/".$args['insectsPerRow']." == parseInt((j+1)/".$args['insectsPerRow']."))
								insect.addClass('end-of-row');
							jQuery('<p class=\"insect-tag insect-unknown\" />').appendTo(insect);
							var image = jQuery('<div class=\"insect-image empty\" />').appendTo(insect).data('occID',insectData[j].id)
									.data('collectionIndex',this.myIndex).click(function(){
								loadInsect(jQuery(this).data('occID'),jQuery(this).data('collectionIndex'),null,'C');								
							});
							jQuery('<p class=\"insect-determination empty\" />').appendTo(insect);
							jQuery('<div class=\"ui-state-default ui-corner-all display-button\">".lang::get('LANG_Display')."</div>')
									.appendTo(insect).attr('occID',insectData[j].id).data('collectionIndex',this.myIndex).data('collectionInsectIndex',j).click(function(){
								loadInsect(jQuery(this).attr('occID'),jQuery(this).data('collectionIndex'),null,'C');								
							});
							loadImage('occurrence_image', 'occurrence_id', insectData[j].id, image, ".$args['Insect_Image_Ratio'].", function(imageRecord){collection_preferred_object.insects.push({insect_id: imageRecord.occurrence_id, insect_image_path: imageRecord.path})}, 'med-',
								function(img){
									var group = jQuery('#collection-insects').find('.collection-insect');
									jQuery(img).parent().removeClass('empty');
									if(group.find('.empty').length == 0){
										var tallest = 0;
										group.each(function(){ tallest = Math.max($(this).height(), tallest); });
										group.each(function(){ $(this).height(Math.max($(this).height(), tallest)); }); // have synchronicity problems.
									}
								});
							ajaxStack.push(jQuery.ajax({
								url: \"".$svcUrl."/data/determination\" + 
					    			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
					    			\"&occurrence_id=\" + insectData[j].id + \"&deleted=f&orderby=id&REMOVEABLEJSONP&callback=?\",
								dataType: 'json',
								myID: insectData[j].id,
								success: function(detData) {
   								  if(!(detData instanceof Array)){
   									alertIndiciaError(detData);
   		  			  			  } else {
   		  			  			   if (detData.length>0) {
					    			var insect = jQuery('.collection-insect').filter('[occID='+detData[0].occurrence_id+']');
					    			var tag = insect.find('.insect-tag');
					    			var det = insect.find('.insect-determination');
									var i = detData.length-1;
									var string = '';
									if(detData[i].taxon != '' && detData[i].taxon != null){
										string = detData[i].taxon;
						  			}
									if(detData[i].taxa_taxon_list_id_list != '' && detData[i].taxa_taxon_list_id_list != null){
										detData[i].taxa_taxon_list_id_list;
									  	var resultsIDs = detData[i].taxa_taxon_list_id_list.substring(1, detData[i].taxa_taxon_list_id_list.length - 1).split(',');
										if(resultsIDs[0] != '') {
											for(var j=0; j < resultsIDs.length; j++){
												for(var k = 0; k< insectTaxa.length; k++){
													if(insectTaxa[k].id == resultsIDs[j]){
														string = (string == '' ? '' : string + ', ') + insectTaxa[k].taxon;
													}
												};
											}
								  		}
									}	
									if(string != '')
										jQuery('<div><p>".lang::get('LANG_Last_ID').":</p><p><strong>'+string+'</strong></p></div>').addClass('insect-id').appendTo(det);
									if(detData[i].determination_type == 'B' || detData[i].determination_type == 'I' || detData[i].determination_type == 'U'){
										tag.removeClass('insect-unknown').addClass('insect-dubious');
									} else {
										tag.removeClass('insect-unknown').addClass('insect-ok');
									}
								   }
									var group = jQuery('#collection-insects').find('.collection-insect');
									group.filter('[occID='+this.myID+']').find('.insect-determination').removeClass('empty');
									if(group.find('.empty').length == 0){
										var tallest = 0;
										group.each(function(){ tallest = Math.max($(this).height(), tallest); });
										group.each(function(){ $(this).height(Math.max($(this).height(), tallest)); }); // have synchronicity problems.
							}}}}));
						}
					}
				  }})); 
			}}
	    }));
	myScrollTo('#poll-banner');
};
fillLocationDetails = function(a1)
{
	jQuery(this.target).empty();
	collection_preferred_object.location_description = '';
	if(a1.features.length > 0) {
	   	var text = a1.features[0].attributes.NOM+' ('+a1.features[0].attributes.INSEE_NEW+'), '+a1.features[0].attributes.DEPT_NOM+' ('+a1.features[0].attributes.DEPT_NUM+'), '+a1.features[0].attributes.REG_NOM+' ('+a1.features[0].attributes.REG_NUM+')';
		collection_preferred_object.location_description = text;
	   	jQuery('<span>'+text+'</span>').appendTo(this.target);
  }
}
addCollection = function(index, attributes, geom, first){
	// first the text, then the flower and environment picture, then the small insect pictures, then the afficher button
	var collection=jQuery('<div class=\"ui-widget-content ui-corner-all filter-collection\" />').appendTo('#results-collections-results');
	var details = jQuery('<div class=\"collection-details\" />').appendTo(collection); 
	var flower = jQuery('<div class=\"collection-image collection-flower\" />').data('occID', attributes.flower_id).
		data('collectionIndex', index).click(function(){
			loadFlower(jQuery(this).data('occID'), jQuery(this).data('collectionIndex'));
	});
	var filter = new OpenLayers.Filter.Spatial({
  			type: OpenLayers.Filter.Spatial.CONTAINS ,
    		property: 'the_geom',
    		value: geom
  	});
	flower.appendTo(collection);
	insertImage('med-'+attributes.image_de_la_fleur, flower, ".$args['Flower_Image_Ratio'].", false);
	var location = jQuery('<div class=\"collection-image collection-environment\" />').appendTo(collection);
	insertImage('med-'+attributes.image_de_environment, location, ".$args['Environment_Image_Ratio'].", false);
	jQuery('<div class=\"collection-photoreel\"></div>').attr('collID', attributes.collection_id).appendTo(collection);
	var displayButtonContainer = jQuery('<div class=\"collection-buttons\"></div>').appendTo(collection);
	jQuery('<div class=\"ui-state-default ui-corner-all display-button\">".lang::get('LANG_Display')."</div>').click(function(){
		loadCollection(jQuery(this).data('value'), jQuery(this).data('index'));
	}).appendTo(displayButtonContainer).data('value',attributes.collection_id).data('index',index);
	if(attributes.datedebut == attributes.datefin){
	  jQuery('<p class=\"collection-date\">'+convertDate(attributes.datedebut,false)+'</p>').appendTo(details);
    } else {
	  jQuery('<p class=\"collection-date\">'+convertDate(attributes.datedebut,false)+' - '+convertDate(attributes.datefin,false)+'</p>').appendTo(details);
    }
	jQuery('<p class=\"collection-name\">'+attributes.nom+'</p>').appendTo(details);
	var locality = jQuery('<p  class=\"collection-locality\"></p>').appendTo(details);
	var scope = {target: locality};
	inseeProtocol.read({filter: filter, callback: fillLocationDetails, scope: scope});
	jQuery.getJSON(\"".$svcUrl."/data/sample?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + (first ? \"&reset_timeout=true\" : \"\") + \"&callback=?&parent_id=\"+attributes.collection_id,
        function(sessiondata) {
		  if(!(sessiondata instanceof Array)){
   			alertIndiciaError(sessiondata);
   		  } else for (var i=0;i<sessiondata.length;i++){
   		  	var photoreel = jQuery('.collection-photoreel').filter('[collID='+sessiondata[i].parent_id+']');
   		  	jQuery('<span class=\"photoreel-session\"></span>').attr('sessID', sessiondata[i].id).appendTo(photoreel);
			$.getJSON(\"".$svcUrl."/data/occurrence/\" +
					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&orderby=id\" +
					\"&sample_id=\"+sessiondata[i].id+\"&deleted=f&callback=?\", function(insectData) {
		    	if(!(insectData instanceof Array)){
   					alertIndiciaError(insectData);
   				} else if (insectData.length>0) {
 					for (var j=0;j<insectData.length;j++){
						var container = jQuery('<div/>').addClass('thumb').attr('occID', insectData[j].id.toString()).data('collectionIndex',index).click(function () {
							loadInsect(jQuery(this).attr('occID'),jQuery(this).data('collectionIndex'),null,'P');
						});
						jQuery('<span>".lang::get('LANG_Unknown')."</span>').addClass('thumb-text').appendTo(container);
						jQuery('.photoreel-session').filter('[sessID='+insectData[j].sample_id+']').append(container);
						$.getJSON(\"".$svcUrl."/data/occurrence_image\" +
					   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   								\"&occurrence_id=\" + insectData[j].id + \"&callback=?\", function(imageData) {
							  if(!(imageData instanceof Array)){
   								alertIndiciaError(imageData);
   							  } else if (imageData.length>0) {
						      	var container = jQuery('.thumb').filter('[occID='+imageData[0].occurrence_id.toString()+']');
							    var img = new Image();
							    // thumbs are fixed in size, and small - dont worry about ratios
								jQuery(img).attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."thumb-'+imageData[0].path)
			    					.attr('width', container.width()).attr('height', container.height()).addClass('thumb-image').appendTo(container);
							  }}); 
						$.getJSON(\"".$svcUrl."/data/determination\" + 
					    		\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
					    		\"&occurrence_id=\" + insectData[j].id + \"&orderby=id&deleted=f&callback=?\", function(detData) {
						      if(!(detData instanceof Array)){
   								alertIndiciaError(detData);
   							  } else if (detData.length>0) {
						        var container = jQuery('.thumb').filter('[occID='+detData[0].occurrence_id.toString()+']');
						        if(detData[detData.length-1].determination_type == 'B' || detData[detData.length-1].determination_type == 'I' || detData[detData.length-1].determination_type == 'U'){
						        	container.find('.thumb-text').remove();
						        	jQuery('<span>".lang::get('LANG_Dubious')."</span>').addClass('thumb-text').appendTo(container);
							  	} else {
									container.find('.thumb-text').remove();
							  	}
  						}});  					
					}
				}});
		}});
};
addInsect = function(index, attributes, endOfRow, first){
	var container=jQuery('<div class=\"ui-widget-content ui-corner-all filter-insect\" />').attr('occID',attributes.insect_id).appendTo('#results-insects-results');
	if(endOfRow) container.addClass('end-of-row');
	jQuery('<div />').addClass('insect-unknown').appendTo(container); // flag
	var insect = jQuery('<div class=\"insect-image empty\" />').data('occID',attributes.insect_id).data('insectIndex',index).click(function(){
		loadInsect(jQuery(this).data('occID'), null, jQuery(this).data('insectIndex'), 'S');
	});
	insect.appendTo(container);
	jQuery('<div class=\"insect-determinationX empty\" />').attr('occID',attributes.insect_id).appendTo(container).append(\"<p>".lang::get('LANG_No_Determinations')."</p>\");
	insertImage('med-'+attributes.image_d_insecte, insect, ".$args['Insect_Image_Ratio'].", function(img){
			var group = jQuery('#results-insects-results').find('.filter-insect');
			jQuery(img).parent().removeClass('empty');
			if(group.find('.empty').length == 0){
				var tallest = 0;
				group.each(function(){ tallest = Math.max($(this).height(), tallest); });
				group.each(function(){ $(this).height(Math.max($(this).height(), tallest)); }); // have synchronicity problems.
			}
		});
	jQuery.ajax({
		url: \"".$svcUrl."/data/determination?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + (first ? \"&reset_timeout=true\" : \"\") + \"&orderby=id&callback=?&occurrence_id=\" + attributes.insect_id,
		dataType: 'json',
		myID: attributes.insect_id,
		success: function(detData) {
   		  if(!(detData instanceof Array)){
   			alertIndiciaError(detData);
   		  } else {
   		   if (detData.length>0) {
			var i = detData.length-1;
   			var string = '';
			var determination = jQuery('.insect-determinationX').filter('[occID='+detData[i].occurrence_id+']').empty();
			var flag = determination.parent().find('.insect-unknown');
			if(detData[i].taxon != '' && detData[i].taxon != null){
				string = detData[i].taxon;
			}
			if(detData[i].taxa_taxon_list_id_list != '' && detData[i].taxa_taxon_list_id_list != null && detData[i].taxa_taxon_list_id_list != '{}'){
				detData[i].taxa_taxon_list_id_list;
				var resultsIDs = detData[i].taxa_taxon_list_id_list.substring(1, detData[i].taxa_taxon_list_id_list.length - 1).split(',');
				if(resultsIDs[0] != '') {
					for(var j=0; j < resultsIDs.length; j++){
						for(var k = 0; k< insectTaxa.length; k++){
							if(insectTaxa[k].id == resultsIDs[j]){
								string = (string == '' ? '' : string + ', ') + insectTaxa[k].taxon;
							}
						};
					}
				}
			}
			jQuery('<p>'+string+'</p>').appendTo(determination)
			if(detData[i].determination_type == 'B' || detData[i].determination_type == 'I' || detData[i].determination_type == 'U'){
				flag.removeClass('insect-unknown').addClass('insect-dubious');
			} else 
				flag.removeClass('insect-unknown').addClass('insect-ok');
		   }
		   var group = jQuery('#results-insects-results').find('.filter-insect');
		   group.filter('[occID='+this.myID+']').find('.insect-determinationX').removeClass('empty');
		   if(group.find('.empty').length == 0){
				var tallest = 0;
				group.each(function(){ tallest = Math.max($(this).height(), tallest); });
				group.each(function(){ $(this).height(Math.max($(this).height(), tallest)); }); // have synchronicity problems.
		   
  		  }}
	}});
	jQuery('<div class=\"ui-state-default ui-corner-all display-button\">".lang::get('LANG_Display')."</div>').click(function(){
		loadInsect(jQuery(this).attr('occID'), null, jQuery(this).data('insectIndex'), 'S');
	}).appendTo(container).attr('occID',attributes.insect_id).data('insectIndex',index);
};


setCollectionPage = function(pageNum){
	var no_units = 4;
	var no_tens = 2;
	var no_hundreds = 1;
	jQuery('#results-collections-results').empty();
	var numPages = Math.ceil(searchResults.features.length/".$args['collectionsPerPage'].");
	if(numPages > 1) {
		var item;
		var itemList = jQuery('<div class=\"item-list\"></div>').appendTo('#results-collections-results');
		var pageCtrl = jQuery('<ul>').addClass('pager').appendTo(itemList);
		for (var j = pageNum - no_units; j<= pageNum + no_units; j++){
			if(j <= numPages && j >= 1){
				if(j == pageNum)
					jQuery('<li class=\"pager-current\">'+pageNum+'</li>').appendTo(pageCtrl);
				else {
					item = jQuery('<li class=\"pager-item\"></li>').attr('value',j).click(function(){setCollectionPage(jQuery(this).attr('value'))}).appendTo(pageCtrl);
					jQuery('<a class=\"active\">'+j+'</a>').appendTo(item);
				}
  			}
		}
		var start = Math.ceil(j/10)*10;
		for (j = start; j< start + no_tens*10; j=j+10){
			if(j <= numPages){
				item = jQuery('<li class=\"pager-item\"></li>').attr('value',j).click(function(){setCollectionPage(jQuery(this).attr('value'))}).appendTo(pageCtrl);
				jQuery('<a class=\"active\">'+j+'</a>').appendTo(item);
			}
		}
		start = Math.ceil(j/100)*100;
		for (j = start; j< start + no_hundreds*100; j=j+100){
			if(j <= numPages){
				item = jQuery('<li class=\"pager-item\"></li>').attr('value',j).click(function(){setCollectionPage(jQuery(this).attr('value'))}).appendTo(pageCtrl);
				jQuery('<a class=\"active\">'+j+'</a>').appendTo(item);
			}
		}
		if(pageNum != numPages){
			item = jQuery('<li class=\"pager-next\"></li>').attr('value',pageNum+1).click(function(){setCollectionPage(jQuery(this).attr('value'))}).appendTo(pageCtrl);
			jQuery('<a class=\"active\">&nbsp;</a>').appendTo(item);
  			item = jQuery('<li class=\"pager-last\"></li>').attr('value',numPages).click(function(){setCollectionPage(jQuery(this).attr('value'))}).appendTo(pageCtrl);
			jQuery('<a class=\"active\">'+numPages+'</a>').appendTo(item);
  		}
		start = Math.floor((pageNum - no_units -1)/10)*10;
		for (j = start; j> start - no_tens*10; j=j-10){
			if(j >= 1){
				item = jQuery('<li class=\"pager-item\"></li>').attr('value',j).click(function(){setCollectionPage(jQuery(this).attr('value'))}).prependTo(pageCtrl);
				jQuery('<a class=\"active\">'+j+'</a>').appendTo(item);
			}
		}
		start = Math.floor(j/100)*100;
		for (j = start; j> start - no_hundreds*100; j=j-100){
			if(j >= 1){
				item = jQuery('<li class=\"pager-item\"></li>').attr('value',j).click(function(){setCollectionPage(jQuery(this).attr('value'))}).prependTo(pageCtrl);
				jQuery('<a class=\"active\">'+j+'</a>').appendTo(item);
			}
		}
		if(pageNum != 1){
			item = jQuery('<li class=\"pager-previous\"></li>').attr('value',pageNum-1).click(function(){setCollectionPage(jQuery(this).attr('value'))}).prependTo(pageCtrl);
			jQuery('<a class=\"active\">&nbsp;</a>').appendTo(item);
  			item = jQuery('<li class=\"pager-first\"></li>').click(function(){setCollectionPage(1)}).prependTo(pageCtrl);
			jQuery('<a class=\"active\">&nbsp;</a>').appendTo(item);
  		}
  		pageCtrl.find('li').filter(':first').addClass('first');
  		pageCtrl.find('li').filter(':last').addClass('last');
	}
    for (var i = (pageNum-1)*".$args['collectionsPerPage'].", first = true; i < searchResults.features.length && i < pageNum*".$args['collectionsPerPage']."; i++, first = false){
		addCollection(i, searchResults.features[i].attributes,searchResults.features[i].geometry, first);
	}
	if(numPages > 1) {
		itemList.clone(true).appendTo('#results-collections-results');
	}
}
setInsectPage = function(pageNum){
	jQuery('#results-insects-results').empty();
	var numPages = Math.ceil(searchResults.features.length/(".$args['insectsRowsPerPage']."*".$args['insectsPerRow']."));
	var no_units = 4;
	var no_tens = 2;
	var no_hundreds = 1;
	if(numPages > 1) {
		var item;
		var itemList = jQuery('<div class=\"item-list\"></div>').appendTo('#results-insects-results');
		var pageCtrl = jQuery('<ul>').addClass('pager').appendTo(itemList);
		for (var j = pageNum - no_units; j<= pageNum + no_units; j++){
			if(j <= numPages && j >= 1){
				if(j == pageNum)
					jQuery('<li class=\"pager-current\">'+pageNum+'</li>').appendTo(pageCtrl);
				else {
					item = jQuery('<li class=\"pager-item\"></li>').attr('value',j).click(function(){setInsectPage(jQuery(this).attr('value'))}).appendTo(pageCtrl);
					jQuery('<a class=\"active\">'+j+'</a>').appendTo(item);
				}
  			}
		}
		var start = Math.ceil(j/10)*10;
		for (j = start; j< start + no_tens*10; j=j+10){
			if(j <= numPages){
				item = jQuery('<li class=\"pager-item\"></li>').attr('value',j).click(function(){setInsectPage(jQuery(this).attr('value'))}).appendTo(pageCtrl);
				jQuery('<a class=\"active\">'+j+'</a>').appendTo(item);
			}
		}
		start = Math.ceil(j/100)*100;
		for (j = start; j< start + no_hundreds*100; j=j+100){
			if(j <= numPages){
				item = jQuery('<li class=\"pager-item\"></li>').attr('value',j).click(function(){setInsectPage(jQuery(this).attr('value'))}).appendTo(pageCtrl);
				jQuery('<a class=\"active\">'+j+'</a>').appendTo(item);
			}
		}
		if(pageNum != numPages){
			item = jQuery('<li class=\"pager-next\"></li>').attr('value',pageNum+1).click(function(){setInsectPage(jQuery(this).attr('value'))}).appendTo(pageCtrl);
			jQuery('<a class=\"active\">&nbsp;</a>').appendTo(item);
  			item = jQuery('<li class=\"pager-last\"></li>').attr('value',numPages).click(function(){setInsectPage(jQuery(this).attr('value'))}).appendTo(pageCtrl);
			jQuery('<a class=\"active\">'+numPages+'</a>').appendTo(item);
  		}
		start = Math.floor((pageNum - no_units -1)/10)*10;
		for (j = start; j> start - no_tens*10; j=j-10){
			if(j >= 1){
				item = jQuery('<li class=\"pager-item\"></li>').attr('value',j).click(function(){setInsectPage(jQuery(this).attr('value'))}).prependTo(pageCtrl);
				jQuery('<a class=\"active\">'+j+'</a>').appendTo(item);
			}
		}
		start = Math.floor(j/100)*100;
		for (j = start; j> start - no_hundreds*100; j=j-100){
			if(j >= 1){
				item = jQuery('<li class=\"pager-item\"></li>').attr('value',j).click(function(){setInsectPage(jQuery(this).attr('value'))}).prependTo(pageCtrl);
				jQuery('<a class=\"active\">'+j+'</a>').appendTo(item);
			}
		}
		if(pageNum != 1){
			item = jQuery('<li class=\"pager-previous\"></li>').attr('value',pageNum-1).click(function(){setInsectPage(jQuery(this).attr('value'))}).prependTo(pageCtrl);
			jQuery('<a class=\"active\">&nbsp;</a>').appendTo(item);
  			item = jQuery('<li class=\"pager-first\"></li>').click(function(){setInsectPage(1)}).prependTo(pageCtrl);
			jQuery('<a class=\"active\">&nbsp;</a>').appendTo(item);
  		}
  		pageCtrl.find('li').filter(':first').addClass('first');
  		pageCtrl.find('li').filter(':last').addClass('last');
	}
    for (var i = (pageNum-1)*".$args['insectsRowsPerPage']."*".$args['insectsPerRow'].", first = true; i < searchResults.features.length && i < pageNum*".$args['insectsRowsPerPage']."*".$args['insectsPerRow']."; i++, first = false){
		addInsect(i, searchResults.features[i].attributes, (i+1)/".$args['insectsPerRow']." == parseInt((i+1)/".$args['insectsPerRow']."), first);
	}
	if(numPages > 1) {
		itemList.clone(true).appendTo('#results-insects-results');
	}
}

// searchLayer in map is used for georeferencing.
// map editLayer is switched off. TODO: need to switch off click control
searchLayer = null;
inseeLayer = null;
polygonLayer = null;     
locationLayer = null;
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


flowerIDstruc = {
	type: 'flower',
	mainForm: 'form#fo-new-flower-id-form',
	timeOutTimer: null,
	pollTimer: null,
	pollFile: '',
	invokeURL: '".$args['ID_tool_flower_url']."',
	pollURL: '".str_replace("{HOST}", $_SERVER['HTTP_HOST'], $args['ID_tool_flower_poll_dir'])."',
	name: 'flowerIDstruc',
	determinationType: '".(user_access('IForm n'.$node->nid.' flower expert') ? 'C' : 'A')."',
	taxaList: flowerTaxa
};

toolPoller = function(toolStruct){
	if(toolStruct.pollFile == '') return;
	toolStruct.pollTimer = setTimeout('toolPoller('+toolStruct.name+');', ".$args['ID_tool_poll_interval'].");
	jQuery.ajax({
	 url: toolStruct.pollURL+toolStruct.pollFile,
	 toolStruct: toolStruct,
	 success: function(data){
	  pollReset(this.toolStruct);
	  var da = data.split('\\n');
      jQuery(this.toolStruct.mainForm+' [name=determination\\:taxon_details]').val(da[2]); // Stores the state of identification, which details how the identification was arrived at within the tool.
	  da[1] = da[1].replace(/\\\\\\\\i\{\}/g, '').replace(/\\\\\\\\i0\{\}/g, '').replace(/\\\\/g, '');
	  var items = da[1].split(':');
	  var count = items.length;
	  if(items[count-1] == '') count--;
	  if(items[count-1] == '') count--;
	  if(count <= 0){
	  	// no valid stuff so blank it all out.
	  	jQuery('#'+this.toolStruct.type+'_taxa_list').append(\"".lang::get('LANG_Taxa_Unknown_In_Tool')."\");
	  	jQuery(this.toolStruct.mainForm+' [name=determination\\:determination_type]').val('X'); // Unidentified.
      } else {
      	var resultsIDs = [];
      	var resultsText = \"".lang::get('LANG_Taxa_Returned')."<br />{ \";
      	var notFound = '';
		for(var j=0; j < count; j++){
			var found = false;
			itemText = items[j].replace(/</g, '&lt;').replace(/>/g, '&gt;');
			for(i = 0; i< this.toolStruct.taxaList.length; i++){
				if(this.toolStruct.taxaList[i].taxon == itemText){
					resultsIDs.push(this.toolStruct.taxaList[i].id);
					resultsText = resultsText + (j == 0 ? '' : '<br />&nbsp;&nbsp;') + itemText;
					found = true;
  				}
  			};
  			if(!found){
  				notFound = (notFound == '' ? '' : notFound + ', ') + itemText;
  			}
  		}
		jQuery('#'+this.toolStruct.type+'_taxa_list').append(resultsText+ ' }');
		jQuery('#'+this.toolStruct.type+'-id-button').data('toolRetValues', resultsIDs);
	  	if(notFound != ''){
			var comment = jQuery(this.toolStruct.mainForm+' [name=determination\\:comment]');
			comment.val('".lang::get('LANG_ID_Unrecognised')." '+notFound+' '+comment.val());
		}
  	  }
  	 }
    });
};

pollReset = function(toolStruct){
	clearTimeout(toolStruct.timeOutTimer);
	clearTimeout(toolStruct.pollTimer);
	jQuery('#'+toolStruct.type+'-id-cancel').hide();
	jQuery('#'+toolStruct.type+'-id-button').show();
	toolStruct.pollFile='';
	toolStruct.timeOutTimer = null;
	toolStruct.pollTimer = null;
};

idButtonPressed = function(toolStruct){
	jQuery(toolStruct.mainForm+' [name=determination\\:determination_type]').val(toolStruct.determinationType);
	jQuery('#'+toolStruct.type+'-id-button').data('toolRetValues', []);
	jQuery(toolStruct.mainForm+' [name=determination\\:taxon_details]').val('');
	jQuery('#'+toolStruct.type+'_taxa_list').empty();
	jQuery(toolStruct.mainForm+' [name=determination\\:taxa_taxon_list_id]').val('');
	jQuery('#'+toolStruct.type+'-id-cancel').show();
	jQuery('#'+toolStruct.type+'-id-button').hide();
	var d = new Date;
	var s = d.getTime();
	toolStruct.pollFile = '".session_id()."_'+s.toString()
	clearTimeout(toolStruct.timeOutTimer);
	clearTimeout(toolStruct.pollTimer);
	window.open(toolStruct.invokeURL+toolStruct.pollFile,'','');
	toolStruct.pollTimer = setTimeout('toolPoller('+toolStruct.name+');', ".$args['ID_tool_poll_interval'].");
	toolStruct.timeOutTimer = setTimeout('toolReset('+toolStruct.name+');', ".$args['ID_tool_poll_timeout'].");
};
jQuery('#flower-id-button').click(function(){
	idButtonPressed(flowerIDstruc);
});
jQuery('#flower-id-cancel').click(function(){
	pollReset(flowerIDstruc);
});

jQuery('#flower-id-cancel').hide();

taxonChosen = function(toolStruct){
	jQuery('#'+toolStruct.type+'-id-button').data('toolRetValues', []);
	jQuery(toolStruct.mainForm+' [name=determination\\:taxon_details]').val('');
	jQuery('#'+toolStruct.type+'_taxa_list').empty();
	jQuery(toolStruct.mainForm+' [name=determination\\:comment]').val('');
  	jQuery(toolStruct.mainForm+' [name=determination\\:determination_type]').val(toolStruct.determinationType);
};
jQuery('form#fo-new-flower-id-form select[name=determination\\:taxa_taxon_list_id]').change(function(){
	pollReset(flowerIDstruc);
	taxonChosen(flowerIDstruc);
});

insectIDstruc = {
	type: 'insect',
	mainForm: 'form#fo-new-insect-id-form',
	timeOutTimer: null,
	pollTimer: null,
	pollFile: '',
	invokeURL: '".$args['ID_tool_insect_url']."',
	pollURL: '".str_replace("{HOST}", $_SERVER['HTTP_HOST'], $args['ID_tool_insect_poll_dir'])."',
	name: 'insectIDstruc',
	determinationType: '".(user_access('IForm n'.$node->nid.' insect expert') ? 'C' : 'A')."',
	taxaList: insectTaxa
};

jQuery('#insect-id-button').click(function(){
	idButtonPressed(insectIDstruc);
});
jQuery('#insect-id-cancel').click(function(){
	pollReset(insectIDstruc);
});
jQuery('#insect-id-cancel').hide();
jQuery('form#fo-new-insect-id-form select[name=determination\\:taxa_taxon_list_id]').change(function(){
	pollReset(insectIDstruc);
	taxonChosen(insectIDstruc);
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
	jQuery('#results-insects-header,#results-insects-results').hide();
	jQuery('#results-collections-header,#results-collections-results').show();
	jQuery('#results-collections-header').addClass('ui-state-active').removeClass('ui-state-default');
	runSearch(true);
});
jQuery('#search-insects-button').click(function(){
	jQuery('#results-collections-header,#results-collections-results').hide();
	jQuery('#results-insects-header,#results-insects-results').show();
	jQuery('#results-insects-header').addClass('ui-state-active').removeClass('ui-state-default');
	runSearch(false);
});

combineOR = function(ORgroup){
	if(ORgroup.length > 1){
		return new OpenLayers.Filter.Logical({
				type: OpenLayers.Filter.Logical.OR,
				filters: ORgroup
			});
	}
	return ORgroup[0];
};

encodeMap = function(){
	var features = [];
	if(inseeLayer != null && inseeLayer.features.length > 0)
		features = inseeLayer.features;
	else if(polygonLayer != null && polygonLayer.features.length > 0)
		features = polygonLayer.features
	else {
		var mapBounds = jQuery('#map')[0].map.getExtent();
		var feature = new OpenLayers.Feature.Vector(mapBounds.toGeometry());
		features = [feature];
	}
      var format=new OpenLayers.Format.GML.v2({featureName: 'user', featureType: 'user',
          featureNS: 'user', featurePrefix: 'user'});
      return format.write(features);
    }

decodeMap = function(string){
      var format=new OpenLayers.Format.GML.v2({featureName: 'user', featureType: 'user',
          featureNS: 'user', featurePrefix: 'user'});
      var features=format.read(string);
	if(inseeLayer != null) inseeLayer.destroyFeatures();
	if(polygonLayer!= null) polygonLayer.destroyFeatures();
	var div = jQuery('#map')[0];
	div.map.searchLayer.destroyFeatures();
	polygonLayer.addFeatures(features, {});
	var bounds= polygonLayer.getDataExtent();
    div.map.zoomToExtent(bounds);

}
    
runSearch = function(forCollections){
  	var ORgroup = [];
  	if(searchLayer != null)
		searchLayer.destroy();
    jQuery('#results-collections-results,#results-insects-results').empty();
	jQuery('#focus-occurrence,#focus-collection').hide();
	var filters = [];

	// By default restrict selection to area displayed on map. When using the georeferencing system the map searchLayer
	// will contain a single point zoomed in appropriately.
	var mapBounds = jQuery('#map')[0].map.getExtent();
	if (mapBounds != null) filters.push(new OpenLayers.Filter.Spatial({type: OpenLayers.Filter.Spatial.BBOX, property: 'geom', value: mapBounds}));
	// should only be one entry in the inseeLayer
	if(inseeLayer != null)
  		if(inseeLayer.features.length > 0)
			filters.push(new OpenLayers.Filter.Spatial({type: OpenLayers.Filter.Spatial.WITHIN, property: 'geom',value: inseeLayer.features[0].geometry}));
	if(polygonLayer != null)
  		if(polygonLayer.features.length > 0){
  			ORgroup = [];
  			for(i=0; i< polygonLayer.features.length; i++)
				ORgroup.push(new OpenLayers.Filter.Spatial({type: OpenLayers.Filter.Spatial.WITHIN, property: 'geom', value: polygonLayer.features[i].geometry}));
		  	if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
		}

  	// filters.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.EQUAL_TO, property: 'survey_id', value: '".$args['survey_id']."' }));

  	var user = jQuery('input[name=username]').val();
  	if(user != '') filters.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'username', value: user}));
  	var start_date = jQuery('input[name=real_start_date]').val();
  	var end_date = jQuery('input[name=real_end_date]').val();
  	if(start_date != '".lang::get('click here')."' && start_date != '')
  		filters.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.GREATER_THAN_OR_EQUAL_TO, property: 'datefin', value: start_date}));
  	if(end_date != '".lang::get('click here')."' && end_date != '')
  		filters.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LESS_THAN_OR_EQUAL_TO, property: 'datedebut', value: end_date}));
 	
  	var flower = jQuery('select[name=flower\\:taxa_taxon_list_id]').val();
  	if(flower != '') filters.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'flower_taxon_ids', value: '*|'+flower+'|*'}));
  	flower = jQuery('[name=flower\\:taxon_extra_info]').val();
  	if(flower != '' && flower != '".lang::get('LANG_More_Precise')."')
  		filters.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'taxons_fleur_precise', value: '*'+flower+'*' }));

  	ORgroup = [];
  	jQuery('#flower-filter-body').find('[name^=occAttr:".$args['flower_type_attr_id']."]').filter('[checked]').each(function(index, elem){
  		ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.EQUAL_TO, property: 'flower_type_id', value: elem.value}));
  	});
  	if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
  	ORgroup = [];
  	jQuery('#flower-filter-body').find('[name^=locAttr:".$args['habitat_attr_id']."]').filter('[checked]').each(function(index, elem){
  		ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'habitat_ids', value: '*|'+elem.value+'|*'}));
  	});
  	if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));

  	var insect = jQuery('select[name=insect\\:taxa_taxon_list_id]').val();
  	if(insect != '') filters.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'insect_taxon_ids', value: '*|'+insect+'|*'}));
  	insect = jQuery('[name=insect\\:taxon_extra_info]').val();
  	if(insect != '' && insect != '".lang::get('LANG_More_Precise')."')
  		filters.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'taxons_insecte_precise', value: '*'+insect+'*'}));

  	ORgroup = [];
  	jQuery('#conditions-filter-body').find('[name^=smpAttr:".$args['sky_state_attr_id']."]').filter('[checked]').each(function(index, elem){
  		ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'sky_ids', value: '*|'+elem.value+'|*'}));
  	});
  	if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
  	
  	ORgroup = [];
  	jQuery('#conditions-filter-body').find('[name^=smpAttr:".$args['temperature_attr_id']."]').filter('[checked]').each(function(index, elem){
  		ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'temp_ids', value: '*|'+elem.value+'|*'}));
  	});
  	if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
  	
  	ORgroup = [];
  	jQuery('#conditions-filter-body').find('[name^=smpAttr:".$args['wind_attr_id']."]').filter('[checked]').each(function(index, elem){
  		ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'wind_ids', value: '*|'+elem.value+'|*'}));
  	});
  	if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
  	
  	ORgroup = [];
  	jQuery('#conditions-filter-body').find('[name^=smpAttr:".$args['shade_attr_id']."]').filter('[checked]').each(function(index, elem){
  		ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'shade_ids', value: '*|'+elem.value+'|*'}));
  	});
  	if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
  	if(forCollections){
		properties = ['collection_id','datedebut','datefin','geom','nom','image_de_environment','image_de_la_fleur','flower_id']
		feature = 'spipoll_collections_cache_view';
  	} else {
  		feature = 'spipoll_insects_cache_view';
  		properties = ['insect_id','collection_id','geom','image_d_insecte'];
  	}
	var strategy = new OpenLayers.Strategy.Fixed({preload: false, autoActivate: false});
	searchLayer = new OpenLayers.Layer.Vector('Search Layer', {
          strategies: [strategy],
          displayInLayerSwitcher: false,
	      protocol: new OpenLayers.Protocol.WFS({
              url: '".str_replace("{HOST}", $_SERVER['HTTP_HOST'], $args['search_url'])."',
              featurePrefix: '".$args['search_prefix']."',
              featureType: feature,
              geometryName:'geom',
              featureNS: '".$args['search_ns']."',
              srsName: 'EPSG:900913',
              version: '1.1.0',   
              maxFeatures: ".$args['max_features'].",
              propertyNames: properties,
              sortBy: 'datedebut' // not supported by Openlayers, so have to use orderby in a DB view.
		  })
	});
	if(forCollections) {
		jQuery('#results-collections-results').empty().append('<div class=\"collection-loading-panel\" ><img src=\"".helper_config::$base_url."media/images/ajax-loader2.gif\" />".lang::get('loading')."...</div>');
		searchLayer.events.register('featuresadded', {}, function(a1){
			searchResults = a1;
			searchResults.type = 'C';
			if(searchResults.features.length >= ".$args['max_features']."){
				alert(\"".lang::get('LANG_Max_Features_Reached')."\");
			}
			setCollectionPage(1);
		});
		searchLayer.events.register('loadend', {}, function(){
			if(searchLayer.features.length == 0){
				jQuery('#results-collections-results').empty().text('".lang::get('LANG_No_Collection_Results')."');
			}
		});
	} else {
		jQuery('#results-insects-results').empty().append('<div class=\"insect-loading-panel\" ><img src=\"".helper_config::$base_url."media/images/ajax-loader2.gif\" />".lang::get('loading')."...</div>');
		searchLayer.events.register('featuresadded', {}, function(a1){
			searchResults = a1;
			searchResults.type = 'I';
			if(searchResults.features.length >= ".$args['max_features']."){
				alert(\"".lang::get('LANG_Max_Features_Reached')."\");
			}
			setInsectPage(1);
		});
		searchLayer.events.register('loadend', {}, function(){
			if(searchLayer.features.length == 0){
				jQuery('#results-insects-results').empty().text('".lang::get('LANG_No_Insect_Results')."');
			}
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

errorPos = null;
clearErrors = function(formSel) {
	jQuery(formSel).find('.inline-error').remove();
	errorPos = null;
};

myScrollToError = function(){
	jQuery('.inline-error,.error').filter(':visible').prev().each(function(){
		if(errorPos == null || jQuery(this).offset().top < errorPos){
			errorPos = jQuery(this).offset().top;
			window.scroll(0,errorPos);
		}
	});
};

validateRequiredField = function(name, formSel){
    var control = jQuery(formSel).find('[name='+name+']');
    if(control.val() == '') {
        var label = $('<p/>')
				.attr({'for': name})
				.addClass('inline-error')
				.html($.validator.messages.required);
		label.insertBefore(control);
		return false;
    }
    return true;
}

var insect_alert_object = {
	insect_id: null,
	insect_image_path: null,
	date: null,
	user_id: null,
	collection_user_id: null,
	details: []
};
var flower_alert_object = {
	flower_id: null,
	flower_image_path: null,
	date: null,
	user_id: null,
	collection_user_id: null,
	details: []
};

var collection_preferred_object = {
	collection_id: null,
	collection_name: null,
	flower_id: null,
	flower_image_path: null,
	environment_image_path: null,
	date: null,
	location_description: null,
	user_id: null,
	insects: []
};

jQuery('form#fo-express-doubt-form').ajaxForm({
	async: false,
	dataType:  'json', 
	beforeSubmit:   function(data, obj, options){
		if(!confirm(\"".lang::get('LANG_Confirm_Express_Doubt')."\")){
			return FALSE;
		}	
		var toolValues = jQuery('#fo-doubt-button').data('toolRetValues');
		if(toolValues.length == 0)
			data.push({name: 'determination\\:taxa_taxon_list_id_list[]', value: ''});
		else {
			for(var i = 0; i<toolValues.length; i++){
				data.push({name: 'determination\\:taxa_taxon_list_id_list[]', value: toolValues[i]});
			}
		}
  		jQuery('#doubt_submit_button').addClass('loading-button');
   		return true;
	},
	success:   function(data){
		if(data.error == undefined){
			jQuery('#fo-express-doubt').removeClass('ui-accordion-content-active');
			loadDeterminations(jQuery('[name=determination\\:occurrence_id]').val(), '#fo-id-history', '#fo-current-id', insect_alert_object.insect_id == null ? 'form#fo-new-flower-id-form' : 'form#fo-new-insect-id-form', function(args, type){
			";
	if($args['alert_js_function'] != '') {
		data_entry_helper::$javascript .= "
				if(type == 'F'){
					flower_alert_object.details = [];
					for(i=0; i< args.length && i < 5; i++){
						flower_alert_object.details.push({flower_taxa: args[i].taxa, date: args[i].date, user_id: args[i].user_id});
						flower_alert_object.date = flower_alert_object.details[0].date;
					}
					flower_alert_object.details = JSON.stringify(flower_alert_object.details);
					".$args['alert_js_function']."({alert_type: 'D', type: 'F', flower: flower_alert_object});
				} else {
					insect_alert_object.details = [];
					for(i=0; i< args.length && i < 5; i++){
						insect_alert_object.details.push({insect_taxa: args[i].taxa, date: args[i].date, user_id: args[i].user_id});
						insect_alert_object.date = insect_alert_object.details[0].date;
					}
					insect_alert_object.details = JSON.stringify(insect_alert_object.details);
					".$args['alert_js_function']."({alert_type: 'D', type: 'I', insect: insect_alert_object});
				}";
	}
	data_entry_helper::$javascript .= "
			}, insect_alert_object.insect_id == null ? ".(user_access('IForm n'.$node->nid.' flower expert') ? '1' : '0')." : ".(user_access('IForm n'.$node->nid.' insect expert') ? '1' : '0').",
			insect_alert_object.insect_id == null ? ".(user_access('IForm n'.$node->nid.' flag dubious flower') ? '1' : '0')." : ".(user_access('IForm n'.$node->nid.' flag dubious insect') ? '1' : '0').",
			insect_alert_object.insect_id == null ? flowerTaxa : insectTaxa,
			insect_alert_object.insect_id == null ? 'F' : 'I');
		} else {
			alert(data.error);
		}
	},
	complete: function (){
  		jQuery('.loading-button').removeClass('loading-button');
  	} 
});

jQuery('form#fo-new-insect-id-form').ajaxForm({ 
	async: false,
	dataType:  'json', 
	beforeSubmit:   function(data, obj, options){
		var valid = true;
		clearErrors('form#fo-new-insect-id-form');
		if (!jQuery('form#fo-new-insect-id-form input').valid()) { valid = false; }
		if (jQuery('form#fo-new-insect-id-form [name=determination\\:taxon_details]').val() == ''){
			if (!validateRequiredField('determination\\:taxa_taxon_list_id', '#fo-new-insect-id-form')) {
				valid = false;
  			} else {
				data.push({name: 'determination\\:taxa_taxon_list_id_list[]', value: ''});
  			}
		} else {
			var toolValues = jQuery('#insect-id-button').data('toolRetValues');
			for(var i = 0; i<toolValues.length; i++){
				data.push({name: 'determination\\:taxa_taxon_list_id_list[]', value: toolValues[i]});
			}			
		}
   		if ( valid == false ) {
			myScrollToError();
			return false;
		};
  		jQuery('#insect_id_submit_button').addClass('loading-button');
   		return true;
	},
	success:   function(data){
		if(data.error == undefined){
			jQuery(insectIDstruc.mainForm+' [name=determination\\:determination_type]').val(insectIDstruc.determinationType);
			jQuery(insectIDstruc.mainForm+' [name=determination\\:taxa_taxon_list_id],[name=determination\\:comment],[name=determination\\:taxon_details],[name=determination\\:taxon_extra_info]').val('');
			jQuery('#insect_taxa_list').empty();
			jQuery('#fo-new-insect-id').removeClass('ui-accordion-content-active');
			loadDeterminations(jQuery('[name=determination\\:occurrence_id]').val(), '#fo-id-history', '#fo-current-id', 'form#fo-new-insect-id-form', function(args, type){
			";
	if($args['alert_js_function'] != '') {
		data_entry_helper::$javascript .= "
				insect_alert_object.details = [];
				for(i=0; i< args.length && i < 5; i++){
					insect_alert_object.details.push({insect_taxa: args[i].taxa, date: args[i].date, user_id: args[i].user_id});
					insect_alert_object.date = insect_alert_object.details[0].date;
				}
				insect_alert_object.details = JSON.stringify(insect_alert_object.details);
				".$args['alert_js_function']."({alert_type: 'R', type: 'I', insect: insect_alert_object});";
	}
	data_entry_helper::$javascript .= "
			  			}, ".(user_access('IForm n'.$node->nid.' insect expert') ? '1' : '0').", ".(user_access('IForm n'.$node->nid.' flag dubious insect') ? '1' : '0').", insectTaxa, 'I');
		} else {
			alert(data.error);
		}
	},
	complete: function (){
  		jQuery('.loading-button').removeClass('loading-button');
  	}
	
});
jQuery('form#fo-new-flower-id-form').ajaxForm({ 
	async: false,
	dataType:  'json', 
	beforeSubmit:   function(data, obj, options){
		var valid = true;
		clearErrors('form#fo-new-flower-id-form');
		if (!jQuery('form#fo-new-flower-id-form input').valid()) { valid = false; }
		if (jQuery('form#fo-new-flower-id-form [name=determination\\:taxon_details]').val() == ''){
			if (!validateRequiredField('determination\\:taxa_taxon_list_id', '#fo-new-flower-id-form')) {
				valid = false;
  			} else {
				data.push({name: 'determination\\:taxa_taxon_list_id_list[]', value: ''});
  			}
		} else {
			var toolValues = jQuery('#flower-id-button').data('toolRetValues');
			for(var i = 0; i<toolValues.length; i++){
				data.push({name: 'determination\\:taxa_taxon_list_id_list[]', value: toolValues[i]});
			}			
		}
   		if ( valid == false ) {
			myScrollToError();
			return false;
		};
  		jQuery('#flower_id_submit_button').addClass('loading-button');
   		return true;
	},
	success:   function(data){
		if(data.error == undefined){
			jQuery(flowerIDstruc.mainForm+' [name=determination\\:determination_type]').val(flowerIDstruc.determinationType);
			jQuery(flowerIDstruc.mainForm+' [name=determination\\:taxa_taxon_list_id],[name=determination\\:comment],[name=determination\\:taxon_details],[name=determination\\:taxon_extra_info]').val('');
			jQuery('#flower_taxa_list').empty();
			jQuery('#fo-new-flower-id').removeClass('ui-accordion-content-active');
			loadDeterminations(jQuery('[name=determination\\:occurrence_id]').val(), '#fo-id-history', '#fo-current-id', 'form#fo-new-flower-id-form', function(args, type){
			";
	if($args['alert_js_function'] != '') {
		data_entry_helper::$javascript .= "
				flower_alert_object.details = [];
				for(i=0; i< args.length && i < 5; i++) {
					flower_alert_object.details.push({flower_taxa: args[i].taxa, date: args[i].date, user_id: args[i].user_id});
					flower_alert_object.date = flower_alert_object.details[0].date;
				}
				flower_alert_object.details = JSON.stringify(flower_alert_object.details);
				".$args['alert_js_function']."({alert_type: 'R', type: 'F', flower: flower_alert_object});";
	}
	data_entry_helper::$javascript .= "
			  			}, ".(user_access('IForm n'.$node->nid.' flower expert') ? '1' : '0').", ".(user_access('IForm n'.$node->nid.' flag dubious flower') ? '1' : '0').", flowerTaxa, 'F');
		} else {
			alert(data.error);
		}
	},
	complete: function (){
  		jQuery('.loading-button').removeClass('loading-button');
  	}
	
});
jQuery('#fo-new-comment-form').ajaxForm({ 
	async: false,
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
			loadComments(jQuery('[name=occurrence_comment\\:occurrence_id]').val(), '#fo-comment-list', 'occurrence_comment', 'occurrence_id', 'occurrence-comment-block', 'occurrence-comment-body', true);
  		} else {
			alert(data.error);
		}
	} 
});
jQuery('#fc-new-comment-form').ajaxForm({ 
	async: false,
	dataType:  'json', 
	beforeSubmit:   function(data, obj, options){
		if (!jQuery('form#fc-new-comment-form').valid()) { return false; }
		return true;
	},
	success:   function(data){
		if(data.error == undefined){
			jQuery('[name=sample_comment\\:comment]').val('');
			jQuery('#fc-new-comment').removeClass('ui-accordion-content-active');
			loadComments(jQuery('[name=sample_comment\\:sample_id]').val(), '#fc-comment-list', 'sample_comment', 'sample_id', 'sample-comment-block', 'sample-comment-body', true);
  		} else {
			alert(data.error);
		}
	} 
});

loadSampleAttributes = function(keyValue){
    jQuery('#fo-insect-start-time,#fo-insect-end-time,#fo-insect-sky,#fo-insect-temp,#fo-insect-wind,#fo-insect-shade').empty();
	ajaxStack.push($.getJSON(\"".$svcUrl."/data/sample_attribute_value\"  +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&sample_id=\" + keyValue + \"&REMOVEABLEJSONP&callback=?\", function(attrdata) {
		if(!(attrdata instanceof Array)){
   			alertIndiciaError(attrdata);
   		} else if (attrdata.length>0) {
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
  							if(attrdata[i].value == '1'){
								jQuery('#fo-insect-shade').append(\"".lang::get('Yes')."\");
							} else {
								jQuery('#fo-insect-shade').append(\"".lang::get('No')."\");
  							}
							break;
  					}
				}
			}
		}
	}));
}
loadOccurrenceAttributes = function(keyValue){
    jQuery('#focus-flower-type').empty();
	ajaxStack.push($.getJSON(\"".$svcUrl."/data/occurrence_attribute_value\"  +
			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&occurrence_id=\" + keyValue + \"&iso=".$language."&REMOVEABLEJSONP&callback=?\", function(attrdata) {
		if(!(attrdata instanceof Array)){
   			alertIndiciaError(attrdata);
   		} else if (attrdata.length>0) {
			for(i=0; i< attrdata.length; i++){
				if (attrdata[i].id){
					switch(parseInt(attrdata[i].occurrence_attribute_id)){
						case ".$args['flower_type_attr_id'].":
							jQuery('<span>'+attrdata[i].value+'</span>').appendTo('#focus-flower-type');
							break;
  	}}}}}));
}
loadLocationAttributes = function(keyValue){
    jQuery('#focus-habitat').empty();
	ajaxStack.push($.getJSON(\"".$svcUrl."/data/location_attribute_value\"  +
			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&location_id=\" + keyValue + \"&iso=".$language."&REMOVEABLEJSONP&callback=?\", function(attrdata) {
		if(!(attrdata instanceof Array)){
   			alertIndiciaError(attrdata);
   		} else if (attrdata.length>0) {
   			var habitat_string = '';
			for(i=0; i< attrdata.length; i++){
				if (attrdata[i].id){
					switch(parseInt(attrdata[i].location_attribute_id)){
						case ".$args['habitat_attr_id'].":
							if (attrdata[i].raw_value > 0) habitat_string = (habitat_string == '' ? attrdata[i].value : (habitat_string + ' | ' + attrdata[i].value));
							break;
			}}}
			jQuery('<span>'+habitat_string+'</span>').appendTo('#focus-habitat');
  }}));
}

insertImage = function(path, target, ratio, callback){
    var img = new Image();
	jQuery(img).load(function () {
        target.append(this);
        if(this.width/this.height > ratio){
	    	jQuery(this).css('width', '100%').css('height', 'auto').css('vertical-align', 'middle').css('margin-left', 'auto').css('margin-right', 'auto').css('display', 'block');
  		} else {
	        jQuery(this).css('width', (100*this.width/(this.height*ratio))+'%').css('height', 'auto').css('vertical-align', 'middle').css('margin-left', 'auto').css('margin-right', 'auto').css('display', 'block');
  		}
  		if(callback)
  			callback(this);
	}).attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."'+path);
}

loadImage = function(imageTable, key, keyValue, target, imageRatio, callback, prepend, imgCallback){
    jQuery(target).empty();
    ajaxStack.push(jQuery.ajax({ 
        type: \"GET\",
        myTarget: target,
        myCallback: callback,
        myPrepend: prepend,
        myImgCallback: imgCallback,
        url: \"".$svcUrl."/data/\" + imageTable +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&\" + key + \"=\" + keyValue + \"&REMOVEABLEJSONP&callback=?\", 
        success: function(imageData) {
		  if(!(imageData instanceof Array)){
   			alertIndiciaError(imageData);
   		  } else if (imageData.length>0) {
 			insertImage(this.myPrepend+imageData[0].path, jQuery(this.myTarget), imageRatio, this.myImgCallback);
			if(this.myCallback) this.myCallback(imageData[0]);
		  }},
		dataType: 'json'
	}));
}

loadDeterminations = function(keyValue, historyID, currentID, lookup, callback, expert, can_doubt, taxaList, type){
    jQuery(historyID).empty().append('<strong>".lang::get('LANG_History_Title')."</strong>');
	jQuery(currentID).empty();
	jQuery('#poll-banner').empty();
	jQuery('#fo-doubt-button').hide();
	jQuery('#fo-express-doubt-form').find('[name=determination:taxon_extra_info]').val('');
	jQuery('#fo-express-doubt-form').find('[name=determination:taxa_taxon_list_id]').val('');
	jQuery('#fo-doubt-button').data('toolRetValues',[]);
	jQuery('.poll-id-button').data('toolRetValues',[]);
	jQuery('#fo-warning').addClass('occurrence-ok').removeClass('occurrence-dubious').removeClass('occurrence-unknown');
	jQuery('.taxa_list').empty();
    ajaxStack.push(jQuery.ajax({ 
        type: \"GET\", 
        url: \"".$svcUrl."/data/determination?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&reset_timeout=true&orderby=id&REMOVEABLEJSONP&callback=?&occurrence_id=\" + keyValue,
        myCurrentID: currentID,
        myHistoryID: historyID,
        myCallback: callback,
        myLookup: lookup,
		dataType: 'json',
        success: function(detData) {
   		  var callbackArgs = [];
   		  if(!(detData instanceof Array)){
   			alertIndiciaError(detData);
   		  } else if (detData.length>0) {
			var i = detData.length-1;
			var callbackEntry = {date: detData[i].updated_on, user_id: detData[i].cms_ref, taxa: []};
   			jQuery('#fo-express-doubt-form').find('[name=determination\\:occurrence_id]').val(detData[i].occurrence_id);
   			var string = '';
   			var resultsText = \"".lang::get('LANG_Taxa_Returned')."<br />{ \";
			if(detData[i].taxon != '' && detData[i].taxon != null){
				string = detData[i].taxon;
				callbackEntry.taxa.push(detData[i].taxon);
  			}
			jQuery('#fo-express-doubt-form').find('[name=determination\\:taxa_taxon_list_id]').val(detData[i].taxa_taxon_list_id);
			jQuery(this.myLookup).find('[name=determination:taxa_taxon_list_id]').val(detData[i].taxa_taxon_list_id);
			if(detData[i].taxa_taxon_list_id_list != null && detData[i].taxa_taxon_list_id_list != '' && detData[i].taxa_taxon_list_id_list != '{}'){
			  	resultsIDs = detData[i].taxa_taxon_list_id_list.substring(1, detData[i].taxa_taxon_list_id_list.length - 1).split(',');
			  	for(j=0; j < resultsIDs.length; j++){
					for(k = 0; k< taxaList.length; k++)
						if(taxaList[k].id == resultsIDs[j]) {
							string = (string == '' ? '' : string + ', ') + taxaList[k].taxon;
							callbackEntry.taxa.push(taxaList[k].taxon);
							resultsText = resultsText + (j == 0 ? '' : '<br />&nbsp;&nbsp;') + taxaList[k].taxon;
  						}
		  		}
	  			if(resultsIDs.length>1 || resultsIDs[0] != '') {
					jQuery('#fo-doubt-button').data('toolRetValues',resultsIDs);
					jQuery('.poll-id-button').data('toolRetValues',resultsIDs);
					jQuery('.taxa_list').append(resultsText+ ' }');
				}
			}
			callbackArgs.push(callbackEntry);
			jQuery('#poll-banner').append(string);
			if(detData[i].taxon_extra_info != '' && detData[i].taxon_extra_info != null){
				string = (string == '' ? '' : string + ' ') + '('+detData[i].taxon_extra_info+')';
			}
			jQuery('#fo-express-doubt-form').find('[name=determination\\:taxon_extra_info]').val(detData[i].taxon_extra_info);
			jQuery(this.myLookup).find('[name=determination:taxon_extra_info]').val(detData[i].taxon_extra_info);
			if(string != '')
				jQuery('<p><strong>'+string+ '</strong> ".lang::get('LANG_Comment_By')."' + detData[i].person_name + ' ' + convertDate(detData[i].updated_on,false) + '</p>').appendTo(this.myCurrentID)
			else
				jQuery('<p>".lang::get('LANG_Comment_By')."' + detData[i].person_name + ' ' + convertDate(detData[i].updated_on,false) + '</p>').appendTo(this.myCurrentID)
			if(detData[i].determination_type == 'A' && (expert || can_doubt)){
				jQuery('#fo-doubt-button').show();
			} else if(detData[i].determination_type == 'B'){
				jQuery(\"<p>".lang::get('LANG_Doubt_Expressed')."</p>\").appendTo(this.myCurrentID);
				jQuery('#fo-warning').removeClass('occurrence-ok').addClass('occurrence-dubious');
			} else if(detData[i].determination_type == 'C'){
				jQuery(\"<p>".lang::get('LANG_Determination_Valid')."</p>\").appendTo(this.myCurrentID);
				if(!expert)
					jQuery('.new-id-button').hide();
			} else if(detData[i].determination_type == 'I'){
				jQuery(\"<p>".lang::get('LANG_Determination_Incorrect')."</p>\").appendTo(this.myCurrentID);
				jQuery('#fo-warning').removeClass('occurrence-ok').addClass('occurrence-dubious');
			} else if(detData[i].determination_type == 'U'){
				jQuery(\"<p>".lang::get('LANG_Determination_Unconfirmed')."</p>\").appendTo(this.myCurrentID);
				jQuery('#fo-warning').removeClass('occurrence-ok').addClass('occurrence-dubious');
			} else if(detData[i].determination_type == 'X'){
				jQuery(\"<p>".lang::get('LANG_Determination_Unknown')."</p>\").appendTo(this.myCurrentID);
//				jQuery('#fo-warning').removeClass('occurrence-ok').addClass('occurrence-unknown');
			}
			if(detData[i].comment != '' && detData[i].comment != null){
				jQuery('<p>'+detData[i].comment+'</p>').appendTo(this.myCurrentID);
			}
			
			for(i=detData.length - 2; i >= 0; i--){ // deliberately miss last one, in reverse order
				callbackEntry = {date: detData[i].updated_on, user_id: detData[i].cms_ref, taxa: []};
				string = '';
				var item = jQuery('<div></div>').addClass('history-item').appendTo(this.myHistoryID);
				if(detData[i].taxon != '' && detData[i].taxon != null){
					string = detData[i].taxon;
		  			callbackEntry.taxa.push(detData[i].taxon);
  				}
				if(detData[i].taxa_taxon_list_id_list != '' && detData[i].taxa_taxon_list_id_list != null && detData[i].taxa_taxon_list_id_list != '{}'){
					var resultsIDs = detData[i].taxa_taxon_list_id_list.substring(1, detData[i].taxa_taxon_list_id_list.length - 1).split(',');
					for(j=0; j < resultsIDs.length; j++){
						for(k = 0; k< taxaList.length; k++)
							if(taxaList[k].id == resultsIDs[j]) {
								string = (string == '' ? '' : string + ', ') + taxaList[k].taxon;
								callbackEntry.taxa.push(taxaList[k].taxon);
							}
					}
				}
				if(detData[i].taxon_extra_info != '' && detData[i].taxon_extra_info != null){
					string = (string == '' ? '' : string + ' ') + '('+detData[i].taxon_extra_info+')' ;
				}
				string = convertDate(detData[i].updated_on,false) + ' : ' + string;
				jQuery('<p><strong>'+string+ '</strong> ".lang::get('LANG_Comment_By')."' + detData[i].person_name+'</p>').appendTo(item)
				if(detData[i].determination_type == 'B'){
					jQuery(\"<p>".lang::get('LANG_Doubt_Expressed')."</p>\").appendTo(item)
				} else if(detData[i].determination_type == 'I'){
					jQuery(\"<p>".lang::get('LANG_Determination_Incorrect')."</p>\").appendTo(item)
				} else if(detData[i].determination_type == 'U'){
					jQuery(\"<p>".lang::get('LANG_Determination_Unconfirmed')."</p>\").appendTo(item)
				} else if(detData[i].determination_type == 'X'){
					jQuery(\"<p>".lang::get('LANG_Determination_Unknown')."</p>\").appendTo(item)
				}
				if(detData[i].comment != '' && detData[i].comment != null){
					jQuery('<p>'+detData[i].comment+'</p>').appendTo(item);
				}
				callbackArgs.push(callbackEntry);
  			}
			
		  } else {
			jQuery('#fo-doubt-button').hide();
			jQuery('#fo-warning').removeClass('occurrence-ok').addClass('occurrence-unknown');
			jQuery('<p>".lang::get('LANG_No_Determinations')."</p>')
					.appendTo(this.myHistoryID);
			jQuery('<p>".lang::get('LANG_No_Determinations')."</p>')
					.appendTo(this.myCurrentID);
		  }
		  if(this.myCallback) this.myCallback(callbackArgs, type);
		}  
	}));
	// when the occurrence is loaded (eg in loadInsect) all determination:occurrence_ids are set.
	jQuery(lookup).find('[name=determination\\:determination_type]').val(expert ? 'C' : 'A');
};

loadComments = function(keyValue, block, table, key, blockClass, bodyClass, reset_timeout){
    jQuery(block).empty();
    ajaxStack.push(jQuery.ajax({ 
        type: \"GET\",
        url: \"".$svcUrl."/data/\" + table + \"?mode=json&view=list\" +
        	(reset_timeout ? \"&reset_timeout=true\" : \"\") + \"&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&\" + key + \"=\" + keyValue + \"&REMOVEABLEJSONP&callback=?\", 
        myBlock: block,
        myBlockClass: blockClass,
        myBodyClass: bodyClass,
		dataType: 'json',
        success: function(commentData) {
   		  if(!(commentData instanceof Array)){
   			alertIndiciaError(commentData);
   		  } else if (commentData.length>0) {
   			for(i=commentData.length - 1; i >= 0; i--){
	   			var newCommentDetails = jQuery('<div class=\"'+this.myBlockClass+'\"/>')
					.appendTo(block);
				jQuery('<span>".lang::get('LANG_Comment_By')."' + commentData[i].person_name + ' ' + convertDate(commentData[i].updated_on,false) + '</span>')
					.appendTo(newCommentDetails);
	   			var newComment = jQuery('<div class=\"'+bodyClass+'\"/>')
					.appendTo(this.myBlock);
				jQuery('<p>' + commentData[i].comment + '</p>')
					.appendTo(newComment);
			}
		  } else {
			jQuery('<p>".lang::get('LANG_No_Comments')."</p>')
					.appendTo(this.myBlock);
		  }
		}
    }));
};

collectionProcessing = function(keyValue, expert){
    ajaxStack.push($.getJSON(\"".$svcUrl."/data/sample_attribute_value\"  +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&sample_id=\" + keyValue + \"&REMOVEABLEJSONP&callback=?\", function(attrdata) {
		if(!(attrdata instanceof Array)){
   			alertIndiciaError(attrdata);
   		} else if (attrdata.length>0) {
			for(i=0; i< attrdata.length; i++){
				if (attrdata[i].id){
					switch(parseInt(attrdata[i].sample_attribute_id)){
						case ".$args['uid_attr_id'].":
							insect_alert_object.collection_user_id = attrdata[i].value;
							flower_alert_object.collection_user_id = attrdata[i].value;
							if(!expert && parseInt(attrdata[i].value) != ".$uid.")
								jQuery('.new-id-button').hide();
							break;
  					}
				}
			}
		}
    }));
}

loadInsectAddnInfo = function(keyValue, collectionIndex){
    // TODO convert buttons into thumbnails
	collection = '';	
	// fetch occurrence details first to get the sample_id.
	// Get the sample to get the parent_id.
	// get all the samples (sessions) with the same parent_id;
	// fetch all the occurrences of the sessions.
    ajaxStack.push($.getJSON(\"".$svcUrl."/data/occurrence/\" + keyValue +
   			\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&REMOVEABLEJSONP&callback=?\", function(occData) {
   		if(!(occData instanceof Array)){
   			alertIndiciaError(occData);
   		} else if (occData.length > 0) {
            ajaxStack.push($.getJSON(\"".$svcUrl."/data/sample/\" + occData[0].sample_id +
   					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   					\"&REMOVEABLEJSONP&callback=?\", function(smpData) {
   				if(!(smpData instanceof Array)){
   					alertIndiciaError(smpData);
   				} else if (smpData.length > 0) {
   					collection = smpData[0].parent_id;
					collectionProcessing(collection, ".(user_access('IForm n'.$node->nid.' insect expert') ? "true" : "false").");
					jQuery('#fo-insect-date').empty().append(convertDate(smpData[0].date_start,false));
					loadSampleAttributes(smpData[0].id);
					jQuery('#fo-collection-button').data('smpID',smpData[0].parent_id).data('collectionIndex', collectionIndex).show();
  				}
   		   	  }));
   		}
    }));
}
loadFlowerAddnInfo = function(keyValue, collectionIndex){
    // fetch occurrence details first to get the collection id.
    loadOccurrenceAttributes(keyValue);
    ajaxStack.push($.getJSON(\"".$svcUrl."/data/occurrence/\" + keyValue +
   			\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&REMOVEABLEJSONP&callback=?\", function(occData) {
   		if(!(occData instanceof Array)){
   			alertIndiciaError(occData);
   		} else if (occData.length > 0) {
			jQuery('#fo-collection-button').data('smpID',occData[0].sample_id).data('collectionIndex',collectionIndex).show();
			collectionProcessing(occData[0].sample_id, ".(user_access('IForm n'.$node->nid.' flower expert') ? "true" : "false").");
            ajaxStack.push($.getJSON(\"".$svcUrl."/data/sample/\" + occData[0].sample_id +
   					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   					\"&REMOVEABLEJSONP&callback=?\", function(collection) {
   				if(!(collection instanceof Array)){
   					alertIndiciaError(collection);
   				} else if (collection.length > 0) {
					loadLocationAttributes(collection[0].location_id);
  				}
   		   	  }));
   		}
    }));
}

loadInsect = function(insectID, collectionIndex, insectIndex, type){
    abortAjax();
	jQuery('#fo-prev-button,#fo-next-button').hide();
	jQuery('#fo-filter-button').show();
	if(type == 'S'){ // called from search
		if(insectIndex > 0)
			jQuery('#fo-prev-button').show().data('occID', searchResults.features[insectIndex-1].attributes.insect_id).data('collectionIndex', null).data('insectIndex', insectIndex-1).data('type', 'S');
		if(insectIndex < searchResults.features.length-1)
			jQuery('#fo-next-button').show().data('occID', searchResults.features[insectIndex+1].attributes.insect_id).data('collectionIndex', null).data('insectIndex', insectIndex+1).data('type', 'S');
	} else if(type == 'C'){ // called from collection
		jQuery('#fo-filter-button').hide();
		var myThumb = jQuery('.collection-insect').filter('[occID='+insectID+']').prev();
		if(myThumb.length > 0)
			jQuery('#fo-prev-button').show().data('occID', myThumb.attr('occID')).data('collectionIndex', collectionIndex).data('insectIndex', null).data('type', 'C');
		myThumb = jQuery('.collection-insect').filter('[occID='+insectID+']').next();
		if(myThumb.length > 0)
			jQuery('#fo-next-button').show().data('occID', myThumb.attr('occID')).data('collectionIndex', collectionIndex).data('insectIndex', null).data('type', 'C');		
  	} else if(type == 'P'){ // called from photoreel
		var myThumb = jQuery('.thumb').filter('[occID='+insectID+']').prev();
		if(myThumb.length > 0)
			jQuery('#fo-prev-button').show().data('occID', myThumb.attr('occID')).data('collectionIndex', collectionIndex).data('insectIndex', null).data('type', 'P');
		myThumb = jQuery('.thumb').filter('[occID='+insectID+']').next();
		if(myThumb.length > 0)
			jQuery('#fo-next-button').show().data('occID', myThumb.attr('occID')).data('collectionIndex', collectionIndex).data('insectIndex', null).data('type', 'P');		
	}
	insect_alert_object.insect_id = insectID;
	insect_alert_object.user_id = \"".$uid."\";
	flower_alert_object.flower_id = null;
	jQuery('#focus-collection,#filter,#fo-flower-addn-info').hide();
    jQuery('#focus-occurrence,#fo-addn-info-header,#fo-insect-addn-info').show();
	jQuery('[name=determination\\:occurrence_id]').val(insectID);
	jQuery('[name=occurrence_comment\\:occurrence_id]').val(insectID);
	jQuery('#fo-new-comment,#fo-new-insect-id,#fo-new-flower-id,#fo-express-doubt').removeClass('ui-accordion-content-active');
	jQuery('#fo-new-insect-id-button').show();
	jQuery('#fo-new-flower-id-button').hide();
	jQuery('#fo-new-comment-button').".((user_access('IForm n'.$node->nid.' insect expert') || user_access('IForm n'.$node->nid.' create insect comment')) ? "show()" : "hide()").";
	loadDeterminations(insectID, '#fo-id-history', '#fo-current-id', 'form#fo-new-insect-id-form', null, ".(user_access('IForm n'.$node->nid.' insect expert') ? '1' : '0').", ".(user_access('IForm n'.$node->nid.' flag dubious insect') ? '1' : '0').", insectTaxa, 'I');
	loadImage('occurrence_image', 'occurrence_id', insectID, '#fo-image', ".$args['Insect_Image_Ratio'].", function(imageRecord){insect_alert_object.insect_image_path = imageRecord.path}, '', false);
	loadInsectAddnInfo(insectID, collectionIndex);
	loadComments(insectID, '#fo-comment-list', 'occurrence_comment', 'occurrence_id', 'occurrence-comment-block', 'occurrence-comment-body', false);
	myScrollTo('#poll-banner');
}
loadFlower = function(flowerID, collectionIndex){
    abortAjax();
	insect_alert_object.insect_id = null;
	flower_alert_object.flower_id = flowerID;
	flower_alert_object.user_id = \"".$uid."\";
	jQuery('#fo-filter-button').show();
	jQuery('#fo-prev-button,#fo-next-button').hide(); // only one flower per collection, and don't search flowers: no next or prev buttons.
	jQuery('#focus-collection,#filter,#fo-insect-addn-info').hide();
	jQuery('#focus-occurrence,#fo-addn-info-header,#fo-flower-addn-info').show();
	jQuery('#fo-new-comment,#fo-new-insect-id,#fo-new-flower-id,#fo-express-doubt').removeClass('ui-accordion-content-active');
	jQuery('[name=determination\\:occurrence_id]').val(flowerID);
	jQuery('[name=occurrence_comment\\:occurrence_id]').val(flowerID);
	jQuery('#fo-new-flower-id-button').show();
	jQuery('#fo-new-insect-id-button').hide();
	jQuery('#fo-new-comment-button').".((user_access('IForm n'.$node->nid.' flower expert') || user_access('IForm n'.$node->nid.' create flower comment')) ? "show()" : "hide()").";
	loadDeterminations(flowerID, '#fo-id-history', '#fo-current-id', 'form#fo-new-flower-id-form', null, ".(user_access('IForm n'.$node->nid.' flower expert') ? '1' : '0').", ".(user_access('IForm n'.$node->nid.' flag dubious flower') ? '1' : '0').", flowerTaxa, 'F');
	loadImage('occurrence_image', 'occurrence_id', flowerID, '#fo-image', ".$args['Flower_Image_Ratio'].", function(imageRecord){flower_alert_object.flower_image_path = imageRecord.path}, '', false);
	loadFlowerAddnInfo(flowerID, collectionIndex);
	loadComments(flowerID, '#fo-comment-list', 'occurrence_comment', 'occurrence_id', 'occurrence-comment-block', 'occurrence-comment-body', false);
	myScrollTo('#poll-banner');
}

addDrawnGeomToSelection = function(geometry) {
   	// Create the polygon as drawn
   	var feature = new OpenLayers.Feature.Vector(geometry, {});
   	polygonLayer.addFeatures([feature]);
};

MyEditingToolbar=OpenLayers.Class(
		OpenLayers.Control.Panel,{
			initialize:function(layer,options){
				OpenLayers.Control.Panel.prototype.initialize.apply(this,[options]);
				this.addControls([new OpenLayers.Control.Navigation()]);
				var controls=[new OpenLayers.Control.DrawFeature(layer,OpenLayers.Handler.Polygon,{'displayClass':'olControlDrawFeaturePolygon', drawFeature: addDrawnGeomToSelection})];
				this.addControls(controls);
			},
			draw:function(){
				var div=OpenLayers.Control.Panel.prototype.draw.apply(this,arguments);
				this.activateControl(this.controls[0]);
				return div;
			},
	CLASS_NAME:\"MyEditingToolbar\"});

loadFilter = function(){
    if(jQuery('#map').children().length == 0) {
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
    	".$map1JS."
    	editControl = new MyEditingToolbar(polygonLayer, {'displayClass':'olControlEditingToolbar'});
		polygonLayer.map.addControl(this.editControl);
		editControl.activate();
		polygonLayer.map.searchLayer.events.register('featuresadded', {}, function(a1){
			if(inseeLayer != null)
				inseeLayer.destroyFeatures();
			polygonLayer.destroyFeatures();
		});
	}
}

jQuery('#fc-add-preferred').click(function(){
	if(collection_preferred_object.collection_id == null) return;
	var newObj = {};
	for (i in collection_preferred_object) {
		newObj[i] = collection_preferred_object[i]
	};
	newObj.insects = JSON.stringify(newObj.insects);";
	if($args['preferred_js_function'] != '') {
		data_entry_helper::$javascript .= "
		".$args['preferred_js_function']."({type: 'C', collection: newObj});";
	}
	data_entry_helper::$javascript .= "
});
jQuery('#fc-new-comment-button').click(function(){ 
	jQuery('#fc-new-comment').toggleClass('ui-accordion-content-active');
});
jQuery('#fo-new-comment-button').click(function(){ 
	jQuery('#fo-new-comment').toggleClass('ui-accordion-content-active');
});
jQuery('#fo-new-insect-id-button').click(function(){ 
	jQuery('#fo-express-doubt').removeClass('ui-accordion-content-active');
	jQuery('#fo-new-insect-id [name=determination\\:comment]').val(\"".lang::get('LANG_Default_ID_Comment')."\");
	jQuery('#fo-new-insect-id').toggleClass('ui-accordion-content-active');
});
jQuery('#fo-new-flower-id-button').click(function(){ 
	jQuery('#fo-express-doubt').removeClass('ui-accordion-content-active');
	jQuery('#fo-new-flower-id [name=determination\\:comment]').val(\"".lang::get('LANG_Default_ID_Comment')."\");
	jQuery('#fo-new-flower-id').toggleClass('ui-accordion-content-active');
});
jQuery('#fo-collection-button').click(function(){
	loadCollection(jQuery(this).data('smpID'), jQuery(this).data('collectionIndex'));
});
jQuery('#fo-prev-button,#fo-next-button').click(function(){
	loadInsect(jQuery(this).data('occID'), // my occurrence id.
		jQuery(this).data('collectionIndex'), // index of my collection within search results. Used when called from a focus on collection or from search collection photoreel thumbnail
		jQuery(this).data('insectIndex'), // index of me within search results. Used when called from search
		jQuery(this).data('type') // type: 'P' from photoreel, 'S' from search, 'C' from collection, 'X' NA
	);
});
  ";

    switch($mode){
    	case 'INSECT':
		    data_entry_helper::$onload_javascript .= "loadInsect(".$occID.", null, null, 'X');";
		    break;
    	case 'FLOWER':
		    data_entry_helper::$onload_javascript .= "loadFlower(".$occID.", null);";
		    break;
		case 'COLLECTION':
		    data_entry_helper::$onload_javascript .= "loadCollection(".$smpID.", null);";
    		break;
    	default:
    		data_entry_helper::$onload_javascript .= "
    		jQuery('#focus-occurrence,#focus-collection,#results-insects-header,#results-collections-header,#results-insects-results,#results-collections-results').hide();
    		loadFilter();";
    		if($userID != ''){
    			$thisuser = user_load($userID);
    			data_entry_helper::$onload_javascript .= "jQuery('[name=username]').val('".($thisuser->name)."');
    			jQuery('#fold-name-button').click();";
    		}
    		data_entry_helper::$onload_javascript .= "jQuery('#search-collections-button').click();";
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