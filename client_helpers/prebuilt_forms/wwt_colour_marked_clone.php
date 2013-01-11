<?php
/**
 * Indicia, the OPAL Online Recording Toolkit.
 * extends iform_dynamic_sample_occurrence
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
 * Prebuilt Indicia data entry form for WWT Colour-marked wildfowl.
 * NB has Drupal specific code. Relies on presence of IForm loctools and IForm Proxy.
 * NB relies on the individuals and associations optional module being enabled in the warehouse.
 * 
 * @package    Client
 * @subpackage PrebuiltForms
 * @link http://code.google.com/p/indicia/wiki/PrebuiltFormWWTColourMarkedRecords
 */

require_once('dynamic_sample_occurrence.php');
require_once('includes/map.php');
require_once('includes/language_utils.php');
require_once('includes/form_generation.php');

class iform_wwt_colour_marked_clone  extends iform_dynamic_sample_occurrence{

  // A list of the subject observation ids we are loading if editing existing data
  protected static $subjectObservationIds = array();
  protected static $loadedSubjectObservationId;
  protected static $loadedSampleId;
  protected static $auth = array();
  protected static $mode;
  protected static $node;
  // The class called by iform.module which may be a subclass of iform_location_dynamic



  
  protected static $submission = array();
  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */

  public static function get_wwt_colour_marked_clone_definition() {
    return array(
      'title'=>'RFJ WWT Colour-marked Wildfowl form',
      'category' => 'General Purpose Data Entry Forms',
      'helpLink'=>'http://code.google.com/p/indicia/wiki/PrebuiltFormWWTColourMarkedRecords',
      'description'=>'A data entry form reporting observations of colour-marked individual birds.'
    );
  }


public static function get_perms($nid) {
    return array(
      'IForm n'.$nid.' enter data by proxy',
    );
  }
  
  /* TODO
   *  
   *   Survey List
   *     Put in "loading" message functionality.
   *    Add a map and put samples on it, clickable
   *  
   *  Sort out {common}.
   * 
   * The report paging will not be converted to use LIMIT & OFFSET because we want the full list returned so 
   * we can display all the subject observations on the map.
   * When displaying transects, we should display children locations as well as parent.
   */
  
  /**
   * Return the generated form output.
   * @return Form HTML.
   */
   public static function rfj_fixed_args(&$args) {
    // hard-wire some 'dynamic' options to simplify the form. Todo: take out the dynamic code for these
    $args['subjectAccordion'] = false;
    $args['emailShow'] = false;
    $args['nameShow'] = false;
    $args['copyFromProfile'] = false;
    $args['multiple_subject_observation_mode'] = 'single';
    $args['extra_list_id'] = '';
    $args['occurrence_comment'] = false;
    $args['col_widths'] = '';
    $args['includeLocTools'] = false;
    $args['loctoolsLocTypeID'] = 0;
    $args['subject_observation_confidential'] = false;
    $args['observation_images'] = false;
   }  
   
   public static function rfj_set_mode() {
    self::$mode = (isset($args['no_grid']) && $args['no_grid'])     
        ? MODE_NEW_SAMPLE // default mode when no_grid set to true - display new sample
        : MODE_GRID; // default mode when no grid set to false - display grid of existing data
                // mode MODE_EXISTING: display existing sample

    if ($_POST) {
      if(!array_key_exists('website_id', $_POST)) { // non Indicia POST, in this case must be the location allocations. add check to ensure we don't corrupt the data by accident
        if(function_exists('iform_loctools_checkaccess') && iform_loctools_checkaccess(self::$node,'admin') && array_key_exists('mnhnld1', $_POST)){
          iform_loctools_deletelocations(self::$node);
          foreach($_POST as $key => $value){
            $parts = explode(':', $key);
            iform_loctools_insertlocation(self::$node, $parts[2], $parts[1]);
          }
        }
      } else if(!is_null(data_entry_helper::$entity_to_load)) {
        self::$mode = MODE_EXISTING; // errors with new sample, entity populated with post, so display this data.
      } // else valid save, so go back to gridview: default mode 0
    }

    if (array_key_exists('sample_id', $_GET) && $_GET['sample_id']!='{sample_id}') {
      self::$mode = MODE_EXISTING;
      self::$loadedSampleId = $_GET['sample_id'];
    }
    //Subject id from get params
    if (array_key_exists('subject_observation_id', $_GET) && $_GET['subject_observation_id']!='{subject_observation_id}') {
      self::$mode = MODE_EXISTING;
      // single subject_observation case
      self::$loadedSubjectObservationId = $_GET['subject_observation_id'];
    } 
    if (self::$mode!=MODE_EXISTING && array_key_exists('newSample', $_GET)) {
      self::$mode = MODE_NEW_SAMPLE;
      data_entry_helper::$entity_to_load = array();
      self::$subjectObservationIds = array(self::$loadedSubjectObservationId);
    } // else default to mode MODE_GRID or MODE_NEW_SAMPLE depending on no_grid parameter
//    self::$mode = $mode;
 

   }

private function rfj_debug_content($args,&$r){
    if (!empty($args['debug_info']) && $args['debug_info']) {
      $r .= '<input type="button" value="Debug info" onclick="$(\'#debug-info-div\').slideToggle();" /><br />'.
        '<div id="debug-info-div" style="display: none;">';
      $r .= '<p>$_GET is:<br /><pre>'.print_r($_GET, true).'</pre></p>';
      $r .= '<p>$_POST is:<br /><pre>'.print_r($_POST, true).'</pre></p>';
      $r .= '<p>Entity to load is:<br /><pre>'.print_r(data_entry_helper::$entity_to_load, true).'</pre></p>';
      $r .= '<p>Submission was:<br /><pre>'.print_r(self::$submission, true).'</pre></p>';
      $r .= '<input type="button" value="Hide debug info" onclick="$(\'#debug-info-div\').slideToggle();" />';
      $r .= '</div>';
    }

}

private function rfj_optional_buttons(){
// These should be enabled via the form definition
$r='';
    // reset button
    $r .= '<input type="button" class="ui-state-default ui-corner-all" value="'.lang::get('Abandon Form and Reload').'" '
      .'onclick="window.location.href=\''.url('node/'.(self::$node->nid), array('query' => 'newSample')).'\'">';    
    // clear all padlocks button
    $r .= ' <input type="button" class="ui-state-default ui-corner-all" value="'.lang::get('Clear All Padlocks').'" '
      .'onclick="if (indicia && indicia.locks) indicia.locks.unlockRegion(\'body\');">';    
      return $r;
}

private function rfj_hiddens($args,$attributes){
    // Get authorisation tokens to update the Warehouse, plus any other hidden data.
    $hiddens = self::$auth['write'].
          "<input type=\"hidden\" id=\"read_auth_token\" name=\"read_auth_token\" value=\"".self::$auth['read']['auth_token']."\" />\n".
          "<input type=\"hidden\" id=\"read_nonce\" name=\"read_nonce\" value=\"".self::$auth['read']['nonce']."\" />\n".
          "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n".
          "<input type=\"hidden\" id=\"survey_id\" name=\"survey_id\" value=\"".$args['survey_id']."\" />\n";



if (!empty($args['sample_method_id'])){$hiddens .= '<input type="hidden" name="sample:sample_method_id" value="'.$args['sample_method_id'].'"/>';}
if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      $hiddens .= "<input type=\"hidden\" id=\"sample:id\" name=\"sample:id\" value=\"".data_entry_helper::$entity_to_load['sample:id']."\" />\n";    
      
  $existing=(self::$mode==MODE_EXISTING && (self::$loadedSampleId || self::$loadedSubjectObservationId))?true:false;
 $hiddens .= get_user_profile_hidden_inputs($attributes, $args, $existing, self::$auth['read']);
 
      return $hiddens;
}
}

private function rfj_client_validate($args){
    // request automatic JS validation
    if (!isset($args['clientSideValidation']) || $args['clientSideValidation']) {
      data_entry_helper::enable_validation('entry_form');
      // override the default invalidHandler to activate the first accordion panels which has an error
      global $indicia_templates;  
      $indicia_templates['invalid_handler_javascript'] = "function(form, validator) {
          var tabselected=false;
          var accordion$=jQuery('.ui-accordion');
          jQuery.each(validator.errorMap, function(ctrlId, error) {
            // select the tab containing the first error control
            var ctrl = jQuery('[name=' + ctrlId.replace(/:/g, '\\\\:').replace(/\[/g, '\\\\[').replace(/]/g, '\\\\]') + ']');
            if (!tabselected && typeof(tabs)!=='undefined') {
              tabs.tabs('select',ctrl.filter('input,select').parents('.ui-tabs-panel')[0].id);
              tabselected = true;
            }
            ctrl.parents('fieldset').removeClass('collapsed');
            ctrl.parents('.fieldset-wrapper').show();
            // for each accordion, activate the first panel which has an error
            ctrl.parents('.ui-accordion-content').each(function (n) {
              var acc$ = $(this).closest('.ui-accordion');
              var accId = acc$[0].id.replace(/:/g, '\\\\:').replace(/\[/g, '\\\\[').replace(/]/g, '\\\\]');
              if (accordion$.is('#'+accId)) {
                var header$ = $(this).prev('h3');
                var accHeaderId = header$.attr('id').replace(/:/g, '\\\\:').replace(/\[/g, '\\\\[').replace(/]/g, '\\\\]');
                acc$.accordion('activate', '#'+accHeaderId);
                accordion$ = accordion$.not('#'+accId);
              }
            });
          });
        }";
      // By default, validate doesn't validate any ':hidden' fields, 
      // but we need to validate hidden with display: none; fields in accordions
      data_entry_helper::$javascript .= "jQuery.validator.setDefaults({ 
        ignore: \"input[type='hidden']\"
      });\n";
    }}

private function rfj_static_content($args,$attributes,$reloadPath,&$r,&$hiddens,&$existing,&$tabs){
    $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\">\n";
    // debug section
    self::rfj_debug_content($args,$r);
    //Optional buttons
    $r.=self::rfj_optional_buttons();//get html for debig abandon and padlock button
    $hiddens=self::rfj_hiddens($args,$attributes);//get html for hiddens
    self::rfj_client_validate($args);    // request automatic JS validation
    
    //we don't have a header or footer
//    if (method_exists(get_called_class(), 'getHeaderHTML')) {
//      $r .= call_user_func(array(get_called_class(), 'getHeaderHTML'), true, $args);
//    }
    $customAttributeTabs = get_attribute_tabs($attributes);

    // remove added comment controls unless editing an existing sample
    if (self::$mode!==MODE_EXISTING || helper_base::$form_mode==='ERRORS') {
      $controls = helper_base::explode_lines($args['structure']);
      $new_controls = array();
      foreach ($controls as $control) {
        if ($control!=='[show added sample comments]' && $control!=='[add sample comment]') {
          $new_controls[] = $control;
        }
      }
      $args['structure'] = implode("\r\n", $new_controls);
    }
    $tabs = self::get_all_tabs($args['structure'], $customAttributeTabs);
}

private function rfj_get_tabs(&$tabs,&$auth,&$args,&$attributes,&$hiddens,&$r,&$tabHtml){
    $r .= "<div id=\"controls\">\n";
 
    // Build a list of the tabs that actually have content
    $tabHtml = self::get_tab_html($tabs, $auth, $args, $attributes, $hiddens);
    // Output the dynamic tab headers
    if ($args['interface']!='one_page') {
      $headerOptions = array('tabs'=>array());
      foreach ($tabHtml as $tab=>$tabContent) {
        $alias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
        $tabtitle = lang::get("LANG_Tab_$alias");
        if ($tabtitle=="LANG_Tab_$alias") {
          // if no translation provided, we'll just use the standard heading
          $tabtitle = $tab;
        }
        $headerOptions['tabs']['#'.$alias] = $tabtitle;        
      }
      $r .= data_entry_helper::tab_header($headerOptions);
      data_entry_helper::enable_tabs(array(
          'divId'=>'controls',
          'style'=>$args['interface'],
          'progressBar' => isset($args['tabProgress']) && $args['tabProgress']==true
      ));
    }
        // Output the dynamic tab content
    $pageIdx = 0;
    foreach ($tabHtml as $tab=>$tabContent) {
      // get a machine readable alias for the heading
      $tabalias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
      $r .= '<div id="'.$tabalias.'">'."\n";
      // For wizard include the tab title as a header.
      if ($args['interface']=='wizard') {
        $r .= '<h1>'.$headerOptions['tabs']['#'.$tabalias].'</h1>';        
      }
      $r .= $tabContent;    
      // Add any buttons required at the bottom of the tab   
      if ($args['interface']=='wizard') {
        $r .= data_entry_helper::wizard_buttons(array(
          'divId'=>'controls',
          'page'=>$pageIdx===0 ? 'first' : (($pageIdx==count($tabHtml)-1) ? 'last' : 'middle')
        ));        
      } elseif ($pageIdx==count($tabHtml)-1 && !($args['interface']=='tabs' && $args['save_button_below_all_pages']))
        // last part of a non wizard interface must insert a save button, unless it is tabbed interface with save button beneath all pages 
        $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" id=\"save-button\" value=\"".lang::get('LANG_Save')."\" />\n";      
      $pageIdx++;
      $r .= "</div>\n";      
    }
    $r .= "</div>\n";
        }

private function rfj_get_footer($args,&$r){
    if ($args['interface']=='tabs' && $args['save_button_below_all_pages']) {
      $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" id=\"save-button\" value=\"".lang::get('LANG_Save')."\" />\n";
    }
    if(!empty(data_entry_helper::$validation_errors)){
      $r .= data_entry_helper::dump_remaining_errors();
    }   
    $r .= "</form>";
    
    if (method_exists(get_called_class(), 'getTrailerHTML')) $r .= call_user_func(array(get_called_class(), 'getTrailerHTML'), true, $args);
    return $r;
}



   
  public static function get_form($args, $node) {
    define ("MODE_GRID", 0);
    define ("MODE_NEW_SAMPLE", 1);
    define ("MODE_EXISTING", 2);
    
    
    
    self::parse_defaults($args);
    //self::getArgDefaults($args); RFJ don't call this rather than use empty copy of function
    self::$node = $node;
    //static keyword in php5.3 allows better handling of parent class vars
    static::$called_class ='iform_' . $node->iform;
    

    // if we use locks, we want them to be distinct for each drupal user
    if (function_exists('profile_load_profile')) { // check we are in drupal
      global $user;
      data_entry_helper::$javascript .= "if (indicia && indicia.locks && indicia.locks.setUser) {
        indicia.locks.setUser ('".$user->uid."');
      }\n";
    }
    self::rfj_fixed_args($args);
    
    // Get authorisation tokens to update and read from the Warehouse.
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $svcUrl = self::warehouseUrl().'index.php/services';
    self::$auth = $auth;
    
    drupal_add_js(iform_media_folder_path() . 'js/jquery.form.js', 'module');
    // here is where tooltip js could be added
   
    self::$loadedSampleId = null;
    self::$loadedSubjectObservationId = null;

   // Try to rationalise mode setting
   self::rfj_set_mode();
 
    // default mode  MODE_GRID : display grid of the samples to add a new one 
    // or edit an existing one.

