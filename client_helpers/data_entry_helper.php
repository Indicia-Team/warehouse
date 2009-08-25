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

global $templates;

/**
 * Provides a control templates to define the output of the data entry helper class.
 *
 * @package	Client
 */
$templates = array(
  'prefix' => '',
  'label' => '<label for="{id}">{label}:</label>'."\n",
  'suffix' => "<br/>\n",
  'nosuffix' => " \n",
  'image_upload' => '<input type="file" id="{id}" name="{fieldname}" accept="png|jpg|gif"/>'."\n",
  'textarea' => '<textarea id="{id}" name="{fieldname}" class="{class}">{default}</textarea>'."\n",
  'text_input' => '<input type="text" id="{id}" name="{fieldname}" class="{class}" value="{default}">'."\n",
  'date_picker' => '<input type="text" size="30" class="date {class}" id="{id}" name="{fieldname}" value="{default}"/>' .
      '<style type="text/css">.embed + img { position: relative; left: -21px; top: -1px; }</style> ',
  'select' => '<select id="{id}" name="{fieldname}" class="{class}">{options}</select>',
  'select_option' => '<option value="{value}" {selected} >{caption}</option>',
  'select_option_selected' => 'selected="selected"',
  'listbox' => '<select id="{id}" name="{fieldname}" class="{class}" size="{size}" multiple="{multiple}">{options}</select>',
  'listbox_option' => '<option value="{value}" {selected} >{caption}</option>',
  'listbox_option_selected' => 'selected="selected"',
  'list_in_template' => '<ul class="{class}">{items}</ul>',
  'map_panel' => "<div id=\"{divId}\"></div>\n<br/>\n",
  'georeference_lookup' => "<input id=\"imp-georef-search\" \>\n".
      "<input type=\"button\" id=\"imp-georef-search-btn\" class=\"ui-corner-all ui-widget-content ui-state-default indicia-button\" value=\"".lang::get('search')."\" />\n".
      "<div id=\"imp-georef-div\" class=\"ui-corner-all ui-widget-content ui-helper-hidden page-notice\" ><div id=\"imp-georef-output-div\" />\n".
      "</div><a class=\"ui-corner-all ui-widget-content ui-state-default indicia-button\" href=\"#\" id=\"imp-georef-close-btn\">".lang::get('close')."</a>\n".
      "</div>",
  'autocomplete' => '<input type="hidden" class="hidden" id="{id}" name="{fieldname}" value="{default}" />'."\n".
         '<input id="{inputId}" name="{inputId}" value="{defaultCaption}" />'."\n",
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
  'postcode_textbox' => '<input type="text" name="{fieldname}" id="{id}" class="{class}" value="{default}" '.
        'onblur="javascript:decodePostcode(\'{linkedAddressBoxId}\');" />',
  'sref_textbox' => '<input type="text" id="{id}" name="{fieldname}" class="{class}" />' .
        '<input type="hidden" id="imp-geom" name="{table}:geom" />'
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
   * @param string $template Name of the control template, from the global $templates variable.
   * @param array $options Options array containing the control replacement values for the templates.
   * Options can contain a setting for prefixTemplate or suffixTemplate to override the standard templates.
   */
  private static function apply_template($template, $options) {
    global $templates;
    // Don't need the extraParams - they are just for service communication.
    $options['extraParams']=null;
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
      $r .= $templates[$options['prefixTemplate']];
    } else {
      $r .= $templates['prefix'];
    }
    // Add a label only if specified in the options array. Link the label to the inputId if available,
    // otherwise the fieldname (as the fieldname control could be a hidden control).
    if (array_key_exists('label', $options)) {
      $r .= str_replace(
          array('{label}', '{id}'),
          array($options['label'], array_key_exists('inputId', $options) ? $options['inputId'] : $options['id']),
          $templates['label']
      );
    }
    // Output the main control
    $r .= str_replace($replaceTags, $replaceValues, $templates[$template]);
    // output any errors identified for the control
    if (array_key_exists('fieldname', $options)) {
      $r .= self::check_errors($options['fieldname']);
    }
    if (array_key_exists('suffixTemplate', $options)) {
      $r .= $templates[$options['suffixTemplate']];
    } else {
      $r .= $templates['suffix'];
    }
    return $r;
  }

  /**
  * Helper function to support image upload by inserting a file path upload control.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><strong>fieldname</strong><br/>
  * Required. The name of the database field this control is bound to, e.g. occurrence:image.</li>
  * <li><strong>id</strong><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><strong>class</strong><br/>
  * Optional. CSS class names to add to the control.</li>
  * </ul>
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
  * <li><strong>fieldname</strong><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><strong>id</strong><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><strong>default</strong><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><strong>class</strong><br/>
  * Optional. CSS class names to add to the control.</li>
  * </ul>
  */
  public static function text_input() {
    $options = self::check_arguments(func_get_args(), array('fieldname'));
    return self::apply_template('text_input', $options);
  }

 /**
  * Helper function to output an HTML textarea. This includes re-loading of existing values
  * and displaying of validation error messages.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><strong>fieldname</strong><br/>
  * Required. The name of the database field this control is bound to, e.g. occurrence:image.</li>
  * <li><strong>id</strong><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><strong>default</strong><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><strong>class</strong><br/>
  * Optional. CSS class names to add to the control.</li>
  * </ul>
  */
  public static function textarea() {
    $options = self::check_arguments(func_get_args(), array('fieldname'));
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
   * @param array $options Options array with the following possibilities:<ul>
   * <li><strong>listId</strong><br/>
   * Required. The ID of the taxon_lists record which is to be used to obtain the species or taxon list.</li>
   * <li><strong>occAttrs</strong><br/>
   * Integer array, where each entry corresponds to the id of the desired attribute in the
   * occurrence_attributes table.</li>
   * <li><strong>extraParams</strong><br/>
   * Optional. Associative array of items to pass via the query string to the service. This
   * should at least contain the read authorisation array.</li>
   * <li><strong>lookupListId</strong><br/>
   * Required. The ID of the taxon_lists record which is to be used to select taxa from when adding
   * rows to the grid. If specified, then an autocomplete text box and
   * Add Row button are generated automatically allowing the user to pick a species to add as an
   * extra row.</li>
   * <li><strong>header</strong><br/>
   * Include a header row in the grid? Defaults to true.</li>
   * <li><strong>columns</strong><br/>
   * Number of repeating columns of output. For example, a simple grid of species checkboxes could be output in 2 or 3 columns.
   * Defaults to 1.</li>
   * </ul>
   */
  public static function species_checklist()
  {
    global $javascript, $entity_to_load;
    $options = self::check_arguments(func_get_args(), array('listId', 'occAttrs', 'readAuth', 'extraParams', 'lookupListId'));
    $options = array_merge(array(
        'header'=>'true',
        'columns'=>1
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
    foreach ($options['occAttrs'] as $occAttr)
    {
      $occAttrRequest = "$url/occurrence_attribute/$occAttr?mode=json";
      $occAttrRequest .= self::array_to_query_string($options['readAuth']);
      $session = curl_init($occAttrRequest);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $a = json_decode(array_pop(explode("\n\n", curl_exec($session))), true);
      if (! array_key_exists('error', $a))
      {
        $b = $a[0];
        $occAttrs[$occAttr] = $b['caption'];
        // Build the correct control
        switch ($b['data_type'])
        {
          case 'L':
            $tlId = $b['termlist_id'];
            $occAttrControls[$occAttr] =
            data_entry_helper::select(
              'oa:'.$occAttr, 'termlists_term', 'term', 'id',
              $options['readAuth'] + array('termlist_id' => $tlId)
            );
            break;
          case 'D' || 'V':
            // Date-picker control
            $occAttrControls[$occAttr] = "<input type='text' class='date' id='oa:$occAttr' name='oa:$occAttr' " .
                "value='".lang::get('click here')."'/>";
            break;

          default:
            $occAttrControls[$occAttr] =
                "<input type='text' id='oa:$occAttr' name='oa:$occAttr'/>";
            break;
        }
      }
    }


    // Build the grid
    if (! array_key_exists('error', $taxalist))
    {
      $grid = "<table style='display: none'><tbody><tr id='scClonableRow'><td class='scTaxonCell'></td>".
          "<td class='scPresenceCell'><input type='checkbox' name='' value='' checked='true' /></td>";
      foreach ($occAttrControls as $oc) {
        $grid .= "<td class='scOccAttrCell'>$oc</td>";
      }
      $grid .= "</tr></tbody></table>";
      $grid .= "<table class='ui-widget ui-widget-content'>";
      if ($options['header']) {
        $grid .= "<thead class=\"ui-widget-header\">";
        for ($i=0; $i<$options['columns']; $i++) {
          $grid .= "<th>".lang::get('species_checklist.species')."</th><th>".lang::get('species_checklist.present')."</th>";
          foreach ($occAttrs as $a) {
            $grid .= "<th>$a</th>";
          }
        }
        $grid .= '</thead>';
      }
      $rows = array();
      $rowIdx = 0;
      foreach ($taxalist as $taxon) {
        $id = $taxon['id'];
        $row = "<td class='scTaxonCell ui-state-default'>".$taxon['taxon']." ".$taxon['authority']."</td>";
        if (array_key_exists("sc:$id:present", $entity_to_load)) {
          $checked = ' checked="checked"';
        } else {
          $checked='';
        }
        $row .= "<td class='scPresenceCell'><input type='checkbox' name='sc:$id:present' $checked /></td>";
        foreach ($occAttrControls as $oc) {
          $oc = preg_replace('/oa:(\d+)/', "sc:$id:occAttr:$1", $oc);
          $row .= "<td class='scOccAttrCell'>".$oc."</td>";
        }
        if ($rowIdx < count($taxalist)/$options['columns']) {
          $rows[$rowIdx]=$row;
        } else {
          $rows[$rowIdx % (floor(count($taxalist)/$options['columns']))] .= $row;
        }
        $rowIdx++;
      }
      $grid .= '<tbody><tr>'.implode('</tr><tr>', $rows).'</tr></tbody';
      $grid .= '</tbody></table>';

      // Insert an autocomplete box if the termlist has a parent or an alternate
      // termlist has been given in the parameter.
      if ($options['lookupListId'] == null) {

        $tlRequest = "$url/taxon_list/".$options['listId']."?mode=json&view=detail";
        $session = curl_init($tlRequest);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        $tl = json_decode(array_pop(explode("\r\n\r\n",curl_exec($session))), true);
        if (! array_key_exists('error', $tl)) {
          $options['lookupListId'] = $tl[0]['parent_id'];
        }
      }
      if ($options['lookupListId'] != null) {
        // Javascript to add further rows to the grid
        self::add_resource('addrowtogrid');
        $javascript .= "var addRowFn = addRowToGrid('$url', {'auth_token' : '".
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
      // Reference to the config file.
      global $javascript;
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

      $javascript .= "jQuery('#tr$id').treeview(
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
  * <li><strong>fieldname</strong><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><strong>id</strong><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><strong>default</strong><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><strong>class</strong><br/>
  * Optional. CSS class names to add to the control.</li>
  * </ul>
  */
  public static function date_picker() {
    $options = self::check_arguments(func_get_args(), array('fieldname', 'default'));

    self::add_resource('jquery_ui');
    global $javascript;
    $escaped_id=str_replace(':','\\\\:',$options['id']);
    $javascript .= "jQuery('#$escaped_id').datepicker({dateFormat : 'yy-mm-dd', constrainInput: false});\n";

    if (!array_key_exists('default', $options) || $options['default']='') {
      $options['default']=lang::get('click here');
    }
    return self::apply_template('date_picker', $options);
  }

 /**
  * Helper function to generate a select control from a Indicia core service query. The select control can
  * be linked to populate itself when an item is selected in another control by specifying the
  * parentControlId and filterField options.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><strong>fieldname</strong><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><strong>id</strong><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><strong>default</strong><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><strong>class</strong><br/>
  * Optional. CSS class names to add to the control.</li>  *
  * <li><strong>table</strong><br/>
  * Required. Table name to get data from for the select options.</li>
  * <li><strong>captionField</strong><br/>
  * Required. Field to draw values to show in the control from.</li>
  * <li><strong>valueField</strong><br/>
  * Optional. Field to draw values to return from the control from. Defaults
  * to the value of captionField.</li>
  * <li><strong>extraParams</strong><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><strong>parentControlId</strong><br/>
  * Optional. Specifies a parent control for linked lists. If specified then this control is not
  * populated until the parent control's value is set. The parent control's value is used to
  * filter this control's options against the field specified by filterField.</li>
  * <li><strong>filterField</strong><br/>
  * Optional. Specifies the field to filter this control's content against when using a parent
  * control value to set up linked lists. Defaults to parent_id though this is not active
  * unless a parentControlId is specified.</li>
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
  * <li><strong>fieldname</strong><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><strong>id</strong><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><strong>default</strong><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><strong>class</strong><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><strong>table</strong><br/>
  * Required. Table name to get data from for the select options.</li>
  * <li><strong>captionField</strong><br/>
  * Required. Field to draw values to show in the control from.</li>
  * <li><strong>valueField</strong><br/>
  * Optional. Field to draw values to return from the control from. Defaults
  * to the value of captionField.</li>
  * <li><strong>extraParams</strong><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><strong>size</strong><br/>
  * Optional. Number of lines to display in the listbox. Defaults to 3.</li>
  * <li><strong>multiselect</strong><br/>
  * Optional. Allow multi-select in the list box. Defaults to false.</li>
  * <li><strong>parentControlId</strong><br/>
  * Optional. Specifies a parent control for linked lists. If specified then this control is not
  * populated until the parent control's value is set. The parent control's value is used to
  * filter this control's options against the field specified by filterField.</li>
  * <li><strong>filterField</strong><br/>
  * Optional. Specifies the field to filter this control's content against when using a parent
  * control value to set up linked lists. Defaults to parent_id though this is not active
  * unless a parentControlId is specified.</li>
  * </ul>
  */
  public static function listbox()
  {
    $options = self::check_arguments(func_get_args(), array(
        'fieldname', 'table', 'captionField', 'size', 'multiselect', 'valueField', 'extraParams', 'default'
    ));
    return self::select_or_listbox($options, 'listbox', 'listbox_option', 'listbox_option_selected');
  }

  /**
   * Issue a post request to get the population data required for a control. Depends on the
   * options' table and extraParams values what is requested.
   */
  private static function get_population_data($options) {
    $url = parent::$base_url."index.php/services/data";
    // Execute a request to the service
    $request = "$url/".$options['table']."?mode=json";
    if (array_key_exists('extraParams', $options)) {
      $request .= self::array_to_query_string($options['extraParams']);
    }
    $response = self::http_post($request, null);
    return json_decode($response['output'], true);
  }

  /**
   * Internal function to output either a select or listbox control depending on the templates
   * passed.
   *
   * @access private
   */
  private static function select_or_listbox($options, $outerTmpl, $itemTmpl, $selectTmpl) {
    global $templates;
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
        foreach ($response as $item){
          if (array_key_exists($options['captionField'], $item) &&
              array_key_exists($options['valueField'], $item))
          {
            $selected = ($options['default'] == $item[$options['valueField']]) ? $templates[$selectTmpl] : '';
            $opts .= str_replace(
                array('{value}', '{caption}', '{selected}'),
                array($item[$options['valueField']], $item[$options['captionField']], $selected),
                $templates[$itemTmpl]
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
    global $templates, $javascript;
    // setup JavaScript to do the population when the parent control changes
    $parentControlId = str_replace(':','\\\\:',$options['parentControlId']);
    $escapedId = str_replace(':','\\\\:',$options['id']);
    $fn = str_replace(':','',$options['id'])."_populate";
    $url = parent::$base_url."index.php/services/data";
    $request = "$url/".$options['table']."?mode=json";
    if (array_key_exists('extraParams', $options)) {
      $request .= self::array_to_query_string($options['extraParams']);
    }
    $javascript .= str_replace(
        array('{fn}','{escapedId}','{request}','{filterField}','{valueField}','{captionField}','{parentControlId}'),
        array($fn, $escapedId, $request,$options['filterField'],$options['valueField'],$options['captionField'],$parentControlId),
        $templates['linked_list_javascript']
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
  * @param string $id Id and name of the HTML input generated, corresponding to the database field this posts the valueField into.
  * @param string $entity Name of the Indicia entity being posted into. Possibilities are:
  * <ul>
  * <li>language</li>
  * <li>location</li>
  * <li>occurrence</li>
  * <li>occurrence_attribute</li>
  * <li>occurrence_comment</li>
  * <li>person</li>
  * <li>sample</li>
  * <li>survey</li>
  * <li>taxon_group</li>
  * <li>taxa_taxon_list</li>
  * <li>taxon_list</li>
  * <li>term</li>
  * <li>termlist</li>
  * <li>termlists_term</li>
  * <li>user</li>
  * <li>website</li>
  * </ul>
  * @param $captionField string Name of the database field used to generate the display caption for each data item. This does
  * not need to be prefixed with a table name and colon.
  * @param $valueField string Name of the database field used to obtain the value which is stored into the database
  * when this control is saved. Typically, $captionField is used to identify a caption, and $valueField is used to identify
  * the ID stored in the database referring to that caption.
  * @param array $extraParams List of name value pairs for extra parameters that are passed in the calls to the Indicia
  * data services when populating this control. This should include the read authentication and
  * any additional filters required when selecting the data. For example, the following value for this parameter causes
  * the control's content to be filtered to a specific termlist.<br/>
  * <CODE>$readAuth + array('termlist_id' => $config['surroundings_termlist'])</CODE>
  * @param string $defaultCaption Default caption to display in the control on startup.
  * @param string $defaultValue Default hidden value for the control on startup.
  * @see get_read_auth()
  * @link http://code.google.com/p/indicia/wiki/DataModel
  */
  public static function autocomplete() {
    global $templates, $javascript;
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
    $javascript .= str_replace($replaceTags, $options, $templates['autocomplete_javascript']);

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
  * <li><strong>fieldname</strong><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><strong>id</strong><br/>
  * Optional. The id to assign to the HTML control. This should be left to its default value for
  * integration with other mapping controls to work correctly.</li>
  * <li><strong>default</strong><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><strong>class</strong><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><strong>hiddenFields</strong><br/>
  * Optional. Set to true to insert hidden inputs to receive the latitude and longitude. Otherwise there
  * should be separate sref_textbox and sref_system_textbox controls elsewhere on the page. Defaults to true.
  * <li><strong>srefField</strong><br/>
  * Optional. Name of the spatial reference hidden field that will be output by this control if hidddenFields is true.</li>
  * <li><strong>systemField</strong><br/>
  * Optional. Name of the spatial reference system hidden field that will be output by this control if hidddenFields is true.</li>
  * <li><strong>linkedAddressBoxId</strong><br/>
  * Optional. Id of the separate textarea control that will be populated with an address when a postcode is looked up.</li>
  */
  public static function postcode_textbox($options) {
    $options = self::check_options($options);
    // Merge in the defaults
    $options = array_merge(array(
        'srefField'=>'sample:entered_sref',
        'systemfield'=>'sample:entered_sref_system',
        'hiddenFields'=>true,
        'id'=>'imp-postcode',
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
  * <li><strong>class</strong><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><strong>table</strong><br/>
  * Required. Table name to get data from for the select options.</li>
  * <li><strong>extraParams</strong><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><strong>template</strong><br/>
  * Required. HTML template which will be emitted for each item. Fields from the data are identified
  * by wrapping them in ||. For example, |term| would result in the field called term's value being placed inside
  * the HTML.</li>
  * </ul>
  * @return String containing the output HTML.
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
  * <li><strong>fieldname</strong><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><strong>id</strong><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><strong>default</strong><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><strong>class</strong><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><strong>table</strong><br/>
  * Required. Table name to get data from for the select options.</li>
  * <li><strong>captionField</strong><br/>
  * Required. Field to draw values to show in the control from.</li>
  * <li><strong>valueField</strong><br/>
  * Optional. Field to draw values to return from the control from. Defaults
  * to the value of captionField.</li>
  * <li><strong>extraParams</strong><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * </ul>
  */
  public static function radio_group() {
    $options = self::check_arguments(func_get_args(), array('fieldname', 'table', 'captionField', 'valueField', 'extraParams', 'sep', 'default'));
    return self::check_or_radio_group($options, 'radio');
  }

 /**
  * Helper function to generate a list of checkboxes from a Indicia core service query.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><strong>fieldname</strong><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><strong>id</strong><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><strong>default</strong><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><strong>class</strong><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><strong>table</strong><br/>
  * Required. Table name to get data from for the select options.</li>
  * <li><strong>captionField</strong><br/>
  * Required. Field to draw values to show in the control from.</li>
  * <li><strong>valueField</strong><br/>
  * Optional. Field to draw values to return from the control from. Defaults
  * to the value of captionField.</li>
  * <li><strong>extraParams</strong><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * </ul>
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

          $r .= "<span><input type='$type' id='$id' name='$id' value='$item[$valueField]' $checked/>";
          $r .= "$name</span>$sep";
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
   * </ul>{@link  location_select()}</ul>
   * </ul>{@link location_autocomplete()}</li>
   * </ul>{@link postcode_textbox()}</li>
   * </ul>
   *
   * @param array $options Associative array of options to pass to the jQuery.indiciaMapPanel plugin.
   * The div's id can be specified using the divId array entry.
   */
  public static function map_panel($options) {
    global $javascript, $templates;
    self::add_resource('indiciaMapPanel');
    $options = array_merge(array(
    'divId'=>'map',
    'geoPlanetApiKey'=>parent::$geoplanet_api_key,
    'presetLayers'=>array('multimap_landranger','google_physical','google_satellite')
  ), $options);

    if (array_key_exists('readAuth', $options)) {
      // Convert the readAuth into a query string so it can pass straight to the JS class.
      $options['readAuth']=self::array_to_query_string($options['readAuth']);
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
    $javascript .= "jQuery('#".$options['divId']."').indiciaMapPanel($json);\n";

    $r = str_replace(
          array('{divId}'),
          array($options['divId']),
          $templates['map_panel']
      );
    return $r;
  }

  /**
   * Creates a textbox for entry of a spatial reference.
   * Also generates the hidden geom field required to properly post spatial data. The
   * box is automatically linked to a map_panel if one is added to the page.
   *
   * @param string $fieldname Name of the database field that data will be posted to. Defaults to
   * sample:entered_sref.
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
        'table'=>$tokens[0]
    ), $options);
    $options = self::check_options($options);
    $r = self::apply_template('sref_textbox', $options);
    return $r;
  }

  /**
   * Outputs a drop down select control populated with a list of spatial reference systems
   * for the user to select from.
   *
   * @param string $fieldname Name of the database field that data will be posted to. Defaults to
   * sample:entered_sref_system.
   * @param array $systems List of spatial reference systems to display. Associative array with the key
   * being the EPSG code for the system or the notation abbreviation (e.g. OSGB), and the value being
   * the description to display.
   */
  public static function sref_system_select($options) {
    global $templates;
    $options = array_merge(array(
        'fieldname'=>'sample:entered_sref_system',
        'systems'=>array('OSGB'=>lang::get('british national grid'), '4326'=>lang::get('lat long 4326')),
        'id'=>'imp-sref-system'
    ), $options);
    $options = self::check_options($options);
    $opts = "";
    foreach ($options['systems'] as $system=>$caption){
      $selected = ($options['default'] == $system ? $templates['select_option_selected'] : '');
      $opts .= str_replace(
          array('{value}', '{caption}', '{selected}'),
          array($system, $caption, $selected),
          $templates['select_option']
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
   * @param string $fieldname Name of the database field that spatial reference will be posted to. Defaults to
   * sample:entered_sref. The system field is automatically constructed from this.
   * @param array $systems List of spatial reference systems to display. Associative array with the key
   * being the EPSG code for the system or the notation abbreviation (e.g. OSGB), and the value being
   * the description to display.
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
  * <li><strong>fieldname</strong><br/>
  * Required. The name of the database field this control is bound to if any.</li>
   */
  public static function georeference_lookup($options) {
    $options = self::check_options($options);
    $options['id']='imp-georef-search';
    return self::apply_template('georeference_lookup', $options);
  }

 /**
  * Outputs a select control that is dedicated to listing locations and which is bound to any map panel
  * added to the page. Although it is possible to set all the options of a normal select control, generally
  * the table, valueField, captionField, id should be left uninitialised and the fieldname will default to the
  * sample's location_id field so can normally also be left.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><strong>fieldname</strong><br/>
  * Optional. The name of the database field this control is bound to.</li>
  * <li><strong>default</strong><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><strong>class</strong><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><strong>extraParams</strong><br/>
  * Required. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * </ul>
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

  public static function location_autocomplete() {

  }


  /**
   * Either takes the passed in array, or the post data if this is null, and forwards it to the data services
   * for saving as a member of the entity identified.
   */
  public static function forward_post_to($entity, $array = null) {
    if ($array == null)
      $array = self::wrap($_POST, $entity);
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
    if (array_key_exists($media_id, $_FILES)) {
      syslog(LOG_DEBUG, "SITE: Media id $media_id to upload.");
      $uploadpath = parent::$upload_path;
      $target_url = parent::$base_url."/index.php/services/data/handle_media";

      $name = $_FILES[$media_id]['name'];
      $fname = $_FILES[$media_id]['tmp_name'];
      $fext = array_pop(explode(".", $name));
      $bname = basename($fname, ".$fext");

      // Generate a file id to store the image as
      $destination = time().rand(0,1000).".".$fext;

      if (move_uploaded_file($fname, $uploadpath.$destination)) {
        $postargs = array();
        if (array_key_exists('auth_token', $_POST)) {
               $postargs['auth_token'] = $_POST['auth_token'];
        }
        if (array_key_exists('nonce', $_POST)) {
          $postargs['nonce'] = $_POST['nonce'];
        }
        $file_to_upload = array('media_upload'=>'@'.realpath($uploadpath.$destination));
        self::http_post($target_url, $file_to_upload + $postargs);
        return $destination;

      } else {
        //TODO error messaging
        return false;
      }
    }
  }

  /**
  * Wraps data from a species checklist grid (generated by
  * data_entry_helper::species_checklist) into a suitable format for submission. This will
  * return an array of submodel entries which can be dropped directly into the subModel
  * section of the submission array.
  *
  * @param array $arr Array of data generated by data_entry_helper::species_checklist method.
  */
  public static function wrap_species_checklist($arr){
    if (array_key_exists('website_id', $arr)){
      $website_id = $arr['website_id'];
    } else {
      throw new Exception('Cannot find website id in POST array!');
    }
    if (array_key_exists('determiner_id', $arr)){
      $determiner_id = $arr['determiner_id'];
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
      if (! array_key_exists('present', $record) || !$record['present']){
        unset ($records[$id]);
        break;
      }
      $record['taxa_taxon_list_id'] = $id;
      $record['website_id'] = $website_id;
      $record['determiner_id'] = $determiner_id;
      $occAttrs = data_entry_helper::wrap_attributes($record, 'occurrence');
      $occ = data_entry_helper::wrap($record, 'occurrence');
      $occ['metaFields']['occAttributes']['value'] = $occAttrs;
      $subModels[] = array(
        'fkId' => 'sample_id',
        'model' => $occ
      );
    }

    return $subModels;
  }

  /**
  * Wraps attribute fields (entered as normal) into a suitable container for submission.
  * Throws an error if $entity is not something for which attributes are known to exist.
  * @return array
  */
  public static function wrap_attributes($arr, $entity) {
    $prefix=self::get_attr_entity_prefix($entity).'Attr';
    $oap = array();
    $occAttrs = array();
    foreach ($arr as $key => $value) {
      if (strpos($key, $prefix) !== false) {
        $a = explode(':', $key);
        // Attribute in the form occAttr:36 for attribute with attribute id
        // of 36.
        $oap[] = array(
          $entity."_attribute_id" => $a[1], 'value' => $value
        );
      }
    }
    foreach ($oap as $oa) {
      $occAttrs[] = data_entry_helper::wrap($oa, $entity."_attribute");
    }
    return $occAttrs;
  }

  /**
   * Returns a 3 character prefix representing an entity name that can have
   * custom attributes attached.
   * @param string $entity Entity name (location, sample or occurrence).
   */
  private static function get_attr_entity_prefix($entity) {
    switch ($entity) {
      case 'occurrence':
        $prefix = 'occ';
        break;
      case 'location':
        $prefix = 'loc';
        break;
      case 'sample':
        $prefix = 'smp';
        break;
      default:
        throw new Exception('Unknown attribute type. ');
    }
    return $prefix;
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
    // Initialise the wrapped array
    $sa = array(
        'id' => $entity,
        'fields' => array()
    );

    // Iterate through the array
    foreach ($array as $a => $b)
    {
      // Don't wrap the authentication tokens, or any attributes tagged as belonging to another entity
      if ($a!='auth_token' && $a!='nonce' && (!strpos($a, ':') || strpos($a, "$entity:")!==false))
      {
        // strip the entity name tag if present, as should not be in the submission attribute names
        $a = str_replace("$entity:", '', $a);
        // This should be a field in the model.
        // Add a new field to the save array
        $sa['fields'][$a] = array('value' => $b);
      }
    }
    return $sa;
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
        'submodel' => array(
          'model' => 'occurrence',
          'fk' => 'sample_id'
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
   * Helper function to simplify building of a submission. Does simple submissions that do not involve
   * species checklist grids.
   * @param array $values List of the posted values to create the submission from.
   * @param array $structure Describes the structure of the submission. The form should be:
   * array(
   *     'model' => 'main model name',
   *     'submodel' => array('model' => 'child model name', fk => 'foreign key name', image_entity => 'name of image entity if present')
   * )
   */
  public static function build_submission($values, $structure) {
    $modelWrapped = self::inner_build_submission($values, $structure);
    return array('submission' => array('entries' => array(
      array ( 'model' => $modelWrapped )
    )));
  }

  private static function inner_build_submission($values, $structure) {
    // Wrap the main model and attrs into JSON
    $modelWrapped = self::wrap_with_attrs($values, $structure['model']);
    // Is there a child model?
    if (array_key_exists('submodel', $structure)) {
      $submodelWrapped = self::inner_build_submission($values, $structure['submodel']);
      // Join the parent and child models together
      if (!array_key_exists('subModels', $modelWrapped)) {
        $modelWrapped['subModels']=array();
      }
      array_push($modelWrapped['subModels'], array(
        'fkId' => $structure['submodel']['fk'],
        'model' => $submodelWrapped
      ));
    }
    return $modelWrapped;
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
    // Get the parent model into JSON
    $modelWrapped = data_entry_helper::wrap($values, $modelName);
    // Might it have custom attributes?
    if (strcasecmp($modelName, 'occurrence')==0 ||
        strcasecmp($modelName, 'sample')==0 ||
        strcasecmp($modelName, 'location')==0) {
      // Get the attributes
      $attrs = self::wrap_attributes($values, $modelName);
      // If any exist, then store them in the model
      if (count($attrs)>0) {
        $modelWrapped['metaFields'][self::get_attr_entity_prefix($modelName).'Attributes']['value']=$attrs;
      }
    }
    // Does it have an image?
    if ($name = data_entry_helper::handle_media("$modelName:image"))
    {
      // Add occurrence image model
      // TODO Get a caption for the image
      $oiFields = array(
          'path' => $name,
          'caption' => 'Default caption'
      );
      $oiMod = data_entry_helper::wrap($oiFields, $modelName.'_image');
      $modelWrapped['subModels'][] = array(
          'fkId' => 'occurrence_id',
          'model' => $oiMod
      );
    }
    return $modelWrapped;
  }

  /**
  * Helper function to collect javascript code in a single location. Should be called at the end of each HTML
  * page which uses the data entry helper so output all JavaScript required by previous calls.
  *
  * @link http://code.google.com/p/indicia/wiki/TutorialBuildingBasicPage#Build_a_data_entry_page
  */
  public static function dump_javascript() {
    global $javascript;
    global $res;
    $libraries = '';
    $stylesheets = '';
    if (isset($res)) {
      $RESOURCES = self::_RESOURCES();
      foreach ($res as $resource)
      {
        foreach ($RESOURCES[$resource]['stylesheets'] as $s)
        {
          $stylesheets .= "<link rel='stylesheet' type='text/css' href='$s' />\n";
        }
        foreach ($RESOURCES[$resource]['javascript'] as $j)
        {
          $libraries .= "<script type='text/javascript' src='$j'></script>\n";
        }
      }
    }
    $script = "<script type='text/javascript'>
    jQuery(document).ready(function() {
    $javascript
    });
    </script>";
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
    global $entity_to_load;
    global $errors;
    if (is_array($response)) {
      if (array_key_exists('error',$response)) {
        if ($inline && array_key_exists('errors',$response)) {
          // Setup an errors array that the data_entry_helper can output alongside the controls
          $errors = $response['errors'];
          // And tell the helper to reload the existing data.
          $entity_to_load = $_POST;
        } else {
          echo '<div class="ui-state-error ui-corner-all">';
          echo '<p>An error occurred when the data was submitted.</p>';
          if (is_array($response['error'])) {
            echo '<ul>';
            foreach ($response['error'] as $field=>$message)
              echo "<li>$field: $message</li>";
            echo '</ul>';
          } else {
            echo '<p class="error_message">'.$response['error'].'</p>';
          }
          if (array_key_exists('file', $response) && array_key_exists('line', $response)) {
            echo '<p>Error occurred in '.$response['file'].' at line '.$response['line'].'</p>';
          }
          if (array_key_exists('errors', $response)) {
            echo '<pre>'.print_r($response['errors'], true).'</pre>';
          }
          if (array_key_exists('trace', $response)) {
            echo '<pre>'.print_r($response['trace'], true).'</pre>';
          }
          echo '</div>';
        }
      }
      elseif (array_key_exists('warning',$response)) {
        echo 'A warning occurred when the data was submitted.';
        echo '<p class="error">'.$response['error'].'</p>';
      }
      elseif (array_key_exists('success',$response)) {
        echo '<div class="ui-widget ui-corner-all ui-state-highlight page-notice">Thank you for submitting your data.</div>';
      }
    }
  else
    echo "<div class=\"ui-state-error ui-corner-all\">$response</div>";
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
    global $theme;
    global $theme_path;
    if (!isset($theme)) {
      // Use default theme if page does not specify it's own.
      $theme="default";
    }
    if (!isset($theme_path)) {
      // Use default theme path if page does not specify it's own.
      $theme_path="$base/media/themes";
    }

    return array (
      'jquery' => array('deps' => array(), 'stylesheets' => array(), 'javascript' => array("$base/media/js/jquery.js")),
      'openlayers' => array('deps' =>array(), 'stylesheets' => array(), 'javascript' => array("$base/media/js/OpenLayers.js")),
      'addrowtogrid' => array('deps' => array(), 'stylesheets' => array(), 'javascript' => array("$base/client_helpers/addRowToGrid.js")),
      'indiciaMap' => array('deps' =>array('jquery', 'openlayers'), 'stylesheets' => array(), 'javascript' => array("$base/media/js/jquery.indiciaMap.js")),
      'indiciaMapPanel' => array('deps' =>array('jquery', 'openlayers', 'jquery_ui'), 'stylesheets' => array(), 'javascript' => array("$base/media/js/jquery.indiciaMapPanel.js")),
      'indiciaMapEdit' => array('deps' =>array('indiciaMap'), 'stylesheets' => array(), 'javascript' => array("$base/media/js/jquery.indiciaMap.edit.js")),
      'locationFinder' => array('deps' =>array('indiciaMapEdit'), 'stylesheets' => array(), 'javascript' => array("$base/media/js/jquery.indiciaMap.edit.locationFinder.js")),
      'autocomplete' => array('deps' => array('jquery'), 'stylesheets' => array("$base/media/css/jquery.autocomplete.css"), 'javascript' => array("$base/media/js/jquery.autocomplete.js")),
      'jquery_ui' => array('deps' => array('jquery'), 'stylesheets' => array("$theme_path/$theme/jquery-ui.custom.css"), 'javascript' => array("$base/media/js/jquery-ui.custom.min.js", "$base/media/js/jquery-ui.effects.js")),
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
    global $res;
    if (!isset($res)) $res = array();
    if (array_key_exists($resource, self::_RESOURCES()))
    {
      if (!in_array($resource, $res))
      {
        $RESOURCES = self::_RESOURCES();
        foreach ($RESOURCES[$resource]['deps'] as $dep)
        {
          self::add_resource($dep);
        }
        $res[] = $resource;

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
    global $errors;
    $error='';    
    if (isset($errors)) {
       if (array_key_exists($id, $errors)) {
         $error = $errors[$id];
       } elseif (substr($id, -4)=='date') {
          // For date fields, we also include the type, start and end validation problems
          if (array_key_exists($id.'_start', $errors)) {
            $error = $errors[$id.'_start'];
          }
          if (array_key_exists($id.'_end', $errors)) {
            $error = $errors[$id.'_end'];
          }
          if (array_key_exists($id.'_type', $errors)) {
            $error = $errors[$id.'_type'];
          }
       }
    }
    if ($error!='') {
       return '<br/><div class="ui-state-error ui-corner-all inline-error">'.
           '<span class="ui-icon ui-icon-alert" style="float: left; margin-left: 3px;"></span>'.
           lang::get($error).'</div>';
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
    global $entity_to_load;
    $return = null;
    if ($entity_to_load && array_key_exists($id, $entity_to_load)) {
      $return = $entity_to_load[$id];
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
    }
    $r .= '</ul></div>';
    return $r;
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