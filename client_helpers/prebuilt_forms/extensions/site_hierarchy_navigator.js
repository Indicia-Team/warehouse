
//Need to store the parent feature from previous layers along the breadcrumb so we can reload the layer
//using this and the layerLocationTypes.
var parentFromPreviousBreadcrumbs  = [];
//Need to store the number of the current layer level, so we can get the relevant item from layerLocationTypes
var currentLayerCounter = 0;
jQuery(document).ready(function($) {
  //setup default styling for the feature points. The type of icon to show is supplied in the report
  //in a column called 'graphic'.
  var s = new OpenLayers.StyleMap({
    'pointRadius': 15,
    'graphicName': '${graphic}',
    'fillColor': '#ee9900',
    'fillOpacity': 0.4,
    'strokeColor': '#ee9900',
    'strokeOpacity': 1,
    'strokeWidth': 1
  });
  indiciaData.reportlayer = new OpenLayers.Layer.Vector('Report output', {styleMap: s});
  mapInitialisationHooks.push(function (div) {
    "use strict";
    //Put into indicia data so we can see the map div elsewhere
    indiciaData.mapdiv = div;
    //Setup the initial map layer the user sees.
    //We initially don't have a parent, and the user hasn't clicked on the breadcrumb so these parameters are null.
    add_new_layer_for_site_hierarchy_navigator(null,null,false);
  });  
});

/* 
 * Load the sub-locations onto the map when the user clicks on a location.
 */
//TODO, this function is not doing much at the moment, maybe remove
function reload_map_with_sub_sites_for_clicked_feature(features) {
  if (features.length>0 && indiciaData.layerLocationTypes.length > 0) {
    add_new_layer_for_site_hierarchy_navigator(features[0].id,null,false);
  }
}

/*
 * As the user clicks on features on the map, we need to draw new layers.
 * If they use the breadcrumb, the breadcrumbLayerCounter is supplied with the layer number.
 * If they click on a feature, then clickedFeature holds the details of the clicked locations (the parent).
 * Note on the first layer this is null as the user hasn't clicked on anything yet.
 */
function add_new_layer_for_site_hierarchy_navigator(clickedFeatureId,breadcrumbLayerCounter,fromSelectlist) {
  clickedFeature = indiciaData.reportlayer.getFeatureById(clickedFeatureId);
  //Get id and name of the location clicked on or get previously stored parent details if user clicks on breadcrumb
  var parentIdAndName = get_parent_name_and_id(clickedFeature,breadcrumbLayerCounter);
  var parentId = parentIdAndName[0];
  var parentName = parentIdAndName[1];
  var reportRequest;
  //If the user has specified this layer must also display count units, then add them to the report parameters
  if (inArray(indiciaData.layerLocationTypes[currentLayerCounter],indiciaData.showCountUnitsForLayers)) {
    reportRequest = indiciaData.layerReportRequest + '&location_type_id='+indiciaData.layerLocationTypes[currentLayerCounter]+','+indiciaData.layerLocationTypes[indiciaData.layerLocationTypes.length-1]+ '&parent_id='+parentId;
  } else {
    reportRequest = indiciaData.layerReportRequest + '&location_type_id='+indiciaData.layerLocationTypes[currentLayerCounter]+'&parent_id='+parentId;
  }
  
  //Get the locations for the next location type in the clicked location.
  $.getJSON(reportRequest,
      null,
      function(response, textStatus, jqXHR) { 
        if (response.length>0) {
          var currentLayerObjectType;
          var features=[];    
          var existingBreadcrumb;
          var featureIds=[];
          //If the user options tell the system to use symbols instead boundaries, then we plot the centroid instead of boundary
          if (inArray(indiciaData.layerLocationTypes[currentLayerCounter],indiciaData.locationTypesWithSymbols)) {
            //Make nice names for the layers and add centroid geometry to map
            $.each(response, function (idx, obj) {
              currentLayerObjectType = obj.location_type_name;
              indiciaData.mapdiv.addPt(features, obj, 'centroid_geom', {}, obj.id);
              featureIds[idx] = obj.id;
            });
          } else {
            //Make nice names for the layers and add boundary geometry to map
            $.each(response, function (idx, obj) {
              currentLayerObjectType = obj.location_type_name;
              indiciaData.mapdiv.addPt(features, obj, 'boundary_geom', {}, obj.id);
              featureIds[idx] = obj.id;
            });
          }
          //Give the layer a name that includes the location type being shown and the parent name
          if (parentName!==null) {
            indiciaData.reportlayer.setName('Locations of type ' + currentLayerObjectType+ ' in ' + parentName);
          } else {
            indiciaData.reportlayer.setName('Locations of type ' + currentLayerObjectType);
          }
          //make the breadcrumb
          if (indiciaData.useBreadCrumb) {
            breadcrumb(breadcrumbLayerCounter,currentLayerCounter,parentId,parentName);
          }
          //make the select list
          if (indiciaData.useSelectList) {
            selectlist(features);
          }
          //Get the link to report button
          if (indiciaData.useListReportLink) {
            list_report_link(indiciaData.layerLocationTypes[currentLayerCounter],parentId, parentName);
          }     
          //Get the Add Count Unit button
          if (indiciaData.useAddCountUnit) {
            add_count_unit_link(indiciaData.layerLocationTypes[currentLayerCounter],parentId);
          }
          //Get the Add Site button
          if (indiciaData.useAddSite) {
            add_site_link(indiciaData.layerLocationTypes[currentLayerCounter],parentId);
          }
          indiciaData.reportlayer.removeAllFeatures();
          indiciaData.mapdiv.map.addLayer(indiciaData.reportlayer);
          indiciaData.reportlayer.addFeatures(features); 
          zoom_to_area(features);
          currentLayerCounter++;
        } else {
          if (fromSelectlist===true) {
            alert('The selected location does not have any data to display');
          }
        }
      }
  );
}

