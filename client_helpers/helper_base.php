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

require_once('helper_config.php');
require_once('lang.php');
require_once("libcurlEmulator/libcurlemu.inc.php");

global $indicia_templates;

/**
 * Provides control templates to define the output of the data entry helper class.
 *
 * @package	Client
 */
$indicia_templates = array(
  'blank' => '',
  'prefix' => '',
  'label' => '<label for="{id}"{labelClass}>{label}:</label>'."\n",
  'suffix' => "<br/>\n",
  'nosuffix' => " \n",
  'requiredsuffix' => '<span class="deh-required">*</span><br/>'."\n",
  'requirednosuffix' => '<span class="deh-required">*</span>'."\n",
  'button' => '<input type=\"button\" id=\"{id}\" class=\"ui-corner-all ui-widget-content ui-state-default indicia-button {class}\" value=\"{caption}\" />',
  'anchorButton' => '<a class=\"ui-corner-all ui-widget-content ui-state-default indicia-button {class}\" href=\"{href}\" id=\"{id}\">{caption}</a>',
  'lock_icon' => '<span id="{id}_lock" class="unset-lock">&nbsp;</span>',
  'lock_javascript' => "indicia.locks.initControls (
      \"".lang::get('locked tool-tip')."\", 
      \"".lang::get('unlocked tool-tip')."\",
      \"{lock_form_mode}\"
      );\n",
  'validation_message' => '<label for="{for}" class="{class}">{error}</label>'."\n",
  'validation_icon' => '<span class="ui-state-error ui-corner-all validation-icon">'.
      '<span class="ui-icon ui-icon-alert"></span></span>',
  'error_class' => 'inline-error',
  'invalid_handler_javascript' => "function(form, validator) {
          var tabselected=false;
          jQuery.each(validator.errorMap, function(ctrlId, error) {
            // select the tab containing the first error control
            var ctrl = jQuery('[name=' + ctrlId.replace(/:/g, '\\\\:').replace(/\[/g, '\\\\[').replace(/]/g, '\\\\]') + ']');
            if (!tabselected && typeof(tabs)!=='undefined') {
              tabs.tabs('select',ctrl.filter('input,select').parents('.ui-tabs-panel')[0].id);
              tabselected = true;
            }
            ctrl.parents('fieldset').removeClass('collapsed');
            ctrl.parents('.fieldset-wrapper').show();
          });
        }",
  'image_upload' => '<input type="file" id="{id}" name="{fieldname}" accept="png|jpg|gif|jpeg" {title}/>'."\n".
      '<input type="hidden" id="{pathFieldName}" name="{pathFieldName}" value="{pathFieldValue}"/>'."\n",
  'text_input' => '<input type="text" id="{id}" name="{fieldname}"{class} {disabled} {readonly} value="{default}" {title} {maxlength} />'."\n",
  'hidden_text' => '<input type="hidden" id="{id}" name="{fieldname}" {disabled} value="{default}" />',
  'password_input' => '<input type="password" id="{id}" name="{fieldname}"{class} {disabled} value="{default}" {title} />'."\n",
  'textarea' => '<textarea id="{id}" name="{fieldname}"{class} {disabled} cols="{cols}" rows="{rows}" {title}>{default}</textarea>'."\n",
  'checkbox' => '<input type="hidden" name="{fieldname}" value="0"/><input type="checkbox" id="{id}" name="{fieldname}" value="1"{class}{checked}{disabled} {title} />'."\n",
  'date_picker' => '<input type="text" placeholder="{placeholder}" size="30"{class} id="{id}" name="{fieldname}" value="{default}" {title}/>',
  'select' => '<select id="{id}" name="{fieldname}"{class} {disabled} {title}>{items}</select>',
  'select_item' => '<option value="{value}" {selected} >{caption}</option>',
  'select_species' => '<option value="{value}" {selected} >{caption} - {common}</option>',
  'listbox' => '<select id="{id}" name="{fieldname}"{class} {disabled} size="{size}" multiple="{multiple}" {title}>{items}</select>',
  'listbox_item' => '<option value="{value}" {selected} >{caption}</option>',
  'list_in_template' => '<ul{class} {title}>{items}</ul>',
  'check_or_radio_group' => '<span {class}>{items}</span>',
  'check_or_radio_group_item' => '<nobr><span><input type="{type}" name="{fieldname}" id="{itemId}" value="{value}"{class}{checked} {disabled}/><label for="{itemId}">{caption}</label></span></nobr>{sep}',
  'map_panel' => "<script type=\"text/javascript\">\n/* <![CDATA[ */\n".
    "document.write('<div id=\"{divId}\" style=\"width: {width}; height: {height};\"{class}></div>');\n".
    "/* ]]> */</script>",
  'georeference_lookup' => "<script type=\"text/javascript\">\n/* <![CDATA[ */\n".
    "document.write('<input type=\"text\" id=\"imp-georef-search\"{class} />{searchButton}');\n".
    "document.write('<div id=\"imp-georef-div\" class=\"ui-corner-all ui-widget-content ui-helper-hidden\">');\n".
    "document.write('  <div id=\"imp-georef-output-div\">');\n".
    "document.write('  </div>  {closeButton}');\n".
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
      {max}
    });
    jQuery('input#{escaped_input_id}').result(function(event, data) {
      jQuery('input#{escaped_id}').attr('value', data.{valueField});
      jQuery('input#{escaped_id}').change();
    });\r\n",
  'sub_list' => '<div id="{id}:box" class="control-box wide"><div {class}>'."\n".
    '<div>'."\n".
    '{panel_control} <input id="{id}:add" type="button" value="'.lang::get('add').'" />'.
    '<span class="ind-cancel-icon">&nbsp;</span>'."\n".
    '</div>'."\n".
    '<ul id="{id}:sublist" class="ind-sub-list">{items}</ul>{subListAdd}'."\n".
    '</div></div>'."\n",
  'sub_list_add' => "\n".'<input type="hidden"  id="{id}:addToTable" name="{mainEntity}:insert_captions_use" value="{basefieldname}" />'.
    '<input type="hidden" name="{mainEntity}:insert_captions_to_create" value="{table}" />',
  'sub_list_item' => '<li><span class="ind-delete-icon">&nbsp;</span>{caption}'.
    '<input type="hidden" name="{fieldname}" value="{value}" /></li>',
  'sub_list_javascript' => "  if ({hide}==='none') {
    jQuery('#{escaped_id}\\\\:box .ind-cancel-icon').css('display', 'none');
    }
  jQuery('#{escaped_id}\\\\:add').click(function(event){
    // transfer caption and value from search control to the displayed and hidden lists
    var search$ = $('#{escaped_id}\\\\:search\\\\:{escaped_captionField}');
    var hiddenSearch$ = $('#{escaped_id}\\\\:search');
    var caption = search$.val().trim();
    if (jQuery('#{escaped_id}\\\\:addToTable').length==0) {
      // not addToTable mode, so pass IDs
      var value = hiddenSearch$.val().trim();
    } else {
      // addToTable mode, so pass text captions
      var value = caption;
    }
    if (caption!=='') {
      var sublist$ = $('#{escaped_id}\\\\:sublist');
      sublist$.append('{subListItem}');
      search$.val('');
      hiddenSearch$.val('');
      search$.focus();
    }
  });
  jQuery('#{escaped_id}\\\\:box span.ind-cancel-icon').click(function(event){
    // Hide the delete icons and the add bar. Insert an icon to redisplay add bar
    if ($('#{escaped_id}\\\\:sublist span.ind-delete-icon').length>0) {
      $('#{escaped_id}\\\\:sublist span.ind-delete-icon').removeClass('ind-delete-icon').addClass('ind-no-icon');
      $('#{escaped_id}\\\\:sublist span.ind-no-icon:first').removeClass('ind-no-icon').addClass('ind-add-icon');
      $(this).closest('div').hide({hide});
    }
  });
  jQuery('#{escaped_id}\\\\:sublist span.ind-delete-icon').live('click', function(event){
    // remove the value from the displayed list and the hidden list
    var li$ = $(this).closest('li');
    li$.remove();
  });
  jQuery('#{escaped_id}\\\\:sublist span.ind-add-icon').live('click', function(event){
    // restore delete icons on list and show the add bar
    $('#{escaped_id}\\\\:sublist span.ind-no-icon').removeClass('ind-no-icon').addClass('ind-delete-icon');
    $('#{escaped_id}\\\\:sublist span.ind-add-icon').removeClass('ind-add-icon').addClass('ind-delete-icon');
    $('#{escaped_id}\\\\:add').closest('div').show({hide});
  });
  jQuery('form:has(#{escaped_id}\\\\:search)').submit(
    function(event) {
      // select autocomplete search controls in this sub_list and disable them to prevent submiting values
      $('#{escaped_id}\\\\:search, #{escaped_id}\\\\:search\\\\:{escaped_captionField}')
        .attr('disabled', 'disabled');
    });\n",
    
    'linked_list_javascript' => '
{fn} = function() {
  $("#{escapedId}").addClass("ui-state-disabled").html("<option>Loading...</option>");
  $.getJSON("{request}&query={query}", function(data){
    var $control = $("#{escapedId}");
    $control.html("");
    if (data.length>0) {
      $control.removeClass("ui-state-disabled");
      $.each(data, function(i) {
        $control.append("<option value=" + this.{valueField} + ">" + this.{captionField} + "</option>");
      });
    } else {
      $control.html("<option>{instruct}</option>");
    }
  });
};
$("#{parentControlId}").bind("change.indicia", {fn});
$("#{parentControlId}").trigger("change.indicia");'."\n",
    
  'postcode_textbox' => '<input type="text" name="{fieldname}" id="{id}"{class} value="{default}" '.
        'onblur="javascript:decodePostcode(\'{linkedAddressBoxId}\');" />',
  'sref_textbox' => '<input type="text" id="{id}" name="{fieldname}" {class} {disabled} value="{default}" />' .
        '<input type="hidden" id="imp-geom" name="{geomFieldname}" value="{defaultGeom}" />',
  'sref_textbox_latlong' => '<label for="{idLat}">{labelLat}:</label>'.
        '<input type="text" id="{idLat}" name="{fieldnameLat}" {class} {disabled} value="{defaultLat}" /><br />' .
        '<label for="{idLong}">{labelLong}:</label>'.
        '<input type="text" id="{idLong}" name="{fieldnameLong}" {class} {disabled} value="{defaultLong}" />' .
        '<input type="hidden" id="imp-geom" name="geomFieldname" value="{defaultGeom}" />'.
        '<input type="hidden" id="{id}" name="{fieldname}" value="{default}" />',
  'attribute_cell' => "\n<td class=\"scOccAttrCell ui-widget-content {class}\" headers=\"{headers}\">{content}</td>",
  'taxon_label_cell' => "\n<td class=\"scTaxonCell ui-state-default\" headers=\"{tableId}-species\" {colspan}>{content}</td>",
  'helpText' => "\n<p class=\"{helpTextClass}\">{helpText}</p>",
  'button' => '<div class="indicia-button ui-state-default ui-corner-all" id="{id}"><span>{caption}</span></div>',
  'file_box' => '',                   // the JQuery plugin default will apply, this is just a placeholder for template overrides.
  'file_box_initial_file_info' => '', // the JQuery plugin default will apply, this is just a placeholder for template overrides.
  'file_box_uploaded_image' => '',    // the JQuery plugin default will apply, this is just a placeholder for template overrides.
  'paging_container' => "<div class=\"pager ui-helper-clearfix\">\n{paging}\n</div>\n",
  'paging' => '<div class="left">{first} {prev} {pagelist} {next} {last}</div><div class="right">{showing}</div>',
  'jsonwidget' => '<div id="{id}" {class}></div>',
  'report_picker' => '<div id="{id}" {class}>{reports}<div class="report-metadata"></div><div class="ui-helper-clearfix"></div></div>',
  'report_download_link' => '<div class="report-download-link"><a href="{link}">{caption}</a></div>'
);


