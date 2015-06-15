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
 * Extension class that supplies general extension controls that can be used with any project.
 */
class extension_ecmc_data_portal {
  
  /**
   * 
   */
  public static function survey_points_report_params($auth, $args, $tabalias, $options, $path) {
    if (!function_exists('iform_ajaxproxy_url'))
      return 'An AJAX Proxy module must be enabled for the survey_points_report_params control to work.';
    if (!$userId = hostsite_get_user_field('indicia_user_id'))
      return 'The user account must be connected via Easy Login for the survey_points_report_params control to work';
    // map the ID parameter provided after saving a crossing sample to the report input parameter to filter the list
    if (!empty($_GET['id']) && empty($_GET['dynamic-parent_sample_id']))
      $_GET['dynamic-parent_sample_id']=$_GET['id'];
    if (empty($_GET['dynamic-parent_sample_id']))
      return 'Survey identifier not provided - cannot load the survey';
    $requiredOptions = array('transectIdAttrId', 'sampleTypeAttrId', 'sampleTypeTermlistId', 'transectSampleMethodId');
    foreach ($requiredOptions as $option)
      if (empty($options[$option]))
        return "Please provide a value for the @$option option.";
    $surveySampleId=$_GET['dynamic-parent_sample_id'];
    $samples = data_entry_helper::get_population_data(array(
      'table'=>'sample',
      'extraParams'=>$auth['read'] + array('id' => $surveySampleId)
    ));
    if (count($samples)!==1)
        return 'No unique survey found for the provided ID';
    $sample = $samples[0];
    $sampleMethods = data_entry_helper::get_population_data(array(
      'table'=>'termlists_term',
      'extraParams'=>$auth['read'] + array('term'=>'Transect', 'termlist_title'=>'Sample methods', 'view'=>'cache')
    ));
    if (count($sampleMethods)!==1)
        return 'No sample method term found for Transect';
    $transectMethodId = $sampleMethods[0]['id'];
    $date = strtotime($sample['date_start']);
    hostsite_set_page_title(lang::get('Effort and sightings points for {1} on {2}', $sample['location'], date('d/m/Y', $date)));
    $r = '<div id="points-params">';
    $r .= data_entry_helper::radio_group(array(
      'fieldname'=>'point-type-param',
      'lookupValues' => array(
        'E' => lang::get('Effort only'),
        'S' => lang::get('Sightings only'),
        'ES' => lang::get('Both effort and sightings')
      ),
      'default'=>'ES'
    ));
    $r .= '<fieldset>';
    $r .= data_entry_helper::select(array(
        'fieldname'=>'transect-param',
        'label' => lang::get('Transect'),
        'report'=>'reports_for_prebuilt_forms/marinelife/transects_list',
        'valueField'=>'id',
        'captionField'=>'caption',
        'extraParams'=>$auth['read'] + array('parent_sample_id'=>$surveySampleId, 
            'sample_type_attr_id'=>$options['sampleTypeAttrId'], 'transect_id_attr_id'=>$options['transectIdAttrId']),
        'caching'=>false
    ));
    $r .= '<a href="#" id="new-transect" class="indicia-button">New transect</a>';
    $r .= '<a href="'.hostsite_get_url('survey/points/edit-effort', array('transect_sample_id'=>0, 'parent_sample_id'=>$surveySampleId)).
        '" id="edit-effort" class="indicia-button edit-point">Add effort waypoint(s)</a>';
    $r .= '<a href="'.hostsite_get_url('survey/points/edit-sighting', array('transect_sample_id'=>0, 'parent_sample_id'=>$surveySampleId)).
        '" id="edit-sighting" class="indicia-button edit-point">Add sighting(s)</a>';
    $r .= '</fieldset>';
    $r .= '<a href="casual/edit">Add casual sighting(s)</a>';
    $r .= "</div>\n";
    // popup template for adding a transect
    $r .= '<div style="display: none">';
    $r .= '<div id="add-transect-popup">';
    $r .= '<h3>'.lang::get('New transect').'</h3>';
    $r .= data_entry_helper::text_input(array(
      'fieldname'=>'popup-transect-id',
      'label'=>lang::get('Transect ID')
    ));
    $r .= data_entry_helper::select(array(
      'fieldname'=>'popup-sample-type',
      'label'=>lang::get('Sample type'),
      'table'=>'termlists_term',
      'valueField'=>'id',
      'captionField'=>'term',
      'blankText'=>lang::get('<Please select>'),
      'extraParams'=>$auth['read']+array('view'=>'cache', 'termlist_id'=>$options['sampleTypeTermlistId'], 'orderby'=>'sort_order'),
      'validation'=>array('required')
    ));
    $r .= data_entry_helper::date_picker(array(
      'fieldname'=>'popup-transect-date',
      'label'=>lang::get('Date'),
      'helpText'=>lang::get('Please alter the date if the survey spans several days'),
      'default'=> $sample['date_start']
    ));
    $r .= '<button id="transect-popup-save">Save</button> ';
    $r .= '<button id="transect-popup-cancel">Cancel</button>';
    $r .= '</div>';
    $r .= '</div>';
    $url = iform_ajaxproxy_url(null, 'sample');
    data_entry_helper::$javascript .= "indiciaData.surveySampleId=$surveySampleId;\n";
    data_entry_helper::$javascript .= "indiciaData.websiteId=$args[website_id];\n";
    data_entry_helper::$javascript .= "indiciaData.userId='$userId';\n";
    data_entry_helper::$javascript .= "indiciaData.surveyId='$sample[survey_id]';\n"; !!
    data_entry_helper::$javascript .= "indiciaData.sampleDate='$sample[date_start]';\n";
    data_entry_helper::$javascript .= "indiciaData.sampleSref='$sample[entered_sref]';\n";
    data_entry_helper::$javascript .= "indiciaData.transectMethodId=$transectMethodId;\n";
    data_entry_helper::$javascript .= "indiciaData.saveSampleUrl='$url';\n";
    data_entry_helper::$javascript .= "indiciaData.transectIdAttrId=$options[transectIdAttrId];\n";
    data_entry_helper::$javascript .= "indiciaData.sampleTypeAttrId=$options[sampleTypeAttrId];\n";
    data_entry_helper::$javascript .= "indiciaData.transectSampleMethodId=$options[transectSampleMethodId];\n";
    data_entry_helper::$javascript .= "indiciaData.langRequireTransectID='".lang::get('Please supply the Transect ID.')."';\n";
    data_entry_helper::$javascript .= "indiciaData.langRequireSampleType='".lang::get('Please supply the Sample Type.')."';\n";
    return $r;
  }
  
