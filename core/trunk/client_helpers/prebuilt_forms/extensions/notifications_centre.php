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
 * Extension class that supplies new controls to allow the user to view system or user notifications.
 */
class extension_notifications_centre {

  static $initialised = false;
  static $dataServicesUrl;
  
  /*
   * Draw the control that displays auto-check notifications.
   * Pass the following options:
   * @default_edit_page_path = path to the default page to load for record editing, where the input form used is unknown (normally 
   * this affects old records only).
   * @view_record_page_path = path to the view record details page
   */
  public static function auto_check_messages_grid($auth, $args, $tabalias, $options, $path) {
    // set default to show comment and verification notifications
    $options = array_merge(array(
      'id' => 'auto-check-notifications',
      'title' => 'automatic check notifications',
      'sourceType' => 'A',
      'allowReply' => false,
      'allowEditRecord' => true
    ), $options);
    return self::messages_grid($auth, $args, $tabalias, $options, $path);
  }
  
  /*
   * Draw the control that displays user notifications. These are notifications of
   * source type 'C' or 'V' (comments and verifications), 'S' species alerts, 'VT' verification task, 'AC  by default - control this using the @sourceType option.
   * Pass the following options:
   * @default_edit_page_path = path to the default page to load for record editing, where the input form used is unknown (normally 
   * this affects old records only). 
   * @view_record_page_path = path to the view record details page   
   */
  public static function user_messages_grid($auth, $args, $tabalias, $options, $path) {
    // set default to show comment and verification notifications
    $options = array_merge(array(
      'id' => 'user-notifications',
      'title' => 'user message notifications',
      'sourceType' => 'C,V,S,VT,AC',
      'allowReply' => true,
      'allowEditRecord' => true,
    ), $options);
    return self::messages_grid($auth, $args, $tabalias, $options, $path);
  }
   
  /*
   * Use options @sourceType=S,V to show specific source types on the grid, the "S,V" in this example can be replaced with any source_type letter comma separated list.
   * Removing the @sourceType option will display a filter drop-down that the user can select from.
   */
  public static function messages_grid($auth, $args, $tabalias, $options, $path) {
    if (empty($options['id']))
      $options['id'] = 'notifications-grid';
    $indicia_user_id=hostsite_get_user_field('indicia_user_id');
    self::initialise($auth, $args, $tabalias, $options, $path, $indicia_user_id);
    if ($indicia_user_id)
      return self::notifications_grid($auth, $options, variable_get('indicia_website_id', ''), $indicia_user_id); // system
    else
      return '<p>'.lang::get('The notifications system will be enabled when you fill in at least your surname on your account.').'</p>';
  }  
  
  private static function initialise($auth, $args, $tabalias, $options, $path,$user_id) {
    if (!self::$initialised) {
      $indicia_user_id=hostsite_get_user_field('indicia_user_id');
      if ($indicia_user_id) {
        iform_load_helpers(array('report_helper'));
        report_helper::$javascript .= "indiciaData.website_id = ".variable_get('indicia_website_id', '').";\n";
        report_helper::$javascript .= "indiciaData.user_id = ".$indicia_user_id.";\n";
        //The proxy url used when interacting with the notifications table in the database.
        report_helper::$javascript .= "indiciaData.notification_proxy_url = '".iform_ajaxproxy_url(null, 'notification')."';\n";
        //The proxy url used when interacting with the occurrence comment table in the database.
        report_helper::$javascript .= "indiciaData.occurrence_comment_proxy_url = '".iform_ajaxproxy_url(null, 'occ-comment')."';\n";
        // The url used for direct access to data services.
        if (!empty(data_entry_helper::$warehouse_proxy))
          self::$dataServicesUrl = data_entry_helper::$warehouse_proxy."index.php/services/data";
        else
          self::$dataServicesUrl = data_entry_helper::$base_url."index.php/services/data";
        report_helper::$javascript .= "indiciaData.data_services_url = '".self::$dataServicesUrl."';\n";
        //If the user clicks the Remove Notifications submit button, then a hidden field
        //called remove-notifications is set. We can check for this when the 
        //page reloads and then call the remove notifications code.    
        if (!empty($_POST['remove-notifications']) && $_POST['remove-notifications']==1)
          self::build_notifications_removal_submission($user_id, $options);
      }
      self::$initialised = true;
    }
  }
  
