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

require_once 'includes/map.php';
require_once 'includes/form_generation.php';

/**
 * 
 * 
 * @package Client
 * @subpackage PrebuiltForms
 * Form for adding or editing the site details at a location which contains a number of trees.
 */
class iform_tree_locations {

  /**
   * @var int Contains the id of the location attribute used to store the CMS user ID.
   */
  protected static $cmsUserAttrId;
  private static $cmsUserList = null;
  
  /**
   * @var string The Url to post AJAX form saves to.
   */
  private static $ajaxFormUrl = null;
  private static $ajaxFormLocationUrl = null;
  private static $ajaxFormSampleUrl = null;
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_tree_locations_definition() {
    return array(
      'title'=>'Tree Location editor',
      'category' => 'Custom Forms',
      'description'=>'Form for adding or editing the site details at a location which has a number of trees.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return array_merge(
        iform_map_get_map_parameters(),
        iform_map_get_georef_parameters(),
        array(
          array(
            'name'=>'survey_id',
            'caption'=>'Survey',
            'description'=>'The survey that data will be posted into.',
            'type'=>'select',
            'table'=>'survey',
            'captionField'=>'title',
            'valueField'=>'id',
            'siteSpecific'=>true
          ), array(
            'name'=>'sites_list_path',
            'caption'=>'Site list page path',
            'description'=>'Enter the path to the page which the site list is on.',
            'type' => 'string',
            'required' => true,
            'group'=>'General Settings'
          ), array(
            'name'=>'visit_path',
            'caption'=>'Visit Data Entry page path',
            'description'=>'Enter the path to the page which the Visit Data Entry is on.',
            'type' => 'string',
            'required' => true,
            'group'=>'General Settings'
          ), array(
            'name'=>'location_type_term',
            'caption'=>'Site location type term',
            'description'=>'Select the term used for the main site location types.',
            'type' => 'select',
            'table'=>'termlists_term',
            'captionField'=>'term',
            'valueField'=>'term',
            'extraParams' => array('termlist_external_key'=>'indicia:location_types'),
            'required' => true,
            'group'=>'General Settings'
          ), array(
            'name'=>'tree_type_term',
            'caption'=>'Tree location type term',
            'description'=>'Select the term used for tree location types.',
            'type' => 'select',
            'table'=>'termlists_term',
            'captionField'=>'term',
            'valueField'=>'term',
            'extraParams' => array('termlist_external_key'=>'indicia:location_types'),
            'required' => true,            
            'group'=>'General Settings'
          ), array(
            'name'=>'spatial_systems',
            'caption'=>'Allowed Spatial Ref Systems',      
            'description'=>'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code number such as 4326).',
            'type'=>'text_input',
            'group'=>'Other Map Settings'
          ),
          array(
            'name'=>'maxPrecision',
            'caption'=>'Max Sref Precision',
            'description'=>'The maximum precision to be applied when determining the SREF. Leave blank to not set.',
            'type'=>'int',
            'required'=>false,
            'group'=>'Other Map Settings'
          ),
          array(
            'name'=>'minPrecision',
            'caption'=>'Min Sref Precision',
            'description'=>'The minimum precision to be applied when determining the SREF. Leave blank to not set.',
            'type'=>'int',
            'required'=>false,
            'group'=>'Other Map Settings'
          ),
          array(
            'name'=>'tree_map_height',
            'caption'=>'Tree Map Height (px)',
            'description'=>'Height in pixels of the map.',
            'type'=>'int',
            'group'=>'Initial Map View',
            'default'=>600
          ),
          array(
            'name'=>'tree_map_buffer',
            'caption'=>'Site Trees Map Buffer',
            'description'=>'Factor to multiple the size of the site by, in order to generate a margin around the site when displaying the site on the Site Trees tab.',
            'type'=>'string',
            'group'=>'Initial Map View',
            'default'=>'0.1'
          ),
          array(
            'name' => 'allow_user_assignment',
            'label' => 'Allow users to be assigned to Locations',
            'type' => 'boolean',
            'description' => 'Can administrators link users to Locations that they are allowed to record at? Requires a multi-value CMS User ID attribute on the locations.',
            'default'=>true,
            'required'=>false,
            'group' => 'General Settings'
          ),
          array(
            'name'=>'managerPermission',
            'caption'=>'Drupal Permission for Manager mode',
            'description'=>'Enter the Drupal permission name to be used to determine if this user is a manager.',
            'type'=>'string',
            'required' => false
          ),
          array(
            'name' => 'standard_controls_trees',
            'caption' => 'Controls to add to Tree map',
            'description' => 'List of map controls, one per line. Select from layerSwitcher, zoomBox, panZoom, panZoomBar, drawPolygon, drawPoint, drawLine, '.
                'hoverFeatureHighlight, clearEditLayer, modifyFeature, graticule.',
            'type' => 'textarea',
            'group'=>'Other Map Settings',
            'required'=>false,
            'default'=>"layerSwitcher\npanZoomBar\nselectFeature\nhoverFeatureHighlight"
          ),
          array(
            'fieldname'=>'taxon_list_id',
            'label'=>'Tree Species List ',
            'helpText'=>'The species list that tree species can be selected from.',
            'type'=>'select',
            'table'=>'taxon_list',
            'valueField'=>'id',
            'captionField'=>'title',
            'group'=>'Species',
            'siteSpecific'=>true
          ),
        array(
          'name'=>'species_ctrl',
          'caption'=>'Species Selection Control Type',
          'description'=>'The type of control that will be available to select the species.',
          'type'=>'select',
          'options' => array(
            'species_autocomplete' => 'Autocomplete',
            'select' => 'Select',
            'listbox' => 'List box',
            'radio_group' => 'Radio group',
            'treeview' => 'Treeview',
            'tree_browser' => 'Tree browser'
          ),
          'default' => 'select',
          'group'=>'Species'
        ),
        array(
          'name'=>'speciesNameFilterMode',
          'caption'=>'Species Names Filter',
          'description'=>'Select the filter to apply to the species names which are available to choose from.',
          'type'=>'select',
          'options' => array(
            'all' => 'All names are available',
            'currentLanguage' => 'Only allow selection of species using common names in the user\'s language',
            'preferred' => 'Only allow selection of species using names which are flagged as preferred',
            'excludeSynonyms' => 'Allow common names or preferred latin names'
          ),
          'default' => 'all',
          'group'=>'Species'
        ),
        array(
          'name'=>'taxon_filter_field',
          'caption'=>'Field used to filter taxa',
          'description'=>'If you want to allow recording for just part of the selected list(s), then select which field you will '.
                         'use to specify the filter by.',
          'type'=>'select',
          'options' => array(
                         'id' => 'Taxon ID',
                         'taxon' => 'Common name of the taxa',
                         'preferred_name' => 'Preferred name of the taxa',
                         'taxon_meaning_id' => 'Taxon Meaning ID',
                         'taxon_group' => 'Taxon group title',
                         'external_key' => 'Taxon external key'
          ),
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'taxon_filter',
          'caption'=>'Taxon filter items',
          'description'=>'Taxa can be filtered by entering values into this box. '.
                         'Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group. '.
                         'If you provide a single taxon preferred name, taxon meaning ID or external key in this box, then the form is set up for recording just this single '.
                         'species. Therefore there will be no species picker control or input grid, and the form will always operate in the single record, non-grid mode. ',
          'type' => 'textarea',
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'attrOptions',
          'caption'=>'Attribute Options',
          'description'=>'Provides the ability to set the value of options used to generate the attribute controls. One setting per line, each of the format '.
                         '"{option}={value}" to apply to the entire control (e.g. "label=Grid Ref"), or "{attr}|{option}={value}" which apply to a specific '.
                         'custom attribute (e.g. "smpAttr:3|label=Quantity").',
          'type' => 'textarea',
          'required'=>false,
          'group'=>'General Settings'
        )
      )
    );
  }
  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the 
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
  protected static function getArgDefaults($args) {
    return $args;
  }

