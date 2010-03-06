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

class iform_pollenators {

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
    return 'Pollenators';
  }

/**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args, $node) {
  	global $user;
    $logged_in = $user->uid>0;
    $uid = $user->uid;
    $email = $user->mail;
    $username = $user->name;

  	$r = '';

    // Get authorisation tokens to update and read from the Warehouse.
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
	$svcUrl = data_entry_helper::$base_url.'/index.php/services';

//	$presetLayers = array();
//    // read out the activated preset layers
//    if(isset($args['preset_layers'])) {
//	    foreach($args['preset_layers'] as $layer => $active) {
//    	  if ($active!==0) {
//        	$presetLayers[] = $layer;
//    	  }
//    	}
//    }
	drupal_add_js(drupal_get_path('module', 'iform') .'/media/js/jquery.form.js', 'module');
	data_entry_helper::enable_validation('cc-1-collection-details'); // don't care about ID itself, just want resources
	
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
    

    

	// note we have to proxy the post. Every time a write transaction is carried out, the write nonce is trashed.
	// For security reasons we don't want to give the user the ability to generate their own nonce, so we use
	// the fact that the user is logged in to drupal as the main authentication/authorisation/identification
	// process for the user. The proxy packages the post into the correct format
	// 
	// TODO required validation of radio buttons
	
    // There are 2 types of submission:
    // When a user validates a panel using the validate button, the following panel is opened on success
    // When a user presses a modify button, the open panel gets validated, and the panel to be modified is opened.
	
 	$r .= '
<div id="cc-1" class="poll-section">
  <div id="cc-1-title" class="poll-section-title">
  	<span id="cc-1-title-details">'.lang::get('LANG_Collection_Details').'</span>
  	<span id="cc-1-protocol-details"></span>
    <div class="right">
      <div>
        <span id="cc-1-reinit-button" class="reinit-button poll-button-1">'.lang::get('LANG_Reinitialise').'</span>
        <span id="cc-1-mod-button" class="mod-button poll-button-1">'.lang::get('LANG_Modify').'</span>
      </div>
    </div>
  </div>
  <div id="cc-1-body" class="poll-section-body">
   <form id="cc-1-collection-details" action="'.iform_ajaxproxy_url($node, 'loc-sample').'" method="POST">
    <input type="hidden" id="website_id"       name="website_id" value="'.$args['website_id'].'" />
    <input type="hidden" id="imp-sref"         name="location:centroid_sref"  value="46.60361, 1.88806" />
    <input type="hidden" id="imp-geom"         name="location:centroid_geom" value="POINT(46.60361 1.88806)" />
    <input type="hidden" id="imp-sref-system"  name="location:centroid_sref_system" value="4326" />
    <input type="hidden" id="sample:survey_id" name="sample:survey_id" value="'.$args['survey_id'].'" />
    <label for="location:name">'.lang::get('LANG_Collection_Name_Label').'</label>
 	<input type="text" id="location:name"      name="location:name" value="" class="required"/><br />
    <input type="hidden" id="sample:location_name" name="sample:location_name" value=""/>
 	'.data_entry_helper::outputAttribute($sample_attributes[$args['protocol_attr_id']],
 			array('extraParams'=>$readAuth, 'lookUpListCtrl' => 'radio_group', 'validation' => array('required'), 'sep' => '<br/>'))
 	.'    <input type="hidden"                       name="sample:date" value="2010-01-01"/>
    <input type="hidden" id="smpAttr:'.$args['complete_attr_id'].'" name="smpAttr:'.$args['complete_attr_id'].'" value="0" />
    <input type="hidden" id="smpAttr:'.$args['uid_attr_id'].'" name="smpAttr:'.$args['uid_attr_id'].'" value="'.$uid.'" />
    <input type="hidden" id="smpAttr:'.$args['email_attr_id'].'" name="smpAttr:'.$args['email_attr_id'].'" value="'.$email.'" />
    <input type="hidden" id="smpAttr:'.$args['username_attr_id'].'" name="smpAttr:'.$args['username_attr_id'].'" value="'.$username.'" />  
    <input type="hidden" id="locations_website:website_id" name="locations_website:website_id" value="'.$args['website_id'].'" />
    <input type="hidden" id="location:id"      name="location:id" value="" disabled="disabled" />
    <input type="hidden" id="sample:id"        name="sample:id" value="" disabled="disabled" />
    </form>
  </div>
  <div id="cc-1-footer" class="poll-section-footer">
    <div id="cc-1-valid-button" class="right poll-button-1">'.lang::get('LANG_Validate').'</div><br />
  </div>
  
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
';

    data_entry_helper::$javascript .= "
var sessionCounter = 0;

$.fn.foldPanel = function(){
	this.find('.poll-section-body').hide();
	this.find('.poll-section-footer').hide();
	this.find('.reinit-button').show();
	this.find('.mod-button').show();
};

$.fn.unFoldPanel = function(){
	this.find('.poll-section-body').show();
	this.find('.poll-section-footer').show();
	this.find('.mod-button').hide();
	// any reinit button is left in place
};

$.fn.showPanel = function(){
	// if already visible, user has gone back and modified previous panel. Leave in current state.
	if(this.filter(':visible').length > 0) return;
	this.show();
	this.find('.reinit-button').hide(); // hide on first display
	this.unFoldPanel();
};

$.fn.resetPanel = function(){
	this.find('.poll-section-body').show();
	this.find('.poll-section-footer').show();
	this.find('.reinit-button').show();
	this.find('.mod-button').show();
	this.find('.poll-image').empty();
	this.find('.#poll-session').empty();

	// resetForm does not reset the hidden fields. record_status, imp-sref-system, website_id and survey_id are not altered so do not reset.
	// hidden Attributes generally hold unchanging data, but the name needs to be reset (does it for non hidden as well).
	// leave the map/geom pointing to the same place.
	// hidden location:name are set in code anyway.
	this.find('form').each(function(){
		jQuery(this).resetForm();
		jQuery(this).find('[name=sample\\:location_name],[name=location_image\\:path],[name=occurrence_image\\:path]').val('');
		jQuery(this).find('[name=sample\\:id],[name=location\\:id],[name=location_image\\:id],[name=occurrence\\:id],[name=occurrence_image\\:id]').val('').attr('disabled', 'disabled');
		jQuery(this).find('[name=sample\\:date]:hidden').val('2010-01-01');
        jQuery(this).find('input[name=locations_website\\:website_id]').removeAttr('disabled');
		jQuery(this).find('[name^=smpAttr\\:],[name^=locAttr\\:],[name^=occAttr\\:]').each(function(){
			var name = jQuery(this).attr('name').split(':');
			jQuery(this).attr('name', name[0]+':'+name[1]);
		});
	});	
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
        jQuery('#cc-1-protocol-details').empty().show().text('".lang::get('LANG_Protocol_Title_Label')." : ' + checkedProtocol[0].textContent);
    } else {
        jQuery('#cc-1-title-details').empty().text('".lang::get('LANG_Collection_Details')."');
        jQuery('#cc-1-protocol-details').empty().hide();
    }
};

showStationPanel = true;

// The validate functionality for each panel is sufficiently different that we can't generalise a function
// this is the one called when we don't want the panel following to be opened automatically.
validateCollectionPanel = function(){
	var myPanel = jQuery('#cc-1');
	if(myPanel.filter(':visible').length < 1) return true; // panel is not visible so no data to fail validation.
	if(myPanel.find('.poll-section-body:visible').length < 1) return true; // body hidden so data already been validated successfully.
	if(!myPanel.find('form > input').valid()){ return false; }
	// no need to check protocol - if we are this far, we've already filled it in.
  	showStationPanel = false;
	myPanel.find('form').submit();
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
        	jQuery('#cc-2-floral-station > input[name=location\\:name]').val(data[5].value);
        	return true;
  		},
        success:   function(data){
        	// TODO L4 sort out image sizing, image size should be css driven
            // TODO: error condition handling
        	if(data.success == 'multiple records' && data.outer_table == 'location'){
        	    jQuery('#cc-1-collection-details > input[name=location\\:id]').removeAttr('disabled').val(data.outer_id);
        	    jQuery('#cc-1-collection-details > input[name=locations_website\\:website_id]').attr('disabled', 'disabled');
        	    jQuery('#cc-2-floral-station > input[name=location\\:id]').removeAttr('disabled').val(data.outer_id);
        	    $.getJSON(\"".$svcUrl."\" + \"/data/sample\" +
			          \"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
			          \"&location_id=\"+data.outer_id+\"&callback=?\", function(data) {
					if (data.length>0) {
			        	    jQuery('#cc-1-collection-details > input[name=sample\\:id]').removeAttr('disabled').val(data[0].id);
			        	    jQuery('#cc-2-floral-station > input[name=sample\\:id]').removeAttr('disabled').val(data[0].id);
			        	    // In this case we use loadAttributes to set the names of the attributes to include the attribute_value id.
   	       					loadAttributes('sample_attribute_value', 'sample_attribute_id', 'sample_id', 'sample\\:id', data[0].id, 'smpAttr');
						}
				});
        	}
        	checkProtocolStatus();
        	$('#cc-1').foldPanel();
    		if(showStationPanel){ $('#cc-2').showPanel(); }
	    	showStationPanel = true;
	    	
        } 
});

$('#cc-1-delete-collection').ajaxForm({ 
        dataType:  'json', 
        beforeSubmit:   function(data, obj, options){
        	// TODO put catch in to abandon if sample_id is not set. Low priority
  			// Warning this assumes that the data is fixed position:
       		data[2].value = jQuery('#cc-1-collection-details input[name=sample\\:id]').val();
       		data[3].value = jQuery('#cc-1-collection-details input[name=sample\\:date]').val();
       		data[4].value = jQuery('#cc-1-collection-details input[name=sample\\:location_id]').val();
        	return true;
  		},
        success:   function(data){
			$('.poll-section').resetPanel();
			sessionCounter = 0;
			addSession();
			checkProtocolStatus();
			$('.poll-section').hide();
			$('.poll-image').empty();
			$('#cc-1').showPanel();
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
// TODO L1 Copy the sref, sref_system & geom in from the map section: this may be done automatically. This will be done by the map code.
    $r .= '
<div id="cc-2" class="poll-section">
  <div id="cc-2-title" class="poll-section-title"><span>'.lang::get('LANG_Flower_Station').'</span>
    <div class="right">
      <span id="cc-2-mod-button" class="mod-button poll-button-1">'.lang::get('LANG_Modify').'</span>
    </div>
  </div>
  <div id="cc-2-body" class="poll-section-body">
    <div id="cc-2-flower">
      <div id="cc-2-flower-picture">
		<form id="cc-2-flower-upload" enctype="multipart/form-data" action="'.iform_ajaxproxy_url($node, 'media').'" method="POST">
    		<input type="hidden" id="website_id" name="website_id" value="'.$args['website_id'].'" />
     		<input type="submit" value="'.lang::get('LANG_Upload_Flower').'"/>
    		<input name="upload_file" type="file" class="required" />
		</form>
 	    <div id="cc-2-flower-image" class="poll-image">
 	    </div>
 	  </div>
 	  <div id="cc-2-flower-identify">
        <span>'.lang::get('LANG_Identify_Flower').'</span>
 	  </div>
 	</div>
    <div id="cc-2-environment">
      <div id="cc-2-environment-picture">
		<form id="cc-2-environment-upload" enctype="multipart/form-data" action="'.iform_ajaxproxy_url($node, 'media').'" method="POST">
    		<input type="hidden" id="website_id" name="website_id" value="'.$args['website_id'].'" />
    		<input type="submit" value="'.lang::get('LANG_Upload_Environment').'"/>
    		<input name="upload_file" type="file" class="required" />
		</form>
 	    <div id="cc-2-environment-image" class="poll-image">
 	    </div>
 	    <span>'.lang::get('LANG_Environment_Notes').'</span><br />
 	  </div>
 	</div>
 	<br />
';
//    $r .= data_entry_helper::georeference_lookup(array(
//      'label' => lang::get('LANG_Georef_Label'),
//      'georefPreferredArea' => $args['georefPreferredArea'],
//      'georefCountry' => $args['georefCountry'],
//      'georefLang' => $args['language']
//    ));
    $options = iform_map_get_map_options($args, $readAuth);
    $r .= data_entry_helper::map_panel($options);
    $extraParams = $readAuth + array('taxon_list_id' => $args['flower_list_id']);
    $species_ctrl_args=array(
    	    'label'=>lang::get('LANG_Flower_Species'),
        	'fieldname'=>'occurrence:taxa_taxon_list_id',
	        'table'=>'taxa_taxon_list',
    	    'captionField'=>'taxon',
        	'valueField'=>'id',
	        'columns'=>2,
			'validation'=>array('required'),
    	    'extraParams'=>$extraParams
	); // TODO LANG_Blank_Species_Text, which species list?
 	$r .= '
   <form id="cc-2-floral-station" action="'.iform_ajaxproxy_url($node, 'loc-smp-occ').'" method="POST">
    <input type="hidden" id="website_id" name="website_id" value="'.$args['website_id'].'" />
    <input type="hidden" id="location:id" name="location:id" value="" />
    <input type="hidden" id="location:name" name="location:name" value=""/>
    <input type="hidden" name="location:centroid_sref" />
    <input type="hidden" name="location:centroid_geom" />
    <input type="hidden" name="location:centroid_sref_system" value="4326" />
    <input type="hidden" id="location_image:id" name="location_image:id" value="" disabled="disabled" />
    <input type="hidden" id="location_image:path" name="location_image:path" value="" />
    <input type="hidden" id="sample:survey_id" name="sample:survey_id" value="'.$args['survey_id'].'" />
    <input type="hidden" id="sample:id" name="sample:id" value=""/>
    <input type="hidden" name="sample:date" value="2010-01-01"/>
    <input type="hidden" id="occurrence:id" name="occurrence:id" value="" disabled="disabled" />
    <input type="hidden" id="occurrence_image:id" name="occurrence_image:id" value="" disabled="disabled" />
    <input type="hidden" id="occurrence_image:path" name="occurrence_image:path" value="" />
    '.data_entry_helper::autocomplete($species_ctrl_args)
 	.data_entry_helper::outputAttribute($occurrence_attributes[$args['flower_type_attr_id']], array('extraParams'=>$readAuth, 'lookUpListCtrl' => 'radio_group', 'sep' => ' &nbsp; '))
 	.data_entry_helper::outputAttribute($location_attributes[$args['habitat_attr_id']], array('extraParams'=>$readAuth, 'lookUpListCtrl' => 'radio_group', 'sep' => ' &nbsp; '))
 	.data_entry_helper::outputAttribute($location_attributes[$args['distance_attr_id']], array('extraParams'=>$readAuth, 'lookUpListCtrl' => 'radio_group', 'sep' => ' &nbsp; ')).'  	 	
   </form>
  </div>
  <div id="cc-2-footer" class="poll-section-footer">
    <div id="cc-2-valid-button" class="right poll-button-1">'.lang::get('LANG_Validate_Flower').'</div><br />
  </div>
</div>';
	// NB the distance attribute is left blank at the moment if unknown: TODO put in a checkbox : checked if blank for nsp
 	data_entry_helper::$javascript .= "

showSessionsPanel = true;

validateStationPanel = function(){
	var myPanel = jQuery('#cc-2');
	var valid = true;
	if(myPanel.filter(':visible').length < 1) return true; // panel is not visible so no data to fail validation.
	if(myPanel.find('.poll-section-body:visible').length < 1) return true; // body hidden so data already been validated successfully.
	// If no data entered also return true: this can only be the case when pressing the modify button on the collections panel
	if(jQuery('form#cc-2-floral-station > input[name=location_image\\:path]').val() == '' &&
			jQuery('form#cc-2-floral-station > input[name=occurrence\\:id]').val() == '' &&
			jQuery('form#cc-2-floral-station > input[name=occurrence_image\\:path]').val() == '' &&
			jQuery('form#cc-2-floral-station > input[name=occurrence\\:taxa_taxon_list_id]').val() == '' &&
    		jQuery('[name=occAttr\\:".$args['flower_type_attr_id']."],[name^=occAttr\\:".$args['flower_type_attr_id']."\\:]').filter('[checked]').length == 0 &&
    		jQuery('[name=locAttr\\:".$args['habitat_attr_id']."],[name^=locAttr\\:".$args['habitat_attr_id']."\\:]').filter('[checked]').length == 0 &&
    		jQuery('[name=locAttr\\:".$args['distance_attr_id']."],[name^=locAttr\\:".$args['distance_attr_id']."\\:]').val() == '') {
		jQuery('#cc-2').foldPanel();
		return true;
	}
    if(jQuery('form#cc-2-floral-station > input[name=location_image\\:path]').val() == '' ||
					jQuery('form#cc-2-floral-station > input[name=occurrence_image\\:path]').val() == ''){
		alert('".lang::get('LANG_Must_Provide_Pictures')."');
		valid = false;
	}
	if (!jQuery('form#cc-2-floral-station > input').valid()) { valid = false; }
   	if (!validateRadio('occAttr\\:".$args['flower_type_attr_id']."', 'form#cc-2-floral-station')) { valid = false; }
	// TODO find out if habitat descriptions are required.
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
        	// TODO L4 sort out image sizing, image size should be css driven
        	jQuery('form#cc-2-floral-station input[name=occurrence_image\\:path]').val(data.filename);
        	var img = new Image();
        	$(img)
        		.load(function () {
        			$(this).hide();
        			$('#cc-2-flower-image').removeClass('loading').append(this);
        			$(this).fadeIn();
			    })
			    .error(function () { }) // L3 TODO
			    .attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$upload_path)."med-'+data.filename)
			    .attr('width', 300).attr('height', 300);
			jQuery('#cc-2-flower-upload input[name=upload_file]').val('');
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
        	// TODO L4 sort out image sizing, image size should be css driven
        	jQuery('form#cc-2-floral-station input[name=location_image\\:path]').val(data.filename);
        	var img = new Image();
        	$(img)
        		.load(function () {
        			$(this).hide();
        			$('#cc-2-environment-image').removeClass('loading').append(this);
        			$(this).fadeIn();
			    })
			    .error(function () { // L3 TODO
			    })
			    .attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$upload_path)."med-'+data.filename)
			    .attr('width', 300).attr('height', 300);
			jQuery('#cc-2-environment-upload input[name=upload_file]').val('');
        } 
});

$('#cc-2-floral-station').ajaxForm({ 
    dataType:  'json', 
    beforeSubmit:   function(data, obj, options){
    	//TODO need to add check here and in validate for taxon required.
		var valid = true;
    	if(jQuery('form#cc-2-floral-station > input[name=location_image\\:path]').val() == '' ||
					jQuery('form#cc-2-floral-station > input[name=occurrence_image\\:path]').val() == '' ){
			alert('".lang::get('LANG_Must_Provide_Pictures')."');
			valid = false;
		}
		if (!jQuery('form#cc-2-floral-station > input').valid()) { valid = false; }
   		if (!validateRadio('occAttr\\:".$args['flower_type_attr_id']."', 'form#cc-2-floral-station')) { valid = false; }
		// TODO find out if habitat descriptions are required.
   		if ( valid == false ) return valid;
		// DANGER this assumes certain positioning of the centroid sref and geom within the data array
		if(data[3].name != 'location:centroid_sref' || data[4].name != 'location:centroid_geom') {
			alert('Internal error: imp-sref or imp-geom post location mismatch');
			return false;
		}
		data[3].value = jQuery('#imp-sref').val();
		data[4].value = jQuery('#imp-geom').val();
		return true;
	},
    success:   function(data){
       	// TODO L4 sort out image sizing, image size should be css driven
        // TODO: error condition handling
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
	// TODO L1 when deleting, need to check if there are any photos attached to the session
	// TODO L1 when deleting when a sample_id exists in the form, set a deleted flag in the db, and submit, then delete dom
	// TODO L2 sort out single session for flash protocol.
    // TODO L2 put up a confirmation alert on Deleting
    // TODO L3 Copy the date to main collections details form from the first session. This will be done by the session code.
	// TODO L4 Help
    $r .= '
<div id="cc-3" class="poll-section">
  <div id="cc-3-title" class="poll-section-title"><span>'.lang::get('LANG_Sessions_Title').'</span>
    <div id="cc-3-mod-button" class="right mod-button poll-button-1">'.lang::get('LANG_Modify').'</div>
  </div>
  <div id="cc-3-body" class="poll-section-body">
  </div>
  <div id="cc-3-footer" class="poll-section-footer">
	<div id="cc-3-add-button" class="right poll-button-1 add-button">'.lang::get('LANG_Add_Session').'</div>
    <div id="cc-3-valid-button" class="right poll-button-1">'.lang::get('LANG_Validate_Session').'</div><br />
  </div>
</div>';

 	$defAttrOptions = array('extraParams'=>$readAuth, 'lookUpListCtrl' => 'radio_group', 'validation' => array('required')); 	
    data_entry_helper::$javascript .= "
populateSessionSelect = function(){
	var insectSessionSelect = jQuery('form#cc-4-main-form > select[name=occurrence\\:sample_id]');
	insectSessionSelect.empty().append('<option/>');
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
	var newModButton = jQuery('<div class=\"right poll-button-1\">".lang::get('LANG_Modify')."</div><br />')
		.appendTo(newTitle).hide();
	newModButton.click(function() {
		if(!validateAndSubmitOpenSessions()) return false;
		var session=$(this).parents('.poll-session');;
		session.show();
		session.children().show();
		session.children(':first').children().hide(); // this is the mod button itself
    });
    var newForm = jQuery('<form action=\"".iform_ajaxproxy_url($node, 'sample')."\" method=\"POST\" class=\"poll-session-form\" />').appendTo(newSession);
	jQuery('<input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />').appendTo(newForm);
	jQuery('<input type=\"hidden\" name=\"sample:survey_id\" value=\"".$args['survey_id']."\" />').appendTo(newForm);
	jQuery('<input type=\"hidden\" name=\"sample:parent_id\" />').appendTo(newForm).val(jQuery('#cc-1-collection-details > input[name=sample\\:id]').val());
	jQuery('<input type=\"hidden\" name=\"sample:location_id\" />').appendTo(newForm).val(jQuery('#cc-1-collection-details > input[name=location\\:id]').val());
	jQuery('<input type=\"hidden\" name=\"sample:id\" value=\"\" disabled=\"disabled\" />').appendTo(newForm);
	var dateAttr = '".str_replace("\n", "", data_entry_helper::date_picker(array('label' => lang::get('LANG_Date'),
    						'id' => '<id>',
							'fieldname' => 'sample:date',
    						'class' => 'vague-date-picker required')))."';
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
	var newFooter = jQuery('<div id=\"cc-3-session-footer-'+sessionCounter+'\" />').appendTo(newSession);
	var newDeleteButton = jQuery('<div class=\"right poll-button-1 delete-button\">".lang::get('LANG_Delete_Session')."</div><br /><br />')
		.appendTo(newFooter);	
	newDeleteButton.click(function() {
		$(this).parent().parent().remove();
		checkProtocolStatus();
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

addSession();

validateSessionsPanel = function(){
	if(jQuery('#cc-3').filter(':visible').length < 1) return true; // panel is not visible so no data to fail validation.
	if(jQuery('#cc-3').find('.poll-section-body:visible').length < 1) return true; // body hidden so data already been validated successfully.
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
	jQuery(this).parents('.poll-section').unFoldPanel();
});

";

    // TODO Photos.
    // TODO
    // L1 Add list_occurrence_images view
    // Click on empty thumbnail: displays empty insect. 
    // Radio button attributes: validation, reset values after saving, restore values when selecting from photoreel.
    // Determination : uncertainty, identification key tool.
    // Looks.
    // Validate Photos button.
    // Attach to a session sample rather than top level
    // TODO mod button.
    // TODO create photo reel
    //
    $extraParams = $readAuth + array('taxon_list_id' => $args['insect_list_id']);
	$species_ctrl_args=array(
    	    'label'=>lang::get('LANG_Insect_Species'),
        	'fieldname'=>'occurrence:taxa_taxon_list_id',
	        'table'=>'taxa_taxon_list',
    	    'captionField'=>'taxon',
        	'valueField'=>'id',
	        'columns'=>2,
			'validation'=>array('required'),
    	    'extraParams'=>$extraParams
	); // TODO LANG_Blank_Species_Text
 	$r .= '
<div id="cc-4" class="poll-section">
  <div id="cc-4-title" class="poll-section-title">'.lang::get('LANG_Photos').'
    <div id="cc-4-mod-button" class="right mod-button poll-button-1">'.lang::get('LANG_Modify').'</div>
  </div>
  <div id="cc-4-photo-reel" class="photoReelContainer" >
  </div>
  <div id="cc-4-body" class="poll-section-body">
    <div id="cc-4-insect">
      <div id="cc-4-insect-picture">
		<form id="cc-4-insect-upload" enctype="multipart/form-data" action="'.iform_ajaxproxy_url($node, 'media').'" method="POST">
    		<input type="hidden" id="website_id" name="website_id" value="'.$args['website_id'].'" />
    		<!-- Name of input element determines name in $_FILES array -->
    		<input name="upload_file" type="file" class="required" /><br />
    		<input type="submit" value="'.lang::get('LANG_Upload_Insect').'"/>
		</form>
 	    <div id="cc-4-insect-image">
 	    </div>
 	  </div>
 	  <div id="cc-4-insect-identify">
        <p>'.lang::get('LANG_Identify_Insect').'</p>
        <p>HELP : '.lang::get('LANG_Insect_Help').'</p>
        <p>'.lang::get('LANG_Dont_Know_Insect').'</p>
        <p>'.lang::get('LANG_Launch_ID_Tool').'</p>
        <p>'.lang::get('LANG_ID_Insect_Later').'</p>
      </div>
    </div>
 	<form id="cc-4-main-form" action="'.iform_ajaxproxy_url($node, 'occurrence').'" method="POST" >
    	<input type="hidden" id="website_id" name="website_id" value="'.$args['website_id'].'" />
    	<input type="hidden" id="occurrence_image:path" name="occurrence_image:path" value="" />
    	<input type="hidden" id="occurrence:record_status" name="occurrence:record_status" value="C" />
	    <input type="hidden" id="occurrence:id" name="occurrence:id" value="" disabled="disabled" />
    	<input type="hidden" id="occurrence_image:id" name="occurrence_image:id" value="" disabled="disabled" />
	    <label for="occurrence:sample_id">'.lang::get('LANG_Session').'</label>
	    <select id="occurrence:sample_id" name="occurrence:sample_id" value="" class="required" /></select><br />
	    '
 	.data_entry_helper::autocomplete($species_ctrl_args)
 	.data_entry_helper::textarea(array(
	        'label'=>lang::get('LANG_Comment'),
    	    'fieldname'=>'occurrence:comment'
	    ))
	.data_entry_helper::outputAttribute($occurrence_attributes[$args['number_attr_id']],
 			$defAttrOptions)
 	.data_entry_helper::outputAttribute($occurrence_attributes[$args['foraging_attr_id']],
 			$defAttrOptions).'
    </form>
    <div id="cc-4-valid-insect-button" class="right poll-button-1">'.lang::get('LANG_Validate_Insect').'</div><br />
  </div>
  <div id="cc-4-footer" class="poll-section-footer">
    <div id="cc-4-valid-photo-button" class="right poll-button-1">'.lang::get('LANG_Validate_Photos').'</div><br />
  </div>
</div>';

    data_entry_helper::$javascript .= "
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
			jQuery('form#cc-4-main-form > input[name=occurrence_image\\:path]').val(data.filename);
        	var img = new Image();
        	$(img)
        		.load(function () {
        			$(this).hide();
        			$('#cc-4-insect-image').removeClass('loading').append(this);
        			$(this).fadeIn();
			    })
			    .error(function () { // L3 TODO
			    })
			    .attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$upload_path)."med-'+data.filename)
			    .attr('width', 300).attr('height', 300);
			jQuery('#cc-4-insect-upload input[name=upload_file]').val('');
        } 
});

$('#cc-4-main-form').ajaxForm({ 
    dataType:  'json', 
    beforeSubmit:   function(data, obj, options){
    	var valid = true;
		if (!jQuery('form#cc-4-main-form > input').valid()) { valid = false; }
		if (!validateRadio('occAttr\\:".$args['number_attr_id']."', obj)) { valid = false; }
    	if(data[1].value == '' ){
			alert('".lang::get('LANG_Must_Provide_Insect_Picture')."');
			valid = false;
		}
		return valid;
	},
    success:   function(data){
       	if(data.success == 'multiple records' && data.outer_table == 'occurrence'){
       		// if the currently highlighted thumbnail is blank, add the new insect.
       		if(jQuery('.currentPhoto.blankPhoto').length > 0){
       			addToPhotoReel(data.outer_id);
       		} else {
				setPhoto(data.outer_id, jQuery('form#cc-4-main-form > input[name=occurrence_image\\:path]').val());
			}
       		setEmptyPhoto();
       		jQuery('#cc-4-main-form').resetForm();
       		jQuery('#cc-4-main-form').find('[name=occurrence_image\\:path],[name=occurrence\\:taxa_taxon_list_id\\:taxon]').val('');
			jQuery('#cc-4-main-form').find('[name=occurrence\\:id],[name=occurrence_image\\:id]').val('').attr('disabled', 'disabled');
       		jQuery('#cc-4-main-form').find('[name=occurrence_image\\:path]').val('');
			jQuery('#cc-4-main-form').find('[name^=occAttr\\:]').each(function(){
				var name = jQuery(this).attr('name').split(':');
				jQuery(this).attr('name', name[0]+':'+name[1]);
			});
       		
			jQuery('#cc-4-insect-image').empty();
        }
	}
});

validateInsectPanel = function(){
	if(jQuery('#cc-4:visible').length < 1)
		return true; // panel is not visible so no data to fail validation.
	if(jQuery('#cc-4-body:visible').length < 1)
		return true; // body hidden so data already been validated successfully.
	if(!validateInsect()){ return false; }
  	jQuery('#cc-4').foldPanel();
	return true;
};

setEmptyPhoto = function(){
	jQuery('.currentPhoto').removeClass('currentPhoto');
	if(jQuery('.blankPhoto').length == 0) {
		jQuery('<div/>').addClass('blankPhoto thumb currentPhoto').appendTo('#cc-4-photo-reel');
	} else {
		jQuery('.blankPhoto').addClass('currentPhoto');
	}
}

createPhotoReel = function(){
	jQuery('#cc-4-photo-reel').empty();
	setEmptyPhoto();
}

createPhotoReel();

addToPhotoReel = function(occId){
	// last photo in list is the blank empty one. Add to just before this.
	var container = jQuery('<div/>').addClass('thumb').insertBefore('.blankPhoto');
	$.getJSON(\"".$svcUrl."/data/occurrence_image\" +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&occurrence_id=\" + occId + \"&callback=?\", function(imageData) {
		if (imageData.length>0) {
			var img = new Image();
			$(img).attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$upload_path)."thumb-'+imageData[0].path)
			    .attr('width', 50).attr('height', 50).appendTo(container).click(function () {setInsect(this, imageData[0].occurrence_id)});
		}
	});
}

setInsect = function(context, id){
	// first close all the other panels, ensuring any data is saved.
	if(!validateCollectionPanel() || !validateStationPanel() || !validateSessionsPanel())
		return;
	if(jQuery('#cc-4-body:visible').length < 1)
		jQuery('div#cc-4').unFoldPanel();
	else
		if(!validateInsect()){ return false; }		
	jQuery('.currentPhoto').removeClass('currentPhoto');
	$(context).parent().addClass('currentPhoto');
	$.getJSON(\"".$svcUrl."/data/occurrence/\" + id +
          \"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&callback=?\", function(data) {
	    if (data.length>0) {
	        jQuery('form#cc-4-main-form > input[name=occurrence\\:id]').removeAttr('disabled').val(data[0].id);
	        jQuery('form#cc-4-main-form > [name=occurrence\\:sample_id]').find('[value='+data[0].sample_id+']').attr('checked', 'checked');
	        jQuery('form#cc-4-main-form > input[name=occurrence\\:taxa_taxon_list_id\\:taxon]').val(data[0].taxon);
       		jQuery('form#cc-4-main-form > input[name=occurrence\\:taxa_taxon_list_id]').val(data[0].taxa_taxon_list_id);
			jQuery('form#cc-4-main-form > textarea[name=occurrence\\:comment]').val(data[0].comment);
			loadAttributes('occurrence_attribute_value', 'occurrence_attribute_id', 'occurrence_id', 'occurrence\\:id', data[0].id, 'occAttr');
    		loadImage('occurrence_image', 'occurrence_id', 'occurrence\\:id', data[0].id, '#cc-4-insect-image');
  		}
	});
};
setNoInsect = function(context, id){
	// first close all the other panels, ensuring any data is saved.
	if(!validateCollectionPanel() || !validateStationPanel() || !validateSessionsPanel())
		return;
	if(jQuery('#cc-4-body:visible').length < 1)
		jQuery('div#cc-4').unFoldPanel();
	else
		if(!validateInsect()){ return false; }
	// At his point the empty panel is displayed.	
	jQuery('.currentPhoto').removeClass('currentPhoto');
	$(context).parent().addClass('currentPhoto');
};
jQuery('.blankPhoto').click(setNoInsect);

// TODO separate photoreel out into own js

// set the current thumbnail to specified filename.
setPhoto = function(occId, filename){
	// fetch the occurrence_image and set the image to the path
	// var filename = jQuery('#cc-4-main-form input[name=occurrence_image\\:path]').val();
    var img = new Image();
	var temp=jQuery('.currentPhoto').empty().removeClass('blankPhoto');
    $(img).attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$upload_path)."thumb-'+filename)
			    .attr('width', 50).attr('height', 50).appendTo(temp).click(function () {setInsect(this, occId)});
	// TODO fetch the occurrence determination: if indeterminate set div to include a question mark
	// TODO set a click event to populate the form dependant on occurrence_id.
}

validateInsect = function(){
	// TODO will have to expand when use key or when identify later.
	if(jQuery('form#cc-4-main-form > input[name=occurrence\\:id]').val() == '' &&
			jQuery('form#cc-4-main-form > input[name=occurrence_image\\:path]').val() == '' &&
			jQuery('form#cc-4-main-form > [name=occurrence\\:sample_id]').val() == '' &&
			jQuery('form#cc-4-main-form > input[name=occurrence\\:taxa_taxon_list_id]').val() == '' &&
			jQuery('form#cc-4-main-form > textarea[name=occurrence\\:comment]').val() == '' &&
			jQuery('[name=occAttr\\:".$args['number_attr_id']."],[name^=occAttr\\:".$args['number_attr_id']."\\:]').filter('[checked]').length == 0){
		return true;
	}
	var valid = true;
    if (!jQuery('form#cc-4-main-form > input').valid()) { return false; }
  	if (!validateRadio('occAttr\\:".$args['number_attr_id']."', 'form#cc-4-main-form')) { valid = false; }
	if(jQuery('form#cc-4-main-form input[name=occurrence_image\\:path]').val() == ''){
		alert('".lang::get('LANG_Must_Upload_Insect_Picture')."');
		valid = false;;
	}
	if(valid == false) return false;
	jQuery('form#cc-4-main-form').submit();
	return true;
  }

$('#cc-4-valid-insect-button').click(validateInsect);
$('#cc-4-valid-photo-button').click(function(){
	alert('TODO');
});

// Default state: fold everything except the collection details block.
$('.poll-section').hide();
$('#cc-1').showPanel();

loadAttributes = function(attributeTable, attributeKey, key, keyName, keyValue, prefix){
	$.getJSON(\"".$svcUrl."/data/\" + attributeTable +
   			\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
   			\"&\" + key + \"=\" + keyValue + \"&callback=?\", function(attrdata) {
		if (attrdata.length>0) {
			var form = jQuery('input[name='+keyName+'][value='+keyValue+']').parent();
			for (var i=0;i<attrdata.length;i++){
				if (attrdata[i].id){
					// TODO set value of radio buttons.
					// as this is run only at the start, the names are still vanilla.
					var radiobuttons = jQuery('[name='+prefix+'\\:'+attrdata[i][attributeKey]+']:radio', form);
					if(radiobuttons.length > 0){
						radiobuttons
							.attr('name', prefix+':'+attrdata[i][attributeKey]+':'+attrdata[i].id)
							.filter('[value='+attrdata[i].raw_value+']')
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
//			jQuery('[name='+imageTable+'\\:'+key+']', form).val(imageData[0][key]).removeAttr('disabled');
			jQuery('[name='+imageTable+'\\:path]', form).val(imageData[0].path);
			var img = new Image();
			$(img)
        		.load(function () {
        			$(target).append(this);
			    })
			    .error(function () { // L3 TODO
			    })
			    .attr('src', '".(data_entry_helper::$base_url).(data_entry_helper::$upload_path)."med-'+imageData[0].path)
			    .attr('width', 300).attr('height', 300);
		}
	});
}

// load in any existing incomplete collection.
// general philosophy is that you are taken back to the stage last verified.
// Load in the first if there are more than one. Use the internal report which provides my collections.
// Requires that there is an attribute for completeness, and one for the CMS
// load the data in the order it is entered, so can stop when get to the point where the user finished.
// •requestReport?report=poll_my_collections.xml&reportSource=local&mode=json
jQuery.getJSON(\"".$svcUrl."\" + \"/report/requestReport?report=poll_my_collections.xml&reportSource=local&mode=json\" +
			\"&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"]."\" + 
			\"&survey_id=".$args['survey_id']."&userID_attr_id=".$args['uid_attr_id']."&userID=".$uid."&complete_attr_id=".$args['complete_attr_id']."&callback=?\", function(data) {
	if (data.length>0) {
       	for (var i=0;i<data.length;i++) {
       		if(data[i].completed == '0'){
       			// load up collection details: existing ID, location name and TODO protocol
       			jQuery('#cc-1,#cc-2').find('input[name=sample\\:id]').val(data[i].id).removeAttr('disabled');
       			// TODO sample date?
       			loadAttributes('sample_attribute_value', 'sample_attribute_id', 'sample_id', 'sample\\:id', data[i].id, 'smpAttr');
       			// TODO - could probably do with a check to ensure location_id is filled in.
       			// TODO - set title details.
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
  					}
  				});
  				$.getJSON(\"".$svcUrl."/data/occurrence/\" +
          					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
          					\"&sample_id=\"+data[i].id+\"&callback=?\", function(flowerData) {
          			// there will only be an occurrence if the floral station panel has previously been displayed & validated. 
		    		if (flowerData.length>0) {
  						$('#cc-1').foldPanel();
  						$('#cc-2').showPanel();
		    			// TODO record status?
		    			jQuery('form#cc-2-floral-station > input[name=occurrence\\:sample_id]').val(data[i].id);
		    			jQuery('form#cc-2-floral-station > input[name=occurrence\\:id]').val(flowerData[0].id).removeAttr('disabled');
		    			jQuery('form#cc-2-floral-station > input[name=occurrence\\:taxa_taxon_list_id]').val(flowerData[0].taxa_taxon_list_id);
		    			jQuery('form#cc-2-floral-station > input[name=occurrence\\:taxa_taxon_list_id\\:taxon]').val(flowerData[0].taxon);
		    			loadAttributes('occurrence_attribute_value', 'occurrence_attribute_id', 'occurrence_id', 'occurrence\\:id', flowerData[0].id, 'occAttr');
    	   				loadImage('occurrence_image', 'occurrence_id', 'occurrence\\:id', flowerData[0].id, '#cc-2-flower-image');
	       				$.getJSON(\"".$svcUrl."/data/sample\" + 
    	      					\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."&parent_id=\"+data[i].id+\"&deleted=f&callback=?\", function(sessiondata) {
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
          									\"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
          									\"&sample_id=\"+sessiondata[i].id+\"&callback=?\", function(insectData) {
		    							if (insectData.length>0) {
 											$('#cc-3').foldPanel();
 											$('#cc-4').showPanel();
 											for (var j=0;j<insectData.length;j++){
												addToPhotoReel(insectData[j].id);
											}
										}
		    						});
								}
								populateSessionSelect();
 					  		}
						});
    	   			}
  				});
				// only use the first one which is not complete..
				break;
			}
		}
	}
});
  
  ";
//    if (!$('#".self::$validated_form_id." div > div:eq('+current+') input').valid()) {\n    return; \n}
//    $('#$divId').tabs('select', current+1);  
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