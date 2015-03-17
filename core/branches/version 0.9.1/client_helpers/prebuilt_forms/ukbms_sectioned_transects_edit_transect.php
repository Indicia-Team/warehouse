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

require_once('sectioned_transects_edit_transect.php');

/**
 * 
 * 
 * @package Client
 * @subpackage PrebuiltForms
 * Form for adding or editing the site details on a transect which contains a number of sections.
 */
class iform_ukbms_sectioned_transects_edit_transect extends iform_sectioned_transects_edit_transect {

  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_ukbms_sectioned_transects_edit_transect_definition() {
    return array(
      'title'=>'UKBMS Location editor',
      'category' => 'Sectioned Transects',
      'description'=>'Form for adding or editing the site details on a transect style location which has a number of sub-sections, but which can have various location types.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
   */
  public static function get_parameters() {
    $parentVal = array_merge(
      parent::get_parameters(),
      array(
        array(
          'name'=>'main_type_term_1',
          'caption'=>'Location type term 1',
          'description'=>'Select the term used for the first main site location type.',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'term',
          'extraParams' => array('termlist_external_key'=>'indicia:location_types'),
          'required' => true,
          'group'=>'Transects Editor Settings'
        ),
        array(
          'name'=>'can_change_section_number_1',
          'caption'=>'Change section number 1',
          'description'=>'Select if sites of type 1 can change the number of sections.',
          'type' => 'boolean',
          'required' => false,
          'default' => true,
          'group'=>'Transects Editor Settings'
        ),
        array(
          'name'=>'section_number_1',
          'caption'=>'Section number 1',
          'description'=>'If the user can not change the number of sections for site type 1, then enter the fixed number of sections.',
          'type' => 'int',
          'required' => false,
          'group'=>'Transects Editor Settings'
        ),
        array(
          'name'=>'main_type_term_2',
          'caption'=>'Location type term 2',
          'description'=>'Select the term used for the second main site location type.',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'term',
          'extraParams' => array('termlist_external_key'=>'indicia:location_types'),
          'required' => false,
          'group'=>'Transects Editor Settings'
        ),
        array(
          'name'=>'can_change_section_number_2',
          'caption'=>'Change section number 2',
          'description'=>'Select if sites of type 2 can change the number of sections.',
          'type' => 'boolean',
          'required' => false,
          'group'=>'Transects Editor Settings'
        ),
        array(
          'name'=>'section_number_2',
          'caption'=>'Section number 2',
          'description'=>'If the user can not change the number of sections for site type 2, then enter the fixed number of sections.',
          'type' => 'int',
          'required' => false,
          'group'=>'Transects Editor Settings'
        ),
        array(
          'name'=>'main_type_term_3',
          'caption'=>'Location type term 3',
          'description'=>'Select the term used for the third main site location type.',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'term',
          'extraParams' => array('termlist_external_key'=>'indicia:location_types'),
          'required' => false,
          'group'=>'Transects Editor Settings'
        ),
        array(
          'name'=>'can_change_section_number_3',
          'caption'=>'Change section number 3',
          'description'=>'Select if sites of type 3 can change the number of sections.',
          'type' => 'boolean',
          'required' => false,
          'default' => true,
          'group'=>'Transects Editor Settings'
        ),
        array(
          'name'=>'section_number_3',
          'caption'=>'Section number 3',
          'description'=>'If the user can not change the number of sections for site type 3, then enter the fixed number of sections.',
          'type' => 'int',
          'required' => false,
          'group'=>'Transects Editor Settings'
        )
    ));
    $retVal = array();
    foreach($parentVal as $param){
      switch($param['name']) {
        case 'transect_type_term': break;
      	case 'survey_id':
          $param['description'] = 'The survey that data will be used to define custom attributes. This needs to match the survey used to submit visit data for sites of the first location type.';
          $param['group'] = 'Transects Editor Settings';
          $retVal[] = $param;
          break;
        default:
          $retVal[] = $param;
          break;
      }
    }
    return $retVal;
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
    global $user;
    // use the js from the main form, until there is a deviation.
    drupal_add_js(iform_client_helpers_path() . "prebuilt_forms/js/sectioned_transects_edit_transect.js");
    drupal_add_css(iform_client_helpers_path() . "prebuilt_forms/css/sectioned_transects_edit_transect.css");
    
    $checks=self::check_prerequisites();
    $args = self::getArgDefaults($args);
    if ($checks!==true)
      return $checks;
    iform_load_helpers(array('map_helper'));
    data_entry_helper::add_resource('jquery_form');
    self::$ajaxFormUrl = iform_ajaxproxy_url($node, 'location');
    self::$ajaxFormSampleUrl = iform_ajaxproxy_url($node, 'sample');
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $settings = array(
      'mainLocationType' => helper_base::get_termlist_terms($auth, 'indicia:location_types', array(empty($args['main_type_term_1']) ? 'Transect' : $args['main_type_term_1'])),
      'sectionLocationType' => helper_base::get_termlist_terms($auth, 'indicia:location_types', array(empty($args['section_type_term']) ? 'Section' : $args['section_type_term'])),
      'locationId' => isset($_GET['id']) ? $_GET['id'] : null,
      'canEditBody' => true,
      'canEditSections' => true, // this is specifically the number of sections: so can't delete or change the attribute value.
      // Allocations of Branch Manager are done by a person holding the managerPermission.
      'canAllocBranch' => $args['managerPermission']=="" || user_access($args['managerPermission']),
      // Allocations of Users are done by a person holding the managerPermission or the allocate Branch Manager.
      // The extra check on this for branch managers is done later
      'canAllocUser' => $args['managerPermission']=="" || user_access($args['managerPermission']) 
    );
    // WARNING!!!! we are making the assumption that the attributes are defined to be the same for all the location_types.
    $settings['attributes'] = data_entry_helper::getAttributes(array(
        'id' => $settings['locationId'],
        'valuetable'=>'location_attribute_value',
        'attrtable'=>'location_attribute',
        'key'=>'location_id',
        'fieldprefix'=>'locAttr',
        'extraParams'=>$auth['read'],
        'survey_id'=>$args['survey_id'],
        'location_type_id' => $settings['mainLocationType'][0]['id'],
        'multiValue' => true
    ));
    $settings['section_attributes'] = data_entry_helper::getAttributes(array(
        'valuetable'=>'location_attribute_value',
        'attrtable'=>'location_attribute',
        'key'=>'location_id',
        'fieldprefix'=>'locAttr',
        'extraParams'=>$auth['read'],
        'survey_id'=>$args['survey_id'],
        'location_type_id' => $settings['sectionLocationType'][0]['id'],
        'multiValue' => true
    ));
    if ($args['allow_user_assignment']) {
      if (false==$settings['cmsUserAttr'] = extract_cms_user_attr($settings['attributes']))
        return 'This form is designed to be used with the CMS User ID attribute setup for locations in the survey, or the "Allow users to be assigned to transects" option unticked.';
      // keep a copy of the cms user ID attribute so we can use it later.
      self::$cmsUserAttrId = $settings['cmsUserAttr']['attributeId'];
    }

    // need to check if branch allocation is active.
    if ($args['branch_assignment_permission'] != '') {
      if (false== ($settings['branchCmsUserAttr'] = self::extract_attr($settings['attributes'], "Branch CMS User ID")))
        return '<br />This form is designed to be used with either<br />1) the Branch CMS User ID attribute setup for locations in the survey, or<br />2) the "Permission name for Branch Manager" option left blank.<br />';
      // keep a copy of the branch cms user ID attribute so we can use it later.
      self::$branchCmsUserAttrId = $settings['branchCmsUserAttr']['attributeId'];
    } else self::$branchCmsUserAttrId = false;
    
    data_entry_helper::$javascript .= "indiciaData.sections = {};\n";
    $settings['sections']=array();
    $settings['numSectionsAttr'] = "";
    $settings['maxSectionCount'] = $args['maxSectionCount'];
    $settings['autocalcSectionLengthAttrId'] = empty($args['autocalc_section_length_attr_id']) ? 0 : $args['autocalc_section_length_attr_id'];
    $settings['defaultSectionGridRef'] = empty($args['default_section_grid_ref']) ? 'parent' : $args['default_section_grid_ref'];
    if ($settings['locationId']) {
      $fixedSectionNumber = false;
      data_entry_helper::load_existing_record($auth['read'], 'location', $settings['locationId']);
      $settings['walks'] = data_entry_helper::get_population_data(array(
        'table' => 'sample',
        'extraParams' => $auth['read'] + array('view'=>'detail','location_id'=>$settings['locationId'],'deleted'=>'f'),
        'nocache' => true
      ));
      // Work out permissions for this user: note that canAllocBranch setting effectively shows if a manager.
      if(!$settings['canAllocBranch']) {
        // Check whether I am a normal user and it is allocated to me, and also if I am a branch manager and it is allocated to me.
        $settings['canEditBody'] = false;
        $settings['canEditSections'] = false;
        if($args['allow_user_assignment'] &&
            count($settings['walks']) == 0 &&
            isset($settings['cmsUserAttr']['default']) &&
            !empty($settings['cmsUserAttr']['default'])) {
          foreach($settings['cmsUserAttr']['default'] as $value) { // multi value
            if($value['default'] == $user->uid) { // comparing string against int so no triple equals
              $settings['canEditBody'] = true;
              $settings['canEditSections'] = true;
              break;
            }
          }
        }
        // If a Branch Manager and not a main manager, then can't edit the number of sections
        if($args['branch_assignment_permission'] != '' &&
            user_access($args['branch_assignment_permission']) &&
            isset($settings['branchCmsUserAttr']['default']) &&
            !empty($settings['branchCmsUserAttr']['default'])) {
          foreach($settings['branchCmsUserAttr']['default'] as $value) { // now multi value
            if($value['default'] == $user->uid) { // comparing string against int so no triple equals
              $settings['canEditBody'] = true;
              $settings['canAllocUser'] = true;
              break;
            }
          }
        }
        // but if the location type is a fixed number type, then canEditSections is always false
        for($i = 1; $i < 4; $i++){
          if(!empty($args['main_type_term_'.$i])) {
            $type = helper_base::get_termlist_terms($auth, 'indicia:location_types', array($args['main_type_term_'.$i]));
            if(data_entry_helper::$entity_to_load['location:location_type_id'] == $type[0]['id'] &&
                (!isset($args['can_change_section_number_'.$i]) || !$args['can_change_section_number_'.$i])) {
              $settings['canEditSections'] = false;
              $fixedSectionNumber = $args['section_number_'.$i];
            }
          }
        }
      } // for an admin user the defaults apply, which will be can do everything.
      // find the number of sections attribute.
      foreach($settings['attributes'] as $attr) {
        if ($attr['caption']==='No. of sections') {
          $settings['numSectionsAttr'] = $attr['fieldname'];
          if($fixedSectionNumber) {
            for ($i=1; $i<=$fixedSectionNumber; $i++)
              $settings['sections']["S$i"]=null;
            data_entry_helper::$javascript .= "$('#".str_replace(':','\\\\:',$attr['id'])."').val($fixedSectionNumber).attr('readonly','readonly').css('color','graytext');\n";
          } else {
            for ($i=1; $i<=$attr['displayValue']; $i++) {
              $settings['sections']["S$i"]=null;
            }
            $existingSectionCount = empty($attr['displayValue']) ? 1 : $attr['displayValue'];
            data_entry_helper::$javascript .= "$('#".str_replace(':','\\\\:',$attr['id'])."').attr('min',$existingSectionCount).attr('max',".$args['maxSectionCount'].");\n";
            if(!$settings['canEditSections'])
              data_entry_helper::$javascript .= "$('#".str_replace(':','\\\\:',$attr['id'])."').attr('readonly','readonly').css('color','graytext');\n";
          }
        }
      }
      $sections = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $auth['read'] + array('view'=>'detail','parent_id'=>$settings['locationId'],'deleted'=>'f','orderby'=>'code'),
        'nocache' => true
      ));
      foreach($sections as $section) {
        $code = $section['code'];
        if(in_array($section['centroid_sref_system'], array('osgb','osie')))
        	$section['centroid_sref_system'] = strtoupper($section['centroid_sref_system']);
        data_entry_helper::$javascript .= "indiciaData.sections.$code = {'geom':'".$section['boundary_geom']."','id':'".$section['id']."','sref':'".$section['centroid_sref']."','system':'".$section['centroid_sref_system']."'};\n";
        $settings['sections'][$code]=$section;
      }
    } else { // not an existing site therefore no walks. On initial save, no section data is created.
      foreach($settings['attributes'] as $attr) {
        if ($attr['caption']==='No. of sections') {
          $settings['numSectionsAttr'] = $attr['fieldname'];
          data_entry_helper::$javascript .= "$('#".str_replace(':','\\\\:',$attr['id'])."').attr('min',1).attr('max',".$args['maxSectionCount'].");\n";
        }
      }
      $settings['walks'] = array();
    }
    if ($settings['numSectionsAttr'] === '') {
      for ($i=1; $i<=$settings['maxSectionCount']; $i++) {
        $settings['sections']["S$i"]=null;
      }
    }
    $r = '<div id="controls">';
    $headerOptions = array('tabs'=>array('#site-details'=>lang::get('Site Details')));
    if ($settings['locationId']) {
      $headerOptions['tabs']['#your-route'] = lang::get('Your Route');
      if(count($settings['section_attributes']) > 0)
        $headerOptions['tabs']['#section-details'] = lang::get('Section Details');
    }
    if (count($headerOptions['tabs'])) {
      $r .= data_entry_helper::tab_header($headerOptions);
      data_entry_helper::enable_tabs(array(
          'divId'=>'controls',
          'style'=>'Tabs',
          'progressBar' => isset($args['tabProgress']) && $args['tabProgress']==true
      ));
    }
    $r .= self::get_site_tab($auth, $args, $settings);
    if ($settings['locationId']) {
      $r .= self::get_your_route_tab($auth, $args, $settings);
      if(count($settings['section_attributes']) > 0)
        $r .= self::get_section_details_tab($auth, $args, $settings);
    }
    $r .= '</div>'; // controls    
    data_entry_helper::enable_validation('input-form');
    if (function_exists('drupal_set_breadcrumb')) {
      $breadcrumb = array();
      $breadcrumb[] = l(lang::get('Home'), '<front>');
      $breadcrumb[] = l(lang::get('Sites'), $args['sites_list_path']);
      if ($settings['locationId'])
        $breadcrumb[] = data_entry_helper::$entity_to_load['location:name'];
      else
        $breadcrumb[] = lang::get('New Site');
      drupal_set_breadcrumb($breadcrumb);
    }
    // Inform JS where to post data to for AJAX form saving
    data_entry_helper::$javascript .= 'indiciaData.ajaxFormPostUrl="'.self::$ajaxFormUrl."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.ajaxFormPostSampleUrl="'.self::$ajaxFormSampleUrl."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.website_id="'.$args['website_id']."\";\n";
    data_entry_helper::$javascript .= "indiciaData.indiciaSvc = '".data_entry_helper::$base_url."';\n";
    data_entry_helper::$javascript .= "indiciaData.readAuth = {nonce: '".$auth['read']['nonce']."', auth_token: '".$auth['read']['auth_token']."'};\n";    
    data_entry_helper::$javascript .= "indiciaData.currentSection = '';\n";
    data_entry_helper::$javascript .= "indiciaData.sectionTypeId = '".$settings['sectionLocationType'][0]['id']."';\n";
    data_entry_helper::$javascript .= "indiciaData.sectionDeleteConfirm = \"".lang::get('Are you sure you wish to delete section')."\";\n";
    data_entry_helper::$javascript .= "indiciaData.sectionInsertConfirm = \"".lang::get('Are you sure you wish to insert a new section after section')."\";\n";
    data_entry_helper::$javascript .= "indiciaData.sectionChangeConfirm = \"".lang::get('Do you wish to save the currently unsaved changes you have made to the Section Details?')."\";\n";
    data_entry_helper::$javascript .= "indiciaData.numSectionsAttrName = \"".$settings['numSectionsAttr']."\";\n";
    data_entry_helper::$javascript .= "indiciaData.maxSectionCount = \"".$settings['maxSectionCount']."\";\n";
    data_entry_helper::$javascript .= "indiciaData.autocalcSectionLengthAttrId = ".$settings['autocalcSectionLengthAttrId'].";\n";
    data_entry_helper::$javascript .= "indiciaData.defaultSectionGridRef = '".$settings['defaultSectionGridRef']."';\n";
    if ($settings['locationId'])
      data_entry_helper::$javascript .= "selectSection('S1', true);\n";
    return $r;
  }