  public static function surveys_list_extras($auth, $args, $tabalias, $options, $path) {
    $rows = data_entry_helper::get_population_data(array(
      'table'=>'termlists_term',
      'extraParams'=>$auth['read'] + array('term'=>'Cetacean survey route', 'termlist_title'=>'Location types', 'view'=>'cache')
    ));
    $surveyLocationTypeId=$rows[0]['id'];
    return data_entry_helper::hidden_text(array(
      'fieldname'=>'location:location_type_id',
      'default'=>$surveyLocationTypeId
    ));
  }
  
  public static function routes_list_extras($auth, $args, $tabalias, $options, $path) {
    $url = iform_ajaxproxy_url(null, 'location');
    data_entry_helper::$javascript .= "indiciaData.postUrl='$url';\n";
    data_entry_helper::$javascript .= "indiciaData.websiteId=$args[website_id];\n";
    data_entry_helper::$javascript .= "indiciaData.langConfirmDelete='".lang::get('Are you sure that you want to delete {1}? This change cannot be undone.')."';\n";
  }
  
  public static function effort_point_extras($auth, $args, $tabalias, $options, $path) {
    if (empty($_GET['transect_sample_id']))
      return 'Transect sample ID identifier not provided - cannot load the transect';
    $transectSampleId=$_GET['transect_sample_id'];
    $transect = data_entry_helper::get_population_data(array(
      'table'=>'sample',
      'extraParams'=>$auth['read'] + array('id'=>$transectSampleId),
      'caching'=>false
    ));
    if (count($transect)!==1)
      return lang::get('Incorrect transect provided in URL');
    $transect = $transect[0];
    $r = data_entry_helper::hidden_text(array(
      'fieldname'=>'sample:date',
      'default'=>$transect['date_start']
    ));
    $r .= data_entry_helper::hidden_text(array(
      'fieldname'=>'sample:parent_id',
      'default'=>$transectSampleId
    ));
    $r .= data_entry_helper::hidden_text(array(
      'fieldname'=>'parent_sample_id',
      'default'=>$_GET['parent_sample_id']
    ));
    return $r;
  }
  
  /**
   * Output a lat long field split into 2 fields.
   * @param type $auth
   * @param type $args
   * @param type $tabalias
   * @param type $options
   * @param type $path
   */
  public static function lat_long($auth, $args, $tabalias, $options, $path) {
    $r = '';
    $r .= data_entry_helper::hidden_text(array(
      'fieldname'=>'sample:entered_sref_system',
      'default'=>4326
    ));
    $r .= data_entry_helper::hidden_text(array(
      'fieldname'=>'sample:entered_sref'
    ));
    $r .= data_entry_helper::text_input(array(
      'fieldname'=>'lat_long-lat',
      'label'=>lang::get('Position'),
      'afterControl'=>'Latitude'
    ));
    // a null label ensures correct padding for the 2nd control.
    $r .= '<label> </label> ';
    $r .= data_entry_helper::text_input(array(
      'fieldname'=>'lat_long-long',
      'afterControl'=>'Longitude'
    ));
    return $r;
  }
  
