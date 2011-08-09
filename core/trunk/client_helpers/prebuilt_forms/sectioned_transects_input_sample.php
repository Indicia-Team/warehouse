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
 * @package Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

require_once 'includes/form_generation.php';
 
/**
 * 
 * 
 * @package Client
 * @subpackage PrebuiltForms
 * A form for data entry of transect data by entering counts of each for sections along the transect.
 */
class iform_sectioned_transects_input_sample {
  
  /** 
   * Return the form metadata. Note the title of this method includes the name of the form file. This ensures
   * that if inheritance is used in the forms, subclassed forms don't return their parent's form definition.
   * @return array The definition of the form.
   */
  public static function get_sectioned_transects_input_sample_definition() {
    return array(
      'title'=>'Sectioned Transects Sample Input',
      'category' => 'Sectioned Transects',
      'description'=>'A form for inputting the counts of species observed at each section along a transect. Can be called with site=<id> in the URL to force the '.
          'selection of a fixed site, or sample=<id> to edit an existing sample.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
   */
  public static function get_parameters() {   
    return array(
      array(
        'name'=>'survey_id',
        'caption'=>'Survey',
        'description'=>'The survey that data will be posted into.',
        'type'=>'select',
        'table'=>'survey',
        'captionField'=>'title',
        'valueField'=>'id',
        'siteSpecific'=>true
      ),
      array(
        'name'=>'occurrence_attribute_id',
        'caption'=>'Occurrence Attribute',
        'description'=>'The attribute (typically an abundance attribute) that will be presented in the grid for input. Entry of an attribute value will create '.
            ' an occurrence.',
        'type'=>'select',
        'table'=>'occurrence_attribute',
        'captionField'=>'caption',
        'valueField'=>'id',
        'siteSpecific'=>true
      )
    );
  }
  
  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $node The Drupal node object.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   * @todo: Implement this method 
   */
  public static function get_form($args, $node, $response=null) {
    if (isset($_POST['page']) && $_POST['page']=='sample' && !isset(data_entry_helper::$validation_errors)) {
      // we have just saved the sample page, so move on to the occurrences list
      return self::get_occurrences_form($args, $node, $response);
    } else {
      return self::get_sample_form($args, $node, $response);
    }
  }
  
  public static function get_sample_form($args, $node, $response) {
    if (!module_exists('iform_ajaxproxy'))
      return 'This form must be used in Drupal with the Indicia AJAX Proxy module enabled.';
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $sampleId = isset($_GET['sample_id']) ? $_GET['sample_id'] : null;
    if ($sampleId) {
      data_entry_helper::load_existing_record($auth['read'], 'sample', $sampleId);
      $locationId = data_entry_helper::$entity_to_load['sample:location_id'];
    } else {
      $locationId = isset($_GET['site']) ? $_GET['site'] : null;
    }
    $r = '<form method="post">';
    $r .= $auth['write'];
    // we pass through the read auth. This makes it possible for the get_submission method to authorise against the warehouse
    // without an additional (expensive) warehouse call, so it can get location details.
    $r .= '<input type="hidden" name="read_nonce" value="'.$auth['read']['nonce'].'"/>';
    $r .= '<input type="hidden" name="read_auth_token" value="'.$auth['read']['auth_  token'].'"/>';
    $r .= '<input type="hidden" name="website_id" value="'.$args['website_id'].'"/>';
    if (isset(data_entry_helper::$entity_to_load['sample:id']))
      $r .= '<input type="hidden" name="sample:id" value="'.data_entry_helper::$entity_to_load['sample:id'].'"/>';
    $r .= '<input type="hidden" name="sample:survey_id" value="'.$args['survey_id'].'"/>';
    $r .= '<input type="hidden" name="page" value="sample"/>';
    if ($locationId) {
      $site = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $auth['read'] + array('view'=>'detail','id'=>$locationId,'deleted'=>'f')
      ));
      $site = $site[0];
      $r .= '<input type="hidden" name="sample:location_id" value="'.$locationId.'"/>';
      $r .= '<input type="hidden" name="sample:entered_sref" value="'.$site['centroid_sref'].'"/>';
      $r .= '<input type="hidden" name="sample:entered_sref_system" value="'.$site['centroid_sref_system'].'"/>';
      // @todo County/Region
      $r .= '<label>Site name:</label><span>'.$site['name'].'</span><br/>';
    } else {
      // @todo filter to the locations for the current user
      // @todo County/Region
      // Output only the locations for this website and transect type. Note we load both transects and sections, just so that 
      // we always use the same warehouse call and therefore it uses the cache.
      $locationTypes = helper_base::get_termlist_terms($auth, 'indicia:location_types', array('Transect', 'Transect Section'));
      $r .= data_entry_helper::location_select(array(
        'fieldname' => 'sample:location_id',
        'id' => 'location_select',
        'label' => lang::get('Site'),
        'validation' => array('required'),
        'extraParams' => $auth['read'] + array('website_id' => $args['website_id'], 'location_type_id'=>$locationTypes[0]['id'])
      ));
      // sref values for the sample will be populated automatically when the submission is built.
    }
    $r .= data_entry_helper::date_picker(array(
      'label' => lang::get('Date'),
      'fieldname' => 'sample:date',
    ));
    $attributes = data_entry_helper::getAttributes(array(
      'id' => $sampleId,
      'valuetable'=>'sample_attribute_value',
      'attrtable'=>'sample_attribute',
      'key'=>'sample_id',
      'fieldprefix'=>'smpAttr',
      'extraParams'=>$auth['read'],
      'survey_id'=>$args['survey_id']
    ));
    $r .= get_attribute_html($attributes, $args, array());
    $r .= '<input type="submit" value="'.lang::get('Next').'" class="ui-state-default ui-corner-all" />';
    $r .= '</form>';
    return $r;
  }
  