/**
 * Base class for the report and data entry helpers. Provides several generally useful methods and also includes
 * resource management.
 */
class helper_base extends helper_config {
 
  /**
   * @var boolean Flag set to true if returning content for an AJAX request. This allows the javascript to be returned
   * direct rather than embedding in document.ready and window.onload handlers.
   */
  public static $is_ajax = false;

  /**
   * @var integer Website ID, stored here to assist with caching.
   */
  protected static $website_id = null;

  /**
   * @var Array List of resources that have been identified as required by the controls used. This defines the
   * JavaScript and stylesheets that must be added to the page. Each entry is an array containing stylesheets and javascript
   * sub-arrays. This has public access so the Drupal module can perform Drupal specific resource output.
   */
  public static $required_resources=array();

  /**
   * @var Array List of all available resources known. Each resource is named, and contains a sub array of
   * deps (dependencies), stylesheets and javascripts.
   */
  public static $resource_list=null;

  /**
   * @var string Path to Indicia JavaScript folder. If not specified, then it is calculated from the Warehouse $base_url.
   * This path should be a full path on the server (starting with '/' exluding the domain).
   */
  public static $js_path = null;

  /**
   * @var string Path to Indicia CSS folder. If not specified, then it is calculated from the Warehouse $base_url.
   * This path should be a full path on the server (starting with '/' exluding the domain).
   */
  public static $css_path = null;
  
  /**
   * @var string Path to Indicia Images folder.
   */
  public static $images_path = null;
  
  /**
   * @var string Path to proxy script for calls to the warehouse (optional, allows the warehouse to sit behind a firewall only accessible
   * from the server).
   */
  public static $warehouse_proxy = null;

  /**
   * @var array List of resources that have already been dumped out, so we don't duplicate them. For example, if the
   * site template includes JQuery set $dumped_resources[]='jquery'.
   */
  public static $dumped_resources=array();

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
   * @var boolean Setting to completely disable loading from the cache
   */
  public static $nocache = false;

  /**
   * List of methods used to report a validation failure. Options are message, message, hint, icon, colour, inline.
   * The inline option specifies that the message should appear on the same line as the control.
   * Otherwise it goes on the next line, indented by the label width. Because in many cases, controls
   * on an Indicia form occupy the full available width, it is often more appropriate to place error
   * messages on the next line so this is the default behaviour.
   * @var array
   */
  public static $validation_mode=array('message', 'colour');

  /**
   * @var array Name of the form which has been set up for jQuery validation, if any.
   */
  public static $validated_form_id = null;

  /**
   * @var string Helptext positioning. Determines where the information is displayed when helpText is defined for a control.
   * Options are before, after.
   */
  public static $helpTextPos='after';

  /**
   * @var string Form Mode. Initially unset indicating new input, but can be set to ERRORS or RELOAD.
   */
  public static $form_mode=null;

  /**
   * @var array List of all error messages returned from an attempt to save.
   */
  public static $validation_errors=null;

