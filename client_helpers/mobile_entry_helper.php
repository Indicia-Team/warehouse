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

$indicia_templates['jqmLeftButton'] = <<<'EOD'
     <a {class} href="{href}"
       data-direction="reverse" data-icon="arrow-l">
       {caption}
     </a>
EOD;
$indicia_templates['jqmRightButton'] = <<<'EOD'
     <a {class} href="{href}"
       data-icon="arrow-r" data-iconpos="right">
       {caption}
     </a>
EOD;
$indicia_templates['jqmSave-SubmitButton'] = <<<'EOD'
   <input onclick="submitStart()" id="{id}" type="button" {class}
       data-icon="check" data-iconpos="right"
       value="Submit" />
EOD;
$indicia_templates['jqmSubmitButton'] = <<<'EOD'
     <input id="{id}" type="submit" {class}
       data-icon="check" data-iconpos="right"
       value="{caption}" />
EOD;

// Do not display an indicator that the field is required.
$indicia_templates['requirednosuffix'] = "\n";
$indicia_templates['check_or_radio_group_item'] = 
    '<input type="{type}" name="{fieldname}" id="{itemId}" value="{value}"{class}{checked} {disabled}/>'
    . '<label for="{itemId}">{caption}</label>';

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
  public static function date_now($options) {
    $options = self::check_options($options);
    $options = array_merge(array(
      'fieldname' => 'sample:date',
      'default' => '',
    ), $options);
    $id = (isset($options['id'])) ? $options['id'] : $options['fieldname'];
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
    $r .= self::hidden_text($options);
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
  * <li><b>default</b><br/>
  * Optional. The default spatial reference to assign to the control. This is
  * overridden when reloading a record with existing data for this control.</li>
  * <li><b>defaultSys</b><br/>
  * Optional. The default spatial reference system to assign to the control.
  * This is overridden when reloading arecord with existing data for this
  * control.</li>
  * </ul>
  * @return string HTML to insert into the page for the location sref control.
  * </ul>
  *
  * @return string HTML to insert into the page for the spatial reference and
  * system controls.
  */

  public static function sref_now($options) {
    $options = array_merge(array(
      'id' => 'imp-sref',
      'fieldname' => 'sample:entered_sref',
      'defaultSys' => '4326',
    ), $options);
    $id = self::jq_esc($options['id']);

    // JavaScript to obtain the sref value;
    self::$javascript .= "
//    (function ($) {
  if(!navigator.geolocation) {
        // Early return if geolocation not supported.
        makePopup('<div style=\"padding:10px 20px;\"><center><h2>Geolocation is not supported by your browser.</h2></center></div>');   
        jQuery('#app-popup').popup();
        jQuery('#app-popup').popup('open');
        return;
      }
      window.SREF_ACCURACY_LIMIT = 20; //meters
      window.GEOLOCATION_ID; // watch geo id
      // Callback if geolocation succeeds.
      var counter = 0;
      function success(position) {
        var latitude  = position.coords.latitude;
        var longitude = position.coords.longitude;
        var accuracy = position.coords.accuracy;
        $('#$id').attr('value', latitude + ', ' + longitude);
        $('#sref_accuracy').attr('value', accuracy);
        if (accuracy < SREF_ACCURACY_LIMIT){
            navigator.geolocation.clearWatch(window.GEOLOCATION_ID);
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
     // window.GEOLOCATION_ID = navigator.geolocation.watchPosition(success, error, options);

//    }) (jqm);
    ";
    
    // HTML which will accept the sref value
    // $r = '<p id="sref">Replace this with the sref.</p>';
    $r .= self::sref_hidden($options);
    $r .= self::hidden_text(array(
      'fieldname' => 'smpAttr:' . $options['accuracy_attr'],
      'id' => 'sref_accuracy',
      'default' => '-1'
    ));
    
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
    
    // Add a paragraph to footer to give it height.
    $r .= '<p>&nbsp;</p>';
    
    if ($options['next'] != '') {
      // Add a next button on the right.
      $options['class'] = "ui-btn-right tab-next";
      $options['caption'] = lang::get($options['captionNext']);
      $options['href'] = $options['next'];
      $r .= self::apply_template('jqmRightButton', $options);
    } else {
      // Add a save button on the right.
      $options['class'] = "ui-btn-right";
      $options['caption'] = lang::get($options['captionSave']);
      $r .= self::apply_template('jqmSave-SubmitButton', $options);
    }
    $r .= '</div>';   
    
    return $r;
  }
}