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
 * @subpackage PrebuiltForms
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Extension class that supplies new controls to support the Splash project.
 */
class extension_splash_extensions {
    
  /*
   * Validator for Splash Epiphyte survey input forms, validates the following:
   * - That a plot is filled in.
   * - The details of at least one tree have been entered
   * - The user hasn't entered a count for any trees that don't exist
   * - The user has filled in grid references for all trees (this doesn't use the built in mandatory field functionality of Indicia
   * as the system would flag the Epiphytes as not containing a grid references when they actually shouldn't
   */
  public static function splash_validate($auth, $args, $tabAlias, $options) {
    if (empty($options['treeOccurrenceAttrIds'])) {
      drupal_set_message('Please fill in the @treeOccurrenceAttrIds option for the splash_validate control.
                          This should be a comma seperated list of attribute ids that hold the Epiphyte counts for trees.');
      return '';
    }
    $treeOccurrenceAttrIds=explode(',',$options['treeOccurrenceAttrIds']);
    //The validator that makes sure the user hasn't entered a count for a tree that doesn't exist works as follows.
    //- Call the validator function for each epiphyte grid in turn
    //- Cycle through the grid rows
    //- Cycle through all the cells on the row for trees that haven't been entered on the trees grid
    //- Flag error if value found in cell
    data_entry_helper::$javascript .= "
    $('<span class=\"deh-required\">*</span>').insertAfter('.scGridRef\\\\/Accuracy');

    function runValidateOnEpiphyteGrid(gridId,rowCount,treesCount,treeOccurrenceAttrIds) {
      var rowIdx;
      var treeIdxToCheck;
      for (rowIdx=0; rowIdx<rowCount; rowIdx++) {
        for (treeIdxToCheck=treesCount; treeIdxToCheck<treeOccurrenceAttrIds.length;treeIdxToCheck++) {
          if ($('#sc\\\\:'+gridId+'-'+rowIdx+'\\\\:\\\\:occAttr\\\\:'+treeOccurrenceAttrIds[treeIdxToCheck]).val()) {
            alert('You have entered a count for Epiphytes on a tree that doesn\'t exist in the trees grid.');
            return false;
          }
        }
      }
      return true;
    }
    $('#entry_form').submit(function() {
      var treesCount = $('#trees tr').length-3;
      var treeOccurrenceAttrIds = ".json_encode($treeOccurrenceAttrIds).";
      var epiphytesPopulatedCount = $('#Epiphytes-populated tr').length-1;
      var epiphytesFreeCount = $('#Epiphytes-free tr').length-3;
      if ($('#imp-location').val()==='<Please select>') {
        alert('Please select a plot before submitting.');
        return false;
      }
      if (treesCount===0) {
        alert('Please enter the details of at least 1 tree.');
        return false;
      }
      if ($('.scGridRef\\\\/Accuracy[value=]').length>=3) {
        alert('Please fill in the grid reference field for all trees.');
        return false;
      }
      var epiphytePopulatedValidateResult;
      var epiphyteFreeValidateResult;
      epiphytePopulatedValidateResult = runValidateOnEpiphyteGrid('Epiphytes-populated',epiphytesPopulatedCount,treesCount,treeOccurrenceAttrIds);
      //Only do test for Epiphyte grid if first grid passes, otherwise user could get two validation messages.
      if (epiphytePopulatedValidateResult === true) {
        epiphyteFreeValidateResult = runValidateOnEpiphyteGrid('Epiphytes-free',epiphytesFreeCount,treesCount,treeOccurrenceAttrIds);
      }
      if (epiphytePopulatedValidateResult===false||epiphyteFreeValidateResult===false) {
        return false;
      }
    });
    ";
  }
  
  /**
   * Get a location select control pair, first the user must select a square then a plot associated with a square.
   * Only squares that are associated with the user and also have plots are displayed
   * When a plot is selected, then a mini report about the plot is displayed.
   * 
   * $options Options array with the following possibilities:<ul>
   * <li><b>coreSquareLocationTypeId</b><br/>
   * The location type id of a core square</li>
   * <li><b>additionalSquareLocationTypeId</b><br/>
   * The location type id of an additional square</li>
   * <li><b>viceCountyLocationAttributeId</b><br/>
   * The attribute ID that holds the vice counties associated with a square</li>
   * <li><b>noViceCountyFoundMessage</b><br/>
   * A square's vice country makes up part of its name, however if it doesn't have a vice county then display this replacement text instead</li>
   * <li><b>orientationAttributeId</b><br/>
   * The location attribute id that holds a plot's Orientation</li>
   * <li><b>aspectAttributeId</b><br/>
   * The location attribute id that holds a plot's Aspect</li>
   * <li><b>slopeAttributeId</b><br/>
   * The location attribute id that holds a plot's Slope</li>
   * <li><b>ashAttributeId</b><br/>
   * The location attribute id that holds a plot's % Ash Coverage</li>
   * </ul>
   */
  public static function splash_location_select($auth, $args, $tabAlias, $options) {
    if (empty($options['coreSquareLocationTypeId'])) {
      drupal_set_message('Please fill in the @coreSquareLocationTypeId option for the splash_location_select control');
      return '';
    }
    if (empty($options['additionalSquareLocationTypeId'])) {
      drupal_set_message('Please fill in the @additionalSquareLocationTypeId option for the splash_location_select control');
      return '';
    }
    if (empty($options['viceCountyLocationAttributeId'])) {
      drupal_set_message('Please fill in the @viceCountyLocationAttributeId option for the splash_location_select control');
      return '';
    }
    if (empty($options['noViceCountyFoundMessage'])) {
      drupal_set_message('Please fill in the @noViceCountyFoundMessage option for the splash_location_select control');
      return '';
    }
    $coreSquareLocationTypeId=$options['coreSquareLocationTypeId'];
    $additionalSquareLocationTypeId=$options['additionalSquareLocationTypeId'];
    $currentUserId=hostsite_get_user_field('indicia_user_id');
    $viceCountyLocationAttributeId=$options['viceCountyLocationAttributeId'];
    $noViceCountyFoundMessage=$options['noViceCountyFoundMessage'];
    $reportOptions = array(
      'dataSource'=>'reports_for_prebuilt_forms/Splash/get_my_squares_that_have_plots',
      'readAuth'=>$auth['read'],
      'mode'=>'report',
      'extraParams' => array('core_square_location_type_id'=>$coreSquareLocationTypeId,
                             'additional_square_location_type_id'=>$additionalSquareLocationTypeId,
                             'current_user_id'=>$currentUserId,
                             'vice_county_location_attribute_id'=>$viceCountyLocationAttributeId,
                             'no_vice_county_found_message'=>$noViceCountyFoundMessage)
    );
    
    $rawData = data_entry_helper::get_report_data($reportOptions);
    if (empty($rawData)) {
      //If the user doesn't have any plots, then hide the map and disable the Spatial Ref field so they can't continue
      drupal_set_message('Note: You have not been allocated any squares to input data for, or the squares you have been allocated do not have plots.');
      drupal_set_message('You cannot enter data without having a plot to select.');
      data_entry_helper::$javascript .= "$('#map').hide();";
      data_entry_helper::$javascript .= "$('#imp-sref').attr('disabled','disabled');";
      return '<b>You have not been allocated any Squares that contain plots</b></br>';
    } else {
      //Convert the raw data in the report into array format suitable for the Select drop-down to user (an array of ID=>Name pairs)
      foreach($rawData as $rawRow) {
          $squaresData[$rawRow['id']]=$rawRow['name'];        
      }
      //Need a report to collect the square to default the Location Select to in edit mode, as this is not stored against the sample directly.
      if (!empty($_GET['sample_id'])) {
        $squareData = data_entry_helper::get_report_data(array(
          'dataSource'=>'reports_for_prebuilt_forms/Splash/get_square_for_sample',
          'readAuth'=>$auth['read'],
          'extraParams'=>array('sample_id'=>$_GET['sample_id'])
        ));
        $defaultSquareSelection=$squareData[0]['id'];
      } else {
        $defaultSquareSelection='';
      }
      $r = data_entry_helper::select(array(
        'id' => 'squares-select-list',
        'fieldname'=> 'squares-select-list',
        'label' => lang::get('Select a Square'),
        'helpText' => lang::get('Select a square to input data for before selecting a plot.'),
        'lookupValues' => $squaresData, 
        'default' => $defaultSquareSelection
      ));
      //This code is same as standard lookup control
      if (isset($options['extraParams'])) {
        foreach ($options['extraParams'] as $key => &$value)
          $value = apply_user_replacements($value);
        $options['extraParams'] = array_merge($auth['read'], $options['extraParams']);
      } else 
        $options['extraParams'] = array_merge($auth['read']);
      if (empty($options['reportProvidesOrderBy'])||$options['reportProvidesOrderBy']==0) {
        $options['extraParams']['orderby'] = 'name';
      }
      //Setup the Plot drop-down which uses the Suqare selection the user makes.
      $options['parentControlId']= 'squares-select-list';
      $options['filterField']= 'square_id';
      $options['reportProvidesOrderBy']=true;
      $options['searchUpdatesSref']=true;
      $options['label']='Plot';
      $options['report']='reports_for_prebuilt_forms/Splash/get_plots_for_square_id';
    
      //Create the drop-down for the plot
      $location_list_args = array_merge(array(
          'label'=>lang::get('LANG_Location_Label'),
          'view'=>'detail'
      ), $options);
      $r .= data_entry_helper::location_select($location_list_args);
      //Create the mini report
      $r .= self::plot_report_panel($auth,$options);
      return $r;
    }
  }
  
  /*
   * Display a mini report when the user selects a plot
   */
  private static function plot_report_panel($auth,$options) {
    iform_load_helpers(array('report_helper'));
    $reportOptions = array(
      'linkOnly'=>'true',
      'dataSource'=>'reports_for_prebuilt_forms/Splash/get_plot_details',
      'readAuth'=>$auth['read']
    );  
    data_entry_helper::$javascript .= "indiciaData.plotReportRequest='".
       report_helper::get_report_data($reportOptions)."';\n";
    //The html to place the data into using jQuery
    $htmlTemplate = "
    </br><div id='plot_report_panel'>
      </br>
      <h5>Plot Details</h5>
      <div id='field ui-helper-clearfix'>
        <span><b>Plot Type: </b></span><span id='plot-type-value'></span></br>
        <span><b>Plot Description: </b></span><span id='plot-description-value'></span></br>
        <span><b>Vice County: </b></span><span id='vice-county-value'></span></br>
        <span><b>Orientation: </b></span><span id='orientation-value'></span></br>
        <span><b>Aspect: </b></span><span id='aspect-value'></span></br>
        <span><b>Slope: </b></span><span id='slope-value'></span></br>
        <span><b>% Ash cover: </b></span><span id='ash-cover-value'></span></br>
      </div>
    </div></br>";
    //When the plot is changed then get the data about a plot from a report and then 
    //place it into the mini report html template using jQuery.
    data_entry_helper::$javascript .= "$('#imp-location').change(function() {
      if ($(this).val()==='<Please select>') {
        $('#plot-type-value').text('');
        $('#plot-description-value').text('');
        $('#vice-county-value').text('');
        $('#orientation-value').text('');
        $('#aspect-value').text('');
        $('#slope-value').text('');
        $('#ash-value').text('');
      } else {
        var reportRequest = indiciaData.plotReportRequest
        + '&vice_county_name_attribute_id=' + ".$options['viceCountyLocationAttributeId']."
        + '&orientation_attribute_id=' + ".$options['orientationAttributeId']."
        + '&aspect_attribute_id=' + ".$options['aspectAttributeId']."
        + '&slope_attribute_id='+ ".$options['slopeAttributeId']."
        + '&ash_attribute_id=' + ".$options['ashAttributeId']."
        + '&plot_id='+$(this).val() + '&callback=?';
        $.getJSON(reportRequest,
          null,
          function(response, textStatus, jqXHR) {
            $.each(response, function (idx, obj) {         
              if (obj.type) {
                $('#plot-type-value').text(obj.type);
              } else {
                $('#plot-type-value').text('');
              }
              if (obj.description) {
                $('#plot-description-value').text(obj.description);
              } else {
                $('#plot-description-value').text('');
              }
              if (obj.county) {
                $('#vice-county-value').text(obj.county);
              } else {
                $('#vice-county-value').text('');
              }
              if (obj.orientation) {
                $('#orientation-value').text(obj.orientation);
              } else {
                $('#orientation-value').text('');
              }
              if (obj.aspect) {
                $('#aspect-value').text(obj.aspect);
              } else {
                $('#aspect-value').text('');
              }
              if (obj.slope) {
                $('#slope-value').text(obj.slope);
              } else {
                $('#slope-value').text('');
              }
              if (obj.ash) {
                $('#ash-value').text(obj.ash);
              } else {
                $('#ash-value').text('');
              }
            });
          }
        );
      }
    });";
    