  /**
   * @var Array of default validation rules to apply to the controls on the form if the
   * built in client side validation is used (with the jQuery validation plugin). This array
   * can be replaced if required.
   * @todo This array could be auto-populated with validation rules for a survey's fields from the
   * Warehouse.
   */
  public static $default_validation_rules = array(
    'sample:date'=>array('required','date'),
    'sample:entered_sref'=>array('required'),
    'occurrence:taxa_taxon_list_id'=>array('required')
  );

  /**
   * @var array List of messages defined to pass to the validation plugin.
   */
  public static $validation_messages = array();

  /**
   * @var Boolean indicates if any form controls have specified the lockable option.
   * If so, we will need to output some javascript.
   */
  protected static $using_locking = false;

  /**
   * @var Boolean Are we linking in the default stylesheet? Handled sligtly different to the others so it can be added to the end of the
   * list, allowing our CSS to override other stuff.
   */
  protected static $default_styles = false;

  /**
   * Array of html attributes. When replacing items in a template, these get automatically wrapped. E.g.
   * a template replacement for the class will be converted to class="value". The key is the parameter name,
   * and the value is the html attribute it will be wrapped into.
   */
  protected static $html_attributes = array(
    'class' => 'class',
    'outerClass' => 'class',
    'selected' => 'selected'
  );

  /**
   * @var array List of error messages that have been displayed, so we don't duplicate them when dumping any
   * remaining ones at the end.
   */
  protected static $displayed_errors=array();

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
   * <li>graticule</li>
   * <li>clearLayer</li>
   * <li>addrowtogrid</li>
   * <li>indiciaMapPanel</li>
   * <li>indiciaMapEdit</li>
   * <li>georeference_google_search_api</li>
   * <li>google_search</li>
   * <li>locationFinder</li>
   * <li>createPersonalSites</li>
   * <li>autocomplete</li>
   * <li>indicia_locks</li>
   * <li>jquery_cookie</li>
   * <li>jquery_ui</li>
   * <li>jquery_ui_fr</li>
   * <li>jquery_form</li>
   * <li>json</li>
   * <li>reportPicker</li>
   * <li>treeview</li>
   * <li>treeview_async</li>
   * <li>googlemaps</li>
   * <li>multimap</li>
   * <li>virtualearth</li>
   * <li>google_search</li>
   * <li>fancybox</li>
   * <li>flickr</li>
   * <li>treeBrowser</li>
   * <li>defaultStylesheet</li>
   * <li>validation</li>
   * <li>plupload</li>
   * <li>jqplot</li>
   * <li>jqplot_bar</li>
   * <li>jqplot_pie</li>
   * <li>jqplot_category_axis_renderer</li>
   * <li>jqplot_trendline</li>
   * <li>reportgrid</li>
   * <li>tabs</li>
   * <li>wizardprogress</li>
   * <li>spatialReports</li>
   * <li>jsonwidget</li>
   * <li>timeentry</li>
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
   * List of external resources including stylesheets and js files used by the data entry helper class.
   */
  public static function get_resources()
  {
    if (self::$resource_list===null) {
      $base = parent::$base_url;
      if (!self::$js_path) {
        self::$js_path = $base.'media/js/';
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
      global $indicia_theme, $indicia_theme_path;
      if (!isset($indicia_theme)) {
        // Use default theme if page does not specify it's own.
        $indicia_theme="default";
      }
      if (!isset($indicia_theme_path)) {
        // Use default theme path if page does not specify it's own.
        $indicia_theme_path=preg_replace('/css\/$/','themes/', self::$css_path);
      }
      // ensure a trailing path
      if (substr($indicia_theme_path, -1)!=='/')
        $indicia_theme_path .= '/';
      self::$resource_list = array (
        'jquery' => array('javascript' => array(self::$js_path."jquery.js",self::$js_path."ie_vml_sizzlepatch_2.js")),
        'openlayers' => array('javascript' => array(self::$js_path."OpenLayers.js", self::$js_path."proj4js.js", self::$js_path."proj4defs.js")),
        'graticule' => array('deps' =>array('openlayers'), 'javascript' => array(self::$js_path."indiciaGraticule.js")),
        'clearLayer' => array('deps' =>array('openlayers'), 'javascript' => array(self::$js_path."clearLayer.js")),
        'addrowtogrid' => array('javascript' => array(self::$js_path."addRowToGrid.js")),
        'indiciaMapPanel' => array('deps' =>array('jquery', 'openlayers', 'jquery_ui', 'jquery_cookie'), 'javascript' => array(self::$js_path."jquery.indiciaMapPanel.js")),
        'indiciaMapEdit' => array('deps' =>array('indiciaMap'), 'javascript' => array(self::$js_path."jquery.indiciaMap.edit.js")),
        // this resource is required for the georeferencer which dynamically constructs the resource name depending on the driver selected.
        'georeference_google_search_api' => array('javascript' => array("http://www.google.com/jsapi")),
        // For any other usage of google search, e.g. postcode textbox
        'google_search' => array('deps' =>array('georeference_google_search_api'), 'javascript' => array(self::$js_path."google_search.js")),
        'locationFinder' => array('deps' =>array('indiciaMapEdit'), 'javascript' => array(self::$js_path."jquery.indiciaMap.edit.locationFinder.js")),
        'createPersonalSites' => array('deps' => array('jquery'), 'javascript' => array(self::$js_path."createPersonalSites.js")),
        'autocomplete' => array('deps' => array('jquery'), 'stylesheets' => array(self::$css_path."jquery.autocomplete.css"), 'javascript' => array(self::$js_path."jquery.autocomplete.js")),
        'indicia_locks' => array('deps' =>array('jquery_cookie', 'json'), 'javascript' => array(self::$js_path."indicia.locks.js")),
        'jquery_cookie' => array('deps' =>array('jquery'), 'javascript' => array(self::$js_path."jquery.cookie.js")),
        'jquery_ui' => array('deps' => array('jquery'), 'stylesheets' => array("$indicia_theme_path$indicia_theme/jquery-ui.custom.css"), 'javascript' => array(self::$js_path."jquery-ui.custom.min.js", self::$js_path."jquery-ui.effects.js")),
        'jquery_ui_fr' => array('deps' => array('jquery_ui'), 'javascript' => array(self::$js_path."jquery.ui.datepicker-fr.js")),
        'jquery_form' => array('deps' => array('jquery'), 'javascript' => array(self::$js_path."jquery.form.js")),
        'json' => array('javascript' => array(self::$js_path."json2.js")),
        'reportPicker' => array('deps' => array('treeview'), 'javascript' => array(self::$js_path."reportPicker.js")),
        'treeview' => array('deps' => array('jquery'), 'stylesheets' => array(self::$css_path."jquery.treeview.css"), 'javascript' => array(self::$js_path."jquery.treeview.js")),
        'treeview_async' => array('deps' => array('treeview'), 'javascript' => array(self::$js_path."jquery.treeview.async.js", self::$js_path."jquery.treeview.edit.js")),
        'googlemaps' => array('javascript' => array("http://maps.google.com/maps/api/js?v=3.6&amp;sensor=false")),
        'virtualearth' => array('javascript' => array('http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1')),
        'fancybox' => array('deps' => array('jquery'), 'stylesheets' => array(self::$js_path.'fancybox/jquery.fancybox.css'), 'javascript' => array(self::$js_path.'fancybox/jquery.fancybox.pack.js')),
        'flickr' => array('deps' => array('fancybox'), 'javascript' => array(self::$js_path."jquery.flickr.js")),
        'treeBrowser' => array('deps' => array('jquery','jquery_ui'), 'javascript' => array(self::$js_path."jquery.treebrowser.js")),
        'defaultStylesheet' => array('deps' => array(''), 'stylesheets' => array(self::$css_path."default_site.css"), 'javascript' => array()),
        'validation' => array('deps' => array('jquery'), 'javascript' => array(self::$js_path.'jquery.metadata.js', self::$js_path.'jquery.validate.js', self::$js_path.'additional-methods.js')),
        'plupload' => array('deps' => array('jquery_ui','fancybox'), 'javascript' => array(
            self::$js_path.'jquery.uploader.js', self::$js_path.'/plupload/js/plupload.full.js')),
        'jqplot' => array('stylesheets' => array(self::$js_path.'jqplot/jquery.jqplot.css'), 'javascript' => array(                
                self::$js_path.'jqplot/jquery.jqplot.js',
                '[IE]'.self::$js_path.'jqplot/excanvas.js')),
        'jqplot_bar' => array('javascript' => array(self::$js_path.'jqplot/plugins/jqplot.barRenderer.min.js')),
        'jqplot_pie' => array('javascript' => array(self::$js_path.'jqplot/plugins/jqplot.pieRenderer.min.js')),
        'jqplot_category_axis_renderer' => array('javascript' => array(self::$js_path.'jqplot/plugins/jqplot.categoryAxisRenderer.min.js')),
        'jqplot_trendline' => array('javascript'=>array(self::$js_path.'jqplot/plugins/jqplot.trendline.min.js')),
        'reportgrid' => array('deps' => array('jquery_ui'), 'javascript' => array(self::$js_path.'jquery.reportgrid.js', self::$js_path.'json2.js')),
        'tabs' => array('deps' => array('jquery_ui'), 'javascript' => array(self::$js_path.'tabs.js')),
        'wizardprogress' => array('deps' => array('tabs'), 'stylesheets' => array(self::$css_path."wizard_progress.css")),
        'spatialReports' => array('javascript'=>array(self::$js_path.'spatialReports.js')),
        'jsonwidget' => array('javascript'=>array(self::$js_path."jsonwidget/json.js", self::$js_path."jsonwidget/jsonedit.js",
            self::$js_path."jquery.jsonwidget.js"), 'stylesheets'=>array(self::$css_path."jsonwidget.css")),
        'timeentry' => array('javascript'=>array(self::$js_path."jquery.timeentry.pack.js"))
      );
    }
    return self::$resource_list;
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
   * Returns a span containing any validation errors active on the form for the
   * control with the supplied ID.
   *
   * @param string $fieldname Fieldname of the control to retrieve errors for.
   * @param boolean $plaintext Set to true to return just the error text, otherwise it is wrapped in a span.
   */
  public static function check_errors($fieldname, $plaintext=false)
  {
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
        return self::apply_error_template($error, $fieldname);
      }
    } else {
      return '';
    }
  }

