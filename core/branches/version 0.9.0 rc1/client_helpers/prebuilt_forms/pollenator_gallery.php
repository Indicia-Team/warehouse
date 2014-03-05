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
	 * 
	 * V3_2
	 * Deployment: Upgrade Indicia
	 * Upgrade Iform: must run iform schema upgrade, and must upgrade openlayers.
	 * Load new cache tables and functions. update geoserver.
	 * 
	 * Update so looks up attribute IDs automatically.
	 * Update to optimse fecthing of data 
	 * Map size on collections.
	 * Initialise all non filter divs to display:none
	 * Check display on search of multiple session collections.

	 * Task: New Filtering Options: CdC II.2
	 * ASK: Should the flower and insect ID status and types be checkbox groups rather than drop down lists?
	 * Collection search:
	 * Insect ID status: backend & frontend filter
	 * Insect ID type: backend & frontend filter
	 * Insect photoed elsewhere: backend & frontend filter
	 * Task: Validation of Photos: CdC II.4
	 * 
	 * Finished
	 * Task: New Filtering Options: CdC II.2
	 * Collection search: Plant ID status: backend & frontend filter
	 * Collection search: Plant ID type: backend & frontend filter
	 * Insects search: Fully Finished
	 * Task: Adding Informations: CdC II.3: Fully Finished
	 * Task: Geolocation: CdC II.6: Done
	 * 
	 * POSSIBLE TODO add buttons to allow selection of commune, department or region from georef point or
	 * department or region from INSEE.
	 * POSSIBLE TODO Extend INSEE search to search for all communes for particular department or region.
	 * 	 */
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
         'name'=>'2nd_map_zoom',
         'caption'=>'Second Map Zoom',
         'description'=>'Default Zoom when displaying a collection.',
         'type'=>'int',
         'group'=>'Initial Map View',
         'required'=>false
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
          'name'=>'search_collections_layer',
          'caption'=>'Name layer for the Collections Search',
          'description'=>'The Name of the Geoserver Layer used for the WFS feature lookup when searching Collections.',
          'type'=>'string',
          'group'=>'Search'
      ),
      array(
          'name'=>'search_insects_layer',
          'caption'=>'Name layer for the Insects Search',
          'description'=>'The Name of the Geoserver Layer used for the WFS feature lookup when searching Insects.',
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
          'caption'=>'URL for Localisation Searches WFS service',
          'description'=>'The URL used for the SPIPOLL provided WFS feature lookup.',
          'type'=>'string',
          'group'=>'Localisation Search'
      ),
      array(
          'name'=>'INSEE_prefix',
          'caption'=>'Feature type prefix for Localisation Search',
          'description'=>'The Feature type prefix used for the SPIPOLL provided WFS feature lookup.',
          'type'=>'string',
          'group'=>'Localisation Search'
      ),
      array(
          'name'=>'INSEE_type',
          'caption'=>'Feature type for INSEE Search',
          'description'=>'The Feature type used for the WFS feature lookup when search for locaisation details when displaying collections and insects.',
          'type'=>'string',
          'group'=>'Localisation Search'
      ),
      array(
          'name'=>'Localisation_spec',
          'caption'=>'Localisation Search Specification',
          'description'=>'The Specification of the search methods: semi-colon separated list of following groups: Caption:featureType:geometryField:DisplayField:extraDataField:lookUpMaxFeatures:maxNumInMainSearch:SearchField1[like*|*like*|equal]{:SearchField2:[like*|*like*|equal]{..}}.',
          'type'=>'textarea',
          'group'=>'Localisation Search'
      ),
      array(
          'name'=>'INSEE_ns',
          'caption'=>'Name space for Localisation Search',
          'description'=>'The Name space used for the SPIPOLL provided WFS feature lookup.',
          'type'=>'string',
          'group'=>'Localisation Search'
      ),
      array(
          'name'=>'Feature_Colour',
          'caption'=>'Localisation Search Feature Colour',
          'description'=>'The Colour on the map of the features returned by the localisation lookup',
          'type'=>'string',
          'default'=>'Blue',
          'group'=>'Localisation Search'
      ),
      array(
          'name'=>'Feature_Opacity',
          'caption'=>'Localisation Search Feature Opacity',
          'description'=>'The Fill Opacity on the map of the features returned by the localisation lookup',
          'type'=>'string',
          'default'=>'0.1',
          'group'=>'Localisation Search'
      ),
      array(
          'name'=>'Polygon_Colour',
          'caption'=>'Polygon Layer Feature Colour',
          'description'=>'The Colour on the map of the features created by the Polygon Tool',
          'type'=>'string',
          'default'=>'Blue',
          'group'=>'Drawing'
      ),
      array(
          'name'=>'Polygon_Opacity',
          'caption'=>'Polygon Layer Feature Opacity',
          'description'=>'The Fill Opacity on the map of the features created by the Polygon Tool',
          'type'=>'string',
          'default'=>'0.1',
          'group'=>'Drawing'
      ),
      array(
          'name'=>'front_page_attr_id',
          'caption'=>'Front page attribute ID',
          'description'=>'Indicia ID for the sample attribute used to indicate that a collection is to be included on the front page. NB this is used by the DRUPAL front page to build the report request qnd should be left in place.',
          'type'=>'int',
          'default'=>29,
          'group'=>'Collection Attributes'
      ),
      array(
          'name'=>'flower_type_dont_know',
          'caption'=>'Flower Type Dont Know ID',      
          'description'=>'Indicia ID for the term meaning_id that shows a Flower type of dont know.',
          'type'=>'int',
          'group'=>'Floral Station Attributes'
            ),
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
          'name'=>'deleteable_user_id',
          'caption'=>'Deleteable User ID',
          'description'=>'The Drupal User ID of the user who can delete determinations.',
          'required'=>false,
          'type'=>'int',
          'group'=>'User Interface'
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
          'name'=>'ID_tool_convert_strings',
          'caption'=>'Convert Tool Output',
          'description'=>'Choose whether to convert the output of the ID tool from ISO-8859-1 to UTF-8',
          'type'=>'boolean',
          'required'=>false,
          'group'=>'ID Tool'
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

  public static function get_perms($nid, $args) {
    return array('IForm n'.$nid.' access',
    			'IForm n'.$nid.' delete collection',
    			'IForm n'.$nid.' flower expert',
    			'IForm n'.$nid.' flag dubious flower',
    			'IForm n'.$nid.' delete flower determination',
    			'IForm n'.$nid.' create flower comment',
    			'IForm n'.$nid.' delete flower comment',
    			'IForm n'.$nid.' insect expert',
    			'IForm n'.$nid.' delete insect',
    			'IForm n'.$nid.' flag dubious insect',
    			'IForm n'.$nid.' delete insect determination',
    			'IForm n'.$nid.' create insect comment',
    			'IForm n'.$nid.' delete insect comment',
    			'IForm n'.$nid.' delete collection comment',
    			'IForm n'.$nid.' create collection comment',
    			'IForm n'.$nid.' save filter',
    			'IForm n'.$nid.' add preferred collection',
    			'IForm n'.$nid.' edit geolocation',
    			'IForm n'.$nid.' add to front page',
    );
  }

  private static function getAttr($readAuth, $args, $table, $caption){
    switch($table){
      case 'occurrence':
        $prefix = 'occAttr';
        break;
      case 'sample':
        $prefix = 'smpAttr';
        break;
      case 'location':
        $prefix = 'locAttr';
        break;
      default: return false;
    }
    $myAttributes = data_entry_helper::getAttributes(array(
        'valuetable'=>$table.'_attribute_value'
       ,'attrtable'=>$table.'_attribute'
       ,'key'=>$table.'_id'
       ,'fieldprefix'=>$prefix
       ,'extraParams'=>$readAuth
       ,'survey_id'=>$args['survey_id']
      ), false);
    foreach($myAttributes as $attr)
      if (strcasecmp($attr['untranslatedCaption'],$caption)==0)
        return $attr;
    return false;
  }
  private static function getAttrID($readAuth, $args, $table, $caption){
    $attr = self::getAttr($readAuth, $args, $table, $caption);
    if($attr) return $attr['attributeId'];
    return false;
  }

