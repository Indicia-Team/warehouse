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
 * Link in other required php files.
 */
require_once('lang.php');
require_once('helper_config.php');
require_once('helper_base.php');

/**
 * A class with helper methods for handling prebuilt forms and generating complete parameters entry forms from 
 * simple input arrays.
 * @package Client
 */
class form_helper extends helper_base {

  /**
   * Outputs a pair of linked selects, for picking a prebuilt form from the library. The first select is for picking a form 
   * category and the second select is populated by AJAX for picking the actual form.
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>form</b><br/>
   * Optional. The name of the form to select as a default value.</li>
   * <li><b>includeOutputDiv</b><br/>
   * Set to true to generate a div after the controls which will receive the form parameter
   * controls when a form is selected.</li>
   * <li><b>needWebsiteInputs</b><br/>
   * Defaults to false. In this state, the website ID and password controls are not displayed
   * when both the values are already specified, though hidden inputs are put into the form.
   * When set to true, the website ID and password input controls are always included in the form output.
   * </li>
   * </ul>
   */
  public static function prebuilt_form_picker($options) {
    require_once('data_entry_helper.php');
    form_helper::add_resource('jquery_ui');
    $path = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . self::relative_client_helper_path();
    $r = '';
    if (!$dir = opendir($path.'prebuilt_forms/'))
      throw new Exception('Cannot open path to prebuilt form library.');
    while (false !== ($file = readdir($dir))) {
      $parts=explode('.', $file);
      if ($file != "." && $file != ".." && strtolower($parts[count($parts)-1])=='php') {
        require_once $path.'prebuilt_forms/'.$file;
        $file_tokens=explode('.', $file);
        ob_start();
        if (is_callable(array('iform_'.$file_tokens[0], 'get_'.$file_tokens[0].'_definition'))) {
          $definition = call_user_func(array('iform_'.$file_tokens[0], 'get_'.$file_tokens[0].'_definition'));
          $definition['title'] = lang::get($definition['title']);
          $forms[$definition['category']][$file_tokens[0]] = $definition;
          if (isset($options['form']) && $file_tokens[0]==$options['form']) 
            $defaultCategory = $definition['category'];
        } elseif (is_callable(array('iform_'.$file_tokens[0], 'get_title'))) {
          $title = call_user_func(array('iform_'.$file_tokens[0], 'get_title'));
          $forms['Miscellaneous'][$file_tokens[0]] = array('title' => $title);
          if (isset($options['form']) && $file_tokens[0]==$options['form'])
            $defaultCategory = 'Miscellaneous';
        }
        ob_end_clean();
      }
    }
    if (isset($defaultCategory)) {
      $availableForms = array();
      foreach ($forms[$defaultCategory] as $form=>$def) 
        $availableForms[$form] = $def['title'];
    } else {
      $defaultCategory = '';
      $availableForms = array('' => '<Please select a category first>');
    }
    closedir($dir);
    // makes an assoc array from the categories.
    $categories = array_merge(
      array('' => '<Please select>'),
      array_combine(array_keys($forms), array_keys($forms))
    );
    // translate categories
    foreach ($categories as $key=>&$value) {
      $value = lang::get($value);
    }
    asort($categories);
    if (isset($options['needWebsiteInputs']) && !$options['needWebsiteInputs']
        && !empty($options['website_id']) && !empty($options['password'])) {
      $r .= '<input type="hidden" id="website_id" name="website_id" value="'.$options['website_id'].'"/>';
      $r .= '<input type="hidden" id="password" name="password" value="'.$options['password'].'"/>';
    } else {
      $r .= data_entry_helper::text_input(array(
        'label' => lang::get('Website ID'),
        'fieldname' => 'website_id',
        'helpText' => lang::get('Enter the ID of the website record on the Warehouse you are using.'),
        'default' => isset($options['website_id']) ? $options['website_id'] : ''
      ));
      $r .= data_entry_helper::text_input(array(
        'label' => lang::get('Password'),
        'fieldname' => 'password',
        'helpText' => lang::get('Enter the password for the website record on the Warehouse you are using.'),
        'default' => isset($options['password']) ? $options['password'] : ''
      ));
    }
    $r .= data_entry_helper::select(array(
      'id' => 'form-category-picker',
      'label' => lang::get('Select Form Category'),
      'helpText' => lang::get('Select the form category pick a form from.'),
      'lookupValues' => $categories, 
      'default' => $defaultCategory
    ));
    
    $r .= data_entry_helper::select(array(
      'id' => 'form-picker',
      'fieldname' => 'iform',
      'label' => lang::get('Select Form'),
      'helpText' => lang::get('Select the Indicia form you want to use.'),
      'lookupValues' => $availableForms,
      'default' => isset($options['form']) ? $options['form'] : ''
    ));
    // div for the form instructions
    $details = '';
    if (isset($options['form'])) {
      if (isset($forms[$defaultCategory][$options['form']]['description'])) {
        $details .= '<p>'.$forms[$defaultCategory][$options['form']]['description'].'</p>';
      }
      if (isset($forms[$defaultCategory][$options['form']]['helpLink'])) {
        $details .= '<p><a href="'.$forms[$defaultCategory][$options['form']]['helpLink'].'">Find out more...</a></p>';
      }
      if ($details!=='') $details = "<div class=\"ui-state-highlight ui-corner-all page-notice\">$details</div>";
    }
    $r .= "<div id=\"form-def\">$details</div>\n";
    $r .= '<input type="button" value="'.lang::get('Load Settings Form').'" id="load-params" disabled="disabled" />';
    if (isset($options['includeOutputDivs']) && $options['includeOutputDivs']) {
      $r .= '<div id="form-params"></div>';
    }
    self::add_form_picker_js($forms);
    return $r;
  }
  
