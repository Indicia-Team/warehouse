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
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * A stub base class for filter definition code.
 */
class filter_base {

}

/**
 * Class defining a "what" filter - species selection.
 */
class filter_what extends filter_base {
  
  public function get_title() {
    return 'What';
  }
  
  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function get_controls($readAuth, $options) {
    $r = "<p id=\"what-filter-instruct\">".lang::get('You can either filter by species group (first tab) or a selection of individual species (second tab)')."</p>\n".
        '<div id="what-tabs">'."\n";
    // data_entry_helper::tab_header breaks inside fancybox. So output manually.
    $r .= '<ul class="ui-helper-hidden"><li id="species-group-tab-tab"><a href="#species-group-tab" rel="address:species-group-tab"><span>Species groups</span></a></li>' .
        '<li id="species-tab-tab"><a href="#species-tab" rel="address:species-tab"><span>Species and other taxa</span></a></li></ul>';
    $r .= '<div id="species-group-tab">' . "\n";
    $myGroupIds = hostsite_get_user_field('taxon_groups', '');
    if ($myGroupIds) {
      $r .= '<h3>' . lang::get('My groups') . '</h3>';
      $myGroupsData = data_entry_helper::get_population_data(array(
        'table' => 'taxon_group',
        'extraParams' => $readAuth + array('query'=>json_encode(array('in'=>array('id', unserialize($myGroupIds)))))
      ));
      $myGroupNames = array();
      data_entry_helper::$javascript .= "indiciaData.myGroups = [];\n";
      foreach($myGroupsData as $group) {
        $myGroupNames[] = $group['title'];
        data_entry_helper::$javascript .= "indiciaData.myGroups.push([{$group[id]},'{$group[title]}']);\n";
      }
      $r .= '<button type="button" id="my_groups">'.lang::get('Include my groups').'</button>';
      $r .= '<ul class="inline"><li>' . implode('</li><li>', $myGroupNames) . '</li></ul>';      
      $r .= '<h3>' . lang::get('Build a list of groups') . '</h3>';
    }
    $r .= '<p>' . lang::get('Search for and build a list of species groups to include.') . 
        ' <span class="context-instruct">' . lang::get('Please note that your access permissions are limiting the groups available to choose from.') . '</span></p>';
    $r .= data_entry_helper::sub_list(array(      
      'fieldname' => 'taxon_group_list',
      'table' => 'taxon_group',
      'captionField' => 'title',
      'valueField' => 'id',
      'extraParams' => $readAuth,
      'addToTable' => false
    ));
    $r .= "</div>\n";
    $r .= '<div id="species-tab">' . "\n";
    $r .= '<p>' . lang::get('Search for and build a list of species to include.') . 
        ' <span class="context-instruct">' . lang::get('Please note that your access permissions are limiting the species available to choose from.') . '</span></p>';
    if (empty($options['taxon_list_id'])) {
      $r .= '<p>Please specify a @taxon_list_id option in the page configuration.</p>';
    }
    $r .= data_entry_helper::sub_list(array(      
      'fieldname' => 'taxa_taxon_list_list',
      'table' => 'cache_taxa_taxon_list',
      'captionField' => 'taxon',
      'valueField' => 'id',
      'extraParams' => $readAuth + array('taxon_list_id' => $options['taxon_list_id'], 'preferred' => 't'),
      'addToTable' => false
    ));
    $r .= "</div>\n";
    $r .= "</div>\n";
    data_entry_helper::enable_tabs(array(
      'divId' => 'what-tabs'
    ));
    
    return $r;
  }
}

/**
 * Class defining a "when" filter - date selection.
 */
class filter_when extends filter_base {
  
