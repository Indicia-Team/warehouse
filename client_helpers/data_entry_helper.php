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
require_once('submission_builder.php');
require_once("libcurlEmulator/libcurlemu.inc.php");

global $indicia_templates;

/**
 * Provides control templates to define the output of the data entry helper class.
 *
 * @package	Client
 */
$indicia_templates = array(
  'prefix' => '',
  'label' => '<label for="{id}"{labelClass}>{label}:</label>'."\n",
  'suffix' => "<br/>\n",
  'nosuffix' => " \n",
  'requiredsuffix' => '<span class="deh-required">*</span><br/>'."\n",
  'validation_message' => '<label for="{for}" class="{class}">{error}</label>'."\n",
  'validation_icon' => '<span class="ui-state-error ui-corner-all validation-icon">'.
      '<span class="ui-icon ui-icon-alert"></span></span>',
  'error_class' => 'inline-error',
  'image_upload' => '<input type="file" id="{id}" name="{fieldname}" accept="png|jpg|gif|jpeg" {title}/>'."\n".
      '<input type="hidden" id="{pathFieldName}" name="{pathFieldName}" value="{pathFieldValue}"/>'."\n",
  'text_input' => '<input type="text" id="{id}" name="{fieldname}"{class} {disabled} value="{default}" {title} />'."\n",
  'textarea' => '<textarea id="{id}" name="{fieldname}"{class} {disabled} cols="{cols}" rows="{rows}" {title}>{default}</textarea>'."\n",
  'checkbox' => '<input type="hidden" name="{fieldname}" value="0"/><input type="checkbox" id="{id}" name="{fieldname}" value="1"{class}{checked}{disabled} {title} />'."\n",
  'date_picker' => '<input type="text" size="30"{class} id="{id}" name="{fieldname}" value="{default}" {title}/>',
  'select' => '<select id="{id}" name="{fieldname}"{class} {disabled} {title}>{items}</select>',
  'select_item' => '<option value="{value}" {selected} >{caption}</option>',
  'select_species' => '<option value="{value}" {selected} >{caption} - {common}</option>',
  'listbox' => '<select id="{id}" name="{fieldname}"{class} {disabled} size="{size}" multiple="{multiple}" {title}>{options}</select>',
  'listbox_item' => '<option value="{value}" {selected} >{caption}</option>',
  'list_in_template' => '<ul{class} {title}>{items}</ul>',
  'check_or_radio_group' => '<div{class}>{items}</div>',
  'check_or_radio_group_item' => '<nobr><span><input type="{type}" name="{fieldname}" id="{itemId}" value="{value}"{class}{checked} {disabled}/><label for="{itemId}">{caption}</label></span></nobr>{sep}',
  'check_or_radio_group_container' => '<div {class} >{group}</div>',
  'map_panel' => "<script type=\"text/javascript\">\n/* <![CDATA[ */\n".
    "document.write('<div id=\"{divId}\" style=\"width: {width}; height: {height};\"{class}></div>');\n".
    "/* ]]> */</script>",
  'georeference_lookup' => "<script type=\"text/javascript\">\n/* <![CDATA[ */\n".
    "document.write('<input id=\"imp-georef-search\"{class} />');\n".
    "document.write('<input type=\"button\" id=\"imp-georef-search-btn\" class=\"ui-corner-all ui-widget-content ui-state-default indicia-button\" value=\"{search}\" />');\n".
    "document.write('<div id=\"imp-georef-div\" class=\"ui-corner-all ui-widget-content ui-helper-hidden\">');\n".
    "document.write('  <div id=\"imp-georef-output-div\">');\n".
    "document.write('  </div>');\n".
    "document.write('  <a class=\"ui-corner-all ui-widget-content ui-state-default indicia-button\" href=\"#\" id=\"imp-georef-close-btn\">{close}</a>');\n".
    "document.write('</div>');".
    "\n/* ]]> */</script>",
  'tab_header' => '<script type="text/javascript">/* <![CDATA[ */'."\n".
      'document.write(\'<ul class="ui-helper-hidden">{tabs}</ul>\');'.
      "\n/* ]]> */</script>\n".
      "<noscript><ul>{tabs}</ul></noscript>\n",
  'tab_next_button' => "<script type=\"text/javascript\">\n/* <![CDATA[ */\n".
    "document.write('<div{class}>');\n".
    "document.write('  <span>{captionNext}</span>');\n".
    "document.write('  <span class=\"ui-icon ui-icon-circle-arrow-e\"></span>');\n".
    "document.write('</div>');\n".
    "/* ]]> */</script>\n",
  'tab_prev_button' => "<script type=\"text/javascript\">\n/* <![CDATA[ */\n".
    "document.write('<div{class}>');\n".
    "document.write('  <span class=\"ui-icon ui-icon-circle-arrow-w\"></span>');\n".
    "document.write('  <span>{captionPrev}</span>');\n".
    "document.write('</div>');\n".
    "/* ]]> */</script>\n",
  'submit_button' => "<script type=\"text/javascript\">\n/* <![CDATA[ */\n".
    "document.write('<div{class}>');\n".
    "document.write('  <span>{captionSave}</span>');\n".
    "document.write('</div>');\n".
    "/* ]]> */</script>\n".
    "<noscript><input type=\"submit\" value=\"{captionSave}\" /></noscript>\n",
  'loading_block_start' => "<script type=\"text/javascript\">\n/* <![CDATA[ */\n".
      'document.write(\'<div class="ui-widget ui-widget-content ui-corner-all loading-panel" >'.
      '<img src="'.helper_config::$base_url.'media/images/ajax-loader2.gif" />'.
      lang::get('loading')."...</div>');\n".
      'document.write(\'<div class="loading-hide">\');'.
      "\n/* ]]> */</script>\n",
  'loading_block_end' => "<script type=\"text/javascript\">\n/* <![CDATA[ */\n".
      "document.write('</div>');\n".
      "/* ]]> */</script>",
  'taxon_label' => '<div class="biota"><span class="nobreak sci binomial"><em>{taxon}</em></span> {authority} '.
      '<span class="nobreak vernacular">{common}</span></div>',
  'treeview_node' => '<span>{caption}</span>',
  'tree_browser' => '<div{outerClass} id="{divId}"></div><input type="hidden" name="{fieldname}" id="{id}" value="{default}"{class}/>',
  'tree_browser_node' => '<span>{caption}</span>',
  'autocomplete' => '<input type="hidden" class="hidden" id="{id}" name="{fieldname}" value="{default}" />'."\n".
      '<input id="{inputId}" name="{inputId}" value="{defaultCaption}" {class} {disabled} {title}/>'."\n",
  'autocomplete_javascript' => "jQuery('input#{escaped_input_id}').autocomplete('{url}/{table}',
      {
        extraParams : {
          orderby : '{captionField}',
          mode : 'json',
          qfield : '{captionField}',
          {sParams}
        },
        parse: function(data)
        {
          // Clear the current selected key as the user has changed the search text
          jQuery('input#{escaped_id}').val('');
          var results = [];
          jQuery.each(data, function(i, item) {
            results[results.length] =
            {
              'data' : item,
              'result' : item.{captionField},
              'value' : item.{valueField}
            };
          });
          return results;
        },
      formatItem: function(item)
      {
        return item.{captionField};
      }
    });
    jQuery('input#{escaped_input_id}').result(function(event, data) {
      jQuery('input#{escaped_id}').attr('value', data.id);
    });\r\n",
  'linked_list_javascript' => "
{fn} = function() {
  $('#{escapedId}').addClass('ui-state-disabled');
  $('#{escapedId}').html('<option>Loading...</option>');
  $.getJSON('{request}&{filterField}='+$(this).val(), function(data){
    $('#{escapedId}').html('');
    $('#{escapedId}').removeClass('ui-state-disabled');
    $.each(data, function(i) {
      $('#{escapedId}').append('<option value=\"'+this.{valueField}+'\">'+this.{captionField}+'</option>');
    });
  });
}
jQuery('#{parentControlId}').change({fn});
jQuery('#{parentControlId}').change();\n",
  'postcode_textbox' => '<input type="text" name="{fieldname}" id="{id}"{class} value="{default}" '.
        'onblur="javascript:decodePostcode(\'{linkedAddressBoxId}\');" />',
  'sref_textbox' => '<input type="text" id="{id}" name="{fieldname}" {class} {disabled} value="{default}" />' .
        '<input type="hidden" id="imp-geom" name="{table}:geom" value="{defaultGeom}" />',
  'sref_textbox_latlong' => '<label for="{idLat}">{labelLat}:</label>'.
        '<input type="text" id="{idLat}" name="{fieldnameLat}" {class} {disabled} value="{default}" /><br />' .
        '<label for="{idLong}">{labelLong}:</label>'.
        '<input type="text" id="{idLong}" name="{fieldnameLong}" {class} {disabled} value="{default}" />' .
        '<input type="hidden" id="imp-geom" name="{table}:geom" value="{defaultGeom}" />'.
        '<input type="text" id="{id}" name="{fieldname}" style="display:none" value="{default}" />',
  'attribute_cell' => "\n<td class=\"scOccAttrCell ui-widget-content {class}\">{content}</td>",
  'taxon_label_cell' => "\n<td class=\"scTaxonCell ui-state-default\"{colspan}>{content}</td>",
  'helpText' => "\n<p class=\"helpText\">{helpText}</p>",
  'button' => '<div class="indicia-button ui-state-default ui-corner-all" id="{id}"><span>{caption}</span></div>',
  'file_box' => '',                   // the JQuery plugin default will apply, this is just a placeholder for template overrides.
  'file_box_initial_file_info' => '', // the JQuery plugin default will apply, this is just a placeholder for template overrides.
  'file_box_uploaded_image' => ''     // the JQuery plugin default will apply, this is just a placeholder for template overrides.
);


/**
 * Static helper class that provides automatic HTML and JavaScript generation for Indicia online
 * recording website data entry controls.
 * Examples include auto-complete text boxes that are populated by Indicia species lists, maps
 * for spatial reference selection and date pickers. All controls in this class support the following
 * entries in their $options array parameter:
 * <ul>
 * <li><b>label</b><br/>
 * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
 * <li><b>helpText</b><br/>
 * Optional. Defines help text to be displayed alongside the control. The position of the text is defined by
 * data_entry_helper::$helpTextPos, which can be set to before or after (default). The template is defined by
 * global $indicia_templates['helpText'] and can be replaced on an instance by instance basis by specifying an
 * option 'helpTextTemplate' for the control.
 * <li><b>helpTextTemplate</b>
 * If helpText is supplied but you need to change the template for this control only, set this to refer to the name of an
 * alternate template you have added to the $indicia_templates array. The template should contain a {helpText} replacement
 * string.</li>
 * <li><b>prefixTemplate</b>
 * If you need to change the prefix for this control only, set this to refer to the name of an alternate template you
 * have added to the global $indicia_templates array. To change the prefix for all controls, you can update the value of
 * $indicia_templates['prefix'] before building the form.</li>
 * <li><b>suffixTemplate</b>
 * If you need to change the suffix for this control only, set this to refer to the name of an alternate template you
 * have added to the global $indicia_templates array. To change the suffix for all controls, you can update the value of
 * $indicia_templates['suffix'] before building the form.</li>
 * </ul>
 *
 * @package	Client
 */
class data_entry_helper extends helper_config {

  /**
   * @var array When reloading a form, this can be populated with the list of values to load into the controls. E.g. set it to the
   * content of $_POST after submitting a form that needs to reload.
   */
  public static $entity_to_load=null;

  /**
   * @var array List of methods used to report a validation failure. Options are
   * message, message, hint, icon, colour, inline.
   * The inline option specifies that the message should appear on the same line as the control.
   * Otherwise it goes on the next line, indented by the label width. Because in many cases, controls
   * on an Indicia form occupy the full available width, it is often more appropriate to place error
   * messages on the next line so this is the default behaviour.
   */
  public static $validation_mode=array('message', 'colour');

  /**
   * @var string Helptext positioning. Determines where the information is displayed when helpText is defined for a control.
   * Options are before, after.
   */
  public static $helpTextPos='after';

  /**
   * @var string JavaScript text to be emitted after the data entry form. Each control that
   * needs custom JavaScript can append the script to this variable.
   */
  public static $javascript = '';

  /**
   * @var string JavaScript text to be emitted after the data entry form and all other JavaScript.
   */
  public static $late_javascript = '';

  /**
   * @var string JavaScript text to be emitted during window.onload.
   */
  public static $onload_javascript = '';

  /**
   * @var string Path to Indicia JavaScript folder. If not specified, then it is calculated from the Warehouse $base_url.
   */
  public static $js_path = null;

  /**
   * @var string Path to Indicia CSS folder. If not specified, then it is calculated from the Warehouse $base_url.
   */
  public static $css_path = null;

  /**
   * @var Array List of all available resources known. Each resource is named, and contains a sub array of
   * deps (dependencies), stylesheets and javascripts.
   */
  private static $resource_list=null;

  /**
   * @var Array List of resources that have been identified as required by the controls used. This defines the
   * JavaScript and stylesheets that must be added to the page. Each entry is an array containing stylesheets and javascript
   * sub-arrays. This has public access so the Drupal module can perform Drupal specific resource output.
   */
  public static $required_resources=array();

  /**
   * @var array List of resources that have already been dumped out, so we don't duplicate them.
   */
  private static $dumped_resources=array();

  /**
   * @var array List of all error messages returned from an attempt to save.
   */
  public static $validation_errors=null;

  /**
   * @var integer Length of time in seconds after which cached Warehouse responses will start to expire.
   */
  public static $cache_timeout=3600;

  /**
   * @var integer On average, every 1 in $cache_chance_expire times the Warehouse is called for data which is
   * cached but older than the cache timeout, the cached data will be refreshed. This introduces a random element to
   * cache refreshes so that no single form load event is responsible for refreshing all cached content.
   */
  public static $cache_chance_refresh_file=5;

  /**
   * @var integer On average, every 1 in $cache_chance_purge times the Warehouse is called for data, all files
   * older than 5 times the cache_timeout will be purged, apart from the most recent $cache_allowed_file_count files.
   */
  public static $cache_chance_purge=100;

  /**
   * @var integer Number of recent files allowed in the cache which the cache will not bother clearing during a deletion operation.
   * They will be refreshed occasionally when requested anyway.
   */
  public static $cache_allowed_file_count=50;

  /**
   * @var integer On average, every 1 in $interim_image_chance_purge times the Warehouse is called for data, all interim images
   * older than $interim_image_expiry seconds will be deleted. These are images that should have uploaded to the warehouse but the form was not
   * finally submitted.
   */
  public static $interim_image_chance_purge=100;

  /**
   * @var integer On average, every 1 in $cache_chance_expire times the Warehouse is called for data which is
   */
  public static $interim_image_expiry=14400;

  /**
   * @var array List of error messages that have been displayed, so we don't duplicate them when dumping any
   * remaining ones at the end.
   */
  private static $displayed_errors=array();

  /**
   * @var array Website ID, stored here to assist with caching.
   */
  private static $website_id = null;

  /**
   * @var array Name of the form which has been set up for jQuery validation, if any.
   */
  public static $validated_form_id = null;

  /**
   * @var array List of messages defined to pass to the validation plugin.
   */
  public static $validation_messages = array();

  /**
   * @var Array of default validation rules to apply to the controls on the form if the
   * built in client side validation is used (with the jQuery validation plugin). This array
   * can be replaced if required.
   * @todo This array could be auto-populated with validation rules for a survey's fields from the
   * Warehouse.
   */
  public static $default_validation_rules = array(
    'sample:date'=>array('required', 'dateISO'),
    'sample:entered_sref'=>array('required'),
    'occurrence:taxa_taxon_list_id'=>array('required')
  );

  /**
   * Array of html attributes. When replacing items in a template, these get automatically wrapped. E.g.
   * a template replacement for the class will be converted to class="value". The key is the parameter name,
   * and the value is the html attribute it will be wrapped into.
   */
  private static $html_attributes = array(
    'class' => 'class',
    'outerClass' => 'class',
    'selected' => 'selected'
  );

  /**
   * @var Boolean Are we linking in the default stylesheet? Handled sligtly different to the others so it can be added to the end of the
   * list, allowing our CSS to override other stuff.
   */
  private static $default_styles = false;

  /**
   * @var Array List of fields that are to be stored in a cookie and reloaded the next time a form is accessed. These
   * are populated by implementing a hook function called indicia_define_remembered_fields which calls set_remembered_fields.
   */
  private static $remembered_fields=null;

/**********************************/
/* Start of main controls section */
/**********************************/

 /**
  * Helper function to generate an autocomplete box from an Indicia core service query.
  * Because this generates a hidden ID control as well as a text input control, if you are outputting your own HTML label
  * then the label you associate with this control should be of the form "$id:$caption" rather than just the $id which
  * is normal for other controls. For example:
  * <code>
  * <label for='occurrence:taxa_taxon_list_id:taxon'>Taxon:</label>
  * <?php echo data_entry_helper::autocomplete(array(
  *     'fieldname' => 'occurrence:taxa_taxon_list_id', 
  *     'table' => 'taxa_taxon_list', 
  *     'captionField' => 'taxon', 
  *     'valueField' => 'id', 
  *     'extraParams' => $readAuth
  * )); ?>
  * </code>
  * Of course if you use the built in label option in the options array then this is handled for you.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. This should be left to its default value for
  * integration with other mapping controls to work correctly.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>table</b><br/>
  * Required. Table name to get data from for the autocomplete options.</li>
  * <li><b>captionField</b><br/>
  * Required. Field to draw values to show in the control from.</li>
  * <li><b>valueField</b><br/>
  * Optional. Field to draw values to return from the control from. Defaults
  * to the value of captionField.</li>
  * <li><b>extraParams</b><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><b>template</b><br/>
  * Optional. Name of the template entry used to build the HTML for the control. Defaults to autocomplete.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the autocomplete control.
  *
  * @link http://code.google.com/p/indicia/wiki/DataModel
  */
  public static function autocomplete() {
    global $indicia_templates;
    $options = self::check_arguments(func_get_args(), array(
        'fieldname', 'table', 'captionField', 'valueField', 'extraParams', 'defaultCaption', 'default'
    ));
    if (!array_key_exists('id', $options)) $options['id']=$options['fieldname'];
    $options['inputId'] = $options['id'].':'.$options['captionField'];
    $options = array_merge(array(
      'template' => 'autocomplete',
      'url' => parent::$base_url."index.php/services/data",
      'inputId' => $options['id'].':'.$options['captionField'],
      // Escape the ids for jQuery selectors
      'escaped_input_id' => str_replace(':', '\\\\:', $options['inputId']),
      'escaped_id' => str_replace(':', '\\\\:', $options['id']),
      'defaultCaption' => self::check_default_value($options['inputId'],
          array_key_exists('defaultCaption', $options) ? $options['defaultCaption'] : '')
    ), $options);
    self::add_resource('autocomplete');
    // Escape the id for jQuery selectors
    $escaped_id=str_replace(':','\\\\:',$options['id']);
    // Do stuff with extraParams
    $sParams = '';
    foreach ($options['extraParams'] as $a => $b){
      $sParams .= "$a : '$b',";
    }
    // lop the comma off the end
    $options['sParams'] = substr($sParams, 0, -1);
    $replaceTags=array();
    foreach(array_keys($options) as $option) {
      array_push($replaceTags, '{'.$option.'}');
    }
    $options['extraParams']=null;
    self::$javascript .= str_replace($replaceTags, $options, $indicia_templates['autocomplete_javascript']);

    $r = self::apply_template($options['template'], $options);
    return $r;
  }

 /**
  * Helper function to output an HTML checkbox control. This includes re-loading of existing values
  * and displaying of validation error messages.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>template</b><br/>
  * Optional. Name of the template entry used to build the HTML for the control. Defaults to checkbox.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the checkbox control.
  */
  public static function checkbox($options) {
    $options = self::check_options($options);
    $default = isset($options['default']) ? $options['default'] : '';
    $options['checked'] = ($default=='on' || $default == 1 || $default == '1' || $default=='t') ? ' checked="checked"' : '';
    $options['template'] = array_key_exists('template', $options) ? $options['template'] : 'checkbox';
    return self::apply_template($options['template'], $options);
  }

 /**
  * Helper function to generate a list of checkboxes from a Indicia core service query.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>table</b><br/>
  * Required. Table name to get data from for the select options.</li>
  * <li><b>captionField</b><br/>
  * Optional. Field to draw values to show in the control from. Required unless lookupValues is specified.</li>
  * <li><b>valueField</b><br/>
  * Optional. Field to draw values to return from the control from. Defaults
  * to the value of captionField. </li>
  * <li><b>extraParams</b><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><b>lookupValues</b><br/>
  * If the group is to be populated with a fixed list of values, rather than via a service call, then the
  * values can be passed into this parameter as an associated array of key=>caption.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * <li><b>template</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for the outer control.</li>
  * <li><b>itemTemplate</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for each item in the control.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the group of checkboxes.
  */
  public static function checkbox_group() {
    $options = self::check_arguments(func_get_args(), array('fieldname', 'table', 'captionField', 'valueField', 'extraParams', 'sep', 'default'));
    return self::check_or_radio_group($options, 'checkbox');
  }

 /**
  * Helper function to insert a date picker control.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to, for example 'sample:date'.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>allowFuture</b><br/>
  * Optional. If true, then future dates are allowed. Default is false.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the date picker control.
  */
  public static function date_picker() {
    $options = self::check_arguments(func_get_args(), array('fieldname', 'default'));

    self::add_resource('jquery_ui');
    $escaped_id=str_replace(':','\\\\:',$options['id']);
    self::$javascript .= "jQuery('#$escaped_id').datepicker({
  dateFormat : 'yy-mm-dd',
  constrainInput: false";
    // Filter out future dates
    if (!array_key_exists('allow_future', $options) || $options['allow_future']==false) {
      self::$javascript .= ",
  maxDate: '0'";
    }
    // If the validation plugin is running, we need to trigger it when the datepicker closes.
    if (self::$validated_form_id) {
      self::$javascript .= ",
  onClose: function() {
    $(this).valid();
  }";
    }
    self::$javascript .= "\n});\n";

    if (!array_key_exists('default', $options) || $options['default']=='') {
      $options['default']=lang::get('click here');
    }
	// Check for the special default value of today
	if (isset($options['default']) ) {
	  if ($options['default']=='today')
	    $options['default'] = date('Y-m-d');
	}

    // Enforce a class on the control called date
    if (!array_key_exists('class', $options)) {
      $options['class']='';
    }
    return self::apply_template('date_picker', $options);
  }

/**
  * Outputs a file upload control suitable for linking images to records. 
  * The control allows selection of multiple files, and depending on the browser functionality it gives progress feedback.
  * The control uses Google Gears, Flash, Silverlight, Browserplus or HTML5 to enhance the functionality
  * where available. The output of the control can be configured by changing the content of the templates called
  * file_box, file_box_initial_file_info, file_box_uploaded_image and button.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>table</b><br/>
  * Name of the image table to upload images into, e.g. occurrence_image, location_image, sample_image or taxon_image.
  * Defaults to occurrence_image.
  * </li>
  * <li><b>id</b><br/>
  * Optional. Provide a unique identifier for this image uploader control if more than one are required on the page.
  * </li>  
  * <li><b>caption</b><br/>
  * Caption to display at the top of the uploader box. Defaults to the translated string for "Files".
  * </li>
  * <li><b>uploadSelectBtnCaption</b><br/>
  * Set this to override the caption for the button for selecting files to upload.
  * </li>
  * <li><b>flickrSelectBtnCaption</b><br/>
  * Set this to override the caption for the button for selecting files from Flickr.
  * </li>
  * <li><b>uploadStartBtnCaption</b><br/>
  * Set this to override the caption for the start upload button, which is only visible if autoUpload is false.
  * </li>
  * <li><b>useFancybox</b><br/>
  * Defaults to true. If true, then image previews use the Fancybox plugin to display a "lightbox" effect when clicked on.
  * </li>
  * <li><b>imageWidth</b><br/>
  * Defaults to 200. Number of pixels wide the image previews should be.
  * </li>
  * <li><b>resizeWidth</b><br/>
  * If set, then the file will be resized before upload using this as the maximum pixels width.
  * </li>
  * <li><b>resizeHeight</b><br/>
  * If set, then the file will be resized before upload using this as the maximum pixels height.
  * </li>
  * <li><b>resizeQuality</b><br/>
  * Defines the quality of the resize operation (from 1 to 100). Has no effect unless either resizeWidth or resizeHeight are non-zero.
  * </li>
  * <li><b>upload</b><br/>
  * Boolean, defaults to true. Set to false when implementing a Flickr image control without file upload capability.
  * </li>
  * <li><b>flickr</b><br/>
  * Not implemented.
  * </li>
  * <li><b>maxFileCount</b><br/>
  * Maximum number of files to allow upload for. Defaults to 4. Set to false to allow unlimited files.
  * </li>
  * <li><b>autoupload</b><br/>
  * Defaults to true. If false, then a button is displayed which the user must click to initiate upload of the files
  * currently in the queue.
  * </li>
  * <li><b>msgUploadError</b><br/>
  * Use this to override the message displayed for a generic file upload error.
  * </li>
  * <li><b>msgFileTooBig</b><br/>
  * Use this to override the message displayed when the file is larger than the size limit allowed on the Warehouse.
  * </li>
  * <li><b>msgTooManyFiles</b><br/>
  * Use this to override the message displayed when attempting to upload more files than the maxFileCount allows. Use a
  * replacement string [0] to specify the maxFileCount value.
  * </li>
  * <li><b>uploadScript</b><br/>
  * Specify the script used to handle image uploads on the server (relative to the client_helpers folder). You should not
  * normally need to change this. Defaults to upload.php.
  * </li>
  * <li><b>runtimes</b><br/>
  * Array of runtimes that the file upload component will use in order of priority. Defaults to
  * array('silverlight','flash','html5','gears','browserplus','html4'), though flash is removed for
  * Internet Explorer 6 and html5 is removed for Chrome. You should not normally need to change this.
  * </li>
  * <li><b>destinationFolder</b><br/>
  * Override the destination folder for uploaded files. You should not normally need to change this.
  * </li>
  * <li><b>swfAndXapFolder</b><br/>
  * Override the folder which the Plupload Flash (swf) and Silverlight (xap) files are loaded from. You should not
  * normally need to change this.
  * </li>
  * <li><b>codeGenerated</b>
  * If set to all (default), then this returns the HTML required and also inserts JavaScript in the document onload event. However, if you
  * need to delay the loading of the control until a certain event, e.g. when a radio button is checked, then this can be set
  * to php to return just the php and ignore the JavaScript, or js to return the JavaScript instead of inserting it into
  * document onload, in which case the php is ignored. this allows you to attach the JavaScript to any event you need to.
  * </li>
  * <li><b>tabDiv</b><br/>
  * If loading this control onto a set of tabs, specify the tab control's div ID here. This allows the control to
  * automatically generate code which only generates the uploader when the tab is shown, reducing problems in certain
  * runtimes. This has no effect if codeGenerated is not left to the default state of all.
  * </li>
  * </ul>
  *
  * @todo select file button pointer overriden by the flash shim
  * @todo flickr
  * @todo if using a normal file input, after validation, the input needs to show that the file upload has worked.
  * @todo Cleanup uploaded files that never got submitted because of validation failure elsewhere.
  */
  public static function file_box($options) {
    global $indicia_templates;
    // Upload directory defaults to client_helpers/upload, but can be overriden.
    $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
    $relpath = self::relative_client_helper_path();
    // Allow options to be defaulted and overridden
    $defaults = array(
      'caption' => lang::get('Files'),
      'id' => 'default',
      'upload' => true,
      'maxFileCount' => 4,
      'autoupload' => false,
      'flickr' => false,
      'uploadSelectBtnCaption' => lang::get('Select file(s)'),
      'flickrSelectBtnCaption' => lang::get('Choose photo from Flickr'),
      'startUploadBtnCaption' => lang::get('Start upload'),
      'msgUploadError' => lang::get('upload error'),
      'msgFileTooBig' => lang::get('file too big for warehouse'),
      'runtimes' => array('html5','silverlight','flash','gears','browserplus','html4'),
      'autoupload' => true,
      'imagewidth' => 250,
      'uploadScript' => dirname($_SERVER['PHP_SELF']) . '/' . $relpath . 'upload.php',
      'destinationFolder' => dirname($_SERVER['PHP_SELF']) . '/' . $relpath . $interim_image_folder,
      'finalImageFolder' => self::$base_url.(isset(self::$indicia_upload_path) ? self::$indicia_upload_path : 'upload/'),
      'swfAndXapFolder' => $relpath . 'plupload/',
      'jsPath' => self::$js_path,
      'buttonTemplate' => $indicia_templates['button'],
      'table' => 'occurrence_image',
      'maxUploadSize' => self::convert_to_bytes(isset(parent::$maxUploadSize) ? parent::$maxUploadSize : '4M'),
      'codeGenerated' => 'all'
    );
    $browser = self::get_browser_info();
    // Flash doesn't seem to work on IE6.
    if ($browser['name']=='msie' && $browser['version']<7)
      $defaults['runtimes'] = array_diff($defaults['runtimes'], array('flash'));
    if ($browser['name']=='chrome')
      $defaults['runtimes'] = array_diff($defaults['runtimes'], array('html5'));
    $options['id'] = $options['table']. (isset($options['id']) ? '-'.$options['id'] : '');
    $containerId = 'container-'.$options['id'];
    if ($indicia_templates['file_box']!='')
      $defaults['file_boxTemplate'] = $indicia_templates['file_box'];
    if ($indicia_templates['file_box_initial_file_info']!='')
      $defaults['file_box_initial_file_infoTemplate'] = $indicia_templates['file_box_initial_file_info'];
    if ($indicia_templates['file_box_uploaded_image']!='')
      $defaults['file_box_uploaded_imageTemplate'] = $indicia_templates['file_box_uploaded_image'];
    $options = array_merge($defaults, $options);

    if ($options['codeGenerated']!='php') {
      // build the JavaScript including the required file links
      self::add_resource('plupload');
      foreach($options['runtimes'] as $runtime) {
        self::add_resource("plupload_$runtime");
      }
      // convert runtimes list to plupload format
      $options['runtimes'] = implode(',', $options['runtimes']);

      $javascript = "\n$('#".str_replace(':','\\\\:',$containerId)."').uploader({";
      // Just pass the options array through
      $idx = 0;
      foreach($options as $option=>$value) {
        if (is_array($value)) {
          $value = "{ " . implode(" : true, ",$value) . " : true }";
        }
        else
          // not an array, so wrap as string
          $value = "'$value'";
        $javascript .= "\n  $option : $value";
        // comma separated, except last entry
        if ($idx < count($options)-1) $javascript .= ',';
        $idx++;
      }
      // add in any reloaded items, when editing or after validation failure
      if (self::$entity_to_load) {
        $images = self::extract_image_data(self::$entity_to_load, $options['table']);
        $javascript .= ",\n  existingFiles : ".json_encode($images);
      }
      $javascript .= "\n});\n";
    }
    if ($options['codeGenerated']=='js')
      // we only want to return the JavaScript, so go no further.
      return $javascript;
    elseif ($options['codeGenerated']=='all') {
      if (isset($options['tabDiv'])) {
        // The file box is displayed on a tab, so we must only generate it when the tab is displayed.
        $javascript =
            "var tabHandler = function(event, ui) { \n".
            "  if (ui.panel.id=='".$options['tabDiv']."') {\n    ".
        $javascript.
            "    jQuery(jQuery('#".$options['tabDiv']."').parent()).unbind('tabsshow', tabHandler);\n".
            "  }\n};\n".
            "jQuery(jQuery('#".$options['tabDiv']."').parent()).bind('tabsshow', tabHandler);\n";
        // Insert this script at the beginning, because it must be done before the tabs are initialised or the 
        // first tab cannot fire the event
        self::$javascript = $javascript . self::$javascript;
      }	else
        self::$onload_javascript .= $javascript;
    }
    // Output a placeholder div for the jQuery plugin. Also output a normal file input for the noscripts
    // version.
    return '<div class="file-box" id="'.$containerId.'"></div><noscript>'.self::image_upload(array(
      'label' => $options['caption'],
	  // Convert table into a psuedo field name for the images
      'id' => $options['id'],
      'fieldname' => str_replace('_', ':', $options['table'])
    )).'</noscript>';
  }

