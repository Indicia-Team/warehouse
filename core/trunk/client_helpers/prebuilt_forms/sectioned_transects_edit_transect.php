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
    // Need this or the lang class is not loaded
    iform_load_helpers(array('data_entry_helper'));
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
          ), array(
            'name' => 'maxSectionCount',
            'label' => lang::get('Max. Section Count'),
            'type' => 'text_input',
            'description' => lang::get('The maximum number of sections a user is allowed to create for a transect site.'),
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
            'type'=>'text_input',
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
            'name'=>'spatial_systems',
            'caption'=>'Allowed Spatial Ref Systems',      
            'description'=>'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code number such as 4326).',
            'type'=>'string',
            'group'=>'Other Map Settings'
          )
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
    $checks=self::check_prerequisites();
    if ($checks!==true)
      return $checks;
    iform_load_helpers(array('map_helper'));
    data_entry_helper::add_resource('jquery_form');
    self::$ajaxFormUrl = iform_ajaxproxy_url($node, 'location');
    self::$ajaxFormSampleUrl = iform_ajaxproxy_url($node, 'sample');
    if (function_exists('url')) {
      $args['section_edit_path'] = url($args['section_edit_path']);
    }
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $settings = array(
      'locationTypes' => helper_base::get_termlist_terms($auth, 'indicia:location_types', array('Transect', 'Transect Section')),
      'locationId' => isset($_GET['id']) ? $_GET['id'] : null
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
    if (false==$settings['cmsUserAttr'] = extract_cms_user_attr($settings['attributes']))
      return 'This form is designed to be used with the CMS User ID attribute setup for locations in the survey.';
    // keep a copy of the location_attribute_id so we can use it later.
    self::$cmsUserAttrId = $settings['cmsUserAttr']['attributeId'];
    data_entry_helper::$javascript .= "indiciaData.sections = {};\n";
    $settings['sections']=array();
    if ($settings['locationId']) {
      data_entry_helper::load_existing_record($auth['read'], 'location', $settings['locationId']);
      // find the number of sections attribute.
      foreach($settings['attributes'] as $attr) {
        if ($attr['caption']==='No. of sections') {
          $settings['numSectionsAttr'] = $attr['fieldname'];
          for ($i=1; $i<=$attr['displayValue']; $i++) {
            $settings['sections']["S$i"]=null;
          }
          data_entry_helper::$javascript .= "$('#".str_replace(':','\\\\:',$attr['id'])."').attr('min',".$attr['displayValue'].").attr('max',".$args['maxSectionCount'].");\n";
        }
      }
      $sections = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $auth['read'] + array('view'=>'detail','parent_id'=>$settings['locationId'],'deleted'=>'f','orderby'=>'code'),
        'nocache' => true
      ));
      foreach($sections as $section) {
        $code = $section['code'];
        data_entry_helper::$javascript .= "indiciaData.sections.$code = {'geom':'".$section['boundary_geom']."','id':'".$section['id']."'};\n";
        $settings['sections'][$code]=$section;
      }
      $settings['walks'] = data_entry_helper::get_population_data(array(
        'table' => 'sample',
        'extraParams' => $auth['read'] + array('view'=>'detail','location_id'=>$settings['locationId'],'deleted'=>'f'),
        'nocache' => true
      ));
      // only set cantEdit if you can't, otherwise is not present
      if(!(
           (isset($args['managerPermission']) && $args['managerPermission']!="" && user_access($args['managerPermission'])) ||
           (count($settings['walks']) == 0)
          )){
        $settings['cantEdit'] = true;
      }
    } else {
      foreach($settings['attributes'] as $attr) {
        if ($attr['caption']==='No. of sections') {
          $settings['numSectionsAttr'] = $attr['fieldname'];
          data_entry_helper::$javascript .= "$('#".str_replace(':','\\\\:',$attr['id'])."').attr('min',1).attr('max',".$args['maxSectionCount'].");\n";
        }
      }
    }
    $r .= '<div id="controls">';
    $headerOptions = array('tabs'=>array('#site-details'=>lang::get('Site Details')));
    if ($settings['locationId']) {
      $headerOptions['tabs']['#your-route'] = lang::get('Your Route');
      $headerOptions['tabs']['#section-details'] = lang::get('Section Details');
    }
    if (count($headerOptions['tabs'])) {
      $r .= data_entry_helper::tab_header($headerOptions);
      data_entry_helper::enable_tabs(array(
          'divId'=>'controls',
          'style'=>$args['interface'],
          'progressBar' => isset($args['tabProgress']) && $args['tabProgress']==true
      ));
    }
    $r .= self::get_site_tab($auth, $args, $settings);
    if ($settings['locationId']) {
      $r .= self::get_your_route_tab($auth, $args, $settings);
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
    data_entry_helper::$javascript .= "indiciaData.numSectionsAttrName = \"".$settings['numSectionsAttr']."\";\n";
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
      'disabled' => isset($settings['cantEdit']) ? ' disabled="disabled" ' : ''
    ));
    if (isset($settings['cantEdit'])){
      $r .= '<p>'.lang::get('This site cannot be edited because there are walks recorded on it. Please contact the site administrator if you think there are details which need changing.').'</p>';
    } else if(count($settings['walks']) > 0 && (isset($args['managerPermission']) && $args['managerPermission']!="" && user_access($args['managerPermission']))) {
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
      'disabled' => isset($settings['cantEdit']) ? ' disabled="disabled" ' : ''
    ));
    $r .= data_entry_helper::text_input(array(
      'fieldname' => 'location:code',
      'label' => lang::get('Site Code'),
      'class' => 'control-width-4',
      'disabled' => ' readonly="readonly" '
    ));
    
    // setup the map options
    $options = iform_map_get_map_options($args, $auth['read']);
    // find the form blocks that need to go below the map.
    $bottom = '';
    $bottomBlocks = explode("\n", isset($args['bottom_blocks']) ? $args['bottom_blocks'] : '');
    foreach ($bottomBlocks as $block) {
      $bottom .= get_attribute_html($settings['attributes'], $args, array('extraParams'=>$auth['read'], 'disabled' => isset($settings['cantEdit']) ? ' disabled="disabled" ' : ''), $block);
    }
    // other blocks to go at the top, next to the map
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
        'georefLang' => $args['language']
      ));
    }
    $olOptions = iform_map_get_ol_options($args);
    if (isset($settings['cantEdit']))
      $options['clickForSpatialRef']=false;
    $r .= map_helper::map_panel($options, $olOptions);
    $r .= '</div></div>'; // right    
    if (!empty($bottom))
      $r .= $bottom;
    if (user_access('indicia data admin'))
      $r .= self::get_user_assignment_control($auth['read'], $settings['cmsUserAttr'], $args);
    else if (!$settings['locationId']) {
      // for a new record, we need to link the current user to the location if they are not admin.
      global $user;
      $r .= '<input type="hidden" name="locAttr:'.self::$cmsUserAttrId.'" value="'.$user->uid.'">';
    }
    if (!isset($settings['cantEdit']))
      $r .= '<input type="submit" value="'.lang::get('Save').'" class="form-button right" />';
    
    if(!isset($settings['cantEdit']) && $settings['locationId'])
      $r .= '<input type="button" class="form-button right" value="'.lang::get('Delete').'">' ;
    $r .='</form>';
    $r .= '</div>'; // site-details
    // This must go after the map panel, so it has created its toolbar
    data_entry_helper::$onload_javascript .= "$('#current-section').change(selectSection);\n";
    if(!isset($settings['cantEdit']) && $settings['locationId']) {
      $r .= "<div style=\"display:none\" />
<form id=\"form-delete-location\" method=\"POST\">".$auth['write']."
<input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />
<input type=\"hidden\" name=\"survey_id\" value=\"".$args['survey_id']."\" />
<input type=\"hidden\" name=\"location:id\" value=\"".$settings['locationId']."\" />
<input type=\"hidden\" name=\"location:deleted\" value=\"t\" />
</form>
</div>";
      $walkIDs = array();
      foreach($settings['walks'] as $walk) $walkIDs[] = $walk['id'];
      data_entry_helper::$javascript .= "
deleteSurvey = function(sampleID){
  if(confirm(\"".(count($settings['walks']) > 0 ? count($settings['walks']).' '.lang::get('walks will be flagged as deleted when you delete this location.').' ' : '').lang::get('Are you sure you wish to delete this location?')."\")){
    deleteWalks([".implode(',',$walkIDs)."]);
    jQuery('#form-delete-location').submit();
  };
};";
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
    if (!isset($settings['cantEdit'])){
      $options['toolbarPrefix'] = self::section_selector($settings, 'section-select-route');
      if(count($settings['sections'])>1) // do not allow deletion of last section.
        $options['toolbarSuffix'] = '<input type="button" value="'.lang::get('Delete').'" class="remove-section form-button right" title="'.lang::get('Remove the highlighted section. The form will be reloaded after the section is deleted.').'">';
      // also let the user click on a feature to select it. The highlighter just makes it easier to select one.
      // these controls are not present in read-only mode: all you can do is look at the map.
      $options['standardControls'][] = 'selectFeature';
      $options['standardControls'][] = 'hoverFeatureHighlight';
      $options['standardControls'][] = 'drawLine';
      $options['standardControls'][] = 'modifyFeature';
      $help = lang::get('Select a section from the list then click on the map to draw the route and double click to finish. '.
        'You can also select a section using the query tool to click on the section lines. If you make a mistake then use the Modify a feature '.
        'tool to correct the line shape, or redraw the line to replace it entirely.');
      $r .= '<p class="ui-state-highlight page-notice ui-corner-all">'.$help.'</p>';
    }
    $options['clickForSpatialRef'] = false;
    $r .= map_helper::map_panel($options, $olOptions);
    $r .= '</div>';
    return $r;  
  }
  
  private static function get_section_details_tab($auth, $args, $settings) {
    $r = '<div id="section-details" class="ui-helper-clearfix">';
    $r .= '<form method="post" id="section-form" action="'.self::$ajaxFormUrl.'">';
    $r .= '<fieldset><legend>'.lang::get('Section Details').'</legend>';
    // Output a selector for the current section.    
    $r .= self::section_selector($settings, 'section-select');
    if (!isset($settings['cantEdit'])){
      $r .= "<input type=\"hidden\" name=\"location:id\" value=\"\" id=\"section-location-id\" />\n";
      $r .= '<input type="hidden" name="website_id" value="'.$args['website_id']."\" />\n";
    }
    // force a blank centroid, so that the Warehouse will recalculate it from the boundary
    //$r .= "<input type=\"hidden\" name=\"location:centroid_geom\" value=\"\" />\n";   
    $r .= get_attribute_html($settings['section_attributes'], $args, array('extraParams'=>$auth['read'], 'disabled' => isset($settings['cantEdit']) ? ' disabled="disabled" ' : ''));
    if (!isset($settings['cantEdit']))
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
    $query = db_query("select uid, name from {users} where name<>'' order by name");
    $users = array();
    while ($user = db_fetch_object($query)) 
      $users[$user->uid] = $user->name;
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
    // tell the javascript which attr to save the user ID into
    data_entry_helper::$javascript .= "indiciaData.locCmsUsrAttr = " . self::$cmsUserAttrId . ";\n";
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
   * After saving a transect, reload the transect so that the user can continue to save the sections.
   */
  public static function get_redirect_on_success($values, $args) {
    if (!isset($values['location:id'])) {
      return drupal_get_path_alias($_GET['q']).'#your-route';
    }
  }

}
