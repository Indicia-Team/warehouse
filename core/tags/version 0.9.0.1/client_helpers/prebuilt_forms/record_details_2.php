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
 * @package    Client
 * @subpackage PrebuiltForms
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Displays the details of a single record. Takes an occurrence_id in the URL and displays the following using a configurable
 * page template:
 * Record Details including custom attributes
 * A map including geometry
 * Any photos associated with the occurrence
 * Any comments associated with the occurrence including the ability to add comments
 * @package    Client
 * @subpackage PrebuiltForms
 */


require_once('includes/dynamic.php');
require_once('includes/report.php');


class iform_record_details_2 extends iform_dynamic {

  private static $sampleLoaded = false;
  protected static $record;
    
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_record_details_2_definition() {
    return array(
      'title'=>'View details of a record 2',
      'category' => 'Utilities',
      'description'=>'A summary view of a record with commenting capability. Pass a parameter in the URL called occurrence_id to '.
        'define which occurrence to show.'
    );
  }
  

  /** 
   * Return an array of parameters for the edit tab. 
   * @return array The parameters for the form.
   */
  public static function get_parameters() {   
    $retVal = array_merge(
      iform_map_get_map_parameters(),
      array(
        array(
          'name'=>'interface',
          'caption'=>'Interface Style Option',
          'description'=>'Choose the style of user interface, either dividing the form up onto separate tabs, '.
            'wizard pages or having all controls on a single page.',
          'type'=>'select',
          'options' => array(
            'tabs' => 'Tabs',
            'wizard' => 'Wizard',
            'one_page' => 'All One Page'
          ),
          'default' => 'one_page',
          'group' => 'User Interface'
        ),
        //List of fields to hide in the Record Details section
        array(
          'name' => 'fields',
          'caption' => 'Fields to include or exclude',
          'description' => 'List of data fields to hide, one per line. '.
              'Type in the field name as seen exactly in the Record Details section. For custom attributes you should use the system function values '.
              'to filter instead of the caption if defined below.',
          'type' => 'textarea',
          'default' => 
'CMS Username
CMS User ID
Email
Sample ID
Record ID',
          'group' => 'Fields for record details'
        ),
        array(
          'name'=>'operator',
          'caption'=>'Include or exclude',
          'description'=>"Do you want to include only the list of fields you've defined, or exclude them?",
          'type'=>'select',
          'options' => array(
            'in' => 'Include',
            'not in' => 'Exclude'
          ),
          'default' => 'not in',
          'group' => 'Fields for record details'
        ),
        array(
          'name'=>'testagainst',
          'caption'=>'Test attributes against',
          'description'=>'For custom attributes, do you want to filter the list to show using the caption or the system function? If the latter, then '.
              'any custom attributes referred to in the fields list above should be referred to by their system function which might be one of: email, '.
              'cms_user_id, cms_username, first_name, last_name, full_name, biotope, sex_stage, sex, stage, sex_stage_count, certainty, det_first_name, det_last_name.',
          'type'=>'select',
          'options' => array(
            'caption'=>'Caption',
            'system_function'=>'System Function'
          ),
          'default' => 'caption',
          'group' => 'Fields for record details'
        ),
        //Allows the user to define how the page will be displayed.
        array(
        'name'=>'structure',
          'caption'=>'Form Structure',
          'description'=>'Define the structure of the form. Each component must be placed on a new line. <br/>'.
            "The following types of component can be specified. <br/>".
            "<strong>[control name]</strong> indicates a predefined control is to be added to the form with the following predefined controls available: <br/>".
                "&nbsp;&nbsp;<strong>[recorddetails]</strong> - displays information relating to the occurrence and its sample<br/>".
                "&nbsp;&nbsp;<strong>[buttons]</strong> - outputs a row of edit and explore buttons. Use the @buttons option to change the list of buttons to output ".
                "by setting this to an array, e.g. [edit] will output just the edit button, [explore] outputs just the explore button, [species details] outputs a species details button. ".
                "The edit button is automatically skipped if the user does not have rights to edit the record.<br/>".
                "&nbsp;&nbsp;<strong>[comments]</strong> - lists any comments associated with the occurrence. Also includes the ability to add a comment<br/>".
                "&nbsp;&nbsp;<strong>[photos]</strong> - photos associated with the occurrence<br/>".
                "&nbsp;&nbsp;<strong>[map]</strong> - a map that links to the spatial reference and location<br/>".
                "&nbsp;&nbsp;<strong>[previous determinations]</strong> - a list of previous determinations for this record<br/>".
            "<strong>=tab/page name=</strong> is used to specify the name of a tab or wizard page (alpha-numeric characters only). ".
            "If the page interface type is set to one page, then each tab/page name is displayed as a seperate section on the page. ".
            "Note that in one page mode, the tab/page names are not displayed on the screen.<br/>".
            "<strong>|</strong> is used to split a tab/page/section into two columns, place a [control name] on the previous line and following line to split.<br/>",
          'type'=>'textarea',
          'default' => 
'=Record Details and Comments=
[recorddetails]
[buttons]
|
[previous determinations]
[comments]
=Map and Photos=
[map]
|
[photos]',
          'group' => 'User Interface'
        ),
        array(
          'name' => 'default_input_form',
          'caption' => 'Default input form path',
          'description' => 'Default path to use to the edit form for old records which did not have their input form recorded in the database. Specify the '.
              'path to a general purpose list entry form.',
          'type' => 'text_input',
          'group' => 'Path configuration'
        ),
        array(
          'name'=>'explore_url',
          'caption'=>'Explore URL',
          'description'=>'When you click on the Explore this species\' records button you are taken to this URL. Use {rootfolder} as a replacement '.
              'token for the site\'s root URL.',
          'type' => 'string',
          'required'=>false,
          'default' => '',
          'group' => 'Path configuration'
        ),
        array(
          'name'=>'species_details_url',
          'caption'=>'Species details URL',
          'description'=>'When you click on the ... species page button you are taken to this URL with taxon_meaning_id as a parameter. Use {rootfolder} as a replacement '.
              'token for the site\'s root URL.',
          'type' => 'string',
          'required'=>false,
          'default' => '',
          'group' => 'Path configuration'
        ),
        array(
          'name'=>'explore_param_name',
          'caption'=>'Explore Parameter Name',
          'description'=>'Name of the parameter added to the Explore URL to pass through the taxon_meaning_id of the species being explored. '.
            'The default provided (filter-taxon_meaning_list) is correct if your report uses the standard parameters configuration.',
          'type' => 'string',
          'required'=>false,
          'default' => '',
          'group' => 'Path configuration'
        ),
      )
    );
    return $retVal;
  }
 