 /**
  * Generates a text input control with a search button that looks up an entered place against a georeferencing
  * web service. The control is automatically linked to any map panel added to the page.
  *
  * @param array $options Options array with the following possibilities:
  * <ul>
  * <li><b>fieldname</b><br/>
  * Optional. The name of the database field this control is bound to if any.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>georefPreferredArea</b><br/>
  * Optional. Hint provided to the locality search service as to which area to look for the place name in. Any example usage of this
  * would be to set it to the name of a region for a survey based in that region. Note that this is only a hint, and the search
  * service may still return place names outside the region. Defaults to gb.</li>
  * <li><b>georefCountry</b><br/>
  * Optional. Hint provided to the locality search service as to which country to look for the place name in. Defaults to United Kingdom.</li>
  * <li><b>georefLang</b><br/>
  * Optional. Language to request place names in. Defaults to en-EN for English place names.</li>
  * <li><b>driver</b><br/>
  * Optional. Driver to use for the georeferencing operation. Supported options are:<br/>
  *   geoplanet - uses the Yahoo! GeoPlanet place search. This is the default.<br/>
  *   google_search_api - uses the Google AJAX API LocalSearch service. This method requires both a
  *       georefPreferredArea and georefCountry to work correctly.<br/>
  *   geoportal_lu - Use the Luxembourg specific place name search provided by geoportal.lu.
  * </li>
  * </ul>
  * @link http://code.google.com/apis/ajaxsearch/terms.html Google AJAX Search API Terms of Use.
  * @link http://code.google.com/p/indicia/wiki/GeoreferenceLookupDrivers Documentation for the driver architecture.
  * @return string HTML to insert into the page for the georeference lookup control.
  */
  public static function georeference_lookup($options) {
    $options = self::check_options($options);
    $options = array_merge(array(
      'id' => 'imp-georef-search',
      'driver' => 'geoplanet',
      // Internationalise the labels here, because if we do this directly in the template setup code it is too early for any custom
      // language files to be loaded.
      'search' => lang::get('search'),
      'close' => lang::get('close'),
    ), $options);
    // dynamically build a resource to link us to the driver js file, and ensure the map is included.
    self::add_resource('indiciaMapPanel');
    self::$required_resources[] = 'georeference_default_'.$options['driver'];
    self::$resource_list['georeference_default_'.$options['driver']] = array(
      'javascript' => array(self::$js_path.'drivers/georeference/'.$options['driver'].'.js')
    );
    // We need to see if there is a resource in the resource list for any special files required by this driver. This
    // will do nothing if the resource is absent.
    self::add_resource('georeference_'.$options['driver']);
    self::$javascript .= "indicia_url='".self::$base_url."';\n";
    foreach ($options as $key=>$value) {
      // if any of the options are for the georeferencer driver, then we must set them in the JavaScript.
      if (substr($key, 0, 6)=='georef') {
        self::$javascript .= "$.fn.indiciaMapPanel.georeferenceLookupSettings.$key='$value';\n";
      }
    }
    foreach (get_class_vars('helper_config') as $key=>$value) {
      // if any of the config settings are for the georeferencer driver, then we must set them in the JavaScript.
      if (substr($key, 0, strlen($options['driver']))==$options['driver']) {
        self::$javascript .= "$.fn.indiciaMapPanel.georeferenceLookupSettings.$key='$value';\n";
      }
    }
    // If the lookup service driver uses cross domain JavaScript, this setting provides
    // a path to a simple PHP proxy script on the server.
    self::$javascript .= "$.fn.indiciaMapPanel.georeferenceLookupSettings.proxy='".
        dirname($_SERVER['PHP_SELF']) . '/' . self::relative_client_helper_path() . "proxy.php';\n\n";
    return self::apply_template('georeference_lookup', $options);
  }

 /**
  * Simple file upload control suitable for uploading images to attach to occurrences. 
  * Note that when using this control, it is essential that the form's HTML enctype attribute is 
  * set to enctype="multipart/form-data" so that the image file is included in the form data. For multiple 
  * image support and more advanced options, see the file_box control.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to, e.g. occurrence:image.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the file upload control.
  */
  public static function image_upload() {
    $options = self::check_arguments(func_get_args(), array('fieldname'));
    $pathField = $pathField = str_replace(':image','_image:path', $options['fieldname']);
    $alreadyUploadedFile = self::check_default_value($pathField);
    $options = array_merge(array(
      'pathFieldName' => $pathField,
      'pathFieldValue' => $alreadyUploadedFile
    ), $options);
    $r = self::apply_template('image_upload', $options);
    if ($alreadyUploadedFile) {
      // The control is being reloaded after a validation failure. So we can display a thumbnail of the
      // already uploaded file, so the user knows not to re-upload.
      $interimImageFolder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
      $r .= '<img width="100" src="$interimImageFolder$alreadyUploadedFile"/>'."\n";
    }
    return $r;
  }

 /**
  * Outputs an autocomplete control that is dedicated to listing locations and which is bound to any map panel
  * added to the page. Although it is possible to set all the options of a normal autocomplete, generally
  * the table, valueField, captionField, id should be left uninitialised and the fieldname will default to the
  * sample's location_id field so can normally also be left.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Optional. The name of the database field this control is bound to.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>extraParams</b><br/>
  * Required. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the location select control.
  */
  public static function location_autocomplete($options) {
    $options = self::check_options($options);
    $options = array_merge(array(
        'table'=>'location',
        'fieldname'=>'sample:location_id',
        'valueField'=>'id',
        'captionField'=>'name',
        'id'=>'imp-location'
        ), $options);

    return self::autocomplete($options);
  }

 /**
  * Outputs a select control that is dedicated to listing locations and which is bound to any map panel
  * added to the page. Although it is possible to set all the options of a normal select control, generally
  * the table, valueField, captionField, id should be left uninitialised and the fieldname will default to the
  * sample's location_id field so can normally also be left.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Optional. The name of the database field this control is bound to.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>extraParams</b><br/>
  * Required. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the location select control.
  */
  public static function location_select($options) {
    $options = self::check_options($options);
    // Apply location type filter if specified.
    if (array_key_exists('location_type_id', $options)) {
      $options['extraParams'] += array('location_type_id' => $options['location_type_id']);
    }
    $options = array_merge(array(
        'table'=>'location',
        'fieldname'=>'sample:location_id',
        'valueField'=>'id',
        'captionField'=>'name',
        'id'=>'imp-location'
        ), $options);
    return self::select($options);
  }

 /**
  * An HTML list box control.
  * Options can be either populated from a web-service call to the Warehouse, e.g. the contents of 
  * a termlist, or can be populated from a fixed supplied array. The list box can
  * be linked to populate itself when an item is selected in another control by specifying the
  * parentControlId and filterField options.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>table</b><br/>
  * Table name to get data from for the select options if the select is being populated by a service call.</li>
  * <li><b>captionField</b><br/>
  * Field to draw values to show in the control from if the select is being populated by a service call.</li>
  * <li><b>valueField</b><br/>
  * Field to draw values to return from the control from if the select is being populated by a service call. Defaults
  * to the value of captionField.</li>
  * <li><b>extraParams</b><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array if the select is being populated by a service call.</li>
  * <li><b>lookupValues</b><br/>
  * If the select is to be populated with a fixed list of values, rather than via a service call, then the
  * values can be passed into this parameter as an associated array of key=>caption.
  * </li>
  * <li><b>size</b><br/>
  * Optional. Number of lines to display in the listbox. Defaults to 3.</li>
  * <li><b>multiselect</b><br/>
  * Optional. Allow multi-select in the list box. Defaults to false.</li>
  * <li><b>parentControlId</b><br/>
  * Optional. Specifies a parent control for linked lists. If specified then this control is not
  * populated until the parent control's value is set. The parent control's value is used to
  * filter this control's options against the field specified by filterField.</li>
  * <li><b>filterField</b><br/>
  * Optional. Specifies the field to filter this control's content against when using a parent
  * control value to set up linked lists. Defaults to parent_id though this is not active
  * unless a parentControlId is specified.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * <li><b>template</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for the outer control.</li>
  * <li><b>itemTemplate</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for each item in the control.</li>
  * <li><b>selectedItemTemplate</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for the selected item in the control.</li></ul>
  * </ul>
  *
  * @return string HTML to insert into the page for the listbox control.
  */
  public static function listbox()
  {
    $options = self::check_arguments(func_get_args(), array(
        'fieldname', 'table', 'captionField', 'size', 'multiselect', 'valueField', 'extraParams', 'default'
    ));
    // blank text option not applicable to list box
    unset($options['blankText']);
    $options = array_merge(
      array(
        'template' => 'listbox',
        'itemTemplate' => 'listbox_item'
      ),
      $options
    );
    return self::select_or_listbox($options);
  }

  /**
  * Helper function to list the output from a request against the data services, using an HTML template
  * for each item. As an example, the following outputs an unordered list of surveys:
  * <pre>echo data_entry_helper::list_in_template(array(
  *     'label'=>'template',
  *     'table'=>'survey',
  *     'extraParams' => $readAuth,
  *     'template'=>'<li>|title|</li>'
  * ));</pre>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>table</b><br/>
  * Required. Table name to get data from for the select options.</li>
  * <li><b>extraParams</b><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><b>template</b><br/>
  * Required. HTML template which will be emitted for each item. Fields from the data are identified
  * by wrapping them in ||. For example, |term| would result in the field called term's value being placed inside
  * the HTML.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the generated list.
  */
  public static function list_in_template() {
    $options = self::check_arguments(func_get_args(), array('table', 'extraParams', 'template'));
    $response = self::get_population_data($options);
    $items = "";
    if (!array_key_exists('error', $response)){
      foreach ($response as $row){
        $item = $options['template'];
        foreach ($row as $field => $value) {
          $value = htmlspecialchars($value, ENT_QUOTES);
          $item = str_replace("|$field|", $value, $item);
        }
        $items .= $item;
      }
      $options['items']=$items;
      return self::apply_template('list_in_template', $options);
    }
    else
      return lang::get("error loading control");
  }

 /**
  * Generates a map control, with optional data entry fields and location finder powered by the
  * Yahoo! geoservices API. This is just a shortcut to building a control using a map_panel and the
  * associated controls.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>presetLayers</b><br/>
  * Array of preset layers to include. Options are 'google_physical', 'google_streets', 'google_hybrid',
  * 'google_satellite', 'openlayers_wms', 'nasa_mosaic', 'virtual_earth', 'multimap_default', 'multimap_landranger'</li>
  * <li><b>edit</b><br/>
  * True or false to include the edit controls for picking spatial references.</li>
  * <li><b>locate</b><br/>
  * True or false to include the geolocate controls.</li>
  * <li><b>wkt</b><br/>
  * Well Known Text of a spatial object to add to the map at startup.</li>
  * </ul>
  */
  public static function map() {
    $options = self::check_arguments(func_get_args(), array('div', 'presetLayers', 'edit', 'locate', 'wkt'));
    $options = array_merge(array(
        'div'=>'map',
        'edit'=>true,
        'locate'=>true,
        'wkt'=>null
    ), $options);
    $r = '';
    if ($options['edit']) {
      $r .= self::sref_and_system(array(
          'label'=>lang::get('spatial ref'),
      ));
    }
    if ($options['locate']) {
      $r .= self::georeference_lookup(array(
          'label'=>lang::get('search for place on map')
      ));
    }
    $mapPanelOptions = array('initialFeatureWkt' => $options['wkt']);
    if (array_key_exists('presetLayers', $options)) $mapPanelOptions['presetLayers'] = $options['presetLayers'];
    $r .= self::map_panel($mapPanelOptions);
    return $r;
  }

 /**
  * Outputs a map panel.
  * The map panel can be augmented by adding any of the following controls which automatically link themselves
  * to the map:
  * <ul>
  * <li>{@link sref_textbox()}</ul>
  * </ul>{@link sref_system_select()}</ul>
  * </ul>{@link sref_and_system()}</ul>
  * </ul>{@link georeference_lookup()}</ul>
  * </ul>{@link location_select()}</ul>
  * </ul>{@link location_autocomplete()}</li>
  * </ul>{@link postcode_textbox()}</li>
  * </ul>
  *
  * @param array $options Associative array of options to pass to the jQuery.indiciaMapPanel plugin.
  * Has the following possible options:
  * <li><b>indiciaSvc</b><br/>
  * </li>
  * <li><b>indiciaGeoSvc</b><br/>
  * </li>
  * <li><b>readAuth</b><br/>
  * </li>
  * <li><b>height</b><br/>
  * </li>
  * <li><b>width</b><br/>
  * </li>
  * <li><b>initial_lat</b><br/>
  * </li>
  * <li><b>initial_long</b><br/>
  * </li>
  * <li><b>initial_zoom</b><br/>
  * </li>
  * <li><b>scroll_wheel_zoom</b><br/>
  * </li>
  * <li><b>proxy</b><br/>
  * </li>
  * <li><b>displayFormat</b><br/>
  * </li>
  * <li><b>presetLayers</b><br/>
  * </li>
  * <li><b>tilecacheLayers</b><br/>
  * Array of layer definitions for tilecaches, which are pre-cached background tiles. They are less flexible but much faster
  * than typical WMS services. The array is associative, with the following keys:
  *   caption - The display name of the layer
  *   servers - array list of server URLs for the cache
  *   layerName - the name of the layer within the cache
  *   settings - any other settings that need to be passed to the tilecache, e.g. the server resolutions or file format.</li>
  * <li><b>indiciaWMSLayers</b><br/>
  * </li>
  * <li><b>indiciaWFSLayers</b><br/>
  * </li>
  * <li><b>layers</b><br/>
  * </li>
  * <li><b>controls</b><br/>
  * </li>
  * <li><b>editLayer</b><br/>
  * </li>
  * <li><b>editLayerName</b><br/>
  * </li>
  * <li><b>initialFeatureWkt</b><br/>
  * </li>
  * <li><b>defaultSystem</b><br/>
  * </li>
  * <li><b>latLongFormat</b><br/>
  * Override the format for display of lat long references. Select from D (decimal degrees, the default), DM (degrees and decimal minutes)
  * or DMS (degrees, minutes and decimal seconds).</li>
  * <li><b>srefId</b><br/>
  * </li>
  * <li><b>srefSystemId</b><br/>
  * </li>
  * <li><b>geomId</b><br/>
  * </li>
  * <li><b>clickedSrefPrecisionMin</b><br/>
  * </li>
  * <li><b>clickedSrefPrecisionMax</b><br/>
  * </li>
  * <li><b>msgGeorefSelectPlace</b><br/>
  * </li>
  * <li><b>msgGeorefNothingFound</b><br/>
  * </li>
  * <li><b>maxZoom</b><br/>
  * Limit the maximum zoom used when clicking on the map to set a point spatial reference. Use this to prevent over zooming on
  * background maps.</li>
  * <li><b>tabDiv</b><br/>
  * If loading this control onto a set of tabs, specify the tab control's div ID here. This allows the control to
  * automatically generate code which only generates the map when the tab is shown.</li>
  * </ul>
  * @param array $olOptions Optional array of settings for the OpenLayers map object. If overriding the projection or
  * displayProjection settings, just pass the EPSG number, e.g. 27700.
  */
  public static function map_panel($options, $olOptions=null) {
    if (!$options) {
      return '<div class="error">Form error. No options supplied to the map_panel method.</div>';
    } else {
      global $indicia_templates;
      $presetLayers = array();
      // If the caller has not specified the background layers, then default to the ones we have an API key for
      if (!array_key_exists('presetLayers', $options)) {
        if (parent::$multimap_api_key != '') {
          $defaultLayers [] = 'multimap_landranger';
        }
        if (parent::$google_api_key != '') {
          $presetLayers[] = 'google_satellite';
          $presetLayers[] = 'google_hybrid';
          $presetLayers[] = 'google_physical';
        }
        // Fallback as we don't need a key for this.
        $presetLayers[] = 'virtual_earth';
      }
      $options = array_merge(array(
          'indiciaSvc'=>self::$base_url,
          'indiciaGeoSvc'=>self::$geoserver_url,
          'divId'=>'map',
          'class'=>'',
          'width'=>600,
          'height'=>470,
          'presetLayers'=>$presetLayers
      ), $options);
      // When using tilecache layers, the open layers defaults cannot be used. The caller must take control of openlayers settings
      if (isset($options['tilecacheLayers'])) {
        $options['useOlDefaults'] = false;
      }

      //width and height may be numeric, which is interpreted as pixels, or a css string, e.g. '50%'
      //width in % is causing problems with panning in Firefox currently. 13/3/2010.
      if (is_numeric($options['height']))
        $options['height'] .= 'px';
      if (is_numeric($options['width']))
        $options['width'] .= 'px';

      if (array_key_exists('readAuth', $options)) {
        // Convert the readAuth into a query string so it can pass straight to the JS class.
        $options['readAuth']='&'.self::array_to_query_string($options['readAuth']);
        str_replace('&', '&amp;', $options['readAuth']);
      }

      // Autogenerate the links to the various mapping libraries as required
      if (array_key_exists('presetLayers', $options)) {
        foreach ($options['presetLayers'] as $layer)
        {
          $a = explode('_', $layer);
          $a = strtolower($a[0]);
          switch($a)
          {
            case 'google':
              self::add_resource('googlemaps');
              break;
            case 'multimap':
              self::add_resource('multimap');
              break;
            case 'virtual':
              self::add_resource('virtualearth');
              break;
          }
        }
      }

      // This resource has a dependency on the googlemaps resource so has to be added afterwards.
      self::add_resource('indiciaMapPanel');

      // We need to fudge the JSON passed to the JavaScript class so it passes any actual layers
      // and controls, not the string class names.
      $json_insert='';
      if (array_key_exists('controls', $options)) {
        $json_insert .= ',"controls":['.implode(',', $options['controls']).']';
        unset($options['controls']);
      }
      if (array_key_exists('layers', $options)) {
        $json_insert .= ',"layers":['.implode(',', $options['layers']).']';
        unset($options['layers']);
      }
      $json=substr(json_encode($options), 0, -1).$json_insert.'}';
      if ($olOptions) {
        $json .= ','.json_encode($olOptions);
      }
      $javascript = '';
      if (isset($options['tabDiv'])) {
        // The map is displayed on a tab, so we must only generate it when the tab is displayed.        
        $javascript .= "var tabHandler = function(event, ui) { \n";
        $javascript .= "  if (ui.panel.id=='".$options['tabDiv']."') {\n    ";        
      }
      $javascript .= "jQuery('#".$options['divId']."').indiciaMapPanel($json);\n";
      if (isset($options['tabDiv'])) {
        $javascript .= "    jQuery(jQuery('#".$options['tabDiv']."').parent()).unbind('tabsshow', tabHandler);\n";
        $javascript .= "  }\n};\n";
        $javascript .= "jQuery(jQuery('#".$options['tabDiv']."').parent()).bind('tabsshow', tabHandler);\n";
        // Insert this script at the beginning, because it must be done before the tabs are initialised or the 
        // first tab cannot fire the event
        self::$javascript = $javascript . self::$javascript;
      }	else
        self::$onload_javascript .= $javascript;
      
      return self::apply_template('map_panel', $options);
    }
  }

 /**
  * Helper function to output a textbox for determining a locality from an entered postcode.
  *
  * <p>The textbox optionally includes hidden fields for the latitude and longitude and can
  * link to an address control for automatic generation of address information. When the focus
  * leaves the textbox, the Google AJAX Search API is used to obtain the latitude and longitude
  * so they can be saved with the record.</p>
  *
  * <p>The following example displays a postcode box and an address box, which is auto-populated
  * when a postcode is given. The spatial reference controls are "hidden" from the user but
  * are available to post into the database.</p>
  * <code>
  * <?php echo data_entry_helper::postcode_textbox(array(
  *     'label'=>'Postcode',
  *     'fieldname'=>'smpAttr:8',
  *     'linkedAddressBoxId'=>'address'
  * ); 
  * echo data_entry_helper::textarea(array(
  *     'label' => 'Address',
  *     'id' => 'address',
  *     'fieldname' => 'smpAttr:9'
  * ));?>
  * </code>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. This should be left to its default value for
  * integration with other mapping controls to work correctly.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>hiddenFields</b><br/>
  * Optional. Set to true to insert hidden inputs to receive the latitude and longitude. Otherwise there
  * should be separate sref_textbox and sref_system_textbox controls elsewhere on the page. Defaults to true.
  * <li><b>srefField</b><br/>
  * Optional. Name of the spatial reference hidden field that will be output by this control if hidddenFields is true.</li>
  * <li><b>systemField</b><br/>
  * Optional. Name of the spatial reference system hidden field that will be output by this control if hidddenFields is true.</li>
  * <li><b>linkedAddressBoxId</b><br/>
  * Optional. Id of the separate textarea control that will be populated with an address when a postcode is looked up.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the postcode control.
  */
  public static function postcode_textbox($options) {
    // The id field default must take precedence over using the fieldname as the id
    $options = array_merge(array('id'=>'imp-postcode'), $options);
    $options = self::check_options($options);
    // Merge in the defaults
    $options = array_merge(array(
        'srefField'=>'sample:entered_sref',
        'systemField'=>'sample:entered_sref_system',
        'hiddenFields'=>true,
        'linkedAddressBoxId'=>''
        ), $options);
    self::add_resource('google_search');
    $r = self::apply_template('postcode_textbox', $options);
    if ($options['hiddenFields']) {
      $defaultSref=self::check_default_value($options['srefField']);
      $defaultSystem=self::check_default_value($options['systemField'], '4326');
      $r .= "<input type='hidden' name='".$options['srefField']."' id='imp-sref' value='$defaultSref' />";
      $r .= "<input type='hidden' name='".$options['systemField']."' id='imp-sref-system' value='$defaultSystem' />";
    }
    $r .= self::check_errors($options['fieldname']);
    return $r;
  }

 /**
  * Helper function to generate a radio group from a Indicia core service query.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>table</b><br/>
  * Optional. Table name to get data from for the select options. Required unless lookupValues is specified.</li>
  * <li><b>captionField</b><br/>
  * Optional. Field to draw values to show in the control from. Required unless lookupValues is specified.</li>
  * <li><b>valueField</b><br/>
  * Optional. Field to draw values to return from the control from. Defaults
  * to the value of captionField. </li>
  * <li><b>extraParams</b><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><b>lookupValues</b><br/>
  * If the group is to be populated with a fixed list of values, rather than via a service call, then the
  * values can be passed into this parameter as an associated array of key=>caption.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * <li><b>template</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for the outer control.</li>
  * <li><b>itemTemplate</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for each item in the control.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the group of radio buttons.
  */
  public static function radio_group() {
    $options = self::check_arguments(func_get_args(), array('fieldname', 'table', 'captionField', 'valueField', 'extraParams', 'sep', 'default'));
    return self::check_or_radio_group($options, 'radio');
  }

