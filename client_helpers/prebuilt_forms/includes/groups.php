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
 * List of methods that assist with handling recording groups.
 * @package Client
 * @subpackage PrebuiltForms.
 */

function group_authorise_form($args, $readAuth) {
  if (!empty($args['limit_to_group_id']) && $args['limit_to_group_id']!==(empty($_GET['group_id']) ? '' : $_GET['group_id'])) {
    // page owned by a different group, so throw them out
    hostsite_show_message(lang::get('This page is a private recording group page which you cannot access.'), 'alert');
    hostsite_goto_page('<front>');
  }    
  if (!empty($_GET['group_id'])) {
    // loading data into a recording group. Are they a member or is the page public?
    // @todo: consider performance - 2 web services hits required to check permissions.
    if (hostsite_get_user_field('indicia_user_id')) {
      $gu = data_entry_helper::get_population_data(array(
        'table'=>'groups_user',
        'extraParams'=>$readAuth + array('group_id'=>$_GET['group_id'], 'user_id'=>hostsite_get_user_field('indicia_user_id')),
        'nocache'=>true
      ));
    } else {
      $gu = array();
    }
    $gp = data_entry_helper::get_population_data(array(
      'table'=>'group_page',
      'extraParams'=>$readAuth + array('group_id'=>$_GET['group_id'], 'path'=>drupal_get_path_alias($_GET['q']))
    ));
    if (count($gp)===0) {
      hostsite_show_message(lang::get('You are trying to access a page which is not available for this group.'), 'alert');
      hostsite_goto_page('<front>');
    } elseif (count($gu)===0 && $gp[0]['administrator']!==NULL) {
      // not a group member, so throw them out
      hostsite_show_message(lang::get('You are trying to access a page for a group you do not belong to.'), 'alert');
      hostsite_goto_page('<front>');
    }
  }
}