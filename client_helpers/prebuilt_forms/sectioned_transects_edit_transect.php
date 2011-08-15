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
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $locationTypes = helper_base::get_termlist_terms($auth, 'indicia:location_types', array('Transect', 'Transect Section'));
    $locationId = isset($_GET['site']) ? $_GET['site'] : null;
    if ($locationId) {
      data_entry_helper::load_existing_record($auth['read'], 'location', $locationId);
      $sections = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $auth['read'] + array('view'=>'detail','parent_id'=>$locationId,'deleted'=>'f'),
        'nocache' => true
      ));
    } else {
      $sections=array();
    }
    $attributes = data_entry_helper::getAttributes(array(
        'id' => $locationId,
        'valuetable'=>'location_attribute_value',
        'attrtable'=>'location_attribute',
        'key'=>'location_id',
        'fieldprefix'=>'locAttr',
        'extraParams'=>$auth['read'],
        'survey_id'=>$args['survey_id'],
        'location_type_id' => $locationTypes[0]['id']
    ));
    if (false==$cmsUserAttrs = self::extract_cms_user_attrs($attributes))
      return 'This form is designed to be used with the CMS User ID attribute setup for locations in the survey.';
    $r = '<form method="post">';
    $r .= '<div class="left">';
    $r .= $auth['write'];
    $r .= '<fieldset><legend>'.lang::get('Transect Details').'</legend>';
    if ($locationId)
      $r .= "<input type=\"hidden\" name=\"location:id\" value=\"$locationId\" />\n";
    $r .= "<input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= "<input type=\"hidden\" name=\"survey_id\" value=\"".$args['survey_id']."\" />\n";    
    $r .= "<input type=\"hidden\" name=\"location:location_type_id\" value=\"".$locationTypes[0]['id']."\" />\n";
    $r .= data_entry_helper::text_input(array(
      'fieldname' => 'location:name',
      'label' => lang::get('Transect Name'),
      'class' => 'control-width-4'
    ));
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach($list as $system) {
      $systems[$system] = lang::get($system);
    }
    $r .= data_entry_helper::sref_and_system(array(
      'fieldname' => 'location:centroid_sref',
      'geomFieldname' => 'location:centroid_geom',
      'label' => 'Grid Ref.',
      'systems' => $systems
    ));

    $r .= '<div id="section-geoms">';
    foreach($sections as $section) {
      $code = strtolower($section['code']);
      $r .= '<input type="hidden" id="'.$code.'" name="'.$code.'" value="' . $section['boundary_geom'] . '"/>';
      $r .= '<input type="hidden" id="id-'.$code.'" name="id-'.$code.'" value="' . $section['id'] . '"/>';
    }
    $r .= '</div>';
    
    // setup the map options
    $options = iform_map_get_map_options($args, $auth['read']);
    $options['toolbarDiv'] = 'top';
    if (!empty($sections)) {
      // if we have an existing site with sections, output a selector for the current section.
      $sectionArr = array('' => htmlspecialchars(lang::get('<please select>')));
      for ($i=1; $i<=count($sections); $i++)
        $sectionArr[$i] = $i;
      $options['toolbarPrefix'] = data_entry_helper::select(array(
        'id' => 'current-section',
        'label' => lang::get('Select section'),
        'lookupValues' => $sectionArr,
        'suffixTemplate' => 'nosuffix'
      ));
      $options['toolbarPrefix'] .= '<a id = "section-edit" style="display: none" href="'.$args['section_edit_path'].'?from=transect&transect_id='.$locationId.'&section_id=0">' . lang::get('Edit') . '</a> | ';
      // also let the user click on a feature to select it. The highlighter just makes it easier to select one.
      $options['standardControls'][] = 'selectFeature';
      $options['standardControls'][] = 'hoverFeatureHighlight';
    }
    if ($locationId) {
      $options['toolbarPrefix'] .= '<a href="'.$args['section_edit_path'].'?from=transect&transect_id='.$locationId.'">' . lang::get('Add Section') . '</a>';
    }
    $r .= get_attribute_html($attributes, $args, array());
    $olOptions = iform_map_get_ol_options($args);
    $r .= '</fieldset>';
    if (user_access('indicia data admin'))
      $r .= self::get_user_assignment_control($auth['read'], $cmsUserAttrs, $args);
    elseif (!$locationId) {
      // for a new record, we need to link the current user to the location if they are not admin.
      global $user;
      $r .= '<input type="hidden" name="locAttr:'.self::$cmsUserAttrId.'" value="'.$user->uid.'">';
    }
    $r .= '<input type="submit" value="'.lang::get('Save').'" class="ui-state-default ui-corner-all" />';
    $r .= "</div>";
    $r .= '<div class="right" style="border: solid silver 1px">';
    $r .= map_helper::map_panel($options, $olOptions);
    $r .= '</div>';
    $r .='</form>';
    data_entry_helper::link_default_stylesheet();
    if (function_exists('drupal_set_breadcrumb')) {
      $breadcrumb = array();
      $breadcrumb[] = l('Home', '<front>');
      $breadcrumb[] = l('Sites', $args['sites_list_path']);
      $breadcrumb[] = data_entry_helper::$entity_to_load['location:name'];
      drupal_set_breadcrumb($breadcrumb);
    }
    return $r;
  }
  
  /**
   * If the user has permissions, then display a control so that they can specify the list of users associated with this site.
   */
  private static function get_user_assignment_control($readAuth, $cmsUserAttrs, $args) {
    $query = db_query("select uid, name from {users} where name<>'' order by name");
    $users = array();
    while ($user = db_fetch_object($query)) 
      $users[$user->uid] = $user->name;
    $r = '<fieldset id="alloc-recorders"><legend>'.lang::get('Allocate recorders to the site').'</legend>';
    $r .= data_entry_helper::select(array(
      'label' => lang::get('Select user'),
      'fieldname' => 'cmsUserId',
      'lookupValues' => $users,
      'afterControl' => '<span id="add-user" class="ui-state-default ui-corner-all">'.lang::get('Add').'</span>'
    ));
    $r .= '<table id="user-list" style="width: auto">';
    
    foreach ($cmsUserAttrs as $attr) {
      if (isset($attr['displayValue'])) 
        $r .= '<tr><td id="user-'.$attr['displayValue'].'"><input type="hidden" name="'.$attr['fieldname'].':'.
            $attr['displayValue'].'" value="'.$attr['displayValue'].'"/>'.
            $users[$attr['displayValue']].'</td><td><div class="ui-state-default ui-corner-all"><span class="remove-user ui-icon ui-icon-circle-close"></span></td></div></tr>';
    }
    $r .= '</table>';
    $r .= '</fieldset>';
    // tell the javascript which attr to save the user ID into
    data_entry_helper::$javascript .= "indiciaData.locCmsUsrAttr = " . self::$cmsUserAttrId . ";\n";
    return $r;
  }
  
  /** 
   * Find the attribute(s) called CMS User ID, or return false.
   * @return array List of attribute definitions
   */
  private static function extract_cms_user_attrs(&$attributes) {
    $r = array();
    foreach($attributes as $idx => $attr) {
      if (strcasecmp($attr['caption'], 'CMS User ID')===0) {
        $r[$idx] = $attr;
        // keep a copy of the location_attribute_id so we can use it later.
        self::$cmsUserAttrId = $attr['attributeId'];
        unset($attributes[$idx]);
      }
    }
    if (count($r)) 
      return $r;
    else
      return false;
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
  
  
  
}
