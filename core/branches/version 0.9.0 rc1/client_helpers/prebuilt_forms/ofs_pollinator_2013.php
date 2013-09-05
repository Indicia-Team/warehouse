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
 * @package    Client
 * @subpackage PrebuiltForms
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Prebuilt Indicia data entry form.
 * NB has Drupal specific code. Relies on presence of IForm loctools and IForm Proxy.
 *
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('dynamic_sample_occurrence.php');

class iform_ofs_pollinator_2013 extends iform_dynamic_sample_occurrence {
  
  /**
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_ofs_pollinator_2013_definition() {
    return array(
      'title'=>'UK Farm Pollination 2013',
      'category' => 'Specific Surveys',
      'description'=>'TODO.'
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $parentVal = parent::get_parameters();
    $retVal=array();
    foreach($parentVal as $param){
      if($param['name'] == 'structure')
        $param['default'] = "=About the visit=\r\n".
            "?Before inputting your data, please tell us who you are, confirm the date of the visit and which farm you visited.?\r\n".
            "[recorder names]\r\n".
            "@validation=required\r\n".
            "[date]\r\n".
            "@default=09/06/2013\r\n".
            "@helpText=Click in this box to select the date if it was not Open Farm Sunday.\r\n".
            "[place search]\r\n".
            "@fieldname=sample:location_name\r\n".
            "@validation=required\r\n".
            "@helpText=Type in the farm name or grid reference to search for\r\n".
            "[farm]\r\n".
            "[spatial reference]\r\n".
            "@helpText=If you know the exact grid reference then please type it in here, or you can click on the map where you did the survey to set your exact location.\r\n".
            "[map]\r\n".
            "[*]\r\n".
            "=Crop Habitat=\r\n".
            "[*]\r\n".
            "=Tallies=\r\n".
            "[species]\r\n".
            "[species attributes]\r\n".
            "[sample comment]\r\n".
            "[*]\r\n".
            "=*=\r\n";
      if($param['name'] != 'remembered' && 
            $param['name'] != 'extra_list_id')
        $retVal[] = $param;
    }
    return $retVal;
  }


  protected static function get_control_farm($auth, $args, $tabAlias, $options) {
    data_entry_helper::$javascript .= "mapGeoreferenceHooks.push(function(div, ref, corner1, corner2, epsgCode, name, obj) {
  var other;
  if (typeof(obj.comment) == 'undefined' || obj.comment===null) {
    other='Unknown address';
  } else {
    other = obj.comment.replace(/\\r\\n/g, '<br/>');
    other = other.replace(/\\r/g, '<br/>');
    other = other.replace(/\\n/g,'<br/>');
  }
  $('#selected-farm').html('<strong>'+name+'</strong><br/>Address:<br/>'+other);
  $('#imp-sref').val(obj.centroid_sref);
  $('#imp-geom').val(obj.centroid_geom);
  $('#sample_location_id').val(obj.id);
  $('#selected-farm').fadeIn('fast');
  setTimeout(\"alert('Please check that the farm shown on the map is the correct farm before proceeding. If you know \"+
  	\"exactly where on the farm you did the survey then you can click on the map to set a more precise grid reference or \"+
  	\"you can type the reference into the Grid ref input box. \"+
  	\"Once the grid reference has been set, click the Next Step button at the bottom right of the page.');\");
});
// move required astericks inside span if present
$('.control-box').each(function(idx,elem){
  var next = $(elem).next('.deh-required');
  if (next.length>0)
    $(elem).append(next);
});";
  	 
  	return '<div id="selected-farm" ></div><input type="hidden" name="sample:location_id" id="sample_location_id" />';
  	 
  }
  /**
   * Get the control for species input, either a grid or a single species input control.
   */
  protected static function get_control_species($auth, $args, $tabAlias, $options) {
    global $user;
    $extraParams = $auth['read'];
    $extraParams['preferred'] = "true";
    // Build the configuration options
    if (isset($options['view']))
      $extraParams['view'] = $options['view'];    
    // There may be options in the form occAttr:n|param => value targetted at specific attributes
    $occAttrOptions = array();
    // make sure that if extraParams is specified as a config option, it does not replace the essential stuff
    if (isset($options['extraParams']))
      $options['extraParams'] = array_merge($extraParams, $options['extraParams']);
    $species_ctrl_opts=array_merge(array(
        'occAttrOptions' => $occAttrOptions,
        'listId' => $args['list_id'],
        'label' => lang::get('occurrence:taxa_taxon_list_id'),
        'columns' => 1,
        'extraParams' => $extraParams,
        'survey_id' => $args['survey_id'],
        'language' => iform_lang_iso_639_2(hostsite_get_user_field('language')) // used for termlists in attributes
    ), $options);
    if ($groups=hostsite_get_user_field('taxon_groups')) {
      $species_ctrl_opts['usersPreferredGroups'] = unserialize($groups);
    }
    if (isset($args['col_widths']) && $args['col_widths']) $species_ctrl_opts['colWidths']=explode(',', $args['col_widths']);
    call_user_func(array(self::$called_class, 'build_grid_taxon_label_function'), $args, $options);
    if (self::$mode == self::MODE_CLONE)
      $species_ctrl_opts['useLoadedExistingRecords'] = true;
    // Start by outputting a hidden value that tells us we are using a grid when the data is posted,
    // then output the grid control
    return '<input type="hidden" value="true" name="gridmode" />'.
        self::my_species_checklist($species_ctrl_opts);
  }

