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
   * If no options are supplied then the only validation applied is a check to make sure the plot is filled in.
   *
   * $options Options array with the following possibilities:<ul>
   * <li><b>treeCountMode</b><br/>
   * If true then an additional check is made to make sure at least 1 tree has been entered.</li>
   * <li><b>treeGridRefAndEpiphyteMode</b><br/>
   * If true then all validation applies</li>
   *
   * Validator for Splash Epiphyte survey input forms, validates the following:
   * - That a plot is filled in.
   * - The details of at least one tree have been entered (treeCountMode and treeGridRefAndEpiphyteMode)
   * - The user hasn't entered an Epiphyte presence for any trees that don't exist (treeGridRefAndEpiphyteMode)
   * - The user has filled in grid references for all trees (this doesn't use the built in mandatory field functionality of Indicia
   * as the system would flag the Epiphytes as not containing a grid references when they actually shouldn't) - (treeGridRefAndEpiphyteMode)
   */
  public static function splash_validate($auth, $args, $tabAlias, $options) {
    if (empty($options['treeOccurrenceAttrIds']) && !empty($options['treeGridRefAndEpiphyteMode']) && $options['treeGridRefAndEpiphyteMode']===true) {
      drupal_set_message('Please fill in the @treeOccurrenceAttrIds option for the splash_validate control.
                          This should be a comma seperated list of attribute ids that hold the Epiphyte counts for trees.');
      return '';
    }
    
    //The validator that makes sure the user hasn't entered a Epiphyte presence for a tree that doesn't exist works as follows.
    //- Cycle through each the occurrence attribute that holds the presence boolean for trees that haven't been entered on the trees grid (taking into account trees can be deleted)
    //- Use jQuery to cycle through each instance of the attribute on the page (effectively check all rows on both grids)
    //- Make a count of all attributes that are found as present (checked), taking into account rows can be deleted. As we are only checking the cells for trees not on the trees grid
    //if the error count is above 0 then we know there are problems on the page
    data_entry_helper::$javascript .= "
    $('<span class=\"deh-required\">*</span>').insertAfter('.scGridRef');
    $('#save-button').click(function(){
      if ($('#imp-location').val()==='<Please select>'||$('#squares-select-list').val()==='<Please select>'||
          $('#imp-location').val()===''||$('#squares-select-list').val()==='') {
        alert('Please select a plot before submitting.');
        return false;
      }";
    if ((!empty($options['treeCountMode']) && $options['treeCountMode']===true)||
        (!empty($options['treeGridRefAndEpiphyteMode']) && $options['treeGridRefAndEpiphyteMode']===true)) {
      data_entry_helper::$javascript .= "    
      //Take 1 off because there is an empty row on the grid.
      var treesCount = $('#trees').find('.scTaxonCell:not([disabled])').length - 1;
      if (treesCount < 1) {
        alert('Please enter the details of at least 1 tree.');
        return false;
      }";
    }
    if (!empty($options['treeGridRefAndEpiphyteMode']) && $options['treeGridRefAndEpiphyteMode']===true) {
      $treeOccurrenceAttrIds=explode(',',$options['treeOccurrenceAttrIds']);
      data_entry_helper::$javascript .= "
      var treeOccurrenceAttrIds = ".json_encode($treeOccurrenceAttrIds).";
      if ($('.scGridRef[value=]').length>=3) {
        alert('Please fill in the grid reference field for all trees.');
        return false;
      }
      var epiphyteValidateResult;
      epiphyteValidateResult = runValidateOnEpiphyteGrid(treesCount,treeOccurrenceAttrIds);
      if (epiphyteValidateResult>0) {
        alert('You have entered an Epiphyte presence for a tree that doesn\'t exist in the trees grid. ' +
        'Number of problems found = ' + epiphyteValidateResult);
        return false;
      }";
    }
    data_entry_helper::$javascript .= "
      $('#entry_form').submit();
    });";
    
    if (!empty($options['treeGridRefAndEpiphyteMode']) && $options['treeGridRefAndEpiphyteMode']===true) {
    data_entry_helper::$javascript .= "
    function runValidateOnEpiphyteGrid(treesCount,treeOccurrenceAttrIds) {
      var treeIdxToCheck;
      var issueCount=0;
      for (treeIdxToCheck=treesCount; treeIdxToCheck<treeOccurrenceAttrIds.length;treeIdxToCheck++) {
        var result = $('[id*=occAttr\\\\:'+treeOccurrenceAttrIds[treeIdxToCheck]+']').each(function(){
          //Need to check if parent is not disabled as we want to check if the row has been deleted by the user, only
          //count issue if the row not deleted. Don't check cell itself as it is re-enabled just before submission to
          //allow value to be submitted.
          if ($(this).is(':checked') && $(this).parent().attr('disabled')!=='disabled') {
            issueCount++;
          }
        });
      }  
      return issueCount;
    }
    ";
    }
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
   * <li><b>userSquareAttrId</b><br/>
   * The ID of the person attribute that holds the user squares.</li>
   * <li><b>orientationAttributeId</b><br/>
   * The location attribute id that holds a plot's Orientation</li>
   * <li><b>aspectAttributeId</b><br/>
   * The location attribute id that holds a plot's Aspect</li>
   * <li><b>slopeAttributeId</b><br/>
   * The location attribute id that holds a plot's Slope</li>
   * <li><b>ashAttributeId</b><br/>
   * The location attribute id that holds a plot's % Ash Coverage</li>
   * <li><b>privatePlotAttrId</b><br/>
   * Optional attribute for the location attribute id which holds whether a plot is private. If supplied then when a private plot is selected
   * as the location then all occurrences are set to have a sensitivity_precision=10000</li>
   * <li><b>rowInclusionCheckModeHasData</b><br/>
   * Optional. Supply this as true if the species grid is in rowInclusionCheck=hasData mode and you are using the privatePlotAttrId option.
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
    if (empty($options['userSquareAttrId'])) {
      drupal_set_message('Please fill in the @userSquareAttrId option for the splash_location_select control');
      return '';
    }
    $coreSquareLocationTypeId=$options['coreSquareLocationTypeId'];
    $additionalSquareLocationTypeId=$options['additionalSquareLocationTypeId'];
    $currentUserId=hostsite_get_user_field('indicia_user_id');
    $viceCountyLocationAttributeId=$options['viceCountyLocationAttributeId'];
    $noViceCountyFoundMessage=$options['noViceCountyFoundMessage'];
    $userSquareAttrId=$options['userSquareAttrId'];
    $extraParamForSquarePlotReports=array(
                        'core_square_location_type_id'=>$coreSquareLocationTypeId,
                        'additional_square_location_type_id'=>$additionalSquareLocationTypeId,
                        'current_user_id'=>$currentUserId,
                        'vice_county_location_attribute_id'=>$viceCountyLocationAttributeId,
                        'no_vice_county_found_message'=>$noViceCountyFoundMessage,
                        'user_square_attr_id'=>$userSquareAttrId);
    $reportOptions = array(
      'dataSource'=>'reports_for_prebuilt_forms/Splash/get_my_squares_that_have_plots',
      'readAuth'=>$auth['read'],
      'mode'=>'report',
      'extraParams' => $extraParamForSquarePlotReports
    );
    //In PSS/NPMS we don't show the Vice County in the label.
    if (!empty($reportOptions['extraParams'])&&!empty($options['pssMode'])&&$options['pssMode']===true) {
      $reportOptions['extraParams']=array_merge($reportOptions['extraParams'],['pss_mode'=>true]);
      data_entry_helper::$javascript .= "$('#imp-sref').attr('readonly','readonly');";
    }
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
        'blankText'=>'<Please select>',
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
      if (!empty($options['plotNumberAttrId']))
        $options['extraParams']['plot_number_attr_id']=$options['plotNumberAttrId'];
      //Create the drop-down for the plot
      $location_list_args = array_merge(array(
          'label'=>lang::get('LANG_Location_Label'),
          'view'=>'detail'
      ), $options);
      $r .= data_entry_helper::location_select($location_list_args);
      //Create the mini report, not currently required on PSS site
      if (empty($options['pssMode']))
        $r .= self::plot_report_panel($auth,$options);
      //If an attribute holding whether plots are private is supplied, then we want to return
      //whether the selected plot is private and set the occurrence sensitivity_precision appropriately
      if (!empty($options['privatePlotAttrId'])) {
        $extraParamForSquarePlotReports=array_merge($extraParamForSquarePlotReports,array('private_plot_attr_id'=>$options['privatePlotAttrId'],'only_return_private_plots'=>true));
        //When the page initially loads, collect all the private plots that can be selected by the user, rather than
        //load whether the plot is private when each selection is made.
        $myPlotsAndSquares = data_entry_helper::get_report_data(array(
          'dataSource'=>'reports_for_prebuilt_forms/Splash/get_my_squares_and_plots',
          'readAuth'=>$auth['read'],
          'extraParams'=>$extraParamForSquarePlotReports
        ));
        $privatePlots=array();    
        foreach ($myPlotsAndSquares as $locationDataItem) {
          $privatePlots[]=$locationDataItem['id'];
        }
        //Need option to tell the system if the species grid has rowInclusionCheck=hasData, and we are setting the occurrences
        //sensitivity_precision for occurrences when a plot is private.
        //This is because the way the system detects if an occurrence is present is different.
        if (!empty($options['rowInclusionCheckModeHasData']) && $options['rowInclusionCheckModeHasData']==true) {
          $rowInclusionCheckModeHasData='true';
        } else {
          $rowInclusionCheckModeHasData='false';
        }
        if (!empty($_GET['sample_id']))
          $editMode='true';
        else
          $editMode='false';
        data_entry_helper::$javascript .= '
        private_plots_set_precision('.json_encode($privatePlots).','.$rowInclusionCheckModeHasData.','.$editMode.');
        ';
      }
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
      'dataSource'=>'reports_for_prebuilt_forms/Splash/get_square_details_for_square_id',
      'readAuth'=>$auth['read']
    );  
    //Report that will return the type of the square selected by the user
    data_entry_helper::$javascript .= "indiciaData.squareReportRequest='".
       report_helper::get_report_data($reportOptions)."';\n";
    
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
      <h5>Details</h5>
      <div id='field ui-helper-clearfix'>
        <span><b>Square Type: </b></span><span id='square-type-value'></span></br>
        <span><b>Plot Type: </b></span><span id='plot-type-value'></span></br>
        <span><b>Plot Description: </b></span><span id='plot-description-value'></span></br>
        <span><b>Vice County: </b></span><span id='vice-county-value'></span></br>
        <span><b>Orientation: </b></span><span id='orientation-value'></span></br>
        <span><b>Aspect: </b></span><span id='aspect-value'></span></br>
        <span><b>Slope: </b></span><span id='slope-value'></span></br>
        <span><b>% Ash cover: </b></span><span id='ash-cover-value'></span></br>
      </div>
    </div></br>";
    //When the square or plot is changed or the page is loaded then get the data about the square/plot from reports and then
    //place it into the mini report html template using jQuery.
    data_entry_helper::$javascript .= "
    $('#squares-select-list').ready(function() {
      loadMiniSquareReport();
    });
    $('#squares-select-list').change(function() {
      loadMiniSquareReport();
    });
    function loadMiniSquareReport() {
      if ($('#squares-select-list').val()) {
        var squareReportRequest = indiciaData.squareReportRequest
        + '&square_id=' + $('#squares-select-list').val()
        + '&core_square_location_type_id=' + ".$options['coreSquareLocationTypeId']."
        + '&callback=?';
        $.getJSON(squareReportRequest,
          null,
          function(response, textStatus, jqXHR) {
            if (response[0].type) {
              $('#square-type-value').text(response[0].type);
            } else {
              $('#square-type-value').text('');
            }
          }
        );
      }
    }
    $('#imp-location').ready(function() {
      loadMiniPlotReport();
    });
    $('#imp-location').change(function() {
      loadMiniPlotReport();
    });
    function loadMiniPlotReport() {
      if ($('#imp-location').val()==='<Please select>') {
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
        + '&plot_id='+ $('#imp-location').val() + '&callback=?';
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
    }";
    
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
    data_entry_helper::$javascript .= "$('#container-location\\\\:name').hide();\n";
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
        'dataSource'=>'reports_for_prebuilt_forms/Splash/get_square_details_for_square_id',
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
    //In PSS/NPMS we don't show the Vice County in the label.
    if (!empty($reportOptions['extraParams'])&&!empty($options['pssMode'])&&$options['pssMode']===true) {
      $reportOptions['extraParams']=array_merge($reportOptions['extraParams'],['pss_mode'=>true]);
    }
    //Make the name of the square a link to the maintain square page
    if (!empty($reportOptions)) {
      $squareNameData = data_entry_helper::get_report_data($reportOptions);
      if (!empty($squareNameData[0]['name'])) {
        //Use user supplied option if present
        if (!empty($options['label']))
          $label=$options['label'];
        else
          $label='Square Name';
        $urlParam=array('location_id'=>$squareNameData[0]['id']);
        return '<div><label>'.$label.':</label><a href="'.
            url($options['squareDetailsPage'], array('query'=>$urlParam)).
            '">'.$squareNameData[0]['name'].'</a></div>';
      }
    }
  }
 
  /*
   * For some plot types we simply provide the user with a free drawing tool (drawPolygon) to draw the plot.
   * For Plot Squares/Rectangles plot types, when the user clicks on the map on the plot details page, we calculate a plot square on the map where the south-west corner is the clicked point
   * or for PSS the middle is the click point.
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
   * 2543|10|20,2544|10|20,2545|0,2546|20.
   * The first number is the plot location type, the second is the rectangle width and the third is the length.
   * If only two numbers are specified then the plot will be a square when the side length matches the second number.
   * For plots where drawPolygon is used, the size should be 0 e.g. 2545|0 in the example above</li>
   * </ul>
   *
   */
  public static function draw_map_plot($auth, $args, $tabalias, $options, $path) {
    drupal_add_js(iform_client_helpers_path().'prebuilt_forms/extensions/splash_extensions.js');
    if (empty($options['squareSizes'])) {
      drupal_set_message('Please fill in the @squareSizes option for the draw_map_plot control');
      return '';
    }
    iform_load_helpers(array('map_helper'));
    //Array to hold the plot width and length for Splash
    map_helper::$javascript .= "indiciaData.plotWidthLength='';\n";
    //Some Splash plot types use the polygon tool to draw the plot as any shape, specify the plot types and pass to Javascript.
    if (!empty($options['freeDrawPlotTypeNames']))
      map_helper::$javascript .= "indiciaData.freeDrawPlotTypeNames=".json_encode(explode(',',$options['freeDrawPlotTypeNames'])).";";
    //The user provides the square sizes associated with the various plot types as a comma seperated option list.
    $squareSizesOptionsSplit=explode(',',$options['squareSizes']);
    //Eash option consists of the following formats
    //<plot type id>|<square side lengh> or <plot type id>|<rectangle width>|<rectangle length> or <plot type id>|0 (for drawPolygon plots)
    //So these options need splitting into an array for use
    foreach ($squareSizesOptionsSplit as $squareSizeOption) {
      $squareSizeSingleOptionSplit = explode('|',$squareSizeOption);
      //The user can supply the options for the plot in two formats, like this,
      //<location_type_id>|<number>,<location_type_id>|<number>...
      //Or like this,
      //<location_type_id>|<number>|<number>,<location_type_id>|<number>|<number>...
      //In code, both formats are treated the same way, if the second number is missing, the use the first number twice
      if (empty($squareSizeSingleOptionSplit[2]))
        $squareSizesArray[$squareSizeSingleOptionSplit[0]]=array($squareSizeSingleOptionSplit[1],$squareSizeSingleOptionSplit[1]);
     else
        $squareSizesArray[$squareSizeSingleOptionSplit[0]]=array($squareSizeSingleOptionSplit[1],$squareSizeSingleOptionSplit[2]);
    }    
    //Javascript needs to know the square sizes for each location type (note that squares can actually be rectangles now code is extended for PSS project)
    $squareSizesForJavascript=json_encode($squareSizesArray);
    map_helper::$javascript .= "indiciaData.squareSizes=$squareSizesForJavascript;\n";
    if (!empty($options['pssMode'])) {
      //In PSS, the size of the plot types are displayed in fields on screen.
      map_helper::$javascript .= "indiciaData.plotWidthAttrId='".$options['plotWidthAttrId']."';\n";
      map_helper::$javascript .= "indiciaData.plotLengthAttrId='".$options['plotLengthAttrId']."';\n";
      map_helper::$javascript .= "indiciaData.pssMode=true;\n";
    } else {
      map_helper::$javascript .= "indiciaData.noSizeWarning='Please select plot type from the drop-down.';\n";
    }
    //In edit mode, we need to manually load the plot geom
    map_helper::$javascript .= "$('#imp-boundary-geom').val($('#imp-geom').val());\n";
    //On NPMS/PSS system there is a checkbox for enhanced mode (when this isn't selected, plots are not configurable and default to a 3 x 3 square.
    //Note that on splash there is no enhanced mode so plots are fully configurable.
    if (!empty($options['enhancedModeCheckboxAttrId']))
      map_helper::$javascript .= "indiciaData.enhancedModeCheckboxAttrId=".$options['enhancedModeCheckboxAttrId'].";\n";
    //On PSS/NPMS non-enhanced mode the user can define some attributes that should be hidden from view.
    //Comma separated list.
    if (!empty($options['hideLocationAttrsInSimpleMode']))
      map_helper::$javascript .= "indiciaData.hideLocationAttrsInSimpleMode='".$options['hideLocationAttrsInSimpleMode']."';\n";
    //If enhanced mode is toggled, then clear the map and also run the code as if the plot type has changed.
    //This allows the plot drawing to be reset for a new mode.
    map_helper::$javascript .= "  
    $('#locAttr\\\\:'+indiciaData.enhancedModeCheckboxAttrId).change(function() {  
      clear_map_features();
      plot_type_dropdown_change();
    });";
    //If you change the location type then clear the features already on the map
    //If no location type is selected, then don't provide the plot drawing code with plot size details, this way it automatically warns the user  
    map_helper::$javascript .= " 
    $('#location\\\\:location_type_id').change(function() {
      clear_map_features();
      if ($(this).val()) {
        plot_type_dropdown_change();
      } else {
        indiciaData.plotWidthLength='';
        $('#locAttr\\\\:'+indiciaData.plotWidthAttrId).val('');
        $('#locAttr\\\\:'+indiciaData.plotLengthAttrId).val('');
      }
    });
    //Don't use $(document).ready as that fires before the indiciaData.mapdiv is setup
    $(window).load(function() {
      plot_type_dropdown_change();
      if (!$('#location\\\\:location_type_id').val()) {
        indiciaData.plotWidthLength='';
        $('#locAttr\\\\:'+indiciaData.plotWidthAttrId).val('');
        $('#locAttr\\\\:'+indiciaData.plotLengthAttrId).val('');
      }
      //As requested by client, stop return submitting form when spatial reference field is focussed
      document.getElementById('imp-sref').addEventListener('keypress', function(event) {
        if (event.keyCode == 13) {
          event.preventDefault();
        }
      })
    });\n";
    //Do not allow submission if there is no plot set
    data_entry_helper::$javascript .= '
    $("#save-button").click(function() { 
      if (!$("#imp-boundary-geom").val()) {
        alert("Please select a plot type and create a plot before continuing."); 
        return false; 
      } else { 
        $("#entry_form").submit(); 
      }
    });';
  }
 
  /*
   * When the administrator allocates squares to a user, allow the user to enter a mileage value
   * and then reload the screen only showing squares which are within that distance of the user's post code.
   */
  public static function postcode_distance_limiter($auth, $args, $tabalias, $options, $path) {
    $r='';
    //When then screen loads, attempt to add a point to the map showing the user's post code (which is in the $_GET).
    data_entry_helper::$javascript.="
      jQuery(document).ready(function($) {
      mapInitialisationHooks.push(function (div) {
           //Put into indicia data so we can see the map div elsewhere
          indiciaData.mapdiv = div;
          if (indiciaData.postCodeGeom) {
            var feature = new OpenLayers.Feature.Vector(OpenLayers.Geometry.fromWKT(indiciaData.postCodeGeom));
            indiciaData.mapdiv.map.editLayer.addFeatures([feature]);  
          }
      });
    });
    ";
    //Once the limiter is applied, the post code geom is passed to the URL and so is the indicia user id, so we need to pick these up from the URL
    if (!empty($_GET['dynamic-post_code_geom'])) {
      data_entry_helper::$javascript.="
        indiciaData.postCodeGeom='".$_GET['dynamic-post_code_geom']."';
      ";
    }
    if (!empty($_GET['dynamic-the_user_id']))
      $indiciaUserId = $_GET['dynamic-the_user_id'];
    else
      $indiciaUserId=0;
    //If the page is loaded without a user id at all, it means the user will be working to see which user squares are closest
    //to their own post code.
    if (empty($postCode) && function_exists('hostsite_get_user_field') && hostsite_get_user_field('field_indicia_post_code'))
      $postCode=hostsite_get_user_field('field_indicia_post_code');
    if (!empty($options['label']))
      $buttonLabel=$options['label'];
    else 
      $buttonLabel='Get Squares';
    //Only show the post code limiter if there is a post code to actually use as the origin point.
    if (!empty($postCode)) {
      data_entry_helper::$javascript.="
        indiciaData.google_api_key='".data_entry_helper::$google_api_key."';
        var georeferenceProxy='".data_entry_helper::getRootFolder() . data_entry_helper::client_helper_path() . "proxy.php';
        //Reload the screen with the limit applied
        $('#limit-submit').click(function(){
          var postcode='".$postCode."';
          limit_to_post_code(postcode,georeferenceProxy,".$indiciaUserId.");
        });
      ";
      $r.="<div>Only show squares within this distance (miles) of the user's post code.<br><input id='limit-value' type='textbox'><input id='limit-submit' type='button' value='".$buttonLabel."'></div>\n";     
    } else {
      if(!empty($options['noPostCodeMessage']))
        $noPostCodeMessage=$options['noPostCodeMessage'];
      else
        $noPostCodeMessage='Unable to display post code distance limiter control. This is probably because there is no post code on your own user account, or on the account of the person you are editing.';   
      $r.='<div><em>'.$noPostCodeMessage.'</em></div><br>';
    }  
    return $r;
  }
 
  public static function delete_plot($auth, $args, $tabalias, $options, $path) {
    $postUrl = iform_ajaxproxy_url(null, 'location');
    data_entry_helper::$javascript .= "
    delete_plot = function(location_id) {
      var r = confirm('Are you sure you want to delete this plot?');
      if (r == true) {
      $.post('$postUrl',
        {\"website_id\":".$args['website_id'].",\"id\":location_id, \"deleted\":\"t\"},
        function (data) {
          if (typeof data.error === 'undefined') {
            location.reload();
          } else {
            alert(data.error);
          }
        },
        'json'
      );
      } else {
        return false;
      }
    }\n";
  }
  
  /* Approve a user/square allocation.
   * Squares need approval if the updated_by_id on the allocation record (person_attribute_value) is the same as the user the allocation is intended for (i.e. they allocated it to themselves)
   * The approval simply sets the updated_by_id on the record to the same id as the user who is doing the approval.
   * This also means we need a message on screen that warns the user that they can't approve a square/user allocation record that is 
   * intended for themselves.
   */
  public static function approve_allocation($auth, $args, $tabalias, $options, $path) {
  global $base_url;
  global $user;
  if (function_exists('hostsite_get_user_field')) {
    data_entry_helper::$javascript .= "indiciaData.indicia_user_id = ".hostsite_get_user_field('indicia_user_id').";\n";
  };

  data_entry_helper::$javascript .= "
  indiciaData.baseUrl='".$base_url."';  
  indiciaData.website_id = ".variable_get('indicia_website_id', '').";\n";  
  
  data_entry_helper::$javascript .= "
  approve_allocation= function(id,allocation_updater,allocated_to) {
    if (indiciaData.indicia_user_id===allocated_to) {
      alert('You cannot approve this allocation because you are the user the allocation is intended for.');
      return false;
    }
    var confirmation = confirm('Do you really want to approve the user/square allocation with id '+id+'?');
    if (confirmation) { 
      var s = {
        'website_id':indiciaData.website_id,
        'person_attribute_value:id':id,
        'person_attribute_value:updated_by_id':indiciaData.indicia_user_id
      };
      var postUrl = indiciaData.baseUrl+'/?q=ajaxproxy&index=person_attribute_value';
      $.post(postUrl, 
        s,
        function (data) {
          if (typeof data.error === 'undefined') {
            alert('Square/user allocation approved');
            indiciaData.reports.dynamic.grid_report_grid_0.reload(true);
          } else {
            alert(data.error);
          }
        },
        'json'
      );
    } else {
      return false;
    }
  }\n";
  }
  
  /*
   * Very simple control with a text area to import data,and an upload button.
   * Allows locations (squares) to be attached to people using the person_attribute_values table.
   * Format of data must be
   * <person emai>,<location name>,<location name>,<location name>....(as many as you need)
   * e.g.
   * admin@bb.com,NO1402
   * admin2@abcd.com,NO1402,NO1202,NP1202
   * admin3@abcde.com,NO1402
   * 
   * Duplicates are ignored and result in an alert showing the duplicate record which must be cleared before import continues.
   * 
   * Very simple control that needed to be developed very quickly. Not particularly well optimised as places one record at a 
   * time into the database. However this allowed me to use existing code from the user/sqaure admin page, and as the import will only
   * be done once or twice this won't be an issue.
   * @minimumLocationDate option must be provided to specify a minimum created_on date for squares (tested with format yyyy-mm-dd
   * suh as 2014-5-26 although other formats may work). This means that old squares can be ignored for instance.
   */
  public static function simple_user_square_upload($auth, $args, $tabalias, $options, $path) {
    if (empty($options['minimumLocationDate'])) {
      drupal_set_message('Please enter a @minimumLocationDate option to specify minimum square created_on date to look for');
      return false;
    }
    $minSquareDate=new DateTime($options['minimumLocationDate']);
    $r = '';
    //Need to call this so we can use indiciaData.read
    data_entry_helper::$js_read_tokens = $auth['read'];
    if (!function_exists('iform_ajaxproxy_url'))
      return 'An AJAX Proxy module must be enabled for user sites administration to work.';
    $r .= '<div><form method="post"><textarea id="upload-data" name="upload-data" cols="20" rows="50"></textarea>';
    $r .= '<input type="submit" id="upload-squares" value="Upload"></form></div><br>';
    $postUrl = iform_ajaxproxy_url(null, 'person_attribute_value');
    //If there is data to upload then get the lines of data
    if (!empty($_POST['upload-data']))
     $uploadLines=data_entry_helper::explode_lines($_POST['upload-data']);
    $convertedUploadData=array();
    $convertedUploadIdx=0;
    if (!empty($uploadLines)) {
      //Get existing data to detect duplicates
      $existingPersonAttrVals = data_entry_helper::get_population_data(array(
        'table' => 'person_attribute_value',
        'extraParams' => $auth['read'] + array(),
        'nocache' => true
      )); 
      //Cycle through all the lines in the upload data
      foreach ($uploadLines as $lineIdx=>$uploadLine) {
        //Split each line up into cells, cell 2 (index 1) onwards contain all the squares we are going to attach to people.
        $lineParts=explode(",",$uploadLine);
        $email = $lineParts[0];
        //Get the id of the person to attach squares to
        $personData = data_entry_helper::get_population_data(array(
          'table' => 'person',
          'extraParams' => $auth['read'] + array('email_address' => $email, 'view' => 'detail'),
          'nocache' => true
        )); 
        if (empty($personData[0]['id'])) {
          $personData = data_entry_helper::get_report_data(array(
            'dataSource'=>'reports_for_prebuilt_forms/Splash/get_person_for_email_address',
            'readAuth'=>$auth['read'],
            'extraParams'=>array('email_address' => $email)
          ));
        }
        //Cycle through all the squares we want to attach to a person.
        for ($idx2=1; $idx2<count($lineParts); $idx2++) {
          if (!empty($lineParts[$idx2])) {
            //Get the name of the square to attach and then its id.
            $location = $lineParts[$idx2];
            $locationData = data_entry_helper::get_population_data(array(
              'table' => 'location',
              'extraParams' => $auth['read'] + array('name' => $location, 'view' => 'detail'),
              'nocache' => true
            )); 
            //Save the data ready to import.
            if (!empty($personData[0]['id'])&&!empty($locationData[0]['id'])) {
              $locationCreatedOnDate=new DateTime($locationData[0]['created_on']);
              //Only attach squares if they are newer than the specified minimum created_on option
              if ($locationCreatedOnDate>=$minSquareDate) { 
                $convertedUploadData[$convertedUploadIdx][0]=$personData[0]['id'];
                $convertedUploadData[$convertedUploadIdx][1]=$locationData[0]['id'];
                $convertedUploadIdx++;
              }
            } else {
              drupal_set_message('An upload issue has been detected.');
              if (empty($personData[0]['id']))
                drupal_set_message('Could not upload to person. The following email address was not found '.$email);
              if (empty($locationData[0]['id']))
                drupal_set_message('Could not upload square. The following location was not found '.$location);
        }
      }
        }
      }
      data_entry_helper::$javascript .= "
      var i;
      var i2;
      var uploadLines = ".json_encode($convertedUploadData).";
      var existingPersonAttrVals = ".json_encode($existingPersonAttrVals).";
      var duplicateDetected = false;
      for (i=0; i<uploadLines.length; i++) {
        for (i2=0; i2<existingPersonAttrVals .length; i2++) {
          if (uploadLines[i][1]==existingPersonAttrVals[i2]['value']&&uploadLines[i][0]==existingPersonAttrVals[i2]['person_id']) {
            duplicateDetected=true;
          }
        }
        if (duplicateDetected==false) {
          $.post('$postUrl', 
          {\"website_id\":".$args['website_id'].",\"person_attribute_id\":".$options['mySitesPsnAttrId'].
            ",\"person_id\":uploadLines[i][0],\"int_value\":uploadLines[i][1]},
          function (data) {
            if (typeof data.error !== 'undefined') {
              alert(data.error);
            }              
          },
          'json'
          );
          var emptyObj={};
          emptyObj.value=uploadLines[i][1];
          emptyObj.person_id=uploadLines[i][0];

          existingPersonAttrVals.push(emptyObj);
        } else {
          alert('A duplicate entry upload has been attempted for person id ' + uploadLines[i][0] + ' location id ' + uploadLines[i][1]); 
        }
        duplicateDetected=false;
      }
      alert('Import Complete');";
    }
    return $r;
  }
}