  private static function get_site_tab($auth, $args, $settings) {
    $r = '<div id="site-details" class="ui-helper-clearfix">';
    $r .= '<form method="post" id="input-form">';
    $r .= $auth['write'];    
    $r .= '<div id="cols" class="ui-helper-clearfix"><div class="left" style="width: 54%">';
    $r .= '<fieldset><legend>'.lang::get('Site Details').'</legend>';
    $r .= "<input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $typeTerms = array();
    if(!empty($args['main_type_term_1'])) $typeTerms[] = $args['main_type_term_1'];
    if(!empty($args['main_type_term_2'])) $typeTerms[] = $args['main_type_term_2'];
    if(!empty($args['main_type_term_3'])) $typeTerms[] = $args['main_type_term_3'];
    $typeTermIDs = helper_base::get_termlist_terms($auth, 'indicia:location_types', $typeTerms);
    $lookUpValues = array('' => '<' . lang::get('please select') . '>');
    foreach($typeTermIDs as $termDetails){
      $lookUpValues[$termDetails['id']] = $termDetails['term'];
    }
    // if location is predefined, can not change unless a 'managerPermission'
    $canEditType = !$settings['locationId'] ||
                   (isset($args['managerPermission']) && $args['managerPermission']!= '' && function_exists('user_access') && user_access($args['managerPermission']));
    if($canEditType) {
      $r .= data_entry_helper::select(array(
            'label' => lang::get('Site Type'),
            'id' => 'location_type_id',
      		'fieldname' => 'location:location_type_id',
            'lookupValues' => $lookUpValues
      ));
      data_entry_helper::$javascript .= "$('#location_type_id').change(function(){
  switch($(this).val()){\n";
      for($i = 1; $i < 4; $i++){
        if(!empty($args['main_type_term_'.$i])) {
          $type = helper_base::get_termlist_terms($auth, 'indicia:location_types', array($args['main_type_term_'.$i]));
          data_entry_helper::$javascript .= "    case \"".$type[0]['id']."\":\n";
          if(!isset($args['can_change_section_number_'.$i]) || !$args['can_change_section_number_'.$i]) {
            if ($settings['locationId'])
              // not saved yet, so no sections yet created, hence no need to worry about existing value. make number attribute readonly. set value. min value will be 1.
              data_entry_helper::$javascript .= "      var minValue = $('[name=".str_replace(':','\\\\:',$settings['numSectionsAttr'])."]').attr('min');
      if(minValue > ".$args['section_number_'.$i].") { // existing value is greater than one we want to set
        alert('You are reducing the number of sections below that already existing. Please use the Remove Section button on the Your Route tab to reduce the number of sections to ".$args['section_number_'.$i]." before changing the Site type');
        return false;
      }
      $('[name=".str_replace(':','\\\\:',$settings['numSectionsAttr'])."]').val(".$args['section_number_'.$i].").attr('readonly','readonly').css('color','graytext');\n";
            else
              // not saved yet, so no sections yet created, hence no need to worry about existing value. make number attribute readonly. set value. min value will be 1.
              data_entry_helper::$javascript .= "      $('[name=".str_replace(':','\\\\:',$settings['numSectionsAttr'])."]').val(".$args['section_number_'.$i].").attr('readonly','readonly').css('color','graytext');\n";
          } else {
            // user modifiable number of sections. value of attribute is left alone: don't have to worry att his point whether existing data.
            data_entry_helper::$javascript .= "      $('[name=".str_replace(':','\\\\:',$settings['numSectionsAttr'])."]').removeAttr('readonly').css('color','');\n";
          }
          data_entry_helper::$javascript .= "      break;\n";
        }
      }
      data_entry_helper::$javascript .= "    default: break;
  };
  return true;
});\n";
    }
    if ($settings['locationId'])
      $r .= '<input type="hidden" name="location:id" id="location:id" value="'.$settings['locationId']."\" />\n";
    $r .= data_entry_helper::text_input(array(
      'fieldname' => 'location:name',
      'label' => lang::get('Site Name'),
      'class' => 'control-width-4 required',
      'disabled' => $settings['canEditBody'] ? '' : ' disabled="disabled" '
    ));
    if (!$settings['canEditBody']){
      $r .= '<p>'.lang::get('This site cannot be edited because there are walks recorded on it. Please contact the site administrator if you think there are details which need changing.').'</p>';
    } else if(count($settings['walks']) > 0) { // can edit it
      $r .= '<p>'.lang::get('This site has walks recorded on it. Please do not change the site details without considering the impact on the existing data.').'</p>';
    }
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach($list as $system) {
      $systems[$system] = lang::get($system);
    }
    if(isset(data_entry_helper::$entity_to_load['location:centroid_sref_system']) &&
        in_array(data_entry_helper::$entity_to_load['location:centroid_sref_system'], array('osgb','osie')))
      data_entry_helper::$entity_to_load['location:centroid_sref_system'] = strtoupper(data_entry_helper::$entity_to_load['location:centroid_sref_system']);
    $r .= data_entry_helper::sref_and_system(array(
      'fieldname' => 'location:centroid_sref',
      'geomFieldname' => 'location:centroid_geom',
      'label' => 'Grid Ref.',
      'systems' => $systems,
      'class' => 'required',
      'helpText' => lang::get('Click on the map to set the central grid reference.'),
      'disabled' => $settings['canEditBody'] ? '' : ' disabled="disabled" '
    ));
    if ($settings['locationId'] && data_entry_helper::$entity_to_load['location:code']!='' && data_entry_helper::$entity_to_load['location:code'] != null)
      $r .= data_entry_helper::text_input(array(
        'fieldname' => 'location:code',
        'label' => lang::get('Site Code'),
        'class' => 'control-width-4',
        'disabled' => ' readonly="readonly" '
      ));
    else
      $r .= "<p>".lang::get('The Site Code will be allocated by the Administrator.')."</p>";
      
    // setup the map options
    $options = iform_map_get_map_options($args, $auth['read']);
    // find the form blocks that need to go below the map.
    $bottom = '';
    $bottomBlocks = explode("\n", isset($args['bottom_blocks']) ? $args['bottom_blocks'] : '');
    foreach ($bottomBlocks as $block) {
      $bottom .= get_attribute_html($settings['attributes'], $args, array('extraParams'=>$auth['read'], 'disabled' => $settings['canEditBody'] ? '' : ' disabled="disabled" '), $block);
    }
    // other blocks to go at the top, next to the map
    if(isset($args['site_help']) && $args['site_help'] != ''){
      $r .= '<p class="ui-state-highlight page-notice ui-corner-all">'.t($args['site_help']).'</p>';
    }
    $r .= get_attribute_html($settings['attributes'], $args, array('extraParams'=>$auth['read'])); 
    $r .= '</fieldset>';
    $r .= "</div>"; // left
    $r .= '<div class="right" style="width: 44%">';
    if (!$settings['locationId']) {
      $help = t('Use the search box to find a nearby town or village, then drag the map to pan and click on the map to set the centre grid reference of the transect. '.
          'Alternatively if you know the grid reference you can enter it in the Grid Ref box on the left.');
      $r .= '<p class="ui-state-highlight page-notice ui-corner-all">'.$help.'</p>';
      $r .= data_entry_helper::georeference_lookup(array(
        'label' => lang::get('Search for place'),
        'driver'=>$args['georefDriver'],
        'georefPreferredArea' => $args['georefPreferredArea'],
        'georefCountry' => $args['georefCountry'],
        'georefLang' => $args['language'],
        'readAuth' => $auth['read']
      ));
    }
    if(isset($args['maxPrecision']) && $args['maxPrecision'] != ''){
      $options['clickedSrefPrecisionMax'] = $args['maxPrecision'];
    }
    if(isset($args['minPrecision']) && $args['minPrecision'] != ''){
      $options['clickedSrefPrecisionMin'] = $args['minPrecision'];
    }
    $olOptions = iform_map_get_ol_options($args);
    $options['clickForSpatialRef']=$settings['canEditBody'];
    $r .= map_helper::map_panel($options, $olOptions);
    $r .= '</div></div>'; // right    
    if (!empty($bottom))
      $r .= $bottom;
    if ($args['branch_assignment_permission'] != '') {
      if ($settings['canAllocBranch'] || $settings['locationId'])
        $r .= self::get_branch_assignment_control($auth['read'], $settings['branchCmsUserAttr'], $args, $settings);
    }
    if ($args['allow_user_assignment']) {
      if ($settings['canAllocUser']) {
        $r .= self::get_user_assignment_control($auth['read'], $settings['cmsUserAttr'], $args);
      } else if (!$settings['locationId']) {
        // for a new record, we need to link the current user to the location if they are not admin.
        global $user;
        $r .= '<input type="hidden" name="locAttr:'.self::$cmsUserAttrId.'" value="'.$user->uid.'">';
      }
    }
    if ($settings['canEditBody'])
      $r .= '<button type="submit" class="indicia-button right">'.lang::get('Save').'</button>';
    
    if($settings['canEditBody'] && $settings['locationId'])
      $r .= '<button type="button" class="indicia-button right" id="delete-transect">'.lang::get('Delete').'</button>' ;
    $r .='</form>';
    $r .= '</div>'; // site-details
    // This must go after the map panel, so it has created its toolbar
    data_entry_helper::$onload_javascript .= "$('#current-section').change(selectSection);\n";
    if($settings['canEditBody'] && $settings['locationId']) {
      $walkIDs = array();
      foreach($settings['walks'] as $walk) 
        $walkIDs[] = $walk['id'];
      $sectionIDs = array();
      foreach($settings['sections'] as $code=>$section)
        $sectionIDs[] = $section['id'];
      data_entry_helper::$javascript .= "
deleteSurvey = function(){
  if(confirm(\"".(count($settings['walks']) > 0 ? count($settings['walks']).' '.lang::get('walks will also be deleted when you delete this location.').' ' : '').lang::get('Are you sure you wish to delete this location?')."\")){
    deleteWalks([".implode(',',$walkIDs)."]);
    deleteSections([".implode(',',$sectionIDs)."]);
    $('#delete-transect').html('Deleting Site');
    deleteLocation(".$settings['locationId'].");
    $('#delete-transect').html('Done');
    window.location='".url($args['sites_list_path'])."';
  };
};
$('#delete-transect').click(deleteSurvey);
";
    }
    return $r;
  }
}