  public static function my_species_checklist($options)
  {
    global $indicia_templates;
    $base = base_path();
    if(substr($base, -1)!='/') $base.='/';
    $indicia_templates['taxon_label'] = '{taxon}<br/><img src="'.$base.drupal_get_path('module', 'iform').'/client_helpers/prebuilt_forms/images/ofs_pollinator/{taxonComp}.png" alt="[{taxon} Image]">';
  	 
    // load taxon list
    // load attributes.
    $options = data_entry_helper::get_species_checklist_options($options);
    //make a copy of the options so that we can maipulate it
    $overrideOptions = $options;
    $occAttrControls = array();
    $occAttrs = array();
    $taxonRows = array();
    // at this stage no preloading: no editing of existing data.
    // load the full list of species for the grid, including the main checklist plus any additional species in the reloaded occurrences.
    $taxalist = self::get_species_checklist_taxa_list($options, $taxonRows);
    // If we managed to read the species list data we can proceed
    if (! array_key_exists('error', $taxalist)) {
      $attrOptions = array(
  				'id' => null
  				,'valuetable'=>'occurrence_attribute_value'
  				,'attrtable'=>'occurrence_attribute'
  				,'key'=>'occurrence_id'
  				,'fieldprefix'=>"sc:-idx-::occAttr"
  				,'extraParams'=>$options['readAuth']
  				,'survey_id'=>array_key_exists('survey_id', $options) ? $options['survey_id'] : null
      );
      $attributes = data_entry_helper::getAttributes($attrOptions);
      // Get the attribute and control information required to build the custom occurrence attribute columns
      data_entry_helper::species_checklist_prepare_attributes($options, $attributes, $occAttrControls, $occAttrs);
      $grid = "\n";
      // No look up list -> no cloneable row
      $grid .= '<table class="ui-widget ui-widget-content species-grid '.$options['class'].'" id="'.$options['id'].'">';
      $visibleColIdx = 0;
      $grid .= "<thead class=\"ui-widget-header\"><tr><th>Wings</th><th>Other Features</th>";
      for ($i=0; $i<$options['columns']; $i++) {
        $grid .= self::get_species_checklist_col_header($options['id']."-species-$i", lang::get('species_checklist.species'), $visibleColIdx, $options['colWidths'], '', '');
        $grid .= self::get_species_checklist_col_header($options['id']."-present-$i", lang::get('species_checklist.present'), $visibleColIdx, $options['colWidths'], 'display:none', '');
        foreach ($occAttrs as $idx=>$a){
          $filename = preg_replace('/\s+/', '-', strtolower($a));
          $grid .= self::get_species_checklist_col_header($options['id']."-attr$idx-$i", lang::get($a), $visibleColIdx, $options['colWidths'], '',
                   $base.drupal_get_path('module', 'iform').'/client_helpers/prebuilt_forms/images/ofs_pollinator/'.$filename.'.png') ;
        }
      }
      $grid .= '</tr></thead>';
      $rows = array();
      $taxonCounter = array();
      $rowIdx = 0;
      $grid .= "\n<tbody>\n";
      if(count($taxonRows))
        $grid .= '<tr class="top"><td rowspan="2" class="dot-right"><b>No obvious wings</b></td><td>Antennae short</td>'.
          self::dump_one_row(0, $taxonRows[0], $taxalist, $taxonRows, $occAttrControls, $attributes, $options).'</tr>';
      if(count($taxonRows)>1)
        $grid .= '<tr class="dot-top"><td>Antennae varying lengths</td>'.
          self::dump_one_row(1, $taxonRows[1], $taxalist, $taxonRows, $occAttrControls, $attributes, $options).'</tr>';
      if(count($taxonRows)>2)
        $grid .= '<tr class="top"><td rowspan="2" class="dot-right"><b>One pair of wings</b><br/>One pair of wings, usually clear<br />Wings, held out from or held along the body</td><td rowspan="2" class="scOtherFeaturesCell" >Antennae usually short<br/><img src="'.$base.drupal_get_path('module', 'iform').'/client_helpers/prebuilt_forms/images/ofs_pollinator/short-antennae.png" alt=""></td>'.
          self::dump_one_row(2, $taxonRows[2], $taxalist, $taxonRows, $occAttrControls, $attributes, $options).'</tr>';
      if(count($taxonRows)>3)
        $grid .= '<tr class="dot-top">'.
          self::dump_one_row(3, $taxonRows[3], $taxalist, $taxonRows, $occAttrControls, $attributes, $options).'</tr>';
      if(count($taxonRows)>4)
        $grid .= '<tr class="top"><td class="dot-right"><b>Two pairs of wings</b><br/>Two pairs of wings, coloured</td><td>Antennae usually long</td>'.
          self::dump_one_row(4, $taxonRows[4], $taxalist, $taxonRows, $occAttrControls, $attributes, $options).'</tr>';
      if(count($taxonRows)>5)
        $grid .= '<tr class="dot-top"><td rowspan="2" class="dot-right">Two pairs of wings, usually clear<br/>Wings held out from or held along body</td><td rowspan="2" class="scOtherFeaturesCell" >Antennae usually long<br/><img src="'.$base.drupal_get_path('module', 'iform').'/client_helpers/prebuilt_forms/images/ofs_pollinator/long-antennae.png" alt=""></td>'.
          self::dump_one_row(5, $taxonRows[5], $taxalist, $taxonRows, $occAttrControls, $attributes, $options).'</tr>';
      if(count($taxonRows)>6)
        $grid .= '<tr class="dot-top">'.
          self::dump_one_row(6, $taxonRows[6], $taxalist, $taxonRows, $occAttrControls, $attributes, $options).'</tr>';
      $txnID=7;
      if(count($taxonRows)>$txnID){
        $grid .= '<tr class="top"><td class="dot-right"><b>?</b></td><td></td>'.
                 '<td class="scTaxonCell">Unknown Other?</td>'.
                 '<td style="display:none" class="scPresenceCell"><input type="hidden" value="'.$taxonRows[$txnID]["ttlId"].'" id="sc:'.$options['id'].'-'.$txnID.'::present" name="sc:'.$options['id'].'-'.$txnID.'::present"></td>'.
                 '<td class="scOccAttrCell ui-widget-content scComment" colspan="'.count($attributes).'"><input type="text" value="" name="sc:'.$options['id'].'-'.$txnID.'::comment" id="sc:'.$options['id'].'-'.$txnID.'::comment"></td></tr>';
      }
      $grid .= "</tbody>\n</table>\n";
      $grid .= '<input name="rowInclusionCheck" value="hasData" type="hidden" />';
      $r .= $grid;
      return $r;
    } else {
      return $taxalist['error'];
    }
  }
  