/*
 * Get id and name of the location clicked on or get previously stored parent details if user clicks on breadcrumb
 */
function get_parent_name_and_id(clickedFeature,breadcrumbLayerCounter) {
  result = [];
  var i;
  //If user clicks on breadcrumb, get previously stored details. Also remove breadcrumb elements from layers beyond this point
  if (breadcrumbLayerCounter!=null) {
    parentId = parentFromPreviousBreadcrumbs[breadcrumbLayerCounter]['id'];
    parentName = parentFromPreviousBreadcrumbs[breadcrumbLayerCounter]['name'];
    if (indiciaData.useBreadCrumb) {
      for (i=breadcrumbLayerCounter+1;i<=currentLayerCounter; i++) {
        $('#breadcrumb-part-'+i).remove();
        delete parentFromPreviousBreadcrumbs[i];
      }
    }
    currentLayerCounter = breadcrumbLayerCounter; 
  } else {
    //if user hasn't clicked on the breadcrumb then they have either clicked on a location so 
    //we need to get the clicked location as the parent.
    //Otherwise we are working on the page when the page first opens so we have no parent details
    if (clickedFeature!=null) {
      parentId = clickedFeature.id;
      parentName = clickedFeature.attributes.name;
    } else {
      parentId = null;
      parentName = null;
    }
  }
  result[0] = parentId;
  result[1] = parentName;
  return result;
}

/*
 * Make the breadcrumb
 */
function breadcrumb(breadcrumbLayerCounter,currentLayerCounter,parentId,parentName) {
  var existingBreadcrumb;
  existingBreadcrumb = $('#map-breadcrumb').html();
  //If the user hasn't clicked on the breadcrumb then we are either on firt tab or they have clicked on a location.
  //So we need to store the parent for use in the breadcrumb.
  if (breadcrumbLayerCounter==null) { 
    parentFromPreviousBreadcrumbs [currentLayerCounter]=[];
    parentFromPreviousBreadcrumbs [currentLayerCounter]['id']=parentId;
    parentFromPreviousBreadcrumbs [currentLayerCounter]['name']=parentName;
    //If there is an existing bredcrumb, we need don't want to lose it when drawing the breadcrumb
    if (existingBreadcrumb) {
      breadcrumbPartFront = existingBreadcrumb + '<li id="breadcrumb-part-'+currentLayerCounter+'">';
    } else {
      breadcrumbPartFront = '<li id = "breadcrumb-part-'+currentLayerCounter+'">';
    }
    $('#map-breadcrumb').html(breadcrumbPartFront + "<a onclick='add_new_layer_for_site_hierarchy_navigator(null,"+currentLayerCounter+",false)'>"+ indiciaData.reportlayer.name + "</a></li>");
  }
}

/*
 * When the user moves layer, zoom the map.
 * This involves finding the most northerly, southerly, easterly and westerly boundaries of all the 
 * locations we are drawing as a whole.
 */
