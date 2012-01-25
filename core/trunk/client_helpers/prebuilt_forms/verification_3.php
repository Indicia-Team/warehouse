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

require_once('includes/map.php');

/**
 * Prebuilt Indicia data form that lists the output of an occurrences report with an option
 * to verify or reject each record.
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_verification_3 {
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_verification_3_definition() {
    return array(
      'title'=>'Verification 3',
      'category' => 'Verification',
      'description'=>'An advanced verification form with built in review of the record, images and comments. For full functionality, ensure that the report this loads '.
          'data from returns a field called occurrence_id and has a parameter called rule for filtering by verification rule. See the "Auto-checked verification data" '.
          'report for an example.',
      'helpLink' => 'http://code.google.com/p/indicia/wiki/PrebuiltFormVerification3'      
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
    return array_merge(
      iform_map_get_map_parameters(),
      array(
        array(
          'name'=>'report_name',
          'caption'=>'Report Name',
          'description'=>'The report to load into the verification grid, excluding the .xml suffix. This report should have '.
              'at least the following columns: occurrence_id, taxon and should have an idlist type parameter if you are displaying '.
              'a map to filter records against polygons. If you don\'t know which report to use, try the Auto-checked verification '.
              'data report under Library\Occurrences.',
          'type'=>'report_helper::report_picker',
          'default'=>'library/occurrences/verification_list_2',
          'group'=>'Report Settings'
        ), array(
          'name' => 'param_presets',
          'caption' => 'Preset Parameter Values',
          'description' => 'To provide preset values for any report parameter and avoid the user having to enter them, enter each parameter into this '.
              'box one per line. Each parameter is followed by an equals then the value, e.g. survey_id=6. You can use {user_id} as a value which will be replaced by the '.
              'user ID from the CMS logged in user or {username} as a value replaces with the logged in username. Preset Parameter Values can\'t be overridden by the user.',
          'type' => 'textarea',
          'required' => false,
          'group'=>'Report Settings',
          'default'=>'survey_id=
date_from=
date_to=
smpattrs=
occattrs='
        ), array(
          'name' => 'param_defaults',
          'caption' => 'Default Parameter Values',
          'description' => 'To provide default values for any report parameter which allow the report to run initially but can be overridden, enter each parameter into this '.
              'box one per line. Each parameter is followed by an equals then the value, e.g. survey_id=6. You can use {user_id} as a value which will be replaced by the '.
              'user ID from the CMS logged in user or {username} as a value replaces with the logged in username. Unlike preset parameter values, parameters referred '.
              'to by default parameter values are displayed in the parameters form and can therefore be changed by the user.',
          'type' => 'textarea',
          'required' => false,
          'group'=>'Report Settings',
          'default'=>'id=
taxon_group_id=
record_status=C
rule=all
searchArea='
        ), array(
          'name' => 'items_per_page',
          'caption' => 'Items per page',
          'description' => 'Maximum number of rows shown on each page of the table',
          'type' => 'int',
          'default' => 20,
          'required' => true,
          'group'=>'Report Settings'
        ), array(
            'name' => 'columns_config',
            'caption' => 'Columns Configuration',
            'description' => 'Define a list of columns with various configuration options when you want to override the '.
                'default output of the report.',
            'type' => 'jsonwidget',
            'default' => '[
              {"fieldname":"occurrence_id","template":"<div class=\'status-{record_status}\'>{occurrence_id}<\/div>","display":"ID"},
              {"fieldname":"taxon","display":"Species","template":"<div class=\'zero-{zero_abundance}\'>{taxon}<br\/>{common}<\/div>"},
              {"fieldname":"record_status","visible":false},
              {"fieldname":"common","visible":false},
              {"fieldname":"zero_abundance","visible":false},
              {"fieldname":"check","display":"Check"}
            ]',
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
            'group' => 'Report Settings',
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
          'name'=>'email_subject_send_to_verifier',
          'caption'=>'Send to Verifier Email Subject',
          'description'=>'Default subject for the send to verifier email. Replacements allowed include %taxon% and %id%.',
          'type'=>'string',
          'default' => 'Record of %taxon% requires verification (ID:%id%)',
          'group' => 'Verifier emails'
        ), array(
          'name'=>'email_body_send_to_verifier',
          'caption'=>'Send to Verifier Email Body',
          'description'=>'Default body for the acceptance email. Replacements allowed include %taxon%, %id% and %record% which is replaced to give details of the record.',
          'type'=>'textarea',
          'default' => 'The following record requires verification. Please reply to this mail with the word Verified or Rejected '.
              'in the email body, followed by any comments you have including the proposed re-identification if relevant on the next line.'.
              "\n\n%record%",
          'group' => 'Verifier emails'
        ), array(
          'name'=>'email_request_attribute',
          'caption'=>'Email Request Attribute',
          'description'=>'Enter the caption of a sample attribute and the value will be checked and '.
             'an email only sent if it is true. Leave blank if recorder emails are not required.',
          'type'=>'string',
          'default' => '',
          'required'=>false,
          'group' => 'Recorder emails',
         ), array(
          'name'=>'email_address_attribute',
          'caption'=>'Email Address Attribute',
          'description'=>'Enter the caption of the sample attribute being used to capture the ' .
          'email address for the recorder.',
          'type'=>'string',
          'default' => '',
          'required'=>false,
          'group' => 'Recorder emails',
        ),array(
          'name'=>'email_subject_verified',
          'caption'=>'Acceptance Email Subject',
          'description'=>'Default subject for the acceptance email. Replacements allowed include '.
              '%id% (occurrence id), %sample_id%, %taxon%.',
          'type'=>'string',
          'default' => 'Record of %taxon% verified',
          'required'=>false,
          'group' => 'Recorder emails'
        ), array(
          'name'=>'email_body_verified',
          'caption'=>'Acceptance Email Body',
          'description'=>'Default body for the acceptance email. Replacements allowed include '.
              '%id% (occurrence id), %sample_id%, %verifier% (username of verifier), %taxon%, %date%, %entered_sref%, %comment%.',
          'type'=>'textarea',
          'default' => "Your record of %taxon%, recorded on %date% at grid reference %entered_sref% has been checked by ".
            "an expert and verified.\nMany thanks for the contribution.\n\n%verifier%",
          'required'=>false,
          'group' => 'Recorder emails'
        ), array(
          'name'=>'email_subject_rejected',
          'caption'=>'Rejection Email Subject',
          'description'=>'Default subject for the rejection email. Replacements as for acceptance.',
          'type'=>'string',
          'default' => 'Record of %taxon% not verified',
          'required'=>false,
          'group' => 'Recorder emails'
        ), array(
          'name'=>'email_body_rejected',
          'caption'=>'Rejection Email Body',
          'description'=>'Default body for the rejection email. Replacements as for acceptance.',
          'type'=>'textarea',
          'default' => "Your record of %taxon%, recorded on %date% at grid reference %entered_sref% has been checked by ".
            "an expert but unfortunately it could not be verified because there was a problem with your photo.\n".
            "Nonetheless we are grateful for your contribution and hope you will be able to send us further records.\n\n%verifier%",
          'required'=>false,
          'group' => 'Recorder emails'
        ), array(
          'name'=>'auto_discard_rows',
          'caption'=>'Automatically remove rows',
          'description'=>'If checked, then when changing the status of a record the record is removed from the grid if it no '.
              'longer matches the grid filter.',
          'type'=>'checkbox',
          'default'=>'on',
          'required'=>false
        ),
        array(
          'name'=>'show_map',
          'caption'=>'Show map of the currently selected records',
          'description'=>'If checked, then a map of currently selected records is shown. This lets the verifier do things like visually spot outliers to check.',
          'type'=>'checkbox',
          'default'=>'on',
          'required'=>false,
          'Group'=>'Other Map Settings'
        )
      )
    );
  }
  
  private static function get_template_grid_left($args, $auth) {
    $r .= '<div id="outer-grid-left" class="ui-helper-clearfix">';
    $r .= '<div id="grid" class="left">{grid}';
    // Insert a button to verify all visible, only available if viewing the clean records.
    if (isset($_POST['verification-rule']) && $_POST['verification-rule']==='none' && empty($_POST['verification-id']))
      $r .= '<button type="button" id="btn-verify-all">'.lang::get('Verify all visible').'</button>';
    $r .= '</div>';
    $r .= '<div id="record-details-wrap" class="right ui-widget ui-widget-content">';
    $r .= self::instructions('grid on the left');    
    $r .= '<div id="record-details-content" style="display: none">';
    $r .= '<div id="record-details-toolbar">';
    $r .= '<button type="button" id="btn-verify">'.lang::get('Verify').'</button>';
    $r .= '<button type="button" id="btn-reject">'.lang::get('Reject').'</button>';
    $r .= '<button type="button" id="btn-email" class="default-button">'.lang::get('Email').'</button>';
    $r .= '</div>';
    $r .= '<div id="record-details-tabs">';
    // note - there is a dependency in the JS that comments is the last tab and images the 2nd to last.
    $r .= data_entry_helper::tab_header(array(
      'tabs'=>array(
        '#details-tab'=>'Details',
        '#map-tab'=>'Map',
        '#images-tab'=>'Images',
        '#comments-tab'=>'Comments'
      )
    ));
    data_entry_helper::enable_tabs(array(
      'divId'=>'record-details-tabs'
    ));
    $r .= '<div id="details-tab"></div>';
    $r .= '<div id="map-tab">';
    $options = iform_map_get_map_options($args, $auth);
    $options['tabDiv']='map-tab';
    $r .= map_helper::map_panel(
      $options,
      iform_map_get_ol_options($args)
    );
    $r .= '</div>';
    $r .= '<div id="images-tab"></div>';
    $r .= '<div id="comments-tab"></div>';
    $r .= '</div></div></div></div>';
    return $r;
  }
  
  private static function get_template_with_map($args, $auth, $extraParams, $paramDefaults) {
    $r .= '<div id="outer-with-map" class="ui-helper-clearfix">';
    $r .= '{paramsForm}';
    $r .= '<div id="map-and-record" style="clear: both;"><div id="summary-map" class="left">';
    $options = iform_map_get_map_options($args, $auth);
    $olOptions = iform_map_get_ol_options($args);
    // This is used for drawing, so need an editlayer, but not used for input
    $options['editLayer'] = true;
    $options['editLayerInSwitcher'] = true;
    $options['clickForSpatialRef'] = false;
    $r .= map_helper::map_panel(
      $options,
      $olOptions
    );
    // give realistic performance on the map
    $extraParams['limit']=1000;
    $r .= report_helper::report_map(array(
      'dataSource' => $args['report_name'],
      'mode' => 'report',
      'readAuth' => $auth,
      'autoParamsForm' => false,
      'extraParams' => $extraParams,
      'paramDefaults' => $paramDefaults,
      'reportGroup' => 'verification',
      'clickableLayersOutputMode' => 'report'
    ));
    $r .= '</div>';
    $r .= '<div id="record-details-wrap" class="right ui-widget ui-widget-content">';
    $r .= self::instructions('grid below');    
    $r .= '<div id="record-details-content" style="display: none">';
    $r .= '<div id="record-details-toolbar">';
    $r .= '<button type="button" id="btn-verify">'.lang::get('Verify').'</button>';
    $r .= '<button type="button" id="btn-reject">'.lang::get('Reject').'</button>';
    $r .= '<button type="button" id="btn-email" class="default-button">'.lang::get('Email').'</button>';
    $r .= '</div>';
    $r .= '<div id="record-details-tabs">';
    // note - there is a dependency in the JS that comments is the last tab and images the 2nd to last.
    $r .= data_entry_helper::tab_header(array(
      'tabs'=>array(
        '#details-tab'=>'Details',
        '#images-tab'=>'Images',
        '#comments-tab'=>'Comments'
      )
    ));
    data_entry_helper::enable_tabs(array(
      'divId'=>'record-details-tabs'
    ));
    $r .= '<div id="details-tab"></div>';    
    $r .= '<div id="images-tab"></div>';
    $r .= '<div id="comments-tab"></div>';
    $r .= '</div></div></div></div>';
    $r .= '<div id="grid" style="clear:both;">{grid}';
    // Insert a button to verify all visible, only available if viewing the clean records.
    if (isset($_POST['verification-rule']) && $_POST['verification-rule']==='none' && empty($_POST['verification-id']))
      $r .= '<button type="button" id="btn-verify-all">'.lang::get('Verify all visible').'</button>';
    $r .= '</div></div>';
    return $r;
  }
  
  /**
   * Constructs HTML for a block of instructions. 
   * @param string $gridPos Pass in a description of where the records grid is relative to the instruction  block, e.g. 'grid below' or 'grid on the left'
   * @return string HTML for the instruction div
   */
  private static function instructions($gridpos) {
    $r = '<div id="instructions">'.lang::get('You can').":\n<ul>\n";
    $r .= '<li>'.lang::get("Click on a record in the $gridpos to view the details.")."</li>\n";
    $r .= '<li>'.lang::get('When viewing the record details, verify, reject or email the record to someone for checking.')."</li>\n";
    $r .= '<li>'.lang::get('When viewing the record details, view and add comments on the record.')."</li>\n";    
    $r .= '<li>'.lang::get('Use the <strong>Report Parameters</strong> box to filter the list of records to verify.')."</li>\n";
    $r .= '<li>'.lang::get('When viewing a list of clean records with no verification rule violations, click the <strong>Verify all visible</strong> button to quickly verify records.')."</li>\n";
    $r .= '<li>'.lang::get('Use the map tool buttons to draw lines, polygons or points then reload the report using the <strong>Run Report</strong> button in the <strong>Report Parameters</strong> box.')."</li>\n";
    $r .= '<li>'.lang::get('Use the <strong>Buffer (m)</strong> input box to buffer your lines, polygons or points to search against.')."</li>\n";
    $r .= '<li>'.lang::get('Use the <strong>Query Map</strong> tool to click on points on the map and view them in the grid. You can also drag boxes to select multiple records.')."</li>\n";
    $r .= '</ul></div>';
    return $r;
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
    if (isset($_POST['enable'])) {
      module_enable(array('iform_ajaxproxy'));
      drupal_set_message(lang::get('The Indicia AJAX Proxy module has been enabled.', 'info'));
    }
    elseif (!defined('IFORM_AJAXPROXY_PATH')) {
      $r = '<p>'.lang::get('The Indicia AJAX Proxy module must be enabled to use this form. This lets the form save verifications to the '.
          'Indicia Warehouse without having to reload the page.').'</p>';
      $r .= '<form method="post">';
      $r .= '<input type="hidden" name="enable" value="t"/>';
      $r .= '<input type="submit" value="'.lang::get('Enable Indicia AJAX Proxy').'"/>';
      $r .= '</form>';
      return $r;
    }
    if (function_exists('drupal_add_js'))
      drupal_add_js('misc/collapse.js');
    iform_load_helpers(array('data_entry_helper', 'map_helper', 'report_helper'));
    // fancybox for popup comment forms etc
    data_entry_helper::add_resource('fancybox');
    data_entry_helper::add_resource('validation');
    global $user, $indicia_templates;
    $indicia_user_id=self::get_indicia_user_id($args);
    $auth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);

    //extract fixed parameters for report grid.
    $params = explode( "\n", $args['param_presets']);
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
    $opts = array(
      'id' => 'verification-grid',
      'dataSource' => $args['report_name'],
      'mode' => 'report',
      'readAuth' => $auth,
      'rowId' => 'occurrence_id',
      'itemsPerPage' =>isset($args['items_per_page']) ? $args['items_per_page'] : 20,      
      'extraParams' => $extraParams,
      'paramDefaults' => $paramDefaults,
      'fieldsetClass' => 'collapsible collapsed',
      'reportGroup' => 'verification'
    );
    if (!empty($args['columns_config'])) {
      $opts['autoParamsForm'] = true;
      $opts['columns'] = json_decode($args['columns_config'], true);
    } if (isset($args['show_map']) && $args['show_map']) {
      $opts['paramsOnly']=true;
      $paramsForm = data_entry_helper::report_grid($opts);
      $opts['paramsOnly']=false;
      $opts['autoParamsForm']=false;
      $grid = data_entry_helper::report_grid($opts);
      $r = str_replace(array('{grid}','{paramsForm}'), array($grid, $paramsForm), self::get_template_with_map($args, $auth, $extraParams, $paramDefaults));
    } else {
      $grid = data_entry_helper::report_grid($opts);
      $r = str_replace(array('{grid}'), array($grid), self::get_template_grid_left($args, $auth));
    }
    $link = data_entry_helper::get_reload_link_parts();
    global $user;
    data_entry_helper::$javascript .= 'indiciaData.nid = "'.$node->nid."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.username = "'.$user->name."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.rootUrl = "'.$link['path']."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.website_id = '.$args['website_id'].";\n";
    data_entry_helper::$javascript .= 'indiciaData.ajaxFormPostUrl="'.iform_ajaxproxy_url($node, 'occurrence')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.ajaxUrl="'.url('iform/ajax/verification_3')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.autoDiscard = '.$args['auto_discard_rows'].";\n";
    // output some translations for JS to use
    data_entry_helper::$javascript .= "indiciaData.popupTranslations = {};\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.title="'.lang::get('Add {1} comment')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.save="'.lang::get('Save and {1}')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.verbV="'.lang::get('verify')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.verbR="'.lang::get('reject')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.V="'.lang::get('Verification')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.R="'.lang::get('Rejection')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.emailTitle="'.lang::get('Email record details for checking')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.sendEmail="'.lang::get('Send Email')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.emailSent="'.lang::get('The email was sent successfully.')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.requestManualEmail="'.
        lang::get('The webserver is not correctly configured to send emails. Please send the following email usual your email client:')."\";\n";
    data_entry_helper::$javascript .= "indiciaData.statusTranslations = {};\n";
    data_entry_helper::$javascript .= 'indiciaData.statusTranslations.V = "'.lang::get('Verified')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.statusTranslations.R = "'.lang::get('Rejected')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.statusTranslations.I = "'.lang::get('In progress')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.statusTranslations.T = "'.lang::get('Test record')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.statusTranslations.S = "'.lang::get('Sent for verification')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.statusTranslations.C = "'.lang::get('Awaiting verification')."\";\n";
    
    data_entry_helper::$javascript .= 'indiciaData.email_subject_send_to_verifier = "'.$args['email_subject_send_to_verifier']."\";\n";
    $body = str_replace(array("\r", "\n"), array('', '\n'), $args['email_body_send_to_verifier']);
    data_entry_helper::$javascript .= 'indiciaData.email_body_send_to_verifier = "'.$body."\";\n";
    
    data_entry_helper::$javascript .= 'indiciaData.email_request_attribute = "'.$args['email_request_attribute']."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.email_address_attribute = "'.$args['email_address_attribute']."\";\n";
   
    data_entry_helper::$javascript .= 'indiciaData.email_subject_verified = "'.$args['email_subject_verified']."\";\n";
    $body = str_replace(array("\r", "\n"), array('', '\n'), $args['email_body_verified']);
    data_entry_helper::$javascript .= 'indiciaData.email_body_verified = "'.$body."\";\n";
    
    data_entry_helper::$javascript .= 'indiciaData.email_subject_rejected = "'.$args['email_subject_rejected']."\";\n";
    $body = str_replace(array("\r", "\n"), array('', '\n'), $args['email_body_rejected']);
    data_entry_helper::$javascript .= 'indiciaData.email_body_rejected = "'.$body."\";\n";
    return $r;
    
  }
  
  /**
   * Use the mapping from Drupal to Indicia users to get the Indicia user ID for the current logged in Drupal user.
   */
  private static function get_indicia_user_id($args) {
    $userId = '';
    global $user;
    if (substr(',', $args['verifiers_mapping'])!==false) {
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
  
  /**
   * Ajax handler to provide the content for the details of a single record.
   */
  public static function ajax_details($website_id, $password) {
    iform_load_helpers(array('data_entry_helper'));
    $auth = data_entry_helper::get_read_auth($website_id, $password);
    data_entry_helper::load_existing_record($auth, 'occurrence', $_GET['occurrence_id']);
    data_entry_helper::load_existing_record($auth, 'sample', data_entry_helper::$entity_to_load['occurrence:sample_id']);
    $siteLabels = array();
    if (!empty(data_entry_helper::$entity_to_load['sample:location'])) $siteLabels[] = data_entry_helper::$entity_to_load['sample:location'];
    if (!empty(data_entry_helper::$entity_to_load['sample:location_name'])) $siteLabels[] = data_entry_helper::$entity_to_load['sample:location_name'];
    // build an array of all the data. This allows the JS to insert the data into emails etc. Note we
    // use an array rather than an assoc array to build the JSON, so that order is guaranteed.
    $data = array(
      array('caption'=>lang::get('Species'), 'value'=>data_entry_helper::$entity_to_load['occurrence:taxon']),
      array('caption'=>lang::get('Date'), 'value'=>data_entry_helper::$entity_to_load['sample:date']),
      array('caption'=>lang::get('Grid Ref.'), 'value'=>data_entry_helper::$entity_to_load['sample:entered_sref']),
      array('caption'=>lang::get('Site'), 'value'=>implode(' | ', $siteLabels)),
      array('caption'=>lang::get('Comment'), 'value'=>data_entry_helper::$entity_to_load['sample:comment']),
      array('caption'=>lang::get('Comment'), 'value'=>data_entry_helper::$entity_to_load['occurrence:comment'])
    );
    $smpAttrs = data_entry_helper::getAttributes(array(
        'id' => data_entry_helper::$entity_to_load['sample:id'],
        'valuetable'=>'sample_attribute_value',
        'attrtable'=>'sample_attribute',
        'key'=>'sample_id',
        'extraParams'=>$auth,
        'survey_id'=>data_entry_helper::$entity_to_load['occurrence:survey_id']
    ));
    $occAttrs = data_entry_helper::getAttributes(array(
        'id' => $_GET['occurrence_id'],
        'valuetable'=>'occurrence_attribute_value',
        'attrtable'=>'occurrence_attribute',
        'key'=>'occurrence_id',
        'extraParams'=>$auth,
        'survey_id'=>data_entry_helper::$entity_to_load['occurrence:survey_id']
    ));
    $attributes = array_merge($smpAttrs, $occAttrs);
    foreach($attributes as $attr) {
      $data[] = array('caption'=>lang::get($attr['caption']), 'value'=>$attr['displayValue']);
    }
    
    $r = "<table>\n";
    $status = data_entry_helper::$entity_to_load['occurrence:record_status'];
    $r .= '<tr><td><strong>'.lang::get('Status').'</strong></td><td class="status status-'.$status.'">';
    $r .= self::statusLabel($status);
    if (data_entry_helper::$entity_to_load['occurrence:zero_abundance']==='t')
      $r .= '<br/>' . lang::get('This is a record indicating absence.');
    $r .= "</td></tr>\n";
    foreach($data as $item) {
      if (!is_null($item['value']) && $item['value'] != '') 
        $r .= "<tr><td><strong>".$item['caption']."</strong></td><td>".$item['value'] ."</td></tr>\n";
    }
    $r .= "</table>\n";
    $additional=array();
    $additional['wkt'] = data_entry_helper::$entity_to_load['occurrence:wkt'];
    $additional['taxon'] = data_entry_helper::$entity_to_load['occurrence:taxon'];
    $additional['sample_id'] = data_entry_helper::$entity_to_load['occurrence:sample_id'];
    $additional['date'] = data_entry_helper::$entity_to_load['sample:date'];
    $additional['entered_sref'] = data_entry_helper::$entity_to_load['sample:entered_sref'];
    header('Content-type: application/json');
    echo json_encode(array(
      'content' => $r,
      'data' => $data,
      'additional' => $additional
    ));
  }
  
  private function statusLabel($status) {
    switch ($status) {
      case 'V' :
        return lang::get('Verified');
      case 'R' :
        return lang::get('Rejected');
      case 'I' :
        return lang::get('In progress');
      case 'T' :
        return lang::get('Test record');
      case 'S' :
        return lang::get('Sent for verification');
      case 'C' :
        return lang::get('Awaiting verification');
      default :
        return lang::get('Unknown');
    }
    
  }
  
  public static function ajax_images($website_id, $password) {
    echo self::get_images($website_id, $password);
  }
  
  private static function get_images($website_id, $password) {
    iform_load_helpers(array('data_entry_helper'));
    $auth = data_entry_helper::get_read_auth($website_id, $password);
    $images = data_entry_helper::get_population_data(array(
      'table' => 'occurrence_image',
      'extraParams'=>$auth + array('occurrence_id'=>$_GET['occurrence_id']),
      'nocache'=>true
    ));
    $r = '';
    if (count($images)===0) 
      $r .= lang::get('No images found for this record '.$_GET['occurrence_id']);
    else {
      $path = data_entry_helper::get_uploaded_image_folder();
      $r .= '<ul class="gallery">';
      foreach ($images as $image) {
        $r .= '<li><a href="'.$path.$image['path'].'" class="fancybox"><img src="'.$path.'thumb-'.
            $image['path'].'"/>'.'<br/>'.$image['caption'].'</a></li>';
      }
      $r .= '</ul>';
    }
    $r .= '<script type="text/javascript">$("a.fancybox").fancybox();</script>';
    return $r;
  }
  
  public static function ajax_comments($website_id, $password) {
    echo self::get_comments($website_id, $password);
  }
  
  private static function get_comments($website_id, $password, $includeAddNew = true) {
    iform_load_helpers(array('data_entry_helper'));
    $auth = data_entry_helper::get_read_auth($website_id, $password);
    $comments = data_entry_helper::get_population_data(array(
      'table' => 'occurrence_comment',
      'extraParams' => $auth + array('occurrence_id'=>$_GET['occurrence_id'], 'sortdir'=>'DESC', 'orderby'=>'updated_on'),
      'nocache'=>true
    ));
    $r = '';
    if (count($comments)===0) 
      $r .= '<p id="no-comments">'.lang::get('No comments have been made.').'</p>';
    $r .= '<div id="comment-list">';
    foreach($comments as $comment) {
      $r .= '<div class="comment">';
      $r .= '<div class="header">';
      $r .= '<strong>'.(empty($comment['person_name']) ? $comment['username'] : $comment['person_name']).'</strong> ';
      $r .= self::ago(strtotime($comment['updated_on']));
      $r .= '</div>';
      $c = str_replace("\n", '<br/>', $comment['comment']);
      $r .= "<div>$c</div>";
      $r .= '</div>';
    }
    $r .= '</div>';
    if ($includeAddNew) {
      global $user;
      $r .= '<form><fieldset><legend>'.lang::get('Add new comment').'</legend>';
      $r .= '<input type="hidden" id="comment-by" value="'.$user->name.'"/>';
      $r .= '<textarea id="comment-text"></textarea><br/>';
      $r .= '<button type="button" class="default-button" onclick="saveComment();">'.lang::get('Save').'</button>';
      $r .= '</fieldset></form>';
    }
    return $r;
  }
  
  public static function ajax_imagesAndComments($website_id, $password) {
    header('Content-type: application/json');
    echo json_encode(array(
      'images' => self::get_images($website_id, $password),
      'comments' => self::get_comments($website_id, $password, false)
    ));
  }
  
  /**
   * Ajax method to send an email. Takes the subject and body in the $_GET parameters.
   * @return boolean True if the email was sent.
   */
  public static function ajax_email() {
    global $user;
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= 'From: '. $user->mail . PHP_EOL . "\r\n";
    $headers .= 'Return-Path: '. $user->mail . "\r\n";
    $emailBody = $_POST['body'];        
    $emailBody = str_replace("\n", "<br/>", $emailBody);
    // Send email. Depends upon settings in php.ini being correct
    $success = mail($_POST['to'],
         $_POST['subject'],
         wordwrap($emailBody, 70),
         $headers);
    if ($success)
      echo 'OK';
    else  
      echo 'Fail';
  }
  
  /** 
   * Convert a timestamp into readable format (... ago) for use on a comment list.
   * @param timestamp $timestamp The date time to convert.
   * @return string The output string.
   */
  private static function ago($timestamp) {
   $difference = time() - $timestamp;
   // Having the full phrase means that it is fully localisable if the phrasing is different.
   $periods = array(
       lang::get("{1} second ago"),
       lang::get("{1} minute ago"), 
       lang::get("{1} hour ago"), 
       lang::get("Yesterday"), 
       lang::get("{1} week ago"), 
       lang::get("{1} month ago"), 
       lang::get("{1} year ago"), 
       lang::get("{1} decade ago"));
   $periodsPlural = array(
       lang::get("{1} seconds ago"),
       lang::get("{1} minutes ago"), 
       lang::get("{1} hours ago"), 
       lang::get("{1} days ago"), 
       lang::get("{1} weeks ago"), 
       lang::get("{1} months ago"), 
       lang::get("{1} years ago"), 
       lang::get("{1} decades ago"));
   $lengths = array("60","60","24","7","4.35","12","10");
   for($j = 0; $difference >= $lengths[$j]; $j++)
   $difference /= $lengths[$j];
   $difference = round($difference);
   if($difference == 1) 
     $text = str_replace('{1}', $difference, $periods[$j]);
   else
     $text = str_replace('{1}', $difference, $periodsPlural[$j]);
   return $text; 
  }
  
}