/**
   * Return the generated form output.
   * @return Form HTML.
   */
  /**
   *
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
	data_entry_helper::add_resource('autocomplete');
	
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

	// The only things that will be editable after the collection is saved will be the identification of the flower/insects.
	// no id - just getting the attributes, rest will be filled in using AJAX
	$sample_attributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$readAuth
       ,'survey_id'=>$args['survey_id']
    ));
    $uidAttrID = self::getAttrID($readAuth, $args, 'sample', 'CMS User ID');
    $usernameAttrID = self::getAttrID($readAuth, $args, 'sample', 'CMS Username');
    $frontPageAttrID = self::getAttrID($readAuth, $args, 'sample', 'FrontPage');
    $startTimeAttrID = self::getAttrID($readAuth, $args, 'sample', 'Start Time');
    $endTimeAttrID = self::getAttrID($readAuth, $args, 'sample', 'End Time');
    $skyAttrID = self::getAttrID($readAuth, $args, 'sample', 'Sky');
    $temperatureAttrID = self::getAttrID($readAuth, $args, 'sample', 'Temperature');
    $shadeAttrID = self::getAttrID($readAuth, $args, 'sample', 'Shade');
    $windAttrID = self::getAttrID($readAuth, $args, 'sample', 'Wind');
    $occurrence_attributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'occurrence_attribute_value'
       ,'attrtable'=>'occurrence_attribute'
       ,'key'=>'occurrence_id'
       ,'fieldprefix'=>'occAttr'
       ,'extraParams'=>$readAuth
       ,'survey_id'=>$args['survey_id']
    ));
    $flowerTypeAttrID = self::getAttrID($readAuth, $args, 'occurrence', 'Flower Type');
    $foragingAttrID = self::getAttrID($readAuth, $args, 'occurrence', 'Foraging');
    $location_attributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'location_attribute_value'
       ,'attrtable'=>'location_attribute'
       ,'key'=>'location_id'
       ,'fieldprefix'=>'locAttr'
       ,'extraParams'=>$readAuth
       ,'survey_id'=>$args['survey_id']
    ));
    $habitatAttrID = self::getAttrID($readAuth, $args, 'location', 'Habitat');
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
			'listCaptionSpecialChars'=>true,
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
			'listCaptionSpecialChars'=>true,
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
    // Switch to degrees, minutes, seconds for lat long.
    $options['latLongFormat'] = 'DMS';
    $options['initialFeatureWkt'] = null;
    $options['proxy'] = '';
    $options['suffixTemplate'] = 'nosuffix';
    if( lang::get('msgGeorefSelectPlace') != 'msgGeorefSelectPlace')
    	$options['msgGeorefSelectPlace'] = lang::get('msgGeorefSelectPlace');
    if( lang::get('msgGeorefNothingFound') != 'msgGeorefNothingFound')
    	$options['msgGeorefNothingFound'] = lang::get('msgGeorefNothingFound');
    
    $options2 = $options;
    $options['searchLayer'] = true;
    $options['editLayer'] = false;
    $options['layers'] = array('polygonLayer');
    
	$options2['divId'] = "map2";
    $options2['height'] = $args['2nd_map_height'];
    if(isset($args['2nd_map_zoom']) && $args['2nd_map_zoom']!="")
      $options2['maxZoom'] = $args['2nd_map_zoom'];
	// we are using meaning_ids: services now use ids, so can't just output value - convert raw value
    data_entry_helper::$javascript .= "var terms = {";
	$extraParams = $readAuth + array('view'=>'detail', 'iso'=>$language, 'orderby'=>'meaning_id');
	$terms_data_def=array('table'=>'termlists_term','extraParams'=>$extraParams);
	$terms = data_entry_helper::get_population_data($terms_data_def);
	$first = true;
	foreach ($terms as $term) {
		data_entry_helper::$javascript .= ($first ? '' : ',').$term['meaning_id'].": \"".htmlSpecialChars($term['term'])."\"\n";
		$first=false;
	}
    data_entry_helper::$javascript .= "};
convertTerm=function(id){
	if(typeof terms[id] == 'undefined') return id;
	return terms[id];
}
var flowerTaxa = [";
	$extraParams = $readAuth + array('taxon_list_id' => $args['flower_list_id'], 'view'=>'list');
    $species_data_def=array('table'=>'taxa_taxon_list','extraParams'=>$extraParams);
	$taxa = data_entry_helper::get_population_data($species_data_def);
	$first = true;
	foreach ($taxa as $taxon) {
		data_entry_helper::$javascript .= ($first ? '' : ',')."{id: ".$taxon['id'].", taxon: \"".str_replace('"','\\"',$taxon['taxon'])."\"}\n";
		$first=false;
	}
    data_entry_helper::$javascript .= "];\nvar insectTaxa = [";
    $extraParams['taxon_list_id'] = $args['insect_list_id'];
    $species_data_def['extraParams']=$extraParams;
    $taxa = data_entry_helper::get_population_data($species_data_def);
	$first = true;
	foreach ($taxa as $taxon) {
		data_entry_helper::$javascript .= ($first ? '' : ',')."{id: ".$taxon['id'].", taxon: \"".str_replace('"','\\"',$taxon['taxon'])."\"}\n";
		$first=false;
	}
    data_entry_helper::$javascript .= "];";
    // TBD Breadcrumb
 	$r .= '<h1 id="poll-banner"></h1>
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
			<label for="end_date" >'.lang::get('LANG_And').'</label>
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
		  <label >'.lang::get('LANG_ID_Status').':</label>
		  <span class="control-box "><nobr>
		    <span><input type="checkbox" value="X" id="flower_id_status:0" name="flower_id_status[]"><label for="flower_id_status:0">'.lang::get('LANG_ID_Status_Unidentified').'</label></span></nobr> &nbsp; <nobr>
		    <span><input type="checkbox" value="A" id="flower_id_status:1" name="flower_id_status[]"><label for="flower_id_status:1">'.lang::get('LANG_ID_Status_Initial').'</label></span></nobr> &nbsp; <nobr>
		    <span><input type="checkbox" value="B" id="flower_id_status:2" name="flower_id_status[]"><label for="flower_id_status:2">'.lang::get('LANG_ID_Status_Doubt').'</label></span></nobr> &nbsp; <nobr>
		    <span><input type="checkbox" value="C" id="flower_id_status:3" name="flower_id_status[]"><label for="flower_id_status:3">'.lang::get('LANG_ID_Status_Validated').'</label></span></nobr> &nbsp; 
		  </span>
		  <label >'.lang::get('LANG_ID_Type').':</label>
		  <span class="control-box "><nobr>
		    <span><input type="checkbox" value="seul" id="flower_id_type:0" name="flower_id_type[]"><label for="flower_id_type:0">'.lang::get('LANG_ID_Type_Single').'</label></span></nobr> &nbsp; <nobr>
		    <span><input type="checkbox" value="multi" id="flower_id_type:1" name="flower_id_type[]"><label for="flower_id_type:1">'.lang::get('LANG_ID_Type_Multiple').'</label></span></nobr> &nbsp; 
		  </span>
          '.str_replace("\n", "", data_entry_helper::outputAttribute($occurrence_attributes[$flowerTypeAttrID], $defAttrOptions))
    	  .str_replace("\n", "", data_entry_helper::outputAttribute($location_attributes[$habitatAttrID], $defAttrOptions)).'
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
		  <label >'.lang::get('LANG_ID_Status').':</label>
		  <span class="control-box "><nobr>
		    <span><input type="checkbox" value="X" id="insect_id_status:0" name="insect_id_status[]"><label for="insect_id_status:0">'.lang::get('LANG_ID_Status_Unidentified').'</label></span></nobr> &nbsp; <nobr>
		    <span><input type="checkbox" value="A" id="insect_id_status:1" name="insect_id_status[]"><label for="insect_id_status:1">'.lang::get('LANG_ID_Status_Initial').'</label></span></nobr> &nbsp; <nobr>
		    <span><input type="checkbox" value="B" id="insect_id_status:2" name="insect_id_status[]"><label for="insect_id_status:2">'.lang::get('LANG_ID_Status_Doubt').'</label></span></nobr> &nbsp; <nobr>
		    <span><input type="checkbox" value="C" id="insect_id_status:3" name="insect_id_status[]"><label for="insect_id_status:3">'.lang::get('LANG_ID_Status_Validated').'</label></span></nobr> &nbsp; 
		  </span>
		  <label >'.lang::get('LANG_ID_Type').':</label>
		  <span class="control-box "><nobr>
		    <span><input type="checkbox" value="seul" id="insect_id_type:0" name="insect_id_type[]"><label for="insect_id_type:0">'.lang::get('LANG_ID_Type_Single').'</label></span></nobr> &nbsp; <nobr>
		    <span><input type="checkbox" value="multi" id="insect_id_type:1" name="insect_id_type[]"><label for="insect_id_type:1">'.lang::get('LANG_ID_Type_Multiple').'</label></span></nobr> &nbsp; 
		  </span>
		'.str_replace("\n", "", data_entry_helper::outputAttribute($occurrence_attributes[$foragingAttrID], $defAttrOptions)).'
		</div>
		<div id="conditions-filter-header" class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all">
	  		<div id="fold-conditions-button" class="ui-state-default ui-corner-all fold-button fold-button-folded">&nbsp;</div>
			<div id="reset-conditions-button" class="ui-state-default ui-corner-all reset-button">'.lang::get('LANG_Reset_Filter').'</div>
	  		<div id="conditions-filter-title">
		  		<span>'.lang::get('LANG_Conditions_Filter_Title').'</span>
      		</div>
		</div>
		<div id="conditions-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all">
    	  '.str_replace("\n", "", data_entry_helper::outputAttribute($sample_attributes[$skyAttrID], $defAttrOptions))
		  .str_replace("\n", "", data_entry_helper::outputAttribute($sample_attributes[$temperatureAttrID], $defAttrOptions))
		  .str_replace("\n", "", data_entry_helper::outputAttribute($sample_attributes[$windAttrID], $defAttrOptions))
		  .str_replace("\n", "", data_entry_helper::outputAttribute($sample_attributes[$shadeAttrID], $defAttrOptions)).'
		</div>
		<div id="location-filter-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-all">
	  		<div id="fold-location-button" class="ui-state-default ui-corner-all fold-button">&nbsp;</div>
			<div id="reset-location-button" class="ui-state-default ui-corner-all reset-button">'.lang::get('LANG_Reset_Filter').'</div>
			<div id="location-filter-title">
		  		<span>'.lang::get('LANG_Location_Filter_Title').'</span>
      		</div>
		</div>
		<div id="location-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-all">
		  <div id="location-entry">'; 
	$searches=explode(';',trim($args['Localisation_spec']));
	if(count($searches)==1){
		$parts=explode(':',$searches[0]);
		$r .= '<input type="hidden" id="place:INSEE_Type" name="place:INSEE_Type" value="0"><label for="place:INSEE">'.lang::get('LANG_Search').' '.$parts[0].'</label>';
	} else {
		$r .= '<label for="place:INSEE_Type">'.lang::get('LANG_Search').' </label><select id="place:INSEE_Type" name="place:INSEE_Type">';
		for($i=0; $i< count($searches); $i++){
			$parts=explode(':',$searches[$i]);
			$r .= '<option value="'.$i.'">'.$parts[0].'</option>';
		}
		$r .= '</select>';
	}
	$r .= '<input type="text" id="place:INSEE" name="place:INSEE" class="INSEE-search" value="'.lang::get('LANG_Enter_Location').'"
	 		  onclick="if(this.value==\''.lang::get('LANG_Enter_Location').'\'){this.value=\'\'; this.style.color=\'#000\'}"  
              onblur="if(this.value==\'\'){this.value=\''.lang::get('LANG_Enter_Location').'\'; this.style.color=\'#555\'}" />
    	    <input type="button" id="search-insee-button" class="ui-corner-all ui-widget-content ui-state-default search-button" value="'.lang::get('search').'" />
    	    <div class="ui-corner-all ui-widget-content ui-helper-hidden" id="imp-insee-div" style="display: none;">
    	      <div id="imp-insee-output-div"></div>
    	      <a id="imp-insee-close-btn" href="#" class="ui-corner-all ui-widget-content ui-state-default indicia-button">'.lang::get('close').'</a>
    	    </div>
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
	<div id="results-collections-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top" style="display: none">
	  <div id="results-collections-title">
	  	<span>'.lang::get('LANG_Collections_Search_Results').'</span>
      </div>
	</div>
	<div id="results-collections-results" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-bottom" style="display: none">
    </div>
	<div id="results-insects-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top" style="display: none">
	  <div id="results-insects-title">
	  	<span>'.lang::get('LANG_Insects_Search_Results').'</span>
      </div>
	</div>
	<div id="results-insects-results" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-bottom" style="display: none">
    </div>';
	if(user_access('IForm n'.$node->nid.' insect expert') || user_access('IForm n'.$node->nid.' flower expert')){
		$r .= '
	<form id="bulk-validation-form" action="'.iform_ajaxproxy_url($node, 'determination').'" method="POST" style="display:none;">
		<input type="hidden" name="website_id" value="'.$args['website_id'].'" />
		<input type="hidden" name="determination:occurrence_id" value="" />
		<input type="hidden" name="determination:cms_ref" value="'.$uid.'" />  
		<input type="hidden" name="determination:person_name" value="'.$username.'" />  
		<input type="hidden" name="determination:email_address" value="'.$email.'" />
		<input type="hidden" name="determination:determination_type" value="C" />
		<input type="hidden" name="determination:taxon_details" value="" />
		<input type="hidden" name="determination:taxa_taxon_list_id" value="" />
		<input type="hidden" name="determination:comment" value="'.lang::get('LANG_Bulk_Validation_Comment').'" />
		<input type="hidden" name="determination:taxon_extra_info" value="" />
	</form>
	<div id="results-validate-page" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-all" style="display: none">
	  <div id="validate-page-button" class="ui-state-default ui-corner-all validate-page-button">'.lang::get('LANG_Validate_Page').'</div>
	  <div id="validate-page-progress"></div>
	  <div id="validate-page-message"></div>
	  <div id="cancel-validate-page" class="ui-state-default ui-corner-all cancel-validate-button">'.lang::get('LANG_Cancel').'</div>
	</div>
	<div id="results-validate-taxon-outer" style="display: none">
	  <div id="results-validate-taxon" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-all" style="display: none">
		<div id="validate-taxon-button" class="ui-state-default ui-corner-all validate-taxon-button">'.lang::get('LANG_Validate_Taxon').'</div>
		<div id="validate-taxon-progress"></div>
		<div id="validate-taxon-message"></div>
		<div id="cancel-validate-taxon" class="ui-state-default ui-corner-all cancel-validate-button">'.lang::get('LANG_Cancel').'</div>
	</div></div>';
	}
	$r .= '
</div>
<div id="focus-collection" class="ui-accordion ui-widget ui-helper-reset" style="display: none">
	<div id="fc-header" class="ui-accordion-content ui-helper-reset ui-state-active ui-corner-top ui-accordion-content-active">
	  <div id="fc-header-buttons">
	    <span id="fc-prev-button" class="ui-state-default ui-corner-all previous-button">'.lang::get('LANG_Previous').'</span>
	    <span id="fc-next-button" class="ui-state-default ui-corner-all next-button">'.lang::get('LANG_Next').'</span>
	  	<span id="fc-filter-button" class="ui-state-default ui-corner-all collection-button">'.lang::get('LANG_List').'</span>
	  </div>
	</div>';
	if(user_access('IForm n'.$node->nid.' delete collection')){
	  $r .= '<div id="fc-delete-collection" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active">
		<form id="fc-delete-collection-form" action="'.iform_ajaxproxy_url($node, 'sample').'" method="POST">
		<input type="hidden" name="website_id" value="'.$args['website_id'].'" />
		<input type="hidden" name="survey_id" value="'.$args['survey_id'].'" />
		<input type="hidden" name="sample:id" value="" />
		<input type="hidden" name="sample:deleted" value="t" />
		<input type="submit" id="fc_delete_collection_submit_button" class="ui-state-default ui-corner-all preferred-button" value="'.lang::get('LANG_Submit_delete-collection').'" />
		</form>
	  </div>';
	}
	$r .= '	<div id="collection-details" class="ui-accordion-content ui-helper-reset ui-widget-content '.(user_access('IForm n'.$node->nid.' add preferred collection') ? '' : 'ui-corner-bottom').' ui-accordion-content-active" '.(user_access('IForm n'.$node->nid.' add preferred collection') ? 'style="border-bottom:none;"' : '').'>
	  <div id="flower-image-container" ><div id="flower-image" class="flower-image"></div>
        <div id="show-flower-button" class="ui-state-default ui-corner-all display-button">'.lang::get('LANG_Display').'</div>
      </div>
      <div id="environment-image" class="environment-image"></div>
      <div id="collection-description">
	    <p id="collection-date"></p>
	    <p id="collection-flower-name"></p>
	    <p>'.lang::get($occurrence_attributes[$flowerTypeAttrID]['caption']).': <span id="collection-flower-type" class="collection-value"></span></p>
	    <p>'.lang::get($location_attributes[$habitatAttrID]['caption']).': <span id="collection-habitat" class="collection-value"></span></p>
	    <br />
	    <p id="collection-user-name"></p>
	    <a id="collection-user-link">'.lang::get('LANG_User_Link').'</a>
	    <br /><br />
	    <p>'.lang::get('LANG_INSEE_Localisation').': <span id="collection-locality"></span></p>
        <br /><p id="fc-new-location-desc">'.lang::get('LANG_Localisation_Desc').'</p>
	  </div>
      <div id="map2_container">';
    // this is a bit of a hack, because the apply_template method is not public in data entry helper.
    $tempScript = data_entry_helper::$onload_javascript;
    data_entry_helper::$onload_javascript = '';
    $r .= data_entry_helper::map_panel($options2, $olOptions);
    $map2JS = data_entry_helper::$onload_javascript;
    data_entry_helper::$onload_javascript = $tempScript;
    data_entry_helper::$javascript .= "\njQuery('#fc-new-location').find('br').remove();\n";
    $r .= '</div>
      <div id="fc-new-location">
		<form id="fc-new-location-form" action="'.iform_ajaxproxy_url($node, 'location').'" method="POST">
    		<input type="hidden"                       name="website_id" value="'.$args['website_id'].'" />
    		<input type="hidden"                       name="survey_id" value="'.$args['survey_id'].'" />
    		<input type="hidden" id="imp-sref-system"  name="location:centroid_sref_system" value="4326" />
		 	<input type="hidden"                       name="location:name" value="" />
    		<input type="hidden" id="location-id"      name="location:id" value=""/>
    	'.data_entry_helper::sref_textbox(array(
		        'srefField'=>'location:centroid_sref',
        		'systemfield'=>'location:centroid_sref_system',
        		'fieldname'=>'location:centroid_sref',
        		'splitLatLong'=>true,
		        'labelLat' => lang::get('Latitude'),
    			'fieldnameLat' => 'place:lat',
        		'labelLong' => lang::get('Longitude'),
    			'fieldnameLong' => 'place:long',
    			'idLat'=>'imp-sref-lat',
        		'idLong'=>'imp-sref-long',
    			'suffixTemplate'=>'nosuffix')).'
            <input type="submit" id="fc_location_submit_button" class="ui-state-default ui-corner-all submit-button" value="'.lang::get('LANG_Submit_Location').'" />
        </form>
	  </div>
      <div id="fc-new-location-message"></div></div>';
    if(user_access('IForm n'.$node->nid.' add preferred collection')){
    	$r .= '<div id="preferred-button-container" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active"><span id="fc-add-preferred" class="ui-state-default ui-corner-all preferred-button">'.lang::get('LANG_Add_Preferred_Collection').'</span></div>';
    }
    if(user_access('IForm n'.$node->nid.' add to front page')){
    	$r .= '<div id="fc-front-page" class="ui-widget-content ui-corner-all">
    <form id="fc-front-page-form" action="'.iform_ajaxproxy_url($node, 'sample').'" method="POST">
       <input type="hidden" name="website_id" value="'.$args['website_id'].'" />
       <input type="hidden" name="survey_id" value="'.$args['survey_id'].'" />
       <input type="hidden" name="sample:id" value="" />
       <input type="hidden" name="sample:date_start" value="2010-01-01"/>
       <input type="hidden" name="sample:date_end" value="2010-01-01"/>
       <input type="hidden" name="sample:date_type" value="D"/>
       <input type="hidden" name="sample:location_id" value="" />
       <label>'.lang::get('LANG_Front Page').'</label><div class="control-box "><nobr><span><input type="radio" id="smpAttr:'.$frontPageAttrID.':0" name="smpAttr:'.$frontPageAttrID.'" value="0" checked="checked" /><label for="smpAttr:'.$frontPageAttrID.':0">'.lang::get('No').'</label></span></nobr> &nbsp; <nobr><span><input type="radio" id="smpAttr:'.$frontPageAttrID.':1" name="smpAttr:'.$frontPageAttrID.'" value="1" /><label for="smpAttr:'.$frontPageAttrID.':1">'.lang::get('Yes').'</label></span></nobr></div>
       <input type="submit" id="fc_front_page_submit_button" class="ui-state-default ui-corner-all submit-button" value="'.lang::get('LANG_Submit_Front_Page').'" />
    </form>
    <div id="fc-front-page-message"></div>
  </div>
';
    }
    $r .= '<div id="collection-insects"></div>';
    if(user_access('IForm n'.$node->nid.' insect expert')){
		$r .= '
    <div id="results-validate-collection" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-all">
	  <div id="validate-collection-button" class="ui-state-default ui-corner-all validate-collection-button">'.lang::get('LANG_Validate_Collection').'</div>
	  <div id="validate-collection-progress"></div>
	  <div id="validate-collection-message"></div>
	  <div id="cancel-validate-collection" class="ui-state-default ui-corner-all cancel-validate-button">'.lang::get('LANG_Cancel').'</div>
	</div>';
    }
	$r .= '<div id="fc-comments-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
	    <div id="fc-new-comment-button" class="ui-state-default ui-corner-all new-comment-button">'.lang::get('LANG_New_Comment').'</div>
		<span>'.lang::get('LANG_Comments_Title').'</span>
	</div>
	<div id="fc-new-comment" class="ui-accordion-content ui-helper-reset ui-widget-content">
		<form id="fc-new-comment-form" action="'.iform_ajaxproxy_url($node, 'smp-comment').'" method="POST">
		    <input type="hidden" name="website_id" value="'.$args['website_id'].'" />
    		<input type="hidden" name="sample_comment:sample_id" value="" />
    		<label for="sample_comment:person_name">'.lang::get('LANG_Username').' :</label>
		    <input type="text" name="sample_comment:person_name" value="'.$username.'" readonly="readonly" />  
    		<label for="sample_comment:email_address">'.lang::get('LANG_Email').' :</label>
		    <input type="text" name="sample_comment:email_address" value="'.$email.'" readonly="readonly" />
		    '.data_entry_helper::textarea(array('label'=>lang::get('LANG_Comment').' ', 'fieldname'=>'sample_comment:comment', 'class'=>'required', 'suffixTemplate'=>'nosuffix')).'
    		<input type="submit" id="fc_comment_submit_button" class="ui-state-default ui-corner-all submit-button" value="'.lang::get('LANG_Submit_Comment').'" />
    	</form>
	</div>
	<div id="fc-comment-list" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
	</div>
</div>
<div id="focus-occurrence" class="ui-accordion ui-widget ui-helper-reset" style="display: none">
	<div id="fo-header" class="ui-accordion-content ui-helper-reset ui-state-active ui-corner-top ui-accordion-content-active">
	  <div id="fo-header-buttons">
 	    <span id="fo-collection-button" class="ui-state-default ui-corner-all collection-button">'.lang::get('LANG_Collection').'</span>
	    <span id="fo-prev-button" class="ui-state-default ui-corner-all previous-button">'.lang::get('LANG_Previous').'</span>
	    <span id="fo-next-button" class="ui-state-default ui-corner-all next-button">'.lang::get('LANG_Next').'</span>
	  	<span id="fo-filter-button" class="ui-state-default ui-corner-all collection-button">'.lang::get('LANG_List').'</span>
	  </div>
	</div>';
	if(user_access('IForm n'.$node->nid.' delete insect')){
		$r .= '<div id="fo-delete-insect" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active">
		<form id="fo-delete-insect-form" action="'.iform_ajaxproxy_url($node, 'occurrence').'" method="POST">
		<input type="hidden" name="website_id" value="'.$args['website_id'].'" />
		<input type="hidden" name="survey_id" value="'.$args['survey_id'].'" />
		<input type="hidden" name="occurrence:id" value="" />
		<input type="hidden" name="occurrence:deleted" value="t" />
		<input type="submit" id="fo_delete_insect_submit_button" class="ui-state-default ui-corner-all preferred-button" value="'.lang::get('LANG_Submit_delete-insect').'" />
		</form>
	</div>';
	}
	$r .= '	
	<div id="fo-picture" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
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
	<div id="fo-id-buttons" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active">
		<div id="fo-new-insect-id-button" class="ui-state-default ui-corner-all new-id-button">'.lang::get('LANG_New_ID').'</div>
		<div id="fo-new-flower-id-button" class="ui-state-default ui-corner-all new-id-button">'.lang::get('LANG_New_ID').'</div>
		<div id="fo-doubt-button" class="ui-state-default ui-corner-all doubt-button">'.lang::get('LANG_Doubt').'</div>
    </div>
	<div id="fo-id-history" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active"></div>
    ';
    if(isset($args['deleteable_user_id']) && $args['deleteable_user_id']!='' && $args['deleteable_user_id']==$user->uid){
     // need two as they use different species lists.
     $r .= '	<div id="fo-edit-insect-id" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all">
	  <p class="title">Modify Existing Insect Determination</p>
	  <form id="fo-edit-insect-id-form" action="'.iform_ajaxproxy_url($node, 'determination').'" method="POST">
		<input type="hidden" name="website_id" value="'.$args['website_id'].'" />
		<label class="follow-on">Determination ID : </label><input type="text" name="determination:id" value="" readonly="readonly" /><br />
		<label class="follow-on">Deleted : </label><input type="checkbox" name="determination:deleted" value="t" /><br />
		<label class="follow-on">Occurrence ID : </label><input type="text" name="determination:occurrence_id" value="" readonly="readonly" /><br />
		<label class="follow-on">CMS User Ref ID : </label><input type="text" name="determination:cms_ref" value="" readonly="readonly" /><br />
    	<label class="follow-on">CMS Person Name : </label><input type="text" name="determination:person_name" value="" readonly="readonly" /><br />
		<label class="follow-on">CMS Email : </label><input type="text" name="determination:email_address" value="" readonly="readonly" /><br />
		<label class="follow-on">Type : </label><select name="determination:determination_type" /><option value="A">'.lang::get('LANG_Det_Type_A').'</option><option value="C" selected>'.lang::get('LANG_Det_Type_C').'</option><option value="X">'.lang::get('LANG_Det_Type_X').'</option></select><br />
		<label class="follow-on">Taxon Details : </label><input type="text" name="determination:taxon_details" value="" /><br />
 	    <label class="follow-on">Extra Info : </label><input type="text" name="determination:taxon_extra_info" value="" /><br />
		';
     $edit_insect_ctrl_args = $focus_insect_ctrl_args;
     $edit_insect_ctrl_args['class']='species-select';
     $edit_insect_ctrl_args['blankText']='';
     $edit_insect_ctrl_args['label']='Main Species';
     $edit_insect_ctrl_args['id']='dummy_tag';
     $r .= data_entry_helper::select($edit_insect_ctrl_args);
     unset($edit_insect_ctrl_args['id']);
     for($j=0; $j<10; $j++){
       $edit_insect_ctrl_args['label']='Species Array '.$j;
       $edit_insect_ctrl_args['fieldname']='fo-edit-insect-id-list-'.$j;
       $r .= data_entry_helper::select($edit_insect_ctrl_args);
     }
     $r .= '        <label >Comment : </label><textarea name="determination:comment" class="taxon-comment" rows="3" ></textarea><br />
        <input type="submit" class="ui-state-default ui-corner-all submit-button" value="'.lang::get('LANG_Validate').'" />
      </form>
	</div>
	<div id="fo-edit-flower-id" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all">
	  <p class="title">Modify Existing Flower Determination</p>
	  <form id="fo-edit-flower-id-form" action="'.iform_ajaxproxy_url($node, 'determination').'" method="POST">
		<input type="hidden" name="website_id" value="'.$args['website_id'].'" />
		<label class="follow-on">Determination ID : </label><input type="text" name="determination:id" value="" readonly="readonly" /><br />
		<label class="follow-on">Deleted : </label><input type="checkbox" name="determination:deleted" value="t" /><br />
		<label class="follow-on">Occurrence ID : </label><input type="text" name="determination:occurrence_id" value="" readonly="readonly" /><br />
		<label class="follow-on">CMS User Ref ID : </label><input type="text" name="determination:cms_ref" value="" readonly="readonly" /><br />
    	<label class="follow-on">CMS Person Name : </label><input type="text" name="determination:person_name" value="" readonly="readonly" /><br />
		<label class="follow-on">CMS Email : </label><input type="text" name="determination:email_address" value="" readonly="readonly" /><br />
		<label class="follow-on">Type : </label><select name="determination:determination_type" /><option value="A">'.lang::get('LANG_Det_Type_A').'</option><option value="C" selected>'.lang::get('LANG_Det_Type_C').'</option><option value="X">'.lang::get('LANG_Det_Type_X').'</option></select><br />
		<label class="follow-on">Taxon Details : </label><input type="text" name="determination:taxon_details" value="" /><br />
 	    <label class="follow-on">Extra Info : </label><input type="text" name="determination:taxon_extra_info" value="" /><br />
		';
     $edit_flower_ctrl_args = $focus_flower_ctrl_args;
     $edit_flower_ctrl_args['class']='species-select';
     $edit_flower_ctrl_args['blankText']='';
     $edit_flower_ctrl_args['label']='Main Species';
     $edit_flower_ctrl_args['id']='dummy_tag';
     $r .= data_entry_helper::select($edit_flower_ctrl_args);
     unset($edit_flower_ctrl_args['id']);
     for($j=0; $j<10; $j++){
       $edit_flower_ctrl_args['label']='Species Array '.$j;
       $edit_flower_ctrl_args['fieldname']='fo-edit-flower-id-list-'.$j;
       $r .= data_entry_helper::select($edit_flower_ctrl_args);
     }
     $r .= '        <label >Comment : </label><textarea name="determination:comment" class="taxon-comment" rows="3" ></textarea><br />
        <input type="submit" class="ui-state-default ui-corner-all submit-button" value="'.lang::get('LANG_Validate').'" />
      </form>
	</div>';
    }
    $r .= '	<form id="fo-new-insect-id-form" action="'.iform_ajaxproxy_url($node, 'determination').'" method="POST" style="display: none;">
      <div id="fo-new-insect-id" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-top ui-accordion-content-active">
        <p class="title">'.lang::get('LANG_New_ID').'</p>  
		<input type="hidden" name="website_id" value="'.$args['website_id'].'" />
    	<input type="hidden" name="determination:occurrence_id" value="" />
		<input type="hidden" name="determination:cms_ref" value="'.$uid.'" />  
    	<input type="hidden" name="determination:person_name" value="'.$username.'" />  
		<input type="hidden" name="determination:email_address" value="'.$email.'" />';
		if(user_access('IForm n'.$node->nid.' insect expert')){
			$r .= '		<label for="fo-insect-expert-det-type" class="follow-on">'.lang::get('Status').' : </label><select id="fo-insect-expert-det-type" name="determination:determination_type" /><option value="C" selected>'.lang::get('LANG_Det_Type_C').'</option><option value="X">'.lang::get('LANG_Det_Type_X').'</option></select>';
		} else {
			$r .= '		<input type="hidden" name="determination:determination_type" value="A" />';
		}
		$r .= '		<div class="id-tool-group">
          <input type="hidden" name="determination:taxon_details" />
          <span id="insect-id-button" class="ui-state-default ui-corner-all poll-id-button" >'.lang::get('LANG_Launch_ID_Key').'</span>
		  <span id="insect-id-cancel" class="ui-state-default ui-corner-all poll-id-cancel" >'.lang::get('LANG_Cancel_ID').'</span>
 	      <p id="insect_taxa_list" class="taxa_list"></p>
 	    </div>
 	    <div class="id-specified-group">
 	      '.data_entry_helper::select($focus_insect_ctrl_args).'
          <label for="insect:taxon_extra_info" class="follow-on">'.lang::get('LANG_More_Precise').' : </label> 
          <input type="text" id="insect:taxon_extra_info" name="determination:taxon_extra_info" class="taxon-info" />
        </div>
 	    <div class="id-comment">
          <label for="insect:comment">'.lang::get('LANG_ID_Comment').' </label>
          <textarea id="insect:comment" name="determination:comment" class="taxon-comment" rows="3" ></textarea>
        </div>
	  </div>
      <div id="fo-new-insect-id-buttons" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
        <input type="submit" id="insect_id_submit_button" class="ui-state-default ui-corner-all submit-button" value="'.lang::get('LANG_Validate').'" />
      </div>
    </form>
    <form id="fo-new-flower-id-form" action="'.iform_ajaxproxy_url($node, 'determination').'" method="POST" style="display: none;">
	  <div id="fo-new-flower-id" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-top ui-accordion-content-active">
		<p class="title">'.lang::get('LANG_New_ID').'</p>
		<input type="hidden" name="website_id" value="'.$args['website_id'].'" />
		<input type="hidden" name="determination:occurrence_id" value="" />
		<input type="hidden" name="determination:cms_ref" value="'.$uid.'" />  
		<input type="hidden" name="determination:person_name" value="'.$username.'" />  
		<input type="hidden" name="determination:email_address" value="'.$email.'" />';
		if(user_access('IForm n'.$node->nid.' flower expert')){
			$r .= '		<label for="fo-flower-expert-det-type" class="follow-on">'.lang::get('Status').' : </label><select id="fo-flower-expert-det-type" name="determination:determination_type" /><option value="C" selected>'.lang::get('LANG_Det_Type_C').'</option><option value="X">'.lang::get('LANG_Det_Type_X').'</option></select>';
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
          <label for="flower:taxon_extra_info" class="follow-on">'.lang::get('LANG_More_Precise').' : </label> 
          <input type="text" id="flower:taxon_extra_info" name="determination:taxon_extra_info" class="taxon-info" />
        </div>
 	    <div class="id-comment">
          <label for="flower:comment">'.lang::get('LANG_ID_Comment').' </label>
          <textarea id="flower:comment" name="determination:comment" class="taxon-comment" rows="3" ></textarea>
        </div>
	  </div>
      <div id="fo-new-flower-id-buttons" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
        <input type="submit" id="flower_id_submit_button" class="ui-state-default ui-corner-all submit-button" value="'.lang::get('LANG_Validate').'" />
      </div>
	</form>
	<form id="fo-express-doubt-form" action="'.iform_ajaxproxy_url($node, 'determination').'" method="POST" style="display: none;">
	  <div id="fo-express-doubt" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-top ui-accordion-content-active">
	    <p class="title">'.lang::get('LANG_Doubt').'</p>
		<input type="hidden" name="website_id" value="'.$args['website_id'].'" />
    	<input type="hidden" name="determination:occurrence_id" value="" />
		<input type="hidden" name="determination:cms_ref" value="'.$uid.'" />  
    	<input type="hidden" name="determination:person_name" value="'.$username.'" />  
		<input type="hidden" name="determination:email_address" value="'.$email.'" />
    	<input type="hidden" name="determination:determination_type" value="B" />
        <input type="hidden" name="determination:taxon_extra_info" />
        <input type="hidden" name="determination:taxa_taxon_list_id" />
 	    <div class="doubt-comment">
          <label for="determination:comment">'.lang::get('LANG_Doubt_Comment').' </label>
          <textarea id="determination:comment" name="determination:comment" class="taxon-comment" rows="3" ></textarea>
        </div>
      </div>
      <div id="fo-express-doubt-buttons" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
        <input type="submit" id="doubt_submit_button" class="ui-state-default ui-corner-all submit-button" value="'.lang::get('LANG_Validate').'" />
      </div>
    </form>
	
	<div id="fo-localisation-info" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-top ui-accordion-content-active">
	    <span class="addn-info-title">'.lang::get('LANG_INSEE_Localisation').'</span>
	    <p>'.lang::get('LANG_Locality_Commune').' : <span id="fo-locality-commune"></span></p>
	    <p>'.lang::get('LANG_Locality_Department').' : <span id="fo-locality-department"></span></p>
	    <p>'.lang::get('LANG_Locality_Region').' : <span id="fo-locality-region"></span></p>
    </div>
	<div id="fo-insect-addn-info" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
	    <span class="addn-info-title">'.lang::get('LANG_Additional_Info_Title').'</span>
	    <p>'.lang::get('LANG_Date').' : <span id="fo-insect-date"></span></p>
	    <p>'.lang::get('LANG_Time').' : <span id="fo-insect-start-time"></span> '.lang::get('LANG_To').' <span id="fo-insect-end-time"></span></p>
	    <p>'.$sample_attributes[$skyAttrID]['caption'].': <span id="fo-insect-sky"></span></p>
	    <p>'.$sample_attributes[$temperatureAttrID]['caption'].': <span id="fo-insect-temp"></span></p>
	    <p>'.$sample_attributes[$windAttrID]['caption'].': <span id="fo-insect-wind"></span></p>
	    <p>'.$sample_attributes[$shadeAttrID]['caption'].': <span id="fo-insect-shade"></span></p>
	    <p>'.$occurrence_attributes[$foragingAttrID]['caption'].': <span id="fo-insect-foraging">TEST - TBD REMOVE</span></p>
	</div>

	<div id="fo-flower-addn-info" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
	    <span class="addn-info-title">'.lang::get('LANG_Additional_Info_Title').'</span>
	    <p>'.$occurrence_attributes[$flowerTypeAttrID]['caption'].': <span id="focus-flower-type"></span></p>
	    <p>'.$location_attributes[$habitatAttrID]['caption'].': <span id="focus-habitat"></span></p>
	</div>

	<div id="fo-comments-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
	    <div id="fo-new-comment-button" class="ui-state-default ui-corner-all new-comment-button">'.lang::get('LANG_New_Comment').'</div>
		<span>'.lang::get('LANG_Comments_Title').'</span>
	</div>
	<div id="fo-new-comment" class="ui-accordion-content ui-helper-reset ui-widget-content">
		<form id="fo-new-comment-form" action="'.iform_ajaxproxy_url($node, 'occ-comment').'" method="POST">
		    <input type="hidden" name="website_id" value="'.$args['website_id'].'" />
    		<input type="hidden" name="occurrence_comment:occurrence_id" value="" />
    		<label for="occurrence_comment:person_name">'.lang::get('LANG_Username').' :</label>
		    <input type="text" name="occurrence_comment:person_name" value="'.$username.'" readonly="readonly" />  
    		<label for="occurrence_comment:email_address">'.lang::get('LANG_Email').' :</label>
		    <input type="text" name="occurrence_comment:email_address" value="'.$email.'" readonly="readonly" />
		    '.data_entry_helper::textarea(array('label'=>lang::get('LANG_Comment').' ', 'fieldname'=>'occurrence_comment:comment', 'class'=>'required', 'suffixTemplate'=>'nosuffix')).'
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
	if(jQuery('#refresh-message').is(':hidden')){
		var errorString = \"".lang::get('LANG_Indicia_Warehouse_Error')."\";
		if(data.error){	errorString = errorString + ' : ' + data.error;	}
		if(data.errors){
			for (var i in data.errors){
				errorString = errorString + ' : ' + data.errors[i];
			}
		}
		// the most likely cause is authentication failure - eg the read authentication has timed out.
		// prevent further use of the form:
		jQuery('#filter,#focus-occurrence,#focus-collection').hide();
		jQuery('#refresh-message').show();
		alert(errorString);
	}
};

jQuery('#imp-georef-search-btn').removeClass('indicia-button').addClass('search-button');
$.validator.messages.required = \"".lang::get('validation_required')."\";

// remove the (don't know) entry from flower type filter.
jQuery('[name=occAttr\\:".$flowerTypeAttrID."]').filter('[value=".$args['flower_type_dont_know']."]').parent().remove();
jQuery('[name=location\\:geom]').attr('name', 'location:centroid_geom');

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
jQuery('#name-filter-header').click(function(evt){
    if($(evt.originalTarget).hasClass('reset-button')){
        return;
    }
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
jQuery('#date-filter-header').click(function(evt){
    if($(evt.originalTarget).hasClass('reset-button')){
        return;
    }
	jQuery('#date-filter-header').toggleClass('ui-state-active').toggleClass('ui-state-default');
	jQuery('#fold-date-button').toggleClass('fold-button-folded');
	jQuery('#date-filter-body').toggleClass('ui-accordion-content-active');
});

jQuery('#reset-flower-button').click(function(){
	jQuery('#flower-filter-body').find('select').val('');
	jQuery('[name=flower\\:taxon_extra_info]').val(\"".lang::get('LANG_More_Precise')."\");
	jQuery('#flower-filter-body').find(':checkbox').removeAttr('checked');
});
jQuery('#flower-filter-header').click(function(evt){
    if($(evt.originalTarget).hasClass('reset-button')){
        return;
    }
	jQuery('#flower-filter-header').toggleClass('ui-state-active').toggleClass('ui-state-default');
	jQuery('#fold-flower-button').toggleClass('fold-button-folded');
	jQuery('#flower-filter-body').toggleClass('ui-accordion-content-active');
});

jQuery('#reset-insect-button').click(function(){
	jQuery('#insect-filter-body').find('select').val('');
	jQuery('[name=insect\\:taxon_extra_info]').val(\"".lang::get('LANG_More_Precise')."\");
	jQuery('#insect-filter-body').find(':checkbox').removeAttr('checked');
});
jQuery('#insect-filter-header').click(function(evt){
    if($(evt.originalTarget).hasClass('reset-button')){
        return;
    }
	jQuery('#insect-filter-header').toggleClass('ui-state-active').toggleClass('ui-state-default');
	jQuery('#fold-insect-button').toggleClass('fold-button-folded');
	jQuery('#insect-filter-body').toggleClass('ui-accordion-content-active');
});

jQuery('#reset-conditions-button').click(function(){
	jQuery('#conditions-filter-body').find(':checkbox').removeAttr('checked');
});
jQuery('#conditions-filter-header').click(function(evt){
    if($(evt.originalTarget).hasClass('reset-button')){
        return;
    }
	jQuery('#conditions-filter-header').toggleClass('ui-state-active').toggleClass('ui-state-default');
	jQuery('#fold-conditions-button').toggleClass('fold-button-folded');
	jQuery('#conditions-filter-body').toggleClass('ui-accordion-content-active');
});

jQuery('#reset-location-button').click(function(){
	polygonLayer.destroyFeatures();
	polygonLayer.map.searchLayer.destroyFeatures(); //georef Layer
	if(inseeLayer != null) inseeLayer.destroyFeatures();
	inseeLayerStore.destroyFeatures();
	jQuery('[name=place\\:INSEE]').val('');
	jQuery('#imp-insee-div').hide();
	var div = jQuery('#map')[0];
	var center = new OpenLayers.LonLat(".$args['map_centroid_long'].", ".$args['map_centroid_lat'].");
	center.transform(div.map.displayProjection, div.map.projection);
	div.map.setCenter(center, ".((int) $args['map_zoom']).");
});
jQuery('#location-filter-header').click(function(evt){
    if($(evt.originalTarget).hasClass('reset-button')){
        return;
    }
	jQuery('#location-filter-header').toggleClass('ui-state-active').toggleClass('ui-state-default');
	jQuery('#fold-location-button').toggleClass('fold-button-folded');
	jQuery('#location-filter-body').toggleClass('ui-accordion-content-active');
});

jQuery('#flower-image,#show-flower-button').click(function(){
	if(jQuery('#flower-image').data('occID') != 'none'){
		loadFlower(jQuery('#flower-image').data('occID'), jQuery('#flower-image').data('collectionIndex'));
	}
});

jQuery('#fo-doubt-button').click(function(){
	jQuery('#fo-new-insect-id-form,#fo-new-flower-id-form').hide();
	jQuery('#fo-express-doubt-form [name=determination\\:comment]').val(\"".lang::get('LANG_Default_Doubt_Comment')."\");
	if(jQuery('#fo-express-doubt-form').filter(':visible').length>0) {
	  jQuery('#fo-express-doubt-form').hide();
	  if(!jQuery('#fo-id-history').hasClass('empty')){
		jQuery('#fo-id-history').addClass('ui-accordion-content-active');
		jQuery('#fo-id-buttons').removeClass('ui-corner-bottom');
	  }
	} else {
	  jQuery('#fo-express-doubt-form').show();
	  if(!jQuery('#fo-id-history').hasClass('empty')){
		jQuery('#fo-id-history').removeClass('ui-accordion-content-active');
		jQuery('#fo-id-buttons').addClass('ui-corner-bottom');
	  }
	}
});

jQuery('#fc-next-button,#fc-prev-button').click(function(){
	var index = jQuery(this).data('index');
	var id = searchResults.features[index].attributes.collection_id;
	loadCollection(id, index);
});

jQuery('#fc-filter-button,#fo-filter-button').click(function(){
    jQuery('#filter').show();
    jQuery('#poll-banner').empty();
    jQuery('#focus-occurrence,#focus-collection,#results-insects-header,#results-collections-header,#results-insects-results,#results-collections-results').hide();
    loadFilter();
    if(searchResults != null){
    	if(searchResults.type == 'C')
    		jQuery('#results-collections-header,#results-collections-results').show();
    	else 
    		jQuery('#results-insects-header,#results-insects-results').show();
	}
});
bulkValidating=false;";
    if(user_access('IForm n'.$node->nid.' insect expert')){
		data_entry_helper::$javascript .= "
bulkCancel=false;
bulkType='';
jQuery('#validate-taxon-progress').progressbar({value: 0});
jQuery('#validate-page-progress').progressbar({value: 0});
jQuery('#validate-collection-progress').progressbar({value: 0});
jQuery('form#bulk-validation-form').ajaxForm({
	dataType:  'json', 
	beforeSubmit:   function(data, obj, options){
		var list_string = jQuery('#bulk-validation-form').data('taxa_taxon_list_id_list_string');
		if(list_string == null) list_string='';
		var resultsIDs = list_string.substring(1, list_string.length - 1).split(',');
		if(resultsIDs[0] != '') {
			for(var i = 0; i<resultsIDs.length; i++)
				data.push({name: 'determination\\:taxa_taxon_list_id_list[]', value: resultsIDs[i]});
		} else
			data.push({name: 'determination\\:taxa_taxon_list_id_list[]', value: ''});
		if(bulkCancel){
			bulkValidateFinish(\"".lang::get('LANG_Bulk_Validation_Canceled')."\");
			return false;
		}	
		return true;
	},
	success:   function(data){
		if(data.error == undefined){
			var form = jQuery('form#bulk-validation-form');
			jQuery('.filter-insect-container').filter('[occID='+form.find('[name=determination\\:occurrence_id]').val()+']').find('.occurrence-dubious,.insect-ok').removeClass('occurrence-dubious insect-ok').addClass('occurrence-valid');
			jQuery('.collection-insect-container').filter('[occID='+form.find('[name=determination\\:occurrence_id]').val()+']').find('.occurrence-dubious,.insect-ok').removeClass('occurrence-dubious insect-ok').addClass('occurrence-valid');
			jQuery('.collection-flower-determination').filter('[occID='+form.find('[name=determination\\:occurrence_id]').val()+']').find('.flower-dubious,.flower-ok').remove();
			jQuery('.collection-flower-determination').filter('[occID='+form.find('[name=determination\\:occurrence_id]').val()+']').find('p').append('<span class=\"flower-valid\"><img src=\"/misc/watchdog-ok.png\" style=\"vertical-align: middle;\"></span>').find('.flower-dubious').remove();
			uploadValidation();
		} else {
			alert(data.error);
			bulkValidateFinish(\"".lang::get('LANG_Bulk_Validation_Error')."\");
  		}
	} 
});
uploadValidation = function(){
	var occID = false;
	var max = jQuery('#validate-'+bulkType.toLowerCase()+'-progress').data('max');
	switch(bulkType){
	  case 'Taxon':
		var index = jQuery('#validate-taxon-progress').data('index');
		jQuery('#validate-taxon-progress').data('index',index+1);
		jQuery('#validate-taxon-progress').progressbar('option','value',index*100/max);
		if(index<max){
			if(jQuery('#results-collections-results').filter(':visible').length > 0)
				occID=searchResults.features[index].attributes.flower_id;
			else 
				occID=searchResults.features[index].attributes.insect_id;
			jQuery('#validate-taxon-message').html('<span>'+index+'/'+max+' : '+Math.round(index*100/max)+'%</span>');
		}
		break;
	  case 'Page':
		var todolist;
		if(jQuery('#results-collections-results').filter(':visible').length > 0)
			todolist =jQuery('.collection-flower-determination').find('.flower-dubious,.flower-ok').parent();
		else
			todolist =jQuery('.filter-insect-container').find('.occurrence-dubious,.insect-ok').parent();
		var completed = max - todolist.length;
		jQuery('#validate-page-progress').progressbar('option','value',completed*100/max);
		if(todolist.length>0){
			occID=jQuery(todolist[0]).parent().attr('occID');
			jQuery('#validate-page-message').html('<span>'+completed+'/'+max+' : '+Math.round(completed*100/max)+'%</span>');
		}
		break;
	  case 'Collection': // insects only
		var todolist = jQuery('.collection-insect-container').find('.occurrence-dubious,.insect-ok').parent().parent();
		var completed = max - todolist.length;
		jQuery('#validate-collection-progress').progressbar('option','value',completed*100/max);
		if(todolist.length>0){
			occID=jQuery(todolist[0]).attr('occID');
			jQuery('#validate-collection-message').html('<span>'+completed+'/'+max+' : '+Math.round(completed*100/max)+'%</span>');
		}
		break;
	}
	if(occID && !bulkCancel){
		ajaxStack.push($.getJSON(\"".$svcUrl."/data/determination\" + 
				\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
				\"&occurrence_id=\" + occID + \"&deleted=f&orderby=id&sortdir=DESC&REMOVEABLEJSONP&callback=?\", function(detData) {
			if(!(detData instanceof Array)){
   				alertIndiciaError(detData);
   			// taxon based is more complex than page as we do not have the determination stored - check last is 'doubted' or 'original', at this point a taxon has been specified so no 'X'
   			} else if (detData.length>0) {
				if(bulkType!='Taxon' || detData[0].determination_type == 'A' || detData[0].determination_type == 'B'){
					// all reidentified taxon will have either a unidentified or Valid flag, so will not appear in this list, so doesn't matter if the taxon has changed.
					var form = jQuery('form#bulk-validation-form');
					form.find('[name=determination\\:occurrence_id]').val(detData[0].occurrence_id);
					form.find('[name=determination\\:taxa_taxon_list_id]').val(detData[0].taxa_taxon_list_id);
					form.data('taxa_taxon_list_id_list_string',detData[0].taxa_taxon_list_id_list);
					form.find('[name=determination\\:taxon_details]').val(detData[0].taxon_details);
					form.find('[name=determination\\:taxon_extra_info]').val(detData[0].taxon_extra_info == null ? '' : detData[0].taxon_extra_info);
					jQuery('form#bulk-validation-form').submit();
				} else {
					uploadValidation();
				}
			}}));
	} else {
		bulkValidateFinish(bulkCancel ? \"".lang::get('LANG_Bulk_Validation_Canceled')."\" : \"".lang::get('LANG_Bulk_Page_Validation_Completed')."\");
	}
}
bulkValidatePrep=function(type, max){
	bulkValidating=true; //switches off afficher insect.
	bulkCancel=false;
	bulkType=type;
	jQuery('#validate-'+bulkType.toLowerCase()+'-button').addClass('loading-button');
	jQuery('#validate-'+bulkType.toLowerCase()+'-progress,#cancel-validate-'+bulkType.toLowerCase()).show();
	jQuery('#validate-page-message,#validate-taxon-message,#validate-collection-message').empty();
	jQuery('#imp-georef-search-btn,#search-insee-button,#search-insects-button,#search-collections-button,#validate-page-button,#validate-taxon-button,#validate-collection-button').attr('disabled','disabled');
	jQuery('#validate-'+bulkType.toLowerCase()+'-message').html('<span>0/'+max+' : 0%</span>');
	jQuery('#validate-'+bulkType.toLowerCase()+'-progress').data('max',max).data('index',0).progressbar('option','value',0);
}
bulkValidateFinish=function(message){
	bulkCancel=false;
	bulkValidating=false; //switches on afficher insect.
	jQuery('#validate-page-button,#validate-taxon-button,#validate-collection-button').removeClass('loading-button');
	jQuery('#validate-page-progress,#cancel-validate-page,#validate-taxon-progress,#cancel-validate-taxon,#validate-collection-progress,#cancel-validate-collection').hide();
	jQuery('#validate-page-message,#validate-taxon-message,#validate-collection-message').empty();
	if(message) jQuery('#validate-'+bulkType.toLowerCase()+'-message').html('<span>'+message+'</span>');
	jQuery('#imp-georef-search-btn,#search-insee-button,#search-insects-button,#search-collections-button,#validate-page-button,#validate-taxon-button,#validate-collection-button').removeAttr('disabled');
}
jQuery('.cancel-validate-button').click(function(){bulkCancel=true;});
jQuery('#validate-page-button').click(function(){
	// first of all we only validate insect-ok and occurrence-dubious.
	var max;
	if(jQuery('#results-collections-results').filter(':visible').length > 0)
		max =jQuery('.collection-flower-determination').find('.flower-dubious,.flower-ok').length;
	else
		max =jQuery('.filter-insect-container').find('.occurrence-dubious,.insect-ok').length;
	bulkValidatePrep('Page', max);
	if(max==0){
		bulkValidateFinish(\"".lang::get('LANG_Bulk_Page_Nothing_To_Do')."\");
	} else if(!confirm(\"".lang::get('LANG_Confirm_Bulk_Page_Validation')."\")){
		bulkValidateFinish(false);
		return;
	} else {
		uploadValidation();
	}
});
jQuery('#validate-taxon-button').click(function(){
	// first of all we only validate insect-ok and occurrence-dubious.
	var max=0;
	if(searchResults!= null) max=searchResults.features.length;
	bulkValidatePrep('Taxon', max);
	if(max==0){
		bulkValidateFinish(\"".lang::get('LANG_Bulk_Taxon_Nothing_To_Do')."\");
	} else if(!confirm(\"".lang::get('LANG_Confirm_Bulk_Taxon_Validation')."\")){
		bulkValidateFinish(false);
		return;
	} else {
		uploadValidation();
	}
});
jQuery('#validate-collection-button').click(function(){
	// first of all we only validate insect-ok and occurrence-dubious.
	var max=jQuery('.collection-insect-container').find('.occurrence-dubious,.insect-ok').length;
	bulkValidatePrep('Collection', max);
	if(max==0){
		bulkValidateFinish(\"".lang::get('LANG_Bulk_Collection_Nothing_To_Do')."\");
	} else if(!confirm(\"".lang::get('LANG_Confirm_Bulk_Collection_Validation')."\")){
		bulkValidateFinish(false);
		return;
	} else {
		uploadValidation();
	}
});
";
	}
	data_entry_helper::$javascript .= "
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
    jQuery('[name=sample\\:id]').val(id);
    jQuery('#fc-add-preferred').attr('smpID', id);
	collection_preferred_object.collection_id = id;
	jQuery('#fc-new-comment-button').".(user_access('IForm n'.$node->nid.' create collection comment') ? "show()" : "hide()").";
	jQuery('#fc-new-comment').removeClass('ui-accordion-content-active');
	jQuery('#fc-new-location,#fc-new-location-desc').".(user_access('IForm n'.$node->nid.' edit geolocation') ? "show()" : "hide()").";
	jQuery('#focus-occurrence,#filter,#fc-next-button,#fc-prev-button').hide();
	jQuery('#flower-image,#environment-image').empty().addClass('loading');
	jQuery('#collection-insects,#collection-date,#collection-flower-name,#collection-flower-type,#collection-habitat,#collection-user-name').empty();
	jQuery('#focus-collection').show();
	jQuery('#flower-image,#environment-image').height(jQuery('#flower-image').width()/(".$args['Flower_Image_Ratio']."));
    jQuery('#fc-front-page-message,#fc-new-location-message').empty();
    if(index != null){
    	if(index < (searchResults.features.length-1) )
    		jQuery('#fc-next-button').show().data('index', index+1);
    	if(index > 0)
    		jQuery('#fc-prev-button').show().data('index', index-1);
    }
    if(jQuery('#map2').children().length == 0) {
    	".$map2JS."
		jQuery('#map2')[0].map.editLayer.events.register('featuresadded', {}, function(a1){
			jQuery('#fc-new-location-message').empty();
			var parser = new OpenLayers.Format.WKT();
			var feature = parser.read(jQuery('#imp-geom').val());
			var filter = new OpenLayers.Filter.Spatial({
  				type: OpenLayers.Filter.Spatial.CONTAINS ,
    			property: 'the_geom',
    			value: feature.geometry
			});
			var locality = jQuery('#collection-locality');
			var scope = {target: locality};
			inseeProtocol.read({filter: filter, callback: fillLocationDetails, scope: scope});
		});
 	};
	jQuery('#map2')[0].map.editLayer.clickControl.".(user_access('IForm n'.$node->nid.' edit geolocation') ? "" : "de")."activate();
	jQuery('#map2')[0].map.editLayer.destroyFeatures();
//	jQuery('#map2').width('auto');
	jQuery('#flower-image').data('occID', 'none').data('collectionIndex', index);
	loadComments(id, '#fc-comment-list', 'sample_comment', 'sample_id', 'sample-comment-block', 'sample-comment-body', true, ".(user_access('IForm n'.$node->nid.' delete collection comment') ? "true" : "false").");
	// only need to reset the timeout on the first fetch as rest follow on quickly.
	ajaxStack.push($.getJSON(\"".$svcUrl."/data/occurrence\" +
			\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
			\"&sample_id=\"+id+\"&deleted=f&REMOVEABLEJSONP&callback=?\", function(flowerData) {
   		if(!(flowerData instanceof Array)){
   			alertIndiciaError(flowerData);
   		} else if (flowerData.length>0) {
   			loadImage('occurrence_image', 'occurrence_id', flowerData[0].id, '#flower-image', ".$args['Flower_Image_Ratio'].", function(imageRecord){collection_preferred_object.flower_image_path = imageRecord.path}, 'med-', false, false);
			jQuery('#flower-image').data('occID', flowerData[0].id);
			collection_preferred_object.flower_id = flowerData[0].id;
			ajaxStack.push($.getJSON(\"".$svcUrl."/data/determination\" + 
					\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
					\"&occurrence_id=\" + flowerData[0].id + \"&deleted=f&orderby=id&sortdir=DESC&REMOVEABLEJSONP&callback=?\", function(detData) {
   				if(!(detData instanceof Array)){
   					alertIndiciaError(detData);
   				} else if (detData.length>0) {
					var string = '';
					if(detData[0].taxon != '' && detData[0].taxon != null){
						string = htmlspecialchars(detData[0].taxon);
		  			}
					if(detData[0].taxa_taxon_list_id_list != '' && detData[0].taxa_taxon_list_id_list != null){
			  			var resultsIDs = detData[0].taxa_taxon_list_id_list.substring(1, detData[0].taxa_taxon_list_id_list.length - 1).split(',');
						if(resultsIDs[0] != '') {
							for(var j=0; j < resultsIDs.length; j++){
								for(k = 0; k< flowerTaxa.length; k++){
									if(flowerTaxa[k].id == resultsIDs[j]){
										string = (string == '' ? '' : string + ', ') + htmlspecialchars(flowerTaxa[k].taxon);
										break;
									}
								};
					  		}
					  	}
					}
		  			if(detData[0].taxon_extra_info != '' && detData[0].taxon_extra_info != null && detData[0].taxon_extra_info != 'null'){
						string = (string == '' ? '' : string + ' ') + '('+htmlspecialchars(detData[0].taxon_extra_info)+')';
					}
					jQuery('<span>".lang::get('LANG_Flower_Name').": <span class=\"collection-value\">'+string+'</span></span>').appendTo('#collection-flower-name');
				}}));
			ajaxStack.push($.getJSON(\"".$svcUrl."/data/occurrence_attribute_value\"  +
   					\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   					\"&occurrence_id=\" + flowerData[0].id + \"&REMOVEABLEJSONP&callback=?\", function(attrdata) {
				if(!(attrdata instanceof Array)){
   					alertIndiciaError(attrdata);
   				} else if (attrdata.length>0) {
   					for(i=0; i< attrdata.length; i++){
						if (attrdata[i].id){
							switch(parseInt(attrdata[i].occurrence_attribute_id)){
								case ".$flowerTypeAttrID.":
									jQuery('<span>'+convertTerm(attrdata[i].raw_value)+'</span>').appendTo('#collection-flower-type');
									break;
  			}}}}}));
				
		}
	}));";
    if(user_access('IForm n'.$node->nid.' add to front page')){
    	data_entry_helper::$javascript .= "
	jQuery('#fc-front-page-form').find('[name^=smpAttr\\:".$frontPageAttrID."]')
		.attr('name', 'smpAttr:".$frontPageAttrID."')
		.filter('[value=0]')
		.attr('checked', 'checked');";
    }
	data_entry_helper::$javascript .= "
	ajaxStack.push($.getJSON(\"".$svcUrl."/data/sample_attribute_value\"  +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&sample_id=\" + id + \"&REMOVEABLEJSONP&callback=?\", function(attrdata) {
		if(!(attrdata instanceof Array)){
   			alertIndiciaError(attrdata);
   		} else if (attrdata.length>0) {
			for(i=0; i< attrdata.length; i++){
				if (attrdata[i].id){
					switch(parseInt(attrdata[i].sample_attribute_id)){
						case ".$usernameAttrID.":
							jQuery('<span>".lang::get('LANG_Comment_By')."'+attrdata[i].value+'</span>').appendTo('#collection-user-name');
							break;
						case ".$uidAttrID.":
							collection_preferred_object.user_id = attrdata[i].value
			       		    jQuery('#collection-user-link').attr('href', '".url('node/'.$node->nid)."?user_id='+attrdata[i].value);
							if(attrdata[i].value == ".$user->uid.") { // user can edit geolocation of own collections.
								jQuery('#fc-new-location,#fc-new-location-desc').show();
								jQuery('#map2')[0].map.editLayer.clickControl.activate();
							}
			       		    break;";
    if(user_access('IForm n'.$node->nid.' add to front page')){
    	data_entry_helper::$javascript .= "
						case ".$frontPageAttrID.":
							jQuery('#fc-front-page-form').find('[name^=smpAttr\\:".$frontPageAttrID."]')
								.attr('name', 'smpAttr:".$frontPageAttrID.":'+attrdata[i].id)
								.filter('[value='+attrdata[i].raw_value+']')
								.attr('checked', 'checked');
							break;";
    }
    $base = base_path();
    if(substr($base, -1)!='/') $base.='/';
	data_entry_helper::$javascript .= "
    }}}}}));
	ajaxStack.push($.getJSON(\"".$svcUrl."/data/sample/\" +id+
			\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
			\"&REMOVEABLEJSONP&callback=?\", function(collectionData) {
   		if(!(collectionData instanceof Array)){
   			alertIndiciaError(collectionData);
   		} else if (collectionData.length>0) {
   			if(collectionData[0].parent_id != null) {
   				alertIndiciaError({error: \"".lang::get('LANG_Bad_Collection_ID')."\"});
   				return;
   			}
   			jQuery('[name=sample\\:date_start]').val(collectionData[0].date_start);
   			jQuery('[name=sample\\:date_end]').val(collectionData[0].date_end);
   			jQuery('[name=sample\\:date_type]').val(collectionData[0].date_type);
   			jQuery('[name=sample\\:location_id]').val(collectionData[0].location_id);
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
				    jQuery('#location-id').val(locationData[0].id);
	    			jQuery('[name=location\\:name]').val(locationData[0].name);
				    jQuery('#imp-sref').val(locationData[0].centroid_sref);
					jQuery('#imp-geom').val(locationData[0].centroid_geom);
					var parts=locationData[0].centroid_sref.split(' ');
					var refx = parts[0].split(',');
					jQuery('#imp-sref-lat').val(refx[0]);
					jQuery('#imp-sref-long').val(parts[1]).change(); // adds location, auto 
				    loadImage('location_image', 'location_id', locationData[0].id, '#environment-image', ".$args['Environment_Image_Ratio'].", function(imageRecord){collection_preferred_object.environment_image_path = imageRecord.path}, 'med-', true, false);
				}
			}));
	        ajaxStack.push($.getJSON(\"".$svcUrl."/data/location_attribute_value\"  +
   					\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   					\"&location_id=\" + collectionData[0].location_id + \"&REMOVEABLEJSONP&callback=?\", function(attrdata) {
				if(!(attrdata instanceof Array)){
   					alertIndiciaError(attrdata);
   				} else if (attrdata.length>0) {
					var habitat_string = '';
					for(i=0; i< attrdata.length; i++){
						if (attrdata[i].id){
							switch(parseInt(attrdata[i].location_attribute_id)){
								case ".$habitatAttrID.":
									if (attrdata[i].raw_value > 0) habitat_string = (habitat_string == '' ? convertTerm(attrdata[i].raw_value) : (habitat_string + ' / ' + convertTerm(attrdata[i].raw_value)));
									break;
					}}}
					jQuery('<span>'+habitat_string+'</span>').appendTo('#collection-habitat');
  			}}));
		}
	}));
	// we want to tag end of row picture, so we need to keep track of its position in list.
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
					url: \"".$svcUrl."/data/occurrence?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&deleted=f&orderby=id&REMOVEABLEJSONP&callback=?&query=\"+escape(escape(JSON.stringify({'in': {'sample_id': sessList}}))),
					dataType: 'json',
					myIndex: this.myIndex,
					success: function(insectData) {
					  if(!(insectData instanceof Array)){
   						alertIndiciaError(insectData);
   		  			  } else if (insectData.length>0) {
					   var n=0;
					   // there seems to be an upper limit on how many can be done at one time - so restrict to 20 at a time
				 	   while(n<insectData.length){
   						var insectIDs=[];
						for (var j=0;j<20 && n<insectData.length; j++,n++){
							insectIDs.push(insectData[n].id);
							var insect=jQuery('<div class=\"collection-insect-container\" />').attr('occID', insectData[n].id).appendTo('#collection-insects');
							if(n/".$args['insectsPerRow']." == parseInt(n/".$args['insectsPerRow'].")) insect.addClass('start-of-row');
							if((n+1)/".$args['insectsPerRow']." == parseInt((n+1)/".$args['insectsPerRow'].")) insect.addClass('end-of-row');
							insect = jQuery('<div class=\"ui-widget-content ui-corner-all collection-insect\" />').appendTo(insect);
							jQuery('<p class=\"insect-tag determination-flag centre-flag occurrence-unknown\" />').appendTo(insect);
							var image = jQuery('<div class=\"insect-image empty loading\" />').appendTo(insect).data('occID',insectData[n].id)
									.data('collectionIndex',this.myIndex).click(function(){
								loadInsect(jQuery(this).data('occID'),jQuery(this).data('collectionIndex'),null,'C');								
							});
							image.height(image.width()/(".$args['Insect_Image_Ratio']."));
							jQuery('<p class=\"insect-determination empty\" />').appendTo(insect);
							jQuery('<div class=\"ui-state-default ui-corner-all display-button\">".lang::get('LANG_Display')."</div>')
									.appendTo(insect).attr('occID',insectData[n].id).data('collectionIndex',this.myIndex).data('collectionInsectIndex',j).click(function(){
								loadInsect(jQuery(this).attr('occID'),jQuery(this).data('collectionIndex'),null,'C');								
							});
						}
						ajaxStack.push($.getJSON(\"".$svcUrl."/data/occurrence_image\" +
								\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
								\"&REMOVEABLEJSONP&callback=?&query=\"+escape(escape(JSON.stringify({'in': {'occurrence_id': insectIDs}}))), 
							function(imageData) {
								if(!(imageData instanceof Array)){
									alertIndiciaError(imageData);
								} else if (imageData.length>0) {
									for (var k=0;k<imageData.length;k++){
										var target = jQuery('.collection-insect-container').filter('[occID='+imageData[k].occurrence_id+']').find('.insect-image');
										target.empty().removeClass('empty');
										insertImage('med-', imageData[k].path, target, ".$args['Insect_Image_Ratio'].", false, false);
										collection_preferred_object.insects.push({insect_id: imageData[k].occurrence_id, insect_image_path: imageData[k].path})
									}}}));
						ajaxStack.push($.getJSON(\"".$svcUrl."/data/determination\" + 
									\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
									\"&deleted=f&orderby=id&sortdir=DESC&REMOVEABLEJSONP&callback=?&query=\"+escape(escape(JSON.stringify({'in': {'occurrence_id': insectIDs}}))),
								function(detData) {
									if(!(detData instanceof Array)){
										alertIndiciaError(detData);
									} else {
										if (detData.length>0) {
											for (var k=0;k<detData.length;k++){
												var insect = jQuery('.collection-insect-container').filter('[occID='+detData[k].occurrence_id+']');
												var det = insect.find('.insect-determination').filter('.empty');
												if(det.length>0){
													var string = '';
													if(detData[k].taxon != '' && detData[k].taxon != null){
														string = htmlspecialchars(detData[k].taxon);
										  			}
													if(detData[k].taxa_taxon_list_id_list != '' && detData[k].taxa_taxon_list_id_list != null){
														var resultsIDs = detData[k].taxa_taxon_list_id_list.substring(1, detData[k].taxa_taxon_list_id_list.length - 1).split(',');
														if(resultsIDs[0] != '') {
															for(var j=0; j < resultsIDs.length; j++){
																for(var m = 0; m< insectTaxa.length; m++){
																	if(insectTaxa[m].id == resultsIDs[j]){
																		string = (string == '' ? '' : string + ', ') + htmlspecialchars(insectTaxa[m].taxon);
																		break;
																	}}}}}
													// we use the short determination data here - no extra info
													det.empty().removeClass('empty');
													if(string != '')
														jQuery('<div><p>".lang::get('LANG_Last_ID').":</p><p><strong>'+string+'</strong></p></div>').addClass('insect-id').appendTo(det);
													var tag = insect.find('.insect-tag').removeClass('occurrence-unknown');
													if(detData[k].determination_type == 'B' || detData[k].determination_type == 'I' || detData[k].determination_type == 'U'){
														tag.addClass('occurrence-dubious');
													} else if(detData[k].determination_type == 'C'){
														tag.addClass('occurrence-valid');
													} else {
														tag.addClass('insect-ok');
													}
												}
											}
										}
						}}));
					  }}
					}
				}));
				jQuery('#collection-insects').ajaxStop(function(event){
					var group = jQuery('#collection-insects').find('.collection-insect');
					var tallest = 0;
					group.each(function(){ tallest = Math.max($(this).height(), tallest); });
					group.each(function(){ $(this).height(Math.max($(this).height(), tallest)); }); // have synchronicity problems.
					$(this).unbind(event);
				});
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
fillOccurrenceLocationDetails = function(a1)
{
	jQuery('#fo-locality-commune,#fo-locality-department,#fo-locality-region').empty();
	if(a1.features.length > 0) {
	    jQuery('#fo-locality-commune').empty().append(a1.features[0].attributes.NOM+' ('+a1.features[0].attributes.INSEE_NEW+')');
	    jQuery('#fo-locality-department').empty().append(a1.features[0].attributes.DEPT_NOM+' ('+a1.features[0].attributes.DEPT_NUM+')');
	    jQuery('#fo-locality-region').empty().append(a1.features[0].attributes.REG_NOM+' ('+a1.features[0].attributes.REG_NUM+')');
	}
}
addCollection = function(index, attributes, geom, first){
	// first the text, then the flower and environment picture, then the small insect pictures, then the afficher button
	var collection=jQuery('<div class=\"ui-widget-content ui-corner-all filter-collection\" />').attr('collID', attributes.collection_id).attr('index', index).appendTo('#results-collections-results');

	var details = jQuery('<div class=\"collection-details\" />').appendTo(collection);
	if(attributes.datedebut_txt == attributes.datefin_txt){
	  jQuery('<p class=\"collection-date\">'+convertDate(attributes.datedebut_txt,false)+'</p>').appendTo(details);
    } else {
	  jQuery('<p class=\"collection-date\">'+convertDate(attributes.datedebut_txt,false)+' - '+convertDate(attributes.datefin_txt,false)+'</p>').appendTo(details);
    }
	jQuery('<p class=\"collection-name\">'+attributes.nom+'</p>').appendTo(details);
	var locality = jQuery('<p class=\"collection-locality\"></p>').appendTo(details);
	var filter = new OpenLayers.Filter.Spatial({
  			type: OpenLayers.Filter.Spatial.CONTAINS ,
    		property: 'the_geom',
    		value: geom
  	});
	var scope = {target: locality};
	inseeProtocol.read({filter: filter, callback: fillLocationDetails, scope: scope});
	
	if(typeof attributes.deleted != 'undefined' && attributes.deleted==true) {
		collection.css('background-color','#FF0000');
		return;
	}
	
	var flower = jQuery('<div class=\"collection-image collection-flower loading\" />').data('occID', attributes.flower_id).
		data('collectionIndex', index).click(function(){
			loadFlower(jQuery(this).data('occID'), jQuery(this).data('collectionIndex'));
	});
	flower.appendTo(collection);
	insertImage('med-', attributes.image_de_la_fleur, flower, ".$args['Flower_Image_Ratio'].", false, false);

	var location = jQuery('<div class=\"collection-image collection-environment loading\" />').appendTo(collection);
	insertImage('med-', attributes.image_de_environment, location, ".$args['Environment_Image_Ratio'].", false, false);
	
	jQuery('<div class=\"collection-flower-determination empty\"></div>').data('occID', attributes.flower_id).attr('occID', attributes.flower_id).appendTo(collection);

	jQuery('<div class=\"collection-photoreel\"></div>').attr('collID', attributes.collection_id).appendTo(collection);
	
	var displayButtonContainer = jQuery('<div class=\"collection-buttons\"></div>').appendTo(collection);
	jQuery('<div class=\"ui-state-default ui-corner-all display-button\">".lang::get('LANG_Display')."</div>').click(function(){
		loadCollection(jQuery(this).data('value'), jQuery(this).data('index'));
	}).appendTo(displayButtonContainer).data('value',attributes.collection_id).data('index',index);

	jQuery.getJSON(\"".$svcUrl."/data/sample?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + (first ? \"&reset_timeout=true\" : \"\") + \"&callback=?&parent_id=\"+attributes.collection_id,
        function(sessiondata) {
		  if(!(sessiondata instanceof Array)){
   			alertIndiciaError(sessiondata);
   		  } else {
   			var sessionIDs=[];
   			for (var i=0;i<sessiondata.length;i++){
				var photoreel = jQuery('.collection-photoreel').filter('[collID='+sessiondata[i].parent_id+']');
				jQuery('<span class=\"photoreel-session\"></span>').attr('sessID', sessiondata[i].id).appendTo(photoreel);
				sessionIDs.push(sessiondata[i].id);
			}
			$.getJSON(\"".$svcUrl."/data/occurrence/\" +
					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&orderby=id\" +
					\"&deleted=f&callback=?&query=\"+escape(escape(JSON.stringify({'in': {'sample_id': sessionIDs}}))), function(insectData) {
		    	if(!(insectData instanceof Array)){
   					alertIndiciaError(insectData);
   				} else if (insectData.length>0) {
				  var n=0;
				  // there seems to be an upper limit on how many can be done at one time - so restrict to 20 at a time
				  while(n<insectData.length){
   					var insectIDs=[];
					for (var j=0;j<20 && n<insectData.length; j++,n++){
						var container = jQuery('<div/>').addClass('thumb loading').attr('occID', insectData[n].id.toString()).data('collectionIndex',index).data('determination',false).data('image',false).click(function () {
							loadInsect(jQuery(this).attr('occID'),jQuery(this).data('collectionIndex'),null,'P');
						});
						jQuery('.photoreel-session').filter('[sessID='+insectData[n].sample_id+']').append(container);
						insectIDs.push(insectData[n].id);
					}
					// Not all insects will have determinations, but they will all have images
					$.getJSON(\"".$svcUrl."/data/determination\" + 
							\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
							\"&orderby=id&sortdir=DESC&deleted=f&callback=?&query=\"+escape(escape(JSON.stringify({'in': {'occurrence_id': insectIDs}}))), function(detData) {
						if(!(detData instanceof Array)){
   							alertIndiciaError(detData);
   						} else if (detData.length>0) {
							for (var k=0;k<detData.length;k++){
								var container = jQuery('.thumb').filter('[occID='+detData[k].occurrence_id.toString()+']');
								if(container.length > 0){
									if(container.data('determination')===false) {
										container.data('determination',detData[k]);
										if(container.data('image')!==false){
											// image already loaded, but will have been flagged as unknown.
											var img = new Image();
											var src = '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."thumb-'+container.data('image').path;
											var background = '';
											switch(detData[k].determination_type){
												case 'B':
  												case 'I':
  												case 'U':
													background=src;
													src = '".drupal_get_path('module', 'iform')."/client_helpers/prebuilt_forms/images/boundary-doubtful.png';
												case 'A':
												case 'C':
												case 'R':
													container.empty();  // loading class already removed.
													img = jQuery(img).attr('src', src).attr('width', container.width()).attr('height', container.height()).addClass('thumb-image').appendTo(container);
													if(background!='') img.css('background', 'url('+background+')').css('background-size','100% 100%');
												case 'X': // unidentified, leave as is.
													break;
											}
										}
									}
								}
							}
  					}});
					$.getJSON(\"".$svcUrl."/data/occurrence_image\" +
							\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   							\"&orderby=id&sortdir=DESC&callback=?&query=\"+escape(escape(JSON.stringify({'in': {'occurrence_id': insectIDs}}))), function(imageData) {
						if(!(imageData instanceof Array)){
   							alertIndiciaError(imageData);
   						} else if (imageData.length>0) { // thumbs are fixed in size, and small - dont worry about ratios
   							for (var j=0;j<imageData.length;j++){
								var container = jQuery('.thumb').filter('[occID='+imageData[j].occurrence_id.toString()+']');
								if(container.length > 0){
									if(container.data('image')===false){
										container.empty().removeClass('loading').data('image',imageData[j]);
										var img = new Image();
										var background = '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."thumb-'+imageData[j].path;
  										var src = '".drupal_get_path('module', 'iform')."/client_helpers/prebuilt_forms/images/boundary-unknown.png';
  										if(container.data('determination')!==false){
											switch(container.data('determination').determination_type){
												case 'B':
  												case 'I':
  												case 'U':
													src = '".drupal_get_path('module', 'iform')."/client_helpers/prebuilt_forms/images/boundary-doubtful.png';
													break;
												case 'A':
												case 'C':
												case 'R':
													src=background;
													background='';
													break;
												case 'X': // unidentified, leave as is.
													break;
											}
										}
										container.empty();  // loading class already removed.
										img = jQuery(img).attr('src', src).attr('width', container.width()).attr('height', container.height()).addClass('thumb-image').appendTo(container);
										if(background!='') img.css('background', 'url('+background+')').css('background-size','100% 100%');
									}
								}
			    			}}})
			      }; 
			}});
		}});
	$.getJSON(\"".$svcUrl."/data/determination\" + 
			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
			\"&reset_timeout=true&deleted=f&orderby=id&sortdir=DESC&deleted=f&callback=?&occurrence_id=\"+attributes.flower_id,
		function(detData) {
			if(!(detData instanceof Array)){
				alertIndiciaError(detData);
			} else {
				if (detData.length>0) {
					for(var i=0; i< detData.length; i++){
						var determination = jQuery('.collection-flower-determination').filter('[occID='+detData[i].occurrence_id+']').filter('.empty');
						if(determination.length > 0){
							var string = '';
							if(detData[i].taxon != '' && detData[i].taxon != null){
								string = htmlspecialchars(detData[i].taxon);
							}
							if(detData[i].taxa_taxon_list_id_list != '' && detData[i].taxa_taxon_list_id_list != null && detData[i].taxa_taxon_list_id_list != '{}'){
								var resultsIDs = detData[i].taxa_taxon_list_id_list.substring(1, detData[i].taxa_taxon_list_id_list.length - 1).split(',');
								if(resultsIDs[0] != '') {
									for(var j=0; j < resultsIDs.length; j++){
										for(var k = 0; k< flowerTaxa.length; k++){
											if(flowerTaxa[k].id == resultsIDs[j]){
												string = (string == '' ? '' : string + ', ') + htmlspecialchars(flowerTaxa[k].taxon);
												break;
							}}}}}
							if(detData[i].taxon_extra_info != '' && detData[i].taxon_extra_info != null && detData[i].taxon_extra_info != 'null'){
								string = (string == '' ? '' : string + ' ') + '('+htmlspecialchars(detData[i].taxon_extra_info)+')';
							}
							if(detData[i].determination_type == 'B' || detData[i].determination_type == 'I' || detData[i].determination_type == 'U'){
								string='<span class=\"flower-dubious\"><img src=\"".$base.drupal_get_path('module', 'iform')."/client_helpers/prebuilt_forms/images/occ_doubtful.png\" style=\"vertical-align: middle;\"></span>'+string;
							} else if(detData[i].determination_type == 'C'){
								string=string+'<span class=\"flower-valid\"><img src=\"/misc/watchdog-ok.png\" style=\"vertical-align: middle;\"></span>';
							} else string=string+'<span class=\"flower-ok\"></span>';
							determination.empty().removeClass('empty').append('<p>'+string+'</p>');
				}}}
	}});
};
addInsect = function(index, attributes, endOfRow, first){
	var container1=jQuery('<div class=\"filter-insect-container\" />').attr('occID',attributes.insect_id).attr('index', index).appendTo('#results-insects-results');
	if(endOfRow) container1.addClass('end-of-row');
	var container=jQuery('<div class=\"ui-widget-content ui-corner-all filter-insect\" />').appendTo(container1);
	if(typeof attributes.deleted != 'undefined' && attributes.deleted==true) {
		container1.css('background-color','#FF0000');
		jQuery('<div class=\"insect-determinationX empty\" />').attr('occID',attributes.insect_id).appendTo(container).append(\"<p>".lang::get('LANG_No_Determinations')."</p>\");
		return;
	}
	jQuery(\"<div class='determination-flag centre-flag occurrence-unknown'/>\").appendTo(container); // flag
	var insect = jQuery('<div class=\"insect-image empty\" />').data('occID',attributes.insect_id).data('insectIndex',index).click(function(){
		loadInsect(jQuery(this).data('occID'), null, jQuery(this).data('insectIndex'), 'S');
	}).appendTo(container);
	insect.height(insect.width()/(".$args['Insect_Image_Ratio']."));
	jQuery('<div class=\"insect-determinationX empty\" />').attr('occID',attributes.insect_id).appendTo(container).append(\"<p>".lang::get('LANG_No_Determinations')."</p>\");
	insertImage('med-', attributes.image_d_insecte, insect, ".$args['Insect_Image_Ratio'].", false, false);
	jQuery('<div class=\"ui-state-default ui-corner-all display-button\">".lang::get('LANG_Display')."</div>').click(function(){
		loadInsect(jQuery(this).attr('occID'), null, jQuery(this).data('insectIndex'), 'S');
	}).appendTo(container).attr('occID',attributes.insect_id).data('insectIndex',index);
};
addInsectDeterminations = function(insects){
	$.getJSON(\"".$svcUrl."/data/determination\" + 
			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
			\"&reset_timeout=true&orderby=id&sortdir=DESC&deleted=f&callback=?&query=\"+escape(escape(JSON.stringify({'in': {'occurrence_id': insects}}))),
		function(detData) {
			if(!(detData instanceof Array)){
				alertIndiciaError(detData);
			} else {
				if (detData.length>0) {
					for(var i=0; i< detData.length; i++){
						var determination = jQuery('.insect-determinationX').filter('[occID='+detData[i].occurrence_id+']').filter('.empty');
						if(determination.length > 0){
							var string = '';
							if(detData[i].taxon != '' && detData[i].taxon != null){
								string = htmlspecialchars(detData[i].taxon);
							}
							if(detData[i].taxa_taxon_list_id_list != '' && detData[i].taxa_taxon_list_id_list != null && detData[i].taxa_taxon_list_id_list != '{}'){
								var resultsIDs = detData[i].taxa_taxon_list_id_list.substring(1, detData[i].taxa_taxon_list_id_list.length - 1).split(',');
								if(resultsIDs[0] != '') {
									for(var j=0; j < resultsIDs.length; j++){
										for(var k = 0; k< insectTaxa.length; k++){
											if(insectTaxa[k].id == resultsIDs[j]){
												string = (string == '' ? '' : string + ', ') + htmlspecialchars(insectTaxa[k].taxon);
												break;
							}}}}}
							if(detData[i].taxon_extra_info != '' && detData[i].taxon_extra_info != null && detData[i].taxon_extra_info != 'null'){
								string = (string == '' ? '' : string + ' ') + '('+htmlspecialchars(detData[i].taxon_extra_info)+')';
							}
							determination.empty().removeClass('empty').append('<p>'+string+'</p>');
							var flag = determination.parent().find('.occurrence-unknown').removeClass('occurrence-unknown');
							if(detData[i].determination_type == 'B' || detData[i].determination_type == 'I' || detData[i].determination_type == 'U'){
								flag.addClass('occurrence-dubious');
							} else if(detData[i].determination_type == 'C'){
								flag.addClass('occurrence-valid');
							} else 
								flag.addClass('insect-ok');
				}}}
				var group = jQuery('#results-insects-results').find('.filter-insect');
				var tallest = 0;
				group.each(function(){ tallest = Math.max($(this).height(), tallest); });
				group.each(function(){ $(this).height(Math.max($(this).height(), tallest)); }); // have synchronicity problems.
	}});
};

setCollectionPage = function(pageNum){
	if(bulkValidating) return; //prevent query changing underneath bulk validation
	jQuery('#results-collections-results,#validate-page-message,#validate-taxon-message,#validate-collection-message').empty();
	var no_units = 4;
	var no_tens = 2;
	var no_hundreds = 1;
	if(searchResults.features.length >= ".$args['max_features']."){
		jQuery(\"<span class='features-warning'>".lang::get('LANG_Max_Collections_Reached')."</span>\").appendTo('#results-collections-results');
	}
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
	if(pageNum==numPages)
		jQuery('#results-validate-taxon-outer').show();
	else
		jQuery('#results-validate-taxon-outer').hide();
}
setInsectPage = function(pageNum){
	if(bulkValidating) return; //prevent query changing underneath bulk validation
	jQuery('#results-insects-results,#validate-page-message,#validate-taxon-message,#validate-collection-message').empty();
	if(searchResults.features.length >= ".$args['max_features']."){
		jQuery(\"<span class='features-warning'>".lang::get('LANG_Max_Insects_Reached')."</span>\").appendTo('#results-insects-results');
	}
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
	var insectIDs=[];
    for (var i = (pageNum-1)*".$args['insectsRowsPerPage']."*".$args['insectsPerRow'].", first = true; i < searchResults.features.length && i < pageNum*".$args['insectsRowsPerPage']."*".$args['insectsPerRow']."; i++, first = false){
		addInsect(i, searchResults.features[i].attributes, (i+1)/".$args['insectsPerRow']." == parseInt((i+1)/".$args['insectsPerRow']."), first);
		insectIDs.push(searchResults.features[i].attributes.insect_id);
	}
	addInsectDeterminations(insectIDs);
	if(numPages > 1) {
		itemList.clone(true).appendTo('#results-insects-results');
	}
	if(pageNum==numPages)
		jQuery('#results-validate-taxon-outer').show();
	else
		jQuery('#results-validate-taxon-outer').hide();
}

// searchLayer in map is used for georeferencing.
// map editLayer is switched off. TODO: need to switch off click control
// editlayer left in map2: replaces locationLayer
searchResultsLayer = null;
inseeLayer = null;
polygonLayer = new OpenLayers.Layer.Vector('Polygon Layer', {
	styleMap: new OpenLayers.StyleMap({
                \"default\": new OpenLayers.Style({
                    fillColor: \"".$args['Polygon_Colour']."\",
                    strokeColor: \"".$args['Polygon_Colour']."\",
                    fillOpacity: ".$args['Polygon_Opacity'].",
                    strokeWidth: 1
                  })
	}),
	displayInLayerSwitcher: false
});
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
// we have a problem if the ID tool broadcasts that it is outputting in 8859, but actually does utf-8
// doff cap to php.js
function utf8_decode (str_data) {
    var tmp_arr = [],
        i = 0,
        ac = 0,
        c1 = 0,
        c2 = 0,
        c3 = 0;
 
    str_data += '';
 
    while (i < str_data.length) {        c1 = str_data.charCodeAt(i);
        if (c1 < 128) {
            tmp_arr[ac++] = String.fromCharCode(c1);
            i++;
        } else if (c1 > 191 && c1 < 224) {            c2 = str_data.charCodeAt(i + 1);
            tmp_arr[ac++] = String.fromCharCode(((c1 & 31) << 6) | (c2 & 63));
            i += 2;
        } else {
            c2 = str_data.charCodeAt(i + 1);            c3 = str_data.charCodeAt(i + 2);
            tmp_arr[ac++] = String.fromCharCode(((c1 & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
            i += 3;
        }
    } 
    return tmp_arr.join('');
}
toolPoller = function(toolStruct){
	if(toolStruct.pollFile == '') return;
	toolStruct.pollTimer = setTimeout('toolPoller('+toolStruct.name+');', ".$args['ID_tool_poll_interval'].");
	jQuery.ajax({
	 url: toolStruct.pollURL+toolStruct.pollFile,
	 toolStruct: toolStruct,
	 success: function(data){
	  pollReset(this.toolStruct);
".(isset($args['ID_tool_convert_strings']) && $args['ID_tool_convert_strings'] ? "	  data=utf8_decode(data);\n" : "" )."	  var da = data.split('\\n');
      jQuery(this.toolStruct.mainForm+' [name=determination\\:taxon_details]').val(da[2]); // Stores the state of identification, which details how the identification was arrived at within the tool.
	  da[1] = da[1].replace(/\\\\i\{\}/g, '').replace(/\\\\i0\{\}/g, '').replace(/\\\\/g, '');
	  var items = da[1].split(':');
	  var count = items.length;
	  if(items[count-1] == '') count--;
	  if(items[count-1] == '') count--;	  
	  if(count <= 0){
	  	// no valid stuff so blank it all out.
	  	jQuery('#'+this.toolStruct.type+'_taxa_list').append(\"".lang::get('LANG_Taxa_Unknown_In_Tool')."\");
	  	jQuery(this.toolStruct.mainForm+' [name=determination\\:determination_type]').val('X'); // Unidentified.
		jQuery('#'+this.toolStruct.type+'-id-button').data('toolRetValues', []);
      } else {
      	var resultsIDs = [];
      	var resultsText = \"".lang::get('LANG_Taxa_Returned')."<br />{ \";
      	var notFound = '';
		for(var j=0; j < count; j++){
			var found = false;
			for(i = 0; i< this.toolStruct.taxaList.length; i++){
				if(this.toolStruct.taxaList[i].taxon == items[j]){
					resultsIDs.push(this.toolStruct.taxaList[i].id);
					resultsText = resultsText + (j == 0 ? '' : '<br />&nbsp;&nbsp;') + htmlspecialchars(items[j]);
					found = true;
					break;
  				}
  			};
  			if(!found){
  				notFound = (notFound == '' ? '' : notFound + ', ') + items[j]; // going into a input field - no special characterisation required.
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

// an expert can set the determination type to 'X' manually, so reset everything in this case.
expertSetUnidentified = function(toolStruct){
	jQuery('#'+toolStruct.type+'-id-button').data('toolRetValues', []);
	jQuery(toolStruct.mainForm+' [name=determination\\:taxon_details]').val('');
	jQuery('#'+toolStruct.type+'_taxa_list').empty();
	jQuery(toolStruct.mainForm+' [name=determination\\:taxa_taxon_list_id]').val('');
	jQuery(toolStruct.mainForm+' [name=determination\\:comment]').val(\"".lang::get('LANG_Default_ID_Comment')."\");
  };
jQuery('#fo-flower-expert-det-type').change(function(){
	if(jQuery(this).val()=='X') expertSetUnidentified(flowerIDstruc);
});
jQuery('#fo-insect-expert-det-type').change(function(){
	if(jQuery(this).val()=='X') expertSetUnidentified(insectIDstruc);
});

jQuery('#flower-id-cancel').hide();

taxonChosen = function(toolStruct){
	jQuery('#'+toolStruct.type+'-id-button').data('toolRetValues', []);
	jQuery(toolStruct.mainForm+' [name=determination\\:taxon_details]').val('');
	jQuery('#'+toolStruct.type+'_taxa_list').empty();
	jQuery(toolStruct.mainForm+' [name=determination\\:comment]').val('');
	jQuery(toolStruct.mainForm+' [name=determination\\:taxon_extra_info]').val('');
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
$('#imp-insee-close-btn').click(function(e) {
  $('#imp-insee-div').hide();
  if(inseeLayer != null){
		inseeLayer.destroyFeatures();
  }
  inseeLayerStore.destroyFeatures();
  e.preventDefault();
});
var defaultDiacriticsRemovalMap = [
    {'base':'A', 'letters':/[\u0041\u24B6\uFF21\u00C0\u00C1\u00C2\u1EA6\u1EA4\u1EAA\u1EA8\u00C3\u0100\u0102\u1EB0\u1EAE\u1EB4\u1EB2\u0226\u01E0\u00C4\u01DE\u1EA2\u00C5\u01FA\u01CD\u0200\u0202\u1EA0\u1EAC\u1EB6\u1E00\u0104\u023A\u2C6F]/g},
    {'base':'AA','letters':/[\uA732]/g},
    {'base':'AE','letters':/[\u00C6\u01FC\u01E2]/g},
    {'base':'AO','letters':/[\uA734]/g},
    {'base':'AU','letters':/[\uA736]/g},
    {'base':'AV','letters':/[\uA738\uA73A]/g},
    {'base':'AY','letters':/[\uA73C]/g},
    {'base':'B', 'letters':/[\u0042\u24B7\uFF22\u1E02\u1E04\u1E06\u0243\u0182\u0181]/g},
    {'base':'C', 'letters':/[\u0043\u24B8\uFF23\u0106\u0108\u010A\u010C\u00C7\u1E08\u0187\u023B\uA73E]/g},
    {'base':'D', 'letters':/[\u0044\u24B9\uFF24\u1E0A\u010E\u1E0C\u1E10\u1E12\u1E0E\u0110\u018B\u018A\u0189\uA779]/g},
    {'base':'DZ','letters':/[\u01F1\u01C4]/g},
    {'base':'Dz','letters':/[\u01F2\u01C5]/g},
    {'base':'E', 'letters':/[\u0045\u24BA\uFF25\u00C8\u00C9\u00CA\u1EC0\u1EBE\u1EC4\u1EC2\u1EBC\u0112\u1E14\u1E16\u0114\u0116\u00CB\u1EBA\u011A\u0204\u0206\u1EB8\u1EC6\u0228\u1E1C\u0118\u1E18\u1E1A\u0190\u018E]/g},
    {'base':'F', 'letters':/[\u0046\u24BB\uFF26\u1E1E\u0191\uA77B]/g},
    {'base':'G', 'letters':/[\u0047\u24BC\uFF27\u01F4\u011C\u1E20\u011E\u0120\u01E6\u0122\u01E4\u0193\uA7A0\uA77D\uA77E]/g},
    {'base':'H', 'letters':/[\u0048\u24BD\uFF28\u0124\u1E22\u1E26\u021E\u1E24\u1E28\u1E2A\u0126\u2C67\u2C75\uA78D]/g},
    {'base':'I', 'letters':/[\u0049\u24BE\uFF29\u00CC\u00CD\u00CE\u0128\u012A\u012C\u0130\u00CF\u1E2E\u1EC8\u01CF\u0208\u020A\u1ECA\u012E\u1E2C\u0197]/g},
    {'base':'J', 'letters':/[\u004A\u24BF\uFF2A\u0134\u0248]/g},
    {'base':'K', 'letters':/[\u004B\u24C0\uFF2B\u1E30\u01E8\u1E32\u0136\u1E34\u0198\u2C69\uA740\uA742\uA744\uA7A2]/g},
    {'base':'L', 'letters':/[\u004C\u24C1\uFF2C\u013F\u0139\u013D\u1E36\u1E38\u013B\u1E3C\u1E3A\u0141\u023D\u2C62\u2C60\uA748\uA746\uA780]/g},
    {'base':'LJ','letters':/[\u01C7]/g},
    {'base':'Lj','letters':/[\u01C8]/g},
    {'base':'M', 'letters':/[\u004D\u24C2\uFF2D\u1E3E\u1E40\u1E42\u2C6E\u019C]/g},
    {'base':'N', 'letters':/[\u004E\u24C3\uFF2E\u01F8\u0143\u00D1\u1E44\u0147\u1E46\u0145\u1E4A\u1E48\u0220\u019D\uA790\uA7A4]/g},
    {'base':'NJ','letters':/[\u01CA]/g},
    {'base':'Nj','letters':/[\u01CB]/g},
    {'base':'O', 'letters':/[\u004F\u24C4\uFF2F\u00D2\u00D3\u00D4\u1ED2\u1ED0\u1ED6\u1ED4\u00D5\u1E4C\u022C\u1E4E\u014C\u1E50\u1E52\u014E\u022E\u0230\u00D6\u022A\u1ECE\u0150\u01D1\u020C\u020E\u01A0\u1EDC\u1EDA\u1EE0\u1EDE\u1EE2\u1ECC\u1ED8\u01EA\u01EC\u00D8\u01FE\u0186\u019F\uA74A\uA74C]/g},
    {'base':'OI','letters':/[\u01A2]/g},
    {'base':'OO','letters':/[\uA74E]/g},
    {'base':'OU','letters':/[\u0222]/g},
    {'base':'P', 'letters':/[\u0050\u24C5\uFF30\u1E54\u1E56\u01A4\u2C63\uA750\uA752\uA754]/g},
    {'base':'Q', 'letters':/[\u0051\u24C6\uFF31\uA756\uA758\u024A]/g},
    {'base':'R', 'letters':/[\u0052\u24C7\uFF32\u0154\u1E58\u0158\u0210\u0212\u1E5A\u1E5C\u0156\u1E5E\u024C\u2C64\uA75A\uA7A6\uA782]/g},
    {'base':'S', 'letters':/[\u0053\u24C8\uFF33\u1E9E\u015A\u1E64\u015C\u1E60\u0160\u1E66\u1E62\u1E68\u0218\u015E\u2C7E\uA7A8\uA784]/g},
    {'base':'T', 'letters':/[\u0054\u24C9\uFF34\u1E6A\u0164\u1E6C\u021A\u0162\u1E70\u1E6E\u0166\u01AC\u01AE\u023E\uA786]/g},
    {'base':'TZ','letters':/[\uA728]/g},
    {'base':'U', 'letters':/[\u0055\u24CA\uFF35\u00D9\u00DA\u00DB\u0168\u1E78\u016A\u1E7A\u016C\u00DC\u01DB\u01D7\u01D5\u01D9\u1EE6\u016E\u0170\u01D3\u0214\u0216\u01AF\u1EEA\u1EE8\u1EEE\u1EEC\u1EF0\u1EE4\u1E72\u0172\u1E76\u1E74\u0244]/g},
    {'base':'V', 'letters':/[\u0056\u24CB\uFF36\u1E7C\u1E7E\u01B2\uA75E\u0245]/g},
    {'base':'VY','letters':/[\uA760]/g},
    {'base':'W', 'letters':/[\u0057\u24CC\uFF37\u1E80\u1E82\u0174\u1E86\u1E84\u1E88\u2C72]/g},
    {'base':'X', 'letters':/[\u0058\u24CD\uFF38\u1E8A\u1E8C]/g},
    {'base':'Y', 'letters':/[\u0059\u24CE\uFF39\u1EF2\u00DD\u0176\u1EF8\u0232\u1E8E\u0178\u1EF6\u1EF4\u01B3\u024E\u1EFE]/g},
    {'base':'Z', 'letters':/[\u005A\u24CF\uFF3A\u0179\u1E90\u017B\u017D\u1E92\u1E94\u01B5\u0224\u2C7F\u2C6B\uA762]/g},
    {'base':'a', 'letters':/[\u0061\u24D0\uFF41\u1E9A\u00E0\u00E1\u00E2\u1EA7\u1EA5\u1EAB\u1EA9\u00E3\u0101\u0103\u1EB1\u1EAF\u1EB5\u1EB3\u0227\u01E1\u00E4\u01DF\u1EA3\u00E5\u01FB\u01CE\u0201\u0203\u1EA1\u1EAD\u1EB7\u1E01\u0105\u2C65\u0250]/g},
    {'base':'aa','letters':/[\uA733]/g},
    {'base':'ae','letters':/[\u00E6\u01FD\u01E3]/g},
    {'base':'ao','letters':/[\uA735]/g},
    {'base':'au','letters':/[\uA737]/g},
    {'base':'av','letters':/[\uA739\uA73B]/g},
    {'base':'ay','letters':/[\uA73D]/g},
    {'base':'b', 'letters':/[\u0062\u24D1\uFF42\u1E03\u1E05\u1E07\u0180\u0183\u0253]/g},
    {'base':'c', 'letters':/[\u0063\u24D2\uFF43\u0107\u0109\u010B\u010D\u00E7\u1E09\u0188\u023C\uA73F\u2184]/g},
    {'base':'d', 'letters':/[\u0064\u24D3\uFF44\u1E0B\u010F\u1E0D\u1E11\u1E13\u1E0F\u0111\u018C\u0256\u0257\uA77A]/g},
    {'base':'dz','letters':/[\u01F3\u01C6]/g},
    {'base':'e', 'letters':/[\u0065\u24D4\uFF45\u00E8\u00E9\u00EA\u1EC1\u1EBF\u1EC5\u1EC3\u1EBD\u0113\u1E15\u1E17\u0115\u0117\u00EB\u1EBB\u011B\u0205\u0207\u1EB9\u1EC7\u0229\u1E1D\u0119\u1E19\u1E1B\u0247\u025B\u01DD]/g},
    {'base':'f', 'letters':/[\u0066\u24D5\uFF46\u1E1F\u0192\uA77C]/g},
    {'base':'g', 'letters':/[\u0067\u24D6\uFF47\u01F5\u011D\u1E21\u011F\u0121\u01E7\u0123\u01E5\u0260\uA7A1\u1D79\uA77F]/g},
    {'base':'h', 'letters':/[\u0068\u24D7\uFF48\u0125\u1E23\u1E27\u021F\u1E25\u1E29\u1E2B\u1E96\u0127\u2C68\u2C76\u0265]/g},
    {'base':'hv','letters':/[\u0195]/g},
    {'base':'i', 'letters':/[\u0069\u24D8\uFF49\u00EC\u00ED\u00EE\u0129\u012B\u012D\u00EF\u1E2F\u1EC9\u01D0\u0209\u020B\u1ECB\u012F\u1E2D\u0268\u0131]/g},
    {'base':'j', 'letters':/[\u006A\u24D9\uFF4A\u0135\u01F0\u0249]/g},
    {'base':'k', 'letters':/[\u006B\u24DA\uFF4B\u1E31\u01E9\u1E33\u0137\u1E35\u0199\u2C6A\uA741\uA743\uA745\uA7A3]/g},
    {'base':'l', 'letters':/[\u006C\u24DB\uFF4C\u0140\u013A\u013E\u1E37\u1E39\u013C\u1E3D\u1E3B\u017F\u0142\u019A\u026B\u2C61\uA749\uA781\uA747]/g},
    {'base':'lj','letters':/[\u01C9]/g},
    {'base':'m', 'letters':/[\u006D\u24DC\uFF4D\u1E3F\u1E41\u1E43\u0271\u026F]/g},
    {'base':'n', 'letters':/[\u006E\u24DD\uFF4E\u01F9\u0144\u00F1\u1E45\u0148\u1E47\u0146\u1E4B\u1E49\u019E\u0272\u0149\uA791\uA7A5]/g},
    {'base':'nj','letters':/[\u01CC]/g},
    {'base':'o', 'letters':/[\u006F\u24DE\uFF4F\u00F2\u00F3\u00F4\u1ED3\u1ED1\u1ED7\u1ED5\u00F5\u1E4D\u022D\u1E4F\u014D\u1E51\u1E53\u014F\u022F\u0231\u00F6\u022B\u1ECF\u0151\u01D2\u020D\u020F\u01A1\u1EDD\u1EDB\u1EE1\u1EDF\u1EE3\u1ECD\u1ED9\u01EB\u01ED\u00F8\u01FF\u0254\uA74B\uA74D\u0275]/g},
    {'base':'oi','letters':/[\u01A3]/g},
    {'base':'ou','letters':/[\u0223]/g},
    {'base':'oo','letters':/[\uA74F]/g},
    {'base':'p','letters':/[\u0070\u24DF\uFF50\u1E55\u1E57\u01A5\u1D7D\uA751\uA753\uA755]/g},
    {'base':'q','letters':/[\u0071\u24E0\uFF51\u024B\uA757\uA759]/g},
    {'base':'r','letters':/[\u0072\u24E1\uFF52\u0155\u1E59\u0159\u0211\u0213\u1E5B\u1E5D\u0157\u1E5F\u024D\u027D\uA75B\uA7A7\uA783]/g},
    {'base':'s','letters':/[\u0073\u24E2\uFF53\u00DF\u015B\u1E65\u015D\u1E61\u0161\u1E67\u1E63\u1E69\u0219\u015F\u023F\uA7A9\uA785\u1E9B]/g},
    {'base':'t','letters':/[\u0074\u24E3\uFF54\u1E6B\u1E97\u0165\u1E6D\u021B\u0163\u1E71\u1E6F\u0167\u01AD\u0288\u2C66\uA787]/g},
    {'base':'tz','letters':/[\uA729]/g},
    {'base':'u','letters':/[\u0075\u24E4\uFF55\u00F9\u00FA\u00FB\u0169\u1E79\u016B\u1E7B\u016D\u00FC\u01DC\u01D8\u01D6\u01DA\u1EE7\u016F\u0171\u01D4\u0215\u0217\u01B0\u1EEB\u1EE9\u1EEF\u1EED\u1EF1\u1EE5\u1E73\u0173\u1E77\u1E75\u0289]/g},
    {'base':'v','letters':/[\u0076\u24E5\uFF56\u1E7D\u1E7F\u028B\uA75F\u028C]/g},
    {'base':'vy','letters':/[\uA761]/g},
    {'base':'w','letters':/[\u0077\u24E6\uFF57\u1E81\u1E83\u0175\u1E87\u1E85\u1E98\u1E89\u2C73]/g},
    {'base':'x','letters':/[\u0078\u24E7\uFF58\u1E8B\u1E8D]/g},
    {'base':'y','letters':/[\u0079\u24E8\uFF59\u1EF3\u00FD\u0177\u1EF9\u0233\u1E8F\u00FF\u1EF7\u1E99\u1EF5\u01B4\u024F\u1EFF]/g},
    {'base':'z','letters':/[\u007A\u24E9\uFF5A\u017A\u1E91\u017C\u017E\u1E93\u1E95\u01B6\u0225\u0240\u2C6C\uA763]/g}
];
function removeDiacritics (str) {
    var changes = defaultDiacriticsRemovalMap;
    for(var i=0; i<changes.length; i++) {
        str = str.replace(changes[i].letters, changes[i].base);
    }
    return str;
}
inseeLayerStore = new OpenLayers.Layer.Vector('INSEE Layer Store', {displayInLayerSwitcher: false});
jQuery('#search-insee-button').click(function(){
	if(inseeLayer != null){
		inseeLayer.destroyFeatures();
		inseeLayer.destroy();
	}
	inseeLayerStore.destroyFeatures();
	jQuery('#imp-insee-div').hide();
	polygonLayer.map.searchLayer.destroyFeatures();
	polygonLayer.destroyFeatures();
	var filters = [];
	var myGeometryName;
	var myFeatureType;
	var myDisplayField;
	var myExtraDataField;
	var myINSEELookUpMaxFeatures;
	var place = removeDiacritics(jQuery('input[name=place\\:INSEE]').val());
	place = place.toUpperCase();
	while(place!='' && place.substring(0,1)=='*'){place=place.substring(1);}
	while(place!='' && place.substring(place.length-1)=='*'){place=place.substring(0,place.length-1);}
	if(place == \"".lang::get('LANG_Enter_Location')."\") return;
	jQuery('#search-insee-button').addClass('loading-button');
	switch(jQuery('[name=place\\:INSEE_Type]').val()){";
	$searches=explode(';',trim($args['Localisation_spec']));
	for($i=0; $i< count($searches); $i++){
		$parts=explode(':',$searches[$i]);
		data_entry_helper::$javascript .= "case \"".$i."\": myFeatureType=\"".$parts[1]."\";myGeometryName=\"".$parts[2]."\";myDisplayField=\"".$parts[3]."\";myExtraDataField=\"".$parts[4]."\";myINSEELookUpMaxFeatures=\"".$parts[5]."\";mySearchINSEEfeaturesLimit=\"".$parts[6]."\";";
		for($j=7; $j< count($parts); $j=$j+2){
		data_entry_helper::$javascript .= "
	filters.push(new OpenLayers.Filter.Comparison({
		type: ".($parts[$j+1]=='equal' ? 'OpenLayers.Filter.Comparison.EQUAL_TO' : 'OpenLayers.Filter.Comparison.LIKE').",
		property: '".$parts[$j]."',
		value: ".($parts[$j+1]=='*like*'?'"*"+':'')."place".($parts[$j+1]=='equal'?'':'+"*"')."
	}));";
		}
		data_entry_helper::$javascript .= "break;";
	}
	data_entry_helper::$javascript .= "
	}
	var strategy = new OpenLayers.Strategy.Fixed({preload: false, autoActivate: false});
	var styleMap = new OpenLayers.StyleMap({
                \"default\": new OpenLayers.Style({
                    fillColor: \"".$args['Feature_Colour']."\",
                    strokeColor: \"".$args['Feature_Colour']."\",
                    fillOpacity: ".$args['Feature_Opacity'].",
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
              featureType: myFeatureType,
              geometryName: myGeometryName,
              featureNS: '".$args['INSEE_ns']."',
              srsName: 'EPSG:900913',
              version: '1.1.0'                  
              ,maxFeatures: myINSEELookUpMaxFeatures
              ,propertyNames: (myExtraDataField=='' ? [myGeometryName, myDisplayField] : [myGeometryName, myDisplayField, myExtraDataField])
          }),
          filter: new OpenLayers.Filter.Logical({
		      type: OpenLayers.Filter.Logical.OR,
		      filters: filters
		  	  })
    });
    inseeLayer.events.register('featuresadded', {}, function(a1){
		var div = jQuery('#map')[0];
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
		jQuery('#search-insee-button').removeClass('loading-button');
		if(inseeLayer.features.length == 0){
			jQuery('#imp-insee-div').show();
			jQuery('#imp-insee-output-div').empty().append(\"<span>".lang::get('LANG_NO_INSEE')."</span>\");
		} else if(inseeLayer.features.length > 1){
			jQuery('#imp-insee-output-div').empty();
			jQuery('#imp-insee-div').show();
			if(inseeLayer.features.length == myINSEELookUpMaxFeatures)
				jQuery('#imp-insee-output-div').append('<span>'+\"".lang::get('LANG_Max_INSEE_Features')."\".replace('<>',myINSEELookUpMaxFeatures)+'</span> ');
			if(inseeLayer.features.length > mySearchINSEEfeaturesLimit)
				jQuery('#imp-insee-output-div').append('<span>'+\"".lang::get('LANG_INSEE_Search_Limit')."\".replace('<>',mySearchINSEEfeaturesLimit)+'</span>');
			var ol=jQuery('<ol></ol>');
			jQuery('#imp-insee-output-div').append(ol);
			for(var i = 0; i< inseeLayer.features.length; i++){
				var text = inseeLayer.features[i].data[myDisplayField]+(myExtraDataField=='' ? '' : ' ('+inseeLayer.features[i].data[myExtraDataField]+')');
				ol.append(jQuery('<li>').append(jQuery(\"<a href='#'>\" + text + '</a>')
                  .click(function(e) {e.preventDefault();})
                  .click((// use closures to persist the values of feature
                    function(feature){
                      return function() {
                        var removeList = [];
                        for(var i=0; i< inseeLayer.features.length; i++){removeList.push(inseeLayer.features[i]);}
                        if(removeList.length) {
                        	inseeLayer.removeFeatures(removeList);
                        	inseeLayerStore.addFeatures(removeList);
                        }
                        inseeLayerStore.removeFeatures([feature]);
                        inseeLayer.addFeatures([feature]);
                      };
                    })(inseeLayer.features[i]))));
			}
		}
    });
    jQuery('#map')[0].map.addLayer(inseeLayer);
    strategy.load({});
});

jQuery('#search-collections-button').click(function(){
	if(bulkValidating) return; //prevent results changing underneath bulk validation
	jQuery('#results-insects-header,#results-insects-results').hide();
	jQuery('#results-collections-header,#results-collections-results').show();
	jQuery('#results-collections-header').addClass('ui-state-active').removeClass('ui-state-default');
	runSearch(true);
});
jQuery('#search-insects-button').click(function(){
	if(bulkValidating) return; //prevent results changing underneath bulk validation
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
	if(inseeLayer != null && inseeLayer.features.length > 0 && inseeLayer.features.length <= mySearchINSEEfeaturesLimit)
		features = inseeLayer.features;
	else if(polygonLayer.features.length > 0)
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
    var format=new OpenLayers.Format.GML.v2({featureName: 'user', featureType: 'user', featureNS: 'user', featurePrefix: 'user'});
    var features=format.read(string);
	if(inseeLayer != null) inseeLayer.destroyFeatures();
	inseeLayerStore.destroyFeatures();
	jQuery('#imp-insee-div').hide();
	polygonLayer.destroyFeatures();
	var div = jQuery('#map')[0];
	div.map.searchLayer.destroyFeatures();
	polygonLayer.addFeatures(features, {});
	var bounds= polygonLayer.getDataExtent();
    div.map.zoomToExtent(bounds);

}
mySearchINSEEfeaturesLimit=1;
function pad(number, length) {
    var str = '' + number;
    while (str.length < length) {
        str = '0' + str;
    }
    return str;
}
runSearch = function(forCollections){
	if(bulkValidating) return; //prevent query changing underneath bulk validation
  	var ORgroup = [];
  	if(searchResultsLayer != null)
		searchResultsLayer.destroy();
    jQuery('#results-collections-results,#results-insects-results,#validate-page-message,#validate-taxon-message,#validate-collection-message,#collection-insects').empty();
	jQuery('#focus-occurrence,#focus-collection').hide();
	jQuery('#validate-taxon-progress,#validate-page-progress,,#validate-collection-progress,#cancel-validate-page,#cancel-validate-taxon,#cancel-validate-collection').hide();
  	jQuery('#results-validate-taxon,#results-validate-page,#results-validate-taxon-outer').hide();
	var filters = [];

	// By default restrict selection to area displayed on map. When using the georeferencing system the map searchLayer
	// will contain a single point zoomed in appropriately.
	var mapBounds = jQuery('#map')[0].map.getExtent();
	if (mapBounds != null) filters.push(new OpenLayers.Filter.Spatial({type: OpenLayers.Filter.Spatial.BBOX, property: 'geom', value: mapBounds}));
	if(inseeLayer != null){
  		if(inseeLayer.features.length > 0 && inseeLayer.features.length<=mySearchINSEEfeaturesLimit){
			ORgroup = [];
			for(i=0; i< inseeLayer.features.length; i++)
				ORgroup.push(new OpenLayers.Filter.Spatial({type: OpenLayers.Filter.Spatial.WITHIN, property: 'geom', value: inseeLayer.features[i].geometry}));
			if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));}}
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
  	if(flower != '') {
  		var flower_taxon_filter = new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'flower_taxon_ids', value: '*|'+flower+'|*'});
  		if(jQuery('select[name=flower\\:taxa_taxon_list_id] option:selected').text().substring(0,13) == 'Taxon inconnu'){
  			ORgroup = [];
  			ORgroup.push(flower_taxon_filter);
//  			ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.EQUAL_TO, property: 'status_fleur_code', value: 'X'}));
  			filters.push(combineOR(ORgroup));
  		} else {
  			if(forCollections)
				jQuery('#results-validate-taxon,#results-validate-page').show();
  			filters.push(flower_taxon_filter);
  		}
  	}
  	flower = jQuery('[name=flower\\:taxon_extra_info]').val();
  	if(flower != '' && flower != '".lang::get('LANG_More_Precise')."')
  		filters.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'taxons_fleur_precise', value: '*'+flower+'*' }));
	ORgroup = [];
  	jQuery('#flower-filter-body').find('[name=flower_id_status\\[\\]]').filter('[checked]').each(function(index, elem){
  		ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.EQUAL_TO, property: 'status_fleur_code', value: elem.value}));
  	});
  	if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
	ORgroup = [];
  	jQuery('#flower-filter-body').find('[name=flower_id_type\\[\\]]').filter('[checked]').each(function(index, elem){
  		ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.EQUAL_TO, property: 'flower_taxon_type', value: elem.value}));
  	});
  	if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
		
	ORgroup = [];
  	jQuery('#flower-filter-body').find('[name^=occAttr:".$flowerTypeAttrID."]').filter('[checked]').each(function(index, elem){
  		ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.EQUAL_TO, property: 'flower_type_id', value: elem.value}));
  	});
  	if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
  	ORgroup = [];
  	jQuery('#flower-filter-body').find('[name^=locAttr:".$habitatAttrID."]').filter('[checked]').each(function(index, elem){
  		ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'habitat_ids', value: '*|'+elem.value+'|*'}));
  	});
  	if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
  	
	var insect = jQuery('select[name=insect\\:taxa_taxon_list_id]').val();
	var insect_name = jQuery('select[name=insect\\:taxa_taxon_list_id] option:selected').text()
	var insect_statuses = jQuery('[name=insect_id_status\\[\\]]').filter('[checked]');
	var insect_taxon_types = jQuery('[name=insect_id_type\\[\\]]').filter('[checked]');
	var foraging = jQuery('#insect-filter-body').find('[name^=occAttr:".$foragingAttrID."]').filter('[checked]');
	if(forCollections){
		var foundSome = false;
		var queries = [];
//		if(insect_name.substring(0,13) == 'Taxon inconnu'){
//			if((insect_statuses.length == 1 && insect_statuses[0].val() == 'X') || insect_statuses.length == 0){
//				queries.push({status: 'X'});
//				queries.push({taxon: insect});
//			} else {
//				insect_statuses.each(function(index, elem){
//					queries.push({status: elem.value, taxon: insect});
//				});
//			}
//		} else 
		if(insect != ''){
			if(insect_statuses.length > 0){
				insect_statuses.each(function(index, elem){
					queries.push({status: elem.value, taxon: insect});
				});
			} else {
				queries.push({taxon: insect});
			}
		} else if(insect_statuses.length > 0){
			insect_statuses.each(function(index, elem){
				queries.push({status: elem.value});
			});
		}
		if(insect_taxon_types.length>0){
			if(queries.length == 0){
				insect_taxon_types.each(function(index, elem){
					queries.push({type: elem.value});
				});
			} else {
				var newQueries = [];
				for(var i=0; i< insect_taxon_types.length; i++){
					for(var j=0; j< queries.length; j++){
						var newObject = jQuery.extend({}, queries[j]);
						newObject.type = jQuery(insect_taxon_types[i]).val();
						newQueries.push(newObject);
					}
				}
				queries = newQueries;
			}
		}
		if(foraging.length>0){
			if(queries.length == 0){
				for(var i=0; i< foraging.length; i++)
					queries.push({foraging: jQuery(foraging[i]).val()});
			} else {
				var newQueries = [];
				for(var i=0; i< foraging.length; i++){
					for(var j=0; j< queries.length; j++){
						var newObject = jQuery.extend({}, queries[j]);
						newObject.foraging = jQuery(foraging[i]).val();
						newQueries.push(newObject);
					}
				}
				queries = newQueries;
			}
		}
		if(queries.length > 0){
			ORgroup = []
			for(var j=0; j< queries.length; j++){
				var query='*|'+
					(typeof queries[j].status == 'undefined' ? '.' : queries[j].status) + ':' +
					(typeof queries[j].type == 'undefined' ? '.' : queries[j].type.charAt(0).toUpperCase()) + ':' +
					(typeof queries[j].foraging == 'undefined' ? '.' : queries[j].foraging) + ':' +
					(typeof queries[j].taxon == 'undefined' ? '.....' : pad(queries[j].taxon,5)) + '|*';
				ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'insect_search', value: query}));
			}
			filters.push(combineOR(ORgroup));
		}
	} else {
		if(insect != '') {
			var insect_taxon_filter = new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'insect_taxon_ids', value: '*|'+insect+'|*'});
			if(insect_name.substring(0,13) == 'Taxon inconnu'){
				ORgroup = [];
				ORgroup.push(insect_taxon_filter);
//				ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'status_insecte_code', value: '*X*'}));
				filters.push(combineOR(ORgroup));
			} else {
				jQuery('#results-validate-taxon,#results-validate-page').show();
				filters.push(insect_taxon_filter);
			}
		}
		ORgroup = [];
		insect_statuses.each(function(index, elem){
			ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.EQUAL_TO, property: 'status_insecte_code', value: elem.value}));
		});
		if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
		ORgroup = [];
		insect_taxon_types.each(function(index, elem){
			ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.EQUAL_TO, property: 'insect_taxon_type', value: elem.value}));
		});
		if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
		ORgroup = []
		foraging.each(function(index, elem){
			ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.EQUAL_TO, property: 'notonaflower_id', value: elem.value}));
		});
		if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
	}
	insect = jQuery('[name=insect\\:taxon_extra_info]').val();
	if(insect != '' && insect != '".lang::get('LANG_More_Precise')."')
		filters.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'taxons_insecte_precise', value: '*'+insect+'*'}));
	
  	ORgroup = [];
  	jQuery('#conditions-filter-body').find('[name^=smpAttr:".$skyAttrID."]').filter('[checked]').each(function(index, elem){
  		ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'sky_ids', value: '*|'+elem.value+'|*'}));
  	});
  	if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
  	
  	ORgroup = [];
  	jQuery('#conditions-filter-body').find('[name^=smpAttr:".$temperatureAttrID."]').filter('[checked]').each(function(index, elem){
  		ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'temp_ids', value: '*|'+elem.value+'|*'}));
  	});
  	if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
  	
  	ORgroup = [];
  	jQuery('#conditions-filter-body').find('[name^=smpAttr:".$windAttrID."]').filter('[checked]').each(function(index, elem){
  		ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'wind_ids', value: '*|'+elem.value+'|*'}));
  	});
  	if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
  	
  	ORgroup = [];
  	jQuery('#conditions-filter-body').find('[name^=smpAttr:".$shadeAttrID."]').filter('[checked]').each(function(index, elem){
  		ORgroup.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'shade_ids', value: '*|'+elem.value+'|*'}));
  	});
  	if(ORgroup.length >= 1) filters.push(combineOR(ORgroup));
  	if(forCollections){
		properties = ['collection_id','datedebut_txt','datefin_txt','geom','nom','image_de_environment','image_de_la_fleur','flower_id']
		feature = '".$args['search_collections_layer']."';
  	} else {
  		feature = '".$args['search_insects_layer']."';
  		properties = ['insect_id','collection_id','geom','image_d_insecte'];
  	}
  	
    var style = new OpenLayers.Style({
                    pointRadius: '\${radius}',
                    fillColor: '#ffcc66',
                    fillOpacity: 0.8,
                    strokeColor: '#cc6633',
                    strokeWidth: 2,
                    strokeOpacity: 1
       }, {context: {radius: function(feature) {
                     if(feature.attributes.count>=10) return 10;
                     if(feature.attributes.count>=5) return 8;
                     if(feature.attributes.count>1) return 6;
                     return 4;}}
    });
    var styleMap = new OpenLayers.StyleMap({\"default\": style});
	searchResults = null;  
	var strategy = new OpenLayers.Strategy.Fixed({preload: false, autoActivate: false});
	clusterStrategy = new OpenLayers.Strategy.Cluster();
	searchResultsLayer = new OpenLayers.Layer.Vector('Search Layer', {
		  styleMap: styleMap,
	      strategies: [strategy, clusterStrategy],
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
		  }),
		  filter: new OpenLayers.Filter.Logical({type: OpenLayers.Filter.Logical.AND, filters: filters})
	});
	if(forCollections) {
		jQuery('#results-collections-results').empty().append('<div class=\"collection-loading-panel\" ><img src=\"".$base.drupal_get_path('module', 'iform')."/media/images/ajax-loader2.gif\" />".lang::get('loading')."...</div>');
		searchResultsLayer.events.register('featuresadded', {}, function(a1){
			searchResultsLayer.events.remove('featuresadded');
			searchResults = clusterStrategy;
			searchResults.type = 'C';
			setCollectionPage(1);
		});
		searchResultsLayer.events.register('loadend', {}, function(){
			if(searchResultsLayer.features.length == 0){
				jQuery('#results-validate-taxon,#results-validate-page').hide();
				jQuery('#results-collections-results').empty().text('".lang::get('LANG_No_Collection_Results')."');
			}
		});
	} else {
		jQuery('#results-insects-results').empty().append('<div class=\"insect-loading-panel\" ><img src=\"".$base.drupal_get_path('module', 'iform')."/media/images/ajax-loader2.gif\" />".lang::get('loading')."...</div>');
		searchResultsLayer.events.register('featuresadded', {}, function(a1){
			searchResultsLayer.events.remove('featuresadded');
			searchResults = clusterStrategy;
			searchResults.type = 'I';
			setInsectPage(1);
		});
		searchResultsLayer.events.register('loadend', {}, function(){
			if(searchResultsLayer.features.length == 0){
				jQuery('#results-validate-taxon,#results-validate-page').hide();
				jQuery('#results-insects-results').empty().text('".lang::get('LANG_No_Insect_Results')."');
			}
		});
	}
	jQuery('#map')[0].map.addLayer(searchResultsLayer);
	strategy.load({});
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
			return false;
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
			jQuery('#fo-express-doubt-form').hide();
			loadDeterminations(jQuery('[name=determination\\:occurrence_id]').val(), insect_alert_object.insect_id == null ? 'form#fo-new-flower-id-form' : 'form#fo-new-insect-id-form', function(args, type){
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
			jQuery('.filter-insect-container').filter('[occID='+jQuery('form#fo-express-doubt-form').find('[name=determination\\:occurrence_id]').val()+']').find('.insect-ok').removeClass('insect-ok').addClass('occurrence-dubious');
		} else {
			alert(data.error);
		}
	},
	complete: function (){
  		jQuery('.loading-button').removeClass('loading-button');
  	} 
});

jQuery('form#fo-edit-insect-id-form').ajaxForm({ // no tool attached, no alerts
	async: false,
	dataType:  'json', 
	beforeSubmit:   function(data, obj, options){
		clearErrors('form#fo-edit-insect-id-form');
		if (!jQuery('form#fo-edit-insect-id-form input').valid()) {
			myScrollToError();
			return false;
		};
		var found=false;
		for(var i = 0; i<10; i++){
			if(jQuery('#fo-edit-insect-id-list-'+i).val() != ''){
				found=true;
				data.push({name: 'determination\\:taxa_taxon_list_id_list[]', value: jQuery('#fo-edit-insect-id-list-'+i).val()});
			}			
		}
		jQuery('form#fo-edit-insect-id-form').find('[name=determination\\:deleted]').removeAttr('checked');
		if(!found)
			data.push({name: 'determination\\:taxa_taxon_list_id_list[]', value: ''});
   		return true;
	},
	success:   function(data){
		if(data.error == undefined){
			loadDeterminations(jQuery('[name=determination\\:occurrence_id]').val(), 'form#fo-new-insect-id-form', function(args, type){
  			}, ".(user_access('IForm n'.$node->nid.' insect expert') ? '1' : '0').", ".(user_access('IForm n'.$node->nid.' flag dubious insect') ? '1' : '0').", insectTaxa, 'I');
		} else {
			alert(data.error);
		}
	}
});

jQuery('form#fo-edit-flower-id-form').ajaxForm({ // no tool attached, no alerts
	async: false,
	dataType:  'json', 
	beforeSubmit:   function(data, obj, options){
		clearErrors('form#fo-edit-flower-id-form');
		if (!jQuery('form#fo-edit-flower-id-form input').valid()) {
			myScrollToError();
			return false;
		};
		var found=false;
		for(var i = 0; i<10; i++){
			if(jQuery('#fo-edit-flower-id-list-'+i).val() != ''){
				found=true;
				data.push({name: 'determination\\:taxa_taxon_list_id_list[]', value: jQuery('#fo-edit-flower-id-list-'+i).val()});
			}			
		}
		jQuery('form#fo-edit-flower-id-form').find('[name=determination\\:deleted]').removeAttr('checked');
		if(!found)
			data.push({name: 'determination\\:taxa_taxon_list_id_list[]', value: ''});
   		return true;
	},
	success:   function(data){
		if(data.error == undefined){
			loadDeterminations(jQuery('[name=determination\\:occurrence_id]').val(), 'form#fo-new-flower-id-form', function(args, type){
  			}, ".(user_access('IForm n'.$node->nid.' flower expert') ? '1' : '0').", ".(user_access('IForm n'.$node->nid.' flag dubious flower') ? '1' : '0').", flowerTaxa, 'F');
		} else {
			alert(data.error);
		}
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
			jQuery('.filter-insect-container').filter('[occID='+jQuery('form#fo-express-doubt-form').find('[name=determination\\:occurrence_id]').val()+']')
				.find('.insect-ok').removeClass('insect-ok')
				.addClass(jQuery(insectIDstruc.mainForm+' [name=determination\\:determination_type]').val() == 'X' ? 'occurrence-dubious' : 'occurrence-valid');
			jQuery(insectIDstruc.mainForm+' [name=determination\\:determination_type]').val(insectIDstruc.determinationType);
			jQuery(insectIDstruc.mainForm+' [name=determination\\:taxa_taxon_list_id],[name=determination\\:comment],[name=determination\\:taxon_details],[name=determination\\:taxon_extra_info]').val('');
			jQuery('#insect_taxa_list').empty();
			jQuery('#fo-new-insect-id-form').hide();
			loadDeterminations(jQuery('[name=determination\\:occurrence_id]').val(), 'form#fo-new-insect-id-form', function(args, type){
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
			jQuery('#fo-new-flower-id-form').hide();
			loadDeterminations(jQuery('[name=determination\\:occurrence_id]').val(), 'form#fo-new-flower-id-form', function(args, type){
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
			loadComments(jQuery('[name=occurrence_comment\\:occurrence_id]').val(), '#fo-comment-list', 'occurrence_comment', 'occurrence_id', 'occurrence-comment-block', 'occurrence-comment-body', true, false);
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
			loadComments(jQuery('[name=sample_comment\\:sample_id]').val(), '#fc-comment-list', 'sample_comment', 'sample_id', 'sample-comment-block', 'sample-comment-body', true, ".(user_access('IForm n'.$node->nid.' delete collection comment') ? "true" : "false").");
  		} else {
			alert(data.error);
		}
	} 
});
";
	if(user_access('IForm n'.$node->nid.' delete collection')){
		data_entry_helper::$javascript .= "
jQuery('#fc-delete-collection-form').ajaxForm({
		async: false,
		dataType:  'json',
		beforeSubmit:   function(data, obj, options){
			if(!confirm(\"".lang::get('LANG_Collection_Delete_Confirmation')."\")){
				return false;
			}	
			jQuery('#fc_delete_collection_submit_button').addClass('loading-button');
			return true;
		},
		success:   function(data){
			if(data.error != undefined){
				alert(data.error);
			} else {
				// blank out collection in collection list.
				jQuery('.filter-collection').filter('[collID='+collection_preferred_object.collection_id+']').each(function(idx, collection){
					searchResults.features[jQuery(collection).attr('index')].attributes.deleted= true;
					jQuery(collection).css('background-color','#FF0000');
					jQuery(collection).find('.collection-image,.collection-flower-determination,.collection-photoreel,.collection-buttons').remove();
				});
				// return to filter page
				jQuery('#fc-filter-button').click();
		}},
		complete: function (){
			jQuery('.loading-button').removeClass('loading-button');
		}
	});";
	}
  	if(user_access('IForm n'.$node->nid.' delete insect')){
		data_entry_helper::$javascript .= "
jQuery('#fo-delete-insect-form').ajaxForm({
		async: false,
		dataType:  'json',
		beforeSubmit:   function(data, obj, options){
			if(!confirm(\"".lang::get('LANG_Insect_Delete_Confirmation')."\")){
				return false;
			}	
			jQuery('#fo_delete_insect_submit_button').addClass('loading-button');
			return true;
		},
		success:   function(data){
			if(data.error != undefined){
				alert(data.error);
			} else {
				// blank out insect in gallery search insect list.
				jQuery('.filter-insect-container').filter('[occID='+insect_alert_object.insect_id+']').each(function(idx, insect){
					searchResults.features[jQuery(insect).attr('index')].attributes.deleted= true;
					jQuery(insect).css('background-color','#FF0000');
					jQuery(insect).find('.determination-flag,.insect-image,.display-button').remove();
				});
				// remove insect from gallery search collection insect photoreel.
				jQuery('.collection-photoreel .thumb').filter('[occID='+insect_alert_object.insect_id+']').remove();
				// no need to blank out insect in collection insect list as the collection is refetched when it is redisplayed.
				// return to filter search or collection page
				if(jQuery('.collection-insect-container').filter('[occID='+form.find('[name=determination\\:occurrence_id]').val()+']').length > 0) {
					jQuery('#fo-collection-button').click();
				} else {
					jQuery('#fo-filter-button').click();
				}
		}},
		complete: function (){
			jQuery('.loading-button').removeClass('loading-button');
		}
	});";
	}
	if(user_access('IForm n'.$node->nid.' add to front page')){
    	data_entry_helper::$javascript .= "
jQuery('#fc-front-page-form').ajaxForm({ 
	async: false,
	dataType:  'json', 
	beforeSubmit:   function(data, obj, options){
		if (!jQuery('form#fc-front-page-form').valid()) { return false; }
  		jQuery('#fc_front_page_submit_button').addClass('loading-button');
		return true;
	},
	success:   function(data){
		if(data.error != undefined){
			alert(data.error);
		} else {
			if(jQuery('#fc-front-page-form').find('[name^=smpAttr\\:".$frontPageAttrID."]')
						.filter('[checked]').val() == '1')
				jQuery('#fc-front-page-message').empty().append(\"<span>".lang::get('LANG_Included_In_Front_Page')."<span>\");
			else
				jQuery('#fc-front-page-message').empty().append(\"<span>".lang::get('LANG_Removed_From_Front_Page')."<span>\");
			ajaxStack.push(
				jQuery.ajax({ 
					type: \"GET\", 
					url: \"".$svcUrl."/data/sample_attribute_value\"  +
						\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
						\"&sample_id=\" + data.outer_id + \"&REMOVEABLEJSONP&callback=?\",
					dataType: 'json',
					success: function(attrdata) {
						if(!(attrdata instanceof Array)){
							alertIndiciaError(attrdata);
						} else if (attrdata.length>0) {
							for(i=0; i< attrdata.length; i++){
								if (attrdata[i].id){
									switch(parseInt(attrdata[i].sample_attribute_id)){
										case ".$frontPageAttrID.":
											jQuery('#fc-front-page-form').find('[name^=smpAttr\\:".$frontPageAttrID."]')
												.attr('name', 'smpAttr:".$frontPageAttrID.":'+attrdata[i].id)
												.filter('[value='+attrdata[i].raw_value+']')
												.attr('checked', 'checked');
											break;
									}
					}}}}}));
			ajaxStack.push(
				jQuery.ajax({ 
					type: \"GET\", 
					url: \"".$svcUrl."/data/sample_attribute_value\"  +
						\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
						\"&sample_attribute_id=".$frontPageAttrID."&value=1&REMOVEABLEJSONP&callback=?\",
					dataType: 'json',
					success: function(attrdata) {
						if(!(attrdata instanceof Array)){
							alertIndiciaError(attrdata);
						} else {
							jQuery('#fc-front-page-message').append(\"<br /><span>".lang::get('LANG_Number_In_Front_Page')."\"+attrdata.length+\".<span>\");
						}}}));
    }},
	complete: function (){
  		jQuery('.loading-button').removeClass('loading-button');
  	}
});";
    }
	data_entry_helper::$javascript .= "
jQuery('#fc-new-location-form').ajaxForm({ 
	async: false,
	dataType:  'json', 
	beforeSubmit:   function(data, obj, options){
		if(!confirm(\"".lang::get('LANG_Localisation_Confirm')."\")) { return false; }
		if (!jQuery('form#fc-new-location-form').valid()) { return false; }
  		jQuery('#fc_location_submit_button').addClass('loading-button');
		return true;
	},
	success:   function(data){
		if(data.error != undefined){
			alert(data.error);
		} else {
			jQuery('#fc-new-location-message').empty().append(\"<span>".lang::get('LANG_Location_Updated')."<span>\");
		}
	},
	complete: function (){
  		jQuery('.loading-button').removeClass('loading-button');
  	}
});
// Boolean attributes: not present = 0;
loadSampleAttributes = function(keyValue){
    jQuery('#fo-insect-start-time,#fo-insect-end-time,#fo-insect-sky,#fo-insect-temp,#fo-insect-wind,#fo-insect-shade').empty();
	jQuery('#fo-insect-shade').append(\"".lang::get('No')."\"); // default with no attribute is 'No'
    ajaxStack.push($.getJSON(\"".$svcUrl."/data/sample_attribute_value\"  +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&sample_id=\" + keyValue + \"&REMOVEABLEJSONP&callback=?\", function(attrdata) {
		if(!(attrdata instanceof Array)){
   			alertIndiciaError(attrdata);
   		} else if (attrdata.length>0) {
			for(i=0; i< attrdata.length; i++){
				if (attrdata[i].id){
					switch(parseInt(attrdata[i].sample_attribute_id)){
						case ".$startTimeAttrID.":
							jQuery('#fo-insect-start-time').append(attrdata[i].value);
							break;
						case ".$endTimeAttrID.":
							jQuery('#fo-insect-end-time').append(attrdata[i].value);
							break;
  						case ".$skyAttrID.":
							jQuery('#fo-insect-sky').append(convertTerm(attrdata[i].raw_value));
							break;
  						case ".$temperatureAttrID.":
							jQuery('#fo-insect-temp').append(convertTerm(attrdata[i].raw_value));
							break;
  						case ".$windAttrID.":
							jQuery('#fo-insect-wind').append(convertTerm(attrdata[i].raw_value));
							break;
  						case ".$shadeAttrID.":
  							if(attrdata[i].value == '1'){
								jQuery('#fo-insect-shade').empty().append(\"".lang::get('Yes')."\");
  							}
							break;
  					}
				}
			}
		}
	}));
}
loadOccurrenceAttributes = function(keyValue){
    jQuery('#focus-flower-type,#fo-insect-foraging').empty();
	jQuery('#fo-insect-foraging').append(\"".lang::get('No')."\");
    ajaxStack.push($.getJSON(\"".$svcUrl."/data/occurrence_attribute_value\"  +
			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&occurrence_id=\" + keyValue + \"&REMOVEABLEJSONP&callback=?\", function(attrdata) {
		if(!(attrdata instanceof Array)){
   			alertIndiciaError(attrdata);
   		} else if (attrdata.length>0) {
			for(i=0; i< attrdata.length; i++){
			  if (attrdata[i].id){
				if (attrdata[i].id){
					switch(parseInt(attrdata[i].occurrence_attribute_id)){
						case ".$flowerTypeAttrID.":
							jQuery('<span>'+convertTerm(attrdata[i].raw_value)+'</span>').appendTo('#focus-flower-type');
							break;
						case ".$foragingAttrID.":
  							if(attrdata[i].value == '1'){
								jQuery('#fo-insect-foraging').empty().append(\"".lang::get('Yes')."\");
  							}
							break;
  }}}}}}));
}
loadLocationAttributes = function(keyValue){
    jQuery('#focus-habitat').empty();
	ajaxStack.push($.getJSON(\"".$svcUrl."/data/location_attribute_value\"  +
			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&location_id=\" + keyValue + \"&REMOVEABLEJSONP&callback=?\", function(attrdata) {
		if(!(attrdata instanceof Array)){
   			alertIndiciaError(attrdata);
   		} else if (attrdata.length>0) {
			var habitat_string = '';
			for(i=0; i< attrdata.length; i++){
				if (attrdata[i].id){
					switch(parseInt(attrdata[i].location_attribute_id)){
						case ".$habitatAttrID.":
							if (attrdata[i].raw_value > 0) habitat_string = (habitat_string == '' ? convertTerm(attrdata[i].raw_value) : (habitat_string + ', ' + convertTerm(attrdata[i].raw_value)));
							break;
			}}}
			jQuery('<span>'+habitat_string+'</span>').appendTo('#focus-habitat');
  }}));
}

insertImage = function(prepend, path, target, ratio, allowFull, callback){
    var img = new Image();
	var item = jQuery(img).load(function () {
		target.removeClass('loading').height('').append(this);
		target.append(this);
		if(this.width/this.height > ratio){
			jQuery(this).css('width', '100%').css('height', 'auto').css('vertical-align', 'middle').css('margin-left', 'auto').css('margin-right', 'auto').css('display', 'block');
		} else {
			jQuery(this).css('width', (100*this.width/(this.height*ratio))+'%').css('height', 'auto').css('vertical-align', 'middle').css('margin-left', 'auto').css('margin-right', 'auto').css('display', 'block');
		}
		if(callback) callback(this);
	}).attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."'+prepend+path);
	if(allowFull)
		item.click(function(){
			window.open ('".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."'+path, 'newwindow', config='toolbar=no, menubar=no, location=no, directories=no, status=no')
		});
}

loadImage = function(imageTable, key, keyValue, target, imageRatio, callback, prepend, allowfull, imgCallback){
    jQuery(target).empty();
    ajaxStack.push(jQuery.ajax({ 
        type: \"GET\",
        myTarget: target,
        myRatio: imageRatio,
        myCallback: callback,
        myPrepend: prepend,
        myAllowfull: allowfull,
        myImgCallback: imgCallback,
        url: \"".$svcUrl."/data/\" + imageTable +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&\" + key + \"=\" + keyValue + \"&REMOVEABLEJSONP&callback=?\", 
        success: function(imageData) {
		  if(!(imageData instanceof Array)){
   			alertIndiciaError(imageData);
   		  } else if (imageData.length>0) {
 			insertImage(this.myPrepend, imageData[0].path, jQuery(this.myTarget), this.myRatio, this.myAllowfull, this.myImgCallback);
			if(this.myCallback) this.myCallback(imageData[0]);
		  }},
		dataType: 'json'
	}));
}

setIDButtons = function (expert, owner, reidentifiable, can_doubt, type){
  if(expert === true) jQuery('#fo-id-buttons').addClass('expert');
  else if(expert===false) jQuery('#fo-id-buttons').removeClass('expert'); // leave possiblity of 'other' which does nothing

  if(owner === true) jQuery('#fo-id-buttons').addClass('owner');
  else if(owner===false) jQuery('#fo-id-buttons').removeClass('owner'); // leave possiblity of 'other' which does nothing

  if(reidentifiable === true) jQuery('#fo-id-buttons').addClass('reidentifiable');
  else if(reidentifiable===false) jQuery('#fo-id-buttons').removeClass('reidentifiable'); // leave possiblity of 'other' which does nothing

  if(can_doubt === true) {
    jQuery('#fo-id-buttons').addClass('can_doubt');
    jQuery('#fo-doubt-button').show();
  } else if(can_doubt === false) {
    jQuery('#fo-id-buttons').removeClass('can_doubt');
    jQuery('#fo-doubt-button').hide();
  }  // leave possiblity of 'other' which does nothing

  if(jQuery('#fo-id-buttons').hasClass('expert') ||
      (jQuery('#fo-id-buttons').hasClass('owner') && jQuery('#fo-id-buttons').hasClass('reidentifiable'))){
    jQuery(type=='I'?'#fo-new-insect-id-button':'#fo-new-flower-id-button').show();
  } else {
    jQuery(type=='I'?'#fo-new-insect-id-button':'#fo-new-flower-id-button').hide();
  }

  if(jQuery('#fo-id-buttons').hasClass('expert') ||
      jQuery('#fo-id-buttons').hasClass('can_doubt') || 
      (jQuery('#fo-id-buttons').hasClass('owner') && jQuery('#fo-id-buttons').hasClass('reidentifiable'))){
    if(jQuery('#fo-id-history:visible').length>0)
      jQuery('#fo-id-buttons').show().removeClass('ui-corner-bottom'); // history is visible so current ID will not have this class
    else {
      jQuery('#fo-id-buttons').show().addClass('ui-corner-bottom'); // history is visible
      jQuery('#fo-current-id').removeClass('ui-corner-bottom');
    }
  } else {
    jQuery('#fo-id-buttons').hide();
    if(jQuery('#fo-id-history:visible').length==0)
      jQuery('#fo-current-id').addClass('ui-corner-bottom');
  }
}

// this function is called to reset the page after a doubt is emitted, an insect or flower is reidentified, a determination is changed, 
loadDeterminations = function(keyValue, newIDForm, callback, isExpert, canDoubt, taxaList, type){
    jQuery('#fo-id-history').show().empty().append('<strong>".lang::get('LANG_History_Title')."</strong>').addClass('empty');
	jQuery('#fo-current-id').empty().removeClass('ui-corner-bottom');
	jQuery('#poll-banner').empty();
	setIDButtons(null, null, null, false, type);
	jQuery('#fo-express-doubt-form').find('[name=determination\\:taxon_extra_info]').val('');
	jQuery('#fo-express-doubt-form').find('[name=determination\\:taxa_taxon_list_id]').val('');
	jQuery('#fo-doubt-button').data('toolRetValues',[]).hide();
	jQuery('.poll-id-button').data('toolRetValues',[]);
	jQuery('.taxa_list').empty();
    ajaxStack.push(jQuery.ajax({ 
        type: \"GET\", 
        url: \"".$svcUrl."/data/determination?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&reset_timeout=true&deleted=f&orderby=id&REMOVEABLEJSONP&callback=?&occurrence_id=\" + keyValue,
		params: {newIDForm: newIDForm,
				callback: callback,
				isExpert: isExpert,
				canDoubt: canDoubt,
				taxaList: taxaList,
				type: type},
		dataType: 'json',
        success: function(detData) {
   		  var callbackArgs = [];
   		  if(!(detData instanceof Array)){
   			alertIndiciaError(detData);
   		  } else if (detData.length>0) {
			if(detData.length==1){
				jQuery('#fo-id-history').hide();
				jQuery(jQuery('#fo-id-buttons:visible').length>0?'#fo-id-buttons':'#fo-current-id').addClass('ui-corner-bottom');
			} else
				jQuery(jQuery('#fo-id-buttons:visible').length>0?'#fo-id-buttons':'#fo-current-id').removeClass('ui-corner-bottom');
			var i = detData.length-1;\n".(
isset($args['deleteable_user_id']) && $args['deleteable_user_id']!='' && $args['deleteable_user_id']==$user->uid ?
"			var modForm = jQuery(this.params.type=='I'?'#fo-edit-insect-id':'#fo-edit-flower-id');
			modForm.find('[name=determination\\:id]').val(detData[i].id);
			modForm.find('[name=determination\\:deleted]').removeAttr('checked');
			modForm.find('[name=determination\\:occurrence_id]').val(detData[i].occurrence_id);
			modForm.find('[name=determination\\:cms_ref]').val(detData[i].cms_ref);
			modForm.find('[name=determination\\:person_name]').val(detData[i].person_name);
			modForm.find('[name=determination\\:email_address]').val(detData[i].email_address);
			modForm.find('[name=determination\\:determination_type]').val(detData[i].determination_type);
			modForm.find('[name=determination\\:taxon_details]').val(detData[i].taxon_details==null?'':detData[i].taxon_details);
			modForm.find('[name=determination\\:taxon_extra_info]').val(detData[i].taxon_extra_info==null?'':detData[i].taxon_extra_info);
			modForm.find('[name=determination\\:comment]').val(detData[i].comment==null?'':detData[i].comment);
			modForm.find('[name=determination\\:taxa_taxon_list_id]').val(detData[i].taxa_taxon_list_id==null?'':detData[i].taxa_taxon_list_id);
			for(j=0; j < 10; j++) jQuery((this.params.type=='I'?'#fo-edit-insect-id':'#fo-edit-flower-id')+'-list-'+j).val('');":""
)."\n			var callbackEntry = {date: detData[i].updated_on, user_id: detData[i].cms_ref, taxa: []};
   			jQuery('#fo-express-doubt-form').find('[name=determination\\:occurrence_id]').val(detData[i].occurrence_id);
   			var string = '';
   			var resultsText = \"".lang::get('LANG_Taxa_Returned')."<br />{ \";
			if(detData[i].taxon != '' && detData[i].taxon != null){
				string = htmlspecialchars(detData[i].taxon);
				callbackEntry.taxa.push(detData[i].taxon);
  			}
			jQuery('#fo-express-doubt-form').find('[name=determination\\:taxa_taxon_list_id]').val(detData[i].taxa_taxon_list_id);
			jQuery(this.params.newIDForm).find('[name=determination:taxa_taxon_list_id]').val(detData[i].taxa_taxon_list_id);
			if(detData[i].taxa_taxon_list_id_list != null && detData[i].taxa_taxon_list_id_list != '' && detData[i].taxa_taxon_list_id_list != '{}'){
			  	resultsIDs = detData[i].taxa_taxon_list_id_list.substring(1, detData[i].taxa_taxon_list_id_list.length - 1).split(',');
			  	for(j=0; j < resultsIDs.length; j++){
					jQuery((type=='I'?'#fo-edit-insect-id':'#fo-edit-flower-id')+'-list-'+j).val(resultsIDs[j]);
					for(k = 0; k< this.params.taxaList.length; k++)
						if(this.params.taxaList[k].id == resultsIDs[j]) {
							string = (string == '' ? '' : string + ', ') + htmlspecialchars(this.params.taxaList[k].taxon);
							callbackEntry.taxa.push(this.params.taxaList[k].taxon);
							resultsText = resultsText + (j == 0 ? '' : '<br />&nbsp;&nbsp;') + htmlspecialchars(this.params.taxaList[k].taxon);
							break;
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
			if(detData[i].taxon_extra_info != '' && detData[i].taxon_extra_info != null && detData[i].taxon_extra_info != 'null'){
				string = (string == '' ? '' : string + ' ') + '('+htmlspecialchars(detData[i].taxon_extra_info)+')';
			}
			jQuery('#fo-express-doubt-form').find('[name=determination\\:taxon_extra_info]').val(detData[i].taxon_extra_info==null ? '' : detData[i].taxon_extra_info);
			jQuery(this.params.newIDForm).find('[name=determination:taxon_extra_info]').val(detData[i].taxon_extra_info==null ? '' : detData[i].taxon_extra_info);
			if((this.params.type == 'I' && ".(user_access('IForm n'.$node->nid.' delete insect determination') ? 'true' : 'false').") || 
					(this.params.type == 'F' && ".(user_access('IForm n'.$node->nid.' delete flower determination') ? 'true' : 'false')." && detData.length>1)){ // can't delete the flower determination if it is the only one
				var delButton = jQuery('<span class=\"main-determination-delete-button\" style=\"float: right;\"><img src=\"/misc/watchdog-error.png\" style=\"vertical-align: middle;\"></span>').appendTo('#fo-current-id');
				var delForm = jQuery('<form action=\"".iform_ajaxproxy_url($node, 'determination')."\" method=\"POST\" style=\"display: none;\"><input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" /><input type=\"hidden\" name=\"determination:id\" value=\"'+detData[i].id+'\" /><input type=\"hidden\" name=\"determination:deleted\" value=\"t\" /></form>').appendTo(delButton);
				delForm.ajaxForm({
					async: false,
					dataType:  'json',
					params: {occID: detData[i].occurrence_id, // these persist
							newIDForm: this.params.newIDForm,
							isExpert: this.params.isExpert,
							canDoubt: this.params.canDoubt,
							taxaList: this.params.taxaList,
							type: this.params.type},
					success:   function(data){
						if(data.error == undefined){
							if(this.params.type == 'I'){
								// flag out insect in gallery search insect list as modified.
								jQuery('.filter-insect-container').filter('[occID='+occID+']').each(function(idx, insect){
									searchResults.features[jQuery(insect).attr('index')].attributes.modified= true;
									jQuery(insect).css('background-color','#00FFFF');
								});
								// gallery search collection insect photoreel doesn't need to change.
								// no need to change insect in collection insect list as the collection is refetched when it is redisplayed.
							} else { //type == 'F' Flower
								// flag flower in gallery search collection list as modified.
								jQuery('.collection-flower-determination').filter('[occID='+this.params.occID+']').css('background-color','#00FFFF');
							}
							loadDeterminations(this.params.occID, this.params.newIDForm, null, this.params.isExpert, this.params.canDoubt, this.params.taxaList, this.params.type); // no alert
						} else {
							alert(data.error);
						}
					} 
				});
				delButton.click(function(e){
				  e.preventDefault();
				  if(!confirm(\"".lang::get('LANG_Determination_Delete_Confirmation')."\")) return;
				  var me = $(e.target.parentNode);
				  me.find('form').submit();
				});
			}
			if(string != '')
				jQuery('<p><strong>'+string+ '</strong> ".lang::get('LANG_Comment_By')."' + detData[i].person_name + ' ' + convertDate(detData[i].updated_on,false) + '</p>').appendTo('#fo-current-id');
			else
				jQuery('<p>".lang::get('LANG_Comment_By')."' + detData[i].person_name + ' ' + convertDate(detData[i].updated_on,false) + '</p>').appendTo('#fo-current-id');
			setIDButtons(null, null, detData[i].determination_type != 'C', this.params.canDoubt && detData[i].determination_type == 'A', this.params.type);
			// Type 'A' - do nothing
			if(detData[i].determination_type == 'B'){
				jQuery(\"<p>".lang::get('LANG_Doubt_Expressed')."</p>\").appendTo('#fo-current-id');
				jQuery('#fo-current-id').prepend(\"<div class='determination-flag occurrence-dubious'></div>\");
			} else if(detData[i].determination_type == 'C'){
				// This has been set by an expert. If it is the same as the previous determination, then it has been confirmed,
				// If different then it is has been corrected. Use different messages.
				// Ideally could be able to tell if bulk validation - but cant!
				jQuery('#fo-current-id').prepend(\"<div class='determination-flag occurrence-valid'></div>\");
				if(i==0 || (detData[i].taxon == detData[i-1].taxon && detData[i].taxa_taxon_list_id_list == detData[i-1].taxa_taxon_list_id_list))
				  jQuery(\"<p>".lang::get('LANG_Determination_Valid')."</p>\").appendTo('#fo-current-id');
				else
				  jQuery(\"<p>".lang::get('LANG_Determination_Corrected')."</p>\").appendTo('#fo-current-id');
			} else if(detData[i].determination_type == 'I'){
				jQuery(\"<p>".lang::get('LANG_Determination_Incorrect')."</p>\").appendTo('#fo-current-id');
				jQuery('#fo-current-id').prepend(\"<div class='determination-flag occurrence-dubious'></div>\");
			} else if(detData[i].determination_type == 'U'){
				jQuery(\"<p>".lang::get('LANG_Determination_Unconfirmed')."</p>\").appendTo('#fo-current-id');
				jQuery('#fo-current-id').prepend(\"<div class='determination-flag occurrence-dubious'></div>\");
			} else if(detData[i].determination_type == 'X'){
				jQuery(\"<p>".lang::get('LANG_Determination_Unknown')."</p>\").appendTo('#fo-current-id');
//				jQuery('#fo-current-id').prepend(\"<div class='determination-flag occurrence-unknown'></div>\");
			}
			if(detData[i].comment != '' && detData[i].comment != null){
				jQuery('<p>'+detData[i].comment+'</p>').appendTo('#fo-current-id');
			}
			
			for(i=detData.length - 2; i >= 0; i--){ // deliberately miss last one, in reverse order
				callbackEntry = {date: detData[i].updated_on, user_id: detData[i].cms_ref, taxa: []};
				string = '';
				jQuery('#fo-id-history').removeClass('empty');
				var item = jQuery('<div></div>').attr('detID',detData[i].id).addClass('history-item').appendTo('#fo-id-history');
				if((this.params.type == 'I' && ".(user_access('IForm n'.$node->nid.' delete insect determination') ? 'true' : 'false').") || 
						(this.params.type == 'F' && ".(user_access('IForm n'.$node->nid.' delete flower determination') ? 'true' : 'false').")){
					var delButton = jQuery('<span style=\"float: right;\"><img src=\"/misc/watchdog-error.png\" style=\"vertical-align: middle;\"></span>').appendTo(item);
					var delForm = jQuery('<form action=\"".iform_ajaxproxy_url($node, 'determination')."\" method=\"POST\" style=\"display: none;\"><input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" /><input type=\"hidden\" name=\"determination:id\" value=\"'+detData[i].id+'\" /><input type=\"hidden\" name=\"determination:deleted\" value=\"t\" /></form>').appendTo(delButton);
					delForm.ajaxForm({
						async:    false,
						dataType: 'json',
						params: {occID: detData[i].occurrence_id, // these persist
							type: this.params.type},
						success:  function(data){
							if(data.error == undefined){
								// we're just about to remove the historic ID: if there is only one, that means this will leave the main
								// ID as the only one: for flowers we can't remove the last ID.
								if(this.params.type == 'F' && jQuery('.history-item').length == 1){
									jQuery('.main-determination-delete-button').remove();
								}
								jQuery('.history-item').filter('[detID='+data.outer_id+']').remove();
							} else { alert(data.error); }
						} 
					});
					delButton.click(function(e){
					  e.preventDefault();
					  if(!confirm(\"".lang::get('LANG_Determination_Delete_Confirmation')."\")) return;
					  var me = $(e.target.parentNode);
					  me.find('form').submit();
					});
				}
				if(detData[i].taxon != '' && detData[i].taxon != null){
					string = htmlspecialchars(detData[i].taxon);
		  			callbackEntry.taxa.push(detData[i].taxon);
  				}
				if(detData[i].taxa_taxon_list_id_list != '' && detData[i].taxa_taxon_list_id_list != null && detData[i].taxa_taxon_list_id_list != '{}'){
					var resultsIDs = detData[i].taxa_taxon_list_id_list.substring(1, detData[i].taxa_taxon_list_id_list.length - 1).split(',');
					for(j=0; j < resultsIDs.length; j++){
						for(k = 0; k< this.params.taxaList.length; k++)
							if(this.params.taxaList[k].id == resultsIDs[j]) {
								string = (string == '' ? '' : string + ', ') + htmlspecialchars(this.params.taxaList[k].taxon);
								callbackEntry.taxa.push(this.params.taxaList[k].taxon);
								break;
							}
					}
				}
				if(detData[i].taxon_extra_info != '' && detData[i].taxon_extra_info != null && detData[i].taxon_extra_info != 'null'){
					string = (string == '' ? '' : string + ' ') + '('+htmlspecialchars(detData[i].taxon_extra_info)+')' ;
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
			jQuery('#fo-id-history').hide();
			jQuery(jQuery('#fo-id-buttons:visible').length>0?'#fo-id-buttons':'#fo-current-id').addClass('ui-corner-bottom');
			jQuery(this.params.type=='I'?'#fo-edit-insect-id':'#fo-edit-flower-id').removeClass('ui-accordion-content-active');
			jQuery('#fo-current-id').append(\"<div class='determination-flag occurrence-unknown'></div><p>".lang::get('LANG_No_Determinations')."</p>\");
			setIDButtons(null, null, true, false, this.params.type); // can re-identify unidentified species 
			jQuery('#poll-banner').append(\"".lang::get('LANG_No_Determinations')."\");
		  }
		  if(this.params.callback) this.params.callback(callbackArgs, this.params.type);
		}  
	}));
	// when the occurrence is loaded (eg in loadInsect) all determination:occurrence_ids are set.
	jQuery(newIDForm).find('[name=determination\\:determination_type]').val(isExpert ? 'C' : 'A');
};

loadComments = function(keyValue, block, table, key, blockClass, bodyClass, reset_timeout, addDelete){
    jQuery(block).empty();
    ajaxStack.push(jQuery.ajax({ 
        type: \"GET\",
        url: \"".$svcUrl."/data/\" + table + \"?mode=json&view=list\" +
        	(reset_timeout ? \"&reset_timeout=true\" : \"\") + \"&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&\" + key + \"=\" + keyValue + \"&REMOVEABLEJSONP&callback=?\", 
        params: {keyValue: keyValue,
        		 block: block,
        		 table: table,
        		 key: key, 
        		 blockClass: blockClass,
        		 bodyClass: bodyClass,
        		 addDelete: addDelete},
		dataType: 'json',
        success: function(commentData) {
   		  if(!(commentData instanceof Array)){
   			alertIndiciaError(commentData);
   		  } else if (commentData.length>0) {
   			for(i=commentData.length - 1; i >= 0; i--){
	   			var newCommentDetails = jQuery('<div class=\"'+this.params.blockClass+'\"/>').appendTo(block);
				if (this.params.addDelete){
					var delButton = jQuery('<span style=\"float: right;\"><img src=\"/misc/watchdog-error.png\" style=\"vertical-align: middle;\"></span>').appendTo(newCommentDetails);
					var delForm = jQuery('<form action=\"'+(this.params.table == 'sample_comment' ? '".iform_ajaxproxy_url($node, 'smp-comment')."' : '".iform_ajaxproxy_url($node, 'occ-comment')."')+'\" method=\"POST\" style=\"display: none;\"><input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" /><input type=\"hidden\" name=\"'+this.params.table+':id\" value=\"'+commentData[i].id+'\" /><input type=\"hidden\" name=\"'+this.params.table+':deleted\" value=\"t\" /></form>').appendTo(delButton);
					delForm.ajaxForm({
						async: false,
						dataType:  'json',
						success:   function(data){
							if(data.error == undefined){
								loadComments(this.params.keyValue, this.params.block, this.params.table, this.params.key, this.params.blockClass, this.params.bodyClass, true, this.params.addDelete);
							} else {
								alert(data.error);
							}
						} 
					});
					delButton.click(function(e){
					  e.preventDefault();
					  if(!confirm(\"".lang::get('LANG_Comment_Delete_Confirmation')."\")) return;
					  var me = $(e.target.parentNode);
					  me.find('form').submit();
					});
				}
				jQuery('<span>".lang::get('LANG_Comment_By')."' + commentData[i].person_name + ' ' + convertDate(commentData[i].updated_on,false) + '</span>').appendTo(newCommentDetails);
	   			var newComment = jQuery('<div class=\"'+this.params.blockClass+'\"/>').appendTo(this.params.block);
				jQuery('<p>' + commentData[i].comment + '</p>').appendTo(newComment);
			}
		  } else {
			jQuery('<p>".lang::get('LANG_No_Comments')."</p>').appendTo(this.params.block);
		  }
		}
    }));
};

collectionProcessing = function(keyValue, type){
    ajaxStack.push($.getJSON(\"".$svcUrl."/data/sample_attribute_value\"  +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&sample_id=\" + keyValue + \"&REMOVEABLEJSONP&callback=?\", function(attrdata) {
		if(!(attrdata instanceof Array)){
   			alertIndiciaError(attrdata);
   		} else if (attrdata.length>0) {
			for(i=0; i< attrdata.length; i++){
				if (attrdata[i].id){
					switch(parseInt(attrdata[i].sample_attribute_id)){
						case ".$uidAttrID.":
							insect_alert_object.collection_user_id = attrdata[i].value;
							flower_alert_object.collection_user_id = attrdata[i].value;
							setIDButtons(null, parseInt(attrdata[i].value) == ".$uid.", null, null, type);
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
    loadOccurrenceAttributes(keyValue);
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
   					if(smpData[0].parent_id == null) {
   						alertIndiciaError({error: \"".lang::get('LANG_Bad_Insect_ID')."\"});
   						return;
		   			}
   					collection = smpData[0].parent_id;
					collectionProcessing(collection, 'I');
					jQuery('#fo-insect-date').empty().append(convertDate(smpData[0].date_start,false));
					loadSampleAttributes(smpData[0].id);
					jQuery('#fo-collection-button').data('smpID',smpData[0].parent_id).data('collectionIndex', collectionIndex).show();
					ajaxStack.push($.getJSON(\"".$svcUrl."/data/location/\" +smpData[0].location_id +
							\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
							\"&REMOVEABLEJSONP&callback=?\", function(locationData) {
						if(!(locationData instanceof Array)){
							alertIndiciaError(locationData);
						} else if (locationData.length>0) {
							var parser = new OpenLayers.Format.WKT();
							var feature = parser.read(locationData[0].centroid_geom);
							var filter = new OpenLayers.Filter.Spatial({
  								type: OpenLayers.Filter.Spatial.CONTAINS ,
    							property: 'the_geom',
    							value: feature.geometry
							});
							inseeProtocol.read({filter: filter, callback: fillOccurrenceLocationDetails});
						}
					}));
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
			collectionProcessing(occData[0].sample_id, 'F');
            ajaxStack.push($.getJSON(\"".$svcUrl."/data/sample/\" + occData[0].sample_id +
   					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   					\"&REMOVEABLEJSONP&callback=?\", function(collection) {
   				if(!(collection instanceof Array)){
   					alertIndiciaError(collection);
   				} else if (collection.length > 0) {
   					if(collection[0].parent_id != null) {
   						alertIndiciaError({error: \"".lang::get('LANG_Bad_Flower_ID')."\"});
   						return;
		   			}
   					loadLocationAttributes(collection[0].location_id);
   					ajaxStack.push($.getJSON(\"".$svcUrl."/data/location/\" +collection[0].location_id +
							\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
							\"&REMOVEABLEJSONP&callback=?\", function(locationData) {
						if(!(locationData instanceof Array)){
							alertIndiciaError(locationData);
						} else if (locationData.length>0) {
							var parser = new OpenLayers.Format.WKT();
							var feature = parser.read(locationData[0].centroid_geom);
							var filter = new OpenLayers.Filter.Spatial({
  								type: OpenLayers.Filter.Spatial.CONTAINS ,
    							property: 'the_geom',
    							value: feature.geometry
							});
							inseeProtocol.read({filter: filter, callback: fillOccurrenceLocationDetails});
						}
					}));
				}
			}));
   		}
    }));
}

loadInsect = function(insectID, collectionIndex, insectIndex, type){
	if(bulkValidating) return; // don't do anything if bulk validating.
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
		var myThumb = jQuery('.collection-insect-container').filter('[occID='+insectID+']').prev();
		if(myThumb.length > 0)
			jQuery('#fo-prev-button').show().data('occID', myThumb.attr('occID')).data('collectionIndex', collectionIndex).data('insectIndex', null).data('type', 'C');
		myThumb = jQuery('.collection-insect-container').filter('[occID='+insectID+']').next();
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
	jQuery('#fo-image').empty();
    jQuery('#focus-occurrence,#fo-addn-info-header,#fo-insect-addn-info').show();
	jQuery('#fo-edit-insect-id,#fo-delete-insect').addClass('ui-accordion-content-active');
	jQuery('#fo-edit-flower-id,#fo-new-comment').removeClass('ui-accordion-content-active');
	jQuery('#fo-image').height(jQuery('#fo-image').width()/(".$args['Insect_Image_Ratio']."));
    jQuery('[name=determination\\:occurrence_id],[name=occurrence_comment\\:occurrence_id],[name=occurrence\\:id]').val(insectID);
	jQuery('#fo-new-insect-id-form,#fo-new-flower-id-form,#fo-express-doubt-form,#fo-new-flower-id-button').hide();
	setIDButtons(".(user_access('IForm n'.$node->nid.' insect expert') ? 'true' : 'false').", false, false, false, 'I');
	jQuery('#fo-new-comment-button').".((user_access('IForm n'.$node->nid.' insect expert') || user_access('IForm n'.$node->nid.' create insect comment')) ? "show()" : "hide()").";
	loadDeterminations(insectID, 'form#fo-new-insect-id-form', null, ".(user_access('IForm n'.$node->nid.' insect expert') ? '1' : '0').", ".(user_access('IForm n'.$node->nid.' flag dubious insect') ? '1' : '0').", insectTaxa, 'I');
	loadImage('occurrence_image', 'occurrence_id', insectID, '#fo-image', ".$args['Insect_Image_Ratio'].", function(imageRecord){insect_alert_object.insect_image_path = imageRecord.path}, '', true, false);
	loadInsectAddnInfo(insectID, collectionIndex);
	loadComments(insectID, '#fo-comment-list', 'occurrence_comment', 'occurrence_id', 'occurrence-comment-block', 'occurrence-comment-body', false, ".(user_access('IForm n'.$node->nid.' delete insect comment') ? "true" : "false").");
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
	jQuery('#fo-image').empty();
	jQuery('#fo-edit-flower-id').addClass('ui-accordion-content-active');
	jQuery('#fo-edit-insect-id,#fo-delete-insect,#fo-new-comment').removeClass('ui-accordion-content-active');
	jQuery('#focus-occurrence,#fo-addn-info-header,#fo-flower-addn-info').show();
	jQuery('#fo-image').height(jQuery('#fo-image').width()/(".$args['Flower_Image_Ratio']."));
	jQuery('#fo-new-insect-id-form,#fo-new-flower-id-form,#fo-express-doubt-form').hide();
	jQuery('[name=determination\\:occurrence_id],[name=occurrence_comment\\:occurrence_id]').val(flowerID);
	jQuery('[name=occurrence\\:id]').val('');
	jQuery('#fo-new-insect-id-button').hide();
	setIDButtons(".(user_access('IForm n'.$node->nid.' flower expert') ? 'true' : 'false').", false, false, false, 'F');
	jQuery('#fo-new-comment-button').".((user_access('IForm n'.$node->nid.' flower expert') || user_access('IForm n'.$node->nid.' create flower comment')) ? "show()" : "hide()").";
	loadDeterminations(flowerID, 'form#fo-new-flower-id-form', null, ".(user_access('IForm n'.$node->nid.' flower expert') ? '1' : '0').", ".(user_access('IForm n'.$node->nid.' flag dubious flower') ? '1' : '0').", flowerTaxa, 'F');
	loadImage('occurrence_image', 'occurrence_id', flowerID, '#fo-image', ".$args['Flower_Image_Ratio'].", function(imageRecord){flower_alert_object.flower_image_path = imageRecord.path}, '', true, false);
	loadFlowerAddnInfo(flowerID, collectionIndex);
	loadComments(flowerID, '#fo-comment-list', 'occurrence_comment', 'occurrence_id', 'occurrence-comment-block', 'occurrence-comment-body', false, ".(user_access('IForm n'.$node->nid.' delete flower comment') ? "true" : "false").");
	myScrollTo('#poll-banner');
}

addDrawnGeomToSelection = function(geometry) {
	// Create the polygon as drawn
	var feature = new OpenLayers.Feature.Vector(geometry, {});
	polygonLayer.addFeatures([feature]);
	polygonLayer.map.searchLayer.destroyFeatures();
	jQuery('#imp-insee-div').hide();
	if(inseeLayer != null) inseeLayer.destroyFeatures();
	inseeLayerStore.destroyFeatures();
};
OpenLayers.Control.ClearLayer = OpenLayers.Class(OpenLayers.Control, {
    destroy: function() {
        this.deactivate();        
        OpenLayers.Control.prototype.destroy.apply(this, arguments);
    },
    activate: function() {
      jQuery('#imp-insee-div').hide();
      if(inseeLayer != null) inseeLayer.destroyFeatures();
      inseeLayerStore.destroyFeatures();
      polygonLayer.destroyFeatures();
      polygonLayer.map.searchLayer.destroyFeatures();
      return false;
    },
    CLASS_NAME: \"OpenLayers.Control.ClearLayer\"
});

MyEditingToolbar=OpenLayers.Class(
		OpenLayers.Control.Panel,{
			initialize:function(layer,options){
				OpenLayers.Control.Panel.prototype.initialize.apply(this,[options]);
				this.addControls([]);
				var controls=[new OpenLayers.Control.Navigation(),
					new OpenLayers.Control.DrawFeature(layer,OpenLayers.Handler.Polygon,{'displayClass':'olControlDrawFeaturePolygon', drawFeature: addDrawnGeomToSelection}),
					new OpenLayers.Control.ClearLayer({'displayClass':'olControlClearLayer', title: '".lang::get('LANG_ClearTooltip')."'})];
				this.addControls(controls);
			},
			draw:function(){
				var div=OpenLayers.Control.Panel.prototype.draw.apply(this,arguments);
				this.activateControl(this.controls[0]);
				return div;
			},
	CLASS_NAME:\"MyEditingToolbar\"});
// a move may be associated with a zoom as well.
lastZoom=0;
reQuery = function(){
	lastZoom = jQuery('#map')[0].map.getZoom();
	if(jQuery('#results-collections-results').filter(':visible').length > 0)
		runSearch(true);
	else if(jQuery('#results-insects-results').filter(':visible').length > 0)
		runSearch(false);
};
moveEnd = function(){
	if(lastZoom == jQuery('#map')[0].map.getZoom()) reQuery();
};
loadFilter = function(){
    if(jQuery('#map').children().length == 0) {
		mapInitialisationHooks.push(function(mapdiv) {
			var editControl = new MyEditingToolbar(polygonLayer, {'displayClass':'olControlEditingToolbar'});
			mapdiv.map.addControl(editControl);
			editControl.activate();
			mapdiv.map.events.on({'zoomend': reQuery, 'dragend': reQuery, 'moveend': moveEnd});
		});
    	".$map1JS."
		mapInitialisationHooks=[];
		polygonLayer.map.searchLayer.events.register('featuresadded', {}, function(a1){
			// draw a square defining the bounds for the georeffed location.
			polygonLayer.destroyFeatures();
			if(inseeLayer != null) inseeLayer.destroyFeatures();
			var searchLayer = polygonLayer.map.searchLayer;
			var bounds=searchLayer.getDataExtent();
			var feature = new OpenLayers.Feature.Vector(bounds.toGeometry())
			polygonLayer.addFeatures([feature]);
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
	jQuery('#fo-express-doubt-form').hide();
	jQuery('#fo-new-insect-id-form [name=determination\\:comment]').val(\"".lang::get('LANG_Default_ID_Comment')."\");
	if(jQuery('#fo-new-insect-id-form').filter(':visible').length>0) {
	  jQuery('#fo-new-insect-id-form').hide();
	  if(!jQuery('#fo-id-history').hasClass('empty')){
		jQuery('#fo-id-history').addClass('ui-accordion-content-active');
		jQuery('#fo-id-buttons').removeClass('ui-corner-bottom');
	  }
	} else {
	  jQuery('#fo-new-insect-id-form').show();
	  if(!jQuery('#fo-id-history').hasClass('empty')){
		jQuery('#fo-id-history').removeClass('ui-accordion-content-active');
		jQuery('#fo-id-buttons').addClass('ui-corner-bottom');
	  }
	}
});
jQuery('#fo-new-flower-id-button').click(function(){ 
	jQuery('#fo-express-doubt-form').hide();
	jQuery('#fo-new-flower-id-form [name=determination\\:comment]').val(\"".lang::get('LANG_Default_ID_Comment')."\");
	if(jQuery('#fo-new-flower-id-form').filter(':visible').length>0) {
	  jQuery('#fo-new-flower-id-form').hide();
	  if(!jQuery('#fo-id-history').hasClass('empty')){
		jQuery('#fo-id-history').addClass('ui-accordion-content-active');
		jQuery('#fo-id-buttons').removeClass('ui-corner-bottom');
	  }
	} else {
	  jQuery('#fo-new-flower-id-form').show();
	  if(!jQuery('#fo-id-history').hasClass('empty')){
		jQuery('#fo-id-history').removeClass('ui-accordion-content-active');
		jQuery('#fo-id-buttons').addClass('ui-corner-bottom');
	  }
	}
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

$('#username').autocomplete([";
	/* warning Drupal specific code */
    $userList = array();
    $results = db_query('SELECT uid, name FROM {users} ORDER BY name');
    while($result = db_fetch_object($results)){
      $account = user_load($result->uid);
      if($account->uid != 1) // && user_access('IForm loctools node '.$node->nid.' user', $account)){
        $userList[] = '"'.str_replace('"','\\"',$account->name).'"';
    }
    data_entry_helper::$javascript .= implode(',',$userList)."]);\n";
    
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