if(!isset(self::$mode))self::$mode=99;
switch(self::$mode){
	case MODE_GRID :
		return self::rfj_do_grid($args,$node,$tabs,$svcUrl);
		break;
	case MODE_NEW_SAMPLE :
		break;
	case MODE_EXISTING :
		if(is_null(data_entry_helper::$entity_to_load)){ // only load if not in error situation
		      // Displaying an existing sample. If we know the subject_observation ID, 
		      // and don't know the sample ID 
		      // then we must get the sample id from the subject_observation data.
//RFJ      			if (self::$loadedSubjectObservationId && !self::$loadedSampleId) {
//RFJ		          data_entry_helper::load_existing_record($auth['read'], 'subject_observation', self::$loadedSubjectObservationId);
		        //RFJself::$loadedSampleId = data_entry_helper::$entity_to_load['subject_observation:sample_id'];
//RFJ		      }
		      data_entry_helper::$entity_to_load = self::reload_form_data(self::$loadedSampleId, $args, $auth);
    			}
		break;
}
    // from this point on, we are MODE_EXISTING or MODE_NEW_SAMPLE
    
    // get the sample attributes
    $attrOpts = array(
        'id' => data_entry_helper::$entity_to_load['sample:id']
       ,'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
    );
    
    // select only the custom attributes that are for this sample method or all sample methods, if this
    // form is for a specific sample method.
    if (!empty($args['sample_method_id']))
      $attrOpts['sample_method_id']=$args['sample_method_id'];
    $attributes = data_entry_helper::getAttributes($attrOpts, false);
    // Check if Recorder details is included as a control. 
    // If so, remove the recorder attributes from the $attributes array so not output anywhere else.
    $arr = helper_base::explode_lines($args['structure']);
    if (in_array('[recorder details]', $arr)) {
      $attrCount = count($attributes);
      for ($i = 0; $i < $attrCount; $i++) {
        if (strcasecmp($attributes[$i]['caption'], 'first name')===0 
        || strcasecmp($attributes[$i]['caption'], 'last name')===0 
        || strcasecmp($attributes[$i]['caption'], 'email')===0) {
          unset($attributes[$i]);
        }
      }
    }
    //// Make sure the form action points back to this page
    $reload = data_entry_helper::get_reload_link_parts();
    unset($reload['params']['sample_id']);
    unset($reload['params']['subject_observation_id']);
    unset($reload['params']['newSample']);
    $reloadPath = $reload['path'];
    // don't url-encode the drupal path id using dirty url
    $pathParam = (function_exists('variable_get') && variable_get('clean_url', 0)=='0') ? 'q' : '';
    if(count($reload['params'])) {
      if ($pathParam==='q' && array_key_exists('q', $reload['params'])) {
        $reloadPath .= '?q='.$reload['params']['q'];
        unset($reload['params']['q']);
        if (count($reload['params'])) {
          $reloadPath .= '&'.http_build_query($reload['params']);
        }
      } else {
        $reloadPath .= '?'.http_build_query($reload['params']);
      }
    }
    

self::rfj_static_content($args,$attributes,$reloadPath,$r,$hiddens,$existing,$tabs);
self::rfj_get_tabs($tabs,$auth,$args,$attributes,$hiddens,$r,$tabHtml);
return self::rfj_get_footer($args,$r);
  

  }








private function rfj_get_subject_observation_attribute_values($auth,&$form_data,$subRecords){ 
// now generic function to process form attributes from data 
$table = 'subject_observation_attribute_value';
$keyfield='subject_observation_id';
$keyvals=self::$subjectObservationIds;
$prefix='sjoAttr';
$attrib_id='subject_observation_attribute_id';


    // load the xxx_attribute value(s) for this sample
    $query = array('in'=>array($keyfield, $keyvals));
    $filter = array('query'=>json_encode($query),);
    $options = array(
      'table' => $table,
      'extraParams' => $auth['read'] + $filter,
      'nocache' => true,
    );
    $xxxAttrs = data_entry_helper::get_population_data($options);

    // add each xxx_attribute to the form data
    for ($idx=0; $idx<count($subRecords); $idx++) {
      $subRecord=$subRecords[$idx];
      // prefix the keys and load to form data
      $fieldprefix = 'idn:'.$idx.":$prefix:";
      foreach ($xxxAttrs as $xxxAttr) {
        if ($xxxAttr[$keyfield]===$subRecord['id']) {
          if (!empty($xxxAttr['id'])) {
            $form_data[$fieldprefix.$xxxAttr[$attrib_id].':'.$xxxAttr['id']] = $xxxAttr['raw_value'];
          }
        }
      }
    }
    }