  /**
  * <p>Outputs a grid that loads the content of a report or Indicia table.</p>
  * <p>The grid supports a simple pagination footer as well as column title sorting through PHP. If
  * used as a PHP grid, note that the current web page will reload when you page or sort the grid, with the
  * same $_GET parameters but no $_POST information. If you need 2 grids on one page, then you must define a different
  * id in the options for each grid.</p>
  * <p>The grid operation will be handled by AJAX calls when possible to avoid reloading the web page.</p>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>id</b><br/>
  * Optional unique identifier for the grid. This is required if there is more than one grid on a single
  * web page to allow separation of the page and sort $_GET parameters in the URLs generated.</li>
  * <li><b>mode</b><br/>
  * Pass report for a report, or direct for an Indicia table or view. Default is report.</li>
  * <li><b>readAuth</b><br/>
  * Read authorisation tokens.</li>
  * <li><b>dataSource</b><br/>
  * Name of the report file or table/view.</li>
  * <li><b>itemsPerPage</b><br/>
  * Number of rows to display per page. Defaults to 20.</li>
  * <li><b>columns</b><br/>
  * Specify a list of the columns you want to output if you need more control over the columns, for example to
  * specify the order, change the caption or build a column with a configurable data display using a template.
  * Pass an array to this option, with each array entry containing an associative array that specifies the
  * information about the column represented by the position within the array. The associative array for the column can contain
  * the following keys:
  *  - fieldname: name of the field to output in this column. Does not need to be specified when using the template option.
  *  - display: caption of the column, which defaults to the fieldname if not specified
  *  - actions: list of action buttons to add to each grid row. Each button is defined by a sub-array containing
  *      values for caption, url, urlParams, class and javascript. The javascript, url and urlParams values can all use the
  *      field names from the report in braces as substitutions, for example {id} is replaced by the value of the field
  *      called id in the respective row. In addition, the url can use {currentUrl} to represent the current page's URL.
  *  - visible: true or false, defaults to true
  *  - template: allows you to create columns that contain dynamic content using a template, rather than just the output
  *  of a field. The template text can contain fieldnames in braces, which will be replaced by the respective field values.
  *  Note that template columns cannot be sorted by clicking grid headers.
  * An example array for the columns option is:
  * array(
  *   array('fieldname' => 'survey', 'display' => 'Survey Title'),
  *   array('display' => 'action', 'template' => '<a href="www.mysite.com\survey\{id}\edit">Edit</a>'),
  *   array('display' => 'Actions', 'actions' => array(
  *     array('caption' => 'edit', 'url'=>'{currentUrl}', 'urlParams'=>array('survey_id'=>'{id}'))
  *   ))
  *
  * )
  * </li>
  * <li><b>rowId</b>
  * Optional. Names the field in the data that contains the unique identifier for each row. If set, then the <tr> elements have their id attributes
  * set to row + this field value, e.g. row37.</li>
  * <li><b>IncludeAllColumns</b>
  * Defaults to true. If true, then any columns in the report, view or table which are not in the columns
  * option array are automatically added to the grid after any columns specified in the columns option array.
  * Therefore the default state for a report_grid control is to include all the report, view or table columns
  * in their default state, since the columns array will be empty.</li>
  * <li><b>autoParamsForm</b>
  * Defaults to true. If true, then if a report requires parameters, a parameters input form will be auto-generated
  * at the top of the grid. If set to false, then it is possible to manually build a parameters entry HTML form if you
  * follow the following guidelines. First, you need to specify the id option for the report grid, so that your
  * grid has a reproducable id. Next, the form you want associated with the grid must itself have the same id, but with
  * the addition of params on the end. E.g. if the call to report_grid specifies the option 'id' to be 'my-grid' then
  * the parameters form must be called 'my-grid-params'. Finally the input controls which define each parameter must have
  * the name 'param-id-' followed by the actual parameter name, replacing id with the grid id. So, in our example,
  * a parameter called survey will need an input or select control with the name attribute set to 'param-my-grid-survey'.
  * The submit button for the form should have the method set to "get" and should post back to the same page.
  * As a final alternative, if parameters are required by the report but some can be hard coded then
  * those may be added to the extraParams array.</li>
  * <li><b>paramDefaults</b>
  * Optional associative array of parameter default values.</li>
  * <li><b>paramsOnly</b>
  * Defaults to false. If true, then this method will only return the parameters form, not the grid content. autoParamsForm
  * is ignored if this flag is set.</li>
  * <li><b>ignoreParams</b>
  * Array that can be set to a list of the report parameter names that should not be included in the parameters form. Useful
  * when using paramsOnly=true to display a parameters entry form, but the system has default values for some of the parameters
  * which the user does not need to be asked about.</li>
  * <li><b>completeParamsForm</b>
  * Defaults to true. If false, the control HTML is returned for the params form without being wrapped in a <form> and
  * without the Run Report button.</li>
  * <li><b>paramsFormButtonCaption</b>
  * Caption of the button to run the report on the report parameters form. Defaults to Run Report. This caption
  * is localised when appropriate.
  * </ul>
  * @todo Action column or other configurable links in grid
  * @todo Allow additional params to filter by table column or report parameters
  * @todo Display a filter form for direct mode
  * @todo For report mode, provide an AJAX/PHP button that can load the report from parameters
  * in a form on the page.
  */
  public static function report_grid($options) {
    self::add_resource('fancybox');
    self::$javascript .= "jQuery('a.fancybox').fancybox();\n";
    $options = self::get_report_grid_options($options);
    // Output a div to keep the grid and pager together
    $r = '<div id="'.$options['id'].'">';
    // Work out the names and current values of the params we expect in the report request URL for sort and pagination
    $sortAndPageUrlParams = self::get_report_grid_sort_page_url_params($options);
    $page = ($sortAndPageUrlParams['page']['value'] ? $sortAndPageUrlParams['page']['value'] : 0);
    // set the limit to one higher than we need, so the extra row can trigger the pagination next link
    $extraParams = '&limit='.($options['itemsPerPage']+1).'&wantColumns=1&wantParameters=1';
    $extraParams .= '&offset=' . $page * $options['itemsPerPage'];

    // Add in the sort parameters
    foreach ($sortAndPageUrlParams as $param => $content) {
      if ($content['value']!=null) {
        if ($param != 'page')
          $extraParams .= '&' . $param .'='. $content['value'];
      }
    }
    $response = self::get_report_data($options, $extraParams);
    if (isset($response['error'])) return $response['error'];
    if (isset($response['parameterRequest'])) {
      $currentParamValues = self::get_report_grid_current_param_values($options);
      $r .= self::get_report_grid_parameters_form($response, $options, $currentParamValues);
      // if we have a complete set of parameters in the URL, we can re-run the report to get the data
      if (count($currentParamValues)==count($response['parameterRequest'])) {
        $response = self::get_report_data($options, $extraParams.'&'.self::array_to_query_string($currentParamValues));
        if (isset($response['error'])) return $response['error'];
        $records = $response['records'];
      }
    } else {
      $records = $response['records'];
    }
    // return the params form, if that is all that is being requested.
    if ($options['paramsOnly']) return $r;
    
    self::report_grid_get_columns($response, $options);
    $pageUrl = self::report_grid_get_reload_url($sortAndPageUrlParams);
    $thClass = $options['thClass'];
    $r .= "\n<table class=\"".$options['class']."\"><thead class=\"$thClass\"><tr>\n";
    // build a URL with just the sort order bit missing, so it can be added for each table heading link
    $sortUrl = $pageUrl . ($sortAndPageUrlParams['page']['value'] ?
        $sortAndPageUrlParams['page']['name'].'='.$sortAndPageUrlParams['page']['value'].'&' :
        ''
    );
    $sortdirval = $sortAndPageUrlParams['sortdir']['value'] ? strtolower($sortAndPageUrlParams['sortdir']['value']) : 'asc';
    foreach ($options['columns'] as $field) {
      if (isset($field['visible']) && $field['visible']=='false')
        continue; // skip this column as marked invisible
      // allow the display caption to be overriden in the column specification
      $caption = lang::get(empty($field['display']) ? $field['fieldname'] : $field['display']);
      if (isset($field['fieldname'])) {
        if (empty($field['orderby'])) $field['orderby']=$field['fieldname'];
        $sortLink = $sortUrl.$sortAndPageUrlParams['orderby']['name'].'='.$field['orderby'];
        // reverse sort order if already sorted by this field in ascending dir
        if ($sortAndPageUrlParams['orderby']['value']==$field['orderby'] && $sortAndPageUrlParams['sortdir']['value']!='DESC')
          $sortLink .= '&'.$sortAndPageUrlParams['sortdir']['name']."=DESC";
        if (!isset($field['img']) || $field['img']!='true')
          $caption = "<a href=\"$sortLink\" title=\"Sort by $caption\">$caption</a>";
        // set a style for the sort order
        $orderStyle = ($sortAndPageUrlParams['orderby']['value']==$field['orderby']) ? ' '.$sortdirval : '';
        $orderStyle .= ' sortable';
        $fieldId = ' id="' . $options['id'] . '-th-' . $field['orderby'] . '"';
      } else {
        $orderStyle = '';
        $fieldId = '';
      }

      $r .= "<th$fieldId class=\"$thClass$orderStyle\">$caption</th>\n";
    }
    $r .= "</tr></thead><tbody>\n";
    $rowClass = '';
    $outputCount = 0;
    $imagePath = data_entry_helper::$base_url.(isset(data_entry_helper::$indicia_upload_path) ? data_entry_helper::$indicia_upload_path : 'upload/');
    if (count($records)>0) {
      foreach ($records as $row) {
        // Don't output the additional row we requested just to check if the next page link is required.
        if ($outputCount>=$options['itemsPerPage'])
          break;
        // set a unique id for the row if we know the identifying field.
        $rowId = isset($options['rowId']) ? ' id="row'.$row[$options['rowId']].'"' : '';
        $r .= "<tr $rowClass$rowId>";
        foreach ($options['columns'] as $field) {
          $class='';
          if (isset($field['visible']) && $field['visible']=='false')
            continue; // skip this column as marked invisible
          if (isset($field['actions'])) {
            $value = self::get_report_grid_actions($field['actions'],$row);
            $class=' class="actions"';
          } elseif (isset($field['template'])) 
            $value = self::mergeParamsIntoTemplate($row, $field['template'], true);
          else {
            $value = isset($field['fieldname']) && isset($row[$field['fieldname']]) ? $row[$field['fieldname']] : '';
            // The verification_1 form depends on the tds in the grid having a class="data fieldname".
            $class=' class="data '.$field['fieldname'].'"';
          }          
          if (isset($field['img']) && $field['img']=='true' && !empty($value))
            $value = "<a href=\"$imagePath$value\" class=\"fancybox\"><img src=\"$imagePath"."thumb-$value\" /></a>";
          $r .= "<td$class>$value</td>\n";
        }
        $r .= '</tr>';
        $rowClass = empty($rowClass) ? ' class="'.$options['altRowClass'].'"' : '';
        $outputCount++;
      }
    }
    $r .= "</tbody></table>\n";
    // Output pagination links
    $pagLinkUrl = $pageUrl . ($sortAndPageUrlParams['orderby']['value'] ? $sortAndPageUrlParams['orderby']['name'].'='.$sortAndPageUrlParams['orderby']['value'].'&' : '');
    $pagLinkUrl .= $sortAndPageUrlParams['sortdir']['value'] ? $sortAndPageUrlParams['sortdir']['name'].'='.$sortAndPageUrlParams['sortdir']['value'].'&' : '';
    $r .= "<div class=\"pager\">\n";
    // If not on first page, we can go back.
    if ($sortAndPageUrlParams['page']['value']>0) {
      $prev = max(0, $sortAndPageUrlParams['page']['value']-1);
      $r .= "<a class=\"prev\" href=\"$pagLinkUrl".$sortAndPageUrlParams['page']['name']."=$prev\">&#171 previous</a> \n";
    }
    // pagination separator if both links are present
    if ($sortAndPageUrlParams['page']['value']>0 && count($records)>$options['itemsPerPage'])
      $r .= ' &#166; ';
    // if the service call returned more records than we are displaying (because we asked for 1 more), then we can go forward
    if (count($records)>$options['itemsPerPage']) {
      $next = $sortAndPageUrlParams['page']['value'] + 1;
      $r .= "<a class=\"next\" href=\"$pagLinkUrl".$sortAndPageUrlParams['page']['name']."=$next\">next &#187</a> \n";
    }
    $r .= "</div></div>\n";

    // Now AJAXify the grid
    self::add_resource('reportgrid');
    self::$javascript .= "$('#".$options['id']."').reportgrid({
  mode: '".$options['mode']."',
  dataSource: '".str_replace('\\','/',$options['dataSource'])."',
  itemsPerPage: ".$options['itemsPerPage'].",
  auth_token: '".$options['readAuth']['auth_token']."',
  nonce: '".$options['readAuth']['nonce']."',
  extraParams: ".json_encode($options['extraParams']).",
  callback: '".$options['callback']."',
  url: '".parent::$base_url."',
  imagePath: '".$imagePath."',
  altRowClass: '".$options['altRowClass']."'";
    if (isset($orderby))
      self::$javascript .= ",
  orderby: '".$orderby."'";
    if (isset($sortdir))
      self::$javascript .= ",
  sortdir: '".$sortdir."'";
    if (isset($options['columns']))
      self::$javascript .= ",\n  columns: ".json_encode($options['columns'])."
});\n";
    return $r;
  }

  /**
   * Works out the orderby, sortdir and page URL param names for this report grid, and also gets their
   * current values.
   * @param $options Control options array
   * @return array Contains the orderby, sortdir and page params, as an assoc array. Each array value
   * is an array containing name & value.
   */
  private static function get_report_grid_sort_page_url_params($options) {
    $orderbyKey = 'orderby' . (isset($options['id']) ? '-'.$options['id'] : '');
    $sortdirKey = 'sortdir' . (isset($options['id']) ? '-'.$options['id'] : '');
    $pageKey = 'page' . (isset($options['id']) ? '-'.$options['id'] : '');
    return array(
      'orderby' => array(
        'name' => $orderbyKey,
        'value' => isset($_GET[$orderbyKey]) ? $_GET[$orderbyKey] : null
      ),
      'sortdir' => array(
        'name' => $sortdirKey,
        'value' => isset($_GET[$sortdirKey]) ? $_GET[$sortdirKey] : null
      ),
      'page' => array(
        'name' => $pageKey,
        'value' => isset($_GET[$pageKey]) ? $_GET[$pageKey] : null
      )
    );
  }


  /**
   * Build a url suitable for inclusion in the links for the report grid column headings or pagination
   * bar. This effectively re-builds the current page's URL, but drops the query string parameters that
   * indicate the sort order and page number.
   * @param array $sortAndPageParams List of the sorting and pagination parameters which should be excluded.
   * @return unknown_type
   */
  private static function report_grid_get_reload_url($sortAndPageUrlParams) {
    // get the url parameters. Don't use $_GET, because it contains any parameters that are not in the
    // URL when search friendly URLs are used (e.g. a Drupal path node/123 is mapped to index.php?q=node/123
    // using Apache mod_alias but we don't want to know about that)
    $reloadUrl = self::get_reload_link_parts();
    // Build a basic URL path back to this page, but with the page, sortdir and orderby removed
    $pageUrl = $reloadUrl['path'].'?';
    // find the names of the params we must not include
    $excludedParams = array();
    foreach($sortAndPageUrlParams as $param) {
      $excludedParams[] = $param['name'];
    }
    foreach ($reloadUrl['params'] as $key => $value) {
      if (!in_array($key, $excludedParams))
        $pageUrl .= "$key=$value&";
    }
    return $pageUrl;
  }

  /**
   * Private function that builds a parameters form according to a parameterRequest recieved
   * when calling a report. If the autoParamsForm is false then an empty string is returned.
   * @param $response
   * @return string HTML for the form.
   */
  private static function get_report_grid_parameters_form($response, $options, $params) {
    if ($options['autoParamsForm'] || $options['paramsOnly']) {
      // get the url parameters. Don't use $_GET, because it contains any parameters that are not in the
      // URL when search friendly URLs are used (e.g. a Drupal path node/123 is mapped to index.php?q=node/123
      // using Apache mod_alias but we don't want to know about that)
      $reloadUrl = self::get_reload_link_parts();
      $r = '';
      if ($options['completeParamsForm']==true) {
        $r .= '<form action="'.$reloadUrl['path'].'" method="get" id="'.$options['id'].'-params">'."\n";
        $r .= '<fieldset><legend>'.lang::get('Report Parameters').'</legend>';
      }
      // Output any other get parameters from our URL as hidden fields
      foreach ($reloadUrl['params'] as $key => $value) {
        // since any param will be from a URL it could be encoded. So we don't want to double encode if they repeatedly
        // run the report.
        $value = urldecode($value);
        // ignore any parameters that are going to be in the grid parameters form
        if (substr($key,0,6)!='param-')
          $r .= "<input type=\"hidden\" value=\"$value\" name=\"$key\" />\n";
      }      
      foreach($response['parameterRequest'] as $key=>$info) {
        // Skip parameters if we have been asked to ignore them
        if (isset($options['ignoreParams']) && in_array($key, $options['ignoreParams'])) continue;
        $ctrlOptions = array(
          'label' => $info['display'],
          'helpText' => $info['description'],
          'fieldname' => 'param-' . (isset($options['id']) ? $options['id'] : '')."-$key"
        );
        // If this parameter is in the URL or post data, put it in the control
        if (isset($params[$key])) {
          $ctrlOptions['default'] = $params[$key];
        }
        if ($info['datatype']=='lookup' && isset($info['population_call'])) {
          // population call is colon separated, of the form direct|report:table|view|report:idField:captionField
          $popOpts = explode(':', $info['population_call']);
          // @todo Support report calls to populate the lookup
          $ctrlOptions = array_merge($ctrlOptions, array(
            'table'=>$popOpts[1],
            'valueField'=>$popOpts[2],
            'captionField'=>$popOpts[3],
            'blankText'=>'<'.lang::get('please select').'>',
            'extraParams'=>$options['readAuth']
          ));
          $r .= data_entry_helper::select($ctrlOptions);
        } elseif ($info['datatype']=='lookup' && isset($info['lookup_values'])) {
          // Convert the lookup values into an associative array
          $lookups = explode(',', $info['lookup_values']);
          $lookupsAssoc = array();
          foreach($lookups as $lookup) {
            $lookup = explode(':', $lookup);
            $lookupsAssoc[$lookup[0]] = $lookup[1];
          }

          $ctrlOptions = array_merge($ctrlOptions, array(
            'blankText'=>'<'.lang::get('please select').'>',
            'lookupValues' => $lookupsAssoc
          ));
          $r .= data_entry_helper::select($ctrlOptions);
        } else {
          $r .= data_entry_helper::text_input($ctrlOptions);
        }
      }
      if ($options['completeParamsForm']==true) {
        $r .= '<input type="submit" value="'.lang::get($options['paramsFormButtonCaption']).'"/>'."\n";
        $r .= "</fieldset></form>\n";
      }
      return $r;
    } else {
      return $r;
    }
  }

  /**
   * Add any columns that don't have a column definition to the end of the columns list, by first
   * building an array of the column names of the columns we did specify, then adding any missing fields
   * from the results to the end of the options['columns'] array.
   * @param $response
   * @param $options
   * @return unknown_type
   */
  private static function report_grid_get_columns($response, &$options) {
    if ($options['includeAllColumns'] && isset($response['columns'])) {
      $specifiedCols = array();
      $actionCols = array();
      $idx=0;
      foreach ($options['columns'] as $col) {
        if (isset($col['fieldname'])) $specifiedCols[] = $col['fieldname'];
        // action columns need to be removed and added to the end
        if (isset($col['actions'])) {
          // remove the action col from its current location, store it so we can add it to the end
          unset($options['columns'][$idx]);
          $actionCols[] = $col;
        }
        $idx++;
      }
      foreach ($response['columns'] as $resultField => $value) {
        if (!in_array($resultField, $specifiedCols)) {
          $options['columns'][] = array_merge(
            $value,
            array('fieldname'=>$resultField)
          );
        }
      }
      // add the actions columns back in at the end
      $options['columns'] = array_merge($options['columns'], $actionCols);
    }
  }

  private static function get_report_grid_actions($actions, $row) {
    $links = array();
    // allow the current URL to be replaced into an action link. We extract url parameters from the url, not $_GET, in case
    // the url is being rewritten.
    $currentUrl = self::get_reload_link_parts();
    $row['currentUrl'] = $currentUrl['path'];    
    foreach ($actions as $action) {
      if (isset($action['url'])) {        
        // include any $_GET parameters to reload the same page, except the parameters that are specified by the action
        if (isset($action['urlParams'])) 
          $urlParams = array_merge($currentUrl['params'], $action['urlParams']);
        else if (substr($action['url'], 0, 1)=='#')
          // if linking to an internal bookmark, no need to attach the url parameters
          $urlParams = array();
        else
          $urlParams = array_merge($currentUrl['params']);
        if (count($urlParams)>0) {
          $action['url'].= (strpos($action['url'], '?')===false) ? '?' : '&';
        }
        $href=' href="'.self::mergeParamsIntoTemplate($row, $action['url'].self::array_to_query_string($urlParams), true).'"';
      } else {
        $href='';
      }
      if (isset($action['javascript'])) {
        $onclick=' onclick="'.self::mergeParamsIntoTemplate($row,$action['javascript'],true).'"';
      } else {
        $onclick = '';
      }
      $class=(isset($action['class'])) ? ' '.$action['class'] : '';
      $links[] = "<a class=\"action-button$class\"$href$onclick>".$action['caption'].'</a>';
    }
    return implode('<br/>', $links);
  }

  /**
   * Utility method that returns the parts required to build a link back to the current page.
   * @return array Associative array containing path and params (itself a key/value paired associative array).
   */
  public static function get_reload_link_parts() {
    $split = strpos($_SERVER['REQUEST_URI'], '?');
    $gets = $split!==false ? explode('&', substr($_SERVER['REQUEST_URI'], $split+1)) : array();
    $getsAssoc = array();
    foreach ($gets as $get) {
      list($key, $value) = explode('=', $get);
      $getsAssoc[$key] = $value;
    }
    $path = $split!==false ? substr($_SERVER['REQUEST_URI'], 0, $split) : $_SERVER['REQUEST_URI'];
    return array(
      'path'=>$path,
      'params' => $getsAssoc
    );
  }

  private static function get_report_grid_options($options) {
    // Generate a unique number for this grid, in case there are 2 on a page.
    $uniqueId = rand(0,10000);
    $options = array_merge(array(
      'mode' => 'report',
      'id' => 'grid-'.$uniqueId,
      'itemsPerPage' => 20,
      'class' => 'ui-widget ui-widget-content report-grid',
      'thClass' => 'ui-widget-header',
      'altRowClass' => 'odd',
      'columns' => array(),
      'includeAllColumns' => true,
      'autoParamsForm' => true,
      'paramsOnly' => false,
      'extraParams' => array(),
      'completeParamsForm' => true,
      'callback' => '',
      'paramsFormButtonCaption' => 'Run Report'
    ), $options);
    return $options;
  }


  /**
   * Returns the query string describing additional sort query params for a
   * data request to populate the report grid.
   */
  private static function get_report_grid_data_request_sort_params($options, $paramKeyNames) {
    $r = '';
    if (isset($_GET[$paramKeyNames['orderby']]))
      $orderby = $_GET[$paramKeyNames['orderby']];
    else
      $orderby = null;
    if ($orderby)
      $r .= "&orderby=$orderby";
    if (isset($_GET[$paramKeyNames['sortdir']]))
      $sortdir = $_GET[$paramKeyNames['sortdir']];
    else
      $sortdir = 'ASC';
    if ($sortdir && $orderby)
      $r .= "&sortdir=$sortdir";
    return $r;
  }

  /**
   * Returns the parameters for the report grid data services call which are embedded in the query string or 
   * default param value data.
   * @param $options
   * @return Array Associative array of parameters.
   */
  private static function get_report_grid_current_param_values($options) {
    // Are there any parameters embedded in the URL?
    $paramKey = 'param-' . (isset($options['id']) ? $options['id'] : '').'-';
    $params = array();
    foreach ($_GET as $key=>$value) {
      if (substr($key, 0, strlen($paramKey))==$paramKey) {
        // We have found a parameter, so put it in the request to the report service
        $param = substr($key, strlen($paramKey));
        $params[$param]=$value;
      }
    }
    if (isset($options['paramDefaults'])) {
      foreach ($options['paramDefaults'] as $key=>$value) {        
        // We have found a parameter, so put it in the request to the report service        
        $params[$key]=$value;
      }
    }
    return $params;
  }

  /**
  * <p>Outputs a div that contains a chart.</p>
  * <p>The chart is rendered by the jqplot plugin.</p>
  * <p>The chart loads its data from a report, table or view indicated by the dataSource parameter, and the
  * method of loading is indicated by xValues, xLabels and yValues. Each of these can be an array to define
  * a multi-series chart. The largest array from these 4 options defines the total series count. If an option
  * is not an array, or the array is smaller than the total series count, then the last option is used to fill
  * in the missing values. For example, by setting:<br/>
  * 'dataSource' => array('report_1', 'report_2'),<br/>
  * 'yValues' => 'count',<br/>
  * 'xLabels' => 'month'<br/>
  * then you get a chart of count by month, with 2 series' loaded separately from report 1 and report 2. Alternatively
  * you can use a single report, with 2 different columns for count to define the 2 series:<br/>
  * 'dataSource' => 'combined_report',<br/>
  * 'yValues' => array('count_1','count_2'),<br/>
  * 'xLabels' => 'month'<br/>
  * The latter is obviuosly slightly more efficient as only a single report is run. Pie charts will always revert to a
  * single series.</p>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>mode</b><br/>
  * Pass report to retrieve the underlying chart data from a report, or direct for an Indicia table or view. Default is report.</li>
  * <li><b>readAuth</b><br/>
  * Read authorisation tokens.</li>
  * <li><b>dataSource</b><br/>
  * Name of the report file or table/view(s) to retrieve underlying data. Can be an array for multi-series charts.</li>
  * <li><b>class</b><br/>
  * CSS class to apply to the outer div.</li>
  * <li><b>headerClass</b><br/>
  * CSS class to apply to the box containing the header.</li>
  * <li><b>height</b><br/>
  * Chart height in pixels.</li>
  * <li><b>width</b><br/>
  * Chart width in pixels.</li>
  * <li><b>chartType</b><br/>
  * Currently supports line, bar or pie.</li>
  * <li><b>rendererOptions</b><br/>
  * Associative array of options to pass to the jqplot renderer.
  * </li>
  * <li><b>legendOptions</b><br/>
  * Associative array of options to pass to the jqplot legend. For more information see links below.  
  * </li>
  * <li><b>seriesOptions</b><br/>
  * For line and bar charts, associative array of options to pass to the jqplot series. For example:<br/>
  * 'seriesOptions' => array(array('label'=>'My first series','label'=>'My 2nd series'))<br/>
  * For more information see links below.
  * </li>
  * <li><b>axesOptions</b><br/>
  * For line and bar charts, associative array of options to pass to the jqplot axes. For example:<br/>
  * 'axesOptions' => array('yaxis'=>array('min' => 0, 'max' => '3', 'tickInterval' => 1))<br/>
  * For more information see links below.
  * </li>
  * <li><b>yValues</b><br/>
  * Report or table field name(s) which contains the data values for the y-axis (or the pie segment sizes). Can be
  * an array for multi-series charts.</li>
  * <li><b>xValues</b><br/>
  * Report or table field name(s) which contains the data values for the x-axis. Only used where the x-axis has a numerical value
  * rather than showing arbitrary categories. Can be an array for multi-series charts.</li>
  * <li><b>xLabels</b><br/>
  * When the x-axis shows arbitrary category names (e.g. a bar chart), then this indicates the report or view/table
  * field(s) which contains the labels. Also used for pie chart segment names. Can be an array for multi-series
  * charts.</li>
  * </ul>
  * @todo look at the ReportEngine to check it is not prone to SQL injection (eg. offset, limit).
  * @link http://www.jqplot.com/docs/files/jqplot-core-js.html#Series
  * @link http://www.jqplot.com/docs/files/jqplot-core-js.html#Axes
  * @link http://www.jqplot.com/docs/files/plugins/jqplot-barRenderer-js.html
  * @link http://www.jqplot.com/docs/files/plugins/jqplot-lineRenderer-js.html
  * @link http://www.jqplot.com/docs/files/plugins/jqplot-pieRenderer-js.html
  * @link http://www.jqplot.com/docs/files/jqplot-core-js.html#Legend
  */
  public static function report_chart($options) {
    $options = array_merge(array(
      'mode' => 'report',
      'id' => 'chartdiv',
      'class' => 'ui-widget ui-widget-content ui-corner-all',
      'headerClass' => 'ui-widget-header ui-corner-all',
      'height' => 400,
      'width' => 400,
      'chartType' => 'line', // bar, pie
      'rendererOptions' => array(),
      'legendOptions' => array(),
      'seriesOptions' => array(),
      'axesOptions' => array()
    ), $options);
    // @todo Check they have supplied a valid set of data & label field names
    self::add_resource('jqplot');
    $opts = array();
    switch ($options['chartType']) {
      case 'bar' :
        self::add_resource('jqplot_bar');
        $renderer='$.jqplot.BarRenderer';
        break;
      case 'pie' :
        self::add_resource('jqplot_pie');
        $renderer='$.jqplot.PieRenderer';
        break;
      // default is line
    }
    if (isset($options['xLabels'])) {
      self::add_resource('jqplot_category_axis_renderer');
    }
    $opts[] = "seriesDefaults:{\n".(isset($renderer) ? "  renderer:$renderer,\n" : '')."  rendererOptions:".json_encode($options['rendererOptions'])."}";
    $opts[] = 'legend:'.json_encode($options['legendOptions']);
    $opts[] = 'series:'.json_encode($options['seriesOptions']);
    // make yValues, xValues, xLabels and dataSources into arrays of the same length so we can treat single and multi-series the same
    $yValues = is_array($options['yValues']) ? $options['yValues'] : array($options['yValues']);
    $dataSources = is_array($options['dataSource']) ? $options['dataSource'] : array($options['dataSource']);
    if (isset($options['xValues'])) $xValues = is_array($options['xValues']) ? $options['xValues'] : array($options['xValues']);
    if (isset($options['xLabels'])) $xLabels = is_array($options['xLabels']) ? $options['xLabels'] : array($options['xLabels']);
    // What is this biggest array? This is our series count.
    $seriesCount = max(
        count($yValues),
        count($dataSources),
        (isset($xValues) ? count($xValues) : 0),
        (isset($xLabels) ? count($xLabels) : 0)
    );
    // any array that is too short must be padded out with the last entry
    if (count($yValues)<$seriesCount) $yValues = array_pad($yValues, $seriesCount, $yValues[count($yValues)-1]);
    if (count($dataSources)<$seriesCount) $dataSources = array_pad($dataSources, $seriesCount, $dataSources[count($dataSources)-1]);
    if (isset($xValues) && count($xValues)<$seriesCount) $xValues = array_pad($xValues, $seriesCount, $xValues[count($xValues)-1]);
    if (isset($xLabels) && count($xLabels)<$seriesCount) $xLabels = array_pad($xLabels, $seriesCount, $xLabels[count($xLabels)-1]);
    // build the series data
    $seriesData = array();
    $lastRequestSource = '';
    for ($idx=0; $idx<$seriesCount; $idx++) {
      // copy the array data back into the options array to make a normal request for report data
      $options['yValues'] = $yValues[$idx];
      $options['dataSource'] = $dataSources[$idx];
      if (isset($xValues)) $options['xValues'] = $xValues[$idx];
      if (isset($xLabels)) $options['xLabels'] = $xLabels[$idx];
      // now request the report data, only if the last request was not for the same data
      if ($lastRequestSource != $options['dataSource'])
        $data=self::get_report_data($options);
      $lastRequestSource = $options['dataSource'];
      $values=array();
      $xLabelsForSeries=array();
      foreach ($data as $row) {
        if (isset($options['xValues']))
          // 2 dimensional data
          $values[] = '['.$row[$options['xValues']].','.$row[$options['yValues']].']';
        else {
          // 1 dimensional data, so we should have labels. For a pie chart these are use as x data values. For other charts they are axis labels.
          if ($options['chartType']=='pie') {
            $values[] = '["'.$row[$options['xLabels']].'",'.$row[$options['yValues']].']';
          } else {
            $values[] = $row[$options['yValues']];
            if (isset($options['xLabels']))
              $xLabelsForSeries[] = $row[$options['xLabels']];
          }
        }
      }
      // each series will occupy an entry in $seriesData
      $seriesData[] = '['.implode(',', $values).']';
    }
    if (isset($options['xLabels']) && $options['chartType']!='pie') {
      // make a renderer to output x axis labels
      $options['axesOptions']['xaxis']['renderer'] = '$.jqplot.CategoryAxisRenderer';
      $options['axesOptions']['xaxis']['ticks'] = $xLabelsForSeries;
    }
    // We need to fudge the json so the renderer class is not a string
    $opts[] = str_replace('"$.jqplot.CategoryAxisRenderer"', '$.jqplot.CategoryAxisRenderer',
        'axes:'.json_encode($options['axesOptions']));

    // Finally, dump out the Javascript with our constructed parameters
    self::$javascript .= "$.jqplot('".$options['id']."',  [".implode(',', $seriesData)."], \n{".implode(",\n", $opts)."});\n";
    $r = '<div class="'.$options['class'].'" style="width:'.$options['width'].'; ">';
    if (isset($options['title']))
      $r .= '<div class="'.$options['headerClass'].'">'.$options['title'].'</div>';
    $r .= '<div id="'.$options['id'].'" style="height:'.$options['height'].';width:'.$options['width'].'; "></div>'."\n";
    $r .= "</div>\n";
    return $r;
  }

 /**
  * Helper function to generate a select control from a Indicia core service query. The select control can
  * be linked to populate itself when an item is selected in another control by specifying the
  * parentControlId and filterField options.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>  *
  * <li><b>table</b><br/>
  * Table name to get data from for the select options if the select is being populated by a service call.</li>
  * <li><b>captionField</b><br/>
  * Field to draw values to show in the control from if the select is being populated by a service call.</li>
  * <li><b>valueField</b><br/>
  * Field to draw values to return from the control from if the select is being populated by a service call. Defaults
  * to the value of captionField.</li>
  * <li><b>extraParams</b><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array if the select is being populated by a service call.</li>
  * <li><b>lookupValues</b><br/>
  * If the select is to be populated with a fixed list of values, rather than via a service call, then the
  * values can be passed into this parameter as an associated array of key=>caption.
  * </li>
  * <li><b>parentControlId</b><br/>
  * Optional. Specifies a parent control for linked lists. If specified then this control is not
  * populated until the parent control's value is set. The parent control's value is used to
  * filter this control's options against the field specified by filterField.</li>
  * <li><b>filterField</b><br/>
  * Optional. Specifies the field to filter this control's content against when using a parent
  * control value to set up linked lists. Defaults to parent_id though this is not active
  * unless a parentControlId is specified.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * <li><b>blankText</b><br/>
  * Optional. If specified then the first option in the drop down is the blank text, used when there is no value.</li>
  * <li><b>template</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for the outer control.</li>
  * <li><b>itemTemplate</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for each item in the control.</li>
  * <li><b>selectedItemTemplate</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for the selected item in the control.</li></ul>
  *
  * @return string HTML code for a select control.
  */
  public static function select()
  {
    $options = self::check_arguments(func_get_args(), array(
        'fieldname', 'table', 'captionField', 'valueField', 'extraParams', 'default'
    ));
    $options = array_merge(
      array(
        'template' => 'select',
        'itemTemplate' => 'select_item'
      ),
      $options
    );
    return self::select_or_listbox($options);
  }

 /**
  * Outputs a spatial reference input box and a drop down select control populated with a list of
  * spatial reference systems for the user to select from. If there is only 1 system available then
  * the system drop down is ommitted since it is not required.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. Name of the database field that spatial reference will be posted to. Defaults to
  * sample:entered_sref. The system field is automatically constructed from this.</li>
  * <li><b>systems</b>
  * Optional. List of spatial reference systems to display. Associative array with the key
  * being the EPSG code for the system or the notation abbreviation (e.g. OSGB), and the value being
  * the description to display.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the spatial reference and system selection control.
  */
  public static function sref_and_system($options) {
    $options = array_merge(array(
      'fieldname'=>'sample:entered_sref'
    ), $options);
    // Force no separate lines for the 2 controls
    if (!array_key_exists('systems',$options) || count($options['systems'])!=1) {
      $srefOptions = array_merge($options, array('suffixTemplate'=>'nosuffix'));
    } else {
      $srefOptions = $options;
    }
    $r = self::sref_textbox($srefOptions);
    // tweak the options passed to the system selector
    $options['fieldname']=$options['fieldname']."_system";
    unset($options['label']);
    if (array_key_exists('systems', $options) && count($options['systems']) == 1) {
      // Hidden field for the system
      $keys = array_keys($options['systems']);
      $r .= "<input type=\"hidden\" id=\"imp-sref-system\" name=\"".$options['fieldname']."\" value=\"".$keys[0]."\" />\n";
    }
    else {
      $r .= self::sref_system_select($options);
    }
    return $r;
  }

 /**
  * Outputs a drop down select control populated with a list of spatial reference systems
  * for the user to select from.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to. Defaults to sample:entered_sref_system.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>systems</b>
  * Optional. List of spatial reference systems to display. Associative array with the key
  * being the EPSG code for the system or the notation abbreviation (e.g. OSGB), and the value being
  * the description to display.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the spatial reference systems selection control.
  */
  public static function sref_system_select($options) {
    global $indicia_templates;
    $options = array_merge(array(
        'fieldname'=>'sample:entered_sref_system',
        'systems'=>array('OSGB'=>lang::get('british national grid'), '4326'=>lang::get('lat long 4326')),
        'id'=>'imp-sref-system'
    ), $options);
    $options = self::check_options($options);
    $opts = "";
    foreach ($options['systems'] as $system=>$caption){
      $selected = ($options['default'] == $system ? 'selected' : '');
      $opts .= str_replace(
          array('{value}', '{caption}', '{selected}'),
          array($system, $caption, $selected),
          $indicia_templates['select_item']
      );
    }
    $options['items'] = $opts;
    return self::apply_template('select', $options);
  }

 /**
  * Creates a textbox for entry of a spatial reference.
  * Also generates the hidden geom field required to properly post spatial data. The
  * box is automatically linked to a map_panel if one is added to the page.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to. Defaults to sample:entered_sref.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>splitLatLong</b><br/>
  * Optional. If set to true, then 2 boxes are created, one for the latitude and one for the longitude.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the spatial reference control.
  * @todo This does not work for reloading data at the moment, when using split lat long mode.
  */
  public static function sref_textbox($options) {
    // get the table and fieldname
    $tokens=explode(':', $options['fieldname']);
    // Merge the default parameters
    $options = array_merge(array(
        'srefField'=>'sample:entered_sref',
        'systemfield'=>'sample:entered_sref_system',
        'hiddenFields'=>true,
        'id'=>'imp-sref',
        'table'=>$tokens[0],
        'default'=>self::check_default_value($options['fieldname']),
        'defaultGeom'=>self::check_default_value($tokens[0].':geom'),
        'splitLatLong'=>false
    ), $options);
    $options = self::check_options($options);
    if ($options['splitLatLong']) {
      // Outputting separate lat and long fields, so we need a few more options
      $options = array_merge(array(
        'labelLat' => lang::get('Latitude'),
        'labelLong' => lang::get('Longitude'),
        'idLat'=>'imp-sref-lat',
        'idLong'=>'imp-sref-long'
      ), $options);
      $r = self::apply_template('sref_textbox_latlong', $options);
    } else
      $r = self::apply_template('sref_textbox', $options);
    return $r;
  }

 /**
  * Helper function to generate a species checklist from a given taxon list.
  *
  * <p>This function will generate a flexible grid control with one row for each species
  * in the specified list. For each row, the control will display the list preferred term
  * for that species, a checkbox to indicate its presence, and a series of cells for a set
  * of occurrence attributes passed to the control.</p>
  *
  * <p>Further, the control will incorporate the functionality to add extra terms to the
  * control from the parent list of the one given. This will take the form of an autocomplete
  * box against the parent list which will add an extra row to the control upon selection.</p>
  *
  * <p>To change the format of the label displayed for each taxon, use the global $indicia_templates variable
  * to set the value for the entry 'taxon_label'. The tags available in the template are {taxon},
  * {authority} and {common}.</p>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>listId</b><br/>
  * Optional. The ID of the taxon_lists record which is to be used to obtain the species or taxon list. This is 
  * required unless lookupListId is provided.</li>
  * <li><b>occAttrs</b><br/>
  * Optional integer array, where each entry corresponds to the id of the desired attribute in the
  * occurrence_attributes table. If omitted, then all the occurrence attributes for this survey are loaded.</li>
  * <li><b>occAttrClasses</b><br/>
  * String array, where each entry corresponds to the css class(es) to apply to the corresponding
  * attribute control (i.e. there is a one to one match with occAttrs). If this array is shorter than
  * occAttrs then all remaining controls re-use the last class.</li>
  * <li><b>extraParams</b><br/>
  * Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><b>lookupListId</b><br/>
  * Optional. The ID of the taxon_lists record which is to be used to select taxa from when adding
  * rows to the grid. If specified, then an autocomplete text box and Add Row button are generated
  * automatically allowing the user to pick a species to add as an extra row.</li>
  * <li><b>header</b><br/>
  * Include a header row in the grid? Defaults to true.</li>
  * <li><b>columns</b><br/>
  * Number of repeating columns of output. For example, a simple grid of species checkboxes could be output in 2 or 3 columns.
  * Defaults to 1.</li>
  * <li><b>checkboxCol</b><br/>
  * Include a presence checkbox column in the grid. If present, then this contains a checkbox for each row which must be ticked for the
  * row to be saved. Otherwise any row containing data in an attribute gets saved.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * <li><b>survey_id</b><br/>
  * Optional. Used to determine which attributes are valid for this website/survey combination</li>
  * <li><b>occurrenceComment</b><br/>
  * Optional. If set to true, then an occurrence comment input field is included on each row.</li>
  * <li><b>occurrenceImages</b><br/>
  * Optional. If set to true, then images can be uploaded for each occurrence row. Currently not supported for 
  * multi-column grids.</li>
  * <li><b>attrCellTemplate</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for each cell containing an attribute input control. Valid replacements are {label}, {class} and {content}.
  * Default is attribute_cell.</li>
  * <li><b>PHPtaxonLabel</b></li>
  * If set to true, then the taxon_label template should contain a PHP statement that returns the HTML to display for each
  * taxon's label. Otherwise the template should be plain HTML. Defaults to false.
  * </ul>
  */
  public static function species_checklist()
  {
    global $indicia_templates;
    $options = self::check_arguments(func_get_args(), array('listId', 'occAttrs', 'readAuth', 'extraParams', 'lookupListId'));
    $options = self::get_species_checklist_options($options);
    if ($options['columns']>1 && $options['occurrenceImages'])
      throw new Exception('The species_checklist control does not support having more than one occurrence per row (columns option > 0) '.
          'at the same time has having the occurrenceImages option enabled.');
    self::add_resource('json');
    self::add_resource('autocomplete');
	  if ($options['occurrenceImages']) {
	    self::add_resource('plupload');
      // store some globals that we need later when creating uploaders
      $relpath = self::relative_client_helper_path();
      $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
      self::$javascript .= "uploadScript = '".dirname($_SERVER['PHP_SELF']) . '/' . $relpath . "upload.php';\n";
      self::$javascript .= "destinationFolder = '".dirname($_SERVER['PHP_SELF']) . '/' . $relpath . $interim_image_folder."';\n";
      self::$javascript .= "swfAndXapFolder = '".$relpath . "plupload/';\n";
      self::$javascript .= "jsPath = '".self::$js_path."';\n";
    }
    $occAttrControls = array();
    $occAttrs = array();
    $taxaThatExist = array();
    
    // Load any existing sample's occurrence data into $entity_to_load
    if (isset(self::$entity_to_load['sample:id']))
      self::preload_species_checklist_occurrences(self::$entity_to_load['sample:id'], $options['readAuth'], $options['occurrenceImages']);
    // load the full list of species for the grid, including the main checklist plus any additional species in the reloaded occurrences.  
	  $taxalist = self::get_species_checklist_taxa_list($options, $taxaThatExist);
    // If we managed to read the species list data we can proceed
    if (! array_key_exists('error', $taxalist)) {
      $attributes = self::getAttributes(array(
          'id' => null
           ,'valuetable'=>'occurrence_attribute_value'
           ,'attrtable'=>'occurrence_attribute'
           ,'key'=>'occurrence_id'
           ,'fieldprefix'=>"sc:{ttlId}::occAttr"
           ,'extraParams'=>$options['readAuth']
           ,'survey_id'=>array_key_exists('survey_id', $options) ? $options['survey_id'] : null
      ));
    
      // Get the attribute and control information required to build the custom occurrence attribute columns
      self::species_checklist_prepare_attributes($options, $attributes, $occAttrControls, $occAttrs);
      $grid = '';	
      if (isset($options['lookupListId'])) {
        $grid .= self::get_species_checklist_clonable_row($options, $occAttrControls, $attributes);
      }
      $grid .= '<table class="ui-widget ui-widget-content species-grid '.$options['class'].'" id="'.$options['id'].'">';
      $grid .= self::get_species_checklist_header($options, $occAttrs);
      $rows = array();
      $rowIdx = 0;
	  
      foreach ($taxalist as $taxon) {
        $id = $taxon['id'];
        // Get the cell content from the taxon_label template
        $firstCell = self::mergeParamsIntoTemplate($taxon, 'taxon_label');
        // If the taxon label template is PHP, evaluate it.
        if ($options['PHPtaxonLabel']) $firstCell=eval($firstCell);
        // Now create the table cell to contain this.
        $colspan = isset($options['lookupListId']) ? ' colspan="2"' : '';
        $row = str_replace('{content}', $firstCell,
            str_replace('{colspan}', $colspan, $indicia_templates['taxon_label_cell'])
        );

        $existing_record_id = false;
        $search = preg_grep("/^sc:$id:[0-9]*:present$/", array_keys(self::$entity_to_load));
        if (count($search)===1) {
          // we have to implode the search result as the key can be not zero, then strip out the stuff other than the occurrence Id.
          $existing_record_id = str_replace(array("sc:$id:",":present"), '', implode('', $search));
        }
        if ($options['checkboxCol']=='true') {
          if (self::$entity_to_load!=null && array_key_exists("sc:$id:$existing_record_id:present", self::$entity_to_load)) {
            $checked = ' checked="checked"';			
          } else {
            $checked='';
          }
          $row .= "\n<td class=\"scPresenceCell\"><input type=\"checkbox\" class=\"scPresence\" name=\"sc:$id:$existing_record_id:present\" $checked /></td>";
        }
        foreach ($occAttrControls as $attrId => $control) {
          if ($existing_record_id) {
            $search = preg_grep("/^sc:$id:$existing_record_id:occAttr:$attrId".'[:[0-9]*]?$/', array_keys(self::$entity_to_load));
            $ctrlId = (count($search)===1) ? implode('', $search) : $attributes[$attrId]['fieldname'];
          } else {
            $ctrlId = str_replace('{ttlId}', $id, $attributes[$attrId]['fieldname']);
          }
          if (isset(self::$entity_to_load[$ctrlId])) {
            $existing_value = self::$entity_to_load[$ctrlId];
          } elseif (array_key_exists('default', $attributes[$attrId])) {
		        // this case happens when reloading an existing record
            $existing_value = $attributes[$attrId]['default'];
          } else
            $existing_value = '';
          // inject the field name into the control HTML
          $oc = str_replace('{fieldname}', $ctrlId, $control);
          if (!empty($existing_value)) {
            // For select controls, specify which option is selected from the existing value
            if (substr($oc, 0, 7)=='<select') {			  
              $oc = str_replace('value="'.$existing_value.'"',
                  'value="'.$existing_value.'" selected="selected"', $oc);			  
            } else {
              $oc = str_replace('value=""', 'value="'.$existing_value.'"', $oc);
            }			
            $error = self::check_errors("occAttr:$attrId");
            if ($error) {
              $oc = str_replace("class='", "class='ui-state-error ", $oc);
              $oc .= $error;
            }
          }
          $row .= str_replace(array('{label}', '{content}'), array(lang::get($attributesForThisRow[$attrId]['caption']), $oc), $indicia_templates[$options['attrCellTemplate']]);
        }
        if ($options['occurrenceComment']) {
          $row .= "\n<td class=\"ui-widget-content scCommentCell\"><input class=\"control-width-4 scComment\" type=\"text\" name=\"sc:$id:$existing_record_id:occurrence:comment\" ".
		      "value=\"".self::$entity_to_load["sc:$id:$existing_record_id:occurrence:comment"]."\" /></td>";
        }
        if ($options['occurrenceImages']) {
          $existingImages = preg_grep("/^sc:$id:$existing_record_id:occurrence_image:id:[0-9]*$/", array_keys(self::$entity_to_load));
          if (count($existingImages)===0)
            $row .= "\n<td class=\"ui-widget-content scImageLinkCell\"><a href=\"\" class=\"add-image-link scImageLink\" id=\"add-images:$id:$existing_record_id\">".lang::get('add images').'</a></td>';
          else
            $row .= "\n<td class=\"ui-widget-content scImageLinkCell\"><a href=\"\" class=\"hide-image-link scImageLink\" id=\"hide-images:$id:$existing_record_id\">".lang::get('hide images').'</a></td>';
        }
        // Are we in the first column? Note this is disabled if using occurrenceImages as it adds extra rows and messes things up.
        if ($options['occurrenceImages'] || $rowIdx < count($taxalist)/$options['columns']) {
          $rows[$rowIdx]=$row;
        } else {
          $rows[$rowIdx % (ceil(count($taxalist)/$options['columns']))] .= $row;
        }
        $rowIdx++;
        if ($options['occurrenceImages']) {
          // If there are existing images for this row, display the image control
          if (count($existingImages)>0) {
            $totalCols = ($options['lookupListId'] ? 2 : 1) + ($options['checkboxCol'] ? 1 : 0) + ($options['occurrenceImages'] ? 1 : 0) + count($occAttrControls);
            $rows[$rowIdx]='<td colspan="'.$totalCols.'">'.data_entry_helper::file_box(array(
              'table'=>"sc:$id:$existing_record_id:occurrence_image",
              'label'=>lang::get('Upload your photos'),
              'maxFileCount' => 3              
            )).'</td>';
            $rowIdx++;
          }          
        }
      }
      $grid .= "<tbody>\n<tr>".implode("</tr>\n<tr>", $rows)."</tr>\n";
      $grid .= '</tbody></table>';
      // If the lookupListId parameter is specified then the user is able to add extra rows to the grid,
      // selecting the species from this list. Add the required controls for this.
      if (isset($options['lookupListId'])) {
        // Javascript to add further rows to the grid
        self::add_resource('addrowtogrid');
        self::$javascript .= "addRowToGrid('".parent::$base_url."index.php/services/data"."', '".
            $options['id']."', '".$options['lookupListId']."', {'auth_token' : '".
            $options['readAuth']['auth_token']."', 'nonce' : '".$options['readAuth']['nonce']."'},".
            "'".$indicia_templates['taxon_label']."');\r\n";
      }
      if ($options['checkboxCol']=='true') { // need to tag if checkboxes active so can delete entry if needed
        $grid .= "<input type='hidden' id='control:checkbox' name='control:checkbox' value='YES'/>";
      }
      return $grid;
    } else {
      return $taxalist['error'];
    }

  }
  
  /**
   * Normally, the species checklist will handle loading the list of occurrences from the database automatically.
   * However, when a form needs access to occurrence data before loading the species checklist, this method 
   * can be called to preload the data. The data is loaded into data_entry_helper::$entity_to_load and the count
   * of occurrences loaded is returned.
   * @param int $sampleId ID of the sample to load
   * @param array $readAuth Read authorisation array
   * @return int Number of occurrences that were loaded.
   */
  public static function preload_species_checklist_occurrences($sampleId, $readAuth, $loadImages) {
    $occurrenceIds = array();
    // don't load from the db if there are validation errors, since the $_POST will already contain all the 
    // data we need.
    if (is_null(self::$validation_errors)) {
      $occurrences = self::get_population_data(array(
        'table' => 'occurrence',
        'extraParams' => $readAuth + array('view'=>'detail','sample_id'=>$sampleId,'deleted'=>'f'),
        'nocache' => true
      ));
      foreach($occurrences as $occurrence){
        self::$entity_to_load['occurrence:record_status']=$occurrence['record_status'];          
        self::$entity_to_load['sc:'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':present'] = true;
        self::$entity_to_load['sc:'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':occurrence:comment'] = $occurrence['comment'];
        self::$entity_to_load['occurrence:taxa_taxon_list_id']=$occurrence['taxa_taxon_list_id'];
        self::$entity_to_load['occurrence:taxa_taxon_list_id:taxon']=$occurrence['taxon'];
        // Keep a list of all Ids
        $occurrenceIds[$occurrence['id']] = $occurrence['taxa_taxon_list_id'];
      }
      // load the attribute values into the entity to load as well
      $attrValues = self::get_population_data(array(
        'table' => 'occurrence_attribute_value',
        'extraParams' => $readAuth + array('occurrence_id' => array_keys($occurrenceIds)),
        'nocache' => true
      ));
      foreach($attrValues as $attrValue) {
        self::$entity_to_load['sc:'.$occurrenceIds[$attrValue['occurrence_id']].':'.$attrValue['occurrence_id'].':occAttr:'.$attrValue['occurrence_attribute_id'].':'.$attrValue['id']]
            = $attrValue['raw_value'];
      }
      if ($loadImages) {
        $images = self::get_population_data(array(
          'table' => 'occurrence_image',
          'extraParams' => $readAuth + array('occurrence_id' => array_keys($occurrenceIds)),
          'nocache' => true
        ));
        foreach($images as $image) {
          self::$entity_to_load['sc:'.$occurrenceIds[$image['occurrence_id']].':'.$image['occurrence_id'].':occurrence_image:id:'.$image['id']]
              = $image['id'];
          self::$entity_to_load['sc:'.$occurrenceIds[$image['occurrence_id']].':'.$image['occurrence_id'].':occurrence_image:path:'.$image['id']]
              = $image['path'];
          self::$entity_to_load['sc:'.$occurrenceIds[$image['occurrence_id']].':'.$image['occurrence_id'].':occurrence_image:caption:'.$image['id']]
              = $image['caption'];
        }
      }
    }
    return count($occurrenceIds);
  }

  /**
   * Retrieve the grid header row for the species checklist grid control.
   * @param array $options Control options array.
   * @param array $occAttrs Array of custom attributes included in the grid.
   * @return string Html for the <thead> element.
   */
  private static function get_species_checklist_header($options, $occAttrs) {
    $r = '';
    if ($options['header']) {
      $r .= "<thead class=\"ui-widget-header\"><tr>";
      for ($i=0; $i<$options['columns']; $i++) {
        $colspan = isset($options['lookupListId']) ? ' colspan="2"' : '';
        $r .= "<th$colspan>".lang::get('species_checklist.species')."</th>";
        if ($options['checkboxCol']=='true') {
          $r .= "<th>".lang::get('species_checklist.present')."</th>";
        }
        foreach ($occAttrs as $a) {
          $r .= "<th>$a</th>";
        }
        if ($options['occurrenceComment']) {
          $r .= "<th>".lang::get('Comment')."</th>";
        }
        if ($options['occurrenceImages']) {
          $r .= "<th>".lang::get('Images')."</th>";
        }
      }
      $r .= '</tr></thead>';
      return $r;
    }
  }
  /**
   * Private method to build the list of taxa to add to a species checklist grid.
   * @param array $options Options array for the control
   * @param array $taxaThatExist Array that is modified by this method to contain a list of
   * the taxa_taxon_list_ids for rows which have existing data to load.
   * @return array The taxon list to store in the grid.
   */
  private static function get_species_checklist_taxa_list($options, &$taxaThatExist) {
    // Get the list of species that are always added to the grid
    if (isset($options['listId']) && !empty($options['listId']))
      $taxalist = self::get_population_data($options);
    else
      $taxalist = array();
    // build a list of the ids we have got from the default list.
    $taxaLoaded = array();
    foreach ($taxalist as $taxon)
      $taxaLoaded[] = $taxon['id'];
    // If there are any extra taxa to add to the list, get their details
    if(self::$entity_to_load) {
      // copy the options array so we can modify it
      $extraTaxonOptions = array_merge(array(), $options);
      // We don't want to filter the taxa to be added, because if they are in the sample, then they must be included whatever.
      unset($extraTaxonOptions['extraParams']['taxon_list_id']);
      unset($extraTaxonOptions['extraParams']['preferred']);
      unset($extraTaxonOptions['extraParams']['language_iso']);
      // create an array to hold the IDs, so that get_population_data can construct a single IN query, faster
      // than multiple requests.
      $extraTaxonOptions['extraParams']['id'] = array();
      foreach(self::$entity_to_load as $key => $value) {
        $parts = explode(':', $key);
        // Is this taxon attribute data?
        if (count($parts) > 2 && $parts[0] == 'sc' && $parts[1]!='{ttlId}') {
          // track that this taxon row has existing data to load
          if (!in_array($parts[1], $taxaThatExist)) $taxaThatExist[] = $parts[1];
          // If not already loaded
          if(!in_array($parts[1], $taxaLoaded)) {
            $taxaLoaded[] = $parts[1];
            // store the id of the taxon in the array, so we can load them all in one go later
            $extraTaxonOptions['extraParams']['id'][]=$parts[1];
          }
        }
      }
      // append the taxa to the list to load into the grid
      $taxalist = array_merge($taxalist, self::get_population_data($extraTaxonOptions));
    }
  	return $taxalist;
  }

  /**
   * Internal method to prepare the options array for a species_checklist control.
   *
   * @param array $options Options array passed to the control
   * @return array Options array prepared with defaults and other values required by the control.
   */
  private static function get_species_checklist_options($options) {
    // validate some options
    if (!isset($options['listId']) && !isset($options['lookupListId'])) 
      throw new Exception('Either the listId or lookupListId parameters must be provided for a species checklist.');
    // Apply default values
    $options = array_merge(array(
        'header'=>'true',
        'columns'=>1,
        'checkboxCol'=>'true',
        'attrCellTemplate'=>'attribute_cell',
        'PHPtaxonLabel' => false,
        'occurrenceComment' => false,
        'occurrenceImages' => false,
        'id' => 'species-grid-'.rand(0,1000)
    ), $options);
    
    $options['extraParams']['orderby'] = 'taxon';
    // If filtering for a language, then use any taxa of that language. Otherwise, just pick the preferred names.
    if (!isset($options['extraParams']['language_iso']))
      $options['extraParams']['preferred'] = 't';
    if (array_key_exists('listId', $options) && !empty($options['listId'])) {
      $options['extraParams']['taxon_list_id']=$options['listId'];
    }
    if (array_key_exists('readAuth', $options)) {
      $options['extraParams'] += $options['readAuth'];
    } else {
      $options['readAuth'] = array(
          'auth_token' => $options['extraParams']['auth_token'],
          'nonce' => $options['extraParams']['nonce']
      );
    }
    $options['table']='taxa_taxon_list';
  	return $options;
  }

  /**
   * Internal function to prepare the list of occurrence attribute columns for a species_checklist control.
   */
  private static function species_checklist_prepare_attributes($options, $attributes, &$occAttrControls, &$occAttrs) {
    $idx=0;  
    if (array_key_exists('occAttrs', $options))
      $attrs = $options['occAttrs'];
    else
      // There is no specified list of occurrence attributes, so use all available for the survey
      $attrs = array_keys($attributes);
    foreach ($attrs as $occAttrId) {
      // test that this occurrence attribute is linked to the survey
      if (!isset($attributes[$occAttrId]))
        throw new Exception('The occurrence attributes requested for the grid are not linked with the survey.');
      $attrDef = $attributes[$occAttrId];
      $occAttrs[$occAttrId] = $attrDef['caption'];
      // Get the control class if available. If the class array is too short, the last entry gets reused for all remaining.
      $class = self::species_checklist_occ_attr_class($options, $idx, $attrDef['caption']); // provide a default class based on the control caption
      // Build the correct control
      switch ($attrDef['data_type']) {
        case 'L':
          $tlId = $attrDef['termlist_id'];
          $occAttrControls[$occAttrId] = data_entry_helper::select(array(
            'fieldname' => '{fieldname}',
            'table'=>'termlists_term',
            'captionField'=>'term',
            'valueField'=>'id',
            'extraParams' => $options['readAuth'] + array('termlist_id' => $tlId),
            'class' => $class,
            'blankText' => ''
          ));
          break;
        case 'D':
        case 'V':
          // Date-picker control
          $occAttrControls[$occAttrId] = '<input type="text" class="date $class" ' .
            'id="{fieldname}" name="{fieldname}" ' .
            "value=\"".lang::get('click here')."\"/>";
          break;
        default:
          $occAttrControls[$occAttrId] =
            "<input type=\"text\" id=\"{fieldname}\" name=\"{fieldname}\" class=\"$class\" value=\"\" />";
          break;
      }
      $idx++;      
    }
  }
  
  /**
   * Returns the class to apply to a control for an occurrence attribute, identified by an index. 
   * @access private
   */
  private static function species_checklist_occ_attr_class($options, $idx, $caption) {
    return (array_key_exists('occAttrClasses', $options) && $idx<count($options['occAttrClasses'])) ? 
          $options['occAttrClasses'][$idx] : 
          'sc'.str_replace(' ', '', ucWords($caption)); // provide a default class based on the control caption
  }

  /**
   * When the species checklist grid has a lookup list associated with it, this is a
   * secondary checklist which you can pick species from to add to the grid. As this happens,
   * a hidden table is used to store a clonable row which provides the template for new rows
   * to be added to the grid.
   */
  private static function get_species_checklist_clonable_row($options, $occAttrControls, $attributes) {
    global $indicia_templates;
    $r = "<table style='display: none'><tbody><tr id='".$options['id']."-scClonableRow'>";
    $colspan = isset($options['lookupListId']) ? ' colspan="2"' : '';
    $r .= str_replace('{colspan}', $colspan, $indicia_templates['taxon_label_cell']);
    if ($options['checkboxCol']=='true') {
      $r .= '<td class="scPresenceCell"><input type="checkbox" class="scPresence" name="" value="" /></td>';
    }
	$idx = 0;
    foreach ($occAttrControls as $attrId=>$oc) {
	  $class = self::species_checklist_occ_attr_class($options, $idx, $attributes[$attrId]['caption']); 
      $r .= str_replace(array('{content}', '{class}'), 
          array(str_replace('{fieldname}', "sc:{ttlId}::occAttr:$attrId", $oc), $class.'Cell'),
          $indicia_templates['attribute_cell']
      );
	  $idx++;
    }
    if ($options['occurrenceComment']) {
      $r .= '<td class="ui-widget-content scCommentCell"><input class="control-width-4 scComment" type="text" name="sc:{ttlId}::occurrence:comment" value="" /></td>';
    } 
    if ($options['occurrenceImages']) {
      // Add a link, but make it display none for now as we can't link images till we know what species we are linking to.
      $r .= '<td class="ui-widget-content scImageLinkCell"><a href="" class="add-image-link scImageLink" style="display: none" id="images:{ttlId}:">'.lang::get('add images').'</a></td>';
    }
    $r .= "</tr></tbody></table>";
    return $r;
  }

 /**
  * Helper function to output an HTML textarea. This includes re-loading of existing values
  * and displaying of validation error messages.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to, e.g. occurrence:image.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>rows</b><br/>
  * Optional. HTML rows attribute. Defaults to 4.</li>
  * <li><b>cols</b><br/>
  * Optional. HTML cols attribute. Defaults to 80.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the textarea control.
  */
  public static function textarea() {
    $options = self::check_arguments(func_get_args(), array('fieldname'));
    $options = array_merge(array(
        'cols'=>'80',
        'rows'=>'4'
    ), $options);
    return self::apply_template('textarea', $options);
  }

 /**
  * Helper function to output an HTML text input. This includes re-loading of existing values
  * and displaying of validation error messages.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the text input control.
  */
  public static function text_input() {
    $options = self::check_arguments(func_get_args(), array('fieldname'));
    $options = array_merge(array(
      'default'=>''
    ), $options);
    return self::apply_template('text_input', $options);
  }

  /**
  * Helper function to generate a treeview from a given list
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to, for example 'occurrence:taxa_taxon_list_id'.
  * NB the tree itself will have an id of "tr$fieldname".</li>
  * <li><b>id</b><br/>
  * Optional. ID of the control. Defaults to the fieldname.</li>
  * <li><b>table</b><br/>
  * Required. Name (Kohana-style) of the database entity to be queried.</li>
  * <li><b>view</b><br/>
  * Name of the view of the table required (list, detail).</li>
  * <li><b>captionField</b><br/>
  * Field to draw values to show in the control from.</li>
  * <li><b>valueField</b><br/>
  * Field to draw values to return from the control from. Defaults
  * to the value of $captionField.</li>
  * <li><b>parentField</b><br/>
  * Field used to indicate parent within tree for a record.</li>
  * <li><b>default</b><br/>
  * Initial value to set the control to (not currently used).</li>
  * <li><b>extraParams</b><br/>
  * Array of key=>value pairs which will be passed to the service
  * as GET parameters. Needs to specify the read authorisation key/value pair, needed for making
  * queries to the data services.</li>
  * <li><b>extraClass</b><br/>
  * main class to be added to UL tag - currently can be treeview, treeview-red,
  * treeview_black, treeview-gray. The filetree class although present, does not work properly.</li>
  * </ul>
  *
  * TODO
  * Need to do initial value.
  */
  public static function treeview()
  {
    global $indicia_templates;
    $options = self::check_arguments(func_get_args(), array('fieldname', 'table', 'captionField', 'valueField',
        'topField', 'topValue', 'parentField', 'default', 'extraParams', 'class'));
    self::add_resource('treeview');
    // Declare the data service
    $url = parent::$base_url."index.php/services/data";
    // Setup some default values
    $options = array_merge(array(
      'valueField'=>$options['captionField'],
      'class'=>'treeview',
      'id'=>$options['fieldname'],
      'view'=>'list'
    ), $options);
    $default = self::check_default_value($options['fieldname'],
        array_key_exists('default', $options) ? $options['default'] : null);
    // Do stuff with extraParams
    $sParams = '';
    foreach ($options['extraParams'] as $a => $b){
      $sParams .= "$a : '$b',";
    }
    // lop the comma off the end
    $sParams = substr($sParams, 0, -1);
    extract($options, EXTR_PREFIX_ALL, 'o');
    self::$javascript .= "jQuery('#tr$o_fieldname').treeview({
      url: '$url/$o_table',
      extraParams : {
        orderby : '$o_captionField',
        mode : 'json',
        $sParams
      },
      valueControl: '$o_fieldname',
      valueField: '$o_valueField',
      captionField: '$o_captionField',
      view: '$o_view',
      parentField: '$o_parentField',
      dataType: 'jsonp',
      nodeTmpl: '".$indicia_templates['treeview_node']."'
    });\n";

    $tree = '<input type="hidden" class="hidden" id="'.$o_id.'" name="'.$o_fieldname.'" /><ul id="tr'.$o_id.'" class="'.$o_class.'"></ul>';
    $tree .= self::check_errors($o_fieldname);
    return $tree;
  }

  /**
  * Helper function to generate a browser control from a given list. The browser
  * behaves similarly to a treeview, except that the child lists are appended to the control
  * rather than inserted as list children. This allows controls to be created which allow
  * selection of an item, then the control is updated with the new list of options after each
  * item is clicked.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to, for example 'occurrence:taxa_taxon_list_id'.
  * NB the tree itself will have an id of "tr$fieldname".</li>
  * <li><b>id</b><br/>
  * Optional. ID of the hidden input which contains the value. Defaults to the fieldname.</li>
  * <li><b>divId</b><br/>
  * Optional. ID of the outer div. Defaults to div_ plus the fieldname.</li>
  * <li><b>table</b><br/>
  * Required. Name (Kohana-style) of the database entity to be queried.</li>
  * <li><b>view</b><br/>
  * Name of the view of the table required (list, detail).</li>
  * <li><b>captionField</b><br/>
  * Field to draw values to show in the control from.</li>
  * <li><b>valueField</b><br/>
  * Field to draw values to return from the control from. Defaults
  * to the value of $captionField.</li>
  * <li><b>parentField</b><br/>
  * Field used to indicate parent within tree for a record.</li>
  * <li><b>default</b><br/>
  * Initial value to set the control to (not currently used).</li>
  * <li><b>extraParams</b><br/>
  * Array of key=>value pairs which will be passed to the service
  * as GET parameters. Needs to specify the read authorisation key/value pair, needed for making
  * queries to the data services.</li>
  * <li><b>outerClass</b><br/>
  * Class to be added to the control's outer div.</li>
  * <li><b>class</b><br/>
  * Class to be added to the input control (hidden).</li>
  * <li><b>default</b><br/>
  * Optional. The default value for the underlying control.</li>
  * </ul>
  *
  * TODO
  * Need to do initial value.
  */
  public static function tree_browser($options) {
    global $indicia_templates;
    self::add_resource('treeBrowser');
    // Declare the data service
    $url = parent::$base_url."index.php/services/data";
    // Apply some defaults to the options
    $options = array_merge(array(
      'valueField' => $options['captionField'],
      'id' => $options['fieldname'],
      'divId' => 'div_'.$options['fieldname'],
      'singleLayer' => true,
      'outerClass' => 'ui-widget ui-corner-all ui-widget-content tree-browser',
      'listItemClass' => 'ui-widget ui-corner-all ui-state-default',
      'default' => self::check_default_value($options['fieldname'],
          array_key_exists('default', $options) ? $options['default'] : ''),
      'view'=>'list'
    ), $options);
    $escaped_divId=str_replace(':','\\\\:',$options['divId']);
    // Do stuff with extraParams
    $sParams = '';
    foreach ($options['extraParams'] as $a => $b){
      $sParams .= "$a : '$b',";
    }
    // lop the comma off the end
    $sParams = substr($sParams, 0, -1);
    extract($options, EXTR_PREFIX_ALL, 'o');
    self::$javascript .= "
$('div#$escaped_divId').indiciaTreeBrowser({
  url: '$url/$o_table',
  extraParams : {
    orderby : '$o_captionField',
    mode : 'json',
    $sParams
  },
  valueControl: '$o_id',
  valueField: '$o_valueField',
  captionField: '$o_captionField',
  view: '$o_view',
  parentField: '$o_parentField',
  nodeTmpl: '".$indicia_templates['tree_browser_node']."',
  singleLayer: '$o_singleLayer',
  backCaption: '".lang::get('back')."',
  listItemClass: '$o_listItemClass',
  defaultValue: '$o_default'
});\n";
    return self::apply_template('tree_browser', $options);
  }

  /**
  * Insert buttons which, when clicked, displays the next or previous tab. Insert this inside the tab divs
  * on each tab you want to have a next or previous button, excluding the last tab.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>divId</b><br/>
  * The id of the div which is tabbed and whose next tab should be selected.</li>
  * <li><b>captionNext</b><br/>
  * Optional. The untranslated caption of the next button. Defaults to next step.</li>
  * <li><b>captionPrev</b><br/>
  * Optional. The untranslated caption of the previous button. Defaults to prev step.</li>
  * <li><b>class</b><br/>
  * Optional. Additional classes to add to the div containing the buttons. Use left, right or
  * centre to position the div, making sure the containing element is either floated, or has
  * overflow: auto applied to its style. Default is right.</li>
  * <li><b>buttonClass</b><br/>
  * Class to add to the button elements.</li>
  * <li><b>page</b><br/>
  * Specify first, middle or last to indicate which page this is for. Use middle (the default) for
  * all pages other than the first or last.</li>
  * </ul>
  *
  * @link http://docs.jquery.com/UI/Tabs
  */
  public static function wizard_buttons($options=array()) {
    // Default captions
    $options = array_merge(array(
      'captionNext' => 'next step',
      'captionPrev' => 'prev step',
      'captionSave' => 'save',
      'buttonClass' => 'ui-widget-content ui-state-default ui-corner-all indicia-button',
      'class'       => 'right',
      'page'        => 'middle',
      'suffixTemplate' => 'nosuffix'
    ), $options);
    $options['class'] .= ' buttons';
    // localise the captions
    $options['captionNext'] = lang::get($options['captionNext']);
    $options['captionPrev'] = lang::get($options['captionPrev']);
    $options['captionSave'] = lang::get($options['captionSave']);
    // Output the buttons
    $r = '<div class="'.$options['class'].'">';
    $buttonClass=$options['buttonClass'];
    if (array_key_exists('divId', $options)) {
      if ($options['page']!='first') {
        $options['class']=$buttonClass." tab-prev";
        $r .= self::apply_template('tab_prev_button', $options);
      }
      if ($options['page']!='last') {
        $options['class']=$buttonClass." tab-next";
        $r .= self::apply_template('tab_next_button', $options);
      } else {
        $options['class']=$buttonClass." tab-submit";
        $r .= self::apply_template('submit_button', $options);
      }
    }
    $r .= '</div><div style="clear:both"></div>';
    return $r;
  }

