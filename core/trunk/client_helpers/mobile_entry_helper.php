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
require_once('data_entry_helper.php');

global $indicia_templates;

$indicia_templates['jqmPage'] = $indicia_templates['jqmPageElement'] = <<<'EOD'
     <div data-role="{role}" {attr}>{content}</div>
EOD;

$indicia_templates['jqmBackButton'] = <<<'EOD'
  <a href='{href}' data-rel='back'>{caption}</a>
EOD;

$indicia_templates['jqmNumberInput'] = <<<'EOD'
<table {class}>
  <tr {class}>
    <td {class}>
     <label for="{name}"><b>{caption}</b></label>
    </td><td>
     <input type="number" name="{fieldname}" id="{id}" min="1" max="50" value="{value}">
    </td>
  </tr>
</table>
EOD;

$indicia_templates['jqmDate'] = <<<'EOD'
  <input id="{id}" name="{fieldname}" type="date" value="{default}">
EOD;

$indicia_templates['jqmCheckbox'] = <<<'EOD'
    <label><input type="checkbox" data-iconpos="{data-iconpos}" id="{id}"
    name="{fieldname}" value="{value}">{caption}</label>
EOD;

$indicia_templates['jqmLeftButton'] = <<<'EOD'
     <a {class} href="{href}" data-role="button"
       data-direction="reverse" data-icon="arrow-l">
       {caption}
     </a>
EOD;

//TODO: clean this up. May be move to client side templating.
$indicia_templates['jqmLocation'] = <<<'EOD'
<div data-role="tabs" id="location">
  <div data-role="navbar">
  <input id="imp-sref" name="sample:entered_sref" type="text" value="0">
  <input type="hidden" id="imp-sref-system" name="sample:entered_sref_system" value="4326">
    <ul>
      <li><a href="#gps" data-ajax="false" class="ui-btn-active">GPS</a></li>
      <li><a href="#map" data-ajax="false">Map</a></li>
      <li><a href="#gref" data-ajax="false">Grid Ref</a></li>
    </ul>

  </div>
  <div id="gps" class="ui-body-d ui-content">
    <input type="button" value="Try again">
  </div>
  <div id="map" class="ui-body-d ui-content">
    <div id="map-canvas" style="width: 100vw; height: 50vh;"></div>
  </div>
  <div id="gref" class="ui-body-d ui-content">
  </div>
</div>
EOD;

$indicia_templates['jqmRightButton'] = <<<'EOD'
     <a {class} href="{href}" data-role="button"
       data-icon="arrow-r" data-iconpos="right">
       {caption}
     </a>
EOD;

$indicia_templates['jqmControlSubmitButton'] = <<<'EOD'
   <div align="{align}">
     <input id="{id}" type="button" value="{caption}"
      data-icon="check" data-theme="b"  data-iconpos="right">
   </div>
EOD;

$indicia_templates['jqmSubmitButton'] = <<<'EOD'
     <input id="{id}" type="submit" {class}
       data-icon="check" data-iconpos="right"
       value="{caption}" />
EOD;

// Do not display an indicator that the field is required.
$indicia_templates['requirednosuffix'] = "\n";

$indicia_templates['check_or_radio_group_item'] = <<<'EOD'
    <input type="{type}" name="{fieldname}" id="{itemId}" value="{value}"
      {class}{checked} {disabled}/>
    <label for="{itemId}">{caption}</label>
EOD;
$indicia_templates['check_or_radio_group'] =  <<<'EOD'
    <fieldset data-role="controlgroup" {class}>
      {items}
    </fieldset>
EOD;
$indicia_templates['collapsible_select'] =  <<<'EOD'
    <div>
      {items}
    </div>
EOD;

// jQuery Mobile fieldcontainer grouping
$indicia_templates['fieldcontain_prefix'] = '<div class="ui-field-contain">';
$indicia_templates['fieldcontain_suffix'] = '</div>';

/**
 * Static helper class that provides automatic HTML and JavaScript generation for 
 * Indicia online recording mobile app data entry controls.
 *
 * @package	Client
 */
class mobile_entry_helper extends data_entry_helper {

