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
  'validation_message' => '<br/><div class="ui-state-error ui-corner-all inline-error">'.
      '<span class="ui-icon ui-icon-alert" style="float: left; margin-left: 3px;"></span>{error}</div>'."\n",
  'validation_icon' => '<span class="ui-state-error ui-corner-all validation-icon">'.
      '<span class="ui-icon ui-icon-alert"></span></span>',
  'image_upload' => '<input type="file" id="{id}" name="{fieldname}" accept="png|jpg|gif" {title}/>'."\n",
  'textarea' => '<textarea id="{id}" name="{fieldname}"{class} cols="{cols}" rows="{rows}" {title}>{default}</textarea>'."\n",
  'text_input' => '<input type="text" id="{id}" name="{fieldname}"{class} value="{default}" {title} />'."\n",
  'date_picker' => '<input type="text" size="30"{class} id="{id}" name="{fieldname}" value="{default}" {title}/>',
  'select' => '<select id="{id}" name="{fieldname}"{class} {title}>{options}</select>',
  'select_option' => '<option value="{value}" {selected} >{caption}</option>',
  'select_option_selected' => 'selected="selected"',
  'listbox' => '<select id="{id}" name="{fieldname}"{class} size="{size}" multiple="{multiple}" {title}>{options}</select>',
  'listbox_option' => '<option value="{value}" {selected} >{caption}</option>',
  'listbox_option_selected' => 'selected="selected"',
  'list_in_template' => '<ul{class} {title}>{items}</ul>',
  'map_panel' => "<div id=\"{divId}\" style=\"width: {width}px; height: {height}px;\"{class}></div>\n<br/>\n",
  'georeference_lookup' => "<input id=\"imp-georef-search\"{class} />\n".
      "<input type=\"button\" id=\"imp-georef-search-btn\" class=\"ui-corner-all ui-widget-content ui-state-default indicia-button\" value=\"".lang::get('search')."\" />\n".
      "<div id=\"imp-georef-div\" class=\"ui-corner-all ui-widget-content ui-helper-hidden\"><div id=\"imp-georef-output-div\">\n".
      "</div><a class=\"ui-corner-all ui-widget-content ui-state-default indicia-button\" href=\"#\" id=\"imp-georef-close-btn\">".lang::get('close')."</a>\n".
      "</div>",
  'tab_header' => '<script type="text/javascript">/* <![CDATA[ */'."\n".
      'document.write(\'<ul class="ui-helper-hidden">{tabs}</ul>\');'.
      "\n/* ]]> */</script>\n".
      "<noscript><ul>{tabs}</ul></noscript>\n",
  'loading_block_start' => "<script type=\"text/javascript\">\n/* <![CDATA[ */\n".
      'document.write(\'<div class="ui-widget ui-widget-content ui-corner-all loading-panel" >'.
      '<img src="'.helper_config::$base_url.'media/images/ajax-loader2.gif" />'.
      lang::get('loading')."...</div>');\n".
      'document.write(\'<div class="loading-hide">\');'.
      "\n/* ]]> */</script>\n",
  'loading_block_end' => "<script type=\"text/javascript\">\n/* <![CDATA[ */\n".
      "document.write('</div>');\n".
      "/* ]]> */</script>",
	'taxon_label' => '<div class="biota"><span class="nobreak sci binomial"><em>{taxon}</em></span> {authority}'.
    	'<span class="nobreak vernacular">{common}</span></div>',
  'autocomplete' => '<input type="hidden" class="hidden" id="{id}" name="{fieldname}" value="{default}" />'."\n".
      '<input id="{inputId}" name="{inputId}" value="{defaultCaption}" {title}/>'."\n",
  'autocomplete_javascript' => "jQuery('input#{escaped_input_id}').autocomplete('{url}/{table}',
      {
        minChars : 1,
        mustMatch : true,
        extraParams :
        {
          orderby : '{captionField}',
          mode : 'json',
          qfield : '{captionField}',
          {sParams}
        },
        dataType: 'jsonp',
        parse: function(data)
        {
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
      },
      formatResult: function(item) {
        return item.{valueField};
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
  'sref_textbox' => '<input type="text" id="{id}" name="{fieldname}" {class} value="{default}" />' .
        '<input type="hidden" id="imp-geom" name="{table}:geom" value="{defaultGeom}" />'
);


/**
 * Static helper class that provides automatic HTML and JavaScript generation for Indicia online
 * recording website data entry controls. Examples include auto-complete text boxes that are populated
 * by Indicia species lists, maps for spatial reference selection and date pickers.
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
   * message, hint, icon, colour.
   */
  public static $validation_mode=array('message');
  
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
   * @var array List of resources that have already been dumped out, so we don't duplicate them.
   */
  private static $dumped_resources=array();
  
  
  
  
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
   * captionField is supplied, and if not uses a valueField if available.
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
    // If captionField is supplied but not captionField, use the captionField as the valueField
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
    global $indicia_templates, $indicia_errors;
    // Don't need the extraParams - they are just for service communication.
    $options['extraParams']=null;
    // Set default validation error output mode
    if (!array_key_exists('validation_mode', $options)) {
      $options['validation_mode']=self::$validation_mode;
    }
    // Decide if the main control has an error. If so, highlight with the error class and set it's title.
    if (isset($indicia_errors)) {      
      if (array_key_exists('fieldname', $options) && array_key_exists($options['fieldname'], $indicia_errors)) {
        $error = $indicia_errors[$options['fieldname']];
      }
    }
    // Add a hint to the control if there is an error and this option is set
    if (isset($error) && in_array('hint', $options['validation_mode'])) {        
      $options['title'] = 'title="'.$error.'"';
    } else {
      $options['title'] = '';
    }
    // Add an error class to colour the control if there is an error and this option is set
    if (isset($error) && in_array('colour', $options['validation_mode'])) {
      $options['class'] .= ' ui-state-error';
    }
    if (!empty($options['class'])) {
      $options['class']=' class="'.$options['class'].'"';
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
    $r = '';
    if (array_key_exists('prefixTemplate', $options)) {
      $r .= $indicia_templates[$options['prefixTemplate']];
    } else {
      $r .= $indicia_templates['prefix'];
    }
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
    if (isset($error) && in_array('icon', $options['validation_mode'])) {
      $r .= $indicia_templates['validation_icon'];
    }    
    // Add a message to the control if there is an error and this option is set
    if (isset($error) && in_array('message', $options['validation_mode'])) {
      $r .= self::check_errors($options['fieldname']);
    }    
    if (array_key_exists('suffixTemplate', $options)) {
      $r .= $indicia_templates[$options['suffixTemplate']];
    } else {
      $r .= $indicia_templates['suffix'];
    }
    return $r;
  }

  /**
  * Helper function to support image upload by inserting a file path upload control.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to, e.g. occurrence:image.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
  * </ul>
  * 
  * @return string HTML to insert into the page for the file upload control.
  */
  public static function image_upload() {
    $options = self::check_arguments(func_get_args(), array('fieldname'));
    return self::apply_template('image_upload', $options);
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
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
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
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
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
   * Required. The ID of the taxon_lists record which is to be used to obtain the species or taxon list.</li>
   * <li><b>occAttrs</b><br/>
   * Integer array, where each entry corresponds to the id of the desired attribute in the
   * occurrence_attributes table.</li>
   * <li><b>occAttrClasses</b><br/>
   * String array, where each entry corresponds to the css class(es) to apply to the corresponding
   * attribute control (i.e. there is a one to one match with occAttrs). If this array is shorter than
   * occAttrs then all remaining controls re-use the last class.</li>
   * <li><b>extraParams</b><br/>
   * Optional. Associative array of items to pass via the query string to the service. This
   * should at least contain the read authorisation array.</li>
   * <li><b>lookupListId</b><br/>
   * Optional. The ID of the taxon_lists record which is to be used to select taxa from when adding
   * rows to the grid. If specified, then an autocomplete text box and
   * Add Row button are generated automatically allowing the user to pick a species to add as an
   * extra row.</li>
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
   * </ul>
   */
  public static function species_checklist()
  {  
    $options = self::check_arguments(func_get_args(), array('listId', 'occAttrs', 'readAuth', 'extraParams', 'lookupListId'));
    // Apply default values
    $options = array_merge(array(
        'header'=>'true',
        'columns'=>1,
        'checkboxCol'=>'true'
    ), $options);
    self::add_resource('json');
    self::add_resource('autocomplete');
    $occAttrControls = array();
    $occAttrs = array();
    if (array_key_exists('listId', $options)) {
      $options['extraParams']['taxon_list_id']=$options['listId'];
    }
    if (!array_key_exists('preferred', $options['extraParams'])) {
      // default to only preferred taxon names
      $options['extraParams']['preferred']='t';
    }
    if (!array_key_exists('orderby', $options['extraParams'])) {
      // default to only preferred taxon names
      $options['extraParams']['orderby']='taxon';
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
    $taxalist = self::get_population_data($options);
    $url = parent::$base_url."index.php/services/data";
    
    // Get the list of occurrence attributes
    if (array_key_exists('occAttrs', $options)) {
      $idx=0;
      $class='';
      foreach ($options['occAttrs'] as $occAttr)
      {
        $a = self::get_population_data(array(
            'table'=>'occurrence_attribute',
            'extraParams'=>$options['readAuth'] + array('id'=>$occAttr)
        ));        
        if (count($a)>0 && !array_key_exists('error', $a))
        { 
          $b = $a[0];
          $occAttrs[$occAttr] = $b['caption'];
          // Get the control class if available. If the class array is too short, the last entry gets reused for all remaining.
          $class = (array_key_exists('occAttrClasses', $options) && $idx<count($options['occAttrClasses'])) ? $options['occAttrClasses'][$idx] : $class;          
          // Build the correct control
          switch ($b['data_type'])
          {
            case 'L':
              $tlId = $b['termlist_id'];
              $occAttrControls[$occAttr] = data_entry_helper::select(array(
                  'fieldname' => 'oa:'.$occAttr, 
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
              $occAttrControls[$occAttr] = "<input type='text' class='date $class' id='oa:$occAttr' name='oa:$occAttr' " .
                  "value='".lang::get('click here')."'/>";
              break;  
            default:              
              $occAttrControls[$occAttr] =
                  "<input type='text' id='oa:$occAttr' name='oa:$occAttr' class='$class'/>";
              break;
          }
        }
        $idx++;
      }
    }    
    // Build the grid
    if (! array_key_exists('error', $taxalist))
    {
      $grid = "<table style='display: none'><tbody><tr id='scClonableRow'><td class='scTaxonCell'></td>";
      if ($options['checkboxCol']=='true') {
        $grid .= "<td class='scPresenceCell'><input type='checkbox' name='' value='' checked='true' /></td>";
      }
      foreach ($occAttrControls as $oc) {
        $grid .= "<td class='scOccAttrCell'>$oc</td>";
      }
      $grid .= "</tr></tbody></table>";
      $grid .= '<table class="ui-widget ui-widget-content '.$options['class'].'">';
      if ($options['header']) {
        $grid .= "<thead class=\"ui-widget-header\"><tr>";
        for ($i=0; $i<$options['columns']; $i++) {
          $grid .= "<th>".lang::get('species_checklist.species')."</th>";
          if ($options['checkboxCol']=='true') {
            $grid .= "<th>".lang::get('species_checklist.present')."</th>";
          }
          foreach ($occAttrs as $a) {
            $grid .= "<th>$a</th>";
          }
        }
        $grid .= '</tr></thead>';
      }
      $rows = array();
      $rowIdx = 0;
      foreach ($taxalist as $taxon) {
        $id = $taxon['id'];
        $row = "<td class='scTaxonCell ui-state-default'>".self::getTaxonLabel($taxon)."</td>";
        if ($options['checkboxCol']=='true') {
          if (self::$entity_to_load!=null && array_key_exists("sc:$id:present", self::$entity_to_load)) {
            $checked = ' checked="checked"';
          } else {
            $checked='';
          }
          $row .= "<td class='scPresenceCell'><input type='checkbox' name='sc:$id:present' $checked /></td>";
        }
        foreach ($occAttrControls as $oc) {
          $oc = preg_replace('/oa:(\d+)/', "sc:$id:occAttr:$1", $oc);
          $row .= "<td class='scOccAttrCell ui-widget-content'>".$oc."</td>";
        }
        if ($rowIdx < count($taxalist)/$options['columns']) {
          $rows[$rowIdx]=$row;
        } else {
          $rows[$rowIdx % (ceil(count($taxalist)/$options['columns']))] .= $row;
        }
        $rowIdx++;
      }
      $grid .= "<tbody>\n<tr>".implode("</tr>\n<tr>", $rows)."</tr>\n";
      $grid .= '</tbody></table>';

      // Insert an autocomplete box if the termlist has a parent or an alternate
      // termlist has been given in the parameter.
      if (!isset($options['lookupListId'])) {
        $tlRequest = "$url/taxon_list/".$options['listId']."?mode=json&view=detail";
        $session = curl_init($tlRequest);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        $arr = explode("\r\n\r\n",curl_exec($session));        
        $tl = json_decode(array_pop($arr), true);
        if (! array_key_exists('error', $tl)) {
          $options['lookupListId'] = $tl[0]['parent_id'];
        }
      }
      if (isset($options['lookupListId'])) {
        // Javascript to add further rows to the grid
        self::add_resource('addrowtogrid');
        self::$javascript .= "var addRowFn = addRowToGrid('$url', {'auth_token' : '".
            $options['readAuth']['auth_token']."', 'nonce' : '".$options['readAuth']['nonce']."'});
        jQuery('#addRowButton').click(addRowFn);\r\n";

        // Drop an autocomplete box against the parent termlist
        $grid .= '<label for="addSpeciesBox">'.lang::get('enter additional species').':</label>';
        $grid .= data_entry_helper::autocomplete('addSpeciesBox',
            'taxa_taxon_list', 'taxon', 'id', $options['readAuth'] +
            array('taxon_list_id' => $options['lookupListId']));
        $grid .= "<button type='button' id='addRowButton'>".lang::get('add row')."</button>";
      }      
      return $grid;
    } else {
      return $taxalist['error'];
    }
    
  }
  
  /**
   * Applies a template to build the output label for each taxon in a taxon grid.
   * 
   * @access private
   * @param $taxon Array holding the taxon attributes.
   * @return string HTML for the taxon label
   */
  private static function getTaxonLabel($taxon) {
    global $indicia_templates;
    // Build an array of all the possible tags we could replace in the template.
    $replaceTags=array();
    $replaceValues=array();
    foreach (array_keys($taxon) as $option) {
      if (!is_array($taxon[$option])) {
        array_push($replaceTags, '{'.$option.'}');
        array_push($replaceValues, $taxon[$option]);
      }
    }    
    return str_replace($replaceTags, $replaceValues, $indicia_templates['taxon_label']);    
  }
  

  /**
  * Helper function to generate a treeview from a given list
  *
  * @param string $id id attribute for the returned hidden input control.
  * NB the tree itself will have an id of "tr$id"
  * @param string $entity Name (Kohana-style) of the database entity to be queried.
  * @param string $captionField Field to draw values to show in the control from.
  * @param string $valueField Field to draw values to return from the control from. Defaults
  * to the value of $captionField.
  * @param string $topField Field used in filter to define top level entries
  * @param string $topValue Value of $topField used in filter to define top level entries
  * @param string $parentField Field used to indicate parent within tree for a record.
  * to the value of $captionField.
  * @param string $defaultValue initial value to set the control to (not currently used).
  * @param string[] extraParams Array of key=>value pairs which will be passed to the service
  * as GET parameters. Needs to specify the read authorisation key/value pair, needed for making
  * queries to the data services. Can also be used to specify the "view" type e.g. "detail"
  * @param string extraClass : main class to be added to UL tag - currently can be treeview, treeview-red,
  * treeview_black, treeview-gray. The filetree class although present, does not work properly.
  *
  * TO DO
  * Need to do initial value.
  * Need to look at how the filetree can be implemented.
  */
  public static function treeview($id, $entity,
    $captionField, $valueField, $topField, $topValue, $parentField,
    $defaultValue, $extraParams,
    $extraClass = 'treeview')
    {
      self::add_resource('treeview');
      // Declare the data service
      $url = parent::$base_url."/index.php/services/data";
      // If valueField is null, set it to $captionField
      if ($valueField == null) $valueField = $captionField;
      $defaultValue = $default = self::check_default_value($id, $defaultValue);
      // Do stuff with extraParams
      $sParams = '';
      foreach ($extraParams as $a => $b){
        $sParams .= "$a : '$b',";
      }
      // lop the comma off the end
      $sParams = substr($sParams, 0, -1);

      self::$javascript .= "jQuery('#tr$id').treeview(
      {
        url: '$url/$entity',
        extraParams : {
          orderby : '$captionField',
          mode : 'json',
          $sParams
        },
        valueControl: '$id',
        nameField: '$captionField',
        valueField: '$valueField',
        topField: '$topField',
        topValue: '$topValue',
        parentField: '$parentField',
        dataType: 'jsonp',
        parse: function(data) {
        var results =
        {
          'data' : data,
          'caption' : data.$captionField,
          'value' : data.$valueField
        };
        return results;
      }
    });\n";

    $tree = '<input type="hidden" class="hidden" id="'.$id.'" name="'.$id.'" /><ul id="tr'.$id.'" class="'.$extraClass.'"></ul>';
    $r .= self::check_errors($id);
    return $tree;
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
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
  * </ul>
  * 
  * @return string HTML to insert into the page for the date picker control.
  */
  public static function date_picker() {
    $options = self::check_arguments(func_get_args(), array('fieldname', 'default'));

    self::add_resource('jquery_ui');
    $escaped_id=str_replace(':','\\\\:',$options['id']);
    self::$javascript .= "jQuery('#$escaped_id').datepicker({dateFormat : 'yy-mm-dd', constrainInput: false});\n";

    if (!array_key_exists('default', $options) || $options['default']=='') {
      $options['default']=lang::get('click here');
    }
    // Enforce a class on the control called date
    if (!array_key_exists('class', $options)) {
      $options['class']='';
    }
    $options['class'] .= ' date';
    return self::apply_template('date_picker', $options);
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
  * Required. Table name to get data from for the select options.</li>
  * <li><b>captionField</b><br/>
  * Required. Field to draw values to show in the control from.</li>
  * <li><b>valueField</b><br/>
  * Optional. Field to draw values to return from the control from. Defaults
  * to the value of captionField.</li>
  * <li><b>extraParams</b><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
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
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
  * <li><b>blankText</b><br/>
  * Optional. If specified then the first option in the drop down is the blank text, used when there is no value.</li>
  * </ul>
  *
  * @return string HTML code for a select control.
  */
  public static function select()
  {
    $options = self::check_arguments(func_get_args(), array(
        'fieldname', 'table', 'captionField', 'valueField', 'extraParams', 'default'
    ));
    return self::select_or_listbox($options, 'select', 'select_option', 'select_option_selected');
  }

 /**
  * Helper function to generate a list box from a Indicia core service query. The list box can
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
  * Required. Table name to get data from for the select options.</li>
  * <li><b>captionField</b><br/>
  * Required. Field to draw values to show in the control from.</li>
  * <li><b>valueField</b><br/>
  * Optional. Field to draw values to return from the control from. Defaults
  * to the value of captionField.</li>
  * <li><b>extraParams</b><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
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
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
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
    return self::select_or_listbox($options, 'listbox', 'listbox_option', 'listbox_option_selected');
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
    global $indicia_cachetimeout;
    $ret_value = 3600; /* this is the default timeout period if none specified anywhere
                          This should be red flagged to all users, so they know the consequences:
                          by default changes to species lists etc will take up to 1 hour to be visible
                          on the website */

    if (isset($indicia_cachetimeout)) {
      if (is_numeric($indicia_cachetimeout) && $indicia_cachetimeout > 0) {
        $ret_value = $indicia_cachetimeout;
      } else {
        $ret_value = false;
      }
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
    global $indicia_website_id;
    
    /* If timeout is not set, we're not caching */
    if (!$timeout)
      return false;
    if(!is_dir($path) || !is_writeable($path))
      return false;

    $cacheFileName = $path.'cache_'.$indicia_website_id.'_';
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
    if ($timeout && $file && is_file($file) && filemtime($file) >= (time() - $timeout)) {
      $response = array();
      $handle = fopen($file, 'rb');
      $tags = fgets($handle);
      $response['output'] = fread($handle, filesize($file));
      fclose($handle);
      if ($tags == self::array_to_query_string($options)."\n")
        return($response);
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
    if ($file && !is_file($file)) {
      $handle = fopen($file, 'wb');
      fputs($handle, self::array_to_query_string($options)."\n");
      fwrite($handle, $response['output']);
      fclose($handle);
    }
  }

  /**
   * Issue a post request to get the population data required for a control. Depends on the
   * options' table and extraParams values what is requested. This is now cacheable.
   * NB that this function only uses the 'table' and 'extraParams' of $options
   * When generating the cache for this data we need to use the table and
   * any extra params, excluding the read_auth and the nonce. The cache should be 
   * used by all accesses to the DB.
   */
  private static function get_population_data($options) {
    global $indicia_website_id;
    $url = parent::$base_url."index.php/services/data";
    $request = "$url/".$options['table']."?mode=json";

    if (array_key_exists('extraParams', $options)) {
      $cacheOpts = $options['extraParams'];
      $request .= self::array_to_query_string($options['extraParams']);
    } else
      $cacheOpts = array();
    
    $cacheOpts['table'] = $options['table'];
    $cacheOpts['indicia_website_id'] = $indicia_website_id;
    /* If present 'auth_token' amd 'nonce' are ignored as these are session dependant. */
    if (array_key_exists('auth_token', $cacheOpts)) {
      unset($cacheOpts['auth_token']);
    }
    if (array_key_exists('nonce', $cacheOpts)) {
      unset($cacheOpts['nonce']);
    }

    $cacheTimeOut = self::_getCacheTimeOut($options);
    /* TODO : confirm if upload directory is best place for cache files */
    $cacheFile = self::_getCacheFileName(parent::$upload_path, $cacheOpts, $cacheTimeOut);
    if(!($response = self::_getCachedResponse($cacheFile, $cacheTimeOut, $cacheOpts))) 
      $response = self::http_post($request, null);    
    self::_timeOutCacheFile($cacheFile, $cacheTimeOut);
    self::_cacheResponse($cacheFile, $response, $cacheOpts);

    return json_decode($response['output'], true);
  }

  /**
   * Internal function to output either a select or listbox control depending on the templates
   * passed.
   *
   * @access private
   */
  private static function select_or_listbox($options, $outerTmpl, $itemTmpl, $selectTmpl) {
    global $indicia_templates;
    self::add_resource('json');    
    $options = array_merge(array(
      'filterField'=>'parent_id',
      'size'=>3
    ), $options);
    if (array_key_exists('parentControlId', $options)) {
      // no options for now
      $options['options'] = '';
      self::init_linked_lists($options);
    } else {
      $response = self::get_population_data($options);
      if (!array_key_exists('error', $response)) {
        $opts = "";
        if (array_key_exists('blankText', $options)) {
          $opts .= str_replace(
              array('{value}', '{caption}', '{selected}'),
              array('', $options['blankText']),
              $indicia_templates[$itemTmpl]
          );
        }        
        foreach ($response as $item){
          if (array_key_exists($options['captionField'], $item) &&
              array_key_exists($options['valueField'], $item))
          {
            $selected = ($options['default'] == $item[$options['valueField']]) ? $indicia_templates[$selectTmpl] : '';
            $opts .= str_replace(
                array('{value}', '{caption}', '{selected}'),
                array($item[$options['valueField']], $item[$options['captionField']], $selected),
                $indicia_templates[$itemTmpl]
            );
          }
        }
        $options['options'] = $opts;
      } else
        echo $response['error'];
    }
    return self::apply_template($outerTmpl, $options);
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
      $request .= self::array_to_query_string($options['extraParams']);
    }
    self::$javascript .= str_replace(
        array('{fn}','{escapedId}','{request}','{filterField}','{valueField}','{captionField}','{parentControlId}'),
        array($fn, $escapedId, $request,$options['filterField'],$options['valueField'],$options['captionField'],$parentControlId),
        $indicia_templates['linked_list_javascript']
    );
  }

  /**
  * Helper function to generate an autocomplete box from an Indicia core service query.
  * Because this generates a hidden ID control as well as a text input control, the HTML label you
  * associate with this control should be of the form "$id:$caption" rather than just the $id which
  * is normal for other controls. For example:
  * <label for='occurrence:taxa_taxon_list_id:taxon'>Taxon:</label>
  * <?php echo data_entry_helper::autocomplete('occurrence:taxa_taxon_list_id', 'taxa_taxon_list', 'taxon', 'id', $readAuth); ?>
  * <br/>
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
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
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
    self::add_resource('autocomplete');
    $options['url'] = parent::$base_url."/index.php/services/data";
    // Escape the id for jQuery selectors
    $escaped_id=str_replace(':','\\\\:',$options['id']);
    // Do stuff with extraParams
    $sParams = '';
    foreach ($options['extraParams'] as $a => $b){
      $sParams .= "$a : '$b',";
    }
    // lop the comma off the end
    $options['sParams'] = substr($sParams, 0, -1);
    $options['defaultCaption'] = self::check_default_value($options['captionField'],
        array_key_exists('defaultCaption', $options) ? $options['defaultCaption'] : '');
    $options['inputId'] = $options['id'].':'.$options['captionField'];
    // Escape the ids for jQuery selectors
    $options['escaped_id']=str_replace(':','\\\\:',$options['id']);
    $options['escaped_input_id']=str_replace(':','\\\\:',$options['inputId']);
    $replaceTags=array();
    foreach(array_keys($options) as $option) {
      array_push($replaceTags, '{'.$option.'}');
    }
    $options['extraParams']=null;
    self::$javascript .= str_replace($replaceTags, $options, $indicia_templates['autocomplete_javascript']);

    $r = self::apply_template('autocomplete', $options);
    return $r;
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
  * 		'label'=>'Postcode',
  * 		'fieldname'=>'smpAttr:8',
  * 		'linkedAddressBoxId'=>'address'
  * ); ?>
  * <br />
  * <label for="address">Address:</label>
  * <textarea name="address" id="address"></textarea>
  * <br />
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
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
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
    $options['default'] = self::check_default_value($options['fieldname'],
        array_key_exists('default', $options) ? $options['default'] : '');
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
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
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
  * Required. Table name to get data from for the select options.</li>
  * <li><b>captionField</b><br/>
  * Required. Field to draw values to show in the control from.</li>
  * <li><b>valueField</b><br/>
  * Optional. Field to draw values to return from the control from. Defaults
  * to the value of captionField.</li>
  * <li><b>extraParams</b><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
  * </ul>
  * 
  * @return string HTML to insert into the page for the group of radio buttons.
  */
  public static function radio_group() {
    $options = self::check_arguments(func_get_args(), array('fieldname', 'table', 'captionField', 'valueField', 'extraParams', 'sep', 'default'));
    return self::check_or_radio_group($options, 'radio');
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
  * Required. Field to draw values to show in the control from.</li>
  * <li><b>valueField</b><br/>
  * Optional. Field to draw values to return from the control from. Defaults
  * to the value of captionField.</li>
  * <li><b>extraParams</b><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
  * </ul>
  * 
  * @return string HTML to insert into the page for the group of checkboxes.
  */
  public static function checkbox_group() {
    $options = self::check_arguments(func_get_args(), array('fieldname', 'table', 'captionField', 'valueField', 'extraParams', 'sep', 'default'));
    return self::check_or_radio_group($options, 'checkbox');
  }

  /**
   * Internal method to output either a checkbox group or a radio group.
   */
  private static function check_or_radio_group($options, $type) {
    $options = array_merge(array('sep' => ''), $options);
    $url = parent::$base_url."/index.php/services/data";
    // Execute a request to the service
    $response = self::get_population_data($options);
    $r = "";
    if (!array_key_exists('error', $response)){
      foreach ($response as $item) {
        if (array_key_exists($options['captionField'], $item) && array_key_exists($options['valueField'], $item)) {

          $name = htmlspecialchars($item[$options['captionField']], ENT_QUOTES);
          $checked = ($options['default'] == $item[$options['valueField']]) ? 'checked="checked" ' : '';

          $r .= "<span><input type='$type' name='".$options['fieldname']."' value='".$item[$options['valueField']]."' $checked/>";
          $r .= "$name</span>".$options['sep'];
        }
      }
    }
    $r .= self::check_errors($options['fieldname']);
    return $r;
  }

  /**
  * Generates a map control, with optional data entry fields and location finder powered by the
  * Yahoo! geoservices API. This is just a shortcut to building a control using a map_panel and the
  * associated controls.
  *
  * @param string $div Id of a div to add the map into
  * @param array $layers Array of preset layers to include
  * @param bool $edit Include editable controls
  * @param bool $locate Include location finder
  * @param bool $defaultJs Automatically generate default javascript - otherwise leaves you to do this.
  */
  public static function map() {
    $options = self::check_arguments(func_get_args(), array('div', 'presetLayers', 'edit', 'locate', 'wkt'));
    $options = array_merge(array(
        'div'=>'map',
        'presetLayers'=>array('multimap_landranger','google_physical','google_satellite'),
        'edit'=>true,
        'locate'=>true,
        'wkt'=>null
    ));
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
    $r .= self::map_panel(array('initialFeatureWkt', $options['wkt']));
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
   * The div's id can be specified using the divId array entry.
   */
  public static function map_panel($options) {
    global $indicia_templates;
    self::add_resource('indiciaMapPanel');
    $options = array_merge(array(
      'indiciaSvc'=>self::$base_url,
      'indiciaGeoSvc'=>self::$geoserver_url,
      'divId'=>'map',
      'class'=>'',
      'width'=>600,
      'height'=>470,
      'geoPlanetApiKey'=>parent::$geoplanet_api_key,
      'presetLayers'=>array('multimap_landranger','google_physical','google_satellite')
    ), $options);

    if (array_key_exists('readAuth', $options)) {
      // Convert the readAuth into a query string so it can pass straight to the JS class.
      $options['readAuth']=self::array_to_query_string($options['readAuth']);
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
    self::$javascript .= "jQuery('#".$options['divId']."').indiciaMapPanel($json);\n";

    $r = str_replace(
          array('{divId}','{class},{width},{height}'),
          array($options['divId'], empty($options['class']) ? '' : ' class="'.$options['class'].'"'),
          $indicia_templates['map_panel']
      );
    return $r;
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
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
  * </ul>
  * 
  * @return string HTML to insert into the page for the spatial reference control. 
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
        'defaultGeom'=>self::check_default_value($tokens[0].':geom')
    ), $options);
    $options = self::check_options($options);
    $r = self::apply_template('sref_textbox', $options);
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
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
  * <li><b>systems</b>
  * Optional. List of spatial reference systems to display. Associative array with the key
  * being the EPSG code for the system or the notation abbreviation (e.g. OSGB), and the value being
  * the description to display.</li>
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
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
      $selected = ($options['default'] == $system ? $indicia_templates['select_option_selected'] : '');
      $opts .= str_replace(
          array('{value}', '{caption}', '{selected}'),
          array($system, $caption, $selected),
          $indicia_templates['select_option']
      );
    }
    $options['options'] = $opts;
    return self::apply_template('select', $options);
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
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
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
  * Generates a text input control with a search button that looks up an entered place against a georeferencing
  * web service. At this point in time only the Yahoo! GeoPlanet service is supported. The control is automatically
  * linked to any map panel added to the page.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Optional. The name of the database field this control is bound to if any.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
  * </ul>
  * 
  * @return string HTML to insert into the page for the georeference lookup control.
  */
  public static function georeference_lookup($options) {
    $options = self::check_options($options);
    $options['id']='imp-georef-search';
    self::$javascript .= "indicia_url='".self::$base_url."';\n";
    return self::apply_template('georeference_lookup', $options);
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
  * <li><b>label</b><br/>
  * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
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
    echo self::select($options);
  }

  
  /**
  * Helper method to enable the support for tabbed interfaces for a div. The jQuery documentation
  * describes how to specify a list within the div which defines the tabs that are present. This method
  * also automatically selects the first tab that contains validation errors if the form is being
  * reloaded after a validation attempt.
  * 
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>divId</b><br/>
  * Optional. The id of the div which will be tabbed. If not specified then the caller is 
  * responsible for calling the jQuery tabs plugin - this method just links the appropriate
  * jQuery files.</li>
  * 
  * @link http://docs.jquery.com/UI/Tabs
  */
  public static function enable_tabs($options) {
    if (array_key_exists('divId', $options)) {
      // We put this javascript into $late_javascript so that it can come after the other controls.
      // This prevents some obscure bugs - e.g. OpenLayers maps cannot be centered properly on hidden
      // tabs because they have no size.
      self::$late_javascript .= "var tabs = $(\"#".$options['divId']."\").tabs();\n";
      // find any errors on the tabs.
      self::$late_javascript .= "var errors=$(\"#".$options['divId']." .ui-state-error\");\n";
      // select the tab containing the first error, if validation errors are present
      self::$late_javascript .= "if (errors.length>0) {\n".
          "  tabs.tabs('select',$(errors[0]).parents('.ui-tabs-panel')[0].id);\n".
          "  var panel;\n".
          "  for (var i=0; i<errors.length; i++) {\n".
          "    panel = $(errors[i]).parents('.ui-tabs-panel')[0];\n".
          "    $('#'+panel.id+'-tab').addClass('ui-state-error');\n".
          "}}\n";
    }
    self::add_resource('jquery_ui');
  }
  
  public static function tab_header($options) {
    $options = self::check_options($options);
    // Convert the tabs array to a string of <li> elements
    $tabs = "";
    foreach($options['tabs'] as $link => $caption) {
      $tabId=substr("$link-tab",1);
      $tabs .= "<li id=\"$tabId\"><a href=\"$link\"><span>$caption</span></a></li>";
    }
    $options['tabs'] = $tabs;
    return self::apply_template('tab_header', $options);  
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
    // For clean code, the jquery_ui stuff should have gone out in the page header, but just in case...
    if (!in_array('jquery_ui', self::$dumped_resources)) {
      $r = self::internal_dump_javascript('', '', array('jquery_ui'));
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
    self::$javascript .= "$('.loading-panel').remove();\n".
        "var panel=$('.loading-hide')[0];\n".
        "$(panel).hide();\n".    
        "$(panel).removeClass('loading-hide');\n".
        "$(panel).fadeIn('slow');\n";    
    return $indicia_templates['loading_block_end'];
  }

  /**
   * Either takes the passed in array, or the post data if this is null, and forwards it to the data services
   * for saving as a member of the entity identified.
   */
  public static function forward_post_to($entity, $array = null) {
    if ($array == null)
      $array = submission_builder::wrap($_POST, $entity);
    $request = parent::$base_url."/index.php/services/data/$entity";
    $postargs = 'submission='.json_encode($array);
    // passthrough the authentication tokens as POST data
    if (array_key_exists('auth_token', $_POST))
      $postargs .= '&auth_token='.$_POST['auth_token'];
    if (array_key_exists('nonce', $_POST))
      $postargs .= '&nonce='.$_POST['nonce'];
    $response = self::http_post($request, $postargs);
    // The response should be in JSON if it worked
    $output = json_decode($response['output'], true);
    // If this is not JSON, it is an error, so just return it as is.
    if (!$output)
      $output = $response['output'];
    return $output;
  }

  public static function handle_media($media_id) {
    return submission_builder::handle_media($media_id);
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
    if (array_key_exists('occurrence:determiner_id', $arr)){
      $determiner_id = $arr['occurrence:determiner_id'];
    }
    if (array_key_exists('occurrence:record_status', $arr)){
      $determiner_id = $arr['occurrence:record_status'];
    }
    $records = array();
    $subModels = array();
    foreach ($arr as $key=>$value){
      if (strpos($key, 'sc') !== false){
        // Don't explode the last element for occurrence attributes
        $a = explode(':', $key, 3);
        $records[$a[1]][$a[2]] = $value;
      }
    }
    foreach ($records as $id => $record){
      if ((array_key_exists('present', $record) && $record['present']) || 
          ($include_if_any_data && implode('',$record)!='')) {            
        $record['taxa_taxon_list_id'] = $id;
	      $record['website_id'] = $website_id;
	      if (isset($determiner_id)) {
	        $record['determiner_id'] = $determiner_id;
	      }
        if (isset($record_status)) {
          $record['record_status'] = $determiner_id;
        }
	      $occAttrs = data_entry_helper::wrap_attributes($record, 'occurrence');
	      $occ = data_entry_helper::wrap($record, 'occurrence');
	      $occ['metaFields']['occAttributes']['value'] = $occAttrs;
	      $subModels[] = array(
	        'fkId' => 'sample_id',
	        'model' => $occ
	      );
      }
    }    
    return $subModels;
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
   *     'submodel' => array('model' => 'child model name', fk => 'foreign key name', image_entity => 'name of image entity if present'),
   *     'superModel' => array('parent model name' => array(fk => 'foreign key name', image_entity => 'name of image entity if present')),
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
   * @param $resources List of resources to include in the header. The available options are:
   * <ul>
   * <li>jquery<li>
   * <li>openlayers<li>
   * <li>addrowtogrid<li>
   * <li>indiciaMap<li>
   * <li>indiciaMapPanel<li>
   * <li>indiciaMapEdit<li>
   * <li>locationFinder<li>
   * <li>autocomplete<li>
   * <li>jquery_ui<li>
   * <li>json<li>
   * <li>treeview<li>
   * <li>googlemaps<li>
   * <li>multimap<li>
   * <li>virtualearth<li>
   * <li>google_search<li>
   * <li>flickr<li>
   * <li>defaultStylesheet<li>
   * </ul>
   * The default for this is jquery_ui and defaultStylesheet.
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
    // place a css class on the body if JavaScript enabled
    self::$javascript .= '$("body").addClass("js");';
    return self::dump_javascript();
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
    global $indicia_resources;    
    $dump = self::internal_dump_javascript(self::$javascript, self::$late_javascript, $indicia_resources);
    // ensure scripted JS does not output again if recalled.
    self::$javascript = "";
    self::$late_javascript = "";
    return "\n\n".$dump;
  }
  
  /**
   * Internal implementation of the dump_javascript method which takes the javascript and resources list
   * as flexible parameters, rather that using the globals.
   * @access private
   */
  private static function internal_dump_javascript($javascript, $late_javascript, $resources) {
    $libraries = '';
    $stylesheets = '';
    if (isset($resources)) {
      $RESOURCES = self::_RESOURCES();
      foreach ($resources as $resource)
      {
        if (!in_array($resource, self::$dumped_resources)) {
          foreach ($RESOURCES[$resource]['stylesheets'] as $s)
          {
            $stylesheets .= "<link rel='stylesheet' type='text/css' href='$s' />\n";
          }
          foreach ($RESOURCES[$resource]['javascript'] as $j)
          {
            $libraries .= "<script type='text/javascript' src='$j'></script>\n";
          }
          // Record the resource as being dumped, so we don't do it again.
          array_push(self::$dumped_resources, $resource);
        }
      }
    }
    if (!empty($javascript) || !empty($late_javascript)) {
      $script = "<script type='text/javascript'>/* <![CDATA[ */
jQuery(document).ready(function() {
$javascript
$late_javascript
});
/* ]]> */</script>";
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
    global $indicia_errors;
    if (is_array($response)) {
      if (array_key_exists('error',$response)) {
        if ($inline && array_key_exists('errors',$response)) {
          // Setup an errors array that the data_entry_helper can output alongside the controls
          $indicia_errors = $response['errors'];
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
  * Retrieves a token and inserts it into a data entry form which authenticates that the
  * form was submitted by this website.
  *
  * @param string $website_id Indicia ID for the website.
  * @param string $password Indicia password for the website.
  */
  public static function get_auth($website_id, $password) {
    $postargs = "website_id=$website_id";
    $response = self::http_post(parent::$base_url.'/index.php/services/security/get_nonce', $postargs);
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
    global $indicia_website_id;
    
    $indicia_website_id = $website_id; /* Store this for use with data caching */
    $postargs = "website_id=$website_id";
    $response = self::http_post(parent::$base_url.'/index.php/services/security/get_read_nonce', $postargs);
    $nonce = $response['output'];
    return array(
        'auth_token' => sha1("$nonce:$password"),
        'nonce' => $nonce
    );
  }

  /**
   * Takes an associative array and converts it to a list of params for a query string.
   */
  private static function array_to_query_string($array) {
    $r = '';
    if(is_array($array))
      arsort($array);
    foreach ($array as $a => $b)
    {
      $r .= "&$a=$b";
    }
    return $r;
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
   */
  public static function link_default_stylesheet() {
    self::add_resource('defaultStylesheet');
  }

  /**
   * List of external resources including stylesheets and js files used by the data entry helper class.
   */
  private static function _RESOURCES()
  {
    $base = parent::$base_url;
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

    return array (
      'jquery' => array('deps' => array(), 'stylesheets' => array(), 'javascript' => array("$base/media/js/jquery.js")),
      'openlayers' => array('deps' =>array(), 'stylesheets' => array(), 'javascript' => array("$base/media/js/OpenLayers.js", "$base/media/js/Proj4js.js")),          
      'addrowtogrid' => array('deps' => array(), 'stylesheets' => array(), 'javascript' => array("$base/client_helpers/addRowToGrid.js")),
      'indiciaMap' => array('deps' =>array('jquery', 'openlayers'), 'stylesheets' => array(), 'javascript' => array("$base/media/js/jquery.indiciaMap.js")),
      'indiciaMapPanel' => array('deps' =>array('jquery', 'openlayers', 'jquery_ui'), 'stylesheets' => array(), 'javascript' => array("$base/media/js/jquery.indiciaMapPanel.js")),
      'indiciaMapEdit' => array('deps' =>array('indiciaMap'), 'stylesheets' => array(), 'javascript' => array("$base/media/js/jquery.indiciaMap.edit.js")),
      'locationFinder' => array('deps' =>array('indiciaMapEdit'), 'stylesheets' => array(), 'javascript' => array("$base/media/js/jquery.indiciaMap.edit.locationFinder.js")),
      'autocomplete' => array('deps' => array('jquery'), 'stylesheets' => array("$base/media/css/jquery.autocomplete.css"), 'javascript' => array("$base/media/js/jquery.autocomplete.js")),
      'jquery_ui' => array('deps' => array('jquery'), 'stylesheets' => array("$indicia_theme_path/$indicia_theme/jquery-ui.custom.css"), 'javascript' => array("$base/media/js/jquery-ui.custom.min.js", "$base/media/js/jquery-ui.effects.js")),
      'json' => array('deps' => array(), 'stylesheets' => array(), 'javascript' => array("$base/media/js/json2.js")),
      'treeview' => array('deps' => array('jquery'), 'stylesheets' => array("$base/media/css/jquery.treeview.css"), 'javascript' => array("$base/media/js/jquery.treeview.js", "$base/media/js/jquery.treeview.async.js",
      "$base/media/js/jquery.treeview.edit.js")),
      'googlemaps' => array('deps' => array(), 'stylesheets' => array(), 'javascript' => array("http://maps.google.com/maps?file=api&v=2&key=".parent::$google_api_key)),
      'multimap' => array('deps' => array(), 'stylesheets' => array(), 'javascript' => array("http://developer.multimap.com/API/maps/1.2/".parent::$multimap_api_key)),
      'virtualearth' => array('deps' => array(), 'stylesheets' => array(), 'javascript' => array('http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1')),
      'google_search' => array('deps' => array(), 'stylesheets' => array(),
          'javascript' => array(
            "http://www.google.com/jsapi?key=".parent::$google_search_api_key,
            "$base/media/js/google_search.js"
          )
      ),
      'flickr' => array('deps' => array('jquery'), 'stylesheets' => '', 'javascript' => array("$base/media/js/jquery.flickr.js","$base/media/js/thickbox-compressed.js")),
      'defaultStylesheet' => array('deps' => array(''), 'stylesheets' => array("$base/media/css/default_site.css"), 'javascript' => array())
    );
  }

  /**
   * Internal method to link up the external css or js files associated with a set of code.
   * Ensures each file is only linked once.
   *
   * @param string $resource Name of resource to link.
   */
  private static function add_resource($resource)
  {
    global $indicia_resources;
    if (!isset($indicia_resources)) $indicia_resources = array();
    if (array_key_exists($resource, self::_RESOURCES()))
    {
      if (!in_array($resource, $indicia_resources))
      {
        $RESOURCES = self::_RESOURCES();
        foreach ($RESOURCES[$resource]['deps'] as $dep)
        {
          self::add_resource($dep);
        }
        $indicia_resources[] = $resource;
      }
    }
  }

  /**
   * Returns a span containing any validation errors active on the form for the
   * control with the supplied ID.
   *
   * @param string $id ID of the control to retrieve errors for.
   */
  public static function check_errors($id)
  {
    global $indicia_errors, $indicia_templates;
    $error='';    
    if (isset($indicia_errors)) {
       if (array_key_exists($id, $indicia_errors)) {
         $error = $indicia_errors[$id];
       } elseif (substr($id, -4)=='date') {
          // For date fields, we also include the type, start and end validation problems
          if (array_key_exists($id.'_start', $indicia_errors)) {
            $error = $indicia_errors[$id.'_start'];
          }
          if (array_key_exists($id.'_end', $indicia_errors)) {
            $error = $indicia_errors[$id.'_end'];
          }
          if (array_key_exists($id.'_type', $indicia_errors)) {
            $error = $indicia_errors[$id.'_type'];
          }
       }
    }
    if ($error!='') {
       return str_replace('{error}', lang::get($error), $indicia_templates['validation_message']);
    } else {
      return '';
    }
  }

  /**
   * Returns the default value for the control with the supplied Id. The default value is
   * taken as either the $_POST value for this control, or the first of the remaining
   * arguments which contains a non-empty value.
   *
   * @param string $id Id of the control to select the default for.
   * $param [string, [string ...]] Variable list of possible default values. The first that is
   * not empty is used.
   */
  public static function check_default_value($id) {    
    $return = null;
    if (self::$entity_to_load!=null && array_key_exists($id, self::$entity_to_load)) {
      $return = self::$entity_to_load[$id];
    }
    if (!$return) {
      // iterate the variable arguments and use the first one with a real value
      for ($i=1; $i<func_num_args(); $i++) {
        if (func_get_arg($i)) {
          $return = func_get_arg($i);
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
  public static function system_check($fullInfo) {
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
      $curl_check = self::http_post(parent::$base_url.'/index.php/services/security/get_read_nonce', $postargs, false);
      if ($curl_check['result']) {
        if ($fullInfo) {
          $r .= '<li>Success: Indicia Warehouse URL responded to a POST request.</li>';
        }
      } else {
        // Some sort of cUrl problem occurred
        if ($curl_check['errno']) {
          $r .= '<li class="ui-state-error">Warning: The cUrl PHP library could not access the Indicia Warehouse. The error was reported as:';
          $r .= $curl_check['output'].'</br>';
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
    $session = curl_init($url);
    // Set the POST options.
    curl_setopt ($session, CURLOPT_POST, true);
    if ($postargs!==null) {
      curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
    }
    curl_setopt($session, CURLOPT_HEADER, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

    // Do the POST and then close the session
    $response = curl_exec($session);
    if (curl_errno($session) || strpos($response, 'HTTP/1.1 200 OK')===false) {
      if ($output_errors) {
        echo '<div class="error">cUrl POST request failed. Please check cUrl is installed on the server and the $base_url setting is correct.<br/>';
        if (curl_errno($session))
          echo 'Error number: '.curl_errno($session).'<br/>';
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

}

?>