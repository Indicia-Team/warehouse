
//Need to store the parent feature from previous layers along the breadcrumb so we can reload the layer
//using this and the layerLocationTypes.
var parentFromPreviousBreadcrumbs  = [];
//Store the parent layers in an array for when the user clicks on the breadcrumb
var previousParentLayers = [];
//Need to store the number of the current layer level, so we can get the relevant item from layerLocationTypes
var currentLayerCounter = 0;
//When the user enters another page (such as Editing a Count Unit) then we pass the location ids in the 
//homepage breadcrumb so that new page can create a similar breadcrumb for returning to the homepage with
var breadcrumbIdsToPass;
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
  //Need seperate layer to display parent location feature as we don't want it clickable
  indiciaData.clickedParentLayer = new OpenLayers.Layer.Vector('Report output', {styleMap: s});
  mapInitialisationHooks.push(function (div) {
    "use strict";
    //Put into indicia data so we can see the map div elsewhere
    indiciaData.mapdiv = div;
    //If breadcrumb is specified in the URL, that we are going to preload the page with
    //a specific location zoomed, get the location ids we need for the breadcrumb
    var breadCrumbIds = [];
    if (indiciaData.preloadBreadcrumb) {
      breadCrumbIds = indiciaData.preloadBreadcrumb.split(',');
    }
    //Setup the initial map layer the user sees.
    //We initially don't have a parent, and the user hasn't clicked on the breadcrumb so these parameters are null.
    add_new_layer_for_site_hierarchy_navigator(null,null,false,breadCrumbIds);
  });  
});

/* 
 * Load the sub-locations onto the map when the user clicks on a location.
 */
//TODO, this function is not doing much at the moment, maybe remove
function reload_map_with_sub_sites_for_clicked_feature(features) {
  if (features.length>0 && indiciaData.layerLocationTypes.length > 0) {
    add_new_layer_for_site_hierarchy_navigator(features[0].id,null,false,'');
  }
}

/*
 * As the user clicks on features on the map, we need to draw new layers.
 * If they use the breadcrumb, the breadcrumbLayerCounter is supplied with the layer number.
 * If they click on a feature, then clickedFeature holds the details of the clicked locations (the parent).
 * Note on the first layer this is null as the user hasn't clicked on anything yet.
 */