  /**
   * Sends a POST using the cUrl library.
   * @return array An array with a result element set to true or false for successful or failed posts respectively.
   * The output is returned in an output element in the array. If there is an error, then an errorno element gives the 
   * cUrl error number (as generated by the cUrl library used for the post).
   */
  public static function http_post($url, $postargs=null, $output_errors=true) {
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
        echo '<div class="error">cUrl POST request failed. Please check cUrl is installed on the server and the $base_url setting is correct.<br/>URL:'.$url.'<br/>';
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
   * Calculates the folder that submitted images end up in according to the helper_config.
   */
  public static function get_uploaded_image_folder() {
    if (!isset(self::$final_image_folder) || self::$final_image_folder=='warehouse') {
      if (!empty(self::$warehouse_proxy)) {
        $warehouseUrl = self::$warehouse_proxy;
      } else {
        $warehouseUrl = self::$base_url;
      }
      return $warehouseUrl.(isset(self::$indicia_upload_path) ? self::$indicia_upload_path : 'upload/');
    } else {
      return dirname(__FILE__).'/'.self::$final_image_folder;
    }
  }

  /**
   * Calculates the relative path to the client_helpers folder from wherever the current PHP script is.
   */
  public static function relative_client_helper_path() {
    // get the paths to the client helper folder and php file folder as an array of tokens
    $clientHelperFolder = explode(DIRECTORY_SEPARATOR, dirname(realpath(__FILE__)));
    $currentPhpFileFolder = explode(DIRECTORY_SEPARATOR, dirname(realpath($_SERVER['SCRIPT_FILENAME'])));
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
   * Parameters forms are a quick way of specifying a simple form used to specify the input
   * parameters for a process. Returns the HTML required for a parameters form, e.g. the form
   * defined for input of report parameters or the default values for a csv import.
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>form</b><br/>
   * Associative array defining the form structure. The structure is the same as described for <em>fixed_values_form</em> in a Warehouse model.
   * @link http://code.google.com/p/indicia/wiki/SampleModelPage.
   * </li>
   * <li><b>id</b><br/>
   * When used for report output, id of the report instance on the page if relevant, so that controls can be given unique ids.
   * </li>
   * <li><b>form</b><br/>
   * Associative array defining the form content.
   * </li>
   * <li><b>readAuth</b><br/>
   * Read authorisation array.
   * </li>
   * <li><b>fieldNamePrefix</b><br/>
   * Optional prefix for form field names.
   * </li>
   * <li><b>defaults</b><br/>
   * Associative array of default values for each form element.
   * </li>
   * <li><b>paramsToHide</b><br/>
   * An optional array of parameter names for parameters that should be added to the form output as hidden inputs rather than visible controls.
   * <li><b>paramsToExclude</b><br/>
   * An optional array of parameter names for parameters that should be skipped in the form output despite being in the form definition.
   * </li>
   * <li><b>presetParams</b><br/>
   * Optional array of param names and values that have a fixed value and are therefore output only as a hidden control.
   * </li>
   * <li><b>inlineMapTools</b><br/>
   * Defaults to false. If true, then map drawing parameter tools are embedded into the report parameters form. If false, then the map
   * drawing tools are added to a toolbar at the top of the map.
   * </li>
   * <li><b>helpText</b><br/>
   * Defaults to true. Set to false to disable helpText being displayed alongside controls, useful for building compact versions of 
   * simple parameter forms.
   * </li>
   * <li><b>nocache</b><br/>
   * Set to true to disable caching of lookups.
   * </li>
   * </ul>
   */
  public static function build_params_form($options) {
    require_once('data_entry_helper.php');
    // apply defaults
    $options = array_merge(array(
      'inlineMapTools' => false,
      'helpText' => true
    ), $options);
    $r = '';
    // Any ignored parameters will not be in the requested parameter form definition, but we do need hiddens
    if(isset($options['paramsToHide'])){
      foreach($options['paramsToHide'] as $key) {
        $default = isset($options['defaults'][$key]) ? $options['defaults'][$key] : '';
        $fieldPrefix=(isset($options['fieldNamePrefix']) ? $options['fieldNamePrefix'].'-' : '');
        $r .= "<input type=\"hidden\" name=\"$fieldPrefix$key\" value=\"$default\" class=\"test\"/>\n";
      }
    }
    foreach($options['form'] as $key=>$info) {
      $tools = array(); 
      // Skip parameters if we have been asked to ignore them
      if (!isset($options['paramsToExclude']) || !in_array($key, $options['paramsToExclude'])) 
        $r .= self::get_params_form_control($key, $info, $options, $tools);
      // If the form has defined any tools to add to the map, we need to create JavaScript to add them to the map.
      if (count($tools)) {
        $fieldname=(isset($options['fieldNamePrefix']) ? $options['fieldNamePrefix'].'-' : '') .$key;
        self::add_resource('spatialReports');
        self::add_resource('clearLayer');
        if (isset($info['allow_buffer']) && $info['allow_buffer']=='true')
          data_entry_helper::$javascript .= "enableBuffering();\n";
        if ($options['inlineMapTools']) {
          $r .= '<label>'.$info['display'].':</label>';
          $r .= '<div class="control-box">Use the following tools to define the query area.<br/>'.
          '<div id="map-toolbar" class="olControlEditingToolbar left"></div></div><br/>';
        }
        $r .= '<input type="hidden" name="'.$fieldname.'" id="hidden-wkt" value="'.
            (isset($_POST[$fieldname]) ? $_POST[$fieldname] : '').'"/>';
        if (isset($info['allow_buffer']) && $info['allow_buffer']=='true') {
          $bufferInput = data_entry_helper::text_input(array(
            'label'=>'Buffer (m)',
            'fieldname'=>'geom_buffer',
            'prefixTemplate'=>'blank', // revert to default
            'suffixTemplate'=>'blank', // revert to default
            'class'=>'control-width-1',
            'default'=>isset($_POST['geom_buffer']) ? $_POST['geom_buffer'] : 0
          ));
          if ($options['inlineMapTools']) 
            $r .= $bufferInput;
          else {
            $bufferInput = str_replace(array('<br/>',"\n"), '', $bufferInput);
            data_entry_helper::$javascript .= "$.fn.indiciaMapPanel.defaults.toolbarSuffix+='$bufferInput';\n";
          }
          // keep a copy of the unbuffered polygons in this input, so that when the page reloads both versions
          // are available
          $r .= '<input type="hidden" name="orig-wkt" id="orig-wkt" '.
              'value="'.(isset($_POST['orig-wkt']) ? $_POST['orig-wkt'] : '')."\" />\n";
        }
        // Output some JavaScript to setup a toolbar for the map drawing tools. Also JS
        // to handle getting the polygons from the edit layer into the report parameter
        // when run report is clicked.
        $toolbarDiv = $options['inlineMapTools'] ? 'map-toolbar' : 'top';
        data_entry_helper::$javascript .= "
  $.fn.indiciaMapPanel.defaults.toolbarDiv='$toolbarDiv';
  mapInitialisationHooks.push(function(div) {
    var styleMap = new OpenLayers.StyleMap(OpenLayers.Util.applyDefaults(
          {fillOpacity: 0.05},
          OpenLayers.Feature.Vector.style['default']));
    div.map.editLayer.styleMap = styleMap;\n";
        
        if (isset($info['allow_buffer']) && $info['allow_buffer']=='true')
          $origWkt = empty($_POST['orig-wkt']) ? '' : $_POST['orig-wkt'];
        else
          $origWkt = empty($_POST[$fieldname]) ? '' : $_POST[$fieldname];

        if (!empty($origWkt)) {
          data_entry_helper::$javascript .= "  var geom=OpenLayers.Geometry.fromWKT('$origWkt');\n";
          data_entry_helper::$javascript .= "  if (div.map.projection.getCode() !== div.indiciaProjection.getCode()) {\n";
          data_entry_helper::$javascript .= "    geom.transform(div.indiciaProjection, div.map.projection);\n";
          data_entry_helper::$javascript .= "  }\n";
          data_entry_helper::$javascript .= "  div.map.editLayer.addFeatures([new OpenLayers.Feature.Vector(geom)]);\n";
        }
        data_entry_helper::$javascript .= "
  });
  var add_map_tools = function(opts) {\n";
        foreach ($tools as $tool) {
          data_entry_helper::$javascript .= "  opts.standardControls.push('draw$tool');\n";
        }
        data_entry_helper::$javascript .= "  opts.standardControls.push('clearEditLayer');
  }
  mapSettingsHooks.push(add_map_tools);\n";
      }
    }
    return $r;
  }
  
  /** 
   * Internal method to safely find the value of a preset parameter. Returns empty string if not defined.
   */
  private static function get_preset_param($options, $name) {
    if (!isset($options['presetParams']))
      return '';
    else if (!isset($options['presetParams'][$name]))
      return '';
    else
      return $options['presetParams'][$name];
  }
  
  private static function get_params_form_control($key, $info, $options, &$tools) {
    $r = '';
    
    $fieldPrefix=(isset($options['fieldNamePrefix']) ? $options['fieldNamePrefix'].'-' : '');
    $ctrlOptions = array(
      'label' => $info['display'],
      'helpText' => $options['helpText'] ? $info['description'] : '', // note we can't fit help text in the toolbar versions of a params form
      'fieldname' => $fieldPrefix.$key,
      'nocache' => isset($options['nocache']) && $options['nocache']      
    );
    // If this parameter is in the URL or post data, put it in the control instead of the original default
    if (isset($options['defaults'][$key]))
      $ctrlOptions['default'] = $options['defaults'][$key];
    elseif (isset($info['default']))
      $ctrlOptions['default'] = $info['default'];
    if ($info['datatype']=='idlist') {
      // idlists are not for human input so use a hidden. 
      $r .= "<input type=\"hidden\" name=\"$fieldPrefix$key\" value=\"".self::get_preset_param($options, $key)."\" class=\"".$fieldPrefix."idlist-param\" />\n";
    } elseif (isset($options['presetParams']) && array_key_exists($key, $options['presetParams'])) {
      $r .= "<input type=\"hidden\" name=\"$fieldPrefix$key\" value=\"".self::get_preset_param($options, $key)."\" />\n";
    } elseif ($info['datatype']=='lookup' && isset($info['population_call'])) {
      // population call is colon separated, of the form direct|report:table|view|report:idField:captionField:params(key=value,key=value,...)
      $popOpts = explode(':', $info['population_call']);
      $extras = array();
      // if there are any extra parameters on the report lookup call, apply them
      if (count($popOpts) >= 5) {
        // because any extra params might contain colons, any colons from item 5 onwards are considered part of the extra params. So we 
        // have to take the remaining items and re-implode them, then split them by commas instead. E.g. population call could be set to
        // direct:term:id:term:term=a:b - in this case option 5 (term=a:b) is not to be split by colons.
        $extraItems = explode(',', implode(':', array_slice($popOpts, 4)));
        foreach ($extraItems as $extraItem) {
          $extraItem = explode('=', $extraItem);
          $extras[$extraItem[0]] = $extraItem[1];
        }
      }
      $ctrlOptions = array_merge($ctrlOptions, array(
        'valueField'=>$popOpts[2],
        'captionField'=>$popOpts[3],
        'blankText'=>'<'.lang::get('please select').'>',
        'extraParams'=>$options['readAuth'] + $extras
      ));
      if ($popOpts[0]=='direct')
        $ctrlOptions['table']=$popOpts[1];
      else
        $ctrlOptions['report']=$popOpts[1];
      if (isset($info['linked_to']) && isset($info['linked_filter_field'])) {
        if (isset($options['presetParams']) && array_key_exists($info['linked_to'], $options['presetParams'])) {
          // if the control this is linked to is hidden because it has a preset value, just use that value as a filter on the
          // population call for this control
          $ctrlOptions = array_merge($ctrlOptions, array(
            'extraParams' => array_merge($ctrlOptions['extraParams'], array($info['linked_filter_field']=>$options['presetParams'][$info['linked_to']]))
          ));
        } else {
          // otherwise link the 2 controls
          $ctrlOptions = array_merge($ctrlOptions, array(
            'parentControlId' => $fieldPrefix.$info['linked_to'],
            'filterField' => $info['linked_filter_field'],
            'parentControlLabel' => $options['form'][$info['linked_to']]['display']
          ));
        }
      }
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
    } elseif ($info['datatype']=='date') {
      $r .= data_entry_helper::date_picker($ctrlOptions);
    } elseif ($info['datatype']=='geometry') {
      $tools = array('Polygon','Line','Point');
    } elseif ($info['datatype']=='polygon') {
      $tools = array('Polygon');
    } elseif ($info['datatype']=='line') {
      $tools = array('Line');
    } elseif ($info['datatype']=='point') {
      $tools = array('Point');
    } else {
      if (method_exists('data_entry_helper', $info['datatype'])) {
        $ctrl = $info['datatype'];
        $r .= data_entry_helper::$ctrl($ctrlOptions);
      } else {
        $r .= data_entry_helper::text_input($ctrlOptions);
      }
    }
    return $r;
  }

  /**
   * Utility method that returns the parts required to build a link back to the current page.
   * @return array Associative array containing path and params (itself a key/value paired associative array).
   */
  public static function get_reload_link_parts() {
    $split = strpos($_SERVER['REQUEST_URI'], '?');
    // convert the query parameters into an array
    $gets = ($split!==false && strlen($_SERVER['REQUEST_URI']) > $split+1) ?
        explode('&', substr($_SERVER['REQUEST_URI'], $split+1)) :
        array();
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

  /**
   * Takes an associative array and converts it to a list of params for a query string. This is like
   * http_build_query but it does not url encode the & separator, and gives control over urlencoding the array values.
   */
  protected static function array_to_query_string($array, $encodeValues=false) {
    $params = array();
    if(is_array($array)) {
      arsort($array);
      foreach ($array as $a => $b)
      {
        if ($encodeValues) $b=urlencode($b);
        $params[] = "$a=$b";
      }
    }
    return implode('&', $params);
  }

    /**
   * Applies a output template to an array. This is used to build the output for each item in a list,
   * such as a species checklist grid or a radio group.
   *
   * @param array $item Array holding the item attributes.
   * @param string $template Name of the template to use, or actual template text if
   * $useTemplateAsIs is set to true.
   * @param boolean $useTemplateAsIs If true then the template parameter contains the actual
   * template text, otherwise it is the name of a template in the $indicia_templates array. Default false.
   * @param boolean $allowHtml If true then HTML is emitted as is from the parameter values inserted into the template,
   * otherwise they are escaped.
   * @param boolean $allowEscapeQuotes If true then parameter names can be suffixes -esape-quote, -escape-dblquote, 
   * -escape-htmlquote or -escape-htmldblquote to insert backslashes or html entities into the replacements for string escaping. 
   * @return string HTML for the item label
   */
  public static function mergeParamsIntoTemplate($params, $template, $useTemplateAsIs=false, $allowHtml=false, $allowEscapeQuotes=false) {
    global $indicia_templates;
    // Build an array of all the possible tags we could replace in the template.
    $replaceTags=array();
    $replaceValues=array();
    foreach ($params as $param=>$value) {
      if (!is_array($value) && !is_object($value)) {
        array_push($replaceTags, '{'.$param.'}');
        if ($allowEscapeQuotes) {
          array_push($replaceTags, '{'.$param.'-escape-quote}');
          array_push($replaceTags, '{'.$param.'-escape-dblquote}');
          array_push($replaceTags, '{'.$param.'-escape-htmlquote}');
          array_push($replaceTags, '{'.$param.'-escape-htmldblquote}');
        }
        // allow sep to have <br/>
        $value = ($param == 'sep' || $allowHtml) ? $value : htmlentities($value);
        // HTML attributes get automatically wrapped
        if (in_array($param, self::$html_attributes) && !empty($value))
          $value = " $param=\"$value\"";
        array_push($replaceValues, $value);
        if ($allowEscapeQuotes) {
          array_push($replaceValues, str_replace("'","\'",$value));
          array_push($replaceValues, str_replace('"','\"',$value));
          array_push($replaceValues, str_replace("'","&#39;",$value));
          array_push($replaceValues, str_replace('"','&quot;',$value));
        }
      }
    }
    if (!$useTemplateAsIs) $template = $indicia_templates[$template];
    return str_replace($replaceTags, $replaceValues, $template);
  }

  /**
   * Takes a file that has been uploaded to the client website upload folder, and moves it to the warehouse upload folder using the
   * data services.
   *
   * @param string $path Path to the file to upload, relative to the interim image path folder (normally the
   * client_helpers/upload folder.
   * @param boolean $persist_auth Allows the write nonce to be preserved after sending the file, useful when several files
   * are being uploaded.
   * @param array readAuth Read authorisation tokens, if not supplied then the $_POST array should contain them.
   * @param string $service Path to the service URL used. Default is data/handle_media, but could be import/upload_csv.
   * @return string Error message, or true if successful.
   */
  protected static function send_file_to_warehouse($path, $persist_auth=false, $readAuth = null, $service='data/handle_media') {
    if ($readAuth==null) $readAuth=$_POST;
    $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
    $interim_path = dirname(__FILE__).'/'.$interim_image_folder;
    if (!file_exists($interim_path.$path))
      return "The file $interim_path$path does not exist and cannot be uploaded to the Warehouse.";
    $serviceUrl = parent::$base_url."index.php/services/".$service;
    // This is used by the file box control which renames uploaded files using a guid system, so disable renaming on the server.
    $postargs = array('name_is_guid' => 'true');
    // attach authentication details
    if (array_key_exists('auth_token', $readAuth))
      $postargs['auth_token'] = $readAuth['auth_token'];
    if (array_key_exists('nonce', $readAuth))
      $postargs['nonce'] = $readAuth['nonce'];
    if ($persist_auth)
      $postargs['persist_auth'] = 'true';
    $file_to_upload = array('media_upload'=>'@'.realpath($interim_path.$path));
    $response = self::http_post($serviceUrl, $file_to_upload + $postargs);
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
    unlink(realpath($interim_path.$path));
    return $r;
  }

 /**
  * Internal function to find the path to the root of the site, including the trailing slash.
  */
  public static function getRootFolder() {
    $rootFolder = dirname($_SERVER['PHP_SELF']);
    if ($rootFolder =='\\') $rootFolder = '/'; // if no directory, then on windows may just return a single backslash.
    if (substr($rootFolder, -1)!='/') $rootFolder .= '/';
    return $rootFolder;
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
  * 'write_tokens' => the write authorisation array, if needed as separate tokens rather than just placing in form.
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
    return self::internal_dump_resources(self::$required_resources) . 
        self::get_scripts('$("body").addClass("js");', '', '', true);
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
    // Jquery validation js has to be added at this late stage, because only then do we know all the messages required.
    self::setup_jquery_validation_js();
    $dump = self::internal_dump_resources(self::$required_resources);
    $dump .= self::get_scripts(self::$javascript, self::$late_javascript, self::$onload_javascript, true);
    // ensure scripted JS does not output again if recalled.
    self::$javascript = "";
    self::$late_javascript = "";
    self::$onload_javascript = "";
    return $dump;
  }

  /**
   * Internal implementation of the dump_javascript method which takes the javascript and resources list
   * as flexible parameters, rather that using the globals.
   * @access private
   */
  protected static function internal_dump_resources($resources) {
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
              // if enabling fancybox, link it up
              if (strpos($j, 'fancybox.')!==false)
                self::$javascript .= "jQuery('a.fancybox').fancybox();\n";
              // look out for a condition that this script is IE only.
              if (substr($j, 0, 4)=='[IE]'){
              	$libraries .= "<!--[if IE]><script type=\"text/javascript\" src=\"".substr($j, 4)."\"></script><![endif]-->\n";
              } else
                $libraries .= "<script type=\"text/javascript\" src=\"$j\"></script>\n";
            }
          }
          // Record the resource as being dumped, so we don't do it again.
          array_push(self::$dumped_resources, $resource);
        }
      }
    }
    return $stylesheets.$libraries;
  }
  
  /**
   * A utility function for building the inline script content which should be inserted into a page from the javaascript,
   * late javascript and onload javascript. Can optionally include the script tags wrapper around the script generated.
   * @param string $javascript JavaScript to run when the page is ready, i.e. in $(document).ready.
   * @param string $late_javascript JavaScript to run at the end of $(document).ready.
   * @param string $onload_javascript JavaScript to run in the window.onLoad handler which comes later in the page load process.
   * @param bool $includeWrapper If true then includes script tags around the script.
   */
  public static function get_scripts($javascript, $late_javascript, $onload_javascript, $includeWrapper=false) {
    if (!empty($javascript) || !empty($late_javascript) || !empty($onload_javascript)) {
      $script = $includeWrapper ? "<script type='text/javascript'>/* <![CDATA[ */\n" : "";
      $script .= "if (typeof indiciaData==='undefined') {
  indiciaData = {onloadFns: []};
}
indiciaData.windowLoaded=false;
";
      if (!self::$is_ajax)
        $script .= "jQuery(document).ready(function() {\n";
      $script .= "$javascript\n$late_javascript\n";
      if (!self::$is_ajax)
        $script .= "});\n";
      if (!empty($onload_javascript)) {
        if (self::$is_ajax)
          // ajax requests are simple - page has already loaded so just return the javascript
          $script .= "$onload_javascript\n";
        else {
          // create a function that can be called from window.onLoad. Don't put it directly in the onload
          // in case another form is added to the same page which overwrites onload.
          $script .= "indiciaData.onloadFns.push(function() {\n$onload_javascript\n});\n";
          $script .= "window.onload = function() {\n".
              "  $.each(indiciaData.onloadFns, function(idx, fn) {\n".
              "    fn();\n".
              "  });\n".
              "  indiciaData.windowLoaded=true\n".              
              "}\n";
          }              
      }
      $script .= $includeWrapper ? "/* ]]> */</script>\n" : "";
    } else {
      $script='';
    } 
    return $script; 
  }

  /**
   * If required, setup jQuery validation. This JavaScript must be added at the end of form preparation otherwise we would
   * not know all the control messages. It will normally be called by dump_javascript automatically, but is exposed here
   * as a public method since the iform Drupal module does not call dump_javascript, but is responsible for adding JavaScript
   * to the page via drupal_add_js.
   */
  public static function setup_jquery_validation_js() {
    // In the following block, we set the validation plugin's error class to our template.
    // We also define the error label to be wrapped in a <p> if it is on a newline.
    if (self::$validated_form_id) {
      global $indicia_templates;
      self::$javascript .= "$('#".self::$validated_form_id."').validate({
        errorClass: \"".$indicia_templates['error_class']."\",
        ". (in_array('inline', self::$validation_mode) ? "" : "errorElement: 'p',") ."
        highlight: function(element, errorClass) {
          var jqElement = $(element);
          if (jqElement.is(':radio') || jqElement.is(':checkbox')) {
            //if the element is a radio or checkbox group then highlight the group
            var jqBox = jqElement.parents('.control-box');
            if (jqBox.length != 0) {
              jqBox.eq(0).addClass('ui-state-error');
            } else {
              jqElement.addClass('ui-state-error');
            }
          } else {
            jqElement.addClass('ui-state-error');
          }
        },
        unhighlight: function(element, errorClass) {
          var jqElement = $(element);
          if (jqElement.is(':radio') || jqElement.is(':checkbox')) {
            //if the element is a radio or checkbox group then highlight the group
            var jqBox = jqElement.parents('.control-box');
            if (jqBox.length != 0) {
              jqBox.eq(0).removeClass('ui-state-error');
            } else {
              jqElement.removeClass('ui-state-error');
            }
          } else {
            jqElement.removeClass('ui-state-error');
          }
        },
        invalidHandler: ".$indicia_templates['invalid_handler_javascript'].",
        messages: ".json_encode(self::$validation_messages) .
        // Do not place errors if 'message' not in validation_mode
        (in_array('message', self::$validation_mode) ? "" : ",
        errorPlacement: function(error, element) {}") ."
      });\n";
    }
  }

  /**
   * Internal method to build a control from its options array and its template. Outputs the
   * prefix template, a label (if in the options), a control, the control's errors and a
   * suffix template.
   *
   * @param string $template Name of the control template, from the global $indicia_templates variable.
   * @param array $options Options array containing the control replacement values for the templates.
   * Options can contain a setting for prefixTemplate or suffixTemplate to override the standard templates.
   */
  public static function apply_template($template, $options) {
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
        $error = self::check_errors($options['fieldname'], true);
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
    if (!array_key_exists('readonly', $options)) {
      $options['readonly']='';
    }
    if (array_key_exists('maxlength', $options)) {
      $options['maxlength']='maxlength="'.$options['maxlength'].'"';
    } else {
      $options['maxlength']='';
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
      $validationClasses = self::build_validation_class($options);
      $options['class'] .= ' '.$validationClasses;
    }

    // replace html attributes with their wrapped versions, e.g. a class becomes class="..."
    foreach (self::$html_attributes as $name => $attr) {
      if (!empty($options[$name])) {
        $options[$name]=' '.$attr.'="'.$options[$name].'"';
      }
    }

    // If options contain a help text, output it at the end if that is the preferred position
    $r = self::get_help_text($options, 'before');
    //Add prefix
    $r .= self::apply_static_template('prefix', $options);

    // Add a label only if specified in the options array. Link the label to the inputId if available,
    // otherwise the fieldname (as the fieldname control could be a hidden control).
    if (array_key_exists('label', $options) && !empty($options['label'])) {
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
    $r .= self::apply_replacements_to_template($indicia_templates[$template], $options);

    // Add a lock icon to the control if the lockable option is set to true
    if (array_key_exists('lockable', $options) && $options['lockable']===true) {
      $r .= self::apply_replacements_to_template($indicia_templates['lock_icon'], $options);
      if (!self::$using_locking) {
        self::$using_locking = true;
        $options['lock_form_mode'] = self::$form_mode ? self::$form_mode : 'NEW';
        // write lock javascript at the start of the late javascript so after control setup but before any other late javascript
        self::$late_javascript = self::apply_replacements_to_template($indicia_templates['lock_javascript'], $options).self::$late_javascript;
        self::add_resource('indicia_locks');
      }
    }

    // Add an error icon to the control if there is an error and this option is set
    if ($error && in_array('icon', $options['validation_mode'])) {
      $r .= $indicia_templates['validation_icon'];
    }
    // Add a message to the control if there is an error and this option is set
    if ($error && in_array('message', $options['validation_mode'])) {
      $r .=  self::apply_error_template($error, $options['fieldname']);
    }
    if (isset($options['afterControl']))
      $r .= $options['afterControl'];

    // Add suffix
    if (isset($validationClasses) && !empty($validationClasses) && strpos($validationClasses, 'required')!==false) {
      $r .= self::apply_static_template('requiredsuffix', $options);
    } else {
      $r .= self::apply_static_template('suffix', $options);
    }

    // If options contain a help text, output it at the end if that is the preferred position
    $r .= self::get_help_text($options, 'after');

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
   * Explodes a value on several lines into an array split on the lines. Tolerates any line ending.
   * @param string $value A multi-line string to be split.
   * @return array An array with one entry per line in $value.
   */
  public static function explode_lines($value) {
    $structure = str_replace("\r\n", "\n", $value);
    $structure = str_replace("\r", "\n", $structure);
    return explode("\n", trim($structure));
  }
  
  /**
   * Explodes a value with key=value several lines into an array split on the lines. Tolerates any line ending.
   * @param string $value A multi-line string to be split.
   * @return array An associative array with one entry per line in $value. Array keys are the items before the = on each line,
   * and values are the data after the = on each line.
   */
  public static function explode_lines_key_value_pairs($value) {
    preg_match_all("/([^=\r\n]+)=([^\r\n]+)/", $value, $pairs);
    $trim = create_function('&$val', '$val = trim($val);');
    array_walk($pairs[1], $trim);
    array_walk($pairs[2], $trim);
    return array_combine($pairs[1], $pairs[2]); 
  }
  
  /**
   * Utility function to load a list of terms from a termlist. 
   * @param array $auth Read authorisation array.
   * @param mixed $termlist Either the id or external_key of the termlist to load. 
   * @param array $filter List of the terms that are required, or null for all terms.
   * @return array Output of the Warehouse data services request for the terms.
   */
  public static function get_termlist_terms($auth, $termlist, $filter=null) {
    if (!is_int($termlist)) {
      $termlistFilter=array('external_key' => $termlist);
      $list = data_entry_helper::get_population_data(array(
        'table' => 'termlist',
        'extraParams' => $auth['read'] + $termlistFilter
      ));
      if (count($list)==0)
        throw new Exception("Termlist $termlist not available on the Warehouse");
      if (count($list)>1)
        throw new Exception("Multiple termlists identified by $termlist found on the Warehouse");
      $termlist = $list[0]['id'];
    }
    $extraParams = $auth['read'] + array(
      'view' => 'detail',
      'termlist_id' => $termlist
    );
    // apply a filter for the actual list of terms, if required.
    if ($filter)
      $extraParams['query'] = urlencode(json_encode(array('in'=>array('term', $filter))));
    $terms = data_entry_helper::get_population_data(array(
      'table' => 'termlists_term',
      'extraParams' => $extraParams
    ));
    return $terms;
  }

 /**
   * Converts the validation rules in an options array into a string that can be used as the control class,
   * to trigger the jQuery validation plugin.
   * @param $options. Control options array. For validation to be applied should contain a validation entry,
   * containing a single validation string or an array of strings.
   * @return string The validation rules formatted as a class.
   */
  protected static function build_validation_class($options) {
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
    return self::convert_to_jquery_val_metadata($rules, $options);
  }

  /**
   * Returns templated help text for a control, but only if the position matches the $helpTextPos value, and
   * the $options array contains a helpText entry.
   * @param array $options Control's options array. Can specify the class for the help text item using option helpTextClass.
   * @param string $pos Either before or after. Defines the position that is being requested.
   * @return string Templated help text, or nothing.
   */
  protected static function get_help_text($options, $pos) {
    $options = array_merge(array('helpTextClass'=>'helpText'), $options);
    if (array_key_exists('helpText', $options) && !empty($options['helpText']) && self::$helpTextPos == $pos) {
      return str_replace('{helpText}', $options['helpText'], self::apply_static_template('helpText', $options));
    } else
      return '';
  }

  /**
   * Takes a template string (e.g. <div id="{id}">) and replaces the tokens with the equivalent values looked up from the $options array.
   */
  protected static function apply_replacements_to_template($template, $options) {
    // Build an array of all the possible tags we could replace in the template.
    $replaceTags=array();
    $replaceValues=array();
    foreach (array_keys($options) as $option) {
      if (!is_array($options[$option]) && !is_object($options[$option])) {
        array_push($replaceTags, '{'.$option.'}');
        array_push($replaceValues, $options[$option]);
      }
    }
    return str_replace($replaceTags, $replaceValues, $template);
  }

   /**
  * Takes a list of validation rules in Kohana/Indicia format, and converts them to the jQuery validation
  * plugin metadata format.
  * @param array $rules List of validation rules to be converted.
  * @param array $options Options passed to the validated control.
  * @return string Validation metadata classes to add to the input element.
  * @todo Implement a more complete list of validation rules.
  */
  protected static function convert_to_jquery_val_metadata($rules, $options) {
    $converted = array();
    foreach ($rules as $rule) {
      // Detect the rules that can simply be passed through
      $rule = trim($rule);
      if    ($rule=='required'
          || $rule=='dateISO'
          || $rule=='email'
          || $rule=='url'
          || $rule=='time'
          || $rule=='integer') {
        $converted[] = $rule.':true';
      // Now any rules which need parsing or conversion
      } else if ($rule=='date' && !isset($options['allowVagueDates']) || $options['allowVagueDates']===false) {
        $converted[] = 'customDate:true';
      } else if ($rule=='digit') {
        $converted[] = 'digits:true';
      } else if ($rule=='numeric') {
        $converted[] = 'number:true';
      // the next test uses a regexp named expression to find the digit in a maximum rule (maximum[10])
      } elseif (preg_match('/maximum\[(?P<val>\d+)\]/', $rule, $matches)) {
        $converted[] = 'max:'.$matches['val'];
      // and again for minimum rules
      } elseif (preg_match('/minimum\[(?P<val>\d+)\]/', $rule, $matches)) {
        $converted[] = 'min:'.$matches['val'];
      } elseif (preg_match('/regex\[(?P<val>.+)\]/', $rule, $matches)) {
        $converted[] = 'pattern:'. $matches['val'];
      }
    }
    if (count($converted) == 0) {
      return '';
    } else {
      return '{'. implode(', ', $converted) .'}';
    }
  }

 /**
  * Returns a static template which is either a default template or one
  * specified in the options
  * @param string $name The static template type. e.g. prefix or suffix.
  * @param array $options Array of options which may contain a template name.
  * @return string Template value.
  */
  protected static function apply_static_template($name, $options) {
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
    return self::apply_replacements_to_template($r, $options);
  }

 /**
  * Returns a string where characters have been escaped for use in jQuery selectors
  * @param string $name The string to be escaped.
  * @return string escaped name.
  */
  protected static function jq_esc($name) {
    // not complete, only escapes :[], add other characters as needed.
    $from = array(':','[',']');
    $to = array('\\\\:','\\\\[','\\\\]');
    return $name ? str_replace($from, $to, $name) : $name;
  }

  /**
   * Method to format a control error message inside a templated span.
   */
  private static function apply_error_template($error, $fieldname) {
    if (empty($error))
      return '';
    global $indicia_templates;
    if (empty($error)) return '';
    $template = str_replace('{class}', $indicia_templates['error_class'], $indicia_templates['validation_message']);
    $template = str_replace('{for}', $fieldname, $template);
    return str_replace('{error}', lang::get($error), $template);
  }

}

?>