  public static function autoset_record_id_and_record_type($auth, $args, $tabalias, $options, $path) {
    $mode = empty($options['mode']) ? false : $options['mode'];
    // skip this functionality for existing data
    if (empty($_GET['sample_id']) && !empty($_GET['transect_sample_id'])) {
      $recordIdAttrId = $options['recordIdAttrId'];
      if (!empty($_SESSION['last_transect']) && !empty($_SESSION['last_record_id']) && $_SESSION['last_transect']===$_GET['transect_sample_id']) {
        // session holds the last record ID we created
        $recordId = $_SESSION['last_record_id']+1;
      } else {
        // not available in session, so we need to fetch the max existing record ID for this transect from the db
        $_SESSION['last_transect']=$_GET['transect_sample_id'];
        iform_load_helpers(array('report_helper'));
        $report = report_helper::get_report_data(array(
          'dataSource' => 'reports_for_prebuilt_forms/marinelife/max_occattr_val_for_parent_sample',
          'readAuth' => $auth['read'],
          'extraParams' => array('parent_sample_id'=>$_GET['transect_sample_id'], 'attr_id'=>$options['recordIdAttrId'])
        ));
        if (count($report)===0 || empty($report[0]['maxval'])) {
          $recordId=1;
          if ($mode==='effort') {
            $mode = 'start';
          }
        } else {
          $recordId = $report[0]['maxval'] + 1;
        }
      }
      if ($mode && !empty($options['recordTypeAttrId'])) {
        // set the record type control to hold the $mode
        data_entry_helper::$javascript .= "$.each($('#smpAttr\\\\:$options[recordTypeAttrId] option'), function() {
  if ($(this).html().toLowerCase()==='$mode'.toLowerCase()) {
    $(this).attr('selected', 'selected');
  }  
});\n";
      }
      $_SESSION['last_record_id'] = $recordId;
      data_entry_helper::$javascript .= "$('#smpAttr\\\\:$recordIdAttrId').val('$recordId');\n";
      // default the selector for what happens after save
      if ($mode==='sighting')
        data_entry_helper::$javascript .= "$('#next-action').val('sighting');\n";
    }
  }
  
  public static function clone_effort_button($auth, $args, $tabalias, $options, $path) {
    $r = '<button type="button" id="clone-effort" disabled="disabled">Copy last effort</button>';
    $resportingServerURL = (!empty(data_entry_helper::$warehouse_proxy))?data_entry_helper::$warehouse_proxy:data_entry_helper::$base_url.
        'index.php/services/report/requestReport?report=reports_for_prebuilt_forms/marinelife/get_last_effort.xml&'.
        'exclusions='.$options['exclusions'].'&callback=?';
    $nonce = $auth['read']['nonce'];
    $auth_token = $auth['read']['auth_token'];
    data_entry_helper::$javascript .= "
$('#smpAttr\\\\:$options[timeAttrId]').keyup(function(e) {
  if ($(e.currentTarget).val().match(/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/)) {
    $('#clone-effort').removeAttr('disabled');
  } else {
    $('#clone-effort').attr('disabled', 'disabled');
  }
});
$('#clone-effort').click(function() {
  if ($('#smpAttr\\\\:$options[timeAttrId]').val().match(/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/)) {
    var reportingURL = '$resportingServerURL';
    var reportOptions={
      'mode': 'json',
      'nonce': '$nonce',
      'auth_token': '$auth_token',
      'reportSource': 'local',
      'parent_sample_id': $_GET[transect_sample_id],
      'time': $('#smpAttr\\\\:$options[timeAttrId]').val()
    }
    $.getJSON(reportingURL, reportOptions,
      function(data) {
        $.each(data, function() {
          // don't clone the start time, UTC time, or record type attributes
          
          $('#smpAttr\\\\:'+this.sample_attribute_id).val(this.val);
        });
      }
    );
  }
});
";
    return $r;
  }
  
  public static function points_editor($auth, $args, $tabalias, $options, $path) {
    if (!empty($_GET['dynamic-transect'])) {
      $tokens = explode(':', $_GET['dynamic-transect']);
      hostsite_set_page_title('Review points for transect '.$tokens[2].' of survey '.$tokens[1].' by group '.$tokens[0]);
    }
    hostsite_set_breadcrumb(array('Review transect lines'=>'data/review-transect-lines'));
    data_entry_helper::$javascript .= "mapInitialisationHooks.push(drawPoints);\n";
    data_entry_helper::$javascript .= "indiciaData.website_id=$args[website_id];\n";
    data_entry_helper::$javascript .= 'indiciaData.ajaxFormPostUrl="'.iform_ajaxproxy_url(null, 'sample')."\";\n";
    return '';
  }
  
}