/********************************/
/* End of main controls section */
/********************************/

  /**
   * Returns the browser name and version information
   * @param $agent Agent string, optional. If not suplied, then the http user agent is used.
   * @return array Browser information array. Contains name and version elements.
   */
  public static function get_browser_info($agent=null) {
    $browsers = array("firefox", "msie", "opera", "chrome", "safari",
                            "mozilla", "seamonkey",    "konqueror", "netscape",
                            "gecko", "navigator", "mosaic", "lynx", "amaya",
                            "omniweb", "avant", "camino", "flock", "aol");
    if (!$agent)
      $agent = $_SERVER['HTTP_USER_AGENT'];
    $agent = strtolower($agent);
    foreach($browsers as $browser)
    {
        if (preg_match("#($browser)[/ ]?([0-9.]*)#", $agent, $match))
        {
            $r['name'] = $match[1] ;
            $r['version'] = $match[2] ;
            break ;
        }
    }
    return $r;
  }

 /**
  * Call the enable_validation method to turn on client-side validation for any controls with
  * validation rules defined. 
  * To specify validation on each control, set the control's options array
  * to contain a 'validation' entry. This must be set to an array of validation rules in Indicia
  * validation format. For example, 'validation' => array('required', 'email').
  * @param string @form_id Id of the form the validation is being attached to.
  */
  public static function enable_validation($form_id) {
    self::$validated_form_id = $form_id;
    self::add_resource('validation');
  }

 /**
  * Takes a list of validation rules in Kohana/Indicia format, and converts them to the jQuery validation
  * plugin metadata format.
  * @param array $rules List of validation rules to be converted.
  * @return string Validation metadata classes to add to the input element.
  * @access private
  * @todo Implement a more complete list of validation rules.
  */
  private static function convert_to_jquery_val_metadata($rules) {
    $converted = array();
    foreach ($rules as $rule) {
      // Detect the rules that can simply be passed through
      if    ($rule=='required'
          || $rule=='dateISO'
          || $rule=='email'
          || $rule=='url') {
        $converted[] = $rule;
       } else if ($rule='digit') {
         $converted[] = 'digits';
       }
       // Now any rules which need parsing or convertion
    }
    return implode(' ', $converted);
  }