  /**
   * Adds the JavaScript required to drive the prebuilt form picker.
   * @param array $forms List of prebuilt forms and their associated settings required 
   * by the picker.
   */
  private static function add_form_picker_js($forms) {
    self::$javascript .= "var prebuilt_forms = ".json_encode($forms).";

$('#form-category-picker').change(function(evt) {
  var opts = '<option value=\"\">".lang::get('&lt;Please select&gt;')."</option>';
  $.each(prebuilt_forms[evt.currentTarget.value], function(form, def) {
    opts += '<option value=\"'+form+'\">'+def.title+'</option>';
  });
  $('#form-picker').html(opts);
  $('#form-picker').change();
});

$('#form-picker').change(function() {
  var details='', def;
  $('#load-params').attr('disabled', false);
  $('#form-params').html('');
  if ($('#form-picker').val()!=='') {
    def = prebuilt_forms[$('#form-category-picker').val()][$('#form-picker').val()];
    if (typeof def.description !== 'undefined') {
      details += '<p>'+def.description+'</p>';
    }
    if (typeof def.helpLink !== 'undefined') {
      details += '<p><a href=\"'+def.helpLink+'\" target=\"_blank\">".lang::get('Find out more...')."</a></p>';
    }
    if (details!=='') {
      details = '<div class=\"ui-state-highlight ui-corner-all page-notice\">' + details + '</div>';
    }
  }
  $('#form-def').hide().html(details).fadeIn();
});

$('#load-params').click(function() {
  if ($('#form-picker').val()==='' || $('#website_id').val()==='' || $('#form-picker').val()==='') {
    alert('".lang::get('Please specify a website ID, password and select a form before proceeding.')."');
  } else {
    if (typeof prebuilt_forms[$('#form-category-picker').val()][$('#form-picker').val()] !== \"undefined\") {
      // now use an Ajax request to get the form params
      $.post(
        '".self::getRootFolder() . self::client_helper_path()."prebuilt_forms_ajax.php',
        {form: $('#form-picker').val(),
            website_id: $('#website_id').val(),
            password: $('#password').val(),
            base_url: '".self::$base_url."'},
        function(data) {
          $('#form-params').hide().html(data).fadeIn();
          Drupal.attachBehaviors();
        }
      );
    } else {
      $('#form-params').hide();
    }
  }
});\n";
  }
  
