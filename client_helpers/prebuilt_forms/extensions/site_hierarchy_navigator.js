
jQuery(document).ready(function($) {
  indiciaData.reportlayer = new OpenLayers.Layer.Vector('Report output');
  mapInitialisationHooks.push(function (div) {
    "use strict";
    //Put into indicia data so we can see the map div elsewhere
    indiciaData.mapdiv = div;
    //Setup the initial map layer the user sees.
    add_new_layer_for_site_hierarchy_navigator(null,null);
  });  
});

/* 
 * Load the sub-locations onto the map when the user clicks on a location.
 * To do this we need to get the parent location type and name.
 * Also get the type of the locations we are loading so the layer can have 
 * an appropriate name, then we call the method to draw the new layer.
 */
function reload_map_with_sub_sites_for_clicked_feature(features) {
  var parentId, parentName,locationTypeName;
  $.each(features, function(idx, feature) {
    parentId = feature.id;
    parentName = feature.attributes.name;
    locationTypeName = feature.attributes.location_type_name;
  });
  add_new_layer_for_site_hierarchy_navigator(parentId,parentName);
}

function add_new_layer_for_site_hierarchy_navigator(parentId,parentName) {
  if (indiciaData.layerLocationTypes.length > 0) {
    //Get the locations for the next location type and the clicked location.
    $.getJSON(indiciaData.layerReportRequest + '&location_type_id='+indiciaData.layerLocationTypes.shift()+ '&parent_id='+parentId,
        null,
        function(response, textStatus, jqXHR) { 
          var currentLayerObjectType;
          var features=[];       
          $.each(response, function (idx, obj) {
            currentLayerObjectType = obj.location_type_name;
            indiciaData.mapdiv.addPt(features, obj, 'boundary_geom', {"type":"vector"}, obj.id);
          });
          //Give the layer a name that includes the location type being shown and the parent name
          if (parentId!==null) {
            indiciaData.reportlayer = new OpenLayers.Layer.Vector('Locations of type ' + currentLayerObjectType+ ' in ' + parentName);
          }
          indiciaData.mapdiv.map.addLayer(indiciaData.reportlayer);
          indiciaData.reportlayer.addFeatures(features);
        }
    );
  }
}

