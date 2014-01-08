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
 * NB has Drupal specific code. Relies on presence of IForm loctools and IForm Proxy.
 *
 * @package	Client
 * @subpackage PrebuiltForms
 */

require_once('includes/map.php');
require_once('includes/language_utils.php');
require_once('includes/user.php');
  
class iform_pollenators {

	/* TODO
	 * Functionality shortfalls:
	 *	ID tool results storage.
	 * Functionality nice to haves:
	 *	Ajaxform error handling, also image loading and json. Also put in checks if setting data[] that we are setting the correct entries.
	 *	nsp on floral station - "do not know"
	 *	convert Image uploads to flash to give progress bar.
	 *	Autogenerate the name for the collection.
	 * Look and feel:
	 *	Confirm insect radio button attributes: validation, reset values after saving, restore values when selecting from photoreel.
	 *	find out if habitat descriptions are required.
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
//        array(
//          'name'=>'spatial_systems',
//          'caption'=>'Allowed Spatial Ref Systems',      
//          'description'=>'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code number such as 4326).',
//          'type'=>'string',
//          'group'=>'Map'
//        ),
      array(
      	'name'=>'survey_id',
        'caption'=>'Survey ID',
        'description'=>'The Indicia ID of the survey that data will be posted into.',
        'type'=>'int'
      ),
      array(
      	'name'=>'percent_insects',
        'caption'=>'Insect Identification Percentage',
        'description'=>'The percentage of insects that must be identified before the collection may be completed.',
        'type'=>'int'
      ),
      array(
      	'name'=>'gallery_node',
        'caption'=>'Gallery Node',
        'description'=>'The DRUPAL node number for the Gallery/Filter node.',
        'type'=>'int'
      ),
      array(
          'name'=>'protocol_attr_id',
          'caption'=>'Protocol Attribute ID',      
          'description'=>'Indicia ID for the sample attribute that stores the Protocol.',
          'type'=>'int',
          'group'=>'Collection Attributes'
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
          'name'=>'email_attr_id',
          'caption'=>'Email Attribute ID',      
          'description'=>'Indicia ID for the sample attribute that stores the user\'s email.',
          'type'=>'smpAttr',
          'group'=>'Collection Attributes'
        ),
      
      array(
          'name'=>'flower_list_id',
          'caption'=>'Flower Species List ID',
          'description'=>'The Indicia ID for the species list that flowers can be selected from.',
          'type'=>'int',
          'group'=>'Floral Station Attributes'
          ),
      array(
          'name'=>'location_picture_camera_attr_id',
          'caption'=>'Location Picture Camera Attribute ID',      
          'description'=>'Indicia ID for the location attribute that stores the Camera EXIF data for the location picture.',
          'type'=>'int',
          'group'=>'Floral Station Attributes'
            ),
      array(
          'name'=>'location_picture_datetime_attr_id',
          'caption'=>'Location Picture Datetime Attribute ID',      
          'description'=>'Indicia ID for the location attribute that stores the DateTime EXIF data for the location picture.',
          'type'=>'int',
          'group'=>'Floral Station Attributes'
      ),
      array(
          'name'=>'occurrence_picture_camera_attr_id',
          'caption'=>'Flower and Insect Picture Camera Attribute ID',      
          'description'=>'Indicia ID for the occurrence attribute that stores the Camera EXIF data for the flower and insect pictures.',
          'type'=>'int',
          'group'=>'Floral Station Attributes'
            ),
      array(
          'name'=>'occurrence_picture_datetime_attr_id',
          'caption'=>'Flower and Insect Picture Datetime Attribute ID',      
          'description'=>'Indicia ID for the occurrence attribute that stores the DateTime EXIF data for the flower and insect pictures.',
          'type'=>'int',
          'group'=>'Floral Station Attributes'
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
          'name'=>'distance_attr_id',
          'caption'=>'Distance Attribute ID',      
          'description'=>'Indicia ID for the location attribute that stores how far the nearest hive is.',
          'type'=>'int',
          'group'=>'Floral Station Attributes'
      ),
      array(
          'name'=>'within50m_attr_id',
          'caption'=>'within50m Attribute ID',      
          'description'=>'Indicia ID for the location attribute that describes whether the location is within 50m of a grand culture.',
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
          ),
          
      array(
          'name'=>'insect_list_id',
          'caption'=>'Insect Species List ID',
          'description'=>'The Indicia ID for the species list that insects can be selected from.',
          'type'=>'int',
          'group'=>'Insect Attributes'
          ),
      array(
          'name'=>'number_attr_id',
          'caption'=>'Insect Number Attribute ID',
          'description'=>'The Indicia ID for the occurrence attribute that stores the number of insects.',
          'type'=>'int',
          'group'=>'Insect Attributes'
      	),
      array(
          'name'=>'foraging_attr_id',
          'caption'=>'Foraging Attribute ID',
          'description'=>'The Indicia ID for the occurrence attribute that stores the foraging flag.',
          'type'=>'int',
          'group'=>'Insect Attributes'
      ),

      array(
          'name'=>'help_module',
          'caption'=>'Help DRUPAL Module',
          'description'=>'The DRUPAL module which contains the context sensitive help functionality.',
          'type'=>'string',
          'group'=>'Help',
      	  'required'=>false
      ),
      array(
          'name'=>'help_inclusion_function',
          'caption'=>'Help Module inclusion function',
          'description'=>'The DRUPAL PHP function which is used to include the relevant Javascript into the page.',
          'type'=>'string',
          'group'=>'Help',
      	  'required'=>false
      ),
      array(
          'name'=>'help_function',
          'caption'=>'Help Module invocation function',
          'description'=>'The Javascript function which is called when the help buttons are clicked.',
          'type'=>'string',
          'group'=>'Help',
      	  'required'=>false
      ),
      array(
          'name'=>'help_collection_arg',
          'caption'=>'Collection Help argument',
          'description'=>'The argument(s) passed to the Help Module invocation function when the help button in "Create a collection" is clicked.',
          'type'=>'string',
          'group'=>'Help',
      	  'required'=>false
      ),
      array(
          'name'=>'help_flower_arg',
          'caption'=>'Flower Identification Help argument',
          'description'=>'The argument(s) passed to the Help Module invocation function when the help button in "Flower Identification" is clicked.',
          'type'=>'string',
          'group'=>'Help',
      	  'required'=>false
      ),
      array(
          'name'=>'help_environment_arg',
          'caption'=>'Environment Identification Help argument',
          'description'=>'The argument(s) passed to the Help Module invocation function when the help button in "Environment" is clicked.',
          'type'=>'string',
          'group'=>'Help',
      	  'required'=>false
      ),
      array(
          'name'=>'help_location_arg',
          'caption'=>'Location Help argument',
          'description'=>'The argument(s) passed to the Help Module invocation function when the help button in "Location" is clicked.',
          'type'=>'string',
          'group'=>'Help',
      	  'required'=>false
      ),
      array(
          'name'=>'help_session_arg',
          'caption'=>'Session Help argument',
          'description'=>'The argument(s) passed to the Help Module invocation function when the help button in "Session" is clicked.',
          'type'=>'string',
          'group'=>'Help',
      	  'required'=>false
      ),
      array(
          'name'=>'help_insect_arg',
          'caption'=>'Insect Identification Help argument',
          'description'=>'The argument(s) passed to the Help Module invocation function when the help button in "Insect Identification" is clicked.',
          'type'=>'string',
          'group'=>'Help',
      	  'required'=>false
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
      )

      
      ) 
    );
    return $retVal;
  	
  }

  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_pollenators_definition() {
    return array(
      'title'=>self::get_title(),
      'category' => 'SPIPOLL forms',      
      'description'=>'Pollenators: Data Entry.'
    );
  }

  /**
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'Pollenators Data Entry';
  }

  public static function get_perms($nid, $args) {
    return array('IForm n'.$nid.' access');
  }
  
  private function help_button($use_help, $id, $func, $arg) {
  	if($use_help == false) return '';
  	data_entry_helper::$javascript .= "
jQuery('#".$id."').click(function(){
	".$func."(".$arg.");
});
";
  	return '<div id="'.$id.'" class="right ui-state-default ui-corner-all help-button">'.lang::get('LANG_Help_Button').'</div>';
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
	if($args['language'] != 'en')
		data_entry_helper::add_resource('jquery_ui_'.$args['language']);
	data_entry_helper::enable_validation('cc-1-collection-details'); // don't care about ID itself, just want resources

	if($args['help_module'] != '' && $args['help_inclusion_function'] != '' && module_exists($args['help_module']) && function_exists($args['help_inclusion_function'])) {
    	$use_help = true;
    	data_entry_helper::$javascript .= call_user_func($args['help_inclusion_function']);
    } else {
    	$use_help = false;
    }

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
    $defNRAttrOptions = array('extraParams'=>$readAuth+array('orderby'=>'id'),
    				'lookUpListCtrl' => 'radio_group',
    				'lookUpKey' => 'meaning_id',
    				'language' => iform_lang_iso_639_2($args['language']),
    				'booleanCtrl' => 'radio', // default has changed
    				'containerClass' => 'group-control-box',
       				'sep' => ' &nbsp; ',
    				'suffixTemplate'=>'nosuffix');
    $defAttrOptions=$defNRAttrOptions;
    $defAttrOptions ['validation'] = array('required');
    $checkOptions = $defNRAttrOptions;
    $checkOptions['lookUpListCtrl'] = 'checkbox_group';
    $language = iform_lang_iso_639_2($args['language']);
    global $indicia_templates;
	$indicia_templates['sref_textbox_latlong'] = '<div class="latLongDiv"><label for="{idLat}">{labelLat}:</label>'.
        '<input type="text" id="{idLat}" name="{fieldnameLat}" {class} {disabled} value="{default}" /></div>' .
        '<div class="latLongDiv"><label for="{idLong}">{labelLong}:</label>'.
        '<input type="text" id="{idLong}" name="{fieldnameLong}" {class} {disabled} value="{default}" /></div>';
    $base = base_path();
    if(substr($base, -1)!='/') $base.='/';
	$r .= '<script type="text/javascript">
/* <![CDATA[ */
document.write("<div class=\"ui-widget ui-widget-content ui-corner-all loading-panel\" ><img src=\"'.$base.drupal_get_path('module', 'iform').'/media/images/ajax-loader2.gif\" />'.lang::get('loading').'...<span class=\"poll-loading-extras\">0</span></div>");
document.write("<div class=\"poll-loading-hide\">");
/* ]]> */</script>
';
    data_entry_helper::$javascript .= "var flowerTaxa = [";
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
    
    // note we have to proxy the post. Every time a write transaction is carried out, the write nonce is trashed.
	// For security reasons we don't want to give the user the ability to generate their own nonce, so we use
	// the fact that the user is logged in to drupal as the main authentication/authorisation/identification
	// process for the user. The proxy packages the post into the correct format
	// 
    // There are 2 types of submission:
    // When a user validates a panel using the validate button, the following panel is opened on success
    // When a user presses a modify button, the open panel gets validated, and the panel to be modified is opened.
	// loadAttribute
    // <form id="cc-1-collection-details"
    // has the main sample (+attributes), location (no attributes).
    // form id="cc-1-delete-collection" just has the main sample.
    // form id="cc-2-flower-upload" just uploads the flower picture: no DB
    // form id="cc-2-environment-upload" just uploads the location picture: no DB
    // form id="cc-2-floral-station"
    // has the location (+attributes), location_image, main sample (no attributes), flower occurrence (+attributes), determination, flower_image
    // form id="cc-3-delete-session" just has the session sample.
    // form class=\"poll-session-form\" has the session (+attributes) 
    // form id="cc-4-insect-upload" just uploads the insect picture: no DB
    // form id="cc-4-main-form"
    // has the insect occurrence (+attributes), determination, insect image
    // form id="cc-4-delete-insect" just has the insect occurrence.
    // form id="cc-5-collection" has the main sample and closed attribute (forced to 1).
    $r .= '
<div id="refresh-message" style="display:none" ><p>'.lang::get('LANG_Please_Refresh_Page').'</p></div>
<div id="cc-1" class="poll-section">
  <div id="cc-1-title" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top poll-section-title">
  	<span id="cc-1-title-details">'.lang::get('LANG_Collection_Details').'</span>
    <div class="right">
      <div>
        <span id="cc-1-reinit-button" class="ui-state-default ui-corner-all reinit-button">'.lang::get('LANG_Reinitialise').'</span>
        <span id="cc-1-mod-button" class="ui-state-default ui-corner-all mod-button">'.lang::get('LANG_Modify').'</span>
      </div>
    </div>
  </div>
  <div id="cc-1-details" class="ui-accordion-content ui-helper-reset ui-widget-content">
    <span id="cc-1-protocol-details"></span>
  </div>
  <div id="cc-1-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active poll-section-body">
   <form id="cc-1-collection-details" action="'.iform_ajaxproxy_url($node, 'loc-sample').'" method="POST">
    <input type="hidden" id="website_id"       name="website_id" value="'.$args['website_id'].'" />
    <input type="hidden" id="imp-sref"         name="location:centroid_sref"  value="" />
    <input type="hidden" id="imp-geom"         name="location:centroid_geom" value="" />
    <input type="hidden" id="X-sref-system"  name="location:centroid_sref_system" value="900913" />
    <input type="hidden" id="sample:survey_id" name="sample:survey_id" value="'.$args['survey_id'].'" />
    '.iform_pollenators::help_button($use_help, "collection-help-button", $args['help_function'], $args['help_collection_arg']).'
    <label for="location:name">'.lang::get('LANG_Collection_Name_Label').':</label>
 	<input type="text" id="location:name"      name="location:name" value="" class="required"/>
    <input type="hidden" id="sample:location_name" name="sample:location_name" value=""/>
 	'.str_replace("\n", "", data_entry_helper::outputAttribute($sample_attributes[$args['protocol_attr_id']], $defNRAttrOptions))
 	.'    <input type="hidden"                       name="sample:date" value="2010-01-01"/>
    <input type="hidden" id="smpAttr:'.$args['complete_attr_id'].'" name="smpAttr:'.$args['complete_attr_id'].'" value="0" />
    <input type="hidden" id="smpAttr:'.$args['uid_attr_id'].'" name="smpAttr:'.$args['uid_attr_id'].'" value="'.$uid.'" />
    <input type="hidden" id="smpAttr:'.$args['email_attr_id'].'" name="smpAttr:'.$args['email_attr_id'].'" value="'.$email.'" />
    <input type="hidden" id="smpAttr:'.$args['username_attr_id'].'" name="smpAttr:'.$args['username_attr_id'].'" value="'.$username.'" />  
    <input type="hidden" id="locations_website:website_id" name="locations_website:website_id" value="'.$args['website_id'].'" />
    <input type="hidden" id="location:id"      name="location:id" value="" disabled="disabled" />
    <input type="hidden" id="sample:id"        name="sample:id" value="" disabled="disabled" />
    </form>
    <div class="button-container">
      <div id="cc-1-valid-button" class="ui-state-default ui-corner-all save-button">'.lang::get('LANG_Validate').'</div>
    </div>
  </div>
  <div id="cc-1-trailer" class="poll-section-trailer">
    <div id="cc-1-trailer-image" ><img src="'.$base.drupal_get_path('module', 'iform').'/media/images/exclamation.jpg" /></div>
    <p>'.lang::get('LANG_Collection_Trailer_Point_1').'</p>
    <p>'.lang::get('LANG_Collection_Trailer_Point_2').'</p>
  </div>
<div style="display:none" />
    <form id="cc-1-delete-collection" action="'.iform_ajaxproxy_url($node, 'sample').'" method="POST">
       <input type="hidden" name="website_id" value="'.$args['website_id'].'" />
       <input type="hidden" name="sample:survey_id" value="'.$args['survey_id'].'" />
       <input type="hidden" name="sample:id" value="" />
       <input type="hidden" name="sample:date" value="2010-01-01"/>
       <input type="hidden" name="sample:location_id" value="" />
       <input type="hidden" name="sample:deleted" value="t" />
    </form>
</div>
  <div id="cc-1-main-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">
';