  /**
   * Generates the parameters form required for configuring a prebuilt form.
   * Fieldsets are given classes which define that they are collapsible and normally initially
   * collapsed, though the css for handling this must be defined elsewhere. For Drupal usage this
   * css is normally handled by default in the template.
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>form</b>
   * Name of the form file without the .php extension, e.g. mnhnl_dynamic_1.</li>
   * <li><b>currentSettings</b>
   * Associative array of default values to load into the form controls.</li>
   * <li><b>expandFirst</b>
   * Optional. If set to true, then the first fieldset on the form is initially expanded.</li>
   * <li><b>siteSpecific</b>
   * Optional. Defaults to false. If true then only parameters marked as specific to a site
   * are loaded. Used to provide a reduced version of the params form after migrating a
   * form between sites (e.g. when installing a Drupal feature).</li>
   * </ul>
   */
  public static function prebuilt_form_params_form($options) {
    if (function_exists('hostsite_add_library') && (!defined('DRUPAL_CORE_COMPATIBILITY') || DRUPAL_CORE_COMPATIBILITY!=='7.x')) {
      hostsite_add_library('collapse');
    }
    require_once('data_entry_helper.php');
    // temporarily disable caching because performance is not as important as reflecting
    // the latest available parameters, surveys etc. in the drop downs
    $oldnocache = self::$nocache;
    if (!isset($options['siteSpecific']))
      $options['siteSpecific']=false;
    self::$nocache = true;
    $formparams = self::get_form_parameters($options['form']);
    $fieldsets = array();
    $r = '';
    foreach ($formparams as $control) {
      // skip hidden controls or non-site specific controls when displaying the reduced site specific
      // version of the form
      if ((isset($control['visible']) && !$control['visible']) ||
          ($options['siteSpecific'] && !(isset($control['siteSpecific']) && $control['siteSpecific'])))
        continue;
      $fieldset = isset($control['group']) ? $control['group'] : 'Other IForm Parameters';
      // apply default options to the control
      $ctrlOptions = array_merge(array(
        'id' => $control['fieldname'],
        'sep' => '<br/>',
        'class' => '',
        'blankText'=>'<'.lang::get('please select').'>',
        'extraParams' => array(),
        'readAuth'=>$options['readAuth']
      ), $control);
      $type = self::map_type($control);

      // current form settings will overwrite the default
      if (isset($options['currentSettings']) && isset($options['currentSettings'][$control['fieldname']]))
        $ctrlOptions['default'] = $options['currentSettings'][$control['fieldname']];

      $ctrlOptions['extraParams'] = array_merge($ctrlOptions['extraParams'], $options['readAuth']);
      // standardise the control width unless specified already in the control options
      if (strpos($ctrlOptions['class'], 'control-width')==false && $type != 'checkbox' && $type != 'report_helper::report_picker')
        $ctrlOptions['class'] .= ' control-width-6';
      if (!isset($fieldsets[$fieldset])) 
        $fieldsets[$fieldset]='';
      // form controls can specify the report helper class
      if (substr($type, 0, 15)=='report_helper::') {
        $type=substr($type, 15);
        require_once('report_helper.php');
        $fieldsets[$fieldset] .= report_helper::$type($ctrlOptions);
      } else {
        $fieldsets[$fieldset] .= data_entry_helper::$type($ctrlOptions);
      }
        
    }
    $class=(isset($options['expandFirst']) && $options['expandFirst']) ? 'collapsible' : 'collapsible collapsed';
    foreach($fieldsets as $fieldset=>$content) {
      // Drupal 7 collapsible fieldsets broken, see http://drupal.org/node/1607822
      // so we remove the class
      if (defined('DRUPAL_CORE_COMPATIBILITY') && DRUPAL_CORE_COMPATIBILITY==='7.x')
        $class='';
      $r .= "<fieldset class=\"$class\"><legend>$fieldset</legend>\n";
      $r .= $fieldsets[$fieldset];
      $r .= "\n</fieldset>\n";
      // any subsequent fieldset should be collapsed
      if (isset($options['expandFirst']) && $options['expandFirst'])
        $class .= ' collapsed';
    }
    self::$nocache = $oldnocache;
    return $r;
  }

  /**
   * Version 0.6 of Indicia converted from using a specific format for defining
   * prebuilt form parameters forms to arrays which map directly onto the options
   * for controls defined in the data entry helper. This makes the forms much more
   * powerful with built in AJAX support etc. However, old forms need to have the
   * control options mapped to the newer option names.
   * @param array $controlList List of controls as defined by the prebuilt form.
   * @return array List of modified controls.
   */
  private static function map_control_options($controlList) {
    $mappings = array(
        'name'=>'fieldname',
        'caption'=>'label',
        'options'=>'lookupValues',
        'description'=>'helpText'
    );
    foreach ($controlList as &$options) {
      foreach ($options as $option => $value) {
        if (isset($mappings[$option])) {
          $options[$mappings[$option]] = $value;
          unset($options[$option]);
        }
      }
      if (!isset($options['required']) || $options['required']===true) {
        if (!isset($options['class'])) $options['class']='';
        $options['class'] .= ' required';
        $options['suffixTemplate'] = 'requiredsuffix';
      }
    }
    return $controlList;
  }
  