  /**
   * Return the actual grid code.
   */
  private static function notifications_grid($auth, $options, $website_id, $user_id) {
    iform_load_helpers(array('report_helper'));
    //$sourceType is a user provided option for the grid to preload rather than the user selecting from the filter drop-down.
    //When the source types are provided like this, the filter drop-down is not displayed.
    //There can be more than one sourcetype, this is supplied as a comma seperated list and needs putting into an array
    $sourceType=empty($options['sourceType']) ? array() : explode(',',$options['sourceType']);
    if (!empty($sourceType))
      report_helper::$javascript .= "indiciaData.preloaded_source_types = '".$options['sourceType']."';\n";  
    //reload path to current page
    $reloadPath = self::getReloadPath ();
    $r='';
    $r .= self::get_notifications_html($auth, $sourceType, $website_id, $user_id, $options);
    $r .= "<form method = \"POST\" action=\"$reloadPath\">\n";
    //hidden field is set when Remove Notifications for user notifications is clicked,
    //when the page reloads this is then checked for
    $r .= '<input type="hidden" name="remove-notifications" class="remove-notifications"/>';
    //We need to store a list of source types on the grid, so we know what to clean up when the remove all button is clicked.
    $r .= '<input style="display:none" name="source-types" value="' . implode($sourceType) . '">';
    if (!empty($_POST['notifications-'.$options['id'].'-source_filter']))
      $r .= '<input style="display:none" name="source-filter" value="' . $_POST['notifications-'.$options['id'].'-source_filter'] . '">';
    $r .= self::remove_all_button($options);
    $r .= "</form>";
    return $r;
  }
  
  /**
   * Build a submission that the system can understand that includes the notifications we
   * want to remove.
   * @param type $auth
   * @param integer $user_id
   * @param array $options
   * 
   */
  private static function build_notifications_removal_submission($user_id,$options) {
    // rebuild the auth token since this is a reporting page but we need to submit data.
    $auth = data_entry_helper::get_read_write_auth(variable_get('indicia_website_id', ''), variable_get('indicia_password', ''));
    //Using 'submission_list' and 'entries' allows us to specify several top-level submissions to the system
    //i.e. we need to be able to submit several notifications.
    $submission['submission_list']['entries'] = array();
    $submission['id']='notification';
    $extraParams= array(
      'user_id'=>$user_id,
      'system_name'=>'indicia',
      'default_edit_page_path'=>'',
      'view_record_page_path'=>'',
      'website_id'=>variable_get('indicia_website_id', '')
    );
    //If the page is using a filter drop-down option, then collect the type of notification
    //to remove from the filter drop-down
    $extraParams['source_filter']=empty($_POST['source-filter']) ? 'all' : $_POST['source-filter'];
    //Get the source types to remove from a hidden field if the user has configured the page
    //to use a user specified option to specify exactly what kind of notifications to display
    if (!empty($options['sourceType'])) 
      $sourceTypesToClearFromConfig = explode(',', $options['sourceType']);
    //Place quotes around the source type letters for the report to accept as strings
    if (!empty($sourceTypesToClearFromConfig)) {
      if (array_key_exists(0,$sourceTypesToClearFromConfig) && !empty($sourceTypesToClearFromConfig[0])) {
        foreach ($sourceTypesToClearFromConfig as &$type)
          $type="'".$type."'";
        $extraParams['source_types'] = implode(',',$sourceTypesToClearFromConfig);
      }
    }
    //If the user has supplied some config options for the different source types
    if (!empty($options['sourceTypes']))
      // this disables the param for picking a single source type
      $extraParams['source_filter'] = 'all';
    //Only include notifications associated with a set of recording group ids if option is supplied.
    if (!empty($options['groupIds'])) {
      $extraParams['group_ids'] = $options['groupIds'];
    }
    // respect training mode
    if (hostsite_get_user_field('training')) 
      $extraParams['training'] = 'true';
    $notifications = data_entry_helper::get_report_data(array(
      'dataSource'=>'library/notifications/notifications_list_for_notifications_centre',
      'readAuth'=>$auth['read'],
      'extraParams'=>$extraParams
    ));
    $count=0;
    if (count($notifications)>0) {
      //Setup the structure we need to submit.
      foreach ($notifications as $notification) { 
        $data['id']='notification';
        $data['fields']['id']['value'] = $notification['notification_id'];
        $data['fields']['acknowledged']['value'] = 't';
        $submission['submission_list']['entries'][] = $data;
        $count++;
      }
      //Submit the stucture for processing
      $response = data_entry_helper::forward_post_to('save', $submission, $auth['write_tokens']);
      if (is_array($response) && array_key_exists('success', $response)) {
        if ($count===1)
          drupal_set_message(lang::get("1 notification has been removed."));
        else
          drupal_set_message(lang::get("{1} notifications have been removed.", $count));
      } else 
        drupal_set_message(print_r($response,true));
    }
  }
  