/**
 * Removes any data entry values persisted into the $_SESSION by Indicia.
 *
 * @link	http://code.google.com/p/indicia/wiki/TutorialDataEntryWizard
 */
  public static function clear_session() {
    foreach ($_SESSION as $name=>$value) {
      if (substr($name, 0, 8)=='indicia:') {
        unset($_SESSION[$name]);
      }
    }
  }

  /**
   * Adds the data from the $_POST array into the session. Call this method when arriving at the second
   * and subsequent pages of a data entry wizard to keep the previous page's data available for saving later.
   *
   * @link	http://code.google.com/p/indicia/wiki/TutorialDataEntryWizard
   */
  public static function add_post_to_session () {
    foreach ($_POST as $name=>$value) {
      $_SESSION['indicia:'.$name]=$value;
    }
  }

  /**
   * Returns an array constructed from all the indicia variables that have previously been stored
   * in the session.
   *
   * @link	http://code.google.com/p/indicia/wiki/TutorialDataEntryWizard
   */
  public static function extract_session_array () {
    $result = array();
    foreach ($_SESSION as $name=>$value) {
      if (substr($name, 0, 8)=='indicia:') {
        $result[substr($name, 8)]=$value;
      }
    }
    return $result;
  }

  /**
  * Retrieves a data value from the Indicia Session data
  *
  * @param string $name Name of the session value to retrieve
  * @param string $default Default value to return if not set or empty
  * @link	http://code.google.com/p/indicia/wiki/TutorialDataEntryWizard
  */
  public static function get_from_session($name, $default='') {
    $result = '';
    if (array_key_exists("indicia:$name", $_SESSION)) {
      $result = $_SESSION["indicia:$name"];
    }
    if (!$result) {
      $result = $default;
    }
    return $result;
  }

  /**
   * Internal method to handle the deprecated use of a list of arguments, for backwards compatibility.
   * Converts the list of arguments to an options array unless the first argument is already
   * an options array. The arguments are mapped to the array in the order specified by the mapping.
   */
  private static function check_arguments(array $args, array $mapping=null) {
    if (count($args)>0) {
      if (is_array($args[0])) {
        // First argument is an options array
        $options = $args[0];
      } elseif ($mapping) {
        // arguments are passed individuall using deprecated method - so for backward compatibility we'll
        // map them to an options array
        $options=array();
        $i=0;
        foreach ($args as $arg) {
          $options[$mapping[$i]]=$arg;
          $i++;
        }
      }
    }
    if (isset($options)) {
      return self::check_options($options);
    } else {
      return array();
    }
  }

  /**
   * Checks that an Id is supplied, if not, uses the fieldname as the id. Also checks if a
   * captionField is supplied, and if not uses a valueField if available. Finally, gets the control's
   * default value.
   * If the control is set to be remembered, then adds it to the list of remembered fields.
   */
  private static function check_options($options) {
    // force some defaults to be present in the options
    $options = array_merge(array(
        'class'=>'',
        'multiple'=>''
        ), $options);
    // If fieldname is supplied but not id, then use the fieldname as the id
    if (!array_key_exists('id', $options) && array_key_exists('fieldname', $options)) {
      $options['id']=$options['fieldname'];
    }
    // If captionField is supplied but not valueField, use the captionField as the valueField
    if (!array_key_exists('valueField', $options) && array_key_exists('captionField', $options)) {
      $options['valueField']=$options['captionField'];
    }
    // Get a default value - either the supplied value in the options, or the loaded value, or nothing.
    if (array_key_exists('fieldname', $options)) {
      $options['default'] = self::check_default_value($options['fieldname'],
          array_key_exists('default', $options) ? $options['default'] : '');
    }
    return $options;
  }

  /**
   * Internal method to build a control from its options array and its template. Outputs the
   * prefix template, a label (if in the options), a control, the control's errors and a
   * suffix template.
   *
   * @access private
   * @param string $template Name of the control template, from the global $indicia_templates variable.
   * @param array $options Options array containing the control replacement values for the templates.
   * Options can contain a setting for prefixTemplate or suffixTemplate to override the standard templates.
   */
  private static function apply_template($template, $options) {
    global $indicia_templates;
    // Don't need the extraParams - they are just for service communication.
    $options['extraParams']=null;
    // Set default validation error output mode
    if (!array_key_exists('validation_mode', $options)) {
      $options['validation_mode']=self::$validation_mode;
    }
    // Decide if the main control has an error. If so, highlight with the error class and set it's title.
    $error="";
    if (self::$validation_errors!==null) {
      if (array_key_exists('fieldname', $options)) {
        $error = self::check_errors($options['fieldname'], false);
      }
    }
    // Add a hint to the control if there is an error and this option is set
    if ($error && in_array('hint', $options['validation_mode'])) {
      $options['title'] = 'title="'.$error.'"';
    } else {
      $options['title'] = '';
    }
    if (!array_key_exists('class', $options)) {
      $options['class']='';
    }
    if (!array_key_exists('disabled', $options)) {
      $options['disabled']='';
    }
    // Add an error class to colour the control if there is an error and this option is set
    if ($error && in_array('colour', $options['validation_mode'])) {
      $options['class'] .= ' ui-state-error';
      if (array_key_exists('outerClass', $options)) {
        $options['outerClass'] .= ' ui-state-error';
      } else {
        $options['outerClass'] = 'ui-state-error';
      }
    }
    // add validation metadata to the control if specified, as long as control has a fieldname
    if (array_key_exists('fieldname', $options)) {
      $options['class'] .= ' '.self::build_validation_class($options);
    }
    // replace html attributes with their wrapped versions, e.g. a class becomes class="..."
    foreach (self::$html_attributes as $name => $attr) {
      if (!empty($options[$name])) {
        $options[$name]=' '.$attr.'="'.$options[$name].'"';
      }
    }

    // Build an array of all the possible tags we could replace in the template.
    $replaceTags=array();
    $replaceValues=array();
    foreach (array_keys($options) as $option) {
      if (!is_array($options[$option])) {
        array_push($replaceTags, '{'.$option.'}');
        array_push($replaceValues, $options[$option]);
      }
    }
    // If options contain a help text, output it at the end if that is the preferred position
    $r = self::get_help_text($options, 'before');
    //Add prefix
    $r .= self::apply_static_template('prefix', $options);

    // Add a label only if specified in the options array. Link the label to the inputId if available,
    // otherwise the fieldname (as the fieldname control could be a hidden control).
    if (array_key_exists('label', $options)) {
    	$r .= str_replace(
          array('{label}', '{id}', '{labelClass}'),
          array(
              $options['label'],
              array_key_exists('inputId', $options) ? $options['inputId'] : $options['id'],
              array_key_exists('labelClass', $options) ? ' class="'.$options['labelClass'].'"' : '',
          ),
          $indicia_templates['label']
      );
    }
    // Output the main control
    $r .= str_replace($replaceTags, $replaceValues, $indicia_templates[$template]);

    // Add an error icon to the control if there is an error and this option is set
    if ($error && in_array('icon', $options['validation_mode'])) {
      $r .= $indicia_templates['validation_icon'];
    }
    // Add a message to the control if there is an error and this option is set
    if (in_array('message', $options['validation_mode'])) {
      $r .= $error;
    }

    //Add suffix
    $r .= self::apply_static_template('suffix', $options);

    // If options contain a help text, output it at the end if that is the preferred position
    $r .= self::get_help_text($options, 'after');

    return $r;
  }

  /**
   * Returns templated help text for a control, but only if the position matches the $helpTextPos value, and
   * the $options array contains a helpText entry.
   * @param array $options Control's options array
   * @param string $pos Either before or after. Defines the position that is being requested.
   * @return string Templated help text, or nothinbg.
   */
  private static function get_help_text($options, $pos) {
    if (array_key_exists('helpText', $options) && self::$helpTextPos == $pos) {
      return str_replace('{helpText}', $options['helpText'], self::apply_static_template('helpText', $options));
    }
  }

 /**
  * Returns a static template which is either a default template or one
  * specified in the options
  * @param string $name The static template type. e.g. prefix or suffix.
  * @param array $options Array of options which may contain a template name.
  * @return string Template value.
  * @access private
  */
  private static function apply_static_template($name, $options) {
    global $indicia_templates;
    $key = $name .'Template';
    $r = '';

    if (array_key_exists($key, $options)) {
      //a template has been specified
      if (array_key_exists($options[$key], $indicia_templates))
        //the specified template exists
        $r = $indicia_templates[$options[$key]];
      else
        $r = $indicia_templates[$name] .
        '<span class="ui-state-error">Code error: suffix template '.$options[$key].' not in list of known templates.</span>';
    } else {
      //no template specified
      $r = $indicia_templates[$name];
    }
    return $r;
  }

  /**
   * Applies a output template to an array. This is used to build the output for each item in a list,
   * such as a species checklist grid or a radio group.
   *
   * @access private
   * @param array $item Array holding the item attributes.
   * @param string $template Name of the template to use, or actual template text if
   * $useTemplateAsIs is set to true.
   * @param boolean $useTemplateAsIs If true then the template parameter contains the actual
   * template text, otherwise it is the name of a template in the $indicia_templates array. Default false.
   * @return string HTML for the item label
   */
  private static function mergeParamsIntoTemplate($params, $template, $useTemplateAsIs=false) {
    global $indicia_templates;
    // Build an array of all the possible tags we could replace in the template.
    $replaceTags=array();
    $replaceValues=array();
    foreach ($params as $param=>$value) {
      if (!is_array($value)) {
        array_push($replaceTags, '{'.$param.'}');
        // allow sep to have <br/>
        $value = $param == 'sep' ? $value : htmlSpecialChars($value);
        // HTML attributes get automatically wrapped
        if (in_array($param, self::$html_attributes) && !empty($value))
          $value = " $param=\"$value\"";
        array_push($replaceValues, $value);
      }
    }
    if (!$useTemplateAsIs) $template = $indicia_templates[$template];
    return str_replace($replaceTags, $replaceValues, $template);
  }

  /**
   * Private function to fetch a validated timeout value from passed in options array
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>cachetimeout</b><br/>
   * Optional. The length in seconds before the cache times out and is refetched.</li></ul>
   * @return Timeout in number of seconds, else FALSE if data is not to be cached.
   */
  private static function _getCacheTimeOut($options)
  {
    if (is_numeric(self::$cache_timeout) && self::$cache_timeout > 0) {
      $ret_value = self::$cache_timeout;
    } else {
      $ret_value = false;
    }
    if (isset($options['cachetimeout'])) {
      if (is_numeric($options['cachetimeout']) && $options['cachetimeout'] > 0) {
        $ret_value = $options['cachetimeout'];
      } else {
        $ret_value = false;
      }
    }
    return $ret_value;
  }

  /**
   * Private function to generate a filename to be used as the cache file for this data
   * @param string $path directory path for file
   * @param array $options Options array : contents are used along with md5 to generate the filename.
   * @param number $timeout - will be false if no caching to take place
   * @return string filename, else FALSE if data is not to be cached.
   */
  private static function _getCacheFileName($path, $options, $timeout)
  {
    /* If timeout is not set, we're not caching */
    if (!$timeout)
      return false;
    if(!is_dir($path) || !is_writeable($path))
      return false;

    $cacheFileName = $path.'cache_'.self::$website_id.'_';
    $cacheFileName .= md5(self::array_to_query_string($options));

    return $cacheFileName;
  }

  /**
   * Private function to return the cached data stored in the specified local file.
   * @param string $file Cache file to be used, includes path
   * @param number $timeout - will be false if no caching to take place
   * @param array $options Options array : contents used to confirm what this data is.
   * @return array equivalent of call to http_post, else FALSE if data is not to be cached.
   */
  private static function _getCachedResponse($file, $timeout, $options)
  {
    // Note the random element, we only timeout a cached file sometimes.
    if (($timeout && $file && is_file($file) &&
        (rand(1, self::$cache_chance_refresh_file)!=1 || filemtime($file) >= (time() - $timeout)))
    ) {
      $response = array();
      $handle = fopen($file, 'rb');
      if(!$handle) return false;
      $tags = fgets($handle);
      $response['output'] = fread($handle, filesize($file));
      fclose($handle);
      if ($tags == self::array_to_query_string($options)."\n")
        return($response);
    } else {
      self::_timeOutCacheFile($file, $timeout);
    }
    return false;
  }

  /**
   * Private function to remove a cache file if it has timed out.
   * @param string $file Cache file to be removed, includes path
   * @param number $timeout - will be false if no caching to take place
   */
  private static function _timeOutCacheFile($file, $timeout)
  {
    if ($file && is_file($file) && filemtime($file) < (time() - $timeout)) {
      unlink($file);
    }
  }

  /**
   * Private function to create a cache file provided it does not already exist.
   * @param string $file Cache file to be removed, includes path - will be false if no caching to take place
   * @param array $response http_post return value
   * @param array $options Options array : contents used to tag what this data is.
   */
  private static function _cacheResponse($file, $response, $options)
  {
  	// need to create the file as a binary event - so create a temp file and move across.
    if ($file && !is_file($file)) {
      $handle = fopen($file.getmypid(), 'wb');
      fputs($handle, self::array_to_query_string($options)."\n");
      fwrite($handle, $response['output']);
      fclose($handle);
      rename($file.getmypid(),$file);
    }
  }

  /**
   * Issue a post request to get the population data required for a control. Depends on the
   * options' table and extraParams values what is requested. This is now cacheable.
   * NB that this function only uses the 'table' and 'extraParams' of $options. If $options
   * contains a value for nocache=true then caching is skipped as well.
   * When generating the cache for this data we need to use the table and
   * any extra params, excluding the read_auth and the nonce. The cache should be
   * used by all accesses to the DB.
   */
  public static function get_population_data($options) {
    $url = parent::$base_url."index.php/services/data";
    $request = "$url/".$options['table']."?mode=json";    
    if (array_key_exists('extraParams', $options)) {
      // make a copy of the extra params
      $params = array_merge($options['extraParams']);
      $cacheOpts = array();
      // process them to turn any array parameters into a query parameter for the service call
      $filterToEncode = array('where'=>array(array()));
      $otherParams = array();
      foreach($params as $param=>$value) {
        if (is_array($value))
          $filterToEncode['in'] = array($param, $value);
        elseif ($param=='orderby' || $param=='sortdir' || $param=='auth_token' || $param=='nonce' || $param=='view')
          // these params are not filters, so can't go in the query
          $otherParams[$param] = $value;
        else
          $filterToEncode['where'][0][$param] = $value;
        // implode array parameters (for IN clauses) in the cache options, since we need a single depth array
        $cacheOpts[$param]= is_array($value) ? implode('|',$value) : $value;
      }
      // use advanced querying technique if we need to
      if (isset($filterToEncode['in']))
        $request .= '&query='.json_encode($filterToEncode).'&'.self::array_to_query_string($otherParams);
      else
        $request .= '&'.self::array_to_query_string($options['extraParams']);
    } else
      $cacheOpts = array();
    $cacheOpts['table'] = $options['table'];
    $cacheOpts['indicia_website_id'] = self::$website_id;
    /* If present 'auth_token' and 'nonce' are ignored as these are session dependant. */
    if (array_key_exists('auth_token', $cacheOpts)) {
      unset($cacheOpts['auth_token']);
    }
    if (array_key_exists('nonce', $cacheOpts)) {
      unset($cacheOpts['nonce']);
    }

    if (isset($_GET['nocache']) || (isset($options['nocache']) && $options['nocache'])) 
      $response = self::http_post($request, null);
    else {
      $cacheTimeOut = self::_getCacheTimeOut($options);
      $cacheFolder = self::relative_client_helper_path() . (isset(parent::$cache_folder) ? parent::$cache_folder : 'cache/');
      $cacheFile = self::_getCacheFileName($cacheFolder, $cacheOpts, $cacheTimeOut);
      if (!($response = self::_getCachedResponse($cacheFile, $cacheTimeOut, $cacheOpts)))
        $response = self::http_post($request, null);
    } 
    $r = json_decode($response['output'], true);
    if (!is_array($r)) {
      throw new Exception('Invalid response received from Indicia Warehouse. '.print_r($response, true));
    }
    // Only cache valid responses
    self::_cacheResponse($cacheFile, $response, $cacheOpts);
    self::_purgeCache();
    self::_purgeImages();
    return $r;
  }

  /**
   * Internal function to ensure old cache files are purged periodically.
   */
  private static function _purgeCache() {
    $cacheFolder = self::relative_client_helper_path() . (isset(parent::$cache_folder) ? parent::$cache_folder : 'cache/');
    self::_purgeFiles(self::$cache_chance_purge, $cacheFolder, self::$cache_timeout * 5, self::$cache_allowed_file_count);
  }

  /**
   * Internal function to ensure old image files are purged periodically.
   */
  private static function _purgeImages() {
    $interimImageFolder = self::relative_client_helper_path() . (isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/');
    self::_purgeFiles(self::$cache_chance_purge, $interimImageFolder, self::$interim_image_expiry);
  }

  private static function _purgeFiles($chanceOfPurge, $folder, $timeout, $allowedFileCount=0) {
    // don't do this every time.
    if (rand(1, $chanceOfPurge)===1) {
      // First, get an array of files sorted by date
      $files = array();
      $dir =  opendir($folder);
      if ($dir) {
        while ($filename = readdir($dir)) {
          if ($filename == '.' || $filename == '..' || is_dir($filename))
            continue;
          $lastModified = filemtime($folder . $filename);
          $files[] = array($folder .$filename, $lastModified);
        }
      }
      // sort the file array by date, oldest first
      usort($files, array('data_entry_helper', '_DateCmp'));
      // iterate files, ignoring the number of files we allow in the cache without caring.
      for ($i=0; $i<count($files)-$allowedFileCount; $i++) {
        // if we have reached a file that is not old enough to expire, don't go any further
        if ($files[$i][1] > (time() - $timeout)) {
          break;
        }
        // clear out the old file
        if (is_file($files[$i][0]))
          unlink($files[$i][0]);
      }
    }
  }

  private static function _DateCmp($a, $b)
  {
    if ($a[1]<$b[1])
      $r = -1;
    else if ($a[1]>$b[1])
      $r = 1;
    else $r=0;
    return $r;
  }

  /**
   * Internal function to output either a select or listbox control depending on the templates
   * passed.
   *
   * @access private
   */
  private static function select_or_listbox($options) {
    global $indicia_templates;
    self::add_resource('json');
    $options = array_merge(array(
      'filterField'=>'parent_id',
      'size'=>3
    ), $options);
    if (array_key_exists('parentControlId', $options)) {
      // no options for now
      $options['items'] = '';
      self::init_linked_lists($options);
    } else {
      $lookupValues = self::get_list_data_from_options($options);
      $opts = "";
      if (array_key_exists('blankText', $options)) {
        $opts .= str_replace(
            array('{value}', '{caption}', '{selected}'),
            array('', htmlentities($options['blankText'])),
            $indicia_templates[$options['itemTemplate']]
        );
      }
      foreach ($lookupValues as $key => $value){
        $item['selected'] = (isset($options['default']) && $options['default'] == $key) ? 'selected' : '';
        $item['value'] = $key;
        $item['caption'] = $value;
        $opts .= self::mergeParamsIntoTemplate($item, $options['itemTemplate']);
      }
      $options['items'] = $opts;
    }
    if (isset($response['error']))
      return $response['error'];
    else
      return self::apply_template($options['template'], $options);
  }
  
  /**
  * When populating a list control (select, listbox, checkbox or radio group), use either the 
  * table, captionfield and valuefield to build the list of values as an array, or if lookupValues
  * is in the options array use that instead of making a database call.
  * @param array $options Options array for the control.
  * @return array Associative array of the lookup values and captions.
  */
  private static function get_list_data_from_options($options) {
    if (isset($options['lookupValues'])) {
      // lookup values are provided
      $lookupValues = $options['lookupValues'];
    } else {
      // lookup values need to be obtained from the database
      $response = self::get_population_data($options);
      $lookupValues = array();
      if (!array_key_exists('error', $response)) {
        foreach ($response as $item) {
          if (array_key_exists($options['captionField'], $item) &&
              array_key_exists($options['valueField'], $item)) {
            $lookupValues[$item[$options['valueField']]] = $item[$options['captionField']];
          }
        }
      }
    }
    return $lookupValues;
  }

 /**
  * Where there are 2 linked lists on a page, initialise the JavaScript required to link the lists.
  *
  * @param array Options array of the child linked list.
  */
  private static function init_linked_lists($options) {
    global $indicia_templates;
    // setup JavaScript to do the population when the parent control changes
    $parentControlId = str_replace(':','\\\\:',$options['parentControlId']);
    $escapedId = str_replace(':','\\\\:',$options['id']);
    $fn = str_replace(':','',$options['id'])."_populate";
    $url = parent::$base_url."index.php/services/data";
    $request = "$url/".$options['table']."?mode=json";
    if (array_key_exists('extraParams', $options)) {
      $request .= '&'.self::array_to_query_string($options['extraParams']);
    }
    self::$javascript .= str_replace(
        array('{fn}','{escapedId}','{request}','{filterField}','{valueField}','{captionField}','{parentControlId}'),
        array($fn, $escapedId, $request,$options['filterField'],$options['valueField'],$options['captionField'],$parentControlId),
        $indicia_templates['linked_list_javascript']
    );
  }

  /**
   * Internal method to output either a checkbox group or a radio group.
   */
  private static function check_or_radio_group($options, $type) {
    global $indicia_templates;
    $options = array_merge(
      array(
        'sep' => '',
        'template' => 'check_or_radio_group',
        'itemTemplate' => 'check_or_radio_group_item',
        'containerTemplate' => 'check_or_radio_group_container',
        'class' => 'control-box'
      ),
      $options
    );
    // We want to apply validation to the inner items, not the outer control
    if (array_key_exists('validation', $options)) {
      $itemClass = self::build_validation_class($options);
      unset($options['validation']);
    } else {
      $itemClass='';
    }    
    $lookupValues = self::get_list_data_from_options($options);
    $items = "";
    $idx = 0;
    foreach ($lookupValues as $value => $caption){
      $idx++;
      $item = array_merge(
        $options,        
        array(
          'disabled' => isset($options['disabled']) ?  $options['disabled'] : '',
          'checked' => (isset($options['default']) && $options['default'] == $value) ? 'checked="checked" ' : '',
          'type' => $type,
          'caption' => $caption,
          'value' => $value,
          'class' => $itemClass,
          'itemId' => $options['fieldname'].':'.$idx
        )
      );      
      $items .= self::mergeParamsIntoTemplate($item, $options['itemTemplate']);      
    }    
    $options['items']=$items;
    // We don't want to output for="" in the top label, as it is not directly associated to a button
    $lblTemplate = $indicia_templates['label'];
    $indicia_templates['label'] = str_replace(' for="{id}"', '', $lblTemplate);    
    $r = self::apply_template($options['template'], $options);    
    // reset the old template
    $indicia_templates['label'] = $lblTemplate;
    if (array_key_exists('containerClass', $options)) {
      $containerOpts = array(
              'group' => $r,
              'class' => $options['containerClass']
            );
      if(array_key_exists('suffixTemplate', $options)) {
      	$containerOpts['suffixTemplate'] = $options['suffixTemplate'];
      }
      $r = self::apply_template($options['containerTemplate'], $containerOpts);
    }
    return $r;
  }

 /**
  * Helper method to enable the support for tabbed interfaces for a div.
  * The jQuery documentation describes how to specify a list within the div which defines the tabs that are present.
  * This method also automatically selects the first tab that contains validation errors if the form is being
  * reloaded after a validation attempt.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>divId</b><br/>
  * Optional. The id of the div which will be tabbed. If not specified then the caller is
  * responsible for calling the jQuery tabs plugin - this method just links the appropriate
  * jQuery files.</li>
  * <li><b>style</b><br/>
  * Optional. Possible values are tabs (default) or wizard. If set to wizard, then the tab header
  * is not displayed and the navigation should be provided by the tab_button control. This
  * must be manually added to each tab page div.</li>
  * <li><b>progressBar</b><br/>
  * Optional. Set to true to output a progress header above the tabs/wizard which shows which 
  * stage the user is on out of the sequence of steps in the wizard.</li>  
  * </ul>
  *
  * @link http://docs.jquery.com/UI/Tabs
  */
  public static function enable_tabs($options) {
    // A jquery selector for the element which must be at the top of the page when moving to the next page. Could be the progress bar or the 
    // tabbed div itself.
    if (isset($options['progressBar']) && $options['progressBar']==true)
      $topSelector = '.wiz-prog';
    else
      $topSelector = '#'.$options['divId'];
    // Only do anything if the id of the div to be tabified is specified
    if (array_key_exists('divId', $options)) {
      $divId = $options['divId'];
      // Scroll to the top of the page. This may be required if subsequent tab pages are longer than the first one, meaning the
        // browser scroll bar is too long making it possible to load the bottom blank part of the page if the user accidentally
      // drags the scroll bar while the page is loading.
      self::$javascript .= "\nscroll(0,0);";
      self::$javascript .= "\n$('.tab-submit').click(function() {
      var form = $(this).parents('form:first');
      form.submit();
      });";
      self::$javascript .= "\n$('.tab-next').click(function() {\n";
      self::$javascript .= "  var current=$('#$divId').tabs('option', 'selected');\n";
      // Use a selector to find the inputs on the current tab and validate them.
      if (isset(self::$validated_form_id)) {
        self::$javascript .= "  if (!$('#".self::$validated_form_id." div > .ui-tabs-panel:eq('+current+') input').valid()) {\n    return; \n}";
      }
      // If all is well, move to the next tab. Note the code detects if the top of the tabset is not visible, if so
      // it forces it into view. This helps a lot when the tabs vary in height.
      self::$javascript .= "  var a = $('ul.ui-tabs-nav a')[current+1];
  $(a).click();
  scrollTopIntoView('$topSelector');
});";

      self::$javascript .= "\n$('.tab-prev').click(function() {
  var current=$('#$divId').tabs('option', 'selected');
  var a = $('ul.ui-tabs-nav a')[current-1];
  $(a).click();
  scrollTopIntoView('$topSelector');
});\n";

      // We put this javascript into $late_javascript so that it can come after the other controls.
      // This prevents some obscure bugs - e.g. OpenLayers maps cannot be centered properly on hidden
      // tabs because they have no size.
      self::$late_javascript .= "var tabs = $(\"#$divId\").tabs();\n";
      // find any errors on the tabs.
      self::$late_javascript .= "var errors=$(\"#$divId .ui-state-error\");\n";
      // select the tab containing the first error, if validation errors are present
      self::$late_javascript .= "
