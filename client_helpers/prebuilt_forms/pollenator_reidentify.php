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

require_once('includes/language_utils.php');
require_once('includes/user.php');

class iform_pollenator_reidentify {

 /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $retVal = array_merge(
     array(
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
          'name'=>'search_insects_layer',
          'caption'=>'Name layer for the Insects Search',
          'description'=>'The Name of the Geoserver Layer used for the WFS feature lookup when searching Insects.',
          'type'=>'string',
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
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_pollenator_reidentify_definition() {
    return array(
      'title'=>self::get_title(),
      'category' => 'SPIPOLL forms',      
      'description'=>'Pollenators: Re-identifier of insects following taxa reorganisations.'
    );
  }

  /**
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'Pollenators: Re-identifier';
  }

/**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args, $node) {
	$r = '';
	drupal_add_js(drupal_get_path('module', 'iform') .'/media/js/jquery.form.js', 'module');
	data_entry_helper::link_default_stylesheet();
	data_entry_helper::add_resource('jquery_ui');
	data_entry_helper::add_resource('openlayers');
	data_entry_helper::enable_validation('new-comments-form'); // don't care about ID itself, just want resources
	data_entry_helper::add_resource('autocomplete');
	
	global $user;
    $uid = $user->uid;
    $email = $user->mail;
    $username = $user->name;
	// Get authorisation tokens to update and read from the Warehouse.
	$readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
	$svcUrl = data_entry_helper::$base_url.'/index.php/services';
	
	// note we have to proxy the post. Every time a write transaction is carried out, the write nonce is trashed.
	// For security reasons we don't want to give the user the ability to generate their own nonce, so we use
	// the fact that the user is logged in to drupal as the main authentication/authorisation/identification
	// process for the user. The proxy also packages the post into the correct format	

	// Two insect lists:
	// 1) list we are going to pick our old taxa from. This will only be those which data entry is no longer allowed.
	// 2) list of new taxa: This will only be those which data entry is allowed
    // the controls for the filter include all taxa, not just the ones allowed for data entry, just to be on the safe side.
	$source_insect_ctrl_args=array(
    	    'label'=>lang::get('Insect Species'),
        	'id'=>'insect-taxa-taxon-list-id',
			'fieldname'=>'insect:taxa_taxon_list_id',
	        'table'=>'taxa_taxon_list',
    	    'captionField'=>'taxon',
			'listCaptionSpecialChars'=>true,
        	'valueField'=>'id',
	        'columns'=>2,
    		'blankText'=>lang::get('Choose Taxon'),
    	    'extraParams'=>$readAuth + array('taxon_list_id' => $args['insect_list_id'], 'view'=>'detail','orderby'=>'taxonomic_sort_order','allow_data_entry'=>'f')
	);
	
 	$r .= '<h1 id="poll-banner"></h1>
<div id="refresh-message" style="display:none" ><p>'.lang::get('Please Refresh Page').'</p></div>
<div id="filter" class="ui-accordion ui-widget ui-helper-reset">
	<div id="filter-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-accordion-content-active ui-corner-top">
	  	<div id="results-collections-title">
	  		<span>'.lang::get('Filter').'</span>
    	</div>
	</div>
	<div id="filter-spec" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active">
	  <div class="ui-accordion ui-widget ui-helper-reset">
		<div id="insect-filter-header" class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all">
			<div id="insect-filter-title">
				<span>'.lang::get('Insect Filter').'</span>
			</div>
		</div>
		<div id="insect-filter-body" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
		  '.data_entry_helper::select($source_insect_ctrl_args).'
		  <label >'.lang::get('Status').':</label>
		  <span class="control-box "><nobr>
		    <span><input type="checkbox" value="X" id="insect_id_status:0" name="insect_id_status[]"><label for="insect_id_status:0">'.lang::get('Unidentified').'</label></span></nobr> &nbsp; <nobr>
		    <span><input type="checkbox" value="A" id="insect_id_status:1" name="insect_id_status[]"><label for="insect_id_status:1">'.lang::get('Initial').'</label></span></nobr> &nbsp; <nobr>
		    <span><input type="checkbox" value="B" id="insect_id_status:2" name="insect_id_status[]"><label for="insect_id_status:2">'.lang::get('Doubt').'</label></span></nobr> &nbsp; <nobr>
		    <span><input type="checkbox" value="C" id="insect_id_status:3" name="insect_id_status[]"><label for="insect_id_status:3">'.lang::get('Validated').'</label></span></nobr> &nbsp; 
		  </span>
		  <label >'.lang::get('Identification Type').':</label>
		  <span class="control-box "><nobr>
		    <span><input type="checkbox" value="seul" id="insect_id_type:0" name="insect_id_type[]"><label for="insect_id_type:0">'.lang::get('Single Taxon').'</label></span></nobr> &nbsp; <nobr>
		    <span><input type="checkbox" value="multi" id="insect_id_type:1" name="insect_id_type[]"><label for="insect_id_type:1">'.lang::get('Multiple Taxa').'</label></span></nobr> &nbsp; 
		  </span>
		</div>
	  </div>
	</div>
	<div id="filter-footer" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-bottom">
	  <div id="search-insects-button" class="ui-state-default ui-corner-all search-button">'.lang::get('Search Insects').'</div>
	</div>
	<div id="results-reassignment-taxon-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
	  <div id="results-reassignment-taxon-title">
	  	<span>'.lang::get('Actions To Be Taken').'</span>
      </div>
	</div>
    <div id="results-reassignment-taxon" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-botton">
		<label >'.lang::get('Single?').'</label><input type="checkbox" value="invalid" id="do-only-one" name="do-only-one"><br/>
		<label >'.lang::get('Becomes invalid?').'</label><input type="checkbox" value="invalid" id="becomes-invalid" name="becomes-invalid"><br/>
		<label>New Taxa : </label><table id="new-insect-id-list"><thead><tr><th>Species</th><th>ID</th><th>Remove</th></tr></thead><tbody id="new-insect-id-list-body" class="new-id-list-body"><tr id="insectAutocompleteRow1" class="autocompleteRow"><td>'.lang::get('Add').' <input name="insectAutocomplete1" id="insectAutocomplete1" /></td><td><input name="insect2" id="insect2" /></td><td></td></tr></tbody></table>
    <form id="bulk-reassignment-form" action="'.iform_ajaxproxy_url($node, 'determination').'" method="POST" >
		<input type="hidden" name="website_id" value="'.$args['website_id'].'" />
		<input type="hidden" name="determination:occurrence_id" value="" />
		<input type="hidden" name="determination:cms_ref" value="'.$uid.'" />  
		<input type="hidden" name="determination:person_name" value="'.$username.'" />  
		<input type="hidden" name="determination:email_address" value="'.$email.'" />
		<input type="hidden" name="determination:determination_type" value="C" />
		<input type="hidden" name="determination:taxon_details" value="" />
		<input type="hidden" name="determination:taxa_taxon_list_id" value="" />
		<label >Comment : </label><textarea name="determination:comment" class=\"taxon-comment\" rows="3" style=\"width: 480px;\" />'.lang::get('Réaffectation majeure partie des taxons').'</textarea>
		<input type="hidden" name="determination:taxon_extra_info" value="" />
	</form>
	  	<div id="reassign-button" class="ui-state-default ui-corner-all reassign-button">'.lang::get('Reassign Taxon').'</div>
		<div id="reassign-progress"></div>
		<div id="reassign-message"></div>
		<div id="last-updated"></div>
	  	<div id="cancel-reassign-taxon" class="ui-state-default ui-corner-all cancel-reassign-button">'.lang::get('Cancel').'</div>
	</div>
	<div id="results-insects-header" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top">
	  <div id="results-insects-title">
	  	<span>'.lang::get('Search Results').'</span>
      </div>
	</div>
	<div id="results-insects-results" class="ui-accordion-content ui-helper-reset ui-widget-content ui-accordion-content-active ui-corner-bottom">
    </div>
</div>
';

	$extraParams = $readAuth + array('taxon_list_id' => $args['insect_list_id'], 'view'=>'list', 'allow_data_entry'=>'t');
	$species_data_def=array('table'=>'taxa_taxon_list','extraParams'=>$extraParams);
	$taxa = data_entry_helper::get_population_data($species_data_def);
	data_entry_helper::$javascript .= "var insectTaxa = [";
	// No XPER ID here
	$taxa = data_entry_helper::get_population_data($species_data_def);
	$first = true;
	foreach ($taxa as $taxon) {
		data_entry_helper::$javascript .= ($first ? '' : ',').'{id: '.$taxon['id'].', taxon: "'.str_replace('"','\\"',$taxon['taxon']).'"}'."\n";
		$first=false;
	}
    data_entry_helper::$javascript .= "];
replacechar = function(match){
  switch(match) {
    case '<':  return '&lt;';
    case '>':  return '&gt;';
    case '\"': return '&quot;';
    case '\'': return '&#039;';
    case '&':  return '&amp;';
    default: return match;
  }
};
    
htmlspecialchars = function(value){ return value.replace(/[<>\"'&]/g, function(m){return replacechar(m)}) };
jQuery('#insect-taxa-taxon-list-id option').each(function(idx,elem){
  jQuery(elem).html(jQuery(elem).html()+' ('+jQuery(elem).val()+')');
});
    		
jQuery('input#insectAutocomplete1').autocomplete(insectTaxa,
      { matchContains: true,
        parse: function(data)
        {
          var results = [];
          jQuery.each(data, function(i, item) {
            results[results.length] = { 'data' : item, 'result' : item.id, 'value' : item.taxon };
          });
          return results;
        },
      formatItem: function(item) { return item.taxon; }
      // {max}
});
jQuery('input#insectAutocomplete1').result(function(event, data) {
  jQuery('input#insectAutocomplete1').val('');
  jQuery('input#insect2').val('');
  if(jQuery('#new-insect-id-list-body input').filter('[value='+data.id+']').length == 0)
    jQuery('#new-insect-id-list-body').find('.autocompleteRow').before('<tr class=\"new-id-list-entry\"><td>'+htmlspecialchars(data.taxon)+'</td><td><input type=\"hidden\" name=\"taxa_taxon_list_id_list\" value=\"'+data.id+'\"\>'+data.id+'</td><td><img class=\"removeRow\" src=\"/misc/watchdog-error.png\" alt=\"".lang::get('Remove this entry')."\" title=\"".lang::get('Remove this entry')."\"/></td></tr>');
  else
    alert('".lang::get('The chosen taxon is already in the replacement list.')."');
});
jQuery('input#insect2').change(function() {
  jQuery('input#insectAutocomplete1').val('');
  var value = jQuery('input#insect2').val();
  jQuery('input#insect2').val('');
  if(jQuery('#new-insect-id-list-body input').filter('[value='+value+']').length == 0)
    for(var i=0; i<insectTaxa.length; i++){
      if(value == insectTaxa[i].id){
        jQuery('#new-insect-id-list-body').find('.autocompleteRow').before('<tr class=\"new-id-list-entry\"><td>'+htmlspecialchars(insectTaxa[i].taxon)+'</td><td><input type=\"hidden\" name=\"taxa_taxon_list_id_list\" value=\"'+value+'\"\>'+value+'</td><td><img class=\"removeRow\" src=\"/misc/watchdog-error.png\" alt=\"".lang::get('Remove this entry')."\" title=\"".lang::get('Remove this entry')."\"/></td></tr>');
        break;
      }
    }
  else
    alert('".lang::get('The chosen taxon is already in the replacement list.')."');
});
jQuery('.removeRow').live('click', function (){ jQuery(this).closest('tr').remove(); });
bulkAssigning=false;
bulkCancel=false;
jQuery('#reassign-progress').progressbar({value: 0});
jQuery('form#bulk-reassignment-form').ajaxForm({
	dataType:  'json', 
	beforeSubmit:   function(data, obj, options){
		if(bulkCancel){
			bulkReassignFinish(\"".lang::get('Bulk Reassignment Canceled')."\");
			return false;
		}	
		return true;
	},
	success:   function(data){
		if(data.error == undefined){
			var form = jQuery('form#bulk-reassignment-form');
			var dateObj = new Date();
	        var timeToday = dateObj.getHours() + ':' + dateObj.getMinutes() + ':' + dateObj.getSeconds();
			jQuery('.results-insects-record-'+form.find('[name=determination\\:occurrence_id]').val()).html(\"".lang::get('Processed at ')."\"+timeToday);
			jQuery('#last-updated').empty().append(\"".lang::get('Last update at ')."\"+timeToday);
			if(jQuery('#do-only-one').filter(':checked').length>0)
				bulkReassignFinish(\"".lang::get('Bulk Reassignment Completed')."\");
			else
				uploadReassignment();
		} else {
			alert(data.error);
			bulkReassignFinish(\"".lang::get('Bulk Reassignment Error')."\");
  		}
	} 
});
// Done: Convert single taxon
// Done: allow filtering by single or multi taxa.
// Done: Convert Multi taxa
// Done: apply re-validation rule.
// Done: allow single to multi explode.
// Done: remove single at a time restriction.
// Done: ensure no taxon duplication
// Done: add value to end of taxon select options
// Done: add second autocomplete to allow addition of row using id
// Done: add timetag to processed text
// TODO: Add counter to results list.
uploadReassignment = function(){
	var occID = false;
	var max = jQuery('#reassign-progress').data('max');
	var index = jQuery('#reassign-progress').data('index');
	jQuery('#reassign-progress').data('index',index+1);
	jQuery('#reassign-progress').progressbar('option','value',index*100/max);
	if(index<max){
		occID=searchResults.features[index].attributes.insect_id;
		jQuery('#reassign-message').html('<span>'+index+'/'+max+' : '+Math.round(index*100/max)+'%</span>');
	}
	if(occID && !bulkCancel){
		$.getJSON(\"".$svcUrl."/data/determination\" + 
				\"?mode=json&view=list&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" + 
				\"&reset_timeout=true&occurrence_id=\" + occID + \"&deleted=f&orderby=id&sortdir=DESC&REMOVEABLEJSONP&callback=?\", function(detData) {
			if(!(detData instanceof Array)){
   				alertIndiciaError(detData);
			} else if (detData.length>0) {
				// only dealing with latest. no determination id as generating new record.
				// all reidentified taxon will have either a unidentified or Valid flag, so will not appear in this list, so doesn't matter if the taxon has changed.
				var form = jQuery('form#bulk-reassignment-form');
				form.find('[name=determination\\:taxa_taxon_list_id_list\\[\\]]').remove();
				form.find('[name=determination\\:occurrence_id]').val(detData[0].occurrence_id);
				if(detData[0].taxa_taxon_list_id == jQuery('[name=insect\\:taxa_taxon_list_id]').val()) { // double check matches chosen taxa.
					if(jQuery('.new-id-list-entry').length == 1) // single->single replacement
						form.find('[name=determination\\:taxa_taxon_list_id]').val(jQuery('.new-id-list-entry input').val()); // only one
					else { // single->multiple replacement
						form.find('[name=determination\\:taxa_taxon_list_id]').val('');
						jQuery('.new-id-list-entry input').each(
							function(idx, elem){ // do not need to check for duplication here.
								jQuery('form#bulk-reassignment-form').append('<input type=\"hidden\" name=\"determination:taxa_taxon_list_id_list[]\" value=\"'+jQuery(elem).val()+'\" >');
							});
					}
				} else
					form.find('[name=determination\\:taxa_taxon_list_id]').val(detData[0].taxa_taxon_list_id);
				form.find('[name=determination\\:taxon_details]').val(detData[0].taxon_details);
				form.find('[name=determination\\:taxon_extra_info]').val(detData[0].taxon_extra_info == null ? '' : detData[0].taxon_extra_info);
				if(jQuery('#becomes-invalid').filter(':checked').length>0 && detData[0].determination_type=='C')
					form.find('[name=determination\\:determination_type]').val('A');
				else
					form.find('[name=determination\\:determination_type]').val(detData[0].determination_type);
				var decoded = (detData[0].taxa_taxon_list_id_list != null && detData[0].taxa_taxon_list_id_list != '' && detData[0].taxa_taxon_list_id_list != '{}') ? JSON.parse(detData[0].taxa_taxon_list_id_list.replace('{','[').replace('}',']')) : [];
				if(decoded.length>0)
					for(var j=0; j < decoded.length; j++) {
						if(decoded[j] == jQuery('[name=insect\\:taxa_taxon_list_id]').val())
							jQuery('.new-id-list-entry input').each(
								function(idx, elem){
									if(jQuery('form#bulk-reassignment-form [name=determination\\:taxa_taxon_list_id_list\\[\\]]').filter('[value='+jQuery(elem).val()+']').length == 0)
										jQuery('form#bulk-reassignment-form').append('<input type=\"hidden\" name=\"determination:taxa_taxon_list_id_list[]\" value=\"'+jQuery(elem).val()+'\" >');
								});
						else
							form.append('<input type=\"hidden\" name=\"determination:taxa_taxon_list_id_list[]\" value=\"'+decoded[j]+'\" >');
					}
				jQuery('form#bulk-reassignment-form').submit();
			}});
	} else {
		bulkReassignFinish(bulkCancel ? \"".lang::get('Bulk Reassignment Canceled')."\" : \"".lang::get('Bulk Reassignment Completed')."\");
	}
}
bulkReassignPrep=function(max){
	bulkAssigning=true; //switches off searches etc.
	bulkCancel=false;
	jQuery('#reassign-button').addClass('loading-button');
	jQuery('#reassign-progress,#cancel-reassign-taxon').show();
	jQuery('#reassign-message').empty();
	jQuery('#search-insects-button,#reassign-button').attr('disabled','disabled');
	jQuery('#reassign-message').html('<span>0/'+max+' : 0%</span>');
	jQuery('#reassign-progress').data('max',max).data('index',0).progressbar('option','value',0);
}
bulkReassignFinish=function(message){
	bulkCancel=false;
	bulkAssigning=false;
	jQuery('#reassign-button').removeClass('loading-button');
	jQuery('#reassign-progress,#cancel-reassign-taxon').hide();
	jQuery('#reassign-message').empty();
	if(message) jQuery('#reassign-message').html('<span>'+message+'</span>');
	jQuery('#search-insects-button,#reassign-button').removeAttr('disabled');
}
bulkReassignFinish(false);
jQuery('.cancel-reassign-button').click(function(){bulkCancel=true;});
jQuery('#reassign-button').click(function(){
	var max=0;
	if(searchResults!= null) max=searchResults.features.length;
	bulkReassignPrep(max);
	if(jQuery('.new-id-list-entry').length==0) {
		bulkReassignFinish(\"".lang::get('No replacement taxa defined.')."\");
	} else if(max==0){
		bulkReassignFinish(\"".lang::get('No identifications listed: nothing to do.')."\");
	} else if(!confirm(\"".lang::get('Are you sure you wish to carry out this bulk reassignment?')."\")){
		bulkReassignFinish(false);
		return;
	} else {
		uploadReassignment();
	}
});

jQuery('#search-insects-button').click(function(){
	if(bulkAssigning) return; //prevent results changing underneath bulk reassignment
	jQuery('#results-insects-header').addClass('ui-state-active').removeClass('ui-state-default');
	runSearch();
});

function pad(number, length) {
    var str = '' + number;
    while (str.length < length) {
        str = '0' + str;
    }
    return str;
}
runSearch = function(){
	var combineOR = function(ORgroup){ return (ORgroup.length > 1 ? new OpenLayers.Filter.Logical({type: OpenLayers.Filter.Logical.OR, filters: ORgroup}) : ORgroup[0]);};

	if(bulkAssigning) return; //prevent query changing underneath bulk reassignment
  	var ORgroup = [];
    jQuery('#results-insects-results,#reassign-message').empty();
	jQuery('#reassign-progress,#cancel-reassign-taxon').hide();
	jQuery('#results-reassign-taxon,#results-reassign-outer').hide();
	var filters = [];

  	// filters.push(new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.EQUAL_TO, property: 'survey_id', value: '".$args['survey_id']."' }));
 			
	var insect = jQuery('select[name=insect\\:taxa_taxon_list_id]').val();
	if(insect == '') return;
	var insect_taxon_filter = new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.LIKE, property: 'insect_taxon_ids', value: '*|'+insect+'|*'});
	var insect_statuses = jQuery('[name=insect_id_status\\[\\]]').filter('[checked]');
  	var insect_taxon_types = jQuery('[name=insect_id_type\\[\\]]').filter('[checked]');
	filters.push(insect_taxon_filter);
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
	
  	feature = '".$args['search_insects_layer']."';
  	properties = ['insect_id','collection_id','geom'];
  	
	searchResults = null;  
	var protocol = new OpenLayers.Protocol.WFS({
              url: '".str_replace("{HOST}", $_SERVER['HTTP_HOST'], $args['search_url'])."',
              featurePrefix: '".$args['search_prefix']."',
              featureType: feature,
              geometryName:'geom',
              featureNS: '".$args['search_ns']."',
              srsName: 'EPSG:900913',
              version: '1.1.0',   
              maxFeatures: ".$args['max_features'].",
              propertyNames: properties,
              callback: function(a1){
                jQuery('#results-insects-results').empty();
                if(a1.error && (typeof a1.error.success == 'undefined' || a1.error.success == false)){
                  alert(\"".lang::get('Insect search failed')."\");
                  return;
                }
                if(a1.features.length > 0) {
                  jQuery('#results-insects-results').append('<p>".lang::get('Number returned')." : '+a1.features.length+'</p><table><thead><tr><th>#</th><th>".lang::get('Collection')."</th><th>".lang::get('ID')."</th><th>".lang::get('Status')."</th></tr></thead><tbody id=\"results-insects-table\"/></table>');
                  for(var i=0; i<a1.features.length; i++){
                    jQuery('#results-insects-table').append('<tr><td>'+(i+1)+'&nbsp; </td><td>'+a1.features[i].data.collection_id+'</td><td>'+a1.features[i].data.insect_id+'</td><td class=\"results-insects-record-'+a1.features[i].data.insect_id+'\">".lang::get('Unprocessed')."</td></tr>');
                  }
                  searchResults = a1;
                } else
                  jQuery('#results-insects-results').append('<p>".lang::get('No species records returned')."</p>');
              }
		  });
    jQuery('#results-insects-results').empty().append('<div class=\"insect-loading-panel\" ><img src=\"".$base.drupal_get_path('module', 'iform')."/media/images/ajax-loader2.gif\" />".lang::get('Loading')."...</div>');
    protocol.read({filter: new OpenLayers.Filter.Logical({type: OpenLayers.Filter.Logical.AND, filters: filters})});
};

searchResults = null;  
collection = '';
";

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