  /*
   * Draw the remove all notifications button.
   */
  private static function remove_all_button($options) {
    $title = empty($options['title']) ? lang::get('shown') : lang::get($options['title']);
    return "<input id=\"remove-all\" onclick=\"return acknowledge_all_notifications('".$options['id']."')\" type=\"submit\" ".
        "class=\"indicia-button\" value=\"".lang::get('Acknowledge all {1} notifications', $title)."\"/>\n";
  }
  
  /*
   * Draw the notifications grid.
   */
  private static function get_notifications_html($auth, $sourceType, $website_id, $user_id, $options) {
    iform_load_helpers(array('report_helper'));    
    $imgPath = empty(data_entry_helper::$images_path) ? data_entry_helper::relative_client_helper_path()."../media/images/" : data_entry_helper::$images_path;
    $sendReply = $imgPath."nuvola/mail_send-22px.png";
    $cancelReply = $imgPath."nuvola/mail_delete-22px.png";    
    //When the user wants to reply to a message, we have to add a new row
    report_helper::$javascript .= "
    indiciaData.reply_to_message = function(notification_id, occurrence_id) {
      if (!$('#reply-row-'+occurrence_id).length) {
        rowHtml = '<tr id='+\"reply-row-\"+occurrence_id+'><td><label for=\"\">".lang::get('Enter your reply below').":</label><textarea style=\"width: 95%\" id=\"reply-' +occurrence_id+'\"></textarea></td>';
        rowHtml += '<td class=\"actions\">';
        rowHtml += '<div><img class=\"action-button\" src=\"$sendReply\" onclick=\"reply('+occurrence_id+','+notification_id+',true);\" title=\"Send reply\">';
        rowHtml += '<img class=\"action-button\" src=\"$cancelReply\" onclick=\"reply('+occurrence_id+','+notification_id+',false);\" title=\"Cancel reply\">';
        rowHtml += '</div></td></tr>';
        $(rowHtml).insertAfter('tr#row'+notification_id);
        $('tr#row'+notification_id+' .action-button').hide();
      }
    };\n
    "; 
    $urlParams=array('occurrence_id'=>'{occurrence_id}');
    if (!empty($_GET['group_id']))
      $urlParams['group_id']=$_GET['group_id'];
    $availableActions = 
      array(
        array('caption'=>lang::get('Edit this record'), 'class'=>'edit-notification', 'url'=>'{rootFolder}{editing_form}', 'urlParams'=>$urlParams,
              'img'=>$imgPath.'nuvola/package_editors-22px.png', 'visibility_field'=>'editable_flag'),
        array('caption'=>lang::get('View this record'), 'class'=>'view-notification', 'url'=>'{rootFolder}{viewing_form}', 'urlParams'=>$urlParams,
              'img'=>$imgPath.'nuvola/find-22px.png', 'visibility_field'=>'viewable_flag' ),
        array('caption'=>lang::get('Mark as read'), 'javascript'=>'remove_message({notification_id});',
              'img'=>$imgPath.'nuvola/kmail-22px.png'));
    //Only allow replying for 'user' messages.
    if (isset($options['allowReply']) && $options['allowReply']===true)
      $availableActions = array_merge($availableActions,array(array('caption'=>lang::get('Reply to this message'), 'img'=>$imgPath.'nuvola/mail_reply-22px.png', 'visibility_field'=>'reply_flag',
          'javascript'=>"indiciaData.reply_to_message(".'{notification_id}'.",".'{occurrence_id}'.");")));
    $extraParams= array(
      'user_id'=>$user_id,
      'system_name'=>'indicia',
      'orderby'=>'triggered_on',
      'sortdir'=>'DESC',
      'default_edit_page_path'=>$options['default_edit_page_path'],
      'view_record_page_path'=>$options['view_record_page_path'],
      'website_id'=>$website_id);
    //Implode the source types so we can submit to the database in one text field.
    if (!empty($sourceType)) {
      $extraParams['source_types'] = "'" . implode("' ,'", $sourceType) . "'";
      //If the user has supplied some config options for the different source types then we don't need the 
      // source filter drop down.
      $extraParams['source_filter'] = 'all';
    }
    //Only include notifications associated with a set of recording group ids if option is supplied.
    if (!empty($options['groupIds']))
      $extraParams['group_ids'] = $options['groupIds'];
    $columns = array(
        'data' => array('fieldname'=>'data','json'=>true,
            'template'=>'<div class="type-{source_type}"><div class="status-{record_status}"></div></div><div class="note-type-{source_type}">{comment}</div>'.
            '<div class="comment-from helpText" style="margin-left: 34px; display: block;">from {username} on {triggered_date}</div>', 'display'=>'Message'),
        'occurrence_id' => array('fieldname'=>'occurrence_id'),
        'actions' => array('actions'=>$availableActions),
        'triggered_date' => array('fieldname'=>'triggered_date', 'visible' => false)
    );
    // allow columns config to override our default setup
    if (!empty($options['columns'])) {
      foreach($options['columns'] as $column) {
        if (!empty($column['actions']))
          $columns['actions'] = $column;
        elseif (!empty($column['fieldname']))
          $columns[$column['fieldname']] = $column;
      }
    }
    $r = report_helper::report_grid(array(
      'id'=>'notifications-'.$options['id'],
      'readAuth' => $auth['read'],
      'itemsPerPage'=>10,
      'dataSource'=>'library/notifications/notifications_list_for_notifications_centre',
      'rowId'=>'notification_id',
      'ajax'=>true,
      'mode'=>'report',
      'extraParams'=>$extraParams,
      'paramDefaults'=>array('source_filter'=>'all'),
      'paramsFormButtonCaption'=>lang::get('Filter'),
      'columns'=>array_values($columns)
    ));
    return $r;
  }

  /*
   * Ge the node path to reload the page with.
   */
  protected static function getReloadPath () {
    $reload = data_entry_helper::get_reload_link_parts();
    unset($reload['params']['sample_id']);
    unset($reload['params']['occurrence_id']);
    unset($reload['params']['location_id']);
    unset($reload['params']['new']);
    unset($reload['params']['newLocation']);
    $reloadPath = $reload['path'];
    if(count($reload['params'])) {
      // decode params prior to encoding to prevent double encoding.
      foreach ($reload['params'] as $key => $param) {
        $reload['params'][$key] = urldecode($param);
      }
      $reloadPath .= '?'.http_build_query($reload['params']);
    }
    return $reloadPath;
  }
}