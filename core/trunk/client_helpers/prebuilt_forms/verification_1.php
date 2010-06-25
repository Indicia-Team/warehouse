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
        'description'=>'The name of the report file to load into the verification grid, excluding the .xml suffix.',
        'type'=>'string'
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
            'E.g. "survey=12,taxon=53"',
        'type'=>'textarea',
        'required'=>false
      ), array(
        'name'=>'verifiers_mapping',
        'caption'=>'Verifiers Mapping',
        'description'=>'Provide either the ID of a single Indicia user to act as the verifier, or provide a comma separated list '.
            'of <drupal user id>=<indicia user id> pairs to define the mapping from Drupal to Indicia users. E.g. '.
            '"1=2,2=3"',
        'type'=>'textarea',
        'default'=>1
      ), array(
        'name'=>'emails_enabled',
        'caption'=>'Enable Notification Emails',
        'description'=>'Are notification emails enabled to inform recorders of their records being verified or rejected?',
        'type'=>'boolean',
        'default'=>true,
        'group' => 'Notification emails'
      ), array(
        'name'=>'email_subject',
        'caption'=>'Notification Email Subject',
        'description'=>'Default subject for the notification email. Replacements allowed include %action% (verified or rejected), '.
            '%verifier% (username of verifier), %taxon%, %date_start%, %entered_sref%.',
        'type'=>'string',
        'default' => 'Record of %taxon% %action%',
        'group' => 'Notification emails'
      ),
      array(
        'name'=>'email_body',
        'caption'=>'Notification Email Body',
        'description'=>'Default body for the notification email. Replacements allowed include %action% (verified or rejected), '.
            '%verifier% (username of verifier), %taxon%, %date_start%, %entered_sref%.',
        'type'=>'textarea',
        'default' => "Your record of %taxon%, recorded on %date_start% at grid reference %entered_sref% has been checked by ".
          "an expert and %action%.\nMany thanks for the contribution.\n\n%verifier%",
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
   * Return the Indicia form code
   * @param array $args Input parameters.
   * @param array $node Drupal node object
   * @param array $response Response from Indicia services after posting a verification.
   * @return HTML string
   */
  public static function get_form($args, $node, $response) {
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $r = '';
    if ($_POST) {
      // dump out any errors that occurred on verification
      if (data_entry_helper::$validation_errors) {
        $r .= '<div class="page-notice ui-state-highlight ui-corner-all"><p>'.
            implode('</p></p>', array_values(data_entry_helper::$validation_errors)).
            '</p></div>';
      } else if (isset($_POST['occurrence:record_status']) && isset($response['success']) && $args['emails_enabled']) {
        // Provide a send email form to allow the user to send a verification email
        if ($_POST['occurrence:record_status']=='V') $action = 'verified';
        elseif ($_POST['occurrence:record_status']=='R') $action = 'rejected';
        else $action='';
        if ($action) {
          $occ = data_entry_helper::get_population_data(array(
            'table' => 'occurrence',
            'extraParams' => $auth['read'] + array('id' => $response['outer_id'], 'view' => 'detail')
          ));
          $email_attr = data_entry_helper::get_population_data(array(
            'table' => 'sample_attribute_value',
            'extraParams' => $auth['read'] + array('caption'=>'Email', 'sample_id' => $occ[0]['sample_id'])
          ));
          $subject = self::get_email_component('subject', $action, $occ[0], $args);
          $body = self::get_email_component('body', $action, $occ[0], $args);
          
          if (!empty($email_attr[0]['value'])) {
            $r .= '
<form id="email-form" action="mailto:'.$email_attr[0]['value'].'?subject='.$subject.'&body='.$body.'" method="post" enctype="text/plain">
<fieldset>
<legend>Send a notification email to the recorder.</legend>
<input type="submit" value="Send Email">
</fieldset>
</form>
';
          } else {
            $r .= '<div class="page-notice ui-state-highlight ui-corner-all">The record has been '.$action.'. The recorder did not leave an email address so cannot be notified.</div>';
          }
        }
      }
    }

    global $user;
    //extract fixed parameters for report grid.
    $params = explode( ",", $args['fixed_params']);
    foreach ($params as $param){
      $keyvals = explode("=", $param);
      $key = trim($keyvals[0]);
      $val = trim($keyvals[1]);
      $extraParams[$key] = $val;
    }
    
    $r .= data_entry_helper::report_grid(array(
      'id' => 'verification-grid',
      'dataSource' => $args['report_name'],
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => array(
        array('display' => 'Actions', 'actions' => array(
          array('caption' => 'Verify', 'javascript'=>'indicia_verify(\'{taxon}\', {occurrence_id}, true, '.$user->uid.'); return false;'),
          array('caption' => 'Reject', 'javascript'=>'indicia_verify(\'{taxon}\', {occurrence_id}, false, '.$user->uid.'); return false;')
        ))
      ),
      'itemsPerPage' =>10,
      'autoParamsForm' => $args['auto_params_form'],
      'extraParams' => $extraParams
    ));
    $r .= '
<form id="verify" method="post" action="">
  '.$auth['write'].'
  <input type="hidden" id="occurrence:id" name="occurrence:id" value="" />
  <input type="hidden" id="occurrence:record_status" name="occurrence:record_status" value="" />
  <input type="hidden" id="website7_id" name="website_id" value="'.$args['website_id'].'" />
  <input type="hidden" id="occurrence:verified_by_id" name="occurrence:verified_by_id" value="" />
</form>
';
    
    drupal_add_js('
var verifiers_mapping = "'.$args['verifiers_mapping'].'";
var url = '.json_encode(data_entry_helper::get_reload_link_parts()).';
function indicia_verify(taxon, id, valid, cmsUser){
  var action;
  if (valid) {
    $("#occurrence\\\\:record_status").attr("value", "V");
    action = "verify";
  } else {
    $("#occurrence\\\\:record_status").attr("value", "R");
    action = "reject";
  }
  if (confirm("Are you sure you want to " + action + " this record of " + taxon + "?")) {
    $("#occurrence\\\\:id").attr(\'value\', id);
    if (verifiers_mapping.indexOf("=")==-1) {
      verifier = verifiers_mapping;
    } else {
      var verifierMaps = verifiers_mapping.split(",");
      var keyval, verifiersArr = new Array();
      $.each(verifierMaps, function(idx, map) {
        keyval = map.split("=");
        if (parseInt(keyval[0].trim())==cmsUser) {
          verifier = keyval[1].trim();
        }
      });
    }
    $("#occurrence\\\\:verified_by_id").attr(\'value\', verifier);
    // We need to dynamically build the submitTo so we get the correct sort order
    var submitTo = "";
    // access globals created by the report grid to get the current state of pagination and sort as a result of AJAX calls
    url.params["page-verification-grid"] = report_grid_page;
    if (report_grid_orderby!=null) {
      url.params["orderby-verification-grid"] = report_grid_orderby;
    } else {
      delete url.params["orderby-verification-grid"];
    }
    if (report_grid_sortdir!=null) {
      url.params["sortdir-verification-grid"] = report_grid_sortdir;
    } else {
      delete url.params["sortdir-verification-grid"]
    }
    $.each(url.params, function(field, value) {
      submitTo += (submitTo ==="" ? "?" : "&");
      submitTo += field + "=" + value;
    });
    submitTo = url.path + submitTo;
    $("form#verify").attr("action", submitTo);
    $("form#verify").submit();
  }
}
', 'inline');
    return $r;
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
    $r = str_replace('%action%', $action, $args["email_$part"]);
    foreach($occ as $attr=>$value) {
      $r = str_replace('%'.$attr.'%', $value, $r);
    }
    global $user;
    $r = str_replace('%verifier%', $user->name, $r);
    return rawurlencode($r);
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    return data_entry_helper::build_submission($values, array('model'=>'occurrence'));
  }
  
}