function zoom_to_area(features) {
  var bounds = new OpenLayers.Bounds();
  var boundsOfAllObjects = new OpenLayers.Bounds();
  //For each location
  $.each(features, function (idx, feature) {
    bounds = feature.geometry.getBounds()
    //Store the left most boundary if it is further west than current stored furthest west boundary
    if (!boundsOfAllObjects.left || boundsOfAllObjects.left > bounds.left) {
      boundsOfAllObjects.left = bounds.left;
    }
    //As above for south
    if (!boundsOfAllObjects.bottom || boundsOfAllObjects.bottom > bounds.bottom) {
      boundsOfAllObjects.bottom = bounds.bottom;
    }
    //As above for east
    if (!boundsOfAllObjects.right || boundsOfAllObjects.right < bounds.right) {
      boundsOfAllObjects.right = bounds.right;
    }
    //As above for north
    if (!boundsOfAllObjects.top || boundsOfAllObjects.top < bounds.top) {
      boundsOfAllObjects.top = bounds.top;
  }   
  });
  //Zoom and center
  if (indiciaData.mapdiv.map.getZoomForExtent(bounds) > indiciaData.mapdiv.settings.maxZoom) {
    //if showing something small, don't zoom in too far
    indiciaData.mapdiv.map.setCenter(bounds.getCenterLonLat(), div.settings.maxZoom);
  } else {
    zoom = indiciaData.mapdiv.map.getZoomForExtent(boundsOfAllObjects);
    indiciaData.mapdiv.map.setCenter(boundsOfAllObjects.getCenterLonLat(), zoom); 
  }
}

/*
 * A select list that displays the same locations as on the map. Selecting a location
 * from the select list zooms in the same way map clicking does.
 */
function selectlist(features) {
  var selectListOptions;
  selectListOptions += '<option value="">Please select a location</option>';
  $.each(features, function (idx, feature) {
    selectListOptions += '<option value="'+feature.attributes.name+'" onclick="add_new_layer_for_site_hierarchy_navigator('+feature.id+', null,true)">'+feature.attributes.name+'</option>';
  });
  $('#map-selectlist').html(selectListOptions)
}

/*
 * A control where we construct a button linking to a report page whose path and parameter are as per administrator supplied options.
 * The options format is comma seperated where the format of the elements is "location_type_id|report_path|report_parameter".
 * If an option is not found for the displayed layer's location type, then the report link button is hidden from view.
 */
function list_report_link(currentSiteType,parentId, parentName) {
  //construct a comma seperated list of location ids shown on the current layer 
  //which is then put in the report parameter in the url.
  //If the current layer location type is in the administrator specified options list, then we know to draw the report button
  for (i=0; i<indiciaData.locationTypesForListReport.length;i++) {
    if (indiciaData.locationTypesForListReport[i] === currentSiteType) {
      button = '<FORM>';
      button += "<INPUT TYPE=\"button\" VALUE=\"View Sites and Count Units Within "+parentName+"\"\n\
                    ONCLICK=\"window.location.href='"+indiciaData.reportLinkUrls[i]+parentId+"'\">";
      button += '</FORM>'; 
      return $('#map-listreportlink').html(button);
    }
  }
  return $('#map-listreportlink').html('');
}

/*
 * Control button that takes user to Add Count Unit page whose path and parameter are as per administrator supplied options.
 * The parameter is used to automatically zoom the map to the area we want to add the count unit.
 * The options format is comma seperated where the format of the elements is "location_type_id|page_path|parameter_name".
 * If an option is not found for the displayed layer's location type, then the Add Count Unit button is hidden from view.
 */
function add_count_unit_link(currentSiteType,parentLocationId) {
  if (parentLocationId) {
    //If the current layer location type is in the administrator specified options list, then we know to draw the add count unit button
    for (i=0; i<indiciaData.locationTypesForAddCountUnits.length;i++) {
      if (indiciaData.locationTypesForAddCountUnits[i] === currentSiteType) {
        button = '<FORM>';
        button += "<INPUT TYPE=\"button\" VALUE=\"Add Count Unit\"\n\
                      ONCLICK=\"window.location.href='"+indiciaData.addCountUnitLinkUrls[i]+parentLocationId+"'\">";
        button += '</FORM>'; 
        return $('#map-addcountunit').html(button);
      }
    }
  }
  return $('#map-addcountunit').html('');
}

/*
 * Control button that takes user to Add Site page whose path and parameter are as per administrator supplied options.
 * The parameter is used to automatically zoom the map to the region/site we want to add the new site to.
 * The options format is comma seperated where the format of the elements is "location_type_id|page_path|parameter_name".
 * If an option is not found for the displayed layer's location type, then the Add Site button is hidden from view.
 */
function add_site_link(currentSiteType,parentLocationId) {
  if (parentLocationId) {
    //If the current layer location type is in the administrator specified options list, then we know to draw the add site button
    for (i=0; i<indiciaData.locationTypesForAddSites.length;i++) {
      if (indiciaData.locationTypesForAddSites[i] === currentSiteType) {
        button = '<FORM>';
        button += "<INPUT TYPE=\"button\" VALUE=\"Add Site\"\n\
                      ONCLICK=\"window.location.href='"+indiciaData.addSiteLinkUrls[i]+parentLocationId+"'\">";
        button += '</FORM>'; 
        return $('#map-addsite').html(button);
      }
    }
  }
  return $('#map-addsite').html('');
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