  /**
   * Maps control types in simple form definition arrays (e.g. parameter forms for prebuilt forms or reports)
   * to their constituent controls.
   * @param array $control Control definition array, which includes a type entry defining the control type.
   * @return string Data_entry_helper control name.
   */
  private static function map_type($control) {
    $mapping = array(
        'textfield'=>'text_input', // in case there is any Drupal hangover code
        'string'=>'text_input',
        'int'=>'text_input',
        'float'=>'text_input',
        'smpAttr'=>'text_input',
        'occAttr'=>'text_input',
        'locAttr'=>'text_input',
        'taxAttr'=>'text_input',
        'psnAttr'=>'text_input',
        'termlist'=>'text_input',
        'boolean'=>'checkbox',
        'list'=>'checkbox_group'
      );
    return array_key_exists($control['type'], $mapping) ? $mapping[$control['type']] : $control['type'];
  }
  
  /** 
   * Retrieve the parameters for an iform. This is defined by each iform individually.
   * @param object $form The name of the form we are retrieving the parameters for.
   * @return array list of parameter definitions.
   */
  public static function get_form_parameters($form) {
    $path = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . self::relative_client_helper_path();
    require_once $path."prebuilt_forms/$form.php";
    // first some parameters that are always required to configure the website
    $params = array(
      array(
        'fieldname'=>'view_access_control',
        'label'=>'View access control',
        'helpText'=>'If ticked, then a Drupal permission is created for this form to allow you to specify which '.
            'roles are able to view the form.',
        'type'=>'checkbox',
        'required'=>false
      ),
      array(
        'fieldname'=>'permission_name',
        'label'=>'Permission name for view access control',
        'helpText'=>'If you want to use a default permission name when using view access control, leave this blank. Otherwise, specify the name of '.
            'a permission to define for accessing this form. One use of this is to create a single permission which is shared between several forms '.
            '(e.g. a permission could be called "Online Recording". Another situation where this should be used is when creating a feature for Instant Indicia '.
            'so the permission name can be consistent across sites which share this form.',
        'type'=>'text_input',
        'required'=>false
      )
    );
    // now get the specific parameters from the form
    if (!is_callable(array('iform_'.$form, 'get_parameters'))) 
      throw new Exception("Form $form does not implement the get_parameters method.");
    $formParams = self::map_control_options(call_user_func(array('iform_'.$form, 'get_parameters')));
    $params = array_merge($params, $formParams);
    // add in a standard parameter for specifying a redirection.
    $params[] = array(
      'fieldname'=>'redirect_on_success',
      'label'=>'Redirect to page after successful data entry',
      'helpText'=>'The url of the page that will be navigated to after a successful data entry. '.
          'leave blank to just display a success message on the same page so further records can be entered. if the site is internationalised, '.
          'make sure that the page you want to go to has a url specified that is the same for all language versions. also ensure your site uses '.
          'a path prefix for the language negotiation (administer > site configuration > languages > configure). then, specify the url that you attached to the node '.
          'so that the language prefix is not included.',
      'type'=>'text_input',
      'required'=>false
    );
    $params[] = array(
      'fieldname'=>'message_after_save',
      'label'=>'Display notification after save',
      'helpText'=>'After saving an input form, should a message be added to the page stating that the record has been saved? This should be left '.
          'unchecked if the page is redirected to a page that has information about the record being saved inherent in the page content. Otherwise ticking '.
          'this box can help to make it clear that a record was saved.',
      'type'=>'checkbox',
      'required'=>false,
      'default'=>true
    );
    $params[] = array(
      'fieldname'=>'additional_css',
      'label'=>'Additional CSS files to include',
      'helpText'=>'Additional CSS files to include on the page. You can use the following replacements to simplify the setting '.
          'of the correct paths. {mediacss} is replaced by the media/css folder in the module. {theme} is replaced by the '.
          'current theme folder. {prebuiltformcss} is replaced by the prebuilt_forms/css folder. Specify one CSS file per '.
          'line.',
      'type'=>'textarea',
      'required'=>false
    );
    $params[] = array(
      'fieldname'=>'additional_templates',
      'label'=>'Additional template files to include',
      'helpText'=>'Additional templates files to include on the page. You can use the following replacements to simplify the setting '.
          'of the correct paths. {prebuiltformtemplates} is replaced by the prebuilt_forms/templates folder. Specify one template file per '.
          'line. The structure of template files is described <a target="_blank" href="http://indicia-docs.readthedocs.org/en/latest/site-building/' .
          'iform/customising-page-functionality.html#overridding-the-html-templates-used-to-output-the-input-controls">in the documentation</a>.',
      'type'=>'textarea',
      'required'=>false
    );
    // allow the user ui options module to add it's own param. This could probably be refactored as a proper Drupal hook...
    if (function_exists('iform_user_ui_options_additional_params'))
      $params = array_merge($params, iform_user_ui_options_additional_params());
    return $params;
  }

}
 
 ?>