  public static function get_occurrences_form($args, $node, $response) {
    if (!module_exists('iform_ajaxproxy'))
      return 'This form must be used in Drupal with the Indicia AJAX Proxy module enabled.';
    data_entry_helper::add_resource('jquery_form');
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $parentId = $_POST['sample:location_id'];
    $sections = data_entry_helper::get_population_data(array(
      'table' => 'location',
      'extraParams' => $auth['read'] + array('view'=>'detail','parent_id'=>$parentId,'deleted'=>'f')
    ));
    $r = '<table id="transect-input" class="ui-widget navigateable"><thead class="ui-widget-header"><tr>';
    $r .= '<th>' . lang::get('species') . '</th>';
    foreach ($sections as $section) {
      $r .= '<th>' . $section['code'] . '</th>';
    }
    $r .= '</tr></thead>';
    $r .= '<tbody class="ui-widget-content"></tbody>';
    $r .= '</table>';
    // A stub form for AJAX posting when we need to create an occurrence
    $r .= '<form style="display: none" id="occ-form" method="post" action="'.iform_ajaxproxy_url($node, 'occurrence').'">';
    $r .= '<input name="website_id" value="'.$args['website_id'].'"/>';
    $r .= '<input name="occurrence:id" id="occid" />';
    $r .= '<input name="occurrence:taxa_taxon_list_id" id="ttlid" />';
    $r .= '<input name="occurrence:sample_id" id="occ_sampleid"/>';
    $r .= '<input name="occAttr:' . $args['occurrence_attribute_id'] . '" id="occattr"/>';
    $r .= '</form>';
    // A stub form for AJAX posting when we need to create a sample
    $r .= '<form style="display: none" id="smp-form" method="post" action="'.iform_ajaxproxy_url($node, 'sample').'">';
    $r .= '<input name="website_id" value="'.$args['website_id'].'"/>';
    $r .= '<input name="sample:parent_id" value="'.$_POST['sample:id'].'" />';
    $r .= '<input name="sample:survey_id" value="'.$args['survey_id'].'" />';
    $r .= '<input name="sample:entered_sref" id="smpsref" />';
    $r .= '<input name="sample:entered_sref_system" id="smpsref_system" />';
    $r .= '<input name="sample:location_id" id="smploc" />';
    $r .= '<input name="sample:date" value="'.$_POST['sample:date'].'" />';
    $r .= '</form>';
    $r .= print_r($_POST, true);
    // tell the Javascript where to get species from.
    // @todo handle diff species lists.
    data_entry_helper::$javascript .= "indiciaData.initSpeciesList = 9;\n";
    // allow js to do AJAX by passing in the information it needs to post forms
    data_entry_helper::$javascript .= "indiciaData.readAuth = {nonce: '".$auth['read']['nonce']."', auth_token: '".$auth['read']['auth_token']."'};\n";
    data_entry_helper::$javascript .= "indiciaData.transect = ".$parentId.";\n";
    data_entry_helper::$javascript .= "indiciaData.parentSample = ".$_POST['sample:id'].";\n";
    data_entry_helper::$javascript .= "indiciaData.sections = ".json_encode($sections).";\n";
    data_entry_helper::$javascript .= "indiciaData.occAttrId = ".$args['occurrence_attribute_id'] .";\n";    
    
    // @todo output the existing sample ids into indiciaData.samples.s1, s2 etc.
    data_entry_helper::$javascript .= "indiciaData.samples = [];\n";
    
    // Do an AJAX population of the grid rows.
    data_entry_helper::$javascript .= "loadSpeciesList();\n";
    data_entry_helper::add_resource('jquery_ui');
    return $r;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * For example, the following represents a submission structure for a simple
   * sample and 1 occurrence submission
   * return data_entry_helper::build_sample_occurrence_submission($values);
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   * @todo: Implement this method
   */
  public static function get_submission($values, $args) {
    if (!isset($values['sample:entered_sref'])) {
      // the sample does not have sref data, as the user has just picked a transect site at this point. Copy the 
      // site's centroid across to the sample.
      $read = array(
        'nonce' => $values['read_nonce'],
        'auth_token' => $values['read_auth_token']
      );
      $site = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $read + array('view'=>'detail','id'=>$values['sample:location_id'],'deleted'=>'f')
      ));
      $site = $site[0];
      $values['sample:entered_sref'] = $site['centroid_sref'];
      $values['sample:entered_sref_system'] = $site['centroid_sref_system'];
      
    }
    $submission = submission_builder::build_submission($values, array('model' => 'sample'));
    return($submission);
  }

}