  /**
   * Override the get_form_html function.
   * getForm in dynamic.php will now call this.
   * Vary the display of the page based on the interface type
   * 
   * @package    Client
   * @subpackage PrebuiltForms
   */ 
  protected static function get_form_html($args, $auth, $attributes) {
    if (empty($_GET['occurrence_id'])) {
      return 'This form requires an occurrence_id parameter in the URL.';
    } else {
      data_entry_helper::$javascript .= 'indiciaData.username = "'.hostsite_get_user_field('name')."\";\n";
      data_entry_helper::$javascript .= 'indiciaData.user_id = "'.hostsite_get_user_field('indicia_user_id')."\";\n";
      data_entry_helper::$javascript .= 'indiciaData.website_id = '.$args['website_id'].";\n";
      data_entry_helper::$javascript .= 'indiciaData.ajaxFormPostUrl="'.iform_ajaxproxy_url(null, 'occurrence')."&sharing=reporting\";\n";
      return parent::get_form_html($args, $auth, $attributes);
    }
  }

  
  /**
   * Draw Record Details section of the page.
   * @return string The output freeform report.
   * 
   * @package    Client
   * @subpackage PrebuiltForms
   */
  protected static function get_control_recorddetails($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('report_helper'));
    $fields=helper_base::explode_lines($args['fields']);
    $fieldsLower=helper_base::explode_lines(strtolower($args['fields']));
    //Draw the Record Details, but only if they aren't requested as hidden by the administrator
    $detailstemplateHtml = '';
    $attrsTemplate='<div class="field ui-helper-clearfix"><span>{caption}:</span><span>{value}</span></div>';
    $test=$args['operator']==='in';
    $availableFields = array(
      'occurrence_id'=>'Record ID',
      'taxon'=>'Species',
      'preferred_taxon'=>'Preferred species name',
      'survey_title'=>'Survey',
      'recorder'=>'Recorder',
      'inputter'=>'Input by',
      'record_status'=>'Record status',
      'verifier'=>'Verified by',
      'date'=>'Date',
      'entered_sref'=>'Grid ref',
      'occurrence_comment'=>'Record comment',
      'location_name'=>'Site name',
      'sample_comment'=>'Sample comment',
    );
    