    data_entry_helper::$javascript .= "
$.validator.messages.required = \"".lang::get('validation_required')."\";
var sessionCounter = 0;
jQuery('#imp-georef-search-btn').removeClass('indicia-button').addClass('search-button');
jQuery('.poll-loading-hide').hide();
// can't use shuffle to side as dynamic generated code does like it in IE7

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

$.fn.foldPanel = function(){
	this.children('.poll-section-body,.poll-section-footer,.poll-section-trailer').hide();
	this.children('.poll-section-title').find('.reinit-button,.mod-button').show();
	this.children('.photoReelContainer').addClass('ui-corner-all').removeClass('ui-corner-top'); /* visibility depends on specific circumstances */
};

$.fn.unFoldPanel = function(){
	this.children('.poll-section-body,.poll-section-footer,.poll-section-trailer,.photoReelContainer').show();
	this.children('.poll-section-title').find('.mod-button').hide();
	this.children('.photoReelContainer').addClass('ui-corner-top').removeClass('ui-corner-all');
	window.scroll(0,0); // force the window to display the top.
	buildMap();
	checkSessionButtons();
	// any reinit button is left in place
};

$.fn.showPanel = function(){
	this.show();
	this.unFoldPanel();
};

$.fn.hidePanel = function(){
	this.hide(); 
};

inseeLayer = null;

newDefaultSref = '0, 0';
oldDefaultSref = '".
    		((int)$args['map_centroid_lat'] > 0 ? $args['map_centroid_lat'].'N' : (-((int)$args['map_centroid_lat'])).'S').' '.
    		((int)$args['map_centroid_long'] > 0 ? $args['map_centroid_long'].'E' : (-((int)$args['map_centroid_long'])).'W')."';
defaultGeom = '';
$.getJSON('".$svcUrl."' + '/spatial/sref_to_wkt'+
        			'?sref=' + newDefaultSref +
          			'&system=' + jQuery('#imp-sref-system').val() +
          			'&callback=?', function(data) {
            	defaultGeom = data.wkt;
                   	});

$.fn.resetPanel = function(){
	this.find('.poll-section-body').show();
	this.find('.poll-section-footer,.poll-section-trailer').show();
	this.find('.reinit-button').show();
	this.find('.mod-button').show();
	this.find('.poll-image').empty();
	this.find('.poll-session').remove();
	this.find('.inline-error').remove();
	this.find('#imp-georef-search').val('');
	this.find('#imp-georef-div').hide();
	this.find('#imp-georef-output-div').empty();
	this.find('[name=place\\:INSEE]').val('".lang::get('LANG_INSEE')."');
	this.find('#imp-sref-lat').val('');
	this.find('#imp-sref-long').val('');
	this.find('#X-sref-system').val('900913'); //note only one of these in cc-1, distinct from location:centroid_sref_system. This indicates no geolocation loaded.
	// TODO Map
	this.find('.thumb').not('.blankPhoto').remove();
	this.find('.blankPhoto').addClass('currentPhoto');
	
	// resetForm does not reset the hidden fields. record_status, website_id and survey_id are not altered so do not reset.
	// hidden Attributes generally hold unchanging data, but the name needs to be reset (does it for non hidden as well).
	// hidden location:name are set in code anyway.
	this.find('.poll-dummy-form input').val('');
	this.find('.poll-dummy-form input').removeAttr('checked');
	this.find('.poll-dummy-form select').val('');
	this.find('.poll-dummy-form textarea').val('');
	this.find('.poll-dummy-form').find('[name$=\\:determination_type]').val('A');
	this.find('form').each(function(){
		jQuery(this).resetForm();
		jQuery(this).find('[name=sample\\:location_name],[name=location_image\\:path],[name=occurrence_image\\:path]').val('');
		jQuery(this).filter('#cc-1-collection-details').find('[name=sample\\:id],[name=location\\:id]').val('').attr('disabled', 'disabled');
		jQuery(this).find('[name=location_image\\:id],[name=occurrence\\:id],[name=determination\\:id],[name=occurrence_image\\:id]').val('').attr('disabled', 'disabled');
		jQuery(this).find('[name=sample\\:date]:hidden').val('2010-01-01');
		jQuery(this).find('input[name=locations_website\\:website_id]').removeAttr('disabled');
		jQuery(this).find('[name=locAttr\\:".$args['location_picture_camera_attr_id']."],[name^=locAttr\\:".$args['location_picture_camera_attr_id']."\\:],[name=locAttr\\:".$args['location_picture_datetime_attr_id']."],[name^=locAttr\\:".$args['location_picture_datetime_attr_id']."\\:],[name=occAttr\\:".$args['occurrence_picture_camera_attr_id']."],[name^=occAttr\\:".$args['occurrence_picture_camera_attr_id']."\\:],[name=occAttr\\:".$args['occurrence_picture_datetime_attr_id']."],[name^=occAttr\\:".$args['occurrence_picture_datetime_attr_id']."\\:]').val('');
		jQuery(this).find('[name^=smpAttr\\:],[name^=locAttr\\:],[name^=occAttr\\:]').filter('.multiselect').remove();
		jQuery(this).find('[name^=smpAttr\\:],[name^=locAttr\\:],[name^=occAttr\\:]').each(function(){
			var name = jQuery(this).attr('name').split(':');
			if(name[1].indexOf('[]') > 0) name[1] = name[1].substr(0, name[1].indexOf('[]'));
			jQuery(this).attr('name', name[0]+':'+name[1]);
		});
		jQuery(this).find('[name^=smpAttr\\:],[name^=locAttr\\:],[name^=occAttr\\:]').filter(':checkbox').removeAttr('checked').each(function(){
			var name = jQuery(this).attr('name').split(':');
			var similar = jQuery('[name='+name[0]+'\\:'+name[1]+'],[name='+name[0]+'\\:'+name[1]+'\\[\\]]').filter(':checkbox');
			if(similar.length > 1)
				jQuery(this).attr('name', name[0]+':'+name[1]+'[]');
		});
		jQuery(this).find('input[name=location\\:centroid_sref]').val('');
		jQuery(this).find('input[name=location\\:centroid_geom]').val('');
    });	
  };

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
	$('.loading-panel').remove();
	$('.poll-loading-hide').show();
	jQuery('#cc-1').hide();
	jQuery('#refresh-message').show();
	throw('WAREHOUSE ERROR');
};
			
