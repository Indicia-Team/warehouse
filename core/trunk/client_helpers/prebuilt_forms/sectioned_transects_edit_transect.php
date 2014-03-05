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

require_once 'includes/map.php';
require_once 'includes/form_generation.php';

/**
 * 
 * 
 * @package Client
 * @subpackage PrebuiltForms
 * Form for adding or editing the site details on a transect which contains a number of sections.
 */
class iform_sectioned_transects_edit_transect {

  /**
   * @var int Contains the id of the location attribute used to store the CMS user ID.
   */
  protected static $cmsUserAttrId;
  private static $cmsUserList = null;
  
  /**
   * @var int Contains the id of the location attribute used to store the CMS user ID.
   */
  protected static $branchCmsUserAttrId;
  
  /**
   * @var string The Url to post AJAX form saves to.
   */
  private static $ajaxFormUrl = null;
  private static $ajaxFormSampleUrl = null;
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_sectioned_transects_edit_transect_definition() {
    return array(
      'title'=>'Transect editor',
      'category' => 'Sectioned Transects',
      'description'=>'Form for adding or editing the site details on a transect which has a number of sub-sections.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
   */
  public static function get_parameters() {
    return array_merge(
        iform_map_get_map_parameters(),
        iform_map_get_georef_parameters(),
        array(
          array(
            'name'=>'managerPermission',
            'caption'=>'Drupal Permission for Manager mode',
            'description'=>'Enter the Drupal permission name to be used to determine if this user is a manager. Entering this will allow the identified users to delete or modify the site even there are walks (samples) associated with it.',
            'type'=>'string',
            'required' => false
          ),
          array(
            'name' => 'branch_assignment_permission',
            'label' => 'Drupal Permission name for Branch Manager',
            'type' => 'string',
            'description' => 'If you do not want to use the Branch Manager functionality, leave this blank. '.
                             'Otherwise, specify the name of a permission to which when assigned to a user determines that the user is a branch manager. '.
                             '<br />Requires a single-value Branch CMS User ID integer attribute on the locations.',
            'required'=>false,
            'group' => 'Transects Editor Settings'
          ), array(
            'name' => 'maxSectionCount',
            'label' => 'Max. Section Count',
            'type' => 'int',
            'description' => 'The maximum number of sections a user is allowed to create for a transect site. If there is no user selectable attribute to set the number of sections, then the number is fixed at this value and the user will not be able to delete sections.',
            'group' => 'Transects Editor Settings'
          ), array(
            'name'=>'survey_id',
            'caption'=>'Survey',
            'description'=>'The survey that data will be posted into.',
            'type'=>'select',
            'table'=>'survey',
            'captionField'=>'title',
            'valueField'=>'id',
            'siteSpecific'=>true
          ), array(
            'name'=>'sites_list_path',
            'caption'=>'Site list page path',
            'description'=>'Enter the path to the page which the site list is on.',
            'type' => 'string',
            'required' => true,
            'group'=>'Transects Editor Settings'
          ), array(
            'name'=>'transect_type_term',
            'caption'=>'Transect type term',
            'description'=>'Select the term used for transect location types.',
            'type' => 'select',
            'table'=>'termlists_term',
            'captionField'=>'term',
            'valueField'=>'term',
            'extraParams' => array('termlist_external_key'=>'indicia:location_types'),
            'required' => true,
            'group'=>'Transects Editor Settings'
          ), array(
            'name'=>'section_type_term',
            'caption'=>'Section type term',
            'description'=>'Select the term used for section location types.',
            'type' => 'select',
            'table'=>'termlists_term',
            'captionField'=>'term',
            'valueField'=>'term',
            'extraParams' => array('termlist_external_key'=>'indicia:location_types'),
            'required' => true,            
            'group'=>'Transects Editor Settings'
          ), array(
            'name'=>'bottom_blocks',
            'caption'=>'Form blocks to place at bottom',
            'description'=>'A list of the blocks which need to be placed at the bottom of the form, below the map.',
            'type'=>'textarea',
            'group'=>'Transects Editor Settings',
            'siteSpecific'=>true,
            'required'=>false
          ), array(
            'name'=>'site_help',
            'caption'=>'Site Help Text',
            'description'=>'Help text to be placed on the Site tab, before the attributes.',
            'type'=>'textarea',
            'group'=>'Transects Editor Settings',
            'required'=>false
          ), array(
            'name'=>'spatial_systems',
            'caption'=>'Allowed Spatial Ref Systems',      
            'description'=>'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code number such as 4326).',
            'type'=>'text_input',
            'group'=>'Other Map Settings'
          ),
          array(
            'name'=>'maxPrecision',
            'caption'=>'Max Sref Precision',
            'description'=>'The maximum precision to be applied when determining the SREF. Leave blank to not set.',
            'type'=>'int',
            'required'=>false,
            'group'=>'Other Map Settings'
          ),
          array(
            'name'=>'minPrecision',
            'caption'=>'Min Sref Precision',
            'description'=>'The minimum precision to be applied when determining the SREF. Leave blank to not set.',
            'type'=>'int',
            'required'=>false,
            'group'=>'Other Map Settings'
          ),
          array(
            'name'=>'route_map_height',
            'caption'=>'Your Route Map Height (px)',
            'description'=>'Height in pixels of the map.',
            'type'=>'int',
            'group'=>'Initial Map View',
            'default'=>600
          ),
          array(
            'name'=>'route_map_buffer',
            'caption'=>'Your Route Map Buffer',
            'description'=>'Factor to multiple the size of the site by, in order to generate a margin around the site when displaying the site on the Your Route tab.',
            'type'=>'string',
            'group'=>'Initial Map View',
            'default'=>'0.1'
          ),
          array(
            'name' => 'allow_user_assignment',
            'label' => 'Allow users to be assigned to transects',
            'type' => 'boolean',
            'description' => 'Can administrators link users to transects that they are allowed to record at? Requires a multi-value CMS User ID attribute on the locations.',
            'default'=>true,
            'required'=>false,
            'group' => 'Transects Editor Settings'
          ),
          array(
            'name'=>'autocalc_section_length_attr_id',
            'caption'=>'Location attribute to autocalc section length',
            'description'=>'Location attribute that stores the section length, if you want it to be autocalculated from the geometry.',
            'type'=>'select',
            'table'=>'location_attribute',
            'valueField'=>'id',
            'captionField'=>'caption',
            'group'=>'Transects Editor Settings',
            'required'=>false
          ),
          array(
            'name'=>'default_section_grid_ref',
            'caption'=>'Default grid ref for a section?',
            'description'=>'Default the grid ref for a section to what?',
            'type'=>'select',
            'lookupValues'=>array(
              'parent'=>'Same as parent transect',
              'sectionCentroid100'=>'100m grid square covering the centroid of the section',
              'sectionStart100'=>'100m grid square covering the start of the section'
            ),
            'default'=>'parent',
            'group'=>'Transects Editor Settings'
          )
        )
    );
  }
  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the 
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
  protected static function getArgDefaults($args) {
      
    if (!isset($args['route_map_height'])) $args['route_map_height'] = 600;
    if (!isset($args['route_map_buffer'])) $args['route_map_buffer'] = 0.1;
    if (!isset($args['allow_user_assignment'])) $args['allow_user_assignment'] = true;
    if (!isset($args['managerPermission'])) $args['managerPermission'] = '';
    if (!isset($args['branch_assignment_permission'])) $args['branch_assignment_permission'] = '';
    
    return $args;
  }

  private static function extract_attr(&$attributes, $caption, $unset=true) {
  	$found=false;
  	foreach($attributes as $idx => $attr) {
  		if (strcasecmp($attr['caption'], $caption)===0) { // should this be untranslated?
  			// found will pick up just the first one
  			if (!$found)
  				$found=$attr;
  			if ($unset)
  				unset($attributes[$idx]);
  			else
  				// don't bother looking further if not unsetting them all
  				break;
  		}
  	}
  	return $found;
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
    $checks=self::check_prerequisites();
    $args = self::getArgDefaults($args);
    if ($checks!==true)
      return $checks;
    iform_load_helpers(array('map_helper'));
    data_entry_helper::add_resource('jquery_form');
    self::$ajaxFormUrl = iform_ajaxproxy_url($node, 'location');
    self::$ajaxFormSampleUrl = iform_ajaxproxy_url($node, 'sample');
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $typeTerms = array(
      empty($args['transect_type_term']) ? 'Transect' : $args['transect_type_term'],
      empty($args['section_type_term']) ? 'Section' : $args['section_type_term']
    );
    $settings = array(
      'locationTypes' => helper_base::get_termlist_terms($auth, 'indicia:location_types', $typeTerms),
      'locationId' => isset($_GET['id']) ? $_GET['id'] : null,
      'canEditBody' => true,
      'canEditSections' => true, // this is specifically the number of sections: so can't delete or change the attribute value.
      // Allocations of Branch Manager are done by a person holding the managerPermission.
      'canAllocBranch' => $args['managerPermission']=="" || user_access($args['managerPermission']),
      // Allocations of Users are done by a person holding the managerPermission or the allocate Branch Manager.
      // The extra check on this for branch managers is done later
      'canAllocUser' => $args['managerPermission']=="" || user_access($args['managerPermission']) 
    );
    $settings['attributes'] = data_entry_helper::getAttributes(array(
        'id' => $settings['locationId'],
        'valuetable'=>'location_attribute_value',
        'attrtable'=>'location_attribute',
        'key'=>'location_id',
        'fieldprefix'=>'locAttr',
        'extraParams'=>$auth['read'],
        'survey_id'=>$args['survey_id'],
        'location_type_id' => $settings['locationTypes'][0]['id'],
        'multiValue' => true
    ));
    $settings['section_attributes'] = data_entry_helper::getAttributes(array(
        'valuetable'=>'location_attribute_value',
        'attrtable'=>'location_attribute',
        'key'=>'location_id',
        'fieldprefix'=>'locAttr',
        'extraParams'=>$auth['read'],
        'survey_id'=>$args['survey_id'],
        'location_type_id' => $settings['locationTypes'][1]['id'],
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
    }
    
    data_entry_helper::$javascript .= "indiciaData.sections = {};\n";
    $settings['sections']=array();
    $settings['numSectionsAttr'] = "";
    $settings['maxSectionCount'] = $args['maxSectionCount'];
    $settings['autocalcSectionLengthAttrId'] = empty($args['autocalc_section_length_attr_id']) ? 0 : $args['autocalc_section_length_attr_id'];
    $settings['defaultSectionGridRef'] = empty($args['default_section_grid_ref']) ? 'parent' : $args['default_section_grid_ref'];
    if ($settings['locationId']) {
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
      } // for an admin user the defaults apply, which will be can do everything.
      // find the number of sections attribute.
      foreach($settings['attributes'] as $attr) {
        if ($attr['caption']==='No. of sections') {
          $settings['numSectionsAttr'] = $attr['fieldname'];
          for ($i=1; $i<=$attr['displayValue']; $i++) {
            $settings['sections']["S$i"]=null;
          }
          $existingSectionCount = empty($attr['displayValue']) ? 1 : $attr['displayValue'];
          data_entry_helper::$javascript .= "$('#".str_replace(':','\\\\:',$attr['id'])."').attr('min',$existingSectionCount).attr('max',".$args['maxSectionCount'].");\n";
          if(!$settings['canEditSections'])
            data_entry_helper::$javascript .= "$('#".str_replace(':','\\\\:',$attr['id'])."').attr('readonly','readonly').css('color','graytext');\n";
        }
      }
      $sections = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $auth['read'] + array('view'=>'detail','parent_id'=>$settings['locationId'],'deleted'=>'f','orderby'=>'code'),
        'nocache' => true
      ));
      foreach($sections as $section) {
        $code = $section['code'];
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
    data_entry_helper::$javascript .= "indiciaData.sectionTypeId = '".$settings['locationTypes'][1]['id']."';\n";
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
  
  private static function check_prerequisites() {
    // check required modules installed
    if (isset($_POST['enable'])) {
      module_enable(array('iform_ajaxproxy'));
      drupal_set_message(lang::get('The Indicia AJAX Proxy module has been enabled.', 'info'));      
    }
    $ok=true;
    if (!module_exists('iform_ajaxproxy')) {
       drupal_set_message('This form must be used in Drupal with the Indicia AJAX Proxy module enabled.');
       $ok=false;
    }
    if (!function_exists('iform_ajaxproxy_url')) {
      drupal_set_message(lang::get('The Indicia AJAX Proxy module must be enabled to use this form. This lets the form save verifications to the '.
          'Indicia Warehouse without having to reload the page.'));
      $r .= '<form method="post">';
      $r .= '<input type="hidden" name="enable" value="t"/>';
      $r .= '<input type="submit" value="'.lang::get('Enable Indicia AJAX Proxy').'">';
      $r .= '</form>';
      return $r;
    }
    return $ok;
  }
  
  private static function get_site_tab($auth, $args, $settings) {
    $r = '<div id="site-details" class="ui-helper-clearfix">';
    $r .= '<form method="post" id="input-form">';
    $r .= $auth['write'];    
    $r .= '<div id="cols" class="ui-helper-clearfix"><div class="left" style="width: 54%">';
    $r .= '<fieldset><legend>'.lang::get('Transect Details').'</legend>';
    $r .= "<input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= "<input type=\"hidden\" name=\"survey_id\" value=\"".$args['survey_id']."\" />\n";    
    $r .= "<input type=\"hidden\" name=\"location:location_type_id\" value=\"".$settings['locationTypes'][0]['id']."\" />\n";
    if ($settings['locationId'])
      $r .= '<input type="hidden" name="location:id" id="location:id" value="'.$settings['locationId']."\" />\n";
    $r .= data_entry_helper::text_input(array(
      'fieldname' => 'location:name',
      'label' => lang::get('Transect Name'),
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
    $('#delete-transect').html('Deleting Transect');
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
  
  private static function get_your_route_tab($auth, $args, $settings) {
    $r = '<div id="your-route" class="ui-helper-clearfix">';
    $olOptions = iform_map_get_ol_options($args);
    $options = iform_map_get_map_options($args, $auth['read']);
    $options['divId'] = 'route-map';
    $options['toolbarDiv'] = 'top';
    $options['tabDiv']='your-route';
    $options['gridRefHint']=true;
    if ($settings['canEditBody']){
      $options['toolbarPrefix'] = self::section_selector($settings, 'section-select-route');
      if($settings['canEditSections'] && count($settings['sections'])>1 && $settings['numSectionsAttr'] != "") // do not allow deletion of last section, or if the is no section number attribute
        $options['toolbarSuffix'] = '<input type="button" value="'.lang::get('Remove Section').'" class="remove-section form-button right" title="'.lang::get('Completely remove the highlighted section. The total number of sections will be reduced by one. The form will be reloaded after the section is deleted.').'">';
      else $options['toolbarSuffix'] = '';
      $options['toolbarSuffix'] .= '<input type="button" value="'.lang::get('Erase Route').'" class="erase-route form-button right" title="'.lang::get('If the Draw Line control is active, this will erase each drawn point one at a time. If not active, then this will erase the whole highlighted route. This keeps the Section, allowing you to redraw the route for it.').'">';
      if($settings['canEditSections'] && count($settings['sections'])<$args['maxSectionCount'] && $settings['numSectionsAttr'] != "") // do not allow insertion of section if it exceeds max number, or if the is no section number attribute
        $options['toolbarSuffix'] .= '<input type="button" value="'.lang::get('Insert Section').'" class="insert-section form-button right" title="'.lang::get('This inserts an extra section after the currently selected section. All subsequent sections are renumbered, increasing by one. All associated occurrences are kept with the moved sections. This can be used to facilitate the splitting of this section.').'">';
      // also let the user click on a feature to select it. The highlighter just makes it easier to select one.
      // these controls are not present in read-only mode: all you can do is look at the map.
      $options['standardControls'][] = 'selectFeature';
      $options['standardControls'][] = 'hoverFeatureHighlight';
      $options['standardControls'][] = 'drawLine';
      $options['standardControls'][] = 'modifyFeature';
      $options['switchOffSrefRetrigger'] = true;
      $help = lang::get('Select a section from the list then click on the map to draw the route and double click to finish. '.
        'You can also select a section using the query tool to click on the section lines. If you make a mistake in the middle '.
        'of drawing a route, then you can use the Erase Route button to remove the last point drawn. After a route has been '.
        'completed use the Modify a feature tool to correct the line shape (either by dragging one of the circles along the '.
        'line to form the correct shape, or by placing the mouse over a circle and pressing the Delete button on your keyboard '.
        'to remove that point). Alternatively you could just redraw the line - this new line will then replace the old one '.
        'completely. If you are not in the middle of drawing a line, the Erase Route button will erase the whole route for the '.
        'currently selected section.').
        ($settings['numSectionsAttr'] != "" ?
           '<br />'.(count($settings['sections'])>1 ?
             lang::get('The Remove Section button will remove the section completely, reducing the number of sections by one.').' '
             : '').
           lang::get('To increase the number of sections, return to the Site Details tab, and increase the value in the No. of sections field there.')
           : '');
      $r .= '<p class="ui-state-highlight page-notice ui-corner-all">'.$help.'</p>';
    }
    $options['clickForSpatialRef'] = false;
    // override the opacity so the parent square does not appear filled in.
    $options['fillOpacity'] = 0;
    // override the map height and buffer size, which are specific to this map.
    $options['height'] = $args['route_map_height'];
    $options['maxZoomBuffer'] = $args['route_map_buffer'];
    
    $r .= map_helper::map_panel($options, $olOptions);
    if(count($settings['section_attributes']) == 0)
      $r .= '<button class="indicia-button right" type="button" title="'.
            lang::get('Returns to My Sites page. Any changes to sections carried out on this page (including creating new ones) are saved to the database as they are done, but changes to the Site Details must be saved using the Save button on that tab.').
            '" onclick="window.location.href=\'' . url($args['redirect_on_success']) . '\'">'.lang::get('Return to My Sites').'</button>';
    $r .= '</div>';
    return $r;  
  }
  
  private static function get_section_details_tab($auth, $args, $settings) {
    $r = '<div id="section-details" class="ui-helper-clearfix">';
    $r .= '<form method="post" id="section-form" action="'.self::$ajaxFormUrl.'">';
    $r .= '<fieldset><legend>'.lang::get('Section Details').'</legend>';
    // Output a selector for the current section.    
    $r .= self::section_selector($settings, 'section-select')."<br/>";
    if ($settings['canEditBody']){
      $r .= "<input type=\"hidden\" name=\"location:id\" value=\"\" id=\"section-location-id\" />\n";
      $r .= '<input type="hidden" name="website_id" value="'.$args['website_id']."\" />\n";
    }
    // for the SRef, we want to be able to edit the sref, but just display the system. Do not want the Geometry.
    $r .= '<label for="imp-sref">Section Grid Ref.:</label><input type="text" value="" class="required" name="location:centroid_sref" id="section-location-sref"><span class="deh-required">*</span>';
    // for the system we need to translate the system: easiest way is to have a disabled select plus a hidden field.
    $systems = array();
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach($list as $system) {
      $systems[$system] = lang::get($system);
    }
    $options = array(
    		'fieldname' => '',
    		'systems' => $systems,
    		'disabled' => ' disabled="disabled"',
    		'id' => 'section-location-system-select');
    // Output the hidden system control
    $r .= '<input type="hidden" id="section-location-system" name="location:centroid_sref_system" value="" />';
    $r .= data_entry_helper::sref_system_select($options);
    // force a blank centroid, so that the Warehouse will recalculate it from the boundary
    //$r .= "<input type=\"hidden\" name=\"location:centroid_geom\" value=\"\" />\n";   
    $r .= get_attribute_html($settings['section_attributes'], $args, array('extraParams'=>$auth['read'], 'disabled' => $settings['canEditBody'] ? '' : ' disabled="disabled" '));
    if ($settings['canEditBody'])
      $r .= '<input type="submit" value="'.lang::get('Save').'" class="form-button right" id="submit-section" />';    
    $r .= '</fieldset></form>';
    $r .= '</div>';
    return $r;
  }
  
  /**
   * Build a row of buttons for selecting the route.
   */
  private static function section_selector($settings, $id) {
    $sectionArr = array();
    foreach ($settings['sections'] as $code=>$section)
      $sectionArr[$code] = $code;
    $selector = '<label for="'.$id.'">'.lang::get('Select section').':</label><ol id="'.$id.'" class="section-select">';
    foreach ($sectionArr as $key=>$value) {
      $classes = array();
      if ($key=='S1') 
        $classes[] = 'selected';
      if (!isset($settings['sections'][$key]))
        $classes[] = 'missing';
      $class = count($classes) ? ' class="'.implode(' ', $classes).'"' : '';
      $selector .= "<li id=\"$id-$value\"$class>$value</li>";
    }
    $selector .= '</ol>';
    return $selector;
  }
  
  /**
   * If the user has permissions, then display a control so that they can specify the list of users associated with this site.
   */
  private static function get_user_assignment_control($readAuth, $cmsUserAttr, $args) {
    if(self::$cmsUserList == null) {
      $query = db_query("select uid, name from {users} where name <> '' order by name");
      $users = array();
      // there have been DB API changes for Drupal7: db_query now returns the result array.
      if(version_compare(VERSION, '7', '<')) {
        while ($user = db_fetch_object($query)) 
          $users[$user->uid] = $user->name;
      } else {
        foreach ($query as $user) 
          $users[$user->uid] = $user->name;
      }
      self::$cmsUserList = $users;
  	} else $users= self::$cmsUserList;
    $r = '<fieldset id="alloc-recorders"><legend>'.lang::get('Allocate recorders to the site').'</legend>';
    $r .= data_entry_helper::select(array(
      'label' => lang::get('Select user'),
      'fieldname' => 'cmsUserId',
      'lookupValues' => $users,
      'afterControl' => '<button id="add-user" type="button">'.lang::get('Add').'</button>'
    ));
    $r .= '<table id="user-list" style="width: auto">';
    $rows = '';
    // cmsUserAttr needs to be multivalue
    if (isset($cmsUserAttr['default']) && !empty($cmsUserAttr['default'])) {
      foreach($cmsUserAttr['default'] as $value) {
        $rows .= '<tr><td id="user-'.$value['default'].'"><input type="hidden" name="'.$value['fieldname'].'" '.
            'value="'.$value['default'].'"/>'.$users[$value['default']].
            '</td><td><div class="ui-state-default ui-corner-all"><span class="remove-user ui-icon ui-icon-circle-close"></span></div></td></tr>';
        }
    }
    if (empty($rows))
      $rows = '<tr><td colspan="2"></td></tr>';
    $r .= "$rows</table>\n";
    $r .= '</fieldset>';
    if ($args['allow_user_assignment']) {
      // tell the javascript which attr to save the user ID into
      data_entry_helper::$javascript .= "indiciaData.locCmsUsrAttr = " . self::$cmsUserAttrId . ";\n";
    }
    return $r;
  }

  private static function get_branch_assignment_control($readAuth, $branchCmsUserAttr, $args, $settings) {
    if(!$branchCmsUserAttr) return '<span style="display:none;">No branch location attribute</span>'; // no attribute so don't display
    if(self::$cmsUserList == null) {
      $query = db_query("select uid, name from {users} where name <> '' order by name");
      $users = array();
      // there have been DB API changes for Drupal7: db_query now returns the result array.
      if(version_compare(VERSION, '7', '<')) {
        while ($user = db_fetch_object($query)) 
          $users[$user->uid] = $user->name;
      } else {
        foreach ($query as $user) 
          $users[$user->uid] = $user->name;
      }
      self::$cmsUserList = $users;
    } else $users= self::$cmsUserList;
    
    // next reduce the list to branch users
    if($settings['canAllocBranch']){ // only check the users permissions if can change value - for performance reasons.
      $new_users = array();
      foreach ($users as $uid=>$name){
        $account = user_load($uid);
        if(user_access($args['branch_assignment_permission'], $account))
          $new_users[$uid]=$name;
      }
      $users = $new_users;
    }

    $r = '<fieldset id="alloc-branch"><legend>'.lang::get('Site Branch Allocation').'</legend>';
    if($settings['canAllocBranch']) {
      $r .= data_entry_helper::select(array(
        'label' => lang::get('Select Branch Manager'),
        'fieldname' => 'branchCmsUserId',
        'lookupValues' => $users,
        'afterControl' => '<button id="add-branch-coord" type="button">'.lang::get('Add').'</button>'
      ));
      // tell the javascript which attr to save the user ID into
      data_entry_helper::$javascript .= "indiciaData.locBranchCmsUsrAttr = " . self::$branchCmsUserAttrId . ";\n";
    }
    $r .= '<table id="branch-coord-list" style="width: auto">';
    $rows = '';
    // cmsUserAttr needs to be multivalue
    if (isset($branchCmsUserAttr['default']) && !empty($branchCmsUserAttr['default'])) {
      foreach($branchCmsUserAttr['default'] as $value) {
        if($settings['canAllocBranch'])
          $rows .= '<tr><td id="branch-coord-'.$value['default'].'"><input type="hidden" name="'.$value['fieldname'].'" '.
            'value="'.$value['default'].'"/>'.$users[$value['default']].
            '</td><td><div class="ui-state-default ui-corner-all"><span class="remove-user ui-icon ui-icon-circle-close"></span></div></td></tr>';
        else
          $rows .= '<tr><td>'.$users[$value['default']].'</td><td></td></tr>';
      }
    }
    if (empty($rows))
      $rows = '<tr><td colspan="2"></td></tr>';
    $r .= "$rows</table>\n";
    $r .= '</fieldset>';

    return $r;
  }
    
  /**
   * Construct a submission for the location.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    $s = submission_builder::build_submission($values, 
      array(
        'model' => 'location'
      )
    );
    // on first save of a new transect, link it to the website.
    if (empty($values['location:id']))
      $s['subModels'] = array(
        array(
          'fkId' => 'location_id', 
          'model' => array(
            'id' => 'locations_website',
            'fields' => array(
              'website_id' => $args['website_id']
            )
          )
        )
      );
    return $s;
  }
  
  /**
   * After saving a new transect, reload the transect so that the user can continue to save the sections.
   */
  public static function get_redirect_on_success($values, $args) {
    if (!isset($values['location:id'])) {
      return drupal_get_path_alias($_GET['q']).'#your-route';
    }
  }

}
