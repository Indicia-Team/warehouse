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
    // ensure that the lang class loads
    iform_load_helpers(array('data_entry_helper'));  
    return array_merge(
        iform_map_get_map_parameters(),
        iform_map_get_georef_parameters(),
        array(
          array(
            'name' => 'maxSectionCount',
            'label' => lang::get('Max. Section Count'),
            'type' => 'text_input',
            'description' => lang::get('The maximum number of sections a user is allowed to create for a transect site.'),
            'group' => 'UKBMS Settings'
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
            'group'=>'UKBMS Settings'
          ), array(
            'name'=>'section_edit_path',
            'caption'=>'Section edit page path',
            'description'=>'Enter the path to the page which the section editor is on.',
            'type'=>'text_input',
            'group'=>'UKBMS Settings'
          ),
          array(
            'name'=>'spatial_systems',
            'caption'=>'Allowed Spatial Ref Systems',      
            'description'=>'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code number such as 4326).',
            'type'=>'string',
            'group'=>'Other Map Settings'
          ),
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
    require_once drupal_get_path('module', 'iform').'/client_helpers/map_helper.php';
    if (function_exists('url')) {
      $args['section_edit_path'] = url($args['section_edit_path']);
    }
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $settings = array(
      'locationTypes' => helper_base::get_termlist_terms($auth, 'indicia:location_types', array('Transect', 'Transect Section')),
      'locationId' => isset($_GET['id']) ? $_GET['id'] : null
    );
    if ($settings['locationId']) {
      data_entry_helper::load_existing_record($auth['read'], 'location', $settings['locationId']);
      $settings['sections'] = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $auth['read'] + array('view'=>'detail','parent_id'=>$settings['locationId'],'deleted'=>'f'),
        'nocache' => true
      ));
      data_entry_helper::$javascript .= "indiciaData.sections = {};\n";
      foreach($settings['sections'] as $section) {
        $code = strtolower($section['code']);
        data_entry_helper::$javascript .= "indiciaData.sections.$code = {'geom':'".$section['boundary_geom']."','id':'".$section['id']."'};\n";
      }
    } else {
      $settings['sections']=array();
    }
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
    if (false==$settings['cmsUserAttr'] = extract_cms_user_attr($settings['attributes']))
      return 'This form is designed to be used with the CMS User ID attribute setup for locations in the survey.';
    // keep a copy of the location_attribute_id so we can use it later.
    self::$cmsUserAttrId = $settings['cmsUserAttr']['attributeId'];
    $r = '<form method="post" id="input-form">';
    $r .= $auth['write'];
    $r .= '<div id="controls">';
    $customAttributeTabs = array_merge(array(
      'Site' => array('[*]'),
    ), get_attribute_tabs($settings['attributes']));
    if (count($customAttributeTabs)>1) {
      $headerOptions = array('tabs'=>array());
      foreach($customAttributeTabs as $tab=>$content) {
        $alias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
        $headerOptions['tabs']['#'.$alias] = lang::get($tab); 
      }
      $r .= data_entry_helper::tab_header($headerOptions);
      data_entry_helper::enable_tabs(array(
          'divId'=>'controls',
          'style'=>$args['interface'],
          'progressBar' => isset($args['tabProgress']) && $args['tabProgress']==true
      ));
    }
    
    foreach($customAttributeTabs as $tab=>$content) {
      if ($tab=='Site')
        $r .= self::get_site_tab($auth, $args, $settings);
      else {
        $alias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
        $r .= "\n<div id=\"$alias\">\n";
        $r .= get_attribute_html($settings['attributes'], $args, array('extraParams'=>$auth['read']), $tab); 
        $r .= "</div>\n";
      }
        
    }
    $r .= '</div>'; // controls
    $r .= '<input type="submit" value="'.lang::get('Save').'" class="ui-state-default ui-corner-all" />';
    $r .='</form>';
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
    return $r;
  }
  
  private static function get_site_tab($auth, $args, $settings) {
    $r = '<div id="site" class="ui-helper-clearfix">';
    $r .= '<div class="left" style="width: 44%">';
    $r .= '<fieldset><legend>'.lang::get('Transect Details').'</legend>';
    $r .= "<input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= "<input type=\"hidden\" name=\"survey_id\" value=\"".$args['survey_id']."\" />\n";    
    $r .= "<input type=\"hidden\" name=\"location:location_type_id\" value=\"".$settings['locationTypes'][0]['id']."\" />\n";
    if ($settings['locationId'])
      $r .= '<input type="hidden" name="location:id" value="'.$settings['locationId']."\" />\n";
    $r .= data_entry_helper::text_input(array(
      'fieldname' => 'location:name',
      'label' => lang::get('Transect Name'),
      'class' => 'control-width-4 required'
    ));
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach($list as $system) {
      $systems[$system] = lang::get($system);
    }
    $r .= data_entry_helper::sref_and_system(array(
      'fieldname' => 'location:centroid_sref',
      'geomFieldname' => 'location:centroid_geom',
      'label' => 'Grid Ref.',
      'systems' => $systems,
      'class' => 'required'
    ));
    
    // setup the map options
    $options = iform_map_get_map_options($args, $auth['read']);
    $options['toolbarDiv'] = 'top';
    if (!empty($settings['sections'])) {
      // if we have an existing site with sections, output a selector for the current section.
      $sectionArr = array('' => htmlspecialchars(lang::get('<select>')));
      for ($i=1; $i<=count($settings['sections']); $i++)
        $sectionArr[$i] = $i;
      $options['toolbarPrefix'] = data_entry_helper::select(array(
        'fieldname'=>'',
        'id' => 'current-section',
        'label' => lang::get('Select section'),
        'lookupValues' => $sectionArr,
        'suffixTemplate' => 'nosuffix'
      ));
      $options['toolbarPrefix'] .= '<a href="'.$args['section_edit_path'].
          (strpos($args['section_edit_path'], '?')===false ? '?' : '&').
          'from=transect&transect_id='.$settings['locationId'].'&section_id=0" id="section-edit" style="display: none"><button type="button">' . 
          lang::get('Edit') . '</button></a>';
      // also let the user click on a feature to select it. The highlighter just makes it easier to select one.
      $options['standardControls'][] = 'selectFeature';
      $options['standardControls'][] = 'hoverFeatureHighlight';
    }
    if ($settings['locationId']) {
      $options['toolbarPrefix'] .= '<a href="'.$args['section_edit_path'].
          (strpos($args['section_edit_path'], '?')===false ? '?' : '&').
          'from=transect&transect_id='.$settings['locationId'].'"><button type="button">' . lang::get('Add Section') . '</button></a>';
    }
    $r .= get_attribute_html($settings['attributes'], $args, array('extraParams'=>$auth['read']), 'Site'); 
    $r .= data_entry_helper::textarea(array(
      'label' => lang::get('Notes'),
      'fieldname' => 'location:comment',
      'class' => 'control-width-4'      
    ));    
    $r .= '</fieldset>';
    if (user_access('indicia data admin'))
      $r .= self::get_user_assignment_control($auth['read'], $settings['cmsUserAttr'], $args);
    elseif (!$settings['locationId']) {
      // for a new record, we need to link the current user to the location if they are not admin.
      global $user;
      $r .= '<input type="hidden" name="locAttr:'.self::$cmsUserAttrId.'" value="'.$user->uid.'">';
    }
    $r .= "</div>";
    $r .= '<div class="right" style="width: '.$options['width'].'px;">';
    if ($settings['locationId']) {
      $help = lang::get('Use the Add Section button to create each section of your transect in turn.');
      if (count($settings['sections'])>0)
        $help .= ' '.lang::get('For existing sections, select a section from the drop down list then click Edit to make changes. '.
            'You can also select a section using the query tool to click on the section lines, or reset the transect centroid grid reference '.
            'using the Click Grid Ref tool.');
    } else {
      $help = t('Use the search box to find a nearby town or village, then drag the map to pan and click on the map to set the centre grid reference of the transect. '.
          'Alternatively if you know the grid reference you can enter it in the Grid Ref box on the left.');
    }
    $r .= '<p class="ui-state-highlight page-notice ui-corner-all">'.$help.'</p>';
    if (!$settings['locationId']) {
      $r .= data_entry_helper::georeference_lookup(array(
        'label' => lang::get('Search for place'),
        'driver'=>$args['georefDriver'],
        'georefPreferredArea' => $args['georefPreferredArea'],
        'georefCountry' => $args['georefCountry'],
        'georefLang' => $args['language']
      ));
    }
    $olOptions = iform_map_get_ol_options($args);
    $r .= map_helper::map_panel($options, $olOptions);
    $r .= '</div>'; // right
    $r .= '</div>'; // site
    return $r;
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
    if (isset($cmsUserAttr['displayValue'])) 
      $rows .= '<tr><td id="user-'.$cmsUserAttr['displayValue'].'"><input type="hidden" name="'.$cmsUserAttr['fieldname'].':'.
          $cmsUserAttr['displayValue'].'" value="'.$cmsUserAttr['displayValue'].'"/>'.
          $users[$cmsUserAttr['displayValue']].'</td><td><div class="ui-state-default ui-corner-all"><span class="remove-user ui-icon ui-icon-circle-close"></span></td></div></tr>';
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
      return 'site-details';
    }
  } 
  
  
  
}