checkProtocolStatus = function(display){
  	var checkedProtocol = jQuery('[name=smpAttr\\:".$args['protocol_attr_id']."],[name^=smpAttr\\:".$args['protocol_attr_id']."\\:]').filter('[checked]').parent();
    if(jQuery('[name=location\\:name]').val() != '' && checkedProtocol.length > 0) {
        jQuery('#cc-1-title-details').empty().text(jQuery('#cc-1-collection-details input[name=location\\:name]:first').val());
        firstBracket = checkedProtocol.find('label')[0].innerHTML.indexOf('(');
        secondBracket = checkedProtocol.find('label')[0].innerHTML.lastIndexOf(')');
        jQuery('#cc-1-protocol-details').empty().show().html('<strong>".lang::get('LANG_Protocol_Title_Label')."</strong> : <span class=\"protocol-head\">' +
                  checkedProtocol.find('label')[0].innerHTML.slice(0, firstBracket-1) +
                  '</span><span class=\"protocol-description\"> | ' +
                  checkedProtocol.find('label')[0].innerHTML.slice(firstBracket+1, secondBracket) + '</span>');
    } else {
        jQuery('#cc-1-title-details').empty().text(\"".lang::get('LANG_Collection_Details')."\");
        // TODO autogenerate a name
        jQuery('#cc-1-protocol-details').empty().hide();
    }
    if(display == true){
      jQuery('#cc-1-details').addClass('ui-accordian-content-active');
    } else if(display == false){
      jQuery('#cc-1-details').removeClass('ui-accordian-content-active');
    } // anything else just leave
};
checkForagingStatus = function(setForagingConfirm){
	jQuery('[name=occAttr\\:".$args['foraging_attr_id']."],[name^=occAttr\\:".$args['foraging_attr_id'].":]').filter('[checked]').each(function(index, elem){
		if(elem.value==1){ // need to allow string 1 comparison so no ===
			jQuery('#Foraging_Confirm').show();
			if(setForagingConfirm)
				jQuery('[name=dummy_foraging_confirm]').filter('[value=1]').attr('checked',true);
		} else
			jQuery('#Foraging_Confirm').hide();
	});
};
checkSessionButtons = function(){
	if (jQuery('#cc-3-body').children().length === 1) {
	    jQuery('#cc-3').find('.delete-button').hide();
	    jQuery('#cc-3-valid-button').empty().text(\"".lang::get('LANG_Validate_Session')."\")
  	} else {
		jQuery('#cc-3').find('.delete-button').show();
	    jQuery('#cc-3-valid-button').empty().text(\"".lang::get('LANG_Validate_Session_Plural')."\")
  	}
	if(jQuery('[name=smpAttr\\:".$args['protocol_attr_id']."],[name^=smpAttr\\:".$args['protocol_attr_id']."\\:]').filter(':first').filter('[checked]').length >0){
	    jQuery('#cc-3-title-title').empty().text(\"".lang::get('LANG_Sessions_Title')."\");
		jQuery('#cc-3').find('.add-button').hide();
	} else {
	    jQuery('#cc-3-title-title').empty().text(\"".lang::get('LANG_Sessions_Title_Plural')."\");
		jQuery('#cc-3').find('.add-button').show();
  	}
};

showStationPanel = true;

// The validate functionality for each panel is sufficiently different that we can't generalise a function
// this is the one called when we don't want the panel following to be opened automatically.
validateCollectionPanel = function(){
	clearErrors('form#cc-1-collection-details');
	if(jQuery('#cc-1-body:visible').length == 0) return true; // body hidden so data already been validated successfully.
	if(!jQuery('#cc-1-body').find('form > input').valid()){
		myScrollToError();
		return false;
  	}
	// no need to check protocol - if we are this far, we've already filled it in.
  	showStationPanel = false;
	jQuery('#cc-1-collection-details').submit();
	return true;
  };

errorPos = null;
clearErrors = function(formSel) {
	jQuery(formSel).find('.inline-error').remove();
	errorPos = null;
};
myScrollTo = function(selector){
	jQuery(selector).filter(':visible').each(function(){
		if(errorPos == null || jQuery(this).offset().top < errorPos){
			errorPos = jQuery(this).offset().top;
			window.scroll(0,errorPos);
		}
	});
};
myScrollToError = function(){
	jQuery('.inline-error,.error').filter(':visible').prev().each(function(){
		if(errorPos == null || jQuery(this).offset().top < errorPos){
			errorPos = jQuery(this).offset().top;
			window.scroll(0,errorPos);
		}
	});
};

validateRadio = function(name, formSel){
    var controls = jQuery(formSel).find('[name='+name+'],[name^='+name+'\\:]');
    if(controls.filter('[checked]').length < 1) {
        var label = $('<p/>')
				.attr({'for': name})
				.addClass('inline-error')
				.html($.validator.messages.required);
		label.insertBefore(controls.filter(':first').parent());
		return false;
    }
    return true;
}

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

validateOptInt = function(name, formSel){
	var control = jQuery(formSel).find('[name='+name+'],[name^='+name+'\\:]');
	var ctrvalue = control.val();
	var OK = true;
	if(ctrvalue == '') return true;
	for (i = 0 ; i < ctrvalue.length ; i++) {
		if ((ctrvalue.charAt(i) < '0') || (ctrvalue.charAt(i) > '9')) OK = false
	}
	if(OK) return OK;
	var label = $('<p/>')
				.attr({'for': name})
				.addClass('inline-error')
				.html(\"".lang::get('validation_integer')."\");
	label.insertBefore(control);
	return false;
}

insertImage = function(path, target, ratio){
	var img = new Image();
	jQuery(img).load(function () {
        target.removeClass('loading').append(this);
        if(this.width/this.height > ratio){
	    	jQuery(this).css('width', '100%').css('height', 'auto').css('vertical-align', 'middle').css('margin-left', 'auto').css('margin-right', 'auto').css('display', 'block');
  		} else {
	        jQuery(this).css('width', (100*this.width/(this.height*ratio))+'%').css('height', 'auto').css('vertical-align', 'middle').css('margin-left', 'auto').css('margin-right', 'auto').css('display', 'block');
  		}
	}).attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."'+path);
}
				    
$('#cc-1').ajaxError(function(event, request, settings){
	var insectURL = insectIDstruc.pollURL+insectIDstruc.pollFile;
	var flowerURL = flowerIDstruc.pollURL+flowerIDstruc.pollFile;
	if(settings.url != flowerURL && settings.url != insectURL){ // these urls may not be present.
		alert(\"".lang::get('ajax_error')."\" + '\\n' + settings.url + '\\n' + request.status + ' ' + request.statusText + '\\n' + \"".lang::get('ajax_error_bumpf')."\");
		// unknown data state so prevent further use of the form:
		$('.loading-panel').remove();
		$('.poll-loading-hide').show();
		jQuery('#cc-1').hide();
		jQuery('#refresh-message').show();
		throw('AJAX ERROR');
	}
});
 
validateTime = function(name, formSel){
    var control = jQuery(formSel).find('[name='+name+'],[name^='+name+'\\:]');
    if(control.val().match(/^(2[0-3]|[0,1][0-9]):[0-5][0-9]$/) == null) {
        var label = $('<p/>')
				.attr({'for': name})
				.addClass('inline-error')
				.html('".lang::get('validation_time')."');
		label.insertBefore(control);
		return false;
    }
    return true;
}

$('#cc-1-collection-details').ajaxForm({
		async: false,
        dataType:  'json', 
        beforeSubmit:   function(data, obj, options){
        	// if location id filled in but sample id is not -> error
        	if(data.length == 15 && data[14].value == ''){
        		alertIndiciaError({error : \"".lang::get('Internal Error 1: sample id not filled in, so not safe to save collection')."\"});
				return false;
			}
        	clearErrors('form#cc-1-collection-details');
        	var valid = true;
        	if (!jQuery('form#cc-1-collection-details > input').valid()) { valid = false; }
        	if (!validateRadio('smpAttr\\:".$args['protocol_attr_id']."', 'form#cc-1-collection-details')) { valid = false; }
	       	if ( valid == false ) {
				myScrollToError();
				return false;
  			};
  			// Warning this assumes that:
  			// 1) the location:name is the sixth field in the form.
  			// 1) the sample:location_name is the seventh field in the form.
  			data[6].value = data[5].value;
  			if(data[3].value=='900913'){
  				data[1].value=newDefaultSref;
  				data[2].value=defaultGeom;
  			}
  			jQuery('#cc-2-floral-station > input[name=location\\:name]').val(data[5].value);
  			jQuery('#cc-1-valid-button').addClass('loading-button');
        	return true;
  		},
        success:   function(data){
        	if(data.success == 'multiple records' && data.outer_table == 'location'){
        	    jQuery('[name=location\\:id],[name=sample\\:location_id]').removeAttr('disabled').val(data.outer_id);
        	    jQuery('[name=locations_website\\:website_id]').attr('disabled', 'disabled');
        	    // data.struct.children[0] holds the details of the sample record.
				jQuery('#cc-6-consult-collection').attr('href', '".url('node/'.$args['gallery_node'])."'+'?collection_id='+data.struct.children[0].id);
				jQuery('#cc-1-collection-details,#cc-2-floral-station,#cc-1-delete-collection').find('[name=sample\\:id]').removeAttr('disabled').val(data.struct.children[0].id);
				// In this case we use loadAttributes to set the names of the attributes to include the attribute_value id.
				// cant use the struct as it can't tell which attribute is which. 
				loadAttributes('#cc-1-collection-details,#cc-5-collection', 'sample_attribute_value', 'sample_attribute_id', 'sample_id', data.struct.children[0].id, 'smpAttr', true, true);
			   	checkProtocolStatus(true);
        		$('#cc-1').foldPanel();
    			if(showStationPanel){ $('#cc-2').showPanel(); }
		    	showStationPanel = true;
        	}  else 
				alertIndiciaError(data);
        },
        complete: function (){
  			jQuery('.loading-button').removeClass('loading-button');
  		}
});

$('#cc-1-delete-collection').ajaxForm({ 
		async: false,
		dataType:  'json', 
        beforeSubmit:   function(data, obj, options){
  			// Warning this assumes that the data is fixed position:
       		data[3].value = jQuery('#cc-1-collection-details input[name=sample\\:date]').val();
        	if(data[2].value == '') return false;
			if(data[4].value == ''){ // double check that location id is filled in
				alertIndiciaError({error : \"".lang::get('Internal Error 3: location id not set, so unsafe to delete collection.')."\"});
				return false;
			}
  			jQuery('#cc-1-reinit-button').addClass('loading-button');
        	return true;
  		},
        success:   function(data){
        	if(data.success == 'multiple records' && data.outer_table == 'sample'){
        		jQuery('#cc-3-body').empty();
        		pollReset(flowerIDstruc);
        		pollReset(insectIDstruc);
	        	jQuery('.poll-section').resetPanel();
				sessionCounter = 0;
				addSession();
				checkProtocolStatus(false);
				jQuery('.poll-section').hidePanel();
				jQuery('.poll-image').empty();
				jQuery('#cc-1').showPanel();
				jQuery('.reinit-button').hide();
				if(jQuery('#map').children().length > 0) {
					var div = jQuery('#map')[0];
					div.map.editLayer.destroyFeatures();
					div.map.searchLayer.destroyFeatures();
					if(inseeLayer != null) inseeLayer.destroyFeatures();
					jQuery('#cc-2-loc-description').empty();
					var center = new OpenLayers.LonLat(".$args['map_centroid_long'].", ".$args['map_centroid_lat'].");
					center.transform(div.map.displayProjection, div.map.projection);
					div.map.setCenter(center, ".((int) $args['map_zoom']).");
				}
        	}  else 
				alertIndiciaError(data);
  		},
        complete: function (){
  			jQuery('.loading-button').removeClass('loading-button');
  		}
});

$('#cc-1-valid-button').click(function() {
	jQuery('#cc-1-collection-details').submit();
});

$('#cc-1-reinit-button').click(function() {
    clearErrors('form#cc-1-collection-details');
	if(jQuery('form#cc-1-collection-details > input[name=sample\\:id]').filter('[disabled]').length > 0) { return } // sample id is disabled, so no data has been saved - do nothing.
    if (!jQuery('form#cc-1-collection-details > input').valid()) {
    	myScrollToError();
    	alert(\"".lang::get('LANG_Unable_To_Reinit')."\");
        return ;
  	}
	if(confirm(\"".lang::get('LANG_Confirm_Reinit')."\")){
		jQuery('#cc-1-delete-collection').submit();
	}
});

";

 	// Flower Station section.

    $options = iform_map_get_map_options($args, $readAuth);
    $olOptions = iform_map_get_ol_options($args);
    // The maps internal projection will be left at its default of 900913.
    $options['searchLayer'] = 'true';
    $options['initialFeatureWkt'] = null;
    $options['proxy'] = '';
    // Switch to degrees, minutes, seconds for lat long.
    $options['latLongFormat'] = 'DMS';
    $options['suffixTemplate'] = 'nosuffix';
    if( lang::get('msgGeorefSelectPlace') != 'msgGeorefSelectPlace')
    	$options['msgGeorefSelectPlace'] = lang::get('msgGeorefSelectPlace');
    if( lang::get('msgGeorefNothingFound') != 'msgGeorefNothingFound')
    	$options['msgGeorefNothingFound'] = lang::get('msgGeorefNothingFound');
    
    $extraParams = $readAuth + array('taxon_list_id' => $args['flower_list_id'], 'view'=>'detail','orderby'=>'taxonomic_sort_order', 'allow_data_entry'=>'t');
    $species_ctrl_args=array(
    	    'label'=>lang::get('LANG_Flower_Species'),
        	'fieldname'=>'flower:taxa_taxon_list_id',
	        'table'=>'taxa_taxon_list',
    	    'captionField'=>'taxon',
        	'listCaptionSpecialChars'=>true,
    	    'valueField'=>'id',
	        'columns'=>2,
    		'blankText'=>lang::get('LANG_Choose_Taxon'),
    	    'extraParams'=>$extraParams,
    		'suffixTemplate'=>'nosuffix'
	);
    
    $r .= '
<div id="cc-2" class="poll-section">
  <div id="cc-2-title" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-all poll-section-title"><span>'.lang::get('LANG_Flower_Station').'</span>
    <div class="right">
      <span id="cc-2-mod-button" class="ui-state-default ui-corner-all mod-button">'.lang::get('LANG_Modify').'</span>
    </div>
  </div>
  <div id="cc-2-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-top ui-accordion-content-active poll-section-body">
    <div id="cc-2-flower" >
	  <div id="cc-2-flower-title">'.lang::get('LANG_Upload_Flower').'</div>
	  <form id="cc-2-flower-upload" enctype="multipart/form-data" action="'.iform_ajaxproxy_url($node, 'media').'" method="POST">
    		<input type="hidden" id="website_id" name="website_id" value="'.$args['website_id'].'" />
    		<input name="upload_file" type="file" class="required" />
     		<input type="submit" value="'.lang::get('LANG_Upload').'" class="btn-submit" />
 	  		<div id="cc-2-flower-image" class="poll-image"></div>
      </form>
 	  <div id="cc-2-flower-identify" class="poll-dummy-form">
 	    <div class="id-tool-group">
          '.iform_pollenators::help_button($use_help, "flower-help-button", $args['help_function'], $args['help_flower_arg']).'
		  <p><strong>'.lang::get('LANG_Identify_Flower').'</strong></p>
          <input type="hidden" id="flower:taxon_details" name="flower:taxon_details" />
          <input type="hidden" name="flower:determination_type" value="A" />  
   	      <label for="flower-id-button">'.lang::get('LANG_Flower_ID_Key_label').' :</label><span id="flower-id-button" class="ui-state-default ui-corner-all poll-id-button" >'.lang::get('LANG_Launch_ID_Key').'</span>
		  <span id="flower-id-cancel" class="ui-state-default ui-corner-all poll-id-cancel" >'.lang::get('LANG_Cancel_ID').'</span>
 	      <p id="flower_taxa_list"></p>
 	    </div>
 	    <div class="id-later-group">
 	      <label for="id-flower-later" class="follow-on">'.lang::get('LANG_ID_Flower_Later').' </label><input type="checkbox" id="id-flower-later" name="id-flower-later" /> 
 	    </div>
 	    <div class="id-specified-group">
 	      '.data_entry_helper::select($species_ctrl_args).'
          <label for="flower:taxon_extra_info" class="follow-on">'.lang::get('LANG_ID_More_Precise').' </label> 
          <input type="text" id="flower:taxon_extra_info" name="flower:taxon_extra_info" class="taxon-info" />
        </div>
      </div>
      <div class="id-comment">
        <label for="flower:comment" >'.lang::get('LANG_ID_Comment').' </label>
        <textarea id="flower:comment" name="flower:comment" class="taxon-comment" rows="3" ></textarea>
      </div>
    </div>
    <div class="poll-break"></div>
 	<div id="cc-2-environment">
	  '.iform_pollenators::help_button($use_help, "environment-help-button", $args['help_function'], $args['help_environment_arg']).'
	  <div id="cc-2-environment-title">'.lang::get('LANG_Upload_Environment').'</div>
 	  <form id="cc-2-environment-upload" enctype="multipart/form-data" action="'.iform_ajaxproxy_url($node, 'media').'" method="POST">
    	<input type="hidden" id="website_id" name="website_id" value="'.$args['website_id'].'" />
    	<input name="upload_file" type="file" class="required" />
    	<input type="submit" value="'.lang::get('LANG_Upload').'" class="btn-submit" />
 	  	<div id="cc-2-environment-image" class="poll-image"></div>
      </form>
 	</div>
 	<form id="cc-2-floral-station" action="'.iform_ajaxproxy_url($node, 'loc-smp-occ').'" method="POST">
      <input type="hidden" id="website_id" name="website_id" value="'.$args['website_id'].'" />
      <input type="hidden" id="location:id" name="location:id" value="" />
      <input type="hidden" id="location:name" name="location:name" value=""/>
      <input type="hidden" name="location:centroid_sref" />
      <input type="hidden" name="location:centroid_geom" />
      <input type="hidden" id="imp-sref-system" name="location:centroid_sref_system" value="4326" />
      <input type="hidden" id="location_image:path" name="location_image:path" value="" />
      <input type="hidden" id="location_picture_camera_attr" name="locAttr:'.$args['location_picture_camera_attr_id'].'" value="" />
      <input type="hidden" id="location_picture_datetime_attr" name="locAttr:'.$args['location_picture_datetime_attr_id'].'" value="" />
      <input type="hidden" id="sample:survey_id" name="sample:survey_id" value="'.$args['survey_id'].'" />
      <input type="hidden" id="sample:id" name="sample:id" value=""/>
      <input type="hidden" name="sample:date" value="2010-01-01"/>
      <input type="hidden" name="determination:taxa_taxon_list_id" value=""/>  
      <input type="hidden" name="determination:taxon_details" value=""/>  
      <input type="hidden" name="determination:taxon_extra_info" value=""/>  
      <input type="hidden" name="determination:comment" value=""/>  
      <input type="hidden" name="determination:determination_type" value="A" />  
      <input type="hidden" name="determination:cms_ref" value="'.$uid.'" />
      <input type="hidden" name="determination:email_address" value="'.$email.'" />
      <input type="hidden" name="determination:person_name" value="'.$username.'" />  
      <input type="hidden" name="occurrence:use_determination" value="Y"/>    
      <input type="hidden" name="occurrence:record_status" value="C" />
      <input type="hidden" id="location_image:id" name="location_image:id" value="" disabled="disabled" />
      <input type="hidden" id="occurrence:id" name="occurrence:id" value="" disabled="disabled" />
      <input type="hidden" id="determination:id" name="determination:id" value="" disabled="disabled" />
      <input type="hidden" id="occurrence_image:id" name="occurrence_image:id" value="" disabled="disabled" />
      <input type="hidden" id="occurrence_image:path" name="occurrence_image:path" value="" />
      <input type="hidden" id="flower_picture_camera_attr" name="occAttr:'.$args['occurrence_picture_camera_attr_id'].'" value="" />
      <input type="hidden" id="flower_picture_datetime_attr" name="occAttr:'.$args['occurrence_picture_datetime_attr_id'].'" value="" />
      '.str_replace("\n", "", data_entry_helper::outputAttribute($occurrence_attributes[$args['flower_type_attr_id']], $defNRAttrOptions))
      .str_replace("\n", "", data_entry_helper::outputAttribute($location_attributes[$args['distance_attr_id']], $defNRAttrOptions))
      .str_replace("\n", "", data_entry_helper::outputAttribute($location_attributes[$args['within50m_attr_id']], $defNRAttrOptions))
      .str_replace("\n", "", data_entry_helper::outputAttribute($location_attributes[$args['habitat_attr_id']], $checkOptions)).'
    </form>
    <div class="poll-break"></div>
    <div id="cc-2-location-container">
      '.iform_pollenators::help_button($use_help, "location-help-button", $args['help_function'], $args['help_location_arg']).'
      <div id="cc-2-location-notes" >'.lang::get('LANG_Location_Notes').'</div>
      <div id="cc-2-location-entry">
        '.data_entry_helper::georeference_lookup(iform_map_get_georef_options($args, $readAuth)).'
  	    <label for="place:INSEE">'.lang::get('LANG_Or').'</label><input type="text" id="place:INSEE" name="place:INSEE" value="'.lang::get('LANG_INSEE').'"
	 		onclick="if(this.value==\''.lang::get('LANG_INSEE').'\'){this.value=\'\'; this.style.color=\'#000\'}"  
            onblur="if(this.value==\'\'){this.value=\''.lang::get('LANG_INSEE').'\'; this.style.color=\'#555\'}" /><input type="button" id="search-insee-button" class="ui-corner-all ui-widget-content ui-state-default search-button" value="'.lang::get('search').'" />
 	    <label >'.lang::get('LANG_Or').'</label>
    	'.data_entry_helper::sref_textbox(array(
		        'srefField'=>'place:entered_sref',
        		'systemfield'=>'place:entered_sref_system',
        		'id'=>'place-sref',
        		'fieldname'=>'place:name',
        		'splitLatLong'=>true,
		        'labelLat' => lang::get('Latitude'),
    			'fieldnameLat' => 'place:lat',
        		'labelLong' => lang::get('Longitude'),
    			'fieldnameLong' => 'place:long',
    			'idLat'=>'imp-sref-lat',
        		'idLong'=>'imp-sref-long',
    			'suffixTemplate'=>'nosuffix')).'
 	  </div>
 	  <div class="poll-map-container">';
    $tempScript = data_entry_helper::$onload_javascript;
    data_entry_helper::$onload_javascript = '';
    $r .= data_entry_helper::map_panel($options, $olOptions);
    $map1JS = data_entry_helper::$onload_javascript;
    data_entry_helper::$onload_javascript = $tempScript;
 	$r .= '</div>
 	  <div id="cc-2-loc-description"></div>
    </div>
  </div>
  <div id="cc-2-footer" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active poll-section-footer">
    <div id="cc-2-valid-button" class="ui-state-default ui-corner-all save-button">'.lang::get('LANG_Validate_Flower').'</div>
  </div>
</div>';

	// NB the distance attribute is left blank at the moment if unknown: TODO put in a checkbox : checked if blank for nsp
 	data_entry_helper::$javascript .= "

showSessionsPanel = true;

buildMap = function (){
	if(jQuery('.poll-map-container:visible').length == 0) return; 
	if(jQuery('#map').children().length == 0) {
		".$map1JS."
  		jQuery('#map')[0].map.searchLayer.events.register('featuresadded', {}, function(a1){
			if(inseeLayer != null) inseeLayer.destroyFeatures();
		});
		jQuery('#map')[0].map.editLayer.events.register('featuresadded', {}, function(a1){
			if(inseeLayer != null) inseeLayer.destroy();
			jQuery('#cc-2-loc-description').empty();
		  	var filter = new OpenLayers.Filter.Spatial({type: OpenLayers.Filter.Spatial.CONTAINS, property: 'the_geom', value: jQuery('#map')[0].map.editLayer.features[0].geometry});
			var strategy = new OpenLayers.Strategy.Fixed({preload: false, autoActivate: false});
			var styleMap = new OpenLayers.StyleMap({\"default\": new OpenLayers.Style({fillColor: \"Red\", strokeColor: \"Red\", fillOpacity: 0, strokeWidth: 1})});
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
					,propertyNames: ['the_geom', 'NOM', 'INSEE_NEW', 'DEPT_NUM', 'DEPT_NOM', 'REG_NUM', 'REG_NOM']
				}),
				filter: filter
			});
		    inseeLayer.events.register('featuresadded', {}, function(a1){
    			if(a1.features.length > 0)
			    	jQuery('<span>'+a1.features[0].attributes.NOM+' ('+a1.features[0].attributes.INSEE_NEW+'), '+a1.features[0].attributes.DEPT_NOM+' ('+a1.features[0].attributes.DEPT_NUM+'), '+a1.features[0].attributes.REG_NOM+' ('+a1.features[0].attributes.REG_NUM+')</span>').appendTo('#cc-2-loc-description');
		    });
			jQuery('#map')[0].map.addLayer(inseeLayer);
			strategy.load({});
		});
	}
}

flowerIDstruc = {
	type: 'flower',
	selector: '#cc-2-flower-identify',
	mainForm: 'form#cc-2-floral-station',
	timeOutTimer: null,
	pollTimer: null,
	pollFile: '',
	invokeURL: '".$args['ID_tool_flower_url']."',
	pollURL: '".str_replace("{HOST}", $_SERVER['HTTP_HOST'], $args['ID_tool_flower_poll_dir'])."',
	name: 'flowerIDstruc',
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
 
    while (i < str_data.length) {
        c1 = str_data.charCodeAt(i);
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
      jQuery(this.toolStruct.selector+' [name='+this.toolStruct.type+'\\:taxon_details]').val(da[2]); // Stores the state of identification, which details how the identification was arrived at within the tool.
	  da[1] = da[1].replace(/\\\\i\{\}/g, '').replace(/\\\\i0\{\}/g, '').replace(/\\\\/g, '');
	  var items = da[1].split(':');
	  var count = items.length;
	  if(items[count-1] == '') count--;
	  if(items[count-1] == '') count--;
	  if(count <= 0){
	  	// no valid stuff so blank it all out.
	  	jQuery('#'+this.toolStruct.type+'_taxa_list').append(\"".lang::get('LANG_Taxa_Unknown_In_Tool')."\");
	  	jQuery(this.toolStruct.selector+' [name='+this.toolStruct.type+'\\:determination_type]').val('X'); // Unidentified.
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
  				notFound = (notFound == '' ? '' : notFound + ', ') + items[j]; // don't need special chars as going into an input field
  			}
  		}
		jQuery('#'+this.toolStruct.type+'_taxa_list').append(resultsText+ ' }');
		jQuery('#'+this.toolStruct.type+'-id-button').data('toolRetValues', resultsIDs);
	  	if(notFound != ''){
			var comment = jQuery('[name='+this.toolStruct.type+'\\:comment]');
			comment.val('".lang::get('LANG_ID_Unrecognised')." '+notFound+'. '+comment.val());
		}
  	  }
    }});
};

pollReset = function(toolStruct){
	clearTimeout(toolStruct.timeOutTimer);
	clearTimeout(toolStruct.pollTimer);
	jQuery('#'+toolStruct.type+'-id-cancel').hide();
	jQuery('#'+toolStruct.type+'-id-button').show();
	jQuery('#'+toolStruct.type+'-id-button').data('toolRetValues', []);
	jQuery(toolStruct.selector+' [name='+toolStruct.type+'\\:taxon_details]').val('');
	jQuery('#'+toolStruct.type+'_taxa_list').empty();
	toolStruct.pollFile='';
	toolStruct.timeOutTimer = null;
	toolStruct.pollTimer = null;
};

idButtonPressed = function(toolStruct){
	jQuery(toolStruct.selector+' [name='+toolStruct.type+'\\:determination_type]').val('A');
	jQuery('#id-'+toolStruct.type+'-later').removeAttr('checked');
	jQuery('#'+toolStruct.type+'-id-button').data('toolRetValues', []);
	jQuery(toolStruct.selector+' [name='+toolStruct.type+'\\:taxon_details]').val('');
	jQuery('#'+toolStruct.type+'_taxa_list').empty();
	jQuery(toolStruct.selector+' [name='+toolStruct.type+'\\:taxa_taxon_list_id]').val('');
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
  	jQuery('#id-'+toolStruct.type+'-later').removeAttr('checked');
	jQuery('#'+toolStruct.type+'-id-button').data('toolRetValues', []);
	jQuery(toolStruct.selector+' [name='+toolStruct.type+'\\:taxon_details]').val('');
	jQuery('#'+toolStruct.type+'_taxa_list').empty();
	jQuery('[name='+toolStruct.type+'\\:comment]').val('');
	jQuery(toolStruct.selector+' [name='+toolStruct.type+'\\:taxon_extra_info]').val('');
	jQuery(toolStruct.selector+' [name='+toolStruct.type+'\\:determination_type]').val('A');
};
jQuery('#cc-2-flower-identify select[name=flower\\:taxa_taxon_list_id]').change(function(){
	pollReset(flowerIDstruc);
	taxonChosen(flowerIDstruc);
});

idLater = function (toolStruct){
	if (jQuery('#id-'+toolStruct.type+'later').attr('checked') != '') {
	  	jQuery(toolStruct.selector+' [name='+toolStruct.type+'\\:determination_type]').val('A');
		jQuery('#'+toolStruct.type+'-id-button').data('toolRetValues', []);
		jQuery(toolStruct.selector+' [name='+toolStruct.type+'\\:taxon_details]').val('');
		jQuery('#'+toolStruct.type+'_taxa_list').empty();
		jQuery(toolStruct.selector+' [name='+toolStruct.type+'\\:taxa_taxon_list_id]').val('');
		jQuery(toolStruct.selector+' [name='+toolStruct.type+'\\:taxon_extra_info]').val(''); // more precise info
		jQuery('[name='+toolStruct.type+'\\:comment]').val('');
	}
};
jQuery('#id-flower-later').change(function (){
	pollReset(flowerIDstruc);
	idLater(flowerIDstruc);
});


jQuery('#search-insee-button').click(function(){
	if(inseeLayer != null)
		inseeLayer.destroy();
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
  			}),
  		  filter: new OpenLayers.Filter.Logical({
			      type: OpenLayers.Filter.Logical.OR,
			      filters: filters
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
	strategy.load({});
});

validateStationPanel = function(){
	var myPanel = jQuery('#cc-2');
	var valid = true;
	clearErrors('form#cc-2-floral-station');
	clearErrors('#cc-2-flower-identify');
	if(myPanel.filter(':visible').length == 0) return true; // panel is not visible so no data to fail validation.
	if(myPanel.find('.poll-section-body:visible').length == 0) return true; // body hidden so data already been validated successfully.
	// If no data entered also return true: this can only be the case when pressing the modify button on the collections panel
	if(jQuery('form#cc-2-floral-station > input[name=location_image\\:path]').val() == '' &&
			jQuery('form#cc-2-floral-station > input[name=occurrence\\:id]').val() == '' &&
			jQuery('form#cc-2-floral-station > input[name=occurrence_image\\:path]').val() == '' &&
			jQuery('#cc-2-flower-identify select[name=flower\\:taxa_taxon_list_id]').val() == '' &&
			jQuery('[name=flower\\:taxon_details]').val() == '' &&
			jQuery('#cc-2-flower-identify [name=flower\\:taxon_extra_info]').val() == '' &&
			jQuery('[name=flower\\:comment]').val() == '' &&
			jQuery('[name=occAttr\\:".$args['flower_type_attr_id']."],[name^=occAttr\\:".$args['flower_type_attr_id']."\\:]').filter('[checked]').length == 0 &&
			jQuery('[name=locAttr\\:".$args['within50m_attr_id']."],[name^=locAttr\\:".$args['within50m_attr_id']."\\:]').filter('[checked]').length == 0 &&
			jQuery('[name=locAttr\\:".$args['habitat_attr_id']."],[name^=locAttr\\:".$args['habitat_attr_id']."\\:]').filter('[checked]').length == 0 &&
    		jQuery('[name=locAttr\\:".$args['distance_attr_id']."],[name^=locAttr\\:".$args['distance_attr_id']."\\:]').val() == '') {
		jQuery('#cc-2').foldPanel();
		return true;
	}
    if(jQuery('form#cc-2-floral-station > input[name=location_image\\:path]').val() == '' ||
					jQuery('form#cc-2-floral-station > input[name=occurrence_image\\:path]').val() == ''){
		if(jQuery('form#cc-2-floral-station > input[name=occurrence_image\\:path]').val() == '')
			myScrollTo('#cc-2-flower-upload');
		else
			myScrollTo('#cc-2-environment-upload');
		alert(\"".lang::get('LANG_Must_Provide_Pictures')."\");
		valid = false;
	}
    if(jQuery('#imp-geom').val() == '') {
		alert(\"".lang::get('LANG_Must_Provide_Location')."\");
		myScrollTo('.poll-map-container');
		valid = false;
	}
	if (jQuery('#id-flower-later').attr('checked') == '' &&	 jQuery('[name=flower\\:taxon_details]').val() == ''){
		if(!validateRequiredField('flower\\:taxa_taxon_list_id', '#cc-2-flower-identify')) { valid = false; }
    }
	if (!jQuery('form#cc-2-floral-station > input').valid()) { valid = false; }
	if (!validateRadio('occAttr\\:".$args['flower_type_attr_id']."', 'form#cc-2-floral-station')) { valid = false; }
	if (!validateRadio('locAttr\\:".$args['within50m_attr_id']."', 'form#cc-2-floral-station')) { valid = false; }
	if (!validateOptInt('locAttr\\:".$args['distance_attr_id']."', 'form#cc-2-floral-station')) { valid = false; }
   	if ( valid == false ) {
   		myScrollToError();
   		return valid;
   	}
	showSessionsPanel = false;
	jQuery('form#cc-2-floral-station').submit();
	return true;
};

// Flower upload picture form.
$('#cc-2-flower-upload').ajaxForm({ 
		async: false,
		dataType:  'json', 
        beforeSubmit:   function(data, obj, options){
         	if (!jQuery('form#cc-2-flower-upload').valid() ||
                    jQuery('#cc-2-flower-image').hasClass('loading')) {
   				return false;
   			}
   			$('#cc-2-flower-image').empty();
        	$('#cc-2-flower-image').addClass('loading');
		   	jQuery('form#cc-2-floral-station input[name=occurrence_image\\:path]').val('');
  		},
        success:   function(data){
        	if(data.success == true){
	        	// There is only one file
	        	jQuery('form#cc-2-floral-station input[name=occurrence_image\\:path]').val(data.files[0].filename);
	        	jQuery('#flower_picture_camera_attr').val(data.files[0].EXIF_Camera_Make);
	        	jQuery('#flower_picture_datetime_attr').val(data.files[0].EXIF_DateTime);
	        	insertImage('med-'+data.files[0].filename, jQuery('#cc-2-flower-image'), ".$args['Flower_Image_Ratio'].");
	        	jQuery('#cc-2-flower-upload input[name=upload_file]').val('');
  			} else
				alertIndiciaError(data);
  		},
  		complete: function(){
			$('#cc-2-flower-image').removeClass('loading');
  		}
});

// Flower upload picture form.
$('#cc-2-environment-upload').ajaxForm({ 
		async: false,
		dataType:  'json', 
        beforeSubmit:   function(data, obj, options){
         	if (!jQuery('form#cc-2-environment-upload').valid()) {
   				return false;
   			}
        	$('#cc-2-environment-image').empty();
        	$('#cc-2-environment-image').addClass('loading')
	       	jQuery('form#cc-2-floral-station input[name=location_image\\:path]').val('');
  		},
        success:   function(data){
        	if(data.success == true){
	        	// There is only one file
	        	jQuery('form#cc-2-floral-station input[name=location_image\\:path]').val(data.files[0].filename);
	        	jQuery('#location_picture_camera_attr').val(data.files[0].EXIF_Camera_Make);
	        	jQuery('#location_picture_datetime_attr').val(data.files[0].EXIF_DateTime);
	        	insertImage('med-'+data.files[0].filename, jQuery('#cc-2-environment-image'), ".$args['Environment_Image_Ratio'].");
				jQuery('#cc-2-environment-upload input[name=upload_file]').val('');
			} else
				alertIndiciaError(data);
        },
  		complete: function(){
			$('#cc-2-environment-image').removeClass('loading');
  		}
});

findID = function(name, data){
	for(var i=0; i< data.length;i++){
		if(data[i].name == name) return i;
	}
};

$('#cc-2-floral-station').ajaxForm({ 
	async: false,
	dataType:  'json', 
    beforeSubmit:   function(data, obj, options){
		var valid = true;
		clearErrors('form#cc-2-floral-station');
		clearErrors('#cc-2-flower-identify');
    	if(jQuery('form#cc-2-floral-station > input[name=location_image\\:path]').val() == '' ||
					jQuery('form#cc-2-floral-station > input[name=occurrence_image\\:path]').val() == '' ){
			if(jQuery('form#cc-2-floral-station > input[name=occurrence_image\\:path]').val() == '')
				myScrollTo('#cc-2-flower-upload');
			else
				myScrollTo('#cc-2-environment-upload');
			alert(\"".lang::get('LANG_Must_Provide_Pictures')."\");
			valid = false;
		}
		if(jQuery('#imp-geom').val() == '') {
			myScrollTo('.poll-map-container');
			alert(\"".lang::get('LANG_Must_Provide_Location')."\");
			valid = false;
		}
		if (!jQuery('form#cc-2-floral-station > input').valid()) { valid = false; }
		if (!validateRadio('occAttr\\:".$args['flower_type_attr_id']."', 'form#cc-2-floral-station')) { valid = false; }
		if (!validateRadio('locAttr\\:".$args['within50m_attr_id']."', 'form#cc-2-floral-station')) { valid = false; }
		if (!validateOptInt('locAttr\\:".$args['distance_attr_id']."', 'form#cc-2-floral-station')) { valid = false; }
		data[findID('location:centroid_sref', data)].value = jQuery('#imp-sref').val();
		data[findID('location:centroid_geom', data)].value = jQuery('#imp-geom').val();
		if (jQuery('#id-flower-later').attr('checked') == ''){
			data[findID('determination:taxa_taxon_list_id', data)].value = jQuery('#cc-2-flower-identify select[name=flower\\:taxa_taxon_list_id]').val();
			data[findID('determination:taxon_details', data)].value = jQuery('#cc-2-flower-identify [name=flower\\:taxon_details]').val();
			data[findID('determination:taxon_extra_info', data)].value = jQuery('#cc-2-flower-identify [name=flower\\:taxon_extra_info]').val();
			data[findID('determination:comment', data)].value = jQuery('[name=flower\\:comment]').val();
			data[findID('determination:determination_type', data)].value = jQuery('#cc-2-flower-identify [name=flower\\:determination_type]').val();
			if (jQuery('#cc-2-flower-identify [name=flower\\:taxon_details]').val() == ''){
				if (!validateRequiredField('flower\\:taxa_taxon_list_id', '#cc-2-flower-identify')) {
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
		} else {
			data.splice(12,8); // remove determination entries.
		}
   		if ( valid == false ) {
			myScrollToError();
			return false;
		};
  		jQuery('#cc-2-valid-button').addClass('loading-button');
   		return true;
	},
    success:   function(data){
       	if(data.success == 'multiple records' && data.outer_table == 'sample'){
       		// the sample and location ids are already fixed, so just need to populate the occurrence and image IDs, and rename the location and occurrence attribute.
			// ONLY 1 CHILD, THE OCCURRENCE: TBD ADD CHECK THAT IF ALREADY EXISTS THAT VALUES ARE THE SAME.
			jQuery('#cc-2-floral-station > input[name=occurrence\\:id]').removeAttr('disabled').val(data.struct.children[0].id);
			// the occurrence has whole range of children: attributes, image and determination.
			loadAttributes('#cc-2-floral-station', 'occurrence_attribute_value', 'occurrence_attribute_id', 'occurrence_id', data.struct.children[0].id, 'occAttr', false, true);
			for(i=0; i<data.struct.children[0].children.length; i++){
				if(data.struct.children[0].children[i].model == 'occurrence_image'){
					jQuery('#cc-2-floral-station > input[name=occurrence_image\\:id]').removeAttr('disabled').val(data.struct.children[0].children[i].id);}
				if(data.struct.children[0].children[i].model == 'determination'){
					jQuery('#cc-2-floral-station > input[name=determination\\:id]').removeAttr('disabled').val(data.struct.children[0].children[i].id);}
			}
			// ONLY 1 PARENT, THE LOCATION: TBD ADD CHECK THAT IF ALREADY EXISTS THAT VALUES ARE THE SAME.
		    var location_id = jQuery('#cc-2-floral-station > input[name=location\\:id]').val();
       		loadAttributes('#cc-2-floral-station', 'location_attribute_value', 'location_attribute_id', 'location_id', location_id, 'locAttr', true, true);
			for(i=0; i<data.struct.parents[0].children.length; i++){
				if(data.struct.parents[0].children[i].model == 'location_image'){
					jQuery('#cc-2-floral-station > input[name=location_image\\:id]').removeAttr('disabled').val(data.struct.parents[0].children[i].id);}}
			jQuery('#cc-2').foldPanel();
			if(showSessionsPanel) { jQuery('#cc-3').showPanel(); }
			showSessionsPanel = true;
        }  else {
			if(data.error){
				var lastIndex = data.error.lastIndexOf('Validation error'); 
    			if (lastIndex != -1 && lastIndex  == (data.error.length - 16)){ 
					if(data.errors){
						if(data.errors['location:centroid_sref']){
							var label = $('<p/>')
								.addClass('inline-error')
								.html(\"".lang::get('LANG_Invalid_Location')."\");
							label.insertBefore('.latLongDiv:first');
							myScrollToError();
							return;
						}
					}
				}
			}
			alertIndiciaError(data);
		}
	},
    complete: function (){
  		jQuery('.loading-button').removeClass('loading-button');
  	}
});

$('#cc-2-valid-button').click(function() {
	jQuery('#cc-2-floral-station').submit();
});

";

 	// Sessions.
    $r .= '
<div id="cc-3" class="poll-section">
  <div id="cc-3-title" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-all poll-section-title"><span id="cc-3-title-title" >'.lang::get('LANG_Sessions_Title').'</span>
    <div id="cc-3-mod-button" class="right ui-state-default ui-corner-all mod-button">'.lang::get('LANG_Modify').'</div>
  </div>
  <div id="cc-3-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-top ui-accordion-content-active poll-section-body">
  </div>
  <div id="cc-3-footer" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active poll-section-footer">
	<div id="cc-3-add-button" class="ui-state-default ui-corner-all add-button">'.lang::get('LANG_Add_Session').'</div>
    <div id="cc-3-valid-button" class="ui-state-default ui-corner-all save-button">'.lang::get('LANG_Validate_Session').'</div>
  </div>
</div>
<div style="display:none" />
    <form id="cc-3-delete-session" action="'.iform_ajaxproxy_url($node, 'sample').'" method="POST">
       <input type="hidden" name="website_id" value="'.$args['website_id'].'" />
       <input type="hidden" name="sample:survey_id" value="'.$args['survey_id'].'" />
       <input type="hidden" name="sample:id" value="" />
       <input type="hidden" name="sample:date" value="2010-01-01"/>
       <input type="hidden" name="sample:location_id" value="" />
       <input type="hidden" name="sample:deleted" value="t" />
    </form>
</div>';

    data_entry_helper::$javascript .= "
$('#cc-3-delete-session').ajaxForm({ 
		async: false,
		dataType:  'json', 
		beforeSubmit:   function(data, obj, options){
			// Warning this assumes that the data is fixed position:
			data[4].value = jQuery('#cc-1-collection-details input[name=location\\:id]').val();
			if(data[2].value == '') return false;
			// double check that location id is filled in
			if(data[4].value == ''){
				alertIndiciaError({error : \"".lang::get('Internal Error 7: location id not set, so unsafe to delete session.')."\"});
				return false;
			}
			// don't have to worry about parent_id
			return true;
  		},
        success:   function(data){
        	if(data.success != 'multiple records' || data.outer_table != 'sample')
				alertIndiciaError(data);
  		}
        ,complete: function (){
  			jQuery('.loading-button').removeClass('loading-button');
  		}
});
populateSessionSelect = function(){
	var insectSessionSelect = jQuery('form#cc-4-main-form > select[name=occurrence\\:sample_id]');
	var value = insectSessionSelect.val();
	insectSessionSelect.empty();
	// NB at this point the attributes have been loaded so have full name.
	$('.poll-session-form').each(function(i){
		jQuery('<option value=\"'+
				jQuery(this).children('input[name=sample\\:id]').val()+
				'\">'+
				jQuery(this).children('input[name=dummy_date]').val()+
				' : '+
				jQuery(this).children('[name=smpAttr\\:".$args['start_time_attr_id']."],[name^=smpAttr\\:".$args['start_time_attr_id']."\\:]').val()+
				' > '+
				jQuery(this).children('[name=smpAttr\\:".$args['end_time_attr_id']."],[name^=smpAttr\\:".$args['end_time_attr_id']."\\:]').val()+
				'</option>')
			.appendTo(insectSessionSelect);
	});
	insectSessionSelect.find('option').each(function(i,obj){
  		if(i == 0 || jQuery(obj).val() == value)
			insectSessionSelect.val(insectSessionSelect.find('option').filter(':first').val());
  	});
}
compareTimes = function(nameStart, nameEnd, formSel){
    var controlStart = jQuery(formSel).find('[name='+nameStart+'],[name^='+nameStart+'\\:]');
    var controlEnd = jQuery(formSel).find('[name='+nameEnd+'],[name^='+nameEnd+'\\:]');
    var valueStart = controlStart.val().split(':');
    var valueEnd = controlEnd.val().split(':');
    var minsDiff = (valueEnd[0]-valueStart[0])*60+(valueEnd[1]-valueStart[1]);
    if(minsDiff < 0){
        $('<p/>').attr({'for': nameEnd}).addClass('inline-error').html(\"".lang::get('validation_endtime_before_start')."\").insertBefore(controlEnd);
		return false;
    }
    // The Flash selection is first, Long second for the protocol: So
    var isFlashProtocol = jQuery('[name=smpAttr\\:".$args['protocol_attr_id']."],[name^=smpAttr\\:".$args['protocol_attr_id']."\\:]').filter(':first').filter('[checked]').length >0;
    if(!isFlashProtocol && minsDiff < 20){ // Long Protocol, session must not be less than 20mins
        $('<p/>').attr({'for': nameStart}).addClass('inline-error').html(\"".lang::get('validation_time_less_than_20')."\").insertBefore(controlStart);
        $('<p/>').attr({'for': nameEnd}).addClass('inline-error').html(\"".lang::get('validation_please_check')."\").insertBefore(controlEnd);
		return false;
    }
    if(isFlashProtocol && minsDiff != 20){ // Flash Protocol, session must be exactly 20mins
        $('<p/>').attr({'for': nameStart}).addClass('inline-error').html(\"".lang::get('validation_time_not_20')."\").insertBefore(controlStart);
        $('<p/>').attr({'for': nameEnd}).addClass('inline-error').html(\"".lang::get('validation_please_check')."\").insertBefore(controlEnd);
		return false;
    }
    return true;
}
convertDate = function(dateStr){
	// Converts a YYYY-MM-DD date to YYYY/MM/DD so IE can handle it.
	return dateStr.slice(0,4)+'/'+dateStr.slice(5,7)+'/'+dateStr.slice(8,10);
} 

checkDate = function(name, formSel){
  var control = jQuery(formSel).find('[name='+name+']');
  var session = this;
  var dateError = false;
  var d2 = new Date(convertDate(control.val()));
  var two_days=2*1000*60*61*24; // allows a bit of leaway
  jQuery('.required').filter('[name=sample:date]').each(function(){
    var d1 = new Date(convertDate(jQuery(this).val()));
    if(Math.abs(d1.getTime()-d2.getTime()) > two_days){
      dateError=true;
    }
  });
  if(dateError){
    $('<p/>').attr({'for': name}).addClass('inline-error').html(\"".lang::get('validation_session_date_error')."\").insertBefore(control);
    return false;
  };
  return true;
}

validateAndSubmitOpenSessions = function(){
	var valid = true;
	// only check the visible forms as rest have already been validated successfully.
	$('.poll-session-form:visible').each(function(i){
		clearErrors(this);
   		if (valid && !checkDate('sample\\:date', this)) { valid = false; }
		if (!jQuery(this).children('input').valid()) { valid = false; }
	    if (!validateTime('smpAttr\\:".$args['start_time_attr_id']."', this)) { valid = false; }
   		if (!validateTime('smpAttr\\:".$args['end_time_attr_id']."', this)) { valid = false; }
   		if (valid && !compareTimes('smpAttr\\:".$args['start_time_attr_id']."', 'smpAttr\\:".$args['end_time_attr_id']."', this)) { valid = false; }
   		if (!validateRadio('smpAttr\\:".$args['sky_state_attr_id']."', this)) { valid = false; }
   		if (!validateRadio('smpAttr\\:".$args['temperature_attr_id']."', this)) { valid = false; }
   		if (!validateRadio('smpAttr\\:".$args['wind_attr_id']."', this)) { valid = false; }
   		if (!validateRadio('smpAttr\\:".$args['shade_attr_id']."', this)) { valid = false; }
   });
	if(valid == false) {
		myScrollToError();
		return false;
	};
	$('.poll-session-form:visible').submit();
	return true;
}

addSession = function(){
	sessionCounter = sessionCounter + 1;
	// dynamically build the contents of the session block.
	var newSession = jQuery('<div id=\"cc-3-session-'+sessionCounter+'\" class=\"poll-session\"/>')
		.appendTo('#cc-3-body');
	var newTitle = jQuery('<div class=\"poll-session-title\">".lang::get('LANG_Session')." '+sessionCounter+'</div>')
		.appendTo(newSession);
	var newModButton = jQuery('<div class=\"right ui-state-default ui-corner-all mod-button\">".lang::get('LANG_Modify')."</div>')
		.appendTo(newTitle).hide();
	var newDeleteButton = jQuery('<div class=\"right ui-state-default ui-corner-all delete-button\">".lang::get('LANG_Delete_Session')."</div>')
		.appendTo(newTitle);	
	newModButton.click(function() {
		if(!validateAndSubmitOpenSessions()) return false;
		var session=$(this).parents('.poll-session');
		session.show();
		session.children().show();
		session.children(':first').children(':first').hide(); // this is the mod button itself
    });
    var formContainer = jQuery('<div />').appendTo(newSession);
    var newForm = jQuery('<form action=\"".iform_ajaxproxy_url($node, 'sample')."\" method=\"POST\" class=\"poll-session-form\" />').appendTo(formContainer);
	jQuery('<input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />').appendTo(newForm);
	jQuery('<input type=\"hidden\" name=\"sample:survey_id\" value=\"".$args['survey_id']."\" />').appendTo(newForm);
	jQuery('<input type=\"hidden\" name=\"sample:parent_id\" />').appendTo(newForm).val(jQuery('#cc-1-collection-details > input[name=sample\\:id]').val());
	jQuery('<input type=\"hidden\" name=\"sample:location_id\" />').appendTo(newForm).val(jQuery('#cc-1-collection-details > input[name=location\\:id]').val());
	jQuery('<input type=\"hidden\" name=\"sample:id\" value=\"\" disabled=\"disabled\" />').appendTo(newForm);\n";
    if($use_help){
        data_entry_helper::$javascript .= "
	var helpDiv = jQuery('<div class=\"right ui-state-default ui-corner-all help-button\">".lang::get('LANG_Help_Button')."</div>');
	helpDiv.click(function(){
		".$args['help_function']."(".$args['help_session_arg'].");
	});
	helpDiv.appendTo(newForm);";
    }
    // we have to be careful with the dates: Indicia supplies dates as YYYY-MM-DD, but this is not ready understood by the IE JS Date, which is OK with YYYY/MM/DD.
    // We keep the YYYY-MM-DD internally for consistency.
    data_entry_helper::$javascript .= "
	var dateID = 'cc-3-session-date-'+sessionCounter;
	var dateAttr = '<label for=\"'+dateID+'\">".lang::get('LANG_Date')." :</label><input type=\"text\" size=\"10\" class=\"vague-date-picker required\" id=\"'+dateID+'\" name=\"dummy_date\" value=\"".lang::get('click here')."\" /> ';
	dateAttr = dateAttr + '<input type=\"hidden\" id=\"real-'+dateID+'\" name=\"sample:date\" value=\"\" class=\"required\"/> ';
    jQuery(dateAttr).appendTo(newForm);
	jQuery('#'+dateID).datepicker({
		dateFormat : 'dd/mm/yy',
		constrainInput: false,
		maxDate: '0',
		altField : '#real-'+dateID,
		altFormat : 'yy-mm-dd'
	});
    jQuery('".str_replace("\n", "", data_entry_helper::outputAttribute($sample_attributes[$args['start_time_attr_id']], $defAttrOptions))."').appendTo(newForm);
	jQuery('".str_replace("\n", "", data_entry_helper::outputAttribute($sample_attributes[$args['end_time_attr_id']], $defAttrOptions))."').appendTo(newForm);
	jQuery('".str_replace("\n", "", data_entry_helper::outputAttribute($sample_attributes[$args['sky_state_attr_id']], $defNRAttrOptions))."').appendTo(newForm);
	jQuery('".str_replace("\n", "", data_entry_helper::outputAttribute($sample_attributes[$args['temperature_attr_id']], $defNRAttrOptions))."').appendTo(newForm);
	jQuery('".str_replace("\n", "", data_entry_helper::outputAttribute($sample_attributes[$args['wind_attr_id']], $defNRAttrOptions))."').appendTo(newForm);
	jQuery('".str_replace("\n", "", data_entry_helper::outputAttribute($sample_attributes[$args['shade_attr_id']], array_merge($defNRAttrOptions, array('default'=>'-1'))))."').appendTo(newForm);
	newDeleteButton.click(function() {
		var container = $(this).parent().parent();
		jQuery('#cc-3-delete-session').find('[name=sample\\:id]').val(container.find('[name=sample\\:id]').val());
		jQuery('#cc-3-delete-session').find('[name=sample\\:date]').val(container.find('[name=sample\\:date]').val());
		jQuery('#cc-3-delete-session').find('[name=sample\\:location_id]').val(container.find('[name=sample\\:location_id]').val());
		if(container.find('[name=sample\\:id]').filter('[disabled]').length == 0){
			jQuery(this).addClass('loading-button');
			$.getJSON(\"".$svcUrl."/data/occurrence/\" +
					\"?mode=json&view=detail&reset_timeout=true&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
					\"&sample_id=\"+container.find('[name=sample\\:id]').val()+\"&deleted=f&callback=?\", function(insectData) {
				if(!(insectData instanceof Array)){
   					alertIndiciaError(insectData);
   				} else if (insectData.length>0) {
					jQuery('.loading-button').removeClass('loading-button');
					alert(\"".lang::get('LANG_Cant_Delete_Session')."\");
				} else if(confirm(\"".lang::get('LANG_Confirm_Session_Delete')."\")){
					jQuery('#cc-3-delete-session').submit();
					container.remove();
					checkSessionButtons();
				}
			});
		} else if(confirm(\"".lang::get('LANG_Confirm_Session_Delete')."\")){
			container.remove();
			checkSessionButtons();
		}
    });
    newForm.ajaxForm({
   		async: false,
    	dataType:  'json',
    	beforeSubmit:   function(data, obj, options){
			// double check that location id and sample id are filled in
			if(jQuery('#cc-1-collection-details input[name=location\\:id]').val() == ''){
				alertIndiciaError({error : \"".lang::get('Internal Error 8: location id not set, so unsafe to save session.')."\"});
				return false;
			}
			if(jQuery('#cc-1-collection-details input[name=sample\\:id]').val() == ''){
				alertIndiciaError({error : \"".lang::get('Internal Error 9: sample id not set, so unsafe to save session.')."\"});
				return false;
			}
	    	var valid = true;
    		clearErrors(obj);
    		if (!obj.find('input').valid()) {
    			valid = false; }
    		if (!validateRadio('smpAttr\\:".$args['sky_state_attr_id']."', obj)) { valid = false; }
   			if (!validateRadio('smpAttr\\:".$args['temperature_attr_id']."', obj)) { valid = false; }
   			if (!validateRadio('smpAttr\\:".$args['wind_attr_id']."', obj)) { valid = false; }
   			if (!validateRadio('smpAttr\\:".$args['shade_attr_id']."', obj)) { valid = false; }
   			data[2].value = jQuery('#cc-1-collection-details > input[name=sample\\:id]').val();
			data[3].value = jQuery('#cc-1-collection-details > input[name=location\\:id]').val();
			jQuery('#cc-3-valid-button').addClass('loading-button');
			if(!valid) myScrollToError();
			return valid;
		},
   	    success:   function(data, status, form){
   	    	var thisSession = form.parents('.poll-session');
    		if(data.success == 'multiple records' && data.outer_table == 'sample'){
   	    	    form.children('input[name=sample\\:id]').removeAttr('disabled').val(data.outer_id);
   	    	    loadAttributes(form, 'sample_attribute_value', 'sample_attribute_id', 'sample_id', data.outer_id, 'smpAttr', true, true);
				thisSession.show();
				thisSession.children(':first').show().find('*').show();
				thisSession.children().not(':first').hide();
  			} else 
	        	alertIndiciaError(data);
  		},
        complete: function (){
  			jQuery('.loading-button').removeClass('loading-button');
  		}
	});
	newSession.find('.deh-required').remove();
    return(newSession);
};

validateSessionsPanel = function(){
	if(jQuery('#cc-3:visible').length == 0) return true; // panel is not visible so no data to fail validation.
	if(jQuery('#cc-3').find('.poll-section-body:visible').length == 0) return true; // body hidden so data already been validated successfully.
	var openSession = jQuery('.poll-session-form:visible');
	if(openSession.length > 0){
		if(jQuery('input[name=sample\\:id]', openSession).val() == '' &&
				jQuery('input[name=sample\\:date]', openSession).val() == '' &&
				jQuery('[name=smpAttr\\:".$args['start_time_attr_id']."],[name^=smpAttr\\:".$args['start_time_attr_id']."\\:]', openSession).val() == '' &&
				jQuery('[name=smpAttr\\:".$args['end_time_attr_id']."],[name^=smpAttr\\:".$args['end_time_attr_id']."\\:]', openSession).val() == '' &&
				jQuery('[name=smpAttr\\:".$args['sky_state_attr_id']."],[name^=smpAttr\\:".$args['sky_state_attr_id']."\\:]', openSession).filter('[checked]').length == 0 &&
    			jQuery('[name=smpAttr\\:".$args['temperature_attr_id']."],[name^=smpAttr\\:".$args['temperature_attr_id']."\\:]', openSession).filter('[checked]').length == 0 &&
    			jQuery('[name=smpAttr\\:".$args['wind_attr_id']."],[name^=smpAttr\\:".$args['wind_attr_id']."\\:]', openSession).filter('[checked]').length == 0 &&
    			jQuery('[name=smpAttr\\:".$args['shade_attr_id']."],[name^=smpAttr\\:".$args['shade_attr_id']."\\:]', openSession).filter('[checked]').length == 0) {
    		jQuery('#cc-3').foldPanel();
			return true;
		}
	}
	// not putting in an empty data set check here - user can delete the session if needed, and there must be at least one.
	if(!validateAndSubmitOpenSessions()) return false;
	jQuery('#cc-3').foldPanel();
	populateSessionSelect();
	return true;
};
jQuery('#cc-3-valid-button').click(function(){
	if(!validateAndSubmitOpenSessions()) return;
	jQuery('#cc-3').foldPanel();
	jQuery('#cc-4').showPanel();
	populateSessionSelect();
});
jQuery('#cc-3-add-button').click(function(){
	if(!validateAndSubmitOpenSessions()) return;
	addSession();
	checkSessionButtons();
});

jQuery('.mod-button').click(function() {
	// first close all the other panels, ensuring any data is saved.
	if(!validateCollectionPanel() || !validateStationPanel() || !validateSessionsPanel() || !validateInsectPanel())
		return;
	jQuery('#cc-5').hidePanel();
	jQuery(this).parents('.poll-section-title').parent().unFoldPanel(); //slightly complicated because cc-1 contains the rest.
});

";

    $extraParams = $readAuth + array('taxon_list_id' => $args['insect_list_id'], 'view'=>'detail','orderby'=>'taxonomic_sort_order', 'allow_data_entry'=>'t');
	$species_ctrl_args=array(
    	    'label'=>lang::get('LANG_Insect_Species'),
        	'fieldname'=>'insect:taxa_taxon_list_id',
	        'table'=>'taxa_taxon_list',
    	    'captionField'=>'taxon',
        	'listCaptionSpecialChars'=>true,
    	    'valueField'=>'id',
	        'columns'=>2,
    		'blankText'=>lang::get('LANG_Choose_Taxon'),
    	    'extraParams'=>$extraParams,
			'suffixTemplate'=>'nosuffix'
	);
	$checkOptions['labelClass']='checkbox-label';
 	$r .= '
<div id="cc-4" class="poll-section">
  <div id="cc-4-title" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-all poll-section-title">'.lang::get('LANG_Photos').'
    <div id="cc-4-mod-button" class="right ui-state-default ui-corner-all mod-button">'.lang::get('LANG_Modify').'</div>
  </div>
  <div id="cc-4-photo-reel" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-top ui-accordion-content-active photoReelContainer" >
    <div class="photo-blurb">'.lang::get('LANG_Photo_Blurb').'</div>
    <div class="blankPhoto thumb currentPhoto"></div>
  </div>
  <div id="cc-4-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active poll-section-body">  
    <div id="cc-4-insect">
      <div id="cc-4-insect-title">'.lang::get('LANG_Upload_Insect').'</div>
      <form id="cc-4-insect-upload" enctype="multipart/form-data" action="'.iform_ajaxproxy_url($node, 'media').'" method="POST">
    	<input type="hidden" id="website_id" name="website_id" value="'.$args['website_id'].'" />
    	<input name="upload_file" type="file" class="required" />
        <input type="submit" value="'.lang::get('LANG_Upload').'" class="btn-submit" />
 	    <div id="cc-4-insect-image" class="poll-image"></div>
      </form>
      <div id="cc-4-insect-identify" class="poll-dummy-form">
 	    <div class="id-tool-group">
          '.iform_pollenators::help_button($use_help, "insect-help-button", $args['help_function'], $args['help_insect_arg']).'
          <p><strong>'.lang::get('LANG_Identify_Insect').'</strong></p>
          <input type="hidden" id="insect:taxon_details" name="insect:taxon_details" />
          <input type="hidden" name="insect:determination_type" value="A" />  
		  <label for="insect-id-button">'.lang::get('LANG_Insect_ID_Key_label').' :</label><span id="insect-id-button" class="ui-state-default ui-corner-all poll-id-button" >'.lang::get('LANG_Launch_ID_Key').'</span>
		  <span id="insect-id-cancel" class="ui-state-default ui-corner-all poll-id-cancel" >'.lang::get('LANG_Cancel_ID').'</span>
 	      <p id="insect_taxa_list"></p> 
 	    </div>
 	    <div class="id-later-group">
 	      <label for="id-insect-later" class="follow-on">'.lang::get('LANG_ID_Insect_Later').' </label><input type="checkbox" id="id-insect-later" name="id-insect-later" />
 	    </div>
 	    <div class="id-specified-group">
 	      '.data_entry_helper::select($species_ctrl_args).'
          <label for="insect:taxon_extra_info" class="follow-on">'.lang::get('LANG_ID_More_Precise').' </label> 
    	  <input type="text" id="insect:taxon_extra_info" name="insect:taxon_extra_info" class="taxon-info" />
        </div>
      </div>
 	  <div class="id-comment">
        <label for="insect:comment" >'.lang::get('LANG_ID_Comment').' </label>
        <textarea id="insect:comment" name="insect:comment" class="taxon-comment" rows="3" ></textarea>
      </div>
    </div>
    <div class="poll-break"></div> 
 	<form id="cc-4-main-form" action="'.iform_ajaxproxy_url($node, 'occurrence').'" method="POST" >
    	<input type="hidden" id="website_id" name="website_id" value="'.$args['website_id'].'" />
    	<input type="hidden" id="occurrence_image:path" name="occurrence_image:path" value="" />
    	<input type="hidden" id="occurrence:record_status" name="occurrence:record_status" value="C" />
        <input type="hidden" name="occurrence:use_determination" value="Y"/>    
        <input type="hidden" name="determination:taxa_taxon_list_id" value=""/> 
        <input type="hidden" name="determination:taxon_details" value=""/>  	
        <input type="hidden" name="determination:taxon_extra_info" value=""/>  	
        <input type="hidden" name="determination:comment" value=""/>  	
    	<input type="hidden" name="determination:determination_type" value="A" /> 
        <input type="hidden" name="determination:cms_ref" value="'.$uid.'" />
    	<input type="hidden" name="determination:email_address" value="'.$email.'" />
    	<input type="hidden" name="determination:person_name" value="'.$username.'" /> 
    	<input type="hidden" id="occurrence:id" name="occurrence:id" value="" disabled="disabled" />
	    <input type="hidden" id="determination:id" name="determination:id" value="" disabled="disabled" />
	    <input type="hidden" id="occurrence_image:id" name="occurrence_image:id" value="" disabled="disabled" />
        <input type="hidden" id="insect_picture_camera_attr" name="occAttr:'.$args['occurrence_picture_camera_attr_id'].'" value="" />
        <input type="hidden" id="insect_picture_datetime_attr" name="occAttr:'.$args['occurrence_picture_datetime_attr_id'].'" value="" />
	    <label for="occurrence:sample_id">'.lang::get('LANG_Session').' :</label>
	    <select id="occurrence:sample_id" name="occurrence:sample_id" value="" class="required" /></select>
	    '
 	.data_entry_helper::textarea(array(
	        'label'=>lang::get('LANG_Comment'),
    	    'fieldname'=>'occurrence:comment',
 			'suffixTemplate'=>'nosuffix'
	    ))
	.str_replace("\n", "", data_entry_helper::outputAttribute($occurrence_attributes[$args['number_attr_id']],$defNRAttrOptions))
 	.str_replace("\n", "", data_entry_helper::outputAttribute($occurrence_attributes[$args['foraging_attr_id']],$checkOptions)).'
	<div id="Foraging_Confirm"><label>'.lang::get('Foraging_Confirm').'</label><div class="control-box "><nobr><span><input type="radio" name="dummy_foraging_confirm" value="0" checked="checked"  /><label>'.lang::get('No').'</label></span></nobr> &nbsp; <nobr><span><input type="radio" name="dummy_foraging_confirm" value="1" /><label>'.lang::get('Yes').'</label></span></nobr></div></div></form><br />
    <div class="button-container">
      <span id="cc-4-valid-insect-button" class="ui-state-default ui-corner-all save-button">'.lang::get('LANG_Validate_Insect').'</span>
      <span id="cc-4-delete-insect-button" class="ui-state-default ui-corner-all delete-button">'.lang::get('LANG_Delete_Insect').'</span>
    </div>
  </div>
  <div id="cc-4-footer" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active poll-section-footer">
    <div id="cc-4-valid-photo-button" class="ui-state-default ui-corner-all save-button">'.lang::get('LANG_Validate_Photos').'</div>
  </div>
</div>
<div style="display:none" />
    <form id="cc-4-delete-insect" action="'.iform_ajaxproxy_url($node, 'occurrence').'" method="POST">
       <input type="hidden" name="website_id" value="'.$args['website_id'].'" />
       <input type="hidden" name="occurrence:use_determination" value="Y"/>    
       <input type="hidden" name="occurrence:id" value="" />
       <input type="hidden" name="occurrence:sample_id" value="" />
       <input type="hidden" name="occurrence:deleted" value="t" />
    </form>
</div>';

    data_entry_helper::$javascript .= "
jQuery('#Foraging_Confirm').hide();
jQuery('[name=occAttr\\:".$args['foraging_attr_id']."],[name^=occAttr\\:".$args['foraging_attr_id'].":]').change(function(){
	jQuery('[name=dummy_foraging_confirm]').filter('[value=0]').attr('checked',true);
	checkForagingStatus(false);
});

insectIDstruc = {
	type: 'insect',
	selector: '#cc-4-insect-identify',
	mainForm: '#cc-4-main-form',
	timeOutTimer: null,
	pollTimer: null,
	pollFile: '',
	invokeURL: '".$args['ID_tool_insect_url']."',
	pollURL: '".str_replace("{HOST}", $_SERVER['HTTP_HOST'], $args['ID_tool_insect_poll_dir'])."',
	name: 'insectIDstruc',
	taxaList: insectTaxa
};

jQuery('#insect-id-button').click(function(){
	idButtonPressed(insectIDstruc);
});
jQuery('#insect-id-cancel').click(function(){
	pollReset(insectIDstruc);
});
jQuery('#insect-id-cancel').hide();
jQuery('#cc-4-insect-identify select[name=insect\\:taxa_taxon_list_id]').change(function(){
	pollReset(insectIDstruc);
	taxonChosen(insectIDstruc);
});
jQuery('#id-insect-later').change(function (){
	pollReset(insectIDstruc);
	idLater(insectIDstruc);
});

// Insect upload picture form.
$('#cc-4-insect-upload').ajaxForm({ 
		async: false,
		dataType:  'json', 
        beforeSubmit:   function(data, obj, options){
        	if(jQuery('#cc-4-insect-upload input[name=upload_file]').val() == '')
        		return false;
        	$('#cc-4-insect-image').empty();
        	$('#cc-4-insect-image').addClass('loading');
        	jQuery('form#cc-4-main-form input[name=occurrence_image\\:path]').val('');
        },
        success:   function(data){
        	if(data.success == true){
	        	// There is only one file
	        	jQuery('form#cc-4-main-form input[name=occurrence_image\\:path]').val(data.files[0].filename);
	        	jQuery('#insect_picture_camera_attr').val(data.files[0].EXIF_Camera_Make);
	        	jQuery('#insect_picture_datetime_attr').val(data.files[0].EXIF_DateTime);
	        	insertImage('med-'+data.files[0].filename, jQuery('#cc-4-insect-image'), ".$args['Insect_Image_Ratio'].");
				jQuery('#cc-4-insect-upload input[name=upload_file]').val('');
			}  else
				alertIndiciaError(data);
  		},
  		complete: function(){
			$('#cc-4-insect-image').removeClass('loading');
  		}
});

$('#cc-4-main-form').ajaxForm({ 
	async: false,
	dataType:  'json', 
    beforeSubmit:   function(data, obj, options){
    	var valid = true;
    	clearErrors('form#cc-4-main-form');
    	clearErrors('#cc-4-insect-identify');
    	if (!jQuery('form#cc-4-main-form > input').valid()) { valid = false; }
		if (!validateRequiredField('occurrence\\:sample_id', 'form#cc-4-main-form')) { valid = false; }
		if (!validateRadio('occAttr\\:".$args['number_attr_id']."', obj)) { valid = false; }
    	if(data[1].value == '' ){
    		myScrollTo('#cc-4-insect-upload');
			alert(\"".lang::get('LANG_Must_Provide_Insect_Picture')."\");
			valid = false;
		}
		if (jQuery('#id-insect-later').attr('checked') == ''){
			prepPhotoReelForNew(false, jQuery('#cc-4-main-form').find('[name=occurrence\\:id]').val());
			data[findID('determination:taxa_taxon_list_id', data)].value = jQuery('#cc-4-insect-identify select[name=insect\\:taxa_taxon_list_id]').val();
			data[findID('determination:taxon_details', data)].value = jQuery('#cc-4-insect-identify [name=insect\\:taxon_details]').val();
			data[findID('determination:taxon_extra_info', data)].value = jQuery('#cc-4-insect-identify [name=insect\\:taxon_extra_info]').val();
			data[findID('determination:comment', data)].value = jQuery('[name=insect\\:comment]').val();
			data[findID('determination:determination_type', data)].value = jQuery('#cc-4-insect-identify [name=insect\\:determination_type]').val();
			if (jQuery('#cc-4-insect-identify [name=insect\\:taxon_details]').val() == ''){
				if (!validateRequiredField('insect\\:taxa_taxon_list_id', '#cc-4-insect-identify')) {
					valid = false;
  				} else {
					data.push({name: 'determination:taxa_taxon_list_id_list[]', value: ''});
  				}
			} else {
				var toolValues = jQuery('#insect-id-button').data('toolRetValues');
				for(var i = 0; i<toolValues.length; i++){
					data.push({name: 'determination:taxa_taxon_list_id_list[]', value: toolValues[i]});
				}			
			}
		} else {
			prepPhotoReelForNew(true, jQuery('#cc-4-main-form').find('[name=occurrence\\:id]').val());
			data.splice(4,8); // remove determination entries.
		}
   		if ( valid == false ) {
			myScrollToError();
			return false;
		};
		jQuery('#cc-4-valid-insect-button').addClass('loading-button');
		return true;
	},
    success:   function(data){
       	if(data.success == 'multiple records' && data.outer_table == 'occurrence'){
       		addNewToPhotoReel(data.outer_id);
			window.scroll(0,0);
        } else
			alertIndiciaError(data);
	},
    complete: function (){
  		jQuery('.loading-button').removeClass('loading-button');
  	}
});

validateInsectPanel = function(){
	if(jQuery('#cc-4:visible').length == 0) return true; // panel is not visible so no data to fail validation.
	if(jQuery('#cc-4-body:visible').length == 0) return true; // body hidden so data already been validated successfully.
	if(!validateInsect()){ return false; }
	clearInsect();
  	jQuery('#cc-4').foldPanel();
	return true;
};

clearInsect = function(){
	jQuery('#cc-4-main-form').resetForm();
	jQuery('#insect-id-button').data('toolRetValues',[]);
	jQuery('#insect_taxa_list').empty();
	jQuery('[name=insect\\:taxa_taxon_list_id],[name=insect\\:taxon_extra_info],[name=insect\\:comment],[name=insect\\:taxon_details]').val('');
    jQuery('[name=insect\\:determination_type]').val('A'); 
	jQuery('#id-insect-later').removeAttr('checked').removeAttr('disabled');
    jQuery('#cc-4-main-form').find('[name=determination\\:cms_ref]').val('".$uid."');
    jQuery('#cc-4-main-form').find('[name=determination\\:email_address]').val('".$email."');
    jQuery('#cc-4-main-form').find('[name=determination\\:person_name]').val('".$username."'); 
    jQuery('#cc-4-main-form').find('[name=determination\\:determination_type]').val('A'); 
    jQuery('#cc-4-main-form').find('[name=occurrence_image\\:path]').val('');
	jQuery('#cc-4-main-form').find('[name=occurrence\\:id],[name=occurrence_image\\:id],[name=determination\\:id]').val('').attr('disabled', 'disabled');
	// First rename, to be safe. Then add [] to multiple choice checkboxes.
	jQuery('#cc-4-main-form').find('[name^=occAttr\\:]').each(function(){
		var name = jQuery(this).attr('name').split(':');
		if(name[1].indexOf('[]') > 0) name[1] = name[1].substr(0, name[1].indexOf('[]'));
		jQuery(this).attr('name', 'occAttr:'+name[1]);
	});
	jQuery('#cc-4-main-form').find('[name^=occAttr\\:]').filter(':checkbox').removeAttr('checked').each(function(){
		var myName = jQuery(this).attr('name').split(':');
		var similar = jQuery('[name=occAttr\\:'+name[1]+'],[name=occAttr\\:'+name[1]+'\\[\\]]').filter(':checkbox');
		if(similar.length > 1) jQuery(this).attr('name', 'occAttr:'+name[1]+'[]');
	});
    jQuery('#cc-4-insect-image').empty();
    populateSessionSelect();
    jQuery('#Foraging_Confirm').hide();
    jQuery('[name=dummy_foraging_confirm]').filter('[value=0]').attr('checked',true);
    jQuery('#cc-4').find('.inline-error').remove();
	jQuery('.currentPhoto').removeClass('currentPhoto');
	jQuery('.blankPhoto').addClass('currentPhoto');
};

loadInsect = function(id){
	clearInsect();
	jQuery('form#cc-4-main-form > input[name=occurrence\\:id]').removeAttr('disabled').val(id);
	loadAttributes('form#cc-4-main-form', 'occurrence_attribute_value', 'occurrence_attribute_id', 'occurrence_id', id, 'occAttr', true, true);
	loadImage('occurrence_image', 'occurrence_id', 'occurrence\\:id', id, '#cc-4-insect-image', ".$args['Insect_Image_Ratio'].", true);
	$.getJSON(\"".$svcUrl."/data/occurrence/\" + id +
          \"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&callback=?\", function(data) {
	    if(!(data instanceof Array)){
   			alertIndiciaError(data);
   		} else if (data.length>0) {
	        jQuery('form#cc-4-main-form > [name=occurrence\\:sample_id]').val(data[0].sample_id);
			jQuery('form#cc-4-main-form > textarea[name=occurrence\\:comment]').val(data[0].comment);
  		} else {
   			alertIndiciaError({error : \"".lang::get('Internal Error 10: no insect data available for id ')."\"+id});
  		}
	});
	$.getJSON(\"".$svcUrl."/data/determination?occurrence_id=\" + id +
          \"&mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&orderby=id&deleted=f&callback=?\", function(data) {
        if(!(data instanceof Array)){
   			alertIndiciaError(data);
   		} else loadDetermination(data, insectIDstruc);
	});	
	jQuery('.currentPhoto').removeClass('currentPhoto');
	jQuery('[occId='+id+']').addClass('currentPhoto');
}

prepPhotoReelForNew = function(notID, id){
	var container;
	if(id == '')
		container = jQuery('<div/>').addClass('thumb').insertBefore('.blankPhoto').attr('occId', 'new');
	else
		container = jQuery('[occId='+id+']').empty();
	if(notID){
		var img = new Image();
		var src = '".$base.drupal_get_path('module', 'iform')."/client_helpers/prebuilt_forms/images/boundary-unknown.png';
		img = jQuery(img).attr('src', src).attr('width', container.width()).attr('height', container.height()).addClass('thumb-image').addClass('unidentified').appendTo(container);
	}
}

addNewToPhotoReel = function(occId){
	var container = jQuery('[occId='+occId+']');
	if(container.length == 0) {
		container = jQuery('[occId=new]');
		container.attr('occId', occId.toString()).click(function () {
		    setInsect(occId)});
	}
	$.getJSON(\"".$svcUrl."/data/occurrence_image\" +
			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
			\"&occurrence_id=\" + occId + \"&callback=?\", function(imageData) {
		if(!(imageData instanceof Array)){
			alertIndiciaError(imageData);
		} else if (imageData.length>0) {
			var img = new Image();
			var container = jQuery('[occId='+imageData[0].occurrence_id+']');
			if(container.children().length>0){
				var background = '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."thumb-'+imageData[0].path;
				container.children().css('background', 'url('+background+')').css('background-size','100% 100%');
			} else {
				jQuery(img).attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."thumb-'+imageData[0].path)
				    .attr('width', container.width()).attr('height', container.height()).addClass('thumb-image').appendTo(container);
			}
		} else {
			alertIndiciaError({error : \"".lang::get('Internal Error 11: image could not be loaded into photoreel for insect ')."\"+occId});
		}});
}

addExistingToPhotoReel = function(occId){
	var container = jQuery('[occId='+occId+']');
	if(container.length == 0)
		container = jQuery('<div/>').addClass('thumb').insertBefore('.blankPhoto').attr('occId', occId.toString()).click(function () {
		    setInsect(occId)});
	else
		container.empty();
	// we use the presence of the text to determine whether the 
	// insect has been identified or not. NB an insect tagged as unidentified (type = 'X') has actually been through the ID
	// process, so is not unidentified!!!
	jQuery.ajax({ 
        type: \"GET\", 
        url: \"".$svcUrl."/data/determination\" + 
    		\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
    		\"&reset_timeout=true&occurrence_id=\" + occId + \"&orderby=id&deleted=f&callback=?\", 
        success: function(detData) {
	    	if(!(detData instanceof Array)){
   				alertIndiciaError(detData);
   			} else if (detData.length>0) {
				$.getJSON(\"".$svcUrl."/data/occurrence_image\" +
						\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
						\"&occurrence_id=\" + occId + \"&callback=?\", function(imageData) {
					if(!(imageData instanceof Array)){
						alertIndiciaError(imageData);
					} else if (imageData.length>0) {
						var img = new Image();
						var container = jQuery('[occId='+imageData[0].occurrence_id+']');
						jQuery(img).attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."thumb-'+imageData[0].path)
			    			.attr('width', container.width()).attr('height', container.height()).addClass('thumb-image').appendTo(container);
					} else {
						alertIndiciaError({error : \"".lang::get('Internal Error 12: image could not be loaded into photoreel for existing insect ')."\"+occId});
					}
				});
	    	} else { // is conceivable that insect is not identified yet -> does not have determinations
				$.getJSON(\"".$svcUrl."/data/occurrence_image\" +
						\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
						\"&occurrence_id=\" + occId + \"&callback=?\", function(imageData) {
					if(!(imageData instanceof Array)){
						alertIndiciaError(imageData);
					} else if (imageData.length>0) {
						var img = new Image();
						var container = jQuery('[occId='+imageData[0].occurrence_id+']');
						var src = '".$base.drupal_get_path('module', 'iform')."/client_helpers/prebuilt_forms/images/boundary-unknown.png';
						var background = '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."thumb-'+imageData[0].path;
						img = jQuery(img).attr('src', src).attr('width', container.width()).attr('height', container.height()).addClass('thumb-image').addClass('unidentified').appendTo(container);
						img.css('background', 'url('+background+')').css('background-size','100% 100%');
					} else {
						alertIndiciaError({error : \"".lang::get('Internal Error 12: image could not be loaded into photoreel for existing insect ')."\"+occId});
					}
				});
	    	}
  		}, 
    	dataType: 'json' 
    });
}

setInsect = function(id){
	// first close all the other panels, ensuring any data is saved.
	if(!validateCollectionPanel() || !validateStationPanel() || !validateSessionsPanel())
		return;
	jQuery('#cc-5').hidePanel();

	if(jQuery('#cc-4-body:visible').length == 0)
		jQuery('div#cc-4').unFoldPanel();
	else
		if(!validateInsect()){ return ; }
	loadInsect(id);
};

setNoInsect = function(){
	// first close all the other panels, ensuring any data is saved.
	if(!validateCollectionPanel() || !validateStationPanel() || !validateSessionsPanel())
		return;
		
	if(jQuery('#cc-4-body:visible').length == 0)
		jQuery('div#cc-4').unFoldPanel();
	else
		if(!validateInsect()){ return ; }
	clearInsect();
};

jQuery('.blankPhoto').click(setNoInsect);

// TODO separate photoreel out into own js
validateInsect = function(){
    clearErrors('form#cc-4-main-form');
    clearErrors('#cc-4-insect-identify');
    if(jQuery('form#cc-4-main-form > input[name=occurrence\\:id]').val() == '' &&
			jQuery('form#cc-4-main-form > input[name=occurrence_image\\:path]').val() == '' &&
			jQuery('[name=insect\\:taxa_taxon_list_id]').val() == '' &&
			jQuery('[name=insect\\:taxon_details]').val() == '' &&
			jQuery('[name=insect\\:comment]').val() == '' &&
			jQuery('[name=insect\\:taxon_extra_info]').val() == '' &&
			jQuery('form#cc-4-main-form > textarea[name=occurrence\\:comment]').val() == '' &&
			jQuery('[name=occAttr\\:".$args['number_attr_id']."],[name^=occAttr\\:".$args['number_attr_id']."\\:]').filter('[checked]').length == 0){
		return true;
	}
	var valid = true;
    if (!jQuery('form#cc-4-main-form > input').valid()) { valid = false; }
  	if (!validateRadio('occAttr\\:".$args['number_attr_id']."', 'form#cc-4-main-form')) { valid = false; }
	if (jQuery('#id-insect-later').attr('checked') == '' && jQuery('[name=insect\\:taxon_details]').val() == ''){
		if (!validateRequiredField('insect\\:taxa_taxon_list_id', '#cc-4-insect-identify')) { valid = false; }
	}
 	if (!validateRequiredField('occurrence\\:sample_id', 'form#cc-4-main-form')) { valid = false; }
	if(jQuery('form#cc-4-main-form input[name=occurrence_image\\:path]').val() == ''){
    	myScrollTo('#cc-4-insect-upload');
		alert(\"".lang::get('LANG_Must_Provide_Insect_Picture')."\");
		valid = false;
	}
	if(jQuery('[name=occAttr\\:".$args['foraging_attr_id']."],[name^=occAttr\\:".$args['foraging_attr_id'].":]').filter('[checked]').val()==1){
		if(jQuery('[name=dummy_foraging_confirm]').filter('[checked]').val()==0){
			valid = false;
	        var label = $('<p/>')
				.attr({'for': 'dummy_foraging_confirm'})
				.addClass('inline-error')
				.html(\"".lang::get('Foraging_Validation')."\");
			label.appendTo(jQuery('#Foraging_Confirm'));
		}
	}
	if(valid == false) {
		myScrollToError();
		return false;
	}
	jQuery('form#cc-4-main-form').submit();
	clearInsect();
	myScrollTo('.blankPhoto')
	return true;
}

$('#cc-4-valid-insect-button').click(validateInsect);

$('#cc-4-delete-insect-button').click(function() {
	var container = $(this).parent().parent();
	jQuery('#cc-4-delete-insect').find('[name=occurrence\\:id]').val(jQuery('#cc-4-main-form').find('[name=occurrence\\:id]').val()).removeAttr('disabled');
	jQuery('#cc-4-delete-insect').find('[name=occurrence\\:sample_id]').val(jQuery('#cc-4-main-form').find('[name=occurrence\\:sample_id]').val()).removeAttr('disabled');
	if(confirm(\"".lang::get('LANG_Confirm_Insect_Delete')."\")){
		if(jQuery('#cc-4-main-form').find('[name=occurrence\\:id]').filter('[disabled]').length == 0){
			jQuery('#cc-4-delete-insect').submit();
			jQuery('.currentPhoto').remove();
			jQuery('.blankPhoto').addClass('currentPhoto');
		}
		clearInsect();
		myScrollTo('.blankPhoto')
	}
});

$('#cc-4-delete-insect').ajaxForm({ 
		async: false,
		dataType:  'json', 
        beforeSubmit:   function(data, obj, options){
  			// Warning this assumes that the data is fixed position:
        	if(data[2].value == '') return false;
			if(data[3].value == ''){
				alertIndiciaError({error : \"".lang::get('Internal Error 13: sample id not set, so unsafe to save insect.')."\"});
				return false;
			}
        	jQuery('#cc-4-delete-insect').addClass('loading-button');
        	return true;
  		},
        success:   function(data){
       		if(data.success != 'multiple records' || data.outer_table != 'occurrence')
        		alertIndiciaError(data);
  		},
        complete: function (){
  			jQuery('.loading-button').removeClass('loading-button');
  		}
});

$('#cc-4-valid-photo-button').click(function(){
	if(!validateInsect()) return;
	jQuery('#cc-4').foldPanel();
	jQuery('#cc-5').showPanel();
	var numInsects = jQuery('#cc-4-photo-reel').find('.thumb').length - 1; // ignore blank
	var numUnidentified = jQuery('#cc-4-photo-reel').find('.unidentified').length;
	if(jQuery('#id-flower-later').attr('checked') != '' || (numInsects>0 && (numUnidentified/numInsects > (1-(".$args['percent_insects']."/100.0))))){
		jQuery('#cc-5-bad').show();
		jQuery('#cc-5-good,#cc-5-body2,#cc-5-complete-collection').hide();
    } else {
    	jQuery('#cc-5-bad').hide(); // photoreel is left showing
    	jQuery('#cc-5-good,#cc-5-body2,#cc-5-complete-collection').show();
	}
});
";
    
 	$r .= '
<div id="cc-5" class="poll-section">
  <div id="cc-5-body" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top poll-section-body"> 
   <p id="cc-5-good">'.lang::get('LANG_Can_Complete_Msg').'</p> 
   <p id="cc-5-bad">'.lang::get('LANG_Cant_Complete_Msg').'</p> 
   <div style="display:none" />
    <form id="cc-5-collection" action="'.iform_ajaxproxy_url($node, 'sample').'" method="POST">
       <input type="hidden" name="website_id" value="'.$args['website_id'].'" />
       <input type="hidden" name="sample:survey_id" value="'.$args['survey_id'].'" />
       <input type="hidden" name="sample:id" value="" />
       <input type="hidden" name="sample:date_start" value="2010-01-01"/>
       <input type="hidden" name="sample:date_end" value="2010-01-01"/>
       <input type="hidden" name="sample:date_type" value="D"/>
       <input type="hidden" name="sample:location_id" value="" />
       <input type="hidden" id="smpAttr:'.$args['complete_attr_id'].'" name="smpAttr:'.$args['complete_attr_id'].'" value="1" />
    </form>
   </div>
  </div>
  <div id="cc-5-body2" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active poll-section-body">
    <p><img src="'.$base.drupal_get_path('module', 'iform').'/media/images/exclamation.jpg" /> '.lang::get('LANG_Trailer_Head').' :</p>
    <ul>
      <li>'.lang::get('LANG_Trailer_Point_1').'</li>
      <li>'.lang::get('LANG_Trailer_Point_2').'</li>
      <li>'.lang::get('LANG_Trailer_Point_3').'</li>
      <li>'.lang::get('LANG_Trailer_Point_4').'</li>
    </ul>
  </div>
  <div id="cc-5-trailer" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active poll-section-footer">
    <div id="cc-5-complete-collection" class="ui-state-default ui-corner-all complete-button">'.lang::get('LANG_Complete_Collection').'</div>
  </div>
</div>';
data_entry_helper::$javascript .= "
$('#cc-5-collection').ajaxForm({ 
		async: false,
		dataType:  'json', 
        beforeSubmit:   function(data, obj, options){
            // double check that location id and sample id are filled in
			if(jQuery('#cc-1-collection-details input[name=location\\:id]').val() == ''){
				alertIndiciaError({error : \"".lang::get('Internal Error 14: location id not set, so unsafe to save collection.')."\"});
				return false;
			}
			if(jQuery('#cc-1-collection-details input[name=sample\\:id]').val() == ''){
				alertIndiciaError({error : \"".lang::get('Internal Error 15: sample id not set, so unsafe to save collection.')."\"});
				return false;
			}
			jQuery('#cc-5-complete-collection').addClass('loading-button');
        	data[findID('sample:id', data)].value = jQuery('#cc-1-collection-details input[name=sample\\:id]').val();
			data[findID('sample:date_start', data)].value = '';
			data[findID('sample:date_end', data)].value = '';
			date_start = '';
			date_end = '';
			jQuery('.poll-session-form').each(function(i){
				if(jQuery(this).find('input[name=sample\\:id]').val() != '') {
					var sessDate = jQuery(this).find('input[name=sample\\:date]').val();
					var sessDateDate = new Date(convertDate(sessDate)); // sessions are only on one date.
					if(date_start == '' || date_start > sessDateDate) {
						date_start = sessDateDate;
						data[findID('sample:date_start', data)].value = sessDate;
					}
					if(date_end == '' || date_end < sessDateDate) {
						date_end = sessDateDate;
						data[findID('sample:date_end', data)].value = sessDate;
					}
				}
			});
			if(data[findID('sample:date_start', data)].value == '') {
				alert(\"".lang::get('LANG_Session_Error')."\");
				jQuery('#cc-5-complete-collection').removeClass('loading-button');
				return false;
			}
			data[findID('sample:date_type', data)].value = (data[3].value == data[4].value ? 'D' : 'DD');
	       	jQuery('#cc-1-collection-details,#cc-2').find('[name=sample\\:date]:hidden').val(data[3].value);
  			data[findID('sample:location_id', data)].value = jQuery('#cc-1-collection-details input[name=location\\:id]').val();
       		data[7].name = jQuery('#cc-1-collection-details input[name^=smpAttr\\:".$args['complete_attr_id']."\\:]').attr('name');
       		return true;
  		},
        success:   function(data){
       		if(data.success == 'multiple records' && data.outer_table == 'sample'){
				$('#cc-6').showPanel();
  			}  else
				alertIndiciaError(data);
  		},
        complete: function (){
  			jQuery('.loading-button').removeClass('loading-button');
  		}
});
$('#cc-5-complete-collection').click(function(){
	jQuery('#cc-5-complete-collection').addClass('loading-button');
	jQuery('#cc-2,#cc-3,#cc-4,#cc-5').hidePanel();
	jQuery('.reinit-button').hide();
	jQuery('.mod-button').hide();
	jQuery('#cc-5-collection').submit();
});
";

 	$r .= '
<div id="cc-6" class="poll-section">
  <div id="cc-6-body" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top poll-section-body"> 
   <p>'.lang::get('LANG_Final_1').'</p> 
   <p>'.lang::get('LANG_Final_2').'</p> 
   </div>
  <div id="cc-6-footer" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active poll-section-footer">
    <a id="cc-6-consult-collection" href="" class="ui-state-default ui-corner-all link-button">'.lang::get('LANG_Consult_Collection').'</a>
    <a href="'.url('node/'.$node->nid).'" class="ui-state-default ui-corner-all link-button">'.lang::get('LANG_Create_New_Collection').'</a>
  </div>
</div>
</div></div>
<script type="text/javascript">
/* <![CDATA[ */
document.write("</div>");
/* ]]> */</script>
';
 
data_entry_helper::$javascript .= "
loadAttributes = function(formsel, attributeTable, attributeKey, key, keyValue, prefix, reset_timeout, required){
	// first need to remove any hidden multiselect checkbox unclick fields
	jQuery(formsel).find('[name^='+prefix+'\\:]').filter('.multiselect').remove();
	// rename, to be safe. Then add [] to multiple choice checkboxes.
	jQuery(formsel).find('[name^='+prefix+'\\:]').each(function(){
		var name = jQuery(this).attr('name').split(':');
		if(name[1].indexOf('[]') > 0) name[1] = name[1].substr(0, name[1].indexOf('[]'));
		jQuery(this).attr('name', name[0]+':'+name[1]);
	});
	jQuery(formsel).find('[name^='+prefix+'\\:]').filter(':checkbox').removeAttr('checked').each(function(){
		var myName = jQuery(this).attr('name').split(':');
		var similar = jQuery('[name='+myName[0]+'\\:'+myName[1]+'],[name='+myName[0]+'\\:'+myName[1]+'\\[\\]]').filter(':checkbox');
		if(similar.length > 1)
			jQuery(this).attr('name', myName[0]+':'+myName[1]+'[]');
	});
    jQuery.ajax({ 
        type: \"GET\", 
        url: \"".$svcUrl."/data/\" + attributeTable + \"?mode=json&view=list\" +
        	(reset_timeout ? \"&reset_timeout=true\" : \"\") + \"&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&\" + key + \"=\" + keyValue + \"&callback=?\", 
        data: {},
        myFormsel: formsel,
        myAttributeKey: attributeKey,
        success: function(attrdata) {
		  if(!(attrdata instanceof Array)){
   			alertIndiciaError(attrdata);
   		  } else if (attrdata.length>0) {
			for (var i=0;i<attrdata.length;i++){
				// attribute list views now use id rather than meaning_id for the term.
				// This means (1) that the term will be either not present or wrong, as we are storing the meaning_id.
				// and (2) only one row will be returned per attribute.
				// As we already use raw_value below, the only change is no need to check the iso field.
				if (attrdata[i].id){
					var radiobuttons = jQuery(this.myFormsel).find('[name='+prefix+'\\:'+attrdata[i][this.myAttributeKey]+'],[name^='+prefix+'\\:'+attrdata[i][this.myAttributeKey]+'\\:]').filter(':radio');
					var multicheckboxes = jQuery(this.myFormsel).find('[name='+prefix+'\\:'+attrdata[i][this.myAttributeKey]+'\\[\\]],[name^='+prefix+'\\:'+attrdata[i][this.myAttributeKey]+':]').filter(':checkbox');
					var boolcheckbox = jQuery(this.myFormsel).find('[name='+prefix+'\\:'+attrdata[i][this.myAttributeKey]+'],[name^='+prefix+'\\:'+attrdata[i][this.myAttributeKey]+':]').filter(':checkbox');
					if(radiobuttons.length > 0){ // radio buttons all share the same name, only one checked.
						radiobuttons
							.attr('name', prefix+':'+attrdata[i][this.myAttributeKey]+':'+attrdata[i].id)
							.filter('[value='+attrdata[i].raw_value+']')
							.attr('checked', 'checked');
					} else if(multicheckboxes.length > 0){ // individually named
						multicheckboxes = multicheckboxes.filter('[value='+attrdata[i].raw_value+']')
							.attr('name', prefix+':'+attrdata[i][this.myAttributeKey]+':'+attrdata[i].id)
							.attr('checked', 'checked');
						multicheckboxes.each(function(){
							jQuery('<input type=\"hidden\" value=\"0\" class=\"multiselect\">').attr('name', jQuery(this).attr('name')).insertBefore(this);
						});
					} else if(boolcheckbox.length > 0){ // has extra hidden field to force zero if unchecked.
						jQuery(this.myFormsel).find('[name='+prefix+'\\:'+attrdata[i][this.myAttributeKey]+'],[name^='+prefix+'\\:'+attrdata[i][this.myAttributeKey]+':]')
							.attr('name', prefix+':'+attrdata[i][this.myAttributeKey]+':'+attrdata[i].id);
						if (attrdata[i].raw_value == '1')
							boolcheckbox.attr('checked', 'checked');
					} else if (prefix == 'smpAttr' && attrdata[i][this.myAttributeKey] == ".$args['complete_attr_id'].") {
						// The hidden closed attributes are special: these have forced values, and are used to control the state. Do not update their values.
						jQuery(this.myFormsel).find('[name='+prefix+'\\:'+attrdata[i][this.myAttributeKey]+']')
							.attr('name', prefix+':'+attrdata[i][this.myAttributeKey]+':'+attrdata[i].id);
					} else {
						jQuery(this.myFormsel).find('[name='+prefix+'\\:'+attrdata[i][this.myAttributeKey]+']')
							.attr('name', prefix+':'+attrdata[i][this.myAttributeKey]+':'+attrdata[i].id)
							.val(attrdata[i].raw_value);
					}
				}
			}
		  } else if (required){
			alertIndiciaError({error : \"".lang::get('Internal Error 16: could not load attributes ')."\"+attributeTable+' '+keyName+' '+keyValue});
		  }
		  checkProtocolStatus('leave');
		  populateSessionSelect();
		  checkForagingStatus(true);
		},
		dataType: 'json'
	});
}

loadImage = function(imageTable, key, keyName, keyValue, target, ratio, required){
					// location_image, location_id, location:id, 1, #cc-4-insect-image
	$.getJSON(\"".$svcUrl."/data/\" + imageTable +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&\" + key + \"=\" + keyValue + \"&callback=?\", function(imageData) {
		if(!(imageData instanceof Array)){
   			alertIndiciaError(imageData);
   		} else if (imageData.length>0) {
			var form = jQuery('input[name='+keyName+'][value='+keyValue+']').parent();
			jQuery('[name='+imageTable+'\\:id]', form).val(imageData[0].id).removeAttr('disabled');
			jQuery('[name='+imageTable+'\\:path]', form).val(imageData[0].path);
	        insertImage('med-'+imageData[0].path, jQuery(target), ratio);
		} else if(required){
			alertIndiciaError({error : \"".lang::get('Internal Error 17: could not load ')."\"+imageTable+' '+keyName+' '+keyValue});
		}
	});
}

loadDetermination = function(detData, toolStruc){
	jQuery('#'+toolStruc.type+'_taxa_list').empty();
	jQuery('#'+toolStruc.type+'-id-button').data('toolRetValues', []);
	jQuery(toolStruc.mainForm+' input[name=determination\\:id]').val('').attr('disabled', 'disabled');
	jQuery('#id-'+toolStruc.type+'-later').removeAttr('checked').removeAttr('disabled');
	jQuery('[name='+toolStruc.type+'\\:determination_type]').val('A');
	jQuery(toolStruc.mainForm+' input[name=determination\\:determination_type]').val('A');
	jQuery('[name='+toolStruc.type+'\\:taxon_details],[name='+toolStruc.type+'\\:taxa_taxon_list_id],[name='+toolStruc.type+'\\:comment],[name='+toolStruc.type+'\\:taxon_extra_info]').val('');

	if (detData.length>0) {
		jQuery('#id-'+toolStruc.type+'-later').attr('disabled', 'disabled');
		jQuery(toolStruc.mainForm+' input[name=determination\\:id]').val(detData[0].id).removeAttr('disabled');
		jQuery('[name='+toolStruc.type+'\\:taxon_details]').val(detData[0].taxon_details);
		jQuery('[name='+toolStruc.type+'\\:determination_type]').val(detData[0].determination_type);
		jQuery('[name='+toolStruc.type+'\\:taxa_taxon_list_id]').val(detData[0].taxa_taxon_list_id == null ? '' : detData[0].taxa_taxon_list_id);
		jQuery('[name='+toolStruc.type+'\\:comment]').val(detData[0].comment);
		jQuery('[name='+toolStruc.type+'\\:taxon_extra_info]').val(detData[0].taxon_extra_info == null ? '' : detData[0].taxon_extra_info);
		if(detData[0].determination_type == 'X'){
			jQuery('#'+toolStruc.type+'_taxa_list').append(\"".lang::get('LANG_Taxa_Unknown_In_Tool')."\");
		} else {
			var resultsIDs = [];
			if(detData[0].taxa_taxon_list_id_list != null && detData[0].taxa_taxon_list_id_list != ''){
			  	var resultsText = \"".lang::get('LANG_Taxa_Returned')."<br />{ \";
			  	resultsIDs = detData[0].taxa_taxon_list_id_list.substring(1, detData[0].taxa_taxon_list_id_list.length - 1).split(',');
			  	for(var j=0; j < resultsIDs.length; j++){
					for(i = 0; i< toolStruc.taxaList.length; i++)
						if(toolStruc.taxaList[i].id == resultsIDs[j])
							resultsText = resultsText + (j == 0 ? '' : '<br />&nbsp;&nbsp;') + htmlspecialchars(toolStruc.taxaList[i].taxon);
		  		}
	  			if(resultsIDs.length>1 || resultsIDs[0] != '')
					jQuery('#'+toolStruc.type+'_taxa_list').append(resultsText+ ' }');
			}
			jQuery('#'+toolStruc.type+'-id-button').data('toolRetValues', resultsIDs);
  		}
	} else {
		jQuery('#id-'+toolStruc.type+'-later').attr('checked', 'checked');
	}
};
// load in any existing incomplete collection.
// general philosophy is that you are taken back to the stage last verified.
// Load in the first if there are more than one. Use the internal report which provides my collections.
// Requires that there is an attribute for completeness, and one for the CMS
// load the data in the order it is entered, so can stop when get to the point where the user finished.
// have to reset the entire form first...
jQuery('.poll-section').resetPanel();
// Default state: hide everything except the collection details block.
jQuery('.poll-section').hidePanel();
jQuery('#cc-1').showPanel();
jQuery('.reinit-button').hide();
addSession();
jQuery('#flower-id-button').data('toolRetValues',[]);
jQuery('#insect-id-button').data('toolRetValues',[]);
jQuery('#flower_taxa_list').empty();
jQuery('#insect_taxa_list').empty();
preloading=true;
preloadIDs={location_loaded : false,
			sessions: []};
setPreloadStage = function(stage){
	preloadStage=stage;
	$('.poll-loading-extras').empty().text(stage);
};
setPreloadStage(1);

jQuery('#cc-1').ajaxStop(function(){
	if(!preloading) return;
	switch(preloadStage){
		case 1: // just finished the report request
			if(typeof preloadIDs.sample_id == 'undefined' || typeof preloadIDs.location_id == 'undefined'){
				$('.loading-panel').remove();
				$('.poll-loading-hide').show();
				preloading=false;
				return;
			}
			setPreloadStage(2);
			// main sample date is only set when collection is completed, so don't need to load collection sample itself, and keep date as default 
			loadAttributes('#cc-1-collection-details,#cc-5-collection', 'sample_attribute_value', 'sample_attribute_id', 'sample_id', preloadIDs.sample_id, 'smpAttr', false, true);
  			jQuery.getJSON('".$svcUrl."/data/location/' + preloadIDs.location_id +
          					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
          					\"&callback=?\", 
					function(locationdata) {
		    		  if(!(locationdata instanceof Array)){
   						alertIndiciaError(locationdata);
   					  } else if (locationdata.length>0) {
	    				jQuery('input[name=location\\:name]').val(locationdata[0].name);
       					jQuery('input[name=sample\\:location_name]').val(locationdata[0].name); // make sure the 2 coincide
	    				jQuery('input[name=locations_website\\:website_id]').attr('disabled', 'disabled');
						// The location only holds a valid place if the floral station has been saved: otherwise it holds a default value.
						// we use the centroid_sref_system to indicate this: when the initial save is done the system is 900913,
						// but when a user has loaded one it is in 4326
						// NB the location geometry is stored in centroid, due to restrictions in location model.
						if(locationdata[0].centroid_sref_system == 4326 && locationdata[0].centroid_sref != oldDefaultSref){
							jQuery('input[name=location\\:centroid_sref]').val(locationdata[0].centroid_sref);
							jQuery('input[name=location\\:centroid_sref_system]').val(locationdata[0].centroid_sref_system); // note this will change the 900913 in cc-1 to 4326
							jQuery('input[name=location\\:centroid_geom]').val(locationdata[0].centroid_geom);
							var parts=locationdata[0].centroid_sref.split(' ');
							var refx = parts[0].split(',');
							jQuery('input[name=place\\:lat]').val(refx[0]);
							jQuery('input[name=place\\:long]').val(parts[1]);
  						}
  						preloadIDs.location_loaded=true;
					  } else {
						alertIndiciaError({error : \"".lang::get('Internal Error 18: could not load data for location ')."\"+data[i].location_id});
					  }});
			break;
		case 2: // just finished the collection attributes and the location.
			if(preloadIDs.location_loaded==false)
				return alertIndiciaError({error : \"".lang::get('Internal Error 19: could not load data for location ')."\"+preloadIDs.location_id});
			if(jQuery('[name=smpAttr\\:".$args['protocol_attr_id']."]').length > 0)
				return alertIndiciaError({error : \"".lang::get('Internal Error 20: could not load attributes for sample ')."\"+preloadIDs.sample_id});
			$('#cc-1').foldPanel();
			checkProtocolStatus(true);
			$('#cc-2').showPanel();
			setPreloadStage(3);
			// now load floral station stuff.
			loadAttributes('#cc-2-floral-station', 'location_attribute_value', 'location_attribute_id', 'location_id', preloadIDs.location_id, 'locAttr', false, false);
			loadImage('location_image', 'location_id', 'location\\:id', preloadIDs.location_id, '#cc-2-environment-image', ".$args['Environment_Image_Ratio'].", false);
			jQuery.getJSON(\"".$svcUrl."/data/occurrence/\" +
          					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
          					\"&sample_id=\"+preloadIDs.sample_id+\"&deleted=f&callback=?\", 
					function(flowerData) {
          			  // there will only be an occurrence if the floral station panel has previously been displayed & validated. 
		    		  if(!(flowerData instanceof Array)){
						alertIndiciaError(flowerData);
					  } else if (flowerData.length>0) { // do we need another >1 check as well?
						preloadIDs.flower_id = flowerData[0].id;
						jQuery('form#cc-2-floral-station > input[name=occurrence\\:sample_id]').val(preloadIDs.sample_id);
						jQuery('form#cc-2-floral-station > input[name=occurrence\\:id]').val(flowerData[0].id).removeAttr('disabled');
    	   			  }});
			break;
		case 3: // just finished the location attributes, location image and flower.
			// all must be present or none at all: but location_attributes are all optional.
			if(typeof preloadIDs.flower_id == 'undefined' &&
					jQuery('[name=location_image\\:id]').val() == '') {
				$('.loading-panel').remove();
				$('.poll-loading-hide').show();
				buildMap();
				preloading=false;
				return;
			}
			if(typeof preloadIDs.flower_id == 'undefined')
				return alertIndiciaError({error : \"".lang::get('Internal Error 21: could not load flower data for collection ')."\"+preloadIDs.sample_id});
			if(jQuery('[name=location_image\\:id]').val() == '')
				return alertIndiciaError({error : \"".lang::get('Internal Error 23: could not load environment image for location ')."\"+preloadIDs.location_id});
			setPreloadStage(4);
			loadAttributes('#cc-2-floral-station', 'occurrence_attribute_value', 'occurrence_attribute_id', 'occurrence_id', preloadIDs.flower_id, 'occAttr', false, true);
			loadImage('occurrence_image', 'occurrence_id', 'occurrence\\:id', preloadIDs.flower_id, '#cc-2-flower-image', ".$args['Flower_Image_Ratio'].", true);
			jQuery.getJSON(\"".$svcUrl."/data/determination\" + 
					\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&occurrence_id=\"+preloadIDs.flower_id+\"&orderby=id&deleted=f&callback=?\", 
				function(detData) {
					if(!(detData instanceof Array)){
   						alertIndiciaError(detData);
   					 } else loadDetermination(detData, flowerIDstruc);
  				});
			break;
		case 4: // just finished the flower attributes, flower image and optional flower determination. Attrs and image mandatory at this point.
			if(jQuery('#cc-2-floral-station > input[name=occurrence_image\\:id]').val() == '')
				return alertIndiciaError({error : \"".lang::get('Internal Error 24: could not load image for flower ')."\"+preloadIDs.flower_id});
			if(jQuery('[name=occAttr\\:".$args['flower_type_attr_id']."]').length>0)
				return alertIndiciaError({error : \"".lang::get('Internal Error 25: could not load attributes for flower ')."\"+preloadIDs.flower_id});
			setPreloadStage(5);
			jQuery('#cc-2').foldPanel();
			jQuery('#cc-3').showPanel();
			jQuery.getJSON(\"".$svcUrl."/data/sample\" + 
					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&parent_id=\"+preloadIDs.sample_id+\"&callback=?\", 
				function(sessiondata) {
					if(!(sessiondata instanceof Array)){
						alertIndiciaError(sessiondata);
					} else if (sessiondata.length>0) { // may have zero sessions
						sessionCounter = 0;
						jQuery('#cc-3-body').empty();
						for (var i=0;i<sessiondata.length;i++){
							var thisSession = addSession();
							preloadIDs.sessions.push({id : sessiondata[i].id, div : thisSession});
							jQuery('input[name=sample\\:id]', thisSession).val(sessiondata[i].id).removeAttr('disabled');
							jQuery('input[name=sample\\:date]', thisSession).val(sessiondata[i].date_start);
							jQuery('input[name=dummy_date]', thisSession).datepicker('disable').datepicker('setDate', new Date(convertDate(sessiondata[i].date_start))).datepicker('enable');
							// fold this session.
							thisSession.show();
							thisSession.children(':first').show().children().show();
							thisSession.children().not(':first').hide();
						}
						populateSessionSelect();
					}});
			break;
		case 5: // just finished the sessions. no error situations
			if(preloadIDs.sessions.length == 0){
				$('.loading-panel').remove();
				$('.poll-loading-hide').show();
				preloading=false;
				return;
			}
			setPreloadStage(6);
			$('#cc-3').foldPanel();
			$('#cc-4').showPanel();
			populateSessionSelect();
			var sessionIDs = [];
			for (var i=0;i<preloadIDs.sessions.length;i++){
				loadAttributes(preloadIDs.sessions[i].div, 'sample_attribute_value', 'sample_attribute_id', 'sample_id', preloadIDs.sessions[i].id, 'smpAttr', false, true);
				sessionIDs.push(preloadIDs.sessions[i].id);
			}
			$.getJSON(\"".$svcUrl."/data/occurrence/\" +
						\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&orderby=id\" +
						\"&deleted=f&callback=?&query=\"+escape(escape(JSON.stringify({'in': {'sample_id': sessionIDs}}))),
				function(insectData) {
					if(!(insectData instanceof Array)) return alertIndiciaError(insectData);
					if (insectData.length>0)
						for (var j=0;j<insectData.length;j++)
							addExistingToPhotoReel(insectData[j].id);
			});
			break;
		case 6: // just finished the session attributes and insects.
			// at this point the insects are optional, as are their determinations, so can't check for their presence.
			for (var i=0;i<preloadIDs.sessions.length;i++){ // check attributes loaded for each session
				if(jQuery('[name=smpAttr\\:".$args['start_time_attr_id']."]', preloadIDs.sessions[i].div).length>0)
					return alertIndiciaError({error : \"".lang::get('Internal Error 26: could not load attributes for session ')."\"+preloadIDs.sessions[i].id});
			}
			$('.loading-panel').remove();
			$('.poll-loading-hide').show();
			preloading=false;
			break;
  }
});
jQuery.getJSON(\"".$svcUrl."\" + \"/report/requestReport?report=reports_for_prebuilt_forms/poll_my_collections.xml&reportSource=local&mode=json\" +
			\"&auth_token=".$readAuth['auth_token']."&reset_timeout=true&nonce=".$readAuth["nonce"]."\" + 
			\"&survey_id=".$args['survey_id']."&userID_attr_id=".$args['uid_attr_id']."&userID=".$uid."&complete_attr_id=".$args['complete_attr_id']."&callback=?\", 
	function(data) {
	if(!(data instanceof Array)){
   		alertIndiciaError(data);
   	  } else if (data.length>0) { // could have zero length
		var i;
       	for ( i=0;i<data.length;i++) {
       		if(data[i].completed == '0'){
       			jQuery('#cc-1-collection-details,#cc-1-delete-collection,#cc-2').find('[name=sample\\:id]').val(data[i].id).removeAttr('disabled');
		    	jQuery('[name=location\\:id],[name=sample\\:location_id]').val(data[i].location_id).removeAttr('disabled');
       			jQuery('#cc-6-consult-collection').attr('href', '".url('node/'.$args['gallery_node'])."'+'?collection_id='+data[i].id);
       			preloadIDs.sample_id = data[i].id;
       			preloadIDs.location_id = data[i].location_id;
				// only use the first one which is not complete..
				return;
			}
		}
      }
    });
  ";
// because of the use of getJson to retrieve the data - which is asynchronous, the use of the normal loading_block_end
// is not practical - it will do its stuff before the data is loaded, defeating the purpose. Also it uses hide (display:none)
// which is a no-no in relation to the map. This means we have to dispense with the slow fade in.
// it is also complicated by the attibutes and images being loaded asynchronously - and non-linearly.
// Do the best we can! 
//    data_entry_helper::$onload_javascript = "jQuery('.my-loading-hide').addClass('loading-hide').removeClass('my-loading-hide');\n".data_entry_helper::$onload_javascript."\nbuildMap();";
	data_entry_helper::$onload_javascript .= "\nbuildMap();";
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
    return array('pollenators.css');
  }
}