  public function get_title() {
    return 'When';
  }
  
  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function get_controls($readAuth) {
    $r = '<fieldset class="exclusive"><legend>'.lang::get('Specify a date range for the records to include').'.</legend>';
    $r .= data_entry_helper::date_picker(array(
      'label'=>lang::get('Records from'),
      'fieldname'=>'date_from',
    ));
    $r .= data_entry_helper::date_picker(array(
      'label'=>lang::get('Records to'),
      'fieldname'=>'date_to',
    ));
    $r .= '</fieldset>';
    $r .= '<fieldset class="exclusive" id="age"><legend>'.lang::get('Or, specify a maximum age for the records to include').'.</legend>';
    $r .= data_entry_helper::text_input(array(
      'label'=>lang::get('Max. record age'),
      'helpText'=>'How old records can be before they are dropped from the report? Enter a number followed by the unit (days, weeks, months or years), e.g. "2 days" or "1 year".',
      'fieldname'=>'date_age',
      'validation' => array('regex[/^[0-9]+\s*(day|week|month|year)(s)?$/]')
    ));
    // additional helptext in case it is needed when a context is applied
    $r .= '<p class="helpText context-instruct" id="age-help">'.lang::get('Note that you are limited to records from the last <span></span> '.
        'and older records will always be excluded no matter what you set here.').'</p>';
    $r .= '</fieldset>';
    return $r;
  }  
}

/**
 * Class defining a "where" filter - geographic selection.
 */
class filter_where extends filter_base {
  
  public function get_title() {
    return 'Where';
  }
  
  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function get_controls($readAuth, $options) {
    $options = array_merge(array(
      'location_profile_field' => 'location'
    ), $options);
    iform_load_helpers(array('map_helper'));
    $location_list_args=array(
      'label'=>lang::get('Predefined site'),
      'fieldname'=>'location_id',
      'extraParams'=>array('orderby'=>'name', 'view'=>'detail')+ $readAuth,
      'fetchLocationAttributesIntoSample'=>false
    );
    $userId = hostsite_get_user_field('indicia_user_id');
    if (!empty($userId)) {
      if (!empty($options['personSiteAttrId'])) {
        $location_list_args['extraParams']['user_id']=$userId;
        $location_list_args['extraParams']['person_site_attr_id']=$options['personSiteAttrId'];
        $location_list_args['report'] = 'library/locations/my_sites_lookup';
      } else 
        $location_list_args['extraParams']['created_by_id']=$userId;
    }
    $r = '<fieldset class="inline"><legend>'.lang::get('Filter by site or place').'</legend>';
    $r .= '<p class="context-instruct">' . lang::get('Choose from the following place filtering options. Please note that your access permissions ' .
        'are limiting the areas you are able to include.') . '</p>';
    $r .= '<fieldset class="exclusive">';
    $r .= data_entry_helper::location_autocomplete($location_list_args);
    $r .= '</fieldset>';
    $r .= '<fieldset class="exclusive">';
    $r .= data_entry_helper::text_input(array(
      'label' => lang::get('Or, site name contains'),
      'fieldname' => 'location_name'
    ));
    $r .= '</fieldset>';
    $r .= '<fieldset class="exclusive">';
    // Build the array of spatial reference systems into a format Indicia can use.
    $systems=array();
    $systemsConfig = '4326'; // default
    if (!empty($options['sref_systems']))
      $systemsConfig = $options['sref_systems'];
    elseif (function_exists('variable_get'))
      $systemsConfig = variable_get('indicia_spatial_systems', '4326');
    $list = explode(',', str_replace(' ', '', $systemsConfig));
    foreach($list as $system) {
      $systems[$system] = lang::get("sref:$system");
    }
    $r .= data_entry_helper::sref_and_system(array(
      'label' => lang::get('Or, map reference is'),
      'fieldname' => 'sref',
      'systems' => $systems
    ));
    $r .= '</fieldset>';
    $locality = hostsite_get_user_field($options['location_profile_field']);
    if ($locality) {
      $loc = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $readAuth + array('id' => $locality)
      ));
      data_entry_helper::$javascript .= "indiciaData.myLocality='".$loc[0]['name']."';\n";
      $r .= '<fieldset class="exclusive">';
      $r .= '<label for="indexed_location_id">' . lang::get('Or, records in {1}:', $loc[0]['name']) . '</label> ' .
          '<input id="indexed_location_id" type="checkbox" value="' . $locality . '" name="indexed_location_id">';
      $r .= '</fieldset>';
    }
    $r .= '</fieldset>';
    $r .= '<fieldset><legend>'.lang::get('Or, draw a boundary to find records within').'</legend>';
    if (empty($options['linkToMapDiv'])) {
      // need our own map on the popup
      // The js wrapper around the map div does not help here, since it breaks fancybox and fancybox is js only anyway.
      global $indicia_templates;
      $oldwrap = $indicia_templates['jsWrap'];
      $indicia_templates['jsWrap'] = '{content}';
      $r .= map_helper::map_panel(array(
        'divId'=>'filter-pane-map',
        'presetLayers' => array('google_hybrid'),
        'editLayer' => true,      
        'initial_lat'=>variable_get('indicia_map_centroid_lat', 55),
        'initial_long'=>variable_get('indicia_map_centroid_long', -1),
        'initial_zoom'=>(int) variable_get('indicia_map_zoom', 5),
        'width'=>'100%',
        'height'=>400,
        'standardControls'=>array('layerSwitcher','panZoomBar','drawPolygon','drawLine','drawPoint','clearEditLayer')
      ));
      $indicia_templates['jsWrap'] = $oldwrap;
    } 
    else {
      // We are going to use an existing map for drawing boundaries etc. So prepare a container.
      $r .= '<div id="filter-map-container"></div>';
      data_entry_helper::$javascript .= "indiciaData.linkToMapDiv='".$options['linkToMapDiv']."';\n";
    }
    $r .= '</fieldset>';    
    return $r;
  }
}