 /**
  * Ouputs a hidden date control set to the current date.
  * The output of this control can be configured using the following templates: 
  * <ul>
  * <li><b>hidden_text</b></br>
  * Template used for the for hidden inputs.
  * </ul>
  *
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to. 
  * Default 'sample:date'.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the 
  * fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden
  * when reloading a record with existing data for this control.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the date picker control.
  */
  public static function date_now($options, $hidden = NULL) {
    $r = "";
    $options = self::check_options($options);
    $options = array_merge(array(
      'fieldname' => 'sample:date',
      'default' => '0'
    ), $options);
    $id = (isset($options['id'])) ? $options['id'] : $options['fieldname'];
    $options['id'] = $id;
    $id = self::jq_esc($id);
    
    // JavaScript to obtain the date value;
    self::$javascript .= "
    (function ($) {
      // Calculate date string
      var d = new Date();
      var day = d.getDate();
      day = ((day < 10) ? '0' : '') + day
      var month = d.getMonth() + 1;
      month = ((month < 10) ? '0' : '') + month
      var year = d.getFullYear()
      var date = year + '-' + month + '-' + day;
      
      $('#$id').attr('value', date);
    }) (jqm);
    ";
    
    // HTML which will accept the date value
    if (is_null($hidden) || $hidden){
      $r .= self::hidden_text($options);
    } else {
      $r .= data_entry_helper::apply_template('jqmDate', $options);
    }
    return $r;
  }
  
 /**
  * Outputs hidden spatial reference and system controls set to the current
  * position in latitude and longitude. 
  * The output of this control can be configured using the following templates: 
  * <ul>
  * <li><b>hidden_text</b></br>
  * Template used for the for hidden inputs.
  * </ul>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldame</b><br/>
  * Required. The name of the database field the sref control is bound to.
  * Defaults to sample:entered_sref.
  * The system field and geom field is automatically constructed from this.</li>
  * <li><b>accuracy_attr</b><br/>
  * Required. The attribute id which will store the accuracy. Might need to do
  * more work to reload this for an existing record.</li>
  * </ul>
  * <li><b>gps_accuracy_limit</b><br/>
  * Optionl. A threshold value for accuracy to be provided. Default 100m.</li>
  * </ul>
  * <li><b>default</b><br/>
  * Optional. The default spatial reference to assign to the control. This is
  * overridden when reloading a record with existing data for this control.</li>
  * <li><b>defaultSys</b><br/>
  * Optional. The default spatial reference system to assign to the control.
  * This is overridden when reloading arecord with existing data for this
  * control.</li>
  * @return string HTML to insert into the page for the location sref control.
  * </ul>
  *
  * @return string HTML to insert into the page for the spatial reference and
  * system controls.
  */

  public static function sref_now($options, $hidden = NULL) {
    $r = "";

    $options = array_merge(array(
      'id' => 'imp-sref',
      'fieldname' => 'sample:entered_sref',
      'defaultSys' => '4326',
      'gps_accuracy_limit' => 100,
    ), $options);
    $id = self::jq_esc($options['id']);

    // JavaScript to obtain the sref value;
    self::$javascript .= "
//    (function ($) {
  indiciaData.GPS_ACCURACY_LIMIT = " . $options['gps_accuracy_limit'] . "; //meters
  
  if(!navigator.geolocation) {
        // Early return if geolocation not supported.
        makePopup('<div style=\"padding:10px 20px;\"><center><h2>Geolocation is not supported by your browser.</h2></center></div>');   
        jQuery('#app-popup').popup();
        jQuery('#app-popup').popup('open');
        return;
      }
      
      // Callback if geolocation succeeds.
      var counter = 0;
      function success(position) {
        var latitude  = position.coords.latitude;
        var longitude = position.coords.longitude;
        var accuracy = position.coords.accuracy;
        $('#$id').attr('value', latitude + ', ' + longitude);
        $('#sref_accuracy').attr('value', accuracy);
        if (accuracy < indiciaData.GPS_ACCURACY_LIMIT){
            navigator.geolocation.clearWatch(indiciaData.gps_running_id);
            $('.geoloc_icon').css('display', '');
        }
      };
      
      // Callback if geolocation fails.
      function error(error) {
        console.log('Geolocation error.');
      };
      
      // Geolocation options.
      var options = {
        enableHighAccuracy: true,
        maximumAge: 0,
        timeout: 120000
      };
      // Request geolocation.
      indiciaData.gps_running_id = navigator.geolocation.watchPosition(success, error, options);

//    }) (jqm);
    ";
    
    // HTML which will accept the sref value
    // $r = '<p id="sref">Replace this with the sref.</p>';
    if(is_null($hidden) || $hidden ){
      $r .= self::sref_hidden($options);
      $r .= self::hidden_text(array(
        'fieldname' => 'smpAttr:' . $options['accuracy_attr'],
        'id' => 'sref_accuracy',
        'default' => '-1'
      ));
    } else {
      $r .= self::apply_template('jqmLocation', NULL);
    }
    
    return $r;
  }
  