if (errors.length>0) {
  tabs.tabs('select',$(errors[0]).parents('.ui-tabs-panel')[0].id);
  var panel;
  for (var i=0; i<errors.length; i++) {
    panel = $(errors[i]).parents('.ui-tabs-panel')[0];
    $('#'+panel.id+'-tab').addClass('ui-state-error');
  }
}\n";
      if (array_key_exists('active', $options)) {
        self::$late_javascript .= "else {tabs.tabs('select','".$options['active']."');}\n";
      }
      if (array_key_exists('style', $options) && $options['style']=='wizard') {
        self::$late_javascript .= "$('#$divId .ui-tabs-nav').hide();\n";
      }
    }
    // add a progress bar to indicate how many steps are complete in the wizard
    if (isset($options['progressBar']) && $options['progressBar']==true) {
      data_entry_helper::add_resource('wizardprogress');	
      data_entry_helper::$javascript .= "wizardProgressIndicator({divId:'$divId'});\n";
    } else {
      data_entry_helper::add_resource('tabs');	
    }
  }

  /**
   * Outputs the ul element that needs to go inside a tabified div control to define the header tabs.
   * This is required for wizard interfaces as well. 
   * @param array $options Options array with the following possibilities:<ul>
  * <li><b>tabs</b><br/>
  * Array of tabs, with each item being the tab title, keyed by the tab ID including the #.</li>
  */
  public static function tab_header($options) {
    $options = self::check_options($options);
    // Convert the tabs array to a string of <li> elements
    $tabs = "";
    foreach($options['tabs'] as $link => $caption) {
      $tabId=substr("$link-tab",1);
      //rel="address:..." enables use of jQuery.address module (http://www.asual.com/jquery/address/)
      if ($tabs == ""){
        $address = "";
      } else {
        $address = (substr($link, 0, 1) == '#') ? substr($link, 1) : $link;
      }
      $tabs .= "<li id=\"$tabId\"><a href=\"$link\" rel=\"address:/$address\"><span>$caption</span></a></li>";
    }
    $options['tabs'] = $tabs;
    return self::apply_template('tab_header', $options);
  }

  /* Insert a button which, when clicked, displays the previous tab. Insert this inside the tab divs
  * on each tab you want to have a next button, excluding the first tab.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>divId</b><br/>
  * The id of the div which is tabbed and whose next tab should be selected.</li>
  * <li><b>caption</b><br/>
  * Optional. The untranslated caption of the button. Defaults to previous step.</li>
  *
  * @link http://docs.jquery.com/UI/Tabs
  */
  public static function tab_prev_button($options) {
    if (!array_key_exists('caption', $options)) $options['caption'] = 'previous step';
    $options['caption'] = lang::get($options['caption']);
    if (!array_key_exists('class', $options)) $options['class'] = 'ui-widget-content ui-state-default ui-corner-all indicia-button prev-tab';
    if (array_key_exists('divId', $options)) {
      return self::apply_template('tab_prev_button', $options);
    }
  }

  /**
   * <p>Allows the demarcation of the start of a region of the page HTML to be declared which will be replaced by
   * a loading message whilst the page is loading.</p>
   * <p>If JavaScript is disabled then this has no effect. Note that hiding the block is achieved by setting
   * it's left to move it off the page, rather than display: none. This is because OpenLayers won't initialise
   * properly on a div that is display none.</p>
   * <p><b>Warning.</b> To use this function, always insert a call to dump_header in the <head> element of your
   * HTML page to ensure that JQuery is loaded first. Otherwise this will not work.</p>
   *
   * @return string HTML and JavaScript to insert into the page at the start of the block
   * which is replaced by a loading panel while the page is loading.
   */
  public static function loading_block_start() {
    global $indicia_templates, $indicia_theme_path, $indicia_theme;
    self::add_resource('jquery_ui');
    // For clean code, the jquery_ui stuff should have gone out in the page header, but just in case.
    // Don't bother from inside Drupal, since the header is added after the page code runs
    if (!in_array('jquery_ui', self::$dumped_resources) && !defined('DRUPAL_BOOTSTRAP_CONFIGURATION')) {
      $r = self::internal_dump_javascript('', '', '', array('jquery_ui'));
      array_push(self::$dumped_resources, 'jquery_ui');
    } else {
      $r = '';
    }
    $r .= $indicia_templates['loading_block_start'];
    return $r;
  }

  /**
   * Allows the demarcation of the end of a region of the page HTML to be declared which will be replaced by
   * a loading message whilst the page is loading.
   *
   * @return string HTML and JavaScript to insert into the page at the start of the block
   * which is replaced by a loading panel while the page is loading.
   */
  public static function loading_block_end() {
    global $indicia_templates;
    // First hide the message, then hide the form, slide it into view, then show it.
    // This script must precede the other scripts onload, otherwise they may have problems because
    // of assumptions that the controls are visible.
    self::$onload_javascript = "$('.loading-panel').remove();\n".
        "var panel=$('.loading-hide')[0];\n".
        "$(panel).hide();\n".
        "$(panel).removeClass('loading-hide');\n".
        "$(panel).fadeIn('slow');\n" .
        self::$onload_javascript;
    return $indicia_templates['loading_block_end'];
  }

  /**
   * Converts the validation rules in an options array into a string that can be used as the control class,
   * to trigger the jQuery validation plugin.
   * @param $options. For validation to be applied should contain a validation entry, containing a single
   * validation string or an array of strings.
   * @return string The validation rules formatted as a class.
   */
  private static function build_validation_class($options) {
    global $custom_terms;
    $rules = (array_key_exists('validation', $options) ? $options['validation'] : array());
    if (!is_array($rules)) $rules = array($rules);
    if (array_key_exists($options['fieldname'], self::$default_validation_rules)) {
      $rules = array_merge($rules, self::$default_validation_rules[$options['fieldname']]);
    }
    // Build internationalised validation messages for jQuery to use, if the fields have internationalisation strings specified
    foreach ($rules as $rule) {
      if (isset($custom_terms) && array_key_exists($options['fieldname'], $custom_terms))
        self::$validation_messages[$options['fieldname']][$rule] = sprintf(lang::get("validation_$rule"),
          lang::get($options['fieldname']));
    }
    // Convert these rules into jQuery format.
    return self::convert_to_jquery_val_metadata($rules);
  }

  /**
   * Either takes the passed in submission, or creates it from the post data if this is null, and forwards
   * it to the data services for saving as a member of the entity identified.
   * @param string $entity Name of the top level entity being submitted, e.g. sample or occurrence.
   * @param array $submission The wrapped submission structure. If null, then this is automatically constructer
   * from the form data in $_POST.
   * @param array $writeTokens Array containing auth_token and nonce for the write operation. If null then
   * the values are read from $_POST.
   */
  public static function forward_post_to($entity, $submission = null, $writeTokens = null) {
    if (self::$validation_errors==null) {
      $remembered_fields = self::get_remembered_fields();

      if ($submission == null)
        $submission = submission_builder::wrap($_POST, $entity);
      if ($remembered_fields !== null) {
        // if there are any fields to remember, put them in a cookie with a 30 day expiry
        $arr=array();
        foreach ($remembered_fields as $field) {
          $arr[$field]=$_POST[$field];
        }
        setcookie('indicia_remembered', serialize($arr), time()+60*60*24*30);
        // cookies are only set when the page is loaded. So if we are reloading the same form after submission,
        // we need to fudge the cookie
        $_COOKIE['indicia_remembered'] = serialize($arr);
      }
      $images = self::extract_image_data($_POST);
      $request = parent::$base_url."index.php/services/data/$entity";
      $postargs = 'submission='.urlencode(json_encode($submission));
      // passthrough the authentication tokens as POST data. Use parameter writeTokens, or current $_POST if not supplied.
      if ($writeTokens) {
        $postargs .= '&auth_token='.$writeTokens['auth_token'];
        $postargs .= '&nonce='.$writeTokens['nonce'];
      } else {
        if (array_key_exists('auth_token', $_POST))
          $postargs .= '&auth_token='.$_POST['auth_token'];
        if (array_key_exists('nonce', $_POST))
          $postargs .= '&nonce='.$_POST['nonce'];
      }
      // if there are images, we will send them after the main post, so we need to persist the write nonce
      if (count($images)>0)
        $postargs .= '&persist_auth=true';
      $response = self::http_post($request, $postargs);
      // The response should be in JSON if it worked
      $output = json_decode($response['output'], true);
      // If this is not JSON, it is an error, so just return it as is.
      if (!$output)
        $output = $response['output'];
      if (is_array($output) && array_key_exists('success', $output)) {
        // submission succeeded. So we also need to move the images to the warehouse.
        foreach ($images as $image) {
          // SET PERSIST_AUTH false if last file
          $success = self::send_file_to_warehouse($image['path'], true);
          if ($success!==true) {
            return array('error' => lang::get('submit ok but file failed').
                "<br/>$success");
          }
        }
      }
      return $output;
    }
    else
      return array('error' => 'Pre-validation failed', 'errors' => self::$validation_errors);
  }

  /**
  * Wraps data from a species checklist grid (generated by
  * data_entry_helper::species_checklist) into a suitable format for submission. This will
  * return an array of submodel entries which can be dropped directly into the subModel
  * section of the submission array. If there is a field occurrence:determiner_id or
  * occurrence:record_status in the main form data, then these values are applied to each
  * occurrence created from the grid. For example, place a hidden field in the form named
  * "occurrence:record_status" with a value "C" to set all occurrence records to completed
  * as soon as they are entered.
  *
  * @param array $arr Array of data generated by data_entry_helper::species_checklist method.
  * @param boolean $include_if_any_data If true, then any list entry which has any data
  * set will be included in the submission. Set this to true when hiding the select checkbox
  * in the grid.
  */
  public static function wrap_species_checklist($arr, $include_if_any_data=false){
    if (array_key_exists('website_id', $arr)){
      $website_id = $arr['website_id'];
    } else {
      throw new Exception('Cannot find website id in POST array!');
    }
    // determiner and record status can be defined globally for the whole list.
    if (array_key_exists('occurrence:determiner_id', $arr)){
      $determiner_id = $arr['occurrence:determiner_id'];
    }
    if (array_key_exists('occurrence:record_status', $arr)){
      $record_status = $arr['occurrence:record_status'];
    }
    // Species checklist entries take the following format
    // sc:<taxa_taxon_list_id>:[<occurrence_id>]:occAttr:<occurrence_attribute_id>[:<occurrence_attribute_value_id>]
	// or
	// sc:<taxa_taxon_list_id>:[<occurrence_id>]:occurrence:comment
	// or
	// sc:<taxa_taxon_list_id>:[<occurrence_id>]:occurrence_image:fieldname:uniqueImageId
    $records = array();
    $subModels = array();
    foreach ($arr as $key=>$value){
      if (substr($key, 0, 3)=='sc:'){
        // Don't explode the last element for occurrence attributes
        $a = explode(':', $key, 4);
        $records[$a[1]][$a[3]] = $value;
        // store any id so update existing record
        if($a[2]) {
          $records[$a[1]]['id'] = $a[2];
        }
      }
    }
    foreach ($records as $id => $record){
      if ((array_key_exists('present', $record)) ||
          (array_key_exists('id', $record)) ||
          ($include_if_any_data && implode('',$record)!='')) {
        if (array_key_exists('id', $record) && array_key_exists('control:checkbox', $arr) && !array_key_exists('present', $record)){
          // checkboxes do not appear if not checked. If uncheck, delete record.
          $record['deleted'] = 't';
        }
        
        $record['taxa_taxon_list_id'] = $id;
        $record['website_id'] = $website_id;
        if (isset($determiner_id)) {
            $record['determiner_id'] = $determiner_id;
        }
        if (isset($record_status)) {
          $record['record_status'] = $record_status;
        }
        $occAttrs = data_entry_helper::wrap_attributes($record, 'occurrence');
        $occ = data_entry_helper::wrap($record, 'occurrence');
        $occ['metaFields']['occAttributes']['value'] = $occAttrs;
		    self::attachOccurrenceImagesToModel($occ, $record);
        $subModels[] = array(
          'fkId' => 'sample_id',
          'model' => $occ
        );
      }
    }
    return $subModels;
  }
  
  /**
   * When wrapping a species checklist submission, scan the contents of the data for a single grid row to 
   * look for attached images. If found they are attached to the occurrence model as sub-models.
   */
  private static function attachOccurrenceImagesToModel(&$occ, $record) {
    $images = array();
    foreach ($record as $key=>$value) {
	  if (substr($key, 0, 17)=='occurrence_image:') {
	    $tokens = explode(':', $key);
		// build an array of the data keyed by the unique image id (token 2)
		$images[$tokens[2]][$tokens[1]] = array('value' => $value);
	  }
	}
	foreach($images as $image => $data) {
	  $occ['subModels'][] = array(
	    'fkId' => 'occurrence_id',
		'model' => array(
		  'id' => 'occurrence_image',
		  'fields' => $data
		)
	  );
	}
  }

  /**
  * Wraps attribute fields (entered as normal) into a suitable container for submission.
  * Throws an error if $entity is not something for which attributes are known to exist.
  * @return array
  */
  public static function wrap_attributes($arr, $entity) {
    return submission_builder::wrap_attributes($arr, $entity);
  }

  /**
   * Wraps an array (e.g. Post or Session data generated by a form) into a structure
   * suitable for submission.
   * <p>The attributes in the array are all included, unless they
   * named using the form entity:attribute (e.g. sample:date) in which case they are
   * only included if wrapping the matching entity. This allows the content of the wrap
   * to be limited to only the appropriate information.</p>
   * <p>Do not prefix the survey_id or website_id attributes being posted with an entity
   * name as these IDs are used by Indicia for all entities.</p>
   *
   * @param array $array Array of data generated from data entry controls.
   * @param string $entity Name of the entity to wrap data for.
   */
  public static function wrap($array, $entity)
  {
    return submission_builder::wrap($array, $entity);
  }

   /**
   * Wraps a set of values for a model into JSON suitable for submission to the Indicia data services,
   * and also grabs the custom attributes (if there are any) and links them to the model.
   *
   * @param array $values Array of form data (e.g. $_POST).
   * @param string $modelName Name of the model to wrap data for. If this is sample, occurrence or location
   * then custom attributes will also be wrapped. Furthermore, any attribute called $modelName:image can
   * contain an image upload (as long as a suitable entity is available to store the image in).
   */
  public static function wrap_with_attrs($values, $modelName) {
    return submission_builder::wrap_with_attrs($values, $modelName);
  }

  /**
   * Helper function to simplify building of a submission that contains a single sample
   * and occurrence record.
   * @param array $values List of the posted values to create the submission from. Each entry's
   * key should be occurrence:fieldname, sample:fieldname, occAttr:n or smpAttr:n to be correctly
   * identified.
   */
  public static function build_sample_occurrence_submission($values) {
    $structure = array(
        'model' => 'sample',
        'subModels' => array(
          'occurrence' => array('fk' => 'sample_id')
        )
    );
    // Either an uploadable file, or a link to a Flickr external detail means include the submodel
    if ((array_key_exists('occurrence:image', $values) && $values['occurrence:image'])
        || array_key_exists('occurrence_image:external_details', $values) && $values['occurrence_image:external_details']) {
      $structure['submodel']['submodel'] = array(
          'model' => 'occurrence_image',
          'fk' => 'occurrence_id'
      );
    }
    return self::build_submission($values, $structure);
  }

  /**
   * Helper function to simplify building of a submission that contains a single sample
   * and multiple occurrences records generated by a species_checklist control.
   *
   * @param array $values List of the posted values to create the submission from.
   * @param boolean $include_if_any_data If true, then any list entry which has any data
   * set will be included in the submission. Set this to true when hiding the select checkbox
   * in the grid.
   * @return array Sample submission array
   */
  public static function build_sample_occurrences_list_submission($values, $include_if_any_data=false) {
    // We're mainly submitting to the sample model
    $sampleMod = data_entry_helper::wrap_with_attrs($values, 'sample');
    $occurrences = data_entry_helper::wrap_species_checklist($values, $include_if_any_data);

    // Add the occurrences in as subModels
    $sampleMod['subModels'] = $occurrences;

    return $sampleMod;
  }

  /**
   * Helper function to simplify building of a submission. Does simple submissions that do not involve
   * species checklist grids.
   * @param array $values List of the posted values to create the submission from.
   * @param array $structure Describes the structure of the submission. The form should be:
   * array(
   *     'model' => 'main model name',
   *     'subModels' => array('child model name' =>  array(
   *         'fieldPrefix'=>'Optional prefix for HTML form fields in the sub model. If not specified then the sub model name is used.',
   *         'fk' => 'foreign key name',
   *         'image_entity' => 'name of image entity if present'
   *     )),
   *     'superModels' => array('child model name' =>  array(
   *         'fieldPrefix'=>'Optional prefix for HTML form fields in the sub model. If not specified then the sub model name is used.',
   *         'fk' => 'foreign key name',
   *         'image_entity' => 'name of image entity if present'
   *     )),
   *     'metaFields' => array('fieldname1', 'fieldname2', ...)
   * )
   */
  public static function build_submission($values, $structure) {
    return submission_builder::build_submission($values, $structure);
  }

  /**
   * This method allows JavaScript and CSS links to be created and placed in the <head> of the
   * HTML file rather than using dump_javascript which must be called after the form is built.
   * The advantage of dump_javascript is that it intelligently builds the required links
   * depending on what is on your form. dump_header is not intelligent because the form is not
   * built yet, but placing links in the header leads to cleaner code which validates better.
   * @param $resources List of resources to include in the header. The available options are described
   * in the documentation for the add_resource method. The default for this is jquery_ui and defaultStylesheet.
   *
   * @return string Text to place in the head section of the html file.
   */
  public static function dump_header($resources=null) {
    if (!$resources) {
      $resources = array('jquery_ui',  'defaultStylesheet');
    }
    foreach ($resources as $resource) {
      self::add_resource($resource);
    }
    // place a css class on the body if JavaScript enabled. And output the resources
    return self::internal_dump_javascript('$("body").addClass("js");', '', '', self::$required_resources);
  }

  /**
  * Helper function to collect javascript code in a single location. Should be called at the end of each HTML
  * page which uses the data entry helper so output all JavaScript required by previous calls.
  *
  * @return string JavaScript to insert into the page for all the controls added to the page so far.
  *
  * @link http://code.google.com/p/indicia/wiki/TutorialBuildingBasicPage#Build_a_data_entry_page
  */
  public static function dump_javascript() {
    // Add the default stylesheet to the end of the list, so it has highest CSS priority
    if (self::$default_styles) self::add_resource('defaultStylesheet');
    self::setup_jquery_validation_js();    
    $dump = self::internal_dump_javascript(self::$javascript, self::$late_javascript, self::$onload_javascript, self::$required_resources);
    // ensure scripted JS does not output again if recalled.
    self::$javascript = "";
    self::$late_javascript = "";
    self::$onload_javascript = "";
    return $dump;
  }
  
  /**
   * If required, setup jQuery validation. This JavaScript must be added at the end of form preparation otherwise we would
   * not know all the control messages. It will normally be called by dump_javascript automatically, but is exposed here
   * as a public method since the iform Drupal module does not call dump_javascript, but is responsible for adding JavaScript
   * to the page via drupal_add_js.
   */
  public static function setup_jquery_validation_js() {
    global $indicia_templates;
    // In the following block, we set the validation plugin's error class to our template.
    // We also define the error label to be wrapped in a <p> if it is on a newline.
    if (self::$validated_form_id) {
      self::$javascript .= "$('#".self::$validated_form_id."').validate({
        errorClass: \"".$indicia_templates['error_class']."\",
        ". (in_array('inline', self::$validation_mode) ? "\n      " : "errorElement: 'p',\n      ").
        "highlight: function(element, errorClass) {
          $(element).addClass('ui-state-error');
        },
        unhighlight: function(element, errorClass) {
          $(element).removeClass('ui-state-error');
        },
        invalidHandler: function(form, validator) {
          // select the tab containing the first error control
          jQuery.each(validator.errorMap, function(ctrlId, error) {
            tabs.tabs('select',$('input[name=' + ctrlId.replace(/:/g, '\\\\:') + ']').parents('.ui-tabs-panel')[0].id);
            return false; // only do the first error
          });
        },
        messages: ".json_encode(self::$validation_messages)."
      });\n";
    }
  }

  /**
   * Internal implementation of the dump_javascript method which takes the javascript and resources list
   * as flexible parameters, rather that using the globals.
   * @access private
   */
  private static function internal_dump_javascript($javascript, $late_javascript, $onload_javascript, $resources) {
    $libraries = '';
    $stylesheets = '';
    if (isset($resources)) {
      $resourceList = self::get_resources();
      foreach ($resources as $resource)
      {
        if (!in_array($resource, self::$dumped_resources)) {
          if (isset($resourceList[$resource]['stylesheets'])) {
            foreach ($resourceList[$resource]['stylesheets'] as $s) {
              $stylesheets .= "<link rel='stylesheet' type='text/css' href='$s' />\n";
            }
          }
          if (isset($resourceList[$resource]['javascript'])) {
            foreach ($resourceList[$resource]['javascript'] as $j) {
              // look out for a condition that this script is IE only.
              if (substr($j, 0, 4)=='[IE]')
                $libraries .= "<!--[if IE]><script type=\"text/javascript\" src=\"".substr($j, 4)."\"></script><![endif]-->\n";
              else
                $libraries .= "<script type=\"text/javascript\" src=\"$j\"></script>\n";
            }
          }
          // Record the resource as being dumped, so we don't do it again.
          array_push(self::$dumped_resources, $resource);
        }
      }
    }
    if (!empty($javascript) || !empty($late_javascript) || !empty($onload_javascript)) {
      $script = "<script type='text/javascript'>/* <![CDATA[ */
jQuery(document).ready(function() {
$javascript
$late_javascript
});\n";
      if (!empty($onload_javascript)) {
        $script .= "window.onload = function() {
$onload_javascript
};\n";
      }
      $script .= "/* ]]> */</script>";
    } else {
      $script='';
    }
    return $stylesheets.$libraries.$script;
  }

  /**
  * Takes a response from a call to forward_post_to() and outputs any errors from it onto the screen.
  *
  * @param string $response Return value from a call to forward_post_to().
  * @param boolean $inline Set to true if the errors are to be placed alongside the controls rather than at the top of the page.
  * Default is true.
  * @see forward_post_to()
  * @link http://code.google.com/p/indicia/wiki/TutorialBuildingBasicPage#Build_a_data_entry_page
  */
  public static function dump_errors($response, $inline=true)
  {
    $r = "";
    if (is_array($response)) {
      if (array_key_exists('error',$response) || array_key_exists('errors',$response)) {
        if ($inline && array_key_exists('errors',$response)) {
          // Setup an errors array that the data_entry_helper can output alongside the controls
          self::$validation_errors = $response['errors'];
          // And tell the helper to reload the existing data.
          self::$entity_to_load = $_POST;
        } else {
          $r .= "<div class=\"ui-state-error ui-corner-all\">\n";
          $r .= "<p>An error occurred when the data was submitted.</p>\n";
          if (is_array($response['error'])) {
            $r .=  "<ul>\n";
            foreach ($response['error'] as $field=>$message)
              $r .=  "<li>$field: $message</li>\n";
            $r .=  "</ul>\n";
          } else {
            $r .= "<p class=\"error_message\">".$response['error']."</p>\n";
          }
          if (array_key_exists('file', $response) && array_key_exists('line', $response)) {
            $r .= "<p>Error occurred in ".$response['file']." at line ".$response['line']."</p>\n";
          }
          if (array_key_exists('errors', $response)) {
            $r .= "<pre>".print_r($response['errors'], true)."</pre>\n";
          }
          if (array_key_exists('trace', $response)) {
            $r .= "<pre>".print_r($response['trace'], true)."</pre>\n";
          }
          $r .= "</div>\n";
        }
      }
      elseif (array_key_exists('warning',$response)) {
        $r .= 'A warning occurred when the data was submitted.';
        $r .= '<p class="error">'.$response['error']."</p>\n";
      }
      elseif (array_key_exists('success',$response)) {
        $r .= "<div class=\"ui-widget ui-corner-all ui-state-highlight page-notice\">Thank you for submitting your data.</div>\n";
      }
    }
    else
      $r .= "<div class=\"ui-state-error ui-corner-all\">$response</div>\n";
    return $r;
  }

  /**
   * Retrieves any errors that have not been emitted alongside a form control and adds them to the page. 
   * This is useful when added to the bottom of a form, because occasionally an error can be returned which is not associated with a form
   * control, so calling dump_errors with the inline option set to true will not emit the errors onto the page.
   * @return string HTML block containing the error information, built by concatenating the 
   * validation_message template for each error.
   */
  public static function dump_remaining_errors()
  {
    global $indicia_templates;
    $r="";
    if (self::$validation_errors!==null) {
      foreach (self::$validation_errors as $errorKey => $error) {
        if (!in_array($error, self::$displayed_errors)) {
          $r .= str_replace('{error}', lang::get($error), $indicia_templates['validation_message']);
          $r .= "[".$errorKey."]";
        }
      }
      $r .= '<br/>';
    }
    return $r;
  }


  /**
  * Retrieves a token and inserts it into a data entry form which authenticates that the
  * form was submitted by this website.
  *
  * @param string $website_id Indicia ID for the website.
  * @param string $password Indicia password for the website.
  */
  public static function get_auth($website_id, $password) {
    $postargs = "website_id=$website_id";
    $response = self::http_post(parent::$base_url.'index.php/services/security/get_nonce', $postargs);
    $nonce = $response['output'];
    $result = '<input id="auth_token" name="auth_token" type="hidden" class="hidden" ' .
        'value="'.sha1("$nonce:$password").'" />'."\r\n";
    $result .= '<input id="nonce" name="nonce" type="hidden" class="hidden" ' .
        'value="'.$nonce.'" />'."\r\n";
    return $result;
  }

  /**
  * Retrieves a read token and passes it back as an array suitable to drop into the
  * 'extraParams' options for an Ajax call.
  *
  * @param string $website_id Indicia ID for the website.
  * @param string $password Indicia password for the website.
  */
  public static function get_read_auth($website_id, $password) {
    self::$website_id = $website_id; /* Store this for use with data caching */
    $postargs = "website_id=$website_id";
    $response = self::http_post(parent::$base_url.'index.php/services/security/get_read_nonce', $postargs);
    $nonce = $response['output'];
    return array(
        'auth_token' => sha1("$nonce:$password"),
        'nonce' => $nonce
    );
  }