  private function extract_attr(&$attributes, $caption, $unset=true) {
  	$found=false;
  	foreach($attributes as $idx => $attr) {
  		if (strcasecmp($attr['caption'], $caption)===0) { // should this be untranslated?
  			// found will pick up just the first one
  			if (!$found)
  				$found=$attr;
  			if ($unset)
  				unset($attributes[$idx]);
  			else
  				// don't bother looking further if not unsetting them all
  				break;
  		}
  	}
  	return $found;
  }
  
  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $node The Drupal node object.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   */
  public static function get_form($args, $node, $response=null) {
    global $user;
    data_entry_helper::$helpTextPos = 'before';
    $checks=self::check_prerequisites();
    $args = self::getArgDefaults($args);
    if ($checks!==true)
      return $checks;
    iform_load_helpers(array('map_helper'));
    data_entry_helper::add_resource('jquery_form');
    self::$ajaxFormUrl = iform_ajaxproxy_url($node, 'loc-smp-occ');
    self::$ajaxFormLocationUrl = iform_ajaxproxy_url($node, 'location');
    self::$ajaxFormSampleUrl = iform_ajaxproxy_url($node, 'sample');
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $settings = array(
      'SiteLocationType' => helper_base::get_termlist_terms($auth, 'indicia:location_types', array(empty($args['location_type_term']) ? 'TreeSite' : $args['location_type_term'])),
      'TreeLocationType' => helper_base::get_termlist_terms($auth, 'indicia:location_types', array(empty($args['tree_type_term']) ? 'Tree' : $args['tree_type_term'])),
      'locationId' => isset($_GET['id']) ? $_GET['id'] : null,
      // Allocations of Users are done by a person holding the managerPermission.
      'canAllocUser' => $args['managerPermission']=="" || user_access($args['managerPermission']) 
    );
    $settings['attributes'] = data_entry_helper::getAttributes(array(
        'id' => $settings['locationId'],
        'valuetable'=>'location_attribute_value',
        'attrtable'=>'location_attribute',
        'key'=>'location_id',
        'fieldprefix'=>'locAttr',
        'extraParams'=>$auth['read'],
        'survey_id'=>$args['survey_id'],
        'location_type_id' => $settings['SiteLocationType'][0]['id'],
        'multiValue' => true
    ));
    $settings['tree_attributes'] = data_entry_helper::getAttributes(array(
        'valuetable'=>'location_attribute_value',
        'attrtable'=>'location_attribute',
        'key'=>'location_id',
        'fieldprefix'=>'locAttr',
        'extraParams'=>$auth['read'],
        'survey_id'=>$args['survey_id'],
        'location_type_id' => $settings['TreeLocationType'][0]['id'],
        'multiValue' => true
    ));
    if ($args['allow_user_assignment']) {
      if (false==($settings['cmsUserAttr'] = extract_cms_user_attr($settings['attributes'])))
        return 'This form is designed to be used with the "CMS User ID" attribute setup for Site locations in the survey, or the "Allow users to be assigned to locations" option unticked.';
      // keep a copy of the cms user ID attribute so we can use it later.
      self::$cmsUserAttrId = $settings['cmsUserAttr']['attributeId'];
      $found=false;
      foreach($settings['tree_attributes'] as $idx => $attr) {
        if (strcasecmp($attr['caption'], 'Recorder Name')===0) {
          data_entry_helper::$javascript .= "indiciaData.assignedRecorderID = ".$attr['attributeId'].";\n";
          $found=true;
          break;
        }
      }      
      if (!$found)
        return 'This form is designed to be used with the "Recorder Name" attribute setup for Tree locations in the survey, or the "Allow users to be assigned to locations" option unticked.';
    }
    // TBD data drive
    $definitions = array(array("attr"=>"111", "term"=>"WD", "target"=>"112", "required"=>true, "title"=>"You must pick the dominant species from this drop down list when the WD (Dominant Species) checkbox is set."),
                         array("attr"=>"111", "term"=>"WP", "target"=>"113", "required"=>false, "title"=>"If known, you may enter the year that the woodland was planted in when the WP (Planted Date) checkbox is set."));
    $common = "var check_attrs = function(){\n";
    data_entry_helper::$javascript .= "var checkbox_changed_base = function(changedSelector, targetSelector, required){
  $(changedSelector).closest('span').find('label.inline-error').remove();
  $(changedSelector).closest('span').find('.ui-state-error').removeClass('ui-state-error');
  $(changedSelector).closest('span').find('label.error').remove();
  $(changedSelector).closest('span').find('.error').removeClass('error');
  if($(changedSelector).attr('checked'))
    $(targetSelector).addClass(required ? 'required' : 'notrequired').closest('span').show();
  else {
    $(targetSelector).removeClass('required').val('').closest('span').hide();
  }
};
var check_attr_def = [];
check_attrs = function(){
  for(var i=0; i<check_attr_def.length; i++){
    checkbox_changed_base(check_attr_def[i][0], check_attr_def[i][1], check_attr_def[i][2]);
  }
}\n";
    foreach($definitions as $defn){
      data_entry_helper::$javascript .= "$('[id^=locAttr\\\\:".$defn["attr"]."\\\\:]:checkbox').each(function(idx,elem){
  if($('label[for='+$(elem).attr('id').replace(/:/g,'\\\\:')+']').html() == '".$defn["term"]."'){
    var tgt = $('#locAttr\\\\:".$defn["target"]."');
    tgt.prev('label').remove();
    tgt.next('br').remove();
    var span = $('<span/>');
    $(elem).closest('span').append(span);
    span.append(tgt);".
    ($defn["required"] ? "\n    span.append('<span class=\"deh-required\">*</span>');" : "")."
    tgt.attr('title','".$defn["title"]."');
    $(elem).change(function(e){checkbox_changed_base(e.target, '#locAttr\\\\:".$defn["target"]."', ".($defn["required"] ? "true" : "false").");});
    check_attr_def.push([elem, '#locAttr\\\\:".$defn["target"]."', ".($defn["required"] ? "true" : "false")."]);
  }
});\n";
    }
    data_entry_helper::$javascript .= "check_attrs();\nindiciaData.trees = {};\n";
    $settings['trees']=array();
    if ($settings['locationId']) {
      data_entry_helper::load_existing_record($auth['read'], 'location', $settings['locationId']);
      // Work out permissions for this user
      $canEdit = ($args['managerPermission']=="" || user_access($args['managerPermission']));
      if($args['allow_user_assignment'] && isset($settings['cmsUserAttr']['default']) && !empty($settings['cmsUserAttr']['default'])) {
        foreach($settings['cmsUserAttr']['default'] as $value) { // multi value
          if($value['default'] == $user->uid) { // comparing string against int so no triple equals
            $canEdit = true;
            break;
          }
        }
      }
      if(!$canEdit)
        return 'You do not have access to this site.';
      $trees = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $auth['read'] + array('view'=>'detail','parent_id'=>$settings['locationId'],'deleted'=>'f','orderby'=>'name'),
        'nocache' => true
      ));
      foreach($trees as $tree) {
        $id = $tree['id'];
        data_entry_helper::$javascript .= "indiciaData.trees[$id] = {'id':'".$tree['id']."','name':'".str_replace("'","\'",$tree['name'])."','geom':'".$tree['centroid_geom']."','sref':'".$tree['centroid_sref']."','system':'".$tree['centroid_sref_system']."'};\n";
        $settings['trees'][$id]=$tree;
      }
    }
    $r = '<div id="controls">';
    $headerOptions = array('tabs'=>array('#site-details'=>lang::get('Site Details')));
    $tabOptions = array('divId'=>'controls', 'style'=>'Tabs');
    if ($settings['locationId']) {
      $headerOptions['tabs']['#site-trees'] = lang::get('Tree Details');
      $tabOptions['active']='#site-trees';
    }
    $r .= data_entry_helper::tab_header($headerOptions);
    data_entry_helper::enable_tabs($tabOptions);
    
    $settings['treeSampleMethod'] = helper_base::get_termlist_terms($auth, 'indicia:sample_methods', array('TreeInitialRegistration'));
    // TODO put in error check, add in $arg driving of text value
    $settings['treeSampleMethod'] = $settings['treeSampleMethod'][0];

    $r .= self::get_site_tab($auth, $args, $settings);
    if ($settings['locationId']) {
      $r .= self::get_site_trees_tab($auth, $args, $settings);
      data_entry_helper::enable_validation('tree-form');
      data_entry_helper::setup_jquery_validation_js();
    }
    $r .= '</div>'; // controls    
    data_entry_helper::enable_validation('input-form');
    if (function_exists('drupal_set_breadcrumb')) {
      $breadcrumb = array();
      $breadcrumb[] = l(lang::get('Home'), '<front>');
      $breadcrumb[] = l(lang::get('Sites'), $args['sites_list_path']);
      if ($settings['locationId'])
        $breadcrumb[] = data_entry_helper::$entity_to_load['location:name'];
      else
        $breadcrumb[] = lang::get('New Site');
      drupal_set_breadcrumb($breadcrumb);
    }
    // Inform JS where to post data to for AJAX form saving
    data_entry_helper::$javascript .= 'indiciaData.ajaxFormPostUrl="'.self::$ajaxFormUrl."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.ajaxFormPostLocationUrl="'.self::$ajaxFormLocationUrl."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.ajaxFormPostSampleUrl="'.self::$ajaxFormSampleUrl."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.website_id="'.$args['website_id']."\";\n";
    data_entry_helper::$javascript .= "indiciaData.indiciaSvc = '".data_entry_helper::$base_url."';\n";
    data_entry_helper::$javascript .= "indiciaData.readAuth = {nonce: '".$auth['read']['nonce']."', auth_token: '".$auth['read']['auth_token']."'};\n";    
    data_entry_helper::$javascript .= "indiciaData.currentTree = '';\n";
    data_entry_helper::$javascript .= "indiciaData.treeTypeId = '".$settings['TreeLocationType'][0]['id']."';\n";
    data_entry_helper::$javascript .= "indiciaData.treeDeleteConfirm = \"".lang::get('Are you sure you wish to delete tree')."\";\n";
    data_entry_helper::$javascript .= "indiciaData.treeInsertConfirm = \"".lang::get('Are you sure you wish to create a new tree (make sure you have saved any data)')."\";\n";
    data_entry_helper::$javascript .= "indiciaData.treeChangeConfirm = \"".lang::get('Do you wish to save the currently unsaved changes you have made to the Tree Details?')."\";\n";
    data_entry_helper::$javascript .= "indiciaData.treeSampleMethodID = \"".$settings['treeSampleMethod']['id']."\";\n";
    data_entry_helper::$javascript .= "indiciaData.newVisitDialog = \"".lang::get('You have just created a new tree. You can now create the first phenology observation data, or you can leave it until later. Do you wish to create the phenology observation data now? (This will open in a new window.)')."\";\n";
    data_entry_helper::$javascript .= "indiciaData.existingVisitDialog = \"".lang::get('You have just modified an existing tree. Do you wish to create phenology observation data now? (This will open in a new window.)')."\";\n";
    data_entry_helper::$javascript .= "indiciaData.visitURL = \"".($args['visit_path'] . (strpos($args['visit_path'], '?') === false ? '?' : '&') . "new=1&location_id=")."\";\n";
    $r .= '<a id="visit_link" style="display:none;" href="" target="_blank" />';
    if ($settings['locationId'])
      data_entry_helper::$onload_javascript .= "var first=true;\njQuery.each(indiciaData.trees, function(idx, tree) {\n  if(first) selectTree(tree.id, true);  \nfirst=false\n});\nif(first) insertTree();\n";
    return $r;
  }
  
  private static function check_prerequisites() {
    // check required modules installed
    if (isset($_POST['enable'])) {
      module_enable(array('iform_ajaxproxy'));
      drupal_set_message(lang::get('The Indicia AJAX Proxy module has been enabled.', 'info'));      
    }
    $ok=true;
    if (!module_exists('iform_ajaxproxy')) {
       drupal_set_message('This form must be used in Drupal with the Indicia AJAX Proxy module enabled.');
       $ok=false;
    }
    if (!function_exists('iform_ajaxproxy_url')) {
      drupal_set_message(lang::get('The Indicia AJAX Proxy module must be enabled to use this form. This lets the form save verifications to the '.
          'Indicia Warehouse without having to reload the page.'));
      $r .= '<form method="post">';
      $r .= '<input type="hidden" name="enable" value="t"/>';
      $r .= '<input type="submit" value="'.lang::get('Enable Indicia AJAX Proxy').'">';
      $r .= '</form>';
      return $r;
    }
    return $ok;
  }
  
  private static function get_site_tab($auth, $args, $settings) {
    $r = '<div id="site-details" class="ui-helper-clearfix">';
    $r .= '<form method="post" id="input-form">';
    $r .= $auth['write'];    
    $r .= '<fieldset><legend>'.lang::get('Site Details').'</legend>';
    $r .= "<input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= "<input type=\"hidden\" name=\"survey_id\" value=\"".$args['survey_id']."\" />\n";    
    $r .= "<input type=\"hidden\" name=\"location:location_type_id\" value=\"".$settings['SiteLocationType'][0]['id']."\" />\n";
    if ($settings['locationId'])
      $r .= '<input type="hidden" name="location:id" id="location:id" value="'.$settings['locationId']."\" />\n";
    $r .= data_entry_helper::text_input(array(
      'fieldname' => 'location:name',
      'label' => lang::get('Site Name'),
      'class' => 'control-width-4 required'
    ));
    $r .= data_entry_helper::sref_and_system(array(
      'fieldname' => 'location:centroid_sref',
      'geomFieldname' => 'location:centroid_geom',
      'label' => 'Site Central Grid Ref',
      'systems' => array('4326'=>'4326'),
      'class' => 'required',
      'disabled' => ' readonly="readonly" ',
      'helpText' => lang::get('The following field is filled in automatically when the site is drawn on the map.')
    ));
    $r .= '<input type="hidden" name="location:boundary_geom" id="imp-boundary-geom" value="' .
    		data_entry_helper::$entity_to_load['location:boundary_geom'] . '"/>';
    
    // setup the map options
    $options = iform_map_get_map_options($args, $auth['read']);
    $r .= get_attribute_html($settings['attributes'], $args, array('extraParams'=>$auth['read'])); 
    $r .= '</fieldset>';
    if (!$settings['locationId']) {
      $help = lang::get('Use the search box to find a nearby town or village, then drag the map to pan and zoom in or out to find your site.');
      $r .= '<p class="ui-state-highlight page-notice ui-corner-all">'.$help.'</p>';
      $r .= data_entry_helper::georeference_lookup(array(
        'label' => lang::get('Search for place'),
        'driver'=>$args['georefDriver'],
        'georefPreferredArea' => $args['georefPreferredArea'],
        'georefCountry' => $args['georefCountry'],
        'georefLang' => $args['language'],
        'readAuth' => $auth['read']
      ));
    }
    $help = '<p>'.lang::get('Draw your site by selecting the appropriate tool in the top right of the map').':</p>'.
            '<ol><li>'.lang::get('Navigation Tool. This allows you to navigate around the map by dragging.').'</li>'.
                '<li>'.lang::get('Draw Site Tool. This tool allows you to draw your site on the map. Click on the map to start drawing, and click each time you wish to fix a point on the outline of your site.  <b>Double click the last fixed point to finish drawing</b>. Double clicking will replace any previously drawn site outline.').'</li>'.
                '<li>'.lang::get('Modify Site Tool. This tool allows you to change the shape of a previously drawn site. Click on the existing site outline to add grab points (circles) along it. You can then click and drag these points to change the shape of the site. To remove a fixed point, hover your mouse over the point and press the "Delete" button on your keyboard.').'</li></ol>';
    if ($args['allow_user_assignment']) {
      if ($settings['canAllocUser'])
        $help .= '<p>'.lang::get('You, as a scheme administrator, have the option of changing who the site is assigned to, using control under the map. If you so wish, the site may be assigned to more than one person at a time.').'</p>';
    }
    $r .= '<div class="page-notice ui-state-highlight ui-corner-all">'.$help.'</div>';
    if(isset($args['maxPrecision']) && $args['maxPrecision'] != ''){
      $options['clickedSrefPrecisionMax'] = $args['maxPrecision'];
    }
    if(isset($args['minPrecision']) && $args['minPrecision'] != ''){
      $options['clickedSrefPrecisionMin'] = $args['minPrecision'];
    }
    $olOptions = iform_map_get_ol_options($args);
    $options['clickForSpatialRef']=false;
    $options['allowPolygonRecording']=true;
    $options['latLongFormat'] = 'DMS';
    $options['autoFillInCentroid']=true;
    $options['initialFeatureWkt']=false;
    $options['initialBoundaryWkt']=data_entry_helper::$entity_to_load['location:boundary_geom'];
    $options['hintModifyFeature']=lang::get('Modify Site Tool.');
    $options['hintDrawPolygonHint']=lang::get('Draw Site Tool.');
    $options['hintNavigation']=lang::get('Navigation Tool.');
    $options['searchDisplaysPoint']=false;
    
    // with multiple maps can't use built in method on tabshow, so do here...
    $divId = preg_replace('/[^a-zA-Z0-9]/', '', 'site-details');
    $javascript = "var mapTabHandler = function(event, ui) { \n";
    $javascript .= "  panel = typeof ui.newPanel==='undefined' ? ui.panel : ui.newPanel[0];
  if (panel.id=='site-details' && typeof indiciaData.mapdiv !== 'undefined') {
    var map = jQuery('#map')[0].map;
    map.updateSize();
    var layerBounds = map.editLayer.getDataExtent().clone(); // use a clone
    map.zoomToExtent(layerBounds);
  }
};
indiciaFns.bindTabsActivate(jQuery(jQuery('#site-details').parent()), mapTabHandler);\n";
    // Insert this script at the beginning, because it must be done before the tabs are initialised or the
    // first tab cannot fire the event
    data_entry_helper::$javascript = $javascript . data_entry_helper::$javascript;

    $r .= map_helper::map_panel($options, $olOptions);
    if ($args['allow_user_assignment']) {
      if ($settings['canAllocUser']) {
        $r .= self::get_user_assignment_control($auth['read'], $settings['cmsUserAttr'], $args);
      } else if (!$settings['locationId']) {
        // for a new record, we need to link the current user to the location if they are not admin.
        global $user;
        self::get_user_assignment_control($auth['read'], $settings['cmsUserAttr'], $args); // this will populate the recorder list.
        $r .= '<input type="hidden" name="locAttr:'.self::$cmsUserAttrId.'" value="'.$user->uid.'">';
      }
    }
    $r .= '<button type="submit" class="indicia-button right">'.lang::get('Save').'</button>';
    
    if($settings['locationId'])
      $r .= '<button type="button" class="indicia-button right" id="delete-site">'.lang::get('Delete').'</button>' ;
    $r .='</form>';
    $r .= '</div>'; // site-details
    if($settings['locationId']) {
      $treeIDs = array();
      foreach($settings['trees'] as $id=>$tree)
        $treeIDs[] = $tree['id'];
      data_entry_helper::$javascript .= "
deleteSite = function(){
  if(confirm(\"".lang::get('Are you sure you wish to delete this location?')."\")){
    deleteTrees([".implode(',',$treeIDs)."]);
    $('#delete-site').html('Deleting Site');
    deleteLocation(".$settings['locationId'].");
    $('#delete-site').html('Done');
    window.location='".url($args['sites_list_path'])."';
  };
};
$('#delete-site').click(deleteSite);
";
    }
    return $r;
  }
  
  private static function get_site_trees_tab($auth, $args, $settings) {
    global $indicia_templates;
  	$r = '<div id="site-trees" class="ui-helper-clearfix">';
    $r .= '<form method="post" id="tree-form" action="'.self::$ajaxFormUrl.'">';
    $help = '<p>'.lang::get('To add a tree, click on the "Add Tree" button. You can then use the map\'s Location Tool to select the approximate location of your tree on the map. A new tree will then appear on the map.').'</p>'.
            '<p>'.lang::get('To select a tree from the existing list of trees at this site you can either:').'</p>'.
            '<ol><li>'.lang::get('Click on the button for the tree you wish to view, or').'</li>'.
            '<li>'.lang::get('Use the map\'s Query Tool to click on the tree you wish to view on the map.').'</li></ol>'.
            '<p>'.lang::get('To remove a tree, first select the tree you wish to remove, then click on the "Remove Tree" button. It will remove the current tree you are viewing completely.').'</p>';
    $r .= '<div class="ui-state-highlight page-notice ui-corner-all">'.$help.'</div>';
    $r .=  self::tree_selector($settings);
    $r .= '<input type="button" value="'.lang::get('Remove Tree').'" class="remove-tree form-button right" title="'.lang::get('Completely remove the highlighted tree. The total number of tree will be reduced by one. The form will be reloaded after the tree is deleted.').'">';
    $r .= '<input type="button" value="'.lang::get('Add Tree').'" class="insert-tree form-button right" title="'.lang::get('This inserts an extra tree.').'">';
    $r .= '<div id="cols" class="ui-helper-clearfix"><div class="left" style="width: '.(98-(isset($args['percent_width']) ? $args['percent_width'] : 50)).'%">';
    $r .= '<fieldset><legend>'.lang::get('Tree Details').'</legend>';
    $r .= '<input type="hidden" name="location:id" value="" id="tree-location-id" />';
    $r .= '<input type="hidden" name="locations_website:website_id" value="'.$args['website_id'].'" id="locations-website-website-id" />';
    $r .= '<input type="hidden" name="location:parent_id" value="'.$settings['locationId'].'" />';
    $r .= '<input type="hidden" name="location:location_type_id" value="'.$settings['TreeLocationType'][0]['id'].'" />';
    $r .= '<input type="hidden" name="website_id" value="'.$args['website_id']."\" />\n";
    $r .= data_entry_helper::text_input(array(
    		'fieldname' => 'location:name',
    		'label' => lang::get('Tree ID'),
    		'class' => 'control-width-4 required'
    ));
    $systems = array();
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach($list as $system) {
      $systems[$system] = lang::get($system);
    }
    $srefOptions = array(
    		'id' => 'imp-sref-tree',
    		'fieldname' => 'location:centroid_sref',
    		'geomid' => 'imp-geom-tree',
    		'geomFieldname' => 'location:centroid_geom',
    		'label' => 'Grid Ref',
            'labelClass'=>'auto',
    		'class' => 'required',
    		'helpText' => lang::get('You can also click on the map to set the grid reference. If directly entering the coordinates from a GPS device, set the format to "Lat/Long" first. To enter an OS Grid square, choose the "OSGB" or "OSIE" formats.')
    );
    data_entry_helper::$javascript .= "
$('#imp-sref-tree').attr('title',
    '".lang::get("When directly entering coordinates as a GPS Lat/Long, the format is flexible. ".
                  "The figures may be entered as decimal degrees (e.g. 56.532), degrees and decimal minutes (e.g. 56:31.92), or degrees, minutes and decimal seconds (e.g. 56:31:55.2). ".
                  "The degrees minutes and seconds must all be separated by a colon (:). ".
                  "The direction letter can be placed at the start or the end of the number (e.g. N56.532 or 56.532N), and it can be upper or lower case, but by convention you should use upper case. ".
                  "There should be no spaces between the numbers and the letter. ".
                  "You can mix the formats of the Latitude and Longitude, provided they each follow the previous guidelines and a space separates them (e.g. N56.532 2:30W).")."  ".
    lang::get("When directly entering a OSGB reference, this should take the format of 2 letters followed by an even number of digits (e.g. NT274628). ".
                  "The letters specify the 100km grid square, the first half of the numbers are the Easting, and the second half the Northing. ".
                  "The size of the square is determined by the number of digits entered: each pair reduces the square size by a factor of 10 - 8 numbers will give a 10m square. ".
                  "There should be no spaces between any of the characters. ".
                  "The letters may be upper or lower case, but by convention you should use upper case.")."  ".
    lang::get("When directly entering a OSIE reference, this should take the format of 1 letter followed by an even number of digits (e.g. J081880). ".
                  "The letter specifies the 100km grid square, the first half of the numbers are the Easting, and the second half the Northing. ".
                  "The size of the square is determined by the number of digits entered: each pair reduces the square size by a factor of 10 - 8 numbers will give 10m square. ".
                  "There should be no spaces between any of the characters. ".
                  "The letter may be upper or lower case, but by convention you should use upper case.")."');\n";
    // Output the sref control
    $r .= data_entry_helper::sref_textbox($srefOptions);
    $srefOptions = array(
    		'id' => 'imp-sref-system-tree',
    		'fieldname' => 'location:centroid_sref_system',
    		'class' => 'required',
    		'systems' => $systems
    );
    // Output the system control
    if (count($systems) < 2) {
    	// Hidden field for the system
    	$keys = array_keys($options['systems']);
    	$r .= "<input type=\"hidden\" id=\"imp-sref-system-tree\" name=\"".$options['fieldname']."\" value=\"".$keys[0]."\" />\n";
// TODO    	self::include_sref_handler_js($options['systems']);
    }
    else {
    	$r .= data_entry_helper::sref_system_select($srefOptions);
    }
    $r .= '<input type="hidden" name="survey_id" value="'.$args['survey_id'].'" />';
    $r .= '<input type="hidden" name="sample:survey_id" value="'.$args['survey_id'].'" />';
    $r .= '<input type="hidden" name="sample:id" value="" />';
    // this sample will reference the location id.
    if (isset(data_entry_helper::$entity_to_load['sample:date']) && preg_match('/^(\d{4})/', data_entry_helper::$entity_to_load['sample:date'])) {
      // Date has 4 digit year first (ISO style) - convert date to expected output format
      // @todo The date format should be a global configurable option. It should also be applied to reloading of custom date attributes.
      $d = new DateTime(data_entry_helper::$entity_to_load['sample:date']);
      data_entry_helper::$entity_to_load['sample:date'] = $d->format('d/m/Y');
    }
    $r .= data_entry_helper::date_picker(array(
    		'label'=>lang::get('Date Tree Selected'),
    		'fieldname'=>'sample:date',
    		'class' => 'control-width-2 required'
    ));
    $r .= '<input type="hidden" id="sample:sample_method_id" value="'.$settings['treeSampleMethod']['id'].'" name="sample:sample_method_id">';
    $r .= '<input type="hidden" id="sample:location_name" value="" name="sample:location_name">';
    $r .= '<input type="hidden" name="occurrence:id" value="" id="occurrence:id" />';
    $r .= '<input type="hidden" name="occurrence:record_status" value="C" id="occurrence:record_status" />';
    $extraParams = $auth['read'];
    $extraParams['taxon_list_id'] = $args['taxon_list_id'];
    $options = array('speciesNameFilterMode' => $args['speciesNameFilterMode']);
    $ctrl = $args['species_ctrl'];
    $species_ctrl_opts=array_merge(array(
        'fieldname'=>'occurrence:taxa_taxon_list_id',
        'label'=>lang::get('Tree Species'),
        'columns'=>1, // applies to radio buttons
        'parentField'=>'parent_id', // applies to tree browsers
        'blankText'=>lang::get('Please select'), // applies to selects
        'cacheLookup'=>/* TODO $args['cache_lookup']*/ false
    ), $options);
    if (isset($species_ctrl_opts['extraParams']))
      $species_ctrl_opts['extraParams']=array_merge($extraParams, $species_ctrl_opts['extraParams']);
    else
      $species_ctrl_opts['extraParams']=$extraParams;
    if (!empty($args['taxon_filter'])) {
      $species_ctrl_opts['taxonFilterField']=$args['taxon_filter_field']; // applies to autocompletes
      $species_ctrl_opts['taxonFilter']=helper_base::explode_lines($args['taxon_filter']); // applies to autocompletes
    }
    // obtain table to query and hence fields to use     
    $db = data_entry_helper::get_species_lookup_db_definition(/*TODO $args['cache_lookup']*/ false);
    // get local vars for the array
    extract($db);
    if ($ctrl!=='species_autocomplete') {
      // The species autocomplete has built in support for the species name filter.
      // For other controls we need to apply the species name filter to the params used for population
      if (!empty($species_ctrl_opts['taxonFilter']) || $options['speciesNameFilterMode'])
        $species_ctrl_opts['extraParams'] = array_merge($species_ctrl_opts['extraParams'], data_entry_helper::get_species_names_filter($species_ctrl_opts));
      // for controls which don't know how to do the lookup, we need to tell them
      $species_ctrl_opts = array_merge(array(
        'table' => $tblTaxon,
        'captionField' => $colTaxon,
        'valueField' => $colId,
      ), $species_ctrl_opts);
    }
    // if using something other than an autocomplete, then set the caption template to include the appropriate names. Autocompletes
    // use a JS function instead.
    if ($ctrl!=='autocomplete' && isset($args['species_include_both_names']) && $args['species_include_both_names']) {
      if ($args['speciesNameFilterMode']==='all')
        $indicia_templates['species_caption'] = "{{$colTaxon}}";
      elseif ($args['speciesNameFilterMode']==='language')
        $indicia_templates['species_caption'] = "{{$colTaxon}} - {{$colPreferred}}";
      else
        $indicia_templates['species_caption'] = "{{$colTaxon}} - {{$colCommon}}";
      $species_ctrl_opts['captionTemplate'] = 'species_caption';
    }
    if ($ctrl=='tree_browser') {
      // change the node template to include images
      $indicia_templates['tree_browser_node']='<div>'.
          '<img src="'.data_entry_helper::$base_url.'/upload/thumb-{image_path}" alt="Image of {caption}" width="80" /></div>'.
          '<span>{caption}</span>';
    }
    // Dynamically generate the species selection control required.
    $r .= call_user_func(array('data_entry_helper', $ctrl), $species_ctrl_opts);
    $ctrlOptions = array('extraParams'=>$auth['read']);
    $attrSpecificOptions = array();
    $options = helper_base::explode_lines_key_value_pairs($args['attrOptions']);
    self::parseForAttrSpecificOptions($options, $ctrlOptions, $attrSpecificOptions);
    $r .= get_attribute_html($settings['tree_attributes'], $args, $ctrlOptions, '', $attrSpecificOptions);
    
    $r .= '</fieldset>';
    $r .= "</div>" .
    		'<div class="right" style="width: '.(isset($args['percent_width']) ? $args['percent_width'] : 50).'%">';
    $olOptions = iform_map_get_ol_options($args);
    $options = iform_map_get_map_options($args, $auth['read']);
    $options['divId'] = 'trees-map';
    $options['toolbarDiv'] = 'top';
    $options['tabDiv']='site-trees';
    $options['gridRefHint']=true;
    $options['latLongFormat'] = 'DMS'; // TODO drive from args or user.
    if (array_key_exists('standard_controls_trees', $args) && $args['standard_controls_trees']) {
      $standard_controls_trees = str_replace("\r\n", "\n", $args['standard_controls_trees']);
      $options['standardControls']=explode("\n", $standard_controls_trees);
      // If drawing controls are enabled, then allow polygon recording.
      if (in_array('drawPolygon', $options['standardControls']) || in_array('drawLine', $options['standardControls']))
        $options['allowPolygonRecording']=true;
    }
    // also let the user click on a feature to select it. The highlighter just makes it easier to select one.
    // these controls are not present in read-only mode: all you can do is look at the map.
    $options['switchOffSrefRetrigger'] = true;
    $options['clickForSpatialRef'] = true;
    // override the opacity so the parent square does not appear filled in.
    $options['fillOpacity'] = 0;
    // override the map height and buffer size, which are specific to this map.
    $options['height'] = $args['tree_map_height'];
    $options['maxZoomBuffer'] = $args['tree_map_buffer'];
    
    $options['srefId']='imp-sref-tree';
    $options['geomId']='imp-geom-tree';
    $options['srefSystemId']='imp-sref-system-tree';
    $help = '<p>'.lang::get('Add your trees using the appropriate tools in the top right of the map').':</p>'.
            '<ol><li>'.lang::get('Navigation Tool.').'</li>'.
                '<li>'.lang::get('Query Tool. This tool allows you to click on a tree on the map to view its tree details.').'</li>'.
                '<li>'.lang::get('Location Tool. This tool allows you to select the approximate location of a new tree on your site map. You can also reposition existing trees by first clicking on the tree and then clicking on its new location on the map.').'</li></ol>';
    $r .= '<div class="ui-state-highlight page-notice ui-corner-all">'.$help.'</div>';
    $r .= map_helper::map_panel($options, $olOptions);
    $r .= data_entry_helper::file_box(/* array_merge( */array(
    		'table'=>'location_medium',
        'readAuth' => $auth['read'],
    		'caption'=>lang::get('Photos of Tree'),
    		'readAuth'=>$auth['read']
    )/*, $options)*/);
    $r .= "</div>"; // right
    $r .= '<div class="follow_on_block" style="clear:both;">';
    $r .= get_attribute_html($settings['tree_attributes'], $args, $ctrlOptions, 'Lower Block', $attrSpecificOptions);
    data_entry_helper::$javascript .= "
$('#fieldset-optional-external-sc').prepend(\"".lang::get('If you choose to record this tree for one of the citizen science projects below, please submit the tree ID used for that scheme.')."\");
";
    $r .= data_entry_helper::textarea(array(
    		'id'=>'location-comment',
    		'fieldname'=>'location:comment',
    		'label'=>lang::get("Additional information"),
    		'labelClass'=>'autowidth'))."<br />";
    $r .= '<input type="submit" value="'.lang::get('Save').'" class="form-button right" id="submit-tree" />';
    $r .= '</div></form></div>';
    data_entry_helper::$onload_javascript .= "$('#current-tree').change(selectTree);\n";
    return $r;
  }

  /**
   * Parses the options provided to a control in the user interface definition and splits the options which
   * apply to the entire control (@label=Grid Ref) from ones which apply to a specific custom attribute
   * (smpAttr:3|label=Quantity).
   */
  protected static function parseForAttrSpecificOptions($options, &$ctrlOptions, &$attrSpecificOptions) {
  	// look for options specific to each attribute
  	foreach ($options as $option => $value) {
  		// split the id of the option into the control name and option name.
  		if (strpos($option, '|')!==false) {
  			$optionId = explode('|', $option);
  			if (!isset($attrSpecificOptions[$optionId[0]])) $attrSpecificOptions[$optionId[0]]=array();
  			$attrSpecificOptions[$optionId[0]][$optionId[1]] = $value;
  		} else {
  			$ctrlOptions[$option]=$value;
  		}
  	}
  }

  /**
   * Build a row of buttons for selecting the trees.
   */
  private static function tree_selector($settings) {
    $selector = '<label for="tree-select">'.lang::get('Select tree').':</label><ol id="tree-select" class="tree-select">';
    foreach ($settings['trees'] as $id=>$tree) { // TBD
      $selector .= "<li id=\"tree-$id\">".$tree['name']."</li>";
    }
    $selector .= '</ol>';
    return $selector;
  }
  
  /**
   * If the user has permissions, then display a control so that they can specify the list of users associated with this site.
   */
  private static function get_user_assignment_control($readAuth, $cmsUserAttr, $args) {
    $r = "";
  	if(self::$cmsUserList == null) {
      $query = db_query("select uid, name from {users} where name <> '' order by name");
      $users = array();
      // there have been DB API changes for Drupal7: db_query now returns the result array.
      if(version_compare(VERSION, '7', '<')) {
        while ($user = db_fetch_object($query))
          $users[$user->uid] = $user->name;
      } else {
        foreach ($query as $user) {
          $built_name = $user->name;
          $account = user_load($user->uid);
          $fieldname = 'field_first_name';
          $fieldinfo = field_get_items('user', $account, $fieldname);
          if ($fieldinfo) {
            $built_name = check_plain($fieldinfo[0]['value']);
            $fieldname = 'field_last_name';
            $fieldinfo = field_get_items('user', $account, $fieldname);
            if ($fieldinfo && $built_name!="") {
              $built_last_name = check_plain($fieldinfo[0]['value']);
              if($built_last_name != "") $built_name .= " ".$built_last_name;
            }
            else $built_name = $user->name;
          }
          $users[$user->uid] = $built_name;
        }
      }
      self::$cmsUserList = $users;
    } else $users= self::$cmsUserList;
    $selected = "";
    $r .= '<fieldset id="alloc-recorders"><legend>'.lang::get('Allocate recorders to the site').'</legend>';
    $r .= data_entry_helper::select(array(
      'label' => lang::get('Select user'),
      'fieldname' => 'cmsUserId',
      'lookupValues' => $users,
      'afterControl' => '<button id="add-user" type="button">'.lang::get('Add').'</button>'
    ));
    $r .= '<table id="user-list" style="width: auto">';
    $rows = '';
    // cmsUserAttr needs to be multivalue
    if (isset($cmsUserAttr['default']) && !empty($cmsUserAttr['default'])) {
      foreach($cmsUserAttr['default'] as $value) {
      	$selected .= ($selected == "" ? "" : $selected.", ").$users[$value['default']];
        $rows .= '<tr><td id="user-'.$value['default'].'"><input type="hidden" name="'.$value['fieldname'].'" '.
            'value="'.$value['default'].'"/>'.$users[$value['default']].
            '</td><td><div class="ui-state-default ui-corner-all"><span class="remove-user ui-icon ui-icon-circle-close"></span></div></td></tr>';
        }
    }
    data_entry_helper::$javascript .= "indiciaData.assignedUsers = '" . $selected . "';\n";
    if (empty($rows))
      $rows = '<tr><td colspan="2"></td></tr>';
    $r .= "$rows</table>\n";
    $r .= '</fieldset>';
    if ($args['allow_user_assignment']) {
      // tell the javascript which attr to save the user ID into
      data_entry_helper::$javascript .= "indiciaData.locCmsUsrAttr = " . self::$cmsUserAttrId . ";\n";
    }
    return $r;
  }

  /**
   * Construct a submission for the location.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    $s = submission_builder::build_submission($values, 
      array(
        'model' => 'location'
      )
    );
    // on first save of a new site, link it to the website.
    if (empty($values['location:id']))
      $s['subModels'] = array(
        array(
          'fkId' => 'location_id', 
          'model' => array(
            'id' => 'locations_website',
            'fields' => array(
              'website_id' => $args['website_id']
            )
          )
        )
      );
    return $s;
  }
  
  /**
   * After saving a new site, reload the site so that the user can continue to save the trees.
   */
  public static function get_redirect_on_success($values, $args) {
    if (!isset($values['location:id'])) {
      return drupal_get_path_alias($_GET['q']).'#site-trees';
    }
  }

}
