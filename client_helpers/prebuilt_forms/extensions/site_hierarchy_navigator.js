
jQuery(document).ready(function($) {
  //Setup the initial map layer the user sees.
  indiciaData.initialreportlayer = new OpenLayers.Layer.Vector('Report output');
  mapInitialisationHooks.push(function (div) {
    "use strict";
    //Put into indcia data so we can see the map div elsewhere
    indiciaData.mapdiv = div;
    indiciaData.mapdiv.map.addLayer(indiciaData.initialreportlayer);
    //Initially we just get the first location type. The parent_id is blank as the user hasn't clicked on anything yet.
    $.getJSON(indiciaData.layerReportRequest + '&location_type_id='+indiciaData.layerLocationTypes.shift()+ '&parent_id=',
        null,
        function(response, textStatus, jqXHR) {
          var features=[];
          //add the locations to the map
          $.each(response, function (idx, obj) {
            indiciaData.mapdiv.addPt(features, obj, 'boundary_geom', {"type":"vector"}, obj.id);
          });
          indiciaData.initialreportlayer.addFeatures(features);
        }
    );
  });
});

/* 
 * Load the new locations onto the map when the user clicks on a location
 */
function reload_map_with_sub_sites_for_clicked_feature(features) {
  if (indiciaData.layerLocationTypes.length > 0) {
    //Get the clicked location and id
    //TODO - The location name line needs correcting
    for(var fid in features) {
      var parentId = features[fid].id;
      //var parentName = features[fid].name;
    } 
    //Give the new layer a name 
    //Todo - This needs changing to be name of the clicked location.
    indiciaData.initialreportlayer2 = new OpenLayers.Layer.Vector('Layer' + parentId); 
    indiciaData.mapdiv.map.addLayer(indiciaData.initialreportlayer2);
    //Get the locations for the next location type and the clicked location.
    //TODO - Can re-use code here as same as initial screen load so put into seperate function
    $.getJSON(indiciaData.layerReportRequest + '&location_type_id='+indiciaData.layerLocationTypes.shift()+ '&parent_id='+parentId,
        null,
        function(response, textStatus, jqXHR) {
          var features=[];
          $.each(response, function (idx, obj) {
            indiciaData.mapdiv.addPt(features, obj, 'boundary_geom', {"type":"vector"}, obj.id);
          });
          indiciaData.initialreportlayer2.addFeatures(features);
        }
    );
  }
}