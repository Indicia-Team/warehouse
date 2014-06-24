var clear_map_features, plot_type_dropdown_change;

(function($) {
  clear_map_features = function clear_map_features() {
    var mapLayers = indiciaData.mapdiv.map.layers;
    for(var a = 0; a < mapLayers.length; a++ ){
      if (mapLayers[a].CLASS_NAME=='OpenLayers.Layer.Vector') {
        mapLayers[a].destroyFeatures()
      }
    };
    $('#imp-boundary-geom').val('');
  }
  
  plot_type_dropdown_change = function plot_type_dropdown_change() {
    indiciaData.clickMiddleOfPlot=false;
    //Some plot types use a free drawn polygon as the plot.
    if (inArray($('#location\\:location_type_id option:selected').text(),indiciaData.freeDrawPlotTypeNames)) {
      $('.olControlDrawFeaturePolygonItemActive').show();
      $('.olControlDrawFeaturePolygonItemInactive').show();
      //if using drawPolygon then we never need the length and width attributes on the screen
      if ($('#locAttr\\:'+indiciaData.plotWidthAttrId).length) {
        $('#locAttr\\:'+indiciaData.plotWidthAttrId).hide();
      }
      if ($('#locAttr\\:'+indiciaData.plotLengthAttrId).length) {
        $('#locAttr\\:'+indiciaData.plotLengthAttrId).hide();
      }
      //If using drawPolygon then we don't draw a plot automatically
      indiciaData.mapdiv.settings.clickForPlot=false;
      indiciaData.mapdiv.settings.click_zoom=false;  
    } else {
      //Otherwise we auto generate the plot rectangle/square, remove the drawPolygon tool
      $('.olControlDrawFeaturePolygonItemActive').hide();
      $('.olControlDrawFeaturePolygonItemInactive').hide();
      //if for some plot types (currently PSS, the plot length/width can be adjusted on screen, show and fill in these fields if they exist
      if ($('#locAttr\\:'+indiciaData.plotWidthAttrId).length) {
        $('#locAttr\\:'+indiciaData.plotWidthAttrId).show();
        $('#locAttr\\:'+indiciaData.plotWidthAttrId).val(indiciaData.squareSizes[$('#location\\:location_type_id').val()][0]);
      }
      if ($('#locAttr\\:'+indiciaData.plotLengthAttrId).length) {
        $('#locAttr\\:'+indiciaData.plotLengthAttrId).show();
        $('#locAttr\\:'+indiciaData.plotLengthAttrId).val(indiciaData.squareSizes[$('#location\\:location_type_id').val()][1]);

      }
      //Need to select the click control by default, hide the draw free polygon tool
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