    self::load_record($auth);
    
    $details_report = '<div class="record-details-fields ui-helper-clearfix">';
    foreach($availableFields as $field=>$caption) {
      if ($test===in_array(strtolower($caption), $fieldsLower) && !empty(self::$record[$field]))
        $details_report .= str_replace(array('{caption}','{value}'), array($caption, self::$record[$field]), $attrsTemplate);      
    }
    $details_report .= '</div>';
    
    if (!self::$record['sensitivity_precision']) {
      //draw any custom attributes added by the user, but only for a non-sensitive record
      $attrs_report = report_helper::freeform_report(array(
        'readAuth' => $auth['read'],
        'class'=>'record-details-fields ui-helper-clearfix',
        'dataSource'=>'reports_for_prebuilt_forms/record_details_2/record_data_attributes_with_hiddens',
        'bands'=>array(array('content'=>$attrsTemplate)),
        'extraParams'=>array(
          'occurrence_id'=>$_GET['occurrence_id'],
          //the SQL needs to take a set of the hidden fields, so this needs to be converted from an array.
          'attrs'=>strtolower(self::convert_array_to_set($fields)),
          'testagainst'=>$args['testagainst'],
          'operator'=>$args['operator'],
          'sharing'=>'reporting'
        )
      ));
    }

    $r = '<h3>Record Details</h3>';
    
    if (isset($details_report))
      $r .= $details_report;
    if (isset($attrs_report))
      $r .= $attrs_report;
  