  /**
   * A version of the select control which supports hierarchical termlist data by
   * adding new selects to the next line populated with the child terms when a 
   * parent term is selected. Applies jQuery Mobile enhancement to the added
   * select. 
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the
   * fieldname is used.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden
   * when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>  *
   * <li><b>table</b><br/>
   * Table name to get data from for the select options. Should be termlists_term
   * for termlist data.</li>
   * <li><b>report</b><br/>
   * Report name to get data from for the select options if the select is being
   * populated by a service call using a report.
   * Mutually exclusive with the table option. The report should return a
   * parent_id field.</li>
   * <li><b>captionField</b><br/>
   * Field to draw values to show in the control from if the select is being
   * populated by a service call.</li>
   * <li><b>valueField</b><br/>
   * Field to draw values to return from the control from if the select is being
   * populated by a service call. Defaults to the value of captionField.</li>
   * <li><b>extraParams</b><br/>
   * Optional. Associative array of items to pass via the query string to the
   * service. This should at least contain the read authorisation array if the
   * select is being populated by a service call. It can also contain
   * view=cache to use the cached termlists entries or view=detail for the 
   * uncached version.</li>
   * </ul>
   * The output of this control can be configured using the following templates: 
   * <ul>
   * <li><b>select</b></br>
   * Template used for the HTML select element.
   * </li>
   * <li><b>select_item</b></br>
   * Template used for each option item placed within the select element.
   * </li>
   * <li><b>hidden_text</b></br>
   * HTML used for a hidden input that will hold the value to post to the database.
   * </li>
   * </ul>
   */
  public static function hierarchical_select($options) {
    $options = array_merge(array(
      'id' => 'select-' . rand(0,10000),
      'blankText' => '<please select>'
    ), $options);
    $options['extraParams']['preferred'] = 't';
    
    // Get the data for the control. Not Ajax populated at the moment. We either
    // populate the lookupValues for the top level control or store in the 
    // childData for output into JavaScript
    $values = self::get_population_data($options);
    $lookupValues = array();
    $childData = array();
    foreach ($values as $value) {
      if (empty($value['parent_id'])) {
        $lookupValues[$value[$options['valueField']]] = $value[$options['captionField']];
      }
      else {
        // not a top level item, so put in a data array we can store in JSON.
        if (!isset($childData[$value['parent_id']])) {
          $childData[$value['parent_id']] = array();
        }
        $childData[$value['parent_id']][] = array(
            'id' => $value[$options['valueField']],
            'caption' => $value[$options['captionField']]
            );
      }
    }    
    // build an ID with just alphanumerics, that we can use to keep JavaScript
    // function and data names unique
    $dataId = preg_replace('/[^a-zA-Z0-9]/', '', $options['id']);
    // dump the control population data out for JS to use
    self::$javascript .= "indiciaData.selectData$dataId = " . json_encode($childData) . ";\n";

    // Convert the options so that the top-level select uses the lookupValues
    // we've already loaded rather than reloads its own.
    unset($options['table']);
    unset($options['report']);
    unset($options['captionField']);
    unset($options['valueField']);
    $options['lookupValues'] = $lookupValues;
    
    // Output a hidden input that contains the value to post.
    $hiddenOptions = array(
        'id' => 'fld-' . $options['id'],
        'fieldname' => $options['fieldname'],
        'default' => self::check_default_value($options['fieldname'])
        );
    if (isset($options['default'])) {
      $hiddenOptions['default'] = $options['default'];
    }
    $r = self::hidden_text($hiddenOptions);
    
    // Output a select. Use templating to add a wrapper div, so we can keep all
    // the hierarchical selects together. 
    global $indicia_templates;
    $oldTemplate = $indicia_templates['select'];
    $classes = 'hierarchical-select control-box ';
    if (!empty($options['class'])) {
      $classes .= $options['class'];
    }
    $indicia_templates['select'] = '<div class="' . $classes . '">'
        . $indicia_templates['select']
        . '</div>';
    $options['class'] = 'hierarchy-select';
    // The fieldname must be different from the hidden input.
    $options['fieldname'] = 'parent-'.$options['fieldname'];
    $r .= self::select($options);
    $indicia_templates['select'] = $oldTemplate;

    // Now output JavaScript that creates and populates child selects as each
    // option is selected. There is also code for reloading existing values.    
    $options['blankText'] = htmlspecialchars(lang::get($options['blankText']));
    // jQuery safe version of the Id. 
    $safeId = preg_replace('/[:]/', '\\\\\\:', $options['id']);
    self::$javascript .= <<<EOD
  // Enclosure needed in case there are multiple on the page.
  // Call the enclosed function with the version of jQuery installed with the 
  // jQuery Mobile module.
  (function ($) {
    function pickHierarchySelectNode(select) {
      // jQuery Mobile wraps the select in a nest of two divs.
      var dad = select.parent();
      var grandpa = dad.parent();
      if(grandpa.hasClass('ui-select')) {
      
        // Remove selects lower in the hierarchy. Something seems to prevent 
        // jQuery.remove() propagating to all children so we traverse the
        // DOM ourselves to do this.
        var youngGreatUncles = grandpa.nextAll();
        var cousins = youngGreatUncles.children();
        var cousins2 = cousins.children();
        cousins2.remove();
        cousins.remove();
        youngGreatUncles.remove();
        
        if (typeof indiciaData.selectData$dataId [select.val()] !== 'undefined') {
          // We need to add a select.
          // Create a unique id for the select.
          var index = grandpa.prevAll().length;
          var newId = '{$options['id']}-' + index;
          // Construct the select.
          var html = '<select id="' + newId + '" class="hierarchy-select">';
          html += '<option>{$options['blankText']}</option>';
          $.each(indiciaData.selectData$dataId [select.val()], function(idx, item) {
            html += '<option value="' + item.id + '">' + item.caption + '</option>';
          });
          html += '</select>';
          // Add a change event to the select.
          var obj = $(html);
          obj.change(function(evt) { 
            $('#fld-$safeId').val($(evt.target).val());
            pickHierarchySelectNode($(evt.target));
          });
          // Insert the select in the DOM.
          grandpa.after(obj);
          // Apply jQuery Mobile enhancements.
          safeNewId = newId.replace(':', '\\\\:');
          $('#' + safeNewId).selectmenu();
        }
      }    
    }
    
    $('#$safeId').change(function(evt) {
      $('#fld-$safeId').val($(evt.target).val());
      pickHierarchySelectNode($(evt.target));
    });
    
    // Code from here on is to reload existing values.
    function findItemParent(idToFind) {
      var found = false;
      $.each(indiciaData.selectData$dataId, function(parentId, items) {
        $.each(items, function(idx, item) {
          if (item.id === idToFind) {
            found=parentId;
          }
        });
      });
      return found;
    }
    var found = true, last = $('#fld-$safeId').val(), tree = [last], toselect, thisselect;
    while (last !== '' && found) {
      found=findItemParent(last);
      if (found) {
        tree.push(found);
        last=found;
      }
    }   
  
    // now we have the tree, work backwards to select each item
    thisselect = $('#$safeId');
    while (tree.length > 0) {
      toselect=tree.pop();
      $.each(thisselect.find('option'), function(idx, option) {
        if ($(option).val() === toselect) {
          $(option).attr('selected',true);
          thisselect.trigger('change');
        }
      });
      thisselect = thisselect.next();
    }
  }) (jqm);
EOD;
    return $r;
  }

