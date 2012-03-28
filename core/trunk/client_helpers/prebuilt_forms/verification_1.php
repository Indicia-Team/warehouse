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
 * Prebuilt Indicia data form that lists the output of an occurrences report with an option
 * to verify or reject each record.
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_verification_1 {
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
    return array(
      array(
        'name'=>'report_name',
        'caption'=>'Report Name',
        'description'=>'The name of the report file to load into the verification grid, excluding the .xml suffix. This report should have '.
            'at least the following columns: occurrence_id, taxon. If you don\'t know which report to use, try the recent_occurrences_in_survey report.',
        'type'=>'string',
        'default'=>'reports_for_prebuilt_forms/verification_1/basic_verification_grid'
      ), array(
        'name'=>'auto_params_form',
        'caption'=>'Automatic Parameters Form',
        'description'=>'If the report requires input parameters, shall an automatic form be generated to allow the user to '.
            'specify those parameters?',
        'type'=>'boolean',
        'default'=>true
      ), array(
        'name'=>'fixed_params',
        'caption'=>'Fixed Parameters',
        'description'=>'Provide a comma separated list '.
            'of <parameter_name>=<parameter_value> pairs to define fixed values for parameters that the report requires. '.
            'E.g. "survey=12,taxon=53". Any parameters ommitted from this list will be requested from the user when the verification page is viewed.',
        'type'=>'textarea',
        'required'=>false
      ), array(
        'name' => 'param_defaults',
        'caption' => 'Default Parameter Values',
        'description' => 'To provide default values for any report parameter which allow the report to run initially but can be overridden, enter each parameter into this '.
            'box one per line. Each parameter is followed by an equals then the value, e.g. survey_id=6. You can use {user_id} as a value which will be replaced by the '.
            'user ID from the CMS logged in user or {username} as a value replaces with the logged in username. Unlike fixed parameter values, parameters referred '.
            'to by default parameter values are displayed in the parameters form and can therefore be changed by the user.',
        'type' => 'textarea',
        'required' => false
      ), array(
          'name' => 'columns_config',
          'caption' => 'Columns Configuration',
          'description' => 'Define a list of columns with various configuration options when you want to override the '.
              'default output of the report.',
          'type' => 'jsonwidget',
          'schema' => '{
  "type":"seq",
  "title":"Columns List",
  "sequence":
  [
    {
      "type":"map",
      "title":"Column",
      "mapping": {
        "fieldname": {"type":"str","desc":"Name of the field to output in this column. Does not need to be specified when using the template option."},
        "display": {"type":"str","desc":"Caption of the column, which defaults to the fieldname if not specified."},
        "actions": {
          "type":"seq",
          "title":"Actions List",
          "sequence": [{
            "type":"map",
            "title":"Actions",
            "desc":"List of actions to make available for each row in the grid.",
            "mapping": {
              "caption": {"type":"str","desc":"Display caption for the action\'s link."},
              "visibility_field": {"type":"str","desc":"Optional name of a field in the data which contains true or false to define the visibility of this action."},
              "url": {"type":"str","desc":"A url that the action link will point to, unless overridden by JavaScript. The url can contain tokens which '.
                  'will be subsituted for field values, e.g. for http://www.example.com/image/{id} the {id} is replaced with a field called id in the current row. '.
              'Can also use the subsitution {currentUrl} to link back to the current page, {rootFolder} to represent the folder on the server that the current PHP page is running from, and '.
              '{imageFolder} for the image upload folder"},
              "urlParams": {
                "type":"map",
                "subtype":"str",
                "desc":"List of parameters to append to the URL link, with field value replacements such as {id} begin replaced '.
                    'by the value of the id field for the current row."
              },
              "class": {"type":"str","desc":"CSS class to attach to the action link."},
              "javascript": {"type":"str","desc":"JavaScript that will be run when the link is clicked. Can contain field value substitutions '.
                  'such as {id} which is replaced by the value of the id field for the current row. Because the javascript may pass the field values as parameters to functions, '.
                  'there are escaped versions of each of the replacements available for the javascript action type. Add -escape-quote or '.
                  '-escape-dblquote to the fieldname. For example this would be valid in the action javascript: foo(\"{bar-escape-dblquote}\"); '.
                  'even if the field value contains a double quote which would have broken the syntax."}
            }
          }]
        },
        "visible": {"type":"bool","desc":"Should this column be shown? Hidden columns can still be used in templates or actions."},
        "template": {"type":"str","desc":"Allows you to create columns that contain dynamic content using a template, rather than just the output '.
        'of a field. The template text can contain fieldnames in braces, which will be replaced by the respective field values. '.
        'Note that template columns cannot be sorted by clicking grid headers." }
      }
    }
  ]
}',
          'required' => false
        ), array(
        'name'=>'verifiers_mapping',
        'caption'=>'Verifiers Mapping',
        'description'=>'Provide either the ID of a single Indicia user to act as the verifier, or provide a comma separated list '.
            'of <drupal user id>=<indicia user id> pairs to define the mapping from Drupal to Indicia users. E.g. '.
            '"1=2,2=3"',
        'type'=>'textarea',
        'default'=>1
      ), array(
        'name' => 'path_to_record_details_page',
        'caption' => 'Path to a record details page',
        'description' => 'If a path is supplied here to a <em>View details of a record</em> page, then a link is provided from each row to the page. The path should '.
            'be relative to the root of the website.',
        'type' => 'text_input',
        'required' => false
      ), array(
        'name'=>'send_for_verification',
        'caption'=>'Allow records to be sent for verification',
        'description'=>'Enables a facility to email the details of a record to a verifier, who can check the record and reply so that their response can '.
            'be entered into the grid at a later date?',
        'type'=>'boolean',
        'default'=>false
       ), array(
        'name'=>'emails_enabled',
        'caption'=>'Enable Notification Emails',
        'description'=>'Are notification emails enabled to inform recorders of their records being verified or rejected?',
        'type'=>'boolean',
        'default'=>true,
        'group' => 'Notification emails'
       ), array(
        'name'=>'log_send_to_verifier',
        'caption'=>'Log sending to verifier',
        'description'=>'If checked, then an automatic comment is created to log the sending of a record to a verifier.',
        'type'=>'boolean',
        'default'=>true,
        'group' => 'Notification emails'
       ), array(
        'name'=>'log_send_to_verifier_comment',
        'caption'=>'Send to verifier log comment',
        'description'=>'Comment used to log when sending to verifier. Replacements allowed include %email%, %date%, %user%.',
        'type'=>'string',
        'default' => 'Sent to %email% for verification on %date% by %user%',
        'group' => 'Notification emails'
      ),
       array(
        'name'=>'email_request_attribute',
        'caption'=>'Check Email Requested',
        'description'=>'Enter the caption of a sample attribute and the value will be checked and'.
           'an email only sent if it is true. Leave blank if not required.',
        'type'=>'string',
        'default' => '',
        'required'=>false,
        'group' => 'Notification emails',
       ), array(
        'name'=>'email_subject_send_to_verifier',
        'caption'=>'Send to Verifier Email Subject',
        'description'=>'Default subject for the send to verifier email. Replacements allowed include %taxon% and %id%.',
        'type'=>'string',
        'default' => 'Record of %taxon% requires verification (ID:%id%)',
        'group' => 'Notification emails'
      ), array(
        'name'=>'email_body_send_to_verifier',
        'caption'=>'Send to Verifier Email Body',
        'description'=>'Default body for the acceptance email. Replacements allowed include %taxon%, %id% and %record% which is replaced to give details of the record.',
        'type'=>'textarea',
        'default' => 'The following record requires verification. Please reply to this mail with the word Verified or Rejected '.
            'in the email body, followed by any comments you have including the proposed re-identification if relevant on the next line.'.
            "\n\n%record%",
        'group' => 'Notification emails'
      ),array(
        'name'=>'email_subject_verified',
        'caption'=>'Acceptance Email Subject',
        'description'=>'Default subject for the acceptance email. Replacements allowed include %action% (verified or rejected), '.
            '%verifier% (username of verifier), %taxon%, %date_start%, %entered_sref%.',
        'type'=>'string',
        'default' => 'Record of %taxon% %action%',
        'group' => 'Notification emails'
      ), array(
        'name'=>'email_body_verified',
        'caption'=>'Acceptance Email Body',
        'description'=>'Default body for the acceptance email. Replacements allowed include %action% (verified or rejected), '.
            '%verifier% (username of verifier), %taxon%, %date_start%, %entered_sref%, %comment%.',
        'type'=>'textarea',
        'default' => "Your record of %taxon%, recorded on %date_start% at grid reference %entered_sref% has been checked by ".
          "an expert and %action%.\nMany thanks for the contribution.\n\n%verifier%",
        'group' => 'Notification emails'
      ), array(
        'name'=>'email_subject_rejected',
        'caption'=>'Rejection Email Subject',
        'description'=>'Default subject for the rejection email. Replacements as for acceptance.',
        'type'=>'string',
        'default' => 'Record of %taxon% not verified',
        'group' => 'Notification emails'
      ), array(
        'name'=>'email_body_rejected',
        'caption'=>'Rejection Email Body',
        'description'=>'Default body for the rejection email. Replacements as for acceptance.',
        'type'=>'textarea',
        'default' => "Your record of %taxon%, recorded on %date_start% at grid reference %entered_sref% has been checked by ".
          "an expert but unfortunately it could not be verified because there was a problem with your photo.\n".
          "Nonetheless we are grateful for your contribution and hope you will be able to send us further records.\n\n%verifier%",
        'group' => 'Notification emails'
      ),
    );
  }
  
  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'Verification 1 - a simple grid for verification';  
  }
  
  /**
   * Return the Indicia form code.
   * Expects there to be a sample attribute with caption 'Email' containing the email
   * address.
   * @param array $args Input parameters.
   * @param array $node Drupal node object
   * @param array $response Response from Indicia services after posting a verification.
   * @return HTML string
   */
  public static function get_form($args, $node, $response) {
    global $user, $indicia_templates;
    // put each param control in a div, which makes it easier to layout with CSS
    $indicia_templates['prefix']='<div id="container-{fieldname}" class="param-container">';
    $indicia_templates['suffix']='</div>';
    $indicia_user_id=self::get_indicia_user_id($args);
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $r = '';
    if ($_POST) {
      // dump out any errors that occurred on verification
      if (data_entry_helper::$validation_errors) {
        $r .= '<div class="page-notice ui-state-highlight ui-corner-all"><p>'.
            implode('</p></p>', array_values(data_entry_helper::$validation_errors)).
            '</p></div>';
      } else if (isset($_POST['email']) && !isset($response['error'])) {
        // To send HTML mail, the Content-type header must be set
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= 'From: '. $user->mail . PHP_EOL . "\r\n";
        $headers .= 'Return-Path: '. $user->mail . "\r\n";
        if (isset($_POST['photoHTML']))
          $emailBody = str_replace('[photo]', '<br/>' . $_POST['photoHTML'], $_POST['email_content']);
        else
          $emailBody = $_POST['email_content'];        
        $emailBody = str_replace("\n", "<br/>", $emailBody);
        // Send email. Depends upon settings in php.ini being correct
        $success = mail($_POST['email_to'],
             $_POST['email_subject'],
             wordwrap($emailBody, 70),
             $headers);        
        if ($success) {
          $r .= '<div class="page-notice ui-state-highlight ui-corner-all"><p>An email was sent to '.$_POST['email_to'].'.</p></div>';
        }  
        else
          $r.= '<div class="page-notice ui-widget-content ui-corner-all ui-state-highlight left">The webserver is not correctly configured to send emails. Please send the following email manually: <br/>'.
              '<div id="manual-email"><span>To:</span><div>' . $_POST['email_to'] . '</div>' .
              '<span>Subject:</span><div>' . $_POST['email_subject'] . '</div>' .
              '<span>Content:</span><div>' . $emailBody . '</div>'.
              '</div></div><div style="clear: both">';
      } else if (isset($_POST['occurrence:record_status']) && isset($response['success']) && $args['emails_enabled']) {        
        $r .= self::get_notification_email_form($args, $response, $auth);
      }
      if (isset($_POST['action']) && $_POST['action']=='send_to_verifier' && $args['log_send_to_verifier']) 
        $comment = str_replace(array('%email%', '%date%', '%user%'), array($_POST['email_to'], date('jS F Y'), $user->name), $args['log_send_to_verifier_comment']);
      elseif (isset($_POST['action']) && $_POST['action']='general_comment')
        $comment = $_POST['comment'];
      // If there is a comment to save, add it to the occurrence comments
      if (isset($comment)) {
        // get our own write tokens for this submission, as the main ones are used in the JavaScript form.
        $loggingAuth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
        $sub = data_entry_helper::wrap(array(
          'comment' => $comment,
          'occurrence_id' => $_POST['occurrence:id'],
          'created_by_id' => $indicia_user_id
        ), 'occurrence_comment');
        $logResponse = data_entry_helper::forward_post_to('occurrence_comment', $sub, $loggingAuth['write_tokens']);
        if (!array_key_exists('success', $logResponse)) {
          $r .= data_entry_helper::dump_errors($response, false);
        }
      }
    }

    //extract fixed parameters for report grid.
    $params = explode( ",", $args['fixed_params']);
    foreach ($params as $param){
      $keyvals = explode("=", $param);
      $key = trim($keyvals[0]);
      $val = trim($keyvals[1]);
      $extraParams[$key] = $val;
    }
    // plus defaults which are not fixed
    $params = explode( "\n", $args['param_defaults']);
    foreach ($params as $param){
      $keyvals = explode("=", $param);
      $key = trim($keyvals[0]);
      $val = trim($keyvals[1]);
      $paramDefaults[$key] = $val;
    }
    $actions = array();
    if ($args['send_for_verification']) {
      // store authorisation details as a global in js, since some of the JavaScript needs to be able to access Indicia data
      data_entry_helper::$javascript .= 'auth=' . json_encode($auth) . ';';
      $actions[] = 
        array('caption' => str_replace(' ', '&nbsp;', lang::get('Send to verifier')), 'class'=>'send_for_verification_btn',
            'javascript'=>'indicia_send_to_verifier(\'{taxon}\', {occurrence_id}, '.$user->uid.', '.$args['website_id'].'); return false;'
        );     
      $r .= self::get_send_for_verification_form();  
    }
    $actions[] = array('caption' => str_replace(' ', '&nbsp;', lang::get('Verify')), 'javascript'=>'indicia_verify(\'{taxon}\', {occurrence_id}, true, '.$user->uid.'); return false;');
    $actions[] = array('caption' => str_replace(' ', '&nbsp;', lang::get('Reject')), 'javascript'=>'indicia_verify(\'{taxon}\', {occurrence_id}, false, '.$user->uid.'); return false;');
    $actions[] = array('caption' => str_replace(' ', '&nbsp;', lang::get('Comments')), 'javascript'=>'indicia_comments(\'{taxon}\', {occurrence_id}, '.$user->uid.
        ', \''.$auth['read']['nonce'].'\', \''.$auth['read']['auth_token'].'\'); return false;');
    if (isset($args['path_to_record_details_page']) && $args['path_to_record_details_page'])
      $actions[] = array(
          'caption' => str_replace(' ', '&nbsp;', lang::get('View details')), 
          'url' => '{rootFolder}' . $args['path_to_record_details_page'], 
          'urlParams' => array('occurrence_id'=>'{occurrence_id}')
      );
    // default columns behaviour is to just include anything returned by the report plus add an actions column
    $columns = array(
        array('display' => 'Actions', 'actions' => $actions)
    );
    // this can be overridden
    if (isset($args['columns_config']) && !empty($args['columns_config']))
      $columns = array_merge(json_decode($args['columns_config'], true), $columns);
    $r .= data_entry_helper::report_grid(array(
      'id' => 'verification-grid',
      'dataSource' => $args['report_name'],
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => $columns,
      'rowId' => 'occurrence_id',
      'itemsPerPage' =>10,
      'autoParamsForm' => $args['auto_params_form'],
      'extraParams' => $extraParams,
      'paramDefaults' => $paramDefaults
    ));
    // Put in a blank form, which lets JavaScript set the values and post the data.
    $r .= '
<form id="verify" method="post" action="">
  '.$auth['write'].'
  <input type="hidden" id="occurrence:id" name="occurrence:id" value="" />
  <input type="hidden" id="occurrence:record_status" name="occurrence:record_status" value="" />
  <input type="hidden" id="occurrence_comment:comment" name="occurrence_comment:comment" value="" />
  <input type="hidden" name="occurrence_comment:created_by_id" value="'.$indicia_user_id.'" />
  <input type="hidden" id="website_id" name="website_id" value="'.$args['website_id'].'" />
  <input type="hidden" id="occurrence:verified_by_id" name="occurrence:verified_by_id" value="" />
</form>
';
    
    drupal_add_js('
var indicia_user_id = '.$indicia_user_id.';
var url = '.json_encode(data_entry_helper::get_reload_link_parts()).';
var svc = "'.data_entry_helper::$base_url.'index.php/services/data/";
var email_subject_send_to_verifier = "'.$args['email_subject_send_to_verifier'].'";
var email_body_send_to_verifier = "'.str_replace(array("\r", "\n"), array('', '\n'), $args['email_body_send_to_verifier']).'";
', 'inline'
);

/*.';

');*/
    return $r;
  }
  
  /**
   * Use the mapping from Drupal to Indicia users to get the Indicia user ID for the current logged in Drupal user.
   */
  private static function get_indicia_user_id($args) {
    global $user;
    if (strpos($args['verifiers_mapping'], ',')!==false) {
      $arr = explode(',', $args['verifiers_mapping']);
      foreach ($arr as $mapping) {
        $mapArr = explode('=', $mapping);
        if (count($mapArr) == 0) {
          return trim($mapping);
        } else {
          if (trim($mapArr[0])==$user->uid) {
            return trim($mapArr[1]);
          }
        }
      }
    } else {
      // verifiers mapping is just a single number
      return trim($args['verifiers_mapping']);
    }
	  return 1; // default to admin  
  }
  
  private static function get_send_for_verification_form() {
    data_entry_helper::add_resource('fancybox');
    data_entry_helper::add_resource('validation');
  }
  
  /**
  *  Provide a send email form to allow the user to send a verification email
  */
  private static function get_notification_email_form($args, $response, $auth) {  
    if ($_POST['occurrence:record_status']=='V') $action = 'verified';
    elseif ($_POST['occurrence:record_status']=='R') $action = 'rejected';
    else $action='';
    if ($action) {
      //obtain information for email notification
      $occ = data_entry_helper::get_population_data(array(
        'table' => 'occurrence',
        'extraParams' => $auth['read'] + array('id' => $response['outer_id'], 'view' => 'detail')
      ));
      $email_attr = data_entry_helper::get_population_data(array(
        'table' => 'sample_attribute_value',
        'extraParams' => $auth['read'] + array('caption'=>'Email', 'sample_id' => $occ[0]['sample_id'])
      ));
      if ($args['email_request_attribute'] != '') {
        $email_request_attr = data_entry_helper::get_population_data(array(
          'table' => 'sample_attribute_value',
          'extraParams' => $auth['read'] + array('caption'=>urlencode($args['email_request_attribute']), 'sample_id' => $occ[0]['sample_id'])
        ));
      }

      //only send email if address was supplied and email requested
      if (!empty($email_attr[0]['value']) &&
          (($args['email_request_attribute'] == '') ||
          (!empty($email_request_attr[0]['value']) && $email_request_attr[0]['value']))) {
        $subject = self::get_email_component('subject', $action, $occ[0], $args);
        if ($action=='verified') 
          // Use the verifier's comment, not the comment from the record, in the email body
          $occ[0]['comment'] = $_POST['occurrence_comment:comment'];
        $body = self::get_email_component('body', $action, $occ[0], $args);
        $body = str_replace(array("\r","\n"), array('','\n'), $body);
        data_entry_helper::$javascript .= 'jQuery.fancybox(\''.
          '<form id="email" action="" method="post">'.
          '<fieldset>'.
          '<legend>Send a notification email to the recorder.</legend>'.
          '<label>To:</label><input type="text" name="email_to" size="80" value="'. $email_attr[0]['value'] .'"><br />'.
          '<label>Subject:</label><input type="text" name="email_subject" size="80" value="'. $subject .'"><br />'.
          '<label>Body:</label><textarea name="email_content" rows="5" cols="80">'.$body.'</textarea><br />'.
          '<input type="hidden" name="email" value="1">'.
          '<input type="button" value="Send Email" onclick="'.
          '$(\\\'form#email\\\').attr(\\\'action\\\', submit_to());'.
          '$(\\\'form#email\\\').submit();'.
          '">'.
          '</fieldset>'.
          "</form>');";
      } else {
        data_entry_helper::$javascript .= 'jQuery.fancybox(\''.
            '<div class="page-notice ui-state-highlight ui-corner-all" id="email">The record has been '.$action.
            '. The recorder did not leave an email address or did not want an email so cannot be notified.</div>\');';
      }
    }    
  }
  
  /**
   * Internal method to get the email subject or body field from the template in the arguments, 
   * and apply the values in the occurrence to the template. Finally it is encoded for inclusion 
   * in the mailto form.
   * @access private
   * @param string $part subject or body. 
   * @param string $action verified or rejected, for inclusion in the template. 
   * @param array $occ Occurrence data, to provide values for the template replacements. 
   * @param array $args Form arguments
   * @return Encoded string
   */
  private static function get_email_component($part, $action, $occ, $args) {
    $r = str_replace('%action%', $action, $args['email_'.$part.'_'.$action]);
    foreach($occ as $attr=>$value) {
      $r = str_replace('%'.$attr.'%', $value, $r);
    }
    global $user;
    $r = str_replace('%verifier%', $user->name, $r);
    return $r;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    // Submission includes the occurrence comment only if it is populated. This occurs when entering a verification or rejection comment.
    if (isset($_POST['occurrence_comment:comment']) && !empty($_POST['occurrence_comment:comment'])) {
      return data_entry_helper::build_submission($values, array('model'=>'occurrence','subModels' => array('occurrence_comment' =>  array(          
          'fk' => 'occurrence_id'
      ))));
    } else
      return data_entry_helper::build_submission($values, array('model'=>'occurrence'));
  }
  
}