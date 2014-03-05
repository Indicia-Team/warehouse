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
require_once('includes/report.php');
require_once('includes/report_filters.php');

/**
 * Prebuilt Indicia data form that lists the output of an occurrences report with an option
 * to verify, reject or flag dubious each record.
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_verification_4 {

  /**
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_verification_4_definition() {
    return array(
      'title'=>'Verification 4',
      'category' => 'Verification',
      'description'=>'Verification form based on standard report filtering system.'
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $r = array_merge(
      iform_map_get_map_parameters(),
      iform_report_get_minimal_report_parameters(),
      array(
        array(
          'name'=>'mapping_report_name',
          'caption'=>'Report for map output',
          'description'=>'Report used to obtain the output for the map. Should have the same parameters as the grid report but only needs to '.
              'return the occurrence id, geom and any shape formatting.',
          'type'=>'report_helper::report_picker',
          'group'=>'Report Settings',
          'default'=>'library/occurrences/filterable_explore_list_mapping'
        ),
        array(
          'name'=>'mapping_report_name_lores',
          'caption'=>'Report for lo res map output',
          'description'=>'Report used to obtain the output for the map at low zoom levels.',
          'type'=>'report_helper::report_picker',
          'group'=>'Report Settings',
          'default'=>'library/occurrences/filterable_explore_list_mapping_lores'
        ),
        array(
          'name'=>'record_details_report',
          'caption'=>'Report for record details',
          'description'=>'Report used to obtain the details of a record. See reports_for_prebuilt_forms/verification_3/record_data.xml for an example.',
          'type'=>'report_helper::report_picker',
          'group'=>'Report Settings',
          'default'=>'reports_for_prebuilt_forms/verification_3/record_data'
        ),
        array(
          'name'=>'record_attrs_report',
          'caption'=>'Report for record attributes',
          'description'=>'Report used to obtain the custom attributes of a record. See reports_for_prebuilt_forms/verification_3/record_data_attributes.xml for an example.',
          'type'=>'report_helper::report_picker',
          'group'=>'Report Settings',
          'default'=>'reports_for_prebuilt_forms/verification_3/record_data_attributes'
        ),
        array(
            'name' => 'columns_config',
            'caption' => 'Columns Configuration',
            'description' => 'Define a list of columns with various configuration options when you want to override the '.
                'default output of the report.',
            'type' => 'jsonwidget',
            'default' => '[]',
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
              '"1=2,2=3. If the Easy Login feature is enabled then this setting is ignored, as every Drupal user has their '.
              'own warehouse User ID.',
          'type'=>'textarea',
          'default'=>1
        ), array(
          'name'=>'email_subject_send_to_verifier',
          'caption'=>'Send to Expert Email Subject',
          'description'=>'Default subject for the send to expert email. Replacements allowed include %taxon% and %id%.',
          'type'=>'string',
          'default' => 'Record of %taxon% requires verification (ID:%id%)',
          'group' => 'Verifier emails'
        ), array(
          'name'=>'email_body_send_to_verifier',
          'caption'=>'Send to Expert Email Body',
          'description'=>'Default body for the send to expert email. Replacements allowed include %taxon%, %id% and %record% which is replaced to give details of the record.',
          'type'=>'textarea',
          'default' => 'The following record requires verification. Please reply to this mail with the word Verified, Rejected or Query '.
              'in the email body, followed by any comments you have including the proposed re-identification if relevant on the next line.'.
              "\n\n%record%",
          'group' => 'Verifier emails'
        ), array(
          'name'=>'email_subject_send_to_recorder',
          'caption'=>'Send to Recorder Email Subject',
          'description'=>'Default subject for the send to recorder email. Replacements allowed include %taxon% and %id%.',
          'type'=>'string',
          'default' => 'Record of %taxon% requires confirmation (ID:%id%)',
          'group' => 'Recorder emails'
        ), array(
          'name'=>'email_body_send_to_recorder',
          'caption'=>'Send to Recorder Email Body',
          'description'=>'Default body for the send to recorder email. Replacements allowed include %taxon%, %id% and %record% which is replaced to give details of the record.',
          'type'=>'textarea',
          'default' => 'The following record requires confirmation. Please could you reply to this email stating how confident you are that the record is correct '.
              'and any other information you have which may help to confirm this.'.
              "\n\n%record%",
          'group' => 'Recorder emails'
        ), array(
          'name'=>'auto_discard_rows',
          'caption'=>'Automatically remove rows',
          'description'=>'If checked, then when changing the status of a record the record is removed from the grid if it no '.
              'longer matches the grid filter.',
          'type'=>'checkbox',
          'default'=>'true',
          'required'=>false
        ),
        array(
          'name'=>'show_map',
          'caption'=>'Show map of the currently selected records',
          'description'=>'If checked, then a map of currently selected records is shown. This lets the verifier do things like visually spot outliers to check.',
          'type'=>'checkbox',
          'default'=>'true',
          'required'=>false,
          'group'=>'Other Map Settings'
        ),
        array(
          'name'=>'indicia_species_layer_feature_type',
          'caption'=>'Feature type for Indicia species layer',
          'description'=>'Set to the name of a feature type on GeoServer that will be loaded to display the Indicia species data for the selected record. '.
              'Leave empty for no layer. Normally this should be set to a feature type that exposes the cache_occurrences view.',
          'type'=>'text_input',
          'required'=>false,
          'group'=>'Other Map Settings'
        ),
        array(
          'name'=>'indicia_species_layer_ds_filter_field',
          'caption'=>'Filter method',
          'description'=>'Method of filtering taxa to display the species layer.',
          'type'=>'select',
          'options'=>array(
            'taxon_meaning_id'=>'Meaning ID',
            'taxon_external_key'=>'External Key',
          ),
          'required'=>false,
          'group'=>'Other Map Settings'
        ), array(
          'name'=>'indicia_species_layer_filter_field',
          'caption'=>'Field to filter on',
          'description'=>'Set to the name of a field exposed by the feature type which can be used to filter for the species data to display. Examples include '.
              'taxon_external_key, taxon_meaning_id.',
          'type'=>'text_input',
          'required'=>false,
          'group'=>'Other Map Settings'
        ),array(
          'name'=>'indicia_species_layer_sld',
          'caption'=>'SLD file from GeoServer for Indicia species layer',
          'description'=>'Set to the name of an SLD file available on the GeoServer for the rendering of the Indicia species layer, or leave blank for default.',
          'type'=>'text_input',
          'required'=>false,
          'group'=>'Other Map Settings'
        ),
        array(
          'name'=>'additional_wms_species_layer_title',
          'caption'=>'Additional WMS layer title',
          'description'=>'Title of an additional species layer to load from a WMS service',
          'type'=>'text_input',
          'required'=>false,
          'group'=>'Other Map Settings'
        ),
        array(
          'name'=>'additional_wms_species_layer_url',
          'caption'=>'Additional WMS layer URL',
          'description'=>'URL of an additional species layer to load from a WMS service. {external_key} is replaced by the species external key.',
          'type'=>'text_input',
          'required'=>false,
          'group'=>'Other Map Settings'
        ),
        array(
          'name'=>'additional_wms_species_layer_settings',
          'caption'=>'Additional WMS layer settings',
          'description'=>'JSON settings object for an additional species layer to load from a WMS service. {external_key} is replaced by the species external key.',
          'type'=>'textarea',
          'required'=>false,
          'group'=>'Other Map Settings'
        ),
        array(
          'name'=>'additional_wms_species_layer_ol_settings',
          'caption'=>'Additional WMS layer OpenLayers settings',
          'description'=>'JSON settings object for the Open Layers settings object for an additional species layer to load from a WMS service.',
          'type'=>'textarea',
          'required'=>false,
          'group'=>'Other Map Settings'
        ),
        array(
          'name'=>'view_records_report_path',
          'caption'=>'View records report path',
          'description'=>'Path to page used to show a list of records, e.g. when clicking on the record counts on the Experience tab',
          'type'=>'string',
          'required' => 'false'
        )
      )
    );
    // Set default values for the report
    foreach($r as &$param) {
      if ($param['name']=='report_name')
        $param['default']='library/occurrences/verification_list_3';
      elseif ($param['name']=='param_presets') {
        $param['default'] = 'survey_id=
date_from=
date_to=
smpattrs=
occattrs=';
      }
      elseif ($param['name']=='param_defaults')
        $param['default'] = 'id=
record_status=C
records=unverified
searchArea=
idlist=';

    }
    return $r;
  }
  
  /**
   * Returns the HTML for the standard set of tabs, excluding the details and optional map tab.
   * @return string HTML to insert onto the page
   */
  private static function other_tab_html() {
    $r = '<div id="experience-tab"><p>'.lang::get('Recorder\'s other records of this species and species group. Click to explore:').'</p><div id="experience-div"></div></div>';
    $r .= '<div id="phenology-tab"><p>'.lang::get('The following phenology chart shows the relative abundance of records through the '.
        'year for this species, <em>from the verified online recording data only.</em>').'</p><div id="chart-div"></div></div>';
    $r .= '<div id="images-tab"></div>';
    $r .= '<div id="comments-tab"></div>';
    return $r;
  }

  private static function get_template_with_map($args, $readAuth, $extraParams, $paramDefaults) {
    $r = '<div id="outer-with-map" class="ui-helper-clearfix">';
    $r .= '<div id="grid" class="left" style="width:65%">{paramsForm}{grid}';
    // Insert a button to verify all visible, only available if viewing the clean records.
    if (isset($_POST['verification-rule']) && $_POST['verification-rule']==='none' && empty($_POST['verification-id']))
      $r .= '<button type="button" id="btn-verify-all">'.lang::get('Verify all visible').'</button>';
    $r .= '</div>';
    $r .= '<div id="map-and-record" class="right" style="width: 34%"><div id="summary-map">';
    $options = iform_map_get_map_options($args, $readAuth);
    $olOptions = iform_map_get_ol_options($args);
    // This is used for drawing, so need an editlayer, but not used for input
    $options['editLayer'] = true;
    $options['editLayerInSwitcher'] = true;
    $options['clickForSpatialRef'] = false;
    $options['featureIdField']='occurrence_id';
    $r .= map_helper::map_panel(
      $options,
      $olOptions
    );
    $reportMapOpts=array(
      'dataSource' => !empty($args['mapping_report_name']) ? $args['mapping_report_name'] : $args['report_name'],
      'mode' => 'report',
      'readAuth' => $readAuth,
      'autoParamsForm' => false,
      'extraParams' => $extraParams,
      'paramDefaults' => $paramDefaults,
      'reportGroup' => 'verification',
      'clickableLayersOutputMode' => 'report',
      'rowId'=>'occurrence_id',
      'sharing'=>'verification',
      'ajax'=>TRUE
    );
    if (!empty($args['mapping_report_name_lores']))
      $reportMapOpts['dataSourceLoRes']=$args['mapping_report_name_lores'];
    $r .= report_helper::report_map($reportMapOpts);
    $r .= '</div>';
    global $user;
    if (function_exists('hostsite_get_user_field') && $locationId=hostsite_get_user_field('location_expertise', false)) 
      iform_map_zoom_to_location($locationId, $readAuth);
    $r .= '<div id="record-details-wrap" class="ui-widget ui-widget-content">';
    $r .= self::instructions('grid on the left');
    $r .= '<div id="record-details-content" style="display: none">';
    $r .= '<div id="record-details-toolbar">';
    $r .= '<label>Set status:</label>';
    $r .= '<button type="button" id="btn-verify">'.lang::get('Verify').'</button>';
    $r .= '<button type="button" id="btn-reject">'.lang::get('Reject').'</button>';
    $r .= '<button type="button" id="btn-query">'.lang::get('Query').'</button>';
    $r .= '<button type="button" id="btn-multiple" title="'.lang::get('Select this tool to tick off a list of records and action all of the ticked records in one go').'">'.lang::get('Select records').'</button>';
    $r .= '<br/><label>Contact:</label>';
    $r .= '<button type="button" id="btn-email-expert" class="default-button">'.lang::get('Another expert').'</button>';
    $r .= '<button type="button" id="btn-email-recorder" class="default-button">'.lang::get('Recorder').'</button>';
    $r .= '</div>';
    $r .= '<div id="record-details-tabs">';
    // note - there is a dependency in the JS that comments is the last tab and images the 2nd to last.
    $r .= data_entry_helper::tab_header(array(
      'tabs'=>array(
        '#details-tab'=>lang::get('Details'),
        '#experience-tab'=>lang::get('Experience'),
        '#phenology-tab'=>lang::get('Phenology'),
        '#images-tab'=>lang::get('Images'),
        '#comments-tab'=>lang::get('Comments')
      )
    ));
    data_entry_helper::$javascript .= "indiciaData.detailsTabs = ['details','experience','phenology','images','comments'];\n";
    data_entry_helper::enable_tabs(array(
      'divId'=>'record-details-tabs'
    ));
    $r .= '<div id="details-tab"></div>';
    $r .= self::other_tab_html();
    $r .= '</div></div></div></div></div>';
    return $r;
  }

  /**
   * Constructs HTML for a block of instructions.
   * @param string $gridPos Pass in a description of where the records grid is relative to the instruction  block, e.g. 'grid below' or 'grid on the left'
   * @return string HTML for the instruction div
   */
  private static function instructions($gridpos) {
    $r = '<div id="instructions">'.lang::get('You can').":\n<ul>\n";
    $r .= '<li>'.lang::get('Select the records to include in the list of records to verify using the drop-down box above the grid.')."</li>\n";
    $r .= '<li>'.lang::get('Fine tune the list of records by entering search criteria into the boxes at the top of each grid column.')."</li>\n";
    $r .= '<li>'.lang::get("Click on a record in the $gridpos to view the details.")."</li>\n";
    $r .= '<li>'.lang::get('When viewing the record details, verify, reject, query or email the record details for confirmation.')."</li>\n";
    $r .= '<li>'.lang::get('When viewing the record details, view and add comments on the record.')."</li>\n";
    $r .= '<li>'.lang::get('Use the ... button to the left of each record to view bulk-verification options for similar records.')."</li>\n";
    $r .= '<li>'.lang::get('Use the map tool buttons to draw lines, polygons or points then reload the report using the <strong>Filter</strong> button above the grid.')."</li>\n";
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
    // set some defaults, applied when upgrading from a form configured on a previous form version.
    if (empty($args['email_subject_send_to_recorder']))
      $args['email_subject_send_to_recorder'] = 'Record of %taxon% requires confirmation (ID:%id%)';
    if (empty($args['email_body_send_to_recorder']))
      $args['email_body_send_to_recorder'] = 'The following record requires confirmation. Please could you reply to this email stating how confident you are that the record is correct '.
              'and any other information you have which may help to confirm this.'.
              "\n\n%record%";
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
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    data_entry_helper::$js_read_tokens = $readAuth;
    // Find a list of websites we are allowed verify
    $websiteIds = iform_get_allowed_website_ids($readAuth, 'verification');
    if (function_exists('module_exists') && module_exists('easy_login')) {
      if (strpos($args['param_presets'].$args['param_defaults'], 'expertise_location')===false)
        $args['param_presets'].="\nexpertise_location={profile_location_expertise}";
      if (strpos($args['param_presets'].$args['param_defaults'], 'expertise_taxon_groups')===false)
        $args['param_presets'].="\nexpertise_taxon_groups={profile_taxon_groups_expertise}";
      if (strpos($args['param_presets'].$args['param_defaults'], 'expertise_surveys')===false)
        $args['param_presets'].="\nexpertise_surveys={profile_surveys_expertise}";
    }
    $args['sharing']='verification';
    $opts = array_merge(
        iform_report_get_report_options($args, $readAuth),
        array(
          'id' => 'verification-grid',
          'reportGroup' => 'verification',
          'rowId' => 'occurrence_id',
          'paramsFormButtonCaption' => lang::get('Filter'),
          'paramPrefix'=>'<div class="report-param">',
          'paramSuffix'=>'</div>',
          'sharing'=>'verification',
          'ajax'=>TRUE
        )
    );
    $opts['columns'][] = array(
      'display'=>'',
      'template' => '<div class="nowrap"><button class="default-button quick-verify tools-btn" type="button" id="quick-{occurrence_id}" title="Record tools">...</button>'.
          '<input type="hidden" class="row-input-form" value="{rootFolder}{input_form}"/><input type="hidden" class="row-belongs-to-site" value="{belongs_to_site}"/><ul class="verify-tools"><li><a href="#" class="quick-verify-tool">Bulk verify similar records</a></li>'.
          '<li><a href="#" class="trust-tool">Recorder\'s trust settings</a></li><li><a href="#" class="edit-record">Edit record</a></li></ul>'.
          '<input type="checkbox" class="check-row no-select" style="display: none" value="{occurrence_id}" /></div>'
    );
    $params = self::report_filter_panel($args, $readAuth);
    $opts['zoomMapToOutput']=false;
    $grid = report_helper::report_grid($opts);
    $r = str_replace(array('{grid}','{paramsForm}'), array($grid, $params),
        self::get_template_with_map($args, $readAuth, $opts['extraParams'], $opts['paramDefaults']));
    $link = data_entry_helper::get_reload_link_parts();
    global $user;
    data_entry_helper::$js_read_tokens = $readAuth;
    data_entry_helper::$javascript .= 'indiciaData.nid = "'.$node->nid."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.username = "'.$user->name."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.userId = "'.$indicia_user_id."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.rootUrl = "'.$link['path']."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.website_id = '.$args['website_id'].";\n";
    data_entry_helper::$javascript .= 'indiciaData.ajaxFormPostUrl="'.iform_ajaxproxy_url($node, 'occurrence')."&user_id=$indicia_user_id&sharing=verification\";\n";
    data_entry_helper::$javascript .= 'indiciaData.ajaxUrl="'.url('iform/ajax/verification_4')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.autoDiscard = '.$args['auto_discard_rows'].";\n";
    if (!empty($args['indicia_species_layer_feature_type']) && !empty(data_entry_helper::$geoserver_url)) {
      data_entry_helper::$javascript .= "indiciaData.indiciaSpeciesLayer = {\n".
          '  "title":"'.lang::get('Online recording data for this species')."\",\n".
          '  "featureType":"'.$args['indicia_species_layer_feature_type']."\",\n".
          '  "wmsUrl":"'.data_entry_helper::$geoserver_url."wms\",\n".
          '  "cqlFilter":"website_id IN ('.implode(',',$websiteIds).') AND '.$args['indicia_species_layer_filter_field']."='{filterValue}'\",\n".
          '  "filterField":"'.$args['indicia_species_layer_ds_filter_field']."\",\n".
          '  "sld":"'.(isset($args['indicia_species_layer_sld']) ? $args['indicia_species_layer_sld'] : '')."\"\n".
          "};\n";
    }
    if (!empty($args['additional_wms_species_layer_title'])) {
      data_entry_helper::$javascript .= 'indiciaData.wmsSpeciesLayers = [{"title":"'.$args['additional_wms_species_layer_title'].'",'.
          '"url":"'.$args['additional_wms_species_layer_url'].'",'.
          '"settings":'.$args['additional_wms_species_layer_settings'].','.
          '"olSettings":'.$args['additional_wms_species_layer_ol_settings'].
          "}];\n";
    }
    // output some translations for JS to use
    data_entry_helper::$javascript .= "indiciaData.popupTranslations = {};\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.title="'.lang::get('Add {1} comment')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.save="'.lang::get('Save and {1}')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.verbV="'.lang::get('verify')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.verbR="'.lang::get('reject')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.verbD="'.lang::get('query')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.V="'.lang::get('Verification')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.R="'.lang::get('Rejection')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.D="'.lang::get('Query')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.emailTitle="'.lang::get('Email record details for checking')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.sendEmail="'.lang::get('Send Email')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.emailSent="'.lang::get('The email was sent successfully.')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.requestManualEmail="'.
        lang::get('The webserver is not correctly configured to send emails. Please send the following email usual your email client:')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.multipleWarning="'.
        lang::get('You are about to verify multiple records. Please note that this comment will apply to all the ticked records. '.
        'If you did not intend to do this, please close this box and turn off the Select Records tool before proceeding.')."\";\n";     
    data_entry_helper::$javascript .= "indiciaData.statusTranslations = {};\n";
    data_entry_helper::$javascript .= 'indiciaData.statusTranslations.V = "'.lang::get('Verified')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.statusTranslations.R = "'.lang::get('Rejected')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.statusTranslations.D = "'.lang::get('Query')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.statusTranslations.I = "'.lang::get('In progress')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.statusTranslations.T = "'.lang::get('Test record')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.statusTranslations.S = "'.lang::get('Sent for verification')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.statusTranslations.C = "'.lang::get('Awaiting verification')."\";\n";
    data_entry_helper::$javascript .= "indiciaData.commentTranslations = {};\n";
    data_entry_helper::$javascript .= 'indiciaData.commentTranslations.emailed = "'.lang::get('I emailed this record to {1} for checking.')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.commentTranslations.recorder = "'.lang::get('the recorder')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.commentTranslations.expert = "'.lang::get('an expert')."\";\n";

    data_entry_helper::$javascript .= 'indiciaData.email_subject_send_to_verifier = "'.$args['email_subject_send_to_verifier']."\";\n";
    $body = str_replace(array("\r", "\n"), array('', '\n'), $args['email_body_send_to_verifier']);
    data_entry_helper::$javascript .= 'indiciaData.email_body_send_to_verifier = "'.$body."\";\n";

    data_entry_helper::$javascript .= 'indiciaData.email_subject_send_to_recorder = "'.$args['email_subject_send_to_recorder']."\";\n";
    $body = str_replace(array("\r", "\n"), array('', '\n'), $args['email_body_send_to_recorder']);
    data_entry_helper::$javascript .= 'indiciaData.email_body_send_to_recorder = "'.$body."\";\n";

    data_entry_helper::$javascript .= 'indiciaData.str_month = "'.lang::get('month')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.expertise_location = "'.$opts['extraParams']['expertise_location']."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.expertise_surveys = "'.$opts['extraParams']['expertise_surveys']."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.expertise_taxon_groups = "'.$opts['extraParams']['expertise_taxon_groups']."\";\n";
    data_entry_helper::add_resource('jqplot');
    data_entry_helper::add_resource('jqplot_bar');
    return $r;

  }

  /**
   * Use the mapping from Drupal to Indicia users to get the Indicia user ID for the current logged in Drupal user.
   * If there is a user profile field called profile_indicia_user_id then this value is used instead, for
   * example when the Easy Login feature is installed.
   */
  private static function get_indicia_user_id($args) {
    // Does the host site provide a warehouse user ID?
    if (function_exists('hostsite_get_user_field') && $userId = hostsite_get_user_field('indicia_user_id'))
      return $userId;
    // Use the configured mapping from local user ID to warehouse ID.
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
  public static function ajax_details($website_id, $password, $node) {
    $details_report = empty($node->params['record_details_report']) ? 'reports_for_prebuilt_forms/verification_3/record_data' : $node->params['record_details_report'];
    $attrs_report = empty($node->params['record_attrs_report']) ? 'reports_for_prebuilt_forms/verification_3/record_data_attributes' : $node->params['record_attrs_report'];
    iform_load_helpers(array('report_helper'));
    // Auth should be passed from JS to save regenerating it
    $readAuth = array(
      'nonce' => $_GET['nonce'],
      'auth_token' => $_GET['auth_token']
    );
    $options = array(
      'dataSource' => $details_report,
      'readAuth' => $readAuth,
      'sharing' => 'verification',
      'extraParams' => array('occurrence_id'=>$_GET['occurrence_id'], 'wantColumns'=>1, 'locality_type_id' => variable_get('indicia_profile_location_type_id', 0))
    );
    $reportData = report_helper::get_report_data($options);
    // set some values which must exist in the record
    $record = array_merge(array(
        'wkt'=>'','taxon'=>'','sample_id'=>'','date'=>'','entered_sref'=>'','taxon_external_key'=>'','taxon_meaning_id'=>'','record_status'=>'','zero_abundance'=>''
    ), $reportData['records'][0]);
    // build an array of all the data. This allows the JS to insert the data into emails etc. Note we
    // use an array rather than an assoc array to build the JSON, so that order is guaranteed.
    $data = array();
    $email='';
    foreach($reportData['columns'] as $col=>$def) {
      if ($def['visible']!=='false' && !empty($record[$col])) {
        $caption = explode(':', $def['display']);
        // is this a new heading?
        if (!isset($data[$caption[0]]))
          $data[$caption[0]]=array();
        $data[$caption[0]][] = array('caption'=>$caption[1], 'value'=>$record[$col]);
      }
      if ($col==='email' && !empty($record[$col])) 
        $email=$record[$col];
    }

    // Do the custom attributes
    $options = array(
      'dataSource' => $attrs_report,
      'readAuth' => $readAuth,
      'sharing' => 'verification',
      'extraParams' => array('occurrence_id'=>$_GET['occurrence_id'])
    ); 
    $reportData = report_helper::get_report_data($options);
    foreach ($reportData as $attribute) {
      if (!empty($attribute['value'])) {
        if (!isset($data[$attribute['attribute_type']]))
          $data[$attribute['attribute_type']]=array();
        $data[$attribute['attribute_type']][] = array('caption'=>$attribute['caption'], 'value'=>$attribute['value']);
      }
    }

    $r = "<table>\n";
    $r .= '<tr><td class="caption">'.lang::get('Status').'</td><td class="status status-'.$record['record_status'].'">';
    $r .= self::statusLabel($record['record_status']);
    if ($record['zero_abundance']==='t')
      $r .= '<br/>' . lang::get('This is a record indicating absence.');
    $r .= "</td></tr>\n";
    foreach($data as $heading=>$items) {
      $r .= "<tr><td colspan=\"2\" class=\"header\">$heading</td></tr>\n";
      foreach ($items as $item) {
        if (!is_null($item['value']) && $item['value'] != '') {
          $r .= "<tr><td class=\"caption\">".$item['caption']."</td><td>".$item['value'] ."</td></tr>\n";
          if ($email==='' && (strtolower($item['caption'])==='email' || strtolower($item['caption'])==='email address'))
            $email=$item['value'];
        }
      }
    }
    $r .= "</table>\n";

    $extra=array();
    $extra['wkt'] = $record['wkt'];
    $extra['taxon'] = $record['taxon'];
    $extra['recorder'] = $record['recorder'];
    $extra['sample_id'] = $record['sample_id'];
    $extra['created_by_id'] = $record['created_by_id'];
    $extra['input_by_first_name'] = $record['input_by_first_name'];
    $extra['input_by_surname'] = $record['input_by_surname'];
    $extra['survey_title'] = $record['survey_title'];
    $extra['survey_id'] = $record['survey_id'];
    $extra['date'] = $record['date'];
    $extra['entered_sref'] = $record['entered_sref'];
    $extra['taxon_external_key'] = $record['taxon_external_key'];
    $extra['taxon_meaning_id'] = $record['taxon_meaning_id'];
    $extra['recorder_email'] = $email;
    $extra['taxon_group'] = $record['taxon_group'];
    $extra['taxon_group_id'] = $record['taxon_group_id'];
    $extra['localities'] = $record['localities'];
    $extra['locality_ids'] = $record['locality_ids'];
    header('Content-type: application/json');
    echo json_encode(array(
      'content' => $r,
      'data' => $data,
      'extra' => $extra
    ));
  }

  private function statusLabel($status) {
    switch ($status) {
      case 'V' :
        return lang::get('Verified');
      case 'R' :
        return lang::get('Rejected');
      case 'D' :
        return lang::get('Queried');
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
    // Auth should be passed from JS to save regenerating it
    $readAuth = array(
      'nonce' => $_GET['nonce'],
      'auth_token' => $_GET['auth_token']
    );
    echo self::get_images($readAuth);
  }

  private static function get_images($readAuth) {
    iform_load_helpers(array('data_entry_helper'));
    $images = data_entry_helper::get_population_data(array(
      'table' => 'occurrence_image',
      'extraParams'=>$readAuth + array('occurrence_id'=>$_GET['occurrence_id']),
      'nocache'=>true,
      'sharing'=>'verification'
    ));
    $r = '';
    if (count($images)===0)
      $r .= lang::get('No images found for this record');
    else {
      $path = data_entry_helper::get_uploaded_image_folder();
      $r .= '<ul class="gallery">';
      foreach ($images as $image) {
        $r .= '<li><a href="'.$path.$image['path'].'" class="fancybox"><img src="'.$path.'thumb-'.
            $image['path'].'"/>'.'<br/>'.$image['caption'].'</a></li>';
      }
      $r .= '</ul>';
      $r .= '<p>'.lang::get('Click on image thumbnails to view full size').'</p>';
    }
    return $r;
  }

  public static function ajax_comments($website_id, $password) {
    // Auth should be passed from JS to save regenerating it
    $readAuth = array(
      'nonce' => $_GET['nonce'],
      'auth_token' => $_GET['auth_token']
    );
    echo self::get_comments($readAuth);
  }

  private static function get_comments($readAuth, $includeAddNew = true) {
    iform_load_helpers(array('data_entry_helper'));
    $comments = data_entry_helper::get_population_data(array(
      'table' => 'occurrence_comment',
      'extraParams' => $readAuth + array('occurrence_id'=>$_GET['occurrence_id'], 'sortdir'=>'DESC', 'orderby'=>'updated_on'),
      'nocache'=>true,
      'sharing'=>'verification'
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
    // Auth should be passed from JS to save regenerating it
    $readAuth = array(
      'nonce' => $_GET['nonce'],
      'auth_token' => $_GET['auth_token']
    );
    header('Content-type: application/json');
    echo json_encode(array(
      'images' => self::get_images($readAuth),
      'comments' => self::get_comments($readAuth, false)
    ));
  }

  /**
   * Ajax method to send an email. Takes the subject and body in the $_GET parameters.
   * @return boolean True if the email was sent.
   */
  public static function ajax_email() {
    global $user;
    $site_email = variable_get('site_mail', '');
    $headers = array();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8;';
    $headers[] = 'From: '. $site_email;
    $headers[] = 'Reply-To: '. $user->mail;
    $headers[] = 'Return-Path: '. $site_email;
    $headers = implode("\r\n", $headers) . PHP_EOL;
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
   * AJAX callback method to fill in the record's experience tab.
   * 
   * Returns a report detailing the total number of records of the species and
   * species group, as well as a breakdown by verified and rejected records.
   * Records link to the Explore report if view_records_report_path is filled in.
   * 
   * @param type $website_id
   * @param type $password
   * @param type $node 
   */
  public static function ajax_experience($website_id, $password, $node) {
    iform_load_helpers(array('report_helper'));
    // Auth should be passed from JS to save regenerating it
    $readAuth = array(
      'nonce' => $_GET['nonce'],
      'auth_token' => $_GET['auth_token']
    );
    $data = report_helper::get_report_data(array(
      'dataSource' => 'library/totals/user_experience_for_record',
      'readAuth' => $readAuth,
      'extraParams' => array('occurrence_id'=>$_GET['occurrence_id'])
    ));
    $r = '';
    foreach($data as $row) {
      if ($row['v_total']===0) {
        $r .= '<p>This recorder has not recorded this ' + $row['type'] + ' before.</p>';
      } else {
        $r .= '<p>Records of ' . $row['what'] . '<p>';
        $r .= '<table><thead><th></th><th>Last 3 months</th><th>Last year</th><th>All time</th></thead>';
        $r .= '<tbody>';
        $r .= '<tr class="verified"><th>Verified</th><td>' . self::records_link($row, 'v_3months', $node) . '</td><td>' . 
                self::records_link($row, 'v_1year', $node) . '</td><td>' . self::records_link($row, 'v_total', $node) . '</td></tr>';
        $r .= '<tr class="rejected"><th>Rejected</th><td>' . self::records_link($row, 'r_3months', $node) . '</td><td>' . 
                self::records_link($row, 'r_1year', $node) . '</td><td>' . self::records_link($row, 'r_total', $node) . '</td></tr>';
        $r .= '<tr class="total"><th>Total</th><td>' . self::records_link($row, 'total_3months', $node) . '</td><td>' . 
                self::records_link($row, 'total_1year', $node) . '</td><td>' . self::records_link($row, 'total_total', $node) . '</td></tr>';
        $r .= "</tbody></table>\n";
        
      }
    }
    // See if there is a filled in profile_experience field for the user. If so, add
    // the statement to the response.
    if (!empty($_GET['user_id'])) {
      $result = db_query("SELECT vuid.value
FROM {profile_values} vuser_id
JOIN {profile_fields} fuser_id ON fuser_id.fid=vuser_id.fid AND fuser_id.name='profile_indicia_user_id'
JOIN {profile_values} vuid ON vuid.uid=vuser_id.uid
JOIN {profile_fields} fuid ON fuid.fid=vuid.fid AND fuid.name='profile_experience'
WHERE vuser_id.value=".$_GET['user_id']);
      if ($exp = db_fetch_object($result)) {
        if (!empty($exp->value)) 
          $r .= "<h3>User's description of their experience</h3>{$exp->value}\n";
      } 
    }
    echo $r;
  }
  
  /**
   * Convert a number on the Experience tab into a link to the Explore page for the underlying records.
   */
  private static function records_link($row, $value, $node) {
    if (!empty($node->params['view_records_report_path']) && !empty($_GET['user_id'])) {
      $tokens = explode('_', $value);
      $params = array('dynamic-ownGroups'=>0,'dynamic-recent'=>0,'dynamic-user_filter'=>$_GET['user_id']);
      switch ($tokens[0]) {
        case 'r' : 
          $params['dynamic-record_status'] = 'R';
          break;
        case 'v' :
          $params['dynamic-record_status'] = 'V';
          break;
      }
      switch ($tokens[1]) {
        case '3months' :
          $params['dynamic-input_date_from'] = date('Y-m-d', strToTime('3 months ago'));
          break;
        case '1year' :
          $params['dynamic-input_date_from'] = date('Y-m-d', strToTime('1 year ago'));
          break;
      }
      if ($row['type']==='species')
        $params['dynamic-taxon_meaning_id'] = $row['what_id'];
      else
        $params['dynamic-taxon_group_id'] = $row['what_id'];
      return l($row[$value], $node->params['view_records_report_path'], 
          array('attributes'=>array('target' => '_blank'), 'query'=>$params));
      
    } else
      return $row[$value];
  }

  /**
   * Ajax method to retrieve phenology data for a species by external key.
   */
  public static function ajax_phenology($website_id, $password) {
    iform_load_helpers(array('report_helper'));
    // Auth should be passed from JS to save regenerating it
    $readAuth = array(
      'nonce' => $_GET['nonce'],
      'auth_token' => $_GET['auth_token']
    );
    $extraParams = array(
      'external_key'=>(empty($_GET['external_key']) || $_GET['external_key']==='null') ? '' : $_GET['external_key'],
      'taxon_meaning_id'=>(empty($_GET['external_key']) || $_GET['external_key']==='null') ? $_GET['taxon_meaning_id'] : '',
      'date_from'=>'',
      'date_to'=>'',
      'survey_id'=>'',
      'quality'=>'V'
    );
    $data = report_helper::get_report_data(array(
      'dataSource' => 'library/months/phenology',
      'readAuth' => $readAuth,
      'extraParams' => $extraParams,
      'sharing'=>'verification'
    ));
    // must output all months
    $output = array(array(1,0),array(2,0),array(3,0),array(4,0),array(5,0),array(6,0),array(7,0),array(8,0),array(9,0),array(10,0),array(11,0),array(12,0));
    foreach ($data as $month)
      // -1 here, because our array is zero indexed, but the report returns a real month number
      $output[$month['name']-1][1] = intval($month['value']);
    echo json_encode($output);
  }

  /**
   * Ajax method to proxy requests for bulk verification on to the warehouse, attaching write auth
   * as it goes.
   */
  public static function ajax_bulk_verify($website_id, $password) {
    iform_load_helpers(array('data_entry_helper'));
    $auth = data_entry_helper::get_read_write_auth($website_id, $password);
    $url = data_entry_helper::$base_url."index.php/services/data_utils/bulk_verify";
    $params = array_merge($_POST, $auth['write_tokens']);
    $response = data_entry_helper::http_post($url, $params);
    echo $response['output'];
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
   for($j = 0; (($difference >= $lengths[$j]) && ($j < 7)) ; $j++) {
     $difference /= $lengths[$j];
   }
   $difference = round($difference);
   if($difference == 1)
     $text = str_replace('{1}', $difference, $periods[$j]);
   else
     $text = str_replace('{1}', $difference, $periodsPlural[$j]);
   return $text;
  }
  
  private static function report_filter_panel($args, $readAuth) {
    $options = array(
      'allowSave' => true,
      'sharing' => 'verification',
      'linkToMapDiv'=>'map',
      'filter-quality'=>'P'
    );
    if (!function_exists('iform_ajaxproxy_url'))
      return 'The AJAX Proxy module must be enabled to support saving filters.';
    if (!function_exists('hostsite_get_user_field') || !hostsite_get_user_field('indicia_user_id'))
      return 'The verification_4 form requires Easy Login.';
    $r = report_filter_panel($readAuth, $options, $args['website_id'], $hiddenStuff);
    return $r . $hiddenStuff;
  }

}