public function rfj_populate($auth,$keyfield,$keyvals,$table,$viewtype='detail'){
//$keyfield='subject_observation_id'
//$keyvals=self::$subjectObservationIds;
//$table='identifiers_subject_observation';



    // generic load table
    $query = array('in'=>array($keyfield, $keyvals));
    $filter = array('query'=>json_encode($query),);
    $options = array(
      'table' => $table,
      'extraParams' => $auth['read'] + array('view'=>$viewtype) + $filter,
      'nocache' => true,
    );
    return data_entry_helper::get_population_data($options);
}




  /*
   * helper function to reload data for existing sample 
   * @param $loadedSampleId Required. id for required sample.
   * if not supplied, all subject_observations in the sample are loaded
   * @return array of data values matching the form control names. 
   */

  private static function reload_form_data($loadedSampleId, $args, $auth) {
    $form_data = array();
    if (!$loadedSampleId) { // required
      return $form_data;
    }
    
    // load the sample
    data_entry_helper::load_existing_record($auth['read'], 'sample', $loadedSampleId);
    $form_data = array_merge(data_entry_helper::$entity_to_load, $form_data);
    
    // if we have a subject_observation, then we just load that,
    // otherwise we need all the subjects_observations in the sample
    $filter = array();
    if (count(self::$subjectObservationIds)===1) {
//RFJ      $filter = array('id'=>self::$subjectObservationIds[0]);
//RFJ      self::$subjectObservationIds = array();
    }
  //Atually need to account for parent_ids too so that they are in order
    // load the subject_observation(s) for this sample
    $options = array(
      'table' => 'subject_observation',
      'extraParams' => $auth['read'] + array('sample_id'=>$loadedSampleId, 'view'=>'detail') + $filter,
      'nocache' => true,
    );
    $subjectObservations = data_entry_helper::get_population_data($options);
//    file_put_contents('/var/www/vhosts/monitoring.wwt.org.uk/httpdocs/recording/tmp/debug.txt',print_r($subjectObservations,true));

    // add each subject_observation to the form data
    //needs to get EACH linked table separately for each sobs
    for ($idx=0; $idx<count($subjectObservations); $idx++) {
      $subjectObservation=$subjectObservations[$idx];
      self::$subjectObservationIds[] = $subjectObservation['id'];
      // prefix the keys and load to form data
      $fieldprefix = 'idn:'.$idx.':subject_observation:';
      $keys = array_keys($subjectObservation);
      foreach ($keys as $key) {
        $form_data[$fieldprefix.$key] = $subjectObservation[$key];
      }
    }
	self::rfj_get_subject_observation_attribute_values($auth,$form_data,$subjectObservations);  
    
    
    // load the occurrence(s) for this sample
    $query = array('in'=>array('subject_observation_id', self::$subjectObservationIds));
    $filter = array('query'=>json_encode($query),);
    $options = array(
      'table' => 'occurrences_subject_observation',
      'extraParams' => $auth['read'] + array('view'=>'detail') + $filter,
      'nocache' => true,
    );
    $osos = data_entry_helper::get_population_data($options);
    $occurrenceIds = array();
    foreach ($osos as $oso) {
      $occurrenceIds[] = $oso['occurrence_id'];
    }
    
    // xxxxxxxxxx
    $query = array('in'=>array('id', $occurrenceIds));
    $filter = array('query'=>json_encode($query),);
    $options = array(
      'table' => 'occurrence',
      'extraParams' => $auth['read'] + array('view'=>'detail') + $filter,
      'nocache' => true,
    );
    $occurrences = data_entry_helper::get_population_data($options);
    // xxxxxxxxxx
    $query = array('in'=>array('occurrence_id', $occurrenceIds));
    $filter = array('query'=>json_encode($query),);
    $options = array(
      'table' => 'occurrence_image',
      'extraParams' => $auth['read'] + array('view'=>'list') + $filter,
      'nocache' => true,
    );
    $occurrence_images = data_entry_helper::get_population_data($options);

    // add each occurrence, occurrences_subject_observation and occurrence_image to the form data
    for ($idx=0; $idx<count($subjectObservations); $idx++) {
      $subjectObservation=$subjectObservations[$idx];
      // note, this code would break with more than one occurrence on the subject_observation
      // fortunately, that can't happen with this form yet, but may do with associations?
      // prefix the keys and load to form data
      foreach ($osos as $oso) {
        if ($oso['subject_observation_id']===$subjectObservation['id']) {
          foreach ($occurrences as $occurrence) {
            if ($oso['occurrence_id']===$occurrence['id']) {
              $fieldprefix = 'idn:'.$idx.':occurrences_subject_observation:';
              $keys = array_keys($oso);
              foreach ($keys as $key) {
                $form_data[$fieldprefix.$key] = $oso[$key];
              }
              $fieldprefix = 'idn:'.$idx.':occurrence:';
              $keys = array_keys($occurrence);
              foreach ($keys as $key) {
                $form_data[$fieldprefix.$key] = $occurrence[$key];
                if ($key=='taxon' && $args['species_ctrl']=='autocomplete') {
                  $form_data[$fieldprefix.'taxa_taxon_list_id:taxon'] = $occurrence[$key];
                }
              }
            }
          }
          foreach ($occurrence_images as $occurrence_image) {
            if ($oso['occurrence_id']===$occurrence_image['occurrence_id']) {
              $fieldprefix = 'idn:'.$idx.':occurrence_image:';
              $fieldsuffix = ':'.$occurrence_image['path'];
              $keys = array_keys($occurrence_image);
              foreach ($keys as $key) {
                $form_data[$fieldprefix.$key.$fieldsuffix] = $occurrence_image[$key];
              }
            }
          }
        }
      }
    }
    


    // load the identifiers_subject_observation(s) for this sample
    $isos=self::rfj_populate($auth,'subject_observation_id',self::$subjectObservationIds,'identifiers_subject_observation');
    // load the identifiers_subject_observation_attributes(s) for this sample
    $isoIds = array();
    foreach ($isos as $iso) {
      $isoIds[] = $iso['id'];
    }

    // load the identifiers_subject_observation_attribute_value(s) for this sample
    $isoAttrs=self::rfj_populate($auth,'identifiers_subject_observation_id',$isoIds,'identifiers_subject_observation_attribute_value','list');

    // load the identifier(s) for this sample
    $identifierIds = array();
    foreach ($isos as $iso) {
      $identifierIds[] = $iso['identifier_id'];
    }

   // load the identifiers for this sample
    $identifiers =self::rfj_populate($auth,'id',$identifierIds,'identifier','detail');
    
   // load the identifier_attributes(s) for this sample
    $idnAttrs=self::rfj_populate($auth,'identifier_id',$identifierIds,'identifier_attribute_value','list');

   // add each identifier to the form data
    for ($idx=0; $idx<count($subjectObservations); $idx++) {
      $subjectObservation=$subjectObservations[$idx];
      // prefix the keys and load to form data
      foreach ($isos as $iso) {
        if ($iso['subject_observation_id']===$subjectObservation['id']) {
          foreach ($identifiers as $identifier) {
            if ($iso['identifier_id']===$identifier['id']) {
              switch($identifier['identifier_type_id']){
              	case $args['neck_collar_type'] :
              		$identifier_type = 'neck-collar';
              		break;
              	case $args['enscribed_colour_ring_type'] :
              		$identifier_type = 'colour-ring';
              		break;
              	case $args['metal_ring_type'] :
	              	$identifier_type = 'metal';
              		break;
              	default:
        	      	$identifier_type = '';
              }
              
              $fieldprefix = 'idn:'.$idx.':'.$identifier_type.':identifiers_subject_observation:';
              $keys = array_keys($iso);
              foreach ($keys as $key) {
                $form_data[$fieldprefix.$key] = $iso[$key];
              }

              $fieldprefix = 'idn:'.$idx.':'.$identifier_type.':identifier:';
              $form_data[$fieldprefix.'checkbox'] = 'on';
              $keys = array_keys($identifier);
              foreach ($keys as $key) {
                $form_data[$fieldprefix.$key] = $identifier[$key];
              }

              $fieldprefix = 'idn:'.$idx.':'.$identifier_type.':idnAttr:';
              foreach ($idnAttrs as $idnAttr) {
                if ($iso['identifier_id']===$idnAttr['identifier_id']) {
                  if ($idnAttr['multi_value']==='t') {
                    $form_data[$fieldprefix.$idnAttr['identifier_attribute_id']][] = $idnAttr['raw_value'];
                  } else {
                    $form_data[$fieldprefix.$idnAttr['identifier_attribute_id']] = $idnAttr['raw_value'];
                  }
                }
              }

              $fieldprefix = 'idn:'.$idx.':'.$identifier_type.':isoAttr:';
              foreach ($isoAttrs as $isoAttr) {
                if ($iso['id']===$isoAttr['identifiers_subject_observation_id']) {
                  if (!empty($isoAttr['id'])) {
                    $form_data[$fieldprefix.$isoAttr['identifiers_subject_observation_attribute_id'].':'.$isoAttr['id']] = $isoAttr['raw_value'];
                  }
                }
              }
            }
          }
        }
      }
    }

    return $form_data;
  }

  protected static function get_control_recorderlist($auth, $args, $tabalias, $options) {
  return data_entry_helper::sub_list(array(
  	'fieldname'=>'smpAttr:42',
  	'table'=>'person',
  	'captionField'=>'caption',
  	'extraParams'=>$auth['read'], //+array('surname'=>'p*')
  	
  ));
  }
  
  
  /**
   * Get the control for species input, either a grid or a single species input control.
   */
  protected static function get_control_species($auth, $args, $tabalias, $options) {
    global $user;
    $extraParams = $auth['read'];
    $fieldPrefix = !empty($options['fieldprefix']) ? $options['fieldprefix'] : '';
    if (!empty($args['taxon_filter_field']) && !empty($args['taxon_filter'])) {
      $filterLines = helper_base::explode_lines($args['taxon_filter']);
      if ($args['multiple_subject_observation_mode'] !== 'single' && $args['taxon_filter_field']!=='taxon_group' && count($filterLines)===1) {
        // The form is configured for filtering by taxon name or meaning id. If there is only one specified then the form
        // cannot display a species checklist, as there is no point. So, convert our preferred taxon name or meaning ID to find the 
        // preferred taxa_taxon_list_id from the selected checklist, and then output a hidden ID.
        if (empty($args['list_id']))
          throw new exception(lang::get('Please configure the Initial Species List parameter to define which list the species to record is selected from.'));
        $filter = array(
          'preferred'=>'t',
          'taxon_list_id'=>$args['list_id']
        );
        if ($args['taxon_filter_field']=='preferred_name')
          $filter['taxon']=$filterLines[0];
        else
          $filter[$args['taxon_filter_field']]=$filterLines[0];
        $options = array(
          'table' => 'taxa_taxon_list',
          'extraParams' => $auth['read'] + $filter
        );
        $response =data_entry_helper::get_population_data($options);
        if (count($response)===0)
          throw new exception(lang::get('Failed to find the single species that this form is setup to record in the defined list.'));
        if (count($response)>1)
          throw new exception(lang::get('This form is setup for single species recording, but more than one species with the same name exists in the list.'));          
        return '<input type="hidden" name="'.$fieldPrefix.'occurrence:taxa_taxon_list_id" value="'.$response[0]['id']."\"/>\n";
      }
    }
    if (call_user_func(array(get_called_class(), 'getGridMode'), $args)) {      
      // multiple species being input via a grid      
      $species_ctrl_opts=array_merge(array(
          'listId'=>$args['list_id'],
          'label'=>lang::get('occurrence:taxa_taxon_list_id'),
          'columns'=>1,          
          'extraParams'=>$extraParams,
          'survey_id'=>$args['survey_id'],
          'occurrenceComment'=>$args['occurrence_comment'],
          'occurrenceConfidential'=>(isset($args['subject_observation_confidential']) ? $args['subject_observation_confidential'] : false),
          'occurrenceImages'=>$args['observation_images'],
          'PHPtaxonLabel' => true,
          'language' => iform_lang_iso_639_2($user->lang), // used for termlists in attributes
          'cacheLookup' => isset($args['cache_lookup']) && $args['cache_lookup'],
          'speciesNameFilterMode' => self::getSpeciesNameFilterMode($args),          
      ), $options);
      if ($args['extra_list_id']) $species_ctrl_opts['lookupListId']=$args['extra_list_id'];
      if (!empty($args['taxon_filter_field']) && !empty($args['taxon_filter'])) {
        $species_ctrl_opts['taxonFilterField']=$args['taxon_filter_field'];
        $species_ctrl_opts['taxonFilter']=$filterLines;
      }
      if (isset($args['col_widths']) && $args['col_widths']) $species_ctrl_opts['colWidths']=explode(',', $args['col_widths']);
      call_user_func(array(get_called_class(), 'build_grid_taxon_label_function'), $args);
      call_user_func(array(get_called_class(), 'build_grid_autocomplete_function'), $args);
      
      // Start by outputting a hidden value that tells us we are using a grid when the data is posted,
      // then output the grid control
      return '<input type="hidden" value="true" name="gridmode" />'.
          data_entry_helper::species_checklist($species_ctrl_opts);
    }
    else {
      // A single species entry control of some kind
      if ($args['extra_list_id']=='')
        $extraParams['taxon_list_id'] = $args['list_id'];
      // @todo At the moment the autocomplete control does not support 2 lists. So use just the extra list. Should 
      // update to support 2 lists.
      elseif ($args['species_ctrl']=='autocomplete')
        $extraParams['taxon_list_id'] = empty($args['extra_list_id']) ? $args['list_id'] : $args['extra_list_id'];
      else
        $extraParams['taxon_list_id'] = array($args['list_id'], $args['extra_list_id']);
      if (!empty($args['taxon_filter_field']) && !empty($args['taxon_filter']))
        // filter the taxa available to record
        $query = array('in'=>array($args['taxon_filter_field'], helper_base::explode_lines($args['taxon_filter'])));
      else 
        $query = array();
      // Apply the species names filter to the single species picker control
      if (isset($args['species_names_filter'])) {
        $languageFieldName = isset($args['cache_lookup']) && $args['cache_lookup'] ? 'language_iso' : 'language';
        switch($args['species_names_filter']) {
          case 'preferred' :
            $extraParams += array('preferred'=>'t');
            break;
          case 'currentLanguage' :
            if (isset($options['language']))
              $extraParams += array($languageFieldName=>$options['language']);
            break;
          case 'excludeSynonyms':
            $query['where'] = array("(preferred='t' OR $languageFieldName<>'lat')");
            break;
        }
      }
      if (count($query)) 
        $extraParams['query'] = json_encode($query);
      $species_ctrl_opts=array_merge(array(
          'label'=>lang::get('occurrence:taxa_taxon_list_id'),
          'fieldname'=>$fieldPrefix.'occurrence:taxa_taxon_list_id',
          'table'=>'taxa_taxon_list',
          'captionField'=>'taxon',
          'valueField'=>'id',
          'columns'=>2,
          'parentField'=>'parent_id',
          'extraParams'=>$extraParams,
          'blankText'=>'Please select'
      ), $options);
      if (isset($args['cache_lookup']) && $args['cache_lookup'])
        $species_ctrl_opts['extraParams']['view']='cache';
      global $indicia_templates;
      if (isset($args['species_include_both_names']) && $args['species_include_both_names']) {
        if ($args['species_names_filter']=='all')
          $indicia_templates['species_caption'] = '{taxon}';
        elseif ($args['species_names_filter']=='language')
          $indicia_templates['species_caption'] = '{taxon} - {preferred_name}';
        else
          $indicia_templates['species_caption'] = '{taxon} - {common}';
        $species_ctrl_opts['captionTemplate'] = 'species_caption';
      }
      if ($args['species_ctrl']=='tree_browser') {
        // change the node template to include images
        $indicia_templates['tree_browser_node']='<div>'.
            '<img src="'.self::warehouseUrl().'/upload/thumb-{image_path}" alt="Image of {caption}" width="80" /></div>'.
            '<span>{caption}</span>';
      }
      // Dynamically generate the species selection control required.
      return call_user_func(array('data_entry_helper', $args['species_ctrl']), $species_ctrl_opts);
    }
  }
  
  
  /**
   * Get the observation comment control
   */
  protected static function get_control_observationcomment($auth, $args, $tabalias, $options) {
    $fieldPrefix = !empty($options['fieldprefix']) ? $options['fieldprefix'] : '';
    return data_entry_helper::textarea(array_merge(array(
      'fieldname'=>$fieldPrefix.'subject_observation:comment',
      'label'=>lang::get('Any information you might like to add'),
      'class'=>'control-width-5',
    ), $options)); 
  }
  
  /**
   * Get the add sample comment control. This is for additional comments by other people after the 
   * colour-marked individual has been reported.
   */
  protected static function get_control_showaddedsamplecomments($auth, $args, $tabalias, $options) {
    $r = '';
    if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      $reportName = 'reports_for_prebuilt_forms/sample_comments_list';
      $r .= data_entry_helper::report_grid(array(
        'id' => 'sample-comments-grid',
        'dataSource' => $reportName,
        'mode' => 'report',
        'readAuth' => $auth['read'],
        'itemsPerPage' =>(isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 10),
        'autoParamsForm' => true,
        'extraParams' => array(
          'sample_id'=>data_entry_helper::$entity_to_load['sample:id'], 
        )
      ));    
    }
    return $r;
  }
  
  /**
   * Get the add sample comment control. This is for additional comments by other people after the 
   * colour-marked individual has been reported.
   */
  protected static function get_control_addsamplecomment($auth, $args, $tabalias, $options) {
    return data_entry_helper::textarea(array_merge(array(
      'fieldname'=>'sample_comment:comment',
      'label'=>lang::get('Add a comment about this report'),
      'class'=>'control-width-6',
    ), $options)); 
  }
  
  /**
   * Get the block of custom attributes at the species (occurrence) level
   */
  protected static function get_control_speciesattributes($auth, $args, $tabalias, $options) {
    if (!(call_user_func(array(get_called_class(), 'getGridMode'), $args))) {  
      // Add any dynamically generated controls
      $attrArgs = array(
         'valuetable'=>'occurrence_attribute_value',
         'attrtable'=>'occurrence_attribute',
         'key'=>'occurrence_id',
         'fieldprefix'=>'occAttr',
         'extraParams'=>$auth['read'],
         'survey_id'=>$args['survey_id']
      );
      if (count(self::$subjectObservationIds)==1) {
        // if we have a single subject observation Id to load, use it to get attribute values
        $attrArgs['id'] = self::$subjectObservationIds[0];
      }
      $attributes = data_entry_helper::getAttributes($attrArgs, false);
      $defAttrOptions = array('extraParams'=>$auth['read']);
      $r = get_attribute_html($attributes, $args, $defAttrOptions);
      if ($args['occurrence_comment'])
        $r .= data_entry_helper::textarea(array(
          'fieldname'=>'occurrence:comment',
          'label'=>lang::get('Record Comment')
        ));
      if ($args['subject_observation_confidential'])
        $r .= data_entry_helper::checkbox(array(
          'fieldname'=>'occurrence:confidential',
          'label'=>lang::get('Record Confidental')
        ));
      if ($args['observation_images']){
        $opts = array(
          'table'=>'occurrence_image',
          'label'=>lang::get('Upload your photos'),
        );
        if ($args['interface']!=='one_page')
          $opts['tabDiv']=$tabalias;
        $opts['resizeWidth'] = isset($options['resizeWidth']) ? $options['resizeWidth'] : 1600;
        $opts['resizeHeight'] = isset($options['resizeHeight']) ? $options['resizeHeight'] : 1600;
        $opts['caption'] = lang::get('Photos');
        $r .= data_entry_helper::file_box($opts);
      }
      return $r;
    } else 
      // in grid mode the attributes are embedded in the grid.
      return '';
  }
  
  /** 
   * Get the location search control.
   */
  protected static function get_control_placesearch($auth, $args, $tabalias, $options) {
    $georefOpts = iform_map_get_georef_options($args, $auth['read']);
    if ($georefOpts['driver']=='geoplanet' && empty(helper_config::$geoplanet_api_key))
      // can't use place search without the driver API key
      return '';
    return data_entry_helper::georeference_lookup(array_merge(
      $georefOpts,
      $options
    ));
  }

  /**
   * Get the block of sample custom attributes for the recorder
   */
  protected static function get_control_recorderdetails($auth, $args, $tabalias, $options) {
    // get the sample attributes
    $attrOpts = array(
        'id' => data_entry_helper::$entity_to_load['sample:id']
       ,'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
    );
    // select only the custom attributes that are for this sample method or all sample methods, if this
    // form is for a specific sample method.
    if (!empty($args['sample_method_id']))
      $attrOpts['sample_method_id']=$args['sample_method_id'];
    $attributes = data_entry_helper::getAttributes($attrOpts, false);
    // load values from profile. This is Drupal specific code, so degrade gracefully.
    if (function_exists('profile_load_profile')) {
      global $user;
      profile_load_all_profile($user);
      foreach($attributes as &$attribute) {
        if (!isset($attribute['default'])) {
          $attrPropName = 'profile_'.strtolower(str_replace(' ','_',$attribute['caption']));
          if (isset($user->$attrPropName)) {
            $attribute['default'] = $user->$attrPropName;
          } elseif (strcasecmp($attribute['caption'], 'email')===0 && isset($user->mail)) {
            $attribute['default'] = $user->mail;
          }
        }
      }
    }
    $defAttrOptions = array('extraParams'=>$auth['read'], 'class'=>"required");
    $attrHtml = '';
    // Drupal specific code
    if (!user_access('IForm n'.self::$node->nid.' enter data by proxy')) {
      if (isset($options['lockable'])) {
        unset($options['lockable']);
      }
      $defAttrOptions += array('readonly'=>'readonly="readonly"');
      $attrHtml .= '<div class="readonlyFieldset">';
    }
    $defAttrOptions += $options;
    $blockOptions = array();
    $attrHtml .= get_attribute_html($attributes, $args, $defAttrOptions, 'Enter data by proxy', $blockOptions);
    if (!user_access('IForm n'.self::$node->nid.' enter data by proxy')) {
      $attrHtml .= '</div>';
    }
  
    return $attrHtml;
  }
  
  /*
   * Get the species picker with selected colour identifier controls
   */
  
  public function rfj_species_identifier_init(&$options,&$filter,&$dataOpts,$auth){
    // we need to control which items are lockable if locking requested
    if (!empty($options['lockable']) && $options['lockable']==true) {
      $options['identifiers_lockable'] = $options['lockable'];
    } else {
      $options['identifiers_lockable'] = '';
    }
    unset($options['lockable']);
    // get the identifier type data
    $filter = array(
      'termlist_external_key' => 'indicia:assoc:identifier_type',
    );
    $dataOpts = array(
      'table' => 'termlists_term',
      'extraParams' => $auth['read'] + $filter,
    );
    $options['identifierTypes'] = data_entry_helper::get_population_data($dataOpts);
    
    // get the identifier attribute type data
    $dataOpts = array(
      'table' => 'identifier_attribute',
      'extraParams' => $auth['read'],
    );
    $options['idnAttributeTypes'] = data_entry_helper::get_population_data($dataOpts);
    
    // set up the known system types for identifier attributes
    $options['baseColourId'] = -1;
    $options['textColourId'] = -1;
    $options['sequenceId'] = -1;
    $options['positionId'] = -1;
    foreach ($options['idnAttributeTypes'] as $idnAttributeType) {
      if (!empty($idnAttributeType['system_function'])) {
        switch ($idnAttributeType['system_function']) {
          case 'base_colour' :
            $options['baseColourId'] = $idnAttributeType['id'];
            break;
          case 'text_colour' :
            $options['textColourId'] = $idnAttributeType['id'];
            break;
          case 'sequence' :
            $options['sequenceId'] = $idnAttributeType['id'];
            break;
          case 'position' :
            $options['positionId'] = $idnAttributeType['id'];
            break;
        }
      }
    }
    
    // get the subject observation attribute type data
    $dataOpts = array(
      'table' => 'subject_observation_attribute',
      'extraParams' => $auth['read'],
    );
    $options['sjoAttributeTypes'] = data_entry_helper::get_population_data($dataOpts);
//    file_put_contents('/var/www/vhosts/monitoring.wwt.org.uk/httpdocs/recording/tmp/debug.txt',print_r($options['sjoAttributeTypes'],true));
    // set up the known system types for subject_observation attributes
    $options['attachmentId'] = -1;
    $options['genderId'] = -1;
    $options['stageId'] = -1;
    $options['lifeStatusId'] = -1;
    $options['unmarkedAdults'] = -1; // not a system function
    foreach ($options['sjoAttributeTypes'] as $sjoAttributeType) {
      if (!empty($sjoAttributeType['system_function'])) {
        switch ($sjoAttributeType['system_function']) {
          case 'attachment' :
            $options['attachmentId'] = $sjoAttributeType['id'];
            break;
          case 'gender' :
            $options['genderId'] = $sjoAttributeType['id'];
            break;
          case 'stage' :
            $options['stageId'] = $sjoAttributeType['id'];
            break;
          case 'life_status' :
            $options['lifeStatusId'] = $sjoAttributeType['id'];
            break;
//          case 'unmarked_adults' :
//            $options['unmarkedAdults'] = $sjoAttributeType['id'];
//            break;
        }
      }
    }
    
    // get the identifiers subject observation attribute type data
    $dataOpts = array(
      'table' => 'identifiers_subject_observation_attribute',
      'extraParams' => $auth['read'],
    );
    $options['isoAttributeTypes'] = data_entry_helper::get_population_data($dataOpts);
    
    // set up the known system types for subject_observation attributes
    $options['conditionsId'] = -1;
    foreach ($options['isoAttributeTypes'] as $isoAttributeType) {
      if (!empty($isoAttributeType['system_function'])) {
        switch ($isoAttributeType['system_function']) {
          case 'identifier_condition' :
            $options['conditionsId'] = $isoAttributeType['id'];
            break;
        }
      }
    }
      
  }
  public function rfj_check_speciesidentifier_attributes_present($options){
    // throw an exception if any of the required custom attributes is missing
    $errorMessages = array();
    foreach (array('baseColourId', 'textColourId', 'sequenceId', 'positionId', 
      'attachmentId', 'genderId', 'stageId', 'lifeStatusId', 'conditionsId', ) as $attrId) {
      if ($options[$attrId]===-1) {
        $errorMessages[] = lang::get('Required custom attribute for '.$attrId.' has not been found. '
        .'Please check this has been created on the warehouse and is associated with the correct system function.');
      }
    }
    if (count($errorMessages)>0) {
      $errorMessage = implode('<br />', $errorMessages);
      throw new exception($errorMessage);
    }
    
  }
  private function rfj_check_speciesidentifier_javascript(&$options,$args){
    // configure the identifiers javascript
    // write it late so it happens after any locked values are applied
    if (!$options['inNewIndividual']) {
      data_entry_helper::$late_javascript .= "indicia.wwt.initForm (
        '".$options['baseColourId']."',
        '".$options['textColourId']."',
        '".$options['sequenceId']."',
        '".$options['positionId']."',
        '".$args['default_leg_vertical']."',
        '".(!empty($args['neck_collar_regex']) ? $args['neck_collar_regex'] : '')."',
        '".(!empty($args['enscribed_colour_ring_regex']) ? $args['enscribed_colour_ring_regex'] : '')."',
        '".(!empty($args['metal_ring_regex']) ? $args['metal_ring_regex'] : '')."',
        '".($args['clientSideValidation'] ? 'true' : 'false')."',
        '".($args['subjectAccordion'] ? 'true' : 'false')."'\n".
        ");\n";
    }
  
  }