    return $r;
  }
 
  /**
   * Used to convert an array of attributes to a string formatted like a set,
   * this is then used by the record_data_attributes_with_hiddens report to return
   * custom attributes which aren't in the hidden attributes list.
   * @return string The set of hidden custom attributes.
   * 
   * @package    Client
   * @subpackage PrebuiltForms
   */
  protected static function convert_array_to_set($theArray) {
    return "'".implode("','", str_replace("'", "''", $theArray))."'";
  }
  

  /**
   * Draw Photes section of the page.
   * @return string The output report grid.
   * 
   * @package    Client
   * @subpackage PrebuiltForms
   */
  protected static function get_control_photos($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('report_helper'));
    data_entry_helper::add_resource('fancybox');
    //default an items per page if not set by administrator
    if (empty($options['itemsPerPage'])) {
      $options['itemsPerPage'] = 12;
    }  
    //default a column count if not set by administrator
    if (empty($options['galleryColCount'])) {
      $options['galleryColCount'] = 3;
    }  

    return '<h3>Photos</h3>'.report_helper::report_grid(array(
      'readAuth' => $auth['read'],
      'dataSource'=>'occurrence_image',
      'itemsPerPage' => $options['itemsPerPage'],
      'columns' => array(
        array(
          'fieldname' => 'path',
          'template' => '<div class="gallery-item"><a class="fancybox" href="{imageFolder}{path}"><img src="{imageFolder}thumb-{path}" title="{caption}" alt="{caption}"/><br/>{caption}</a></div>'
        )
      ),
      //mode direct means the datasource is a table instead of a report
      'mode' => 'direct',
      'autoParamsForm' => false,
      'includeAllColumns' => false,
      'headers' => false,
      'galleryColCount' => $options['galleryColCount'],
      'extraParams' => array(
        'occurrence_id'=>$_GET['occurrence_id'],
        'sharing'=>'reporting'
      ),
      'ajax' => true
    ));
  }
  
  
  /**
   * Draw Map section of the page.
   * @return string The output map panel.
   * 
   * @package    Client
   * @subpackage PrebuiltForms
   */
  protected static function get_control_map($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('data_entry_helper'));
    self::load_record($auth);
    $options = array_merge(
      iform_map_get_map_options($args, $auth['read']),
      $options
    );
    if (isset(self::$record['geom'])) {
      $options['initialFeatureWkt'] = self::$record['geom'];
    }
    
    if ($args['interface']!=='one_page')
      $options['tabDiv'] = $tabalias;
    
    $olOptions = iform_map_get_ol_options($args);
    
    if (!isset($options['standardControls']))
      $options['standardControls']=array('layerSwitcher','panZoom');
    return '<h3>Map</h3>'.data_entry_helper::map_panel($options, $olOptions);
    
  }
 
  
  /**
   * Draw the Comments section of the page.
   * @return string The output HTML string.
   * 
   * @package    Client
   * @subpackage PrebuiltForms
   */
  protected static function get_control_comments($auth, $args) { 
    iform_load_helpers(array('data_entry_helper'));
    $r = '<div>'; 
    $comments = data_entry_helper::get_population_data(array(
      'table' => 'occurrence_comment',
      'extraParams' => $auth['read'] + array('occurrence_id'=>$_GET['occurrence_id'], 'sortdir'=>'DESC', 'orderby'=>'updated_on'),
      'nocache'=>true
    ));
    
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
    
    global $user;
    $r .= '<form><fieldset><legend>'.lang::get('Add new comment').'</legend>';
    $r .= '<input type="hidden" id="comment-by" value="'.$user->name.'"/>';
    $r .= '<textarea id="comment-text"></textarea><br/>';
    $r .= '<button type="button" class="default-button" onclick="saveComment(';
    $r .= $_GET['occurrence_id'].');">'.lang::get('Save').'</button>';
    $r .= '</fieldset></form>';
    $r .= '</div>';
    
    return '<h3>Comments</h3>'.$r;
  }
  
  /**
   * Displays a list of determinations associated with an occurrence record.
   * 
   * @return string The determinations report grid.
   */
  protected static function get_control_previousdeterminations($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('report_helper'));
    $options = array_merge(array(
      'report'=>'library/determinations/determinations_list'
    ));
    return report_helper::freeform_report(array(
        'readAuth' => $auth['read'],
        'dataSource'=>$options['report'],
        'mode' => 'report',
        'autoParamsForm' => false,
        'header' => '<h3>Previous Determinations</h3>',
        'bands'=>array(array('content'=>'<div class="field ui-helper-clearfix">{taxon_html} by {person_name} on {date}</div>')),
        'extraParams' => array(
          'occurrence_id'=>$_GET['occurrence_id'],
          'sharing'=>'reporting'
        ),
    ));
  }
  
  /**
   * An edit button control which only displays if the user owns the record.
   * @param array $auth Read authorisation array
   * @param array $args Form options
   * @param string $tabalias The alias of the tab this appears on
   * @param array $options Options configured for this control. 
   * @return string HTML for the buttons.
   */
  protected static function get_control_buttons($auth, $args, $tabalias, $options) {
    $options = array_merge(array(
      'buttons'=>array('edit','explore','species details')
    ));
    $r = '';
    foreach ($options['buttons'] as $button) {
      if ($button==='edit')
        $r .= self::buttons_edit($auth, $args, $tabalias, $options);
      elseif ($button==='explore')
        $r .= self::buttons_explore($auth, $args, $tabalias, $options);
      elseif ($button==='species details')
        $r .= self::buttons_species_details($auth, $args, $tabalias, $options);
      else
        throw new exception("Unknown button $button");
    }
    return $r;
  }
  
  /**
   * Retrieve the HTML required for an edit record button.
   * @param array $auth Read authorisation array
   * @param array $args Form options
   * @param string $tabalias The alias of the tab this appears on
   * @param array $options Options configured for this control. 
   * @return string HTML for the button.
   */
  protected static function buttons_edit($auth, $args, $tabalias, $options) {
    if (!$args['default_input_form'])
      throw new exception('Please set the default input form path setting before using the [edit button] control');
    self::load_record($auth);
    $record = self::$record;
    if (($user_id=hostsite_get_user_field('indicia_user_id')) && $user_id==self::$record['created_by_id']
        && variable_get('indicia_website_id', 0)==self::$record['website_id']) {
      $pathParam = (function_exists('variable_get') && variable_get('clean_url', 0)=='0') ? 'q' : '';
      if (empty($record['input_form']))
        $record['input_form']=$args['default_input_form'];
      $url = data_entry_helper::getRootFolder() . (empty($pathParam) ? '' : "?$pathParam=") .
          "$record[input_form]&occurrence_id=$record[occurrence_id]";
      return '<a class="button" href="'.$url.'">' . lang::get('Edit this record') . '</a>';
    }
    else 
      // no rights to edit, so button omitted.
      return '';
  }
  
  /**
   * Retrieve the HTML required for an explore records of the same species button.
   * @param array $auth Read authorisation array
   * @param array $args Form options
   * @param string $tabalias The alias of the tab this appears on
   * @param array $options Options configured for this control. 
   * @return string HTML for the button.
   */
  protected static function buttons_explore($auth, $args, $tabalias, $options) {
    if (!empty($args['explore_url']) && !empty($args['explore_param_name'])) {
      $url = $args['explore_url'];
      if (strcasecmp(substr($url, 0, 12), '{rootfolder}')!==0 && strcasecmp(substr($url, 0, 4), 'http')!==0)
          $url='{rootFolder}'.$url;
      $pathParam = (function_exists('variable_get') && variable_get('clean_url', 0)=='0') ? 'q' : '';
      $rootFolder = data_entry_helper::getRootFolder() . (empty($pathParam) ? '' : "?$pathParam=");
      $url = str_replace('{rootFolder}', $rootFolder, $url);
      $url.= (strpos($url, '?')===false) ? '?' : '&';
      $url .= $args['explore_param_name'] . '=' . self::$record['taxon_meaning_id'];
      $taxon = empty(self::$record['preferred_taxon']) ? self::$record['taxon'] : self::$record['preferred_taxon'];
      $r='<a class="button" href="'.$url.'">' . lang::get('Explore records of {1}', $taxon) . '</a>';
    }
    else 
      throw new exception('The page has been setup to use an explore records button, but an "Explore URL" or "Explore Parameter Name" has not been specified.');
    return $r;
  }
  
  /**
   * Retrieve the HTML required for an details of the same species button.
   * @param array $auth Read authorisation array
   * @param array $args Form options
   * @param string $tabalias The alias of the tab this appears on
   * @param array $options Options configured for this control. 
   * @return string HTML for the button.
   */
  protected static function buttons_species_details($auth, $args, $tabalias, $options) {
    if (!empty($args['species_details_url'])) {
      $url = $args['species_details_url'];
      if (strcasecmp(substr($url, 0, 12), '{rootfolder}')!==0 && strcasecmp(substr($url, 0, 4), 'http')!==0)
          $url='{rootFolder}'.$url;
      $pathParam = (function_exists('variable_get') && variable_get('clean_url', 0)=='0') ? 'q' : '';
      $rootFolder = data_entry_helper::getRootFolder() . (empty($pathParam) ? '' : "?$pathParam=");
      $url = str_replace('{rootFolder}', $rootFolder, $url);
      $url.= (strpos($url, '?')===false) ? '?' : '&';
      $url .= 'taxon_meaning_id=' . self::$record['taxon_meaning_id'];
      $taxon = empty(self::$record['preferred_taxon']) ? self::$record['taxon'] : self::$record['preferred_taxon'];
      return '<a class="button" href="'.$url.'">' . lang::get('{1} details page', $taxon) . '</a>';
    }
    return '';
  }
  
  /*
   * Convert a timestamp into readable format (... ago) for use on a comment list.
   * @param timestamp $timestamp The date time to convert.
   * @return string The output string.
   */
  protected static function ago($timestamp) {
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
  
  
  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the 
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
 
  protected static function getArgDefaults($args) {
    if (!isset($args['interface']) || empty($args['interface']))
      $args['interface'] = 'one_page';
    
    if (!isset($args['hide_fields']) || empty($args['hide_fields']))
      $args['hide_fields'] = 
'CMS Username
Email
Sample ID
Record ID';
    
    if (!isset($args['structure']) || empty($args['structure'])) {
      $args['structure'] = 
'=Record Details and Comments=
[recorddetails]
|
[comments]
=Map and Photos=
[map]
|
[photos]';
    }
    return $args;      
  } 
  
  /**
   * Disable save buttons for this form class. Not a data entry form...
   * @return boolean 
   */
  protected static function include_save_buttons() {
    return FALSE;  
  }
  
  /**
   * Override the standard header as this is not an HTML form.
   */
  protected static function getHeader($args) {
    return '';
  }
  
  /**
   * Override the standard footer as this is not an HTML form.
   */
  protected static function getFooter($args) {
    return '';
  }
  
  /**
   * Loads the record associated with the page if not already loaded.
   */
  protected static function load_record($auth) {
    if (!isset(self::$record)) {
      $records=report_helper::get_report_data(array(
        'readAuth' => $auth['read'],
        'dataSource'=>'reports_for_prebuilt_forms/record_details_2/record_data',
        'extraParams'=>array('occurrence_id'=>$_GET['occurrence_id'], 'sharing'=>'reporting')
      ));
      self::$record = $records[0];
    }
  }
  
  /**
   * Override some default behaviour in dynamic.
   */
  protected static function getFirstTabAdditionalContent($args, $auth, &$attributes) {
    return '';
  }
   
   
}
?>