/**
 * Class defining a "who" filter - recorder selection.
 */
class filter_who extends filter_base {
  
  public function get_title() {
    return 'Who';
  }
  
  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function get_controls($readAuth, $options) {
    $r = '<p class="context-instruct">' . lang::get('Please note, you cannnot change this setting because of your access permissions in this context.') . '</p>';
    $r .= data_entry_helper::checkbox(array(
      'label' => lang::get('Only include my records'),
      'fieldname' => 'my_records'
    ));
    return $r;  
  }
}

/**
 * Class defining a "quality" filter - record status, photos, verification rule check selection.
 */
class filter_quality extends filter_base {
  
  public function get_title() {
    return 'Quality';
  }
  
  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function get_controls($readAuth) {
    $r = '<p class="context-instruct">' . lang::get('Please note, your options for quality filtering are restricted by your access permissions in this context.') . '</p>';
    $r .= data_entry_helper::select(array(
      'label'=>lang::get('Records to include'),
      'fieldname'=>'quality',
      'id'=>'quality-filter',
      'lookupValues' => array(
        'V' => lang::get('Verified records only'),
        'C' => lang::get('Recorder was certain'),
        'L' => lang::get('Recorder thought the record was at least likely'),
        '!R' => lang::get('Exclude rejected'),
        'all' => lang::get('All records'),
        'D' => lang::get('Queried records only'),
        'R' => lang::get('Rejected records only')
      )
    ));
    $r .= data_entry_helper::select(array(
      'label'=>'Automated checks',
      'fieldname'=>'autochecks',
      'lookupValues'=>array(
        ''=>lang::get('Not filtered'),
        'P'=>lang::get('Only include records that pass all automated checks'),
        'F'=>lang::get('Only include records that fail at least one automated check')
      )
    ));
    $r .= data_entry_helper::checkbox(array(
      'label' => lang::get('Only include records which have photos available'),
      'fieldname' => 'has_photos'
    ));
    return $r;
  }
}

/**
 * Class defining a "source" filter - website, survey, input form selection.
 */
class filter_source extends filter_base {
  