    return $htmlTemplate;
  }

  /*
   * When creating a plot, we need the plot location record to hold its parent square in location.parent_id.
   * To do this, the calling page provides the square id in the $_GET which we then place in a hidden field on the page to be 
   * processed during submission.
   * 
   */
  public static function insert_parent_square_id_into_location_record($auth, $args, $tabalias, $options, $path) {
    //Don't run the code unless the page in in add mode.
    if (!empty($_GET['parent_square_id'])) {
      //Save the hidden field for processing during submission
      $hiddenField = '<div>';
      $hiddenField  .= "  <INPUT TYPE=\"hidden\" VALUE=\"".$_GET['parent_square_id']." id=\"location:parent_id\" name=\"location:parent_id\">";
      $hiddenField  .= '</div></br>';
      return $hiddenField;
    }
  }
  
  /*
   * This function performs two tasks,
   * 1. In view mode (summary mode) it allows the page to be displayed with read-only data.
   * 2. When the user is creating a plot, it copies the grid reference of the plot into the location name field to be saved as the plot name.
   */
  public static function grid_ref_as_location_name_and_make_summary_mode($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('data_entry_helper'));
    global $indicia_templates;
    // put each param control in a div, this allows us to set the fields on the page to read-only when in view mode.
    $indicia_templates['prefix']='<div id="container-{fieldname}" class="param-container read-only-capable">';
    $indicia_templates['suffix']='</div>';
    //Hide the location name field as this will be auto-populated with the grid reference when the user submits
    data_entry_helper::$javascript = "$('#container-location\\\\:name').hide();\n";
    data_entry_helper::$javascript .= "$('#entry_form').submit(function() { $('#location\\\\:name').val($('#imp-sref').val());});\n";
    //Make the page read-only in summary mode
    if (!empty($_GET['summary_mode']) && $_GET['summary_mode']==true) {
      data_entry_helper::$javascript .= "$('.read-only-capable').find('input, textarea, text, button, select').attr('disabled','disabled');\n"; 
      data_entry_helper::$javascript .= "$('.page-notice, .indicia-button').hide();\n"; 
    }
  }
  
  /*
   * When the plot details page is in edit/view mode we display a list of species recorded against the plot.
   */
  public static function known_taxa_summary($auth, $args, $tabalias, $options, $path) {
    if (!empty($_GET['location_id'])) {
      iform_load_helpers(array('report_helper'));
      return report_helper::report_grid(array(
        'id'=>'taxa-summary',
        'readAuth' => $auth['read'],
        'itemsPerPage'=>10,
        'dataSource'=>'library/taxa/filterable_explore_list',
        'rowId'=>'id',
        'ajax'=>true,
        'columns'=>array(array('fieldname'=>'taxon_group','visible'=>false),array('fieldname'=>'taxon_group_id','visible'=>false),
                      array('fieldname'=>'first_date','visible'=>false),array('fieldname'=>'last_date','visible'=>false)),
        'mode'=>'report',
        'extraParams'=>array(
            'location_list'=>$_GET['location_id'],
            'website_id'=>$args['website_id']),
      ));
    }
  }
  
  /*
   * When the plot details or square/user administration pages are displayed then we need to display the name of the square.
   * As the square display name is made from the name of the square plus its vice counties, then we need to collect this information from a report.
   */
  public static function get_square_name($auth, $args, $tabalias, $options, $path) {
    //The plot details page use's location_id as its parameter in edit mode
    if (!empty($_GET['location_id'])) {
      $reportOptions = array(
        'dataSource'=>'reports_for_prebuilt_forms/Splash/get_square_name_for_plot_id',
        'readAuth'=>$auth['read'],
        'extraParams' => array('website_id'=>$args['website_id'], 
            'vice_county_location_attribute_id'=>$options['viceCountyLocationAttributeId'], 
            'no_vice_county_found_message'=>$options['noViceCountyFoundMessage'],
            'plot_id'=>$_GET['location_id']),
        'valueField'=>'id',
        'captionField'=>'name'
      );
    }
    //The square/user admin page use's dynamic-location_id as its parameter. Only perform code for this 
    //page if this is present.
    //In add mode, the Plot Details page is given its parent square in the parent_square_id parameter, so use this to get the parent square name.
    if (!empty($_GET['dynamic-location_id'])||!empty($_GET['parent_square_id'])) {
      $reportOptions = array(
        'dataSource'=>'reports_for_prebuilt_forms/Splash/get_square_name_for_square_id',
        'readAuth'=>$auth['read'],
        'extraParams' => array('website_id'=>$args['website_id'], 
            'vice_county_location_attribute_id'=>$options['viceCountyLocationAttributeId'], 
            'no_vice_county_found_message'=>$options['noViceCountyFoundMessage']),
        'valueField'=>'id',
        'captionField'=>'name'
      );
      if (!empty($_GET['dynamic-location_id'])) 
        $reportOptions['extraParams']['square_id']= $_GET['dynamic-location_id'];
      if (!empty($_GET['parent_square_id'])) 
        $reportOptions['extraParams']['square_id']= $_GET['parent_square_id'];
    }
    
    if (!empty($reportOptions)) {
      $squareNameData = data_entry_helper::get_report_data($reportOptions);
      if (!empty($squareNameData[0]['name']))
        return '<div><label>Square name:</label>'.$squareNameData[0]['name'].'</div>';
    }
  }
  
  /*
   * When the user clicks on the map on the plot details page, we calculate a plot square on the map where the south-west corner is the clicked point.
   * The size of the plot square depends on the plot type but it extends north/east along the lat long grid system (as opposed to British National Grid which is at a slight angle).
   * However we cannot calculate points a certain number of metres apart using lat/long because the unit is degrees, so to make the square calculation we need to use the British National Grid 
   * to help as this is in metres. However the BNG is also at a slight angle which makes the situation complicated, the high level algorithm for calculating a grid square is as follows,
   * 1. Get the lat/long value from the point the user clicked on.
   * 2. Take any arbitrary point north of the original point as long as we know it is definitely more than the length of one of the plot square's sides.
   * 3. Convert both these points into british national grid format
   * 4. As the British National Grid is at an angle to lot/long we can make a right angle triangle by getting the 3rd point from the Y British National Grid value of the north point, and getting the 
   * x value from X British National Grid value of the southern point.
   * 5. Now we have the right angle triangle, the hypotenuse is the distance between the southern and northern points. As the third point we calculated has the same X BNG value as the southern point,
   * and the same Y value as the top point and then by looking at the 3 points it is very easy to calculate the length of the adjacent and opposite sites of the triangle.
   * 6. Once we have the adjacent and opposite sites of the triangle, we can calculate the hypotenuse of the triangle in metres. 
   * 7. For the purposes of this explanation let us assume our square will be 10m. If we have calculated the length of the hypotenuse (the distance between our north and southern points) as 100m,
   * then we know that 10m is just 10% of the length of this line.
   * 8. Once we know the percentage, then we can look at the original lat long grid references and work out the number of degrees difference between the points and then find 10 percent of this to
   * get the lat long value of the north-west point of the sqaure.
   * 9. We repeat the above procedure to the east to get the lat long position of the south-east point of the plot square. Once we have 3 of the points, we can work out the lat long position of the north-east point by combining 
   * the lat long grid ref values of the south-east and north-west points.
   * 
   * $options Options array with the following possibilities:<ul>
   * <li><b>squareSizes</b><br/>
   * The length of the plot square associated with each plot type. Mandatory. Comma seperated list in the following format,
   * plot_location_type_id|length_in_metres,plot_location_type_id|length_in_metres.....e.g. 2543|10,2544|10,2545|20,2546|20</li>
   * </ul>
   * 
   */
  public static function draw_map_plot($auth, $args, $tabalias, $options, $path) {
    if (empty($options['squareSizes'])) {
      drupal_set_message('Please fill in the @squareSizes option for the draw_map_plot control');
      return '';
    }
    //The user provides the square sizes associated with the various plot types as a comma seperated option list.
    $squareSizesOptionsSplit=explode(',',$options['squareSizes']);
    //Eash option consists of the following format <plot type id>|<square side lengh>
    //So these options need splitting into an array for use
    foreach ($squareSizesOptionsSplit as $squareSizeOption) {
      $squareSizeSingleOptionSplit = explode('|',$squareSizeOption);
      $squareSizesArray[$squareSizeSingleOptionSplit[0]]=$squareSizeSingleOptionSplit[1];
    }
    $squareSizesJavascriptArray=json_encode($squareSizesArray);
    iform_load_helpers(array('map_helper'));
    //When the user clicks on the map we need to draw the map square. Initialise the map and then add a trigger to it to allow the user
    //to click on the map and then we automatically draw the plot square.
    map_helper::$javascript .= "
    mapInitialisationHooks.push(function (div) {
      indiciaData.mapdiv = div;
      OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
      
        defaultHandlerOptions: {
          'single': true,
          'double': false,
          'pixelTolerance': 0,
          'stopSingle': false,
          'stopDouble': false
        },

        initialize: function(options) {
          this.handlerOptions = OpenLayers.Util.extend(
            {}, this.defaultHandlerOptions
          );
          OpenLayers.Control.prototype.initialize.apply(
            this, arguments
          );
          this.handler = new OpenLayers.Handler.Click(
            this, {
              'click': this.trigger
            }, this.handlerOptions
          );
        },
      
        trigger: function(e) {    
          if ($('#location\\\\:location_type_id').val()) {
            //When map is clicked on then remove previous plot square.
            plotSquareLayer.removeAllFeatures();
            var attributes = {name: 'plot_map'};
            
            var polygon=square_calculator(e.xy);
            //Save the plot square to a hidden field for saving in the database
            $('#imp-boundary-geom').val(polygon);
            if (indiciaData.mapdiv.map.projection.getCode() != indiciaData.mapdiv.indiciaProjection.getCode()) {
              polygon.transform(indiciaData.mapdiv.indiciaProjection, indiciaData.mapdiv.map.projection);
            }
            var feature = new OpenLayers.Feature.Vector(polygon, attributes);
            feature.geometry=polygon;
            plotSquareLayer.addFeatures([feature]);

            var bounds = new OpenLayers.Bounds();
            bounds = feature.geometry.getBounds();
            zoom = indiciaData.mapdiv.map.getZoomForExtent(bounds);
            indiciaData.mapdiv.map.setCenter(bounds.getCenterLonLat(), zoom); 
          } else {
            alert('Please select a plot type before selecting the plot location.');
          }
        }
      });
      
      plotSquareLayer = new OpenLayers.Layer.Vector('Plot Square Layer');
      indiciaData.mapdiv.map.addLayer(plotSquareLayer); 
      var click = new OpenLayers.Control.Click();
      indiciaData.mapdiv.map.addControl(click);";
      //This code is a workaround for an issue where the Plot's square draws correctly but
      //the grid reference click point appears in the wrong place when my custom code to automatically 
      //zoom into the Plot Square is present.
      //The click point would display in the same pixel position as it was clicked before the zoom occurred.
      //In the code below we force the custom trigger click.activate() to be activated before the standard
      //olControlClickSref control as items which are activated last are peformed first, this forces
      //the standard click code to run before my custom code which performs the zoom. The click point then works with the zoom.
      map_helper::$javascript .= "
      $.each(indiciaData.mapdiv.map.controls, function(idx, ctrl) {
        if (ctrl.displayClass==='olControlClickSref') {
          ctrl.deactivate();
        }
      });
      click.activate();
      $.each(indiciaData.mapdiv.map.controls, function(idx, ctrl) {
        if (ctrl.displayClass==='olControlClickSref') {
          ctrl.activate();
        }
      });
    });";
    //If the user changes the plot type, remove the plot from the page as its size might now be incorrect
    map_helper::$javascript .= "$('#location\\\\:location_type_id').change(function() {  
      plotSquareLayer.removeAllFeatures();
      $('#imp-boundary-geom').val('');
    });";
    //Do not allow submission if there is no plot set
    data_entry_helper::$javascript .= "$('#entry_form').submit(function() { if (!$('#imp-boundary-geom').val()) {alert('Please click on the map to specify a square.'); return false; }});\n";
  
    //This is the code that creates the plot square. It is called by the trigger when the user clicks on the map.
    //Firstly get the initial south-west point in the various grid reference formats (4326=lat long, 27700 = British National Grid)
    map_helper::$javascript .= "
    function square_calculator(eventXY) {
      var squareSizesJavascriptArray=$squareSizesJavascriptArray;
      var xy3857 = indiciaData.mapdiv.map.getLonLatFromPixel(eventXY),
      pt3857 = new OpenLayers.Geometry.Point(xy3857.lon, xy3857.lat),
      InitialClickPoint4326 = pt3857.clone().transform(indiciaData.mapdiv.map.projection, new OpenLayers.Projection('epsg:4326')),
      InitialClickPoint27700 = pt3857.clone().transform(indiciaData.mapdiv.map.projection, new OpenLayers.Projection('epsg:27700'));";
    
    //Get an arbitrary point north of the original long, lat position. In our case this is 1 degree north but the amount doesn't really matter. Then convert to British National Grid
    map_helper::$javascript .= "
      northTestPointLatLon = InitialClickPoint4326.clone();
      northTestPointLatLon.y = northTestPointLatLon.y+1;
      northTestPoint27700 = northTestPointLatLon.clone().transform('epsg:4326', new OpenLayers.Projection('epsg:27700'));";
      
    //Get a point the is at right angles to the original point and the arbitrary point north.
    //We can do this by taking the british national grid x value of the south point and combining it with the 
    //the y value of the north point. This will then create a right-angle triangle as the British National Grid is at an angle
    //compared to long lat.
    map_helper::$javascript .= "
      northRightAnglePoint27700 = northTestPoint27700.clone();
      northRightAnglePoint27700.x = InitialClickPoint27700.x;
    
      //We then work out the side lengths and angle of the right-angled triangle
      var opposite = northTestPoint27700.x - northRightAnglePoint27700.x;
      var adj = northRightAnglePoint27700.y - InitialClickPoint27700.y;
      var gridAngle = Math.atan(opposite/adj);
      //The hypotenuse is the distance north along the longitude line to our test point but in British National Grid 27700 metres.
      var hyp = adj/Math.cos(gridAngle);";
      
    //As we now know the length in metres between the south point and our arbitrary north point (the hypotenuse), 
    //we can now use the percent value to work out the Y distance in Lat Long 4326 format for the corner of the square above the original click point.
    //This is because we know the distance in 4326 degrees, but now we also know the percentage the square length (at the time of writing 10m or 20m) is along the line.
    map_helper::$javascript .= "
      var hypmetrePercent = squareSizesJavascriptArray[$('#location\\\\:location_type_id').val()]/hyp;
      var actualSquareNorthWestPoint4326= InitialClickPoint4326.clone();
      actualSquareNorthWestPoint4326.y = InitialClickPoint4326.y+((northTestPointLatLon.y-InitialClickPoint4326.y)*hypmetrePercent);";
    
    //Next we need to use the same technique along the side of the square. We just need to use X values rather than Y values.
    map_helper::$javascript .= "
      eastTestPointLatLon = InitialClickPoint4326.clone();
      eastTestPointLatLon.x = eastTestPointLatLon.x+1;
      eastTestPoint27700 = eastTestPointLatLon.clone().transform('epsg:4326', new OpenLayers.Projection('epsg:27700'));

      eastRightAnglePoint27700 = eastTestPoint27700.clone();
      eastRightAnglePoint27700.y = InitialClickPoint27700.y;
    
      var opposite =  eastRightAnglePoint27700.y-eastTestPoint27700.y;
      var adj = eastRightAnglePoint27700.x - InitialClickPoint27700.x;
      var gridAngle = Math.atan(opposite/adj);
      //The hypotenuse is the distance north along the latitude line to our east test point but in British National Grid 27700 metres.
      var hyp = adj/Math.cos(gridAngle);
   
      var hypmetrePercent = squareSizesJavascriptArray[$('#location\\\\:location_type_id').val()]/hyp;
    
      var actualSquareSouthEastPoint4326= InitialClickPoint4326.clone();
      actualSquareSouthEastPoint4326.x = InitialClickPoint4326.x+((eastTestPointLatLon.x-InitialClickPoint4326.x)*hypmetrePercent);";
    
    //As we know 3 of the plot corners, we can work out the 4th and then convert the plot square back into a form the map can understand  
    map_helper::$javascript .= "   
      actualSquareNorthEastPoint4326 = actualSquareSouthEastPoint4326.clone();
      actualSquareNorthEastPoint4326.y = actualSquareNorthWestPoint4326.y;

      mercOriginal = OpenLayers.Layer.SphericalMercator.forwardMercator(InitialClickPoint4326.x,InitialClickPoint4326.y);
      mercNorth = OpenLayers.Layer.SphericalMercator.forwardMercator(actualSquareNorthWestPoint4326.x,actualSquareNorthWestPoint4326.y);
      mercEast = OpenLayers.Layer.SphericalMercator.forwardMercator(actualSquareSouthEastPoint4326.x,actualSquareSouthEastPoint4326.y);  
      mercNorthEast = OpenLayers.Layer.SphericalMercator.forwardMercator(actualSquareNorthEastPoint4326.x,actualSquareNorthEastPoint4326.y);

      var polygonMetadata = 'POLYGON(('+mercOriginal.lon+' '+mercOriginal.lat+','+mercNorth.lon+' '+mercNorth.lat+','+mercNorthEast.lon+' '+mercNorthEast.lat+','+mercEast.lon+' '+mercEast.lat+'))';
      var polygon=OpenLayers.Geometry.fromWKT(polygonMetadata);
      return polygon;
    }";
  }

}
?>
