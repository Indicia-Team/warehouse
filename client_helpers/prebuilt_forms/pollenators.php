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
     array(
//        array(
//          'name'=>'spatial_systems',
//          'caption'=>'Allowed Spatial Ref Systems',      
//          'description'=>'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code number such as 4326).',
//          'type'=>'string',
//          'group'=>'Map'
//        ),
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
          'description'=>'Indicia ID for the location attribute that stores how far the nearest house is.',
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
      )

      
      ) 
    );
    return $retVal;
  	
  }

  /**
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'Pollenators Data Entry';
  }

  public static function get_perms($nid) {
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
	data_entry_helper::enable_validation('cc-1-collection-details'); // don't care about ID itself, just want resources

	if($args['help_module'] != '' && $args['help_inclusion_function'] != '' && module_exists($args['help_module']) && function_exists($args['help_inclusion_function'])) {
    	$use_help = true;
    	data_entry_helper::$javascript .= call_user_func($args['help_inclusion_function']);
    } else {
    	$use_help = false;
    }

    if($args['ID_tool_module'] != '' && $args['ID_tool_inclusion_function'] != '' && module_exists($args['ID_tool_module']) && function_exists($args['ID_tool_inclusion_function'])) {
    	$use_ID_tool = true;
    	data_entry_helper::$javascript .= call_user_func($args['ID_tool_inclusion_function']);
    } else {
    	$use_ID_tool = false;
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
    $defAttrOptions = array('extraParams'=>$readAuth,
    				'lookUpListCtrl' => 'radio_group',
    				'validation' => array('required'),
    				'language' => iform_lang_iso_639_2($args['language']),
    				'containerClass' => 'group-control-box',
    				'suffixTemplate'=>'nosuffix');
	$language = iform_lang_iso_639_2($args['language']);
    global $indicia_templates;
	$indicia_templates['sref_textbox_latlong'] = '<label for="{idLat}">{labelLat}:</label>'.
        '<input type="text" id="{idLat}" name="{fieldnameLat}" {class} {disabled} value="{default}" />' .
        '<label for="{idLong}">{labelLong}:</label>'.
        '<input type="text" id="{idLong}" name="{fieldnameLong}" {class} {disabled} value="{default}" />';
    
    $r .= data_entry_helper::loading_block_start();

    // note we have to proxy the post. Every time a write transaction is carried out, the write nonce is trashed.
	// For security reasons we don't want to give the user the ability to generate their own nonce, so we use
	// the fact that the user is logged in to drupal as the main authentication/authorisation/identification
	// process for the user. The proxy packages the post into the correct format
	// 
    // There are 2 types of submission:
    // When a user validates a panel using the validate button, the following panel is opened on success
    // When a user presses a modify button, the open panel gets validated, and the panel to be modified is opened.
	
 	$r .= '
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
  <div id="cc-1-details" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active">
    <span id="cc-1-protocol-details"></span>
  </div>
  <div id="cc-1-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active poll-section-body">
   <form id="cc-1-collection-details" action="'.iform_ajaxproxy_url($node, 'loc-sample').'" method="POST">
    <input type="hidden" id="website_id"       name="website_id" value="'.$args['website_id'].'" />
    <input type="hidden" id="imp-sref"         name="location:centroid_sref"  value="" />
    <input type="hidden" id="imp-geom"         name="location:centroid_geom" value="" />
    <input type="hidden" id="imp-sref-system"  name="location:centroid_sref_system" value="4326" />
    <input type="hidden" id="sample:survey_id" name="sample:survey_id" value="'.$args['survey_id'].'" />
    '.iform_pollenators::help_button($use_help, "collection-help-button", $args['help_function'], $args['help_collection_arg']).'
    <label for="location:name">'.lang::get('LANG_Collection_Name_Label').'</label>
 	<input type="text" id="location:name"      name="location:name" value="" class="required"/>
    <input type="hidden" id="sample:location_name" name="sample:location_name" value=""/>
 	'.data_entry_helper::outputAttribute($sample_attributes[$args['protocol_attr_id']], $defAttrOptions)
 	.'    <input type="hidden"                       name="sample:date" value="2010-01-01"/>
    <input type="hidden" id="smpAttr:'.$args['complete_attr_id'].'" name="smpAttr:'.$args['complete_attr_id'].'" value="0" />
    <input type="hidden" id="smpAttr:'.$args['uid_attr_id'].'" name="smpAttr:'.$args['uid_attr_id'].'" value="'.$uid.'" />
    <input type="hidden" id="smpAttr:'.$args['email_attr_id'].'" name="smpAttr:'.$args['email_attr_id'].'" value="'.$email.'" />
    <input type="hidden" id="smpAttr:'.$args['username_attr_id'].'" name="smpAttr:'.$args['username_attr_id'].'" value="'.$username.'" />  
    <input type="hidden" id="locations_website:website_id" name="locations_website:website_id" value="'.$args['website_id'].'" />
    <input type="hidden" id="location:id"      name="location:id" value="" disabled="disabled" />
    <input type="hidden" id="sample:id"        name="sample:id" value="" disabled="disabled" />
    </form>
    <div id="cc-1-valid-button" class="ui-state-default ui-corner-all save-button">'.lang::get('LANG_Validate').'</div>
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

$.fn.foldPanel = function(){
	this.children('.poll-section-body').addClass('poll-hide');
	this.children('.poll-section-footer').addClass('poll-hide');
	this.children('.poll-section-title').find('.reinit-button').show();
	this.children('.poll-section-title').find('.mod-button').show();
	this.children('.photoReelContainer').addClass('ui-corner-all').removeClass('ui-corner-top')
};

$.fn.unFoldPanel = function(){
	this.children('.poll-section-body').removeClass('poll-hide');
	this.children('.poll-section-footer').removeClass('poll-hide');
	this.children('.poll-section-title').find('.mod-button').hide();
	this.children('.photoReelContainer').addClass('ui-corner-top').removeClass('ui-corner-all')
	window.scroll(0,0); // force the window to display the top.
	// any reinit button is left in place
};

// because the map has to be generated in a properly sized div, we can't use the normal hide/show functions.
// just move the panels off to the side.
$.fn.showPanel = function(){
	this.removeClass('poll-hide');
	this.unFoldPanel();
};

$.fn.hidePanel = function(){
	this.addClass('poll-hide'); 
};

inseeLayer = null;

defaultSref = '".
    		((int)$args['map_centroid_lat'] > 0 ? $args['map_centroid_lat'].'N' : (-((int)$args['map_centroid_lat'])).'S').' '.
    		((int)$args['map_centroid_long'] > 0 ? $args['map_centroid_long'].'E' : (-((int)$args['map_centroid_long'])).'W')."';
defaultGeom = '';
$.getJSON('".$svcUrl."' + '/spatial/sref_to_wkt'+
        			'?sref=' + defaultSref +
          			'&system=' + jQuery('#imp-sref-system').val() +
          			'&callback=?', function(data) {
            	defaultGeom = data.wkt;
        	});

$.fn.resetPanel = function(){
	this.find('.poll-section-body').removeClass('poll-hide');
	this.find('.poll-section-footer').removeClass('poll-hide');
	this.find('.reinit-button').show();
	this.find('.mod-button').show();
	this.find('.poll-image').empty();
	this.find('.poll-session').empty();

	// resetForm does not reset the hidden fields. record_status, imp-sref-system, website_id and survey_id are not altered so do not reset.
	// hidden Attributes generally hold unchanging data, but the name needs to be reset (does it for non hidden as well).
	// hidden location:name are set in code anyway.
	this.find('form').each(function(){
		jQuery(this).resetForm();
		jQuery(this).find('[name=sample\\:location_name],[name=location_image\\:path],[name=occurrence_image\\:path]').val('');
		jQuery(this).filter('#cc-1-collection-details').find('[name=sample\\:id],[name=location\\:id]').val('').attr('disabled', 'disabled');
		jQuery(this).find('[name=location_image\\:id],[name=occurrence\\:id],[name=occurrence_image\\:id]').val('').attr('disabled', 'disabled');
		jQuery(this).find('[name=sample\\:date]:hidden').val('2010-01-01');		
        jQuery(this).find('input[name=locations_website\\:website_id]').removeAttr('disabled');
		jQuery(this).find('[name^=smpAttr\\:],[name^=locAttr\\:],[name^=occAttr\\:]').each(function(){
			var name = jQuery(this).attr('name').split(':');
			jQuery(this).attr('name', name[0]+':'+name[1]);
		});
		jQuery(this).find('input[name=location\\:centroid_sref]').val('');
		jQuery(this).find('input[name=location\\:centroid_geom]').val('');
    });	
	this.find('.poll-dummy-form > input').val('');
	this.find('.poll-dummy-form > select').val('');
  };

checkProtocolStatus = function(){
	if (jQuery('#cc-3-body').children().length === 1) {
	    jQuery('#cc-3').find('.delete-button').hide();
  	} else {
		jQuery('#cc-3').find('.delete-button').show();
	}
	if(jQuery('[name=smpAttr\\:".$args['protocol_attr_id']."],[name^=smpAttr\\:".$args['protocol_attr_id']."\\:]').filter(':first').filter('[checked]').length >0){
	    jQuery('#cc-3').find('.add-button').hide();
	} else {
	    jQuery('#cc-3').find('.add-button').show();
  	}
  	var checkedProtocol = jQuery('[name=smpAttr\\:".$args['protocol_attr_id']."],[name^=smpAttr\\:".$args['protocol_attr_id']."\\:]').filter('[checked]').parent();
    if(jQuery('[name=location\\:name]').val() != '' && checkedProtocol.length > 0) {
        jQuery('#cc-1-title-details').empty().text(jQuery('#cc-1-collection-details input[name=location\\:name]:first').val());
        jQuery('#cc-1-protocol-details').empty().show().text(\"".lang::get('LANG_Protocol_Title_Label')." : \" + checkedProtocol.find('label')[0].innerHTML;
    } else {
        jQuery('#cc-1-title-details').empty().text(\"".lang::get('LANG_Collection_Details')."\");
        // TODO autogenerate a name
        jQuery('#cc-1-protocol-details').empty().hide();
    }
};

showStationPanel = true;

// The validate functionality for each panel is sufficiently different that we can't generalise a function
// this is the one called when we don't want the panel following to be opened automatically.
validateCollectionPanel = function(){
	if(jQuery('#cc-1-body').filter('.poll-hide').length > 0) return true; // body hidden so data already been validated successfully.
	if(!jQuery('#cc-1-body').find('form > input').valid()){ return false; }
	// no need to check protocol - if we are this far, we've already filled it in.
  	showStationPanel = false;
	jQuery('#cc-1-collection-details').submit();
	return true;
  };

validateRadio = function(name, formSel){
    var controls = jQuery(formSel).find('[name='+name+'],[name^='+name+'\\:]');
	controls.parent().parent().find('p').remove(); // remove existing errors
    if(controls.filter('[checked]').length < 1) {
        var label = $('<p/>')
				.attr({'for': name})
				.addClass('radio-error')
				.html($.validator.messages.required);
		label.insertBefore(controls.filter(':first').parent());
		return false;
    }
    return true;
}

validateRequiredField = function(name, formSel){
    var control = jQuery(formSel).find('[name='+name+']');
	control.parent().find('.required-error').remove(); // remove existing errors
    if(control.val() == '') {
        var label = $('<p/>')
				.attr({'for': name})
				.addClass('required-error')
				.html($.validator.messages.required);
		label.insertBefore(control);
		return false;
    }
    return true;
}

$('#cc-1-collection-details').ajaxForm({ 
        // dataType identifies the expected content type of the server response 
        dataType:  'json', 
        // success identifies the function to invoke when the server response 
        // has been received 
        beforeSubmit:   function(data, obj, options){
        	var valid = true;
        	if (!jQuery('form#cc-1-collection-details > input').valid()) { valid = false; }
        	if (!validateRadio('smpAttr\\:".$args['protocol_attr_id']."', 'form#cc-1-collection-details')) { valid = false; }
	       	if ( valid == false ) return valid;
  			// Warning this assumes that:
  			// 1) the location:name is the sixth field in the form.
  			// 1) the sample:location_name is the seventh field in the form.
  			data[6].value = data[5].value;
  			if(data[1].value=='') data[1].value=defaultSref;
  			if(data[2].value=='') data[2].value=defaultGeom;
  			jQuery('#cc-2-floral-station > input[name=location\\:name]').val(data[5].value);
        	return true;
  		},
        success:   function(data){
        	if(data.success == 'multiple records' && data.outer_table == 'location'){
        	    jQuery('#cc-1-collection-details > input[name=location\\:id]').removeAttr('disabled').val(data.outer_id);
        	    jQuery('#cc-1-collection-details > input[name=locations_website\\:website_id]').attr('disabled', 'disabled');
        	    jQuery('#cc-2-floral-station > input[name=location\\:id]').removeAttr('disabled').val(data.outer_id);
        	    $.getJSON(\"".$svcUrl."\" + \"/data/sample\" +
			          \"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
			          \"&location_id=\"+data.outer_id+\"&parent_id=NULL&callback=?\", function(data) {
					if (data.length>0) {
			       		    jQuery('#cc-6-consult-collection').attr('href', '".url('node/'.$args['gallery_node'])."'+'?collection_id='+data[0].id);
			        	    jQuery('#cc-1-collection-details > input[name=sample\\:id]').removeAttr('disabled').val(data[0].id);
			        	    jQuery('#cc-2-floral-station > input[name=sample\\:id]').removeAttr('disabled').val(data[0].id);
			        	    // In this case we use loadAttributes to set the names of the attributes to include the attribute_value id.
   	       					loadAttributes('sample_attribute_value', 'sample_attribute_id', 'sample_id', 'sample\\:id', data[0].id, 'smpAttr');
						}
				});
			   	checkProtocolStatus();
        		$('#cc-1').foldPanel();
    			if(showStationPanel){ $('#cc-2').showPanel(); }
		    	showStationPanel = true;
        	}  else {
				var errorString = \"".lang::get('LANG_Indicia_Warehouse_Error')."\";
				if(data.error){
					errorString = errorString + ' : ' + data.error;
				}
				if(data.errors){
					for (var i in data.errors)
					{
						errorString = errorString + ' : ' + data.errors[i];
					}				
				}
				alert(errorString);
			}
        } 
});

$('#cc-1-delete-collection').ajaxForm({ 
        dataType:  'json', 
        beforeSubmit:   function(data, obj, options){
  			// Warning this assumes that the data is fixed position:
       		data[2].value = jQuery('#cc-1-collection-details input[name=sample\\:id]').val();
       		data[3].value = jQuery('#cc-1-collection-details input[name=sample\\:date]').val();
       		data[4].value = jQuery('#cc-1-collection-details input[name=location\\:id]').val();
        	if(data[2].value == '') return false;
        	return true;
  		},
        success:   function(data){
			jQuery('#cc-3-body').empty();
        	jQuery('.poll-section').resetPanel();
			sessionCounter = 0;
			addSession();
			checkProtocolStatus();
			jQuery('.poll-section').hidePanel();
			jQuery('.poll-image').empty();
			jQuery('#cc-1').showPanel();
			jQuery('.reinit-button').hide();
			jQuery('#map')[0].map.editLayer.destroyFeatures();
  		} 
});

$('#cc-1-valid-button').click(function() {
	jQuery('#cc-1-collection-details').submit();
});

$('#cc-1-reinit-button').click(function() {
	if(jQuery('form#cc-1-collection-details > input[name=sample\\:id]').filter('[disabled]').length > 0) { return } // sample id is disabled, so no data has been saved - do nothing.
    if (!jQuery('form#cc-1-collection-details > input').valid()) {
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
    $extraParams = $readAuth + array('taxon_list_id' => $args['flower_list_id'], 'orderby' => 'taxon');
    $species_ctrl_args=array(
    	    'label'=>lang::get('LANG_Flower_Species'),
        	'fieldname'=>'flower:taxa_taxon_list_id',
	        'table'=>'taxa_taxon_list',
    	    'captionField'=>'taxon',
        	'valueField'=>'id',
	        'columns'=>2,
			'validation'=>array('required'),
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
	  <form id="cc-2-flower-upload" enctype="multipart/form-data" action="'.iform_ajaxproxy_url($node, 'media').'" method="POST">
    		<input type="hidden" id="website_id" name="website_id" value="'.$args['website_id'].'" />
    		<input name="upload_file" type="file" class="required" />
     		<input type="submit" value="'.lang::get('LANG_Upload_Flower').'" class="btn-submit" />
      </form>
 	  <div id="cc-2-flower-image" class="poll-image"></div>
 	  <div id="cc-2-flower-identify" class="poll-dummy-form">
        '.iform_pollenators::help_button($use_help, "flower-help-button", $args['help_function'], $args['help_flower_arg']).'
 	    <p><strong>'.lang::get('LANG_Identify_Flower').'</strong></p>
        <label for="id-flower-later" class="follow-on">'.lang::get('LANG_ID_Flower_Later').' </label><input type="checkbox" id="id-flower-later" name="id-flower-later" /> 
		'.($args['ID_tool_flower_url'] != '' && $args['ID_tool_flower_poll_dir'] ?  '<label for="flower-id-button">'.lang::get('LANG_Flower_ID_Key_label').' :</label><span id="flower-id-button" class="ui-state-default ui-corner-all poll-id-button" >'.lang::get('LANG_Launch_ID_Key').'</span>' : '')
		.'<span id="flower-id-cancel" class="ui-state-default ui-corner-all poll-id-cancel" >'.lang::get('LANG_Cancel_ID').'</span>
    	'.data_entry_helper::select($species_ctrl_args).'
		<input type="text" name="flower:taxon_text_description" readonly="readonly">
      </div>
 	</div>
    <div class="poll-break"></div>
 	<div id="cc-2-environment">
	  <form id="cc-2-environment-upload" enctype="multipart/form-data" action="'.iform_ajaxproxy_url($node, 'media').'" method="POST">
    		<input type="hidden" id="website_id" name="website_id" value="'.$args['website_id'].'" />
    		<input name="upload_file" type="file" class="required" />
    		<input type="submit" value="'.lang::get('LANG_Upload_Environment').'" class="btn-submit" />
      </form>
 	  <div id="cc-2-environment-image" class="poll-image"></div>
 	</div>
 	<form id="cc-2-floral-station" action="'.iform_ajaxproxy_url($node, 'loc-smp-occ').'" method="POST">
      <input type="hidden" id="website_id" name="website_id" value="'.$args['website_id'].'" />
      <input type="hidden" id="location:id" name="location:id" value="" />
      <input type="hidden" id="location:name" name="location:name" value=""/>
      <input type="hidden" name="location:centroid_sref" />
      <input type="hidden" name="location:centroid_geom" />
      <input type="hidden" name="location:centroid_sref_system" value="4326" />
      <input type="hidden" id="location_image:path" name="location_image:path" value="" />
      <input type="hidden" id="sample:survey_id" name="sample:survey_id" value="'.$args['survey_id'].'" />
      <input type="hidden" id="sample:id" name="sample:id" value=""/>
      <input type="hidden" name="sample:date" value="2010-01-01"/>
      <input type="hidden" name="determination:taxa_taxon_list_id" value=""/>  
      <input type="hidden" name="determination:taxon_text_description" value=""/>  
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
      '.data_entry_helper::outputAttribute($occurrence_attributes[$args['flower_type_attr_id']], array('extraParams'=>$readAuth, 'lookUpListCtrl' => 'radio_group', 'sep' => ' &nbsp; ', 'language' => iform_lang_iso_639_2($args['language']), 'containerClass' => 'group-control-box', 'suffixTemplate'=>'nosuffix'))
 	  .data_entry_helper::outputAttribute($location_attributes[$args['distance_attr_id']], array('extraParams'=>$readAuth, 'lookUpListCtrl' => 'radio_group', 'sep' => ' &nbsp; ', 'language' => iform_lang_iso_639_2($args['language']), 'containerClass' => 'group-control-box', 'suffixTemplate'=>'nosuffix')) 	 	
      .data_entry_helper::outputAttribute($location_attributes[$args['habitat_attr_id']], array('extraParams'=>$readAuth, 'lookUpListCtrl' => 'checkbox_group', 'sep' => ' &nbsp; ', 'language' => iform_lang_iso_639_2($args['language']), 'containerClass' => 'group-control-box', 'suffixTemplate'=>'nosuffix')).'
    </form>
    <div class="poll-break"></div>
    <div>
      '.iform_pollenators::help_button($use_help, "location-help-button", $args['help_function'], $args['help_location_arg']).'
      <div>'.lang::get('LANG_Location_Notes').'</div>
 	  <div class="poll-map-container">
    ';

    $r .= data_entry_helper::map_panel($options, $olOptions);
    $r .= '
      </div>
      <div><div id="cc-2-location-entry">
        '.data_entry_helper::georeference_lookup(array(
      		'label' => lang::get('LANG_Georef_Label'),
      		'georefPreferredArea' => $args['georefPreferredArea'],
      		'georefCountry' => $args['georefCountry'],
      		'georefLang' => $args['language'],
    		'suffixTemplate'=>'nosuffix'
    		)).'
    	<span >'.lang::get('LANG_Georef_Notes').'</span>
 	    <label for="place:INSEE">'.lang::get('LANG_Or').'</label>
 		<input type="text" id="place:INSEE" name="place:INSEE" value="'.lang::get('LANG_INSEE').'"
	 		onclick="if(this.value==\''.lang::get('LANG_INSEE').'\'){this.value=\'\'; this.style.color=\'#000\'}"  
            onblur="if(this.value==\'\'){this.value=\''.lang::get('LANG_INSEE').'\'; this.style.color=\'#555\'}" />
    	<input type="button" id="search-insee-button" class="ui-corner-all ui-widget-content ui-state-default search-button" value="Search" />
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
 	  </div></div>
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

var flowerTimer1;
var flowerTimer2;
var IDcounter = 0;

flowerPoller = function(){
	flowerTimer1 = setTimeout('flowerPoller();', ".$args['ID_tool_poll_interval'].");
	$.get('".str_replace("{HOST}", $_SERVER['HTTP_HOST'], $args['ID_tool_flower_poll_dir']).session_id()."_'+IDcounter.toString(), function(data){
      var da = data.split('\\n');
      // first count number of returned items.
      // if > 1 put all into taxon_description.
      // if = 1 rip out the description, remove the formatting, scan the flower select for it, and set the value. Set the taxon description.
	  da[1] = da[1].replace(/\\\\\\\\i\{\}/g, '').replace(/\\\\\\\\i0\{\}/g, '');
      var items = da[1].split(':');
	  var count = items.length;
	  if(items[count-1] == '') count--;
	  if(count <= 0){
	  	// no valid stuff so blank it all out.
	  	jQuery('#cc-2-flower-identify > select[name=flower\\:taxa_taxon_list_id]').val('');
	  	jQuery('#cc-2-flower-identify > select[name=flower\\:taxon_text_description]').val('');
	  } else if(count == 1){
	  	jQuery('#cc-2-flower-identify > select[name=flower\\:taxa_taxon_list_id]').val('');
	  	jQuery('#cc-2-flower-identify > select[name=flower\\:taxon_text_description]').val(items[0]);
  		var x = jQuery('#cc-2-flower-identify').find('option').filter('[text='+items[0]+']');
	  	if(x.length > 0){
			jQuery('#cc-2-flower-identify > select[name=flower\\:taxon_text_description]').val('');
	  		jQuery('#cc-2-flower-identify > select[name=flower\\:taxa_taxon_list_id]').val(x[0].value);
  		}
	  } else {
	  	jQuery('#cc-2-flower-identify > select[name=flower\\:taxa_taxon_list_id]').val('');
	  	jQuery('#cc-2-flower-identify > select[name=flower\\:taxon_text_description]').val(da[1]);
	  }
	  flowerReset();
    });
};
flowerReset = function(){
	clearTimeout(flowerTimer1);
	clearTimeout(flowerTimer2);
	jQuery('#flower-id-cancel').hide();
};

jQuery('#flower-id-button').click(function(){
	IDcounter++;
	clearTimeout(flowerTimer1);
	clearTimeout(flowerTimer2);
	window.open('".$args['ID_tool_flower_url'].session_id()."_'+IDcounter.toString(),'','') 
	flowerTimer1 = setTimeout('flowerPoller();', ".$args['ID_tool_poll_interval'].");
	flowerTimer2 = setTimeout('flowerReset();', ".$args['ID_tool_poll_timeout'].");
	jQuery('#flower-id-cancel').show();
});
jQuery('#flower-id-cancel').click(function(){
	flowerReset();
});
jQuery('#flower-id-cancel').hide();

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

validateStationPanel = function(){
	var myPanel = jQuery('#cc-2');
	var valid = true;
	if(myPanel.filter('.poll-hide').length > 0) return true; // panel is not visible so no data to fail validation.
	if(myPanel.find('.poll-section-body').filter('.poll-hide').length > 0) return true; // body hidden so data already been validated successfully.
	// If no data entered also return true: this can only be the case when pressing the modify button on the collections panel
	if(jQuery('form#cc-2-floral-station > input[name=location_image\\:path]').val() == '' &&
			jQuery('form#cc-2-floral-station > input[name=occurrence\\:id]').val() == '' &&
			jQuery('form#cc-2-floral-station > input[name=occurrence_image\\:path]').val() == '' &&
			jQuery('#cc-2-flower-identify > select[name=flower\\:taxa_taxon_list_id]').val() == '' &&
    		jQuery('[name=occAttr\\:".$args['flower_type_attr_id']."],[name^=occAttr\\:".$args['flower_type_attr_id']."\\:]').filter('[checked]').length == 0 &&
    		jQuery('[name=locAttr\\:".$args['habitat_attr_id']."],[name^=locAttr\\:".$args['habitat_attr_id']."\\:]').filter('[checked]').length == 0 &&
    		jQuery('[name=locAttr\\:".$args['distance_attr_id']."],[name^=locAttr\\:".$args['distance_attr_id']."\\:]').val() == '') {
		jQuery('#cc-2').foldPanel();
		return true;
	}
    if(jQuery('form#cc-2-floral-station > input[name=location_image\\:path]').val() == '' ||
					jQuery('form#cc-2-floral-station > input[name=occurrence_image\\:path]').val() == ''){
		alert(\"".lang::get('LANG_Must_Provide_Pictures')."\");
		valid = false;
	}
    if(jQuery('#imp-geom').val() == '') {
		alert(\"".lang::get('LANG_Must_Provide_Location')."\");
		valid = false;
	}
	if (jQuery('#id-flower-later').attr('checked') == ''){
		if(!validateRequiredField('flower\\:taxa_taxon_list_id', '#cc-2-flower-identify')) { valid = false; }
	}
	if (!jQuery('form#cc-2-floral-station > input').valid()) { valid = false; }
   	if (!validateRadio('occAttr\\:".$args['flower_type_attr_id']."', 'form#cc-2-floral-station')) { valid = false; }
   	if ( valid == false ) return valid;
	showSessionsPanel = false;
	jQuery('form#cc-2-floral-station').submit();
	return true;
};

// Flower upload picture form.
$('#cc-2-flower-upload').ajaxForm({ 
        dataType:  'json', 
        beforeSubmit:   function(data, obj, options){
         	if (!jQuery('form#cc-2-flower-upload').valid()) { return false; }
        	$('#cc-2-flower-image').empty();
        	$('#cc-2-flower-image').addClass('loading')
        },
        success:   function(data){
        	if(data.success == true){
	        	// There is only one file
	        	jQuery('form#cc-2-floral-station input[name=occurrence_image\\:path]').val(data.files[0]);
	        	var img = new Image();
	        	$(img).load(function () {
        				$(this).hide();
        				$('#cc-2-flower-image').removeClass('loading').append(this);
        				$(this).fadeIn();
			    	})
				    .attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."med-'+data.files[0])
				    .css('max-width', $('#cc-2-flower-image').width()).css('max-height', $('#cc-2-flower-image').height())
				    .css('vertical-align', 'middle').css('margin-left', 'auto').css('margin-right', 'auto').css('display', 'block');
				jQuery('#cc-2-flower-upload input[name=upload_file]').val('');
			} else {
				var errorString = \"".lang::get('LANG_Indicia_Warehouse_Error')."\";
	        	jQuery('form#cc-2-floral-station input[name=occurrence_image\\:path]').val('');
				$('#cc-2-flower-image').removeClass('loading');
				if(data.error){
					errorString = errorString + ' : ' + data.error;
				}
				if(data.errors){
					for (var i in data.errors)
					{
						errorString = errorString + ' : ' + data.errors[i];
					}				
				}
				alert(errorString);
			}
  		} 
});

// Flower upload picture form.
$('#cc-2-environment-upload').ajaxForm({ 
        dataType:  'json', 
        beforeSubmit:   function(data, obj, options){
         	if (!jQuery('form#cc-2-environment-upload').valid()) { return false; }
        	$('#cc-2-environment-image').empty();
        	$('#cc-2-environment-image').addClass('loading')
        },
        success:   function(data){
        	if(data.success == true){
	        	// There is only one file
	        	jQuery('form#cc-2-floral-station input[name=location_image\\:path]').val(data.files[0]);
	        	var img = new Image();
	        	$(img).load(function () {
        				$(this).hide();
        				$('#cc-2-environment-image').removeClass('loading').append(this);
        				$(this).fadeIn();
			    	})
				    .attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."med-'+data.files[0])
				    .css('max-width', $('#cc-2-environment-image').width()).css('max-height', $('#cc-2-environment-image').height())
				    .css('vertical-align', 'middle').css('margin-left', 'auto').css('margin-right', 'auto').css('display', 'block');
				jQuery('#cc-2-environment-upload input[name=upload_file]').val('');
			} else {
				var errorString = \"".lang::get('LANG_Indicia_Warehouse_Error')."\";
	        	jQuery('form#cc-2-floral-station input[name=location_image\\:path]').val('');
				$('#cc-2-environment-image').removeClass('loading');
				if(data.error){
					errorString = errorString + ' : ' + data.error;
				}
				if(data.errors){
					for (var i in data.errors)
					{
						errorString = errorString + ' : ' + data.errors[i];
					}				
				}
				alert(errorString);
			}
        } 
});

$('#cc-2-floral-station').ajaxForm({ 
    dataType:  'json', 
    beforeSubmit:   function(data, obj, options){
		var valid = true;
    	if(jQuery('form#cc-2-floral-station > input[name=location_image\\:path]').val() == '' ||
					jQuery('form#cc-2-floral-station > input[name=occurrence_image\\:path]').val() == '' ){
			alert(\"".lang::get('LANG_Must_Provide_Pictures')."\");
			valid = false;
		}
		if(jQuery('#imp-geom').val() == '') {
			alert(\"".lang::get('LANG_Must_Provide_Location')."\");
			valid = false;
		}
		if (!jQuery('form#cc-2-floral-station > input').valid()) { valid = false; }
   		if (!validateRadio('occAttr\\:".$args['flower_type_attr_id']."', 'form#cc-2-floral-station')) { valid = false; }
		// DANGER this assumes certain positioning of the centroid sref and geom within the data array
		if(data[3].name != 'location:centroid_sref' || data[4].name != 'location:centroid_geom') {
			alert('Internal error: imp-sref or imp-geom post location mismatch');
			return false;
		}
		data[3].value = jQuery('#imp-sref').val();
		data[4].value = jQuery('#imp-geom').val();
		data[10].value = jQuery('#cc-2-flower-identify > select[name=flower\\:taxa_taxon_list_id]').val();
		data[11].value = jQuery('#cc-2-flower-identify > select[name=flower\\:taxon_text_description]').val();
		if (jQuery('#id-flower-later').attr('checked') == ''){
			if (!validateRequiredField('flower\\:taxa_taxon_list_id', '#cc-2-flower-identify')) { valid = false; }
		} else {
			data.splice(10,5); // remove determination entries.
		}
   		if ( valid == false ) return valid;
		return true;
	},
    success:   function(data){
       	if(data.success == 'multiple records' && data.outer_table == 'sample'){
       		// the sample and location ids are already fixed, so just need to populate the occurrence and image IDs, and rename the location and occurrence attribute.
       	    $.getJSON(\"".$svcUrl."\" + \"/data/occurrence\" +
		          \"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
		          \"&sample_id=\"+data.outer_id+\"&callback=?\", function(occdata) {
				if (occdata.length>0) {
		        	jQuery('#cc-2-floral-station > input[name=occurrence\\:id]').removeAttr('disabled').val(occdata[0].id);
       				loadAttributes('occurrence_attribute_value', 'occurrence_attribute_id', 'occurrence_id', 'occurrence\\:id', occdata[0].id, 'occAttr');
					$.getJSON(\"".$svcUrl."/data/occurrence_image/\" +
       						\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
       						\"&occurrence_id=\"+occdata[0].id+\"&callback=?\", function(imgdata) {
					    if (imgdata.length>0) {
		        			jQuery('#cc-2-floral-station > input[name=occurrence_image\\:id]').removeAttr('disabled').val(imgdata[0].id);
		        		}});
		        }});
		    var location_id = jQuery('#cc-2-floral-station > input[name=location\\:id]').val();
       		loadAttributes('location_attribute_value', 'location_attribute_id', 'location_id', 'location\\:id', location_id, 'locAttr');
			$.getJSON(\"".$svcUrl."/data/location_image/\" +
       				\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
       				\"&location_id=\"+location_id+\"&callback=?\", function(data) {
				if (data.length>0) {
		        	jQuery('#cc-2-floral-station > input[name=location_image\\:id]').removeAttr('disabled').val(data[0].id);
		        }});
			jQuery('#cc-2').foldPanel();
			if(showSessionsPanel) { jQuery('#cc-3').showPanel(); }
			showSessionsPanel = true;
        } 
	}
});

$('#cc-2-valid-button').click(function() {
	jQuery('#cc-2-floral-station').submit();
});

";

 	// Sessions.
    $r .= '
<div id="cc-3" class="poll-section">
  <div id="cc-3-title" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-all poll-section-title"><span>'.lang::get('LANG_Sessions_Title').'</span>
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
        dataType:  'json', 
        beforeSubmit:   function(data, obj, options){
  			// Warning this assumes that the data is fixed position:
       		data[4].value = jQuery('#cc-1-collection-details input[name=location\\:id]').val();
        	if(data[2].value == '') return false;
        	return true;
  		},
        success:   function(data){
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
				jQuery(this).children('input[name=sample\\:date]').val()+
				' : '+
				jQuery(this).children('[name=smpAttr\\:".$args['start_time_attr_id']."],[name^=smpAttr\\:".$args['start_time_attr_id']."\\:]').val()+
				' > '+
				jQuery(this).children('[name=smpAttr\\:".$args['end_time_attr_id']."],[name^=smpAttr\\:".$args['end_time_attr_id']."\\:]').val()+
				'</option>')
			.appendTo(insectSessionSelect);
	});
	if(value)
		insectSessionSelect.val(value);
}

validateAndSubmitOpenSessions = function(){
	var valid = true;
	// only check the visible forms as rest have already been validated successfully.
	$('.poll-session-form:visible').each(function(i){
	    if (!jQuery(this).children('input').valid()) {
	    	valid = false; }
	    if (!jQuery('form#cc-2-floral-station > input').valid()) { valid = false; }
   		if (!validateRadio('smpAttr\\:".$args['sky_state_attr_id']."', this)) { valid = false; }
   		if (!validateRadio('smpAttr\\:".$args['temperature_attr_id']."', this)) { valid = false; }
   		if (!validateRadio('smpAttr\\:".$args['wind_attr_id']."', this)) { valid = false; }
    });
	if(valid == false) return false;
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
		var session=$(this).parents('.poll-session');;
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
    data_entry_helper::$javascript .= "
	var dateAttr = '".str_replace("\n", "", data_entry_helper::date_picker(array('label' => lang::get('LANG_Date'),
    						'id' => '<id>',
							'fieldname' => 'sample:date',
    						'class' => 'vague-date-picker required',
    						'suffixTemplate'=>'nosuffix')))."';
	var dateID = 'cc-3-session-date-'+sessionCounter;
	jQuery(dateAttr.replace(/<id>/g, dateID)).appendTo(newForm);
    jQuery('#'+dateID).datepicker({
		dateFormat : 'yy-mm-dd',
		constrainInput: false,
		maxDate: '0'
	});
    jQuery('".data_entry_helper::outputAttribute($sample_attributes[$args['start_time_attr_id']], $defAttrOptions)."').appendTo(newForm);
	jQuery('".data_entry_helper::outputAttribute($sample_attributes[$args['end_time_attr_id']], $defAttrOptions)."').appendTo(newForm);
	jQuery('".data_entry_helper::outputAttribute($sample_attributes[$args['sky_state_attr_id']], $defAttrOptions)."').appendTo(newForm);
	jQuery('".data_entry_helper::outputAttribute($sample_attributes[$args['temperature_attr_id']], $defAttrOptions)."').appendTo(newForm);
	jQuery('".data_entry_helper::outputAttribute($sample_attributes[$args['wind_attr_id']], $defAttrOptions)."').appendTo(newForm);
	jQuery('".data_entry_helper::outputAttribute($sample_attributes[$args['shade_attr_id']], $defAttrOptions)."').appendTo(newForm);
	newDeleteButton.click(function() {
		var container = $(this).parent().parent();
		jQuery('#cc-3-delete-session').find('[name=sample\\:id]').val(container.find('[name=sample\\:id]').val());
		jQuery('#cc-3-delete-session').find('[name=sample\\:date]').val(container.find('[name=sample\\:date]').val());
		jQuery('#cc-3-delete-session').find('[name=sample\\:location_id]').val(container.find('[name=sample\\:location_id]').val());
		if(container.find('[name=sample\\:id]').filter('[disabled]').length == 0){
			$.getJSON(\"".$svcUrl."/data/occurrence/\" +
					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
					\"&sample_id=\"+container.find('[name=sample\\:id]').val()+\"&callback=?\", function(insectData) {
				if (insectData.length>0) {
					alert(\"".lang::get('LANG_Cant_Delete_Session')."\");
				} else if(confirm(\"".lang::get('LANG_Confirm_Session_Delete')."\")){
					jQuery('#cc-3-delete-session').submit();
					container.remove();
					checkProtocolStatus();
				}
			});
		} else if(confirm(\"".lang::get('LANG_Confirm_Session_Delete')."\")){
			container.remove();
			checkProtocolStatus();
		}
    });
    newForm.ajaxForm({ 
    	dataType:  'json',
    	beforeSubmit:   function(data, obj, options){
    		var valid = true;
    		if (!obj.find('input').valid()) {
    			valid = false; }
    		if (!validateRadio('smpAttr\\:".$args['sky_state_attr_id']."', obj)) { valid = false; }
   			if (!validateRadio('smpAttr\\:".$args['temperature_attr_id']."', obj)) { valid = false; }
   			if (!validateRadio('smpAttr\\:".$args['wind_attr_id']."', obj)) { valid = false; }
    		data[2].value = jQuery('#cc-1-collection-details > input[name=sample\\:id]').val();
			data[3].value = jQuery('#cc-1-collection-details > input[name=location\\:id]').val();
			return valid;
		},
   	    success:   function(data, status, form){
   	    // TODO: error condition handling, eg no date.
   	    	var thisSession = form.parents('.poll-session');
    		if(data.success == 'multiple records' && data.outer_table == 'sample'){
   	    	    form.children('input[name=sample\\:id]').removeAttr('disabled').val(data.outer_id);
   	    	    loadAttributes('sample_attribute_value', 'sample_attribute_id', 'sample_id', 'sample\\:id', data.outer_id, 'smpAttr');
        	}
			thisSession.show();
			thisSession.children(':first').show().find('*').show();
			thisSession.children().not(':first').hide();
  		}
	});
	checkProtocolStatus();
    return(newSession);
};

validateSessionsPanel = function(){
	if(jQuery('#cc-3').filter('.poll-hide').length > 0) return true; // panel is not visible so no data to fail validation.
	if(jQuery('#cc-3').find('.poll-section-body').filter('.poll-hide').length > 0) return true; // body hidden so data already been validated successfully.
	var openSession = jQuery('.poll-session-form:visible');
	if(openSession.length > 0){
		if(jQuery('input[name=sample\\:id]', openSession).val() == '' &&
				jQuery('input[name=sample\\:date]', openSession).val() == '".lang::get('click here')."' &&
				jQuery('[name=smpAttr\\:".$args['start_time_attr_id']."],[name^=smpAttr\\:".$args['start_time_attr_id']."\\:]', openSession).val() == '' &&
				jQuery('[name=smpAttr\\:".$args['end_time_attr_id']."],[name^=smpAttr\\:".$args['end_time_attr_id']."\\:]', openSession).val() == '' &&
				jQuery('[name=smpAttr\\:".$args['sky_state_attr_id']."],[name^=smpAttr\\:".$args['sky_state_attr_id']."\\:]', openSession).filter('[checked]').length == 0 &&
    			jQuery('[name=smpAttr\\:".$args['temperature_attr_id']."],[name^=smpAttr\\:".$args['temperature_attr_id']."\\:]', openSession).filter('[checked]').length == 0 &&
    			jQuery('[name=smpAttr\\:".$args['wind_attr_id']."],[name^=smpAttr\\:".$args['wind_attr_id']."\\:]', openSession).filter('[checked]').length == 0) {
			// NB shade is a boolean, and always has one set (default no)
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
});

jQuery('.mod-button').click(function() {
	// first close all the other panels, ensuring any data is saved.
	if(!validateCollectionPanel() || !validateStationPanel() || !validateSessionsPanel() || !validateInsectPanel())
		return;
	jQuery('#cc-5').hidePanel();
	jQuery(this).parents('.poll-section-title').parent().unFoldPanel(); //slightly complicated because cc-1 contains the rest.
});

";

    $extraParams = $readAuth + array('taxon_list_id' => $args['insect_list_id'], 'orderby' => 'taxon');
	$species_ctrl_args=array(
    	    'label'=>lang::get('LANG_Insect_Species'),
        	'fieldname'=>'insect:taxa_taxon_list_id',
	        'table'=>'taxa_taxon_list',
    	    'captionField'=>'taxon',
        	'valueField'=>'id',
	        'columns'=>2,
			'validation'=>array('required'),
    		'blankText'=>lang::get('LANG_Choose_Taxon'),
    	    'extraParams'=>$extraParams,
			'suffixTemplate'=>'nosuffix'
	);
 	$r .= '
<div id="cc-4" class="poll-section">
  <div id="cc-4-title" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-all poll-section-title">'.lang::get('LANG_Photos').'
    <div id="cc-4-mod-button" class="right ui-state-default ui-corner-all mod-button">'.lang::get('LANG_Modify').'</div>
  </div>
  <div id="cc-4-photo-reel" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-top ui-accordion-content-active photoReelContainer" >
  </div>
  <div id="cc-4-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active poll-section-body">  
    <div id="cc-4-insect">
	  <form id="cc-4-insect-upload" enctype="multipart/form-data" action="'.iform_ajaxproxy_url($node, 'media').'" method="POST">
    		<input type="hidden" id="website_id" name="website_id" value="'.$args['website_id'].'" />
    		<input name="upload_file" type="file" class="required" />
    		<input type="submit" value="'.lang::get('LANG_Upload_Insect').'" class="btn-submit" />
      </form>
 	  <div id="cc-4-insect-image" class="poll-image"></div>
 	  <div id="cc-4-insect-identify" class="poll-dummy-form">
 	    '.iform_pollenators::help_button($use_help, "insect-help-button", $args['help_function'], $args['help_insect_arg']).'
        <p><strong>'.lang::get('LANG_Identify_Insect').'</strong></p>
        <label for="id-insect-later" class="follow-on">'.lang::get('LANG_ID_Insect_Later').' </label><input type="checkbox" id="id-insect-later" name="id-insect-later" /> 
		'.($args['ID_tool_insect_url'] != '' && $args['ID_tool_insect_poll_dir'] ?  '<label for="insect-id-button">'.lang::get('LANG_Insect_ID_Key_label').' :</label><span id="insect-id-button" class="ui-state-default ui-corner-all poll-id-button" >'.lang::get('LANG_Launch_ID_Key').'</span>' : '')
		.'<span id="insect-id-cancel" class="ui-state-default ui-corner-all poll-id-cancel" >'.lang::get('LANG_Cancel_ID').'</span>'
 		.data_entry_helper::select($species_ctrl_args).'
		<input type="text" name="insect:taxon_text_description" readonly="readonly">
      </div>
    </div>
    <div class="poll-break"></div> 
 	<form id="cc-4-main-form" action="'.iform_ajaxproxy_url($node, 'occurrence').'" method="POST" >
    	<input type="hidden" id="website_id" name="website_id" value="'.$args['website_id'].'" />
    	<input type="hidden" id="occurrence_image:path" name="occurrence_image:path" value="" />
    	<input type="hidden" id="occurrence:record_status" name="occurrence:record_status" value="C" />
        <input type="hidden" name="occurrence:use_determination" value="Y"/>    
    	<input type="hidden" name="determination:taxa_taxon_list_id" value=""/> 
        <input type="hidden" name="determination:taxon_text_description" value=""/>  	
		<input type="hidden" name="determination:cms_ref" value="'.$uid.'" />
    	<input type="hidden" name="determination:email_address" value="'.$email.'" />
    	<input type="hidden" name="determination:person_name" value="'.$username.'" /> 
        <input type="hidden" id="occurrence:id" name="occurrence:id" value="" disabled="disabled" />
	    <input type="hidden" id="determination:id" name="determination:id" value="" disabled="disabled" />
	    <input type="hidden" id="occurrence_image:id" name="occurrence_image:id" value="" disabled="disabled" />
	    <label for="occurrence:sample_id">'.lang::get('LANG_Session').'</label>
	    <select id="occurrence:sample_id" name="occurrence:sample_id" value="" class="required" /></select>
	    '
 	.data_entry_helper::textarea(array(
	        'label'=>lang::get('LANG_Comment'),
    	    'fieldname'=>'occurrence:comment',
 			'suffixTemplate'=>'nosuffix'
	    ))
	.data_entry_helper::outputAttribute($occurrence_attributes[$args['number_attr_id']],
 			$defAttrOptions)
 	.data_entry_helper::outputAttribute($occurrence_attributes[$args['foraging_attr_id']],
 			$defAttrOptions).'
    </form>
    <span id="cc-4-valid-insect-button" class="ui-state-default ui-corner-all save-button">'.lang::get('LANG_Validate_Insect').'</span>
    <span id="cc-4-delete-insect-button" class="ui-state-default ui-corner-all delete-button">'.lang::get('LANG_Delete_Insect').'</span>
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
loadInsectPanel = null;

var insectTimer1;
var insectTimer2;

insectPoller = function(){
	insectTimer1 = setTimeout('insectPoller();', ".$args['ID_tool_poll_interval'].");
	$.get('".str_replace("{HOST}", $_SERVER['HTTP_HOST'], $args['ID_tool_insect_poll_dir']).session_id()."_'+IDcounter.toString(), function(data){
	var da = data.split('\\n');
      // first count number of returned items.
      // if > 1 put all into taxon_description.
      // if = 1 rip out the description, remove the formatting, scan the flower select for it, and set the value. Set the taxon description.
	  da[1] = da[1].replace(/\\\\\\\\i\{\}/g, '').replace(/\\\\\\\\i0\{\}/g, '');
      var items = da[1].split(':');
	  var count = items.length;
	  if(items[count-1] == '') count--;
	  if(count <= 0){
	  	// no valid stuff so blank it all out.
	  	jQuery('#cc-4-insect-identify > select[name=insect\\:taxa_taxon_list_id]').val('');
	  	jQuery('#cc-4-flower-identify > select[name=insect\\:taxon_text_description]').val('');
	  } else if(count == 1){
	  	jQuery('#cc-4-insect-identify > select[name=insect\\:taxa_taxon_list_id]').val('');
	  	jQuery('#cc-4-insect-identify > select[name=insect\\:taxon_text_description]').val(items[0]);
	  	var x = jQuery('#cc-4-insect-identify').find('option').filter('[text='+items[0]+']');
	  	if(x.length > 0){
		  	jQuery('#cc-4-insect-identify > select[name=insect\\:taxon_text_description]').val('');
	  		jQuery('#cc-4-insect-identify > select[name=insect\\:taxa_taxon_list_id]').val(x[0].value);
  		}
	  } else {
	  	jQuery('#cc-4-insect-identify > select[name=insect\\:taxa_taxon_list_id]').val('');
	  	jQuery('#cc-4-insect-identify > select[name=insect\\:taxon_text_description]').val(da[1]);
	  }
	  insectReset();
    });
};
insectReset = function(){
	clearTimeout(insectTimer1);
	clearTimeout(insectTimer2);
	jQuery('#insect-id-cancel').hide();
};

jQuery('#insect-id-button').click(function(){
	IDcounter++;
	clearTimeout(insectTimer1);
	clearTimeout(insectTimer2);
	window.open('".$args['ID_tool_insect_url'].session_id()."_'+IDcounter.toString(),'','') 
	insectTimer1 = setTimeout('insectPoller();', ".$args['ID_tool_poll_interval'].");
	insectTimer2 = setTimeout('insectReset();', ".$args['ID_tool_poll_timeout'].");
	jQuery('#insect-id-cancel').show();
});
jQuery('#insect-id-cancel').click(function(){
	insectReset();
});
jQuery('#insect-id-cancel').hide();

    
// Insect upload picture form.
$('#cc-4-insect-upload').ajaxForm({ 
        dataType:  'json', 
        beforeSubmit:   function(data, obj, options){
        	if(jQuery('#cc-4-insect-upload input[name=upload_file]').val() == '')
        		return false;
        	$('#cc-4-insect-image').empty();
        	$('#cc-4-insect-image').addClass('loading')
        },
        success:   function(data){
        	if(data.success == true){
	        	// There is only one file
	        	jQuery('form#cc-4-main-form input[name=occurrence_image\\:path]').val(data.files[0]);
	        	var img = new Image();
	        	$(img).load(function () {
        				$(this).hide();
        				$('#cc-4-insect-image').removeClass('loading').append(this);
        				$(this).fadeIn();
			    	})
				    .attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."med-'+data.files[0])
				    .css('max-width', $('#cc-4-insect-image').width()).css('max-height', $('#cc-4-insect-image').height())
				    .css('vertical-align', 'middle').css('margin-left', 'auto').css('margin-right', 'auto').css('display', 'block');
				jQuery('#cc-4-insect-upload input[name=upload_file]').val('');
			} else {
				var errorString = \"".lang::get('LANG_Indicia_Warehouse_Error')."\";
	        	jQuery('form#cc-4-main-form input[name=occurrence_image\\:path]').val('');
				$('#cc-4-insect-image').removeClass('loading');
				if(data.error){
					errorString = errorString + ' : ' + data.error;
				}
				if(data.errors){
					for (var i in data.errors)
					{
						errorString = errorString + ' : ' + data.errors[i];
					}				
				}
				alert(errorString);
			}
        } 
});

$('#cc-4-main-form').ajaxForm({ 
    dataType:  'json', 
    beforeSubmit:   function(data, obj, options){
    	var valid = true;
		if (!jQuery('form#cc-4-main-form > input').valid()) { valid = false; }
		if (!validateRequiredField('occurrence\\:sample_id', 'form#cc-4-main-form')) { valid = false; }
		if (!validateRadio('occAttr\\:".$args['number_attr_id']."', obj)) { valid = false; }
    	if(data[1].value == '' ){
			alert(\"".lang::get('LANG_Must_Provide_Insect_Picture')."\");
			valid = false;
		}
		data[4].value = jQuery('select[name=insect\\:taxa_taxon_list_id]').val();
		data[5].value = jQuery('select[name=insect\\:taxon_text_description]').val();
		if (jQuery('#id-insect-later').attr('checked') == ''){
			if (!validateRequiredField('insect\\:taxa_taxon_list_id', '#cc-4-insect-identify')) { valid = false; }
		} else {
			data.splice(4,5); // remove determination entries.
		}
		return valid;
	},
    success:   function(data){
       	if(data.success == 'multiple records' && data.outer_table == 'occurrence'){
       		// if the currently highlighted thumbnail is blank, add the new insect.
       		var thumbnail = jQuery('[occId='+data.outer_id+']');
       		if(thumbnail.length == 0){
       			addToPhotoReel(data.outer_id);
       		} else {
       			updatePhotoReel(thumbnail, data.outer_id);
  			}
			if(loadInsectPanel == null){
				clearInsect();
			} else {
				loadInsect(loadInsectPanel);
			}
			loadInsectPanel=null;
			window.scroll(0,0);
        }
	}
});

validateInsectPanel = function(){
	if(jQuery('#cc-4').filter('.poll-hide').length > 0) return true; // panel is not visible so no data to fail validation.
	if(jQuery('#cc-4-body').filter('.poll-hide').length > 0) return true; // body hidden so data already been validated successfully.
	if(!validateInsect()){ return false; }
  	jQuery('#cc-4').foldPanel();
	return true;
};

clearInsect = function(){
	jQuery('#cc-4-main-form').resetForm();
	jQuery('[name=insect\\:taxa_taxon_list_id]').val('');
	jQuery('[name=insect\\:taxon_text_description]').val('');
    jQuery('#id-insect-later').removeAttr('checked').removeAttr('disabled');
    jQuery('#cc-4-main-form').find('[name=determination:cms_ref]').val('".$uid."');
    jQuery('#cc-4-main-form').find('[name=determination:email_address]').val('".$email."');
    jQuery('#cc-4-main-form').find('[name=determination:person_name]').val('".$username."'); 
    jQuery('#cc-4-main-form').find('[name=occurrence_image\\:path]').val('');
	jQuery('#cc-4-main-form').find('[name=occurrence\\:id],[name=occurrence_image\\:id],[name=determination\\:id]').val('').attr('disabled', 'disabled');
    jQuery('#cc-4-main-form').find('[name=occurrence_image\\:path]').val('');
	jQuery('#cc-4-main-form').find('[name^=occAttr\\:]').each(function(){
		var name = jQuery(this).attr('name').split(':');
		jQuery(this).attr('name', name[0]+':'+name[1]);
	});
    jQuery('#cc-4-insect-image').empty();
};

loadInsect = function(id){
	clearInsect();
	$.getJSON(\"".$svcUrl."/data/occurrence/\" + id +
          \"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&callback=?\", function(data) {
	    if (data.length>0) {
	        jQuery('form#cc-4-main-form > input[name=occurrence\\:id]').removeAttr('disabled').val(data[0].id);
	        jQuery('form#cc-4-main-form > [name=occurrence\\:sample_id]').val(data[0].sample_id);
			jQuery('form#cc-4-main-form > textarea[name=occurrence\\:comment]').val(data[0].comment);
			loadAttributes('occurrence_attribute_value', 'occurrence_attribute_id', 'occurrence_id', 'occurrence\\:id', data[0].id, 'occAttr');
    		loadImage('occurrence_image', 'occurrence_id', 'occurrence\\:id', data[0].id, '#cc-4-insect-image');
  		}
	});
	$.getJSON(\"".$svcUrl."/data/determination?occurrence_id=\" + id +
          \"&mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&callback=?\", function(data) {
	    if (data.length>0) {
	    	jQuery('#id-insect-later').removeAttr('checked').attr('disabled', 'disabled');
	        jQuery('form#cc-4-main-form > input[name=determination\\:id]').removeAttr('disabled').val(data[0].id);
	        jQuery('form#cc-4-main-form > input[name=determination\\:cms_ref]').val(data[0].cms_ref);
	        jQuery('form#cc-4-main-form > input[name=determination\\:email_address]').val(data[0].email_address);
	        jQuery('form#cc-4-main-form > input[name=determination\\:person_name]').val(data[0].person_name);
       		jQuery('[name=insect\\:taxa_taxon_list_id]').val(data[0].taxa_taxon_list_id);
       		jQuery('[name=insect\\:taxon_text_description]').val(data[0].taxon_text_description);
  		} else
  			jQuery('#id-insect-later').attr('checked', 'checked').removeAttr('disabled');
	});	
}

updatePhotoReel = function(container, occId){
	container.empty();
	$.getJSON(\"".$svcUrl."/data/occurrence_image\" +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&occurrence_id=\" + occId + \"&callback=?\", function(imageData) {
		if (imageData.length>0) {
			var img = new Image();
			jQuery(img).attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."thumb-'+imageData[0].path)
			    .attr('width', container.width()).attr('height', container.height()).addClass('thumb-image').appendTo(container);
		}
	});
	$.getJSON(\"".$svcUrl."/data/determination\" + 
    		\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
    		\"&occurrence_id=\" + occId + \"&deleted=f&callback=?\", function(detData) {
	    if (detData.length==0) {
	    	// no determination records, so no attempt made at identification. Put up a question mark.
			jQuery('<span>?</span>').addClass('thumb-text').appendTo(container);
		}
	});
}

addToPhotoReel = function(occId){
	// last photo in list is the blank empty one. Add to just before this.
	var container = jQuery('<div/>').addClass('thumb').insertBefore('.blankPhoto').attr('occId', occId.toString()).click(function () {setInsect(this, occId)});
	updatePhotoReel(container, occId);
}

setInsect = function(context, id){
	// first close all the other panels, ensuring any data is saved.
	if(!validateCollectionPanel() || !validateStationPanel() || !validateSessionsPanel())
		return;
		
	if(jQuery('#cc-4-body').filter('.poll-hide').length > 0) {
		jQuery('div#cc-4').unFoldPanel();
		loadInsect(id);
	} else {
		loadInsectPanel=id;
		if(!validateInsect()){
			loadInsectPanel=null;
			return;
		} 
	}
	jQuery('.currentPhoto').removeClass('currentPhoto');
	jQuery('[occId='+id+']').addClass('currentPhoto');
};

setNoInsect = function(){
	// first close all the other panels, ensuring any data is saved.
	if(!validateCollectionPanel() || !validateStationPanel() || !validateSessionsPanel())
		return;
		
	if(jQuery('#cc-4-body').filter('.poll-hide').length > 0)
		jQuery('div#cc-4').unFoldPanel();
	else
		if(!validateInsect()){ return ; }
	// At this point the empty panel is displayed, as it is reset after a successful validate.	
	jQuery('.currentPhoto').removeClass('currentPhoto');
	jQuery('.blankPhoto').addClass('currentPhoto');
};

createPhotoReel = function(div){
	jQuery(div).empty();
	jQuery('<div/>').addClass('blankPhoto thumb currentPhoto').appendTo(div).click(setNoInsect);
}

createPhotoReel('#cc-4-photo-reel');

// TODO separate photoreel out into own js

validateInsect = function(){
	// TODO will have to expand when use key.
	if(jQuery('form#cc-4-main-form > input[name=occurrence\\:id]').val() == '' &&
			jQuery('form#cc-4-main-form > input[name=occurrence_image\\:path]').val() == '' &&
			jQuery('[name=insect\\:taxa_taxon_list_id]').val() == '' &&
			jQuery('form#cc-4-main-form > textarea[name=occurrence\\:comment]').val() == '' &&
			jQuery('[name=occAttr\\:".$args['number_attr_id']."],[name^=occAttr\\:".$args['number_attr_id']."\\:]').filter('[checked]').length == 0){
		if(loadInsectPanel != null){
			loadInsect(loadInsectPanel);
		}
		loadInsectPanel=null;
		return true;
	}
	var valid = true;
    if (!jQuery('form#cc-4-main-form > input').valid()) { return false; }
  	if (!validateRadio('occAttr\\:".$args['number_attr_id']."', 'form#cc-4-main-form')) { valid = false; }
		if (jQuery('#id-insect-later').attr('checked') == ''){
			if (!validateRequiredField('insect\\:taxa_taxon_list_id', '#cc-4-insect-identify')) { valid = false; }
		}
 	if (!validateRequiredField('occurrence\\:sample_id', 'form#cc-4-main-form')) { valid = false; }
	if(jQuery('form#cc-4-main-form input[name=occurrence_image\\:path]').val() == ''){
		alert(\"".lang::get('LANG_Must_Provide_Insect_Picture')."\");
		valid = false;;
	}
	if(valid == false) return false;
	jQuery('form#cc-4-main-form').submit();
	return true;
  }

$('#cc-4-valid-insect-button').click(validateInsect);

$('#cc-4-delete-insect-button').click(function() {
	var container = $(this).parent().parent();
	jQuery('#cc-4-delete-insect').find('[name=occurrence\\:id]').val(jQuery('#cc-4-main-form').find('[name=occurrence\\:id]').val());
	jQuery('#cc-4-delete-insect').find('[name=occurrence\\:sample_id]').val(jQuery('#cc-4-main-form').find('[name=occurrence\\:sample_id]').val());
	if(confirm(\"".lang::get('LANG_Confirm_Insect_Delete')."\")){
		if(jQuery('#cc-4-main-form').find('[name=occurrence\\:id]').filter('[disabled]').length == 0){
			jQuery('#cc-4-delete-insect').submit();
			jQuery('.currentPhoto').remove();
			jQuery('.blankPhoto').addClass('currentPhoto');
		}
		clearInsect();
	}
});

$('#cc-4-delete-insect').ajaxForm({ 
        dataType:  'json', 
        beforeSubmit:   function(data, obj, options){
  			// Warning this assumes that the data is fixed position:
        	if(data[2].value == '') return false;
        	return true;
  		},
        success:   function(data){
  		} 
});

$('#cc-4-valid-photo-button').click(function(){
	if(!validateInsect()) return;
	jQuery('#cc-4').foldPanel();
	jQuery('#cc-5').showPanel();
	var numInsects = jQuery('#cc-4-photo-reel').find('.thumb').length - 1; // ignore blank
	var numUnidentified = jQuery('#cc-4-photo-reel').find('.thumb-text').length;
	if(jQuery('#id-flower-later').attr('checked') != '' || numInsects==0 || (numUnidentified/numInsects > (1-(".$args['percent_insects']."/100.0)))){
		jQuery('#cc-5-good').hide();
		jQuery('#cc-5-bad').show();
		jQuery('#cc-5-complete-collection').hide();
		jQuery('#cc-5-trailer').hide();
    } else {
    	jQuery('#cc-5-good').show();
		jQuery('#cc-5-bad').hide();
		jQuery('#cc-5-complete-collection').show();
		jQuery('#cc-5-trailer').show();
	}
});
";
    
 	$r .= '
<div id="cc-5" class="poll-section">
  <div id="cc-5-body" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-all poll-section-body"> 
   <p id="cc-5-good">'.lang::get('LANG_Can_Complete_Msg').'</p> 
   <p id="cc-5-bad">'.lang::get('LANG_Cant_Complete_Msg').'</p> 
   <div style="display:none" />
    <form id="cc-5-collection" action="'.iform_ajaxproxy_url($node, 'sample').'" method="POST">
       <input type="hidden" name="website_id" value="'.$args['website_id'].'" />
       <input type="hidden" name="sample:survey_id" value="'.$args['survey_id'].'" />
       <input type="hidden" name="sample:id" value="" />
       <input type="hidden" name="sample:date" value="2010-01-01"/>
       <input type="hidden" name="sample:location_id" value="" />
       <input type="hidden" id="smpAttr:'.$args['complete_attr_id'].'" name="smpAttr:'.$args['complete_attr_id'].'" value="1" />
    </form>
   </div>
   <div id="cc-5-complete-collection" class="ui-state-default ui-corner-all complete-button">'.lang::get('LANG_Complete_Collection').'</div>
  </div>
  <div id="cc-5-trailer" class="poll-section-trailer">
    <p>'.lang::get('LANG_Trailer_Head').'</p>
    <ul>
      <li>'.lang::get('LANG_Trailer_Point_1').'</li>
      <li>'.lang::get('LANG_Trailer_Point_2').'</li>
      <li>'.lang::get('LANG_Trailer_Point_3').'</li>
      <li>'.lang::get('LANG_Trailer_Point_4').'</li>
    </ul>
  </div>
</div>';

data_entry_helper::$javascript .= "
$('#cc-5-collection').ajaxForm({ 
        dataType:  'json', 
        beforeSubmit:   function(data, obj, options){
       		data[2].value = jQuery('#cc-1-collection-details input[name=sample\\:id]').val();
       		var date_start = '';
       		var date_end = '';
       		jQuery('.poll-session').find('[name=sample\\:date]').each(function(index, el){
       			var value = $(this).val();
       			if(date_start == '' || date_start > value) {
       				date_start = value;
       			}
       			if(date_end == '' || date_end < value) {
       				date_end = value;
       			}
  			});
  			if(date_start == date_end){
	       		data[3].value = date_start;
	       	} else {
	       		data[3].value = date_start+' to '+date_end;
  			}
	       	jQuery('[name=sample\\:date]:hidden').val(data[3].value);
  			data[4].value = jQuery('#cc-1-collection-details input[name=location\\:id]').val();
       		data[5].name = jQuery('#cc-1-collection-details input[name^=smpAttr\\:".$args['complete_attr_id']."\\:]').attr('name');
        	return true;
  		},
        success:   function(data){
			$('#cc-6').showPanel();
  		} 
});
$('#cc-5-complete-collection').click(function(){
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
</div></div>';
 
data_entry_helper::$javascript .= "
 			
loadAttributes = function(attributeTable, attributeKey, key, keyName, keyValue, prefix){
	var form = jQuery('input[name='+keyName+'][value='+keyValue+']').parent();
	var checkboxes = jQuery('[name^='+prefix+'\\:]', form).filter(':checkbox').removeAttr('checked');
	checkboxes.each(function(){
		var name = jQuery(this).attr('name').split(':');
		if(name.length > 2)
			jQuery(this).attr('name', name[0]+':'+name[1]+'[]');
	});
	
	$.getJSON(\"".$svcUrl."/data/\" + attributeTable +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&\" + key + \"=\" + keyValue + \"&callback=?\", function(attrdata) {
		if (attrdata.length>0) {
			var form = jQuery('input[name='+keyName+'][value='+keyValue+']').parent();
			for (var i=0;i<attrdata.length;i++){
				if (attrdata[i].id && (attrdata[i].iso == null || attrdata[i].iso == '' || attrdata[i].iso == '".$language."')){
					var checkboxes = jQuery('[name='+prefix+'\\:'+attrdata[i][attributeKey]+'\\[\\]],[name^='+prefix+'\\:'+attrdata[i][attributeKey]+':]', form).filter(':checkbox');
					var radiobuttons = jQuery('[name='+prefix+'\\:'+attrdata[i][attributeKey]+'],[name^='+prefix+'\\:'+attrdata[i][attributeKey]+':]', form).filter(':radio');
					if(radiobuttons.length > 0){
						radiobuttons
							.attr('name', prefix+':'+attrdata[i][attributeKey]+':'+attrdata[i].id)
							.filter('[value='+attrdata[i].raw_value+']')
							.attr('checked', 'checked');
					} else 	if(checkboxes.length > 0){
						var checkbox = checkboxes.filter('[value='+attrdata[i].raw_value+']')
							.attr('name', prefix+':'+attrdata[i][attributeKey]+':'+attrdata[i].id)
							.attr('checked', 'checked');
					} else {
						jQuery('[name='+prefix+'\\:'+attrdata[i][attributeKey]+']', form)
							.attr('name', prefix+':'+attrdata[i][attributeKey]+':'+attrdata[i].id)
							.val(attrdata[i].raw_value);
					}
				}
			}
		}
		checkProtocolStatus();
		populateSessionSelect();
	});
}

loadImage = function(imageTable, key, keyName, keyValue, target){
					// location_image, location_id, location:id, 1, #cc-4-insect-image
	$.getJSON(\"".$svcUrl."/data/\" + imageTable +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&\" + key + \"=\" + keyValue + \"&callback=?\", function(imageData) {
		if (imageData.length>0) {
			var form = jQuery('input[name='+keyName+'][value='+keyValue+']').parent();
			jQuery('[name='+imageTable+'\\:id]', form).val(imageData[0].id).removeAttr('disabled');
			jQuery('[name='+imageTable+'\\:path]', form).val(imageData[0].path);
			var img = new Image();
			$(img).load(function () {
        			$(target).empty().append(this);
			    })
			    .attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$indicia_upload_path)."med-'+imageData[0].path)
				.css('max-width', $(target).width()).css('max-height', $(target).height()).css('display', 'block')
				.css('vertical-align', 'middle').css('margin-left', 'auto').css('margin-right', 'auto');
		}
	});
}

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

jQuery.getJSON(\"".$svcUrl."\" + \"/report/requestReport?report=poll_my_collections.xml&reportSource=local&mode=json\" +
			\"&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"]."\" + 
			\"&survey_id=".$args['survey_id']."&userID_attr_id=".$args['uid_attr_id']."&userID=".$uid."&complete_attr_id=".$args['complete_attr_id']."&callback=?\", function(data) {
	if (data.length>0) {
		var i;
       	for ( i=0;i<data.length;i++) {
       		if(data[i].completed == '0'){
       		    jQuery('#cc-6-consult-collection').attr('href', '".url('node/'.$args['gallery_node'])."'+'?collection_id='+data[i].id);
       			// load up collection details: existing ID, location name and protocol
       			jQuery('#cc-1-collection-details,#cc-2').find('input[name=sample\\:id]').val(data[i].id).removeAttr('disabled');
       			// main sample date is only set when collection is completed, so leave default.
       			loadAttributes('sample_attribute_value', 'sample_attribute_id', 'sample_id', 'sample\\:id', data[i].id, 'smpAttr');
  				$.getJSON(\"".$svcUrl."/data/location/\" + data[i].location_id +
          					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
          					\"&callback=?\", function(locationdata) {
		    		if (locationdata.length>0) {
		    			jQuery('input[name=location\\:id]').val(locationdata[0].id).removeAttr('disabled');
	    				jQuery('input[name=location\\:name]').val(locationdata[0].name);
       					jQuery('input[name=sample\\:location_name]').val(locationdata[0].name); // make sure the 2 coincide
	    				// NB the location geometry is stored in centroid, due to restrictions in location model.
	    				jQuery('input[name=location\\:centroid_sref]').val(locationdata[0].centroid_sref);
	    				jQuery('input[name=location\\:centroid_sref_system]').val(locationdata[0].centroid_sref_system);
	    				jQuery('input[name=location\\:centroid_geom]').val(locationdata[0].centroid_geom);
	    				jQuery('input[name=locations_website\\:website_id]').attr('disabled', 'disabled');
	    				loadAttributes('location_attribute_value', 'location_attribute_id', 'location_id', 'location\\:id', locationdata[0].id, 'locAttr');
    	   				loadImage('location_image', 'location_id', 'location\\:id', locationdata[0].id, '#cc-2-environment-image');
						jQuery('#imp-sref').change();
				        var parts=locationdata[0].centroid_sref.split(' ');
 						jQuery('input[name=place\\:lat]').val(parts[0]);
						jQuery('input[name=place\\:long]').val(parts[1]);
  					}
  				});
  				$.getJSON(\"".$svcUrl."/data/occurrence/\" +
          					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
          					\"&sample_id=\"+data[i].id+\"&callback=?\", function(flowerData) {
          			// there will only be an occurrence if the floral station panel has previously been displayed & validated. 
		    		if (flowerData.length>0) {
  						$('#cc-1').foldPanel();
  						$('#cc-2').showPanel();
		    			jQuery('form#cc-2-floral-station > input[name=occurrence\\:sample_id]').val(data[i].id);
		    			jQuery('form#cc-2-floral-station > input[name=occurrence\\:id]').val(flowerData[0].id).removeAttr('disabled');
		    			loadAttributes('occurrence_attribute_value', 'occurrence_attribute_id', 'occurrence_id', 'occurrence\\:id', flowerData[0].id, 'occAttr');
    	   				loadImage('occurrence_image', 'occurrence_id', 'occurrence\\:id', flowerData[0].id, '#cc-2-flower-image');

    	   				$.getJSON(\"".$svcUrl."/data/determination\" + 
    	      						\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&occurrence_id=\"+flowerData[0].id+\"&deleted=f&callback=?\",
    	      					function(detData) {
	    			  		if (detData.length>0) {
								jQuery('#id-flower-later').removeAttr('checked').attr('disabled', 'disabled');
	    			  			jQuery('form#cc-2-floral-station > input[name=determination\\:id]').val(detData[0].id).removeAttr('disabled');
		    					jQuery('form#cc-2-floral-station > input[name=determination\\:cms_ref]').val(detData[0].cms_ref);
								jQuery('form#cc-2-floral-station > input[name=determination\\:email_address]').val(detData[0].email_address);
								jQuery('form#cc-2-floral-station > input[name=determination\\:person_name]').val(detData[0].person_name);
								jQuery('select[name=flower\\:taxa_taxon_list_id]').val(detData[0].taxa_taxon_list_id);
								jQuery('select[name=flower\\:taxon_text_description]').val(detData[0].taxon_text_description);
  							} else {
	    			  			jQuery('form#cc-2-floral-station > input[name=determination\\:id]').val('').attr('disabled', 'disabled');
								jQuery('#id-flower-later').attr('checked', 'checked').removeAttr('disabled');
							}
  						});

    	   				$.getJSON(\"".$svcUrl."/data/sample\" + 
    	      					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&parent_id=\"+data[i].id+\"&callback=?\", function(sessiondata) {
	    			  		if (sessiondata.length>0) {
								jQuery('#cc-2').foldPanel();
								sessionCounter = 0;
								jQuery('#cc-3-body').empty();
 								$('#cc-3').showPanel();
								for (var i=0;i<sessiondata.length;i++){
									var thisSession = addSession();
									jQuery('input[name=sample\\:id]', thisSession).val(sessiondata[i].id).removeAttr('disabled');
									jQuery('input[name=sample\\:date]', thisSession).val(sessiondata[i].date_start);
       								loadAttributes('sample_attribute_value', 'sample_attribute_id', 'sample_id', 'sample\\:id', sessiondata[i].id, 'smpAttr');
  									// fold this session.
  									thisSession.show();
									thisSession.children(':first').show().children().show();
									thisSession.children().not(':first').hide();
									$.getJSON(\"".$svcUrl."/data/occurrence/\" +
          									\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&orderby=id\" +
          									\"&sample_id=\"+sessiondata[i].id+\"&callback=?\", function(insectData) {
		    							if (insectData.length>0) {
 											for (var j=0;j<insectData.length;j++){
												addToPhotoReel(insectData[j].id);
											}
										}
		    						});
								}
 								$('#cc-3').foldPanel();
 								$('#cc-4').showPanel();
								populateSessionSelect();
 					  		}
 					  		$('.loading-panel').remove();
							$('.loading-hide').removeClass('loading-hide');
						});
    	   			} else {
    	   				$('.loading-panel').remove();
						$('.loading-hide').removeClass('loading-hide');
    	   			}
  				});
				// only use the first one which is not complete..
				break;
			}
		}
		if (i >= data.length) {
			$('.loading-panel').remove();
			$('.loading-hide').removeClass('loading-hide');
  		}
	} else {
		$('.loading-panel').remove();
		$('.loading-hide').removeClass('loading-hide');
	}
});
  
  ";
// because of the use of getJson to retrieve the data - which is asynchronous, the use of the normal loading_block_end
// is not practical - it will do its stuff before the data is loaded, defeating the purpose. Also it uses hide (display:none)
// which is a no-no in relation to the map. This means we have to dispense with the slow fade in.
// it is also complicated by the attibutes and images being loaded asynchronously - and non-linearly.
// Do the best we can! 

    data_entry_helper::$onload_javascript .= "jQuery('#map')[0].map.searchLayer.events.register('featuresadded', {}, function(a1){
	if(inseeLayer != null)
		inseeLayer.destroyFeatures();
});
jQuery('#map')[0].map.editLayer.events.register('featuresadded', {}, function(a1){
	if(inseeLayer != null)
		inseeLayer.destroy();
		
  	var filter = new OpenLayers.Filter.Spatial({
  			type: OpenLayers.Filter.Spatial.CONTAINS ,
    		property: 'the_geom',
    		value: jQuery('#map')[0].map.editLayer.features[0].geometry
  		});

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
      		  ,propertyNames: ['the_geom', 'NOM', 'INSEE_NEW', 'DEPT_NUM', 'DEPT_NOM', 'REG_NUM', 'REG_NOM']
  			})
    });
    inseeLayer.events.register('featuresadded', {}, function(a1){
    	jQuery('#cc-2-loc-description').empty();
    	jQuery('<span>'+a1.features[0].attributes.NOM+' ('+a1.features[0].attributes.INSEE_NEW+'), '+a1.features[0].attributes.DEPT_NOM+' ('+a1.features[0].attributes.DEPT_NUM+'), '+a1.features[0].attributes.REG_NOM+' ('+a1.features[0].attributes.REG_NUM+')</span>').appendTo('#cc-2-loc-description');
    });
	jQuery('#map')[0].map.addLayer(inseeLayer);
	strategy.load({filter: filter});
});
\n";

	global $indicia_templates;
	$r .= $indicia_templates['loading_block_end'];

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