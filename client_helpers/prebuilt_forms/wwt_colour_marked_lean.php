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

require_once('dynamic_subject_observation.php');
//require_once('dynamic_sample_occurrence.php');
//require_once('includes/map.php');
//require_once('includes/language_utils.php');
//require_once('includes/form_generation.php');
//require_once('includes/individuals.php');

class iform_wwt_colour_marked_lean  extends iform_dynamic_subject_observation{

  // A list of the subject observation ids we are loading if editing existing data
//  public static $subjectObservationIds = array();
//  public static $loadedSubjectObservationId;
//  protected static $loadedSampleId;
//  protected static $auth = array();
//  protected static $mode;
//  protected static $node;
//  protected static $screenmode;
  // The class called by iform.module which may be a subclass of iform_location_dynamic
 
//  public static $submission = array();
  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */

  public static function get_wwt_colour_marked_lean_definition() {
    return array(
      'title'=>'RFJ WWT Lean Colour-marked Wildfowl form',
      'category' => 'General Purpose Data Entry Forms',
      'helpLink'=>'http://code.google.com/p/indicia/wiki/PrebuiltFormWWTColourMarkedRecords',
      'description'=>'A data entry form reporting observations of colour-marked individual birds using classes.'
    );
  }
  
    /**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args, $node) {
    define ("MODE_GRID", 0);
    define ("MODE_NEW_SAMPLE", 1);
    define ("MODE_EXISTING", 2);
$r='Header above Tabs section';
//$r.=parent::get_form($args, $node);

    self::$node = $node;
    self::$called_class = 'iform_' . $node->iform;
    
        // if we use locks, we want them to be distinct for each drupal user
    if (function_exists('profile_load_profile')) { // check we are in drupal
      global $user;
      data_entry_helper::$javascript .= "if (indicia && indicia.locks && indicia.locks.setUser) {
        indicia.locks.setUser ('".$user->uid."');
      }\n";
    }
wwt_individual::rfj_fixed_args($args);
// Get authorisation tokens to update and read from the Warehouse.
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $svcUrl = wwt_individual::warehouseUrl().'index.php/services';
    self::$auth = $auth;
    
    drupal_add_js(iform_media_folder_path() . 'js/jquery.form.js', 'module');
    // here is where tooltip js could be added
    self::$loadedSampleId = null;
    self::$loadedSubjectObservationId = null;
   // Try to rationalise mode setting
   //MODE_NEW_SAMPLE;
   self::rfj_set_mode($args);
   self::$mode=MODE_NEW_SAMPLE;
// $r.='xxxx'.self::$mode.'yyyy';
    // default mode  MODE_GRID : display grid of the samples to add a new one 
    // or edit an existing one.
switch(self::$mode){
	case MODE_GRID :
		$r.= wwt_individual::rfj_do_grid($args,$node,$tabs,$svcUrl,$submission,$auth);
		return $r;
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


$hiddens=self::get_hiddens($args,$attributes);//get html for hiddens
// debug section
$r.=self::rfj_debug_content($args);

$r.=self::rfj_static_content($args,$attributes,$reloadPath,$tabs);
$r.=self::get_tabs($tabs,$auth,$args,$attributes);
//$r.=self::get_form_html($args, $auth, $attributes);

$r.='Footer below Tabs section';
$r.=self::get_tab_footer($args);

    return $r;
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

  /**
   * Return the generated form output.
   * @return Form HTML.
   */

/**
   * Get the control for species input, either a grid or a single species input control.
   */
  public static function get_control_individuals($auth, $args, $tabalias, $options) {
  //set global variables
  wwt_individual::setGlobals($auth, $args, $tabalias, $options);
  //check that all required attributes are present in form - otherwise fail
  wwt_individual::rfj_check_speciesidentifier_attributes_present();  
  self::rfj_species_identifier_init($options,$filter,$dataOpts,$auth);
  self::rfj_check_speciesidentifier_attributes_present($options);
  self::rfj_check_speciesidentifier_javascript($options,$args);
//INDIVIDUAL FIELDSET etc
// Needs to loop through families and individuals
  $numIndivs=count(self::$subjectObservationIds);
$families=array();
$singles=array();
  $r="";


for($taxIdx=0;$taxIdx<$numIndivs;$taxIdx++){
$r.=$taxIdx;
}
      //Same code is used for new individual being added (without div) 
    if (!$options['inNewIndividual']) {;
      $r .= '<div id="idn:subject:accordion" class="idn-subject-accordion">';



      
// Store template and add one button to add new bird outside family
  if (!$options['inNewIndividual']) {
//  	$r.=self::rfj_individual_template($options,$auth,$tabalias,$args,$opts);    
}
      
      
    }

  
#loop through birds in record and show as families/groups or singletons
  
  
  return "gggg";
  return "XX $auth XX $tabalias XX".print_r($args,true)."XXX".print_r($options,true)."XX";
  }
  
private function rfj_debug_content($args){
    if (!empty($args['debug_info']) && $args['debug_info']) {
      $r = '<input type="button" value="Debug info" onclick="$(\'#debug-info-div\').slideToggle();" /><br />'.
        '<div id="debug-info-div" style="display: none;">';
      $r .= '<p>$_GET is:<br /><pre>'.print_r($_GET, true).'</pre></p>';
      $r .= '<p>$_POST is:<br /><pre>'.print_r($_POST, true).'</pre></p>';
      $r .= '<p>Entity to load is:<br /><pre>'.print_r(data_entry_helper::$entity_to_load, true).'</pre></p>';
      $r .= '<p>Submission was:<br /><pre>'.print_r(self::$submission, true).'</pre></p>';
      $r .= '<input type="button" value="Hide debug info" onclick="$(\'#debug-info-div\').slideToggle();" />';
      $r .= '</div>';
      return $r;
    }
}



protected function get_tab_footer($args){
$r="";
    if ($args['interface']=='tabs' && $args['save_button_below_all_pages']) {
      $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" id=\"save-button\" value=\"".lang::get('Save')."\" />\n";
    }
    if(!empty(data_entry_helper::$validation_errors)){
      $r .= data_entry_helper::dump_remaining_errors();
    }   
    $r .= "</form>";
    
    if (method_exists(get_called_class(), 'getTrailerHTML')) $r .= call_user_func(array(get_called_class(), 'getTrailerHTML'), true, $args);
    return $r;
}

}