  /**
  * Insert buttons which, when clicked, displays the next or previous tab.
  * Insert this inside the tab divs on each tab you want to have a next or 
   * previous button, excluding the last tab.
  * The output of this control can be configured using the following templates: 
  * <ul>
  * <li><b>jqmLeftButton</b></br>
  * HTML template used for previous buttons.
  * </li>
  * <li><b>jqmRightButton</b></br>
  * HTML template used for next buttons.
  * </li>
  * <li><b>jqmSubmitButton</b></br>
  * HTML template used for the submit button.
  * </li>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>prev</b><br/>
  * The href of the page a previous button should go to. If empty it will be
  * omitted.</li>
  * <li><b>next</b><br/>
  * The href of the page a next button should go to. If empty a submit button
  * will be added.</li>
  * <li><b>captionPrev</b><br/>
  * Optional. The untranslated caption of the previous button. Defaults to prev step.</li>
  * <li><b>captionNext</b><br/>
  * Optional. The untranslated caption of the next button. Defaults to next step.</li>
  * <li><b>captionSave</b><br/>
  * Optional. The untranslated caption of the submit button. Defaults to save.</li>
  * <li><b>class</b><br/>
  * Optional. Additional classes to add to the button container.</li>
  * </ul>
  *
  * @link http://docs.jquery.com/UI/Tabs
  */
  public static function wizard_buttons($options = array()) {
    // Default values
    $defaults = array(
      'prev'        => '',
      'next'        => '',
      'captionPrev' => 'prev step',
      'captionNext' => 'next step',
      'captionSave' => 'save',
      'class'       => '',
      'suffixTemplate' => 'nullsuffix',
    );
    $options = array_merge($defaults, $options);
    
    // We'll arrange the buttons in a footer.
    $r = '<div data-role="footer" data-position="fixed"';
    $r .= 'class = "' . $options['class'] .= '">';
    
    if ($options['next'] != '') {
       // Add a paragraph to footer to give it height.
       $r .= '<p>&nbsp;</p>';
      // Add a next button on the right.
      $options['class'] = "ui-btn-right tab-next";
      $options['caption'] = lang::get($options['captionNext']);
      $options['href'] = $options['next'];
      $r .= self::apply_template('jqmRightButton', $options);
    } else {
      // Add a save button on the right.
      $options['caption'] = lang::get($options['captionSave']);
      $options['id'] = "entry-form-submit";
      $options['align'] = "right";
      $r .= self::apply_template('jqmControlSubmitButton', $options);
    }
    $r .= '</div>';   
    
    return $r;
  }

  
  /**
   * A version of the select control which supports hierarchical termlist data by
   * adding new selects to the next line populated with the child terms when a 
   * parent term is selected. Applies jQuery Mobile enhancement to the added
   * select. 
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the
   * fieldname is used.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden
   * when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>  *
   * <li><b>table</b><br/>
   * Table name to get data from for the select options. Should be termlists_term
   * for termlist data.</li>
   * <li><b>report</b><br/>
   * Report name to get data from for the select options if the select is being
   * populated by a service call using a report.
   * Mutually exclusive with the table option. The report should return a
   * parent_id field.</li>
   * <li><b>captionField</b><br/>
   * Field to draw values to show in the control from if the select is being
   * populated by a service call.</li>
   * <li><b>valueField</b><br/>
   * Field to draw values to return from the control from if the select is being
   * populated by a service call. Defaults to the value of captionField.</li>
   * <li><b>extraParams</b><br/>
   * Optional. Associative array of items to pass via the query string to the
   * service. This should at least contain the read authorisation array if the
   * select is being populated by a service call. It can also contain
   * view=cache to use the cached termlists entries or view=detail for the 
   * uncached version.</li>
   * <li><b>captionTemplate</b><br/>
   * Optional and only relevant when loading content from a data service call. 
   * Specifies the template used to build the caption, with each database field
   * represented as {fieldname}.</li>
  * </ul>
   * </ul>
   * The output of this control can be configured using the following templates: 
   * <ul>
   * <li><b>collapsible_select_option_group</b></br>
   * Template used for the HTML select element.
   * </li>
   * <li><b>collapsible_select_option</b></br>
   * Template used for each option item placed within the select element.
   * </li>
   * </ul>
   */
  public static function collapsible_select($options) {
   $options = self::check_options($options);
   $options['extraParams']['preferred'] = 't';
    
    // Get the data for the control. 
    $items = self::get_population_data($options);
    // An array for top level items in the hierarchy.
    $primaryData = array();
    // An array for lower level items in the hierarchy.
    $childData = array();
    // Loop through all the data to organise it in the arrays.
    foreach ($items as $item) {
      // Obtain the value based on the field set in the options
      $itemValue = $item[$options['valueField']];
      // Obtain the caption based on the captionTemplate or captionField options
      if (isset($options['captionTemplate'])) {
        $itemCaption = self::mergeParamsIntoTemplate($item, $options['captionTemplate']);
      }
      else {
        $itemCaption = $item[$options['captionField']];
      }
      
      if (empty($item['parent_id'])) {
        // Store a top level item
        $primaryData[] = array(
            'id' => $itemValue, 
            'caption' => $itemCaption);
      }
      else {
        // Store all children of the same parent together.
        $itemParent = $item['parent_id'];
        if (!isset($childData[$itemParent])) {
          $childData[$itemParent] = array();
        }
        $childData[$itemParent][] = array(
            'id' => $itemValue, 
            'caption' => $itemCaption);
      }
    }

    // Construct the html to output the choices
    $options['items'] = self::_collapsible_select_html($primaryData, $childData, $options);
    // Wrap items and add label  
    $options['labelTemplate'] = 'toplabel';
    return self::apply_template('collapsible_select', $options);
  }
  