private function rfj_individual_imagediv($taxIdx){
// Div for image panel inc tooltip text over image of bird
    $r = '<div title="<b>Mouse button clicks:</b><br/><b>Body</b><br/>Left:next species<br/>Right:Prev species<br/><b>Rings(where appropriate)</b><br/><b>Left:</b> type the ring code,<br/><b>middle:</b> toggle text colour<br/><b>right:</b> cycle through ring colours" id="'.$options['fieldprefix'].'image:panel" class="image_panel ui-corner-all beautytips">';
    // Create Identifier Divs to appear on image
    foreach(array('neck-collar','colour-ring','metal')as $idtype)
    $r .= '<div id="idn:'.$taxIdx.':'.$idtype.':colourbox" class="identifier '.$idtype.'-identifier-colourbox"><input class="identifier-colourbox-input '.$idtype.'-identifier-colourbox-input ui-corner-all"></div>';
    $r .= '</div>'; 
    return $r;
}
    public function rfj_show_hiddens($hidtbls,$options){
    $r='';
   foreach($hidtbls as $hidtbl=>$hiddens ) {
    foreach($hiddens as $hidfld){
	    if (isset(data_entry_helper::$entity_to_load[$options['fieldprefix'].$hidtbl.':'.$hidfld])) 
	    	$r .= '<input type="text" id="'.$options['fieldprefix'].$hidtbl.':'.$hidfld.'" name="'.$options['fieldprefix'].$hidtbl.':'.$hidfld.'" '.'value="'.data_entry_helper::$entity_to_load[$options['fieldprefix'].$hidtbl.':'.$hidfld].'" />'."\n";    
    }
  }
 return $r;
}

public function rfj_where_in_family($taxIdx,$numIndivs){

if($taxIdx+1<$numIndivs){
	 $next_fieldprefix='idn:'.($taxIdx+1).':';
}
else	$next_fieldprefix=false; //must be last individual

$fieldprefix = 'idn:'.$taxIdx.':';
$next_fieldprefix = 'idn:'.($taxIdx+1).':';
$prev_fieldprefix = 'idn:'.($taxIdx-1).':';



if (isset(data_entry_helper::$entity_to_load[$fieldprefix.'subject_observation:parent_id'])) 
	 $parent_id=data_entry_helper::$entity_to_load[$fieldprefix.'subject_observation:parent_id'];
	 
 if($parent_id=='')$parent_id=false;
 if($next_fieldprefix&&data_entry_helper::$entity_to_load[$next_fieldprefix.'subject_observation:parent_id']<>'')
	 $next_parent_id=data_entry_helper::$entity_to_load[$next_fieldprefix.'subject_observation:parent_id'];
 else 	 $next_parent_id=false;
 
 if(!$parent_id && !$next_parent_id ) return 'NEITHER';
 else if($parent_id&& !$next_parent_id) return 'LASTINFAMILY';
 else if(!$parent_id&& $next_parent_id) return 'NEXTISFAMILY';
 else if($parent_id==$next_parent_id) return 'NEXTISSAMEFAMILY';
 else if($parent_id!=$next_parent_id) return 'NEXTISDIFFFAMILY';

}


  protected static function get_control_individuals($auth, $args, $tabalias, $options) {
    static $taxIdx = 0; 
// use number of individuals instead
    
// Initialise settings and attributes available
    self::rfj_species_identifier_init($options,$filter,$dataOpts,$auth);
    self::rfj_check_speciesidentifier_attributes_present($options);
    self::rfj_check_speciesidentifier_javascript($options,$args);
    $r = '';

    //Same code is used for new individual being added (without div) 
    if (!$options['inNewIndividual']) {;
      $r .= '<div id="idn:subject:accordion" class="idn-subject-accordion">';
    }


//INDIVIDUAL FIELDSET etc
// Needs to loop through families and individuals
$numIndivs=count(self::$subjectObservationIds);

$families=array();
$singles=array();
// Loop through individuals
for($taxIdx=0;$taxIdx<$numIndivs;$taxIdx++){
//Is individual part of family - i.e. what is parent_id
$options['fieldprefix'] = 'idn:'.$taxIdx.':';

 $subject_observation_id=data_entry_helper::$entity_to_load[$options['fieldprefix'].'subject_observation:id'];
if (isset(data_entry_helper::$entity_to_load[$options['fieldprefix'].'subject_observation:parent_id'])) {
	 $parent_id=data_entry_helper::$entity_to_load[$options['fieldprefix'].'subject_observation:parent_id'];
 } 
 else $parent_id='';

switch(data_entry_helper::$entity_to_load[$options['fieldprefix'].'subject_observation:subject_type_id']){
	case 113:
		if(!isset($families[$subject_observation_id]))$families[$subject_observation_id]=array();
		$families[$subject_observation_id]['family']=$taxIdx;
		break;
	case 116:
	default:
		if($parent_id !=''){// part of family
			if(!isset($families[$parent_id]))$families[$parent_id]=array('individuals'=>array());
			$families[$parent_id]['individuals'][$subject_observation_id]=$taxIdx;
		}
		else
		$singles[$subject_observation_id]=$taxIdx;

	}
}
    
    // recursive call to get a template for the 'individual panel' markup for a new observation so we can add another bird

$famoddeven='odd';
foreach($families as $family_sjoid=>$family){
$options["fieldprefix"] = 'idn:'.$family['family'].':';
$r.="Fieldprefix:$fieldprefix";
$r .= '<fieldset id="'.$options['fieldprefix'].'family:fieldset" class="taxon_family taxon_family_'.$famoddeven.' ui-corner-all">';
$r .= '<legend id="'.$options['fieldprefix'].'family:legend">Family details</legend>';
$r .= 'Show family associates';
$r .= self::rfj_show_associates($family_id);
$singoddeven='odd';
 foreach($family['individuals'] as $subject_observation_id=>$taxIdx){
  $r.="individual $subject_observation_id=>$taxIdx<br/>";
  $options["fieldprefix"] = 'idn:'.$taxIdx.':';
  $r.=self::rfj_individual($options,$taxIdx,$args,$tabalias,$auth,$singoddeven);
 $singoddeven=(($famoddeven=='odd')?'even':'odd');
 }
$r .= '<input type="button" id="idn:add-associate" class="ui-state-default ui-corner-all idn-add-associate" '.'value="'.lang::get("Add Another Bird\nto this family").'" /><br />';
 $r .= '</fieldset>';
 $famoddeven=(($famoddeven=='odd')?'even':'odd');
}


$singoddeven='odd';
foreach($singles as $single_id=>$taxIdx){
$options["fieldprefix"] = 'idn:'.$taxIdx.':';
$r.="Fieldprefix:$fieldprefix";
$r .= '<fieldset id="'.$options['fieldprefix'].'single:fieldset" class="taxon_single_'.$singoddeven.' ui-corner-all">';
$r .= '<legend id="'.$options['fieldprefix'].'family:legend">Singleton details</legend>';
$r .= 'Show individual associates';
$r .= self::rfj_show_associates($single_id);
 $r.=self::rfj_individual($options,$taxIdx,$args,$tabalias,$auth,$singoddeven);
$r .= '<input type="button" id="idn:add-associate" class="ui-state-default ui-corner-all idn-add-associate" '.'value="'.lang::get("Add Another Bird\nto this family").'" /><br />';
 $r .= '</fieldset>';
 $singoddeven=(($famoddeven=='odd')?'even':'odd');
}
// Store template and add one button to add new bird outside family
  if (!$options['inNewIndividual']) {	$r.=self::rfj_individual_template($options,$auth,$tabalias,$args,$opts);    }
    return $r;
  }


