var clear_map_features, plot_type_dropdown_change;

(function($) {
  clear_map_features = function clear_map_features() {
    var mapLayers = indiciaData.mapdiv.map.layers;
    for(var a = 0; a < mapLayers.length; a++ ){
      if (mapLayers[a].CLASS_NAME=='OpenLayers.Layer.Vector') {
        mapLayers[a].removeAllFeatures();
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
      indiciaData.plotWidthLength = indiciaData.squareSizes[$('#location\\:location_type_id').val()][0]+ ',' + indiciaData.squareSizes[$('#location\\:location_type_id').val()][1];
    }
  }
})(jQuery);
