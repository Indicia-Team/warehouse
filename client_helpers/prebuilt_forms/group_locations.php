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

/**
 * Page for configuring the locations used by a recording group.
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_group_locations {
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   * @todo rename this method.
   */
  public static function get_group_locations_definition() {
    return array(
      'title'=>'Group locations',
      'category' => 'Recording groups',
      'description'=>'A page listing the locations that are linked to a recording group, with links to allow '.
          'this list of locations to be configured.',
      'supportsGroups'=>true
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
   */
  public static function get_parameters() {   
    require_once('includes/map.php');
    $r = array_merge(array(
      array(
        'name'=>'edit_location_path',
        'caption'=>'Path to edit location page',
        'description'=>'Path to a page allowing locations to be edited and created. Should be a page built using the '.
            'Dynamic Locaiton prebuilt form.',
        'type'=>'string',
        'required'=>false
      ),
    ), iform_map_get_map_parameters());
    return $r;
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
    if (empty($_GET['group_id']))
      return 'This page needs a group_id URL parameter.';
    require_once('includes/map.php');
    require_once('includes/groups.php');
    global $indicia_templates;
    iform_load_helpers(array('report_helper', 'map_helper'));
    $conn = iform_get_connection_details($node);
    $readAuth = report_helper::get_read_auth($conn['website_id'], $conn['password']);
    report_helper::$javascript .= "indiciaData.website_id=$conn[website_id];\n";
    report_helper::$javascript .= "indiciaData.nodeId=$node->nid;\n";
    group_authorise_form($args, $readAuth);
    $group = data_entry_helper::get_population_data(array(
      'table'=>'group',
      'extraParams'=>$readAuth + array('id'=>$_GET['group_id'], 'view'=>'detail')
    ));
    $group = $group[0];
    hostsite_set_page_title("$group[title]: {$node->title}");
    $actions = array();
    if (!empty($args['edit_location_path']))
      $actions[] = array(
        'caption'=>'edit',
        'url'=>'{rootFolder}' . $args['edit_location_path'],
        'urlParams'=>array('group_id'=>$_GET['group_id'], 'location_id'=>'{location_id}')
      );
    $actions[] = array(
      'caption'=>'remove',
      'javascript'=>"remove_location_from_group({groups_location_id});"
    );
    $leftcol = report_helper::report_grid(array(
      'readAuth' => $readAuth, 
      'dataSource' => 'library/locations/locations_for_groups',
      'sendOutputToMap' => true,
      'extraParams' => array('group_id'=>$_GET['group_id']),
      'rowId' => 'location_id',
      'columns' => array(
        array(
          'display'=>'Actions',
          'actions'=>$actions,
          'caption'=>'edit',
          'url'=>'{rootFolder}'
        )
      )
    ));
    $leftcol .= '<fieldset><legend>' . lang::Get('Add sites to the group') . '</legend>';
    $leftcol .= '<p>' . lang::get('LANG_Add_Sites_Instruct') . '</p>';
    if (!empty($args['edit_location_path']))
      $leftcol .= lang::get('Either') .
        ' <a class="button" href="' . hostsite_get_url($args['edit_location_path'], array('group_id'=>$_GET['group_id'])) .
        '">' . lang::get('enter details of a new site') .'</a><br/>';
    $leftcol .= data_entry_helper::select(array(
      'label' => lang::get('Or, add an existing site'),
      'fieldname' => 'add_existing_location_id',
      'report' => 'library/locations/locations_available_for_group',
      'caching' => false,
      'blankText' => lang::get('<please select>'),
      'valueField' => 'location_id',
      'captionField' => 'name',
      'extraParams' => $readAuth + array('group_id' => $_GET['group_id'], 'user_id'=>hostsite_get_user_field('indicia_user_id', 0)),
      'afterControl' => '<button id="add-existing">Add</button>'
    ));
    $leftcol .= '</fieldset>';
    // @todo Link existing My Site to group. Need a new report to list sites I created, with sites already in the group
    // removed. Show in a drop down with an add button. Adding must create the groups_locations record, plus refresh
    // the grid and refresh the drop down.
    // @todo set destination after saving added site
    $map = map_helper::map_panel(iform_map_get_map_options($args, $readAuth), iform_map_get_ol_options($args));
    $r = str_replace(array('{col-1}', '{col-2}'), array($leftcol, $map), $indicia_templates['two-col-50']);
    data_entry_helper::$javascript .= "indiciaData.group_id=$_GET[group_id];\n";
    return $r;
  }
}