function add_new_layer_for_site_hierarchy_navigator(clickedFeatureId,breadcrumbLayerCounter,fromSelectlist, breadCrumbIds) {
  clickedFeature = indiciaData.reportlayer.getFeatureById(clickedFeatureId);
  //Get id and name of the location clicked on or get previously stored parent details if user clicks on breadcrumb
  var parentIdAndName = get_parent_name_and_id(clickedFeature,breadcrumbLayerCounter);
  var parentId = parentIdAndName[0];
  var parentName = parentIdAndName[1];
  var reportRequest;
  //Link to Count unit Informatin sheet if we detect a Count Unit has been clicked on/selected
  if (clickedFeature&&clickedFeature.attributes.location_type_id==indiciaData.countUnitBoundaryTypeId) {
    location = indiciaData.countUnitPagePath+'location_id='+parentId+'&'+breadcrumbIdsToPass;
  } else {
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
          //Don't keep zooming once we reach bottom layer
          if (response.length>0 || clickedFeature) {
            var currentLayerObjectTypes = [];
            var features=[];    
            var existingBreadcrumb;
            var featureIds=[];          
            //Make nice names for the layers and add boundary geometry to map
            $.each(response, function (idx, obj) {
              //Make a distinct list of the location types being displayed on the current layer
              if (!inArray(obj.location_type_name,currentLayerObjectTypes)) {
                currentLayerObjectTypes.push(obj.location_type_name);
              }
              if (obj.boundary_geom) {
                indiciaData.mapdiv.addPt(features, obj, 'boundary_geom', {}, obj.id);
              } 
              else {
                indiciaData.mapdiv.addPt(features, obj, 'centroid_geom', {}, obj.id);
              }
              featureIds[idx] = obj.id;
            });
            var currentLayerObjectTypesString;
            var i;
            //Convert the list of location types displayed in the current layer into a comma seperated string
            for (i=0; i<currentLayerObjectTypes.length;i++) {
              if (i===0) {
                currentLayerObjectTypesString=currentLayerObjectTypes[i];
              }
              if (i!==0) {
                currentLayerObjectTypesString=currentLayerObjectTypesString+' '+currentLayerObjectTypes[i];
              }
              if (i!==currentLayerObjectTypes.length-1) {
                currentLayerObjectTypesString=currentLayerObjectTypesString+',';
              }
            }
            //Give the layer a name that includes the location types being shown and the parent name as applicable
            if (parentName && currentLayerObjectTypesString) {
              indiciaData.reportlayer.setName('Locations of type ' + currentLayerObjectTypesString + ' in ' + parentName);
            } else {
              if (currentLayerObjectTypesString) {
                indiciaData.reportlayer.setName('Locations of type ' + currentLayerObjectTypesString);
              }
              if (parentName) {
                indiciaData.reportlayer.setName('Viewing location ' + parentName);
              }
            }
            //make the breadcrumb options we can give to another page by storing up the location ids
            if (indiciaData.useBreadCrumb) {
              if (clickedFeatureId) {
                if (breadcrumbIdsToPass) {
                  breadcrumbIdsToPass = breadcrumbIdsToPass + ',' + clickedFeatureId;
                } else {
                  breadcrumbIdsToPass = 'breadcrumb='+clickedFeatureId;
                }
              }
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
            //Get the Edit Site button
            if (indiciaData.useEditSite) {
              edit_site_link(indiciaData.layerLocationTypes[currentLayerCounter],parentId,parentName);
            }
            //The following is performed when the user clicks back on the breadcrumb, the main difference is the parent layer
            //is collected from the array of parent layers we have built.
            if (previousParentLayers[breadcrumbLayerCounter-1]) { 
              //Add seperate layer for parent location as it isn't a clickable layer 
              indiciaData.clickedParentLayer.removeAllFeatures();
              indiciaData.clickedParentLayer = previousParentLayers[breadcrumbLayerCounter-1].clone();
              indiciaData.clickedParentLayer.setName(indiciaData.clickedParentLayer.id)
              indiciaData.mapdiv.map.addLayer(indiciaData.clickedParentLayer);
              
              indiciaData.reportlayer.removeAllFeatures();
              indiciaData.mapdiv.map.addLayer(indiciaData.reportlayer);
              indiciaData.reportlayer.addFeatures(features); 
              //The following is performed if the map is drawn without the user clicking on the breadcrumb.
              //This is when the map is drawn for first time, or when user has clicked on a feature.
            } else {
              indiciaData.reportlayer.removeAllFeatures();
              indiciaData.clickedParentLayer.removeAllFeatures();
              indiciaData.mapdiv.map.addLayer(indiciaData.reportlayer);
              indiciaData.reportlayer.addFeatures(features); 
              //Add seperate layer for parent location as it isn't a clickable layer
              //The parent layer is only drawn after the user has clicked on a feature, not when map is first drawn.
              if (clickedFeature) {  
                indiciaData.clickedParentLayer.setName(clickedFeature.id)
                indiciaData.mapdiv.map.addLayer(indiciaData.clickedParentLayer);
                indiciaData.clickedParentLayer.addFeatures(clickedFeature); 
                //When we click through the layers, we hold a copy of the parent feature layer for user in the breadcrumb.
                //We only do this if we not already saved a copy (for instance we might already has saved it if the user
                //clicks back on the breadcrumb)
                if (!previousParentLayers[currentLayerCounter-1]) {
                  previousParentLayers.push(indiciaData.clickedParentLayer.clone());
                }
              }
            }
            //When we come back to the page from a breadcrumb on another page, we rebuild the breadcrumb as if the user
            //had been clicking on the map several times, however we only want to draw the map on the last step of rebuilding the breadcrumb
            //otherwise we lose performance.
            if (!(indiciaData.preloadBreadcrumb && currentLayerCounter<breadCrumbIds.length)) {
              //We need to zoom using both the parent feature and the child features
              var featuresToZoom = [];
              featuresToZoom = features;
              //If the user clicks on a feature, that feature becomes the parent feature to display, so we need to include it when zooming.
              if (clickedFeature) {
                featuresToZoom.push(clickedFeature);
              }
              //If the user clicks select a feature from the breadcrumb, that feature becomes the parent feature to display, so we need to include it when zooming.
              if (previousParentLayers[breadcrumbLayerCounter-1]) {
                featuresToZoom.push(indiciaData.clickedParentLayer.features[0]);
              }
              zoom_to_area(featuresToZoom);
            }
            currentLayerCounter++;
          }
          //If the user is returning from another page, they will have specified a location to zoom to.
          //Auto loop so we can give them the page in the same state that they left it in
          if (indiciaData.preloadBreadcrumb && currentLayerCounter<breadCrumbIds.length+1) {
            add_new_layer_for_site_hierarchy_navigator(breadCrumbIds[currentLayerCounter-1],null,false,breadCrumbIds);
          }
        }
    );
  }
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
  //If the user hasn't clicked on the breadcrumb then we are either on first tab or they have clicked on a location.
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
    $('#map-breadcrumb').html(breadcrumbPartFront + "<a onclick='add_new_layer_for_site_hierarchy_navigator(null,"+currentLayerCounter+",false,\"\")'>"+ indiciaData.reportlayer.name + "</a></li>");
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
    if (feature.id !== indiciaData.countUnitBoundaryTypeId) {
      selectListOptions += '<option value="'+feature.attributes.name+'" onclick="add_new_layer_for_site_hierarchy_navigator('+feature.id+', null,true,null)">'+feature.attributes.name+'</option>';
    }
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
                      ONCLICK=\"window.location.href='"+indiciaData.addSiteLinkUrls[i]+parentLocationId+'&location_type_id='+currentSiteType+"'\">";
        button += '</FORM>'; 
        return $('#map-addsite').html(button);
      }
    }
  }
  return $('#map-addsite').html('');
}

/*
 * Allow users to edit sites. When button is displayed, it allows the user to edit the parent site.
 */
function edit_site_link(currentSiteType, parentSiteId, parentSiteName) {
  if (parentSiteId) {
    for (i=0; i<indiciaData.locationTypesForEditSites.length;i++) {
      if (indiciaData.locationTypesForEditSites[i] === currentSiteType) {
        button = '<FORM>';
        button += "<INPUT TYPE=\"button\" VALUE=\"Edit "+parentSiteName+"\"\n\
                      ONCLICK=\"window.location.href='"+indiciaData.editSiteLinkUrls[i]+parentSiteId+"'\">";
        button += '</FORM>'; 
        return $('#map-editsite').html(button);
      }
    }
  }
  return $('#map-editsite').html('');
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