  private static function get_species_checklist_col_header($id, $caption, &$colIdx, $colWidths, $styles='', $img='') {
  	if ($styles != 'display:none') {
  		$colIdx++;
  	    $styles .= count($colWidths)>$colIdx && $colWidths[$colIdx] ? ' width: '.$colWidths[$colIdx].'%;"' : '';
  	    $styles .= $img!='' ? ' background-image:url(\''.$img.'\'); background-size: cover; height: 80px; text-align: center;' : '';
  	    $spanStyle = $img!='' ? ' style="background-color: white;"' : '';
  	}
  	return "<th id=\"$id\" style=\"$styles\"><span $spanStyle>".$caption."</span></th>";
  }
  
  private function dump_one_row($txIdx, $rowIds, $taxalist, $taxonRows, $occAttrControls, $attributes, $options)
  {
    global $indicia_templates;
  	$ttlId = $rowIds['ttlId'];
  	$colIdx = (int)floor($rowIdx / count($taxonRows));
  	// Find the taxon in our preloaded list data that we want to output for this row
  	$taxonIdx = 0;
  	while ($taxonIdx < count($taxalist) && $taxalist[$taxonIdx]['id'] != $ttlId) {
  		$taxonIdx += 1;
  	}
  	if ($taxonIdx >= count($taxalist))
  		return ''; // next taxon, as this one was not found in the list
  	$taxon = $taxalist[$taxonIdx];
  	$firstColumnTaxon=$taxon;
  	// map field names if using a cached lookup
  	if ($options['cacheLookup'])
  		$firstColumnTaxon = $firstColumnTaxon + array(
  				'preferred_name' => $firstColumnTaxon['preferred_taxon'],
  				'common' => $firstColumnTaxon['default_common_name']
  		);
  	$firstColumnTaxon['taxonComp'] = preg_replace('/\s+|\//', '-', strtolower($firstColumnTaxon['taxon']));
  	// Get the cell content from the taxon_label template
  	$firstCell = data_entry_helper::mergeParamsIntoTemplate($firstColumnTaxon, 'taxon_label');
  	// Now create the table cell to contain this.
  	$row = '';
  	$row .= str_replace(array('{content}','{colspan}','{tableId}','{idx}'),
  			array($firstCell,'',$options['id'],$colIdx), $indicia_templates['taxon_label_cell']);
  	$row .= "\n<td class=\"scPresenceCell\" headers=\"$options[id]-present-$colIdx\" style=\"display:none\">";
  	$fieldname = "sc:$options[id]-$txIdx:$existing_record_id:present";
  	$row .= "<input type=\"hidden\" name=\"$fieldname\" id=\"$fieldname\" value=\"$taxon[id]\"/>";
  	$row .= "</td>";
  	$idx = 0;
  	foreach ($occAttrControls as $attrId => $control) {
  		$existing_value='';
  		$valId=false;
  		// no existing record, so use a default control ID which excludes the existing record ID.
  		$ctrlId = str_replace('-idx-', "$options[id]-$txIdx", $attributes[$attrId]['fieldname']);
  		$loadedCtrlFieldName='-';
  	
  		if ($existing_value==='' && array_key_exists('default', $attributes[$attrId]))
  			// this case happens when reloading an existing record
  			$existing_value = $attributes[$attrId]['default'];
  		// inject the field name into the control HTML
  		$oc = str_replace('{fieldname}', $ctrlId, $control);
  		if ($existing_value<>"") {
  			// For select controls, specify which option is selected from the existing value
  			if (substr($oc, 0, 7)=='<select') {
  				$oc = str_replace('value="'.$existing_value.'"',
  						'value="'.$existing_value.'" selected="selected"', $oc);
  			} else if(strpos($oc, 'checkbox') !== false) {
  				if($existing_value=="1")
  					$oc = str_replace('type="checkbox"', 'type="checkbox" checked="checked"', $oc);
  			} else {
  				$oc = str_replace('value=""', 'value="'.$existing_value.'"', $oc);
  			}
  		}
  		$errorField = "occAttr:$attrId" . ($valId ? ":$valId" : '');
  		$error = data_entry_helper::check_errors($errorField);
  		if ($error) {
  			$oc = str_replace("class='", "class='ui-state-error ", $oc);
  			$oc .= $error;
  		}
  		$headers = $options['id']."-attr$attrId-$colIdx";
  		$class = self::species_checklist_occ_attr_class($options, $idx, $attributes[$attrId]['untranslatedCaption']);
  		$class = $class . 'Cell';
  		$row .= str_replace(array('{label}', '{class}', '{content}', '{headers}'), array(lang::get($attributes[$attrId]['caption']), $class, $oc, $headers),
  				$indicia_templates[$options['attrCellTemplate']]);
  		$idx++;
  	}
  	return $row;
  }