public function rfj_show_associates($subject_obs_id){
return "Showing unringed associates from $subject_obs_id";
}












  public function rfj_individual($options,$taxIdx,$args,$tabalias,$auth,$oddeven){
    $r = '<div id="'.$options['fieldprefix'].'individual:panel" class="individual_panel ui-corner-all">';
    $r.=self::rfj_individual_imagediv($taxIdx);
    $r .= '<div class="ui-helper-clearfix">';
    $r .= '<fieldset id="'.$options['fieldprefix'].'individual:fieldset" class="taxon_individual taxon_individual_'.$oddeven.' ui-corner-all">';
    $r .= '<legend id="'.$options['fieldprefix'].'individual:legend" class="individual_header">Individual details</legend>';
    // output the hiddens 
    $hidtbls=array('subject_observation'=>array('id','parent_id'),'occurrences_subject_observation'=>array('id'),'occurrence'=>array('id'));
    $r .= self::rfj_show_hiddens($hidtbls,$options);

    // Check if Record Status is included as a control. If not, then add it as a hidden.
    $arr = helper_base::explode_lines($args['structure']);
    if (!in_array('[record status]', $arr)) {
      if (isset(data_entry_helper::$entity_to_load[$options['fieldprefix'].'occurrence:record_status'])) {
        $value = data_entry_helper::$entity_to_load[$options['fieldprefix'].'occurrence:record_status'];
      } else {
        $value = isset($args['defaults']['occurrence:record_status']) ? $args['defaults']['occurrence:record_status'] : 'C'; 
      }
      $r .= '<input type="hidden" id="'.$options['fieldprefix'].'occurrence:record_status" '.
        'name="'.$options['fieldprefix'].'occurrence:record_status" value="'.$value.'" />'."\n";    
    }
// add subject type and count as a hidden
//$r.=data_entry_helper::$entity_to_load[$options['fieldprefix'].'subject_observation:unmarked_adults'];
    $value = '';
    if (isset(data_entry_helper::$entity_to_load[$options['fieldprefix'].'subject_observation:subject_type_id'])) {
      $value = data_entry_helper::$entity_to_load[$options['fieldprefix'].'subject_observation:subject_type_id'];
    } else if (isset($args['subject_type_id'])) {
      $value = $args['subject_type_id']; 
    }
    //
    if ($value!=='') {
      $r .= '<input type="text" id="'.$options['fieldprefix'].'subject_observation:subject_type_id" '.
        'name="'.$options['fieldprefix'].'subject_observation:subject_type_id" value="'.$value.'" />'."\n";
    }
    //
    if (isset(data_entry_helper::$entity_to_load[$options['fieldprefix'].'subject_observation:count'])) {
      $value = data_entry_helper::$entity_to_load[$options['fieldprefix'].'subject_observation:count'];
    } else  {
      $value = '1'; 
    }
    //
    if ($value!=='') {
      $r .= '<input type="hidden" id="'.$options['fieldprefix'].'subject_observation:count" '.
        'name="'.$options['fieldprefix'].'subject_observation:count" value="'.$value.'" />'."\n";
    }
    // output the species selection control
    $options['blankText'] = '<Please select>';
    $options['lockable'] = $options['identifiers_lockable'];
    if ($args['species_ctrl']=='autocomplete') {
      $temp = data_entry_helper::$javascript;
    }
    $r .= self::get_control_species($auth, $args, $tabalias, $options+array('validation' => array('required'), 'class' => 'select_taxon'));
    if ($args['species_ctrl']=='autocomplete') {
      if (!$options['inNewIndividual']) {
        $autoJavascript = substr(data_entry_helper::$javascript, strlen($temp));
      } else {
        data_entry_helper::$javascript = $temp;
      }
      unset($temp);
    } else {
      $autoJavascript = '';
    }
    unset($options['lockable']);
    
    // gender
    if ($options['genderId'] > 0
      && !empty($args['request_gender_values'])
      && count($args['request_gender_values']) > 0) {
      // filter the genders available
      $query = array('in'=>array('id', $args['request_gender_values']));
      $filter = array('query'=>json_encode($query),'orderby'=>'sort_order',);
      $extraParams = array_merge($filter, $auth['read']);
      $options['lockable'] = $options['identifiers_lockable'];
      $fieldname = $options['fieldprefix'].'sjoAttr:'.$options['genderId'];
      $idname = $fieldname;
      // if this attribute exists on DB, we need to append id to fieldname
      if (is_array(data_entry_helper::$entity_to_load)) {
        $stored_keys = preg_grep('/^'.$fieldname.':[0-9]+$/', array_keys(data_entry_helper::$entity_to_load));
        if (count($stored_keys)===1) {
          foreach ($stored_keys as $stored_key) {
            $fieldname = $stored_key;
          }
        }
      }
      $r .= data_entry_helper::select(array_merge(array(
        'label' => lang::get('Sex of the bird'),
        'fieldname' => $fieldname,
        'id' => $idname,
        'table'=>'termlists_term',
        'captionField'=>'term',
        'valueField'=>'id',
        'default' => $args['default_gender'],
        'extraParams' => $extraParams,
      ), $options));
      unset($options['lockable']);
    }
    // age
    if ($options['stageId'] > 0
      && !empty($args['request_stage_values'])
      && count($args['request_stage_values']) > 0) {
      // filter the stages available
      $query = array('in'=>array('id', $args['request_stage_values']));
      $filter = array('query'=>json_encode($query),'orderby'=>'sort_order',);
      $extraParams = array_merge($filter, $auth['read']);
      $options['lockable'] = $options['identifiers_lockable'];
      $fieldname = $options['fieldprefix'].'sjoAttr:'.$options['stageId'];
      $idname = $fieldname;
      // if this attribute exists on DB, we need to append id to fieldname
      if (is_array(data_entry_helper::$entity_to_load)) {
        $stored_keys = preg_grep('/^'.$fieldname.':[0-9]+$/', array_keys(data_entry_helper::$entity_to_load));
        if (count($stored_keys)===1) {
          foreach ($stored_keys as $stored_key) {
            $fieldname = $stored_key;
          }
        }
      }
      $r .= data_entry_helper::select(array_merge(array(
        'label' => lang::get('Age of the bird'),
        'fieldname' => $fieldname,
        'id' => $idname,
        'table'=>'termlists_term',
        'captionField'=>'term',
        'valueField'=>'id',
        'default' => $args['default_stage'],
        'extraParams' => $extraParams,
      ), $options));
      unset($options['lockable']);
    }
    // subject status
    if ($options['lifeStatusId'] > 0
      && !empty($args['request_life_status_values'])
      && count($args['request_life_status_values']) > 0) {
      // filter the life status's available
      $query = array('in'=>array('id', $args['request_life_status_values']));
      $filter = array('query'=>json_encode($query),'orderby'=>'sort_order',);
      $extraParams = array_merge($filter, $auth['read']);
      $options['lockable'] = $options['identifiers_lockable'];
      $fieldname = $options['fieldprefix'].'sjoAttr:'.$options['lifeStatusId'];
      $idname = $fieldname;
      // if this attribute exists on DB, we need to append id to fieldname
      if (is_array(data_entry_helper::$entity_to_load)) {
        $stored_keys = preg_grep('/^'.$fieldname.':[0-9]+$/', array_keys(data_entry_helper::$entity_to_load));
        if (count($stored_keys)===1) {
          foreach ($stored_keys as $stored_key) {
            $fieldname = $stored_key;
          }
        }
      }
      $r .= data_entry_helper::select(array_merge(array(
        'label' => lang::get('This bird was'),
        'fieldname' => $fieldname,
        'id' => $idname,
        'table'=>'termlists_term',
        'captionField'=>'term',
        'valueField'=>'id',
        'default' => $args['default_life_status'],
        'extraParams' => $extraParams,
      ), $options));
      unset($options['lockable']);
    }
   // Association ID
    if ($options['assocId'] > 0
      && !empty($args['request_assoc_id_values'])
      && count($args['request_assoc_id_values']) > 0
)
{
      $fieldname = $options['fieldprefix'].'sjoAttr:'.$options['assocId'];
      $idname = $fieldname;
      // if this attribute exists on DB, we need to append id to fieldname
      if (is_array(data_entry_helper::$entity_to_load)) {
        $stored_keys = preg_grep('/^'.$fieldname.':[0-9]+$/', array_keys(data_entry_helper::$entity_to_load));
        if (count($stored_keys)===1) {
          foreach ($stored_keys as $stored_key) {
            $fieldname = $stored_key;
          }
        }
      }
          $r .= data_entry_helper::text_input(array_merge(array(
            'label' => lang::get('Assoc id'),
            'fieldname' => $fieldname,
	    'id' => $idname,
	    'valueField'=>'id',
	    'default'=>0,		
          ), $options));
} 
   // Unringed Adults
    if ($options['unringedAdults'] > 0
      && !empty($args['request_unringed_adults_values'])
      && count($args['request_unringed_adults_values']) > 0
)
{
      $fieldname = $options['fieldprefix'].'sjoAttr:'.$options['assocId'];
      $idname = $fieldname;
      // if this attribute exists on DB, we need to append id to fieldname
      if (is_array(data_entry_helper::$entity_to_load)) {
        $stored_keys = preg_grep('/^'.$fieldname.':[0-9]+$/', array_keys(data_entry_helper::$entity_to_load));
        if (count($stored_keys)===1) {
          foreach ($stored_keys as $stored_key) {
            $fieldname = $stored_key;
          }
        }
      }
          $r .= data_entry_helper::text_input(array_merge(array(
            'label' => lang::get('Unringed Adults'),
            'fieldname' => $fieldname,
	    'id' => $idname,
	    'valueField'=>'id',
	    'default'=>0,		
          ), $options));
} 
self::setupIdentifiers($options,$r,$args,$taxIdx,$auth,$tabalias);    // output each required identifier

// other devices (trackers etc.)
if ($options['attachmentId'] > 0        && !empty($args['other_devices'])        && count($args['other_devices']) > 0) {
		$r.=self::rfj_other_devices($options,$taxIdx,$args,$auth)    ;    }
    
// subject_observation comment
if ($args['observation_comment']) {
      $r .= self::get_control_observationcomment($auth, $args, $tabalias, $options);
}
    // occurrence images
    $r.=self::rfj_image_upload($taxIdx,$tabalias,$options);
    $r .= '<input type="button" id="idn:0:remove-individual" class="idn-remove-individual" value="'.lang::get('Remove This Bird').'" />';
    
    // output identifier visualisations
    $r .= '</fieldset>';//Individuals fieldset
    $r .= '</div>'; // close clearfix div
    // remove bird button - don't show if bird is being edited or only bird on the form
       
    $r .= '</div>';


  return $r;
  }
  
  public function rfj_image_upload($taxIdx,$tabalias,$options){
    $opts = array(
      'table'=>'idn:'.$taxIdx.':'.'occurrence_image',
      'label'=>lang::get('Upload your photos'),
    );
    if ($args['interface']!=='one_page')
      $opts['tabDiv']=$tabalias;
    $opts['resizeWidth'] = isset($options['resizeWidth']) ? $options['resizeWidth'] : 1600;
    $opts['resizeHeight'] = isset($options['resizeHeight']) ? $options['resizeHeight'] : 1600;
    $opts['caption'] = lang::get('Photos');
    $opts['imageWidth'] = '168';
    $opts['id'] = 'idn:'.$taxIdx;
    if ($options['inNewIndividual']) {
      $opts['codeGenerated'] = 'php';
    }
    $r .= data_entry_helper::file_box($opts);
  return $r;
  }

  public function rfj_other_devices($options,$taxIdx,$args,$auth){
	$r='';  
      // reset prefix
      $options['fieldprefix'] = 'idn:'.$taxIdx.':';
      // filter the devices available
      $query = array('in'=>array('id', $args['other_devices']));
      $filter = array('query'=>json_encode($query),'orderby'=>'sort_order',);
      $extraParams = array_merge($filter, $auth['read']);
      $fieldname = $options['fieldprefix'].'sjoAttr:'.$options['attachmentId'];
      $default = array();
      // if this attribute exists on DB, we need to write a hidden with id appended to fieldname and set defaults for checkboxes
      if (is_array(data_entry_helper::$entity_to_load)) {
        $stored_keys = preg_grep('/^'.$fieldname.':[0-9]+$/', array_keys(data_entry_helper::$entity_to_load));
        foreach ($stored_keys as $stored_key) {
          $r .= '<input type="hidden" name="'.$stored_key.'" value="" />';
          $default[] = array('fieldname' => $stored_key, 'default' => data_entry_helper::$entity_to_load[$stored_key]);
          unset(data_entry_helper::$entity_to_load[$stored_key]);
        }
      }
      $r .= data_entry_helper::checkbox_group(array_merge(array(
        'label' => lang::get('What other devices did you see on the bird'),
        'fieldname' => $fieldname,
        'table'=>'termlists_term',
        'captionField'=>'term',
        'valueField'=>'id',
        'default'=>$default,
        'extraParams' => $extraParams,
      ), $options));
      return $r;
      }
  
  
  public function rfj_individual_template($options,$auth,$tabalias,$args,$opts){
//      $r = '</div>'; #  Here is the end of the accordian panel
###    $r .= '</fieldset>';// Family Panel
      $temp = data_entry_helper::$entity_to_load;
      data_entry_helper::$entity_to_load = null;
      $options['inNewIndividual'] = true;
      $options['lockable'] = $options['identifiers_lockable'];
      $new_individual = self::get_control_individuals($auth, $args, $tabalias, $options);
      unset($options['lockable']);
      $opts['codeGenerated'] = 'js';
      $photoJavascript = data_entry_helper::file_box($opts);
      data_entry_helper::$entity_to_load = $temp;
      unset($options['inNewIndividual']);
        
      data_entry_helper::$javascript .= "window.indicia.wwt.newIndividual = '".str_replace(array('\'', "\n"), array('\\\'', ' '), $new_individual)."';\n";
      // save the javascript needed for an additional colour-marked individual
      // process it to sanitise the string and remove comments (works now but not 100% reliable)
      data_entry_helper::$javascript .= "window.indicia.wwt.newJavascript = '"
        .str_replace(array('\'', "\n"), array('\\\'', ' '), str_replace('\\', '\\\\', preg_replace('#^\s*//.+$#m', '', $photoJavascript)))
        .str_replace(array('\'', "\n", "\r"), array('\\\'', ' ', ' '), str_replace('\\', '\\\\', preg_replace('#^\s*//.+$#m', '', $autoJavascript)))."';\n";
//      $r .= '</fieldset>';  
      $r .= '<input type="button" id="idn:add-another" class="ui-state-default ui-corner-all" '
        .'value="'.lang::get('Add Another Bird at the Same Date and Location').'" />';  
        return $r;
  }
  
  
  
  
  
  
  
  /*
   * Get the colour identifier control
   */
  
  protected static function get_control_identifier($auth, $args, $tabalias, $options) {
    #creates a new accordion panel for an identifier
    #fieldprefix is the bird number for the form - i.e. starts at 0
    $fieldPrefix = !empty($options['fieldprefix']) ? $options['fieldprefix'] : '';
    $r = '';
    $r .= '<h3 id="'.$fieldPrefix.'header" class="idn:accordion:header"><a href="#">'.$options['identifierName'].'</a></h2>';
    $r .= '<div id="'.$fieldPrefix.'panel" class="idn:accordion:panel">';
    $r .= '<input type="hidden" name="'.$fieldPrefix.'identifier:identifier_type_id" value="'.$options['identifierTypeId'].'" />'."\n";
    $r .= '<input type="hidden" name="'.$fieldPrefix.'identifier:coded_value" id="'.$fieldPrefix.'identifier:coded_value" class="identifier_coded_value" value="" />'."\n";
    $val = isset(data_entry_helper::$entity_to_load[$fieldPrefix.'identifier:id']) ? data_entry_helper::$entity_to_load[$fieldPrefix.'identifier:id'] : '0';
    $r .= '<input type="hidden" name="'.$fieldPrefix.'identifier:id" id="'.$fieldPrefix.'identifier:id" class="identifier_id" value="'.$val.'" />'."\n";
    if (isset(data_entry_helper::$entity_to_load[$fieldPrefix.'identifiers_subject_observation:id'])) {
      $r .= '<input type="hidden" id="'.$fieldPrefix.'identifiers_subject_observation:id" name="'.$fieldPrefix.'identifiers_subject_observation:id" '.
        'value="'.data_entry_helper::$entity_to_load[$fieldPrefix.'identifiers_subject_observation:id'].'" />'."\n";    
    }
    
    // checkbox - (now hidden by CSS, probably should refactor to hidden input?)
    $r .= data_entry_helper::checkbox(array_merge(array(
      // 'label' => lang::get('Is this identifier being recorded?'),
      'label' => '',
      'fieldname' => $fieldPrefix.'identifier:checkbox',
      'class'=>'identifier_checkbox identifierRequired noDuplicateIdentifiers',
    ), $options));
      
    // loop through the requested attributes and output an appropriate control
    $classes = $options['class'];
    foreach ($options['attrList'] as $attribute) {
      // find the definition of this attribute
      $found = false;
      if ($attribute['attrType']==='idn') {
        foreach ($options['idnAttributeTypes'] as $attrType) {
          if ($attrType['id']===$attribute['typeId']) { // Is allowable attribute
            $found = true;
            break;
          }
        }
      } else if ($attribute['attrType']==='iso') {
        foreach ($options['isoAttributeTypes'] as $attrType) {
          if ($attrType['id']===$attribute['typeId']) {
            $found = true;
            break;
          }
        }
      }
      if (!$found) {
        throw new exception(lang::get('Unknown '.$attribute['attrType'].' attribute type id ['.$attribute['typeId'].'] specified for '.
          $options['identifierName'].' in Identifier Attributes array.'));
      }
      // setup any locking
      if (!empty($attribute['lockable']) && $attribute['lockable']===true) {
        $options['lockable'] = $options['identifiers_lockable'];
      }
      // setup any data filters
      if ($attribute['attrType']==='idn' && $options['baseColourId']==$attribute['typeId']) {
        if (!empty($args['base_colours'])) {
//                  $r.=print_r($attribute,true);
//                  $r.=print_r($args['baseColourId'],true);
          // filter the colours available
          $query = array('in'=>array('id', $args['base_colours']));
        }
        $attr_name = 'base-colour';
      } elseif ($attribute['attrType']==='idn' && $options['textColourId']==$attribute['typeId']) {
//                  $r.=print_r($attribute,true);
//                  $r.=print_r($args['textColourId'],true);
        if (!empty($args['text_colours'])) {
          // filter the colours available
          $query = array('in'=>array('id', $args['text_colours']));
        }
        $attr_name = 'text-colour';
      } elseif ($attribute['attrType']==='idn' && $options['positionId']==$attribute['typeId']) {
      $options['class'] = strstr($options['class'], 'select_position') ? $options['class'] : $options['class'].' select_position';

//                  $r.=print_r($attribute,true);
//                  $r.=print_r($args['position'],true);
        $attr_name = 'position';
        if (count($args['position']) > 0) {
          // filter the identifier position available
          $query = array('in'=>array('id', $args['position']));
        }
      } elseif ($attribute['attrType']==='idn' && $options['sequenceId']==$attribute['typeId']) {
        $attr_name = 'sequence';
        $options['maxlength'] = $options['seq_maxlength'] ? $options['seq_maxlength'] : '';
        if ($options['seq_format_class']) {
          $options['class'] = empty($options['class']) ? $options['seq_format_class'] : 
            (strstr($options['class'], $options['seq_format_class']) ? $options['class'] : $options['class'].' '.$options['seq_format_class']);
        }
      } elseif ($attribute['attrType']==='iso' && $options['conditionsId']==$attribute['typeId']) {
        // filter the identifier conditions available
        if ($options['identifierTypeId']==$args['neck_collar_type'] && !empty($args['neck_collar_conditions'])) {
          $query = array('in'=>array('id', $args['neck_collar_conditions']));
        } elseif ($options['identifierTypeId']==$args['enscribed_colour_ring_type'] && !empty($args['coloured_ring_conditions'])) {
          $query = array('in'=>array('id', $args['coloured_ring_conditions']));
        } elseif ($options['identifierTypeId']==$args['metal_ring_type'] && !empty($args['metal_ring_conditions'])) {
          $query = array('in'=>array('id', $args['metal_ring_conditions']));
        }
        $attr_name = 'conditions';
      }

      // add classes as identifiers
      $options['class'] = empty($options['class']) ? $options['classprefix'].$attr_name : 
        (strstr($options['class'], $options['classprefix'].$attr_name) ? $options['class'] : $options['class'].' '.$options['classprefix'].$attr_name);
      $options['class'] = $options['class'].' idn-'.$attr_name;
      if ($attribute['attrType']==='idn' && ($options['baseColourId']==$attribute['typeId'] || $options['textColourId']==$attribute['typeId'])) {
        $options['class'] = strstr($options['class'], 'select_colour') ? $options['class'] : $options['class'].' select_colour';
        $options['class'] = strstr($options['class'], '
        textAndBaseMustDiffer') ? $options['class'] : $options['class'].' textAndBaseMustDiffer';
      }
      if ($attribute['attrType']==='idn' && $options['sequenceId']==$attribute['typeId']) {
        $options['class'] = strstr($options['class'], 'identifier_sequence') ? $options['class'] : $options['class'].' identifier_sequence';
      }
    
      if (!empty($attribute['hidden']) && $attribute['hidden']===true) {
        $dataType = 'H'; // hidden
        if (!empty($attribute['hiddenValue'])) {
          $dataDefault = $attribute['hiddenValue'];
        } else {
          $dataDefault = '';
        }
      } else {
        $dataType = $attrType['data_type'];
      }
      
      // output an appropriate control for the attribute data type
      switch ($dataType) {
        case 'D': //Date
        case 'V':
          $r .= data_entry_helper::date_picker(array_merge(array(
            'label' => lang::get($attrType['caption']),
            'fieldname' => $fieldPrefix.$attribute['attrType'].'Attr:'.$attrType['id'],
          ), $options));
          break;
        case 'L': // Lookup
          $filter = array('termlist_id'=>$attrType['termlist_id'],);
          if (!empty($query)) {
            $filter += array('query'=>json_encode($query),);
          }
          $extraParams = array_merge($filter, $auth['read']);
          if ($attribute['attrType']==='iso' && $options['conditionsId']==$attribute['typeId']) {
            $fieldname = $fieldPrefix.$attribute['attrType'].'Attr:'.$attrType['id'];
            $default = array();
            // if this attribute exists on DB, we need to write a hidden with id appended to fieldname and set defaults for checkboxes
            if (is_array(data_entry_helper::$entity_to_load)) {
              $stored_keys = preg_grep('/^'.$fieldname.':[0-9]+$/', array_keys(data_entry_helper::$entity_to_load));
              foreach ($stored_keys as $stored_key) {
                $r .= '<input type="hidden" name="'.$stored_key.'" value="" />';
                $default[] = array('fieldname' => $stored_key, 'default' => data_entry_helper::$entity_to_load[$stored_key]);
                unset(data_entry_helper::$entity_to_load[$stored_key]);
              }
            }
            $r .= data_entry_helper::checkbox_group(array_merge(array(
              'label' => lang::get($attrType['caption']),
              'fieldname' => $fieldname,
              'table'=>'termlists_term',
              'captionField'=>'term',
              'valueField'=>'id',
              'default'=>$default,
              'extraParams' => $extraParams,
            ), $options));
          } else {
            $r .= data_entry_helper::select(array_merge(array(
              'label' => lang::get($attrType['caption']),
              'fieldname' => $fieldPrefix.$attribute['attrType'].'Attr:'.$attrType['id'],
              'table'=>'termlists_term',
              'captionField'=>'term',
              'valueField'=>'id',
              'blankText' => '<Please select>',
              'extraParams' => $extraParams,
            ), $options));
          }
          break;
        case 'B': //checkbox
          $r .= data_entry_helper::checkbox(array_merge(array(
            'label' => lang::get($attrType['caption']),
            'fieldname' => $fieldPrefix.$attribute['attrType'].'Attr:'.$attrType['id'],
          ), $options));
          break;
        case 'H': //hidden
          // Any multi-value attributes shown as hidden will be single-valued
          // so transform the array to a scalar
          $fieldname = $fieldPrefix.$attribute['attrType'].'Attr:'.$attrType['id'];
          if (!empty(data_entry_helper::$entity_to_load[$fieldname])
            && is_array(data_entry_helper::$entity_to_load[$fieldname])) {
            data_entry_helper::$entity_to_load[$fieldname]
              = data_entry_helper::$entity_to_load[$fieldname][0];
          }
          $r .= data_entry_helper::hidden_text(array_merge(array(
            'fieldname' => $fieldname,
            'default' => $dataDefault,
          ), $options));
          break;
        default: //text input
          $r .= data_entry_helper::text_input(array_merge(array(
            'label' => lang::get($attrType['caption']),
            'fieldname' => $fieldPrefix.$attribute['attrType'].'Attr:'.$attrType['id'],
          ), $options));
      }
      $options['class'] = $classes;
      if (isset($options['maxlength'])) {
        unset($options['maxlength']);
      }
      if (isset($options['lockable'])) {
        unset($options['lockable']);
      }
    }
    $r .= '</div>';

    return $r;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    // set warehouse auth settings from input so we can query database
    self::$auth = array(
      'write' => null,
      'read' => array(
        'auth_token' => $values['read_auth_token'],
        'nonce' => $values['read_nonce']
      ),
      'write_tokens' => array(
        'auth_token' => $values['auth_token'],
        'nonce' => $values['nonce']
      ),
    );
    // remove these or they pollute all the submission models
    unset($values['read_auth_token']);
    unset($values['read_nonce']);
    $ol_keys = preg_grep('/^OpenLayers_/', array_keys($values));
    foreach ($ol_keys as $ol_key) {
      unset($values[$ol_key]);
    }
    // build a sample submission
    $submission = submission_builder::build_submission($values, array('model'=>'sample',));
    // add observation/occurrence and identifier data to sample in submission
    $submission = self::add_observation_submissions($submission, $values, $args);
    // add new sample comment
    $submission = self::add_sample_comment_submissions($submission, $values);
    
    if (isset($args['debug_info']) && $args['debug_info']) {
      self::$submission = $submission;
    }
    return($submission);
  }
  
  /**
   * Adds the sample comment data to the submission array from the form values.
   * @param array $sample The sample submission. 
   * @param array $values Associative array of form data values. 
   * @return array Submission structure with the sample comment added.
   */
  private static function add_sample_comment_submissions($sample, $values) {
    if (array_key_exists('sample_comment:comment', $values) && $values['sample_comment:comment']!=='') {
      // add new sample comment
      $sample_comment = submission_builder::build_submission($values, array('model'=>'sample_comment',));
      // add to the main sample submission
      $sample['subModels'][] = array('fkId' => 'sample_id', 'model' => $sample_comment);
    }
    return $sample;
  }
  
  /**
   * Adds the observation data and identifiers (if new) to the submission array from the form values.
   * @param array $sample The sample submission. 
   * @param array $values Associative array of form data values. 
   * @param array $args Associative array of form configuration parameters. 
   * @return array Submission structure with observations/identifiers added.
   */
  private static function add_observation_submissions($sample, $values, $args) {
#print_r($values);
    // get identifier ids for any stored identifiers which match the submitted identifier codes
    $ident_code_keys = preg_grep('/^idn:[0-9]+:[^:]+:identifier:coded_value$/', array_keys($values));
    $codes = array();
    foreach ($ident_code_keys as $ident_code_key) {
      $code = $values[$ident_code_key];
      if ($code !== '') {
        $codes[] = $code;
      }
    }
    $matches = array();
    if (count($codes)>0) {
      $query = array('in'=>array('coded_value', $codes));
      $filter = array('query'=>json_encode($query),);
      $queryOptions = array(
        'table' => 'identifier',
        'extraParams' => self::$auth['read'] + $filter,
        'nocache' => true,
      );
      $matches = data_entry_helper::get_population_data($queryOptions);
    }
    
    // get submission for each observation and add it to the sample submission
    $keys = preg_grep('/^idn:[0-9]+:occurrence:taxa_taxon_list_id$/', array_keys($values));
    foreach ( $keys as $key )
    {
      // build the observation submission
      $key_parts = explode(':', $key);
      $idx = $key_parts[1];
      $so_keys = preg_grep('/^idn:'.$idx.':(subject_observation|occurrence|occurrence_image|occurrences_subject_observation|occAttr|sjoAttr):/', array_keys($values));
      foreach ($so_keys as $so_key) {
        $so_key_parts = explode(':', $so_key, 3);
        $values[$so_key_parts[2]] = $values[$so_key];
      }
      $so = submission_builder::build_submission($values, array('model'=>'subject_observation',));
      // create submodel for join to occurrence and add it
      $oso = self::build_occurrence_observation_submission($values);
      $so['subModels'][] = array('fkId' => 'subject_observation_id', 'model' => $oso,);      
      // create submodel for each join to identifier (plus identifier models if new) and add it
      foreach (array('neck-collar', 'colour-ring', 'colour-right', 'metal') as $identifier_type) {
        $ident_keys = preg_grep('/^idn:'.$idx.':'.$identifier_type.':(identifier|identifiers_subject_observation|idnAttr|isoAttr):/', array_keys($values));
        foreach ($ident_keys as $i_key) {
          $i_key_parts = explode(':', $i_key, 4);
          $values[$i_key_parts[3]] = $values[$i_key];
        }
        // if identifier checkbox set, this identifier is being reported. If id > 0, this identifier exists.
        if ($values['identifier:checkbox']==1 || $values['identifier:id']!=='0') {
          $so = self::build_identifier_observation_submission($values, $matches, $so);
        }
        // clean up the flattened keys
        foreach ($ident_keys as $i_key) {
          $i_key_parts = explode(':', $i_key, 4);
          unset($values[$i_key_parts[3]]);
        }
      }
      // clean up the flattened subject_observation keys
      foreach ($so_keys as $so_key) {
        $so_key_parts = explode(':', $so_key, 3);
        unset($values[$so_key_parts[2]]);
      }
      // add it all to the main sample submission
      $sample['subModels'][] = array('fkId' => 'sample_id', 'model' => $so,);
    }
    return $sample;
  }
    
  /**
   * Builds a submission for occurrences_subject_observation join data from the form values.
   * @param array $values Associative array of form data values. 
   * @return array occurences_subject_observation Submission structure.
   */
  private static function build_occurrence_observation_submission($values) {
    // provide defaults if these keys not present
    $values = array_merge(array(
      ), $values);
    
    // build submission
    $submission = submission_builder::build_submission($values, array('model'=>'occurrences_subject_observation',));
      
    // add super model for occurrence
    // provide defaults if these keys not present
    $values = array_merge(array(
      'occurrence:sample_id' => 0, // place holder, this will be populated in subject_observation model
      ), $values);

    // build submission
    $occ =  submission_builder::build_submission($values, array('model'=>'occurrence',));
    $submission['superModels'] = array(
      array('fkId' => 'occurrence_id', 'model' => $occ,),
    );
    
    return $submission;
  }
  
  /**
   * Builds a submission for identifiers_subject_observation join data 
   * from the form values. Also adds identifier if it doesn't exist.
   * @param array $values Associative array of form data values. 
   * @param array $matches Associative array of stored identifiers which match submitted values. 
   * @param array $so The subject_observation submission we are adding to
   * @return array subject_observation Submission structure with identifier data added.
   */
  private static function build_identifier_observation_submission($values, $matches, $so) {
    // work out what to do, insert?, update? delete?
    $set = $values['identifier:checkbox']==1;
    $code = $values['identifier:coded_value'];
    $old_id = (integer)$values['identifier:id'];
    $new_id = 0;
    $identifier_status = 'U';
    foreach ($matches as $match) {
      if ($match['coded_value']===$code) {
        $new_id = (integer)$match['id'];
        $identifier_status = $match['status'];
      }
    }
    
    // see if we have any updates on the isoAttr
    $isoAttrUpdated = count(preg_grep('/^isoAttr:[0-9]+$/', array_keys($values))) > 0;
    if (!$isoAttrUpdated) {
      $keys = preg_grep('/^isoAttr:[0-9]+:[0-9]+$/', array_keys($values));
      foreach ($keys as $key) {
        if ($values[$key]==='') {
          $isoAttrUpdated = true;
          break;
        }
      }
    }
      
    // this identifier exists but its identity has been changed
    if ($old_id>0 && $old_id!==$new_id) {
      // unlink the old identifier
      $values['identifiers_subject_observation:deleted'] = 't';
      $iso = submission_builder::build_submission(
        $values, array('model'=>'identifiers_subject_observation',));
      $so['subModels'][] = array('fkId' => 'subject_observation_id', 'model' => $iso,);
    }
    
    // identifier submitted, has been edited and matches an existing identifier
    if ($set && $new_id>0 && $old_id!==$new_id) {
      // create link to the new matching identifier
      unset($values['identifiers_subject_observation:id']);
      $values['identifiers_subject_observation:identifier_id'] = $new_id;
      $values['identifiers_subject_observation:matched'] = $identifier_status!=='U' ? 't' : 'f';
      unset($values['identifiers_subject_observation:verified_status']);
      unset($values['identifiers_subject_observation:verified_by_id']);
      unset($values['identifiers_subject_observation:verified_on']);
      unset($values['identifiers_subject_observation:created_on']);
      unset($values['identifiers_subject_observation:created_by_id']);
      unset($values['identifiers_subject_observation:updated_on']);
      unset($values['identifiers_subject_observation:updated_by_id']);
      unset($values['identifiers_subject_observation:deleted']);
      $iso = submission_builder::build_submission(
        $values, array('model'=>'identifiers_subject_observation',));
      $so['subModels'][] = array('fkId' => 'subject_observation_id', 'model' => $iso,);
    }
    
    // identifier submitted and doesn't match an existing identifier
    if ($set && $new_id===0) {
      // create new link to a new identifier which we also create here
      unset($values['identifiers_subject_observation:id']);
      unset($values['identifiers_subject_observation:identifier_id']);
      $values['identifiers_subject_observation:matched'] = 'f';
      unset($values['identifiers_subject_observation:verified_status']);
      unset($values['identifiers_subject_observation:verified_by_id']);
      unset($values['identifiers_subject_observation:verified_on']);
      unset($values['identifiers_subject_observation:created_on']);
      unset($values['identifiers_subject_observation:created_by_id']);
      unset($values['identifiers_subject_observation:updated_on']);
      unset($values['identifiers_subject_observation:updated_by_id']);
      unset($values['identifiers_subject_observation:deleted']);
      $iso = submission_builder::build_submission(
        $values, array('model'=>'identifiers_subject_observation',));
      // now add the identifier
      unset($values['identifier:id']);
      unset($values['identifier:issue_authority_id']);
      unset($values['identifier:issue_scheme_id']);
      unset($values['identifier:issue_date']);
      unset($values['identifier:first_use_date']);
      unset($values['identifier:last_observed_date']);
      unset($values['identifier:final_date']);
      unset($values['identifier:summary']);
      unset($values['identifier:status']);
      unset($values['identifier:verified_by_id']);
      unset($values['identifier:verified_on']);
      unset($values['identifier:known_subject_id']);
      unset($values['identifier:created_on']);
      unset($values['identifier:created_by_id']);
      unset($values['identifier:updated_on']);
      unset($values['identifier:updated_by_id']);
      unset($values['identifier:deleted']);
      $i =  submission_builder::build_submission($values, array('model'=>'identifier',));
      $iso['superModels'] = array(
        array('fkId' => 'identifier_id', 'model' => $i,),
      );
      $so['subModels'][] = array('fkId' => 'subject_observation_id', 'model' => $iso,);
    }
    
    // identifier exists and is unchanged but has iso attributes which have changed
    if ($old_id>0 && $old_id===$new_id && $isoAttrUpdated) {
      // update link to trigger update to isoAttr
      $iso = submission_builder::build_submission(
        $values, array('model'=>'identifiers_subject_observation',));
      $so['subModels'][] = array('fkId' => 'subject_observation_id', 'model' => $iso,);
    }
    
    return $so;
  }
  
  /**
   * Retrieves a list of the css files that this form requires in addition to the standard
   * Drupal, theme or Indicia ones.
   * 
   * @return array List of css files to include for this form.
   */
  public static function get_css() {
    return array();
  }
  
  /**
   * Returns true if this form should be displaying a multiple subject observation entry grid.
   */
  protected static function getGridMode($args) {
    // if loading an existing sample and we are allowed to display a grid or single species selector
    if ($args['multiple_subject_observation_mode']=='either') {
      // Either we are in grid mode because we were instructed to externally, or because the form is reloading
      // after a validation failure with a hidden input indicating grid mode.
      return isset($_GET['gridmode']) || 
          isset(data_entry_helper::$entity_to_load['gridmode']) ||
          ((array_key_exists('sample_id', $_GET) && $_GET['sample_id']!='{sample_id}') &&
           (!array_key_exists('subject_observation_id', $_GET) || $_GET['subject_observation_id']=='{subject_observation_id}'));
    } else
      return 
          // a form saved using a previous version might not have this setting, so default to grid mode=true
          (!isset($args['multiple_subject_observation_mode'])) ||
          // Are we fixed in grid mode?
          $args['multiple_subject_observation_mode']=='multi';
  }
  
  /**
   * When viewing the list of samples for this user, get the grid to insert into the page.
   */
  protected static function getSampleListGrid($args, $node, $auth, $attributes) {
    global $user;
    // get the CMS User ID attribute so we can filter the grid to this user
    /*
    foreach($attributes as $attrId => $attr) {
      if (strcasecmp($attr['caption'],'CMS User ID')==0) {
        $userIdAttr = $attr['attributeId'];
        break;
      }
    }
    */
    if ($user->uid===0) {
      // Return a login link that takes you back to this form when done.
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">login</a> to the website.');
    }
    // use drupal profile to get warehouse user id
    if (function_exists('profile_load_profile')) {
      profile_load_profile($user);
      $userId = $user->profile_indicia_user_id;
    }
    if (!isset($userId)) {
      return lang::get('This form must be used with the indicia \'Easy Login\' module so records can '.
          'be tagged against the warehouse user id.');
    }
    if (isset($args['grid_report']))
      $reportName = $args['grid_report'];
    else
      // provide a default in case the form settings were saved in an old version of the form
      $reportName = 'reports_for_prebuilt_forms/simple_subject_observation_identifier_list_1';
    if(method_exists(get_called_class(), 'getSampleListGridPreamble'))
      $r = call_user_func(array(get_called_class(), 'getSampleListGridPreamble'));
    else
      $r = '';
    $grid= data_entry_helper::report_grid(array(
      'id' => 'samples-grid',
      'dataSource' => $reportName,
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => call_user_func(array(get_called_class(), 'getReportActions')),
      'itemsPerPage' =>(isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 10),
      'autoParamsForm' => true,
      'extraParams' => array(
        'survey_id'=>$args['survey_id'], 
        'userID'=>$userId,
      )
    ));    
    
/*
How about colouring the ring info?
###    <td class="data codes">LBM(XXXXX);LBGW(YYY);NCBW(ABC)</td>
*/


    $r.=self::colourgrid($grid);
//    $r.=$grid; // add normal grid
    $r .= '<form>';    
    if (isset($args['multiple_subject_observation_mode']) && $args['multiple_subject_observation_mode']=='either') {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Single').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Grid').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample&gridmode')).'\'">';
    } else {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';    
    }
    $r .= '</form>';
    return $r;
  }
  
  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the 
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
 
  protected function getReportActions() {
    return array(array('display' => 'Actions', 'actions' => 
        array(array('caption' => lang::get('Edit'), 'url'=>'{currentUrl}', 'urlParams'=>array('sample_id'=>'{sample_id}','subject_observation_id'=>'{subject_observation_id}')))));
  }
  
  /*
   * helper function to return a proxy-aware warehouse url
   */
  protected function warehouseUrl() {
    return !empty(data_entry_helper::$warehouse_proxy) ? data_entry_helper::$warehouse_proxy : data_entry_helper::$base_url;
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */

  public static function get_parameters() {    
  ### Problem with merging here is that duplicates are appended, not merged, due to numeric keys - these can only be additional parameters!!!
    $retVal = array_merge(
    parent::get_parameters(),
      array(
          array(
          'name'=>'save_button_below_all_pages',
          'caption'=>'Save button below all pages?',
          'description'=>'Should the save button be present below all the pages (checked), or should it be only on the last page (unchecked)? '.
              'Only applies to the Tabs interface style.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name' => 'subject_type_id',
          'caption' => 'Subject Type',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:subject_type'),
          'required' => true,
          'helpText' => 'The subject type that will be used for created subject observations for each colour-marked individual.'
        ),
        array(
          'name'=>'neck_collar_type',
          'caption'=>'Neck Collar Type',
          'description'=>'The type of identifier which indicates a neck collar.',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_type'),
          'required' => true,
          'helpText' => 'The helptext. Todo: change this once you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'neck_collar_position',
          'caption'=>'Neck Collar Position',
          'description'=>'The body position to record for a neck collar.',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_position','orderby'=>'sort_order'),
          'required' => true,
          'helpText' => 'The helptext. Todo: change this once you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'neck_collar_max_length',
          'caption'=>'Neck collar maximum length',
          'description'=>'Maximum length for a neck-collar identifier sequence.',
          'type'=>'string',
          'required' => false,
          'group'=>'Identifiers'
        ),
        array(
          'name'=>'neck_collar_regex',
          'caption'=>'Neck collar validation pattern',
          'description'=>'The validation pattern (as a regular expression) for a neck-collar identifier sequence. '.
              'Eg. ^([A-Z]{2}[0-9]{2}|[A-Z]{3}[0-9])$ would only permit sequences of either 2 uppercase letters followed by 2 digits, '.
              'or 3 uppercase letters followed by 1 digit.',
          'type'=>'string',
          'required' => false,
          'group'=>'Identifiers'
        ),
        array(
          'name'=>'enscribed_colour_ring_type',
          'caption'=>'Enscribed Colour Ring Type',
          'description'=>'The type of identifier which indicates an enscribed colour ring (\'darvic\').',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_type'),
          'required' => true,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'right_enscribed_colour_ring_position',
          'caption'=>'Right Leg Enscribed Colour Ring Position',
          'description'=>'The body position to record for an enscribed colour ring (\'darvic\') on the right leg.',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_position','orderby'=>'sort_order'),
          'required' => true,
          'helpText' => 'The helptext. Todo: change this once you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'left_enscribed_colour_ring_position',
          'caption'=>'Left Leg Enscribed Colour Ring Position',
          'description'=>'The body position to record for an enscribed colour ring (\'darvic\') on the left leg.',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_position','orderby'=>'sort_order'),
          'required' => true,
          'helpText' => 'The helptext. Todo: change this once you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'enscribed_colour_ring_max_length',
          'caption'=>'Colour ring maximum length',
          'description'=>'Maximum length for an enscribed colour ring identifier sequence.',
          'type'=>'string',
          'required' => false,
          'group'=>'Identifiers'
        ),
        array(
          'name'=>'enscribed_colour_ring_regex',
          'caption'=>'Colour ring validation pattern',
          'description'=>'The validation pattern (as a regular expression) for an enscribed colour ring identifier sequence. '.
              'Eg. ^([A-Z]{2}[0-9]{2}|[A-Z]{3}[0-9])$ would only permit sequences of either 2 uppercase letters followed by 2 digits, '.
              'or 3 uppercase letters followed by 1 digit.',
          'type'=>'string',
          'required' => false,
          'group'=>'Identifiers'
        ),
        array(
          'name'=>'metal_ring_type',
          'caption'=>'Metal Ring Type',
          'description'=>'The type of identifier which indicates a metal ring.',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_type'),
          'required' => true,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'metal_ring_position',
          'caption'=>'Metal Ring Position',
          'description'=>'The body position to record for a metal ring.',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_position','orderby'=>'sort_order'),
          'required' => true,
          'helpText' => 'The helptext. Todo: change this once you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'metal_ring_max_length',
          'caption'=>'Metal ring maximum length',
          'description'=>'Maximum length for a metal identifier sequence.',
          'type'=>'string',
          'required' => false,
          'group'=>'Identifiers'
        ),
        array(
          'name'=>'metal_ring_regex',
          'caption'=>'Metal ring validation pattern',
          'description'=>'The validation pattern (as a regular expression) for a metal ring identifier sequence. '.
              'Eg. ^([A-Z]{2}[0-9]{2}|[A-Z]{3}[0-9])$ would only permit sequences of either 2 uppercase letters followed by 2 digits, '.
              'or 3 uppercase letters followed by 1 digit.',
          'type'=>'string',
          'required' => false,
          'group'=>'Identifiers'
        ),
        array(
          'name'=>'base_colours',
          'caption'=>'Base Colours',
          'description'=>'The colours we want to let users record for the background of the coloured identifiers. Tick all that apply.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:ring_colour'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'text_colours',
          'caption'=>'Text Colours',
          'description'=>'The colours we want to let users record for the text enscribed on the coloured identifiers. Tick all that apply.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:ring_colour'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'position',
          'caption'=>'Identifier Position',
          'description'=>'The positions on the organism we want to let users record for the identifiers. Tick all that apply.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_position','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'default_leg_vertical',
          'caption'=>'Default Position on Leg',
          'description'=>'If you are not specifying if a leg mark is above or below the \'knee\' in the above choices, '.
             'you can optionally specify a default position here.',
          'type'=>'select',
          'options' => array(
            '?' => 'No Default',
            'A' => 'Above the \'Knee\'',
            'B' => 'Below the \'Knee\'',
          ),
          'required'=>false,
          'group'=>'Identifiers'
        ),
        array(
          'name'=>'neck_collar_conditions',
          'caption'=>'Neck Collar Conditions',
          'description'=>'The identifier conditions we want to be reportable by recorders when observing a neck collar. Tick all that apply.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_condition','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'coloured_ring_conditions',
          'caption'=>'Coloured Ring Conditions',
          'description'=>'The identifier conditions we want to be reportable by recorders when observing a coloured ring Tick all that apply.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_condition','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'metal_ring_conditions',
          'caption'=>'Metal Ring Conditions',
          'description'=>'The identifier conditions we want to be reportable by recorders when observing a metal ring Tick all that apply.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:identifier_condition','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Identifiers',
        ),
        array(
          'name'=>'other_devices',
          'caption'=>'Other Devices',
          'description'=>'What other devices (such as transmitters/trackers/loggers do you want to record? Tick all that apply.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:attachment_type'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Subject observation',
        ),
        array(
          'name'=>'observation_comment',
          'caption'=>'Allow Comment For Colour-marked Individual',
          'description'=>'Tick this to allow a comment to be input for each reported colour-marked individual. '.
            'This comment is stored on the subject observation record',
          'type'=>'boolean',
          'required' => false,
          'default' => false,
          'group' => 'Subject observation'
        ),
        array(
          'name'=>'request_gender_values',
          'caption'=>'Request Gender Values',
          'description'=>'What (if any) gender options do you want to present for the colour-marked individual? '.
            'Leave un-ticked to hide all gender options.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:gender','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Subject observation',
        ),
        array(
          'name'=>'default_gender',
          'caption'=>'Default Gender',
          'description'=>'What (if any) gender should be the default for the colour-marked individual?',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'blankText'=>'No default',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:gender','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Subject observation',
        ),
        array(
          'name'=>'request_stage_values',
          'caption'=>'Request Age Values',
          'description'=>'What (if any) age/stage options do you want to present for the colour-marked individual? '.
            'Leave un-ticked to hide all age options.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:stage','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Subject observation',
        ),
        array(
          'name'=>'default_stage',
          'caption'=>'Default Age',
          'description'=>'What (if any) age/stage should be the default for the colour-marked individual?',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'blankText'=>'No default',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:stage','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Subject observation',
        ),
        array(
          'name'=>'request_life_status_values',
          'caption'=>'Request Subject Status Values',
          'description'=>'What (if any) life status options do you want to present for the colour-marked individual? '.
            'Leave un-ticked to hide all life status options.',
          'type'=>'checkbox_group',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:life_status','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Subject observation',
        ),
        array(
          'name'=>'default_life_status',
          'caption'=>'Default Subject Status',
          'description'=>'What (if any) subject status should be the default for the colour-marked individual?',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'blankText'=>'No default',
          'extraParams' => array('termlist_external_key'=>'indicia:assoc:life_status','orderby'=>'sort_order'),
          'required' => false,
          'helpText' => 'The helptext. Todo: change this one you see where it shows on screen!!',
          'group' => 'Subject observation',
        ),
        array(
          'name'=>'debug_info',
          'caption'=>'Provide debug information',
          'description'=>'Tick this to provide debug info on the form, DO NOT USE IN PRODUCTION!!!!',
          'type'=>'boolean',
          'required' => false,
          'default' => false,
          'group' => 'Debug'
        ),
      )
    );
    return $retVal;
  }
  

   public function setupIdentifiers(&$options,&$r,$args,$taxIdx,$auth,$tabalias) {   
    // output each required identifier
    $r .= '<div id="'.$options['fieldprefix'].'accordion" class="idn-accordion">';

self::displayIdent($r,$options,$auth, $args, $tabalias,"neck_collar","neck-collar","collar",$taxIdx,'Neck');
self::displayIdent($r,$options,$auth, $args, $tabalias,"enscribed_colour_ring","colour-ring","colourRing",$taxIdx,'Colour Leg Ring');
self::displayIdent($r,$options,$auth, $args, $tabalias,"metal_ring","metal","metalRing",$taxIdx,'Metal Ring');
//self::displayIdent($r,$options,$auth, $args, $tabalias,"enscribed_colour_ring","colour-ring","colourRing",$taxIdx,'Colour Leg Ring 2');



// Metal ring needs to be a choice for legs instead
//self::displayIdent($r,$options,$auth, $args, $tabalias,"metal_ring","metal","metalRing",$taxIdx);


###    unset($options['seq_maxlength']);
    $r .= '</div>'; // end of identifier accordion
}      

private function displayIdent(&$r,$options,$auth, $args, $tabalias,$identType,$identName,$identFormat,$taxIdx,$marktype){
    // setup and call function for identifier
    $options['identifierName'] = '';    $options['identifierTypeId'] = '';
    foreach ($options['identifierTypes'] as $identifier_type) {
    unset($hidepos,$hidecolour,$hiddenValue,$baseColourId,$textColourId);
      if ($identifier_type['id']==$args[$identType."_type"]) {
// Want to use identifier position - not type 
        $options['identifierName'] = lang::get($marktype);
//        $options['identifierName'] = $identifier_type['term'];
        $options['identifierTypeId'] = $identifier_type['id'];
        break;
      }
    }
    switch($marktype){
    	case 'Neck':
    		$hidepos=true;
	    	$hidecolour=false;
#	    	$hiddenValue=$args['left_'.$identType.'_position'];
	    	$hiddenValue=176;// set position to neck
	    	break;
    	case 'Metal Ring':
	    	$hidepos=false;
	    	$hidecolour=true;
	    	$baseColourId=149;
	    	$textColourId=137;
	    	//need to force black and grey colours
	    	break;
    	case 'Colour Leg Ring':
    	case 'Colour Leg Ring 2':
	    	$hidepos=false;
	    	$hidecolour=false;
	    	break;
   	default:
    		$hidepos=true;
	    	$hidecolour=false;
    }


$options['attrList'][]=self::setIdnArray($options['positionId'],false,$hidepos,$hiddenValue);
$options['attrList'][]=self::setIdnArray($options['baseColourId'],!$hidecolour,$hidecolour,$baseColourId);
$options['attrList'][]=self::setIdnArray($options['textColourId'],!$hidecolour,$hidecolour,$textColourId);
$options['attrList'][]=self::setIdnArray($options['sequenceId'],false,false,$hiddenValue);

$options['attrList'][]=array('attrType' => 'iso', 'typeId' => $options['conditionsId'], 'lockable' => false, 'hidden' => false,);
    
    
    $options['fieldprefix'] = 'idn:'.$taxIdx.":$identName:";
    $options['classprefix'] = "idn-$identName-";

    $options['seq_maxlength'] = (!empty($args[$identType.'_max_length'])) ? $args[$identType.'_max_length'] : '';

    if (!empty($args[$identType.'_regex'])) {
      $options['seq_format_class'] = $identFormat.'Format';
    }
    $r .= self::get_control_identifier($auth, $args, $tabalias, $options);

    if (!empty($args[$identType.'_regex'])) {
      unset($options['seq_format_class']);
    }


}
private function setIdnArray($typeId,$lockable,$hidden,$hiddenValue){
	$idn=array('attrType' => 'idn', 'typeId' => $typeId, 'lockable' => $lockable, 'hidden' => $hidden);
	if(isset($hiddenValue)) $idn['hiddenValue'] = $hiddenValue;
	return $idn;
}

private function colourgrid($text){
// This function decodes the identifier code and cooses colour, ringtype and position of div in grid
$result='';
$needle='<td class="data codes">';
$needle2='</td>';
foreach(explode("\n",$text) as $line){

if(substr_count($line,$needle)==1){
$newcode='';# new bird
//	extract the data
	$data=str_replace($needle2,'',str_replace($needle,'',$line));
	foreach(explode(";",$data)as $identifier){
$identifier=trim($identifier);
$start=strpos($identifier,'(');
$stop=strpos($identifier,')');
	$code=substr($identifier,0,$start);
	
$gridpos=substr($identifier,0,2);	
if($gridpos=='?B')$gridpos='X';
switch(strlen($code)){
		case 4:
		$basecolour=substr($code,-2,1);
		$textcolour=substr($code,-1);
			break;
		case 3: //metal
		$basecolour='S';
		$textcolour='B';
			break;
	}
	


$sequence=substr($identifier,$start+1,$stop-$start-1);
$newcode.="<div class=\"gridbg_$basecolour gridfg_$textcolour gridpos_$gridpos\">$sequence</div>";
}

$line=$needle.$newcode.$needle2;
}
$result.="$line\n";
}
return $result;
}


private function rfj_do_grid($args,$node,$tabs,$svcUrl){
      $r = '';
      // debug section
      if (!empty($args['debug_info']) && $args['debug_info']) {
        $r .= '<input type="button" value="Debug info" onclick="$(\'#debug-info-div\').slideToggle();" /><br />'.
          '<div id="debug-info-div" style="display: none;">';
        $r .= '<p>$_GET is:<br /><pre>'.print_r($_GET, true).'</pre></p>';
        $r .= '<p>$_POST is:<br /><pre>'.print_r($_POST, true).'</pre></p>';
        $r .= '<p>Entity to load is:<br /><pre>'.print_r(data_entry_helper::$entity_to_load, true).'</pre></p>';
        $r .= '<p>Submission was:<br /><pre>'.print_r(self::$submission, true).'</pre></p>';
        $r .= '<input type="button" value="Hide debug info" onclick="$(\'#debug-info-div\').slideToggle();" />';
        $r .= '</div>';
      }
      if (method_exists(get_called_class(), 'getHeaderHTML')) {
        $r .= call_user_func(array(get_called_class(), 'getHeaderHTML'), true, $args);
      }
      $attributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>self::$auth['read']
       ,'survey_id'=>$args['survey_id']
      ), false);
      $tabs = array('#sampleList'=>lang::get('LANG_Main_Samples_Tab'));
      if($args['includeLocTools'] 
        && function_exists('iform_loctools_checkaccess') 
        && iform_loctools_checkaccess($node,'admin')) {
        $tabs['#setLocations'] = lang::get('LANG_Allocate_Locations');
      }
      if (method_exists(get_called_class(), 'getExtraGridModeTabs')) {
        $extraTabs = call_user_func(array(get_called_class(), 'getExtraGridModeTabs'), false, self::$auth['read'], $args, $attributes);
        if(is_array($extraTabs)) {
          $tabs = $tabs + $extraTabs;
        }
      }
      if(count($tabs) > 1) {
        $r .= "<div id=\"controls\">".(data_entry_helper::enable_tabs(array('divId'=>'controls','active'=>'#sampleList')))."<div id=\"temp\"></div>";
        $r .= data_entry_helper::tab_header(array('tabs'=>$tabs));
      }
      $r .= "<div id=\"sampleList\">".call_user_func(array(get_called_class(), 'getSampleListGrid'), $args, $node, self::$auth, $attributes)."</div>";
      if($args['includeLocTools'] 
        && function_exists('iform_loctools_checkaccess') 
        && iform_loctools_checkaccess($node,'admin')) {
        $r .= '
  <div id="setLocations">
    <form method="post">
      <input type="hidden" id="mnhnld1" name="mnhnld1" value="mnhnld1" /><table border="1"><tr><td></td>';
        $url = $svcUrl.'/data/location?mode=json&view=detail&auth_token='.self::$auth['read']['auth_token']."&nonce=".self::$auth['read']["nonce"]."&parent_id=NULL&orderby=name".(isset($args['loctoolsLocTypeID'])&&$args['loctoolsLocTypeID']<>''?'&location_type_id='.$args['loctoolsLocTypeID']:'');
        $session = curl_init($url);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        $entities = json_decode(curl_exec($session), true);
        $userlist = iform_loctools_listusers($node);
        foreach($userlist as $uid => $a_user) {
          $r .= '<td>'.$a_user->name.'</td>';
        }
        $r .= "</tr>";
        if(!empty($entities)) {
          foreach($entities as $entity) {
            if(!$entity["parent_id"]) { // only assign parent locations.
              $r .= "<tr><td>".$entity["name"]."</td>";
              $defaultuserids = iform_loctools_getusers($node, $entity["id"]);
              foreach($userlist as $uid => $a_user) {
                $r .= '<td><input type="checkbox" name="location:'.$entity["id"].':'.$uid.(in_array($uid, $defaultuserids) ? '" checked="checked"' : '"').'></td>';
              }
              $r .= "</tr>";
            }
          }
        }
        $r .= "</table>
      <input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save_Location_Allocations')."\" />
    </form>
  </div>";
      }
      if (method_exists(get_called_class(), 'getExtraGridModeTabs')) {
        $r .= call_user_func(array(get_called_class(), 'getExtraGridModeTabs'), true, self::$auth['read'], $args, $attributes);
      }
      if(count($tabs)>1) { // close tabs div if present
        $r .= "</div>";
      }
      if (method_exists(get_called_class(), 'getTrailerHTML')) {
        $r .= call_user_func(array(get_called_class(), 'getTrailerHTML'), true, $args);
      }
      return $r;
    }


}


/**
 * For PHP 5.2, declare the get_called_class method which allows us to use subclasses of this form.
 */
if(!function_exists('get_called_class')) {
function get_called_class() {
    $matches=array();
    $bt = debug_backtrace();
    $l = 0;
    do {
        $l++;
        if(isset($bt[$l]['class']) AND !empty($bt[$l]['class'])) {
            return $bt[$l]['class'];
        }
        $lines = file($bt[$l]['file']);
        $callerLine = $lines[$bt[$l]['line']-1];
        preg_match('/([a-zA-Z0-9\_]+)::'.$bt[$l]['function'].'/',
                   $callerLine,
                   $matches);
        if (!isset($matches[1])) $matches[1]=NULL; //for notices
        if ($matches[1] == 'self') {
               $line = $bt[$l]['line']-1;
               while ($line > 0 && strpos($lines[$line], 'class') === false) {
                   $line--;                 
               }
               preg_match('/class[\s]+(.+?)[\s]+/si', $lines[$line], $matches);
       }
    }
    while ($matches[1] == 'parent'  && $matches[1]);
    return $matches[1];
  } 

} 