  public function get_title() {
    return 'Source';
  }
  
  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function get_controls($readAuth, $options) {
    iform_load_helpers(array('report_helper'));    
    $sources = report_helper::get_report_data(array(
      'dataSource' => 'library/websites/websites_list',
      'readAuth' => $readAuth,
      'caching' => true,
      'extraParams' => array('sharing' => $options['sharing'])
    ));
    $r = '<p class="context-instruct">' . lang::get('Please note, or options for source filtering are limited by your access permissions in this context.') . '</p><div>';
    if (count($sources)>1) {
      $r .= '<div id="filter-websites" class="filter-popup-columns"><h3>'.lang::get('Websites').'</h3><p>'.
          '<select id="filter-websites-mode" name="website_list_op"><option value="in">'.lang::get('Include').'</option><option value="not in">'.lang::get('Exclude').'</option></select> '.
          lang::get('records from').':</p><ul id="website-list-checklist">';
      foreach ($sources as $source) {
        $r .= '<li><input type="checkbox" value="'.$source['id'].'" id="check-'.$source['id'].'"/>' .
            '<label for="check-'.$source['id'].'">'.$source['title'].'</label></li>';
      }
      $r .= '</ul></div>';
    }
    $sources = report_helper::get_report_data(array(
      'dataSource' => 'library/surveys/surveys_list',
      'readAuth' => $readAuth,
      'caching' => true,
      'extraParams' => array('sharing' => $options['sharing'])
    ));
    $r .= '<div id="filter-surveys" class="filter-popup-columns"><h3>'.lang::get('Survey datasets').'</h3><p>'.
          '<select id="filter-surveys-mode" name="survey_list_op"><option value="in">'.lang::get('Include').'</option><option value="not in">'.lang::get('Exclude').'</option></select> '.
          lang::get('records from').':</p><ul id="survey-list-checklist">';
    foreach ($sources as $source) {
      $r .= '<li class="vis-website-'.$source['website_id'].'">' .
          '<input type="checkbox" value="'.$source['id'].'" id="check-survey-'.$source['id'].'"/>' .
          '<label for="check-survey-'.$source['id'].'">'.$source['fulltitle'].'</label></li>';
    }
    $r .= '</ul></div>';
    $sources = report_helper::get_report_data(array(
      'dataSource' => 'library/input_forms/input_forms_list',
      'readAuth' => $readAuth,
      'caching' => true,
      'extraParams' => array('sharing' => $options['sharing'])
    ));
    $r .= '<div id="filter-input_forms" class="filter-popup-columns"><h3>'.lang::get('Input forms').'</h3><p>'.
          '<select id="filter-input_forms-mode" name="input_forms_list_op"><option value="in">'.lang::get('Include').'</option><option value="not in">'.lang::get('Exclude').'</option></select> '.
          lang::get('records from').':</p><ul id="input_form-list-checklist">';
    // create an object to contain a lookup from id to form for JS, since forms don't have a real id.
    $obj=array();
    foreach ($sources as $idx=>$source) {
      $r .= '<li class="vis-survey-'.$source['survey_id'].' vis-website-'.$source['website_id'].'">' .
          '<input type="checkbox" value="'.$source['id'].'" id="check-form-'.$idx.'"/>' .
          '<label for="check-form-'.$idx.'">'.$source['input_form'].'</label></li>';
      $obj[$source['input_form']]=$idx;
    }
    $r .= '</ul></div>';
    report_helper::$javascript .= 'indiciaData.formsList='.json_encode($obj).";\n";
    $r .= '</div><p>'.lang::get('Leave any list unticked to leave that list unfiltered.').'</p>';
    return $r;
  }
}

