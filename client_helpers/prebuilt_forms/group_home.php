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

require_once('dynamic_report_explorer.php');
require_once('includes/report_filters.php');
require_once('includes/groups.php');

/**
 * 
 * 
 * @package Client
 * @subpackage PrebuiltForms
 * A page for editing or creating a user group home page.
 */
class iform_group_home extends iform_dynamic_report_explorer {
  
  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_group_home_definition() {
    return array(
      'title'=>'Group home page',
      'category' => 'Recording groups',
      'description'=>'A home page for recording groups. This is based on a dynamic report explorer, but it applies '.
          'an automatic filter to the page output based on a group_id URL parameter.',
      'supportsGroups'=>true
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
    if (empty($_GET['group_id'])) {
      return 'This page needs a group_id URL parameter.';
    }
    self::$auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    group_authorise_form($node, self::$auth['read']);
    $group = data_entry_helper::get_population_data(array(
      'table'=>'group',
      'extraParams'=>self::$auth['read'] + array('id'=>$_GET['group_id'], 'view'=>'detail')
    ));
    $group = $group[0];
    hostsite_set_page_title($group['title']);
    $def = json_decode($group['filter_definition'], true);
    $defstring='';
    // reconstruct this as a string to feed into dynamic report explorer
    foreach($def as $key=>$value) {
      if ($key)
        $defstring .= "$key=$value\n";
    }
    $prefix = (empty($_GET['implicit']) || $_GET['implicit']==='true') ? 'implicit_' : ''; 
    // add the group parameters to the preset parameters passed to all reports on this page
    $args['param_presets']=implode("\n", array($args['param_presets'], $defstring, "{$prefix}group_id=".$_GET['group_id']));
    return parent::get_form($args, $node);
  }

}
