var private_plots_set_precision,clear_map_features, plot_type_dropdown_change, limit_to_post_code;

(function($) {
  //If the use selects a private plot, then we need to set the sensitivity precision
  //Requires input form Occurrence Sensitivity option to be on on the edit tab.
  private_plots_set_precision = function (privatePlots, rowInclusionCheckModeHasData, editMode) {
    //If the species grid only includes rows if they have data, then the presence is based on the attributes instead of the
    //presence field.
    var partOfNameForPresenceField;
    if (rowInclusionCheckModeHasData==true) {
      partOfNameForPresenceField=":occAttr:";
      
    } else {
      partOfNameForPresenceField=":present";
    }
    //When using private plots we make use of the occurrence sensitivity functionality, however
    //as the functionality is done in code rather than by the user in this case, hide all the existing
    //fields on the page.
    $("[id*=\"sensitivity\"]").each(function() {
      $(this).hide();
    });
    var startOfKeyHolder;  
    var hiddenName;
    $("#tab-submit").click(function() {
      //Find each which is present
      $("[name*=\""+partOfNameForPresenceField+"\"]").each(function() {;
        if ($(this).val() && $(this).val()!="0") {
          //Get the start of the html name we need to use for the occurrence_sensitivity field
          //Do this by chopping the end off the name of the presence field, which is either a field
          //ending in :present or ending in :occAttr:<num> depending on whether this species grid has
          //row inclusion check set to hasData.
          var numItemsToChop;
          var lastIndex
          //If row inclusion check is hasData and in add mode, then we are checking fields which end in occAttr:<num>
          //So we need to chop off the <num> and the occAttr, otherwise we are just removing the word present
          //If in edit mode, we need to do an additional chop, as the occAttr has an additional occurrence_attribute_values id
          //on the end e.g. occAttr:33:32414
          if (rowInclusionCheckModeHasData==true) {
            if (editMode==true) {
              numItemsToChop=3;
            } else {
              numItemsToChop=2;
            }
          } else {
            numItemsToChop=1;
          }
          startOfKeyHolder=$(this).attr("name");
          for (var i = 0; i<numItemsToChop; i++) {
            lastIndex = startOfKeyHolder.lastIndexOf(":")
            startOfKeyHolder = startOfKeyHolder.substring(0, lastIndex);
          }
          //Adjust the name so it can hold sensitivity_precision
          hiddenName = startOfKeyHolder + ":occurrence:sensitivity_precision"
          //If the selected plot is private then set the sensitivity precision.
          if (inArray($("#imp-location").val(),privatePlots)) {
            $("[name=\""+hiddenName+"\"]").val("10000");
          } else {
            $("[name=\""+hiddenName+"\"]").val("");
          }
        }
      });
      $("#entry_form").submit();      
    });
  }
  
  clear_map_features = function clear_map_features() {
    var mapLayers = indiciaData.mapdiv.map.layers;
    for(var a = 0; a < mapLayers.length; a++ ){
      if (mapLayers[a].CLASS_NAME=='OpenLayers.Layer.Vector') {
        destroyAllFeatures(mapLayers[a], 'zoomToBoundary', true);
      }
    };
    $('#imp-boundary-geom').val('');
  }
 
  plot_type_dropdown_change = function plot_type_dropdown_change() {
    //In simple mode we don't draw a proper plot, the plot is just represented by a pre-defined (non-rotatable) square on the screen
    var simpleModePointSize=4;
    //In NPMS simple (not enahanced mode) the admin user can define a comma separated list
    //of location attributes to hide from view.
    if (indiciaData.hideLocationAttrsInSimpleMode) {
      if ($('#locAttr\\:'+indiciaData.enhancedModeCheckboxAttrId).length&&
          indiciaData.enhancedModeCheckboxAttrId&&!$('#locAttr\\:'+indiciaData.enhancedModeCheckboxAttrId).is(':checked')) { 
        $.each(indiciaData.hideLocationAttrsInSimpleMode.split(','), function(idx, attrToHide) {
          $('#container-locAttr\\:'+attrToHide).hide();
        });
      } else {
        $.each(indiciaData.hideLocationAttrsInSimpleMode.split(','), function(idx, attrToHide) {
          $('#container-locAttr\\:'+attrToHide).show();
        });
      }
    }
    indiciaData.clickMiddleOfPlot=false;
    //Some plot types use a free drawn polygon/Line as the plot.
    if (inArray($('#location\\:location_type_id option:selected').text(),indiciaData.freeDrawPlotTypeNames)) {     
      if (!$('#locAttr\\:'+indiciaData.enhancedModeCheckboxAttrId).length||$('#locAttr\\:'+indiciaData.enhancedModeCheckboxAttrId).is(':checked')) {
        show_polygon_line_tool(true);
        //If using drawPolygon/Line in enhanced mode then we don't draw a plot automatically
        indiciaData.mapdiv.settings.clickForPlot=false;
        indiciaData.mapdiv.settings.click_zoom=false;  
      } else {
        //Otherwise deactivate and hide the line/polygon tool and then select the map point clicking tool.
        show_polygon_line_tool(false);
        $.each(indiciaData.mapdiv.map.controls, function(idx, control) {
          if (control.CLASS_NAME==='OpenLayers.Control.DrawFeature'||control.CLASS_NAME==='OpenLayers.Control.Navigation') {
            control.deactivate();
          }
          if (control.CLASS_NAME==='OpenLayers.Control') {
            control.activate();
          }
        });
        //In linear mode, default to using a big plot point so people can actually see it in simple mode
        $('#locAttr\\:'+indiciaData.plotWidthAttrId).val(simpleModePointSize);
        $('#locAttr\\:'+indiciaData.plotLengthAttrId).val(simpleModePointSize);
        indiciaData.mapdiv.settings.clickForPlot=true;
        indiciaData.mapdiv.settings.click_zoom=true;  
        indiciaData.mapdiv.settings.noPlotRotation=true;
      }
      //if using drawPolygon/drawLine then we never need the length and width attributes on the screen
      if ($('#locAttr\\:'+indiciaData.plotWidthAttrId).length) {
        $('#locAttr\\:'+indiciaData.plotWidthAttrId).hide();
      }
      if ($('#locAttr\\:'+indiciaData.plotLengthAttrId).length) {
        $('#locAttr\\:'+indiciaData.plotLengthAttrId).hide();
      }
    } else {
      //Otherwise we auto generate the plot rectangle/square, remove the drawPolygon/Line tool
      show_polygon_line_tool(false);
      //For some plot types the width and length can be adjusted manually, show and fill in these fields if they exist
      if ($('#locAttr\\:'+indiciaData.plotWidthAttrId).length&&(!$('#locAttr\\:'+indiciaData.enhancedModeCheckboxAttrId).length||$('#locAttr\\:'+indiciaData.enhancedModeCheckboxAttrId).is(':checked'))) {
        $('#locAttr\\:'+indiciaData.plotWidthAttrId).show();
        if ($('#location\\:location_type_id').val()) {
          $('#locAttr\\:'+indiciaData.plotWidthAttrId).val(indiciaData.squareSizes[$('#location\\:location_type_id').val()][0]);
        }
        indiciaData.mapdiv.settings.noPlotRotation=false;
      } else {
        $('#locAttr\\:'+indiciaData.plotWidthAttrId).val(simpleModePointSize);
        $('#locAttr\\:'+indiciaData.plotWidthAttrId).hide();
        indiciaData.mapdiv.settings.noPlotRotation=true;
      }
      if ($('#locAttr\\:'+indiciaData.plotLengthAttrId).length&&(!$('#locAttr\\:'+indiciaData.enhancedModeCheckboxAttrId).length||$('#locAttr\\:'+indiciaData.enhancedModeCheckboxAttrId).is(':checked'))) {
        $('#locAttr\\:'+indiciaData.plotLengthAttrId).show();
        if ($('#location\\:location_type_id').val()) {
          $('#locAttr\\:'+indiciaData.plotLengthAttrId).val(indiciaData.squareSizes[$('#location\\:location_type_id').val()][1]);
        }
        indiciaData.mapdiv.settings.noPlotRotation=false;
      } else {
        $('#locAttr\\:'+indiciaData.plotLengthAttrId).val(simpleModePointSize);
        $('#locAttr\\:'+indiciaData.plotLengthAttrId).hide();
        indiciaData.mapdiv.settings.noPlotRotation=true;
      }
      //In non-enhanced mode in PSS mode, plots are always a non-rotatable square of 4x4.
      //In PSS enhanced mode, their size can be configured manually on the page
      indiciaData.mapdiv.settings.clickForPlot=true;
      indiciaData.mapdiv.settings.click_zoom=true;
      $.each(indiciaData.mapdiv.map.controls, function(idx, control) {
        if (control.CLASS_NAME==='OpenLayers.Control.DrawFeature'||control.CLASS_NAME==='OpenLayers.Control.Navigation') {
          control.deactivate();
        }
        if (control.CLASS_NAME==='OpenLayers.Control') {
          control.activate();
        }
      });
      //Only PSS (NPMS) square plots should be drawn so the click point is in the middle of the plot, otherwise the south-west corner is used.
      //Check that the word linear or vertical does not appear in the selected plot type when setting the clickMiddleOfPlot option.
      if (indiciaData.pssMode
          && ($('#location\\:location_type_id option:selected').text().toLowerCase().indexOf('linear')===-1)
          && ($('#location\\:location_type_id option:selected').text().toLowerCase().indexOf('vertical')===-1)) {
        //Rectangular PSS plots have the grid reference in the middle of the plot
        indiciaData.clickMiddleOfPlot=true;
      }
      //Splash plots get their rectangle sizes from user configurable options which are not displayed on screen
      if (!indiciaData.pssMode)
        indiciaData.plotWidthLength = indiciaData.squareSizes[$('#location\\:location_type_id').val()][0]+ ',' + indiciaData.squareSizes[$('#location\\:location_type_id').val()][1];      
    }
  }
 
  /*
   * The polygon/line tool on the map needs showing or hiding depending on the plot type selected.
   */
  function show_polygon_line_tool(show) {
    if (show===true) {
      $('.olControlDrawFeaturePolygonItemActive').show();
      $('.olControlDrawFeaturePathItemActive').show();
      $('.olControlDrawFeaturePolygonItemInactive').show();
      $('.olControlDrawFeaturePathItemInactive').show();
    } else {
      $('.olControlDrawFeaturePolygonItemActive').hide();
      $('.olControlDrawFeaturePathItemActive').hide();
      $('.olControlDrawFeaturePolygonItemInactive').hide();
      $('.olControlDrawFeaturePathItemInactive').hide();
    }
  }
 
  //Function allows the report to only return squares located a certain distance from a user's
  //post code.
  limit_to_post_code= function (postcode,georeferenceProxy,indiciaUserId) {
    $.ajax({
      dataType: 'json',
      url: georeferenceProxy,
      data: {'url':'https://maps.googleapis.com/maps/api/place/textsearch/json','key':indiciaData.google_api_key, 'query':postcode, 'sensor':'false'},
      success: function(data) {
        var done=false;
        $.each(data.results, function() {  
          if ($.inArray('postal_code', this.types)!==-1) {
            done=true;
            return false;
          }
        });
        if (!done) {
          alert('Sorry there appears to be a problem associated with the post code, so I cannot perform a search using it.');
          return false;
        }
        
        //Only provide one corner of the Post Code area for the report as this
        //simplifies things and doesn't adversely affect functionality
        var southWest = OpenLayers.Layer.SphericalMercator.forwardMercator(data.results[0].geometry.viewport.southwest.lng,data.results[0].geometry.viewport.southwest.lat);
        var postCodePoint = 'POINT('+southWest.lon+' '+southWest.lat+')';
        //Get current URL
        var url = window.location.href.toString().split('?');
        var params = '?';
        if (indiciaUserId!=0) {
          params+="dynamic-the_user_id="+indiciaUserId+'&';
        }
        if (postCodePoint && $('#limit-value').val()) {        
          params+="dynamic-post_code_geom="+postCodePoint+'&';
          params+="dynamic-distance_from_post_code="+($('#limit-value').val()*1609)+'&';
        }
        //Remove the & from the end of the url
        params = params.substring(0, params.length - 1);
        //url[0] is the part of the url excluding parameters
        url[0] += params;
        //Reload screen and submit
        window.location=url[0];
        window.location.href;
        $('#entry_form').submit();
      }
    });
  }
  /*
   * Returns true if an item is found in an array
   */
  function inArray(needle, haystack) {
      var length = haystack.length;
      for(var i = 0; i < length; i++) {
          if(haystack[i] == needle) return true;
      }
      return false;
  }
})(jQuery); 