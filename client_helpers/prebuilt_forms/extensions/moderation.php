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
 * @package	Client
 * @subpackage PrebuiltForms
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */
 
/**
 * Extension class that supplies tools for supporting moderation of records.
 */
class extension_moderation {

  /** 
   * A stub method that simply allows the moderation.js file to be added to the page. This adds some
   * JavaScript functions that can be linked to grid actions to perform moderation functions.
   */
  public static function enable_actions($auth, $args, $tabalias, $options, $path) {
    return '';
  }
  
  /**
   * Clears moderation notifications for a moderator automatically on visiting the page, so then they
   * will get notified about new incoming records.
   */
  public static function clear_moderation_task_notifications($auth, $args, $tabalias, $options, $path) {
    //Using 'submission_list' and 'entries' allows us to specify several top-level submissions to the system
    //i.e. we need to be able to submit several notifications.
    $submission['submission_list']['entries'] = array();
    $submission['id']='notification';
    $notifications = data_entry_helper::get_population_data(array(
      'table' => 'notification',
      'extraParams' => $auth['read'] + array('acknowledged' => 'f', 'user_id'=>hostsite_get_user_field('indicia_user_id'),
          'source_type' => 'PT'),
      'nocache' => true
    ));
    if (count($notifications)>0) {
      $auth = data_entry_helper::get_read_write_auth(variable_get('indicia_website_id', 0), variable_get('indicia_password', ''));
      //Setup the structure we need to submit.
      foreach ($notifications as $notification) { 
        $data['id']='notification';
        $data['fields']['id']['value'] = $notification['id'];
        $data['fields']['acknowledged']['value'] = 't';
        $submission['submission_list']['entries'][] = $data;
      }
      //Submit the stucture for processing
      $response = data_entry_helper::forward_post_to('save', $submission, $auth['write_tokens']);
      if (!is_array($response) || !array_key_exists('success', $response))
        drupal_set_message(print_r($response,true));
    }
    return '';
  }

}