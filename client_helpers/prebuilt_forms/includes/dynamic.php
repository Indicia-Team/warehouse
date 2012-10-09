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
 * Parent class for dynamic prebuilt Indicia data entry forms.
 * NB has Drupal specific code.
 * 
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('map.php');
require_once('user.php');
require_once('language_utils.php');
require_once('form_generation.php');

class iform_dynamic {

  // The node id upon which this form appears
  protected static $node;

  // The class called by iform.module which may be a subclass of iform_location_dynamic
  protected static $called_class;

  // The authorisation tokens for accessing the warehouse
  protected static $auth = array();

  // The form mode. Stored in case other inheriting forms need it.
  protected static $mode;

  // Values that $mode can take
  const MODE_GRID = 0; // default mode when no grid set to false - display grid of existing data
  const MODE_NEW = 1; // default mode when no_grid set to true - display new sample
  const MODE_EXISTING = 2; // display existing sample


  public static function get_perms($nid) {
    return array('IForm n'.$nid.' admin', 'IForm n'.$nid.' user');
  }

  public static function get_parameters() {    
    $retVal = array_merge(
      iform_map_get_map_parameters(),
      iform_map_get_georef_parameters(),
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
          'group' => 'User Interface'
        ),
        array(
          'name'=>'tabProgress',
          'caption'=>'Show Progress through Wizard/Tabs',
          'description'=>'For Wizard or Tabs interfaces, check this option to show a progress summary above the controls.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name'=>'clientSideValidation',
          'caption'=>'Client Side Validation',
          'description'=>'Enable client side validation of controls using JavaScript.',
          'type'=>'boolean',
          'default' => true,
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name'=>'attribute_termlist_language_filter',
          'caption'=>'Attribute Termlist Language filter',
          'description'=>'Enable filtering of termlists for attributes using the iso language.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name'=>'no_grid',
          'caption'=>'Skip initial grid of data',
          'description'=>'If checked, then when initially loading the form the data entry form is immediately displayed, as opposed to '.
              'the default of displaying a grid of the user\'s data which they can add to. By ticking this box, it is possible to use this form '.
              'for data entry by anonymous users though they cannot then list the data they have entered.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface'
        ),    
        array(
          'name'=>'grid_num_rows',
          'caption'=>'Number of rows displayed in grid',
          'description'=>'Number of rows display on each page of the grid.',
          'type'=>'int',
          'default' => 10,
          'group' => 'User Interface'
        ),
        array(
          'name'=>'save_button_below_all_pages',
          'caption'=>'Submit button below all pages?',
          'description'=>'Should the submit button be present below all the pages (checked), or should it be only on the last page (unchecked)? '.
              'Only applies to the Tabs interface style.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name'=>'spatial_systems',
          'caption'=>'Allowed Spatial Ref Systems',
          'description'=>'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code number such as 4326). '.
              'Set to "default" to use the settings defined in the IForm Settings page.',
          'type'=>'string',
          'default' => 'default',
          'group'=>'Other Map Settings'
        ),
      )
    );
    return $retVal;
  }

    /**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args, $node) {
    self::$node = $node;
    self::$called_class = 'iform_' . $node->iform;
    
    // Convert parameter, defaults, into structured array
    self::parse_defaults($args);
    // Supply parameters that may be missing after form upgrade
    $args = call_user_func(array(self::$called_class, 'getArgDefaults'), $args);
    
    // 
    if (method_exists(self::$called_class, 'enforcePermissions')){
      if(call_user_func(array(self::$called_class, 'enforcePermissions')) && !user_access('IForm n'.$node->nid.' admin') && !user_access('IForm n'.$node->nid.' user')){
        return lang::get('LANG_no_permissions');
      }
    }
    // Get authorisation tokens to update and read from the Warehouse.
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    self::$auth = $auth;
    
    // Determine how the form was requested and therefore what to output
    $mode = call_user_func(array(self::$called_class, 'getMode'), $args, $node);
    self::$mode = $mode;

    if($mode ==  MODE_GRID) {
      // Output a grid of existing records
      $r = call_user_func(array(self::$called_class, 'getGrid'), $args, $node, $auth);
    } else {
      if ($mode == MODE_EXISTING && is_null(data_entry_helper::$entity_to_load)) { 
        // only load if not in error situation. 
        call_user_func_array(array(self::$called_class, 'getEntity'), array(&$args, $auth));
      }
      // attributes must be fetched after the entity to load is filled in - this is because the id gets filled in then!
      $attributes = call_user_func(array(self::$called_class, 'getAttributes'), $args, $auth);
      $r = call_user_func(array(self::$called_class, 'get_form_html'), $args, $auth, $attributes);      
    }
    return $r;
  }
  
  protected static function get_form_html($args, $auth, $attributes) {
    // Make sure the form action points back to this page
    $reloadPath = call_user_func(array(self::$called_class, 'getReloadPath'));    
    $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\">\n";

    // Get authorisation tokens to update the Warehouse, plus any other hidden data.
    $hiddens = $auth['write'].
          "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n".
          "<input type=\"hidden\" id=\"survey_id\" name=\"survey_id\" value=\"".$args['survey_id']."\" />\n";
    $hiddens .= call_user_func(array(self::$called_class, 'getHidden'), $args);
    $hiddens .= get_user_profile_hidden_inputs($attributes, $args, isset(data_entry_helper::$entity_to_load['sample:id']), $auth['read']);

    // request automatic JS validation
    if (!isset($args['clientSideValidation']) || $args['clientSideValidation'])
      data_entry_helper::enable_validation('entry_form');

    $customAttributeTabs = get_attribute_tabs($attributes);
    $tabs = self::get_all_tabs($args['structure'], $customAttributeTabs);
    $r .= "<div id=\"controls\">\n";
    // Build a list of the tabs that actually have content
    $tabHtml = self::get_tab_html($tabs, $auth, $args, $attributes, $hiddens);
    // Output the dynamic tab headers
    if ($args['interface']!='one_page') {
      $headerOptions = array('tabs'=>array());
      foreach ($tabHtml as $tab=>$tabContent) {
        $alias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
        $tabtitle = lang::get("LANG_Tab_$alias");
        if ($tabtitle=="LANG_Tab_$alias") {
          // if no translation provided, we'll just use the standard heading
          $tabtitle = $tab;
        }
        $headerOptions['tabs']['#'.$alias] = $tabtitle;        
      }
      $r .= data_entry_helper::tab_header($headerOptions);
      data_entry_helper::enable_tabs(array(
          'divId'=>'controls',
          'style'=>$args['interface'],
          'progressBar' => isset($args['tabProgress']) && $args['tabProgress']==true
      ));
    }
    
    // Output the dynamic tab content
    $pageIdx = 0;
    foreach ($tabHtml as $tab=>$tabContent) {
      // get a machine readable alias for the heading
      $tabalias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
      $r .= '<div id="'.$tabalias.'">'."\n";
      // For wizard include the tab title as a header.
      if ($args['interface']=='wizard') {
        $r .= '<h1>'.$headerOptions['tabs']['#'.$tabalias].'</h1>';        
      }
      $r .= $tabContent;    
      if (isset($args['verification_panel']) && $args['verification_panel'] && $pageIdx==count($tabHtml)-1)
        $r .= data_entry_helper::verification_panel(array('readAuth'=>$auth['read'], 'panelOnly'=>true));
      // Add any buttons required at the bottom of the tab   
      if ($args['interface']=='wizard') {
        $r .= data_entry_helper::wizard_buttons(array(
          'divId'=>'controls',
          'page'=>$pageIdx===0 ? 'first' : (($pageIdx==count($tabHtml)-1) ? 'last' : 'middle'),
          'includeVerifyButton'=>isset($args['verification_panel']) && $args['verification_panel'] 
              && ($pageIdx==count($tabHtml)-1)
        ));        
      } elseif ($pageIdx==count($tabHtml)-1) {
        // We need the verify button as well if this option is enabled
        if (isset($args['verification_panel']) && $args['verification_panel'])
          $r .= '<button type="button" class="indicia-button" id="verify-btn">'.lang::get('Precheck my records')."</button>\n";
        if (!($args['interface']=='tabs' && $args['save_button_below_all_pages'])) 
          // last part of a non wizard interface must insert a save button, unless it is tabbed 
          // interface with save button beneath all pages
          $r .= '<input type="submit" class="indicia-button" id="save-button" value="'.lang::get('Submit')."\" />\n";    
      }
      $pageIdx++;
      $r .= "</div>\n";      
    }
    $r .= "</div>\n";
    // add a single submit button outside the tabs if they want a button visible all the time
    if ($args['interface']=='tabs' && $args['save_button_below_all_pages']) 
      $r .= "<input type=\"submit\" class=\"indicia-button\" id=\"save-button\" value=\"".lang::get('Submit')."\" />\n";
    if(!empty(data_entry_helper::$validation_errors)){
      $r .= data_entry_helper::dump_remaining_errors();
    }   
    $r .= "</form>";
    
    if (method_exists(self::$called_class, 'link_species_popups')) $r .= call_user_func(array(self::$called_class, 'link_species_popups'), $args);
    return $r;    
  }

  protected static function getReloadPath () {
    $reload = data_entry_helper::get_reload_link_parts();
    unset($reload['params']['sample_id']);
    unset($reload['params']['occurrence_id']);
    unset($reload['params']['location_id']);
    unset($reload['params']['new']);
    unset($reload['params']['newLocation']);
    $reloadPath = $reload['path'];
    if(count($reload['params'])) {
      $params=array();
      foreach ($reload['params'] as $key => $param)
        // This is deliberately not re-encoded, as encoding causes Drupal ?q=a/b to appear as ?q=a%2Fb which means the form can't be re-submit.
        $params[] = "$key=$param";
      $reloadPath .= '?'.implode('&', $params);
    }
    return $reloadPath;
  }
  
  protected static function get_tab_html($tabs, $auth, $args, $attributes, $hiddens) {
    $defAttrOptions = array('extraParams'=>$auth['read']);
    if(isset($args['attribute_termlist_language_filter']) && $args['attribute_termlist_language_filter'])
        $defAttrOptions['language'] = iform_lang_iso_639_2($args['language']);

    //create array of attribute field names to test against later
    $attribNames = array();
    foreach ($attributes as $key => $attrib){
      $attribNames[$key] = $attrib['id'];
    }
    $tabHtml = array();
    foreach ($tabs as $tab=>$tabContent) {
      $columnsOpen=false;
      // keep track on if the tab actually has real content, so we can avoid floating instructions if all the controls 
      // were removed by user profile integration for example.
      $hasControls = false;
      // get a machine readable alias for the heading
      $tabalias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
      $html = '';
      if (count($tabHtml)===0)
        // output the hidden inputs on the first tab
        $html .= $hiddens;
      // Now output the content of the tab. Use a for loop, not each, so we can treat several rows as one object
      for ($i = 0; $i < count($tabContent); $i++) {
        $component = trim($tabContent[$i]);
        if (preg_match('/\A\?[^�]*\?\z/', $component) === 1) {          
          // Component surrounded by ? so represents a help text
          $helpText = substr($component, 1, -1);
          $html .= '<div class="page-notice ui-state-highlight ui-corner-all">'.lang::get($helpText)."</div>";
        } elseif (preg_match('/\A\[[^�]*\]\z/', $component) === 1) {
          // Component surrounded by [] so represents a control or control block
          $method = 'get_control_'.preg_replace('/[^a-zA-Z0-9]/', '', strtolower($component));
          // Anything following the component that starts with @ is an option to pass to the control
          $options = array();
          while ($i < count($tabContent)-1 && substr($tabContent[$i+1],0,1)=='@' || trim($tabContent[$i])==='') {
            $i++;
            // ignore empty lines
            if (trim($tabContent[$i])!=='') {
              $option = explode('=', substr($tabContent[$i],1), 2);
              $options[$option[0]]=json_decode($option[1], true);
              // if not json then need to use option value as it is
              if ($options[$option[0]]=='') $options[$option[0]]=$option[1];
              $options[$option[0]]=apply_user_replacements($options[$option[0]]);
            }
          }

          if (method_exists(self::$called_class, $method)) { 
            //outputs a control for which a specific output function has been written.
            $html .= call_user_func(array(self::$called_class, $method), $auth, $args, $tabalias, $options);
            $hasControls = true;
          } elseif (($attribKey = array_search(substr($component, 1, -1), $attribNames)) !== false) {
            //outputs a control for a single custom attribute where component is in the form [smpAttr:167]
            $options['extraParams'] = array_merge($defAttrOptions['extraParams'], (array)$options['extraParams']);
            //merge extraParams first so we don't loose authentication
            $options = array_merge($defAttrOptions, $options);
            $html .= data_entry_helper::outputAttribute($attributes[$attribKey], $options);
            $attributes[$attribKey]['handled'] = true;
            $hasControls = true;
          } elseif ($component === '[*]'){
            // this outputs any custom attributes that remain for this tab. The custom attributes can be configured in the 
            // settings text using something like @smpAttr:4|label=My label. The next bit of code parses these out into an 
            // array used when building the html.
            $blockOptions = array();
            foreach ($options as $option => $value) {
              // split the id of the option into the control name and option name.
              $optionId = explode('|', $option);
              if (!isset($blockOptions[$optionId[0]])) $blockOptions[$optionId[0]]=array();
              $blockOptions[$optionId[0]][$optionId[1]] = apply_user_replacements($value);
            }
            $defAttrOptions = array_merge($defAttrOptions, $options);
            $attrHtml = get_attribute_html($attributes, $args, $defAttrOptions, $tab, $blockOptions);
            if (!empty($attrHtml))
              $hasControls = true;
            $html .= $attrHtml;
          } else {         
            $html .= "The form structure includes a control called $component which is not recognised.<br/>";
            //ensure $hasControls is true so that the error message is shown
            $hasControls = true;
          }      
        } elseif ($component === '|') {
          // a splitter in the structure so put the stuff so far in a 50% width left float div, and the stuff that follows in a 50% width right float div.
          $html = '<div class="two columns"><div class="column">'.$html.'</div><div class="column">';
          // need to close the div
          $columnsOpen=true; 
        } else {
          // output anything else as is. This allow us to add html to the form structure.
          $html .= $component;
        }
      }
      // close any column layout divs. extra closure for the outer container of the columns
      if ($columnsOpen) 
        $html .= '</div></div>';  
      if (!empty($html) && $hasControls) {
        $tabHtml[$tab] = $html;
      }
    }
    return $tabHtml;
  }  

  /**
   * Finds the list of all tab names that are going to be required, either by the form
   * structure, or by custom attributes.
   */
    protected static function get_all_tabs($structure, $attrTabs) {    
    $structureArr = helper_base::explode_lines($structure);
    $structureTabs = array();
    foreach ($structureArr as $component) {
      if (preg_match('/^=[A-Za-z0-9 \-\*\?]+=$/', trim($component), $matches)===1) {
        $currentTab = substr($matches[0], 1, -1);
        $structureTabs[$currentTab] = array();
      } else {
        if (!isset($currentTab)) 
          throw new Exception('The form structure parameter must start with a tab title, e.g. =Species=');
        $structureTabs[$currentTab][] = $component;
      }
    }
    // If any additional tabs are required by attributes, add them to the position marked by a dummy tab named [*].
    // First get rid of any tabs already in the structure
    foreach ($attrTabs as $tab => $tabContent) {
      // case -insensitive check if attribute tab already in form structure
      if (in_array(strtolower($tab), array_map('strtolower', array_keys($structureTabs))))
        unset($attrTabs[$tab]);
    }
    // Now we have a list of form structure tabs, with the position of the $attrTabs marked by *. So join it all together.
    // Maybe there is a better way to do this?
    $allTabs = array();
    foreach($structureTabs as $tab => $tabContent) {
      if ($tab=='*') 
        $allTabs += $attrTabs;
      else {
        $allTabs[$tab] = $tabContent;
      }
    }
    return $allTabs;
  }

  /**
   * Convert the unstructured textarea of default values into a structured array.
   */
    protected static function parse_defaults(&$args) {
    $result=array();
    if (isset($args['defaults']))
      $result = helper_base::explode_lines_key_value_pairs($args['defaults']);     
    $args['defaults']=$result;
  }

  /** 
   * Get the spatial reference control.
   * Defaults to sample:entered_sref. Supply $options['fieldname'] for submission to other database fields.
   */
  protected static function get_control_spatialreference($auth, $args, $tabalias, $options) {
    // Build the array of spatial reference systems into a format Indicia can use.
    $systems=array();
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach($list as $system) {
      $systems[$system] = lang::get($system);
    }
    return data_entry_helper::sref_and_system(array_merge(array(
      'label' => lang::get('LANG_SRef_Label'),
      'systems' => $systems
    ), $options));
  }

  /** 
   * Get the location search control.
   */
  Protected static function get_control_placesearch($auth, $args, $tabalias, $options) {
    $georefOpts = iform_map_get_georef_options($args, $auth['read']);
    if ($georefOpts['driver']=='geoplanet' && empty(helper_config::$geoplanet_api_key)) {
      // can't use place search without the driver API key
      return 'The form structure includes a [place search] control but needs a geoplanet api key.<br/>';
    }
    return data_entry_helper::georeference_lookup(array_merge(
      $georefOpts,
      $options
    ));
  }

}