  private static function species_checklist_implode_rows($rows, $imageRowIdxs) {
  	$r = '';
  	foreach ($rows as $idx => $row) {
  		$r .= "<tr>$row</tr>\n";
  	}
  	return $r;
  }
  
  /**
   * Returns the class to apply to a control for an occurrence attribute, identified by an index.
   * @access private
   */
  private static function species_checklist_occ_attr_class($options, $idx, $caption) {
  	return (array_key_exists('occAttrClasses', $options) && $idx<count($options['occAttrClasses'])) ?
  	$options['occAttrClasses'][$idx] :
  	'sc'.str_replace(' ', '', ucWords($caption)); // provide a default class based on the control caption
  }
  
  private static function get_species_checklist_taxa_list($options, &$taxonRows) {
    // load the species names that should be initially included in the grid
    $options['extraParams']['orderby'] = 'taxonomic_sort_order';
    $taxalist = data_entry_helper::get_population_data($options);
  	foreach ($taxalist as $taxon) {
  		// create a list of the rows we are going to add to the grid, with the preloaded species names linked to them
  		$taxonRows[] = array('ttlId'=>$taxon['id']);
  	}
  	return $taxalist;
  }
  
  /**
   * Retrieves a list of the css files that this form requires in addition to the standard
   * Drupal, theme or Indicia ones.
   *
   * @return array List of css files to include for this form.
   */
  public static function get_css() {
    return array('ofs_pollinator_2013.css');
  }

  /**
   * Returns true if this form should be displaying a multiple occurrence entry grid.
   */
  protected static function getGridMode($args) {
    // if loading an existing sample and we are allowed to display a grid or single species selector
    if (isset($args['multiple_occurrence_mode']) && $args['multiple_occurrence_mode']=='either') {
      // Either we are in grid mode because we were instructed to externally, or because the form is reloading
      // after a validation failure with a hidden input indicating grid mode.
      return isset($_GET['gridmode']) ||
          isset(data_entry_helper::$entity_to_load['gridmode']) ||
          ((array_key_exists('sample_id', $_GET) && $_GET['sample_id']!='{sample_id}') &&
           (!array_key_exists('occurrence_id', $_GET) || $_GET['occurrence_id']=='{occurrence_id}'));
    } else
      return
          // a form saved using a previous version might not have this setting, so default to grid mode=true
          (!isset($args['multiple_occurrence_mode'])) ||
          // Are we fixed in grid mode?
          $args['multiple_occurrence_mode']=='multi';
  }

  
}
  

