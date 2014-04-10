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
    if (indiciaData.pssMode) {
      //Rectangular PSS plots have the grid reference in the middle of the plot
      if ($('#location\\:location_type_id option:selected').text()!='Linear'&&$('#location\\:location_type_id option:selected').text()!='Vertical') {
        indiciaData.clickMiddleOfPlot=true;
      }
      //In PSS mode, the plot side lengths are placed into fields on the screen,
      //for Splash we look up the sizes from the form structure options.
      //The plot itself is then drawn by indiciaMapPanel
      $('#locAttr\\:'+indiciaData.plotWidthAttrId).val(indiciaData.squareSizes[$('#location\\:location_type_id').val()][0]);
      $('#locAttr\\:'+indiciaData.plotLengthAttrId).val(indiciaData.squareSizes[$('#location\\:location_type_id').val()][1]);
    } else {
      //Some Splash plot types use a free drawn polygon as the plot.
      if (inArray($('#location\\:location_type_id option:selected').text(),indiciaData.freeDrawPlotTypeNames)) {     
        $('.olControlDrawFeaturePolygonItemActive').show();
        $('.olControlDrawFeaturePolygonItemInactive').show();
        indiciaData.mapdiv.settings.clickForPlot=false;
        indiciaData.mapdiv.settings.click_zoom=false;
      } else {
        $('.olControlDrawFeaturePolygonItemActive').hide();
        $('.olControlDrawFeaturePolygonItemInactive').hide();
        //Otherwise for Splash, the plot is drawn as a rectangle.
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
        indiciaData.plotWidthLength = indiciaData.squareSizes[$('#location\\:location_type_id').val()][0]+ ',' + indiciaData.squareSizes[$('#location\\:location_type_id').val()][1];
      }
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