  private static function _collapsible_select_html($primaryData, $childData, $options) {
    static $depth = -1;
    $depth++;
    $indent = str_repeat('  ', $depth);    
    $r = '';
    $colapsiblesetOpen = false;
    $fieldsetOpen = false;
    
    // Loop through primary items
    foreach ($primaryData as $primaryItem) {
      // Call recursive function to construct html
      $parentId = $primaryItem['id'];
      // Has primary item got children?
      if(array_key_exists($parentId, $childData)) {
        // Primary item has children  
        
        // Manage html of enclosures
         if ($fieldsetOpen) {
          // Close any open fieldset
          $fieldsetOpen = false;
          $r .=  $indent . '</fieldset>' . "\n";
        }
        if (!$colapsiblesetOpen) {
          // Start a collapsibleset if not open
          $colapsiblesetOpen = true;
          $r .= "\n" . $indent . '<div data-role="collapsibleset">' . "\n";
        }
        
        $r .= $indent . '  <div data-role="collapsible">' . "\n";
        $r .= $indent . '    <h3>' . $primaryItem['caption'] . '</h3>' . "\n";
        // Recursive call to get next level in hierarchy
        $newPrimaryData = $childData[$parentId];
        $r .= self::_collapsible_select_html($newPrimaryData, $childData, $options);
        $r .= $indent . '  </div>' . "\n";
      }
      else {
        // No children so output a radio button

        // Manage html of enclosures
        if ($colapsiblesetOpen) {
          // Close any open collapsibleset
          $colapsiblesetOpen = false;
          $r .= $indent . '</div>' . "\n";
        }
         if (!$fieldsetOpen) {
          // Start a fieldset if not open
           $fieldsetOpen = true;
          $r .=  $indent . '<fieldset data-role="controlgroup">' . "\n";
        }
        
        $templateOpts = array(
            'type' => 'radio',
            'fieldname' => $options['fieldname'],
            'itemId' => $options['fieldname'] . ':' . $primaryItem['id'],
            'value' => $primaryItem['id'],
  //          'class' => '',
  //          'checked' => '',
  //          'title' => '',
  //          'disabled' => '',
            'caption' => $primaryItem['caption'],
            'suffixTemplate' => 'nullsuffix',
        );
        $r .= self::apply_template('check_or_radio_group_item', $templateOpts);
      }
    }
    
    // Manage html of enclosures
    if ($colapsiblesetOpen) {
      // Close any open collapsibleset
      $r .= $indent . '</div>' . "\n";
    }
     if ($fieldsetOpen) {
      // Close any open fieldset
      $r .=  $indent . '</fieldset>' . "\n";
    }
    return $r;
  }

}