/**
  * Retrieves read and write nonce tokens from the warehouse.
  * @param string $website_id Indicia ID for the website.
  * @param string $password Indicia password for the website.
  * @return Returns an array containing:
  * 'read' => the read authorisation array,
  * 'write' => the write authorisation input controls to insert into your form.
  * 'writeTokens' => the write authorisation array, if needed as separate tokens rather than just placing in form.
  */
  public static function get_read_write_auth($website_id, $password) {
    self::$website_id = $website_id; /* Store this for use with data caching */
    $postargs = "website_id=$website_id";
    $response = self::http_post(parent::$base_url.'index.php/services/security/get_read_write_nonces', $postargs);
    $nonces = json_decode($response['output'], true);
    $write = '<input id="auth_token" name="auth_token" type="hidden" class="hidden" ' .
        'value="'.sha1($nonces['write'].':'.$password).'" />'."\r\n";
    $write .= '<input id="nonce" name="nonce" type="hidden" class="hidden" ' .
        'value="'.$nonces['write'].'" />'."\r\n";
    return array(
      'write' => $write,
      'read' => array(
        'auth_token' => sha1($nonces['read'].':'.$password),
        'nonce' => $nonces['read']
      ),
      'write_tokens' => array(
        'auth_token' => sha1($nonces['write'].':'.$password),
        'nonce' => $nonces['write']
      ),
    );
  }

  /**
   * Takes an associative array and converts it to a list of params for a query string. This is like
   * http_build_query but it does not url encode the content.
   */
  private static function array_to_query_string($array) {
    $params = array();
    if(is_array($array)) {
      arsort($array);
      foreach ($array as $a => $b)
      {
        $params[] = "$a=$b";
      }
    }
    return implode('&', $params);
  }

  /**
  * Private method to find an option from an associative array of options. If not present, returns the default.
  */
  private static function option($key, array $opts, $default)
  {
    if (array_key_exists($key, $opts)) {
      $r = $opts[$key];
    } else {
      $r = $default;
    }
    return $r;
  }

  /**
   * Causes the default_site.css stylesheet to be included in the list of resources on the
   * page. This gives a basic form layout.
   * This also adds default JavaScript to the page to cause buttons to highlight when you
   * hover the mouse over them.
   */
  public static function link_default_stylesheet() {
    // make buttons highlight when hovering over them
    self::$javascript .= "
$('.ui-state-default').live('mouseover', function() {
  $(this).addClass('ui-state-hover');
});
$('.ui-state-default').live('mouseout', function() {
  $(this).removeClass('ui-state-hover');
});\n";
    self::$default_styles = true;
  }

  /**
   * List of external resources including stylesheets and js files used by the data entry helper class.
   */
  public static function get_resources()
  {
    if (self::$resource_list===null) {
      $base = parent::$base_url;
      if (!self::$js_path) {
        self::$js_path =$base.'media/js/';
      } else if (substr(self::$js_path,-1)!="/") {
        // ensure a trailing slash
        self::$js_path .= "/";
      }
      if (!self::$css_path) {
        self::$css_path =$base.'media/css/';
      } else if (substr(self::$css_path,-1)!="/") {
        // ensure a trailing slash
        self::$css_path .= "/";
      }
      global $indicia_theme;
      global $indicia_theme_path;
      if (!isset($indicia_theme)) {
        // Use default theme if page does not specify it's own.
        $indicia_theme="default";
      }
      if (!isset($indicia_theme_path)) {
        // Use default theme path if page does not specify it's own.
        $indicia_theme_path="$base/media/themes";
      }

      self::$resource_list = array (
        'jquery' => array('javascript' => array(self::$js_path."jquery.js")),
        'openlayers' => array('javascript' => array(self::$js_path."OpenLayers.js", self::$js_path."Proj4js.js", self::$js_path."Proj4defs.js")),
        'addrowtogrid' => array('javascript' => array(self::$js_path."addRowToGrid.js")),
        'indiciaMap' => array('deps' =>array('jquery', 'openlayers'), 'javascript' => array(self::$js_path."jquery.indiciaMap.js")),
        'indiciaMapPanel' => array('deps' =>array('jquery', 'openlayers', 'jquery_ui'), 'javascript' => array(self::$js_path."jquery.indiciaMapPanel.js")),
        'indiciaMapEdit' => array('deps' =>array('indiciaMap'), 'javascript' => array(self::$js_path."jquery.indiciaMap.edit.js")),
        'georeference_google_search_api' => array('javascript' => array("http://www.google.com/jsapi?key=".parent::$google_search_api_key)),
        'locationFinder' => array('deps' =>array('indiciaMapEdit'), 'javascript' => array(self::$js_path."jquery.indiciaMap.edit.locationFinder.js")),
        'autocomplete' => array('deps' => array('jquery'), 'stylesheets' => array(self::$css_path."jquery.autocomplete.css"), 'javascript' => array(self::$js_path."jquery.autocomplete.js")),
        'jquery_ui' => array('deps' => array('jquery'), 'stylesheets' => array("$indicia_theme_path/$indicia_theme/jquery-ui.custom.css"), 'javascript' => array(self::$js_path."jquery-ui.custom.min.js", self::$js_path."jquery-ui.effects.js")),
        'jquery_ui_fr' => array('deps' => array('jquery_ui'), 'javascript' => array(self::$js_path."jquery.ui.datepicker-fr.js")),
      	'json' => array('javascript' => array(self::$js_path."json2.js")),
        'treeview' => array('deps' => array('jquery'), 'stylesheets' => array(self::$css_path."jquery.treeview.css"), 'javascript' => array(self::$js_path."jquery.treeview.js", self::$js_path."jquery.treeview.async.js",
        self::$js_path."jquery.treeview.edit.js")),
        'googlemaps' => array('javascript' => array("http://maps.google.com/maps?file=api&v=2&key=".parent::$google_api_key)),
        'multimap' => array('javascript' => array("http://developer.multimap.com/API/maps/1.2/".parent::$multimap_api_key)),
        'virtualearth' => array('javascript' => array('http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1')),
        'google_search' => array('stylesheets' => array(),
            'javascript' => array(
              "http://www.google.com/jsapi?key=".parent::$google_search_api_key,
              self::$js_path."google_search.js"
            )
        ),
        'fancybox' => array('deps' => array('jquery'), 'stylesheets' => array(self::$js_path.'fancybox/jquery.fancybox.css'), 'javascript' => array(self::$js_path.'fancybox/jquery.fancybox.pack.js')),
        'flickr' => array('deps' => array('fancybox'), 'javascript' => array(self::$js_path."jquery.flickr.js")),
        'treeBrowser' => array('deps' => array('jquery','jquery_ui'), 'javascript' => array(self::$js_path."jquery.treebrowser.js")),
        'defaultStylesheet' => array('deps' => array(''), 'stylesheets' => array(self::$css_path."default_site.css"), 'javascript' => array()),
        'validation' => array('deps' => array('jquery'), 'javascript' => array(self::$js_path.'jquery.validate.js')),
        'plupload' => array('deps' => array('jquery_ui','fancybox'), 'javascript' => array(
            self::$js_path.'jquery.uploader.js', self::$js_path.'/plupload/js/plupload.full.min.js', self::$js_path.'/plupload/js/plupload.html4.js')),
        'jqplot' => array('stylesheets' => array(self::$js_path.'jqplot/jquery.jqplot.css'), 'javascript' => array(self::$js_path.'jqplot/jquery.jqplot.min.js','[IE]'.self::$js_path.'jqplot/excanvas.min.js')),
        'jqplot_bar' => array('javascript' => array(self::$js_path.'jqplot/plugins/jqplot.barRenderer.min.js')),
        'jqplot_pie' => array('javascript' => array(self::$js_path.'jqplot/plugins/jqplot.pieRenderer.min.js')),
        'jqplot_category_axis_renderer' => array('javascript' => array(self::$js_path.'jqplot/plugins/jqplot.categoryAxisRenderer.min.js')),
        'reportgrid' => array('deps' => array('jquery_ui'), 'javascript' => array(self::$js_path.'jquery.reportgrid.js')),
        'tabs' => array('deps' => array('jquery_ui'), 'javascript' => array(self::$js_path.'tabs.js')),
        'wizardprogress' => array('deps' => array('tabs'), 'stylesheets' => array(self::$css_path."wizard_progress.css")),
      );
    }
    return self::$resource_list;
  }

  /**
   * Method to link up the external css or js files associated with a set of code.
   * This is normally called internally by the control methods to ensure the required files are linked into the page so
   * does not need to be called directly. However it can be useful when writing custom code that uses one of these standard
   * libraries such as jQuery. Ensures each file is only linked once. 
   *
   * @param string $resource Name of resource to link. The following options are available:
   * <ul>
   * <li>jquery</li>
   * <li>openlayers</li>
   * <li>addrowtogrid</li>
   * <li>indiciaMap</li>
   * <li>indiciaMapPanel</li>
   * <li>indiciaMapEdit</li>
   * <li>locationFinder</li>
   * <li>autocomplete</li>
   * <li>jquery_ui</li>
   * <li>json</li>
   * <li>treeview</li>
   * <li>googlemaps</li>
   * <li>multimap</li>
   * <li>virtualearth</li>
   * <li>google_search</li>
   * <li>flickr</li>
   * <li>defaultStylesheet</li>
   * </ul>
   */
  public static function add_resource($resource)
  {
    // If this is an available resource and we have not already included it, then add it to the list
    if (array_key_exists($resource, self::get_resources()) && !in_array($resource, self::$required_resources)) {
      $resourceList = self::get_resources();
      if (isset($resourceList[$resource]['deps'])) {
        foreach ($resourceList[$resource]['deps'] as $dep) {
          self::add_resource($dep);
        }
      }
      self::$required_resources[] = $resource;
    }
  }

  /**
   * Returns a span containing any validation errors active on the form for the
   * control with the supplied ID.
   *
   * @param string $fieldname Fieldname of the control to retrieve errors for.
   * @param boolean $plaintext Set to true to return just the error text, otherwise it is wrapped in a span.
   */
  public static function check_errors($fieldname, $plaintext=false)
  {
    global $indicia_templates;
    $error='';
    if (self::$validation_errors!==null) {
       if (array_key_exists($fieldname, self::$validation_errors)) {
         $errorKey = $fieldname;
       } elseif (substr($fieldname, -4)=='date') {
          // For date fields, we also include the type, start and end validation problems
          if (array_key_exists($fieldname.'_start', self::$validation_errors)) {
            $errorKey = $fieldname.'_start';
          }
          if (array_key_exists($fieldname.'_end', self::$validation_errors)) {
            $errorKey = $fieldname.'_end';
          }
          if (array_key_exists($fieldname.'_type', self::$validation_errors)) {
            $errorKey = $fieldname.'_type';
          }
       }
       if (isset($errorKey)) {
         $error = self::$validation_errors[$errorKey];
         // Track errors that were displayed, so we can tell the user about any others.
         self::$displayed_errors[] = $error;
       }
    }
    if ($error!='') {
      if ($plaintext) {
        return $error;
      } else {
        $template = str_replace('{class}', $indicia_templates['error_class'], $indicia_templates['validation_message']);
        $template = str_replace('{for}', $fieldname, $template);
        return str_replace('{error}', lang::get($error), $template);
      }
    } else {
      return '';
    }
  }

  /**
   * Returns the default value for the control with the supplied Id. 
   * The default value is taken as either the $_POST value for this control, or the first of the remaining
   * arguments which contains a non-empty value.
   *
   * @param string $id Id of the control to select the default for.
   * $param [string, [string ...]] Variable list of possible default values. The first that is
   * not empty is used.
   */
  public static function check_default_value($id) {
    $remembered_fields = self::get_remembered_fields();
    if (self::$entity_to_load!=null && array_key_exists($id, self::$entity_to_load)) {
      return self::$entity_to_load[$id];
    } else if ($remembered_fields !== null && in_array($id, $remembered_fields)) {
      $arr = unserialize($_COOKIE['indicia_remembered']);
      if (isset($arr[$id]))
        return $arr[$id];
    }

    $return = null;
    if (is_null($return) || $return == '') { // need to be careful about valid zero values!
      // iterate the variable arguments and use the first one with a real value
      for ($i=1; $i<func_num_args(); $i++) {
        $return = func_get_arg($i);
        if (!is_null($return) && $return != '') {
          break;
        }
      }
    }
    return $return;
  }

  /**
   * Output a DIV which lists configuration problems and is useful for diagnostics.
   * Currently, tests the PHP version and that the cUrl library is installed.
   *
   * @param boolean $fullInfo If true, then successful checks are also output.
   */
  public static function system_check($fullInfo=true) {
    // PHP_VERSION_ID is available as of PHP 5.2.7, if our
    // version is lower than that, then emulate it
    if(!defined('PHP_VERSION_ID'))
    {
        $version = PHP_VERSION;
        define('PHP_VERSION_ID', ($version{0} * 10000 + $version{2} * 100 + $version{4}));
    }
    $r = '<div class="ui-widget ui-widget-content ui-state-highlight ui-corner-all">' .
        '<p class="ui-widget-header"><strong>System check</strong></p><ul>';
    // Test PHP version.
    if (PHP_VERSION_ID<50200) {
      $r .= '<li class="ui-state-error">Warning: PHP version is '.phpversion().' which does not support JSON communication with the Indicia Warehouse.</li>';
    } elseif ($fullInfo) {
      $r .= '<li>Success: PHP version is '.phpversion().'.</li>';
    }
    // Test cUrl library installed
    if (!function_exists('curl_exec')) {
      $r .= '<li class="ui-state-error">Warning: The cUrl PHP library is not installed on the server and is required for communication with the Indicia Warehouse.</li>';
    } else {
      if ($fullInfo) {
        $r .= '<li>Success: The cUrl PHP library is installed.</li>';
      }
      // Test we have full access to the server - it doesn't matter what website id we pass here.'
      $postargs = "website_id=0";
      $curl_check = self::http_post(parent::$base_url.'index.php/services/security/get_read_nonce', $postargs, false);
      if ($curl_check['result']) {
        if ($fullInfo) {
          $r .= '<li>Success: Indicia Warehouse URL responded to a POST request.</li>';
        }
      } else {
        // Some sort of cUrl problem occurred
        if ($curl_check['errno']) {
          $r .= '<li class="ui-state-error">Warning: The cUrl PHP library could not access the Indicia Warehouse. The error was reported as:';
          $r .= $curl_check['output'].'<br/>';
          $r .= 'Please ensure that this web server is not prevented from accessing the server identified by the ' .
              'helper_config.php $base_url setting by a firewall. The current setting is '.parent::$base_url.'</li>';
        } else {
          $r .= '<li class="ui-widget ui-state-error">Warning: A request sent to the Indicia Warehouse URL did not respond as expected. ' .
                'Please ensure that the helper_config.php $base_url setting is correct. ' .
                'The current setting is '.parent::$base_url.'<br></li>';
        }
      }
      $missing_configs = array();
      $blank_configs = array();
      // Run through the expected configuration settings, checking they are present and not empty
      self::check_config('$base_url', isset(self::$base_url), empty(self::$base_url), $missing_configs, $blank_configs);
      self::check_config('$upload_path', isset(self::$upload_path), empty(self::$upload_path), $missing_configs, $blank_configs);
	  // don't test $indicia_upload_path as it is assumed to be upload/ if missing.
      self::check_config('$geoserver_url', isset(self::$geoserver_url), empty(self::$geoserver_url), $missing_configs, $blank_configs);
      self::check_config('$geoplanet_api_key', isset(self::$geoplanet_api_key), empty(self::$geoplanet_api_key), $missing_configs, $blank_configs);
      self::check_config('$google_search_api_key', isset(self::$google_search_api_key), empty(self::$google_search_api_key), $missing_configs, $blank_configs);
      self::check_config('$google_api_key', isset(self::$google_api_key), empty(self::$google_api_key), $missing_configs, $blank_configs);
      self::check_config('$multimap_api_key', isset(self::$multimap_api_key), empty(self::$multimap_api_key), $missing_configs, $blank_configs);
      self::check_config('$flickr_api_key', isset(self::$flickr_api_key), empty(self::$flickr_api_key), $missing_configs, $blank_configs);
      self::check_config('$flickr_api_secret', isset(self::$flickr_api_secret), empty(self::$flickr_api_secret), $missing_configs, $blank_configs);
      // Warn the user of the missing ones - the important bit.
      if (count($missing_configs)>0) {
        $r .= '<li class="ui-widget ui-state-error">Error: The following configuration entries are missing from helper_config.php : '.
            implode(', ', $missing_configs).'. This may prevent the data_entry_helper class from functioning normally.</li>';
      }
      // Also warn them of blank ones - not so important as it should only affect the one area of functionality
      if (count($blank_configs)>0) {
        $r .= '<li class="ui-widget ui-state-error">Warning: The following configuration entries are not specified in helper_config.php : '.
            implode(', ', $blank_configs).'. This means the respective areas of functionality will not be available.</li>';
      }
    // Test we have a writeable cache directory
    $cacheFolder = self::relative_client_helper_path() . (isset(parent::$cache_folder) ? parent::$cache_folder : 'cache/');
    if (!is_dir($cacheFolder)) {
      $r .= '<li class="ui-state-error">The cache path setting in helper_config.php points to a missing directory. This will result in slow form loading performance.</li>';
      } elseif (!is_writeable($cacheFolder)) {
      $r .= '<li class="ui-state-error">The cache path setting in helper_config.php points to a read only directory (' . parent::$upload_path . '). This will result in slow form loading performance.</li>';
    } elseif ($fullInfo) {
        $r .= '<li>Success: Cache directory is present and writeable.</li>';
      }
    }
    $r .= '</ul></div>';
    return $r;
  }

  private static function check_config($name, $isset, $empty, &$missing_configs, &$blank_configs) {
    if (!$isset) {
      array_push($missing_configs, $name);
    } else if ($empty) {
      array_push($blank_configs, $name);
    }
  }

  /**
   * Sends a POST using the cUrl library
   */
  public static function http_post($url, $postargs, $output_errors=true) {
    $session = curl_init();
    // Set the POST options.
    curl_setopt ($session, CURLOPT_URL, $url);
    if ($postargs!==null) {
      curl_setopt ($session, CURLOPT_POST, true);
      curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
    }
    curl_setopt($session, CURLOPT_HEADER, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    // Do the POST and then close the session
    $response = curl_exec($session);
    // Check for an error, or check if the http response was not OK. Note that the cUrl emulator only returns connection: close.
    if (curl_errno($session) || (strpos($response, 'HTTP/1.1 200 OK')===false && strpos($response, 'Connection: close')===false)) {
      if ($output_errors) {
        echo '<div class="error">cUrl POST request failed. Please check cUrl is installed on the server and the $base_url setting is correct.<br/>';
        if (curl_errno($session)) {
          echo 'Error number: '.curl_errno($session).'<br/>';
          echo 'Error message: '.curl_error($session).'<br/>';
        }
        echo "Server response<br/>";
        echo $response.'</div>';
      }
      $return = array(
          'result'=>false,
          'output'=> curl_errno($session) ? curl_error($session) : $response,
          'errno'=>curl_errno($session));
    } else {
      $arr_response = explode("\r\n\r\n",$response);
      // last part of response is the actual data
      $return = array('result'=>true,'output'=>array_pop($arr_response));
    }
    curl_close($session);
    return $return;
  }

  /**
  * Helper function to fetch details of attributes associated with a survey.
  * This can be used to auto-generated the forum structure for a survey for example.
  * @todo at moment this assumes non multiplevalue attributes.
  * @todo Improved documentation
  *
  * @return array of attributes.
  */
  public static function getAttributes($options) {
    $retVal = array();
    self::add_resource('json');
    $surveys = array(NULL);
    if (isset($options['survey_id']))
      $surveys[] = $options['survey_id'];

    $attrOptions = array(
          'table'=>$options['attrtable'],
           'extraParams'=> $options['extraParams']+ array(
             'deleted' => 'f',
             'website_deleted' => 'f',
             'query'=>urlencode(json_encode(array('in'=>array('restrict_to_survey_id', $surveys)))),
             'orderby'=>'weight'
           )
    );
    $response = self::get_population_data($attrOptions);
    if (array_key_exists('error', $response))
        return $response;
    foreach ($response as $item){
        $itemId=$item['id'];
        unset($item['id']);
        $item['fieldname']=$options['fieldprefix'].':'.$itemId.($item['multi_value'] == 't' ? '[]' : '');
        $item['caption']=lang::get($item['caption']);
        $retVal[$itemId] = $item;
    }
    if(!$options['id'])
      return $retVal;

    $options['extraParams'][$options['key']] = $options['id'];
    $existingValuesOptions = array(
          'table'=>$options['valuetable']
        ,'cachetimeout' => 0 // can't cache
           ,'extraParams'=> $options['extraParams']);
    $response = self::get_population_data($existingValuesOptions);
    if (array_key_exists('error', $response))
      return $response;
    foreach ($response as $item){
        if(isset($retVal[$item[$options['attrtable'].'_id']])){
        if(isset($item['id'])){
          $retVal[$item[$options['attrtable'].'_id']]['fieldname'] = $options['fieldprefix'].':'.$item[$options['attrtable'].'_id'].':'.$item['id'];
             $retVal[$item[$options['attrtable'].'_id']]['default'] = $item['raw_value'];
        }
      }
    }
    return $retVal;
  }

  private static function boolean_attribute($ctrl, $options) {
    global $indicia_templates;
    $options = array_merge(
      array(
        'sep' => '',
        'containerTemplate' => 'check_or_radio_group_container',
        'class' => 'control-box'
      ),
      $options
    );
    unset($options['validation']);
    $default = self::check_default_value($options['fieldname'],
        array_key_exists('default', $options) ? $options['default'] : '', '0');
    $options['default'] = $default;
    $options = array_merge(array('sep' => ''), $options);
    if ($options['class']=='') {
      // default class is control-box
      $options['class']='control-box';
    }
    $items = "";
    $buttonList = array(lang::get('No') => '0', lang::get('Yes') => '1');
    $disabled = isset($options['disabled']) ?  $options['disabled'] : '';
    foreach ($buttonList as $caption => $value) {
          $checked = ($default == $value) ? ' checked="checked" ' : '';
          $items .= str_replace(
              array('{type}', '{fieldname}', '{value}', '{checked}', '{caption}', '{sep}', '{disabled}', '{itemId}', '{class}'),
              array($ctrl, $options['fieldname'], $value, $checked, $caption, $options['sep'], $disabled, $options['fieldname'].':'.$value, ''),
              $indicia_templates['check_or_radio_group_item']
          );
    }
    $options['items']=$items;
    $lblTemplate = $indicia_templates['label'];
    $indicia_templates['label'] = str_replace(' for="{id}"', '', $lblTemplate);
    $r = self::apply_template('check_or_radio_group', $options);
    // reset the old template
    $indicia_templates['label'] = $lblTemplate;
    if (array_key_exists('containerClass', $options)) {
      $containerOpts = array(
              'group' => $r,
              'class' => $options['containerClass']
            );
      if(array_key_exists('suffixTemplate', $options)) {
      	$containerOpts['suffixTemplate'] = $options['suffixTemplate'];
      }
      $r = self::apply_template($options['containerTemplate'], $containerOpts);
    }
    return $r;

  }

  /**
  * Helper function to output an attribute
  *
  * @param array $item Attribute definition as returned by a call to getAttributes.
  * @param array $options Additional options for the attribute to be output. Array entries can be:
  *    disabled
  *    suffixTemplate
  *    default
  *    validation
  *    noBlankText
  *    extraParams
  *    booleanCtrl - radio or checkbox for boolean attribute output, default is checkbox. Can also be a checkbox_group, used to
  *    allow selection of both yes and no, e.g. on a filter form.
  *    language - iso 639:3 code for the language to output for terms in a termlist. If not set no language filter is used.
  * @return string HTML to insert into the page for the control.
  */
  public static function outputAttribute($item, $options=array()) {
    $attrOptions = array('label'=>$item['caption'],
              'fieldname'=>$item['fieldname'],
              'disabled'=>isset($options['disabled']) ? $options['disabled'] : '');
    if(isset($options['id'])) $attrOptions['id'] = $options['id'];
    if(isset($options['suffixTemplate'])) $attrOptions['suffixTemplate'] = $options['suffixTemplate'];
    if(isset($item['default'])) $attrOptions['default']= $item['default'];
    else if(isset($options['default'])) $attrOptions['default']= $options['default'];
    if(isset($options['validation'])) $attrOptions['validation'] = $options['validation'];
    if(isset($options['sep'])) $attrOptions['sep'] = $options['sep'];
    switch ($item['data_type']) {
        case 'Text':
        case 'T':
        case 'Float':
        case 'F':
        case 'Integer':
        case 'I':
          $output = self::text_input($attrOptions);
          break;
        case 'Boolean':
        case 'B':
          // Can't use a checkbox as it is not included in the post when unchecked, so unset data is not saved
          // in the optional attribute record.
          // If using this to generate a filter, need also to use checkboxes.
            $attrOptions['class'] = array_key_exists('class', $options) ? $options['class'] : 'control-box';
            if(array_key_exists('containerClass', $options)){
              $attrOptions['containerClass'] = $options['containerClass'];
            }
            if(array_key_exists('booleanCtrl', $options) && $options['booleanCtrl']=='radio') {
              $output = self::boolean_attribute('radio', $attrOptions);
            } elseif(array_key_exists('booleanCtrl', $options) && $options['booleanCtrl']=='checkbox_group') {
              $output = self::boolean_attribute('checkbox', $attrOptions);
            } else {
              $output = self::checkbox($attrOptions);
            }

            break;
        case 'D': // Date
        case 'Specific Date': // Date
        case 'V': // Vague Date
        case 'Vague Date': // Vague Date
            $attrOptions['class'] = ($item['data_type'] == 'D' ? "date-picker" : "vague-date-picker");
            $output = self::date_picker($attrOptions);
            break;
        case 'Lookup List':
        case 'L':
          if(!array_key_exists('noBlankText', $options)){
            $attrOptions = $attrOptions + array('blankText' => '');
          }
          $attrOptions['class'] = array_key_exists('class', $options) ? $options['class'] : 'control-box';
          if(array_key_exists('containerClass', $options)){
            $attrOptions['containerClass'] = $options['containerClass'];
          }
          $dataSvcParams = array('termlist_id' => $item['termlist_id'], 'view' => 'detail');
          if (array_key_exists('language', $options)) {
            $dataSvcParams = $dataSvcParams + array('iso'=>$options['language']);
          }
          if (!array_key_exists('orderby', $options['extraParams'])) {
            $dataSvcParams = $dataSvcParams + array('orderby'=>'sort_order');
          }
          if(array_key_exists('lookUpListCtrl', $options)){
            $ctrl = $options['lookUpListCtrl'];
          } else {
            $ctrl = 'select';
          }
          if(array_key_exists('lookUpKey', $options)){
            $lookUpKey = $options['lookUpKey'];
          } else {
            $lookUpKey = 'id';
          }
          $output = call_user_func(array('data_entry_helper', $ctrl), $attrOptions + array(
                  'table'=>'termlists_term',
                  'captionField'=>'term',
                  'valueField'=>$lookUpKey,
                  'extraParams' => $options['extraParams'] + $dataSvcParams));
          break;
        default:
            if ($item)
              $output = '<strong>UNKNOWN DATA TYPE "'.$item['data_type'].'" FOR ID:'.$item['id'].' CAPTION:'.$item['caption'].'</strong><br />';
            else
              $output = '<strong>Requested attribute is not available</strong><br />';
            break;
    }

    return $output; // str_replace("\n", "", $output);
  }

  /**
   * Takes a file that has been uploaded to the client website upload folder, and moves it to the warehouse upload folder using the
   * data services.
   *
   * @param string $path Path to the file to upload, relative to the interim image path folder (normally the
   * client_helpers/upload folder.
   * @param boolean $persist_auth Allows the write nonce to be preserved after sending the file, useful when several files
   * are being uploaded.
   * @return string Error message, or true if successful.
   */
  private static function send_file_to_warehouse($path, $persist_auth=false) {
    $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
    $uploadpath = data_entry_helper::relative_client_helper_path() . $interim_image_folder;
    $serviceUrl = parent::$base_url."/index.php/services/data/handle_media";
    // This is used by the file box control which renames uploaded files using a guid system, so disable renaming on the server.
    $postargs = array('name_is_guid' => 'true');
    // attach authentication details
    if (array_key_exists('auth_token', $_POST))
      $postargs['auth_token'] = $_POST['auth_token'];
    if (array_key_exists('nonce', $_POST))
      $postargs['nonce'] = $_POST['nonce'];
    if ($persist_auth)
      $postargs['persist_auth'] = 'true';
    $file_to_upload = array('media_upload'=>'@'.realpath($uploadpath.$path));
    $response = data_entry_helper::http_post($serviceUrl, $file_to_upload + $postargs);
    $output = json_decode($response['output'], true);
    $r = true; // default is success
    if (is_array($output)) {
      //an array signals an error
      if (array_key_exists('error', $output)) {
        // return the most detailed bit of error information
        if (isset($output['errors']['media_upload']))
          $r = $output['errors']['media_upload'];
        else
          $r = $output['error'];
      }
    }
    //remove local copy
    unlink(realpath($uploadpath.$path));
    return $r;
  }

  /**
   * Calculates the relative path to the client_helpers folder from wherever the current PHP script is.
   */
  public static function relative_client_helper_path() {
    // get the paths to the client helper folder and php file folder as an array of tokens
    $clientHelperFolder = explode(DIRECTORY_SEPARATOR, realpath(dirname(__FILE__)));
    $currentPhpFileFolder = explode(DIRECTORY_SEPARATOR, realpath(dirname($_SERVER['SCRIPT_FILENAME'])));
    // Find the first part of the paths that is not the same
    for($i = 0; $i<min(count($currentPhpFileFolder), count($clientHelperFolder)); $i++) {
      if ($clientHelperFolder[$i] != $currentPhpFileFolder[$i]) {
        break;
      }
    }
    // step back up the path to the point where the 2 paths differ
    $path = str_repeat('../', count($currentPhpFileFolder)-$i);
    // add back in the different part of the path to the client helper folder
    for ($j = $i; $j < count($clientHelperFolder); $j++) {
      $path .= $clientHelperFolder[$j] . '/';
    }
    return $path;
  }

  /**
   * Retrieves an array of just the image data from a $_POST or set of control values.
   *
   * @param array $values Pass the $_POST data or other array of form values in this parameter.
   * @param string $modelName The singular name of the image table, e.g. location_image or occurrence_image etc. If
   * null, then any image model will be used.
   * @param boolean $simpleFileInputs If true, then allows a file input with name=occurrence:image (or similar)
   * to be used to point to an image file. The file is uploaded to the interim image folder to ensure that it
   * can be handled in the same way as a pre-uploaded file.
   * @param boolean $moveSimpleFiles If true, then any file uploaded by normal means to the server (via multipart form submission
   * for a field named occurrence:image[:n] or similar) will be moved to the interim image upload folder.
   */
  public static function extract_image_data($values, $modelName=null, $simpleFileInputs=false, $moveSimpleFiles=false) {
    $r = array();
    foreach ($values as $key => $value) {
      if (!empty($value)) {
        // If the field is a path, and the model name matches or we are not filtering on model name
        $pathPos = strpos($key, ':path:');
        if ($pathPos !== false)
          // Found an image path. Anything after path is the unique id. We include the colon in this.
          $uniqueId = substr($key, $pathPos + 5);
        else {
          // look for a :path field with no suffix (i.e. a single image upload field after a validation failure,
          // when it stores the path in a hidden field so it is not lost).
          if (substr($key, -5)==':path') {
            $uniqueId = '';
            $pathPos = strlen($key)-5;
          }
        }
        if ($pathPos !==false && ($modelName === null || $modelName == substr($key, 0, strlen($modelName)))) {
          $prefix = substr($key, 0, $pathPos);
          $r[] = array(
            // Id is set only when saving over an existing record.
            'id' => array_key_exists($prefix.':id'.$uniqueId, $values) ?
                $values[$prefix.':id'.$uniqueId] : '',
            'path' => $value,
            'caption' => isset($values[$prefix.':caption'.$uniqueId]) ? utf8_encode($values[$prefix.':caption'.$uniqueId]) : ''
          );
        }
      }
    }

    // Now look for image file inputs, called something like occurrence:image[:n]
    if ($simpleFileInputs) {
      foreach($_FILES as $key => $file) {
        if (substr($key, 0, strlen($modelName))==str_replace('_', ':', $modelName)) {
          if ($file['error']=='1') {
            // file too big error dur to php.ini setting
            if (self::$validation_errors==null) self::$validation_errors = array();
            self::$validation_errors[$key] = lang::get('file too big for webserver');
          }
          elseif (!self::check_upload_size($file)) {
            // even if file uploads Ok to interim location, the Warehouse may still block it.
            if (self::$validation_errors==null) self::$validation_errors = array();
            self::$validation_errors[$key] = lang::get('file too big for warehouse');
          }
          elseif ($file['error']=='0') {
            // no file upload error
            $fname = isset($file['tmp_name']) ? $file['tmp_name'] : '';
            if ($fname && $moveSimpleFiles) {
              // Get the original file's extension
              $parts = explode(".",$file['name']);
              $fext = array_pop($parts);
              // Generate a file id to store the image as
              $destination = time().rand(0,1000).".".$fext;
              $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
              $uploadpath = self::relative_client_helper_path().$interim_image_folder;
              if (move_uploaded_file($fname, $uploadpath.$destination)) {
                $r[] = array(
                  // Id is set only when saving over an existing record. This will always be a new record
                  'id' => '',
                  'path' => $destination,
                  'caption' => ''
                );
                // record the new file name, also note it in the $_POST data so it can be tracked after a validation failure
                $_FILES[$key]['name'] = $destination;
                $pathField = str_replace(':image','_image:path', $key);
                $_POST[$pathField] = $destination;
              }
            } else {
              // Not moving the file, as it should already be moved.
              $r[] = array(
                // Id is set only when saving over an existing record. This will always be a new record
                'id' => '',
                // This should be a file already in the interim image upload folder.
                'path' => $_FILES[$key]['name'],
                'caption' => ''
              );
            }
          }
        }
      }
    }
    return $r;
  }

/**
   * Validation rule to test if an uploaded file is allowed by file size.
   * File sizes are obtained from the helper_config maxUploadSize, and defined as:
   * SB, where S is the size (1, 15, 300, etc) and
   * B is the byte modifier: (B)ytes, (K)ilobytes, (M)egabytes, (G)igabytes.
   * Eg: to limit the size to 1MB or less, you would use "1M".
   *
   * @param   array    $_FILES item
   * @param   array    maximum file size
   * @return  bool
   */
  private static function check_upload_size(array $file)
  {
    if ((int) $file['error'] !== UPLOAD_ERR_OK)
      return TRUE;

    if (isset(parent::$maxUploadSize))
      $size = parent::$maxUploadSize;
    else
      $size = '1M'; // default

    if ( ! preg_match('/[0-9]++[BKMG]/', $size))
      return FALSE;

    $size = self::convert_to_bytes($size);

    // Test that the file is under or equal to the max size
    return ($file['size'] <= $size);
  }

  /**
   * Utility method to convert a memory size string (e.g. 1K, 1M) into the number of bytes.
   *
   * @param string $size Size string to convert. Valid suffixes as G (gigabytes), M (megabytes), K (kilobytes) or nothing.
   * @return integer Number of bytes.
   */
  private static function convert_to_bytes($size) {
    // Make the size into a power of 1024
    switch (substr($size, -1))
    {
      case 'G': $size = intval($size) * pow(1024, 3); break;
      case 'M': $size = intval($size) * pow(1024, 2); break;
      case 'K': $size = intval($size) * pow(1024, 1); break;
      default:  $size = intval($size);                break;
    }
    return $size;
  }

/**
   * Method that retrieves the data from a report or a table/view, ready to display in a chart or grid.
   * @param array $options Options array for the control. Can contain a dataSource (report or table/view name),
   * mode (direct or report) and readAuth entries. Pass linkOnly=true to return just a link to the report data
   * rather than the data.
   * @param string $extra Any additional parameters to append to the request URL, for example orderby, limit or offset.
   * @param boolean $linkOnly Set this to true to return a URL link for the report only, rather than actually accessing the
   * report and returning the data.
   */
  public static function get_report_data($options, $extra='', $linkOnly = false) {
    if ($options['mode']=='report') {
      $serviceCall = 'report/requestReport?report='.$options['dataSource'].'.xml&reportSource=local&';
    } elseif ($options['mode']=='direct') {
      $serviceCall = 'data/'.$options['dataSource'].'?';
    } else {
      throw new Exception('Invalid mode parameter for call to report_grid');
    }
    // We request limit 1 higher than actually needed, so we know if the next page link is required.
    $request = parent::$base_url.'index.php/services/'.
        $serviceCall.
        'mode=json&nonce='.$options['readAuth']['nonce'].
        '&auth_token='.$options['readAuth']['auth_token'].
        $extra;
    if (isset($options['extraParams'])) {
    	foreach ($options['extraParams'] as $key=>$value)
    	  $request .= "&$key=".urlencode($value);
    }
    if (isset($options['linkOnly']) && $options['linkOnly'])
      return $request;
    else {
      $response = self::http_post($request, null);
      return json_decode($response['output'], true);
    }
  }

  /**
   * Provides access to a list of remembered field values from the last time the form was used. 
   * Accessor for the $remembered_fields variable. This is a list of the fields on the form
   * which are to be remembered the next time the form is loaded, e.g. for values that do not change
   * much from record to record. This creates the list on demand, by calling a hook indicia_define_remembered_fields
   * if it exists. indicia_define_remembered_fields should call data_entry_helper::set_remembered_fields to give it
   * an array of field names.
   * Note that this hook architecture is required to allow the list of remembered fields to be made available
   * before the form is constructed, since it is used by the code which saves a submitted form to store the
   * remembered field values in a cookie.
   * @return Array List of the fields to remember.
   */
  public static function get_remembered_fields() {
    if (self::$remembered_fields == null && function_exists('indicia_define_remembered_fields')) {
      indicia_define_remembered_fields();
    }
    return self::$remembered_fields;
  }

  /**
   * Accessor to set the list of remembered fields. 
   * Should only be called by the hook method indicia_define_remembered_fields.
   * @see get_rememebered_fields
   * @param $arr Array of field names
   */
  public static function set_remembered_fields($arr) {
    self::$remembered_fields = $arr;
  }

}
?>