/**
 * Code to output a standardised report filtering panel. 
 *
 * Filters can be saved and loaded by each user. Additionally, filters can define permissions to a certain task, e.g. they can be used to define the 
 * context within which someone can verify. In this case they provide the "outer limit" of the available records. 
 * Requires a [map] control on the page. If you don't want a map, the current option is to include one anyway and use css to hide the #map-container div.
 *
 * @param array $readAuth Pass read authorisation tokens.
 * @param array $options Options array with the following possibilities:
 *   sharing - define the record sharing task that is being filtered against. Options are reporting (default), peer_review, verification, moderation, data_flow.
 *   context_id - can also be passed as URL parameter. Force the initial selection of a particular context (a record which has defines_permissions=true in the
 *   filters table. Set to "default" to select their profile verification settings when sharing=verification.
 *   filter_id - can also be passed as URL parameter. Force the initial selection of a particular filter record in the filters table. 
 *   filterTypes - allows control of the list of filter panels available, e.g. to turn one off. Associative array keyed by category
 *   so that the filter panels can be grouped (use a blank key if not required). The array values are strings with a comma separated list
 *   of the filter types to included in the category - options are what, where, when, who, quality, source.
 *   filter-#name# - set the initial value of a report filter parameter #name#. 
 *   allowLoad - set to false to disable the load bar at the top of the panel.
 *   allowSave - set to false to disable the save bar at the foot of the panel.
 * @param integer $website_id The current website's warehouse ID.
 * @param string $hiddenStuff Output parameter which will contain the hidden popup HTML that will be shown
 * using fancybox during filter editing. Should be appended AFTER any form element on the page as nested forms are not allowed.
 */
function report_filter_panel($readAuth, $options, $website_id, &$hiddenStuff) {
  iform_load_helpers(array('report_helper'));
  $options = array_merge(array(
    'sharing' => 'reporting',
    'allowLoad' => true,
    'allowSave' => true,
    'taxon_list_id' => variable_get('iform_master_checklist_id', 0)
  ), $options);
  if (!preg_match('/^(reporting|peer_review|verification|data_flow|moderation)$/', $options['sharing']))
    return 'The @sharing option must be one of reporting, peer_review, verification, data_flow or moderation (currently '.$options['sharing'].').';
  report_helper::add_resource('reportfilters');
  report_helper::add_resource('validation');
  report_helper::add_resource('fancybox');
  hostsite_add_library('collapse');
  $filterData = report_filters_load_existing($readAuth, strtoupper(substr($options['sharing'], 0, 1)));
  $existing = '';
  $contexts = '';
  $contextDefs = array();
  if ($options['sharing']==='verification') {
    // apply legacy verification settings from their profile
    $location_id = hostsite_get_user_field('location_expertise');
    $taxon_group_ids = hostsite_get_user_field('taxon_groups_expertise');
    if ($location_id || $taxon_group_ids) {
      $selected = (!empty($options['context_id']) && $options['context_id']==='default') ? 'selected="selected" ' : '';
      $contexts .= "<option value=\"default\" $selected>".lang::get('My verification records')."</option>";
      $def = array();
      if ($location_id)
        // user profile geographic limits should always be based on an indexed location.
        $def['indexed_location_id'] = $location_id;
      if ($taxon_group_ids) {
        $arr=unserialize($taxon_group_ids);
        $def['taxon_group_list']=implode(',', $arr);
        $def['taxon_group_names']=array();
        $groups = data_entry_helper::get_population_data(array(
          'table' => 'taxon_group',
          'extraParams' => $readAuth + array('id' => $arr)
        ));
        foreach ($groups as $group) {
          $def['taxon_group_names'][$group['id']]=$group['title'];
        }
      }
      $contextDefs['default'] = $def;
    }
  }
  if (!empty($_GET['context_id'])) $options['context_id']=$_GET['context_id'];
  if (!empty($_GET['filter_id'])) $options['filter_id']=$_GET['filter_id'];
  foreach($filterData as $filter) {
    if ($filter['defines_permissions']==='t') {
      $selected = (!empty($options['context_id']) && $options['context_id']==$filter['id']) ? 'selected="selected" ' : '';
      $contexts .= "<option value=\"{$filter[id]}\" $selected>{$filter[title]}</option>";
      $contextDefs[$filter['id']] = json_decode($filter['definition']);
    }
    else {
      $selected = (!empty($options['filter_id']) && $options['filter_id']==$filter['id']) ? 'selected="selected" ' : '';
      $existing .= "<option value=\"{$filter[id]}\" $selected>{$filter[title]}</option>";
    }
  }
  $r = '<div id="standard-params" class="ui-widget">';
  if ($options['allowLoad']) {
    $r .= '<div class="header ui-toolbar ui-widget-header ui-helper-clearfix"><h2>'. lang::get('New report') . '</h2><span class="changed" style="display:none" title="This filter has been changed">*</span>';
    $r .= '<div>';
    if ($contexts) {
      data_entry_helper::$javascript .= "indiciaData.filterContextDefs = " . json_encode($contextDefs) . ";\n";
      $r .= '<label for="context-filter">'.lang::get('Context:')."</label><select id=\"context-filter\">$contexts</select>";
    }
    $r .= '<label for="select-filter">'.lang::get('Filter:').'</label><select id="select-filter"><option value="" selected="selected">' . lang::get('Select filter') . "...</option>$existing</select>";
    $r .= '<button type="button" id="filter-apply">' . lang::get('Load selected filter') . '</button>';
    $r .= '<button type="button" id="filter-reset">' . lang::get('Reset') . '</button>';
    $r .= '<button type="button" id="filter-build">' . lang::get('Build filter') . '</button></div>';
    $r .= '</div>';
    $r .= '<div id="filter-details" style="display: none">';
    $r .= '<img src="'.data_entry_helper::$images_path.'nuvola/close-22px.png" width="22" height="22" alt="Close filter builder" title="Close filter builder" class="button" id="filter-done"/>'."\n";
  } else 
    $r .= '<div id="filter-details">';
  $r .= '<div id="filter-panes">';
  $filters = array(
    'filter_what'=>new filter_what(),
    'filter_where'=>new filter_where(), 
    'filter_when'=>new filter_when(), 
    'filter_who'=>new filter_who(), 
    'filter_quality'=>new filter_quality(),
    'filter_source'=>new filter_source()
  );
  if (!empty($options['filterTypes'])) {
    $filterModules = array();
    foreach ($options['filterTypes'] as $category => $list) {
      $paneNames = 'filter_'.str_replace(',', ',filter_', $list);
      $paneList = explode(',', $paneNames);
      $filterModules[$category]=array_intersect_key($filters, array_fill_keys($paneList,1));
    }
  } else 
    $filterModules = array('' => $filters);
  foreach ($filterModules as $category => $list) {
    if ($category)
      $r .= '<fieldset class="collapsible collapsed"><legend>' . $category . '</legend>';
    foreach ($list as $moduleName=>$module) {
      $r .= "<div class=\"pane\" id=\"pane-$moduleName\"><a class=\"fb-filter-link\" href=\"#controls-$moduleName\"><span class=\"pane-title\">" . $module->get_title() . '</span>';
      $r .= '<span class="filter-desc"></span></a>';
      $r .= "</div>\n";
    }
    if ($category)
      $r .= '</fieldset>';
  }
  $r .= '</div>'; // filter panes
  $r .= '<div class="toolbar">';
  if ($options['allowSave']) {
    $r .= '<label for="filter-name">Filter name:</label><input id="filter-name"/>' . 
        '<img src="'.data_entry_helper::$images_path.'nuvola/save-22px.png" width="22" height="22" alt="Save filter" title="Save filter" class="button" id="filter-save"/>';
    $r .= '<img src="'.data_entry_helper::$images_path.'trash-22px.png" width="22" height="22" alt="Bin this filter" title="Bin this filter" class="button disabled" id="filter-delete"/>';
  }  
  $r .= '</div></div>'; // toolbar + clearfix
  report_helper::$javascript .= "indiciaData.lang={};\n";
  // create the hidden panels required to populate the popups for setting each type of filter up.
  $hiddenStuff = '';
  foreach ($filterModules as $category => $list) {
    foreach ($list as $moduleName=>$module) {
      $hiddenStuff .= "<div style=\"display: none\"><form id=\"controls-$moduleName\" class=\"filter-controls\"><fieldset>" . $module->get_controls($readAuth, $options) . 
        '<button class="fb-close">Cancel</button>' .
        '<button class="fb-apply" type="submit">Apply</button></fieldset></form></div>';
      $shortName=str_replace('filter_', '', $moduleName);
      report_helper::$javascript .= "indiciaData.lang.NoDescription$shortName='".lang::get('Click to Filter '.ucfirst($shortName))."';\n";
    }
    
  }   
  $r .= '</div>';
  report_helper::$js_read_tokens = $readAuth;
  report_helper::$javascript .= "indiciaData.lang.FilterReport='".lang::get('Filter Report')."';\n";
  report_helper::$javascript .= "indiciaData.lang.FilterSaved='".lang::get('The filter has been saved')."';\n";
  report_helper::$javascript .= "indiciaData.lang.FilterDeleted='".lang::get('The filter has been deleted')."';\n";
  report_helper::$javascript .= "indiciaData.lang.ConfirmFilterChangedLoad='".lang::get('Do you want to load the selected filter and lose your current changes?')."';\n";
  report_helper::$javascript .= "indiciaData.lang.FilterExistsOverwrite='".lang::get('A filter with that name already exists. Would you like to overwrite it?')."';\n";
  report_helper::$javascript .= "indiciaData.lang.AutochecksFailed='".lang::get('Automated checks failed')."';\n";
  report_helper::$javascript .= "indiciaData.lang.AutochecksPassed='".lang::get('Automated checks passed')."';\n";
  report_helper::$javascript .= "indiciaData.lang.HasPhotos='".lang::get('Records which have photos')."';\n";
  report_helper::$javascript .= "indiciaData.lang.ConfirmFilterDelete='".lang::get('Are you sure you want to permanently delete the {title} filter?')."';\n";
  report_helper::$javascript .= "indiciaData.lang.MyRecords='".lang::get('My records only')."';\n";
  report_helper::$javascript .= "indiciaData.filterPostUrl='".iform_ajaxproxy_url(null, 'filter')."';\n";
  report_helper::$javascript .= "indiciaData.filterAndUserPostUrl='".iform_ajaxproxy_url(null, 'filter_and_user')."';\n";
  report_helper::$javascript .= "indiciaData.filterSharing='".strtoupper(substr($options['sharing'], 0, 1))."';\n";
  report_helper::$javascript .= "indiciaData.user_id='".hostsite_get_user_field('indicia_user_id')."';\n";
  report_helper::$javascript .= "indiciaData.website_id=".$website_id.";\n";
  // load up the filter, BEFORE any AJAX load of the grid code. First fetch any URL param overrides.
  $overrideJs = '';
  foreach(array_merge($options, $_GET) as $key=>$value) {
    if (substr($key, 0, 7)==='filter-') {
      $overrideJs .= "indiciaData.filter.def['".substr($key, 7)."']='".$value."';\n";
    }
  }
  if (!empty($overrideJs)) $overrideJs .= "applyFilterToReports(false);\n";
  report_helper::$onload_javascript = "if ($('#select-filter').val()) {".
      "loadFilter($('#select-filter').val(), false);" .
      "};\n" . $overrideJs . report_helper::$onload_javascript;
  return $r;
}

/**
 * Gets the report data for the list of existing filters this user can access.
 */
function report_filters_load_existing($readAuth, $sharing) {
  $filters = report_helper::get_report_data(array(
    'dataSource' => 'library/filters/filters_list',
    'readAuth' => $readAuth,
    'extraParams' => array('filter_sharing_mode' => $sharing, 'user_id' => hostsite_get_user_field('indicia_user_id'))
  ));
  return $filters;
}